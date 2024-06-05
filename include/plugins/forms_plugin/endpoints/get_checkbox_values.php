<?php
require('staff.inc.php');
if (isset($_GET['place'])) {
    $place = $_GET['place'];
    $pk = extractPk($place);
    $c_d = extractC_d($place);
    $checkboxValues = FormsPlugin::getEquipments($pk,$c_d);
    header('Content-Type: application/json');
    echo json_encode($checkboxValues);
    exit;
}
else {
    header("HTTP/1.0 400 Bad Request");
    echo "Place not provided";
    exit;
}

function extractPk($place) {
    if (preg_match('/km\s+([\d.]+)/', $place, $matches)) {
        return "km " . $matches[1];
    }
    return null;
}

function extractC_d($place) {
    if (preg_match('/\b([A-Za-z])\b/', $place, $matches)) {
        return $matches[1];
    }
    return null;
}
?>
