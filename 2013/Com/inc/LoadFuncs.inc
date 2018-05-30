<?php /* Copyright 2011-2013 Braiins Ltd

/Com/inc/LoadFuncs.inc
General Functions when using HttpRequestObject Ajax
Use of this inc assumes use of Ajax.

History:
xx.02.11 Revised for Braiins
08.01.12 Revised for client use of html5 local storage rather than cookies
19.10.12 Added Com_Inc.
11.03.13 LogS and SessionClose() call removed with logging all being done directly via LogIt()

djh?? Add a random check re VisId
djh?? Or don't pass VisId back unless different?

Provides:

 As per the require comments plus

 AjaxReturn($ret, $dat=false)

*/
require Com_Inc.'Error.inc';       # The standard Error() fn which exits via AjaxReturn() unless passed a return parameter
require Com_Inc.'ClassBDB.inc';    # Database class BDB and helper functions
require Com_Inc.'FuncsGen.inc';    # Logging, Time, ChrList and Misc functions
require Com_Inc.'FuncsServer.inc'; # Server related functions
require Com_Inc.'Session.inc';

/////////////
// Globals //
/////////////
$URI       = GetURI(); // djh?? add PageFromN detection
$UserAgent = GetUserAgent();
GetIp(); # Sets Ip and Source (server var) in globals $IpG and $IpN
#Start() is called in Base.inc after App constants are defined to set Visit info and connect to DB

# AjaxReturn
# ----------
function AjaxReturn($ret, $dat=false) {
  SessionClose();
  if ($dat!==false)
    echo $dat, '', $ret;
  else
    echo $ret;
  exit;
}

