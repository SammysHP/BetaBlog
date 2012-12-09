<?php

require_once("config.php");
require_once("Database.php");
require_once("Post.php");
require_once("klein.php");

setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'de');

with('/betablog', function () {

    // Environment
    respond(function ($request, $response) {
        $response->htmlescape = function ($str) {
            return htmlspecialchars($str, ENT_COMPAT, 'UTF-8');
        };
        $response->requireLogin = function ($request, $response) {
            if (!$request->session('loggedin', false)) {
                $response->redirect(CONFIG::GET('base_url') . '/login');
            }
        };
        $response->layout('tpl/page.html');
        $response->loggedin = (boolean) $request->session('loggedin', false);
        $response->title = "SammysHP | Blog";
        $response->baseurl = CONFIG::GET('base_url');
        $response->backurl = $request->session('backurl', $response->baseurl);
        $response->onError(function ($response, $err_msg) {
            $response->redirect($response->baseurl);
        });        
    });

    // Pages
    respond('GET', '@^/(?:page/(?P<page>\d+))?$', function ($request, $response) {
        $response->session('backurl', $request->uri());
        $response->posts = Post::findByPage($request->param('page', 1), CONFIG::GET('pagesize'), !$response->loggedin);
        $response->paginationCurrent = $request->param('page', 1);
        $response->paginationCount = ceil(Post::getPostCount(!$response->loggedin) / CONFIG::GET('pagesize'));
        $response->render('tpl/posts.html');
    });

    // Single post
    respond('GET', '/post/[i:id]', function ($request, $response) {
        $response->session('backurl', $request->uri());
        $response->post = Post::findById($request->param('id'), !$response->loggedin);
        $response->title .= ' -- ' . $response->post->getTitle();
        $response->fullentry = true;
        $response->render('tpl/post.html');
    });

    // Archive
    respond('GET', '/archive', function ($request, $response) {
        $response->session('backurl', $request->uri());
        $response->posts = Post::findAll(!$response->loggedin);
        $response->render('tpl/archive.html');
    });

    // Tag-search
    respond('GET', '/tag/[*:tag]', function ($request, $response) {
        $response->session('backurl', $request->uri());
        $response->posts = Post::findByTag(array($request->param('tag')), !$response->loggedin);
        $response->render('tpl/archive.html');
    });

    // Login (view)
    respond('GET', '/login', function ($request, $response) {
        if ($request->session('loggedin', false)) {
            $response->redirect($response->baseurl);
        }

        $response->render('tpl/login.html');
    });

    // Login (handler)
    respond('POST', '/login', function ($request, $response) {
        if (CONFIG::GET('admin_pw_hash') == md5(CONFIG::GET('salt') . $request->param('password', ''))) {
            $response->session('loggedin', true);
            $response->redirect($response->backurl);
        }

        $response->flash('wrongpw', 'wrongpw');
        $response->redirect($response->baseurl . '/login');
    });

    // Logout
    respond('GET', '/logout', function ($request, $response) {
        $response->session('loggedin', false);
        $response->redirect($response->baseurl);
    });

    // Create (view)
    respond('GET', '/create', function ($request, $response) {
        $response->requireLogin($request, $response);

        $response->post = new Post();
        $response->render('tpl/postform.html');
    });

    // Create (handler)
    respond('POST', '/create', function ($request, $response) {
        $response->requireLogin($request, $response);

        $post = new Post(
            stripslashes($request->param('title')),
            stripslashes($request->param('content')),
            stripslashes($request->param('extended')),
            strtotime($request->param('date')),
            explode(' ', stripslashes($request->param('tags'))),
            ($request->param('published') != '')
        );
        $post->create();

        if ($request->param('save', false)) {
            $response->redirect($response->baseurl . '/edit/' . $post->getId());
        } else {
            $response->redirect($response->baseurl . '/post/' . $post->getId());
        }
    });

    // Edit (view)
    respond('GET', '/edit/[i:id]', function ($request, $response) {
        $response->requireLogin($request, $response);

        $response->post = Post::findById($request->param('id'), false);
        $response->render('tpl/postform.html');
    });

    // Edit (handler)
    respond('POST', '/edit/[i:id]', function ($request, $response) {
        $response->requireLogin($request, $response);

        $post = new Post(
            stripslashes($request->param('title')),
            stripslashes($request->param('content')),
            stripslashes($request->param('extended')),
            strtotime($request->param('date')),
            explode(' ', stripslashes($request->param('tags'))),
            ($request->param('published') != ''),
            $request->param('id')
        );
        $post->save();

        if ($request->param('save', false)) {
            $response->redirect($response->baseurl . '/edit/' . $post->getId());
        } else {
            $response->redirect($response->baseurl . '/post/' . $post->getId());
        }
    });

    // Delete (view)
    respond('GET', '/delete/[i:id]', function ($request, $response) {
        $response->requireLogin($request, $response);

        $response->post = Post::findById($request->param('id'), false);
        $response->render('tpl/delete.html');
    });

    // Delete (handler)
    respond('POST', '/delete/[i:id]', function ($request, $response) {
        $response->requireLogin($request, $response);

        if ($request->param('delete') != '') {
            POST::delete($request->param('id'));
            $response->redirect($response->baseurl);
        } else {
            $response->redirect($response->baseurl . '/post/' . $request->param('id'));
        }
    });

    // Search (view)
    respond('GET', '/search', function ($request, $response) {
        $response->render('tpl/search.html');
    });

    // Search (handler)
    respond('POST', '/search', function ($request, $response) {
        $response->redirect('http://www.google.com/search?hl=de&q=' . rawurlencode($request->param('keywords')) . '+site%3Awww.sammyshp.de%2Fbetablog%2F');
    });

    // Installation
    respond('GET', '/install', function ($request, $response) {
        $response->requireLogin($request, $response);

        Post::install();
        $response->redirect($response->baseurl);
    });

});

dispatch();
