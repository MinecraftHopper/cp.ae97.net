<?php

namespace AE97;

class Validate {

    /**
     * @param type $name
     * @param string $displayName
     * @return \AE97\Validate
     */
    public static function param($name, $displayName = null) {
        if ($displayName == null) {
            return new Validate($name, $displayName);
        } else {
            return new Validate($name);
        }
    }

    private $val;
    private $variableName;

    private function __construct($value, $variableName = "Variable") {
        $this->val = $value;
        $this->variableName = $variableName;
    }

    /**
     * @return \AE97\Validate
     * @throws ValidateException
     */
    public function notNull() {
        if (is_null($this->val)) {
            throw new ValidateException($this->variableName . " cannot be null");
        }
        return $this;
    }

    /**
     * @return \AE97\Validate
     * @throws ValidateException
     */
    public function isNum() {
        if (!is_numeric($this->val)) {
            throw new ValidateException($this->variableName . " is not a number");
        }
        return $this;
    }

}

class ValidateException extends \Exception {

    public function __construct($message) {
        parent::__construct($message);
    }

}
