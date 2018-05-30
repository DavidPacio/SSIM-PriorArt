<?php /* Copyright 2011-2013 Braiins Ltd

/srv/Base.inc

Braiins main site  base or start

History:
21.02.11 Started
15.03.11 Renamed from AppConstants.inc since it brings in funcs as well as defines constants
18.10.12 Changed to use of global $AppEnum, $AppName
19.10.12 Use of Com_Inc added
28.04.13 Tidied up for Constants.inc changes
*/

# $AppName set by requiring App

$AppAid  = 'Braiins';
require ($_SERVER['DOCUMENT_ROOT'][0] === '/' ? '/home/braiinsc/Braiins' : 'C:/wamp/www').'/Com/inc/Constants.inc';
$AppEnum = APP_Braiins;
const DB_Default = DB_Braiins;

define('LOG_FILE', Root_Path.'Braiins/Logs/Braiins.log');
define('REG_LOG',  Root_Path.'Braiins/Logs/Reg.log');

# Funcs, Globals
require Com_Inc.'LoadFuncs.inc';

Start(); # Session start and DB connection

