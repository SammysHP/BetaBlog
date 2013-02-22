<?php

require_once("config.php");
require_once("lib/Comment.php");
require_once("lib/Database.php");
require_once("lib/Exceptions.php");
require_once("lib/Post.php");
require_once("lib/SimpleBars.php");
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
        $response->onError(function ($response, $errormessage) {
            $response->code(500);
            $response->message = $errormessage;
            $response->render('tpl/error.html');
        });
    });

    // Pages
    respond('GET', '@^/(?:page/(?P<page>\d+))?$', function ($request, $response) {
        $response->session('backurl', $request->uri());
        $response->posts = Post::findByPage($request->param('page', 1), Config::PAGESIZE, !$response->loggedin);
        $response->paginationCurrent = $request->param('page', 1);
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
    });

    // Single post
    respond('GET', '/post/[i:id]', function ($request, $response) {
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
            $response->htmlkeywords = implode(', ', $tags);

            $response->render('tpl/post.html');
        } catch (PostNotFoundException $e) {
            $response->flash('Beitrag nicht gefunden', 'error');
            $response->code(404);
            $response->render('tpl/plain.html');
        }
    });

    // Statistics
    respond('GET', '/archive', function ($request, $response) {
        $response->session('backurl', $request->uri());
        $response->title .= Config::PAGE_TITLE_S . 'Archiv';

        $statistics = Post::getYearStatistics(!$response->loggedin);

        // Fill up to 3*n matrix
        $start = $statistics['first'] - 2 + (($statistics['last'] - $statistics['first']) % 3);
        $response->graphs = array();
        for ($year = $start; $year <= $statistics['last']; $year++) {
            if (array_key_exists($year, $statistics['data'])) {
                $data = $statistics['data'][$year];
            } else {
                $data = array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0, 11 => 0, 12 => 0);
            }

            $graph = new SimpleBars();
            $graph->setData($data)
                ->setTitle($year)
                ->setBarWidth(15)
                ->setBarMargin(5)
                ->setGraphHeight(100)
                ->setMaxValue($statistics['max']);

            $response->graphs[$year] = $graph->render();
        }

        $response->postcount = Post::getPostCount(!$response->loggedin);

        $response->render('tpl/statistics.html');
    });

    // Archive
    respond('GET', '/archive/[i:year]', function ($request, $response) {
        $response->session('backurl', $request->uri());
        $response->posts = Post::findByYear($request->param('year'), !$response->loggedin);
        $response->title .= Config::PAGE_TITLE_S . 'Archiv (' . $request->param('year') . ')';
        $response->year = $request->param('year');

        if (count($response->posts) == 0) {
            $response->code(404);
        }

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
        $response->title .= Config::PAGE_TITLE_S . $response->htmlescape($tag);
        $response->posts = Post::findByTag(array($tag), !$response->loggedin);

        if (count($response->posts) == 0) {
            $response->code(404);
        }

        $response->render('tpl/archive.html');
    });

    // Login (view)
    respond('GET', '/login', function ($request, $response) {
        if ($request->session('loggedin', false)) {
            $response->redirect($response->baseurl);
        }

        $response->title .= Config::PAGE_TITLE_S . 'Login';

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
        $response->title .= Config::PAGE_TITLE_S . 'Beitrag erstellen';
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
    });

    // Edit post (view)
    respond('GET', '/post/[i:id]/edit', function ($request, $response) {
        $response->requireLogin($request, $response);

        try {
            $response->post = Post::findById($request->param('id'), false);
        } catch (PostNotFoundException $e) {
            $response->flash('Beitrag nicht gefunden', 'error');
            $response->code(404);
            $response->render('tpl/plain.html');
            exit;
        }

        $response->alltags = Tag::findAll();
        $response->title .= Config::PAGE_TITLE_S . 'Beitrag bearbeiten';
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
    });

    // Publish/retract post
    respond('GET', '/post/[i:id]/[publish|retract:action]', function ($request, $response) {
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
    });

    // Delete post (view)
    respond('GET', '/post/[i:id]/delete', function ($request, $response) {
        $response->requireLogin($request, $response);

        try {
            $post = Post::findById($request->param('id'), false);
            $response->message = 'Beitrag "' . $post->getTitle() . '"';
            $response->render('tpl/delete.html');
        } catch (PostNotFoundException $e) {
            $response->flash('Beitrag nicht gefunden', 'error');
            $response->code(404);
            $response->render('tpl/plain.html');
        }
    });

    // Delete post (handler)
    respond('POST', '/post/[i:id]/delete', function ($request, $response) {
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
            $response->redirect($response->baseurl . 'post/' . $request->param('id'));
        }
    });

    // Search (view)
    respond('GET', '/search', function ($request, $response) {
        $response->alltags = Tag::findAll();
        $response->title .= Config::PAGE_TITLE_S . 'Suche';
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
    });

    // Delete comment (view)
    respond('GET', '/comment/[i:id]/delete', function ($request, $response) {
        $response->requireLogin($request, $response);

        try {
            $comment = Comment::findById($request->param('id'));
            $response->message = 'Kommentar von "' . $comment->getAuthor() . '" vom ' . date('d.m.Y \u\m H:i', $comment->getDate());
            $response->render('tpl/delete.html');
        } catch (CommentNotFoundException $e) {
            $response->flash('Kommentar nicht gefunden', 'error');
            $response->code(404);
            $response->render('tpl/plain.html');
        }
    });

    // Delete comment (handler)
    respond('POST', '/comment/[i:id]/delete', function ($request, $response) {
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
    });

    // List files
    respond('GET', '/files', function ($request, $response) {
        $response->requireLogin($request, $response);

        $response->title .= Config::PAGE_TITLE_S . 'Dateien';

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

        if (substr($filename, 0, 1) == '.' || substr($filename, -4) == '.php') {
            $response->flash('Dateiname nicht erlaubt', 'error');
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
        $response->message = 'Datei "' . rawurldecode($request->param('name')) . '"';
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

    // Rename file (view)
    respond('GET', '/files/rename/[:name]', function ($request, $response) {
        $response->requireLogin($request, $response);
        $response->filename = rawurldecode($request->param('name'));
        $response->render('tpl/rename.html');
    });

    // Rename file (handler)
    respond('POST', '/files/rename/[:name]', function ($request, $response) {
        $response->requireLogin($request, $response);

        $oldname = utf8_decode(basename(rawurldecode($request->param('name'))));
        $newname = utf8_decode(basename($request->param('newname')));
        $oldfile = Config::UPLOAD_DIR . '/' . $oldname;
        $newfile = Config::UPLOAD_DIR . '/' . $newname;

        if (substr($oldname, 0, 1) == '.' || !file_exists($oldfile)) {
            $response->flash('Datei nicht gefunden', 'error');
            $response->redirect($response->baseurl . 'files');
        }

        if (empty($newname) || substr($newname, 0, 1) == '.' || substr($newname, -4) == '.php') {
            $response->flash('Dateiname nicht erlaubt', 'error');
            $response->redirect($response->baseurl . 'files');
        }

        if (file_exists($newfile)) {
            $response->flash('Datei existiert bereits', 'error');
            $response->redirect($response->baseurl . 'files');
        }

        if (rename($oldfile, $newfile)) {
            $response->flash('Datei umbenannt', 'success');
        } else {
            $response->flash('Fehler beim Umbenennen', 'error');
        }

        $response->redirect($response->baseurl . 'files');
    });

    // Installation
    respond('GET', '/install', function ($request, $response) {
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
    });

});

dispatch();
