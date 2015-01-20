<?php

namespace AE97\Panel;

class Config {

    private static $global;
    private $config;

    public function __construct($jsonString) {
        $this->config = json_decode($jsonString, true);
    }

    public function get($path = null) {
        if($path == null) {
            return $this->config;
        } else {
            return $this->config[$path];
        }
    }

    public static function getGlobal($path = null) {
        self::validate();
        if($path == null) {
            return self::$global->get();
        } else {
            return self::$global->get($path);
        }
    }

    private static function validate() {
        if(self::$global == null) {
            self::$global = new Config(file_get_contents(CONFIG_DIR . 'config.json'));
        }
    }
    
}