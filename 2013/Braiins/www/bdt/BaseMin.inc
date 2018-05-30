<?php /* Copyright 2011-2013 Braiins Ltd

/bdt/BaseMin.inc
Braiins DeskTop min (no session, no server funcs) base or start

History:
12.02.12 Started
18.10.12 Changed to use of global $AppEnum, $AppName
19.10.12 Changed to use absolute paths with extra constant Com_Inc, and moved to bdt for use by any BDT php module
28.04.13 Tidied up for Constants.inc changes
*/

# $AppAid (= Alha 2/3 letter 'id') to be set by requiring App before requiring this
# $AppEnum to be set by requiring App after requiring this as Constants.inc defines the Epp Enums

require ($_SERVER['DOCUMENT_ROOT'][0] === '/' ? '/home/braiinsc/Braiins' : 'C:/wamp/www').'/Com/inc/Constants.inc';
const DB_Default = DB_Braiins;
define('LOG_FILE', Root_Path."Braiins/Logs/$AppAid.log");

# Funcs, Globals
require Com_Inc.'LoadFuncs.inc';
require Com_Inc.'ErrorSA.inc';
require Com_Inc.'ClassBDB.inc';
require Com_Inc.'FuncsGen.inc';

$DB = new BDB();
