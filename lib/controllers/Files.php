<?php
namespace controllers;

use Config;

class Files {
    // List files
    public static function listFiles($request, $response) {
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
    }

    // Upload file
    public static function uploadFile($request, $response) {
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
    }

    // Delete confirmation
    public static function deleteConfirmation($request, $response) {
        $response->requireLogin($request, $response);
        $response->message = 'Datei "' . rawurldecode($request->param('name')) . '"';
        $response->render('tpl/delete.html');
    }

    // Delete file
    public static function deleteFile($request, $response) {
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
    }

    // Sort files
    public static function sortFiles($request, $response) {
        $response->requireLogin($request, $response);
        $response->session('filesorting', $request->param('column'));
        $response->redirect($response->baseurl . 'files');
    }

    // Rename dialog
    public static function renameDialog($request, $response) {
        $response->requireLogin($request, $response);
        $response->filename = rawurldecode($request->param('name'));
        $response->render('tpl/rename.html');
    }

    // Rename file
    public static function renameFile($request, $response) {
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
    }
}
