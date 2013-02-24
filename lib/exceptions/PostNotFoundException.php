<?php
namespace exceptions;

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
