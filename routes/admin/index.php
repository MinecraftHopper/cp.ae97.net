<?php

use \AE97\Panel\Authentication,
    \AE97\Panel\Bans,
    \AE97\Panel\User,
    \AE97\Validate;

$this->respond('GET', '/', function($request, $response, $service) {
    if (Authentication::verifySession()) {
        $service->render(HTML_DIR . 'index.phtml', array('action' => 'index', 'page' => HTML_DIR . 'cp/admin/index.phtml'));
    } else {
        $response->redirect("/auth/login", 302)->send();
    }
});

$this->respond('GET', '/bot', function($request, $response, $service) {
    if (Authentication::verifySession()) {
        $service->render(HTML_DIR . 'index.phtml', array('action' => 'bot', 'page' => HTML_DIR . 'cp/admin/bot/index.phtml'));
    } else {
        $response->redirect("/auth/login", 302)->send();
    }
});

$this->respond('GET', '/user/approve', function($request, $response, $service) {
    if (Authentication::verifySession() && Authentication::checkPermission('panel.viewusers')) {
        $perms['approveUser'] = Authentication::checkPermission('panel.approveuser');
        $service->render(HTML_DIR . 'index.phtml', array('action' => 'user', 'page' => HTML_DIR . 'cp/admin/user/approval.phtml', 'perms' => $perms));
    } else {
        $response->redirect("/auth/login", 302)->send();
    }
});

$this->respond('GET', '/user/manage', function($request, $response, $service) {
    if (Authentication::verifySession() && Authentication::checkPermission('panel.viewusers')) {
        $users = User::getAll();
        $service->render(HTML_DIR . 'index.phtml', array('action' => 'user', 'page' => HTML_DIR . 'cp/admin/user/manage.phtml', 'users' => $users));
    } else {
        $response->redirect("/auth/login", 302)->send();
    }
});

$this->respond('GET', '/user/edit', function($request, $response, $service) {
    if (Authentication::verifySession() && Authentication::checkPermission('panel.viewusers')) {
        if ($request->param('name') == null) {
            $response->redirect('/cp/admin/user/manage', 302)->send();
            return;
        }
        $user = User::get($request->param('name'));
        $permList = User::getPerms();
        $userPermsTemp = User::getPerms($user['uuid']);
        $userPerms = array();
        foreach ($userPermsTemp as $perm) {
            $userPerms[$perm['permission']] = true;
        }
        $service->render(HTML_DIR . 'index.phtml', array('action' => 'user', 'page' => HTML_DIR . 'cp/admin/user/edit.phtml', "user" => $user, "allPerms" => $permList, "userPerms" => $userPerms));
    } else {
        $response->redirect("/auth/login", 302)->send();
    }
});

$this->respond('GET', '/ban', function($request, $response, $service) {
    if (Authentication::verifySession() && Authentication::checkPermission('bans.view')) {
        $bans = Bans::getBans();
        $service->render(HTML_DIR . 'index.phtml', array('action' => 'ban', 'page' => HTML_DIR . 'cp/admin/ban/index.phtml', 'bans' => $bans, 'edit' => Authentication::checkPermission('bans.edit')));
    } else {
        $response->redirect("/auth/login", 302)->send();
    }
});

$this->respond('GET', '/user', function($request, $response, $service) {
    if (Authentication::verifySession()) {
        if (Authentication::checkPermission('panel.viewusers')) {
            $perms['approveUser'] = Authentication::checkPermission('panel.approveuser');
            $service->render(HTML_DIR . 'index.phtml', array('action' => 'user', 'page' => HTML_DIR . 'cp/admin/user/approval.phtml', 'perms' => $perms));
        }
    } else {
        $response->redirect("/auth/login", 302)->send();
    }
});

$this->respond('POST', '/user/list/unapproved', function() {
    if (Authentication::verifySession()) {
        $perms['view'] = Authentication::checkPermission('panel.viewuser');
        if ($perms['view']) {
            $accounts = User::getUnapproved();
        } else {
            $accounts = array();
        }
        echo json_encode($accounts);
    } else {
        echo "failed";
    }
});

$this->respond('POST', '/user/approve/[:id]', function($request, $response) {
    if (Authentication::verifySession() && Authentication::checkPermission("user.approve")) {
        $id = $request->id;
        User::approve($id);
        $response->redirect("/cp/admin/user", 302);
    } else {
        $response->redirect("/auth/login", 302)->send();
    }
});

$this->respond('POST', '/user/edit', function($request) {
    Validate::param($request->param('user'))->notNull();
    Validate::param($request->param('perms'))->notNull();

    if (!Authentication::verifySession() || !Authentication::checkPermission("user.edit")) {
        return;
    }
    User::editPerms($request->param('user'), $request->param('perms'));
});