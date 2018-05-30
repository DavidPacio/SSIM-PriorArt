<?php /* Copyright 2011-2013 Braiins Ltd

/bdt/apps/EN/EN.php
Server ops for EN New Entity

History:
16.04.12 Started
19.10.12 Updated
14.03.13 Added locking

*/
$AppAid  = 'EN';
$AppName = 'New Entity';
require '../../Base.inc';
$AppEnum = BDT_EN;
require Com_Inc.'FuncsBRL.inc';
require Com_Inc.'FuncsBraiins.inc';
Start();
SessionOpenBDT(); # BD->*, AgentId, EntityId, TZO, MLevel. EntityId is previous EntityId not used here.

# Op       Dat Rec Dat Returned
# I Init   []      OK CoSize RBs html | Manager select options | RR CBs html | Agent Credits | MemId | EntityTypeCreditsA | EntitySizeCreditsA | Reduced DimGroupsA
# S Save   Form    OK|1|2 Agent Credits if R < 2  Alert Message
# C Change [Ref]   OK ELevel
# R Refresh []     OK Agent Credits

# Check Member permission
($DB->Bits & AP_Entity) || Error(ERR_NOT_AUTH);

# I S C R
switch ($Op) {
  case Op_I: # Init
    # No locking
    # Dat: CoSize RBs html | Manager select options | RR CBs html | Agent Credits | MemId | EntityTypeCreditsA | EntitySizeCreditsA | Reduced DimGroupsA
    # Get Credits
    $Credits = $DB->OneQuery("Select Credits From Agents Where Id=$AgentId");
    # Build the UK GAAP CoSize radio button html
    $csh = '';
    for ($i=ES_Small; $i<=ES_Large; ++$i) {
      $n   = EntitySizeStr($i);
      $checked = $i==ES_SmallFRSSE ? ' checked' : ''; # Default to SmallFRSSE
      $crs = $EntitySizeCreditsA[$i] ? " ($EntitySizeCreditsA[$i])" : '';
      $csh .= "<input name=ENCS id=ENr$i type=radio value=$i$checked> <label for=ENr$i>$n$crs</label>";
    }
    # Build the Manager (staff) select options
    $opts = '';
    $res = $DB->ResQuery(sprintf('Select Id,DName from People Where AgentId=%d And Bits&%d=%d Order by P.FName', $AgentId, MB_STD_BITS | PB_Member, MB_OK | PB_Member));
    while ($o = $res->fetch_object())
      $opts .= "<option value=$o->Id>$o->DName&nbsp;</option>"; # &nbsp; for FF
    $res->free();
    # Build the Reporting Requirements checkboxes html to go into the ENdRR div, and the reduced version of DimGroupsA with [Credits, Small]
    $rrh = '<div class=fl>';
    $rdgA = array();
    foreach ($DimGroupsA as $dg => $dgA) {
      $n   = $dgA[DGI_Name];
      $crs = $dgA[DGI_Credits];
      $rdgA[] = array($crs, $dgA[DGI_ExSmall]); # Credits, ExSmall
      $crs = $crs ? " ($crs)" : '';
      $u   = $dgA[DGI_User] ? '' : ' disabled';
      $t   = $dgA[DGI_Tip];
      $rrh .= "<span title='$t'><input id=ENc$dg type=checkbox$u> <label for=ENc$dg>$n$crs</label></span><br>";
      if ($dg == DG_FinInstrs) # 9
        $rrh .= "</div><div class='fl pl10'>";
    }
    $rrh .= '</div>';
    AjaxReturn(OK, "$csh$opts$rrh$Credits$DB->MemId".json_encode($EntityTypeCreditsA).''.json_encode($EntitySizeCreditsA).''.json_encode($rdgA));

  case Op_S: # Save New
    # Dat: Name  Reference  ESizeId  Manager Id  Level  19 x Dim Group checkbox 0 or 1s  ENCr = 25 fields
    # Assemble data from client and check it
    $TxnId   = TxN_UK_GAAP_DPL; # /- Initial defaults djh?? Need to change to be part of the user selection
    $ETypeId = ET_PrivateLtdCo; # |
    $EName   = Clean(array_shift($DatA), FT_STR);
    $ERef    = Clean(array_shift($DatA), FT_STR);
    $ESizeId = Clean(array_shift($DatA), FT_INT);
    $mngrId  = Clean(array_shift($DatA), FT_INT);
    $level   = Clean(array_shift($DatA), FT_INT);
    $NeCrs     = $EntityTypeCreditsA[$ETypeId]  +  $EntitySizeCreditsA[$ESizeId];
    $crComment = $EntityTypeCreditsA[$ETypeId].TAB.ZeroToEmpty($EntitySizeCreditsA[$ESizeId]); # Entity Type Credits	CoSize Credits
    # Build DGsAllowed
    $DGsAllowed = 0;
    for ($dg=0,$bit=1; $dg<DG_Num; ++$dg, $bit *= 2)
      if (Clean(array_shift($DatA), FT_INT)) {
        $DGsAllowed |= $bit;
        $crs    = $DimGroupsA[$dg][DGI_Credits];
        $NeCrs += $crs;
        $crComment .= "	$dg	".ZeroToEmpty($crs);
      }
    $NeCrsCs = Clean(array_shift($DatA), FT_INT); // Client side calc of credits
    // $DatA should now be empty and client side credits should equal the credits calc here.
    (!count($DatA) && $NeCrs === $NeCrsCs) || Error(ERR_CLIENT_DATA);
    # Get Write lock for Agent
    $DB->GetBDbLock(T_B_Agents, $AgentId, Lock_Write, function($why){AjaxReturn(1, "$CreditsSorry, the new entity cannot be created currently as $why.");});
    # Get Agent Info from Agents
    $AA = $DB->AaQuery("Select Credits,Bits From Agents Where Id=$AgentId");
    #foreach ($AA as $k => $v)
    #  $$k = (int)$v; # -> $Credist, $Bits
    $Credits = (int)$AA['Credits'];
    $Bits    = (int)$AA['Bits'];
    # Check whether access to this Agent is still OK
    (($Bits & MB_STD_BITS) === MB_OK) || Error(ERR_AgentLoginNok); # Agent Delete bit set, or OK bit not set.
    # Check if credits are still OK
    if ($NeCrs > $Credits)
      AjaxReturn(1, "$CreditsInsufficient credits were available to add the entity, presumably due to activity by another person.");

    $DB->autocommit(false); // Start transaction
    # Returns EntityId or false on AgentId + AgentRef duplicate
    # Duplicate names are not prevented. Perhaps they should be.
    #  Credits expected to be passed as <= 0
    #  const ATT_AddEntity  Add Entity  Ref	EName	Identifier	CtryId	TxnId	ETypeId	ESizeId	ManagerId	Level	Bits	DGsAllowed	Entity Type Credits	CoSize Credits{	Dim Group	DG credits...}
    #    AddEntity($agentId, $eRef, $eName, $ident,            $ctryId, $txnId, $eTypeId, $eSizeId, $mngrId, $level, $bits,     $DGsAllowed, $credits, $crComment, $descr='', $enComment=''
    if (!AddEntity($AgentId, $ERef, $EName, 'djh?? fix ident', CTRY_UK, $TxnId, $ETypeId, $ESizeId, $mngrId, $level, EB_Active, $DGsAllowed, -$NeCrs, $crComment))
      AjaxReturn(1, "$Credits<span class=wng>Reference $ERef is already in use.</span><br>Please change it and try again.");
    $DB->commit();
    $Credits -= $NeCrs;
    AjaxReturn(OK, "$Credits$EName, Reference $ERef has been added.");

  case Op_C: # Change to This Entity from the Dialog after Save. Dat = Ref
    $ERef = Clean(array_shift($DatA), FT_STR, true, $refEsc);
    !count($DatA) || Error(ERR_CLIENT_DATA);
    $eO = $DB->ObjQuery("Select Id,Level from Entities Where Ref='$refEsc'"); # not expected to fail so let it error if it does
    $DB->StQuery("Update BDTsessions Set EntityId=$eO->Id,EditT=$DB->TnS Where Id=$DB->VisId");
    AjaxReturn(OK, $eO->Level); # ELevel on client side

  case Op_R: # Refresh credits
    AjaxReturn(OK, $DB->OneQuery("Select Credits From Agents Where Id=$AgentId"));

}

Error(ERR_Op);

