<?php
require('staff.inc.php');
if (isset($_GET['district'])) {
    $district = $_GET['district'];
    $addresses = FormsPlugin::getAddressesByDistrict($district);
    header('Content-Type: application/json');
    echo json_encode($addresses);
    exit;
} else {
    header("HTTP/1.0 400 Bad Request");
    echo "District not provided";
    exit;
}
?>
