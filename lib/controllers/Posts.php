<?php
namespace controllers;

use Config;
use exceptions\BetablogException;
use exceptions\PostNotFoundException;
use models\Comment;
use models\Post;
use models\Tag;
use util\AntiCSRF;

class Posts {
    // Single post
    public static function showSinglePost($request, $response) {
        try {
            $response->session('backurl', $request->uri());

            $response->post = Post::findById($request->param('id'), !$response->loggedin);
            $response->title .= Config::PAGE_TITLE_S . $response->post->getTitle();
            $response->comments = Comment::findByPost($request->param('id'));
            $response->fullentry = true;

            // Comment form content from old request
            foreach ($response->flashes('commentformauthor') as $author) {
                $response->commentformauthor = $author;
            }
            foreach ($response->flashes('commentformmessage') as $message) {
                $response->commentformmessage = $message;
            }

            $tags = array();
            foreach ($response->post->getTags() as $tag) {
                $tags[] = $response->htmlescape($tag);
            }
            $response->htmlkeywords = $tags;

            $response->render('tpl/post.html');
        } catch (PostNotFoundException $e) {
            $response->flash('Beitrag nicht gefunden', 'error');
            $response->code(404);
            $response->render('tpl/plain.html');
        }
    }

    // Show post edit form
    public static function showPostForm($request, $response) {
        $response->requireLogin($request, $response);

        if ($request->param('id') == null) {
            $response->title .= Config::PAGE_TITLE_S . 'Beitrag erstellen';
            $response->post = new Post();
        } else {
            $response->title .= Config::PAGE_TITLE_S . 'Beitrag bearbeiten';
            try {
                $response->post = Post::findById($request->param('id'), false);
            } catch (PostNotFoundException $e) {
                $response->flash('Beitrag nicht gefunden', 'error');
                $response->code(404);
                $response->render('tpl/plain.html');
                exit;
            }
        }

        $response->alltags = Tag::findAll();
        $response->render('tpl/postform.html');
    }

    // Create post (handler)
    public static function createPost($request, $response) {
        AntiCSRF::verifyOrFail();
        $response->requireLogin($request, $response);

        $post = new Post(
            stripslashes($request->param('title')),
            stripslashes($request->param('content')),
            stripslashes($request->param('extended')),
            strtotime($request->param('date')),
            preg_split('@,\s?@', stripslashes($request->param('tags')), NULL, PREG_SPLIT_NO_EMPTY),
            ($request->param('published') != '')
        );

        try {
            $post->create();
        } catch (BetablogException $e) {
            $response->flash('Fehler beim Speichern', 'error');
            $response->code(500);
            $response->render('tpl/plain.html');
            exit;
        }

        $response->flash('Beitrag gespeichert', 'success');

        if ($request->param('save', false)) {
            $response->redirect($response->baseurl . 'post/' . $post->getId() . '/edit');
        } else {
            $response->redirect($response->baseurl . 'post/' . $post->getId());
        }
    }


    // Edit post (handler)
    public static function editPost($request, $response) {
        AntiCSRF::verifyOrFail();
        $response->requireLogin($request, $response);

        $post = new Post(
            stripslashes($request->param('title')),
            stripslashes($request->param('content')),
            stripslashes($request->param('extended')),
            strtotime($request->param('date')),
            preg_split('@,\s?@', stripslashes($request->param('tags')), NULL, PREG_SPLIT_NO_EMPTY),
            ($request->param('published') != ''),
            $request->param('id')
        );

        try {
            $post->save();
        } catch (PostNotFoundException $e) {
            $response->flash('Beitrag nicht gefunden', 'error');
            $response->code(404);
            $response->render('tpl/plain.html');
            exit;
        } catch (BetablogException $e) {
            $response->flash('Fehler beim Speichern', 'error');
            $response->code(500);
            $response->render('tpl/plain.html');
            exit;
        }

        $response->flash('Beitrag gespeichert', 'success');

        if ($request->param('save', false)) {
            $response->redirect($response->baseurl . 'post/' . $post->getId() . '/edit');
        } else {
            $response->redirect($response->baseurl . 'post/' . $post->getId());
        }
    }

    // Publish/retract post
    public static function changePublishStatus($request, $response) {
        AntiCSRF::verifyOrFail();
        $response->requireLogin($request, $response);

        try {
            $post = Post::findById($request->param('id'), false);
            if ($request->param('action') == 'publish') {
                $post->setPublished(true);
                $response->flash('Beitrag veröffentlicht', 'success');
            } else {
                $post->setPublished(false);
                $response->flash('Beitrag zurückgezogen', 'success');
            }
            $post->save();
        } catch (PostNotFoundException $e) {
            $response->flash('Beitrag nicht gefunden', 'error');
            $response->code(404);
        } catch (BetablogException $e) {
            $response->flash('Fehler beim Ändern der Sichtbarkeit', 'error');
            $response->code(500);
        }

        $response->redirect($response->backurl);
    }

    // Show post delete confirmation
    public static function showDeleteConfirmation($request, $response) {
        $response->requireLogin($request, $response);

        $response->title .= Config::PAGE_TITLE_S . 'Beitrag löschen';

        try {
            $post = Post::findById($request->param('id'), false);
            $response->message = 'Beitrag "' . $post->getTitle() . '"';
            $response->render('tpl/delete.html');
        } catch (PostNotFoundException $e) {
            $response->flash('Beitrag nicht gefunden', 'error');
            $response->code(404);
            $response->render('tpl/plain.html');
        }
    }

    // Delete post
    public static function deletePost($request, $response) {
        AntiCSRF::verifyOrFail();
        $response->requireLogin($request, $response);

        if ($request->param('delete') != '') {
            try {
                POST::delete($request->param('id'));
                $response->flash('Beitrag gelöscht', 'success');
                $response->redirect($response->baseurl);
            } catch (PostNotFoundException $e) {
                $response->flash('Beitrag nicht gefunden', 'error');
                $response->code(404);
                $response->render('tpl/plain.html');
                exit;
            }
        } else {
            $response->redirect($response->backurl);
        }
    }
}
