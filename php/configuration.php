<?php

final class Configuration {
    public static function instance() {
        static $_instance = null;
        if ($_instance === null) {
            $_instance = new Configuration();
        }
        return $_instance;
    }
    
    private $configdata = null;

    private function __construct() {
        $filename = __DIR__."/../config/annodb.config";
        $this->configdata = parse_ini_file($filename, TRUE);
    }

    public function get($category, $key, $defvalue = null) {
        if (!array_key_exists($category, $this->configdata)) {
            return $defvalue;
        }
        if (!array_key_exists($key, $this->configdata[$category])) {
            return $defvalue;
        }

        return $this->configdata[$category][$key];
    }
}

?>
