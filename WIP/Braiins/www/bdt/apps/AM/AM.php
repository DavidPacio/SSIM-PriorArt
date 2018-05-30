<?php /* Copyright 2011-2013 Braiins Ltd

/bdt/apps/AM/AM.php
Server ops for AM Admin Members

History:
10.05.12 Started
19.10.12 Updated
12.03.13 Locking added

ToDo djh??
====
For New add check for person already being in the DB as per Reg.php

*/
$AppAid  = 'AM';
$AppName = 'Admin Members';
require '../../Base.inc';
$AppEnum = BDT_AM;
require Com_Inc.'FuncsBraiins.inc';
Start();
SessionOpenBDT(); # BD->*, AgentId, EntityId, TZO, MLevel

# Op       Dat Rec  Dat Returned
# I Init   []       OK Members select options | json array of data for self
# D Delete MemId    OK|1 Alert Message
# F Fetch  MemId    OK json array of data for member
# N New    Form     OK|1 <MemId | Alert Message>
# S Save   Form     OK|1|2|3|4 For OK:< 0 | DName	MLevel	MBits when self>; for R>0 ret is alert message: 1 if unable to save edits due to a duplicate email, 2 on Admin AP rejection (2 cases), 3 on Admin level edit rejection, 4 on lock fail

# Check Member permission
($DB->Bits & AP_Members) || Error(ERR_NOT_AUTH);
$MemId = $DB->MemId;

# I D F N S
switch ($Op) {
  case Op_I: # Init
    # Dat: Members select options | json array of data for self
    # Build the Members select options with self selected
    $DB->GetBDbLock(T_B_People, $MemId, Lock_Read, function($why){Error("Sorry, Members cannot be edited currently as $why.");}); # Get a Read lock
    $opts = '<option value=0>~ Click to Select ~</option>';
    $res = $DB->ResQuery("Select M.Id,P.DName From People M Join People P on P.Id=M.PeoId Where M.AgentId=$AgentId And Not M.Bits&1 Order by P.FName,P.GName");
    $DB->RelBDbLocks(); # Release the Read lock
    while ($o = $res->fetch_object()) {
      $memId = (int)$o->Id;
      $selected = $memId == $MemId ? ' selected' : '';
      $opts .= "<option$selected value=$memId>$o->DName&nbsp;</option>"; # &nbsp; for FF djh?? Do only for the longest one?
    }
    $res->free();
    # Plus self
    AjaxReturn(OK, "$opts".MemberData($MemId));

  case Op_D: # Delete
    ($DB->Bits & AP_Delete) || Error(ERR_NOT_AUTH);
    $dMemId = Clean(array_shift($DatA), FT_INT);
    !count($DatA) || Error(ERR_CLIENT_DATA);
    # Get a Login lock -> Write Lock if obtained
    $DB->GetBDbLock(T_B_People, $dMemId, Lock_Login, function($why){AjaxReturn(1, "Sorry, [DName] cannot be deleted currently as $why.");});
    # Get Previous Member Info
    $dMBits = $DB->OneQuery("Select Bits From People Where Id=$dMemId");
    if ($dMBits&AP_Admin) { # Administrator
      if (!$DB->OneQuery("Select count(*) From People Where AgentId=$AgentId And Id!=$dMemId And not Bits&1 And Bits&".AP_Admin))
        # No other Admin
        AjaxReturn(1, 'There needs to be at least one Administrator, but there is no other. Thus [DName] cannot be deleted unless you add/edit another member as an Administrator.');
      if (!$DB->OneQuery("Select count(*) From People Where AgentId=$AgentId And Id!=$dMemId And Level=9 And not Bits&1 And Bits&".AP_Admin))
        # No other Admin with Level=9
        AjaxReturn(1, 'There needs to be at least one Administrator with an Access Level of 9, but there is no other. Thus [DName] cannot be deleted unless you add/edit another member as an Administrator with a Level of 9.');
    }
    # LogIt("AM Delete MemId=$MemId, dMBits=$dMBits, dMBits | MB_DELETE=".($dMBits | MB_DELETE));
    # Update the record to set the delete bit.
    $DB->UpdateMaster(T_B_People, ['Bits' => $dMBits | MB_DELETE], ['Bits' => $dMBits], $dMemId);
    AjaxReturn(OK, 0);

  case Op_F:
    AjaxReturn(OK, MemberData(Clean(array_shift($DatA), FT_INT)));

  case Op_N: # New
    # Dat: GName | FName | DName | Email | Password | RoleN | DeptN | Access Level | 10 x Access Permissions CBs = 18 fields
    # Assemble data from client (using l/c initial letter to var name) and check it
    $gName = Clean(array_shift($DatA), FT_STR);
    $fName = Clean(array_shift($DatA), FT_STR);
    $pName = Clean(array_shift($DatA), FT_STR);
    $email = Clean(array_shift($DatA), FT_EMAIL);
    $pw    = Clean(array_shift($DatA), FT_PW);
    $roleN = Clean(array_shift($DatA), FT_INT);
    $deptN = Clean(array_shift($DatA), FT_INT);
    $level = Clean(array_shift($DatA), FT_INT);
    for ($apBits=0,$bit=AP_First; $bit<=AP_Last; $bit*=2)
      $apBits |= $bit*Clean(array_shift($DatA), FT_INT);
    // $DatA should now be empty
    if (count($DatA)) {
      LogIt("AM.php New Error: Data validity check failed: count(DatA)=".count($DatA));
      Error(ERR_CLIENT_DATA);
    }
    # Add Person
    $colsAA = array(
    'GName' => $gName,
    'FName' => $fName,
    'DName' => $pName,
    'Email' => $email,
    'CtryId'=> CTRY_UK,             # PW to be added when Id is known
    'Bits'  => MB_OK + PB_EmailOK); # turn email confirmed on
    $DB->autocommit(false); // Start transaction
    if (!$PeoId = $DB->DupInsertMaster(T_B_People, $colsAA)) # -> $PeoId == false on duplicate email
      AjaxReturn(1, "Email $email is already in use.<br>Please change it and try again.");
    $DB->StQuery("Update People Set PW='".$DB->real_escape_string(GenPw($pw, $PeoId))."' Where Id=$PeoId"); # Done afterwards to ensure have right Id
    # Add Member
    $mcomment="*	$email";
    $mBits = MB_OK;
    for ($bit=AP_First; $bit<=AP_Last; $bit*=2) {
      $apBit = $apBits&$bit;
      $mBits |= $apBit;
      $mcomment .= TAB.$apBit?1:0;
    }
    $colsAA = array( # in the order required for comment
      'TypeN'  => MT_Member,
      'PeoId'  => $PeoId,
      'AgentId'=> $AgentId,
      'RoleN'  => $roleN,
      'DeptN'  => $deptN,
      'Level'  => $level,
      'Bits'   => $mBits);
    list($MemId, $LogId) = $DB->InsertMaster(T_B_People, $colsAA);
    # Comment = PW(*)	Email	{10 x APs}	RoleN	DeptN	Level	GName	FName	DName	CtryId with TypeN=MT_Member and PeoId not recorded in comment - will need special trans if they change.
    AddAgentTran($AgentId, ATT_AddMember, $LodId, "$mcomment	$roleN	$deptN	$level	$gName	$fName	$pName	".CTRY_UK);
    $DB->commit();
    AjaxReturn(OK, $MemId); # OK MemId

  case Op_S: # Save
    # Dat: GName | FName | DName | Email | Password | RoleN | DeptN | Access Level | 10 x Access Permissions CBs | MemId = 19 fields
    # Assemble data from client (using l/c initial letter to var name) and check it
    $gName = Clean(array_shift($DatA), FT_STR);  #  if unchanged
    $fName = Clean(array_shift($DatA), FT_STR);  #  if unchanged
    $pName = Clean(array_shift($DatA), FT_STR);  #  if unchanged
    $email = Clean(array_shift($DatA), FT_EMAIL);#  if unchanged
    $pw    = Clean(array_shift($DatA), FT_PW);   # '' if unchanged
    $roleN = Clean(array_shift($DatA), FT_INT);
    $deptN = Clean(array_shift($DatA), FT_INT);
    $level = Clean(array_shift($DatA), FT_INT);
    for ($apBits=0,$bit=AP_First; $bit<=AP_Last; $bit*=2)
      $apBits |= $bit*Clean(array_shift($DatA), FT_INT);
    if (!$sMemId = Clean(array_shift($DatA), FT_INT)) # 0 for self after Init i.e. no fetch
      $sMemId=$MemId;
    # $DatA should now be empty
    if (count($DatA)) {
      LogIt("AM.php Save Error: Data validity check failed: count(DatA)=".count($DatA));
      Error(ERR_CLIENT_DATA);
    }
    $DB->GetBDbLock(T_B_People, $sMemId, Lock_Write, function($why){AjaxReturn(4, "Sorry, your edits cannot be saved as $why.");}); # Get a Write lock
    # Get Previous Member Info
    $mA=$DB->AaQuery("Select PeoId,Level,RoleN,DeptN,M.Bits,GName,FName,DName,P.Email,P.PW From People M Join People P on P.Id=M.PeoId Where M.Id=$sMemId");
    $PeoId = (int)$mA['PeoId'];
    $MBits = (int)$mA['Bits'];
    if ($MBits&AP_Admin) { # Administrator
      if (!($apBits&AP_Admin)) { # Being changed to non-Admin
        if (!$DB->OneQuery("Select count(*) From People Where AgentId=$AgentId And Id!=$sMemId And not Bits&1 And Bits&".AP_Admin))
          # No other Admin
          AjaxReturn(2, 'There needs to be at least one Administrator, but there is no other. Thus the Administrator Access Permission must remain set unless you add/edit another member to have Administrator Permission.');
        if (!$DB->OneQuery("Select count(*) From People Where AgentId=$AgentId And Id!=$sMemId And Level=9 And not Bits&1 And Bits&".AP_Admin))
          # No other Admin with Level=9
          AjaxReturn(2, 'There needs to be at least one Administrator with an Access Level of 9, but there is no other. Thus the Administrator Access Permission must remain set unless you add/edit another Administrator to have a Level of 9.');
      }else if ($level<9 && # Administrator with level being changed to < 9. Check that there is at least one other Administrator with Levl 9
        !$DB->OneQuery("Select count(*) From People Where AgentId=$AgentId And Id!=$sMemId And Level=9 And not Bits&1 And Bits&".AP_Admin))
          # No other Admin with Level=9
          AjaxReturn(3, 'There needs to be at least one Administrator with an Access Level of 9, but there is no other. Thus this Access Level must remain at 9 unless you add/edit another Administrator to have a Level of 9.');
    }
    # Update Person
    $colsAA = array(
      'GName' => $gName,
      'FName' => $fName,
      'DName' => $pName);
    # Comment = Differences as GName	FName	DName
    $pcomment='';
    foreach ($colsAA as $col => $dat) {
      $pcomment .= TAB;
      if ($dat==='' || $dat == $mA[$col])
        unset($colsAA[$col]);
      else
        $pcomment .= $dat;
    }
    $mcomment = '';
    if ($pw) {
      $mcomment = '*';
      $colsAA['PW'] = GenPw($pw, $PeoId);
    }
    $mcomment .= TAB;
    if ($email!=='') {
      $mcomment .= $email;
      $colsAA['Email'] = $email;
    }
    $DB->autocommit(false); // Start transaction
    # Person update if required
    # DumpLog('Person colsAA', $colsAA);
    if (count($colsAA) && $DB->DupUpdateMaster(T_B_People, $colsAA, $mA, $PeoId) === false) # false on duplicate email
      AjaxReturn(1, "Email $email is already in use.<br>Please change it and try again.");
    # Update Member
    $mBits = $MBits&MB_STD_BITS;
    for ($bit=AP_First; $bit<=AP_Last; $bit*=2) {
      $mcomment .= TAB;
      $apBit = $apBits&$bit;
      $mBits |= $apBit;
      if ($apBit != ($MBits&$bit))
        $mcomment .= $apBit?1:0;
    }
    $colsAA = array( # in the order required for comment
      'RoleN' => $roleN,
      'DeptN' => $deptN,
      'Level' => $level);
    foreach ($colsAA as $col => $dat) {
      $mcomment .= TAB;
      if ($dat == $mA[$col])
        unset($colsAA[$col]);
      else
        $mcomment .= $dat;
    }
    if ($mBits != $MBits)
      $colsAA['Bits']=$mBits;
    if (count($colsAA))
      // Have Member update to do
      $DB->UpdateMaster(T_B_People, $colsAA, $mA, $sMemId);
    # Comment = change: PW(*=changed)	Email	{10 x APs}	RoleN	DeptN	Level	GName	FName	DName
    AddAgentTran($AgentId, ATT_EdtMember, $mcomment.$pcomment); # AddAgentTran() rtrims comment

    # Update Visits and BDTSessions if necessary
    $vSet = $bSet = '';
    if ($mBits != $MBits) {
      $vSet = ",Bits=$mBits";
      $bSet = ",MBits=$mBits";
    }
    if ($level != $mA['Level'])
      $bSet .= ",MLevel=$level";
    if ($pName !== '' && $pName != $mA['DName'])
      $vSet .= ",DName='$pName'";
    if ($email !== '' && $email != $mA['Email'])
      $vSet .= ",Email='$email'";
    if ($vSet)
      $DB->StQuery(sprintf('Update Visits Set %s,EditT=%s Where Id=%d', substr($vSet,1), $DB->TnS, $DB->VisId));
    if ($bSet)
      $DB->StQuery(sprintf('Update BDTsessions Set %s,EditT=%s Where Id=%d', substr($bSet,1), $DB->TnS, $DB->VisId));
    $DB->commit();
    AjaxReturn(OK, $sMemId === $MemId ? (($pName===''?$mA['DName']:$pName)."	$level	$mBits"):0); # returns = 0 if not self
}

Error(ERR_Op);

function MemberData($memId) {
  global $DB;
  $o=$DB->ObjQuery("Select GName,FName,DName,P.Email PEmail,RoleN,DeptN,Level,M.Bits MBits From People M Join People P on P.Id=M.PeoId Where M.Id=$memId");
  $datA=array(
    $o->GName,
    $o->FName,
    $o->DName,
    $o->PEmail,
    '', // pw
    (int)$o->RoleN,
    (int)$o->DeptN,
    (int)$o->Level);
  for ($bits=(int)$o->MBits,$bit=AP_First; $bit<=AP_Last; $bit*=2)
    $datA[]=($bits&$bit)?1:0;
  return json_encode($datA);
}

