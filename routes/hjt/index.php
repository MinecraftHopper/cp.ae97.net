<?php

use \AE97\Panel\Authentication,
    \AE97\Panel\HJT,
    \AE97\Panel\Utilities;

$this->respond('GET', '/[|index]?', function($request, $response, $service) {

    if (Authentication::verifySession()) {
        try {
            if (Authentication::checkPermission('hjt.view')) {
                $service->render(HTML_DIR . 'index.phtml', array(
                    'page' => HTML_DIR . 'hjt/view.phtml',
                    'hjts' => HJT::getHJTs()
                ));
            } else {
                $service->render(HTML_DIR . 'errors/403.phtml');
            }
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return array('msg' => 'Failed, MySQL database returned error');
        }
    } else {
        $response->redirect("/auth/login?redirect=/hjt/index", 302);
    }
});

$this->respond('POST', 'hjt/edit', function($request, $response) {
    if (Authentication::verifySession()) {
        try {
            if (Authentication::checkPermission('hjt.edit')) {
                $name = $request->param('name');
                $value = $request->param('newvalue');
                HJT::updateHJT($name, $value);
            } else {
                $response->code(401);
                $response->json(array('msg' => 'Not authorized'));
            }
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            $response->code(500);
            $response->json(array('msg' => 'Error occurred while processing request'));
        }
    } else {
        $response->code(401);
        $response->json(array('msg' => 'Not authorized'));
    }
});

$this->respond('POST', 'hjt/add', function($request, $response) {
    if (Authentication::verifySession()) {
        try {
            if (Authentication::checkPermission('hjt.add')) {
                $name = $request->param('name');
                $value = $request->param('newvalue');
                HJT::addHJT($name, $value);
            } else {
                $response->code(401);
                $response->json(array('msg' => 'Not authorized'));
            }
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            $response->code(500);
            $response->json(array('msg' => 'Error occurred while processing request'));
        }
    } else {
        $response->code(401);
        $response->json(array('msg' => 'Not authorized'));
    }
});

$this->respond('POST', 'hjt/delete', function($request, $response) {
    if (Authentication::verifySession()) {
        try {
            if (Authentication::checkPermission('hjt.delete')) {
                $name = $request->param('name');
                HJT::deleteHJT($name);
            } else {
                $response->code(401);
                $response->json(array('msg' => 'Not authorized'));
            }
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            $response->code(500);
            $response->json(array('msg' => 'Error occurred while processing request'));
        }
    } else {
        $response->code(401);
        $response->json(array('msg' => 'Not authorized'));
    }
});