<?php /* Copyright 2011-2013 Braiins Ltd

/bdt/apps/ED/ED.php
Server ops for ED Entity Delete

History:
13.02.12 Started
12.10.12 Delete speed improved greatly thanks to reorganisation of DBLog and the BDB class
19.10.12 Updated
09.11.12 Revised for change to Bro class
28.12.12 Removed BrosAll delete
14.03.13 Added locking

ToDo
====


*/
$AppAid  = 'ED';
$AppName = 'Delete Entities';
require '../../Base.inc';
$AppEnum = BDT_ED;
require Com_Inc_Tx.'ConstantsRg.inc';
require Com_Inc.'DateTime.inc';
require Com_Inc.'FuncsBraiins.inc';
Start();
SessionOpenBDT(); # BD->*, AgentId, EntityId, TZO, MLevel

# Op       Dat Rec Dat Returned
# I Init   []      n x [Ref  Entity Name  Data Years]
# D Delete [Ref]   <OK | 1> <0 | Alert message>

# Check permissions
($DB->Bits & AP_Delete) || Error(ERR_NOT_AUTH);

# I D
switch ($Op) {
  case Op_I: # Init. No Dat. Order doesn't matter as Entities get sorted client side anyway
    # Dat: n x [Ref  Entity Name]
    $yearEndDateBroId = BroId_Dates_YearEndDate;
    $sele = $join = '';
    for ($y=0; $y<EData_NumYears; ++$y) {
      $sele .= ",B$y.BroStr Y$y";
      $join .= " Left Join Bros B$y On B$y.EntityId=E.Id And B$y.EntYear".($y ? "+$y":'')."=E.CurrYear And B$y.BroId=$yearEndDateBroId";
    }
    # Get a Read lock for the Agent
    $DB->GetBDbLock(T_B_Agents, $AgentId, Lock_Read, function($why){Error("Sorry, agent '[AName]' is not available currently for Entities Delete operations as $why.");});
    $res = $DB->ResQuery("Select Ref,EName$sele From Entities E$join Where E.AgentId=$AgentId And E.Level<=$MLevel And not E.Bits&1");
    if ($res->num_rows) {
      $ents = '';
      while ($o = $res->fetch_object())
        $ents .= "$o->Ref$o->EName".DataYears([$o->Y0,$o->Y1,$o->Y2,$o->Y3]);
      $ents = substr($ents, 1);
    }else
      $ents = 'No entity to be deleted';
    AjaxReturn(OK, $ents);

  case Op_D: # Delete Dat = Ref
    $ref = Clean($DatA[0], FT_STR, true, $refEsc);
    # Get Entity Id and Bits for the Update
    $EA = $DB->AaQuery("Select Id,Bits From Entities Where Ref='$refEsc'");
    $dEntityId = (int)$EA['Id'];
    # Get a Login (Write) lock for the Entity
    $DB->GetBDbLock(T_B_Entities, $dEntityId, Lock_Login, function($why){AjaxReturn(1, "Sorry, entity '[EName]' cannot be deleted currently as $why.");});
    # Delete all data without trace other than for the Agent Trans
    $DB->autocommit(false);
    # Delete the BroTrans records
    $DB->StQuery("Delete From BroTrans Where BrosId In(Select Id From Bros Where EntityId=$dEntityId)");
    # Delete the Bros records
    $DB->StQuery("Delete From Bros Where EntityId=$dEntityId");
    # Delete all the DBLog records for the Entity
    $DB->StQuery("Delete From DBLog Where EntityId=$dEntityId");
    # Remove from Formats if there
    $DB->StQuery("Update Formats Set EntityId=0 Where EntityId=$dEntityId");
    # Update the Entities record to set the delete bit
    $DB->UpdateMaster(T_B_Entities, ['Bits' => MB_DELETE], $EA, $dEntityId);
    # Add the Delete Agent Tran
    AddAgentTran($AgentId, ATT_DelEntity);
    $DB->commit();
    # LogIt("ED Done");
    AjaxReturn(OK, OK);
}

Error(ERR_Op);

function DataYears($dA) {
  $ret='';
  foreach ($dA as $d)
    $ret .= ','.($d ? eeDtoStr(substr($d,2), '%y') : '-'); # substr($d,2) to strip off the leading SrceNChr DatType characters expected to be 13 for these Date Bros
  return trim($ret, ',-');
}

