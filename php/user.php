<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once("database.php");

final class User {
    public $id = null;
    public $mail = null;
    public $_curator = false;

    public function __toString() {
        return "[User #".$this->id." (".$this->mail.")]";
    }

    public function isCurator() {
        return $this->_curator;
    }
}

final class Users {
    public static function loginUser() {
        if (!empty($_SESSION['user'])) {
            return self::byId($_SESSION["user"]);
        } else {
            return null;
        }
    }

    public static function ensureActiveLogin() {
        $loginuser = null;
        if (!empty($_SESSION['user'])) {
            $loginuser = Users::loginUser();
        }
        if (!empty($loginuser)) {
            return true;
        } else {
            header('HTTP/1.0 403 Forbidden');
            die('Forbidden. No active user session found.'); 
        }
    }

    public static function all() {
        $allusers = array();
        if ($result = Database::db()->query("SELECT Id, Mail, IsCurator FROM User")) {
            while($row = $result->fetch_assoc()) {
                $newuser = new User();
                $newuser->id = $row["Id"];
                $newuser->mail = $row["Mail"];
                $newuser->_curator = $row["IsCurator"];
                $allusers[] = $newuser;
            }
            $result->free();
        }
        return $allusers;
    }

    public static function byId($id) {
        static $usercache = array();
        if (array_key_exists($id, $usercache)) {
            return $usercache[$id];
        }
        $db = Database::db();
        $userres = null;
        if($stmt = $db->prepare("SELECT `Id`, `Mail`, IsCurator FROM `User` WHERE `Id` = ?;")) {
            $stmt->bind_param("i", $id);
            $stmt->execute();

            if ($result = $stmt->get_result()) {
                if ($row = $result->fetch_assoc()) {
                    $userres = new User();
                    $userres->id = $row["Id"];
                    $userres->mail = $row["Mail"];
                    $userres->_curator = $row["IsCurator"];
                    $usercache[$userres->id] = $userres;
                }
            }

            $stmt->close();
        }
        return $userres;
    }
    
    public static function byMail($mail) {
        $db = Database::db();
        $userres = null;
        if($stmt = $db->prepare("SELECT `Id`, `Mail`, IsCurator FROM `User` WHERE `Mail` = ?;")) {
            $stmt->bind_param("s", $mail);
            $stmt->execute();

            if ($result = $stmt->get_result()) {
                if ($row = $result->fetch_assoc()) {
                    $userres = new User();
                    $userres->id = $row["Id"];
                    $userres->mail = $row["Mail"];
                    $userres->_curator = $row["IsCurator"];
                }
            }

            $stmt->close();
        }
        return $userres;
    }
    
}


?>
