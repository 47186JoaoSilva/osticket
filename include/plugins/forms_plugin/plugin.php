<?php
set_include_path(get_include_path().PATH_SEPARATOR.dirname(__file__).'/include');
return [
    'id' => 'forms_plugin',
    'version' => '1.0',
    'name' => 'Forms Plugin',
    'author' => 'João Silva, Fábio Manuel, Leandro Duarte',
    'description' => 'Customizes the ticket form in osTicket.',
    'plugin' => 'forms_plugin.php:FormsPlugin',
];

?>

