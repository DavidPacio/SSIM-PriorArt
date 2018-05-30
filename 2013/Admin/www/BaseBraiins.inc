<?php /* Copyright 2011-2013 Braiins Ltd

Admin BaseBraiins.inc
Braiins Admin Base for Braiins DB Non Ajax modules, no Tx DB

History:
26.02.13 Started based on UK-GAAP-DPL version
28.04.13 Tidied up
*/

$AppAid  = 'Admin';
require ($_SERVER['DOCUMENT_ROOT'][0] === '/' ? '/home/braiinsc/Braiins' : 'C:/wamp/www').'/Com/inc/Constants.inc';
$AppEnum = APP_Admin;
$AppName = 'Braiins Admin';
const DB_Default = DB_Braiins;

define('LOG_FILE', Root_Path."Admin/Logs/$AppAid.log");

# Funcs, Globals
require Com_Inc.'LoadFuncsSA.inc';
require './inc/Funcs.inc';

$DB = new BDB();
