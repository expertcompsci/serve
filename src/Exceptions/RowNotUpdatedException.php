<?php
namespace Serve\Exceptions;

class RowNotUpdatedException extends RowException {
    public static function fromId($id) {
        return new static ("Row with Id: " . $id . " was not updated.", 0, null);
    }
}