<?php
namespace controllers;

use Config;
use models\Post;

class Pages {
    public static function showPage($request, $response) {
        $response->session('backurl', $request->uri());

        $response->posts = Post::findByPage($request->param('page', 1), Config::PAGESIZE, !$response->loggedin);
        $response->paginationCurrent = $request->param('page', 1);

        foreach ($response->posts as $post) {
            foreach ($post->getTags() as $tag) {
                $response->htmlkeywords[] = $response->htmlescape($tag);
            }
        }
        $response->htmlkeywords = array_unique($response->htmlkeywords);

        if ($request->param('page', 1) != 1) {
            $response->title .= Config::PAGE_TITLE_S . 'Seite ' . $request->param('page', 1);
        }
        $response->paginationCount = ceil(Post::getPostCount(!$response->loggedin) / Config::PAGESIZE);

        if (count($response->posts) == 0) {
            $response->flash('Diese Seitenzahl existiert nicht', 'error');
            $response->code(404);
            $response->render('tpl/plain.html');
            exit;
        }

        $response->render('tpl/posts.html');
    }
}
