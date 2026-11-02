<?php
declare(strict_types=1);
namespace Monitor\Services;
use Monitor\Models\Settings;

/** Shared hosting transport. Real delivery requires a correctly configured server mail relay. */
final class Mailer {
    public static function otp(string $recipient,string $code): bool {
        $from=Settings::get('mail_from_email');
        $name=Settings::get('mail_from_name',Settings::get('app_name','Monitor'));
        if (!filter_var($from,FILTER_VALIDATE_EMAIL) || strpbrk($from,"\r\n")!==false || strpbrk($recipient,"\r\n")!==false) {return false;}
        $name=trim(str_replace(["\r","\n"],' ',$name));
        $app=trim(str_replace(["\r","\n"],' ',Settings::get('app_name','Monitor')));
        $name=mb_safe_substr($name,100);$app=mb_safe_substr($app,100);
        $subject='Your '.$app.' login code';
        $body="Your sign-in code is: {$code}\n\nThis code expires in 10 minutes.\n\nIf you did not request this code, you can ignore this message.\n\n".$app;
        $headers=['From'=>'=?UTF-8?B?'.base64_encode($name).'?= <'.$from.'>','Content-Type'=>'text/plain; charset=UTF-8','X-Mailer'=>'Monitor'];
        return mail($recipient,$subject,$body,$headers);
    }
}
