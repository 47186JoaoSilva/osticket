<?php
require('staff.inc.php');
if (isset($_GET['address']) && $_GET['address'] !== "") {
    $address = $_GET['address'];
    $cabinets = FormsPlugin::getCabinets($address, null);
    header('Content-Type: application/json');
    echo json_encode($cabinets);
    exit;
} elseif(isset($_GET['district']) && $_GET['district'] !== ""){
    $district = $_GET['district'];
    $cabinets = FormsPlugin::getCabinets(null, $district);
    header('Content-Type: application/json');
    echo json_encode($cabinets);
    exit;
} elseif(isset($_GET['address']) && isset($_GET['district'])){
    $address = $_GET['address'];
    $district = $_GET['district'];
    $cabinets = FormsPlugin::getCabinetsbyDistrict($address, $district);
    header('Content-Type: application/json');
    echo json_encode($cabinets);
    exit;
}
else {
    header("HTTP/1.0 400 Bad Request");
    echo "Address not provided";
    exit;
}
?>
