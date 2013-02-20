<?php

class BetablogException extends Exception {
}

class ItemNotFoundException extends BetablogException {
}

class PostNotFoundException extends ItemNotFoundException {
    private $id;

    public function __construct($id) {
        $this->id = $id;
        parent::__construct("Post with id=" . $this->id . " not found.");
    }

    public function getId() {
        return $this->id;
    }
}

class CommentNotFoundException extends ItemNotFoundException {
    private $id;

    public function __construct($id) {
        $this->id = $id;
        parent::__construct("Comment with id=" . $this->id . " not found.");
    }

    public function getId() {
        return $this->id;
    }
}

class DatabaseException extends BetablogException {
}
