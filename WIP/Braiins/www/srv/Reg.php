<?php /* Copyright 2011-2013 Braiins Ltd

Reg.php
Register server ops

History:
17.02.11 Started
01.03.11 Removed insertion/updating of AdminId in Agents to allow multiple Administrators
08.03.11 Removed A Type, Role, Dept from post
07.05.11 Added insertion of the AgentData ADT _AgentInfo record
03.01.12 Password setting changed; Security question and answer fields removed.
05.01.12 Changed to use FuncsBraiins.inc functions
15.06.12 Updated for Members changes.
18.10.12 Changed to use of global $AppName

ToDo djh??
----
Add copying of demo entities
Add master entities for each Entity Type
Remove list membership stuff if list membership is never implemented

*/

$AppName = 'Braiins Registration';
require 'Base.inc';
require Com_Inc.'Email.inc';
require Com_Inc.'FuncsBraiins.inc';
# Open a Session
SessionOpen();
if ($Op != Op_r)  Error(ERR_Op);

# Op         Dat Rec      DAT Returned
# r Register Form fields  [Email | DName | LoginN | Welcome message if successful with LoginN = LGN_FULL
#                         4 fields

# Called from Error() in Error.inc
function ErrorCallBack($err, $errS) {
  AddLog("ERROR: $errS");
  LogThePost($err, 'Sorry, an error occurred processing your registration. Braiins has been notified.');
  ########## Does not return
}

$RegLog = gmstrftime("%Y.%m.%d %H:%M:%S") . # for the log
" ------------
VisId: $DB->VisId
";
# Used by Error() if called
$ErrorHdr='A registration post could not be processed. See the Reg log entry: ' . substr($RegLog, 0, 24);

/* Dat
   0       1         2        3      4      5
A Name | G Name | F Name | P Name | Email | PW */
$aName = Clean($DatA[0], FT_STR, true, $aNameEsc);
$gName = Clean($DatA[1], FT_STR, true, $gNameEsc);
$fName = Clean($DatA[2], FT_STR, true, $fNameEsc);
$pName = Clean($DatA[3], FT_STR, true, $pNameEsc);
$email = Clean($DatA[4], FT_EMAIL,true, $emailEsc);
$pw    = Clean($DatA[5], FT_PW);

$aCtry = $ctry = CTRY_UK;

# Check if Agent is already in DB. Happens if a registration post is repeated.
if ($DB->OneQuery("Select Id from Agents Where AName='$aNameEsc'"))
  LogThePost(2,"The '$aName' agent name is already in use. Could you have already registered it?");
  ##########

$peoId = $listMemId = 0;
$adminBits = MB_OK + AP_All;

# Is the person already in the DB?
# First check for list member
if ($mO = $DB->OptObjQuery(sprintf('Select Id From People Where AgentId=0 And GName='%s' And Email='%s' And Bits&%d=%d', $gNameEsc, $emailEsc, MB_STD_BITS, MB_OK))) {
  # List member with same GName and same Email so use it
  $peoId     = (int)$mO->Id;
  $listMemId = $peoId; # djh?? Rework
  AddLog("Already in DB.People Id $peoId as list member");
}else{
  # Try a People lookup
  $peoId = $DB->OneQuery("Select Id From People Where FName='$fNameEsc' And GName='$gNameEsc' And CtryId=$ctry And Email='$emailEsc' And Bits&3=2");
  if ($peoId)
    AddLog("Already in DB.People Id $peoId");
}

# The Person
if (!$pName) {
  $pName    = "$gName $fName";
  $pNameEsc = "$gNameEsc $fNameEsc";
}
$colsAA = [
  'GName' => $gName,
  'FName' => $fName,
  'DName' => $pName,
  'Email' => $email,
  'CtryId'=> $ctry,               # PW to be added when Id is known
  'Bits'  => MB_OK + PB_EmailOK]; # turn email confirmed on

$DB->autocommit(false); # Start transaction

if ($peoId) {
  # update the person for possibly new info, esp for list members
  $colsAA['PW'] = GenPw($pw, $peoId);
  if ($n = $DB->UpdateMaster(T_B_People, $colsAA, 0, $peoId))
    AddLog("$n fields of People $peoId updated");
}else{
  # add the person
  if (($peoId = $DB->DupInsertMaster(T_B_People, $colsAA)) === false) {
    $pO = $DB->ObjQuery("Select * From People Where Email='$emailEsc'");  # Duplicate key. Could only be the email address
    $mS = "The '$email' email is already in use for '$pO->DName', Given Name '$pO->GName', Family Name '$pO->FName'.\n\nIf that is you, please edit ";
    if ($pO->GName != $gName)
      $mS .= "Given Name to '$pO->GName' ";
    if ($pO->FName != $fName)
      $mS .= ($pO->GName != $gName ? 'and ' : '') . "Family Name to '$pO->FName' ";
    $mS .= "to match the data on file.\n\nOtherwise please enter a different email.";
    LogThePost(1, $mS);
    ##########
  }
  $DB->StQuery("Update People Set PW='".$DB->real_escape_string(GenPw($pw, $peoId))."' Where Id=$peoId"); # Done afterwards to ensure have right Id
  AddLog("People $peoId added");
}

# Add the Agent
if (!$agentId = AddAgent($aName, CTRY_UK)) # Adds ATT_Add and ATT_CreditsGift Agent Trans too. Not admin
  LogThePost(2,"The '$aName' agent name is already in use. Could you have already registered it?"); # Duplicate key. Could only be the name
  ##########
AddLog("Agent $agentId added");
# Add the Gift AgentTrans tran
AddAgentTran($agentId, ATT_CreditsGift, 'Registration gift credits', A_InitialGiftCredits);
AddLog('Registration gift credits transaction added');
# Administrator Member
if ($listMemId) {
  # Update the current list member to Administrator
  $colsAA = [
    'TypeN'   => MT_Member,
    'AgentId' => $agentId,
    'Bits'    => $adminBits]; # AP_All
  $DB->UpdateMaster(T_B_People, $colsAA, 0, $listMemId);
  $adminId = $listMemId;
  $comment = "List member $listMemId upgraded to Administrator";
  AddAgentTran($agentId, ATT_EdtMember, $comment);
  AddLog($comment);
}else{
  # Add person as an Administrator Member
  $adminId = AddMember($agentId, $peoId, MLevel_Max, AP_All, $email, $gName, $fName, $pName); # Adds ATT_AddMember Agent Tran too
  AddLog("Administrator Member $adminId added");
}

# Copy the demo entities
# djh?? Temporarily just create one

           #AddEntity($agentId, $eRef,          $eName,                             $ident,            $ctryId, $txnId,          $eTypeId,        $eSizeId, $mngrId, $level,      $bits, $DGsAllowed, $credits, $crComment, $descr='', $enComment=''
$entityId = AddEntity($agentId, "TE$agentId-1", "Test Entity 1 for Agent |$aName|", 'djh?? fix ident', CTRY_UK, TxN_UK-GAAP-DPL, ET_PrivateLtdCo, ES_Small, $adminId, ELevel_Min,   146,      124918,        0, '');
AddLog("Entity $entityId added");

$loginN = $DB->LoginN;
if ($loginN >= LGN_FULL && $DB->MemId == $adminId) {
  # Already logged in as same member
  if ($adminBits != $DB->Bits) {
    $DB->StQuery("Update Visits Set Bits=$adminBits,EditT=$DB->TnS Where Id=$DB->VisId");
    InsertVisitTran(V_UP);
  }
}else{
  # Not already logged in as same member
  if ($loginN >= LGN_FULL)
    # Logged in as different member so log out as that member
    SessionStatusChange(V_LT);
  # Login
  $loginN = LGN_FULL;
  $DB->MemId = $adminId; # InsertVisitTran() uses $DB->MemId
  # Visits
  $set = "LoginN=$loginN,MemId=$adminId,Bits=$adminBits,Email='$emailEsc',DName='$pNameEsc',EditT=$DB->TnS";
  $DB->StQuery("Update Visits Set $set Where Id=$DB->VisId");
  # #wc if !DEV exclude next line
  LogIt("Registration Login: Visit $DB->VisId updated with $set");
  InsertVisitTran(V_LI, LGN_FULL);
}

$DB->commit();

# OK. Send emails
require '../../../Com/emails/RegWelcome.inc';
# $aName, $gName, $pName, $email, $uname, $pw, $peoId

require '../../../Com/emails/RegAdvice.inc';
# Uses $aName, $agentId, $pName, $peoId, $adminId, $email
LogIt("Registration emails sent");

# Return             Dat
# == 0 OK            Email | DName | LoginN | Welcome message
# == 1 Email in use  Error message | | |
LogThePost(OK, "$email$pName$loginN<h2 class=c>Welcome to Braiins</h2><p class=c>A confirmation email has been sent to you.</p><p class=c>You are now logged in, and can go to the Braiins Desktop to look at the sample entities and/or start processing accounts.</p>");
##########

function AddLog($msg) {
  global $RegLog;
  $RegLog .= $msg . '
';
}

function LogThePost($ret, $dat) {
  global $DatA, $RegLog;
  # Log the post to the log REG_LOG
  AddLog("Posted values:");
  foreach ($DatA as $field=>$value)
    AddLog("$field: $value");

  $retA = explode('', $dat);
  AddLog('Result: ' . (!$ret ? 'OK' : "Fail with msg to client of '{$retA[0]}'"));
  $n = count($retA);
  while ($n < 4) { # 4 return fields expected
    $dat .= '';
    $n++;
  }
  if ($fh = fopen(REG_LOG, "a")) {
    if (fwrite($fh, $RegLog) === false)
      LogIt('Reg.php was unable to write to ' . REG_LOG . ". Data follows:\n$RegLog");
    fclose($fh);
  }else
    LogIt('Reg.php was unable to open ' . REG_LOG . ". Data follows:\n$RegLog");
  #LogIt($dat);
  # $dat='Reg Test';
  AjaxReturn($ret, $dat);
}

