<?php
// Include necessary osTicket files
require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.signal.php');
require_once(INCLUDE_DIR . 'class.app.php');
require_once(INCLUDE_DIR . 'class.dispatcher.php');

// Define your plugin class
class FormsPlugin extends Plugin {
    function bootstrap() {
        //Signal::connect('model.updated', array($this, 'addFields'));
        //Signal::connect('model.updated', array($this, 'addOrDeleteColumnsFromTable'));
        //Signal::connect('model.deleted', array($this, 'addOrDeleteColumnsFromTable'));
        Signal::connect('model.updated', array($this, 'addOrDeleteColumnsFromTable'));
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
    
    function addOrDeleteColumnsFromTable() {
        if($this->isPluginActive()) {
            //$this->copyBackupIfExists();
            $this->addColumnsToTable();
        }
        else {
            //TODO(): Add condition to see if the checkbox is checked or not
            //$this->createBackupTables();
            //$this->deleteLinesFromTable();
            //$this->deleteColumnsFromTable();
        }
    }
    
    function addColumnsToTable() {
        $columns = array(
            'cabinet_id' => "INT NOT NULL",
            'cinemometer_id' => "INT DEFAULT NULL", 
            'ups_id' => "INT DEFAULT NULL", 
            'router_id' => "INT DEFAULT NULL", 
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
            'cabinModel',
            'CabinSN',
            'boxModel',
            'boxSN',
            'cinemometerModel',
            'cinemometerSN',
            'upsModel',
            'upsSN',
            'routerModel',
            'routerSN',
        );

        foreach ($columns as $column) {
            // Define the SQL query to drop the column
            $query = "ALTER TABLE `ost_ticket__cdata` DROP COLUMN `$column`";

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
        $query = "SELECT id FROM ost_form_field WHERE flags = 30465";
        $result = db_query($query);
        if (!$result) {
            error_log("Error retrieving ticket_id where textbox_name is not empty: " . db_error());
            return; 
        }
        $formFieldsId = [];
        while ($row = db_fetch_array($result)) {
            $formFieldsId[] = $row['id'];
        }
        
        $firstFieldId = $formFieldsId[1];
        $selectQuery = "SELECT entry_id FROM ost_form_entry_values WHERE field_id = $firstFieldId";
        $selectResult = db_query($selectQuery);
        $entryIdsToDelete = [];
        while ($row = db_fetch_array($selectResult)) {
            $entryIdsToDelete[] = $row['entry_id'];
        }
        
        $dateQuery = "SELECT created FROM ost_form_field WHERE name = 'cabinBreak'";
        $dateResult = db_query($dateQuery);
        $row = db_fetch_array($dateResult);
        $createdValue = $row['created'];
        if($createdValue) {
            $deleteQuery = "DELETE FROM ost_ticket WHERE created > '$createdValue'";
            $deleteResult = db_query($deleteQuery);
        }
        
        $deleteQuery1 = "DELETE FROM ost_form_field WHERE id IN (" . implode(',', $formFieldsId) . ")";
        $deleteResult1 = db_query($deleteQuery1);

        $deleteQuery2 = "DELETE FROM ost_ticket__cdata WHERE CabinModel IS NOT NULL";
        $deleteResult2 = db_query($deleteQuery2);
        
        $deleteQuery3 = "DELETE FROM ost_form_entry_values WHERE entry_id IN (" . implode(',', $entryIdsToDelete) . ")";
        $deleteResult3 = db_query($deleteQuery3);

        if ($deleteResult && $deleteResult1 && $deleteResult2 && $deleteResult3) {
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
}
$forms_plugin = new FormsPlugin();
$forms_plugin->bootstrap();