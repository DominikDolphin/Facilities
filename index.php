<?php

// Total number of rooms
require_once("config.php");
require_once("classes/Mustache/Autoloader.php");
global $CFG;
getGoogleSheetData();
Mustache_Autoloader::register();
$mustache = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader('templates')
        ));
$template = $mustache->loadTemplate('index');

$params = [
    'waves' => getWaves(),
    "wwwroot" => $CFG->wwwroot
];

//print_object($params);
echo $template->render($params);
