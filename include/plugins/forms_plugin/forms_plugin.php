<?php
// Include necessary osTicket files
require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.signal.php');
require_once(INCLUDE_DIR . 'class.app.php');
require_once(INCLUDE_DIR . 'class.dispatcher.php');

// Define your plugin class
class FormsPlugin extends Plugin {
    function bootstrap() {
        //Signal quando o plugin é ativado ou desativado
        Signal::connect('model.updated', array($this, 'restoreOrReplaceFiles'));
        Signal::connect('model.updated', array($this, 'moveOrRemoveFiles'));
        Signal::connect('model.updated', array($this, 'addOrDeleteColumnsFromTable'));
        
        //Signal quando o plugin é apagado
        Signal::connect('model.deleted', array($this, 'restoreOrReplaceFiles'));
        Signal::connect('model.deleted', array($this, 'moveOrRemoveFiles'));
        Signal::connect('model.deleted', array($this, 'addOrDeleteColumnsFromTable')); 
    }
    
    
    function restoreOrReplaceFiles() {
        $this->restoreOrReplaceTicketOpenFile();
        $this->restoreOrReplaceTicketViewFile();
        $this->restoreOrReplaceClassTicketFile();
        $this->restoreOrReplaceOpenFile();
        $this->restoreOrReplacePluginsFile();
    }
    
    function restoreOrReplaceTicketOpenFile() {
        if($this->isPluginActive()) {
            if(!$this->doesColumnExist()) {
                $this->replaceTicketOpenFile();
            }
        }
        else {
            $this->restoreTicketOpenFile();
        }
    }
    
    function restoreOrReplaceTicketViewFile() {
        if($this->isPluginActive()) {
            if(!$this->doesColumnExist()) {
                $this->replaceTicketViewFile();
            }
        }
        else {
            $this->restoreTicketViewFile();
        }
    }
    
    function restoreOrReplaceClassTicketFile() {
        if($this->isPluginActive()) {
            if(!$this->doesColumnExist()) {
                $this->replaceClassTicketFile();
            }
        }
        else {
            $this->restoreClassTicketFile();
        }
    }
    
    function restoreOrReplaceOpenFile() {
        if($this->isPluginActive()) {
            if(!$this->doesColumnExist()) {
                $this->replaceOpenFile();
            }
        }
        else {
            $this->restoreOpenFile();
        }
    }
    
    function restoreOrReplacePluginsFile() {
        if($this->isPluginActive()) {
            if(!$this->doesColumnExist()) {
                $this->replacePluginFile();
            }
        }
        else {
            $this->restorePluginFile();
        }
    }
    
    function moveOrRemoveFiles() {
        if($this->isPluginActive()) {
            if(!$this->doesColumnExist()) {
                $this->moveToNewDirectory();
            }
        }
        else {
            $this->moveToOriginalDirectory();
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
    
    function replaceOpenFile() {
        $this->replaceFile(INCLUDE_DIR . 'client/open.inc.php', 'open-modified.inc.php');
    }
    
    function replacePluginFile() {
        $this->replaceFile(INCLUDE_DIR . 'staff/plugins.inc.php', 'plugins-modified.inc.php');
    }
    
    function restoreTicketOpenFile() {
        $this->restoreFile(INCLUDE_DIR . 'staff/ticket-open.inc.php', 'ticket-open-backup.inc.php');
    }
    
    function restoreTicketViewFile() {
        $this->restoreFile(INCLUDE_DIR . 'staff/ticket-view.inc.php', 'ticket-view-backup.inc.php');
    }
    
    function restoreClassTicketFile() {
        $this->restoreFile(INCLUDE_DIR . 'class.ticket.php', 'class.ticket-backup.php');
    }
    
    function restoreOpenFile() {
        $this->restoreFile(INCLUDE_DIR . 'client/open.inc.php', 'open-backup.inc.php');
    }
    
    function restorePluginFile() {
        $this->restoreFile(INCLUDE_DIR . 'staff/plugins.inc.php', 'plugins-backup.inc.php');
    }
    
    function moveToNewDirectory() {
        $this->moveFileToDirectory(INCLUDE_DIR . 'plugins/forms_plugin/get_addresses.php', SCP_DIR . 'get_addresses.php');
        $this->moveFileToDirectory(INCLUDE_DIR . 'plugins/forms_plugin/get_cabinets.php', SCP_DIR . 'get_cabinets.php');
        $this->moveFileToDirectory(INCLUDE_DIR . 'plugins/forms_plugin/get_checkbox_values.php', SCP_DIR . 'get_checkbox_values.php');
        $this->moveFileToDirectory(INCLUDE_DIR . 'plugins/forms_plugin/erase_data.php', SCP_DIR . 'erase_data.php');
    }
    
    function moveToOriginalDirectory() {
        $this->moveFileToDirectory(SCP_DIR . 'get_addresses.php', INCLUDE_DIR . 'plugins/forms_plugin/get_addresses.php');
        $this->moveFileToDirectory(SCP_DIR . 'get_cabinets.php', INCLUDE_DIR . 'plugins/forms_plugin/get_cabinets.php');
        $this->moveFileToDirectory(SCP_DIR . 'get_checkbox_values.php', INCLUDE_DIR . 'plugins/forms_plugin/get_checkbox_values.php');
        $this->moveFileToDirectory(SCP_DIR . 'erase_data.php', INCLUDE_DIR . 'plugins/forms_plugin/erase_data.php');
    }
    
    
    function replaceFile($file_path, $modified_file_name) {
        $modified_file_path = __DIR__ . '\modified_files' . '/' . $modified_file_name;
        
        // Check if both files exist
        if (file_exists($file_path) && file_exists($modified_file_path)) {
            // Read the content of the modified file
            $modified_content = file_get_contents($modified_file_path);
            
            //Copiar o ficheiro original para o backup
            
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
        $backup_file_path = __DIR__ . '\backup_files' . '/' . basename($backup_path);
        
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
    
    function moveFileToDirectory($source, $destination) {
        // Check if the source file exists
        if (!file_exists($source)) {
            return "Source file does not exist.";
        }

        // Attempt to move the file
        if (rename($source, $destination)) {
            return "File moved successfully.";
        } else {
            return "Failed to move the file.";
        }
    }
    
    function addOrDeleteColumnsFromTable() {
        if($this->isPluginActive()) {
            $this->addColumnsToTable();
            $this->copyBackupIfExists();

        }
        else {
            $this->deleteLinesFromTable();
            $this->deleteColumnsFromTable();
        }
    }
    
    static function getDistricts($address) {
        if(!$address) {
            $query = "SELECT DISTINCT district FROM sincro_cabinet";
            $result = db_query($query);

            if ($result) {
                $districts = [];
                while ($row = db_fetch_array($result)) {
                    $districts[] = $row['district'];
                }
                return $districts;
            } else {
                // Handle the case where the query fails
                error_log("Error fetching isactive from ost_plugin table");
                return false; // Return false if unable to fetch isactive
            }
        } else {
            $query = "SELECT DISTINCT district FROM sincro_cabinet WHERE address = '$address'";
            $result = db_query($query);

            if ($result) {
                $districts = [];
                while ($row = db_fetch_array($result)) {
                    $districts[] = $row['district'];
                }
                return $districts;
            } else {
                // Handle the case where the query fails
                error_log("Error fetching isactive from ost_plugin table");
                return false; // Return false if unable to fetch isactive
            }
        }
    }
    
    static function getAddressesByDistrict($district) {
        $query = "SELECT DISTINCT address FROM sincro_cabinet WHERE district = '$district'";
        $result = db_query($query);

        if ($result) {
            $addresses = [];
            while ($row = db_fetch_array($result)) {
                $addresses[] = $row['address'];
            }
            return $addresses;
        } else {
            error_log("Error fetching isactive from ost_plugin table");
            return false; 
        }
    }
    
    static function getCabinets($address, $district) {
        if($address){
            $query = "SELECT model, serial_number FROM sincro_cabinet WHERE address = '$address'";
            $result = db_query($query);

            if ($result) {
                $cabinets = [];
                while ($row = db_fetch_array($result)) {
                    $cabinets[] = "Model: " . $row['model'] . " Nº Série: " . $row['serial_number'];
                }
                return $cabinets;
            } else {
                error_log("Error fetching isactive from ost_plugin table");
                return false; 
            }
        } 
    }
    
    static function getEquipments($serialNumber) {
        $cabinId = "";
        $cinemometerId = "";
        $routerId = "";
        $upsId = "";
        
        //PODE SE TROCAR PELO CODIGO DAS FUNÇOES ABAIXO
        $cabinIdQuery = "SELECT id FROM SINCRO_Cabinet WHERE serial_number = '$serialNumber'";
        $cabinIdresult = db_query($cabinIdQuery);
        if ($cabinIdresult) {
            $row = db_fetch_array($cabinIdresult);
            $cabinId = $row['id']; 
        }
        
        $cinemometerIdQuery = "SELECT idCinemometer FROM SINCRO_Cabinet_has_Cinemometer WHERE idCabin = '$cabinId'";
        $cinemometerIdResult = db_query($cinemometerIdQuery);
        if ($cinemometerIdResult) {
            $row = db_fetch_array($cinemometerIdResult);
            $cinemometerId = $row['idCinemometer']; 
        }
        
        $routerIdQuery = "SELECT idRouter FROM SINCRO_Cabinet_has_Router WHERE idCabin = '$cabinId'";
        $routerIdResult = db_query($routerIdQuery);
        if ($routerIdResult) {
            $row = db_fetch_array($routerIdResult);
            $routerId = $row['idRouter']; 
        }

        $upsIdQuery = "SELECT id_ups FROM SINCRO_Cabinet WHERE id = '$cabinId'";
        $upsIdresult = db_query($upsIdQuery);
        if ($upsIdresult) {
            $row = db_fetch_array($upsIdresult);
            $upsId = $row['id_ups']; 
        }
        
        $result = [];
        
        $cinemometerInfoQuery = "SELECT model, suplier, serial_number FROM SINCRO_Cinemometer WHERE id = '$cinemometerId'";
        $cinemometerInfoResult = db_query($cinemometerInfoQuery);
        if ($cinemometerInfoResult) {
            while ($row = db_fetch_array($cinemometerInfoResult)) {
                    $result[] = "Fornecedor: " . $row['suplier'] . " Model: " . $row['model'];
            }
        }
        
        $routerInfoQuery = "SELECT model, suplier, serial_number FROM SINCRO_Router WHERE id = '$routerId'";
        $routerInfoResult = db_query($routerInfoQuery);
        if ($routerInfoResult) {
            while ($row = db_fetch_array($routerInfoResult)) {
                    $result[] = "Fornecedor: " . $row['suplier'] . " Model: " . $row['model'];
            }
        }
        
        $upsInfoQuery = "SELECT model, suplier, serial_number FROM SINCRO_UPS WHERE id = '$upsId'";
        $upsInfoResult = db_query($upsInfoQuery);
        if ($upsInfoResult) {
            while ($row = db_fetch_array($upsInfoResult)) {
                    $result[] = "Fornecedor: " . $row['suplier'] . " Model: " . $row['model'];
            }
        }
        
        return $result;
    }
    
    static function getCabinetId($serialNumber) {
        $cabinIdQuery = "SELECT id FROM SINCRO_Cabinet WHERE serial_number = '$serialNumber'";
        $cabinIdresult = db_query($cabinIdQuery);
        if ($cabinIdresult) {
            $row = db_fetch_array($cabinIdresult);
            return $row['id']; 
        } else {
            // Handle the case where the query fails
            error_log("Error fetching cabinId from SINCRO_Cabinet table");
            return false; // Return false if unable to fetch isactive
        }
    }
    
    static function getCinemometerId($cabinId) {
        $cinemometerIdQuery = "SELECT idCinemometer FROM SINCRO_Cabinet_has_Cinemometer WHERE idCabin = '$cabinId'";
        $cinemometerIdResult = db_query($cinemometerIdQuery);
        if ($cinemometerIdResult) {
            $row = db_fetch_array($cinemometerIdResult);
            return $row['idCinemometer']; 
        } else {
            // Handle the case where the query fails
            error_log("Error fetching cinemometerId from SINCRO_Cabinet_has_Cinemometer table");
            return false; // Return false if unable to fetch isactive
        }
    }
    
    static function getRouterId($cabinId) {
        $routerIdQuery = "SELECT idRouter FROM SINCRO_Cabinet_has_Router WHERE idCabin = '$cabinId'";
        $routerIdResult = db_query($routerIdQuery);
        if ($routerIdResult) {
            $row = db_fetch_array($routerIdResult);
            return $row['idRouter']; 
        } else {
            // Handle the case where the query fails
            error_log("Error fetching routerId from SINCRO_Cabinet_has_Router table");
            return false; // Return false if unable to fetch isactive
        }
    }
    
    static function getUpsId($cabinId) {
        $upsIdQuery = "SELECT id_ups FROM SINCRO_Cabinet WHERE id = '$cabinId'";
        $upsIdresult = db_query($upsIdQuery);
        if ($upsIdresult) {
            $row = db_fetch_array($upsIdresult);
            return $row['id_ups']; 
        } else {
            // Handle the case where the query fails
            error_log("Error fetching upsId from SINCRO_Cabinet table");
            return false; // Return false if unable to fetch isactive
        }
    }
    
    static function getCabinInfo($cabinId) {
        $cabinInfoQuery = "SELECT model, suplier FROM SINCRO_Cabinet WHERE id = '$cabinId'";
        $cabinInfoResult = db_query($cabinInfoQuery);
        if ($cabinInfoResult) {
            while ($row = db_fetch_array($cabinInfoResult)) {
                    return "Fornecedor: " . $row['suplier'] . " Model: " . $row['model'];
            }
        }
    }
    
    static function getCinemometerInfo($cinemometerId) {
        $cinemometerInfoQuery = "SELECT model, suplier, serial_number FROM SINCRO_Cinemometer WHERE id = '$cinemometerId'";
        $cinemometerInfoResult = db_query($cinemometerInfoQuery);
        if ($cinemometerInfoResult) {
            while ($row = db_fetch_array($cinemometerInfoResult)) {
                    return "Fornecedor: " . $row['suplier'] . " Model: " . $row['model'];
            }
        }
    }
    
    static function getRouterInfo($routerId) {
        $routerInfoQuery = "SELECT model, suplier, serial_number FROM SINCRO_Router WHERE id = '$routerId'";
        $routerInfoResult = db_query($routerInfoQuery);
        if ($routerInfoResult) {
            while ($row = db_fetch_array($routerInfoResult)) {
                    return "Fornecedor: " . $row['suplier'] . " Model: " . $row['model'];
            }
        }
    }
    
    static function getUpsInfo($upsId) {
        $upsInfoQuery = "SELECT model, suplier, serial_number FROM SINCRO_UPS WHERE id = '$upsId'";
        $upsInfoResult = db_query($upsInfoQuery);
        if ($upsInfoResult) {
            while ($row = db_fetch_array($upsInfoResult)) {
                    return "Fornecedor: " . $row['suplier'] . " Model: " . $row['model'];
            }
        }
    }
    
    static function getDistrict($cabinId) {
        $cabinDistrictQuery = "SELECT district FROM SINCRO_Cabinet WHERE id = '$cabinId'";
        $cabinDistrictResult = db_query($cabinDistrictQuery);
        if ($cabinDistrictResult) {
            $row = db_fetch_array($cabinDistrictResult);
            return $row['district']; 
        }
    }
    
    static function getAddress($cabinId) {
        $cabinAddressQuery = "SELECT address FROM SINCRO_Cabinet WHERE id = '$cabinId'";
        $cabinAddressResult = db_query($cabinAddressQuery);
        if ($cabinAddressResult) {
            $row = db_fetch_array($cabinAddressResult);
            return $row['address']; 
        }
    }
    
    function addColumnsToTable() {
        $columns = array(
            'cabinet_id' => "INT NOT NULL",
            'cinemometer_id' => "INT DEFAULT NULL", 
            'ups_id' => "INT DEFAULT NULL", 
            'router_id' => "INT DEFAULT NULL", 
            'other_value' => "VARCHAR(64) DEFAULT NULL"
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
    
    function deleteLinesFromTable() { //APAGAR TAMBEM AS LINHAS DA TABELA CDATA ATRAVES DO ticket_id
        $ticketIdsQuery = "SELECT ticket_id FROM ost_ticket WHERE cabinet_id != 0";
        $ticketIdsResult = db_query($ticketIdsQuery);

        if ($ticketIdsResult) {
            while ($row = db_fetch_array($ticketIdsResult)) {
                $ticketId = $row['ticket_id'];
                $deleteCdataQuery = "DELETE FROM ost_ticket__cdata WHERE ticket_id = $ticketId";
                $deleteCdataResult = db_query($deleteCdataQuery);

                if (!$deleteCdataResult) {
                    error_log("Error deleting related data from ost_ticket__cdata for ticket ID $ticketId: " . db_error());
                }
            }

            // Step 3: Execute the original deletion query from ost_ticket
            $deleteTicketsQuery = "DELETE FROM ost_ticket WHERE cabinet_id != 0";
            $deleteTicketsResult = db_query($deleteTicketsQuery);

            if ($deleteTicketsResult) {
                error_log("Tickets deleted successfully where cabinet_id was not 0.");
            } else {
                error_log("Error deleting tickets where cabinet_id was not 0: " . db_error());
                // Optionally, handle error or log it
            }
        } else {
            error_log("Error fetching ticket IDs from ost_ticket: " . db_error());
            // Optionally, handle error or log it
        }
    }  

    function deleteColumnsFromTable() {
        $columns = array(
            'cabinet_id',
            'cinemometer_id',
            'ups_id',
            'router_id',
            'other_value'
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
    
    function doesColumnExist() {
        // Define your SQL query to check if the column exists in the specified table
        $query = "SHOW COLUMNS FROM ost_ticket LIKE 'cabinet_id'";

        // Execute the query
        $result = db_query($query);

        // Check if the query was successful
        if ($result) {
            // Check if the column exists
            $rowCount = db_num_rows($result);
            return ($rowCount > 0); // Return true if the column exists, false otherwise
        } else {
            // Handle the case where the query fails
            error_log("Error checking column existence in table ost_ticket");
            return false; // Return false if unable to check column existence
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
            }
        }

        if ($cdataBackupExists) {
            // Copy data from ost_ticket__cdata_backup to ost_ticket__cdata
            $copyCdataQuery = "INSERT INTO ost_ticket__cdata SELECT * FROM ost_ticket__cdata_backup";
            $result2 = db_query($copyCdataQuery);
            if (!$result2) {
                error_log("Error copying data from ost_ticket__cdata_backup to ost_ticket__cdata: " . db_error());
            }
        }
        
        // Delete backup tables if they exist
        if ($ticketBackupExists) {
            $deleteTicketBackupQuery = "DROP TABLE ost_ticket_backup";
            $result3 = db_query($deleteTicketBackupQuery);
            if (!$result3) {
                error_log("Error deleting backup table ost_ticket_backup: " . db_error());
            }
        }

        if ($cdataBackupExists) {
            $deleteCdataBackupQuery = "DROP TABLE ost_ticket__cdata_backup";
            $result4 = db_query($deleteCdataBackupQuery);
            if (!$result4) {
                error_log("Error deleting backup table ost_ticket__cdata_backup: " . db_error());
            }
        }
        
        // Log success
        if ($ticketBackupExists || $cdataBackupExists) {
            error_log("Data copied from backup tables to original tables successfully.");
        } else {
            error_log("Backup tables do not exist.");
        }
    }

    
    static function createBackupTables() { 
        // Create ost_ticket_backup table and copy data
        $createTicketTableQuery = "CREATE TABLE IF NOT EXISTS ost_ticket_backup SELECT * FROM ost_ticket WHERE cabinet_id != 0";
        $result1 = db_query($createTicketTableQuery);
        if (!$result1) {
            error_log("Error creating table ost_ticket_backup: " . db_error());
        }

        // Create ost_ticket__cdata_backup table and copy data
        $createCdataTableQuery = "CREATE TABLE IF NOT EXISTS ost_ticket__cdata_backup SELECT * FROM ost_ticket__cdata WHERE ticket_id IN (SELECT ticket_id FROM ost_ticket WHERE cabinet_id != 0)";
        $result2 = db_query($createCdataTableQuery);
        if (!$result2) {
            error_log("Error creating table ost_ticket__cdata_backup: " . db_error());
        }

        // Log success
        if ($result1 && $result2) {
            error_log("Tables ost_ticket_backup and ost_ticket__cdata_backup created and data copied successfully.");
        } else {
            error_log("Tables ost_ticket_backup and ost_ticket__cdata_backup already exist.");
        }
    }
}
$forms_plugin = new FormsPlugin();
$forms_plugin->bootstrap();