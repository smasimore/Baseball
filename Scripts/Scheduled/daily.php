<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include('/Users/constants.php');
include(HOME_PATH . 'Scripts/Include/sweetfunctions.php');

const EMAIL_INTERVAL = 1800; // 5 minutes
const MAX_FREQUENCY = 30;

// global variables
$path = HOME_PATH . 'Scripts/Scripts/Daily/';
$masterStatus = 'In Progress';
$frequencyMap = array(
    'Hourly' => 3600,
);
$scriptArray = array();


$scriptArray = getScriptArray();
$last_status_email_time = time();
while (true) {
    runScripts();
    updateStatus();

    // print table and status    
    foreach ($scriptArray as $name => $script) {
        echo $name . ' ' . $script['status'] . "\n";
    }

    if (time() - $last_status_email_time
        >= EMAIL_INTERVAL || $masterStatus != 'In Progress') {
        $last_status_email_time = time();
        sendStatusEmail();
    }

    // Check if master script succeeded or failed
    if ($masterStatus != 'In Progress') {
        break;
    }

    // Run scripts/upate status every minute
    sleep(MAX_FREQUENCY);
}

function runScripts() {
    global $frequencyMap;
    global $scriptArray;

    foreach ($scriptArray as $script => $script_info) {
        // This should never actually happen
        if ($script_info['status'] == 'Failed') {
            echo 'ERROR: Script with failed status entered runScripts';
            return;
        }

        // If already in progress or (succeeded and daily), continue to
        // next script
        if ($script_info['status'] == 'In Progress' ||
            ($script_info['status'] == 'Succeeded' &&
                $script_info['frequency'] == 'Daily')) {
            continue;
        }

        // If succeeded and frequency not Daily (assumed to be higher
        // frequency), check if interval has passed and run, if not continue
        if ($script_info['status'] == 'Succeeded' &&
            $script_info['frequency'] != 'Daily') {
            $frequency_sec = $frequencyMap[$script_info['frequency']];
            if (time() > $frequency_sec + $script_info['last_start_time']) {
                executeScript($script, $script_info['script']);
            }
        }

        // If not started, check dependencies. If all there, update
        // status to be In Progress
        if ($script_info['status'] == 'Not Started') {
            $dependencies_filled = true;
            foreach ($script_info['dependencies'] as $dependency) {
                if ($scriptArray[$dependency]['status'] != 'Succeeded') {
                    $dependencies_filled = false;
                    break;
                }
            }

            // Dependencies not filled, continue to next script
            if ($dependencies_filled == false) {
                continue;
            }
            
            executeScript($script, $script_info['script']);
        }
    }
}

function executeScript($script, $script_path) {
    global $scriptArray, $path;

    // last piece for parallel calls
    $cmd = '/usr/bin/php ' . $path . $script_path . ' > /dev/null 2>/dev/null &';
    echo $cmd . "\n";
    shell_exec($cmd);
    $scriptArray[$script]['status'] = 'In Progress';
    $scriptArray[$script]['last_start_time'] = time();
}


function updateStatus() {
    global $masterStatus;
    global $scriptArray;

    $ds = date('Y-m-d');
    $sql =
        ' SELECT b.*'.
        ' FROM'.
        ' ('.
            ' SELECT table_name, max(ts) as last_update'.
            ' FROM table_status'.
            ' WHERE ds = "' . $ds . '"'.
            ' GROUP BY table_name'.
        ' ) a'.
        ' LEFT OUTER JOIN table_status b'.
        ' ON a.last_update = b.ts AND a.table_name = b.table_name';
    $status_array = exe_sql('baseball', $sql);

    // No tables have updated, all in progress
    if (!$status_array) {
        return $scriptArray;
    }

    // if only one row, returns an array rather than array of arrays
    if (!is_numeric(key($status_array))) {
        // make sure only looking at tables in scriptArray, has started, and ts > start time
        if (array_key_exists($status_array['table_name'], $scriptArray) &&
            strtotime($status_array['ts']) >= 
                $scriptArray[$status_array['table_name']]['last_start_time'] &&
                $scriptArray[$status_array['table_name']]['status'] != 'Not Started') {
            if ($status_array['num_rows'] > 0) {
                $scriptArray[$status_array['table_name']]['status'] = 'Succeeded';
            } else {
                $scriptArray[$status_array['table_name']]['status'] = 'Failed';
                echo 'SCRIPT FAILED';
                $masterStatus = 'Failed';
            }
        }
    } else {
        // Loop through each row and update status in scriptArray
        $scripts_succeeded = 0;
        foreach ($status_array as $status) {
            if (!array_key_exists($status['table_name'], $scriptArray) || 
                strtotime($status['ts']) < 
                    $scriptArray[$status['table_name']]['last_start_time'] ||
                $scriptArray[$status['table_name']]['status'] == 'Not Started') {
                continue;
            }

            // Not sure if need ! part, added just in case
            if ($status['num_rows'] == 0 || !$status['num_rows']) {
                $scriptArray[$status['table_name']]['status'] =
                    'Failed';
                $masterStatus = 'Failed';
            } else {
                $scriptArray[$status['table_name']]['status'] =
                    'Succeeded';
                $scripts_succeeded++;
            }
        }
        if ($scripts_succeeded == count($scriptArray)) {
            $masterStatus = 'Succeeded';
            echo 'SCRIPT SUCCEEDED';
        }
    }

    return $scriptArray;
}

function sendStatusEmail() {
    global $scriptArray;
    global $masterStatus;

    $body = '';
    $successful_scripts = 0;
    $failed_scripts = 0;
    $in_progress_scripts = 0;
    $not_started_scripts = 0;
    foreach ($scriptArray as $script => $script_info) {
        if ($script_info['status'] == 'Succeeded') {
            $successful_scripts++;
            $body .= $script . ': Succeeded' . "\n\n";
        } elseif ($script_info['status'] == 'Failed') {
            $failed_scripts++;
            $body .= $script . ': Failed ' .  "\n\n";
        } elseif ($script_info['status'] == 'In Progress') {
            $in_progress_scripts++;
            $body .= $script . ': In Progress' . "\n\n";
        } elseif ($script_info['status'] == 'Not Started') {
            $not_started_scripts++;
            $body .= $script . ': Not Started' . "\n";
            foreach ($script_info['dependencies'] as $dependency) {
                if ($scriptArray[$dependency]['status'] != 'Succeeded') {
                    $body .= '>>>>> Waiting for ' . $dependency . "\n";
                }
            }
            $body .= "\n";
        }
    }

    $subject = '';
    if ($masterStatus == 'Failed') {
        $subject = 'Script Failed';
    } elseif ($masterStatus == 'Succeeded') {
        $subject = 'Script Succeeded';
    } else {
        $subject = 'Succeeded: ' . $successful_scripts . '  In Progress: '
            . $in_progress_scripts . '  Not Started: ' . $not_started_scripts;
    }

    send_email($subject, $body);
}

// completed and succeeded attribute will be stored in mysql table
function getScriptArray() {
    return array(
        'odds' => array(
            'script' => 'odds.php',
            'dependencies' => array(),
            'status' => 'Not Started',
            'frequency' => 'Daily',
            'last_start_time' => 0,
        ),
        'players' => array(
            'script' => 'players.php',
            'dependencies' => array(),
            'status' => 'Not Started',
            'frequency' => 'Daily',
            'last_start_time' => 0,
        ),
        'espn_batting' => array(
            'script' => 'espn_batting.php',
            'dependencies' => array(
                'players'
            ),
            'status' => 'Not Started',
            'frequency' => 'Daily',
            'last_start_time' => 0,
        ),
        'espn_pitching' => array(
            'script' => 'espn_pitching.php',
            'dependencies' => array(
                'players'
            ),
            'status' => 'Not Started',
            'frequency' => 'Daily',
            'last_start_time' => 0,
        ),
        'espn_fielding' => array(
            'script' => 'espn_fielding.php',
            'dependencies' => array(
                'players'
            ),
            'status' => 'Not Started',
            'frequency' => 'Daily',
            'last_start_time' => 0,
        ),
        'lineups' => array(
            'script' => 'lineups.php',
            'dependencies' => array(
                'players',
            ),
            'dependencies' => array(),
            'status' => 'Not Started',
            'frequency' => 'Daily',
            'last_start_time' => 0,
        )
    );
}
?>
