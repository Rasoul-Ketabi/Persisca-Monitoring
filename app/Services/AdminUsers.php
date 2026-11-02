<?php
declare(strict_types=1);
namespace Monitor\Services;
use Monitor\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

/** Account operations require Super Admin route authorization before use. */
final class AdminUsers {
    public static function all(): array { return Database::connection()->query('SELECT id,name,email,role,status,last_login_at,created_at FROM users ORDER BY id ASC LIMIT 300')->fetchAll(PDO::FETCH_ASSOC); }
    public static function find(int $id): ?array {
        $q=Database::connection()->prepare('SELECT id,name,email,role,status,last_login_at,created_at FROM users WHERE id=?');$q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public static function validated(array $input, bool $new=false): array {
        $name=trim((string)($input['name']??'')); $email=strtolower(trim((string)($input['email']??'')));
        $role=(string)($input['role']??'');$status=(string)($input['status']??'active');
        if ($name==='' || strlen($name)>140) { throw new RuntimeException('Enter a name up to 140 characters.'); }
        if (strlen($email)>190 || !filter_var($email,FILTER_VALIDATE_EMAIL)) { throw new RuntimeException('Enter a valid email address.'); }
        if (!in_array($role,['admin','super_admin'],true)) { throw new RuntimeException('Select a supported role.'); }
        if (!in_array($status,['active','disabled'],true)) { throw new RuntimeException('Select a supported status.'); }
        if ($new) { self::password((string)($input['password']??''),(string)($input['confirm_password']??'')); }
        return compact('name','email','role','status');
    }
    public static function password(string $password,string $confirm): void {
        if (strlen($password)<8 || strlen($password)>256 || !hash_equals($password,$confirm)) { throw new RuntimeException('Use matching passwords of at least 8 characters.'); }
    }
    public static function create(array $input,int $actor): void {
        $v=self::validated($input,true);$hash=password_hash((string)$input['password'],PASSWORD_DEFAULT);
        try {
            $pdo=Database::connection();$q=$pdo->prepare('INSERT INTO users (name,email,password_hash,role,status,session_version,created_at,updated_at) VALUES (?,?,?,?,?,1,UTC_TIMESTAMP(),UTC_TIMESTAMP())');
            $q->execute([$v['name'],$v['email'],$hash,$v['role'],$v['status']]);
            Maintenance::audit($actor,'user_created','Administrator account created.');
        } catch (\PDOException $e) { if ($e->getCode()==='23000') { throw new RuntimeException('An administrator with that email already exists.'); } throw $e; }
    }
    public static function update(int $id,array $input,int $actor): void {
        $v=self::validated($input);
        $pdo=Database::connection(); $pdo->beginTransaction();
        try {
            // Serialize privilege edits against other account edits on this installation.
            $pdo->query('SELECT id FROM users ORDER BY id FOR UPDATE')->fetchAll(PDO::FETCH_COLUMN);
            $old=self::find($id);if (!$old) { throw new RuntimeException('Administrator not found.'); }
            if ($id===$actor && ($v['status']!=='active'||$v['role']!=='super_admin')) { throw new RuntimeException('You cannot disable or demote your own Super Admin account.'); }
            if ($old['role']==='super_admin' && $old['status']==='active' && ($v['role']!=='super_admin'||$v['status']!=='active')) {
                $active=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='super_admin' AND status='active'")->fetchColumn();
                if ($active<=1) { throw new RuntimeException('The last active Super Admin cannot be disabled or demoted.'); }
            }
            $q=$pdo->prepare('UPDATE users SET session_version=session_version+IF(role<>? OR status<>?,1,0),name=?,email=?,role=?,status=?,updated_at=UTC_TIMESTAMP() WHERE id=?');
            $q->execute([$v['role'],$v['status'],$v['name'],$v['email'],$v['role'],$v['status'],$id]);
            $pdo->commit();
            Maintenance::audit($actor,'user_updated','Administrator details or access updated.');
        } catch (Throwable $e) { if ($pdo->inTransaction()) {$pdo->rollBack();} if ($e instanceof \PDOException && $e->getCode()==='23000') {throw new RuntimeException('An administrator with that email already exists.');} throw $e; }
    }
    public static function resetPassword(int $id,string $password,string $confirm,int $actor): void {
        self::password($password,$confirm);$hash=password_hash($password,PASSWORD_DEFAULT);
        $q=Database::connection()->prepare('UPDATE users SET password_hash=?,session_version=session_version+1,updated_at=UTC_TIMESTAMP() WHERE id=?');$q->execute([$hash,$id]);
        if (!$q->rowCount()) {throw new RuntimeException('Administrator not found.');}
        Maintenance::audit($actor,'password_reset','Administrator password reset.');
    }
    public static function selfPassword(int $id,string $current,string $new,string $confirm): void {
        self::password($new,$confirm);
        $pdo=Database::connection();$q=$pdo->prepare("SELECT password_hash,session_version FROM users WHERE id=? AND status='active'");$q->execute([$id]);$row=$q->fetch(PDO::FETCH_ASSOC);
        if (!$row || !password_verify($current,$row['password_hash'])) {throw new RuntimeException('Current password is incorrect.');}
        $hash=password_hash($new,PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password_hash=?,session_version=session_version+1,updated_at=UTC_TIMESTAMP() WHERE id=?')->execute([$hash,$id]);
        $_SESSION['admin_session_version']=(int)$row['session_version']+1;
        session_regenerate_id(true);
        $_SESSION['admin_last_rotation']=time();
        Auth::clearCachedUser();
        Maintenance::audit($id,'password_changed','Account password changed.');
    }
}
