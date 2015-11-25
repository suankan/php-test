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

// No definition for option "--help" because Composer already implements it

$csv_file = $cmd_options['csv_file'];
$create_table = $cmd_options['create_table'] ? "users" : "";  
$dry_run = $cmd_options['dry_run'];
$mysql_user = $cmd_options['u'];
$mysql_user_password = $cmd_options['p'];
$mysql_host = $cmd_options['h'];

echo "Using input csv_file: ", empty($csv_file) ? "No" : $csv_file, PHP_EOL,
	"Create table: ", empty($create_table) ? "Not creating" : $create_table, PHP_EOL,	
	"If dry_run: ", empty($dry_run) ? "No" : "Yes", PHP_EOL,
	"MySQL DB username: ", empty($mysql_user) ? "Not specified" : $mysql_user, PHP_EOL,
	"MySQL user password: ", empty($mysql_user_password) ? "Not specified" : $mysql_user_password, PHP_EOL,
	"MySQL DB host: ", empty($mysql_host) ? "Not specified" : $mysql_host, PHP_EOL, PHP_EOL;

function validate_options_create_table() {
	//Check if some redundant options are used, like --file and --dry_run 
	if ($csv_file || $dry_run) {
		echo "WARNING: Option --create_table selected. Ignoring options --file and --dry_run" . PHP_EOL;
	}
	//Option --create_table must be accompanied with options -u, -p and -h
	if (!($create_table && $mysql_user && $mysql_user_password && $mysql_host)) {
		echo "ERROR: Option --create_table must be accompanied with options -u, -p and -h, but some of them are missing.". PHP_EOL;		
		return false;
	} else {
		return true;
	}
}

function validate_options_file() {
	//Check if some redundant options are used, like --create_table
	if ($create_table) {
		echo "WARNING: Option --file selected. Ignoring option ----create_table" . PHP_EOL;
	}
	//Option --file must be accompanied with options -u, -p and -h
	if (!($csv_file && $mysql_user && $mysql_user_password && $mysql_host)){
		echo "ERROR: Option --file must be accompanied with options -u, -p and -h, but some of them are missing.". PHP_EOL;
		return false;
	} else {
		return true;
	}
}

function validate_options_dry_run() {
	//Check if some redundant options are used, like --create_table, -u, -p and -h
	if ($create_table || $mysql_user || $mysql_user_password || $mysql_host) {
		echo "WARNING: Option --dry_run was selected. Ignoring options --create_table, -u, -p, -h" . PHP_EOL;
	}
	// This option must be accompanied with --file
	if (!($dry_run && $csv_file)) {
		echo "ERROR: Option --dry_run must be accompanied with option --file, but it is missing.". PHP_EOL;		
		return false;
	} else {
		return true;
	}
}

function create_table($user, $password, $host) {
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

function validate_file($file) {
	
}

function import_file_to_db($file, $user, $password, $host) {
	//echo "Import file to DB is done" . PHP_EOL;
	
}

function dry_run($dry, $file) {
	echo "Dry run is done" . PHP_EOL;
}

//Processing options --create_table, -u, -p and -h
if ($table && $mysql_user && $mysql_user_password && $mysql_host) {
	//check if options --create_table, -u, -p and -h are provided
	if (validate_options_create_table()){
		//create DB, re-create table
		create_table($table, $mysq_user, $mysql_user_password, $mysql_hostname);
		
		//Exit script as required
		die("Database \"catalog\" and table \"users\" are ready. Please proceed to loading CSV data.");
	}
} 
//Processing options --file, -u, -p and -h
elseif ($file && $mysql_user && $mysql_user_password && $mysql_host){
		if (validate_options_file()) {
			import_file_to_db($file, $mysql_user, $mysql_user_password, $mysql_host);
		}
}
//Processing options --dry_run and --file
elseif ($dry && $file) {
	if (validate_options_dry_run()){
		dry_run($dry, $file);
	}
} else {
	// rtfm if any other invalid options provided
	die ("Cannot recognize options, please use --help");
}

/*
//iterating through CSV file
function read_csv($filename) {
	$rows = array();
	foreach (file("$filename", FILE_IGNORE_NEW_LINES) as $line) {
		$rows[] = str_getcsv($line);
	};
	return $rows;
}

$rows_number = count(read_csv($csv_file));
echo "Number of rows in CSV file: ", $rows_number, PHP_EOL;

print_r(read_csv($csv_file));
*/
 
?>
