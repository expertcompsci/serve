<?php
namespace Serve\Helpers;
// AppErrors class is constructed with error messages keyed by a standard code 
// or the field name that is associated with the message (e.g. the field that
// didn't pass the validation). There can be multiple messages for each code.
// [
//   "thingyCount": [
//      "must be a number",
//      "must be postitive"
//   ]
// ]
// This shape is passed to the ResponseBody object that forms the "model" in
// standard shape of response data for the API.
class AppErrors {

    public const errNoSearchTarg = 'errNoSearchTarg';

    private const recognizedErrors = array (
        self::errNoSearchTarg => 'One of %s must not be empty.'
    );

    private static $errors = [];

    public static function fromCodeTarget($code, $target = null) {
        $ret = new static ();
        $ret->addCodeMessage($code, self::recognizedErrors[$code], $target);
        return $ret;
    }

    // Peel off the 'errors' key from the Slim validation library 
    // error messasges and make them conform to our standard shape (see above)
    public static function fromValidation($validationErrors) {
        $ret = new static ();
        // if(is_array($validationErrors)) {
        //     foreach($validationErrors as $targ) {
        //         $targKey = key($targ);
        //         foreach($targ as $msg) {
        //             $ret->addCodeMessage($targKey, $msg);
        //         }
        //     }
        // } else {
        //     throw new \InvalidArgumentException('Expected object.');
        // }
        self::$errors = $validationErrors;
        return $ret;
    }

    // addCodeMessage(code, message, msgVal)
    // Adds to the list of application errors for the given code. The given code 
    // may accept a target value to be included in the message.
    public static function addCodeTarget($code, $msgVal = null) {
        self::addCodeMessage($code, self::recognizedErrors[$code], $target);
    }

    private static function addCodeMessage($code, $message, $msgVal = null) {
        $mashedMsg = '';
        if(empty($msgVal)) {
            $mashedMsg = $message;
        } else {
            $mashedMsg = sprintf($message, $msgVal);
        }
        self::$errors[$code][] = $mashedMsg;
    }

    public static function getModel() {
        return self::$errors;
    }

    public function __invoke() {
        return self::getModel();
    }
}