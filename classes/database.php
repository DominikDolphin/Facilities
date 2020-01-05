<?php
require(__DIR__ . "/../config.php");
require(__DIR__ . "/PDO.class.php");

global $CFG;
$DB = new Db($CFG->dbhost, $CFG->dbport, $CFG->dbname, $CFG->dbuser, $CFG->dbpass);
