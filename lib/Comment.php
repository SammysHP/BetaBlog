<?php

class Comment {
    // TODO: Make statements static

    private $id;
    private $post;
    private $author;
    private $comment;
    private $date;

    public function __construct($post = -1, $author = "", $comment = "", $date = -1, $id = -1) {
        $this->setPost($post);
        $this->setAuthor($author);
        $this->setComment($comment);
        $this->setDate($date);
        $this->id = (int) $id;
    }

    public static function findById($id) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT post, author, comment, date FROM ' . Config::DB_PREFIX . 'comments WHERE id=?'))) {
            throw new Exception("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("i", $id)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new Exception("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($post, $author, $comment, $date)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        if (!$statement->fetch()) {
            throw new Exception("Post with id=" . $id . " not found.");
        }

        return new Comment($post, $author, $comment, $date, $id);
    }

    public static function findByPost($post) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT id, author, comment, date FROM ' . Config::DB_PREFIX . 'comments WHERE post=? ORDER BY date ASC'))) {
            throw new Exception("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("i", $post)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new Exception("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($id, $author, $comment, $date)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        $result = array();

        while ($statement->fetch()) {
            $result[] = new Comment($post, $author, $comment, $date, $id);
        }

        return $result;
    }

    public static function findByPage($pageNo, $pageSize) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT id, post, author, comment, date FROM ' . Config::DB_PREFIX . 'comments ORDER BY date DESC LIMIT ?, ?'))) {
            throw new Exception("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("ii", ($page = ($pageNo - 1) * $pageSize), $pageSize)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new Exception("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($id, $post, $author, $comment, $date)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        $result = array();

        while ($statement->fetch()) {
            $result[] = new Comment($post, $author, $comment, $date, $id);
        }

        return $result;
    }

    public static function getCommentCount($post) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT count(*) FROM ' . Config::DB_PREFIX . 'comments WHERE post=?'))) {
            throw new Exception("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("i", $post)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new Exception("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($count)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        if ($statement->fetch()) {
            return $count;
        }

        return 0;
    }

    public function create() {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('INSERT INTO ' . Config::DB_PREFIX . 'comments (post, author, comment, date) VALUES (?, ?, ?, ?)'))) {
            throw new Exception("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("issi", $this->post, $this->author, $this->comment, $this->date)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new Exception("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }

        $this->id = $db->insert_id;

        return $this;
    }

    public static function delete($id) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('DELETE FROM ' . Config::DB_PREFIX . 'comments WHERE id=?'))) {
            throw new Exception("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("i", $id)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new Exception("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
    }

    public static function deleteAll($post) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('DELETE FROM ' . Config::DB_PREFIX . 'comments WHERE post=?'))) {
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

        if (!$db->query('CREATE TABLE IF NOT EXISTS ' . Config::DB_PREFIX . 'comments (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, post INT NOT NULL, author VARCHAR(100) NOT NULL, comment TEXT NOT NULL, date INT UNSIGNED NOT NULL) CHARACTER SET utf8 COLLATE utf8_unicode_ci')) {
            throw new Exception("Could not create table" . Config::DB_PREFIX . "comments: (" . $db->errno . ") " . $db->error);
        }
    }

    public function getId() {
        return $this->id;
    }

    public function getAuthor() {
        return $this->author;
    }

    public function setAuthor($value) {
        $value = (string) $value;
        $this->author = empty($value) ? "Anonym" : $value;
        return $this;
    }

    public function getComment() {
        return $this->comment;
    }

    public function setComment($value) {
        $this->comment = (string) $value;
        return $this;
    }

    public function getPost() {
        return $this->post;
    }

    public function setPost($value) {
        $this->post = (int) $value;
        return $this;
    }

    public function getDate() {
        return $this->date;
    }

    public function setDate($value) {
        $value = (int) $value;
        $this->date = $value < 0 ? time() : $value;
        return $this;
    }
}
