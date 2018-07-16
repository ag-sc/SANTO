<?php

require_once("database.php");
require_once("user.php");

final class Token {
    public $data = null;
    public function __construct($data) {
        $this->data = $data;
    }
    public function get($key) {
        return $this->data[$key];
    }

    public function __toString() {
        return "[Token S".$this->get("Sentence")."#".$this->get("Number")." [".$this->get("Onset").":".$this->get("Offset")."] '".$this->get("Text")."']";
   }
}
final class SlotEntry {
    public $data = null;
    public function __construct($data) {
        $this->data = $data;
    }
    public function get($key) {
        return $this->data[$key];
    }
    public function id() {
        return $this->get("id");
    }
    public function parentId() {
        return $this->get("Parent");
    }
    public function getClassName() {
        return $this->get("ClassName");
    }
    public function isDataProperty() {
        return !empty($this->get("DataProperty")) && $this->get("DataProperty") == 1;
    }
    public function hasDataGroup() {
        return !empty($this->get("DataGroup"));
    }
    public function getDataGroup() {
        return $this->get("DataGroup");
    }
    public function getText() {
        return $this->get("Text");
    }
    public function getAnnotationId() {
        return $this->get("AnnotationId");
    }
    public function getRelationName() {
        return $this->get("Relation");
    }
    public function isRelation() {
        return !empty($this->get("Relation"));
    }
    public function hasAnnotation() {
        return !empty($this->get("AnnotationId"));
    }
    public function getInstance() {
        if ($this->isRelation() && !empty($this->getDataGroup())) {
            if (!empty($this->get("dgname"))) {
                return $this->get("dgname")."_".$this->getDataGroup();
            } else {
                return $this->get("ClassName")."_".$this->getDataGroup();
            }
        }
        if (!empty($this->get("ClassName"))) {
            return $this->get("ClassName")."_".$this->get("id");
        }
        return null;
    }
    public function hasDataName() {
        return !empty($this->get("DataName"));
    }
    public function getDataName() {
        if (!empty($this->get("DataName"))) {
            return $this->get("DataName");
        }
        return null;
    }
    public function isGroupEntry() {
        if($stmt = Database::prepare("SELECT COUNT(*) FROM `Group` WHERE `Group` = ?")) {
            $stmt->bind_param("i", $this->data["ClassId"]);
            $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
            $result = $stmt->get_result();
            return $result->fetch_row()[0] != 0;
        } else die("ERROR(".__LINE__."): ".$mysqli->error);
    }
    public function getRelation() {
        return $this->get("Relation");
    }
    public function isIndividualName() {
        if (!empty($this->get("IndividualName")) && $this->get("IndividualName") == 1) {
            return true;
        }
        return FALSE;
    }
    public function isParent() {
        if (!empty($this->get("Parent"))) {
            return FALSE;
        }
        return TRUE;
    }
    public function __toString() {
        return "[SlotEntry #".$this->get("Id")."]";
   }
}
final class AnnoClass {
    public $id = null; 
    public $name = null;
    public $individualname = null;
    public function __construct($id, $name, $individualname) {
        $this->id = $id;
        $this->name = $name;
        if ($individualname === 1) {
            $this->individualname = TRUE;
        } else {
            $this->individualname = FALSE;
        }
    }
    public function __toString() {
        return "[Class #".$this->id." (".$this->name.($this->individualname ? "/individual" : "").")]";
    }
    public static function byName($name) {
        $id = null;
        $individualname = null;
        if ($stmt = Database::prepare("SELECT Id, Name, IndividualName FROM `Class` WHERE `Name` = ?;")) {
            $stmt->bind_param("s", $name);
            $stmt->execute() or die("ERROR(".__LINE__.")");
            if ($result = $stmt->get_result()) {
                if ($row = $result->fetch_assoc()) {
                    $id = $row["Id"];
                    $name = $row["Name"];
                    $individualname = $row["IndividualName"];
            }
                $result->close();
            }
            $stmt->close();
        }

        if ($id === null) {
            return null;
        }
        return new AnnoClass($id, $name, $individualname);
    }
}
final class Annotation {
    public $id = null;
    public $user = null;
    public $index = null;
    public $sentence = null;
    public $onset = null;
    public $offset = null;
    public $text = null;
    public $reference = null;
    public $class = null;

    public function __construct($row) {
        $this->id = $row["Id"];
        $this->user = Users::byId($row["User"]);
        $this->index = $row["Index"];
        $this->sentence = $row["Sentence"];
        $this->onset = $row["Onset"];
        $this->offset = $row["Offset"];
        $this->text = $row["Text"];
        $this->reference = $row["Reference"];
        $this->class = new AnnoClass($row['Class'], $row['Name'], $row['IndividualName']);
    }

    public function __toString() {
        return "[Annotation #".$this->id." (".$this->user.", ".$this->class.")]";
    }
}

final class Publication {
    public $id = null;
    public $name = null;
    public $filename = null;
    private $_alltokens = null;

   public function __construct($row) {
        $this->id = $row["Id"];
        $this->name = $row["Name"];
        $this->filename = $row["FileName"];
    }

    public function __toString() {
        return "[Publication #" . $this->id . "(" . $this->name . ", " . $this->filename . ")]";
    }

    public function users() {
        $allusers = array();
        $query = "SELECT UserId FROM User_Publication WHERE PublicationId = ?";
        if ($stmt = Database::prepare($query)) {
            $stmt->bind_param("i", $this->id);
            $stmt->execute();

            if ($result = $stmt->get_result()) {
                while ($row = $result->fetch_assoc()) {
                    $allusers[] = $row['UserId'];
                }
                $result->close();
            }
            $stmt->close();
        }
        $userinstances = array();
        foreach ($allusers as $userid) {
            $userinstances[] = Users::byId($userid);
        }
        return $userinstances;
    }

    public function rename($newname) {
        $res = false;
        if ($newname == $this->name) {
            return true;
        }
        $query = "UPDATE Publication SET Name = ? WHERE Id = ?";
        if ($stmt = Database::prepare($query)) {
            $stmt->bind_param("si", $newname, $this->id);
            $stmt->execute();
            if ($stmt->affected_rows) {
                $this->name = $newname;
                $res = true;
            }
            $stmt->close();
        }
        return $res;
    }

    public function annotations($user = null) {
        $allannotations = array();

        $query = "SELECT Annotation.Id, Annotation.User, Annotation.Index, Annotation.Sentence, Annotation.Onset, Annotation.Offset, Annotation.Text, Annotation.Reference, Class.Id As Class, Class.Name, Class.IndividualName FROM Annotation LEFT JOIN Class ON Class.Id = Annotation.Class WHERE Annotation.PublicationId = ?";
        if ($user) {
            $query = $query . " AND Annotation.User = ?";
        }
        
        if ($stmt = Database::prepare($query)) {
            if ($user) {
                $stmt->bind_param("ii", $this->id, $user->id);
            } else {
                $stmt->bind_param("i", $this->id);
            }

            $stmt->execute();

            if ($result = $stmt->get_result()) {
                while ($row = $result->fetch_assoc()) {
                    $allannotations[] = new Annotation($row);
                }
                $result->close();
            }
            $stmt->close();
        }

        return $allannotations;
    }

    public function slotData($onlyforuser = null) {
        $allentries = array();
        $query = <<<QUERY
	SELECT `Data`.*,
		   Annotation.Id AS OriginalAnnoId,
		   LinkedAnno.Id AS AnnotationId,
		   LinkedAnno.*,
		   Relation.Id as RelationId,
		   Relation.Domain As RelationDomain,
		   Relation.Relation As Relation,
		   Relation.Range As RelationRange,
		   Relation.From As RelationFrom,
		   Relation.To As RelationTo,
		   Relation.DataProperty,
		   Relation.MergedName,
		   Data.Name As DataName,
		   Class.Name as ClassName,
		   Class.IndividualName As IndividualName,
		   Class.Id as ClassId,
		   DGClass.Name AS dgname
		   FROM Data
		   LEFT JOIN Class ON Data.ClassId = Class.Id
		   LEFT JOIN Annotation ON Data.AnnotationId = Annotation.`Id`
		   LEFT JOIN Annotation AS LinkedAnno ON Annotation.`Index` = LinkedAnno.`Index` AND Data.PublicationId = LinkedAnno.PublicationId
		   LEFT JOIN Relation ON Relation.Id = Data.RelationId LEFT JOIN Data AS DGData ON Data.DataGroup = DGData.Id
		   LEFT JOIN Class AS DGClass ON DGData.ClassId = DGClass.Id
		   WHERE Data.`PublicationId` = ?
		   GROUP BY Data.`Id`, Annotation.`Id`, LinkedAnno.`Id`
		   ORDER BY Data.`Id`;
QUERY;
        if (!empty($onlyforuser)) {
            $query = <<<QUERY
	SELECT `Data`.*,
		   Annotation.Id AS OriginalAnnoId,
		   LinkedAnno.Id AS AnnotationId,
		   LinkedAnno.*,
		   Relation.Id as RelationId,
		   Relation.Domain As RelationDomain,
		   Relation.Relation As Relation,
		   Relation.Range As RelationRange,
		   Relation.From As RelationFrom,
		   Relation.To As RelationTo,
		   Relation.DataProperty,
		   Relation.MergedName,
		   Data.Name As DataName,
		   Class.Name as ClassName,
		   Class.IndividualName As IndividualName,
		   Class.Id as ClassId,
		   DGClass.Name AS dgname
		   FROM Data
		   LEFT JOIN Class ON Data.ClassId = Class.Id
		   LEFT JOIN Annotation ON Data.AnnotationId = Annotation.`Id`
		   LEFT JOIN Annotation AS LinkedAnno ON Annotation.`Index` = LinkedAnno.`Index` AND Data.PublicationId = LinkedAnno.PublicationId
		   LEFT JOIN Relation ON Relation.Id = Data.RelationId LEFT JOIN Data AS DGData ON Data.DataGroup = DGData.Id
		   LEFT JOIN Class AS DGClass ON DGData.ClassId = DGClass.Id
		   WHERE Data.`PublicationId` = ? AND Data.User = ?
		   GROUP BY Data.`Id`, Annotation.`Id`, LinkedAnno.`Id`
		   ORDER BY Data.`Id`;

QUERY;
        }

        if ($stmt = Database::prepare($query)) {
            if (empty($onlyforuser)) {
                $stmt->bind_param("i", $this->id);
            } else {
                $stmt->bind_param("ii", $this->id, $onlyforuser->id);
            }
            $stmt->execute();

            if ($result = $stmt->get_result()) {
                while ($row = $result->fetch_assoc()) {
                    $allentries[] = new SlotEntry($row);
                }
                $result->close();
            }
            $stmt->close();
        }
        return $allentries;
    }

    public function tokens() {
        if ($this->_alltokens === null) {
        $alltokens = array();
        $query = "SELECT Id, Text, Onset, Offset, Sentence, Number FROM Token WHERE PublicationId = ? ORDER BY Sentence ASC, Number ASC";
        if ($stmt = Database::prepare($query)) {
            $stmt->bind_param("i", $this->id);
            $stmt->execute();

            if ($result = $stmt->get_result()) {
                while ($row = $result->fetch_assoc()) {
                    $alltokens[] = new Token($row);
                }
                $result->close();
            }
            $stmt->close();
        }
        $this->_alltokens = $alltokens;
        }

        return $this->_alltokens;
    }

    public function isReady($user) {
        $res = FALSE;
        $query = "SELECT Ready FROM User_Publication WHERE UserId = ? AND PublicationId = ?";
        if ($stmt = Database::prepare($query)) {
            $stmt->bind_param("ii", $user->id, $this->id);
            $stmt->execute();

            if ($result = $stmt->get_result()) {
                if ($row = $result->fetch_assoc()) {
                    $res = $row["Ready"];
                }
                $result->close();
            }
            $stmt->close();
        }
        if ($res) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function setReady($user, $value) {
        if ($value) {
            $value = 1;
        } else {
            $value = 0;
        }
        $res = null;
        $query = "INSERT INTO User_Publication (UserId, PublicationId, Ready) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE Ready = ?";
        if ($stmt = Database::prepare($query)) {
            $stmt->bind_param("iiii", $user->id, $this->id, $value, $value);
            if ($stmt->execute()) {
                $res = $value;
            }

            $stmt->close();
        }
        if ($res) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    public function isReadyCuration($user) {
        $res = FALSE;
        $query = "SELECT ReadyCuration FROM User_Publication WHERE UserId = ? AND PublicationId = ?";
        if ($stmt = Database::prepare($query)) {
            $stmt->bind_param("ii", $user->id, $this->id);
            $stmt->execute();

            if ($result = $stmt->get_result()) {
                if ($row = $result->fetch_assoc()) {
                    $res = $row["ReadyCuration"];
                }
                $result->close();
            }
            $stmt->close();
        }
        if ($res) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function setReadyCuration($user, $value) {
        if ($value) {
            $value = 1;
        } else {
            $value = 0;
        }
        $res = null;
        $query = "INSERT INTO User_Publication (UserId, PublicationId, ReadyCuration) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE ReadyCuration = ?";
        if ($stmt = Database::prepare($query)) {
            $stmt->bind_param("iiii", $user->id, $this->id, $value, $value);
            if ($stmt->execute()) {
                $res = $value;
            }

            $stmt->close();
        }
        if ($res) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    public function isReadySlotFilling($user) {
        $res = FALSE;
        $query = "SELECT ReadySlotFilling FROM User_Publication WHERE UserId = ? AND PublicationId = ?";
        if ($stmt = Database::prepare($query)) {
            $stmt->bind_param("ii", $user->id, $this->id);
            $stmt->execute();

            if ($result = $stmt->get_result()) {
                if ($row = $result->fetch_assoc()) {
                    $res = $row["ReadySlotFilling"];
                }
                $result->close();
            }
            $stmt->close();
        }
        if ($res) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function setReadySlotFilling($user, $value) {
        if ($value) {
            $value = 1;
        } else {
            $value = 0;
        }
        $res = null;
        $query = "INSERT INTO User_Publication (UserId, PublicationId, ReadySlotFilling) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE ReadySlotFilling = ?";
        if ($stmt = Database::prepare($query)) {
            $stmt->bind_param("iiii", $user->id, $this->id, $value, $value);
            if ($stmt->execute()) {
                $res = $value;
            }

            $stmt->close();
        }
        if ($res) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}

final class Publications {
    public static function all($forUser = null) {
        $publications = array();
        $query = "SELECT `Id`, `FileName`, `Name` FROM `Publication` ORDER BY Publication.Name ASC";
        if ($forUser) {
            $query = "SELECT Publication.Id, Publication.FileName, Publication.Name FROM `Publication` JOIN User_Publication up ON up.PublicationId = Publication.Id WHERE up.UserId = ".Database::db()->real_escape_string($forUser->id)." ORDER BY Publication.Name ASC";
        }
        if ($result = Database::query($query)) {
            while($row = $result->fetch_assoc()) {
                $publications[] = new Publication($row);
            }
            $result->close();
        }

        return $publications;
    }

    public static function byName($name) {
        if($stmt = Database::prepare("SELECT `Id`, `FileName`, `Name` FROM `Publication` WHERE `Name` = ?;")) {
            $stmt->bind_param("s", $name);
            $stmt->execute();

            if ($result = $stmt->get_result()) {
                if ($row = $result->fetch_assoc()) {
                    return new Publication($row);
                }
            }

            $stmt->close();
        }
        return null;
    }

    public static function create($name, $filename) {
        print_r("creating $name $filename\n");
        if ($stmt = Database::prepare("INSERT INTO Publication (Name, FileName) VALUES (?, ?)")) {
            $stmt->bind_param("ss", $name, $filename);
            $newpub = null;
            if ($stmt->execute()) {
                $newpub = self::byId($stmt->insert_id);
            }
            $stmt->close();
            return $newpub;
        }
        return null;
    }
 
    public static function byId($id) {
        if($stmt = Database::prepare("SELECT `Id`, `FileName`, `Name` FROM `Publication` WHERE `Id` = ?;")) {
            $stmt->bind_param("i", $id);
            $stmt->execute();

            if ($result = $stmt->get_result()) {
                if ($row = $result->fetch_assoc()) {
                    return new Publication($row);
                }
            }

            $stmt->close();
        }
        return null;
    }
    
    public static function nameFromFilename($filename) {
        $parts = explode("_", pathinfo($filename, PATHINFO_FILENAME));
        array_pop($parts); // remove username
        
        return implode(" ", $parts);
    }

    public static function userFromFilename($filename) {
        $parts = explode("_", pathinfo($filename, PATHINFO_FILENAME));
        $res = array_pop($parts);
        if (empty($res)) {
            $res = "admin";
        }
        return $res;
    }

    public static function insertmeta($target) {
        $za = new ZipArchive();
        $za->open($target);

        $files_text = array();
        $files_tokens = array();
        $files_annos = array();
        for($i = 0; $i < $za->numFiles; $i++){ 
            $stat = $za->statIndex( $i ); 
            $fileext = strtolower(pathinfo($stat['name'], PATHINFO_EXTENSION));
            if ($fileext === "txt") {
                $files_text[] = $stat['name'];
            } else if ($fileext === "csv") {
                $files_tokens[] = $stat['name'];
            } else if ($fileext === "annodb") {
                $files_annos[] = $stat['name'];
            }
        }
        
        Database::query("START TRANSACTION");
        $inserted_meta = 0;
        $inserted_docs = 0;
        $missed_meta = 0;
        $missed_docs = 0;
        $skipped_existing = 0;
        $classIDs = array();

        foreach($files_annos as $annofile) {
            $content = $za->getFromName($annofile);
            // gather users from filename
            $username = explode("_", pathinfo($annofile, PATHINFO_FILENAME));
            $username = $username[count($username) - 1];
            if (empty($username)) {
                $username = "admin";
            }
            $anno_user = Users::byMail($username);
            $publication = Publications::byName(self::nameFromFilename($annofile));

            if (empty($publication)) {
                $missed_docs++;
                continue;
            }

            $inserted_docs++;
            $publicationId = $publication->id;
            print_r("ANNOFILE:: user=$anno_user pub=$publication".PHP_EOL);
            
            $annotations = array();
            // read annotations from file
            foreach(explode("\n", $content) as $line){
                if (strlen($line) === 0 or trim($line)[0] === '#') continue;
                $linecsv = str_getcsv($line, "\t");
                if (sizeof($linecsv) === 5) {
                    $annotation = array_combine(array("index", "class", "onset", "offset", "text"), $linecsv);
                    $annotation['meta'] = null;
                } else {
                    $annotation = array_combine(array("index", "class", "onset", "offset", "text", "meta"), $linecsv);
                }
                $annotations[] = $annotation;
            }
            unset($content);

            foreach($annotations as $idx => $annotation) {
                // gather token information on the current annotation
                if (!array_key_exists($annotation["class"], $classIDs)) {
                    $matchingClass = AnnoClass::byName($annotation["class"]);
                    if (!empty($matchingClass)) {
                        $classIDs[$annotation["class"]] = $matchingClass->id;
                    } else {
                        print_r("WARNING: class <".$annotation['class']."> not found! (in: $annofile, annotation: ".json_encode($annotation).")".PHP_EOL);
                        $missed_meta++;
                        continue;
                    }
                }
                $annotation['classId'] = $classIDs[$annotation["class"]];

                if ($stmt = Database::prepare("SELECT `Number`, `Sentence` FROM `Token` WHERE `Onset` = ? AND `PublicationId` = ?;")) {
                    $stmt->bind_param("ii", $annotation["onset"], $publicationId);
                    $stmt->execute() or die("ERROR(" . __LINE__ . "): " . Database::error());
                    if ($result = $stmt->get_result()) {
                        if ($row = $result->fetch_assoc()) {
                            $annotation["onsetToken"] = $row;
                        }
                        $result->close();
                    }
                    $stmt->close();
                } else die("ERROR(" . __LINE__ . "): " . Database::error());

                if ($stmt = Database::prepare("SELECT `Number`, `Sentence` FROM `Token` WHERE `Offset` = ? AND `PublicationId` = ?;")) {
                    $stmt->bind_param("ii", $annotation["offset"], $publicationId);
                    $stmt->execute() or die("ERROR(" . __LINE__ . "): " . Database::error());
                    if ($result = $stmt->get_result()) {
                        if ($row = $result->fetch_assoc()) {
                            $annotation["offsetToken"] = $row;
                        }
                        $result->close();
                    }
                    $stmt->close();
                } else die("ERROR(" . __LINE__ . "): " . Database::error());
                
                if ($stmt = Database::prepare("SELECT * FROM `Annotation` WHERE PublicationId = ? AND `Class` = ? AND `SENTENCE` = ? AND Onset = ? AND Offset = ? AND User = ? LIMIT 1")) {
                    $stmt->bind_param("iiiiii", $publicationId, $annotation["classId"], $annotation["onsetToken"]["Sentence"], $annotation["onsetToken"]["Number"], $annotation["offsetToken"]["Number"], $anno_user->id);
                    $stmt->execute() or die("ERROR(".__LINE__."): ".Database::error().": ".$annotation["class"]);

                    if ($result = $stmt->get_result()) {
                        if ($row = $result->fetch_assoc()) {
                            if (!empty($row['annometa'])) {
                                $skipped_existing++;
                                continue;
                            }
                            $result->close();

                            // update annotation entry
                            if ($ustmt = Database::prepare("UPDATE `Annotation` SET annometa = ? WHERE Id = ? LIMIT 1")) {
                                $ustmt->bind_param("si", $annotation['meta'], $row['Id']);
                                $ustmt->execute() or die("ERROR(".__LINE__."): ".Database::error().": ".$annotation["class"]);
                                $inserted_meta++;
                                $ustmt->close();
                            } else {
                                $missed_meta++;
                                $ustmt->close();
                                continue;
                            }
                        } else {
                            $result->close();
                            $missed_meta++;
                            $stmt->close();
                            continue;
                        }
                    } else {
                        $missed_meta++;
                        $stmt->close();
                        continue;
                    }

                    $stmt->close();
                } 
            }

        }

        $za->close();
        Database::query("COMMIT");
        print_r("=======================\nInserted metadata: $inserted_meta into $inserted_docs documents - Target annotation not found for $missed_meta metadata entries - $missed_docs documents not found\n");
        print_r("skipped $skipped_existing metadata entries that were already filled".PHP_EOL);
    }

    // bulk insert function, $target needs to point to a zip file
    // TODO get rid of those unnecessary token selects when inserting annotations
    // TODO cache class id lookup selects
    public static function bulkinsert($target, $skiptokens = false) {
        $za = new ZipArchive();
        $za->open($target);

        $files_text = array();
        $files_tokens = array();
        $files_annos = array();
        for($i = 0; $i < $za->numFiles; $i++){ 
            $stat = $za->statIndex( $i ); 
            $fileext = strtolower(pathinfo($stat['name'], PATHINFO_EXTENSION));
            if ($fileext === "txt") {
                $files_text[] = $stat['name'];
            } else if ($fileext === "csv") {
                $files_tokens[] = $stat['name'];
            } else if ($fileext === "annodb") {
                $files_annos[] = $stat['name'];
            }
        }

        Database::query("START TRANSACTION");

        $activepubs = array();
        $inserted_pubs = 0;
        $existing_pubs = 0;
        if (!$skiptokens) {
            foreach ($files_tokens as $tokenfile) {
            print_r("Importing $tokenfile\n");
            // create publication
            $pubname = self::nameFromFilename($tokenfile);
            $pubuser = self::userFromFilename($tokenfile);

            print_r($tokenfile . "\t" . $pubname . PHP_EOL);
            // check if it already exists
            $existing = Publications::byName($pubname);
            $pub_existed = FALSE;
            if ($existing) {
                $pub_existed = TRUE;
                print_r("Existing publication: $existing".PHP_EOL);
                $activepubs[$pubname] = $existing;
                $existing_pubs++;
            } else {
                print_r("Creating new publication $pubname".PHP_EOL);
                $activepubs[$pubname] = Publications::create($pubname, $tokenfile);
                $inserted_pubs++;
            }

            $inserted = 0;
            if (!$pub_existed) {
                // insert tokens for all new publications
                foreach(explode("\n", $za->getFromName($tokenfile)) as $line) {
                    if (trim($line) === '') continue;
                    if (trim($line)[0] === '#') continue;
                    $token = array_combine(array("sentence", "number", "onset", "offset", "text"), str_getcsv($line, "\t"));

                    if($stmt = Database::prepare("INSERT INTO `Token` (`PublicationId`, `Text`, `Onset`, `Offset`, `Sentence`, `Number`) VALUES(?, ?, ?, ?, ?, ?);")) {
                        $stmt->bind_param("isiiii", $activepubs[$pubname]->id, $token["text"], $token["onset"], $token["offset"], $token["sentence"], $token["number"]);
                        $stmt->execute() or die("ERROR(".__LINE__."): ".Database::error());
                        $inserted++;
                        $stmt->close();
                    } else die("ERROR(".__LINE__."): ".Database::error());
                }
            } else {
                print_r("Skipping token insertion since publication existed.".PHP_EOL);
            }
            print_r("$inserted inserted".PHP_EOL);
            
            if (!empty($pubuser)) {
                $file_user = Users::byMail($pubuser);
                if (!empty($file_user)) {
                if($stmt = Database::prepare("INSERT IGNORE INTO `User_Publication` (`UserId`, `PublicationId`) VALUES(?, ?)")) {
                    $stmt->bind_param("ii", $file_user->id, $publicationId);
                    $stmt->execute() or die("ERROR(".__LINE__."): ".Database::error);
                    $stmt->close();
                    print_r("associated user ".$file_user->mail.PHP_EOL);
                } else die("ERROR(".__LINE__."): ".$mysqli->error);
                }
            }

        }
        }

        print_r("=======================\nInserted publications: $inserted_pubs\nExisting: $existing_pubs\n"); 
        $inserted_annos = 0;

        // retrieve next available index
        $nextIndex = -1;
        if ($stmt = Database::prepare("SELECT `AUTO_INCREMENT` FROM  INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'anno' AND TABLE_NAME = 'Annotation';")) {
            $stmt->execute() or die("ERROR: ".Database::error());
            $result = $stmt->get_result();
            $arr = $result->fetch_row();
            $nextIndex = intval($arr[0]);
            $stmt->close();
        } else die(Database::error());

        $classIDs = array();

        // insert annotations
        foreach($files_annos as $annofile) {
            $content = $za->getFromName($annofile);
            // gather users from filename
            $username = explode("_", pathinfo($annofile, PATHINFO_FILENAME));
            $username = $username[count($username) - 1];
            $anno_user = Users::byMail($username);

            $publication = Publications::byName(self::nameFromFilename($annofile));
            $publicationId = $publication->id;
            print_r("ANNOFILE:: user=$anno_user pub=$publication".PHP_EOL);
            // associate user with publication
            if($stmt = Database::prepare("INSERT IGNORE INTO `User_Publication` (`UserId`, `PublicationId`) VALUES(?, ?)")) {
                $stmt->bind_param("ii", $anno_user->id, $publicationId);
                $stmt->execute() or die("ERROR(".__LINE__."): ".Database::error);
                $stmt->close();
            } else die("ERROR(".__LINE__."): ".$mysqli->error);

            $annotations = array();
            // read annotations from file
            foreach(explode("\n", $content) as $line){
                if (strlen($line) === 0 or trim($line)[0] === '#') continue;
                $linecsv = str_getcsv($line, "\t");
                if (sizeof($linecsv) === 5) {
                    $annotation = array_combine(array("index", "class", "onset", "offset", "text"), $linecsv);
                    $annotation['meta'] = null;
                } else {
                    $annotation = array_combine(array("index", "class", "onset", "offset", "text", "meta"), $linecsv);
                }
                $annotations[] = $annotation;
            }
            unset($content);

            // gather token and class ids
            foreach($annotations as $idx => $annotation) {
                // gather token information on the current annotation
                if (!array_key_exists($annotation["class"], $classIDs)) {
                    $matchingClass = AnnoClass::byName($annotation["class"]);
                    if (!empty($matchingClass)) {
                        $classIDs[$annotation["class"]] = $matchingClass->id;
                    } else {
                        print_r("WARNING: class <".$annotation['class']."> not found! (in: $annofile, annotation: ".json_encode($annotation).")".PHP_EOL);
                    }
                }
                $annotation['classId'] = $classIDs[$annotation["class"]];

                if ($stmt = Database::prepare("SELECT `Number`, `Sentence` FROM `Token` WHERE `Onset` = ? AND `PublicationId` = ?;")) {
                    $stmt->bind_param("ii", $annotation["onset"], $publicationId);
                    $stmt->execute() or die("ERROR(" . __LINE__ . "): " . Database::error());
                    if ($result = $stmt->get_result()) {
                        if ($row = $result->fetch_assoc()) {
                            $annotation["onsetToken"] = $row;
                        }
                        $result->close();
                    }
                    $stmt->close();
                } else die("ERROR(" . __LINE__ . "): " . Database::error());

                if ($stmt = Database::prepare("SELECT `Number`, `Sentence` FROM `Token` WHERE `Offset` = ? AND `PublicationId` = ?;")) {
                    $stmt->bind_param("ii", $annotation["offset"], $publicationId);
                    $stmt->execute() or die("ERROR(" . __LINE__ . "): " . Database::error());
                    if ($result = $stmt->get_result()) {
                        if ($row = $result->fetch_assoc()) {
                            $annotation["offsetToken"] = $row;
                        }
                        $result->close();
                    }
                    $stmt->close();
                } else die("ERROR(" . __LINE__ . "): " . Database::error());
                
                // insert annotation entry
                if ($stmt = Database::prepare("INSERT IGNORE INTO `Annotation` (`PublicationId`, `Index`, `Class`, `Sentence`, `Onset`, `Offset`, `Text`, `User`) VALUES(?, ?, ?, ?, ?, ?, ?, ?);")) {
                    $stmt->bind_param("iiiiiisi", $publicationId, $nextIndex, $annotation["classId"], $annotation["onsetToken"]["Sentence"], $annotation["onsetToken"]["Number"], $annotation["offsetToken"]["Number"], $annotation["text"], $anno_user->id);
                    $stmt->execute() or die("ERROR(".__LINE__."): ".Database::error().": ".$annotation["class"]);

                    $inserted_annos++;
                    $nextIndex++;
                    $stmt->close();
                } else die("ERROR(" . __LINE__ . "): " . Database::error());

                $annotations[$idx] = $annotation;
            }
        }
        $za->close();
        Database::query("COMMIT");
        print_r("=======================\nInserted publications: $inserted_pubs\nExisting: $existing_pubs\nAnnotations: $inserted_annos\n"); 
    }
    
    public static function renderSelection() {
        global $activepub;
?> 
<div class="ui-controlgroup ui-controlgroup-horizontal" id="document">
    <div class="ui-controlgroup-item toolbar-text">
<?php
        if (!empty($activepub)) {
            echo "<span>Document:</span> <span class=\"activedoc_title\">".$activepub->name."</span>";
        }
?>
</div>
<a href="index2.php?action=selectpub" title="Select document" class="ui-controlgroup-item ui-button"><i class="fas fa-file-alt"></i> <?= empty($activepub) ? "Select Document" : "Change" ?></a>
<?php        if ($_SESSION['admin'] && !empty($activepub)) { ?>
        <a href="index2.php?action=renamepub.ui" title="Rename document" class="ui-controlgroup-item ui-button"><i class="fas fa-edit"></i></a>
        <a href="#" class="export_select ui-controlgroup-item ui-button" title="Export" data-jq-dropdown="#jq-dropdown-export"><i class="fas fa-cloud-download-alt"></i></a>
        <div id="jq-dropdown-export" class="jq-dropdown jq-dropdown-tip">
                <ul class="jq-dropdown-menu">
                <li><a target="_new" href="rdf.php?publication=<?= $activepub->id ?>&output=annotation&user=<?= urlencode(Users::loginUser()->mail) ?>"><i class="fas fa-pencil-alt"></i> Annotations</a></li>
                <li><a target="_new" href="export.php?publication=<?= $activepub->id ?>&output=document"><i class="fas fa-file-alt"></i> Tokens</a></li>
                <li><a target="_new" href="rdf.php?publication=<?= $activepub->id ?>&user=<?= urlencode(Users::loginUser()->mail) ?>"><i class="fas fa-database"></i> RDF</a></li>
                </ul>
            </div>
<?php
        } 
        echo "</div>";
    }
}


?>
