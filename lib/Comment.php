<?php

/**
 * Model of a comment.
 */
class Comment {
    private $id;
    private $post;
    private $author;
    private $comment;
    private $date;

    /**
     * Create a new comment.
     *
     * @param int $post
     * @param string $author
     * @param string $comment
     * @param long $date
     * @param int $id
     */
    public function __construct($post = -1, $author = "", $comment = "", $date = -1, $id = -1) {
        $this->setPost($post);
        $this->setAuthor($author);
        $this->setComment($comment);
        $this->setDate($date);
        $this->id = (int) $id;
    }

    /**
     * Find a comment by its ID.
     *
     * @param int $id
     * @return Comment
     * @throws CommentNotFoundException if comment with given id does not exist
     * @throws DatabaseException
     */
    public static function findById($id) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT post, author, comment, date FROM ' . Config::DB_PREFIX . 'comments WHERE id=?'))) {
            throw new DatabaseException("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("i", $id)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new DatabaseException("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($post, $author, $comment, $date)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        if (!$statement->fetch()) {
            throw new CommentNotFoundException($id);
        }

        return new Comment($post, $author, $comment, $date, $id);
    }

    /**
     * Find all comments of a post post.
     *
     * @param int $post The ID of the post
     * @return Comment[]
     * @throws DatabaseException
     */
    public static function findByPost($post) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT id, author, comment, date FROM ' . Config::DB_PREFIX . 'comments WHERE post=? ORDER BY date ASC'))) {
            throw new DatabaseException("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("i", $post)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new DatabaseException("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($id, $author, $comment, $date)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        $result = array();

        while ($statement->fetch()) {
            $result[] = new Comment($post, $author, $comment, $date, $id);
        }

        return $result;
    }

    /**
     * Find comments for a specific page.
     *
     * This includes comments of all posts.
     *
     * @param int $pageNo The page number, starting from 1
     * @param int $pageSize The number of comments for each page
     * @return Comment[]
     * @throws DatabaseException
     */
    public static function findByPage($pageNo, $pageSize) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT id, post, author, comment, date FROM ' . Config::DB_PREFIX . 'comments ORDER BY date DESC LIMIT ?, ?'))) {
            throw new DatabaseException("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("ii", ($page = ($pageNo - 1) * $pageSize), $pageSize)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new DatabaseException("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($id, $post, $author, $comment, $date)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        $result = array();

        while ($statement->fetch()) {
            $result[] = new Comment($post, $author, $comment, $date, $id);
        }

        return $result;
    }

    /**
     * Counts all comments for a specific post.
     *
     * @param int $post The ID of the post
     * @return int
     * @throws DatabaseException
     */
    public static function getCommentCount($post) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT count(*) FROM ' . Config::DB_PREFIX . 'comments WHERE post=?'))) {
            throw new DatabaseException("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("i", $post)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new DatabaseException("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($count)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        if ($statement->fetch()) {
            return $count;
        }

        return 0;
    }

    /**
     * Load comment count for a list of posts.
     *
     * @param Post[] $posts A list of posts
     * @return Post[]
     * @throws DatabaseException
     */
    public static function loadCommentCount(array $posts) {
        if (count($posts) == 0) {
            return $posts;
        }

        $ids = array();
        foreach ($posts as $post) {
            $ids[] = (int) $post->getId();
        }
        $ids = array_unique($ids);

        $db = Database::getConnection();

        if (!$dbResult = $db->query("SELECT post, count(*) AS count FROM " . Config::DB_PREFIX . 'comments WHERE post IN (' . implode(', ', $ids) . ') GROUP BY post')) {
            throw new DatabaseException("Execute failed: (" . $db->errno . ") " . $db->error);
        }

        $count = array();
        while ($row = $dbResult->fetch_assoc()) {
            $count[$row['post']] = $row['count'];
        }
        $dbResult->free();

        foreach ($posts as $post) {
            if (array_key_exists($post->getId(), $count)) {
                $post->setCommentCount($count[$post->getId()]);
            }
        }

        return $posts;
    }

    /**
     * Saves a new comment.
     *
     * A new ID will be generated.
     *
     * @return Comment this comment
     * @throws DatabaseException
     */
    public function create() {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('INSERT INTO ' . Config::DB_PREFIX . 'comments (post, author, comment, date) VALUES (?, ?, ?, ?)'))) {
            throw new DatabaseException("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("issi", $this->post, $this->author, $this->comment, $this->date)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new DatabaseException("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }

        $this->id = $db->insert_id;

        return $this;
    }

    /**
     * Deletes a comment.
     *
     * @param int $id The ID of the comment
     * @throws DatabaseException
     */
    public static function delete($id) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('DELETE FROM ' . Config::DB_PREFIX . 'comments WHERE id=?'))) {
            throw new DatabaseException("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("i", $id)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new DatabaseException("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }

        if ($statement->affected_rows == 0) {
            throw new CommentNotFoundException($id);
        }
    }

    /**
     * Deletes all comment for a specific post.
     *
     * @param int $post The ID of the post
     * @throws DatabaseException
     */
    public static function deleteAll($post) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('DELETE FROM ' . Config::DB_PREFIX . 'comments WHERE post=?'))) {
            throw new DatabaseException("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("i", $post)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new DatabaseException("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
    }

    /**
     * Creates the database table.
     *
     * @throws Exception on any error
     */
    public static function install() {
        $db = Database::getConnection();

        if (!$db->query('CREATE TABLE IF NOT EXISTS ' . Config::DB_PREFIX . 'comments (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, post INT NOT NULL, author VARCHAR(100) NOT NULL, comment TEXT NOT NULL, date INT UNSIGNED NOT NULL) CHARACTER SET utf8 COLLATE utf8_unicode_ci')) {
            throw new DatabaseException("Could not create table" . Config::DB_PREFIX . "comments: (" . $db->errno . ") " . $db->error);
        }
    }

    /**
     * Get the id of this comment.
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get the author of this comment.
     *
     * @return string
     */
    public function getAuthor() {
        return $this->author;
    }

    /**
     * Set the author of this comment.
     *
     * @param string $value If empty, "Anonym" will be used
     * @return Comment this comment
     */
    public function setAuthor($value) {
        $value = (string) $value;
        $this->author = empty($value) ? "Anonym" : $value;
        return $this;
    }

    /**
     * Get the content of this comment.
     *
     * @return string
     */
    public function getComment() {
        return $this->comment;
    }

    /**
     * Set the content of this comment.
     *
     * @param string $value
     * @return Comment this comment
     */
    public function setComment($value) {
        $this->comment = (string) $value;
        return $this;
    }

    /**
     * Get the id of the post of this comment.
     *
     * @return int
     */
    public function getPost() {
        return $this->post;
    }

    /**
     * Set the id of the post of this comment.
     *
     * @param int $value
     * @return Comment this comment
     */
    public function setPost($value) {
        $this->post = (int) $value;
        return $this;
    }

    /**
     * Get the date of this comment.
     *
     * @return long
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * Set the date of this comment.
     *
     * @param long $value If 0, the current time is used
     * @return Comment this comment
     */
    public function setDate($value) {
        $value = (int) $value;
        $this->date = $value < 0 ? time() : $value;
        return $this;
    }
}
