<?php

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbhost    = 'localhost';
$CFG->dbport    = 3306;
$CFG->dbname    = 'classrooms';
$CFG->dbuser    = 'dominik';
$CFG->dbpass    = 'asdasd';

$CFG->wwwroot   = 'http://patrick.biz/Facilities';

//Google Sheet
$CFG->serviceAccountFile = 'keys/facilitiessheet-ceea3f5eacc3.json';
//$CFG->spreadsheetId = '1ooT6HPRxFqQ6_j_uyZOBXuZ_D0XbDjpe1Imdo9mdPaE';
$CFG->spreadsheetId = '1AZPNmLef_PVjnjkCC-KsX6APmL7F2zfKDxv7yAsi-bA';

//Debugging
$CFG->debug = true;

//DO NOT CHANGE ANY PARAMTERS BELOW
require_once('lib.php');
require_once('classes/database.php');
debug();


