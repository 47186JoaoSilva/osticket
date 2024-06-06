<?php
set_include_path(get_include_path().PATH_SEPARATOR.dirname(__file__).'/include');
return [
    'id' => 'sla_plugin',
    'version' => '1.0',
    'name' => 'SLA Plugin',
    'author' => 'João Silva, Fábio Manuel, Leandro Duarte',
    'description' => 'Updates OsTickets in a way to allow ticket suspension',
    'plugin' => 'sla_plugin.php:SLAPlugin',
];

?>

