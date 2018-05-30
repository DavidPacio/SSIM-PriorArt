<?php /* Copyright 2011-2013 Braiins Ltd

/bdt/Base.inc
Braiins DeskTop Apps base

History:
19.10.12 Changed to use absolute paths with extra constant Com_Inc, and moved to bdt for use by all BDT php modules
28.04.13 Tidied up for Constants.inc changes
*/

# $AppAid (= Alha 2/3 letter 'id') to be set by requiring App before requiring this
# $AppEnum to be set by requiring App after requiring this as Constants.inc defines the Epp Enums

require ($_SERVER['DOCUMENT_ROOT'][0] === '/' ? '/home/braiinsc/Braiins' : 'C:/wamp/www').'/Com/inc/Constants.inc';
const DB_Default = DB_Braiins;

$TxName = 'UK-GAAP-DPL';
define('DB_Tx', DB_Prefix.str_replace('-', '_', $TxName));
define('Com_Inc_Tx', Com_Inc."$TxName/");
define('Com_Str_Tx', Com_Str."$TxName/");

define('LOG_FILE', Root_Path."Braiins/Logs/$AppAid.log");

# Funcs, Globals
require Com_Inc.'LoadFuncs.inc';

