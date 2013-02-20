<?php

/**
 * Model of a tag.
 *
 * Tags will be returned as strings, so this class has only static methods.
 */
class Tag {
    private function __construct() {
    }

    /**
     * Find tags of a post.
     *
     * @param int $post The ID of the post
     * @return string[]
     * @throws DatabaseException
     */
    public static function findByPost($post) {
        $db = Database::getConnection();
        if (!($statement = $db->prepare('SELECT tag FROM ' . Config::DB_PREFIX . 'tags WHERE post=? ORDER BY tag ASC'))) {
            throw new DatabaseException("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->bind_param("i", $post)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->execute()) {
            throw new DatabaseException("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($tag)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        $result = array();

        while ($statement->fetch()) {
            $result[] = $tag;
        }

        return $result;
    }

    /**
     * Find all tags.
     *
     * @return string[]
     * @throws DatabaseException
     */
    public static function findAll() {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('SELECT tag, count(tag) FROM ' . Config::DB_PREFIX . 'tags GROUP BY tag ORDER BY tag ASC'))) {
            throw new DatabaseException("Prepare failed: (" . $db->errno . ") " . $db->error);
        }
        if (!$statement->execute()) {
            throw new DatabaseException("Execute failed: (" . $statement->errno . ") " . $statement->error);
        }
        if (!$statement->bind_result($tag, $count)) {
            throw new DatabaseException("Binding parameters failed: (" . $statement->errno . ") " . $statement->error);
        }

        $result = array();

        while ($statement->fetch()) {
            $result[] = array($tag, $count);
        }

        return $result;
    }

    /**
     * Load tags for a list of posts.
     *
     * @param Post[] $posts A list of posts
     * @return Post[]
     * @throws DatabaseException
     */
    public static function loadTags(array $posts) {
        if (count($posts) == 0) {
            return $posts;
        }

        $ids = array();
        foreach ($posts as $post) {
            $ids[] = (int) $post->getId();
        }
        $ids = array_unique($ids);

        $db = Database::getConnection();

        if (!$dbResult = $db->query("SELECT post, GROUP_CONCAT(tag ORDER BY tag ASC SEPARATOR ',') AS tags FROM " . Config::DB_PREFIX . 'tags WHERE post IN (' . implode(', ', $ids) . ') GROUP BY post')) {
            throw new DatabaseException("Execute failed: (" . $db->errno . ") " . $db->error);
        }

        $allTags = array();
        while ($tags = $dbResult->fetch_assoc()) {
            $allTags[$tags['post']] = explode(',', $tags['tags']);
        }
        $dbResult->free();

        foreach ($posts as $post) {
            if (array_key_exists($post->getId(), $allTags)) {
                $post->setTags($allTags[$post->getId()]);
            }
        }

        return $posts;
    }

    /**
     * Update tags for a post.
     *
     * @param int $id The ID of the post
     * @param string[] $tags
     * @throws DatabaseException
     */
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

        if (!$db->query('INSERT INTO ' . Config::DB_PREFIX . 'tags (post, tag) VALUES ' . $tagString)) {
            throw new DatabaseException("Execute failed: (" . $db->errno . ") " . $db->error);
        }
    }

    /**
     * Delete tags for a post.
     *
     * @param int $id The ID of the post
     * @throws DatabaseException
     */
    public static function delete($post) {
        $db = Database::getConnection();

        if (!($statement = $db->prepare('DELETE FROM ' . Config::DB_PREFIX . 'tags WHERE post=?'))) {
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
     * @throws DatabaseException
     */
    public static function install() {
        $db = Database::getConnection();

        if (!$db->query('CREATE TABLE IF NOT EXISTS ' . Config::DB_PREFIX . 'tags (post INT NOT NULL, tag VARCHAR(100) NOT NULL) CHARACTER SET utf8 COLLATE utf8_unicode_ci')) {
            throw new DatabaseException("Could not create table" . Config::DB_PREFIX . "tags: (" . $db->errno . ") " . $db->error);
        }
    }
}
