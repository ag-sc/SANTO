<?php
require_once("configuration.php");

final class Database {
    private $mysqli;

    public static function instance() {
        static $_instance = null;
        if ($_instance === null) {
            $_instance = new Database();
        }
        return $_instance;
    }

    public static function db() {
        return self::instance()->get();
    }

    private function __construct() {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $dbhost = Configuration::instance()->get("database", "host");
        $dbuser = Configuration::instance()->get("database", "user");
        $dbpass = Configuration::instance()->get("database", "password");
        $dbschema = Configuration::instance()->get("database", "schema");

        $this->mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbschema);
        $this->mysqli->set_charset("utf8");
        if ($this->mysqli->connect_errno) {
            printf("Database connection failed: %s\n", $mysqli->connect_error);
            exit();
        }
    }

    public static function error() {
        return self::instance()->error;
    }

    public static function prepare(...$args) {
        return self::instance()->get()->prepare(...$args);
    }
    
    public static function query(...$args) {
        return self::instance()->get()->query(...$args);
    }

    public function get() {
        return $this->mysqli;
    }
}

?>
