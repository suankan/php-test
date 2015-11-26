LIBRARIES REQUIREMENT:
This script relies on Commando PHP library to processes command line options (https://github.com/nategood/commando). This library is included in folder "vendor".

ASSUMPTIONS MADE:
 - Task didn't specify what to do with non-letter characters (such as !) in names. So I decided to cut exclamation marks off.
 - Task didn't specify a need to handle Irish names such as O'Brien. I decided to just implement this. 

SCRIPT USAGE SCENARIOS:

Creating table:
	php user_upload.php --create_table -u <MySQL user>
		-p <MySQL user password> -h <MySQL hostname>
	
	Script connects to MySQL db using provided credentials and creates database "catalog" if it does not exists.
	Then script drops table "users" and re-creates it anew. Field email is made unique key.
		
Dry run, check CSV content and exit without importing to DB:
	php user_upload.php --dru_run --file <CSV filename>

	Script reads supplied CSV file and:
	- corrects names and surnames by leaving only first letters uppercased.
	- uppercases correctly Irish names such as O'Brien.
	- lowecases all email addresses
	- validates email addresses using regex "\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b"

Importing file:
	php user_upload.php --file <CSV filename> -u <MySQL user> 
		-p <MySQL user password> -h <MySQL hostname>
		
	At this stage script expects CSV file to be free of invalid emails. Otherwise no DB insert will be done.
	Script takes CSV file, validates emails and makes name and surname corrections as in --dru_run.
	Then script imports each CSV row to MySQL DB.

Only above described sets of options could be used. E.g. you cannot use --create_table together with --dry_run  

Options description:

--create_table
     Instructs to create table in MySQL DB with name "users"
--dry_run
     No data will be added to DB. All other functions will be executed
--file <argument>
     Input file with CSV data to be parced
-h <argument>
     MySQL hostname or IP
--help
     Show the help page for this command.
-p <argument>
     Password of MySQL user
-u <argument>
     MySQL username

KNOWN ISSUES:
- Commando method needs() doesn't work as expected (https://github.com/nategood/commando/issues/34). Due to that I had to manually enforce user to supply only valid sets of options.
- Script will not uppercase correctly names such as Mary-Jane, it will result such names to be Mary-jane. Fix is possible.