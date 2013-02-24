<?php
namespace exceptions;

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
