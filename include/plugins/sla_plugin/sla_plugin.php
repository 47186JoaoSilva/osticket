<?php

require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.signal.php');
require_once(INCLUDE_DIR . 'class.app.php');
require_once(INCLUDE_DIR . 'class.dispatcher.php');

class SLAPlugin extends Plugin{
    private $ajax_tickets_did = 0;
    private $class_ticket_did = 0;
    
    function bootstrap() {
        //Signal quando o plugin é ativado ou desativado
        Signal::connect('model.updated', array($this, 'create_suspension_state'));
        Signal::connect('model.updated', array($this, 'create_suspensions_table'));
        Signal::connect('model.updated', array($this, 'do_ajax_tickets_patches'));
        Signal::connect('model.updated', array($this, 'do_class_ticket_patches'));

        //Signal quando o plugin é apagado
        Signal::connect('model.deleted', array($this, 'undo_class_ticket_patches'));
        Signal::connect('model.deleted', array($this, 'undo_ajax_tickets_patches'));
        Signal::connect('model.deleted', array($this, 'drop_suspensions_table'));
        Signal::connect('model.deleted', array($this, 'delete_suspension_state'));
    }
    
    function normalizeLineEndingsToCRLF($file_path) {
        $text = file_get_contents($file_path);

        if ($text === false) {
            //throw new Exception("Unable to read the file: $file_path");
        }
        
        $normalizedText = preg_replace('/\r\n|\r|\n/', "\r\n", $text);
        $result = file_put_contents($file_path, $normalizedText);
        if ($result === false) {
            //throw new Exception("Unable to write to the file: $file_path");
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
        $file_path = 'C:\xampp\htdocs\osticket\include\ajax.tickets.php';
        $searches = array(
            "function changeTicketStatus(".'$tid'.", ".'$status'.", ".'$id=0'.") {
        global ".'$thisstaff'.";

        if (!".'$thisstaff'.")
            Http::response(403, 'Access denied');
        elseif (!".'$tid'."
                || !(".'$ticket'."=Ticket::lookup(".'$tid'."))
                || !".'$ticket->checkStaffPerm'."(".'$thisstaff'."))
            Http::response(404, 'Unknown ticket #');

        ".'$role'." = ".'$ticket->getRole'."(".'$thisstaff'.");

        ".'$info'." = array();
        ".'$state'." = null;
        switch(".'$status'.") {
            case 'open':
            case 'reopen':
                ".'$state'." = 'open';
                break;",
            "function setTicketStatus(".'$tid'.") {
        global ".'$thisstaff'.", ".'$ost'.";

        if (!".'$thisstaff'.")
            Http::response(403, 'Access denied');
        elseif (!".'$tid'."
                || !(".'$ticket'."=Ticket::lookup(".'$tid'."))
                || !".'$ticket->checkStaffPerm'."(".'$thisstaff'."))
            Http::response(404, 'Unknown ticket #');

        ".'$errors'." = ".'$info'." = array();
        if (!".'$_POST['."status_id']
                || !(".'$status'."= TicketStatus::lookup(".'$_POST'."['status_id'])))
            ".'$errors'."['status_id'] = sprintf('%s %s',
                    __('Unknown or invalid'), __('status'));
        elseif (".'$status->getId()'." == ".'$ticket->getStatusId'."())
            ".'$errors'."['err'] = sprintf(__('Ticket already set to %s status'),
                    __(".'$status->getName'."()));
        elseif ((".'$role'." = ".'$ticket->getRole'."(".'$thisstaff'."))) {
            // Make sure the agent has permission to set the status
            switch(mb_strtolower(".'$status->getState'."())) {
                case 'open':
                    if (!".'$role->hasPerm'."(Ticket::PERM_CLOSE)
                            && !".'$role->hasPerm'."(Ticket::PERM_CREATE))
                        ".'$errors'."['err'] = sprintf(__('You do not have permission %s'),
                                __('to reopen tickets'));
                    break;
                case 'closed':
                    if (!".'$role->hasPerm'."(Ticket::PERM_CLOSE))
                        ".'$errors'."['err'] = sprintf(__('You do not have permission %s'),
                                __('to resolve/close tickets'));
                    break;
                case 'deleted':
                    if (!".'$role->hasPerm'."(Ticket::PERM_DELETE))
                        ".'$errors'."['err'] = sprintf(__('You do not have permission %s'),
                                __('to archive/delete tickets'));
                    break;",
            "function changeSelectedTicketsStatus(".'$status'.", ".'$id'."=0) {
        global ".'$thisstaff'.", ".'$cfg'.";

        if (!".'$thisstaff'.")
            Http::response(403, 'Access denied');

        ".'$state'." = null;
        ".'$info'." = array();
        switch(".'$status'.") {
            case 'open':
            case 'reopen':
                ".'$state'." = 'open';
                break;",
            "function setSelectedTicketsStatus(".'$state'.") {
        global ".'$thisstaff'.", ".'$ost'.";

        ".'$errors'." = ".'$info'." = array();
        if (!".'$thisstaff'." || !".'$thisstaff->canManageTickets'."())
            ".'$errors'."['err'] = sprintf('%s %s',
                    sprintf(__('You do not have permission %s'),
                        __('to mass manage tickets')),
                    __('Contact admin for such access'));
        elseif (!".'$_REQUEST'."['tids'] || !count(".'$_REQUEST'."['tids']))
            ".'$errors'."['err']=sprintf(__('You must select at least %s.'),
                    __('one ticket'));
        elseif (!(".'$status'."= TicketStatus::lookup(".'$_REQUEST'."['status_id'])))
            ".'$errors'."['status_id'] = sprintf('%s %s',
                    __('Unknown or invalid'), __('status'));
        elseif (!".'$errors'.") {
            // Make sure the agent has permission to set the status
            switch(mb_strtolower(".'$status->getState'."())) {
                case 'open':
                    if (!".'$thisstaff->hasPerm'."(Ticket::PERM_CLOSE, false)
                            && !".'$thisstaff->hasPerm'."(Ticket::PERM_CREATE, false))
                        ".'$errors'."['err'] = sprintf(__('You do not have permission %s'),
                                __('to reopen tickets'));
                    break;
                case 'closed':
                    if (!".'$thisstaff->hasPerm'."(Ticket::PERM_CLOSE, false))
                        ".'$errors'."['err'] = sprintf(__('You do not have permission %s'),
                                __('to resolve/close tickets'));
                    break;
                case 'deleted':
                    if (!".'$thisstaff->hasPerm'."(Ticket::PERM_DELETE, false))
                        ".'$errors'."['err'] = sprintf(__('You do not have permission %s'),
                                __('to archive/delete tickets'));
                    break;"
        );
        $replaces = array(
            "case 'suspend':
                ".'$state'." =  'suspended';
                break;",
            "case 'suspended':
                    if (!".'$role->hasPerm'."(Ticket::PERM_SUSPEND))
                        ".'$errors'."['err'] = sprintf(__('You do not have permission %s'),
                                __('to suspend tickets'));
                    break;",
            "case 'suspend':
                ".'$state'." =  'suspended';
                break;",
            "case 'suspended':
                    if (!".'$thisstaff->hasPerm'."(Ticket::PERM_SUSPEND, false))
                        ".'$errors'."['err'] = sprintf(__('You do not have permission %s'),
                                __('to suspend tickets'));
                    break;"
        );
        return array('file_path' => $file_path, 'searches' => $searches, 'replaces' => $replaces);
    }
    
    function do_ajax_tickets_patches(){
        if(!$this->isPluginActive() || $this->ajax_tickets_did === 1){
            return;
        }
        $this->ajax_tickets_did = 1;
        $patches = $this->ajax_tickets_list_of_patches();
        foreach ($patches['searches'] as $index=>$search) {
            $this->applyPatch($patches['file_path'], $this->patch_to($search, $search . "\n" . $patches['replaces'][$index]));
        }
        $this->normalizeLineEndingsToCRLF($patches['file_path']);
    }
    
    function undo_ajax_tickets_patches(){
        if($this->$ajax_tickets_did === 0){
            return;
        }
        $this->$ajax_tickets_did = 0;
        $patches = $this->ajax_tickets_list_of_patches();
        foreach ($patches['searches'] as $index=>$search) {
            $this->applyPatch($patches['file_path'], $this->patch_to($search . "\n" . $patches['replaces'][$index], $search));
        }
        $this->normalizeLineEndingsToCRLF($patches['file_path']);
    }

    function class_ticket_list_of_patches(){
        $file_path = 'C:\xampp\htdocs\osticket\include\class.ticket.php';
        $searches = array(
            "const PERM_DELETE   = 'ticket.delete';",
            "/* @trans */ 'Ability to delete tickets'),",
            "function isClosed() {
                 return \$this->hasState('closed');
            }",
            "// Double check permissions (when changing status)
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
        if(!$this->isPluginActive() || $this->class_ticket_did === 1){
            return;
        }
        $this->class_ticket_did = 1;
        $patches = $this->class_ticket_list_of_patches();
        foreach ($patches['searches'] as $index=>$search) {
            $this->applyPatch($patches['file_path'], $this->patch_to($search, $search . "\n" . $patches['replaces'][$index]));
        }   
        $this->normalizeLineEndingsToCRLF($patches['file_path']);
    }
    
    function undo_class_ticket_patches(){
        // Fechar todas as suspensões?
        if($this->class_ticket_did === 0){
            return;
        }
        $this->class_ticket_did = 0;
        $patches = $this->class_ticket_list_of_patches();
        foreach ($patches['searches'] as $index=>$search) {
            $this->applyPatch($patches['file_path'], $this->patch_to($search . "\n" . $patches['replaces'][$index], $search));
        }
        $this->normalizeLineEndingsToCRLF($patches['file_path']);
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
        // TODO (Dependendo do SLA ter em conta: horas laborais, fins de semana/feriados/dias úteis/dias ativos de SLA)
        // TODO TESTAR: Transformar diferença de minutos em horas decimais
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

        $interval = $begin_suspension->diff($end_date);
        $date_diff = (float)(date_diff(date_create($begin_suspension),(date_create($end_date))))->format("%a");
        $time_diff = (((int)($interval->format("%H")))*60) + ((int)($interval->format("%i")));

        $get_suspension_time_query = "SELECT suspension_time FROM ".TABLE_PREFIX."ticket_suspend_status_info WHERE ticket_id = '".$ticket_id."' AND act_flag = 1 AND end_suspension IS NOT NULL ORDER BY tid DESC;";
        $result = db_query($get_suspension_time_query);

        $suspension_time = 0;
        if ($result) {
            while ($row = db_fetch_array($result)) {
                $suspension_time = (int)$row['suspension_time'];
                break;
            }
        }

        $final_time = ((($date_diff * 8 * 60) + $time_diff) / 60) + $suspension_time;

        $end_suspension_query =
            "START TRANSACTION;
                UPDATE ".TABLE_PREFIX."ticket_suspend_status_info
                SET end_suspension = ".$end_date.", suspension_time = ROUND(".$final_time.", 2)
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
