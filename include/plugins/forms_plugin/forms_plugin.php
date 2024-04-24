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
        Signal::connect('model.updated', array($this, 'addOrDeleteColumnsFromTable'));
        
        //Signal quando o plugin é apagado
        Signal::connect('model.deleted', array($this, 'restoreOrReplaceFiles'));
        Signal::connect('model.deleted', array($this, 'addOrDeleteColumnsFromTable'));     
        
    }
    
    function restoreOrReplaceFiles() {
        $this->restoreOrReplaceTicketOpenFile();
        $this->restoreOrReplaceTicketViewFile();
        $this->restoreOrReplaceClassTicketFile();
    }
    
    function restoreOrReplaceTicketOpenFile() {
        if($this->isPluginActive()== 1) {
            $this->replaceTicketOpenFile();
        }
        else {
            $this->restoreTicketOpenFile();
        }
    }
    
    function restoreOrReplaceTicketViewFile() {
        if($this->isPluginActive()== 1) {
            $this->replaceTicketViewFile();
        }
        else {
            $this->restoreTicketViewFile();
        }
    }
    
    function restoreOrReplaceClassTicketFile() {
        if($this->isPluginActive()== 1) {
            $this->replaceClassTicketFile();
        }
        else {
            $this->restoreClassTicketFile();
        }
    }
    
    function addOrDeleteColumnsFromTable() {
        if($this->isPluginActive()== 1) {
            $this->addColumnsToTable();
        }
        else {
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
        $query = "SELECT isactive FROM ost_plugin WHERE name = 'Forms Plugin'"; 

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
        // Execute SQL query to delete the column where textbox_name is not empty
        $query = "DELETE FROM ost_ticket WHERE textbox_name != ''";

        $result = db_query($query);

        // Execute the query
        if ($result) {
            error_log( "Column 'textbox_name' deleted successfully where textbox_name was not empty.");
        } else {
            error_log("Error deleting column 'textbox_name' from table where textbox_name was not empty: " . db_error());
        }
    }
    
}

// Instantiate and initialize the plugin
$forms_plugin = new FormsPlugin();
$forms_plugin->bootstrap();


    