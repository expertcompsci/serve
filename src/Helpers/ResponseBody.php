<?php
namespace Serve\Helpers;

use Serve\Helpers\AppErrors;

class ResponseBody {
    const errNone = 'none';             // model = Expected results.
    const errNoResult = 'noResult';     // model = Empty array.
    const errAppError = 'appError';     // model = AppErrors object describing the application error.
    const errException = 'exception';   // model = Dump of exception: message, file, line, code, trace array

    protected $body = [
        'magic' => '789',   // Why is 6 afraid of 7? Because ...
        'model' => [],
        'error' => 'none'
    ];

    public static function fromOkMessage($message) {
        if(is_string($message)) {
            return new static (array('message' => $message));
        } else {
            throw new \InvalidArgumentException('Expected string in ResponseBody::fromOkMessage()');
        }
    }

    public static function fromResultSet($results, $boolNames = null) {
        if(is_array($results)) {
            if(empty($results)) {
                return new static ($results, self::errNoResult);
            }
            if(!empty($boolNames)) {
                $newResults = array();
                foreach($results as $row) {
                    foreach($boolNames as $name) {
                        if($row[$name] > 0) {
                            $row[$name] = true;
                        } else {
                            $row[$name] = false;
                        }
                    }
                    array_push($newResults, $row);
                }
                return new static ($newResults);
            } else {
                return new static ($results);
            }
        } else {
            throw new \InvalidArgumentException('Expected array in ResponseBody::fromResultSet()');
        }
    }
    // fromAppErrors(String)
    // model = AppErrors object describing the application error.
    // error = 'appError'
    public static function fromAppErrors($appError) {
        if($appError instanceof AppErrors) {
            return new static ($appError(), self::errAppError);
        } else {
            throw new \InvalidArgumentException('Expected AppError object in ResponseBody::fromAppErrors()');
        }
    }
    // fromException(Exception)
    // model = 
    // error = 'exception'
    public static function fromException($results) {
        if($results instanceof \Exception) {
            return new static ($results, self::errException);
        }
    }

    private function __construct($results, $error = self::errNone) {
        if(is_array($results)) {
            $this->body['model'] = $this->body['model'][] = $results;
            $this->body['error'] = $error;
        } elseif($results instanceof \Exception) {
            $dup = $this->checkSqlState($results);
            if(!empty($dup)) {
                $this->body['model'] = $this->body['model'] = Array("duplicate" => Array($dup));
                $this->body['error'] = self::errAppError ;
            } else {
                $this->body['error'] = self::errException;
            }
            $errRet = array(
                'message' => $results->getMessage(),
                'code' => $results->getCode(),
                'file' => $results->getFile(),
                'line' => $results->getLine(),
                'trace' => $results->getTrace(),
            );
            $this->body = array_merge($this->body, $errRet);
        }
    }

    private function checkSqlState($ex) {
        // TODO: Custom error messages based on names of unique keys in db error messages inside exceptions
        // First convert key name to JSON equivalent columns.
        $dbKeysToColums = Array(
            "company_name_key" => "companName",
            "title_source" => ["title", "source"],
            "job_boards_name_key" => ""

        );
/*
{"magic":"789",
    "model":[],
    "error":"exception",
    "message":"SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'asdf' for key 'company_name_key'",
    "code":"23000",
    "file":"C:\\Bitnami\\wampstack-7.1.23-1\\www\\serve\\src\\routes\\employers.php",
    "line":53,
    "trace":[{"file":"C:\\Bitnami\\wampstack-7.1.23-1\\www\\serve\\src\\routes\\employers.php","line":53,"function":"execute","class":"PDOStatement","type":"->","args":[]},{"function":"{closure}","class":"Closure","type":"->","args":[{},{},[]]},{"file":"C:\\Bitnami\\wampstack-7.1.23-1\\www\\serve\\vendor\\slim\\slim\\Slim\\Handlers\\Strategies\\RequestResponse.php","line":41,"function":"call_user_func","args":[{},{},{},[]]},{"file":"C:\\Bitnami\\wampstack-7.1.23-1\\www\\serve\\vendor\\slim\\slim\\Slim\\Route.php","line":356,"function":"__invoke","class":"Slim\\Handlers\\Strategies\\RequestResponse","type":"->","args":[{},{},{},[]]},{"file":"C:\\Bitnami\\wampstack-7.1.23-1\\www\\serve\\vendor\\davidepastore\\slim-validation\\src\\Validation.php","line":110,"function":"__invoke","class":"Slim\\Route","type":"->","args":[{},{}]},{"function":"__invoke","class":"DavidePastore\\Slim\\Validation\\Validation","type":"->","args":[{},{},{}]},{"file":"C:\\Bitnami\\wampstack-7.1.23-1\\www\\serve\\vendor\\slim\\slim\\Slim\\DeferredCallable.php","line":43,"function":"call_user_func_array","args":[{},[{},{},{}]]},{"function":"__invoke","class":"Slim\\DeferredCallable","type":"->","args":[{},{},{}]},{"file":"C:\\Bitnami\\wampstack-7.1.23-1\\www\\serve\\vendor\\slim\\slim\\Slim\\MiddlewareAwareTrait.php","line":70,"function":"call_user_func","args":[{},{},{},{}]},{"file":"C:\\Bitnami\\wampstack-7.1.23-1\\www\\serve\\vendor\\slim\\slim\\Slim\\MiddlewareAwareTrait.php","line":117,"function":"Slim\\{closure}","class":"Slim\\Route","type":"->","args":[{},{}]},{"file":"C:\\Bitnami\\wampstack-7.1.23-1\\www\\serve\\vendor\\slim\\slim\\Slim\\Route.php","line":334,"function":"callMiddlewareStack","class":"Slim\\Route","type":"->","args":[{},{}]},{"file":"C:\\Bitnami\\wampstack-7.1.23-1\\www\\serve\\vendor\\slim\\slim\\Slim\\App.php","line":515,"function":"run","class":"Slim\\Route","type":"->","args":[{},{}]},{"file":"C:\\Bitnami\\wampstack-7.1.23-1\\www\\serve\\vendor\\tuupola\\callable-handler\\src\\CallableHandler.php","line":51,"function":"__invoke","class":"Slim\\App","type":"->","args":[{},{}]},{"file":"C:\\Bitnami\\wampstack-7.1.23-1\\www\\serve\\vendor\\tuupola\\cors-middleware\\src\\CorsMiddleware.php","line":111,"function":"handle","class":"Tuupola\\Middleware\\CallableHandler","type":"->","args":[{}]},{"file":"C:\\Bitnami\\wampstack-7.1.23-1\\www\\serve\\vendor\\tuupola\\callable-handler\\src\\DoublePassTrait.php","line":47,"function":"process","class":"Tuupola\\Middleware\\CorsMiddleware","type":"->","args":[{},{}]},{"function":"__invoke","class":"Tuupola\\Middleware\\CorsMiddleware","type":"->","args":[{},{},{}]},{"file":"C:\\Bitnami\\wampstack-7.1.23-1\\www\\serve\\vendor\\slim\\slim\\Slim\\DeferredCallable.php","line":43,"function":"call_user_func_array","args":[{},[{},{},{}]]},{"function":"__invoke","class":"Slim\\DeferredCallable","type":"->","args":[{},{},{}]},{"file":"C:\\Bitnami\\wampstack-7.1.23-1\\www\\serve\\vendor\\slim\\slim\\Slim\\MiddlewareAwareTrait.php","line":70,"function":"call_user_func","args":[{},{},{},{}]},{"file":"C:\\Bitnami\\wampstack-7.1.23-1\\www\\serve\\vendor\\slim\\slim\\Slim\\MiddlewareAwareTrait.php","line":117,"function":"Slim\\{closure}","class":"Slim\\App","type":"->","args":[{},{}]},{"file":"C:\\Bitnami\\wampstack-7.1.23-1\\www\\serve\\vendor\\slim\\slim\\Slim\\App.php","line":406,"function":"callMiddlewareStack","class":"Slim\\App","type":"->","args":[{},{}]},{"file":"C:\\Bitnami\\wampstack-7.1.23-1\\www\\serve\\vendor\\slim\\slim\\Slim\\App.php","line":314,"function":"process","class":"Slim\\App","type":"->","args":[{},{}]},{"file":"C:\\Bitnami\\wampstack-7.1.23-1\\www\\serve\\public\\index.php","line":39,"function":"run","class":"Slim\\App","type":"->","args":[]}]}
*/
        if($ex->getCode() == "23000") {
            $pieces = \explode(" ", $ex->getMessage());
            if($pieces[5] == "Duplicate" && $pieces[6] == "entry") {
                return "The value " . $pieces[7] . " already exists. Please choose a different value.";
            }
        }
        return "";
    }

    function __invoke() {
        return $this->body;
    }
}