<?php
require_once('config.php');
require_once('lib/klein.php');
require_once('lib/autoloader.php');

setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'de');

respond('\controllers\Environment::initialize');


// Pages
respond('GET',  '@^/(?:page/(?P<page>\d+))?$',      '\controllers\Pages::showPage');

// Single post
respond('GET',  '/post/[i:id]',                     '\controllers\Posts::showSinglePost');

// Archive
respond('GET',  '/archive',                         '\controllers\Archive::showOverview');
respond('GET',  '/archive/[i:year]',                '\controllers\Archive::showYear');

// RSS feed
respond('GET',  '/rss',                             '\controllers\Rss::posts');
respond('GET',  '/rss/comments',                    '\controllers\Rss::comments');

// Authentication
respond('GET',  '/login',                           '\controllers\Authentication::showLoginForm');
respond('POST', '/login',                           '\controllers\Authentication::login');
respond('GET',  '/logout',                          '\controllers\Authentication::logout');

// Post administration
respond('GET',  '/create',                          '\controllers\Posts::showPostForm');
respond('POST', '/create',                          '\controllers\Posts::createPost');
respond('GET',  '/post/[i:id]/edit',                '\controllers\Posts::showPostForm');
respond('POST', '/post/[i:id]/edit',                '\controllers\Posts::editPost');
respond('GET',  '/post/[i:id]/[publish|retract:action]', '\controllers\Posts::changePublishStatus');
respond('GET',  '/post/[i:id]/delete',             '\controllers\Posts::showDeleteConfirmation');
respond('POST', '/post/[i:id]/delete',             '\controllers\Posts::deletePost');

// Search
respond('GET',  '/search',                         '\controllers\Search::showSearchPage');
respond('POST', '/search',                         '\controllers\Search::remoteSearch');
respond('GET',  '/tag/[*:tag]',                    '\controllers\Search::tagSearch');

// Comments
respond('POST', '/post/[i:id]/comment',            'controllers\Comments::createComment');
respond('GET',  '/comment/[i:id]/delete',          'controllers\Comments::deleteConfirmation');
respond('POST', '/comment/[i:id]/delete',          'controllers\Comments::deleteComment');

// Files
respond('GET',  '/files',                          '\controllers\Files::listFiles');
respond('POST', '/files/upload',                   '\controllers\Files::uploadFile');
respond('GET',  '/files/delete/[:name]',           '\controllers\Files::deleteConfirmation');
respond('POST', '/files/delete/[:name]',           '\controllers\Files::deleteFile');
respond('GET',  '/files/sort/[:column]',           '\controllers\Files::sortFiles');
respond('GET',  '/files/rename/[:name]',           '\controllers\Files::renameDialog');
respond('POST', '/files/rename/[:name]',           '\controllers\Files::renameFile');

// Maintenance
respond('GET',  '/install',                        '\controllers\Maintenance::install');


dispatch(substr($_SERVER['REQUEST_URI'], strlen(Config::BASE_URL) - 1));
