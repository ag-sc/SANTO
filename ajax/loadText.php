<?php
require_once("../php/user.php");
Users::ensureActiveLogin();

if (!isset($_SESSION['document'])) {echo ""; exit();};

require_once("../php/functions.php");
require_once("../php/user.php");
$loginuser = Users::loginUser();
$publicationId = $_SESSION['document'];
$tokens = array();

if ($stmt = $mysqli->prepare("SELECT `Id`, `Text`, `Onset`, `Offset`, `Sentence`, `Number` FROM `Token` WHERE `PublicationId` = ?")) {
    $stmt->bind_param("i", $publicationId);
    $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
    $stmt->bind_result($id, $text, $onset, $offset, $sentence, $number);
    while ($stmt->fetch()) {
        if ($onset > $offset) {
            echo "$onset, $offset<br>";
            continue;
        }
        if (!array_key_exists($sentence, $tokens)) $tokens[$sentence] = array();
        $tokens[$sentence][$number] = array("text" => $text, "onset" => $onset, "offset" => $offset, "annotations" => array(), "starts" => array(), "ends" => array());
    }
    $stmt->close();
} else die("ERROR(".__LINE__."): ".$mysqli->error);

$query = <<<QUERY
SELECT (`Offset` - `Onset`) AS `Length`, `Annotation`.`Id`, `Index`, `Sentence`, `Onset`, `Offset`, `Class`.`Name` AS `Class`, `Class`.`Id` AS `ClassId`, `User`, `Class`.`Description` AS `Description`, `Reference`
    FROM `Annotation` 
    JOIN `Class` 
        ON `Class`.`Id` = `Annotation`.`Class` 
    WHERE `PublicationId` = ? AND (`Offset` - `Onset`) >= 0 AND (`User` = ?
QUERY;
if (isset($_SESSION['showPredefined']) && $_SESSION['showPredefined'])
    $query .= " OR `User` = 1";
if (isset($_GET['users']))
    $users = $_GET['users'];
else
    $users = [];
$query .= str_repeat(" OR `User` = ?", sizeof($users));
$query .= ') ORDER BY `Length` DESC';

if ($stmt = $mysqli->prepare($query)) {
    $params = array(&$publicationId, &$_SESSION['user']);
    for ($i = 0; $i < sizeof($users); ++$i) {
        $params[] = &$users[$i];
    }
    call_user_func_array(array($stmt, "bind_param"), array_merge(array(str_repeat("i", sizeof($params))), $params));
    $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
    $stmt->bind_result($length, $id, $index, $sentence, $onset, $offset, $class, $classId, $user, $description, $reference);
    while ($stmt->fetch()) {
        for ($i = $onset; $i <= $offset; ++$i) {
            $tokens[$sentence][$i]['annotations'][] = $classId;
            if ($i == $onset)
                $tokens[$sentence][$i]["starts"][] = array("id" => $id, "annotationId" => $index, "tokenId" => $i, "classId" => $classId, "class" => $class, "onset" => $onset, "offset" => $offset, "user" => $user, "description" => $description, "reference" => $reference);
            if ($i == $offset)
                $tokens[$sentence][$i]["ends"][] = $index;
        }
    }
    $stmt->close();

} else die("ERROR(".__LINE__."): ".$mysqli->error);


// Block:
// {depth: int, lengths: int, annotations: {onset => {id: int, offset: int}}}

$depthBlockIndex = 0;
$depthBlocks = array(array("depth" => 0, "length" => 1, "base-offset" => PHP_INT_MAX , "annotations" => array()));
foreach ($tokens as $sentenceId => $sentence) {
    $annotationDepth = 0;
    foreach ($sentence as $tokenId => $token) {
        $annotationDepth += sizeof($token["starts"]);
        $depthBlocks[$depthBlockIndex]["depth"] = max($depthBlocks[$depthBlockIndex]["depth"], $annotationDepth);
        foreach ($token["starts"] as $annotation)
            $depthBlocks[$depthBlockIndex]["base-offset"] = min($depthBlocks[$depthBlockIndex]["base-offset"], $annotation["onset"]);
        $annotationDepth -= sizeof($token["ends"]);

        foreach($token["starts"] as $annotation) {
            array_push($depthBlocks[$depthBlockIndex]["annotations"], $annotation);
        }
        if ($annotationDepth == 0) {
            array_push($depthBlocks, array("depth" => 0, "length" => 1, "base-offset" => PHP_INT_MAX , "annotations" => array()));
            $depthBlockIndex += 1;
        } else {
            $depthBlocks[$depthBlockIndex]["length"] += 1;
        }
    }
}

//echo nl2br(json_encode($tokens, JSON_PRETTY_PRINT));

echo "<table>";

function fit_any($annotations, $lengthIndex) {
    foreach ($annotations as $index => $annotation) {
        if ($annotation['onset'] >= $lengthIndex)
            return $index;
    }
    return -1;
}

function select_user_color($user) {
    switch ($user) {
        case 3: return "red";
        case 4: return "blue";
        case 5: return"darkgreen";
        case 6: return"darkorange";
        case 7: return"aqua";
        case 8: return"brown";
        case 9: return"indigo";
        case 10: return"gray";
        default: return"black";
    }
}

function get_reference_user_color($ref) {
    global $mysqli;

    if ($stmt = $mysqli->prepare( "SELECT `User` FROM `Annotation` WHERE Id = ?")) {
        $stmt->bind_param("i", $ref);
        $stmt->execute() or die("ERROR(".__LINE__."): ".$mysqli->error);
        $stmt->bind_result($user);
        if ($stmt->fetch()) {
            return select_user_color($user);
        }
        $stmt->close();
    } else die("ERROR(".__LINE__."): ".$mysqli->error);
    return "black";
}

$lastOffset = 0;
$depthBlockIndex = 0;
$index = 0;
$indexedAnnotations = array();
foreach ($tokens as $sentenceId => $sentence) {
    echo "<tr class='sentence'><td class='line-number'>$sentenceId</td><td>";
    $annotationDepth = 0;


    $blockLengthIndex = 0;

    foreach ($sentence as $tokenId => $token) {
        $previousToken = $tokenId-1;
        $whitespaces = $token["onset"] - $lastOffset;
        echo "<span class='whitespace' sentence='$sentenceId' token-left='$previousToken' token-right='$tokenId'>".str_repeat(" ", $whitespaces)."</span>";

        $annotationDepth += sizeof($token["starts"]);
        $blockDepth = $depthBlocks[$depthBlockIndex]['depth'];
        $blockLength = $depthBlocks[$depthBlockIndex]['length'];
        $baseOnset = $depthBlocks[$depthBlockIndex]['base-offset'];
        $annotationSize = sizeof($depthBlocks[$depthBlockIndex]['annotations']);
        if ($blockLengthIndex == 0 && $blockDepth > 0) {
            echo "<table class='annotation-table'>";
            $lengthIndex = $baseOnset;

            $remainingAnnotations = $depthBlocks[$depthBlockIndex]['annotations'];
            echo "<tr class='annotation-label-tr'>";
            while (sizeof($remainingAnnotations) > 0) {
                $i = fit_any($remainingAnnotations, $lengthIndex);
                if ($i == -1) {
                    $lengthIndex = $baseOnset;
                    echo "</tr><tr class='annotation-label-tr'>";
                } else {
                    $class = $remainingAnnotations[$i]['class'];
                    $onset = $remainingAnnotations[$i]['onset'];
                    $offset = $remainingAnnotations[$i]['offset'];
                    $annotation = $remainingAnnotations[$i]["annotationId"];
                    $classId = $remainingAnnotations[$i]["classId"];
                    $id = $remainingAnnotations[$i]["id"];
                    $description = $remainingAnnotations[$i]["description"];
                    $user = $remainingAnnotations[$i]['user'];
                    $ref = $remainingAnnotations[$i]['reference'];

                    $distance = $onset - $lengthIndex;
                    if ($distance > 0) {
                        echo "<td class='annotation-label-td annotation-label-empty' colspan='$distance'></td>";
                    }
                    $length = $offset - $onset + 1;


                    $classes = "";
                    if ($ref != "") {
                        $style = "border-style: dashed; border-color: ".get_reference_user_color($ref);
                    } else {
                        $style = "border-color: ".select_user_color($user);
                    }
                    foreach (getSuperclasses($classId) as $superclass) {
                        $classes .= " highlight-at-$superclass";
                    }

                    echo "<td class='annotation-label-td' colspan='$length'>";
                    $curuseranno = ($user == $loginuser->id) ? "1" : "0";
                    echo "<span class='annotation-label no-select$classes' annotation='$annotation' classId='$classId' onset='$onset' offset='$offset' sentence='$sentenceId' index='$index' annotationId='$id' data-bycuruser='$curuseranno' annotator='$user' reference='$ref' style='$style' title='$description'>$class</span>";
                    echo "</td>";
                    $index += 1;
                    unset($remainingAnnotations[$i]);
                    $lengthIndex = $offset + 1;
                }
            }

            echo "</tr>";
        }
        if ($blockLengthIndex == 0 && $blockDepth > 0) {
            echo "<tr class='annotation-tr'>";
        }
        if ($blockDepth != 0)
            echo "<td class='annotation-td annotation-text'>";
        echo "<span id='token-$tokenId' class='token' sentence='$sentenceId' token='$tokenId' whitespaces='$whitespaces'>{$token["text"]}</span>";
        if ($blockDepth != 0)
            echo "</td>";

        $annotationDepth -= sizeof($token["ends"]);
        if ($annotationDepth == 0)
            $depthBlockIndex += 1;
        $blockLengthIndex = ($blockLengthIndex + 1) % $blockLength;

        if ($blockLengthIndex == 0 && $blockDepth > 0)
            echo "</tr></table>";

        $lastOffset = $token["offset"];
    }
    echo "</tr>";
}
echo "</table>";
