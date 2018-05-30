<?php /* Copyright 2011-2013 Braiins Ltd

/bdt/apps/ER/ER.php
Server ops for ER Entity Reset

History:
08.02.12 Started
12.10.12 Delete speed improved greatly thanks to reorganisation of DBLog and the BDB class
19.10.12 Updated
09.11.12 Revised for change to Bro class
28.12.12 Removed BrosAll delete
14.03.13 Added locking

ToDo
====

*/
$AppAid  = 'ER';
$AppName = 'Reset Entities';
require '../../Base.inc';
$AppEnum = BDT_ER;
require Com_Inc_Tx.'ConstantsRg.inc';
require Com_Inc.'DateTime.inc';
require Com_Inc.'FuncsBraiins.inc';
Start();
SessionOpenBDT(); # BD->*, AgentId, EntityId, TZO, MLevel

# Op       Dat Rec Dat Returned
# I Init   []      n x [Ref  Entity Name  Data Years]
# E rEset  [Ref]   OK

# Check permissions
($DB->Bits & AP_Delete) || Error(ERR_NOT_AUTH);

# I R
switch ($Op) {
  case Op_I: # Init. No Dat. Order doesn't matter as Entities get sorted client side anyway
    # Dat: n x [Ref  Entity Name]
    $yearEndDateBroId = BroId_Dates_YearEndDate;
    $sele = $join = $wher = '';
    for ($y=0; $y<EData_NumYears; ++$y) { # 0, 1, 2, 3
      $sele .= ",B$y.BroStr Y$y";
      $join .= " Left Join Bros B$y On B$y.EntityId=E.Id And B$y.EntYear".($y ? "+$y":'')."=E.CurrYear And B$y.BroId=$yearEndDateBroId";
      $wher .= " Or B$y.BroStr";
    }
    $wher = substr($wher, 4);
    # LogIt("Select Ref,EName$sele From Entities E$join Where E.AgentId=$AgentId And E.Level<=$MLevel And (Bits&2) And ($wher)");
  /*Select Ref,EName,B0.BroStr Y0,B1.BroStr Y1,B2.BroStr Y2,B3.BroStr Y3 From Entities E
      Left Join Bros B0 On B0.EntityId=E.Id And B0.EntYear=E.CurrYear And D0.BroId=8312
      Left Join Bros B1 On B1.EntityId=E.Id And B1.EntYear+1=E.CurrYear And D1.BroId=8312
      Left Join Bros B2 On B2.EntityId=E.Id And B2.EntYear+2=E.CurrYear And D2.BroId=8312
      Left Join Bros B3 On B3.EntityId=E.Id And B3.EntYear+3=E.CurrYear And D3.BroId=8312
      Where E.AgentId=1 And E.Level<=9 And (Bits&2) And (B0.BroStr Or B1.BroStr Or B2.BroStr Or B3.BroStr)  */
    # Get a Read lock for the Agent
    $DB->GetBDbLock(T_B_Agents, $AgentId, Lock_Read, function($why){Error("Sorry, agent '[AName]' is not available currently for Entities Reset operations as $why.");});
    $res = $DB->ResQuery("Select Ref,EName$sele From Entities E$join Where E.AgentId=$AgentId And E.Level<=$MLevel And (Bits&2) And ($wher)");
    if ($res->num_rows) {
      $ents = '';
      while ($o = $res->fetch_object())
        $ents .= "$o->Ref$o->EName".DataYears(array($o->Y0,$o->Y1,$o->Y2,$o->Y3));
      $ents = substr($ents, 1);
    }else
      $ents = 'No entity has data to be reset';
    AjaxReturn(OK, $ents);

  case Op_E: # Reset Dat = Ref
    LogIt("ER Start");
    $ref = Clean($DatA[0], FT_STR, true, $refEsc);
    # Get Entity Info
    $EA = $DB->AaQuery("Select Id,CurrYear,DataState,AcctsState,DGsInUse From Entities Where Ref='$refEsc'");
    $rEntityId = (int)$EA['Id'];
    # Get a Login (Write) lock for the Entity
    $DB->GetBDbLock(T_B_Entities, $dEntityId, Lock_Login, function($why){AjaxReturn(1, "Sorry, entity '[EName]' cannot be reset currently as $why.");});
    # Delete all data without trace other than for the Entity and Agent Trans
    $DB->autocommit(false);
    $prevdId = 0;
    # Delete the BroTrans records
    LogIt('ER Delete the BroTrans records');
    $DB->StQuery("Delete From BroTrans Where BrosId In(Select Id From Bros Where EntityId=$rEntityId)");
    # Delete the Bros records
    LogIt('ER Delete the Bros records');
    $DB->StQuery("Delete From Bros Where EntityId=$rEntityId");
    # Delete the DBLog records
   #$DB->StQuery('Delete From DBLog Where EntityId=$EentityId And TableN In'.ArrayToBracketedCsList(array(T_B_Bros, T_B_BroTrans)));
    LogIt('ER Delete the DBLog records: '.sprintf('Delete From DBLog Where EntityId=%d And TableN In(%d,%d)', $rEntityId, T_B_Bros, T_B_BroTrans));
    $DB->StQuery(sprintf('Delete From DBLog Where EntityId=%d And TableN In(%d,%d)', $rEntityId, T_B_Bros, T_B_BroTrans));
    LogIt('ER Update the Entities record and add the Reset Entity Tran');
    # Remove from Formats if there. Nope. Leave this for Reset. Only do this for Delete.
   #$DB->StQuery("Update Formats Set EntityId=0 Where EntityId=$rEntityId");
    # Update the Entities record
    $ecolsAA = array(
      'CurrYear'       => 0,
      'DataState'      => 0,
      'AcctsState'     => 0,
      'DGsInUse' => 0,
    );
    $DB->UpdateMaster(T_B_Entities, $ecolsAA, $EA, $rEntityId);
    # Add the Reset Entity Tran
    AddEntityTran($rEntityId, 0, BDT_ER, ETA_Reset);
    $DB->commit();
    LogIt("ER Done");
    AjaxReturn(OK);
}

Error(ERR_Op);

function DataYears($dA) {
  $ret='';
  foreach ($dA as $d)
    $ret .= ','.($d ? eeDtoStr(substr($d,2), '%y') : '-'); # substr($d,2) to strip off the leading SrceNChr DatType characters expected to be 13 for these Date Bros
  return trim($ret, ',-');
}

