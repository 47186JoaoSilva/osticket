<?php
require('staff.inc.php');
if (isset($_GET['cabinet'])) {
    $cabinet = $_GET['cabinet'];
    $serialNumber = extractSerialNumber($cabinet);
    $checkboxValues = FormsPlugin::getEquipments($serialNumber);
    //$checkboxValues = array('x','y');
    // Return checkbox values as JSON
    header('Content-Type: application/json');
    echo json_encode($checkboxValues);
    exit;
}
else {
    header("HTTP/1.0 400 Bad Request");
    echo "Cabinet not provided";
    exit;
}

function extractSerialNumber($cabinet) {
    // Define the pattern to match the serial number
    $pattern = '/Série:\s*(\S+)/';

    // Perform the regular expression match
    if (preg_match($pattern, $cabinet, $matches)) {
        // Extract the serial number from the matches array
        return $matches[1];
    } else {
        // No match found
        return "Serial number not found.";
    }
}
?>
