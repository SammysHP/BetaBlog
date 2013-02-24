<?php
function autoloader($classname) {
    $path = 'lib/' . str_replace('\\', '/', $classname) . '.php';
    if (file_exists($path)) {
        require_once($path);
    }
}
spl_autoload_register('autoloader');
