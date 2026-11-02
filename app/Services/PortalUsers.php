<?php
declare(strict_types=1);
namespace Monitor\Services;

use Monitor\Core\Database;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class PortalUsers {
    /** Slugs are admin-managed; an active, published account is required to resolve publicly. */
    private const RESERVED = ['admin','account','login','assets','api','incidents','notifications','telegram','webhook','status','public','install','logout','verify','monitors','settings','system','maintenance','users','cron','dashboard'];

    public static function isValidSlug(string $slug): bool {
        return strlen($slug) <= 120 && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug) === 1
            && !in_array($slug, self::RESERVED, true);
    }
    public static function validateSlug(string $slug): string {
        if (!self::isValidSlug($slug)) {
            throw new RuntimeException('Use a lowercase URL slug containing only letters, numbers and hyphens (maximum 120 characters). Reserved slugs are not allowed.');
        }
        return $slug;
    }
    public static function slugFromName(string $name): string {
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$name);
            if ($converted !== false) { $name = $converted; }
        }
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-');
        if ($slug === '' || !self::isValidSlug(substr($slug,0,120))) { return 'user'; }
        return trim(substr($slug,0,120), '-');
    }
    public static function publicBySlug(string $slug): ?array {
        if (!self::isValidSlug($slug)) { return null; }
        $q=Database::connection()->prepare("SELECT id,name,public_slug,status_page_title FROM portal_users WHERE public_slug=? AND public_status_enabled=1 AND status='active' LIMIT 1");
        $q->execute([$slug]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
    }
    public static function setPublication(int $id, bool $enabled): void {
        $q=Database::connection()->prepare("UPDATE portal_users SET public_status_enabled=?,updated_at=UTC_TIMESTAMP() WHERE id=? AND status='active'");
        $q->execute([$enabled ? 1 : 0,$id]);
        // The portal session is already checked for an active user by PortalAuth.
    }
    public static function all(): array {
        return Database::connection()->query('SELECT p.id,p.name,p.email,p.status,p.monitor_limit,p.last_login_at,p.created_at,p.public_slug,p.public_status_enabled,COUNT(m.id) AS monitor_count FROM portal_users p LEFT JOIN monitors m ON m.portal_user_id=p.id GROUP BY p.id,p.name,p.email,p.status,p.monitor_limit,p.last_login_at,p.created_at,p.public_slug,p.public_status_enabled ORDER BY p.created_at DESC,p.id DESC LIMIT 500')->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function find(int $id): ?array {
        $q=Database::connection()->prepare('SELECT p.*, (SELECT COUNT(*) FROM monitors m WHERE m.portal_user_id=p.id) AS monitor_count FROM portal_users p WHERE p.id=? LIMIT 1');
        $q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public static function validated(array $post): array {
        $name=trim((string)($post['name']??''));$email=strtolower(trim((string)($post['email']??'')));
        $status=(string)($post['status']??'active');$limit=(string)($post['monitor_limit']??'');
        if ($name===''||strlen($name)>140) {throw new RuntimeException('Enter a name up to 140 characters.');}
        if (strlen($email)>190||!filter_var($email,FILTER_VALIDATE_EMAIL)) {throw new RuntimeException('Enter a valid email address.');}
        if (!in_array($status,['active','disabled'],true)) {throw new RuntimeException('Choose an account status.');}
        if (!preg_match('/^(0|[1-9][0-9]{0,5})$/D',$limit)|| (int)$limit>100000) {throw new RuntimeException('Monitor limit must be between 0 and 100000.');}
        return ['name'=>$name,'email'=>$email,'status'=>$status,'monitor_limit'=>(int)$limit];
    }
    private static function publicationData(array $post, string $name, ?string $oldSlug=null): array {
        $slug = trim((string)($post['public_slug'] ?? ''));
        $slug = $slug !== '' ? self::validateSlug($slug) : ($oldSlug ?: self::slugFromName($name));
        $title = trim((string)($post['status_page_title'] ?? ''));
        if (strlen($title) > 190 || strpbrk($title,"\r\n") !== false) { throw new RuntimeException('Page title must be one line and no longer than 190 characters.'); }
        return [$slug, $title ?: null, isset($post['public_status_enabled']) ? 1 : 0];
    }
    public static function create(array $post): int {
        $v=self::validated($post);
        [$slug,$title,$enabled]=self::publicationData($post,$v['name']);
        $pdo=Database::connection();$pdo->beginTransaction();
        try {
            // Insert first to obtain an immutable ID for collision-safe generated suffixes.
            $q=$pdo->prepare('INSERT INTO portal_users(name,email,status,monitor_limit,session_version,public_slug,public_status_enabled,status_page_title,created_at,updated_at) VALUES(?,?,?,?,1,?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())');
            $q->execute([$v['name'],$v['email'],$v['status'],$v['monitor_limit'],'temp-'.bin2hex(random_bytes(12)),$enabled,$title]);
            $id=(int)$pdo->lastInsertId();
            if (trim((string)($post['public_slug']??''))==='') {
                $check=$pdo->prepare('SELECT id FROM portal_users WHERE public_slug=? LIMIT 1');
                $check->execute([$slug]);
                if($check->fetchColumn() !== false) { $slug=substr($slug,0,109).'-'.$id; }
            }
            try {
                $pdo->prepare('UPDATE portal_users SET public_slug=? WHERE id=?')->execute([$slug,$id]);
            } catch(PDOException $collision) {
                // A concurrent account creation can choose the same name. The
                // primary key suffix is deterministic, unique and race-safe.
                if ($collision->getCode() !== '23000' || trim((string)($post['public_slug']??'')) !== '') { throw $collision; }
                $slug=substr(self::slugFromName($v['name']),0,109).'-'.$id;
                $pdo->prepare('UPDATE portal_users SET public_slug=? WHERE id=?')->execute([$slug,$id]);
            }
            $pdo->commit();return $id;
        } catch(PDOException $e) {
            if($pdo->inTransaction()) {$pdo->rollBack();}
            if($e->getCode()==='23000') {throw new RuntimeException('That portal email or public slug is already in use.');}
            throw $e;
        } catch(Throwable $e) {if($pdo->inTransaction()) {$pdo->rollBack();}throw $e;}
    }
    public static function update(int $id,array $post): void {
        $v=self::validated($post);
        $old=self::find($id);
        if(!$old){throw new RuntimeException('Portal user not found.');}
        [$slug,$title,$enabled]=self::publicationData($post,$v['name'],(string)$old['public_slug']);
        try {
            $q=Database::connection()->prepare('UPDATE portal_users SET name=?,email=?,status=?,monitor_limit=?,public_slug=?,public_status_enabled=?,status_page_title=?,session_version=session_version+IF(status<>?,1,0),updated_at=UTC_TIMESTAMP() WHERE id=?');
            $q->execute([$v['name'],$v['email'],$v['status'],$v['monitor_limit'],$slug,$enabled,$title,$v['status'],$id]);
        } catch(PDOException $e) {if($e->getCode()==='23000') {throw new RuntimeException('That portal email or public slug is already in use.');}throw $e;}
    }
    public static function owned(int $id): array {
        $q=Database::connection()->prepare('SELECT id,name,url,operational_status,is_active,is_public,last_checked_at FROM monitors WHERE portal_user_id=? ORDER BY created_at DESC,id DESC LIMIT 500');
        $q->execute([$id]);return $q->fetchAll(PDO::FETCH_ASSOC);
    }
}
