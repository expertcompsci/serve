<?php
namespace Serve\Exceptions;

class RowNotFoundException extends RowException {
    public static function fromId($id) {
        return new static ("Row with id: " . $id . " was not found.", 0, null);
    }
}