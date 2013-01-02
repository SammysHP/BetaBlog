<?php

final class Config {
    // Database configuration
    const DB_HOST       = '';
    const DB_DATABASE   = '';
    const DB_USER       = '';
    const DB_PASSWORD   = '';
    const DB_PREFIX     = 'blog_';

    // Login password
    // 'admin_pw_hash' = md5('salt' . 'password')
    const LOGIN_PW_HASH = '';
    const SALT          = '';

    // Base URL after domain (with trailing slash)
    // 'base_url' = http://example.com/blog/
    //                                ^^^^^^ <-- only this
    const BASE_URL      = '/';

    // URL to "info" page
    const INFO_URL      = '';

    // URL to "about us" page
    const ABOUT_URL     = '';

    // Posts per page
    const PAGESIZE      = 5;

    // HTML title
    const PAGE_TITLE    = 'BetaBlog';

    // Text in header
    const HEADER_TITLE  = 'BetaBlog';



    private function __construct() {
    }
}
