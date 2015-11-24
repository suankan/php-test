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

// No definition for option "--help" is required because Composer already implements it

$csv_file = $cmd_options['file'];
$table = $cmd_options['create_table'] ? "users" : "";  
$dry = $cmd_options['dry_run'];
$mysql_user = $cmd_options['u'];
$mysql_user_password = $cmd_options['p'];
$mysql_host = $cmd_options['h'];


echo "Using input file: ", empty($csv_file) ? "No" : $csv_file, PHP_EOL,
	"Create table: ", empty($table) ? "Not creating" : $table, PHP_EOL,	
	"If dry_run: ", empty($dry) ? "No" : "Yes", PHP_EOL,
	"MySQL DB username: ", empty($mysql_user) ? "Not specified" : $mysql_user, PHP_EOL,
	"MySQL user password: ", empty($mysql_user_password) ? "Not specified" : $mysql_user_password, PHP_EOL,
	"MySQL DB host: ", empty($mysql_host) ? "Not specified" : $mysql_host, PHP_EOL;

//Create/Re-create table if option --create_table is specified
if (!empty($table)) {
	//open connection
	$conn = mysqli_connect($mysql_host, $mysql_user, $mysql_user_password);
	//check connection
	if (!$conn) {
		die("Connection failed: " . mysqli_connect_error() . PHP_EOL);
	}	
	echo "Connected successfully" . PHP_EOL;
	//create db if it doesn't exist
	$create_db_sql = "CREATE DATABASE IF NOT EXISTS Catalog;";
	if (mysqli_query($conn, $create_db_sql)) {
		echo "Database created successfully" . PHP_EOL;
	} else {
		echo "Error creating database: " . $mysqli_error($conn) . PHP_EOL;
	}
	mysqli_select_db($conn, "Catalog");	
	// drop table users if it exists
        if (mysqli_query($conn, "DROP TABLE IF EXISTS users")) {
                echo "Table users exists. Dropping." . PHP_EOL;
        } else {
                echo "Error creating table: " . mysqli_error($conn) . PHP_EOL;
        }
	// create table users
	$create_table_sql = "CREATE TABLE users (
		firstname VARCHAR(30) NOT NULL,
		lastname VARCHAR(30) NOT NULL,
		email VARCHAR(50)
	)";
	if (mysqli_query($conn, $create_table_sql)) {
    		echo "Table users created successfully" . PHP_EOL;
	} else {
		echo "Error creating table: " . mysqli_error($conn) . PHP_EOL;
	}
	mysqli_close();
}

//iterating through CSV file

if (($handle = fopen("$csv_file", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $num = count($data);
        for ($c=0; $c < $num; $c++) {
            echo trim($data[$c]) . ",";
        }
	echo PHP_EOL;
    }
    fclose($handle);
}

/*
foreach (file("$csv_file") as $line) {
	//obtaining 
	$data[] = str_getcsv($line);
	echo $data[0][0], PHP_EOL;

        //open connection to db
        $conn = mysqli_connect($mysql_host, $mysql_user, $mysql_user_password);
        //check connection
        if (!$conn) {
                die("Connection failed: " . mysqli_connect_error() . PHP_EOL);
        }
        echo "Connected successfully" . PHP_EOL;

	//retrieve 
	echo $firstName = ucfirst($data[0]), PHP_EOL,
		$lastName = ucfirst($data[1]), PHP_EOL,
		$email = strtolower($data[2]), PHP_EOL;
}
*/
 
?>
