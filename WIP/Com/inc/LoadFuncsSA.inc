<?php /* Copyright 2011-2013 Braiins Ltd

/Com/inc/LoadFuncsSA.inc
General Functions for Stand Alone modules i.e. those not using Ajax. All are Admin modules.

History:
07.04.11 Created from LoadFuncs.inc
19.10.12 Added Com_Inc. as per LoadFuncs.inc
29.04.13 Tidied up

*/
require Com_Inc.'ErrorSA.inc';  # The Stand Alone, non Ajax, version of Error()
require Com_Inc.'ClassBDB.inc'; # Database, Log, and some time functions
require Com_Inc.'FuncsGen.inc'; # Logging, Time, ChrList and Misc functions
# No server or session funcs
# No Globals as per LoadFuncs.inc

