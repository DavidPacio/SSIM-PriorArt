<?php /* Copyright 2011-2013 Braiins Ltd

Admin UK-GAAP-DPL/BaseTx.inc
Braiins Admin Base for UK GAAP DPL DB Non Ajax modules

History:
29.09.12 Created based on UK-GAAP version
18.10.12 Changed to use of global $AppEnum, $AppName
19.10.12 Changed to use absolute paths with extra constant Com_Inc, Com_Inc_Tx and Com_Str_Tx defining moved to Constants.inc
28.04.13 Tidied up and Base.inc put to use
*/

$AppAid = 'Admin';
require ($_SERVER['DOCUMENT_ROOT'][0] === '/' ? '/home/braiinsc/Braiins' : 'C:/wamp/www').'/Com/inc/Constants.inc';
$AppEnum = APP_Admin;
$AppName = 'Braiins Admin UK-GAAP-DPL';

$TxName  = 'UK-GAAP-DPL';
define('DB_Tx', DB_Prefix.str_replace('-', '_', $TxName));
define('Com_Inc_Tx', Com_Inc."$TxName/");
define('Com_Str_Tx', Com_Str."$TxName/");

const DB_Default = DB_Tx;

define('LOG_FILE', Root_Path."Admin/Logs/$AppAid-$TxName.log");

# Funcs, Globals
require Com_Inc.'LoadFuncsSA.inc';
require './inc/TxFuncs.inc';
require './inc/Funcs.inc';

$DB = new BDB();
