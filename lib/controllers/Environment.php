<?php
namespace controllers;

use Config;

class Environment {
    public static function initialize($request, $response) {
        $response->htmlescape = function ($str) {
            return htmlspecialchars($str, ENT_COMPAT, 'UTF-8');
        };

        $response->requireLogin = function ($request, $response) {
            if (!$request->session('loggedin', false)) {
                $response->session('backurl', $request->uri());
                $response->redirect(Config::BASE_URL . 'login');
            }
        };

        $response->onError(function ($response, $errormessage) {
            $response->code(500);
            $response->message = $errormessage;
            $response->render('tpl/error.html');
        });

        $response->layout('tpl/page.html');

        $response->loggedin = (boolean) $request->session('loggedin', false);
        $response->backurl = $request->session('backurl', $response->baseurl);

        $response->title = Config::PAGE_TITLE;
        $response->baseurl = Config::BASE_URL;
        $response->abouturl = Config::ABOUT_URL;
        $response->infourl = Config::INFO_URL;
        $response->headertitle = Config::HEADER_TITLE;
        $response->htmlkeywords = array();
        $response->lang = Config::LANG;
    }
}
