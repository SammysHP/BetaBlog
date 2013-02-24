<?php
namespace controllers;

use exceptions\DatabaseException;
use models\Comment;
use models\Post;
use models\Tag;

class Maintenance {
    // Installation
    public static function install($request, $response) {
        $response->requireLogin($request, $response);

        try {
            Post::install();
            Tag::install();
            Comment::install();
            $response->flash('System erfolgreich installiert', 'success');
            $response->redirect($response->baseurl);
        } catch (DatabaseException $e) {
            $response->flash('Fehler bei der Installation', 'error');
            $response->code(500);
            $response->render('tpl/plain.html');
        }
    }
}
