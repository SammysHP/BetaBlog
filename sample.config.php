<?php

abstract class CONFIG {
    private static $configdata = array(
        // Database configuration
        'db_host'         => '',
        'db_database'     => '',
        'db_user'         => '',
        'db_pass'         => '',
        'db_prefix'       => '',

        // Login password
        // 'admin_pw_hash' = md5('salt' . 'password')
        'admin_pw_hash'   => '',
        'salt'            => '',

        // Base URL after domain (with trailing slash)
        // 'base_url' = http://example.com/blog/
        //                                ^^^^^^ <-- only this
        'base_url'        => '/',

        // URL to "info" page
        'info_url'        => '',

        // URL to "about us" page
        'about_url'       => '',

        // Posts per page
        'pagesize'        => 10,

        // Text in header
        'header_title'    => ''
    );
    
    public static function GET($name) {
        if(isset(self::$configdata[$name])) {
            return self::$configdata[$name];
        }
        return null;
    }
}
