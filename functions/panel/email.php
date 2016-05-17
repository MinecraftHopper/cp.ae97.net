<?php

namespace AE97\Panel;

use \Mailgun\Mailgun;

class Email {

    public static function send($to, $subject, $html, $from = null, $campaign = null) {
        if ($from == null) {
            $from = Config::getGlobal('mail')['email'];
        }
        try {
            $mail = new Mailgun(Config::getGlobal('mail')['key']);
            $mail->sendMessage(Config::getGlobal('site')['domain'], array(
                'from' => $from,
                'to' => $to,
                'subject' => $subject,
                'html' => $html,
                'o:campaign' => $campaign
            ));
        } catch (\Exception $ex) {
            Utilities::logError($ex);
        }
    }

}
