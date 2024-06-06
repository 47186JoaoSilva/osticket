<?php
require('staff.inc.php');
$backupFile = INCLUDE_DIR . "plugins/forms_plugin/mysqldump/combined_backup.sql";
if (unlink($backupFile)) {
    error_log("Backup file combined_backup.sql deleted successfully.");
} else {
    error_log("Error deleting backup file combined_backup.sql.");
}
exit
?>

