This script relies on Commando PHP library to processes command line options (https://github.com/nategood/commando)

Known issues:
	Commando method needs() doesn't work as expected (https://github.com/nategood/commando/issues/34).
	This prevents enforcing options -u, -p and -h to be specified when option --create_table is used.
