<?php
namespace controllers;

use Config;
use models\Post;
use models\Tag;

class Search {
    // Search page
    public static function showSearchPage($request, $response) {
        $response->alltags = Tag::findAll();
        $response->title .= Config::PAGE_TITLE_S . 'Suche';
        $response->render('tpl/search.html');
    }

    // Remote search (Google)
    public static function remoteSearch($request, $response) {
        $searchstring = stripslashes($request->param('keywords')) . ' site:' . rtrim($_SERVER['HTTP_HOST'], '/') . $response->baseurl;
        $response->redirect('http://www.google.com/search?hl=de&q=' . rawurlencode($searchstring));
    }

    // Tag search
    public static function tagSearch($request, $response) {
        $response->session('backurl', $request->uri());
        $tag = rawurldecode(str_replace('/', '%2F', $request->param('tag')));
        $response->title .= Config::PAGE_TITLE_S . $response->htmlescape($tag);
        $response->posts = Post::findByTag(array($tag), !$response->loggedin);

        if (count($response->posts) == 0) {
            $response->code(404);
        }

        $response->render('tpl/archive.html');
    }
}
