<?php /* Copyright 2011-2013 Braiins Ltd

Admin BaseSIM.inc
Braiins Admin Base for Braiins SIM DB Non Ajax modules

History:
02.07.13 Renamed from B R L
*/

$AppAid = 'Admin';
require ($_SERVER['DOCUMENT_ROOT'][0] === '/' ? '/home/braiinsc/Braiins' : 'C:/wamp/www').'/Com/inc/Constants.inc';
$AppEnum = APP_Admin;
$AppName = 'Braiins Admin SIM';
const DB_Default = DB_SIM;

define('LOG_FILE', Root_Path."Admin/Logs/$AppAid.log");

# App Constants, Funcs, Globals
require Com_Inc.'ConstantsSIM.inc';
require Com_Inc.'LoadFuncsSA.inc';
require './inc/Funcs.inc';

$DB = new BDB();

