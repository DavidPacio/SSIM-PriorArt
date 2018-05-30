<?php /* Copyright 2011-2013 Braiins Ltd

Braiins/www/srv/Login.php

Braiins main site login server ops

History:
13.02.11 Started
02.01.12 Password handling revised
08.01.12 Revised for Session.inc changes re switch from cookies to localStorage
18.10.12 Changed to use of global $AppName
03.03.13 Added Add of Member Login Lock
03.05.13 Revised for BRL People table changes

ToDo djh??
----
Needs to be extended to handle multiple memberships by the same person

Hanlde case of person login when member has email and pw

*/
$AppName = 'Braiins Login';
require 'Base.inc';
SessionOpen();

# Ops:            Dat Rec        DAT Returned
# l Login attempt Email | PW     [Email | DName | LoginN] if successful with LoginN = LGN_FULL
#                                 = 3 fields

if ($DB->LoginN >= LGN_FULL) # LGN_FULL or LGN_GUEST
  # Should not be here if logged in already
  LogIt('Login: Already logged in. Continuing.');

# Dat =    0      1
#       | Email | PW
$email = Clean($DatA[0], FT_STR, true, $emailEsc); # username or email as of 03.05.13
$pw    = Clean($DatA[1], FT_PW, true, $pwEsc);

# Default fail return to be used for both cases 1 and 2 so as not to give anything away e.g. re email not found
$Dat = "Sorry, the username or email address and password combination you entered were not found.\n\nCase does not matter for the username or email address, but it is significant for the password.\n\nPlease check and try again.";

#if ($MO = $DB->OptObjQuery("Select M.Id MId,M.Bits MBits,P.DName,CASE WHEN M.Email IS NOT NULL THEN M.Email ELSE P.EMAIL END AS Email From Members M Join People P ON P.Id=M.PeoId Where ((M.Email IS NULL And P.Email='$emailEsc') Or M.Email='$emailEsc') And ((M.PW IS NULL And BINARY P.PW=SHA1('$pwEsc')) Or BINARY M.PW=SHA1('$pwEsc')) And (M.Bits&1)=0 And (P.Bits&1)=0 Order by M.Id Limit 1")) {
#if ($MO = $DB->OptObjQuery("Select M.Id MId,M.Bits MBits,M.Email MEmail,M.PW MPW,M.Fails MFails,P.Id PId,P.Bits PBits,P.Email PEmail,P.PW PPW,P.Fails PFails,P.DName From People P Left Join Members M on M.PeoId=P.Id Where ((M.Email IS NULL And P.Email='$emailEsc') Or M.Email='$emailEsc') Order By M.Id Limit 1")) {
if ($MO = $DB->OptObjQuery("Select Id,Bits,Email,PW,Fails,DName From People P Where Email='$emailEsc' Or Ref='$emailEsc'")) {
  $memId  = (int)$MO->Id;
  $mBits  = (int)$MO->Bits;
  $mEmail =      $MO->Email;
  $mPw    =      $MO->PW;
  $fails  = (int)$MO->Fails;
  # LogIt('Login: MBits='.$mBits);
  if (($mBits & MB_OK) && ($mBits & PB_Member) && ($mBits & AP_All)) {
    # People record OK, is a Member, and at least one AP bit is set
    $dName = $MO->DName;
    $email = $mEmail; # to return it in the original or stored form
    $pwB   = GenPw($pw, $memId) === $mPw; # PW OK or not
    if ($pwB) {
      # Pw OK
      $loginN = LGN_FULL;
      # Update session info.
      # Visits
      $set = "LoginN=$loginN,EditT=$DB->TnS";
      if ($memId != $DB->MemId) {
        $set .= ",MemId=$memId";
        $DB->MemId = $memId; # InsertVisitTran() uses $DB->MemId
      }
      if ($mBits != $DB->Bits)  $set .= ",Bits=$mBits";
      if ($email != $DB->SessionA['Email']) $set .= ",Email='$emailEsc'";
      if ($dName != $DB->SessionA['DName']) $set .= ",DName='".$DB->real_escape_string($dName)."'";
      $DB->StQuery("Update Visits Set $set Where Id=$DB->VisId");
      # #wc if !DEV exclude next line
      LogIt("Login: OK. Visit $DB->VisId updated with $set");
      InsertVisitTran(V_LI, LGN_FULL);
      # OK, build $Dat
      #       Email  DName  LoginN
      $Dat = "$email$dName$loginN";
      $ret = 0; # OK
      # Add Member Login Lock
      $DB->AddBDbLoginLock(T_B_People, $memId);
    }else{
      # Pw fail
      InsertVisitTran(V_LIF);
      # Increment fails count
      $EntityId=0; # re BDB::DBLog()
      $DB->IncrMaster(T_B_People, $memId, 'Fails');
      if ($fails >= LGN_MaxFails) {
        # Reached fail limit so block
        LogIt('Login: Failed - password and limit of fails reached');
        $ret = 4;
        $Dat = 'Sorry, you have reached the security limit of failed login attempts. Please ' .
          (($mBits & AP_Admin) ? 'contact Braiins to have your password reset.' : 'ask your Administrator to reset your password.') . '';
      }else{
        LogIt('Login: Failed - password');
        $ret = 2;
        # default $Dat
        sleep(2);
      }
    }
  }else{
    InsertVisitTran(V_LIF);
    LogIt('Login: Failed - person not ok, not a member, or no permissions');
    $ret = 3;
    $Dat = 'Sorry, you are not authorised to log in to Braiins.';
  }
}else{
  InsertVisitTran(V_LIF);
  LogIt('Login: Failed - username/email not found');
  $ret = 1;
  # default $Dat
}

# $ret =                                      $Dat = 3 fields
# == 0 OK                                     Email | DName | LoginN
# == 1 login failed - email not found         Default Error message | |
# == 2 login failed - no match, or delete set Default Error message | |
# == 3 login failed - not OK, no perms        Error message | |
# == 4 login failed - limit of fails reached  Error message | |
AjaxReturn($ret, $Dat);

?>
