<?php
namespace controllers;

class Rss {
    // Post RSS
    public static function posts($request, $response) {
        $response->posts = Post::findByPage(1, 10);
        $response->header('Content-type', 'application/rss+xml');
        $response->partial('tpl/rssposts.html');
    }

    // Comment RSS
    public static function comments($request, $response) {
        $response->comments = Comment::findByPage(1, 20);
        $response->header('Content-type', 'application/rss+xml');
        $response->partial('tpl/rsscomments.html');
    }
}
