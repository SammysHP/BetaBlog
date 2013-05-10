<?php
namespace controllers;

use Config;
use util\AntiCSRF;

class Authentication {
    // Login (view)
    public static function showLoginForm($request, $response) {
        if ($request->session('loggedin', false)) {
            $response->redirect($response->baseurl);
        }

        $response->title .= Config::PAGE_TITLE_S . 'Login';

        $response->render('tpl/login.html');
    }

    // Login (handler)
    public static function login($request, $response) {
        AntiCSRF::verifyOrFail();

        if (Config::LOGIN_PW_HASH == md5(Config::SALT . $request->param('password', ''))) {
            $response->session('loggedin', true);
            $response->redirect($response->backurl);
        }

        $response->flash('Falsches Passwort', 'error');
        $response->redirect($response->baseurl . 'login');
    }

    // Logout
    public static function logout($request, $response) {
        session_unset();
        $response->flash('Erfolgreich abgemeldet', 'success');
        $response->redirect($response->baseurl);
    }
}
