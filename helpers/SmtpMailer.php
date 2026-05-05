<?php

class SmtpMailer
{
    private $socket;
    private $timeout = 20;

    public function send(array $config, $toEmail, $toName, $subject, $htmlBody, $textBody = '')
    {
        $host = trim((string) ($config['smtp_host'] ?? ''));
        $port = (int) ($config['smtp_port'] ?? 0);
        $username = trim((string) ($config['smtp_username'] ?? ''));
        $password = (string) ($config['smtp_password'] ?? '');
        $encryption = strtolower(trim((string) ($config['smtp_encryption'] ?? 'tls')));
        $fromEmail = trim((string) ($config['smtp_from_email'] ?? $username));
        $fromName = trim((string) ($config['smtp_from_name'] ?? ($config['shop_name'] ?? 'Online Shop')));

        if ($host === '' || $port <= 0 || $fromEmail === '') {
            throw new RuntimeException('SMTP settings are incomplete.');
        }

        $remoteHost = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host;
        $this->socket = @stream_socket_client($remoteHost . ':' . $port, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT);
        if (!$this->socket) {
            throw new RuntimeException('SMTP connection failed: ' . $errstr);
        }

        stream_set_timeout($this->socket, $this->timeout);

        $this->expect([220]);
        $this->command('EHLO ' . $this->clientName(), [250]);

        if ($encryption === 'tls') {
            $this->command('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Unable to start TLS encryption.');
            }
            $this->command('EHLO ' . $this->clientName(), [250]);
        }

        if ($username !== '') {
            $this->command('AUTH LOGIN', [334]);
            $this->command(base64_encode($username), [334]);
            $this->command(base64_encode($password), [235]);
        }

        $this->command('MAIL FROM:<' . $fromEmail . '>', [250]);
        $this->command('RCPT TO:<' . trim((string) $toEmail) . '>', [250, 251]);
        $this->command('DATA', [354]);

        $boundary = 'b1_' . md5((string) microtime(true));
        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . $this->formatAddress($fromEmail, $fromName),
            'To: ' . $this->formatAddress($toEmail, $toName ?: $toEmail),
            'Subject: ' . $this->encodeHeader($subject),
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"'
        ];

        $textBody = $textBody !== '' ? $textBody : strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $message = implode("\r\n", $headers) . "\r\n\r\n";
        $message .= '--' . $boundary . "\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($textBody)) . "\r\n";
        $message .= '--' . $boundary . "\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $message .= '--' . $boundary . "--\r\n.\r\n";

        fwrite($this->socket, $message);
        $this->expect([250]);
        $this->command('QUIT', [221]);
        fclose($this->socket);

        return true;
    }

    private function command($command, array $expectedCodes)
    {
        fwrite($this->socket, $command . "\r\n");
        return $this->expect($expectedCodes);
    }

    private function expect(array $expectedCodes)
    {
        $response = '';

        while (!feof($this->socket)) {
            $line = fgets($this->socket, 515);
            if ($line === false) {
                break;
            }

            $response .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('SMTP error: ' . trim($response));
        }

        return $response;
    }

    private function encodeHeader($value)
    {
        return '=?UTF-8?B?' . base64_encode((string) $value) . '?=';
    }

    private function formatAddress($email, $name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '<' . $email . '>';
        }

        return $this->encodeHeader($name) . ' <' . $email . '>';
    }

    private function clientName()
    {
        return preg_replace('/[^A-Za-z0-9\.\-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    }
}
