<?php
require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.signal.php');
require_once(INCLUDE_DIR . 'class.app.php');
require_once(INCLUDE_DIR . 'class.dispatcher.php');

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
        if($this->isPluginActive()) {
            if(!$this->doesColumnExist()) {
                $this->replaceTicketOpenFile();
                $this->replaceTicketViewFile();
                $this->applyPatchToClassTicketFile();
                $this->replaceOpenFile();
                $this->replacePluginFile();
            }
        }
        else {
            if($this->doesColumnExist()) {
                $this->restoreTicketOpenFile();
                $this->restoreTicketViewFile();
                $this->restoreClassTicketFile();
                $this->restoreOpenFile();
                $this->restorePluginFile();
            }
        }
    }
    
    function moveOrRemoveFiles() {
        if($this->isPluginActive()) {
            if(!$this->doesColumnExist()) {
                $this->moveToNewDirectory();
            }
        }
        else {
            if($this->doesColumnExist()) {
                $this->moveToOriginalDirectory();
            }
        }
    }
        
    function addOrDeleteColumnsFromTable() {
        if($this->isPluginActive()) {
            if(!$this->doesColumnExist()) {
                $this->addColumnsToTable();
                $this->copyBackupIfExists();
            }
        }
        else {
            if($this->doesColumnExist()) {
                $this->deleteLinesFromTable();
                $this->deleteColumnsFromTable();
            }
        }
    }
    
    //REPLACE AND PATCH______________________________________________________________________________________
    function replaceTicketOpenFile() {
        $this->replaceFile(INCLUDE_DIR . 'staff/ticket-open.inc.php', 'ticket-open-modified.inc.php');
    }
    
    function replaceTicketViewFile() {
        $this->replaceFile(INCLUDE_DIR . 'staff/ticket-view.inc.php', 'ticket-view-modified.inc.php');
    }
    
    function applyPatchToClassTicketFile() {
        $this->insertCodeIntoFile(
            INCLUDE_DIR . 'class.ticket.php', 
            $this->classTicketPatch1, 
            'if ($this->hasFlag(self::FLAG_SEPARATE_THREADS))', 
            '    function isMerged() {'
        );
        $this->insertCodeIntoFile(
            INCLUDE_DIR . 'class.ticket.php', 
            $this->classTicketPatch2, 
            'switch ($fid) {', 
            '        case \'priority\':'
        );
        $this->insertCodeIntoFile(
            INCLUDE_DIR . 'class.ticket.php', 
            $this->classTicketPatch3, 
            '$fields[\'duedate\']  = array(\'type\'=>\'date\', \'required\'=>0, \'error\'=>__(\'Invalid date format - must be MM/DD/YY\'));', 
            '            case \'api\':'
        );
        $this->insertCodeIntoFile(
            INCLUDE_DIR . 'class.ticket.php', 
            $this->classTicketPatch4, 
            'Misc::dbtime($vars[\'duedate\']));', 
            '        if (!$ticket->save())'
        );
    }    
    
    function replaceOpenFile() {
        $this->replaceFile(INCLUDE_DIR . 'client/open.inc.php', 'open-modified.inc.php');
    }
    
    function replacePluginFile() {
        $this->replaceFile(INCLUDE_DIR . 'staff/plugins.inc.php', 'plugins-modified.inc.php');
    }
    //____________________________________________________________________________________________________________

    //RESTORE_____________________________________________________________________________________________________
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
    //____________________________________________________________________________________________________________
    
    //MOVE FILES_____________________________________________________________________________________________________
    function moveToNewDirectory() {
        $this->moveFileToDirectory(INCLUDE_DIR . 'plugins/forms_plugin/endpoints/get_addresses.php', SCP_DIR . 'get_addresses.php');
        $this->moveFileToDirectory(INCLUDE_DIR . 'plugins/forms_plugin/endpoints/get_cabinets.php', SCP_DIR . 'get_cabinets.php');
        $this->moveFileToDirectory(INCLUDE_DIR . 'plugins/forms_plugin/endpoints/get_checkbox_values.php', SCP_DIR . 'get_checkbox_values.php');
        $this->moveFileToDirectory(INCLUDE_DIR . 'plugins/forms_plugin/endpoints/erase_data.php', SCP_DIR . 'erase_data.php');
    }
    
    function moveToOriginalDirectory() {
        $this->moveFileToDirectory(SCP_DIR . 'get_addresses.php', INCLUDE_DIR . 'plugins/forms_plugin/endpoints/get_addresses.php');
        $this->moveFileToDirectory(SCP_DIR . 'get_cabinets.php', INCLUDE_DIR . 'plugins/forms_plugin/endpoints/get_cabinets.php');
        $this->moveFileToDirectory(SCP_DIR . 'get_checkbox_values.php', INCLUDE_DIR . 'plugins/forms_plugin/endpoints/get_checkbox_values.php');
        $this->moveFileToDirectory(SCP_DIR . 'erase_data.php', INCLUDE_DIR . 'plugins/forms_plugin/endpoints/erase_data.php');
    }
    //_____________________________________________________________________________________________________________________
    
    //REPLACE,RESTORE,MOVE AND PATCH FUNCTIONS______________________________________________________________________________
    function replaceFile($file_path, $modified_file_name) {
        $modified_file_path = __DIR__ . '\modified_files' . '/' . $modified_file_name;
        
        if (file_exists($file_path) && file_exists($modified_file_path)) {
            $modified_content = file_get_contents($modified_file_path);
            
            //Copiar o ficheiro original para o backup
            
            file_put_contents($file_path, $modified_content);
        } else {
            if (!file_exists($file_path)) {
                error_log("$file_path does not exist!");
            }
            if (!file_exists($modified_file_path)) {
                error_log("$modified_file_path does not exist!");
            }
        }
    }
    
    function restoreFile($file_path,$backup_path) {
        $backup_file_path = __DIR__ . '\backup_files' . '/' . basename($backup_path);
        
        if (file_exists($backup_file_path)) {
            copy($backup_file_path, $file_path);
            // Delete the backup file after restoring
            //unlink($backup_file_path);
        } else {
            error_log("Backup file for $file_path does not exist!");
        }
    }
    
     function insertCodeIntoFile($filePath, $newCode, $startPoint, $endPoint) {
        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            throw new Exception("Failed to read file at $filePath");
        }

        $startPos = strpos($fileContent, $startPoint);
        if ($startPos === false) {
            throw new Exception("Start point '$startPoint' not found in file $filePath");
        }

        $endPos = strpos($fileContent, $endPoint, $startPos);
        if ($endPos === false) {
            throw new Exception("End point '$endPoint' not found in file $filePath");
        }

        $startPointEnd = $startPos + strlen($startPoint);
        $endPointStart = $endPos;

        $updatedContent = substr($fileContent, 0, $startPointEnd) 
            . "\n" . $newCode . "\n"                             
            . substr($fileContent, $endPointStart);              

        if (file_put_contents($filePath, $updatedContent) === false) {
            throw new Exception("Failed to write updated content to file $filePath");
        }

        return true;
    }
    
    function moveFileToDirectory($source, $destination) {
        if (!file_exists($source)) {
            return "Source file does not exist.";
        }

        if (rename($source, $destination)) {
            return "File moved successfully.";
        } else {
            return "Failed to move the file.";
        }
    }
    //_____________________________________________________________________________________________________________________
    
    //GET INFO OF ANSR FUNCTIONS___________________________________________________________________________________________
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
                error_log("");
                return false; 
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
                error_log("");
                return false; 
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
            error_log("");
            return false; 
        }
    }
    
    static function getPlaces($address) {
        if($address){
            $query = "SELECT pk, c_d FROM sincro_cabinet WHERE address = '$address'";
            $result = db_query($query);

            if ($result) {
                $places = [];
                while ($row = db_fetch_array($result)) {
                    $places[] = $row['pk'] . " " . $row['c_d'];
                }
                return $places;
            } else {
                error_log("");
                return false; 
            }
        } 
    }
    
    static function getEquipments($pk,$c_d) {
        $result = [];
        
        $cabinIdQuery = "SELECT id, model, suplier FROM SINCRO_Cabinet WHERE pk = '$pk' AND c_d = '$c_d'";
        $cabinIdresult = db_query($cabinIdQuery);
        if ($cabinIdresult) {
            while ($row = db_fetch_array($cabinIdresult)) {
                $result[] = $row['suplier'] . " " . $row['model'];
                $cabinId = $row['id'];
            }  
        }
        
        $cinemometerId = self::getCinemometerId($cabinId); 
        $routerId = self::getRouterId($cabinId);
        $upsId = self::getUpsId($cabinId);
        
        $result[] = self::getCinemometerInfo($cinemometerId);
        $result[] = self::getRouterInfo($routerId);
        $result[] = self::getUpsInfo($upsId);
        
        return $result;
    }
    
    static function getCabinetId($pk,$c_d) {
        $cabinIdQuery = "SELECT id FROM SINCRO_Cabinet WHERE pk = '$pk' AND c_d = '$c_d'";
        $cabinIdresult = db_query($cabinIdQuery);
        if ($cabinIdresult) {
            $row = db_fetch_array($cabinIdresult);
            return $row['id']; 
        } else {
            error_log("Error fetching cabinId from SINCRO_Cabinet table");
            return false; 
        }
    }
    
    static function getCinemometerId($cabinId) {
        $cinemometerIdQuery = "SELECT idCinemometer FROM SINCRO_Cabinet_has_Cinemometer WHERE idCabin = '$cabinId'";
        $cinemometerIdResult = db_query($cinemometerIdQuery);
        if ($cinemometerIdResult) {
            $row = db_fetch_array($cinemometerIdResult);
            return $row['idCinemometer']; 
        } else {
            error_log("Error fetching cinemometerId from SINCRO_Cabinet_has_Cinemometer table");
            return false;
        }
    }
    
    static function getRouterId($cabinId) {
        $routerIdQuery = "SELECT idRouter FROM SINCRO_Cabinet_has_Router WHERE idCabin = '$cabinId'";
        $routerIdResult = db_query($routerIdQuery);
        if ($routerIdResult) {
            $row = db_fetch_array($routerIdResult);
            return $row['idRouter']; 
        } else {
            error_log("Error fetching routerId from SINCRO_Cabinet_has_Router table");
            return false;
        }
    }
    
    static function getUpsId($cabinId) {
        $upsIdQuery = "SELECT id_ups FROM SINCRO_Cabinet WHERE id = '$cabinId'";
        $upsIdresult = db_query($upsIdQuery);
        if ($upsIdresult) {
            $row = db_fetch_array($upsIdresult);
            return $row['id_ups']; 
        } else {
            error_log("Error fetching upsId from SINCRO_Cabinet table");
            return false;
        }
    }
    
    static function getCabinInfo($cabinId) {
        $cabinInfoQuery = "SELECT model, suplier FROM SINCRO_Cabinet WHERE id = '$cabinId'";
        $cabinInfoResult = db_query($cabinInfoQuery);
        if ($cabinInfoResult) {
            while ($row = db_fetch_array($cabinInfoResult)) {
                    return $row['suplier'] . " " . $row['model'];
            }
        }
    }
    
    static function getCinemometerInfo($cinemometerId) {
        $cinemometerInfoQuery = "SELECT model, suplier, serial_number FROM SINCRO_Cinemometer WHERE id = '$cinemometerId'";
        $cinemometerInfoResult = db_query($cinemometerInfoQuery);
        if ($cinemometerInfoResult) {
            while ($row = db_fetch_array($cinemometerInfoResult)) {
                    return $row['suplier'] . " " . $row['model'];
            }
        }
    }
    
    static function getRouterInfo($routerId) {
        $routerInfoQuery = "SELECT model, suplier, serial_number FROM SINCRO_Router WHERE id = '$routerId'";
        $routerInfoResult = db_query($routerInfoQuery);
        if ($routerInfoResult) {
            while ($row = db_fetch_array($routerInfoResult)) {
                    return $row['suplier'] . " " . $row['model'];
            }
        }
    }
    
    static function getUpsInfo($upsId) {
        $upsInfoQuery = "SELECT model, suplier, serial_number FROM SINCRO_UPS WHERE id = '$upsId'";
        $upsInfoResult = db_query($upsInfoQuery);
        if ($upsInfoResult) {
            while ($row = db_fetch_array($upsInfoResult)) {
                    return $row['suplier'] . " " . $row['model'];
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
    //_____________________________________________________________________________________________________________________
    
    //AUXILIARY FUNCTIONS__________________________________________________________________________________________________
    function isPluginActive() {
        $query = "SELECT isactive FROM " . TABLE_PREFIX . "plugin WHERE name = 'Forms Plugin'"; 

        $result = db_query($query);

        if ($result) {
            $row = db_fetch_array($result);
            return $row['isactive'];
        } else {
            error_log("Error fetching isactive from " . TABLE_PREFIX . "plugin table");
            return false;
        }
    }
    
    function doesColumnExist() {
        $query = "SHOW COLUMNS FROM " . TABLE_PREFIX . "ticket LIKE 'cabinet_id'";

        $result = db_query($query);

        if ($result) {
            $rowCount = db_num_rows($result);
            return ($rowCount > 0); 
        } else {
            error_log("Error checking column existence in table " . TABLE_PREFIX . "ticket");
            return false;
        }
    }
    //_____________________________________________________________________________________________________________________
    
    //DATABASE AND BACKUP FUNCTIONS________________________________________________________________________________________
    function addColumnsToTable() {
        $columns = array(
            'cabinet_id' => "INT DEFAULT NULL",
            'cinemometer_id' => "INT DEFAULT NULL", 
            'ups_id' => "INT DEFAULT NULL", 
            'router_id' => "INT DEFAULT NULL", 
            'cabinet_is_broken' => "TEXT DEFAULT 'Não'",
            'cinemometer_is_broken' => "TEXT DEFAULT 'Não'",
            'ups_is_broken' => "TEXT DEFAULT 'Não'",
            'router_is_broken' => "TEXT DEFAULT 'Não'",
            'other_is_broken' => "TEXT DEFAULT 'Não'",
        );

        foreach ($columns as $column => $definition) {
            $query = "ALTER TABLE `" . TABLE_PREFIX . "ticket` ADD COLUMN `$column` $definition";

            if (db_query($query)) {
                error_log("Column '$column' added successfully.");
            } else {
                error_log("Error adding column '$column' to table.");
            }
        }
    }

    function deleteLinesFromTable() { 
        $ticketIdsQuery = "SELECT ticket_id FROM " . TABLE_PREFIX . "ticket WHERE cabinet_id != 0";
        $ticketIdsResult = db_query($ticketIdsQuery);

        if ($ticketIdsResult) {
            while ($row = db_fetch_array($ticketIdsResult)) {
                $ticketId = $row['ticket_id'];
                $deleteCdataQuery = "DELETE FROM " . TABLE_PREFIX . "ticket__cdata WHERE ticket_id = $ticketId";
                $deleteCdataResult = db_query($deleteCdataQuery);
                
                $deleteTicketsQuery = "DELETE FROM " . TABLE_PREFIX . "ticket WHERE ticket_id = $ticketId";
                $deleteTicketsResult = db_query($deleteTicketsQuery);
                
                $deleteFormEntryQuery = "DELETE fe, fev FROM " . TABLE_PREFIX . "form_entry fe INNER JOIN " . TABLE_PREFIX . "form_entry_values fev "
                        . "ON fe.id = fev.entry_id "
                        . "WHERE fe.object_type = 'T' AND fe.object_id = $ticketId";
                $deleteFormEntryResult = db_query($deleteFormEntryQuery); //DONE 
                
                $deleteThreadQuery = "DELETE FROM " . TABLE_PREFIX . "thread WHERE id = $ticketId";
                $deleteThreadResult = db_query($deleteThreadQuery); //DONE 
                
                $deleteThreadEntryQuery = "DELETE FROM " . TABLE_PREFIX . "thread_entry WHERE id = $ticketId";
                $deleteThreadEntryResult = db_query($deleteThreadEntryQuery); //DONE 
                
                $deleteThreadEventQuery = "DELETE FROM " . TABLE_PREFIX . "thread_event WHERE id = $ticketId OR thread_id = $ticketId";
                $deleteThreadEventResult = db_query($deleteThreadEventQuery);
                
                $deleteSearchQuery = "DELETE FROM " . TABLE_PREFIX . "_search WHERE (object_type = 'T' OR object_type = 'H') AND object_id = $ticketId";
                $deleteSearchResult = db_query($deleteSearchQuery); //DONE 
                
                if (!$deleteCdataResult && !$deleteTicketsResult && !$deleteFormEntryResult  && !$deleteThreadResult 
                        && !$deleteThreadEntryResult && !$deleteThreadEventResult && !$deleteSearchResult) {
                    error_log("Error deleting tickets");
                } 
            } 
        } else {
            error_log("Error fetching ticket IDs from " . TABLE_PREFIX . "ticket: " . db_error());
        }
    }  

    function deleteColumnsFromTable() {
        $columns = array(
            'cabinet_id',
            'cinemometer_id',
            'ups_id',
            'router_id',
            'cabinet_is_broken',
            'cinemometer_is_broken',
            'ups_is_broken',
            'router_is_broken',
            'other_is_broken',
        );

        foreach ($columns as $column) {
            $query = "ALTER TABLE `" . TABLE_PREFIX . "ticket` DROP COLUMN `$column`";

            if (db_query($query)) {
                error_log("Column '$column' deleted successfully.");
            } else {
                error_log("Error deleting column '$column' from table.");
            }
        }
    }
 
    function copyBackupIfExists() {
        $dbHost = DBHOST; 
        $dbUser = DBUSER; 
        $dbPass = DBPASS; 
        $dbName = DBNAME; 
        $mysqlPath = 'C:/xampp/mysql/bin/mysql.exe';
        $backupFile = INCLUDE_DIR . "plugins/forms_plugin/mysqldump/combined_backup.sql";

        $restoreCommand = "$mysqlPath -h $dbHost -u $dbUser -p$dbPass $dbName < \"$backupFile\"";
        system($restoreCommand, $result);

        if ($result == 0) {
            error_log("Backup restored successfully.");
        } else {
            error_log("Error occurred during the restoration process. Error code: $result");
        }
        
        if (unlink($backupFile)) {
            error_log("Backup file combined_backup.sql deleted successfully.");
        } else {
            error_log("Error deleting backup file combined_backup.sql.");
        }
    }

    
    static function createBackupTables() { 
        $dbHost = DBHOST; 
        $dbUser = DBUSER; 
        $dbPass = DBPASS; 
        $dbName = DBNAME; 
        $mysqlDumpPath = 'C:/xampp/mysql/bin/mysqldump.exe';
        $backupDir = INCLUDE_DIR . "plugins/forms_plugin/mysqldump/";
        
        if (!file_exists($backupDir)) {
            if (!mkdir($backupDir, 0777, true)) {
                error_log("Failed to create backup directory: " . $backupDir);
                return;
            }
        }

        $backupCommand = "$mysqlDumpPath -h $dbHost -u $dbUser -p$dbPass $dbName "
                . TABLE_PREFIX . "ticket__cdata " 
                . TABLE_PREFIX . "ticket "
                . TABLE_PREFIX . "form_entry "
                . TABLE_PREFIX . "form_entry_values "    
                . TABLE_PREFIX . "thread "    
                . TABLE_PREFIX . "thread_entry " 
                . TABLE_PREFIX . "thread_event " 
                . TABLE_PREFIX . "_search " 
                . "> \"" . $backupDir . "combined_backup.sql\"";
        system($backupCommand, $result);

        if ($result == 0) {
            error_log("Backup file created successfully.");
        } else {
            error_log("Error occurred during the backup creation. Error code: $result");
        }
    }
    //_____________________________________________________________________________________________________________________
    
    //PATCHES VARIABLES____________________________________________________________________________________________________
    public $classTicketPatch1 = '            return \'separate\';
        else
            return \'visual\';
        return \'visual\';
    }

    //GETTERS FORMS PLUGIN_____________________________________________________
    function getCabinetId(){
        return $this->cabinet_id;
    }

    function getCinemoterId(){
        return $this->cinemometer_id;
    }

    function getUpsId(){
        return $this->ups_id;
    }

    function getRouterId(){
        return $this->router_id;
    }

    function getDistrict() {
        return FormsPlugin::getDistrict($this->getCabinetId());
    }

    function getAddress() {
        return FormsPlugin::getAddress($this->getCabinetId());
    }

    function getCabinetInfo() {
        return FormsPlugin::getCabinInfo($this->getCabinetId());
    }

    function isCabinBroken() {
        return $this->cabinet_is_broken;
    }    

    function getCinemometerInfo() {
        return FormsPlugin::getCinemometerInfo($this->getCinemoterId());
    }

    function isCinemometerBroken() {
        return $this->cinemometer_is_broken;
    }

    function getUpsInfo() {
        return FormsPlugin::getUpsInfo($this->getUpsId());
    }

    function isUpsBroken() {
        return $this->ups_is_broken;
    }

    function getRouterInfo() {
        return FormsPlugin::getRouterInfo($this->getRouterId());
    }

    function isRouterBroken() {
        return $this->router_is_broken;
    }
    function isOtherBroken() {
        return $this->other_is_broken;
    }
    //__________________________________________________________________________
    ';
    
    public $classTicketPatch2 = '        //NEW FIELDS FOR BROKEN EQUIPMENTS_____________________________________
        case \'brokenCabin\':
            return ChoiceField::init(array(
                        \'id\' => $fid,
                        \'name\' => \'cabinet_is_broken\',
                        \'label\' => __(\'Cabin\'),
                        \'default\' => $this->isCabinBroken(),
                        \'choices\' => array("Não"=>"Não","Sim"=>"Sim")
                        ));
            break;
        case \'brokenCinemometer\':
            return ChoiceField::init(array(
                        \'id\' => $fid,
                        \'name\' => \'cinemometer_is_broken\',
                        \'label\' => __(\'Cinemometer\'),
                        \'default\' => $this->isCinemometerBroken(),
                        \'choices\' => array("Não"=>"Não","Sim"=>"Sim")
                        ));
            break;
        case \'brokenRouter\':
            return ChoiceField::init(array(
                        \'id\' => $fid,
                        \'name\' => \'router_is_broken\',
                        \'label\' => __(\'Router\'),
                        \'default\' => $this->isRouterBroken(),
                        \'choices\' => array("Não"=>"Não","Sim"=>"Sim")
                        ));
            break;
        case \'brokenUps\':
            return ChoiceField::init(array(
                        \'id\' => $fid,
                        \'name\' => \'ups_is_broken\',
                        \'label\' => __(\'UPS\'),
                        \'default\' => $this->isUpsBroken(),
                        \'choices\' => array("Não"=>"Não","Sim"=>"Sim")
                        ));
            break;
        case \'brokenOther\':
            return ChoiceField::init(array(
                        \'id\' => $fid,
                        \'name\' => \'other_is_broken\',
                        \'label\' => __(\'Other\'),
                        \'default\' => $this->isOtherBroken(),
                        \'choices\' => array("Não"=>"Não","Sim"=>"Sim")
                        ));
            break;
        //____________________________________________________________________
    ';
    
    public $classTicketPatch3 = '                //REQUIRED FIELDS FORMS PLUGIN__________________________________________________________________________________________
                $fields[\'district_option\']  = array(\'type\'=>\'string\',  \'required\'=>1, \'error\'=>__(\'District selection is required\'));
                $fields[\'address_option\']  = array(\'type\'=>\'string\',  \'required\'=>1, \'error\'=>__(\'Address selection is required\'));
                $fields[\'place_option\']  = array(\'type\'=>\'string\',  \'required\'=>1, \'error\'=>__(\'Place selection is required\'));
                //______________________________________________________________________________________________________________________
    ';
    
    public $classTicketPatch4 = '        //SAVE VALUES OF NEW FORMS ELEMENTS TO DATABASE__________________________________
        if (isset($vars[\'place_option\'])) {
            preg_match(\'/km\s+([\d.]+)/\', $vars[\'place_option\'], $matches);
            $pk = "km " . $matches[1];
            preg_match(\'/\b([A-Za-z])\b/\', $vars[\'place_option\'], $matches);
            $c_d = $matches[1];
            
            foreach ($vars[\'checkbox_name\'] as $string) {
                if (strpos($string, \'Cabine\') !== false) {
                    $ticket->cabinet_is_broken = \'Sim\';
                } else if (strpos($string, \'Cinemómetro\') !== false) {
                    $ticket->cinemometer_is_broken = \'Sim\';
                } else if (strpos($string, \'Router\') !== false) {
                    $ticket->router_is_broken = \'Sim\';
                } else if (strpos($string, \'UPS\') !== false) {
                    $ticket->ups_is_broken = \'Sim\';
                } else if (strpos($string, \'Outro\') !== false) {
                    $ticket->other_is_broken = \'Sim\';
                }
            }
            
            $ticket->cabinet_id = FormsPlugin::getCabinetId($pk, $c_d);
            $ticket->cinemometer_id = FormsPlugin::getCinemometerId($ticket->cabinet_id);
            $ticket->ups_id = FormsPlugin::getUpsId($ticket->cabinet_id);
            $ticket->router_id = FormsPlugin::getRouterId($ticket->cabinet_id);
        }
        //________________________________________________________________________________
    '; 
    //_____________________________________________________________________________________________________________________
}
$forms_plugin = new FormsPlugin();
$forms_plugin->bootstrap();