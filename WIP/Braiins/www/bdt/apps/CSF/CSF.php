<?php /* Copyright 2011-2013 Braiins Ltd

/bdt/apps/CSF/CSF.php
Server ops for CSF Current entity Set to Final

History:
08.02.12 Started
19.10.12 Updated
14.03.13 Added locking

ToDo
====

*/
$AppAid  = 'CSF';
$AppName = 'Set to Final';
require '../../Base.inc';
$AppEnum = BDT_CSF;
require Com_Inc.'FuncsBraiins.inc';
Start();
SessionOpenBDT(); # BD->*, AgentId, EntityId, TZO, MLevel

# Op       Dat Rec Dat Returned
# I Init   []      OK, case #  reason for case 4
# S Set    []      OK
# U Revert []      OK

# Get Entity Info which all ops use
# Get a Read or Write lock according to Op for the Entity
$DB->GetBDbLock(T_B_Entities, $EntityId, $Op === Op_I ? Lock_Read : Lock_Write, function($why){Error("Sorry, entity '[EName]' is not available currently for 'Set to Final' operations as $why.");});
$EA = $DB->AaQuery("Select StatusN,CurrYear,Level,DataState,AcctsState,Bits From Entities Where Id=$EntityId");
foreach ($EA as $k => $v)
  $$k = (int)$v; # -> $StatusN, $CurrYear, $Level, $DataState, $AcctsState

# Check whether login to this entity still OK
(($Bits & MB_STD_BITS) === MB_OK && $Level <= $MLevel && $StatusN < EStatus_Dormant) || Error(ERR_EntityLoginNok); # Entity Delete bit set, or OK bit not set, or Level changed, or StatusN not one of the active ones
# Check permission
($DB->Bits & AP_Entity) || Error(ERR_NOT_AUTH);

# I S U
switch ($Op) {
  case Op_I: # Init
    # Dat: case  reason for case 4
    # Cases:
    # 1: Set to Final, can't be reversed by this member
    # 2: Set to Final, can be reversed by this member
    # 3: Not Final and OK to Set To Final
    # 4: Not Final and Not OK to Set To Final because in field 2
    $cos = '';
    if ($AcctsState & EASB_Final)
      # Cases 1 and 2
      $case = ($DB->Bits & AP_Admin) ? 2 : 1;
    else{
      # Cases 3 and 4
      $finalCand = $AcctsState & EASB_FinalCand;
      if ($DataState == EDSB_OK && $finalCand)
        $case = 3;
      else{
        $case = 4;
        if ($DataState != EDSB_OK) $cos = "the Data State is '".EntityDataStateStr($DataState).SQ;
        if (!$finalCand) $cos .= ($cos?', and ':'')."the Accounts State is '".EntityAcctsStateStr($AcctsState & 7).SQ; # & 7 to strip off EASB_Down
      }
    }
    AjaxReturn(OK, "$case$cos");

  case Op_S: # Set
    $DB->autocommit(false);
    $acctsState = EASB_Final | ($AcctsState & EASB_Down);
    UpdateEntity($EntityId, 'AcctsState', $acctsState, $EA); # $EA is the Entity info AA read above
    AddEntityTran($EntityId, $CurrYear, BDT_CSF, ETA_Final, 0, ETA_SetAcctsState, $acctsState);
    $DB->commit();
    AjaxReturn(OK);

  case Op_U: # Revert
    $DB->autocommit(false);
    $acctsState = EASB_FinalCand | ($AcctsState & EASB_Down);
    UpdateEntity($EntityId, 'AcctsState', $acctsState, $EA); # $EA is the Entity info AA read above
    AddEntityTran($EntityId, $CurrYear, BDT_CSF, ETA_FinalRev, 0, ETA_SetAcctsState, $acctsState);
    $DB->commit();
    AjaxReturn(OK);
}

Error(ERR_Op);

