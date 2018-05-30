<?php /* Copyright 2011-2013 Braiins Ltd

Admin UK-IFRS-DPL/BaseTx.inc
Braiins Admin Base for UK-IFRS-DPL DB Non Ajax modules

History:
26.06.13 Created based on UK-GAAP-DPL version
*/

$AppAid = 'Admin';
require ($_SERVER['DOCUMENT_ROOT'][0] === '/' ? '/home/braiinsc/Braiins' : 'C:/wamp/www').'/Com/inc/Constants.inc';
$AppEnum = APP_Admin;
$AppName = 'Braiins Admin UK-IFRS-DPL';

$TxName  = 'UK-IFRS-DPL';
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
