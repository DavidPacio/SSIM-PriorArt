<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/Utils/UK-GAAP-DPL/CEsNotInBros.php

History:
08.10.12 Started based on UK-GAAP version previously named ConcreteElementsNotInBros.php
         Fixed problem with exclusivity via DiMes
17.01.13 Added provision for NoTags
18.02.13 BD Maps removed, and some adjustments made for the change to CoA Non-Tx input

ToDo djh??
====
Add check for Tx els with no children being Posting type Bros?

Remove use of TxElementsSkipped with those elements no longer in the DB?

ToDo
====

*/
require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsTx.inc';
require Com_Inc_Tx.'ConstantsRg.inc';
require Com_Inc_Tx.'TxElementsSkipped.inc';

Head("CEs Not In Bros: $TxName", true);

# Tx numbers
$tuples =
$items =
$itemsOneHy =
$itemsMulHys =
$itemsOneHyTupid =
$itemsMulHyTupid =

# Items re Bros
$itemsOneHyToDo =
$itemsMulHyToDo =
$itemsOneHyTupidToDo =
$itemsMulHyTupidToDo =
# In Bros
$itemsOneHyDone =
$itemsMulHyDone =
$itemsOneHyTupidDone =
$itemsMulHyTupidDone =
$itemsMulHyDoneViaHySuper =
$itemsMulHyTupidDoneViaHySuper =
# Not In Bros
$itemsOneHyToGo =
$itemsMulHyToGo =
$itemsOneHyTupidToGo =
$itemsMulHyTupidToGo =

$tuplesSkipped = $tupleMembersSkipped =
$itemsSkipped =
# Bro Numbers
#$totalNumBros =
$txBros =
$brosWithTuple =
$txStartEndBros = $uniqueTxStartEndBros = $nonTxStartEndBros =
$masters = $slaves = $noTags =

# Tuples
$tuplesNotStarted =
$tuplesPartlyDone =
$tuplesFullyDone =
$tupleMembersInNotStartedTuples =
$tupleMembersDoneInPartlyDoneTuples =
$tupleMembersToGoInPartlyDoneTuples =
$tupleMembersInFullyDoneTuples = 0;

# Get Tuple and Bro Info
#
$TxidHyidTupixidToBroidsA=# [CE el TxId => [Bro HyId => [TupTxId => BroId]]] djh?? BroId ever used?
$TxidTuplesA =            # [el TxId (MemTxId) => [i => TupId]] Tuples (TupId) for Tx el (MemTxId)
$TuMembersA  =            # [TupTxId => [MemTxId =>1]] Tuple members (MemTxId => 0) for Tuple (TxId) set to 1 if used in Bros
$TuTxIdToTupIdA =         # [TupTxId => TupId]
$StartEndTxIdsUsedA = []; # [TxId => 1] for StartEnd TxIds used for comparison with $StartEndTxIdsGA

/*Tuple Info
Fron TuplePairs
  Id       # Used as TuMeId: 1 to 524
  TupId    # Id of the Tuple as it would be if in a Tuples Table: 1 to 158
  TupTxId  # Elements.Id of the Tuple
  MemTxId  # Elements.Id of a Member of the Tuple */
$res = $DB->ResQuery('Select TupId,TupTxId,MemTxId from TuplePairs');
$tupleMembers = $res->num_rows;
while ($o = $res->fetch_object()) {
  $TupId   = (int)$o->TupId;
  $TupTxId = (int)$o->TupTxId;
  $MemTxId = (int)$o->MemTxId;
  $TxidTuplesA[$MemTxId][]=$TupId;  # [el TxId (MemTxId) => [i => TupId]] Tuples (TupId) for Tx el (MemTxId)
  $TuMembersA[$TupTxId][$MemTxId]=1;# [TupTxId => [MemTxId =>1]] Tuple members (MemTxId => 1) for Tuple (TxId) set to 0 if used in Bros
  $TuTxIdToTupIdA[$TupTxId]=$TupId; # [TupTxId => TupId]
}
$res->free();
$TupIdToTuTxIdA = array_flip($TuTxIdToTupIdA); # [TupId => TupTxId]

# Bro Info
$res = $DB->ResQuery('Select Id,Bits,TxId,Hys,TupId,PeriodSEN From BroInfo Order by Id');
$totalNumBros = $res->num_rows;
while ($o = $res->fetch_object()) {
  $bits = (int)$o->Bits;
  if ($bits & BroB_Slave)  ++$slaves;
  if ($bits & BroB_Master) ++$masters;
  if ($bits & BroB_NoTags) ++$noTags;
  $broId = (int)$o->Id;
  $txId  = (int)$o->TxId;
  $PeriodSEN = (int)$o->PeriodSEN;
  if ($txId) {
    ++$txBros;
    $hys   =      $o->Hys;
    $TupId = (int)$o->TupId;
    if ($PeriodSEN >= BPT_InstSumEnd) {
      ++$txStartEndBros;
      if (!isset($StartEndTxIdsUsedA[$txId])) {
        ++$uniqueTxStartEndBros;
        $StartEndTxIdsUsedA[$txId] = 1;
      }
    }
    foreach (ChrListToIntA($hys) as $hyId) {
      #if (isset($TxidHyidTupixidToBroidsA[$txId][$hyId][$TupId])) 08.10.12 Failing on this for cases where exclusivity is achieved via DiMes use. For now just skip the test.
      #  die("Die: Duplicate $txId/$hyId/$TupId TxId/HyId/TupId error on Bro $broId"); # Die: Duplicate 5284/43/0 TxId/HyId/TupId error on Bro 3105
      $TxidHyidTupixidToBroidsA[$txId][$hyId][$TupId] = $broId;
    }
    if ($TupId) {
      $TuMembersA[$TupIdToTuTxIdA[$TupId]][$txId]=0; # Tuple member is use
      ++$brosWithTuple;
    }
  }else{
    # Non-Tx Based Bros
    if ($PeriodSEN >= BPT_InstSumEnd) ++$nonTxStartEndBros;
  }
}
$res->free();
unset($tupTxIdsA);

# Pass through the Tx Tuples and Item Elements and output the ones not in Bros
#
$file = "/BrosAndTx/CEsNotInBros-$TxName-".gmstrftime('%Y-%m-%d_%H_%M').'.txt';
$Fh = fopen('../..'.$file, 'w');
echo "<h2 class=c>Concrete Elements Not In Bros: $TxName</h2>
<p class=c>The table is also written to the file <a href='Show.php?$file'>Admin$file</a> in tab delimited form.</p>
<p class=c><span class='b L'>Warning:</span> This module checks for Concrete Elements being included in Bros, and reports those which aren't. It does NOT check that included elements are used correctly.<br>Specifically it does not check the Post Type and RO settings of Bros versus the Taxonomy structure and thus provides no assurance that elements which should be available for Posting are in fact so configured in the Bros.</p>
<p class=c><span class='b L'>Note:</span> A <b>'but'</b> comment for an Item with multiple hypercubes like 'HyId 1 done out of 1,10 but 1 is a subset of 10' is a hint that the HyId of the Bro which gives the 'done' HyId (1 in this example) could potentially be changed to the superset HyId (10 in this example),<br>with Excl Dims adjusted if necessary. Making this change could complete usage of the Concrete Element in question with no additional Bro being required.</p>
<table class=mc>
<tr class='b c bg0'><td>Id</td><td class=l>Standard Label</td><td>Hy<br>Id</td><td>Tup<br>Id</td><td>Type</td><td>Comments</td></tr>
";
fwrite($Fh, "Id	Standard Label	HyId	TupId	Type	Comments\n");
#$maxLevel = $maxId = $maxPres = 0;
$res = $DB->ResQuery('Select E.Id,T.Text,Hypercubes,E.TypeN,PeriodN,SignN,SubstGroupN From Elements E Join Text T on T.Id=E.StdLabelTxtId Where SubstGroupN In (1,2) and abstract is null');
$tot = $res->num_rows;
while ($o = $res->fetch_object()) {
  $elId        = (int)$o->Id;
  $substGroupN = (int)$o->SubstGroupN;

  if ($substGroupN === TSG_Tuple) {
    # Tuples
    ++$tuples;
    if (in_array($elId, $TxElementsSkippedA)) {
      ++$tuplesSkipped;
      $tupleMembersSkipped += count($TuMembersA[$elId]);
      foreach ($TuMembersA[$elId] as $memTxId => $v)
        if (!in_array($memTxId, $TxElementsSkippedA))
          echo "Tuple $elId is in the Bros skip list but its member $memTxId is not.<br>";
      continue;
    }
    $numMembers=count($TuMembersA[$elId]);
    $notInBros=0;
    foreach ($TuMembersA[$elId] as $memTxId => $v)
      $notInBros += $v;
    if ($notInBros) {
      # Some or all tuple members not in Bros
      if ($notInBros == $numMembers) {
        # None done yet
        ++$tuplesNotStarted;
        $tupleMembersInNotStartedTuples += $numMembers;
        $t = 'All Tuple Members to go: ';
      }else{
        # Some done
        ++$tuplesPartlyDone;
        $tupleMembersDoneInPartlyDoneTuples += $numMembers - $notInBros;
        $tupleMembersToGoInPartlyDoneTuples += $notInBros;
        $t = 'Tuple Members remaining: ';
      }
      $tA=[];
      foreach ($TuMembersA[$elId] as $memTxId => $v)
        if ($v) $tA[] = $memTxId;
      Output($elId,$o->Text,'',$TuTxIdToTupIdA[$elId],'Tuple',$t.implode(', ', $tA));
    }else{
      ++$tuplesFullyDone;
      $tupleMembersInFullyDoneTuples+=$numMembers;
    }
  }else{
    # Items
    ++$items;
    $nHys = strlen($hys = $o->Hypercubes);
    if (isset($TxidTuplesA[$elId]))  # [CE el TxId => [i => TupId]] Tuples for Tx el
      $tupIsA = $TxidTuplesA[$elId]; # TupIds for this $elId
    else
      $tupIsA = array(0);
    if (in_array($elId, $TxElementsSkippedA)) {
      # Skipped Items
      ++$itemsSkipped;
      if ($nHys <= 1) { # 5462 has no Hy
        # if (!$nHys) echo "$elId has no hypercube<br>";
        # Single hypercube
        ++$itemsOneHy;
        $itemsOneHyTupid += count($tupIsA)-1;
      }else{
        # Multiple hypercubes
        echo "$elId<br>";
        ++$itemsMulHys; # should end up as 132
        $itemsMulHyTupid += $nHys*(count($tupIsA)-1);
      }
      continue;
    }
    # Items to be included in Bros
    if ($nHys <= 1) {
      # if (!$nHys) echo "$elId has no hypercube<br>";
      # Single hypercube
      $hyId=ChrToInt($hys);
      foreach ($tupIsA as $TupId) {
        if ($TupId) {
          ++$itemsOneHyTupid;
          ++$itemsOneHyTupidToDo;
        }else{
          ++$itemsOneHy;
          ++$itemsOneHyToDo;
        }
        if (isset($TxidHyidTupixidToBroidsA[$elId][$hyId][$TupId])) {
          $TupId ? ++$itemsOneHyTupidDone : ++$itemsOneHyDone;
        }else{
          $TupId ? ++$itemsOneHyTupidToGo : ++$itemsOneHyToGo;
          Output($elId,$o->Text,$hyId,$TupId,ElementTypeToStr($o->TypeN));
        }
      }
    }else{
      # Multiple hypercubes
      foreach ($tupIsA as $TupId) {
        $inBrosA = $notInBrosA = [];
        foreach (ChrListToIntA($hys) as $hyId) {
          if ($TupId) {
            ++$itemsMulHyTupid;
            ++$itemsMulHyTupidToDo;
          }else{
            ++$itemsMulHys; # should end up as 132
            ++$itemsMulHyToDo;
          }
          if (isset($TxidHyidTupixidToBroidsA[$elId][$hyId][$TupId])) {
            $TupId ? ++$itemsMulHyTupidDone : ++$itemsMulHyDone;
            $inBrosA[]=$hyId;
          }else{
            # Not in Bros as TxId.HyId.TupId, but is it there via a superset i.e. an HyId for which hyId is a subset
            #$supersA=[];
            $viaSuper=0;
            for ($i=1; $i<=HyId_Max; ++$i) {
              if ($i != $hyId && IsHypercubeSubset($hyId, $i)) {
                if (isset($TxidHyidTupixidToBroidsA[$elId][$i][$TupId])) {
                  # Is in Bros via a superset Hy
                  $TupId ? ++$itemsMulHyTupidDoneViaHySuper : ++$itemsMulHyDoneViaHySuper;
                  $inBrosA[] = array($hyId, $i); # array = via super
                  $viaSuper=1;
                  break;
                }
                #$supersA[]=$i;
              }
            }
            if (!$viaSuper) {
              # no superset found
              $notInBrosA[]=$hyId;
              $TupId ? ++$itemsMulHyTupidToGo : ++$itemsMulHyToGo;
            }
          }
        }
        if (count($notInBrosA)) {
          $hysDecList = ChrListToCSDecList($hys);
          if ($n=count($inBrosA)) {
            $done = $but = '';
            foreach ($inBrosA as $hyId) {
              if (is_array($hyId)) # hyId, super hyId
                $done .= ",$hyId[0](via super $hyId[1])";
              else{
                $done .= ",$hyId";
                # Check if this "done" is a subset of one of the other hys so allowing more to be done via supers
                foreach (ChrListToIntA($hys) as $i)
                  if ($i != $hyId && IsHypercubeSubset($hyId, $i)) {
                    $but .= ",$hyId is a subset of $i";
                    break;
                  }
              }

            }
            $done = PluralWord($n, 'HyId').' '.substr($done,1)." done out of $hysDecList".($but ? ' but '.substr($but,1) : '');
          }else
            $done = "All $hysDecList HyIds to go";
          $type=ElementTypeToStr($o->TypeN);
          if (in_array($elId, $StartEndTxIdsGA)) $type .= '&nbsp;StartEnd';
          foreach ($notInBrosA as $hyId)
            Output($elId,$o->Text,$hyId,$TupId,$type,$done);
        }
      }
    }
  }
}
fclose($Fh);
echo "</table>\n";

# Tx Numbers
$totalTxItemsWithCmbinations = $itemsOneHy + $itemsMulHys + $itemsOneHyTupid+$itemsMulHyTupid;
echo "<b>Tx Numbers:</b><br>
CE Total = $tot (Concrete Tx Elements of Substitution Groups Tuple or Item)<br>
CE Tuples = $tuples with $tupleMembers Members<br>
CE Items = $items<br>
CE Items with one hypercube = $itemsOneHy<br>
CE Items with multiple hypercubes = $itemsMulHys<br>
CE Items with one hypercube and tuple combinations = $itemsOneHyTupid<br>
CE Items with multiple hypercubes and tuple conbinations = $itemsMulHyTupid<br>
CE Items total including hypercube/tuple conbinations = $totalTxItemsWithCmbinations<br>
";

# Bro Numbers
$nonTxBros = $totalNumBros - $txBros;
echo "<br><b>Bro Numbers:</b><br>
Bros = $totalNumBros<br>
Tx Bros = $txBros<br>
Non-Tx Bros = $nonTxBros<br>
Bros set to NoTags = $noTags<br>
Bros with Tuple = $brosWithTuple<br>
Tx Bros with StartEnd = $txStartEndBros (Unique TxIds = $uniqueTxStartEndBros)<br>
Non-Tx Bros with StartEnd = $nonTxStartEndBros<br>
Slave Bros = $slaves<br>
Master Bros = $masters<br>
";

# Tuple Numbers re Bros
$tuplesToDo = $tuples-$tuplesSkipped;
$tupleMembersToDo=$tupleMembers-$tupleMembersSkipped;
$tupleMembersToGo=$tupleMembersInNotStartedTuples+$tupleMembersToGoInPartlyDoneTuples;
echo "<br><b>Tuple Numbers re Bros:</b><br>
Tuples skipped re Bros = $tuplesSkipped (Members skipped = $tupleMembersSkipped) leaving $tuplesToDo Tuples to be included in Bros with $tupleMembersToDo Members<br>
Tuples done in full = $tuplesFullyDone with $tupleMembersInFullyDoneTuples Members done<br>
Tuples done in part = $tuplesPartlyDone with $tupleMembersDoneInPartlyDoneTuples Members done<br>
Tuples to go in part = $tuplesPartlyDone with $tupleMembersToGoInPartlyDoneTuples Members<br>
Tuples to go in full = $tuplesNotStarted with $tupleMembersInNotStartedTuples Members<br>
Tuple Members to go = $tupleMembersToGo (= $tupleMembersToDo to do - $tupleMembersInFullyDoneTuples in fully done Tuples - $tupleMembersDoneInPartlyDoneTuples in partly done Tuples)<br>
";

# Item Numbers re Bros
$totalToDo = $itemsOneHyToDo + $itemsMulHyToDo + $itemsOneHyTupidToDo + $itemsMulHyTupidToDo;
$totalDone = $itemsOneHyDone + $itemsMulHyDone + $itemsOneHyTupidDone + $itemsMulHyTupidDone + $itemsMulHyDoneViaHySuper + $itemsMulHyTupidDoneViaHySuper;
$totalToGo = $itemsOneHyToGo + $itemsMulHyToGo + $itemsOneHyTupidToGo + $itemsMulHyTupidToGo;
$combinationsSkipped = $totalTxItemsWithCmbinations - $totalToDo;

echo "<br><b>Item Numbers re Bros:</b><br>
Elements skipped re Bros = $itemsSkipped (skipping $combinationsSkipped item conbinations) leaving $totalToDo item combinations to be included in Bros:<br>
- $itemsOneHyToDo Items with one hypercube<br>
- $itemsMulHyToDo Items with multiple hypercubes<br>
- $itemsOneHyTupidToDo Items with one hypercube and tuple combinations<br>
- $itemsMulHyTupidToDo Items with multiple hypercubes and tuple conbinations<br>
= $totalToDo in total<br>
<br>
<b>Items In Bros:</b><br>
- $itemsOneHyDone Items with one Hypercube<br>
- $itemsMulHyDone Items with multiple Hypercubes<br>
- $itemsMulHyDoneViaHySuper Items with multiple Hypercubes done via Superset Hypercube<br>
- $itemsOneHyTupidDone Items with one Hypercube and a Tuple<br>
- $itemsMulHyTupidDone Items with multiple Hypercubes and a Tuple<br>
- $itemsMulHyTupidDoneViaHySuper Items with multiple Hypercubes and a Tuple done via Superset Hypercube<br>
= $totalDone in total<br>
";

# Still To Go Numbers
$txStartEndBrosToGo = count($StartEndTxIdsGA) - $uniqueTxStartEndBros;
echo "<br><b>Items Still To Go:</b><br>
- $itemsOneHyToGo Items with one hypercube<br>
- $itemsMulHyToGo Items with multiple hypercubes<br>
- $itemsOneHyTupidToGo Items with one hypercube and a Tuple<br>
- $itemsMulHyTupidToGo Items with multiple hypercubes and a Tuple<br>
= $totalToGo in total<br>
including $txStartEndBrosToGo StartEnd TxIds to go<br>
";

# Checks
$check1 = $tuplesToDo-$tuplesFullyDone-$tuplesPartlyDone-$tuplesNotStarted;
$check2 = $tupleMembersToDo-$tupleMembersInFullyDoneTuples-$tupleMembersDoneInPartlyDoneTuples-$tupleMembersToGoInPartlyDoneTuples-$tupleMembersInNotStartedTuples;
$check3 = $totalToDo-$totalDone-$totalToGo;
$check5 = $itemsOneHyToDo      - $itemsOneHyDone      - $itemsOneHyToGo;
$check6 = $itemsMulHyToDo      - $itemsMulHyDone      - $itemsMulHyDoneViaHySuper - $itemsMulHyToGo;
$check7 = $itemsOneHyTupidToDo - $itemsOneHyTupidDone - $itemsOneHyTupidToGo;
$check8 = $itemsMulHyTupidToDo - $itemsMulHyTupidDone - $itemsMulHyTupidDoneViaHySuper - $itemsMulHyTupidToGo;
#$check9 = $totalToDo

echo "<br><b>Checks - Should be Zero:</b><br>
Tuples to be included in Bros - Tuples in Bros - Tuples to Go = $check1<br>
Tuple Members to be included in Bros - Tuple Members in Bros - Tuple Members to Go = $check2<br>
Items to be included in Bros  - Items in Bros - Items to Go = $check3<br>
Items with one hypercube ToDo - Done - ToGo = $check5<br>
Items with multiple hypercubes  ToDo - Done - ToGo = $check6<br>
Items with one hypercube and a Tuple ToDo - Done - ToGo = $check7<br>
Items with multiple hypercubes and a Tuple ToDo - Done - ToGo = $check8<br>
";

Footer();
#########

function Output($elId,$label,$hyId,$TupId,$type,$comment='') {
  global $Fh;
  static $prevElId, $prevComment;
  if ($elId!=$prevElId) {
    $id = $elId;
    $la = $label;
    $co = $comment;
  }else{
    $id = $la = DQ;
    $co = $comment ? ($comment!=$prevComment ? $comment : DQ) : '';
  }
  $TupId=ZeroToEmpty($TupId);
  echo "<tr class=c><td>$id</td><td class=l>$la</td><td>$hyId</td><td>$TupId</td><td>$type</td><td class=l>$co</td></tr>\n";
  fwrite($Fh, "$id	$la	$hyId	$TupId	$type	$co\n");
  $prevElId = $elId;
  $prevComment = $comment;
}

