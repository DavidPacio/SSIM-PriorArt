<?php /* Copyright 2011-2013 Braiins Ltd

/bdt/apps/EE/EE.php
Server ops for EE Edit Entity

History:
04.05.12 Started based on EN.php
19.10.12 Updated
14.03.12 Added locking

*/
$AppAid  = 'EE';
$AppName = 'Edit Entity';
require '../../Base.inc';
$AppEnum = BDT_EE;
require Com_Inc.'FuncsBRL.inc';
require Com_Inc.'FuncsBraiins.inc';
Start();
SessionOpenBDT(); # BD->*, AgentId, EntityId, TZO, MLevel

# Op        Dat Rec Dat Returned
# I Init    []      OK   CoSize RBs html | Manager select options | RR CBs html | Agent Credits | EName | ERef | CoSize | ManagerId | Level | EntityTypeCreditsA | EntitySizeCreditsA | Reduced DimGroupsA
# S Save    Form    OK|1|2 Agent Credits if R < 2  ERef or Alert Message
# R Refresh []      OK   Agent Credits

# Check Member permission
($DB->Bits & AP_Entity) || Error(ERR_NOT_AUTH);

# I S R
switch ($Op) {
  case Op_I: # Init
    # Dat: CoSize RBs html | Manager select options | RR CBs html | Agent Credits | EName | ERef | CoSize | ManagerId | Level | EntityTypeCreditsA | EntitySizeCreditsA | Reduced DimGroupsA
    # Get a Read lock for the Entity
    $DB->GetBDbLock(T_B_Entities, $EntityId, Lock_Read, function($why){Error("Sorry, entity '[EName]' is not currently available for editing as $why.");});
    # Get Entity Info and Agent Credits
    $eA = $DB->AaQuery("Select Ref,EName,ESizeId,Level,ManagerId,DGsInUse,DGsAllowed,Credits From Entities E Join Agents A on E.AgentId=A.Id where E.Id=$EntityId");
    $DB->RelBDbLocks(); # Release the Read lock
    foreach ($eA as $k => $v)
      if (is_numeric($v))
        $$k = (int)$v; # -> $ESizeId, $Level, $ManagerId, $DGsInUse, $DGsAllowed, $Credits as ints
      else
        $$k = $v; # -> $Ref, $EName as strings

    $small = $ESizeId <= ES_SmallFRSSE;

    # Build the Manager (staff) select options
    $opts = '';
   #$res = $DB->ResQuery(sprintf('Select Id,DName from People Where AgentId=%d And Not Bits&%d And Bits&%d Order by FName', $AgentId, MB_DELETE, PB_Member));
    $res = $DB->ResQuery(sprintf('Select Id,DName from People Where AgentId=%d And Bits&%d=%d Order by FName', $AgentId, MB_STD_BITS | PB_Member, MB_OK | PB_Member));
    while ($o = $res->fetch_object())
      $opts .= "<option value=$o->Id>$o->DName&nbsp;</option>"; # &nbsp; for FF as '' no good even with white-space:pre
    $res->free();

    # Build the Reporting Requirements checkboxes html to go into the ENdRR div, and the reduced version of DimGroupsA with [Credits, ExSmall, Allowed=checked]
    $rrh = '<div class=fl>';
    $rdgA = array();
    $bit = 1;
    $hasSmallInUse = 0;
    foreach ($DimGroupsA as $dg => $dgA) {
      $iu  = $DGsInUse & $bit;
      $al  = ($DGsAllowed & $bit)?1:0; # Allowed == Selected == Checked

      if ($iu && !$al) { # should not happen
        $iu = 0;
        LogIt("EE.php Op_I Error: For entity $EntityId DimGroup $dg DGsInUse is set when DGsAllowed isn't. Continuing with value unset.");
      }
      if ($exSmall = $dgA[DGI_ExSmall]) {
        # An ExSmall DimGroup
        if ($small && ($iu || $al)) {
          # Entity is small but one of iu or all is set which should not be
          if ($iu) {
            $iu = 0;
            LogIt("EE.php Op_I Error: For entity $EntityId ExSmall DimGroup $dg DGsInUse is set but company is small. Continuing with value unset.");
          }
          if ($al) {
            $al = 0;
            LogIt("EE.php Op_I Error: For entity $EntityId ExSmall DimGroup $dg DGsAllowed is set but company is small. Continuing with value unset.");
          }
        }
        if ($iu) $hasSmallInUse = 1; # so the small CoSize radio btns are to be disabled
      }
      $n   = $dgA[DGI_Name];
      $crs = $dgA[DGI_Credits];
      $rdgA[] = array($crs, $exSmall, $al); # Credits, ExSmall, Allowed
      $crs = $crs ? " ($crs)" : '';
      $u   = ($iu || !$dgA[DGI_User]) ? ' disabled' : '';
      $t   = $dgA[DGI_Tip].($iu ? ' (Disabled as this RR is in use.)' : '');
      $rrh .= "<span title='$t'><input id=EEc$dg type=checkbox$u> <label for=EEc$dg>$n$crs</label></span><br>";
      if ($dg == DG_FinInstrs) # 9
        $rrh .= "</div><div class='fl pl10'>";
      $bit *= 2;
    }
    $rrh .= '</div>';

    # Build the UK GAAP CoSize radio button html.
    # Disable the small ones if any ExSmall DGs have been used.
    $csh = '';
    for ($i=ES_Small; $i<=ES_Large; ++$i) { # 1 to 5
      $n   = EntitySizeStr($i);
      $disabled = ($hasSmallInUse && $i <= ES_SmallFRSSE) ? ' disabled' : '';
      $crs = $EntitySizeCreditsA[$i] ? " ($EntitySizeCreditsA[$i])" : '';
      $csh .= "<input name=EECS id=EEr$i type=radio value=$i$disabled> <label for=EEr$i>$n$crs</label>";
    }
    AjaxReturn(OK, "$csh$opts$rrh$Credits$EName$Ref$ESizeId$ManagerId$Level".json_encode($EntityTypeCreditsA).''.json_encode($EntitySizeCreditsA).''.json_encode($rdgA));

  case Op_S: # Save
    # Dat: EName | ERef | ESizeId | Manager Id | Level | 19 x Dim Group checkbox 0 or 1s | EECr = 24 fields
    # Get Previous Entity Info and Agent Credits
    # Get Write locks for Agent and Entity
    $DB->GetBDbLocks([
      [T_B_Agents,   $AgentId,  Lock_Write],
      [T_B_Entities, $EntityId, Lock_Write]
    ], function($why){AjaxReturn(2, "Sorry, your entity '[EName]' edits cannot be saved currently as $why.");});
    $eA = $DB->AaQuery("Select Ref,EName,TxnId,ETypeId,ESizeId,StatusN,Level,ManagerId,DGsAllowed,Credits From Entities E Join Agents A on E.AgentId=A.Id where E.Id=$EntityId");
    foreach ($eA as $k => $v)
      if (is_numeric($v))
        $$k = (int)$v; # -> $TxnId, $ETypeId, $ESizeId, $StatusN, $Level, $ManagerId, $DGsAllowed, $Credits as ints
      else
        $$k = $v; # # -> $Ref, $EName as strings
    # Start of Previous credits calc
    $EntityTypeCredits = $EntityTypeCreditsA[$ETypeId]; # djh?? Needs to change to use BRL.EntityTypes.Credits
    $EntitySizeCredits     = $EntitySizeCreditsA[$ESizeId];
    $Crs = $EntityTypeCredits + $EntitySizeCredits;
    # Assemble data from client (using l/c initial letter to var name) and check it
    $eName     = Clean(array_shift($DatA), FT_STR); #  if unchanged
    $eRef      = Clean(array_shift($DatA), FT_STR); #  if unchanged
    $eSizeId   = Clean(array_shift($DatA), FT_INT);
    $managerId = Clean(array_shift($DatA), FT_INT);
    $level     = Clean(array_shift($DatA), FT_INT);
    $entityTypeCredits = $EntityTypeCreditsA[$ETypeId];
    $coSizeCredits     = $EntitySizeCreditsA[$eSizeId];
    $crs       = $entityTypeCredits + $coSizeCredits;
    $crComment = ZeroToEmpty($entityTypeCredits-$EntityTypeCredits).TAB.ZeroToEmpty($coSizeCredits-$EntitySizeCredits); # Entity Type Credits d	CoSize Credits d
    # Build DGsAllowed while also finishing previous credits calc
    $dimGroupsAllowed = 0;
    $bit=1;
    # LogIt("Base $crs, $Crs");
    foreach ($DimGroupsA as $dg => $dgA) {
      $Al = $DGsAllowed & $bit;
      $al = Clean(array_shift($DatA), FT_INT);
      $dgCrs = $dgA[DGI_Credits];
      if ($Al) {
        $preCrs = $dgCrs;
        $Crs += $dgCrs;
      }else
        $preCrs = 0;
      if ($al) {
        $newCrs = $dgCrs;
        $crs += $dgCrs;
        $dimGroupsAllowed |= $bit;
      }else
        $newCrs = 0;
      if ($newCrs != $preCrs)
        $crComment .= "	$dg	".($newCrs-$preCrs);
      $bit *= 2;
      # LogIt("dg=$dg, $crs, $Crs");
    }
    $crsDiff = $crs - $Crs;
    $crsDiffCs = Clean(array_shift($DatA), FT_INT); // Client side calc of credits (difference)
    // $DatA should now be empty and client side credits diff should equal the credits diff calc here.
    #(!count($DatA) && $crsDiff === $crsDiffCs) || Error(ERR_CLIENT_DATA);
    if (count($DatA) || $crsDiff !== $crsDiffCs) {
      LogIt("EE.php Save Error: Data validity check failed: count(DatA)=".count($DatA)." Update Crs=$crs, Prev Crs=$Crs, crsDiff=$crsDiff, crsDiffCs=$crsDiffCs");
     #AjaxReturn(1, "$CreditsData failed a validity check..");
      Error(ERR_CLIENT_DATA);
    }
    # Check if credits are still OK
    if ($crsDiff > $Credits)
      AjaxReturn(1, "$CreditsInsufficient credits were available to save the edits, presumably due to activity by another person.");

    # Update
    if (($StatusN == EStatus_Demo || $StatusN == EStatus_Test) && $eRef!=='' && $eRef[0] != '~') $eRef = '~' . $eRef; # to ensure Test and Demo entities sort to the end by ref.
    $colsAA = [ # in the order required for comment
      'EName'     => $eName,
      'Ref'       => $eRef,
      'TxnId'     => $TxnId,   # /- these don't change here but included for comment building
      'ETypeId'   => $ETypeId, # |
      'ESizeId'   => $eSizeId,
      'ManagerId' => $managerId,
      'Level'     => $level
    ];
    # Comment = Differences as EName	ERef	TxnId	ETypeId	ESizeId	ManagerId	Level	StatusN	Entity Type Credits	CoSize Credits{	Dim Group	DG credits...}
    $comment = ''; # new value if different, '' o'wise
    foreach ($colsAA as $col => $dat) {
      if ($dat==='' || $dat == $eA[$col]) {
        $comment .= TAB;
        unset($colsAA[$col]);
      }else
        $comment .= $dat.TAB; # final trailing one serves as sep before $crComment
    }
    $comment .= TAB; # for StatusN which doesn't change here
    if ($dimGroupsAllowed != $DGsAllowed)
      $colsAA['DGsAllowed'] = $dimGroupsAllowed; # not included in array above as not included directly in the comment

    $DB->autocommit(false); // Start transaction
    if ($DB->DupUpdateMaster(T_B_Entities, $colsAA, $eA, $EntityId)===false) # can fail on AgentId + AgentRef duplicate
      AjaxReturn(1, "$Credits<span class=wng>Reference $eRef is already in use.</span><br>Please change it and try again.");
    # ATT_AddEntity Add    Entity  EName	Ref	TxnId	ETypeId	ESizeId	ManagerId	Level	StatusN	Entity Type Credits	CoSize Credits{	Dim Group	DG credits...}
    # ATT_EdtEntity Edit   Entity  As for Add above but empty if no change
   #AddAgentTran($agentId, $typeN, $comment=0, $credits=0, $moreA=0)
    AddAgentTran($AgentId, ATT_EdtEntity, $comment.$crComment, -$crsDiff); # AddAgentTran() rtrims comment
    $DB->commit();
    $Credits -= $crsDiff;
    AjaxReturn(OK, "$Credits".($eRef==='' ? $Ref : $eRef)); # $eREf in case it had a ~ prepended here

  case Op_R: # Refresh credits
    AjaxReturn(OK, $DB->OneQuery("Select Credits From Agents Where Id=$AgentId"));

}

Error(ERR_Op);
