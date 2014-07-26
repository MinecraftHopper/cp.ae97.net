<?php

function getDatabaseConfig() {
    return [
        'host' => 'localhost',
        'factoiddb' => 'factoid',
        'authdb' => 'authentication',
        'user' => 'panel',
        'pass' => ''
    ];
}

function getMailgunConfig() {
    return [
        'key' => '',
        'email' => 'noreply@example.com'
    ];
}

function getSiteConfig() {
    return [
        'domain' => 'example.com',
        'site' => 'cp.example.com',
        'fullsite' => 'http://cp.example.com'
    ];
}
