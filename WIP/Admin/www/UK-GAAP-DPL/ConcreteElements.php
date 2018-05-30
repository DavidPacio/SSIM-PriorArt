<?php /* Copyright 2011-2013 Braiins Ltd

23.05.12 Added provision for duplicate TxIdHyIds
30.05.12 Changed for addition of Slaves replacing duplicate TxIdHyIds
05.06.12 Reworked considerably
03.10.12 No output in case of no presentation tree fixed.
18.02.13 BD Maps removed

ConcreteElements.php

*/
require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsTx.inc';
require Com_Inc_Tx.'ConstantsRg.inc';
require Com_Inc_Tx.'TxElementsSkipped.inc';

Head("Concrete Elements: $TxName", true);

$skipped = $bros = $dups = $tuplesInBrosTupleLists = $brosWithTuple = $noBros = $items = $tuples = $slaves = 0;
$broTxIdsA =
$tupTxIdsA = array();
$res = $DB->ResQuery('Select TupId,TupTxId from TuplePairs Group by TupId');
while ($o = $res->fetch_object())
  $tupTxIdsA[(int)$o->TupId] = (int)$o->TupTxId;
$res = $DB->ResQuery('Select Id,Name,Bits,TxId,Hys,TupId From BroInfo Where TxId is not null Order by Id');
while ($o = $res->fetch_object()) {
  $bits = (int)$o->Bits;
  if ($bits & BroB_Slave) { # Skip Slaves
    ++$slaves;
    continue;
  }
  $id   = $o->Id;
  $name = $o->Name;
  $txId = (int)$o->TxId;
  $hys  =      $o->Hys;
  if ($o->TupId) {
    ++$brosWithTuple;
    $tupId = (int)$o->TupId;
    $tupTxId = $tupTxIdsA[(int)$tupId];
    if (isset($broTxIdsA[$tupTxId]))
      $broTxIdsA[$tupTxId][0]['Name'] .= ", $id";
    else
      $broTxIdsA[$tupTxId][0] = array('Id' => 'Tuple', 'HyId' => 'Tuple', 'Name' => "Tuple Id $tupId, TxId $tupTxId with Bro children $id");
  }
  if (isset($broTxIdsA[$txId]))
    ++$dups;
  foreach (ChrListToIntA($hys) as $hyId)
    $broTxIdsA[$txId][] = array('Id' => $id, 'HyId' => $hyId, 'Bits' => $bits, 'Name' => "$name");
}
$res->free();

$file = "/BrosAndTx/ConcreteElements-$TxName-".gmstrftime('%Y-%m-%d_%H_%M').'.txt';
$Fh = fopen('../..'.$file, 'w');
echo "<h2 class=c>Concrete Elements: $TxName</h2>
<p class=c>The report is in the file <a href='Show.php?$file'>Admin$file</a> in tab delimited form.</p>
";
fwrite($Fh, "Id	Standard Label	Hypercube	Type	Period	Sign	Def Level	Def Parent 1	Def Parent 2	Def Parent 3	Def Parent 4	Def Parent 5	Def Parent 6	Def Parent 7	Def Parent 8	Def Parent 1 Label	Def Parent 2 Label	Def Parent 3 Label	Def Parent 4 Label	Def Parent 5 Label	Def Parent 6 Label	Def Parent 7 Label	Def Parent 8 Label	Def Children	Pres Role	Pres Level	Pres Parent 1	Pres Parent 2	Pres Parent 3	Pres Parent 4	Pres Parent 5	Pres Parent 6	Pres Parent 7	Pres Parent 8	Pres Parent 9	Pres Parent 10	Pres Parent 1 Label	Pres Parent 2 Label	Pres Parent 3 Label	Pres Parent 4 Label	Pres Parent 5 Label	Pres Parent 6 Label	Pres Parent 7 Label	Pres Parent 8 Label	Pres Parent 8 Label	Pres Parent 10 Label	Pres Children	StartEnd	Bro Id	Bro Type	Bro Name\n");
#$maxLevel = $maxId = $maxPres = 0;
 $res = $DB->ResQuery('Select E.Id,T.Text,Hypercubes,E.TypeN,PeriodN,SignN,SubstGroupN From Elements E Join Text T on T.Id=E.StdLabelTxtId Where SubstGroupN In (1,2) and abstract is null');
#$res = $DB->ResQuery('Select E.Id,T.Text,Hypercubes,E.TypeN,PeriodN,SignN From Elements E Join Text T on T.Id=E.StdLabelTxtId Where SubstGroupN=1 and abstract is null');
#$res = $DB->ResQuery('Select E.Id,T.Text,Hypercubes,E.TypeN,PeriodN,SignN From Elements E Join Text T on T.Id=E.StdLabelTxtId Where SubstGroupN=1 and abstract is null and E.Id in (4155, 4290, 85,5167,5266,5267,511,4769,557, 1125)'); # 511,62, 5266, 5267, 5167, 5169, 5189, 85, 1125)');
$tot = $res->num_rows;
while ($o = $res->fetch_object()) {
  $id     = (int)$o->Id;
  $label  = $o->Text;
  $type   = ElementTypeToStr($o->TypeN);
  $period = PeriodTypeToStr($o->PeriodN);
  $sign   = $o->TypeN == TET_Money ? SignToStr($o->SignN) : '';
  $infoA = GetParentRolesLevelsAndParents($id, 0); # index of $pRoleId:occurrence # in this tree => [level, [parent E.Ids]]
  #Dump('$infoA', $infoA);
  #$hypercubesA = array();
  $prevHyId = $n = 0;
  $presInfoA = array();
  $defInfoA  = array();
  foreach ($infoA as $pRoleId => $infoA)
    if ($pRoleId < TR_FirstHypercubeId)
      $presInfoA[$pRoleId] = $infoA;
    else
      $defInfoA[$pRoleId] = $infoA;
  ksort($presInfoA);
  ksort($defInfoA);
  #$nPres = count($presInfoA);
  $nDef   = count($defInfoA);
  #echo "$id $nPres $nDef<br>";
  if ($nDef) { # SubstGroupN=1 Concrete Item. Never a Tuple
    ++$items;
    foreach ($defInfoA as $pRoleId => $infoA) {
      $level = $infoA[0];
      $parentsA = array_reverse($infoA[1]);
      $kids     = $infoA[2];
      if ($pRoleId < TR_FirstHypercubeId)
        die("Die with pRoleId=$pRoleId < TR_FirstHypercubeId");
      else
        $hyId = $pRoleId - TR_FirstHypercubeId + 1;
      if (!$n) {
        $numTx = 0;
        $broId = $broType = $broName = '';
        if (in_array($id, $TxElementsSkippedA)) {
          $broId   = $broType = '-';
          $broName = 'Skipped';
          ++$skipped;
        }else if (isset($broTxIdsA[$id])) {
          $bsA     = $broTxIdsA[$id];
          $numTx   = count($bsA);
          foreach ($bsA as $bA) {
            if ($bA['HyId'] == $hyId) {
              $broId   = $bA['Id'];
              $broName = $bA['Name'];
              ++$bros;
              $broType = BroTypeStr($bA['Bits']);
              --$numTx;
              break;
            }
          }
        }else
          ++$noBros;
        OutputElement($id, $label, $hyId, $type, $period, $sign, $level, $parentsA, $kids, $broId, $broType, $broName, $presInfoA);
        $prevHyId = $hyId;
      }else{
        if ($hyId == $prevHyId) {
          #$broId = $broType = $broName = '';
          OutputElement($id, "Repeat of '$label' for different def tree entry", $hyId, $type, $period, $sign, $level, $parentsA, $kids, $broId, $broType, $broName, $presInfoA);
        }else{
          # different hy
          if ($numTx) {
            foreach ($bsA as $bA) {
              if ($bA['HyId'] == $hyId) {
                $broId   = $bA['Id'];
                $broName = $bA['Name'];
                ++$bros;
                $broType = BroTypeStr($bA['Bits']);
                --$numTx;
                break;
              }
            }
          }else
            $broId = $broType = $broName = '';
          OutputElement($id, "Repeat of '$label' for different hypercube", $hyId, $type, $period, $sign, $level, $parentsA, $kids, $broId, $broType, $broName, $presInfoA);
        }
      }
      ++$n;
    }
    /* djh?? Temporary until Duplicate Bros via DiMes is handled
    if ($numTx) {
      DumpExport('$bsA', $bsA);
      die("Die - for Tx El $id ended with numTx = $numTx whereas 0 is expected");
    } */
  }else{
    # No definition tree entries =  all the SubstGroupN=2 Concrete Tuples but also 10 others which have no hypercubes and no def tree:
  /*if ($o->SubstGroupN != TSG_Tuple) echo "$id no def tree but not tuple<br>";
    5118,5124,5129,5152,5181,5209,5215,5255,5256,5462
    5118 no def tree but not tuple - pres child of 5271 tuple
    5124 no def tree but not tuple - pres child of 5270 tuple
    5129 no def tree but not tuple - pres dad   of 5270 tuple
    5152 no def tree but not tuple - pres child of 5270 tuple
    5181 no def tree but not tuple - pres dad   of 5271 tuple
    5209 no def tree but not tuple - pres child of 5271 tuple
    5215 no def tree but not tuple - no pres tree either
    5255 no def tree but not tuple - no pres tree either
    5256 no def tree but not tuple - pres child of 5254 hy 1
    5462 no def tree but not tuple - in 02 - Business Report Information, Prob should be hy 1
    */
    if ($o->SubstGroupN == TSG_Tuple) {
      ++$tuples;
      $hyId = 'Tuple';
    }else
      $hyId = '';
    $parentsA = array();
    $type = $period = $sign = $level = $kids = '';
    $broId = $broType = $broName = '';
    if (in_array($id, $TxElementsSkippedA)) {
      $broId   = $broType = '-';
      $broName = 'Skipped';
      ++$skipped;
    }else if (isset($broTxIdsA[$id])) {
      $bsA   = $broTxIdsA[$id];
      $numTx = count($bsA);
      foreach ($bsA as $bA) {
        if ($bA['HyId'] == $hyId) {
          $broId   = $bA['Id'];
          $broName = $bA['Name'];
          if ($broId == 'Tuple') {
            ++$tuplesInBrosTupleLists;
            $broType = 'Tuple';
            $broId   = 'N.A.';
          }else{
            ++$bros;
            $broType = BroTypeStr($bA['Bits']);
          }
          --$numTx;
          break;
        }
      }
    }else
      ++$noBros;
    OutputElement($id, $label, $hyId, $type, $period, $sign, $level, $parentsA, $kids, $broId, $broType, $broName, $presInfoA);
  }
  # if ($nPres > $maxPres) {
  #   $maxPres = $nPres;
  #   $maxPresId = $id;
  # }
  #sort($hypercubesA);
  #$hypercubes = implode(',', $hypercubesA);
  #if ($hypercubes != ($dbHypercubes = ChrListToCsList($o->Hypercubes)))
  #  echo "$id hy different here=$hypercubes, db=$dbHypercubes<br>";

  #echo "<br>";
}
fclose($Fh);
# echo "maxLevel=$maxLevel on Id $maxId, maxPres=$maxPres on Id $maxPresId<br>";
# maxLevel=10 on Id 1125, maxPres=7 on Id 1563 for presentation
# maxLevel=8 on Id 1125 for definition
$bros += $tuplesInBrosTupleLists - $dups;
echo "Numbers:<br>
Total=$tot (Concrete Tx Elements of Substitution Groups Item or Tuple)<br>Items=$items<br>
Tuples=$tuples of which $tuplesInBrosTupleLists are in use for the $brosWithTuple Bros or Bro Maps with tuple members<br>
Elements Skipped re Bros=$skipped<br>
Elements Used by Bros=$bros, of which<br>
- $tuplesInBrosTupleLists are tuple members<br>
$dups elements are duplicated for different Hypercubes. (Not double counted in the $bros number above.)<br>
$slaves Slave Bros have been excluded from the above counts.<br>
Elements not used by Bros=$noBros<br>
For a more complete report on Tx and Bro numbers see CEs Not In Bros<br>";
# echo "count TxElementsSkippedA=",count($TxElementsSkippedA),'<br>'; same as skipped
Footer();
#########

function OutputElement($id, $label, $hyId, $type, $period, $sign, $level, $parentsA, $kids, $broId, $broType, $broName, $presInfoA) {
  global $Fh, $StartEndTxIdsGA;
  $fs = "$id	%s	$hyId	$type	$period	$sign	$level";
  $n = 8; # number of def parent columns
  foreach ($parentsA as $p) {
    $fs .= "	$p";
    --$n;
  }
  $fs .= str_repeat(TAB, $n);
  $n = 8; # number of def parent columns
  foreach ($parentsA as $p) {
    $fs .= TAB . ElStdLabel($p);
    --$n;
  }
  $fs .= str_repeat(TAB, $n) . "	$kids";
  # StartEnd
  $startEnd = in_array($id, $StartEndTxIdsGA) ? 'StartEnd' : '';

  $repeatB = false;
  if (count($presInfoA)) {
    foreach ($presInfoA as  $pRoleId => $infoA) {
      if ($repeatB) {
        $thisLabel = $label . ' and repeat for duplicate pres tree entry';
        #$broId = $broType = $broName = '';
      }else
        $thisLabel = $label;
      $plevel = $infoA[0];
      $pparentsA = array_reverse($infoA[1]);
      $pkids     = $infoA[2];
      $thisPres = TAB . Role((int)$pRoleId) . "	$plevel";
      $n = 10; # number of pres parent columns
      foreach ($pparentsA as $p) {
        $thisPres .= "	$p";
        --$n;
      }
      $thisPres .= str_repeat(TAB, $n);
      $n = 10; # number of pres parent columns
      foreach ($pparentsA as $p) {
        $thisPres .= TAB . ElStdLabel($p);
        --$n;
      }
      $thisPres .= str_repeat(TAB, $n);
      fwrite($Fh, sprintf($fs . $thisPres . "	$pkids	$startEnd	$broId	$broType	$broName\n", $thisLabel));
      $repeatB = true;
    }
  }else
    fwrite($Fh, sprintf($fs . "																								$startEnd	$broId	$broType	$broName\n", $label));

}

function GetParentRolesLevelsAndParents($toId, $pRoleId, $num=0) {
  global $DB;
  static $targetId, $infoA, $pRoleIdsA, $fnLevel=0, $prevFnLevel;
  if (!$pRoleId) { # first
    $infoA     = array(); # index of $pRoleId:occurrence # in this tree => [level, [parent E.Ids]]
    $pRoleIdsA = array(); # $pRoleId => occurrence # in this tree
    $fnLevel   = $prevFnLevel = 0; # fn call levels, not element levels
    $targetId  = $toId;
    #echo "$toId<br>"; # call fnLevel=$fnLevel<br>";
  }else{
    $prevFnLevel = $fnLevel;
    $idx = "$pRoleId:$num";
    if (!isset($infoA[$idx]))
      $infoA[$idx] = array(1, array($toId), Kids($targetId, $pRoleId));
    else{
    ++$infoA[$idx][0];
      $infoA[$idx][1][] = $toId;
    }
    ++$fnLevel;
    #echo "call fnLevel=$fnLevel, $toId num=$num lev=$infoA[$idx] $pRoleId<br>";
  }
  if ($pRoleId)
    $res = $DB->ResQuery("Select FromId,PRoleId From Arcs Where ArcroleId In(1,2,3,4,5,6,7) and ToId=$toId and PRoleId=$pRoleId");
  else
    $res = $DB->ResQuery("Select FromId,PRoleId From Arcs Where ArcroleId In(1,2,3,4,5,6,7) and ToId=$toId");
   #echo "num=$res->num_rows<br>";
  if ($res->num_rows) {
    while ($o = $res->fetch_object()) {
      $pRoleId = (int)$o->PRoleId;
      if ($fnLevel > $prevFnLevel) { # in same path
        #echo "call w call level=$fnLevel, prevFnLevel=$prevFnLevel same, $o->FromId, $pRoleId $num<br>";
        GetParentRolesLevelsAndParents((int)$o->FromId, $pRoleId, $num);
      }else{ # first or new path when $fnLevel <= $prevFnLevel
        if (isset($pRoleIdsA[$pRoleId]))
          ++$pRoleIdsA[$pRoleId]; # same pRoleId so increment occurrence #
        else
          $pRoleIdsA[$pRoleId] = 1;
        #echo "call w call level=$fnLevel, prevFnLevel=$prevFnLevel diff, $o->FromId, $pRoleId {$pRoleIdsA[$pRoleId]}<br>";
        GetParentRolesLevelsAndParents((int)$o->FromId, $pRoleId, $pRoleIdsA[$pRoleId]);
      }
    }
  }
  $res->free();
  --$fnLevel;
  return $infoA;
}

function Kids($id, $pRoleId) {
  global $DB;
  $typeN = $pRoleId < TR_FirstHypercubeId ? TLT_Presentation : TLT_Definition;
  $res = $DB->ResQuery("Select ToId From Arcs Where FromId=$id And PRoleId=$pRoleId And TypeN=$typeN");
  if ($res->num_rows) {
    $kids = '';
    while ($o = $res->fetch_object()) {
      $toId = (int)$o->ToId;
      $kids .= ",$toId" . Kids2($toId, $pRoleId, $typeN);
    }
    $res->free();
    $kids = substr($kids, 1);
  }else{
    $res->free();
    $kids = 'None';
  }
  return $kids;
}

function Kids2($id, $pRoleId, $typeN) {
  global $DB;
  $res = $DB->ResQuery("Select ToId From Arcs Where FromId=$id And PRoleId=$pRoleId And TypeN=$typeN");
  if ($res->num_rows) {
    $kids = '(';
    while ($o = $res->fetch_object()) {
      $toId = (int)$o->ToId;
      $kids .= "$toId" . Kids2($toId, $pRoleId, $typeN) . ',';
    }
    $res->free();
    $kids = substr($kids, 0, -1) . ')';
  }else{
    $res->free();
    $kids = '';
  }
  return $kids;
}


