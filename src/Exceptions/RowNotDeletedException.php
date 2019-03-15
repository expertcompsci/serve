<?php
namespace Serve\Exceptions;

class RowNotDeletedException extends RowException {
    public static function fromId($id) {
        return new static ("Row with id: " . $id . " was not deleted.", 0, null);
    }
}