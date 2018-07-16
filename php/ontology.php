<?php
/*
require("php/ontology.php");
echo "<pre>";
foreach(Ontology::instance()->classes() as $curclass) {
    print($curclass.PHP_EOL);
    print("\tsuper:".implode(", ", $curclass->super_classes()).PHP_EOL);
    print("\tsub:".implode(", ", $curclass->sub_classes()).PHP_EOL);
    print("\tsuper (recursive):".implode(", ", array_map(function($e) { if (is_array($e)) { return implode(" ", $e); } else { return $e; } }, $curclass->super_classes(true))).PHP_EOL);
    print("\tsub (recursive):".implode(", ", array_map(function($e) { if (is_array($e)) { return implode(" ", $e); } else { return $e; } }, $curclass->sub_classes(true))).PHP_EOL);
    print("\trelations:".PHP_EOL);
    print("\t\t".implode(PHP_EOL."\t\t", array_map(function($rel) { return $rel.""; }, $curclass->relations())).PHP_EOL);
}
echo "</pre>";
die();
*/
require_once("database.php");

final class Ontology {
    private $name = null;

    /* retrieve the main ontology (subject to extension later on for supporting multiple ontologies) */
    public static function instance() {
        static $_instance = null;
        if ($_instance === null) {
            $_instance = new Ontology();
        }
        return $_instance;
    }

    private function __construct() {
        $this->name = Configuration::instance()->get("ontology", "name");
    }

    public function __toString() {
        return "[Ontology ".$this->name."]";
    }

    public function getclass($classname) {
        return OntologyClass::byName($classname);
    }

    public function getclass_byid($classid) {
        return OntologyClass::byId($classid);
    }
    
    public function classes() {
        $allclasses = array();
        if ($stmt = Database::prepare("SELECT Id, Name, IndividualName, Description FROM Class;")) {
            $stmt->execute() or die("ERROR(".__LINE__."): ".Database::error);
            if ($result = $stmt->get_result()) {
                while ($row = $result->fetch_assoc()) {
                    $newclass = new OntologyClass($row);
                    $allclasses[$newclass->name] = $newclass;
                }
                $result->free();
            } else {
                return null;
            }
            $stmt->close();
        } else die("ERROR(".__LINE__."): ".Database::error());
        return $allclasses;
    }
}

final class OntologyClass {
    public $id;
    public $name;
    public $individualName;
    public $description;

    public function __construct($row, $prefix = '') {
        $this->id = $row[$prefix.'Id'];
        $this->name = $row[$prefix.'Name'];
        $this->individualName = $row[$prefix.'IndividualName'];
        $this->description = $row[$prefix.'Description'];
    }

    public static function byId($id) {
        if ($stmt = Database::prepare("SELECT Id, Name, IndividualName, Description FROM Class WHERE Id = ?;")) {
            $stmt->bind_param("i", $id);
            $stmt->execute() or die("ERROR(".__LINE__."): ".Database::error);
            if ($result = $stmt->get_result()) {
                if ($row = $result->fetch_assoc()) {
                    return new OntologyClass($row);
                } else {
                    return null;
                }
            } else {
                return null;
            }
            $stmt->close();
        } else die("ERROR(".__LINE__."): ".Database::error());
    }
    
    public static function byName($classname) {
        if ($stmt = Database::prepare("SELECT Id, Name, IndividualName, Description FROM Class WHERE Name = ?;")) {
            $stmt->bind_param("s", $classname);
            $stmt->execute() or die("ERROR(".__LINE__."): ".Database::error);
            if ($result = $stmt->get_result()) {
                if ($row = $result->fetch_assoc()) {
                    return new OntologyClass($row);
                } else {
                    return null;
                }
            } else {
                return null;
            }
            $stmt->close();
        } else die("ERROR(".__LINE__."): ".Database::error());
    }

    public function super_classes($recursive=false) {
        $allclasses = array();
        if ($stmt = Database::prepare("SELECT Class.Id, Class.Name, Class.IndividualName, Class.Description FROM SubClass JOIN Class ON SubClass.SuperClass = Class.Id WHERE SubClass.SubClass = ?;")) {
            $stmt->bind_param("i", $this->id);
            $stmt->execute() or die("ERROR(".__LINE__."): ".Database::error);
            if ($result = $stmt->get_result()) {
                while ($row = $result->fetch_assoc()) {
                    $newclass = new OntologyClass($row);
                    $allclasses[$newclass->name] = $newclass;
                }
                $result->free();
            } else {
                return null;
            }
            $stmt->close();
        } else die("ERROR(".__LINE__."): ".Database::error());

        if ($recursive === false) {
            return $allclasses;
        } else {
            $hierarchy = null;
            if (is_array($recursive)) {
                $hierarchy = $recursive;
            } else {
                $hierarchy = array();
            }
            if (count($allclasses) > 0) {
                foreach ($allclasses as $elem) {
                    $hierarchy[] = $elem;
                }
                foreach ($allclasses as $curclass) {
                    $hierarchy = $curclass->super_classes($hierarchy);
                }
            }
            return $hierarchy;
        }
    }

    public function sub_classes($recursive=false) {
        $allclasses = array();
        if ($stmt = Database::prepare("SELECT Class.Id, Class.Name, Class.IndividualName, Class.Description FROM SubClass JOIN Class ON SubClass.SubClass = Class.Id WHERE SubClass.SuperClass = ?;")) {
            $stmt->bind_param("i", $this->id);
            $stmt->execute() or die("ERROR(".__LINE__."): ".Database::error);
            if ($result = $stmt->get_result()) {
                while ($row = $result->fetch_assoc()) {
                    $newclass = new OntologyClass($row);
                    $allclasses[$newclass->name] = $newclass;
                }
                $result->free();
            } else {
                return null;
            }
            $stmt->close();
        } else die("ERROR(".__LINE__."): ".Database::error());
        
        if ($recursive === false) {
            return $allclasses;
        } else {
            $hierarchy = null;
            if (is_array($recursive)) {
                $hierarchy = $recursive;
            } else {
                $hierarchy = array();
            }
            if (count($allclasses) > 0) {
                $hierarchy[] = $allclasses;
                foreach ($allclasses as $curclass) {
                    $hierarchy = $curclass->sub_classes($hierarchy);
                }
            }
            return $hierarchy;
        }
    }

    public function __toString() {
        return "[OntologyClass #".$this->id." (".$this->name.")]";
    }

    public function relations($hierarchy = true) {
        if ($hierarchy === true) {
            $targets = array();
            $targets[] = $this;
            foreach ($this->super_classes() as $parent) {
                $targets[] = $parent;
            }

            $allrelations = array();
            foreach ($targets as $targetClass) { 
                $targetRelations = $targetClass->relations(false);
                foreach ($targetRelations as $rel) {
                    $allrelations[] = $rel;
                }
            }
            return $allrelations;
        } else {
            $allrelations = array();

            if ($stmt = Database::prepare("SELECT Relation.Id, Relation.Domain AS DomainId, DomainClass.Name As DomainName, DomainClass.IndividualName AS DomainIndividualName, DomainClass.Description AS DomainDescription, Relation, Relation.`Range` AS RangeId, RangeClass.Name AS RangeName, RangeClass.IndividualName AS RangeIndividualName, RangeClass.Description AS RangeDescription, `From`, `To`, DataProperty, MergedName FROM Relation JOIN Class AS DomainClass ON Relation.Domain = DomainClass.Id JOIN Class AS RangeClass ON Relation.Range = RangeClass.Id WHERE Relation.Domain = ?;")) {
                $stmt->bind_param("i", $this->id);
                $stmt->execute() or die("ERROR(".__LINE__."): ".Database::error);
                if ($result = $stmt->get_result()) {
                    while ($row = $result->fetch_assoc()) {
                        $newrel= new OntologyRelation($row);
                        $allrelations[$newrel->name] = $newrel;
                    }
                    $result->free();
                } else {
                    return null;
                }
                $stmt->close();
            } else die("ERROR(".__LINE__."): ".Database::error());

            return $allrelations;
        }
    }
}

final class OntologyRelation {
    public $domain = null;
    public $range = null;
    public $id = null;
    public $name = null;
    public $from = null;
    public $to = null;
    public $dataproperty = null;
    public $mergedName = null;

    public final function __construct($row) {
        $this->domain = new OntologyClass($row, "Domain");
        $this->range = new OntologyClass($row, "Range");
        $this->id = $row['Id'];
        $this->name = $row['Relation'];
        $this->from = $row['From'];
        $this->to = $row['To'];
        $this->dataproperty = $row['DataProperty'];
        $this->mergedName = $row['MergedName'];
    }

    public function __toString() {
        return "[OntologyRelation ".$this->domain." [".$this->name."] ".$this->range." (".$this->from.":".$this->to."; dataprop=".$this->dataproperty.")]";
    }
}

?>
