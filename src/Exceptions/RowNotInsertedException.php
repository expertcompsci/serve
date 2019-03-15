<?php
namespace Serve\Exceptions;

class RowNotInsertedException extends RowException {
    public static function fromRowCount($rows) {
        return new static ("Row was not inserted. Row Count was: " . $rows, 0, null);
    }
}