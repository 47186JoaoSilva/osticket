<?php
// Include necessary osTicket files
require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.signal.php');
require_once(INCLUDE_DIR . 'class.app.php');
require_once(INCLUDE_DIR . 'class.dispatcher.php');

// Define your plugin class
class FormsPlugin extends Plugin {
    // Override the bootstrap method
    function bootstrap() {
        // Hook into osTicket's dispatch mechanism
        $this->replaceTicketOpenFile();
        $this->replaceTicketViewFile();
        $this->replaceClassTicketFile();
        
        // Register callbacks to revert changes when the plugin is disabled or deleted
        /*Signal::connect('model.updated', array($this, 'restoreTicketOpenFile'));
        Signal::connect('model.updated', array($this, 'restoreTicketViewFile'));
        Signal::connect('model.updated', array($this, 'restoreClassTicketFile'));*/
        Signal::connect('model.deleted', array($this, 'restoreTicketOpenFile'));
        Signal::connect('model.deleted', array($this, 'restoreTicketViewFile'));
        Signal::connect('model.deleted', array($this, 'restoreClassTicketFile'));
    }

    // Function to replace ticket-open.inc.php with modified content
    function replaceTicketOpenFile() {
        $this->replaceFile(INCLUDE_DIR . 'staff/ticket-open.inc.php', 'ticket-open-modified.inc.php');
    }
    
    // Function to replace ticket-view.inc.php with modified content
    function replaceTicketViewFile() {
        $this->replaceFile(INCLUDE_DIR . 'staff/ticket-view.inc.php', 'ticket-view-modified.inc.php');
    }
    
    // Function to replace class.ticket.php with modified content
    function replaceClassTicketFile() {
        $this->replaceFile(INCLUDE_DIR . 'class.ticket.php', 'class.ticket-modified.php');
    }
    
    // Function to replace the content of a file with modified content
    function replaceFile($file_path, $modified_file_name) {
        $modified_file_path = __DIR__ . '/' . $modified_file_name;
        
        // Check if both files exist
        if (file_exists($file_path) && file_exists($modified_file_path)) {
            // Read the content of the modified file
            $modified_content = file_get_contents($modified_file_path);

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
}

// Instantiate and initialize the plugin
$forms_plugin = new FormsPlugin();
$forms_plugin->bootstrap();


    