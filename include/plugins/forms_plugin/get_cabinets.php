<?php
require('staff.inc.php');
if (isset($_GET['address']) && $_GET['address'] !== "") {
    $address = $_GET['address'];
    $places = FormsPlugin::getPlaces($address);
    header('Content-Type: application/json');
    echo json_encode($places);
    exit;
}
else {
    header("HTTP/1.0 400 Bad Request");
    echo "Address not provided";
    exit;
}
?>
