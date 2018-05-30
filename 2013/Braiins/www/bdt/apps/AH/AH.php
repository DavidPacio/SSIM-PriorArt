<?php /* Copyright 2011-2013 Braiins Ltd

/bdt/apps/AH/AH.php
Server ops for the Ajax calls to edit Headings

History:
07.03.11 Written
29.12.11 Added htmlspecialchars($str, ENT_QUOTES) call for saved data
30.12.11 Revised to work with AgentData ADT_Headings record rather than Headings table
19.10.12 Updated
11.03.13 Locking added

ToDo
====

*/
$AppAid  = 'AH';
$AppName = 'Admin Headings';
require '../../Base.inc';
$AppEnum = BDT_AH;
Start();
SessionOpenBDT(); # DB->*, AgentId, EntityId, TZO, MLevel
#         Dat                             #    Dat
# Op      Rec                  Ret      Fields Returned
# I Init  []                   <OK | 1>   1    <n x [ Ref  Master Heading  Agent Heading, '' if same] | Alert text>
# S Save  [Ref, Agent Heading] <OK | 1>   1    <0 | Alert text>

# Check permissions
($DB->Bits & AP_Agent) or Error(ERR_NOT_AUTH);

# I S
switch ($Op) {
  case Op_I: # Init. No Dat. Order doesn't matter as headings get sorted client side anyway
    # Read Master and Agent Headings
    # Transfer agent heading as '' if same as master to save transfer size.
    $aHdgsA = 0;
    $DB->GetBDbLock(T_B_AgentData, $AgentId, Lock_Read, function($why){AjaxReturn(1, "Sorry, Headings cannot be edited currently as $why.");}); # Get a Read lock
    $res = $DB->ResQuery("Select AgentId,Data From AgentData Where AgentId In(1,$AgentId) And TypeN=".ADT_Headings);
    $DB->RelBDbLocks(); # Release the Read lock
    while ($o = $res->fetch_object())
      if ($o->AgentId == 1)
        $mHdgsA = json_decode($o->Data, true);
      else
        $aHdgsA = json_decode($o->Data, true);
    $res->free();
    $Dat = '';
    foreach ($mHdgsA as $ref => $mHdg) {
      if (isset($aHdgsA[$ref])) {
        if (($aHdg = $aHdgsA[$ref]) == $mHdg) # could be == if master edited and now happens to == previously different agent heading
          $aHdg = '';
      }else
        $aHdg = '';
      $ref = substr($ref,0,-1); # strip trailing H
      $Dat .= "$ref$mHdg$aHdg";
    }
    AjaxReturn(OK, substr($Dat,1));

  case Op_S: # Save Dat = Ref | Agent Heading
    $ref  = Clean($DatA[0], FT_STR);
  //$aHdg = htmlspecialchars(Clean($DatA[1], FT_STR), ENT_QUOTES); # htmlspecialchars() to encode the html/xml special characters & < > ' " if any
    $aHdg = Clean($DatA[1], FT_STR);

    # Cases where AgentId > 1 i.e. not for Braiins Master
    # - different from Master but no previous A record -> Insert
    # - different from Master but previous A record -> Update
    # - same as Master but previous A record -> Delete from A record and delete the recored if empty
    # Cases where AgentId == 1 i.e. Braiins Master
    # - different from previous -> Update
    # No New records here i.e. Ref is expected to exist for the Master at least.
    $ref .= 'H'; # add back trailing H
    $aHdgsA = [];
    $aId = 0;
    $DB->GetBDbLock(T_B_AgentData, $AgentId, Lock_Write, function($why){AjaxReturn(1, "Sorry, your Headings edit cannot be saved as $why.");}); # Get a Write lock
    $DB->autocommit(false); # Start transaction
    # Read Master and Agent Headings
    $res = $DB->ResQuery("Select Id,AgentId,Data From AgentData Where AgentId In(1,$AgentId) And TypeN=".ADT_Headings);
    while ($o = $res->fetch_object())
      if ($o->AgentId == 1)
        $mHdgsA = json_decode($o->Data, true);
      else{
        $aId = (int)$o->Id;
        $aHdgsA = json_decode($o->Data, true);
      }
    $res->free();
    if (!isset($mHdgsA[$ref]))
      Error("Headings Save for Agent $AgentId for Ref '$ref' edited to '$aHdg' failed with no Master '$ref' heading found");
    if ($AgentId > 1) {
      # Not Braiins Master
      $mHdg = $mHdgsA[$ref];
      if ($aHdg != $mHdg)
        $aHdgsA[$ref] = $aHdg;
      else
        unset($aHdgsA[$ref]); # delete agent record if it exists
      if (count($aHdgsA))
        UpdateAgentData($AgentId, ADT_Headings, $aHdgsA);
      else if ($aId) # $aId should exist if here
        $DB->DeleteMaster(T_B_AgentData, $aId);
      else
        # No A record but A edited to equal M so there should be an A record to delete but there isn't
        Error("Headings Save for Agent $AgentId for Ref '$ref' edited to '$aHdg' which returned the Agent headings record to empty so the record should have been deleted but no record existed to delete");
    }else{
      # Braiins Master - Update M
      $mHdgsA[$ref] = $aHdg;
      UpdateAgentData($AgentId, ADT_Headings, $mHdgsA);
    }
    $DB->commit();
    AjaxReturn(OK, 0);
}

Error(ERR_Op);

function UpdateAgentData($agentId, $typeN, $datA) {
  global $DB;
  $colsAA = array(
    'AgentId' => $agentId,
    'TypeN'   => $typeN,
    'Data'    => json_encode($datA));
  if ($pA = $DB->OptAaQuery("Select * from AgentData Where AgentId=$agentId And TypeN=$typeN"))
    $DB->UpdateMaster(T_B_AgentData, $colsAA, $pA);
  else
    $DB->InsertMaster(T_B_AgentData, $colsAA); # Don't expect this case
}


