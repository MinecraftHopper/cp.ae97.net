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

$this->respond('GET', '/ban/new', function($request, $response, $service) {
    if (Authentication::verifySession() && Authentication::checkPermission('bans.new')) {
        $service->render(HTML_DIR . 'index.phtml', array('action' => 'ban', 'page' => HTML_DIR . 'cp/admin/ban/new.phtml'));
    } else {
        $response->redirect("/auth/login", 302)->send();
    }
});

$this->respond('GET', '/ban/edit', function($request, $response, $service) {
    if ($request->param('id') == null) {
        $response->redirect('/admin/ban');
        return;
    }
    if (Authentication::verifySession() && Authentication::checkPermission('bans.edit')) {
        $ban = Bans::getBan($request->param('id'));
        if ($ban == null || count($ban) == 0) {
            $service->flash('No ban with id ' . $request->param('id'));
            $response->redirect('/admin/ban');
            return;
        }
        //$service->render(HTML_DIR . 'index.phtml', array('action' => 'ban', 'page' => HTML_DIR . 'cp/admin/ban/edit.phtml', 'ban' => $ban[0]));
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

$this->respond('POST', '/ban/new', function($request, $response, $service) {
    if (!Authentication::verifySession() || !Authentication::checkPermission("bans.new")) {
        $service->flash("Invalid user");
        $service->refresh();
    }
    try {
        $service->validateParam('mask', "Mask cannot be empty")->notNull();
        $service->validateParam('kickmessage', "Kick message cannot be empty")->notNull();
        $service->validateParam('channels', "Ban must apply to at least one channel")->notNull();
    } catch (\Exception $ex) {
        $service->flash($ex->getMessage());
        $service->refresh();
    }

    $daysBanned = $request->param('daysbanned');

    $date = null;

    if ($daysBanned != null && $daysBanned != 0) {
        $date = new DateTime("now", new DateTimeZone("UTC"));
        $date->modify('+' . $daysBanned . ' day');
    }

    $result = Bans::addBan($request->param('mask'), $_SESSION['uuid'], $request->param('kickmessage'), $date == null ? null : $date->format('Y-j-n G:i:s'));

    if ($result != -1) {
        foreach (explode(',', $request->param('channels')) as $chan) {
            Bans::addChannelToBan($result, $chan);
        }
        $response->redirect('/admin/ban');
    } else {
        $service->flash('Failed to add ban to the database (likely mask already banned)');
        $service->refresh();
    }
});

$this->respond('GET', '/ban/expire', function ($request, $response, $service) {
    try {
        $service->validateParam('id')->notNull();
        if (Authentication::verifySession() && Authentication::checkPermission('bans.expire')) {
            if (Bans::expire($request->param('id'))) {
                $service->flash('Ban expired');
            } else {
                $service->flash('Could not expire ban');
            }
            $service->back();
        } else {
            $response->redirect('/auth/login', 302);
        }
    } catch (\Exception $ex) {
        $service->flash($ex->getMessage());
        $service->back();
        return;
    }
});
