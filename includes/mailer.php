<?php
/**
 * Minimal SMTP client. No Composer/PHPMailer dependency — just enough raw
 * SMTP (with optional STARTTLS/SSL and AUTH LOGIN) to send transactional
 * email through any standard provider (Gmail app password, SendGrid,
 * Mailtrap, your own mail server, etc.).
 *
 * If SMTP_HOST isn't set in .env, falls back to PHP's mail() as a
 * best-effort (which on most local/dev setups does nothing useful) and
 * logs the message instead, so nothing here ever fatals a request that
 * happens to trigger an email.
 */

if (!function_exists('send_email')) {
    function send_email($to, $subject, $html_body, $text_body = null)
    {
        $host = env('SMTP_HOST', '');
        $from = env('SMTP_FROM', 'no-reply@example.com');
        $from_name = env('SMTP_FROM_NAME', 'Food Ordering System');
        $text_body = $text_body ?? strip_tags($html_body);

        if ($host === '') {
            // No SMTP configured — best-effort mail(), and always log so
            // local/dev testing can see what would have been sent.
            error_log("EMAIL (no SMTP configured, attempting mail()): To=$to Subject=$subject");
            $headers = "From: $from_name <$from>\r\nContent-Type: text/html; charset=UTF-8\r\n";
            @mail($to, $subject, $html_body, $headers);
            return true; // don't block the calling flow on a missing dev mail setup
        }

        try {
            return smtp_send($host, (int)env('SMTP_PORT', 587), env('SMTP_SECURE', 'tls'),
                env('SMTP_USER', ''), env('SMTP_PASS', ''),
                $from, $from_name, $to, $subject, $html_body, $text_body);
        } catch (\Throwable $e) {
            error_log("SMTP send failed: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('smtp_send')) {
    function smtp_send($host, $port, $secure, $user, $pass, $from, $from_name, $to, $subject, $html_body, $text_body)
    {
        $timeout = 15;
        $remote = ($secure === 'ssl') ? "ssl://$host:$port" : "$host:$port";
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $sock = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
        if (!$sock) {
            throw new \RuntimeException("Could not connect to $host:$port — $errstr ($errno)");
        }
        stream_set_timeout($sock, $timeout);

        $read = function () use ($sock) {
            $data = '';
            while ($line = fgets($sock, 515)) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') break; // last line of a multi-line response
            }
            return $data;
        };
        $write = function ($cmd) use ($sock) {
            fwrite($sock, $cmd . "\r\n");
        };
        $expect = function ($response, $codes) {
            $code = (int)substr($response, 0, 3);
            if (!in_array($code, $codes, true)) {
                throw new \RuntimeException("Unexpected SMTP response: " . trim($response));
            }
        };

        $expect($read(), [220]);

        $write("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        $ehlo_response = $read();
        $expect($ehlo_response, [250]);

        if ($secure === 'tls') {
            $write("STARTTLS");
            $expect($read(), [220]);
            if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new \RuntimeException("STARTTLS negotiation failed");
            }
            $write("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
            $expect($read(), [250]);
        }

        if ($user !== '') {
            $write("AUTH LOGIN");
            $expect($read(), [334]);
            $write(base64_encode($user));
            $expect($read(), [334]);
            $write(base64_encode($pass));
            $expect($read(), [235]);
        }

        $write("MAIL FROM:<$from>");
        $expect($read(), [250]);
        $write("RCPT TO:<$to>");
        $expect($read(), [250, 251]);
        $write("DATA");
        $expect($read(), [354]);

        $boundary = 'b_' . bin2hex(random_bytes(8));
        $headers = [];
        $headers[] = "From: $from_name <$from>";
        $headers[] = "To: $to";
        $headers[] = "Subject: " . $subject;
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Date: " . date('r');
        $headers[] = "Content-Type: multipart/alternative; boundary=\"$boundary\"";
        $headers[] = "";
        $headers[] = "--$boundary";
        $headers[] = "Content-Type: text/plain; charset=UTF-8";
        $headers[] = "";
        $headers[] = $text_body;
        $headers[] = "";
        $headers[] = "--$boundary";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "";
        $headers[] = $html_body;
        $headers[] = "";
        $headers[] = "--$boundary--";

        $message = implode("\r\n", $headers);
        // Per RFC 5321, lines consisting solely of "." must be escaped.
        $message = preg_replace('/^\./m', '..', $message);

        $write($message . "\r\n.");
        $expect($read(), [250]);

        $write("QUIT");
        fclose($sock);

        return true;
    }
}
