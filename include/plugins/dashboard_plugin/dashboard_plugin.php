<?php
require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.signal.php');
require_once(INCLUDE_DIR . 'class.app.php');
require_once(INCLUDE_DIR . 'class.dispatcher.php');

class DashboardPlugin extends Plugin {
    function bootstrap() {
        //Signal quando o plugin é ativado ou desativado
        Signal::connect('model.updated', array($this, 'applyPatchOrRestoreFile'));
        
        //Signal quando o plugin é apagado
        Signal::connect('model.deleted', array($this, 'applyPatchOrRestoreFile'));
    }
    
    function applyPatchOrRestoreFile() {
        if($this->isPluginActive()) {
            if(!$this->isCodeAlreadyInserted(INCLUDE_DIR . 'staff/dashboard.inc.php', $this->dashboardIncPatch3)) {
                $this->applyPatchToClassReport();
                $this->applyPatchToDashboardInc();
            }
        } else {
            if($this->isCodeAlreadyInserted(INCLUDE_DIR . 'staff/dashboard.inc.php', $this->dashboardIncPatch3)) {
                $this->restoreClassReportFile();
                $this->restoreDashboardFile();
            }
        }
    }
    
    function applyPatchToClassReport() {
        $this->insertCodeIntoFile(
            INCLUDE_DIR . 'class.report.php', 
            $this->classReportPatch1, 
            'var $format;', 
            'function getStartDate($format=null, $translate=true) {'
        );
        $this->insertCodeIntoFile(
            INCLUDE_DIR . 'class.report.php', 
            $this->classReportPatch2, 
            'while ($row = db_fetch_row($res)) $events[] = __($row[0]);', 
            '# TODO: Handle user => db timezone offset'
        );
        $this->insertCodeIntoFile(
            INCLUDE_DIR . 'class.report.php', 
            $this->classReportPatch3, 
            '$plots[__($row[0])][] = (int)$row[2];', 
            'function getTabularData($group=\'dept\') {'
        );
        $this->insertCodeIntoFile(
            INCLUDE_DIR . 'class.report.php', 
            $this->classReportPatch4, 
            '$times = $times->filter(array(\'staff_id__gt\'=>0))->filter($Q);', 
            '# XXX: Die if $group not in $groups'
        );
    }
    
    function applyPatchToDashboardInc() {
        $this->insertCodeIntoFile(
            INCLUDE_DIR . 'staff/dashboard.inc.php', 
            ', $_POST[\'tickets_per_page\']);', 
            '$report = new OverviewReport($_POST[\'start\'], $_POST[\'period\']', 
            '$plots = $report->getPlotData();'
        );
        $this->insertCodeIntoFile(
            INCLUDE_DIR . 'staff/dashboard.inc.php', 
            $this->dashboardIncPatch2, 
            '<?php echo csrf_token(); ?>', 
            '            <button class="green button action-button muted" type="submit">'
        );
        $this->insertCodeIntoFile(
            INCLUDE_DIR . 'staff/dashboard.inc.php', 
            $this->dashboardIncPatch3, 
            '<script>', 
            '</script>'
        );
    }
    
    function restoreClassReportFile() {
        $this->restoreFile(INCLUDE_DIR . 'class.report.php', 'backup_files/class.report-backup.php');
    }
    
    function restoreDashboardFile() {
        $this->restoreFile(INCLUDE_DIR . 'staff/dashboard.inc.php', 'backup_files/dashboard-backup.inc.php');
    }
    
    function restoreFile($file_path,$backup_path) {
        $backup_file_path = __DIR__ . '\backup_files' . '/' . basename($backup_path);
        
        if (file_exists($backup_file_path)) {
            copy($backup_file_path, $file_path);
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

    function isCodeAlreadyInserted($filePath, $codeSnippet) {
        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            throw new Exception("Failed to read file at $filePath");
        }

        return strpos($fileContent, $codeSnippet) !== false;
    }
    
    static function getTicketsData($start,$stop,$ticketsPerPage) {
        $query = "SELECT t.source, t.number, t.created, d.name as dept_name, c.subject 
            FROM ost_ticket t
            JOIN ost_ticket__cdata c ON t.ticket_id = c.ticket_id
            JOIN ost_department d ON t.dept_id = d.id
            WHERE t.created BETWEEN $start AND $stop
            AND (t.closed IS NULL OR t.reopened IS NOT NULL AND t.reopened > t.closed)
            ORDER BY t.created ASC
            LIMIT $ticketsPerPage";

        $result = db_query($query);

        if ($result) {
            $ticketsData = [];
            while ($row = db_fetch_array($result)) {
                $ticketsData[] = [
                    $row['number'] . " " . $row['subject'],
                    $row['created'],
                    $row['dept_name'],
                    $row['source']
                ];
            }
            return $ticketsData;
        } else {
            error_log("Error fetching ticket data from ost_ticket and ost_ticket__cdata tables");
            return false;
        }
    }
    
    function isPluginActive() {
        $query = "SELECT isactive FROM ost_plugin WHERE name = 'Dashboard Plugin'"; 

        $result = db_query($query);

        if ($result) {
            $row = db_fetch_array($result);
            return $row['isactive'];
        } else {
            error_log("Error fetching isactive from ost_plugin table");
            return false;
        }
    }
        
    public $classReportPatch1 = '
    var $tickets_per_page;
    function __construct($start, $end="now", $tickets_per_page=null, $format=null) {
        global $cfg;
        $this->start = Format::sanitize($start);
        $this->end = array_key_exists($end, self::$end_choices) ? $end : "now";
        $this->format = $format ?: $cfg->getDateFormat(true);
        $this->tickets_per_page  = is_numeric($tickets_per_page) ? (int)$tickets_per_page : 10;
    }
    ';
    
    public $classReportPatch2 = '
    $events[] = "created-closed";
    ';
    
    public $classReportPatch3 = '}
        foreach (array_diff($events, $slots) as $slot)
            $plots[$slot][] = 0;

        
        if (isset($plots[\'created\'])) {
            for ($i = 0; $i < sizeof($plots[\'created\']); $i++) {
                $created = $plots[\'created\'][$i] ?? 0;
                $closed = $plots[\'closed\'][$i] ?? 0;
                $plots[\'created-closed\'][$i] = $created - $closed;
            }
        }

        return array("times" => $times, "plots" => $plots, "events" => $events);
    }

    function enumTabularGroups() {
        return array("dept"=>__("Department"), "topic"=>__("Topics"),
            # XXX: This will be relative to permissions based on the
            # logged-in-staff. For basic staff, this will be \'My Stats\'
            "staff"=>__("Agent"), "ticket"=>__("Tickets"),);
    }
    ';
    
    public $classReportPatch4 = '
            break;
        case \'ticket\':
            $headers = array(__(\'Old Runners\'));
            $dash_headers = array(__(\'Created At\'),__(\'Department\'), __(\'Source\'));
            $rows = DashboardPlugin::getTicketsData($start,$stop, $this->tickets_per_page);
            return array("columns" => array_merge($headers, $dash_headers),
                     "data" => $rows);
            break;
        default:
    ';
    
    public $dashboardIncPatch2 = '
            <label>
                <?php echo __( \'Report timeframe\'); ?>:
                <input type="text" class="dp input-medium search-query"
                    name="start" placeholder="<?php echo __(\'Last month\');?>"
                    value="<?php
                        echo Format::htmlchars($report->getStartDate());
                    ?>" />
            </label>
            <label>
                <?php echo __(\'period\');?>:
                <select name="period">
                    <?php foreach ($report::$end_choices as $val=>$desc)
                            echo "<option value=\'$val\'>" . __($desc) . "</option>"; ?>
                </select>
            </label>
            <label>
                <?php echo __(\'Tickets per page\');?>:
                <select name="tickets_per_page">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
            </label>
    ';
    
    public $dashboardIncPatch3 = '
        $.drawPlots(<?php echo JsonDataEncoder::encode($report->getPlotData()); ?>);
        // Set Selected Period For Dashboard Stats and Export
        <?php if ($report && $report->end && $report->tickets_per_page) { ?>
            $("div#basic_search select option").each(function(){
                // Remove default selection
                if ($(this)[0].selected)
                    $(this).removeAttr(\'selected\');
                if ($(this).val() == "<?php echo $report->end; ?>") 
                    $(this).attr("selected","selected");
                if ($(this).val() == "<?php echo $report->tickets_per_page; ?>") 
                    $(this).attr("selected", "selected");
            });
        <?php } ?>
    ';
}

$dashboard_plugin = new DashboardPlugin();
$dashboard_plugin->bootstrap();

