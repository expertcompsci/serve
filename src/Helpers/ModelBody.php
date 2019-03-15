<?php
namespace Serve\Helpers;
use PDO;
class ModelBody {
    /*
    Model Body - Sets default parameter values and merges actual values from a request
    body for SQL stored proc calls. This allows API callers to omit properties from 
    request bodies as long as the default values are otherwise ok with Db, 
    validation etc.

    Initialize request body description arrays with
        1. JSON property name as key.
        2. PDO parameter name.
        3. Default PDO parameter value.
        4. PDO parameter type (Optional. Defautlts to string)
    Note: Don't forget that PHP associative arrays are ordered and the parameter list
    for stored procedure calls depend on this order. Yes, this means that all stored
    procs must use the same order.         
    
    Declare the initializing array like this:
    const $initBody = array(
        "position" => [":parameter_position", ""] 
        "isRejected" => [":parameter_is_rejected", false, PDO::PARAM_BOOL],
        "employerId" => [":parameter_employer_id", null, PDO::PARAM_INT]
    */
    const paramNameNdx = 0;
    const paramValNdx = 1;
    const pdoTypeNdx = 2;

    private $body = [];
    public function __construct($body) {
        $this->body = $this::initBody;
        foreach ($body as $key => $value) {
            if(!empty($this::initBody[$key])) { // Ignore any unrecognized fields in request.
                $this->body[$key][self::paramValNdx] = $value;
            } else {
                if($key == 'id') {  // Assumes integer primary key
                    $id['id'][self::paramNameNdx] = ":parameter_id";
                    $id['id'][self::paramValNdx] = $value;
                    $id['id'][self::pdoTypeNdx] = PDO::PARAM_INT;
                    $this->body = $id + $this->body;
                }
            }
        }
    }

    public function bind($stmt) {
        foreach ($this->body as $key => $value) {
            // Use count() as it incurrs very little overhead by checking internal array element
            if(count($value) > self::pdoTypeNdx) {
                $stmt->bindValue($value[self::paramNameNdx], $value[self::paramValNdx], $value[self::pdoTypeNdx]);
            } else {
                $stmt->bindValue($value[self::paramNameNdx], $value[self::paramValNdx]);
            }
        }
    }

    public function emitSqlParamList() {
        $ret = "";
        $i = 0; // Use count() as it incurrs very little overhead by checking internal array element
        foreach ($this->body as $key => $value) {
            $i++;
            $ret = $ret . $value[self::paramNameNdx] . (($i < count($this->body)) ? "," : "");
        }
        return $ret;
    }

    public function getId() {
        if(isset($this->body['id'])) {
            return $this->body['id'][self::paramValNdx];
        }
    }
}