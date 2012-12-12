<?php

class Tag {
    // TODO: Make statements static

    private function __construct() {
    }

    public static function findByPost($post) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT tag FROM ' . CONFIG::GET('db_prefix') . 'tags WHERE post=? ORDER BY tag ASC'))) {
            throw new Exception("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("i", $post)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new Exception("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($tag)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        $result = array();

        while ($statement->fetch()) {
            $result[] = $tag;
        }

        return $result;
    }

    public static function findAll() {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT tag, count(tag) FROM ' . CONFIG::GET('db_prefix') . 'tags GROUP BY tag ORDER BY tag ASC'))) {
            throw new Exception("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->execute()) {
            throw new Exception("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($tag, $count)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        $result = array();

        while ($statement->fetch()) {
            $result[] = array($tag, $count);
        }

        return $result;
    }

    public static function update($post, array $tags) {
        if (count($tags) == 0) {
            self::delete($post);
            return;
        }

        $db = Database::getConnection();

        $tags = array_unique($tags);

        foreach ($tags as &$tag) {
            $tag = "(" . (int) $post . ", '" . $db->real_escape_string($tag) . "')";
        }
        $tagString = implode(', ', $tags);

        self::delete($post);

        if (!$db->query('INSERT INTO ' . CONFIG::GET('db_prefix') . 'tags (post, tag) VALUES ' . $tagString)) {
            throw new Exception("Execute failed: (" . $db->errno . ") " . $db->error);
        }
    }

    public static function delete($post) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('DELETE FROM ' . CONFIG::GET('db_prefix') . 'tags WHERE post=?'))) {
            throw new Exception("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("i", $post)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new Exception("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
    }

    public static function install() {
        $db = Database::getConnection();

        if (!$db->query('CREATE TABLE IF NOT EXISTS ' . CONFIG::GET('db_prefix') . 'tags (post INT NOT NULL, tag VARCHAR(100) NOT NULL) CHARACTER SET utf8 COLLATE utf8_unicode_ci')) {
            throw new Exception("Could not create table" . CONFIG::GET('db_prefix') . "_tags: (" . $db->errno . ") " . $db->error);
        }
    }
}
