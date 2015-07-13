<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include_once('/Users/constants.php');
include_once(HOME_PATH.'Scripts/Include/sweetfunctions.php');

// For our records:
// PB = Passed Ball
// WP = Wild Pitch
// SB = Stolen Base
// CS = Caught Stealing
// PO = Pick Off
// OA = Other Baserunning Advancement / Out
// DI = Defensive Indifference
// '%[(W.)\w+X]%' = Regex for Walk + Some Out

$where =
    "err_ct = 0
    AND event_tx not like '%PB%'
    AND event_tx not like '%WP%'
    AND event_tx not like '%SB%'
    AND event_tx not like '%CS%'
    AND event_tx not like '%PO%'
    AND event_tx not like '%OA%'
    AND event_tx not like '%DI%'
    AND not event_tx regexp '(W.)\w+X'
    ";
$where = "true";
$sql =
	"SELECT CAST(a.instances/b.instances as decimal(12,10)) as rate,
    	a.event_name,
    	a.start_outs,
    	a.start_bases,
    	a.end_outs,
    	a.end_bases,
    	a.runs_added
	FROM
  		(SELECT sum(a.instances) AS instances,
          	a.event_name,
          	a.outs AS start_outs,
          	a.outs + a.outs_added AS end_outs,
          	c.sim_tx AS start_bases,
         	d.sim_tx AS end_bases,
        	a.runs_added
   		FROM
   		  (SELECT count(1) AS instances,
   		          CASE WHEN (event_cd in(2,19)
   		             	AND battedball_cd = 'G') THEN 'ground_out'
   		 		 	WHEN (event_cd in(2,19)
   		             	AND battedball_cd != 'G') THEN 'fly_out'
   		 			WHEN event_cd = 3 THEN 'strikeout'
   		 			WHEN event_cd in(14,15,16) THEN 'walk'
   		 			WHEN event_cd = 20 THEN 'single'
   		 			WHEN event_cd = 21 THEN 'double'
   		 			WHEN event_cd = 22 THEN 'triple'
   		 			WHEN event_cd = 23 THEN 'home_run'
   		 			ELSE 'other'
   		 		END AS event_name,
   		 		outs_ct AS outs,
   		 		start_bases_cd,
   		 		end_bases_cd,
   		 		event_runs_ct AS runs_added,
   		 		event_outs_ct AS outs_added
			FROM events
			WHERE $where
   		    GROUP BY CASE WHEN (event_cd in(2,19)
   		                 AND battedball_cd = 'G') THEN 'ground_out'
   		 			WHEN (event_cd in(2,19)
   		                 AND battedball_cd != 'G') THEN 'fly_out'
   		 			WHEN event_cd = 3 THEN 'strikeout'
   		 			WHEN event_cd in(14,15,16) THEN 'walk'
   		 			WHEN event_cd = 20 THEN 'single'
   		 			WHEN event_cd = 21 THEN 'double'
   		 			WHEN event_cd = 22 THEN 'triple'
   		 			WHEN event_cd = 23 THEN 'home_run'
   		 		ELSE 'other' END,
   		 		outs_ct,
   		 		start_bases_cd,
   		 		end_bases_cd,
   		 		event_runs_ct ,
   		 		event_outs_ct) a
   		JOIN lkup_cd_bases c ON a.start_bases_cd = c.value_cd
		JOIN lkup_cd_bases d ON a.end_bases_cd = d.value_cd
		WHERE a.instances > 10
   		GROUP BY a.event_name,
   		       a.outs,
   		       a.outs + a.outs_added,
   		       c.sim_tx,
   		       d.sim_tx,
   		       a.runs_added) a

JOIN

	(SELECT sum(a.instances) AS instances,
	      a.event_name,
	      a.outs AS start_outs,
	      c.sim_tx AS start_bases
	FROM
	 	(SELECT count(1) AS instances,
	         CASE WHEN (event_cd in(2,19)
	                    AND battedball_cd = 'G') THEN 'ground_out'
			 	WHEN (event_cd in(2,19)
	                    AND battedball_cd != 'G') THEN 'fly_out'
				WHEN event_cd = 3 THEN 'strikeout'
				WHEN event_cd in(14,15,16) THEN 'walk'
				WHEN event_cd = 20 THEN 'single'
				WHEN event_cd = 21 THEN 'double'
				WHEN event_cd = 22 THEN 'triple'
				WHEN event_cd = 23 THEN 'home_run'
				ELSE 'other'
			END AS event_name,
			outs_ct AS outs,
			start_bases_cd,
			end_bases_cd,
			event_runs_ct,
			event_outs_ct
		FROM events
		WHERE $where
	  	GROUP BY CASE WHEN (event_cd in(2,19)
	                      AND battedball_cd = 'G') THEN 'ground_out'
					WHEN (event_cd in(2,19)
	                      AND battedball_cd != 'G') THEN 'fly_out'
					WHEN event_cd = 3 THEN 'strikeout'
					WHEN event_cd in(14,15,16) THEN 'walk'
					WHEN event_cd = 20 THEN 'single'
					WHEN event_cd = 21 THEN 'double'
					WHEN event_cd = 22 THEN 'triple'
					WHEN event_cd = 23 THEN 'home_run'
					ELSE 'other'
				END,
				outs_ct,
				start_bases_cd,
				end_bases_cd,
				event_outs_ct,
				event_runs_ct) a
		JOIN lkup_cd_bases c ON a.start_bases_cd = c.value_cd
		WHERE a.instances > 10
		GROUP BY a.event_name,
	       a.outs ,
	       c.sim_tx) b
			ON a.start_bases = b.start_bases
			AND a.start_outs = b.start_outs
			AND a.event_name = b.event_name";
$colheads = array(
	'rate',
	'event_name',
	'start_outs',
	'start_bases',
	'end_outs',
	'end_bases',
	'runs_added'
);
$results = exe_sql(DATABASE, $sql);
multi_insert(
	DATABASE,
	'at_bat_impact',
	$results,
	$colheads	
);

?>
