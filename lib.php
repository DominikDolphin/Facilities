<?php

function debug()
{
    global $CFG;
    if ($CFG->debug) {
        ini_set("display_errors", 1);
        error_reporting(E_ALL);
    }
}

function getGoogleSheetData()
{
    global $CFG, $DB;
    require 'googleapi/vendor/autoload.php';
    /**
     * Set here the full path to the private key .json file obtained when you
     * created the service account. Notice that this path must be readable by
     * this script.
     */
    //Keys
    $service_account_file = $CFG->serviceAccountFile;
    /**
     * This is the long string that identifies the spreadsheet. Pick it up from
     * the spreadsheet's URL and paste it below.
     */
    //Test spreadsheet
    //$spreadsheet_id = '1cTqXm93vjGvNTn-CBU5B37HSdf09AHtOqYaZi3DeHQs';
    //Washroom Spreadsheet
    $spreadsheet_id = $CFG->spreadsheetId;
    /**
     * This is the range that you want to extract out of the spreadsheet. It uses
     * A1 notation. For example, if you want a whole sheet of the spreadsheet, then
     * set here the sheet name.
     *
     * @see https://developers.google.com/sheets/api/guides/concepts#a1_notation
     */
    $spreadsheet_range = 'A2:G'; //Added
    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $service_account_file);
    $client = new Google_Client();
    $client->useApplicationDefaultCredentials();
    $client->addScope(Google_Service_Sheets::SPREADSHEETS_READONLY);
    $service = new Google_Service_Sheets($client);
    $result = $service->spreadsheets_values->get($spreadsheet_id, $spreadsheet_range);
    $data = $result->getValues();
    // print_object($data);
    $sum = 0;

    $DB->query('truncate sheets');

    foreach ($data as $key => $value) {

        $params = [
            'name' => $value[0],
            'roomnumber' => $value[1],
            'wave' => $value[2],
            'year_phase' => $value[3] //added
        ];
        // print_object($key);

        //Added +1 because of phase
        if (isset($value[3 + 1]) && trim($value[3 + 1]) != '') {
            $params['startdate'] = strtotime($value[3 + 1]);
        } else {
            $params['startdate'] = 0;
        }
        if (isset($value[4 + 1]) && trim($value[4 + 1]) != '') {
            $params['enddate'] = strtotime($value[4 + 1]);
        } else {
            $params['enddate'] = 0;
        }
        if (isset($value[5 + 1]) && trim($value[5 + 1]) != '') {
            $params['status'] = $value[5 + 1];
        } else {
            $params['status'] = 'Identified';
        }

        //    print_object($params);
        $DB->insert('sheets', $params);
    }
}

/**
 * Returns array for all wave data
 * @global \stdClass $DB
 * @return array
 */
function getWaves()
{
    global $DB;
    $countRows = "SELECT count(*) AS count FROM sheets WHERE status !=  'Cancelled' ";
    $result = $DB->query($countRows);

    $wavesSQL = "SELECT DISTINCT(wave) AS wave FROM sheets";
    //$wavesSQL = "SELECT DISTINCT wave, year_phase AS wave FROM sheets"; // Added
    $waves = $DB->query($wavesSQL);

    foreach ($waves as $key => $value) {


        $wavesArray = [];
        for ($i = 0; $i < count($waves) + 1; $i++) {
            if ($i == 0) {
                $wavesArray[$i]['id'] = $i;
                $wavesArray[$i]['wave'] = '';
                $wavesArray[$i]['graph'] = loadGraph($i);
            } else {
                $wavesArray[$i]['id'] = $i;
                $wavesArray[$i]['wave'] = $waves[$i - 1]['wave'];
                $wavesArray[$i]['graph'] = loadGraph($waves[$i - 1]['wave'], $i);

                $phaseCount = $DB->query('SELECT DISTINCT(year_phase) as phases FROM sheets WHERE wave = ' . $waves[$i - 1]['wave'] . ' ORDER BY phases DESC LIMIT 1');
                // print_object($phaseCount);

                $x = 1;
                $finishBy = $x + $phaseCount[0]['phases'];
                $i++;
                $waveValue = $waves[$i - 2]['wave'];
                while ($x < $finishBy) {
                    $phases = $DB->query('SELECT * FROM sheets WHERE wave=' . $waveValue  . ' AND year_phase=' . $x);
                    foreach ($phases as $key => $phase) {
                        $wavesArray[$i]['id'] = $x;
                        $wavesArray[$i]['wave'] = $phase['wave'] . ' Phase: ' . $x;
                        $wavesArray[$i]['graph'] = loadGraph($phase['wave'], $x);                        

                    }
                    $i++;
                    $x++;
                }
            }
        }

    }



    return $wavesArray;
}

/**
 * Returns an array of all graph data for Javascript
 * @global \stdClass $DB
 * @param int $wave
 * @param int $reference
 * @return array
 */
function loadGraph($wave, $reference = 0, $phase = false)
{
    global $DB;

    $count = 0;
    $to_be_scheduled = 0;
    $in_progress = 0;
    $completed = 0;
    $scheduled = 0;
    $cancelled = 0;
    $postponed = 0;
    $tentatively_scheduled = 0;
    $unknown_count = 0;
    $pending = 0;

    // Total number of rooms
    $sql = "SELECT * FROM sheets ";
    if ($wave != 0 && !$phase) {
        $sql .= "WHERE wave =  $wave";
    } else {
        $sql .= "WHERE wave =  $wave";
    }

    $result = $DB->query($sql);
    //Get all data based on wave
    $tableData = [];
    $i = 0;
    if (count($result) > 0) {
        //while ($row = $result->fetch_assoc()) {
        foreach ($result as $key => $row) {
            $count++;
            //print_object($row);
            switch (strtolower($row['status'])) {
                case 'to complete':
                    $to_be_scheduled++;
                    $color = 'primary';
                    $text = '<i class="fa fa-calendar" aria-hidden="true"></i> To be scheduled';
                    break;
                case 'completed':
                    $completed++; // 
                    $color = 'success';
                    if ($row['enddate'] != 0) {
                        $endDate = 'Completed on ' . date('Y-m-d', $row['enddate']);
                    } else {
                        $endDate = 'Completed - No date entered';
                    }
                    $text = $endDate;
                    break;
                case 'scheduled':
                    $scheduled++; //
                    $color = 'info';
                    if ($row['startdate'] != 0) {
                        $startDate = 'Scheduled for ' . date('Y-m-d', $row['startdate']);
                    } else {
                        $startDate = 'Scheduled - no start date entered';
                    }
                    $text = $startDate;
                    break;
                case 'postponed':
                    $postponed++; //
                    $color = 'warning';
                    $text = '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Postponed';
                    break;
                case 'in progress':
                    $in_progress++; //
                    $color = 'secondary';
                    $text = 'In progress';
                    break;
                case 'cancelled':
                    $cancelled++; //Wasn't there in his
                    $color = 'danger';
                    $text = '<i class="fa fa-ban" aria-hidden="true"></i> Cancelled';
                    break;

                default:
                    $to_be_scheduled++; //$unknown_count
                    $color = 'primary';
                    $text = '<i class="fa fa-calendar" aria-hidden="true"></i> To be scheduled';
                    break;
            }
            $tableData[$i]['room'] = $row['name'] . ' ' . $row['roomnumber'];
            $tableData[$i]['status'] = $row['status'];
            $tableData[$i]['badgeText'] = $text;
            $tableData[$i]['color'] = $color;
            $i++;
        }
    }

    if ($wave == 0) {
        $waveText = 'All years';
        $totalRoomsSql = "SELECT count(*) AS count FROM sheets";
        $totalQuery = $DB->query($totalRoomsSql);
        $totalRooms = $totalQuery[0]["count"];
    } else {
        $waveText = 'Year ' . $wave;
        $totalRoomsSql = "SELECT count(*) AS count FROM sheets WHERE wave = $wave ";
        $totalQuery = $DB->query($totalRoomsSql);
        $totalRooms = $totalQuery[0]["count"];
    }

    //Get percentage
    $completedPercentage = ceil(($completed / $totalRooms) * 100);
    $toCompletePercentage = ceil(($to_be_scheduled / $totalRooms) * 100);
    $inProgressPercentage = ceil(($in_progress / $totalRooms) * 100);
    $scheduledPercentage = ceil(($scheduled / $totalRooms) * 100);
    $postponedPercentage = ceil(($postponed / $totalRooms) * 100);

    $percent = ceil(($completed / $totalRooms) * 100);

    switch ($percent) {
        case 100:
            $backGroundColor = 'bg-success';
            break;
        case 0:
            $backGroundColor = 'bg-danger';
            break;
        default:
            $backGroundColor = 'bg-warning';
            break;
    }

    $graphData = [];
    $graphData[0]['reference'] = $reference;
    $graphData[0]['percent'] = $percent . '% DONE';
    $graphData[0]['bgColor'] = $backGroundColor;
    $graphData[0]['pending'] = $pending;
    $graphData[0]['toComplete'] = $to_be_scheduled;
    $graphData[0]['completed'] = $completed;
    $graphData[0]['scheduled'] = $scheduled;
    $graphData[0]['postponed'] = $postponed;
    $graphData[0]['inProgress'] = $in_progress;
    $graphData[0]['cancelled'] = $cancelled;
    $graphData[0]['waveText'] = $waveText;
    $graphData[0]['waveTotalRooms'] = $totalRooms;
    $graphData[0]['completedPercentage'] = $completedPercentage;
    $graphData[0]['toCompletePercentage'] = $toCompletePercentage;
    $graphData[0]['inProgressPercentage'] = $inProgressPercentage;
    $graphData[0]['scheduledPercentage'] = $scheduledPercentage;
    $graphData[0]['postponedPercentage'] = $postponedPercentage;
    $graphData[0]['data'] = $tableData;

    return $graphData;
}

/**
 * Print a beautiful/readable array
 * @param object $object
 */
function print_object($object)
{
    echo "<pre>";
    print_r($object);
    echo "</pre>";
}
