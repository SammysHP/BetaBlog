<?php

require_once("config.php");
require_once("lib/Database.php");
require_once("lib/Post.php");
require_once("lib/Comment.php");
require_once("lib/Tag.php");
require_once("lib/klein.php");

setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'de');

$namespace = rtrim(Config::BASE_URL, '/');

with($namespace, function () {

    // Environment
    respond(function ($request, $response) {
        $response->htmlescape = function ($str) {
            return htmlspecialchars($str, ENT_COMPAT, 'UTF-8');
        };
        $response->requireLogin = function ($request, $response) {
            if (!$request->session('loggedin', false)) {
                $response->redirect(Config::BASE_URL . 'login');
            }
        };
        $response->layout('tpl/page.html');
        $response->loggedin = (boolean) $request->session('loggedin', false);
        $response->title = Config::PAGE_TITLE;
        $response->baseurl = Config::BASE_URL;
        $response->backurl = $request->session('backurl', $response->baseurl);
        $response->abouturl = Config::ABOUT_URL;
        $response->infourl = Config::INFO_URL;
        $response->headertitle = Config::HEADER_TITLE;
        $response->onError(function ($response, $err_msg) {
            $response->render('tpl/error.html');
        });
    });

    // Pages
    respond('GET', '@^/(?:page/(?P<page>\d+))?$', function ($request, $response) {
        $response->session('backurl', $request->uri());
        $response->posts = Post::findByPage($request->param('page', 1), Config::PAGESIZE, !$response->loggedin);
        $response->paginationCurrent = $request->param('page', 1);
        $response->paginationCount = ceil(Post::getPostCount(!$response->loggedin) / Config::PAGESIZE);
        $response->render('tpl/posts.html');
    });

    // Single post
    respond('GET', '/post/[i:id]', function ($request, $response) {
        $response->session('backurl', $request->uri());

        $response->post = Post::findById($request->param('id'), !$response->loggedin);
        $response->title .= ' – ' . $response->post->getTitle();
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
        $response->htmlkeywords = implode(', ', $tags);

        $response->render('tpl/post.html');
    });

    // Archive
    respond('GET', '/archive', function ($request, $response) {
        $response->session('backurl', $request->uri());
        $response->posts = Post::findAll(!$response->loggedin);
        $response->render('tpl/archive.html');
    });

    // Post RSS
    respond('GET', '/rss', function ($request, $response) {
        $response->posts = Post::findByPage(1, 10);
        $response->header('Content-type', 'application/rss+xml');
        $response->partial('tpl/rssposts.html');
    });

    // Comment RSS
    respond('GET', '/rss/comments', function ($request, $response) {
        $response->comments = Comment::findByPage(1, 20);
        $response->header('Content-type', 'application/rss+xml');
        $response->partial('tpl/rsscomments.html');
    });

    // Tag-search
    respond('GET', '/tag/[*:tag]', function ($request, $response) {
        $response->session('backurl', $request->uri());
        $tag = rawurldecode(str_replace('/', '%2F', $request->param('tag')));
        $response->posts = Post::findByTag(array($tag), !$response->loggedin);
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
        if (Config::LOGIN_PW_HASH == md5(Config::SALT . $request->param('password', ''))) {
            $response->session('loggedin', true);
            $response->redirect($response->backurl);
        }

        $response->flash('Falsches Passwort', 'error');
        $response->redirect($response->baseurl . 'login');
    });

    // Logout
    respond('GET', '/logout', function ($request, $response) {
        $response->flash('Erfolgreich abgemeldet.', 'success');
        $response->session('loggedin', false);
        $response->redirect($response->baseurl);
    });

    // Create (view)
    respond('GET', '/create', function ($request, $response) {
        $response->requireLogin($request, $response);

        $response->post = new Post();
        $response->alltags = Tag::findAll();
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
            preg_split('@,\s?@', stripslashes($request->param('tags')), NULL, PREG_SPLIT_NO_EMPTY),
            ($request->param('published') != '')
        );
        $post->create();

        $response->flash('Beitrag gespeichert.', 'success');

        if ($request->param('save', false)) {
            $response->redirect($response->baseurl . 'post/' . $post->getId() . '/edit');
        } else {
            $response->redirect($response->baseurl . 'post/' . $post->getId());
        }
    });

    // Edit (view)
    respond('GET', '/post/[i:id]/edit', function ($request, $response) {
        $response->requireLogin($request, $response);

        $response->post = Post::findById($request->param('id'), false);
        $response->alltags = Tag::findAll();
        $response->render('tpl/postform.html');
    });

    // Edit (handler)
    respond('POST', '/post/[i:id]/edit', function ($request, $response) {
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
        $post->save();

        $response->flash('Beitrag gespeichert.', 'success');

        if ($request->param('save', false)) {
            $response->redirect($response->baseurl . 'post/' . $post->getId() . '/edit');
        } else {
            $response->redirect($response->baseurl . 'post/' . $post->getId());
        }
    });

    // Delete (view)
    respond('GET', '/post/[i:id]/delete', function ($request, $response) {
        $response->requireLogin($request, $response);

        $response->post = Post::findById($request->param('id'), false);
        $response->render('tpl/delete.html');
    });

    // Delete (handler)
    respond('POST', '/post/[i:id]/delete', function ($request, $response) {
        $response->requireLogin($request, $response);

        if ($request->param('delete') != '') {
            POST::delete($request->param('id'));
            $response->flash('Beitrag gelöscht.', 'success');
            $response->redirect($response->baseurl);
        } else {
            $response->redirect($response->baseurl . 'post/' . $request->param('id'));
        }
    });

    // Search (view)
    respond('GET', '/search', function ($request, $response) {
        $response->alltags = Tag::findAll();
        $response->render('tpl/search.html');
    });

    // Search (handler)
    respond('POST', '/search', function ($request, $response) {
        $searchstring = stripslashes($request->param('keywords')) . ' site:' . rtrim($_SERVER['HTTP_HOST'], '/') . $response->baseurl;
        $response->redirect('http://www.google.com/search?hl=de&q=' . rawurlencode($searchstring));
    });

    // Create Comment
    respond('POST', '/post/[i:id]/comment', function ($request, $response) {
        // Check if post exists
        Post::findById($request->param('id'));

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
        $comment->create();

        $response->flash('Kommentar gespeichert.', 'success');
        $response->redirect($response->baseurl . 'post/' . $request->param('id'));
    });

    // Delete Comment
    respond('GET', '/comment/[i:id]/delete', function ($request, $response) {
        $response->requireLogin($request, $response);

        Comment::delete($request->param('id'));

        $response->flash('Kommentar gelöscht.', 'success');
        $response->redirect($response->backurl);
    });

    // Installation
    respond('GET', '/install', function ($request, $response) {
        $response->requireLogin($request, $response);

        Post::install();
        Tag::install();
        Comment::install();
        $response->flash('System erfolgreich installiert.', 'success');
        $response->redirect($response->baseurl);
    });

});

dispatch();
