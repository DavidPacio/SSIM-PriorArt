<?php /* Copyright 2011-2013 Braiins Ltd

/bdt/apps/EC/EC.php
Server ops for the EC Entity Change (Change Entity) Ajax calls

History:
05.01.12 Started
19.10.12 Updated
03.03.13 Entity Login Locking and entity now unavailable case handling added

ToDo
====

*/
$AppAid  = 'EC';
$AppName = 'Change Entities';
require '../../Base.inc';
$AppEnum = BDT_EC;
Start();
SessionOpenBDT(); # BD->*, AgentId, EntityId, TZO, MLevel

# Op       Dat Rec Dat Returned
# I Init   []      OK, n x [Ref  Entity Name]                         1 field
# C Change [Ref]   OK, ERef  EName  ELevel                         3 fields
#                  1,  n x [Ref  Entity Name]  Error message  ] 3 fields

# Check permissions
($DB->Bits & AP_Read) || Error(ERR_NOT_AUTH);

# I C
switch ($Op) {
  case Op_I: # Init. No Dat.
    AjaxReturn(OK, EntitiesList());
    # ---

  case Op_C: # Change Dat = Ref
    $ref = Clean($DatA[0], FT_STR, true, $refEsc);
    if (($eO = $DB->OptObjQuery("Select Id,AgentId,Ref,EName,Level from Entities Where Ref='$refEsc'")) === false || $eO->Level > $MLevel)
      AjaxReturn(1, EntitiesList()."Sorry, entity '[EName]' is no longer available for you to change to.");
    if ($eO->AgentId != $AgentId) Error("Change Entity AgentId error. Session AgentId=$AgentId, AgentId for change to Entity |$ref| = $eO->AgentId");
    $newEntityId = (int)$eO->Id;
    # Get a Read lock
    $DB->GetBDbLock(T_B_Entities, $newEntityId, Lock_Read, function($why){AjaxReturn(1, EntitiesList()."Sorry, entity '[EName]' is not available currently as $why.");});
    $DB->StQuery("Update BDTsessions Set EntityId=$eO->Id,EditT=$DB->TnS Where Id=$DB->VisId");
    # Release previous Entity Login Lock
    $DB->RelBDbLoginLock(T_B_Entities, $EntityId);
    # Add new persistent Entity Login Lock
    $DB->AddBDbLoginLock(T_B_Entities, $newEntityId);
    AjaxReturn(OK, "$eO->Ref$eO->EName$eO->Level"); # ERef, EName, ELevel on client side. Call releases the Read lock.
}
Error(ERR_Op);

function EntitiesList() {
  global $DB, $AgentId, $EntityId, $MLevel;
  # Dat: n x [Ref  Entity Name]  Order doesn't matter as Entities get sorted client side anyway
  $res = $DB->ResQuery(sprintf('Select Ref,EName From Entities Where AgentId=%d And Id!=%d And Level<=%d And Bits&%d=%d', $AgentId, $EntityId, $MLevel, MB_STD_BITS | EB_Active, MB_OK | EB_Active));
  if ($res->num_rows) {
    $ents = '';
    while ($o = $res->fetch_object())
      $ents .= "$o->Ref$o->EName";
    $ents = substr($ents, 1);
  }else
    $ents = 'No other entity available';
  return $ents;
}


