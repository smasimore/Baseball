<?php
//Copyright 2014, Saber Tooth Ventures, LLC
ini_set('memory_limit', '-1');
ini_set('mysqli.reconnect', '1');
ini_set('mysqli.connect_timeout', '-1');
ini_set('default_socket_timeout', '-1');
ini_set('max_execution_time', '-1');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/sweetfunctions.php');

const SCRIPTS_2014 = HOME_PATH.'Scripts/2014/';
const SCRIPTS = HOME_PATH.'Scripts/';
const SIM = HOME_PATH.'Simulation/';

$lineups = '/usr/bin/php ' . SCRIPTS_2014 . 'lineups_2014.php'; //. ' > /dev/null 2>/dev/null &';
shell_exec($lineups);

$sim = '/usr/bin/php ' . SCRIPTS_2014 . 'sim_nomagic_2014.php';
shell_exec($sim);

$odds = '/usr/bin/php ' . SCRIPTS . 'pullOdds.php';
shell_exec($odds);

$scores = '/usr/bin/php ' . SCRIPTS . 'pullScores.php';
shell_exec($scores);

$sql = '/usr/bin/php ' . SIM . 'sql_to_csv.php' . ' -t sim_nomagic_2014';
shell_exec($sql);

$run_sim = '/usr/local/bin/python ' . SIM . 'main.py 5000 0';
//shell_exec($run_sim);

$csv = '/usr/bin/php ' . SIM . 'csv_to_sql.php' . ' -t sim_output_nomagic_2013';
//shell_exec($csv);

$run_sim_2014 = '/usr/local/bin/python ' . SIM . 'main.py 5000 100';
//shell_exec($run_sim_2014);

$csv_2014 = '/usr/bin/php ' . SIM . 'csv_to_sql.php' . ' -t sim_output_nomagic_2014';
//shell_exec($csv_2014);

$run_sim_pitchertotal_2014 = '/usr/local/bin/python ' . SIM . '2copy_main.py 5000 100';
shell_exec($run_sim_pitchertotal_2014);

$csv_pitchertotal_2014 = '/usr/bin/php ' . SIM . 'csv_to_sql.php' . ' -t sim_output_nomagic_50total_50pitcher_histrunning_2014';
shell_exec($csv_pitchertotal_2014);

$betting = '/usr/bin/php ' . SIM . 'betting_decision.php';
shell_exec($betting);



?>
