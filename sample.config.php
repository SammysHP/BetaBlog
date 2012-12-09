<?php

/*
    'admin_pw_hash' = md5('salt' . 'password')
    
    'pase_url' = http://example.com/blog/
                                   ^^^^^  <-- only this
*/

abstract class CONFIG {
    private static $configdata = array(
        'db_host'         => '',
        'db_database'     => '',
        'db_user'         => '',
        'db_pass'         => '',
        'db_prefix'       => '',
        
        'admin_pw_hash'   => '',
        'salt'            => '',
        'base_url'        => '',
        'pagesize'        => 10
    );
    
    public static function GET($name) {
        if(isset(self::$configdata[$name])) {
            return self::$configdata[$name];
        }
        return null;
    }
}
