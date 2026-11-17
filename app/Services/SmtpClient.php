<?php
declare(strict_types=1);
namespace Monitor\Services;

/** Native SMTP, no external dependencies. Never return or log server dialogue or credentials. */
final class SmtpClient {
    private const STAGES = ['connect','greeting','ehlo','starttls','auth','mail_from','rcpt_to','data','send','quit'];

    /** @param array<string,string> $config @return array{success:bool,stage:string,smtp_code:?int,safe_message:string} */
    public static function send(array $config, string $recipient, string $message): array {
        $stage = 'connect';
        $socket = null;
        $code = null;
        try {
            $host = (string) ($config['smtp_host'] ?? '');
            $port = (int) ($config['smtp_port'] ?? 0);
            $encryption = (string) ($config['smtp_encryption'] ?? 'tls');
            $timeout = (int) ($config['smtp_timeout'] ?? 15);
            if (!Mailer::validHost($host) || $port < 1 || $port > 65535 || $timeout < 3 || $timeout > 60 || !in_array($encryption, ['tls','ssl','none'], true)) {
                return self::failed('connect', null, 'Check the SMTP server configuration.');
            }
            $context = stream_context_create(['ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
                'peer_name' => $host,
                'SNI_enabled' => true,
            ]]);
            $networkHost = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? '[' . $host . ']' : $host;
            $endpoint = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $networkHost . ':' . $port;
            $errno = 0; $errstr = '';
            $socket = @stream_socket_client($endpoint, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
            if (!is_resource($socket)) {
                return self::failed('connect', null, $encryption === 'ssl' ? 'Could not establish a verified TLS connection to the SMTP server.' : 'Could not connect to the SMTP server.');
            }
            stream_set_timeout($socket, $timeout);
            $stage = 'greeting';
            $reply = self::readReply($socket);
            $code = $reply['code'];
            if ($code !== 220) { return self::failed($stage, $code); }
            $stage = 'ehlo';
            if (!self::write($socket, "EHLO monitor.local\r\n")) { return self::failed($stage, null); }
            $reply = self::readReply($socket);
            $code = $reply['code'];
            if ($code !== 250) { return self::failed($stage, $code); }
            $capabilities = self::capabilities($reply['lines']);
            if ($encryption === 'tls') {
                $stage = 'starttls';
                if (!isset($capabilities['STARTTLS'])) { return self::failed($stage, null, 'STARTTLS is not available on this server.'); }
                if (!self::write($socket, "STARTTLS\r\n")) { return self::failed($stage, null); }
                $reply = self::readReply($socket);
                $code = $reply['code'];
                if ($code !== 220) { return self::failed($stage, $code); }
                $tls = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if ($tls !== true) { return self::failed($stage, null, 'TLS negotiation or certificate verification failed.'); }
                if (!self::write($socket, "EHLO monitor.local\r\n")) { return self::failed('ehlo', null); }
                $reply = self::readReply($socket);
                $code = $reply['code'];
                if ($code !== 250) { return self::failed('ehlo', $code); }
                $capabilities = self::capabilities($reply['lines']);
            }
            if (($config['smtp_auth_enabled'] ?? '0') === '1') {
                $stage = 'auth';
                if ($encryption === 'none') { return self::failed($stage, null, 'SMTP authentication requires TLS encryption.'); }
                $user = (string) ($config['smtp_username'] ?? '');
                $password = (string) ($config['smtp_password'] ?? '');
                if ($user === '' || $password === '') { return self::failed($stage, null, 'SMTP username and password are required.'); }
                $auth = strtoupper((string) ($capabilities['AUTH'] ?? ''));
                $methods = preg_split('/\s+/', trim($auth)) ?: [];
                if (in_array('PLAIN', $methods, true)) {
                    if (!self::write($socket, 'AUTH PLAIN ' . base64_encode("\0" . $user . "\0" . $password) . "\r\n")) { return self::failed($stage, null); }
                    $reply = self::readReply($socket); $code = $reply['code'];
                    if ($code === 334) {
                        if (!self::write($socket, base64_encode("\0" . $user . "\0" . $password) . "\r\n")) { return self::failed($stage, null); }
                        $reply = self::readReply($socket); $code = $reply['code'];
                    }
                } elseif (in_array('LOGIN', $methods, true)) {
                    if (!self::write($socket, "AUTH LOGIN\r\n")) { return self::failed($stage, null); }
                    $reply = self::readReply($socket); $code = $reply['code'];
                    if ($code !== 334) { return self::failed($stage, $code); }
                    if (!self::write($socket, base64_encode($user) . "\r\n")) { return self::failed($stage, null); }
                    $reply = self::readReply($socket); $code = $reply['code'];
                    if ($code !== 334) { return self::failed($stage, $code); }
                    if (!self::write($socket, base64_encode($password) . "\r\n")) { return self::failed($stage, null); }
                    $reply = self::readReply($socket); $code = $reply['code'];
                } else {
                    return self::failed($stage, null, 'The SMTP server does not support AUTH PLAIN or AUTH LOGIN.');
                }
                if ($code !== 235) { return self::failed($stage, $code); }
            }
            $sender = (string) ($config['mail_from_email'] ?? '');
            $stage = 'mail_from';
            if (!self::write($socket, 'MAIL FROM:<' . $sender . ">\r\n")) { return self::failed($stage, null); }
            $reply = self::readReply($socket); $code = $reply['code'];
            if ($code !== 250) { return self::failed($stage, $code); }
            $stage = 'rcpt_to';
            if (!self::write($socket, 'RCPT TO:<' . $recipient . ">\r\n")) { return self::failed($stage, null); }
            $reply = self::readReply($socket); $code = $reply['code'];
            if ($code !== 250 && $code !== 251) { return self::failed($stage, $code); }
            $stage = 'data';
            if (!self::write($socket, "DATA\r\n")) { return self::failed($stage, null); }
            $reply = self::readReply($socket); $code = $reply['code'];
            if ($code !== 354) { return self::failed($stage, $code); }
            $stage = 'send';
            $normalized = str_replace(["\r\n", "\r"], "\n", $message);
            $normalized = str_replace("\n", "\r\n", $normalized);
            $normalized = preg_replace('/(?m)^\./', '..', $normalized) ?? $normalized;
            // SMTP DATA terminator is preceded by one CRLF.
            if (!self::write($socket, rtrim($normalized, "\r\n") . "\r\n.\r\n")) { return self::failed($stage, null); }
            $reply = self::readReply($socket); $code = $reply['code'];
            if ($code !== 250) { return self::failed($stage, $code); }
            // A successful DATA 250 means accepted by the SMTP relay. QUIT is best effort.
            $stage = 'quit';
            self::write($socket, "QUIT\r\n");
            return ['success' => true, 'stage' => 'send', 'smtp_code' => 250, 'safe_message' => 'Message accepted by the SMTP server.'];
        } catch (\Throwable $ignored) {
            return self::failed($stage, $code);
        } finally {
            if (is_resource($socket)) { @fclose($socket); }
        }
    }

    /** @return array{code:?int,lines:array<int,string>} */
    private static function readReply($socket): array {
        $lines=[]; $expectedCode=null;
        for ($i=0; $i<30; $i++) {
            $line=@fgets($socket, 2048);
            if ($line===false || strlen($line)>1900 || !preg_match('/^([0-9]{3})([ -])/', $line, $matches)) { throw new \RuntimeException('Invalid SMTP reply.'); }
            $code=(int)$matches[1];
            if ($expectedCode!==null && $code!==$expectedCode) { throw new \RuntimeException('Inconsistent SMTP reply.'); }
            $expectedCode=$code;
            // Only extensions are used from the EHLO response; never return raw server text to UI.
            $lines[]=trim(substr($line, 4));
            if ($matches[2]===' ') { return ['code'=>$code,'lines'=>$lines]; }
        }
        throw new \RuntimeException('SMTP response exceeded safe limit.');
    }

    /** @param array<int,string> $lines @return array<string,string> */
    private static function capabilities(array $lines): array {
        $capabilities=[];
        foreach ($lines as $line) {
            if (preg_match('/^([A-Z0-9-]+)(?:[ =](.*))?$/i', $line, $m)) {
                $name=strtoupper($m[1]);
                if ($name==='AUTH' && isset($m[2])) { $capabilities['AUTH']=($capabilities['AUTH']??'').' '.trim($m[2]); }
                elseif (!isset($capabilities[$name])) { $capabilities[$name]=$m[2]??''; }
            }
        }
        return $capabilities;
    }

    private static function write($socket, string $payload): bool {
        $length=strlen($payload);$offset=0;
        while ($offset<$length) {
            $bytes=@fwrite($socket, substr($payload, $offset));
            if ($bytes===false || $bytes===0) { return false; }
            $offset+=$bytes;
        }
        return true;
    }

    /** @return array{success:bool,stage:string,smtp_code:?int,safe_message:string} */
    private static function failed(string $stage, ?int $code, ?string $message=null): array {
        $safe = $message ?? match ($stage) {
            'connect' => 'Could not connect to the SMTP server.',
            'greeting','ehlo' => 'The SMTP server did not respond as expected.',
            'starttls' => 'TLS negotiation failed.',
            'auth' => 'SMTP authentication failed.',
            'mail_from' => 'The SMTP server rejected the sender address.',
            'rcpt_to' => 'The SMTP server rejected the recipient address.',
            'data','send' => 'The SMTP server could not accept the message.',
            default => 'SMTP delivery could not be completed.',
        };
        return ['success'=>false,'stage'=>in_array($stage,self::STAGES,true)?$stage:'connect','smtp_code'=>$code,'safe_message'=>$safe];
    }
}
