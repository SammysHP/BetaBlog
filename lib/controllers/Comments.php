<?php
namespace controllers;

use Config;
use exceptions\CommentNotFoundException;
use exceptions\DatabaseException;
use exceptions\MailException;
use exceptions\PostNotFoundException;
use models\Comment;
use models\Post;
use util\Mail;

class Comments {
    // Create comment
    public static function createComment($request, $response) {
        // Check if post exists
        try {
            Post::findById($request->param('id'), false);
        } catch (PostNotFoundException $e) {
            $response->redirect($response->baseurl . 'post/' . $request->param('id'));
        }

        $author = trim(stripslashes($request->param('author')));
        $message = trim(stripslashes($request->param('message')));
        $challenge = trim(stripslashes($request->param('challenge')));

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

            if (Config::NOTIFY_EMAIL != '') {
                try {
                    $mail = new Mail();

                    $mail->setFrom(Config::NOTIFY_EMAIL, $comment->getAuthor());
                    $mail->setTo(Config::NOTIFY_EMAIL);

                    $mail->setSubject('Neuer Kommentar');

                    $message = $comment->getAuthor() . " hat geschrieben:\n\n" . $comment->getComment();
                    $message .= "\n\n\n";
                    $message .= 'Ansehen: http://' . $_SERVER['SERVER_NAME'] . $response->baseurl . 'post/' . $comment->getPost() . '#comment-' . $comment->getId() . "\n";
                    $message .= 'Löschen: http://' . $_SERVER['SERVER_NAME'] . $response->baseurl . 'comment/' . $comment->getId() . '/delete';
                    $mail->setMessage($message);

                    $mail->send();
                } catch (MailException $e) {
                    // Nothing to do
                }
            }
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

        try {
            $comment = Comment::findById($request->param('id'));

            if ($request->param('delete') != '') {
                Comment::delete($comment->getId());
                $response->flash('Kommentar gelöscht', 'success');
            }

            $response->redirect($response->baseurl . 'post/' . $comment->getPost());
        } catch (CommentNotFoundException $e) {
            $response->flash('Kommentar nicht gefunden', 'error');
            $response->code(404);
            $response->render('tpl/plain.html');
            exit;
        }
    }
}
