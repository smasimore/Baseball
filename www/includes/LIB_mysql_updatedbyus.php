<?php
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
define("MYSQL_PASSWORD", "baseball");         // Define your MySQL password
define("DATABASE", "baseball");               // Define your default database
define("SUCCESS", true);              // Successful operation flag
define("FAILURE", false);             // Failed operation flag

if(strlen(MYSQL_ADDRESS) + strlen(MYSQL_USERNAME) + strlen(MYSQL_PASSWORD) + strlen(MYSQL_ADDRESS) + strlen(DATABASE) == 0)
    echo "WARNING: MySQL not configured.<br>\n";

// Logging for debugging
function debug_log($function, $table, $data_length) {
    $time = time();
    $log_data = " \n WEB, $time, $function, $table, $data_length"; 
    file_put_contents(
        '/Users/baseball/Logging/mysql_log.txt',
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
        # if (mysqli_connect_errno()) {
	#	printf("Connect failed: %s\n", mysqli_connect_error());
	#       exit();
	# }
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

		// smas trying to fix mysql gone away error - added on 09/14/14
		mysqli_close($mysql_connect);

		return false;
        $result = "";
        }
    else
        {
        // smas trying to fix mysql gone away error - added on 09/14/14
        mysqli_close($mysql_connect);
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
function update($database, $table, $data_array, $key_column, $id)
	{

    // LOGGING
    debug_log('update', $table, count($data_array));

    # Connect to MySQL server and select database
	$mysql_connect = connect_to_database();
	$bool= mysql_select_db ($database, $mysql_connect);

    // SMAS TEST: trying to solve mysql server has gone away error. Added this on 09/14/14.
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
    $result = mysql_query($sql, $mysql_connect);

    # Report SQL error, if one occured, otherwise return result
    if(mysql_error($mysql_connect))
        {
        echo "MySQL Update Error: ".mysql_error($mysql_connect);
        $result = "";
        // smas trying to fix mysql gone away error - added on 09/14/14
        mysqli_close($mysql_connect);
        }
    else
        {
        // smas trying to fix mysql gone away error - added on 09/14/14
        mysqli_close($mysql_connect);
        return $result;
        }
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
    if ($delete == 'delete') {
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

        return $result_set;
        }
	}

function email($subject, $body) {
    $cmd = 'mail -s "' .$subject .
      '" sarahsmasimore@gmail.com, dan700and2@gmail.com ' . '<<EOF' . "\n"
      . $body . "\n" . 'EOF' . "\n";
    shell_exec($cmd);
}

?>
