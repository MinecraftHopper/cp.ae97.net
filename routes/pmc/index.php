<?php

use \AE97\Panel\Authentication,
    \AE97\Panel\Utilities,
    \AE97\Panel\PmcEmail,
    \AE97\Panel\Email;

$this->respond('POST', '/clear', function($request, $response, $service) {

    if (Authentication::verifySession()) {
        try {
            if (Authentication::checkPermission('pmc.emails')) {
                PmcEmail::deleteCode($request->param('ticket'), $_SESSION['uuid']);
                $response->redirect('/pmc/email', 200);
            } else {
                $service->render(HTML_DIR . 'errors/403.phtml');
            }
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return array('msg' => 'Failed, MySQL database returned error');
        }
    } else {
        $response->redirect("/auth/login?redirect=/pmc/email", 302);
    }
});

$this->respond('POST', '/add', function($request, $response, $service) {

    if (Authentication::verifySession()) {
        try {
            if (Authentication::checkPermission('pmc.emails')) {
                $code = PmcEmail::addCode($request->param('email'), $request->param('ticket'), $_SESSION['uuid']);
                $message = str_replace(array("\r\n", "\n", "\r"), '<br>', str_replace("\${ticket}", $request->param('ticket'), str_replace("\${code}", $code, $request->param('message'))));
                $subject = str_replace("\${ticket}", $request->param('ticket'), str_replace("\${code}", $code, $request->param('subject')));
                Email::send($request->param('email'), $subject, $message, 'PMC Validation <pmc@ae97.net>');
                $response->redirect('/pmc/email', 200);
            } else {
                $service->render(HTML_DIR . 'errors/403.phtml');
            }
        } catch (\Exception $ex) {
            Utilities::logError($ex);
            return array('msg' => 'Failed, an unexpected error occurred');
        }
    } else {
        $response->redirect("/auth/login?redirect=/pmc/email", 302);
    }
});

$this->respond('GET', '/[|index|email]?', function($request, $response, $service) {

    if (Authentication::verifySession()) {
        try {
            if (Authentication::checkPermission('pmc.emails')) {
                $service->render(HTML_DIR . 'index.phtml', array(
                    'page' => HTML_DIR . 'pmc/email.phtml',
                    'codes' => PmcEmail::getCodes(),
                    'message' => file_get_contents(BASE_DIR . '/templates/pmcvalidate.txt'),
                    'subject' => 'PMC Ticket #${ticket} Validation'
                ));
            } else {
                $service->render(HTML_DIR . 'errors/403.phtml');
            }
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return array('msg' => 'Failed, MySQL database returned error');
        }
    } else {
        $response->redirect("/auth/login?redirect=/pmc/email", 302);
    }
});
