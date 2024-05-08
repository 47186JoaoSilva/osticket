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
    
    static function getAddresses($district) {
        if(!$district) {
            $query = "SELECT DISTINCT address FROM sincro_cabinet";
            $result = db_query($query);

            if ($result) {
                $addresses = [];
                while ($row = db_fetch_array($result)) {
                    $addresses[] = $row['address'];
                }
                return $addresses;
            } else {
                // Handle the case where the query fails
                error_log("Error fetching isactive from ost_plugin table");
                return false; // Return false if unable to fetch isactive
            }
        } else {
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
                    $result[] = "Fornecedor:" . $row['suplier'] . " Model: " . $row['model'] . " Nº Série: " . $row['serial_number'];
            }
        }
        
        $routerInfoQuery = "SELECT model, suplier, serial_number FROM SINCRO_Router WHERE id = '$routerId'";
        $routerInfoResult = db_query($routerInfoQuery);
        if ($routerInfoResult) {
            while ($row = db_fetch_array($routerInfoResult)) {
                    $result[] = "Fornecedor:" . $row['suplier'] . " Model: " . $row['model'] . " Nº Série: " . $row['serial_number'];
            }
        }
        
        $upsInfoQuery = "SELECT model, suplier, serial_number FROM SINCRO_UPS WHERE id = '$upsId'";
        $upsInfoResult = db_query($upsInfoQuery);
        if ($upsInfoResult) {
            while ($row = db_fetch_array($upsInfoResult)) {
                    $result[] = "Fornecedor:" . $row['suplier'] . " Model: " . $row['model'] . " Nº Série: " . $row['serial_number'];
            }
        }
        
        return $result;
    }
    
    function addFields() {
        if($this->isPluginActive()) {
            $this->addCabinInfo();
            $this->addBoxInfo();
            $this->addCinemometerInfo();
            $this->addUPSInfo();
            $this->addRouterInfo();
        }
    }
    
    function addOrDeleteColumnsFromTable() {
        if($this->isPluginActive()) {
            $this->copyBackupIfExists();
        }
        else {
            //TODO(): Add condition to see if the checkbox is checked or not
            //$this->createBackupTables();
            $this->deleteLinesFromTable();
            $this->deleteColumnsFromTable();
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
    
    function addCabinInfo(){
        //Define the constant values
        $label = "Cabine";
        $breakName = "cabinBreak";
        $tableName = "SINCRO_Cabinet";
        $flags = "30465";
        $name = "cabin";
        $fieldNameM = "cabinModel";
        $fieldNameSN = "cabinSN";
        //Add a break field for Cabin informations
        $this->addBreak($label, $breakName);
        //Add a choice field for the Cabin Model
        $this->addFieldModel($label, $fieldNameM, $tableName, $name, $flags);
        //Add a choice field for the Cabin Serial Number
        $this->addFieldSerial($label, $fieldNameSN, $tableName, $name, $flags);
    }
    
    function addBoxInfo(){
        //Define the constant values
        $label = "Caixa";
        $breakName = "boxBreak";
        $tableName = "SINCRO_Box";
        $flags = "30465";
        $name = "box";
        $fieldNameM = "boxModel";
        $fieldNameSN = "boxSN";
        //Add a break field for Box informations
        $this->addBreak($label, $breakName);
        //Add a choice field for the Box Model
        $this->addFieldModel($label, $fieldNameM, $tableName, $name, $flags);
        //Add a choice field for the Box Serial Number
        $this->addFieldSerial($label, $fieldNameSN, $tableName, $name, $flags);
    }
    
    function addCinemometerInfo(){
        //Define the constant values
        $label = "Cinemómetro";
        $breakName = "cinemometerBreak";
        $tableName = "SINCRO_Cinemometer";
        $flags = "30465";
        $name = "cinemometer";
        $fieldNameM = "cinemometerModel";
        $fieldNameSN = "cinemometerSN";
        //Add a break field for Cinemometer informations
        $this->addBreak($label, $breakName);
        //Add a choice field for the Cinemometer Model
        $this->addFieldModel($label, $fieldNameM, $tableName, $name, $flags);
        //Add a choice field for the Cinemometer Serial Number
        $this->addFieldSerial($label, $fieldNameSN, $tableName, $name, $flags);
    }
    
    function addUPSInfo(){
        //Define the constant values
        $label = "UPS";
        $breakName = "upsBreak";
        $tableName = "SINCRO_UPS";
        $flags = "30465";
        $name = "ups";
        $fieldNameM = "upsModel";
        $fieldNameSN = "upsSN";
        //Add a break field for UPS informations
        $this->addBreak($label, $breakName);
        //Add a choice field for the UPS Model
        $this->addFieldModel($label, $fieldNameM, $tableName, $name, $flags);
        //Add a choice field for the UPS Serial Number
        $this->addFieldSerial($label, $fieldNameSN, $tableName, $name, $flags);
    }
    
    function addRouterInfo(){
        //Define the constant values
        $label = "Router";
        $breakName = "routerBreak";
        $tableName = "SINCRO_Router";
        $flags = "30465";
        $name = "router";
        $fieldNameM = "routerModel";
        $fieldNameSN = "routerSN";
        //Add a break field for Router informations
        $this->addBreak($label, $breakName);
        //Add a choice field for the Router Model
        $this->addFieldModel($label, $fieldNameM, $tableName, $name, $flags);
        //Add a choice field for the Router Serial Number
        $this->addFieldSerial($label, $fieldNameSN, $tableName, $name, $flags);
        //Add a choice field for the Router IP address
        $this->addRouterIP();
    }
    
    function addFieldModel($label, $fieldName, $tableName, $name, $flags){
        if($this->hasField("{$fieldName}")){
            return;
        }
        $sort = $this->getSort();
        if($sort == null){
            return;
        }
        $queryConf = "SELECT DISTINCT model FROM `{$tableName}`";
        $confAux = db_query($queryConf);
        if(!$confAux){
            error_log("Error trying to get the {$name} models from the table {$tableName}") . db_error();
        } else{
            $models = array();
            while ($row = db_fetch_array($confAux)) {
                $models[] = $row['model'];
            }
            $conf = '{"choices":"';
            $counter = 1;
            foreach ($models as $model) {
                if(sizeof($models) != $counter){
                    $conf .= "{$model}:{$model}" . '\r\n';
                    $counter++;
                } else{
                    $conf .= "{$model}:{$model}";
                }
            }
            $conf .= '","default":"","prompt":"Select","multiselect":false}';
            $confSlash = addslashes($conf);
            $query = "INSERT INTO `ost_form_field` 
            (`form_id`, `flags`, `type`, `label`, `name`, `configuration`, `sort`, `hint`, `created`, `updated`) 
            values ('2','{$flags}','choices','Modelo ({$label})' ,'{$fieldName}','{$confSlash}','{$sort}', NULL, NOW(), NOW())";
            $result = db_query($query);

            if(!$result){
                error_log("Coudn't insert the values into the table ost_form_field") . db_error();
            } else{
                //Is there a success log?
            }
        }
    }
    
    function addFieldSerial($label, $fieldName, $tableName, $name, $flags) {

        if($this->hasField("{$fieldName}")){
            return;
        }
        $sort = $this->getSort();
        if($sort == null){
            return;
        }
        $queryConf = "SELECT serial_number FROM `{$tableName}`";
        $confAux = db_query($queryConf);
        if(!$confAux){
            error_log("Error trying to get the {$name} serial number values from the table {$tableName}") . db_error();
        } else{
            $serialNumbers = array();
            while ($row = db_fetch_array($confAux)) {
                $serialNumbers[] = $row['serial_number'];
            }
            $conf = '{"choices":"';
            $counter = 1;
            foreach ($serialNumbers as $serialNumber) {
                if(sizeof($serialNumbers) != $counter){
                    $conf .= "{$serialNumber}:{$serialNumber}" . '\r\n';
                    $counter++;
                } else{
                    $conf .= "{$serialNumber}:{$serialNumber}";
                }
            }
            $conf .= '","default":"","prompt":"Select","multiselect":false}';
            $confSlash = addslashes($conf);
            $query = "INSERT INTO `ost_form_field` 
            (`form_id`, `flags`, `type`, `label`, `name`, `configuration`, `sort`, `hint`, `created`, `updated`) 
            values ('2','{$flags}','choices','Número de série ({$label})','{$fieldName}','{$confSlash}','{$sort}', NULL, NOW(), NOW())";
            $result = db_query($query);

            if(!$result){
                error_log("Coudn't insert the values into the table ost_form_field") . db_error();
            } else{
                //Is there a success log?
            }
        }
    }
    
    function addRouterIP() {

        if($this->hasField("routerIP")){
            return;
        }
        $sort = $this->getSort();
        if($sort == null){
            return;
        }
        $queryConf = "SELECT ip_address FROM `SINCRO_Router`";
        $confAux = db_query($queryConf);
        if(!$confAux){
            error_log("Error trying to get the router ip address values from the table SINCRO_Router") . db_error();
        } else{
            $ipAddresses = array();
            while ($row = db_fetch_array($confAux)) {
                $ipAddresses[] = $row['ip_address'];
            }
            $conf = '{"choices":"';
            $counter = 1;
            foreach ($ipAddresses as $ipAdress) {
                if(sizeof($ipAddresses) != $counter){
                    $conf .= "{$ipAdress}:{$ipAdress}" . '\r\n';
                    $counter++;
                } else{
                    $conf .= "{$ipAdress}:{$ipAdress}";
                }
            }
            $conf .= '","default":"","prompt":"Select","multiselect":false}';
            $confSlash = addslashes($conf);
            $query = "INSERT INTO `ost_form_field` 
            (`form_id`, `flags`, `type`, `label`, `name`, `configuration`, `sort`, `hint`, `created`, `updated`) 
            values ('2','30465','choices','Endereço IP do router','routerIP','{$confSlash}','{$sort}', NULL, NOW(), NOW()I want)";
            $result = db_query($query);

            if(!$result){
                error_log("Coudn't insert the values into the table ost_form_field") . db_error();
            } else{
                //Is there a success log?
            }
        }
    }
    
    function addBreak($label,$name){
        if($this->hasField("{$name}")){
            return;
        }
        $sort = $this->getSort();
        if($sort == null){
            return;
        }
        $query = "INSERT INTO `ost_form_field` 
            (`form_id`, `flags`, `type`, `label`, `name`, `configuration`, `sort`, `hint`, `created`, `updated`) 
            values ('2','30465','break','{$label}','{$name}', NULL,'{$sort}', NULL, NOW(), NOW())";
        $result = db_query($query);

        if(!$result){
            error_log("Coudn't insert the values into the table ost_form_field") . db_error();
        } else{
            //Is there a success log?
        }
    }
    
    function getSort(){
        $querySort = "SELECT MAX(sort) FROM `ost_form_field` WHERE form_id = 2";
        $result = db_query($querySort);
        $row = db_fetch_row($result);
        $maxSort = $row[0];

        if(!$maxSort){
            error_log("Error trying to get the sort number of the form") . db_error();
            return null;
        } else{
            return $maxSort + 1;
        }
    }
    
    function hasField($fieldName){
        //A pesquisa não deve ser feita através do nome, porque não é único, pode causar problemas com plugins futuros
        $query = "SELECT name FROM `ost_form_field` WHERE name = '{$fieldName}'";
        $result= db_query($query);
        if (db_num_rows($result) != 0){
            return true;
        } else {
            return false;
        }
    }
}
$forms_plugin = new FormsPlugin();
$forms_plugin->bootstrap();