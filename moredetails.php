<?php

// Total number of rooms
require_once("config.php");
require_once("classes/Mustache/Autoloader.php");
global $CFG;
Mustache_Autoloader::register();
$mustache = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader('templates')
        ));
$template = $mustache->loadTemplate('moredetails');

$wave = $_GET['wave'];


$params = [
    'graph' => loadGraph($wave),
    "wwwroot" => $CFG->wwwroot
];

//print_object($params);
echo $template->render($params);
