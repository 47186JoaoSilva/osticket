<?php
set_include_path(get_include_path().PATH_SEPARATOR.dirname(__file__).'/include');
return [
    'id' => 'dashboard_plugin',
    'version' => '1.0',
    'name' => 'Dashboard Plugin',
    'author' => 'João Silva, Fábio Manuel, Leandro Duarte',
    'description' => 'Customizes the dashboard in osTicket.',
    'plugin' => 'dashboard_plugin.php:DashboardPlugin',
];

?>

