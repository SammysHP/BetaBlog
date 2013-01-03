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
                $response->session('backurl', $request->uri());
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
        $response->flash('Erfolgreich abgemeldet', 'success');
        $response->session('loggedin', false);
        $response->redirect($response->baseurl);
    });

    // Create post (view)
    respond('GET', '/create', function ($request, $response) {
        $response->requireLogin($request, $response);

        $response->post = new Post();
        $response->alltags = Tag::findAll();
        $response->render('tpl/postform.html');
    });

    // Create post (handler)
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

        $response->flash('Beitrag gespeichert', 'success');

        if ($request->param('save', false)) {
            $response->redirect($response->baseurl . 'post/' . $post->getId() . '/edit');
        } else {
            $response->redirect($response->baseurl . 'post/' . $post->getId());
        }
    });

    // Edit post (view)
    respond('GET', '/post/[i:id]/edit', function ($request, $response) {
        $response->requireLogin($request, $response);

        $response->post = Post::findById($request->param('id'), false);
        $response->alltags = Tag::findAll();
        $response->render('tpl/postform.html');
    });

    // Edit post (handler)
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

        $response->flash('Beitrag gespeichert', 'success');

        if ($request->param('save', false)) {
            $response->redirect($response->baseurl . 'post/' . $post->getId() . '/edit');
        } else {
            $response->redirect($response->baseurl . 'post/' . $post->getId());
        }
    });

    // Delete post (view)
    respond('GET', '/post/[i:id]/delete', function ($request, $response) {
        $response->requireLogin($request, $response);
        $response->render('tpl/delete.html');
    });

    // Delete post (handler)
    respond('POST', '/post/[i:id]/delete', function ($request, $response) {
        $response->requireLogin($request, $response);

        if ($request->param('delete') != '') {
            POST::delete($request->param('id'));
            $response->flash('Beitrag gelöscht', 'success');
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

    // Create comment
    respond('POST', '/post/[i:id]/comment', function ($request, $response) {
        // Check if post exists
        Post::findById($request->param('id'), false);

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

        $response->flash('Kommentar gespeichert', 'success');
        $response->redirect($response->baseurl . 'post/' . $request->param('id'));
    });

    // Delete comment (view)
    respond('GET', '/comment/[i:id]/delete', function ($request, $response) {
        $response->requireLogin($request, $response);
        $response->render('tpl/delete.html');
    });

    // Delete comment (handler)
    respond('POST', '/comment/[i:id]/delete', function ($request, $response) {
        $response->requireLogin($request, $response);

        if ($request->param('delete') != '') {
            Comment::delete($request->param('id'));
            $response->flash('Kommentar gelöscht', 'success');
        }

        $response->redirect($response->backurl);
    });

    // List files
    respond('GET', '/files', function ($request, $response) {
        $response->requireLogin($request, $response);

        $response->sorting = $request->session('filesorting', 'name');

        $directory = Config::UPLOAD_DIR;
        $files = array();
        $content = scandir($directory);
        
        foreach ($content as $file) {
            if (substr($file, 0, 1) == '.') {
                continue;
            }
            
            $entry['name'] = utf8_encode($file);
            $entry['time'] = filemtime($directory . '/' . $file);
            
            $files[] = $entry;
        }

        switch ($response->sorting) {
            case 'name':
                usort($files, function ($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });
                break;
            case 'nameup':
                usort($files, function ($a, $b) {
                    return strcmp($b['name'], $a['name']);
                });
                break;
            case 'date':
                usort($files, function ($a, $b) {
                    return $a['time'] - $b['time'];
                });
                break;
            case 'dateup':
                usort($files, function ($a, $b) {
                    return $b['time'] - $a['time'];
                });
                break;
        }

        $response->files = $files;
        $response->render('tpl/files.html');
    });

    // Upload file
    respond('POST', '/files/upload', function ($request, $response) {
        $response->requireLogin($request, $response);

        $file = $_FILES['upload'];

        if (!file_exists($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $response->flash('Keine Datei angegeben', 'error');
            $response->redirect($response->baseurl . 'files');
        }

        $filename = utf8_decode(basename($request->param('filename', '')));
        if (empty($filename)) {
            $filename = basename($file['name']);
        }

        if (substr($filename, -4) == '.php') {
            $response->flash('Dateityp nicht erlaubt', 'error');
            $response->redirect($response->baseurl . 'files');
        }

        $targetpath = Config::UPLOAD_DIR . '/' . $filename;

        if ($request->param('overwrite') == null && file_exists($targetpath)) {
            $response->flash('Datei existiert bereits', 'error');
            $response->redirect($response->baseurl . 'files');
        }

        if (move_uploaded_file($file['tmp_name'], $targetpath)) {
            $response->flash('Datei hochgeladen', 'success');
        } else {
            $response->flash('Fehler beim Hochladen', 'error');
        }

        $response->redirect($response->baseurl . 'files');
    });

    // Delete file (view)
    respond('GET', '/files/delete/[:name]', function ($request, $response) {
        $response->requireLogin($request, $response);
        $response->render('tpl/delete.html');
    });

    // Delete file (handler)
    respond('POST', '/files/delete/[:name]', function ($request, $response) {
        $response->requireLogin($request, $response);

        if ($request->param('delete') != '') {
            $filename = utf8_decode(basename(rawurldecode($request->param('name'))));
            $filepath = Config::UPLOAD_DIR . '/' . $filename;

            if (substr($filename, 0, 1) == '.' || !file_exists($filepath)) {
                $response->flash('Datei nicht gefunden', 'error');
                $response->redirect($response->baseurl . 'files');
            }

            unlink($filepath);
            $response->flash('Datei gelöscht', 'success');
        }

        $response->redirect($response->baseurl . 'files');
    });

    // Sort file (view)
    respond('GET', '/files/sort/[:column]', function ($request, $response) {
        $response->requireLogin($request, $response);
        $response->session('filesorting', $request->param('column'));
        $response->redirect($response->baseurl . 'files');
    });

    // Installation
    respond('GET', '/install', function ($request, $response) {
        $response->requireLogin($request, $response);

        Post::install();
        Tag::install();
        Comment::install();
        $response->flash('System erfolgreich installiert', 'success');
        $response->redirect($response->baseurl);
    });

});

dispatch();
