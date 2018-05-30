<?php /*

/srv/Boot.php
Server ops for Ajax call to boot (initialise) a main site page

History:
10.02.11 Created
24.02.11 Code from DBconnectBoot.inc brought inline as the inc was used only by this module.
08.01.12 Revised for client use of html5 local storage rather than cookies
18.10.12 Changed to use of global $AppName
03.03.13 Added Member Login Lock checks

*/

# Op      Dat Rec                                               Dat Returned
# i Init  [Inst | New Window | Referrer | ScrRes | TZO | Bver]  [Email | DName | LoginN | Coded VisId]
#                                                               4 fields
$AppName = 'Braiins';
require 'Base.inc';

if ($Op != Op_i) Error(ERR_Op);

# Passed 6 fields. Start() would have errored if not 4.
#          0        1            2        3       4     5
# DatA = Inst | New Window | Referrer | ScrRes | TZO | Bver
$int = (int)$DatA[0];
$new = (int)$DatA[1];
$ref =      $DatA[2];
$scr = (int)$DatA[3];
$tzo = (int)$DatA[4];
$ver =      $DatA[5];
#LogIt("inst $int, new $new, scr $scr, tzo $tzo, ref $ref, ver $ver");
SessionStart($int, $new, $scr, $tzo, $ref, $ver);
# Returns with globals of:
# - $DB->MemId (0 for unknown site visit)
# - $DB->LoginN (LGN__NOT, LGN_LIST, LGN_TENT, LGN_FULL)
# - $DB->Bits being the Member's bit settings
# - $DB->VisId from the Base.inc Start() call
# - $DB->SessionA for Email, DName
$MemId = $DB->MemId;
$VisId = $DB->VisId;
if ($MemId && $DB->LoginN && ($DB->Bits & MB_STD_BITS) != MB_OK) { # MB_STD_BITS = MB_DELETE | MB_OK
  # Member in session but not OK due to deletion or permissions changed presumably so log previous member out totally
  SessionStatusChange(V_LT);
  LogIt("Member $MemId, Visitor $VisId Logged out totally.");
}
if ($DB->LoginN) {
  $email = $DB->SessionA['Email'];
  $dName = $DB->SessionA['DName'];
}else
  $email = $dName = '';

if ($DB->LoginN === LGN_FULL) {
  # Add Member Login Lock if not there already i.e. after MySQL server restart
  if (!$DB->IsBDbLockSet(T_B_People, $MemId, Lock_Login))
    $DB->AddBDbLoginLock(T_B_People, $MemId, $DB->StrOneQuery("Select EditT From Visits Where Id=$VisId"));
}else
  # Not logged in fully
  # Remove Member Login Lock if there already i.e. after Browser crash/IC failure
  if ($DB->IsBDbLockSet(T_B_People, $MemId, Lock_Login))
    $DB->RelBDbLoginLock(T_B_People, $MemId, Lock_Login);

# Returns:
#                Email  | DName   |      LoginN | Coded VisId
AjaxReturn(OK, "$email$dName$DB->LoginN".($VisId * SESS_MN1 + SESS_MN2));

