<?php

use \AE97\Panel\Authentication,
    \AE97\Panel\HJT,
    \AE97\Panel\Utilities;

$this->respond('GET', '/?', function($request, $response, $service) {
    $page = $request->param('page') != null ? $request->param('page') : 1;
    $hjts = HJT::getHJTs($page);
    $service->render(HTML_DIR . 'index.phtml', array('action' => 'hjt', 'page' => HTML_DIR . 'hjt/index.phtml', 'hjts' => $hjts, 'currentPage' => $page));
});

$this->respond('GET', '/edit/[i:id]', function($request, $response, $service) {
    if (Authentication::verifySession()) {
        try {
            if (Authentication::checkPermission('hjt.edit')) {
                $hjts = HJT::getName($request->param('id'));
                $service->render(HTML_DIR . 'index.phtml', array('action' => 'hjt', 'page' => HTML_DIR . 'hjt/edit.phtml', 'id' => $hjts['id'], 'name' => $hjts['name'], 'content' => str_replace(';;', "\n", $hjts['value']), 'mode' => 'Edit'));
            }
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return array('msg' => 'Failed, MySQL database returned error');
        }
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('POST', '/add', function($request, $response, $service) {
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
        $response->redirect('/hjt');
    } else {
        $response->code(401);
        $response->json(array('msg' => 'Not authorized'));
    }
});

$this->respond('GET', '/delete/[i:id]', function($request, $response) {
    if (Authentication::verifySession()) {
        try {
            if (Authentication::checkPermission('hjt.delete')) {
                $name = $request->param('id');
                HJT::removeHJT($name);
            } else {
                $response->code(401);
                $response->json(array('msg' => 'Not authorized'));
            }
        } catch (PDOException $ex) {
            //Utilities::logError($ex);
            $response->code(500);
            $response->json(array('msg' => 'Error occurred while processing request'));
        }
        $response->redirect('/hjt');
    } else {
        $response->code(401);
        $response->json(array('msg' => 'Not authorized'));
    }
});

$this->respond('POST', '/submit-edit', function($request, $response) {
    if (Authentication::verifySession()) {
        try {
            if (Authentication::checkPermission('hjt.edit')) {
                $id = $request->param('id');
                $name = $request->param('name');
                $factoidContext = str_ireplace(array("\r\n", "\r", "\n"), ";;", $request->param('content'));
                HJT::updateHJT($id, $name, $factoidContext);
                $response->redirect('/hjt', 302);
            }
        } catch (Exception $ex) {
            Utilities::logError($ex);
        }
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('GET', '/new', function($request, $response, $service) {
    if (Authentication::verifySession()) {
        try {
            if (Authentication::checkPermission('hjt.create')) {
                $service->render(HTML_DIR . 'index.phtml', array('action' => 'hjt', 'page' => HTML_DIR . 'hjt/new.phtml'));
            }
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return array('msg' => 'Failed, MySQL database returned error');
        }
    } else {
        $response->redirect("/auth/login", 302);
    }
});
