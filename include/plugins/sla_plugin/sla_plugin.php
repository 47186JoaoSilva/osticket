<?php

require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.signal.php');
require_once(INCLUDE_DIR . 'class.app.php');
require_once(INCLUDE_DIR . 'class.dispatcher.php');
require_once(INCLUDE_DIR . 'plugins/sla_plugin/sla_types.php');

class SLAPlugin extends Plugin{
    
    function bootstrap() {
        //Signal quando o plugin é ativado ou desativado
        Signal::connect('model.updated', array($this, 'alter_table_tickets_suspension_state_date'));
        Signal::connect('model.updated', array($this, 'create_suspension_state'));
        Signal::connect('model.updated', array($this, 'create_suspensions_table'));
        Signal::connect('model.updated', array($this, 'do_ajax_tickets_patches'));
        Signal::connect('model.updated', array($this, 'do_status_options_patches'));
        Signal::connect('model.updated', array($this, 'do_class_ticket_patches'));
        Signal::connect('model.updated', array($this, 'reopen_sus_tickets'));

        //Signal quando o plugin é apagado
        Signal::connect('model.deleted', array($this, 'undo_status_options_patches'));
        Signal::connect('model.deleted', array($this, 'undo_class_ticket_patches'));
        Signal::connect('model.deleted', array($this, 'undo_ajax_tickets_patches'));
        Signal::connect('model.deleted', array($this, 'drop_suspensions_table'));
        Signal::connect('model.deleted', array($this, 'delete_suspension_state'));
        Signal::connect('model.deleted', array($this, 'alter_table_tickets_suspension_state_date'));
    }
       
    function reopen_sus_tickets(){
        if(!$this->isPluginActive()){
            $query = 
                "UPDATE ".TABLE_PREFIX."ticket SET status_id = 1, reopened = NOW() WHERE status_id = 6;";
            db_query($query);
        }
        return;
    }
    
    function isCodeAlreadyInserted($filePath, $codeSnippet) {
        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            throw new Exception("Failed to read file at $filePath");
        }

        return strpos($fileContent, $codeSnippet) !== false;
    }

//AJAX TICKET CHANGES_____________________________________________________________________________________________________
    function do_ajax_tickets_patches(){
        $checkCode = "require_once(INCLUDE_DIR.'plugins/sla_plugin/sla_plugin.php');";
        if($this->isPluginActive()) {
            if(!$this->isCodeAlreadyInserted(INCLUDE_DIR . 'class.ticket.php', $checkCode)) {
              $this->replaceFile(INCLUDE_DIR . 'ajax.tickets.php', 'ajax.tickets-modified.php', 'ajax.tickets-backup.php');
            }
        } else {
            if($this->isCodeAlreadyInserted(INCLUDE_DIR . 'class.ticket.php', $checkCode)) {
                $this->undo_ajax_tickets_patches();
            }
        }  
    }
    
    function undo_ajax_tickets_patches(){
        $this->restoreFile(INCLUDE_DIR . 'ajax.tickets.php', 'backup_files/ajax.tickets-backup.php');
    }

//_________________________________________________________________________________________________________________________

//CLASS TICKET CHANGES_____________________________________________________________________________________________________
    function class_ticket_list_of_patches(){
        $filePath = INCLUDE_DIR . 'class.ticket.php';
        $newCode = array(
            'require_once(INCLUDE_DIR.\'plugins/sla_plugin/sla_plugin.php\');',
            '    const PERM_SUSPEND   = \'ticket.suspend\';',
            'self::PERM_SUSPEND => array(
                \'title\' =>
                /* @trans */ \'Suspend\',
                \'desc\'  =>
                /* @trans */ \'Ability to suspend tickets\'),
            );',
            '    }
    
            function isSuspended() {
                return $this->hasState(\'suspended\');
            }
            ',
            'switch ($status->getState()) {
            case \'closed\':
            case \'suspended\':
                if (!($role->hasPerm(Ticket::PERM_CLOSE)))
                    return false;
                break;
            case \'deleted\':
                // XXX: intercept deleted status and do hard delete TODO: soft deletes
                if ($role->hasPerm(Ticket::PERM_DELETE))
                    return $this->delete($comments);
                // Agent doesn\'t have permission to delete  tickets
                return false;
                break;
            }
        }

        $hadStatus = $this->getStatusId();
        if ($this->getStatusId() == $status->getId())
            return true;

        // Perform checks on the *new* status, _before_ the status changes
        $ecb = $refer = null;
        switch ($status->getState()) {
            case \'suspended\':
                if($this->isOpen()){
                    $sla_types = new sla_types();
                    if($sla_types->check_if_schedule($this->getId()) === []){
                        $errors[\'err\'] = $closeable ?: sprintf(__(\'%s cannot be suspended due to SLAs schedule not existing\'), __(\'This ticket\'));
                    }
                    // Check if ticket is closeable
                    $closeable = $force_close ? true : $this->isCloseable();
                    if ($closeable !== true)
                        $errors[\'err\'] = $closeable ?: sprintf(__(\'%s cannot be suspended\'), __(\'This ticket\'));

                    if ($errors)
                        return false;

                    $refer = $this->staff ?: $thisstaff;
                    $this->suspended = $this->lastupdate = SqlFunction::NOW();

                    $ecb = function($t) use ($status) {
                        $t->logEvent(\'suspended\', array(\'status\' => array($status->getId(), $status->getName())), null, \'suspended\');
                    };
                    $sla_plugin = new SLAPlugin();
                    $sla_plugin->start_suspension($this->getId(), $comments);
                }
                break;
            case \'closed\':
                // Check if ticket is closeable
                $closeable = $force_close ? true : $this->isCloseable();
                if ($closeable !== true)
                    $errors[\'err\'] = $closeable ?: sprintf(__(\'%s cannot be closed\'), __(\'This ticket\'));

                if ($errors)
                    return false;

                $refer = $this->staff ?: $thisstaff;
                $this->closed = $this->lastupdate = SqlFunction::NOW();
                if ($thisstaff && $set_closing_agent)
                    $this->staff = $thisstaff;
                // Clear overdue flags & due dates
                $this->clearOverdue(false);

                $ecb = function($t) use ($status) {
                    $t->logEvent(\'closed\', array(\'status\' => array($status->getId(), $status->getName())), null, \'closed\');
                    $t->deleteDrafts();
                };
        if($this->isSuspended()){
                $sla_plugin = new SLAPlugin();
                    $sla_plugin->end_suspension($this->getId());
                    $sla_plugin->close_suspension($this->getId());
                }
                if($this->isOpen()){
                    $sla_plugin = new SLAPlugin();
                    $sla_plugin->close_suspension($this->getId());
                }
                break;
            case \'open\':
                if ($this->isClosed() && $this->isReopenable()) {
                    // Auto-assign to closing staff or the last respondent if the
                    // agent is available and has access. Otherwise, put the ticket back
                    // to unassigned pool.
                    $dept = $this->getDept();
                    $staff = $this->getStaff() ?: $this->getLastRespondent();
                    $autoassign = (!$dept->disableReopenAutoAssign());
                    if ($autoassign
                            && $staff
                            // Is agent on vacation ?
                            && $staff->isAvailable()
                            // Does the agent have access to dept?
                            && $staff->canAccessDept($dept))
                        $this->setStaffId($staff->getId());
                    else
                        $this->setStaffId(0); // Clear assignment
                }

                if ($this->isClosed()) {
                    $this->closed = null;
                    $this->lastupdate = $this->reopened = SqlFunction::NOW();
                    $ecb = function ($t) {
                        $t->logEvent(\'reopened\', false, null, \'closed\');
                        // Set new sla duedate if any
                        $t->updateEstDueDate();
                    };
                }
        if($this->isSuspended()){
                $sla_plugin = new SLAPlugin();
                    $sla_plugin->end_suspension($this->getId());
                }
            ',
            '        case \'suspended\':
            return $this->setStatus(\'suspended\');
        case \'open\':'
        );
        $startPoint = array(
            'require_once(INCLUDE_DIR.\'class.faq.php\');',
            'const PERM_DELETE   = \'ticket.delete\';',
            '/* @trans */ \'Ability to delete tickets\'),',
            'return $this->hasState(\'closed\');',
            'if ($role && $this->getStatusId()) {',
            'switch (strtolower($state)) {'
        );
        $endPoint = array(
            'class Ticket extends VerySimpleModel',
            '    const FLAG_COMBINE_THREADS     = 0x0001;',
            '    // Ticket Sources',
            '    function isCloseable() {',
            '                // If the ticket is not open then clear answered flag',
            '            return $this->setStatus(\'open\');'
        );
        return array('filePath' => $filePath, 'newCode' => $newCode, 'startPoint' => $startPoint, 'endPoint' => $endPoint);
    }
    
    function do_class_ticket_patches(){
        $checkCode = "require_once(INCLUDE_DIR.'plugins/sla_plugin/sla_plugin.php');";
        if($this->isPluginActive()) {
            if(!$this->isCodeAlreadyInserted(INCLUDE_DIR . 'class.ticket.php', $checkCode)) {
                $patches = $this->class_ticket_list_of_patches();
                for ($i = 0; $i <= sizeof($patches['newCode'])-1; $i++) {
                    $this->insertCodeIntoFile($patches['filePath'], $patches['newCode'][$i], $patches['startPoint'][$i], $patches['endPoint'][$i]);
                }   
            }
        } else {
            if($this->isCodeAlreadyInserted(INCLUDE_DIR . 'class.ticket.php', $checkCode)) {
                $this->undo_class_ticket_patches();
            }
        }  
    }
    
    function class_ticket_list_of_unpatches(){
        $filePath = INCLUDE_DIR . 'class.ticket.php';
        $newCode = array(
            ' ',
            ' ',
            '            );
            ',
            '    }
            ',
            '        switch ($status->getState()) {
            case \'closed\':
                if (!($role->hasPerm(Ticket::PERM_CLOSE)))
                    return false;
                break;
            case \'deleted\':
                // XXX: intercept deleted status and do hard delete TODO: soft deletes
                if ($role->hasPerm(Ticket::PERM_DELETE))
                    return $this->delete($comments);
                // Agent doesn\'t have permission to delete  tickets
                return false;
                break;
            }
        }

        $hadStatus = $this->getStatusId();
        if ($this->getStatusId() == $status->getId())
            return true;

        // Perform checks on the *new* status, _before_ the status changes
        $ecb = $refer = null;
        switch ($status->getState()) {
            case \'closed\':
                // Check if ticket is closeable
                $closeable = $force_close ? true : $this->isCloseable();
                if ($closeable !== true)
                    $errors[\'err\'] = $closeable ?: sprintf(__(\'%s cannot be closed\'), __(\'This ticket\'));

                if ($errors)
                    return false;

                $refer = $this->staff ?: $thisstaff;
                $this->closed = $this->lastupdate = SqlFunction::NOW();
                if ($thisstaff && $set_closing_agent)
                    $this->staff = $thisstaff;
                // Clear overdue flags & due dates
                $this->clearOverdue(false);

                $ecb = function($t) use ($status) {
                    $t->logEvent(\'closed\', array(\'status\' => array($status->getId(), $status->getName())), null, \'closed\');
                    $t->deleteDrafts();
                };
                break;
            case \'open\':
                if ($this->isClosed() && $this->isReopenable()) {
                    // Auto-assign to closing staff or the last respondent if the
                    // agent is available and has access. Otherwise, put the ticket back
                    // to unassigned pool.
                    $dept = $this->getDept();
                    $staff = $this->getStaff() ?: $this->getLastRespondent();
                    $autoassign = (!$dept->disableReopenAutoAssign());
                    if ($autoassign
                            && $staff
                            // Is agent on vacation ?
                            && $staff->isAvailable()
                            // Does the agent have access to dept?
                            && $staff->canAccessDept($dept))
                        $this->setStaffId($staff->getId());
                    else
                        $this->setStaffId(0); // Clear assignment
                }

                if ($this->isClosed()) {
                    $this->closed = null;
                    $this->lastupdate = $this->reopened = SqlFunction::NOW();
                    $ecb = function ($t) {
                        $t->logEvent(\'reopened\', false, null, \'closed\');
                        // Set new sla duedate if any
                        $t->updateEstDueDate();
                    };
                }
                ',
            '        case \'open\':'
        );
        $startPoint = array(
            'require_once(INCLUDE_DIR.\'class.faq.php\');',
            'const PERM_DELETE   = \'ticket.delete\';',
            '/* @trans */ \'Ability to delete tickets\'),',
            'return $this->hasState(\'closed\');',
            'if ($role && $this->getStatusId()) {',
            'switch (strtolower($state)) {'
        );
        $endPoint = array(
            'class Ticket extends VerySimpleModel',
            '    const FLAG_COMBINE_THREADS     = 0x0001;',
            '    // Ticket Sources',
            '    function isCloseable() {',
            '                // If the ticket is not open then clear answered flag',
            '            return $this->setStatus(\'open\');'
        );
        return array('filePath' => $filePath, 'newCode' => $newCode, 'startPoint' => $startPoint, 'endPoint' => $endPoint);
    }
    
    function undo_class_ticket_patches(){
        $close_suspension_query =
            "START TRANSACTION;
                UPDATE ".TABLE_PREFIX."ticket_suspend_status_info
                SET act_flag = 0
                WHERE act_flag = 1;
            COMMIT;";
        $result = db_query($close_suspension_query);
        if ($result) {
            error_log("All past suspensions where successfully closed.");
        } else {
            error_log("Error while closing past suspensions: " . db_error());
        }
        
        $unpatches = $this->class_ticket_list_of_unpatches();
        for ($i = 0; $i <= sizeof($unpatches['newCode'])-1; $i++) {
            $this->insertCodeIntoFile($unpatches['filePath'], $unpatches['newCode'][$i], $unpatches['startPoint'][$i], $unpatches['endPoint'][$i]);
        }
    }
    
//_________________________________________________________________________________________________________________________________________
 
//STATUS OPTIONS CHANGES___________________________________________________________________________________________________________________  
    
    function do_status_options_patches(){
        $checkCode = "require_once(INCLUDE_DIR.'plugins/sla_plugin/sla_plugin.php');";
        if($this->isPluginActive()) {
            if(!$this->isCodeAlreadyInserted(INCLUDE_DIR . 'class.ticket.php', $checkCode)) {
                $this->replaceFile(INCLUDE_DIR . 'staff/templates/status-options.tmpl.php', 'status-options-modified.tmpl.php', 'status-options-backup.tmpl.php');
                $this->status_options = true;
            }
        } else {
            if($this->isCodeAlreadyInserted(INCLUDE_DIR . 'class.ticket.php', $checkCode)) {
                $this->undo_status_options_patches();
            }
        }
    }
    
    function undo_status_options_patches(){
        $this->restoreFile(INCLUDE_DIR . 'staff/templates/status-options.tmpl.php', 'backup_files/status-options-backup.tmpl.php');
        $this->status_options = false;
    }
    
//_________________________________________________________________________________________________________________________________________
    
    function alter_table_tickets_suspension_state_date(){
        $query = ""; 
        if(!$this->isPluginActive()){
            $query = "ALTER TABLE ".TABLE_PREFIX."ticket DROP COLUMN suspended;";
        }
        else{
            $query = "ALTER TABLE ".TABLE_PREFIX."ticket ADD suspended DATETIME;";
        }
        $result = db_query($query);
    }
    
    function create_suspension_state(){
        if(!$this->isPluginActive()){
            return;
        }
        $create_suspension_state_query = 
                "INSERT INTO ".TABLE_PREFIX."ticket_status (id, name, state, mode, flags, sort, properties, created, updated) VALUES (6, 'Suspended', 'suspended', 3, 0, 6, '{\"allowreopen\":true,\"reopenstatus\":0,\"description\":\"Suspended tickets. Tickets will still be accessible on client and staff panels.\"}', NOW(), '0000-00-00 00:00:00');";
        $result = db_query($create_suspension_state_query);
        if ($result) {
            error_log("Suspension state was successfully created.");
        } else {
            error_log("Error while creating suspension state:" . db_error());
        }
    }

    function create_suspensions_table(){
        if(!$this->isPluginActive()){
            return;
        }
        $create_table_query = "
            CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."ticket_suspend_status_info(
                tid INT NOT NULL AUTO_INCREMENT,
                ticket_id INT(11),
                act_flag BIT,
                begin_suspension DATETIME,
                end_suspension DATETIME,
                reason VARCHAR(255),
                suspension_time DECIMAL(10,2),
                PRIMARY KEY(tid)
            );
        ";
        $result = db_query($create_table_query);
        if ($result) {
            error_log("Table for saving suspensions' info successfully created.");
        } else {
            error_log("Error while creating the table for saving suspensions' info:" . db_error());
        }
    }

    function delete_suspension_state(){
        $delete_suspension_state_query = "DELETE FROM ".TABLE_PREFIX."ticket_status WHERE name='Suspended';";
        $result = db_query($delete_suspension_state_query);
        if ($result) {
            error_log("Suspension state was successfully deleted.");
        } else {
            error_log("Error while deleting suspension state:" . db_error());
        }
    }

    function drop_suspensions_table(){
        $drop_table_query = "DROP TABLE IF EXISTS ".TABLE_PREFIX."ticket_suspend_status_info;";
        $result = db_query($drop_table_query);
        if ($result) {
            error_log("Table for saving suspensions' info successfully deleted.");
        } else {
            error_log("Error while deleting the table for saving suspensions' info:" . db_error());
        }
    }

    function start_suspension($ticket_id, $reason){
        if(!$this->isPluginActive()){
            return;
        }
        $begin_suspension = date("Y-m-d H:i:s");
        $begin_suspension_query = 
            "INSERT INTO ".TABLE_PREFIX."ticket_suspend_status_info(ticket_id, act_flag, begin_suspension, end_suspension, reason, suspension_time)
                VALUES ('$ticket_id', 1, '$begin_suspension', NULL,'$reason', NULL);";
        db_query($begin_suspension_query);    
    }

    function end_suspension($ticket_id){
        if(!$this->isPluginActive()){
            return;
        }
        $end_date = date("Y-m-d H:i:s");
        $end_time = date("H:i:s");
        $weekday_end_date = strtolower(date('l'));

        $get_begin_suspension_query = "SELECT begin_suspension FROM ".TABLE_PREFIX."ticket_suspend_status_info WHERE ticket_id = '$ticket_id' AND end_suspension IS NULL AND act_flag = 1;";
        $result = db_query($get_begin_suspension_query);

        if (!$result) {
            error_log("Suspension was not terminated.");
            return;
        }

        $begin_suspension = '';
        while ($row = db_fetch_array($result)) {
            $begin_suspension = $row['begin_suspension'];
        }
        
        $get_sla_query = "SELECT ose.name FROM ost_ticket ott
                        INNER JOIN ost_sla osa ON osa.id = ott.sla_id
                        INNER JOIN ost_schedule ose ON ose.id = osa.schedule_id
                        WHERE ott.ticket_id = $ticket_id;";
        $result = db_query($get_sla_query);
        if (!$result) {
            error_log("Error getting sla id.");
            return;
        }
        $sla_name = '';
        while ($row = db_fetch_array($result)) {
            $sla_name = $row['name'];
        }
        
        $sla_types = new sla_types();
        $sus_time = $sla_types->getSusHours($begin_suspension, $end_date, $sla_name, $ticket_id);

        $end_suspension_query =
            "UPDATE ".TABLE_PREFIX."ticket_suspend_status_info
                SET end_suspension = '$end_date', suspension_time = ROUND($sus_time, 2)
                WHERE tid IN (SELECT tid FROM ".TABLE_PREFIX."ticket_suspend_status_info WHERE ticket_id = '$ticket_id' AND end_suspension IS NULL AND act_flag = 1);";
        db_query($end_suspension_query);
    }

    function close_suspension($ticket_id){
        if(!$this->isPluginActive()){
            return;
        }
        $close_suspension_query =
            "UPDATE ".TABLE_PREFIX."ticket_suspend_status_info
             SET act_flag = 0
             WHERE tid IN (SELECT tid FROM ".TABLE_PREFIX."ticket_suspend_status_info WHERE ticket_id = '$ticket_id' AND act_flag = 1 AND end_suspension IS NOT NULL);";
        db_query($close_suspension_query);
    }

    function isPluginActive() {
        $query = "SELECT isactive FROM " . TABLE_PREFIX . "plugin WHERE name = 'SLA Plugin'"; 
        $result = db_query($query);
        if ($result) {
            $row = db_fetch_array($result);
            return $row['isactive'];
        } else {
            error_log("Error fetching isactive from ".TABLE_PREFIX."plugin table");
            return false;
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
        $backupDir = INCLUDE_DIR . "plugins/sla_plugin/mysqldump/";

        $backupCommand = "$mysqlDumpPath -h $dbHost -u $dbUser -p$dbPass $dbName ".TABLE_PREFIX."ticket_suspend_status_info > \"" . $backupDir . "sla_sus_info_backup.sql\"";
        system($backupCommand, $result);

        if ($result == 0) {
            error_log("Backup files for ".TABLE_PREFIX."ticket_suspend_status_info successfully.");
        } else {
            error_log("Error occurred during the backup creation. Error code: $result");
        }
    }
    
//REPLACE,RESTORE,MOVE AND PATCH FUNCTIONS______________________________________________________________________________
    function replaceFile($file_path, $modified_file_name, $backup_file_name) {
        $modified_file_path = __DIR__ . '\modified_files' . '/' . $modified_file_name;
        $backup_file_path = __DIR__ . '\backup_files' . '/' . $backup_file_name;

        if (file_exists($file_path) && file_exists($modified_file_path)) {
            $modified_content = file_get_contents($modified_file_path);

            // Create the backup directory if it doesn't exist
            if (!is_dir(__DIR__ . '\backup_files')) {
                mkdir(__DIR__ . '\backup_files', 0755, true);
            }

            // Copy the original file to the backup directory
            if (!copy($file_path, $backup_file_path)) {
                error_log("Failed to create backup of $file_path at $backup_file_path!");
            }

            // Replace the original file with the modified content
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
    
    function restoreFile($file_path, $backup_path) {
        $backup_file_path = __DIR__ . '\backup_files' . '/' . basename($backup_path);
        
        if (file_exists($backup_file_path)) {
            copy($backup_file_path, $file_path);
            // Delete the backup file after restoring
            unlink($backup_file_path);
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

        $startPointEnd = $startPos + strlen($startPoint);

        if ($endPoint === '') {
            // If endPoint is an empty string, set endPos to the end of the file
            $endPos = strlen($fileContent);
        } else {
            $endPos = strpos($fileContent, $endPoint, $startPos);
            if ($endPos === false) {
                throw new Exception("End point '$endPoint' not found in file $filePath");
            }
        }

        $endPointStart = $endPos;

        if ($newCode === '') {
            $updatedContent = substr($fileContent, 0, $startPointEnd) 
                . "\n"
                . substr($fileContent, $endPointStart);              
        } else {
            $updatedContent = substr($fileContent, 0, $startPointEnd) 
                . "\n" . $newCode . "\n"                             
                . substr($fileContent, $endPointStart);              
        }           

        if (file_put_contents($filePath, $updatedContent) === false) {
            throw new Exception("Failed to write updated content to file $filePath");
        }

        return true;
    }
    //_____________________________________________________________________________________________________________________
}

$sla_plugin = new SLAPlugin();
$sla_plugin->bootstrap();
