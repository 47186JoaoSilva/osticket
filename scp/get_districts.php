<?php
require('staff.inc.php');
if (isset($_GET['address'])) {
    $address = $_GET['address'];
    $districts = FormsPlugin::getDistricts($address);
    header('Content-Type: application/json');
    echo json_encode($districts);
    exit;
} else {
    header("HTTP/1.0 400 Bad Request");
    echo "Address not provided";
    exit;
}
?>

