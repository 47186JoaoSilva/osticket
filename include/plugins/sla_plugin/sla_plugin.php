<?php

require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.signal.php');
require_once(INCLUDE_DIR . 'class.app.php');
require_once(INCLUDE_DIR . 'class.dispatcher.php');
require_once(INCLUDE_DIR . 'plugins/sla_plugin/sla_types.php');

class SLAPlugin extends Plugin{
    private $ajaxTicketsPatched = false;
    private $classTicketPatched = false;   
    private $status_options = false;

    
    function bootstrap() {
        //Signal quando o plugin é ativado ou desativado
        Signal::connect('model.updated', array($this, 'create_suspension_state'));
        Signal::connect('model.updated', array($this, 'create_suspensions_table'));
        Signal::connect('model.updated', array($this, 'do_ajax_tickets_patches'));
        Signal::connect('model.updated', array($this, 'do_class_ticket_patches'));
        Signal::connect('model.updated', array($this, 'do_status_options_patches'));

        //Signal quando o plugin é apagado
        Signal::connect('model.deleted', array($this, 'undo_status_options_patches'));
        Signal::connect('model.deleted', array($this, 'undo_class_ticket_patches'));
        Signal::connect('model.deleted', array($this, 'undo_ajax_tickets_patches'));
        Signal::connect('model.deleted', array($this, 'drop_suspensions_table'));
        Signal::connect('model.deleted', array($this, 'delete_suspension_state'));
    }
    
    function normalizeLineEndingsToCRLF($file_path) {
        $text = file_get_contents($file_path);

        if ($text === false) {
            return;
        }
        
        $normalizedText = preg_replace('/\r\n|\r|\n/', "\r\n", $text);
        $result = file_put_contents($file_path, $normalizedText);
        if ($result === false) {
            return;
        }
    }
       
    function isCodeAlreadyInserted($filePath, $codeSnippet) {
        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            throw new Exception("Failed to read file at $filePath");
        }

        return strpos($fileContent, $codeSnippet) !== false;
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
    
    function applyPatch($file, $patch) {
        if (!file_exists($file)) {
            return false;
        }

        $contents = file_get_contents($file);
        if ($contents === false) {
            return false;
        }
        
        $patchedContents = str_replace($patch['search'], $patch['replace'], $contents);

        $result = file_put_contents($file, $patchedContents);
        return $result !== false;
    }
    
    function patch_to($search_code, $replace_code){
        return [
            'search' => $search_code,
            'replace' => $replace_code
        ];
    } 
    
    function ajax_tickets_list_of_patches(){
        $file_path = INCLUDE_DIR . 'ajax.tickets.php';
        $searches = array(
            "function changeTicketStatus(\$tid, \$status, \$id=0) {
        global \$thisstaff;

        if (!\$thisstaff)
            Http::response(403, 'Access denied');
        elseif (!\$tid
                || !(\$ticket=Ticket::lookup(\$tid))
                || !\$ticket->checkStaffPerm(\$thisstaff))
            Http::response(404, 'Unknown ticket #');

        \$role = \$ticket->getRole(\$thisstaff);

        \$info = array();
        \$state = null;
        switch(\$status) {
            case 'open':
            case 'reopen':
                \$state = 'open';
                break;",
            "function setTicketStatus(\$tid) {
        global \$thisstaff, \$ost;

        if (!\$thisstaff)
            Http::response(403, 'Access denied');
        elseif (!\$tid
                || !(\$ticket=Ticket::lookup(\$tid))
                || !\$ticket->checkStaffPerm(\$thisstaff))
            Http::response(404, 'Unknown ticket #');

        \$errors = \$info = array();
        if (!\$_POST['status_id']
                || !(\$status= TicketStatus::lookup(\$_POST['status_id'])))
            \$errors['status_id'] = sprintf('%s %s',
                    __('Unknown or invalid'), __('status'));
        elseif (\$status->getId() == \$ticket->getStatusId())
            \$errors['err'] = sprintf(__('Ticket already set to %s status'),
                    __(\$status->getName()));
        elseif ((\$role = \$ticket->getRole(\$thisstaff))) {
            // Make sure the agent has permission to set the status
            switch(mb_strtolower(\$status->getState())) {
                case 'open':
                    if (!\$role->hasPerm(Ticket::PERM_CLOSE)
                            && !\$role->hasPerm(Ticket::PERM_CREATE))
                        \$errors['err'] = sprintf(__('You do not have permission %s'),
                                __('to reopen tickets'));
                    break;
                case 'closed':
                    if (!\$role->hasPerm(Ticket::PERM_CLOSE))
                        \$errors['err'] = sprintf(__('You do not have permission %s'),
                                __('to resolve/close tickets'));
                    break;
                case 'deleted':
                    if (!\$role->hasPerm(Ticket::PERM_DELETE))
                        \$errors['err'] = sprintf(__('You do not have permission %s'),
                                __('to archive/delete tickets'));
                    break;",
            "function changeSelectedTicketsStatus(\$status, \$id=0) {
        global \$thisstaff, \$cfg;

        if (!\$thisstaff)
            Http::response(403, 'Access denied');

        \$state = null;
        \$info = array();
        switch(\$status) {
            case 'open':
            case 'reopen':
                \$state = 'open';
                break;",
            "function setSelectedTicketsStatus(\$state) {
        global \$thisstaff, \$ost;

        \$errors = \$info = array();
        if (!\$thisstaff || !\$thisstaff->canManageTickets())
            \$errors['err'] = sprintf('%s %s',
                    sprintf(__('You do not have permission %s'),
                        __('to mass manage tickets')),
                    __('Contact admin for such access'));
        elseif (!\$_REQUEST['tids'] || !count(\$_REQUEST['tids']))
            \$errors['err']=sprintf(__('You must select at least %s.'),
                    __('one ticket'));
        elseif (!(\$status= TicketStatus::lookup(\$_REQUEST['status_id'])))
            \$errors['status_id'] = sprintf('%s %s',
                    __('Unknown or invalid'), __('status'));
        elseif (!\$errors) {
            // Make sure the agent has permission to set the status
            switch(mb_strtolower(\$status->getState())) {
                case 'open':
                    if (!\$thisstaff->hasPerm(Ticket::PERM_CLOSE, false)
                            && !\$thisstaff->hasPerm(Ticket::PERM_CREATE, false))
                        \$errors['err'] = sprintf(__('You do not have permission %s'),
                                __('to reopen tickets'));
                    break;
                case 'closed':
                    if (!\$thisstaff->hasPerm(Ticket::PERM_CLOSE, false))
                        \$errors['err'] = sprintf(__('You do not have permission %s'),
                                __('to resolve/close tickets'));
                    break;
                case 'deleted':
                    if (!\$thisstaff->hasPerm(Ticket::PERM_DELETE, false))
                        \$errors['err'] = sprintf(__('You do not have permission %s'),
                                __('to archive/delete tickets'));
                    break;"
        );
        $replaces = array(
            "case 'suspend':
                \$state =  'suspended';
                break;",
            "case 'suspended':
                    if (!\$role->hasPerm(Ticket::PERM_SUSPEND))
                        \$errors['err'] = sprintf(__('You do not have permission %s'),
                                __('to suspend tickets'));
                    break;",
            "case 'suspend':
                \$state =  'suspended';
                break;",
            "case 'suspended':
                    if (!\$thisstaff->hasPerm(Ticket::PERM_SUSPEND, false))
                        \$errors['err'] = sprintf(__('You do not have permission %s'),
                                __('to suspend tickets'));
                    break;"
            
        );
        return array('file_path' => $file_path, 'searches' => $searches, 'replaces' => $replaces);
    }
    
    function do_ajax_tickets_patches(){
        $checkCode = "case 'suspend':
                \$state =  'suspended';
                break;";
        if(!$this->isPluginActive()){
            if($this->isCodeAlreadyInserted(INCLUDE_DIR . 'ajax.tickets.php', $checkCode)) {
                $this->undo_ajax_tickets_patches();
            }
            return;
        }
        $patches = $this->ajax_tickets_list_of_patches();
        foreach ($patches['searches'] as $index=>$search) {
            $fileContents = file_get_contents($patches['file_path']);
            if(!$this->isCodeAlreadyInserted($patches['file_path'], $patches['replaces'][$index]) || ($patches['replaces'][$index] == $checkCode && substr_count($fileContents, $checkCode) < 2)) {
                $this->applyPatch($patches['file_path'], $this->patch_to($search, $search . "\n" . $patches['replaces'][$index]));
            }
        }
        $this->normalizeLineEndingsToCRLF($patches['file_path']);
        $this->ajaxTicketsPatched = true;
    }
    
    function undo_ajax_tickets_patches(){
        $this->restoreFile(INCLUDE_DIR . 'ajax.tickets.php', 'backup_files/ajax.tickets-backup.php');
    }

    function class_ticket_list_of_patches(){
        $file_path = INCLUDE_DIR . 'class.ticket.php';
        $searches = array(
            "const PERM_DELETE   = 'ticket.delete';",
            "/* @trans */ 'Ability to delete tickets'),",
            "function isClosed() {
         return \$this->hasState('closed');
    }",
            "// Ticket Status helper.
    function setStatus(\$status, \$comments='', &\$errors=array(), \$set_closing_agent=true, \$force_close=false) {
        global \$cfg, \$thisstaff;

        if (\$thisstaff && !(\$role=\$this->getRole(\$thisstaff)))
            return false;

        if ((!\$status instanceof TicketStatus)
                && !(\$status = TicketStatus::lookup(\$status)))
            return false;

        // Double check permissions (when changing status)
        if (\$role && \$this->getStatusId()) {
            switch (\$status->getState()) {
            case 'closed':",
            "// Perform checks on the *new* status, _before_ the status changes
        \$ecb = \$refer = null;
        switch (\$status->getState()) {",
            "\$refer = \$this->staff ?: \$thisstaff;
                \$this->closed = \$this->lastupdate = SqlFunction::NOW();
                if (\$thisstaff && \$set_closing_agent)
                    \$this->staff = \$thisstaff;
                // Clear overdue flags & due dates
                \$this->clearOverdue(false);

                \$ecb = function(\$t) use (\$status) {
                    \$t->logEvent('closed', array('status' => array(\$status->getId(), \$status->getName())), null, 'closed');
                    \$t->deleteDrafts();
                };",
            "if (\$this->isClosed()) {
                    \$this->closed = null;
                    \$this->lastupdate = \$this->reopened = SqlFunction::NOW();
                    \$ecb = function (\$t) {
                        \$t->logEvent('reopened', false, null, 'closed');
                        // Set new sla duedate if any
                        \$t->updateEstDueDate();
                    };
                }",
            "function setState(\$state, \$alerts=false) {
        switch (strtolower(\$state)) {"
        );
        $replaces = array(
            "const PERM_SUSPEND   = 'ticket.suspend';",
            "self::PERM_SUSPEND => array(
                'title' =>
                /* @trans */ 'Suspend',
                'desc'  =>
                /* @trans */ 'Ability to suspend tickets'),",
            "function isSuspended() {
                return \$this->hasState('suspended');
            }",
            "case 'suspended':",
            "case 'suspended':
                if(\$this->isOpen()){
                    // Check if ticket is closeable
                    \$closeable = \$force_close ? true : \$this->isCloseable();
                    if (\$closeable !== true)
                        \$errors['err'] = \$closeable ?: sprintf(__('%s cannot be suspended'), __('This ticket'));

                    if (\$errors)
                        return false;

                    \$refer = \$this->staff ?: \$thisstaff;
                    \$this->suspended = \$this->lastupdate = SqlFunction::NOW();

                    \$ecb = function(\$t) use (\$status) {
                        \$t->logEvent('suspended', array('status' => array(\$status->getId(), \$status->getName())), null, 'suspended');
                    };
                    \$reason = '';
                    SLAPlugin::start_suspension(\$this->getId(), \$reason);
                }
                break;",
            "if(\$this->isSuspended()){
                    SLAPlugin::end_suspension(\$this->getId());
                    SLAPlugin::close_suspension(\$this->getId());
                }
                if(\$this->isOpen()){
                    SLAPlugin::close_suspension(\$this->getId());
                }",
            "if(\$this->isSuspended()){
                    SLAPlugin::end_suspension(\$this->getId());
                }",
            "case 'suspended':
            return \$this->setStatus('suspended');"
        );
        return array('file_path' => $file_path, 'searches' => $searches, 'replaces' => $replaces);
    }
    
    function do_class_ticket_patches(){
        if(!$this->isPluginActive()){
            $checkCode = "const PERM_SUSPEND   = 'ticket.suspend';";
            if($this->isCodeAlreadyInserted(INCLUDE_DIR . 'class.ticket.php', $checkCode)) {
                $this->undo_class_ticket_patches();
            }
            return;
        }
        $patches = $this->class_ticket_list_of_patches();
        foreach ($patches['searches'] as $index=>$search) {
            if(!$this->isCodeAlreadyInserted($patches['file_path'], $patches['replaces'][$index])) {
                $this->applyPatch($patches['file_path'], $this->patch_to($search, $search . "\n" . $patches['replaces'][$index]));
            }
        }   
        $this->normalizeLineEndingsToCRLF($patches['file_path']);
        $this->classTicketPatched = true;
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
        $this->restoreFile(INCLUDE_DIR . 'class.ticket.php', 'backup_files/class.ticket-backup.php');
    }
    
    function do_status_options_patches(){
        $search = "// Map states to actions
\$actions= array(
        'closed' => array(
            'icon'  => 'icon-ok-circle',
            'action' => 'close',
            'href' => 'tickets.php'
            ),
        'open' => array(
            'icon'  => 'icon-undo',
            'action' => 'reopen'
            ),
        );

\$states = array('open');
if (!\$ticket || \$ticket->isCloseable())
    \$states[] = 'closed';";
        $replace = "// Map states to actions
\$actions= array(
        'closed' => array(
            'icon'  => 'icon-ok-circle',
            'action' => 'close',
            'href' => 'tickets.php'
            ),
        'open' => array(
            'icon'  => 'icon-undo',
            'action' => 'reopen'
            ),
        'suspended' => array(
            'icon'  => 'icon-ok-circle',
            'action' => 'suspended'
            ),
        );

\$states = array('open');
if (!\$ticket || \$ticket->isCloseable()){
    \$states[] = 'closed';
    \$states[] = 'suspended';
}";
        if(!$this->isPluginActive()){
            if($this->isCodeAlreadyInserted(INCLUDE_DIR . "staff/templates/status-options.tmpl.php", $replace)) {
                $this->undo_status_options_patches();
            }
            return;
        }
        $this->applyPatch(INCLUDE_DIR . "staff/templates/status-options.tmpl.php", $this->patch_to($search, $replace));
        $this->normalizeLineEndingsToCRLF(INCLUDE_DIR . "staff/templates/status-options.tmpl.php");
        $this->status_options = true;
    }
    
    function undo_status_options_patches(){
        $replace = "// Map states to actions
\$actions= array(
        'closed' => array(
            'icon'  => 'icon-ok-circle',
            'action' => 'close',
            'href' => 'tickets.php'
            ),
        'open' => array(
            'icon'  => 'icon-undo',
            'action' => 'reopen'
            ),
        );

\$states = array('open');
if (!\$ticket || \$ticket->isCloseable())
    \$states[] = 'closed';";
        $search = "// Map states to actions
\$actions= array(
        'closed' => array(
            'icon'  => 'icon-ok-circle',
            'action' => 'close',
            'href' => 'tickets.php'
            ),
        'open' => array(
            'icon'  => 'icon-undo',
            'action' => 'reopen'
            ),
        'suspended' => array(
            'icon'  => 'icon-ok-circle',
            'action' => 'suspended'
            ),
        );

\$states = array('open');
if (!\$ticket || \$ticket->isCloseable()){
    \$states[] = 'closed';
    \$states[] = 'suspended';
}";
        $this->applyPatch(INCLUDE_DIR . "staff/templates/status-options.tmpl.php", $this->patch_to($search, $replace));
        $this->normalizeLineEndingsToCRLF(INCLUDE_DIR . "staff/templates/status-options.tmpl.php");
        $this->status_options = false;
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
            "START TRANSACTION;
                INSERT INTO ".TABLE_PREFIX."ticket_suspend_status_info(ticket_id, act_flag, begin_suspension, end_suspension, reason, suspension_time)
                VALUES (".$ticket_id.", 1, ".$begin_suspension.", NULL,".$reason.", NULL);
            COMMIT;";
            $result = db_query($begin_suspension_query);
            if ($result) {
                error_log("New suspension for ticket ".$ticket_id." was successfully initiated.");
            } else {
                error_log("Error while creating a new suspension for ticket ".$ticket_id.":" . db_error());
            }
    }

    function end_suspension($ticket_id){
        if(!$this->isPluginActive()){
            return;
        }
        // TODO (Dependendo do SLA ter em conta: horas laborais, fins de semana/feriados/dias úteis/dias ativos de SLA)
        $end_date = date("Y-m-d H:i:s");
        $end_time = date("H:i:s");
        $weekday_end_date = strtolower(date('l'));

        $get_begin_suspension_query = "SELECT begin_suspension FROM ".TABLE_PREFIX."ticket_suspend_status_info WHERE ticket_id = '".$ticket_id."' AND end_suspension IS NULL AND act_flag = 1;";
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
                        WHERE ott.ticket_id = ".$ticket_id.";";
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
            "START TRANSACTION;
                UPDATE ".TABLE_PREFIX."ticket_suspend_status_info
                SET end_suspension = ".$end_date.", suspension_time = ROUND(".$sus_time.", 2)
                WHERE tid IN (SELECT tid FROM ".TABLE_PREFIX."ticket_suspend_status_info WHERE ticket_id = '".$ticket_id."' AND end_suspension IS NULL AND act_flag = 1);
            COMMIT;";
        $result = db_query($end_suspension_query);
        if ($result) {
            error_log("Current active suspension for ".$ticket_id." was successfully terminated.");
        } else {
            error_log("Error while terminating current active suspension for ticket ".$ticket_id.":" . db_error());
        }
    }

    function close_suspension($ticket_id){
        if(!$this->isPluginActive()){
            return;
        }
        $close_suspension_query =
            "START TRANSACTION;
                UPDATE ".TABLE_PREFIX."ticket_suspend_status_info
                SET act_flag = 0
                WHERE tid IN (SELECT tid FROM ".TABLE_PREFIX."ticket_suspend_status_info WHERE ticket_id = '".$ticket_id."' AND act_flag = 1 AND end_suspension IS NOT NULL);
            COMMIT;";
        $result = db_query($close_suspension_query);
        if ($result) {
            error_log("All past suspensions for ticket ".$ticket_id." where successfully closed.");
        } else {
            error_log("Error while closing past suspensions for ticket ".$ticket_id.":" . db_error());
        }
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
}

$sla_plugin = new SLAPlugin();
$sla_plugin->bootstrap();
