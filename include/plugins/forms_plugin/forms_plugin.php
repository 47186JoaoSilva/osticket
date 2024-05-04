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
        Signal::connect('model.updated', array($this, 'addOrDeleteColumnsFromTable'));
        Signal::connect('model.deleted', array($this, 'addOrDeleteColumnsFromTable'));
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

        $deleteQuery1 = "DELETE FROM ost_form_field WHERE id IN (" . implode(',', $formFieldsId) . ")";
        $deleteResult1 = db_query($deleteQuery1);

        $deleteQuery2 = "DELETE FROM ost_ticket__cdata WHERE CabinModel IS NOT NULL";
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
        //Add a break field for Cinemometer informations
        $this->addBreak($label, $breakName);
        //Add a choice field for the Cinemometer Model
        $this->addFieldModel($label, $fieldNameM, $tableName, $name, $flags);
        //Add a choice field for the Cinemometer Serial Number
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
        //Add a break field for Cinemometer informations
        $this->addBreak($label, $breakName);
        //Add a choice field for the Cinemometer Model
        $this->addFieldModel($label, $fieldNameM, $tableName, $name, $flags);
        //Add a choice field for the Cinemometer Serial Number
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
        //Add a break field for Cinemometer informations
        $this->addBreak($label, $breakName);
        //Add a choice field for the Cinemometer Model
        $this->addFieldModel($label, $fieldNameM, $tableName, $name, $flags);
        //Add a choice field for the Cinemometer Serial Number
        $this->addFieldSerial($label, $fieldNameSN, $tableName, $name, $flags);
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
            $key = 1;
            foreach ($models as $model) {
                if(sizeof($models) != $key){
                    $conf .= "{$key}:{$model}" . '\r\n';
                    $key++;
                } else{
                    $conf .= "{$key}:{$model}";
                }
            }
            $conf .= '","default":"","prompt":"Select","multiselect":false}';
            $confSlash = addslashes($conf);
            $query = "INSERT INTO `ost_form_field` 
            (`form_id`, `flags`, `type`, `label`, `name`, `configuration`, `sort`, `hint`, `created`, `updated`) 
            values ('2','{$flags}','choices','Modelo ({$label})' ,'{$fieldName}','{$confSlash}','{$sort}', NULL, CURDATE(), CURDATE())";
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
            values ('2','{$flags}','choices','Número de série ({$label})','{$fieldName}','{$confSlash}','{$sort}', NULL, CURDATE(), CURDATE())";
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
            values ('2','30465','break','{$label}','{$name}', NULL,'{$sort}', NULL, CURDATE(), CURDATE())";
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