<?php
// Include necessary osTicket files
require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.signal.php');
require_once(INCLUDE_DIR . 'class.app.php');
require_once(INCLUDE_DIR . 'class.dispatcher.php');

// Define your plugin class
class FormsPlugin extends Plugin {
    /*function bootstrap() {
        //Signal quando o plugin é ativado ou desativado
        Signal::connect('model.updated', array($this, 'restoreOrReplaceFiles'));
        Signal::connect('model.updated', array($this, 'addOrDeleteColumnsFromTable'));
        
        //Signal quando o plugin é apagado
        Signal::connect('model.deleted', array($this, 'restoreOrReplaceFiles'));
        Signal::connect('model.deleted', array($this, 'addOrDeleteColumnsFromTable'));     
        
    }*/
     function bootstrap() {
      Signal::connect('model.updated', array($this, 'addFields'));
    }
    
     function addFields() {
      if($this->isPluginActive()) {
          $this->addCabin();
      }
      else {
          
      }
    }
    function restoreOrReplaceFiles() {
        $this->restoreOrReplaceTicketOpenFile();
        $this->restoreOrReplaceTicketViewFile();
        $this->restoreOrReplaceClassTicketFile();
    }
    
    function restoreOrReplaceTicketOpenFile() {
        if($this->isPluginActive()) {
            $this->replaceTicketOpenFile();
        }
        else {
            $this->restoreTicketOpenFile();
        }
    }
    
    function restoreOrReplaceTicketViewFile() {
        if($this->isPluginActive()) {
            $this->replaceTicketViewFile();
        }
        else {
            $this->restoreTicketViewFile();
        }
    }
    
    function restoreOrReplaceClassTicketFile() {
        if($this->isPluginActive()) {
            $this->replaceClassTicketFile();
        }
        else {
            $this->restoreClassTicketFile();
        }
    }
    
    function addOrDeleteColumnsFromTable() {
        if($this->isPluginActive()) {
            $this->addColumnsToTable();
            $this->copyBackupIfExists();
        }
        else {
            //TODO(): Add condition to see if the checkbox is checked or not
            $this->createBackupTables();
            $this->deleteLinesFromTable();
            $this->deleteColumnsFromTable();
        }
    }
    
    function replaceTicketOpenFile() {
        $this->replaceFile(INCLUDE_DIR . 'staff/ticket-open.inc.php', 'ticket-open-modified.inc.php');
    }
    
    function replaceTicketViewFile() {
        $this->replaceFile(INCLUDE_DIR . 'staff/ticket-view.inc.php', 'ticket-view-modified.inc.php');
    }
    
    function replaceClassTicketFile() {
        $this->replaceFile(INCLUDE_DIR . 'class.ticket.php', 'class.ticket-modified.php');
    }    
    
    // Function to restore the original content of ticket-open.inc.php
    function restoreTicketOpenFile() {
        $this->restoreFile(INCLUDE_DIR . 'staff/ticket-open.inc.php', 'ticket-open-backup.inc.php');
    }
    
    // Function to restore the original content of ticket-view.inc.php
    function restoreTicketViewFile() {
        $this->restoreFile(INCLUDE_DIR . 'staff/ticket-view.inc.php', 'ticket-view-backup.inc.php');
    }
    
    // Function to restore the original content of class.ticket.php
    function restoreClassTicketFile() {
        $this->restoreFile(INCLUDE_DIR . 'class.ticket.php', 'class.ticket-backup.php');
    }
    
    // Function to replace the content of a file with modified content
    function replaceFile($file_path, $modified_file_name) {
        $modified_file_path = __DIR__ . '/' . $modified_file_name;
        
        // Check if both files exist
        if (file_exists($file_path) && file_exists($modified_file_path)) {
            // Read the content of the modified file
            $modified_content = file_get_contents($modified_file_path);
            
            //TODO(): Copiar o ficheiro original para o backup
            
            // Write the modified content to the original file, overwriting the existing content
            file_put_contents($file_path, $modified_content);
        } else {
            // Handle the case where either file doesn't exist
            if (!file_exists($file_path)) {
                error_log("$file_path does not exist!");
            }
            if (!file_exists($modified_file_path)) {
                error_log("$modified_file_path does not exist!");
            }
        }
    }
    
    // Function to restore the original content of a file
    function restoreFile($file_path,$backup_path) {
        $backup_file_path = __DIR__ . '/' . basename($backup_path);
        
        // Check if the backup file exists
        if (file_exists($backup_file_path)) {
            // Restore the backup file to the original location
            copy($backup_file_path, $file_path);
            // Delete the backup file after restoring
            //unlink($backup_file_path);
        } else {
            // Handle the case where the backup file doesn't exist
            error_log("Backup file for $file_path does not exist!");
        }
    }
    
    function addColumnsToTable() {
        $columns = array(
            'textbox_name' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'combo_option' => "VARCHAR(100) DEFAULT ''", 
            'radio_option' => "VARCHAR(100) DEFAULT ''", 
            'numeric_input' => "INT DEFAULT 0", 
            'email_input' => "VARCHAR(255) DEFAULT ''", 
            'checkbox_option' => "VARCHAR(100) DEFAULT ''" 
        );
        
        foreach ($columns as $column => $definition) {
            $query = "ALTER TABLE `ost_ticket` ADD COLUMN `$column` $definition";

            if (db_query($query)) {
                error_log("Column '$column' added successfully.");
            } else {
                error_log("Error adding column '$column' to table.");
            }
        }
    }
    
    function deleteColumnsFromTable() {
        $columns = array(
            'textbox_name',
            'combo_option',
            'radio_option',
            'numeric_input',
            'email_input',
            'checkbox_option'
        );

        foreach ($columns as $column) {
            // Define the SQL query to drop the column
            $query = "ALTER TABLE `ost_ticket` DROP COLUMN `$column`";

            if (db_query($query)) {
                error_log("Column '$column' deleted successfully.");
            } else {
                error_log("Error deleting column '$column' from table.");
            }
        }
    }
    
    function isPluginActive() {
        // Define your SQL query to fetch isactive from ost_plugin table
        $query = "SELECT isactive FROM ost_plugin WHERE name = 'Forms Plugin'"; //TODO(): Is this the best way?

        // Execute the query
        $result = db_query($query);

        // Check if the query was successful
        if ($result) {
            $row = db_fetch_array($result);
            return $row['isactive']; // Return the value of isactive
        } else {
            // Handle the case where the query fails
            error_log("Error fetching isactive from ost_plugin table");
            return false; // Return false if unable to fetch isactive
        }
    }
    
    function deleteLinesFromTable() {
        $query = "SELECT ticket_id FROM ost_ticket WHERE textbox_name != ''";
        $result = db_query($query);

        if (!$result) {
            error_log("Error retrieving ticket_id where textbox_name is not empty: " . db_error());
            return; 
        }

        $ticketIds = [];

        while ($row = db_fetch_array($result)) {
            $ticketIds[] = $row['ticket_id'];
        }

        $deleteQuery1 = "DELETE FROM ost_ticket WHERE ticket_id IN (" . implode(',', $ticketIds) . ")";
        $deleteResult1 = db_query($deleteQuery1);

        $deleteQuery2 = "DELETE FROM ost_ticket__cdata WHERE ticket_id IN (" . implode(',', $ticketIds) . ")";
        $deleteResult2 = db_query($deleteQuery2);

        if ($deleteResult1 && $deleteResult2) {
            error_log("Rows deleted successfully from ost_ticket and ost_ticket__cdata where textbox_name was not empty.");
        } else {
            error_log("Error deleting rows from ost_ticket and ost_ticket__cdata where textbox_name was not empty: " . db_error());
        }
    }  
    
    function copyBackupIfExists() {
        // Check if backup tables exist
        $ticketBackupExists = db_query("SHOW TABLES LIKE 'ost_ticket_backup'")->num_rows > 0;
        $cdataBackupExists = db_query("SHOW TABLES LIKE 'ost_ticket__cdata_backup'")->num_rows > 0;

        // If backup tables exist, copy data
        if ($ticketBackupExists) {
            // Copy data from ost_ticket_backup to ost_ticket
            $copyTicketQuery = "INSERT INTO ost_ticket SELECT * FROM ost_ticket_backup";
            $result1 = db_query($copyTicketQuery);
            if (!$result1) {
                error_log("Error copying data from ost_ticket_backup to ost_ticket: " . db_error());
                return;
            }
        }

        if ($cdataBackupExists) {
            // Copy data from ost_ticket__cdata_backup to ost_ticket__cdata
            $copyCdataQuery = "INSERT INTO ost_ticket__cdata SELECT * FROM ost_ticket__cdata_backup";
            $result2 = db_query($copyCdataQuery);
            if (!$result2) {
                error_log("Error copying data from ost_ticket__cdata_backup to ost_ticket__cdata: " . db_error());
                return;
            }
        }
        
        // Delete backup tables if they exist
        if ($ticketBackupExists) {
            $deleteTicketBackupQuery = "DROP TABLE ost_ticket_backup";
            $result3 = db_query($deleteTicketBackupQuery);
            if (!$result3) {
                error_log("Error deleting backup table ost_ticket_backup: " . db_error());
                return;
            }
        }

        if ($cdataBackupExists) {
            $deleteCdataBackupQuery = "DROP TABLE ost_ticket__cdata_backup";
            $result4 = db_query($deleteCdataBackupQuery);
            if (!$result4) {
                error_log("Error deleting backup table ost_ticket__cdata_backup: " . db_error());
                return;
            }
        }
        
        // Log success
        if ($ticketBackupExists || $cdataBackupExists) {
            error_log("Data copied from backup tables to original tables successfully.");
        } else {
            error_log("Backup tables do not exist.");
        }
    }

    
    function createBackupTables() { // 
        // Check if the tables already exist
        $ticketTableExists = db_query("SHOW TABLES LIKE 'ost_ticket_backup'")->num_rows > 0;
        $cdataTableExists = db_query("SHOW TABLES LIKE 'ost_ticket__cdata_backup'")->num_rows > 0;

        if (!$ticketTableExists) {
            // Create ost_ticket_backup table
            $createTicketTableQuery = "CREATE TABLE ost_ticket_backup LIKE ost_ticket";
            $result1 = db_query($createTicketTableQuery);
            if (!$result1) {
                error_log("Error creating table ost_ticket_backup: " . db_error());
                return;
            }

            // Copy data from ost_ticket to ost_ticket_backup where textbox_name != ''
            $copyTicketDataQuery = "INSERT INTO ost_ticket_backup SELECT * FROM ost_ticket WHERE ticket_id IN (SELECT ticket_id FROM ost_ticket WHERE textbox_name != '')";
            $result2 = db_query($copyTicketDataQuery);
            if (!$result2) {
                error_log("Error copying data to table ost_ticket_backup: " . db_error());
                return;
            }
        }

        if (!$cdataTableExists) {
            // Create ost_ticket__cdata_backup table
            $createCdataTableQuery = "CREATE TABLE ost_ticket__cdata_backup LIKE ost_ticket__cdata";
            $result3 = db_query($createCdataTableQuery);
            if (!$result3) {
                error_log("Error creating table ost_ticket__cdata_backup: " . db_error());
                return;
            }

            // Copy data from ost_ticket__cdata to ost_ticket__cdata_backup where ticket_id matches
            $copyCdataQuery = "INSERT INTO ost_ticket__cdata_backup SELECT * FROM ost_ticket__cdata WHERE ticket_id IN (SELECT ticket_id FROM ost_ticket WHERE textbox_name != '')";
            $result4 = db_query($copyCdataQuery);
            if (!$result4) {
                error_log("Error copying data to table ost_ticket__cdata_backup: " . db_error());
                return;
            }
        }

        // Log success
        if (!$ticketTableExists && !$cdataTableExists) {
            error_log("Tables ost_ticket_backup and ost_ticket__cdata_backup created and data copied successfully.");
        } else {
            error_log("Tables ost_ticket_backup and ost_ticket__cdata_backup already exist.");
        }
    }
        function addCabin() {
        $queryHasCabin = "SELECT name FROM `ost_form_field` WHERE name = 'cabine'";
        $resultHasCabin = db_query($queryHasCabin);
        if (db_num_rows($resultHasCabin) != 0)
            return;
        $querySort = "SELECT MAX(sort) FROM `ost_form_field` WHERE form_id = 2";
        $result = db_query($querySort);
        $row = db_fetch_row($result);
        $maxSort = $row[0];

        if(!$maxSort){
            error_log("Error trying to get the sort number of the form") . db_error();
        } else{
            $maxSort += 1;
            $queryConf = "SELECT serial_number FROM `SINCRO_Cabinet`";
            $confAux = db_query($queryConf);
            if(!$confAux){
                error_log("Error trying to get the cabin serial number values") . db_error();
            } else{
                $serialNumbers = array();
                while ($row = db_fetch_array($confAux)) {
                    $serialNumbers[] = $row['serial_number'];
                }
                $conf = '{"choices":"';
                $key = 1;
                foreach ($serialNumbers as $serialNumber) {
                    if(sizeof($serialNumbers) != $key){
                        $conf .= "{$key}:{$serialNumber}" . '\r\n';
                        $key++;
                    } else{
                        $conf .= "{$key}:{$serialNumber}";
                    }
                }
                $conf .= '","default":"","prompt":"Select","multiselect":false}';
                $confSlash = addslashes($conf);
                $query = "INSERT INTO `ost_form_field` 
                (`form_id`, `flags`, `type`, `label`, `name`, `configuration`, `sort`, `hint`, `created`, `updated`) 
                values ('2','30465','choices','Número de série','cabine','{$confSlash}','{$maxSort}', NULL, CURDATE(), CURDATE())";
                $result = db_query($query);
                
                if(!result){
                    error_log("Coudn't insert the values into the table ost_form_field") . db_error();
                } else{
                    echo ___('successe');
                }
            }
        }
    }
}

$forms_plugin = new FormsPlugin();
$forms_plugin->bootstrap();