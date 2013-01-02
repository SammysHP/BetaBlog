<?php

class Post {
    // TODO: Make statements static

    private $id;
    private $title;
    private $content;
    private $extended;
    private $date;
    private $tags;
    private $published;

    public function __construct($title = "", $content = "<p>\n\n</p>", $extended = "", $date = -1, $tags = array(), $published = true, $id = -1) {
        $this->setTitle($title);
        $this->setContent($content);
        $this->setExtended($extended);
        $this->setDate($date);
        $this->setTags($tags);
        $this->setPublished($published);
        $this->id = (int) $id;
    }

    public static function findById($id, $publishedIn = true) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT title, content, extended, date, published FROM ' . Config::DB_PREFIX . 'posts WHERE id=? AND published>=?'))) {
            throw new Exception("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("ii", $id, $publishedIn)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new Exception("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($title, $content, $extended, $date, $published)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        if (!$statement->fetch()) {
            throw new Exception("Post with id=" . $id . " not found.");
        }

        $post = new Post($title, $content, $extended, $date, array(), $published, $id);
        $statement->close();
        $post->setTags(Tag::findByPost($post->getId()));
        return $post;
    }

    public static function findByTag(array $tags, $publishedIn = true) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT DISTINCT id, title, content, extended, date, published FROM ' . Config::DB_PREFIX . 'posts JOIN ' . Config::DB_PREFIX . 'tags ON (post=id) WHERE published>=? AND tag=? ORDER BY date DESC'))) {
            throw new Exception("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("is", $publishedIn, $tags[0])) { // TODO
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new Exception("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($id, $title, $content, $extended, $date, $published)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        $result = array();

        while ($statement->fetch()) {
            $result[] = new Post($title, $content, $extended, $date, array(), $published, $id);
        }

        foreach ($result as $post) {
            $post->setTags(Tag::findByPost($post->getId()));
        }

        return $result;
    }

    public static function findByPage($pageNo, $pageSize, $publishedIn = true) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT id, title, content, extended, date, published FROM ' . Config::DB_PREFIX . 'posts WHERE published>=? ORDER BY date DESC LIMIT ?, ?'))) {
            throw new Exception("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("iii", $publishedIn, ($page = ($pageNo - 1) * $pageSize), $pageSize)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new Exception("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($id, $title, $content, $extended, $date, $published)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        $result = array();

        while ($statement->fetch()) {
            $result[] = new Post($title, $content, $extended, $date, array(), $published, $id);
        }

        foreach ($result as $post) {
            $post->setTags(Tag::findByPost($post->getId()));
        }

        return $result;
    }

    public static function findAll($publishedIn = true) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT id, title, content, extended, date, published FROM ' . Config::DB_PREFIX . 'posts WHERE published>=? ORDER BY date DESC'))) {
            throw new Exception("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("i", $publishedIn)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new Exception("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($id, $title, $content, $extended, $date, $published)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        $result = array();

        while ($statement->fetch()) {
            $result[] = new Post($title, $content, $extended, $date, array(), $published, $id);
        }

        foreach ($result as $post) {
            $post->setTags(Tag::findByPost($post->getId()));
        }

        return $result;
    }

    public static function getPostCount($published = true) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT count(*) FROM ' . Config::DB_PREFIX . 'posts WHERE published>=?'))) {
            throw new Exception("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("i", $published)) {
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

        if (!($statement = $db->prepare('INSERT INTO ' . Config::DB_PREFIX . 'posts (title, content, extended, date, published) VALUES (?, ?, ?, ?, ?)'))) {
            throw new Exception("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("sssii", $this->title, $this->content, $this->extended, $this->date, $this->published)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new Exception("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }

        $this->id = $db->insert_id;

        Tag::update($this->id, $this->tags);

        return $this;
    }

    public function save() {
        if ($this->id < 0) {
            return $this->create();
        }

        $db = Database::getConnection();

        if (!($statement = $db->prepare('UPDATE ' . Config::DB_PREFIX . 'posts SET title=?, content=?, extended=?, date=?, published=? WHERE id=?'))) {
            throw new Exception("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("sssiii", $this->title, $this->content, $this->extended, $this->date, $this->published, $this->id)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new Exception("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }

        Tag::update($this->id, $this->tags);

        return $this;
    }

    public static function delete($id) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('DELETE FROM ' . Config::DB_PREFIX . 'posts WHERE id=?'))) {
            throw new Exception("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("i", $id)) {
            throw new Exception("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new Exception("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }

        Tag::delete($id);
        Comment::deleteAll($id);
    }

    public static function install() {
        $db = Database::getConnection();

        if (!$db->query('CREATE TABLE IF NOT EXISTS ' . Config::DB_PREFIX . 'posts (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, title VARCHAR(100) NOT NULL, content TEXT NOT NULL, extended TEXT NOT NULL, date INT UNSIGNED NOT NULL, published BOOLEAN NOT NULL) CHARACTER SET utf8 COLLATE utf8_unicode_ci')) {
            throw new Exception("Could not create table" . Config::DB_PREFIX . "posts: (" . $db->errno . ") " . $db->error);
        }
    }

    public function getId() {
        return $this->id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($value) {
        $this->title = (string) $value;
        return $this;
    }

    public function getContent() {
        return $this->content;
    }

    public function setContent($value) {
        $this->content = (string) $value;
        return $this;
    }

    public function getExtended() {
        return $this->extended;
    }

    public function setExtended($value) {
        $this->extended = (string) $value;
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

    public function getTags() {
        return $this->tags;
    }

    public function setTags(array $value) {
        $this->tags = $value;
        return $this;
    }

    public function isPublished() {
        return $this->published;
    }

    public function setPublished($value) {
        $this->published = (boolean) $value;
        return $this;
    }
}
