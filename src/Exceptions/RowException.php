<?php
namespace Serve\Exceptions;

class RowException extends \Exception {
    public static function fromErrInfo($errInfo, $id = null) {
        $msg = (isset($id)) ? "Id : " . $id: ""
            ." ANSI SQLSTATE error code: ". $errInfo[0]
            ." Driver-specific error code: ".$errInfo[1]
            ." Driver-specific error message: [".$errInfo[2]."]";
        return new static ($msg, 0, null);
    }
}