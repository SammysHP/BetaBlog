<?php
namespace util;

use exceptions\MailException;

class Mail {
    private $from = null;
    private $to = null;
    private $subject;
    private $message;

    public function send() {
        if ($this->from == null) {
            throw new MailException('No sender set');
        }
        if ($this->to == null) {
            throw new MailException('No receiver set');
        }

        $from = empty($this->from['name']) ? $this->from['email'] : '=?UTF-8?B?' . base64_encode($this->from['name']) . '?= <' . $this->from['email'] . '>';
        $to = empty($this->to['name']) ? $this->to['email'] : '=?UTF-8?B?' . base64_encode($this->to['name']) . '?= <' . $this->to['email'] . '>';

        $headers = array (
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            'From: ' . $from
        );

        $message = chunk_split(base64_encode($this->message));

        $status = mail(
            $to,
            $this->subject,
            $message,
            implode("\n", $headers)
        );

        // mail() returns '' on success on some systems
        if ($status === false) {
            throw new MailException('Mail was not sent');
        }
    }

    public function setFrom($email, $name = '') {
        if (!strpos($email, '@')) {
            throw new MailException('Invalid sender: ' . $email);
        }

        $this->from = array(
            'email' => $email,
            'name'  => $name
        );

        return $this;
    }

    public function setTo($email, $name = '') {
        if (!strpos($email, '@')) {
            throw new MailException('Invalid receiver: ' . $email);
        }

        $this->to = array(
            'email' => $email,
            'name'  => $name
        );

        return $this;
    }

    public function setSubject($subject) {
        $this->subject = $subject;
        return $this;
    }

    public function setMessage($message) {
        $this->message = $message;
        return $this;
    }
}
