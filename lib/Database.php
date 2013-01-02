<?php

class Database {
    private static $instance;

    private $connection;

    private function __construct() {
        $connection = new mysqli(Config::DB_HOST, Config::DB_USER, Config::DB_PASSWORD, Config::DB_DATABASE);
        if($connection->connect_errno) {
            throw new Exception('Failed to connect to database: (' . $connection->connect_errno . ') ' . $connection->connect_error);
        }

        if (!$connection->query('SET NAMES utf8')) {
            throw new Exception("Execute failed: (" . $connection->errno . ") " . $connection->error);
        }

        $this->connection = $connection;
    }

    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public static function getConnection() {
        $i = self::getInstance();
        return $i->connection;
    }
}
