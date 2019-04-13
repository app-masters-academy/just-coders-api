<?php

namespace AppMasters\AmLLib\Lib;

use Illuminate\Support\Facades\Validator;

class ValidateIt
{
    private static $errors;

    /**
     * @param array $payload
     * @param array $rules
     * @param array|null $acceptableAttributes
     * @param bool $allowInvalidFields - enable to send extra fields that are not on rules, ignoring it
     * @return array|bool|mixed
     */
    static function validate(array $payload, array $rules, array $acceptableAttributes = null, $allowInvalidFields = false)
    {
        self::$errors = null;

        // If some $accecptableAttributes, merge to data, to validate all thing
        // if ($payload != null && is_array($payload) && isset($acceptableAttributes))
        //     $payload = array_merge($acceptableAttributes, $payload);

        // What about the fields? $accecptableAttributes?
        // $fields = $this->getAllAttributes();

        // Must came with $accecptableAttributes?
        // $relations = $this->relations;


        // 1 - We have some data?? If not, it's a EMPTY_PAYLOAD
        $invalidPayload = [];
        if (is_null($payload) || count($payload) == 0) {
            array_push($invalidPayload, 'Empty payload');
            self::$errors = ['field' => $invalidPayload];
            return false;
        }

        // Convert payload to snake case
        $payload = toSnakeCase($payload);

        // TODO ! Method to convert $data to snake_case first!

        // Check for fields that didn't exists
        if ($allowInvalidFields == false) {
            // IF $accecptableAttributes is null, should be the rules keys
            if ($acceptableAttributes === null) {
                $acceptableAttributes = array_keys($rules);
            } else {
                $acceptableAttributes = array_merge($acceptableAttributes, array_keys($rules));
            }

            // 2 - Check for fields that didn't exists
            foreach ($payload as $key => $value) {
                if (!(in_array($key, $acceptableAttributes))) {
                    array_push($invalidPayload, 'Invalid field ' . $key);
                    self::$errors = ['field' => $invalidPayload];
                    return false;
                }
            }
        } else {
            // Ensure that every coming field are on rules
            $newRules = [];
            foreach (array_keys($payload) as $array_key) {
                if (isset($rules[$array_key]))
                    $newRules[$array_key] = $rules[$array_key];
            }
            $rules = $newRules;
        }


        // Last - use the rules validator
        $v = Validator::make($payload, $rules);
        if ($v->fails()) {
            self::$errors = $v->errors()->messages();
            // if (php_sapi_name() == "cli") {
            //     var_dump("sanitize FAILS");
            //     var_dump(self::$errors);
            // }
            // var_dump($payload);
            // set errors and return false
            // var_dump($this->errors);

            return false;
        }

        return $payload;
    }

    public static function getLastError()
    {
        return self::$errors;
    }

}


/**
 * @ TODO go to lib
 * Convert all array keys to snake_case
 * @param $payload
 * @return mixed
 */
function toSnakeCase($payload)
{
    foreach ($payload as $key => $value) {
        $snakeKey = snake_case($key);
        if ($snakeKey != $key) {
            $payload[$snakeKey] = $value;
            unset($payload[$key]);
        }
    }
    return $payload;
}