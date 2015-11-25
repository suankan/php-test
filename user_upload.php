<?php
/*
Create a PHP script that is executed form the command line that accepts a CSV file as input (see command line directives below) and processes the CSV file. The parsed file data is to be inserted into a MySQL database. A CSV file will be provided as part of this task that contains test data, the script must be able to process this file.
The PHP script will need to correctly handle the following criteria:
• CSV file will contain user data and have three columns: name, surname, email (see table definition below)
• CSV file will have an arbitrary list of users
• Script will iterate through the CSV rows and insert each record into a dedicated
MySQL database into the table “users”
• The users database table will need to be created/rebuilt as part of the PHP script. This will be defined as a Command Line directive below.
• Name and surname field should be set to be capitalised e.g. from “john” to “John” before being inserted into DB
• Emails need to be set to be lower case before being inserted into DB
• The script should validate the email address before inserting to make sure that it is valid (valid means that it is a legal email format e.g. “xxxx@asdf@asdf is not a legal format). In the instance that an email is invalid, no insert should be made to database and error message reported to STDOUT.
We are looking for a script that is robust and gracefully handles errors/exceptions.

Command line options:
 --file [csv file name] – this is the name of the CSV to be parsed
 --create_table – this will cause the MySQL users table to be built (and no further
 action will be taken)
 --dry_run – this will be used with the --file directive in the instance that we want to run the script but not insert into the DB. All other functions will be executed, but the database won't be altered.
 -u – MySQL username
 -p – MySQL password
 -h – MySQL host
 --help – which will output the above list of directives with details.
*/

require_once 'vendor/autoload.php';

$cmd_options = new Commando\Command();

// Define option "--file"
$cmd_options->option('file')
	->file()
	->describedAs('Input file with CSV data to be parced');

	// Define flag "--create_table"
$cmd_options->flag('create_table')
	->boolean()
	->needs('u', 'p', 'h')
	->describedAs('Instructs to create table in MySQL DB with name "users"');

// Define flag "--dry_run"
$cmd_options->flag('dry_run')
	->boolean()
	->describedAs('No data will be added to DB. All other functions will be executed');

// Define option "u"
$cmd_options->option('u')
	->needs('p', 'h')
	->describedAs('MySQL username');

// Define option "-p"
$cmd_options->option('p')
	->needs('u', 'h')
	->describedAs('Password of MySQL user');

// Define option "-h"
$cmd_options->option('h')
	->needs('u', 'p')
	->describedAs('MySQL hostname or IP');

//Compose help message
$help = "Usage:

Creating table:
	php user_upload.php --create_table -u <MySQL user>
		-p <MySQL user password> -h <MySQL hostname>
		
Importing file:
	php user_upload.php --file <CSV filename> -u <MySQL user> 
		-p <MySQL user password> -h <MySQL hostname>
		
Dry run:
	php user_upload.php --dru_run --file <CSV filename>
";
$cmd_options->setHelp($help);

//Aligning options and flags to be either set or unset. 
$csv_file = $cmd_options['file'];
if ($cmd_options['create_table'])
	$create_table = 1; // otherwise it is unset
if ($cmd_options['dry_run'])
	$dry_run = 1; // otherwise it is unset
$mysql_user = $cmd_options['u'];
$mysql_user_password = $cmd_options['p'];
$mysql_host = $cmd_options['h'];

echo "Using input csv_file: ", isset($csv_file) ? $csv_file : "No", PHP_EOL,
	"Create table: ", isset($create_table) ? "Yes" : "No", PHP_EOL,	
	"If dry_run: ", isset($dry_run) ? "Yes" : "No", PHP_EOL,
	"MySQL DB username: ", isset($mysql_user) ? $mysql_user : "Not specified", PHP_EOL,
	"MySQL user password: ", isset($mysql_user_password) ? $mysql_user_password : "Not specified", PHP_EOL,
	"MySQL DB host: ", isset($mysql_host) ? $mysql_host : "Not specified", PHP_EOL, PHP_EOL;
	
function create_db_table($user, $password, $host) {
	//open connection
	$conn = mysqli_connect($host, $user, $password) or die(mysqli_connect_error());
	//create DB if it doesn't exist
	if (mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `catalog`;")) {
		echo "Database \"catalog\" created successfully" . PHP_EOL;
	} else {
		echo "Error creating database \"catalog\": " . mysqli_error($conn) . PHP_EOL;
	}
	//enter to DB
	mysqli_select_db($conn, "Catalog");	
	// drop table users if it exists
	if (mysqli_query($conn, "DROP TABLE IF EXISTS `users`")) {
		echo "Table \"users\" exists. Dropping." . PHP_EOL;
	} else {
		echo "Error dropping table \"users\": " . mysqli_error($conn) . PHP_EOL;
	}
	// create table users
	$create_table_sql = "CREATE TABLE `users` (
		name VARCHAR(30) NOT NULL,
		surname VARCHAR(30) NOT NULL,
		email VARCHAR(50) NOT NULL UNIQUE, 
		INDEX index_email (email)
		)	ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
	if (mysqli_query($conn, $create_table_sql)) {
		echo "Table \"users\" created successfully" . PHP_EOL;
	} else {
		echo "Error creating table \"users\": " . mysqli_error($conn) . PHP_EOL;
	}
	//close DB connection
	mysqli_close($conn);
}

function fix_irish_surname($name){
	$pos = strpos($name, "'");
	if ($pos) {
		$name[$pos+1] = strtoupper($name[$pos+1]);
	}
	return $name;
}

function read_validate_csv($file) {
	$rows = array();
	$iter = 0;
	$invalid_emails_count = 0;
	foreach (file("$file", FILE_IGNORE_NEW_LINES) as $line) {
		if ( $iter === 0) {
			$iter++;
			continue;
		}
		$csv_row = str_getcsv($line);
		//remove all whitespaces
		$csv_row = preg_replace("/\s+/", "", $csv_row);
		//lowercase  everything
		$csv_row = array_map("strtolower", $csv_row);
		//uppercase first letters in name and surname
		$csv_row[0] = ucfirst($csv_row[0]);
		$csv_row[1] = ucfirst($csv_row[1]);
		//if sername contains single quote then uppercase letter next to it
		$csv_row[1] = fix_irish_surname($csv_row[1]);
		//remove exclamation marks from name and surname (ASSUMPTION. CASE NOT SPECIFIED IN TASK)
		$csv_row[0] = preg_replace("/!+/", "", $csv_row[0]);
		$csv_row[1] = preg_replace("/!+/", "", $csv_row[1]);
		//validate emails 
		if (preg_match("/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/", $csv_row[2]) === 0) {
			$current_iter = $iter+1;
			echo "WARNING: Found invalid email $csv_row[2] in CSV file, line $current_iter", PHP_EOL;
			$invalid_emails_count++;
		}
		echo $iter+1, " ", implode(",", $csv_row), PHP_EOL;
		$iter++;
	}
	return $invalid_emails_count;
}

function import_csv_to_db($file) {
	
	return True;
}

function import_file_to_db($file, $user, $password, $host) {
	$invalid_emails_count = read_validate_csv($file);
	if ($invalid_emails_count != 0) {
		echo "Found $invalid_emails_count invalid emails. No insert will be done to DB\n";
	} else {
		echo "Importing CSV file to DB..." . PHP_EOL;	
		import_csv_to_db($file) 
	}
}

function do_dry_run($file) {
	$invalid_emails_count = read_validate_csv($file);
	echo "Dry run is done" . PHP_EOL;
	return "Found $invalid_emails_count invalid emails\n";
}

//Processing scenario --create_table, -u, -p, -h. Requiring other options to be not used to avoid ambiguity.
if (isset($create_table, $mysql_user, $mysql_user_password, $mysql_host) && !isset($dry_run) && !isset($file)) {
	//create DB and re-create table
	create_db_table($mysql_user, $mysql_user_password, $mysql_hostname);
	//Exit script as required
	die("Database \"catalog\" and table \"users\" are ready. Please proceed to loading CSV data.");
} //Processing scenario --file, -u, -p, -h. Requiring other options to be not used to avoid ambiguity.
elseif (isset($csv_file, $mysql_user, $mysql_user_password, $mysql_host) && !isset($dry_run) && !isset($create_table)) {
		import_file_to_db($csv_file, $mysql_user, $mysql_user_password, $mysql_host);		
		die();
} //Processing scenario --dry_run and --file. Requiring other options to be not used to avoid ambiguity.
elseif (isset($dry_run, $csv_file) && !isset($create_table) && !isset($mysql_user) && !isset($mysql_user_password) && !isset($mysql_host)) {
	echo do_dry_run($csv_file);
	die();
} else { // rtfm if any other invalid options provided	
	die ("Unrecognized sequence of options. Please use option --help for script usage scenarios.");
}
?>