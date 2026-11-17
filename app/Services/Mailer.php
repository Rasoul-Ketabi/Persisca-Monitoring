<?php
declare(strict_types=1);
namespace Monitor\Services;
use Monitor\Models\Settings;

/** Single transactional mailer for portal OTP and Super Admin delivery checks. */
final class Mailer {
    /** Settings validation does not perform network requests. Blank password retains the existing secret. */
    public static function validateSettings(array $input, array $existing): array {
        $transport=(string)($input['mail_transport']??'php_mail');
        $host=trim((string)($input['smtp_host']??''));
        $port=(string)($input['smtp_port']??'587');
        $encryption=(string)($input['smtp_encryption']??'tls');
        $auth=isset($input['smtp_auth_enabled'])?'1':'0';
        $username=(string)($input['smtp_username']??'');
        $password=(string)($input['smtp_password']??'');
        $clear=isset($input['smtp_password_clear']);
        $from=trim((string)($input['mail_from_email']??''));
        $name=trim((string)($input['mail_from_name']??''));
        $reply=trim((string)($input['mail_reply_to']??''));
        $timeout=(string)($input['smtp_timeout']??'15');
        if (!in_array($transport,['smtp','php_mail'],true)) {throw new \RuntimeException('Choose a supported mail transport.');}
        if ($host!=='' && !self::validHost($host)) {throw new \RuntimeException('Enter a valid SMTP host name or IP address.');}
        if (!ctype_digit($port)||(int)$port<1||(int)$port>65535) {throw new \RuntimeException('SMTP port must be between 1 and 65535.');}
        if (!in_array($encryption,['tls','ssl','none'],true)) {throw new \RuntimeException('Choose a supported SMTP encryption mode.');}
        if (!ctype_digit($timeout)||(int)$timeout<3||(int)$timeout>60) {throw new \RuntimeException('Connection timeout must be between 3 and 60 seconds.');}
        if (strlen($username)>254 || preg_match('/[\x00-\x1F\x7F]/',$username)) {throw new \RuntimeException('Check the SMTP username.');}
        if (strlen($password)>1024 || preg_match('/[\x00\r\n]/',$password)) {throw new \RuntimeException('Check the new SMTP password.');}
        if ($clear&&$password!=='') {throw new \RuntimeException('Choose either a new SMTP password or removal.');}
        if ($clear && (string)($input['confirm_password_clear']??'')!=='yes') {throw new \RuntimeException('Confirm removing the SMTP password.');}
        if ($from!==''&&!self::validAddress($from)) {throw new \RuntimeException('Enter a valid From Email address.');}
        if ($reply!==''&&!self::validAddress($reply)) {throw new \RuntimeException('Enter a valid Reply-To Email address.');}
        if (strlen($name)>120 || preg_match('/[\x00-\x1F\x7F]/',$name)) {throw new \RuntimeException('From Name must be one line, up to 120 characters.');}
        if ($name==='') { $name=Settings::get('app_name','Monitor'); }
        $savedPassword=$clear?'':($password!==''?$password:(string)($existing['smtp_password']??''));
        if ($transport==='smtp') {
            if ($host==='') {throw new \RuntimeException('SMTP host is required when SMTP is selected.');}
            if ($from==='') {throw new \RuntimeException('From Email is required when SMTP is selected.');}
            if ($auth==='1' && ($username==='' || $savedPassword==='')) {throw new \RuntimeException('SMTP authentication requires a username and saved password.');}
            if ($auth==='1' && $encryption==='none') {throw new \RuntimeException('SMTP authentication requires STARTTLS or SSL/TLS.');}
        }
        $values=['mail_transport'=>$transport,'smtp_host'=>$host,'smtp_port'=>(string)(int)$port,
            'smtp_encryption'=>$encryption,'smtp_auth_enabled'=>$auth,'smtp_username'=>$username,
            'mail_from_email'=>$from,'mail_from_name'=>$name,'mail_reply_to'=>$reply,'smtp_timeout'=>(string)(int)$timeout];
        if ($clear||$password!=='') { $values['smtp_password']=$savedPassword; }
        return $values;
    }

    public static function validHost(string $host): bool {
        return strlen($host)>0 && strlen($host)<=253 && !str_contains($host,'..') &&
            (filter_var($host, FILTER_VALIDATE_IP)!==false ||
             (preg_match('/\A[a-z0-9](?:[a-z0-9.-]{0,251}[a-z0-9])?\z/iD',$host)===1 && filter_var($host,FILTER_VALIDATE_DOMAIN,FILTER_FLAG_HOSTNAME)!==false));
    }
    public static function validAddress(string $address): bool {
        return $address!=='' && strlen($address)<=190 && !preg_match('/[\x00-\x20\x7F<>]/',$address) && filter_var($address,FILTER_VALIDATE_EMAIL)!==false;
    }

    /** @return array{success:bool,stage:string,smtp_code:?int,safe_message:string} */
    public static function send(string $recipient,string $subject,string $body,?string $transportOverride=null): array {
        $settings=Settings::all();
        $transport=$transportOverride??(string)($settings['mail_transport']??'php_mail');
        if (!in_array($transport,['smtp','php_mail'],true)) {return self::failure('configuration','Choose a valid mail transport.');}
        $from=(string)($settings['mail_from_email']??'');
        $name=(string)($settings['mail_from_name']??'');
        $reply=(string)($settings['mail_reply_to']??'');
        if ($name==='') { $name=(string)($settings['app_name']??'Monitor'); }
        if (!self::validAddress($recipient)||!self::validAddress($from)||($reply!==''&&!self::validAddress($reply))||
            self::unsafeHeader($subject)||self::unsafeHeader($name)||strlen($subject)>190||strlen($name)>120||trim($subject)==='') {
            return self::failure('configuration','Check the sender and recipient mail settings.');
        }
        try {
            $headers=[
                'Date: '.gmdate('D, d M Y H:i:s +0000'),
                'From: '.self::encoded($name).' <'.$from.'>',
                'To: <'.$recipient.'>',
                'Subject: '.self::encoded($subject),
                'Message-ID: <'.bin2hex(random_bytes(16)).'@'.substr(strrchr($from,'@'),1).'>',
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
            ];
            if ($reply!=='') { $headers[]='Reply-To: <'.$reply.'>'; }
            $encodedBody=chunk_split(base64_encode(str_replace(["\r\n","\r"],"\n",$body)),76,"\r\n");
            if ($transport==='php_mail') {
                $phpHeaders=[];
                foreach ($headers as $header) {
                    if (str_starts_with($header, 'Subject: ') || str_starts_with($header, 'To: ')) {continue;}
                    $split=explode(': ',$header,2);$phpHeaders[$split[0]]=$split[1];
                }
                $accepted=@mail($recipient,self::encoded($subject),$encodedBody,$phpHeaders);
                return $accepted ? ['success'=>true,'stage'=>'send','smtp_code'=>null,'safe_message'=>'Message accepted by PHP mail transport.'] : self::failure('send','PHP mail transport did not accept the message.');
            }
            $password=(string)($settings['smtp_password']??'');
            if (($settings['smtp_auth_enabled']??'0')==='1'&&($password===''||($settings['smtp_username']??'')==='')) {
                return self::failure('configuration','SMTP authentication is not configured.');
            }
            return SmtpClient::send($settings,$recipient,implode("\r\n",$headers)."\r\n\r\n".$encodedBody);
        } catch (\Throwable $ignored) {
            return self::failure('send','Email delivery could not be completed.');
        }
    }

    public static function otp(string $recipient,string $code): bool {
        $app=trim(str_replace(["\r","\n"],' ',Settings::get('app_name','Monitor')));
        $app=mb_safe_substr($app,100);
        $subject='Your '.$app.' login code';
        $body="Your sign-in code is: {$code}\n\nThis code expires in 10 minutes.\n\nIf you did not request this code, you can ignore this message.\n\n".$app;
        return self::send($recipient,$subject,$body)['success'];
    }
    private static function encoded(string $value): string {
        // Keep each RFC 2047 encoded-word under 75 bytes, without splitting UTF-8 characters.
        $characters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
        if ($characters === false) { throw new \RuntimeException('Invalid UTF-8 mail header.'); }
        $chunks = []; $part = '';
        foreach ($characters as $character) {
            if ($part !== '' && strlen($part . $character) > 42) { $chunks[] = $part; $part = ''; }
            $part .= $character;
        }
        if ($part !== '') { $chunks[] = $part; }
        return implode(' ', array_map(static fn(string $chunk): string => '=?UTF-8?B?' . base64_encode($chunk) . '?=', $chunks));
    }
    private static function unsafeHeader(string $value): bool {return preg_match('/[\x00-\x1F\x7F]/',$value)===1;}
    private static function failure(string $stage,string $message): array {return ['success'=>false,'stage'=>$stage,'smtp_code'=>null,'safe_message'=>$message];}
}
