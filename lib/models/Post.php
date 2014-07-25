<?php
namespace models;

use exceptions\DatabaseException;
use exceptions\PostNotFoundException;
use util\Database;
use util\StringUtils;

/**
 * Model of a post.
 */
class Post {
    private $id;
    private $title;
    private $content;
    private $extended;
    private $date;
    private $tags;
    private $published;
    private $commentCount;
    private $pevious;
    private $next;

    /**
     * Create a new post.
     *
     * @param strign $title
     * @param string $content
     * @param string $extended
     * @param long $date
     * @param string[] $tags
     * @param boolean $published
     * @param int $id
     */
    public function __construct($title = "", $content = "<p>\n\n</p>", $extended = "", $date = -1, $tags = array(), $published = false, $id = -1, $commentCount = 0) {
        $this->setTitle($title);
        $this->setContent($content);
        $this->setExtended($extended);
        $this->setDate($date);
        $this->setTags($tags);
        $this->setPublished($published);
        $this->id = (int) $id;
        $this->setCommentCount($commentCount);
    }

    /**
     * Find a post by its ID.
     *
     * @param int $id
     * @param boolean $publishedIn Only published or all
     * @return Post
     * @throws PostNotFoundException if post with given id does not exist
     * @throws DatabaseException
     */
    public static function findById($id, $publishedIn = true) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT title, content, extended, date, published FROM ' . Database::getPrefix() . 'posts WHERE id=? AND published>=?'))) {
            throw new DatabaseException("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("ii", $id, $publishedIn)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new DatabaseException("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($title, $content, $extended, $date, $published)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        if (!$statement->fetch()) {
            throw new PostNotFoundException($id);
        }

        $post = new Post($title, $content, $extended, $date, array(), $published, $id);
        $statement->close();
        $post->setTags(Tag::findByPost($post->getId()));
        $post->setCommentCount(Comment::getCommentCount($post->getId()));
        return $post;
    }

    /**
     * Find a post by its tags.
     *
     * @param string[] $tags
     * @param boolean $publishedIn Only published or all
     * @return Post[]
     * @throws DatabaseException
     */
    public static function findByTag(array $tags, $publishedIn = true) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT DISTINCT id, title, content, extended, date, published FROM ' . Database::getPrefix() . 'posts JOIN ' . Database::getPrefix() . 'tags ON (post=id) WHERE published>=? AND tag=? ORDER BY date DESC'))) {
            throw new DatabaseException("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("is", $publishedIn, $tags[0])) { // TODO support multiple tags
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new DatabaseException("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($id, $title, $content, $extended, $date, $published)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        $posts = array();

        while ($statement->fetch()) {
            $posts[] = new Post($title, $content, $extended, $date, array(), $published, $id);
        }

        Tag::loadTags($posts);
        Comment::loadCommentCount($posts);

        return $posts;
    }

    /**
     * Find posts for a specific page.
     *
     * @param int $pageNo The page number, starting from 1
     * @param int $pageSize The number of posts for each page
     * @param boolean $publishedIn Only published or all
     * @return Post[]
     * @throws DatabaseException
     */
    public static function findByPage($pageNo, $pageSize, $publishedIn = true) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT id, title, content, extended, date, published FROM ' . Database::getPrefix() . 'posts WHERE published>=? ORDER BY date DESC LIMIT ?, ?'))) {
            throw new DatabaseException("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("iii", $publishedIn, ($page = ($pageNo - 1) * $pageSize), $pageSize)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new DatabaseException("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($id, $title, $content, $extended, $date, $published)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        $posts = array();

        while ($statement->fetch()) {
            $posts[] = new Post($title, $content, $extended, $date, array(), $published, $id);
        }

        Tag::loadTags($posts);
        Comment::loadCommentCount($posts);

        return $posts;
    }

    /**
     * Find posts for a specific year.
     *
     * @param int $year
     * @param boolean $publishedIn Only published or all
     * @return Post[]
     * @throws DatabaseException
     */
    public static function findByYear($year, $publishedIn = true) {
        $db = Database::getConnection();

        $start = strtotime((int) $year . '-01-01 0:00:00');
        $end = strtotime('+1 year', $start);

        if (!($statement = $db->prepare('SELECT id, title, content, extended, date, published FROM ' . Database::getPrefix() . 'posts WHERE date >= ? AND date < ? AND published>=? ORDER BY date DESC'))) {
            throw new DatabaseException("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("iii", $start, $end, $publishedIn)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new DatabaseException("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($id, $title, $content, $extended, $date, $published)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        $posts = array();

        while ($statement->fetch()) {
            $posts[] = new Post($title, $content, $extended, $date, array(), $published, $id);
        }

        Tag::loadTags($posts);
        Comment::loadCommentCount($posts);

        return $posts;
    }

    /**
     * Find all posts.
     *
     * @param boolean $publishedIn Only published or all
     * @return Post[]
     * @throws DatabaseException
     */
    public static function findAll($publishedIn = true) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT id, title, content, extended, date, published FROM ' . Database::getPrefix() . 'posts WHERE published>=? ORDER BY date DESC'))) {
            throw new DatabaseException("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("i", $publishedIn)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new DatabaseException("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($id, $title, $content, $extended, $date, $published)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        $posts = array();

        while ($statement->fetch()) {
            $posts[] = new Post($title, $content, $extended, $date, array(), $published, $id);
        }

        Tag::loadTags($posts);
        Comment::loadCommentCount($posts);

        return $posts;
    }

    /**
     * Counts all posts.
     *
     * @param boolean $publishedIn Only published or all
     * @return int
     * @throws DatabaseException
     */
    public static function getPostCount($published = true) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT count(*) FROM ' . Database::getPrefix() . 'posts WHERE published>=?'))) {
            throw new DatabaseException("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("i", $published)) {
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
     * Creates statistics for all posts.
     *
     * The resulting array has the following keys:
     *      max: (int) maximum number of posts in a month
     *      data: (int[][])
     *      first: (int) year of the first post
     *      last: (int) year of the last post
     *
     * @param boolean $publishedIn Only published or all
     * @return mixed[]
     * @throws DatabaseException
     */
    public static function getYearStatistics($published = true) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT date FROM ' . Database::getPrefix() . 'posts WHERE published>=? ORDER BY date DESC'))) {
            throw new DatabaseException("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("i", $published)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new DatabaseException("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($date)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        $emptyYear = array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0, 11 => 0, 12 => 0);
        $result = array();
        $maxMonthCount = 0;

        while ($statement->fetch()) {
            $year = (int) date("Y", $date);
            $month = (int) date("n", $date);

            if (!array_key_exists($year, $result)) {
                $result[$year] = $emptyYear;
            }

            $result[$year][$month]++;

            if ($result[$year][$month] > $maxMonthCount) {
                $maxMonthCount = $result[$year][$month];
            }
        }

        return array(
            'max' => $maxMonthCount,
            'data' => $result,
            'first' => min(array_keys($result)),
            'last' => max(array_keys($result))
        );
    }

    /**
     * Saves a new post.
     *
     * A new ID will be generated.
     *
     * @return Post this post
     * @throws DatabaseException
     */
    public function create() {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('INSERT INTO ' . Database::getPrefix() . 'posts (title, content, extended, date, published) VALUES (?, ?, ?, ?, ?)'))) {
            throw new DatabaseException("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("sssii", $this->title, $this->content, $this->extended, $this->date, $this->published)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new DatabaseException("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }

        $this->id = $db->insert_id;

        Tag::update($this->id, $this->tags);

        return $this;
    }

    /**
     * Saves a already present post.
     *
     * @return Post this post
     * @throws DatabaseException
     */
    public function save() {
        if ($this->id < 0) {
            return $this->create();
        }

        $db = Database::getConnection();

        if (!($statement = $db->prepare('UPDATE ' . Database::getPrefix() . 'posts SET title=?, content=?, extended=?, date=?, published=? WHERE id=?'))) {
            throw new DatabaseException("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("sssiii", $this->title, $this->content, $this->extended, $this->date, $this->published, $this->id)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new DatabaseException("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }

        if ($statement->affected_rows == 0) {
            // Check if post does not exist or if there are no changes
            // Following call will throw a PostNotFoundException if post does not exist
            Post::findById($this->getId(), false);
        }

        Tag::update($this->id, $this->tags);

        return $this;
    }

    /**
     * Deletes a post.
     *
     * @param int $id The ID of the post
     * @throws DatabaseException
     */
    public static function delete($id) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('DELETE FROM ' . Database::getPrefix() . 'posts WHERE id=?'))) {
            throw new DatabaseException("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("i", $id)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new DatabaseException("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }

        if ($statement->affected_rows == 0) {
            throw new PostNotFoundException($id);
        }

        Tag::delete($id);
        Comment::deleteAll($id);
    }

    /**
     * Creates the database table.
     *
     * @throws DatabaseException
     */
    public static function install() {
        $db = Database::getConnection();

        if (!$db->query('CREATE TABLE IF NOT EXISTS ' . Database::getPrefix() . 'posts (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, title VARCHAR(100) NOT NULL, content TEXT NOT NULL, extended TEXT NOT NULL, date INT UNSIGNED NOT NULL, published BOOLEAN NOT NULL) CHARACTER SET utf8 COLLATE utf8_unicode_ci')) {
            throw new DatabaseException("Could not create table" . Database::getPrefix() . "posts: (" . $db->errno . ") " . $db->error);
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

    public function getHumanDate() {
        return StringUtils::formatHumanDate($this->date);
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

    public function getCommentCount() {
        return $this->commentCount;
    }

    public function setCommentCount($value) {
        $this->commentCount = (int) $value;
        return $this;
    }

    /**
     * Get the previous (sorted by date) post.
     *
     * @return Post or null
     * @throws DatabaseException
     */
    public function getPreviousPost($publishedIn = true) {
        if (count($this->previous) == 0) {
            $db = Database::getConnection();

            if (!($statement = $db->prepare('SELECT id, title, content, extended, date, published FROM ' . Database::getPrefix() . 'posts WHERE date<? AND published>=? ORDER BY date DESC LIMIT 1'))) {
                throw new DatabaseException("Prepare failed: (" . $db->errno . ") " . $db->error);
            }
            if (!$statement->bind_param("ii", $this->getDate(), $publishedIn)) {
                throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
            }
            if (!$statement->execute()) {
                throw new DatabaseException("Execute failed: (" . $statement->errno . ") " . $statement->error);
            }
            if (!$statement->bind_result($id, $title, $content, $extended, $date, $published)) {
                throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
            }

            if (!$statement->fetch()) {
                $this->previous[0] = null;
            } else {
                $this->previous[0] = new Post($title, $content, $extended, $date, array(), $published, $id);
            }

            $statement->close();
        }

        return $this->previous[0];
    }

    /**
     * Get the next (sorted by date) post.
     *
     * @return Post or null
     * @throws DatabaseException
     */
    public function getNextPost($publishedIn = true) {
        if (count($this->next) == 0) {
            $db = Database::getConnection();

            if (!($statement = $db->prepare('SELECT id, title, content, extended, date, published FROM ' . Database::getPrefix() . 'posts WHERE date>? AND published>=? ORDER BY date ASC LIMIT 1'))) {
                throw new DatabaseException("Prepare failed: (" . $db->errno . ") " . $db->error);
            }
            if (!$statement->bind_param("ii", $this->getDate(), $publishedIn)) {
                throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
            }
            if (!$statement->execute()) {
                throw new DatabaseException("Execute failed: (" . $statement->errno . ") " . $statement->error);
            }
            if (!$statement->bind_result($id, $title, $content, $extended, $date, $published)) {
                throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
            }

            if (!$statement->fetch()) {
                $this->next[0] = null;
            } else {
                $this->next[0] = new Post($title, $content, $extended, $date, array(), $published, $id);
            }

            $statement->close();
        }

        return $this->next[0];
    }
}
