<?php
namespace controllers;

use Config;
use exceptions\CommentNotFoundException;
use exceptions\DatabaseException;
use exceptions\PostNotFoundException;
use models\Comment;
use models\Post;

class Comments {
    // Create comment
    public static function createComment($request, $response) {
        // Check if post exists
        try {
            Post::findById($request->param('id'), false);
        } catch (PostNotFoundException $e) {
            $response->redirect($response->baseurl . 'post/' . $request->param('id'));
        }

        $author = stripslashes($request->param('author'));
        $message = stripslashes($request->param('message'));
        $challenge = stripslashes($request->param('challenge'));

        if (strlen($message) < 4) {
            $response->flash('Der Kommentar muss mindestens 4 Zeichen lang sein.', 'error');
            $response->flash($author, 'commentformauthor');
            $response->flash($message, 'commentformmessage');
            $response->redirect($response->baseurl . 'post/' . $request->param('id'));
        }
        
        if (strlen($challenge) != 4 || 0 != substr_compare(
                $message,
                $challenge,
                -4,
                4)) {
            $response->flash('Die Kontrolle stimmt nicht mit den letzten 4 Zeichen des Kommentars überein.', 'error');
            $response->flash($author, 'commentformauthor');
            $response->flash($message, 'commentformmessage');
            $response->redirect($response->baseurl . 'post/' . $request->param('id'));
        }

        $comment = new Comment(
            $request->param('id'),
            $author,
            $message
        );

        try {
            $comment->create();
            $response->flash('Kommentar gespeichert', 'success');
        } catch (DatabaseException $e) {
            $response->flash('Fehler beim Speichern des Kommentars', 'error');
        }

        $response->redirect($response->baseurl . 'post/' . $request->param('id'));
    }

    // Delete confirmation
    public static function deleteConfirmation($request, $response) {
        $response->requireLogin($request, $response);

        $response->title .= Config::PAGE_TITLE_S . 'Kommentar löschen';

        try {
            $comment = Comment::findById($request->param('id'));
            $response->message = 'Kommentar von "' . $comment->getAuthor() . '" vom ' . date('d.m.Y \u\m H:i', $comment->getDate());
            $response->render('tpl/delete.html');
        } catch (CommentNotFoundException $e) {
            $response->flash('Kommentar nicht gefunden', 'error');
            $response->code(404);
            $response->render('tpl/plain.html');
        }
    }

    // Delete comment
    public static function deleteComment($request, $response) {
        $response->requireLogin($request, $response);

        if ($request->param('delete') != '') {
            try {
                Comment::delete($request->param('id'));
                $response->flash('Kommentar gelöscht', 'success');
            } catch (CommentNotFoundException $e) {
                $response->flash('Kommentar nicht gefunden', 'error');
                $response->code(404);
                $response->render('tpl/plain.html');
                exit;
            }
        }

        $response->redirect($response->backurl);
    }
}
