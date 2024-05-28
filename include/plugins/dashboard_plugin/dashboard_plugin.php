<?php
require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.signal.php');
require_once(INCLUDE_DIR . 'class.app.php');
require_once(INCLUDE_DIR . 'class.dispatcher.php');

class DashboardPlugin extends Plugin {
    /*function bootstrap() {
       
    }*/
    
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
}

$dashboard_plugin = new DashboardPlugin();
//$dashboard_plugin->bootstrap();

