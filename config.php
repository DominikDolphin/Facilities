<?php

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbhost    = '127.0.0.1';
$CFG->dbport    = 3306;
$CFG->dbname    = 'facilities';
$CFG->dbuser    = 'me';
$CFG->dbpass    = 'asdasd';

$CFG->wwwroot   = 'https://s1dev.ddns.net/washroom';

//Google Sheet
$CFG->serviceAccountFile = 'keys/facilitiessheet-ceea3f5eacc3.json';
$CFG->spreadsheetId = '1ooT6HPRxFqQ6_j_uyZOBXuZ_D0XbDjpe1Imdo9mdPaE';

//Debugging
$CFG->debug = true;

//DO NOT CHANGE ANY PARAMTERS BELOW
require_once('lib.php');
require_once('classes/database.php');
debug();


