<?php /* Copyright 2011-2013 Braiins Ltd

/bdt/apps/AA/AA.php
Server ops for AA Admin Account

History:
08.05.12 Started based on EE.php
19.10.12 Updated
11.03.13 Locking added

*/
$AppAid  = 'AA';
$AppName = 'Admin Account';
require '../../Base.inc';
$AppEnum = BDT_AA;
require Com_Inc.'FuncsBraiins.inc';
Start();
SessionOpenBDT(); # BD->*, AgentId, EntityId, TZO, MLevel
#           Dat              #    Dat
# Op        Rec   Ret      Fields Returned
# I Init    []    <OK | 1>   1    <json string for [AName, Description, AddressLine1, AddressLine2, AddressLine3, CityOrTown, CountyRegion, PostalCodeZip] | Alert Message>
# S Save    Form  <OK | 1>   1    <AName | Alert Message>

# Check Member permission
($DB->Bits & AP_Agent) || Error(ERR_NOT_AUTH);

# I S
switch ($Op) {
  case Op_I: # Init
    # Dat: Name | Description | AddressLine1 | AddressLine2 | AddressLine3 | CityOrTown | CountyRegion | PostalCodeZip
    # Get Agent Info from AgentData ADT_AgentInfo record with a Read Lock
    $Data = $DB->StrOneQuery("Select Data From %s Where AgentId=$AgentId And TypeN=". ADT_AgentInfo, T_B_AgentData,
      [$AgentId, function($why){AjaxReturn(1, "Sorry, your Account cannot be edited currently as $why.");}]);
     #[$AgentId, function($why){Error("Sorry, your Account cannot be edited currently as $why.");}]); Could use this form without need for alert code client side as a lock fail here should be rare
    $AgentInfoA = json_decode($Data, true);
    array_pop($AgentInfoA); # pop country while fixed as UK and readonly client side
    # Build return datA to be sent in json form
    $datA = [];
    foreach ($AgentInfoA as $v)
      $datA[] = $v; # Name, Description, AddressLine1, AddressLine2, AddressLine3, CityOrTown, CountyRegion, PostalCodeZip
    AjaxReturn(OK, json_encode($datA));

  case Op_S: # Save
    # Dat: Name | Description | AddressLine1 | AddressLine2 | AddressLine3 | CityOrTown | CountyRegion | PostalCodeZip = 8 fields
    # Data from client using l/c initial letter to var name and check it
    array_push($DatA, 'United Kindom'); # push country on while fixed as UK
    $agentInfoA = [];
    $DB->GetBDbLock(T_B_AgentData, $AgentId, Lock_Write, function($why){AjaxReturn(1, "Sorry, your Account edits cannot be saved as $why.");}); # Get a Write lock
    $DB->autocommit(false); # Start transaction
    # Get Agent Info from AgentData ADT_AgentInfo record
    $aA = $DB->AaQuery("Select Id,Data From AgentData Where AgentId=$AgentId And TypeN=". ADT_AgentInfo);
    $AgentInfoA = json_decode($aA['Data'], true);
    foreach ($AgentInfoA as $k => $v)
      $agentInfoA[$k] = ($t=Clean(array_shift($DatA), FT_STR))===''?$v:$t; # original value if unchanged as indicated by  being passed back
    # $DatA should now be empty
    if (count($DatA)) {
      LogIt("AA.php Save Error: Data validity check failed: count(DatA)=".count($DatA));
      Error(ERR_CLIENT_DATA); #AjaxReturn(1, "Data failed a validity check..");
    }
    $aName = $agentInfoA['Name'];
    if ($aName != $AgentInfoA['Name']) {
      # Name has changed so need to update the Agents record. This can cause a duplicaye key failure.
      if ($DB->DupUpdateMaster(T_B_Agents, ['AName' => $aName], 0, $AgentId) === false)
        AjaxReturn(1, "<span class=wng>The name $aName is in use for another Agent.</span><br>Please change it and try again.");
    }
    $DB->UpdateMaster(T_B_AgentData, ['Data' => json_encode($agentInfoA)], $aA);
    #AddAgentTran($agentId, ATT_Add, $aName);
    $DB->commit();
    AjaxReturn(OK, $aName);

}
Error(ERR_Op);


