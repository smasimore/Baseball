<?php

if (!defined('DATABASE')) {
	include('/Users/constants.php');
}

/*
########################################################################
Copyright 2007, Michael Schrenk
   This software is designed for use with the book,
   "Webbots, Spiders, and Screen Scarpers", Michael Schrenk, 2007 No Starch Press, San Francisco CA

W3C® SOFTWARE NOTICE AND LICENSE

This work (and included software, documentation such as READMEs, or other
related items) is being provided by the copyright holders under the following license.
 By obtaining, using and/or copying this work, you (the licensee) agree that you have read,
 understood, and will comply with the following terms and conditions.

Permission to copy, modify, and distribute this software and its documentation, with or
without modification, for any purpose and without fee or royalty is hereby granted, provided
that you include the following on ALL copies of the software and documentation or portions thereof,
including modifications:
   1. The full text of this NOTICE in a location viewable to users of the redistributed
      or derivative work.
   2. Any pre-existing intellectual property disclaimers, notices, or terms and conditions.
      If none exist, the W3C Software Short Notice should be included (hypertext is preferred,
      text is permitted) within the body of any redistributed or derivative code.
   3. Notice of any changes or modifications to the files, including the date changes were made.
      (We recommend you provide URIs to the location from which the code is derived.)

THIS SOFTWARE AND DOCUMENTATION IS PROVIDED "AS IS," AND COPYRIGHT HOLDERS MAKE NO REPRESENTATIONS OR
WARRANTIES, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO, WARRANTIES OF MERCHANTABILITY OR FITNESS
FOR ANY PARTICULAR PURPOSE OR THAT THE USE OF THE SOFTWARE OR DOCUMENTATION WILL NOT INFRINGE ANY THIRD
PARTY PATENTS, COPYRIGHTS, TRADEMARKS OR OTHER RIGHTS.

COPYRIGHT HOLDERS WILL NOT BE LIABLE FOR ANY DIRECT, INDIRECT, SPECIAL OR CONSEQUENTIAL DAMAGES ARISING OUT
OF ANY USE OF THE SOFTWARE OR DOCUMENTATION.

The name and trademarks of copyright holders may NOT be used in advertising or publicity pertaining to the
software without specific, written prior permission. Title to copyright in this software and any associated
documentation will at all times remain with copyright holders.
########################################################################
*/

########################################################################
#
# LIB_mysql.php     MySQL database Routines
#
#-----------------------------------------------------------------------
# FUNCTIONS
#
#    insert()
#               Inserts a row into database,
#               as defined by a keyed array
#
#    update()
#               Updates an existing row in a database,
#               as defined by a keyed array and a row index
#
#    exe_sql()
#               Executes a SQL command and return a result set
#
########################################################################

/***********************************************************************
MySQL Constants (scope = global)
----------------------------------------------------------------------*/
define("MYSQL_ADDRESS", "localhost");          // Define the IP address of your MySQL Server
define("MYSQL_USERNAME", "root");         // Define your MySQL user name
define("MYSQL_PASSWORD", DB_PASSWORD);         // Define your MySQL password
// Cert edited this out since it's defined in constants.php
//define("DATABASE", DATABASE);               // Define your default database
define("SUCCESS", true);              // Successful operation flag
define("FAILURE", false);             // Failed operation flag

if(strlen(MYSQL_ADDRESS) + strlen(MYSQL_USERNAME) + strlen(MYSQL_PASSWORD) + strlen(MYSQL_ADDRESS) + strlen(DATABASE) == 0)
    echo "WARNING: MySQL not configured.<br>\n";

// Logging for debugging
function debug_log($function, $table, $data_length) {
	$time = time();
	$log_data = " \n SCRIPT, $time, $function, $table, $data_length"; 
	file_put_contents(
		HOME_PATH.'Logging/mysql_log.txt',
		$log_data, 
		FILE_APPEND
	);
}

/***********************************************************************
Database connection routine (only used by routines in this library
----------------------------------------------------------------------*/
function connect_to_database()
	{
	return(mysqli_connect(MYSQL_ADDRESS, MYSQL_USERNAME, MYSQL_PASSWORD));
	}

/***********************************************************************
insert($database, $table, $data_array)
-------------------------------------------------------------
DESCRIPTION:
        Inserts a row into database as defined by a keyed array
INPUT:
        $database     Name of database (where $table is located)
        $table        Table where row insertion occurs
        $data_array   A keyed array with defines the data to insert
                      (i.e. $data_array['column_name'] = data)
RETURNS
        SUCCESS or FAILURE
***********************************************************************/
function insert($database, $table, $data_array)
	{

    // LOGGING
	debug_log('insert', $table, count($data_array));

	# Connect to MySQL server and select database
	$attempts = 0;

	$mysql_connect = connect_to_database();

	echo '========'."\n";
	while ($attempts < 10 && mysqli_connect_errno()) {
		$mysql_connect = connect_to_database();
		$attempts ++;
	}
	if ($attempts == 10) {
		debug_log('FAILED', 'FAILED', 'FAILED');
		printf("Connect failed: %s\n", mysqli_connect_error());
		echo '////////////////////////////////////////////////'."\n";
		echo 'THIS FAILED 10 TIMES!!'."\n";
		echo 'sudo /Library/StartupItems/MySQLCOM/MySQLCOM restart'."\n";
		echo '////////////////////////////////////////////////'."\n";
		email("RESTART MYSQL", "sudo /Library/StartupItems/MySQLCOM/MySQLCOM restart");
		return false;
	}

    	mysqli_select_db($mysql_connect, $database);

    # Create column and data values for SQL command
    foreach ($data_array as $key => $value)
        {
	$tmp_col[] = $key;
	if ($value === null || $value === "") {
		$tmp_dat[] = "NULL";
	} else {
		$value = trim($value);
		// Make sure there are no foreign charactars
		if (mb_detect_encoding($value) !== 'ASCII') {
			$value = 0;
			echo 'There is a foreign charactar check LIB_mysql_updatedbyus to turn off this error message'."\n".
				'Currently this is defaulted to 0'."\n";
			send_email("Foreign Character", "You can turn this off in LIB_mysql_updatedbyus.php", "d");
		}
		$tmp_dat[] = "'$value'";
	}
     }
     $columns = join(",", $tmp_col);
     $data = join(",", $tmp_dat);

    # Create and execute SQL command
     $sql = "INSERT INTO ".$table."(".$columns.")VALUES(". $data.")";
        $result = mysqli_query($mysql_connect, $sql);

    # Report SQL error, if one occured, otherwise return result
    if(mysqli_error($mysql_connect))
    {
		echo mysqli_error($mysql_connect);
     // smas trying to fix mysql server went away error - added 09/14/14
     mysqli_close($mysql_connect);
		return false;
        $result = "";
        }
    else
        {
     // smas trying to fix mysql server went away error - added 09/14/14
     mysqli_close($mysql_connect);
		# return $result;
		return true;
		}

	}

/***********************************************************************
multi_insert($database, $table, $data_array, $col_heads)
************************************************************************/

function multi_insert($database, $table, $data_array, $colheads) {
    // LOGGING
	debug_log('multi_insert', $table, count($data_array));

	# Connect to MySQL server and select database
	$attempts = 0;

	$mysql_connect = connect_to_database();

	echo '========'."\n";
	while ($attempts < 10 && mysqli_connect_errno()) {
		$mysql_connect = connect_to_database();
		$attempts ++;
	}
	if ($attempts == 10) {
		debug_log('FAILED', 'FAILED', 'FAILED');
		printf("Connect failed: %s\n", mysqli_connect_error());
		echo '////////////////////////////////////////////////'."\n";
		echo 'THIS FAILED 10 TIMES!!'."\n";
		echo 'sudo /Library/StartupItems/MySQLCOM/MySQLCOM restart'."\n";
		echo '////////////////////////////////////////////////'."\n";
		email("RESTART MYSQL", "sudo /Library/StartupItems/MySQLCOM/MySQLCOM restart");
		return false;
	}

	mysqli_select_db($mysql_connect, $database);

	// Create INSERT statement based on colheads
	if (!in_array('ds', $colheads)) {
		exit('INSERT FAILED: Cannot use multi_insert unless you add ds to your data/colheads'."\n");
	}
	$colheads_insert = implode(',', $colheads);
	$sql = array(); 
	foreach ($data_array as $row) {
		$insert_row = array();
		foreach ($colheads as $col) {
			$insert_data = null;
			$insert_data = $row[$col];
			if (!$insert_data) {
				$insert_row[] = 'NULL';
			} else if (mb_detect_encoding($insert_data) !== 'ASCII') {
            	$insert_row[] = 'NULL';
            	echo 'There is a foreign charactar check LIB_mysql_updatedbyus to turn off this error message'."\n".
                	'Currently this is defaulted to NULL'."\n";
            	send_email("Foreign Character", "You can turn this off in LIB_mysql_updatedbyus.php", "d");
        	} else {
				$insert_row[] = "'$insert_data'";
			}
		}
		$sql[] = '('.implode(',', $insert_row).')';
	}

    # Create and execute SQL command
    $final_sql = "INSERT INTO $table ($colheads_insert) VALUES ".implode(',', $sql);
	$result = mysqli_query($mysql_connect, $final_sql);

    # Report SQL error, if one occured, otherwise return result
    if (mysqli_error($mysql_connect)) {
		echo mysqli_error($mysql_connect);
     // smas trying to fix mysql server went away error - added 09/14/14
     mysqli_close($mysql_connect);
		return false;
        $result = "";
    } else {
     // smas trying to fix mysql server went away error - added 09/14/14
     	mysqli_close($mysql_connect);
		# return $result;
		return true;
	}
}

/***********************************************************************
update($database, $table, $data_array, $key_column, $id)
-------------------------------------------------------------
DESCRIPTION:
        Inserts a row into database as defined by a keyed array
INPUT:
        $database     Name of database (where $table is located)
        $table        Table where row insertion occurs
        $data_array   A keyed array with defines the data to insert
                      (i.e. $data_array['column_name'] = data)
RETURNS
        SUCCESS or FAILURE
***********************************************************************/
function update($database, $table, $data_array, $key_column, $id, $second_where = null, $second_value = null, $third_where = null, $third_value = null) {

	// LOGGING
	debug_log('update', $table, count($data_array));

    # Connect to MySQL server and select database
	$mysql_connect = connect_to_database();

	// SMAS TEST: trying to solve mysql server has gone away error. Added this on 07/17/14.
    while ($attempts < 10 && mysqli_connect_errno()) {
        $mysql_connect = connect_to_database();
        $attempts++;
    }
    if ($attempts == 10) {
		debug_log('FAILED', 'FAILED', 'FAILED');
        echo '////////////////////////////////////////////////'."\n";
        echo 'THIS FAILED 10 TIMES!!'."\n";
        echo 'sudo /Library/StartupItems/MySQLCOM/MySQLCOM restart'."\n";
        echo '////////////////////////////////////////////////'."\n";
        email("RESTART MYSQL", "sudo /Library/StartupItems/MySQLCOM/MySQLCOM restart");
        return false;
    }

	$bool = mysqli_select_db($mysql_connect, $database);

    # Create column and data values for SQL command
	$setting_list="";
	for ($xx=0; $xx<count($data_array); $xx++)
		{
		list($key,$value)=each($data_array);
		$setting_list.= $key."="."\"".$value."\"";
		if ($xx!=count($data_array)-1)
			$setting_list .= ",";
		}

    # Create SQL command
	$sql="UPDATE ".$table." SET ".$setting_list." WHERE ". $key_column." = " . "\"" . $id . "\"";
	// Change query if there are more than 1 thing in the where statement
	if ($second_where) {
		$sql="UPDATE ".$table." SET ".$setting_list." WHERE ". $key_column." = " . "\"" . $id . "\" AND ".$second_where." = "."\"" . $second_value . "\"";
	} else if ($third_where) {
		$sql="UPDATE ".$table." SET ".$setting_list." WHERE ". $key_column." = " . "\"" . $id . "\" AND ".$second_where." = "."\"" . $second_value . "\" AND ".$third_where." = "."\"" . $third_value . "\"";
	}
    $result = mysqli_query($mysql_connect, $sql);

    # Report SQL error, if one occured, otherwise return result
    if(mysqli_error($mysql_connect))
        {
        echo "MySQL Update Error: ".mysqli_error($mysql_connect);
        $result = "";
        }
     // smas trying to fix mysql server went away error - added 09/14/14
     mysqli_close($mysql_connect);
    return $result;
	}

/***********************************************************************
exe_sql($database, $sql)
-------------------------------------------------------------
DESCRIPTION:
        Executes a SQL command and returns the result
INPUT:
        $database     Name of database to operate on
        $sql          sql command applied to $database
RETURNS
        An array containing the results of sql operation
***********************************************************************/
function exe_sql($database, $sql, $delete = null)
	{

	// LOGGING
	debug_log('exe_sql', 'sql query', $sql);

	# Connect to MySQL server and select database
	$attempts = 0;
	$mysql_connect = connect_to_database();

	while ($attempts < 10 && mysqli_connect_errno()) {
		$mysql_connect = connect_to_database();
		$attempts++;
	}
	if ($attempts == 10) {
		debug_log('FAILED', 'FAILED', 'FAILED');
		echo '////////////////////////////////////////////////'."\n";
		echo 'THIS FAILED 10 TIMES!!'."\n";
		echo 'sudo /Library/StartupItems/MySQLCOM/MySQLCOM restart'."\n";
		echo '////////////////////////////////////////////////'."\n";
		email("RESTART MYSQL", "sudo /Library/StartupItems/MySQLCOM/MySQLCOM restart");
		return false;
	}
	mysqli_select_db($mysql_connect, $database);

    # Execute SQL command
	$result = mysqli_query($mysql_connect, $sql);

    # Break if this is just a delete
    if ($delete == 'delete' || $delete == 'create') {
        // smas trying to fix mysql gone away error - added on 09/14/14
        mysqli_close($mysql_connect);
	    return true;
    }

    # Report SQL error, if one occured
    if(mysqli_error ($mysql_connect))
        {
        echo "MySQL ERROR: ".mysqli_error($mysql_connect);
        $result_set = "";
        }
    else
        {
        # Fetch every row in the result set
        for ($xx=0; $xx<mysqli_num_rows($result); $xx++)
    	    {
		    $result_set[$xx] = mysqli_fetch_assoc($result);
    	    }

        # If the result set has only one row, return a single dimension array
        if(sizeof($result_set)==1)
            $result_set=$result_set[0];

        }
     // smas trying to fix mysql server went away error - added 09/14/14
     mysqli_close($mysql_connect);
    
	return $result_set;
	}

function email($subject, $body) {
    $cmd = 'mail -s "' .$subject .
      '" sarahsmasimore@gmail.com, dan700and2@gmail.com ' . '<<EOF' . "\n"
      . $body . "\n" . 'EOF' . "\n";
    shell_exec($cmd);
}

?>
