<?php /* Copyright (c) 2011 Brai7ins Ltd

Admin/www/BroSummingTrees.php

List the Bro Summing Trees

History:
07.06.13 Started based on UK-GAAP version
03.07.13 B R L -> SIM

ToDo
====

*/
require 'BaseSIM.inc';
#equire Com_Inc.'FuncsSIM.inc';
require Com_Str.'PropNamesA.inc';    # $PropNamesA
require Com_Str.'PMemNamesA.inc';    # $PMemNamesA
require Com_Str.'PMemsA.inc';        # $PMemsA
require Com_Str.'PMemSumTreesA.inc'; # $PMemSumTreesA
#equire Com_Inc_Tx.'ConstantsRg.inc';
#equire Com_Str_Tx.'BroSumTreesA.inc';      # $BroSumTreesA $CheckBrosA $SumEndBrosA $PostEndBrosA $StockBrosA
#equire Com_Str_Tx.'BroNamesA.inc';         # $BroNamesA
#equire Com_Str_Tx.'BroInfoA.inc';          # $BroInfoA

$TxName = 'Fred';
Head("Summing Trees: $TxName", true);

echo "<h2 class=c>Property and Bro Summing Trees: $TxName</h2>
<p class=c>This report lists how Money, Decimal, Integer, and Share type Property Members and Bros are summed, for each Property in use.</p>
<h3 class=c>Property Members</h3>
<table class=mc>
";
$n = 50;
# $PMemSumTreesA 3 dimensional array of [PropId, [target PMemId, [PMemIds to sum]]]
foreach ($PMemSumTreesA as $propId => $treeA) {
  if ($n >= 50) {
    $n = 0;
    echo "<tr class='b c bg0'><td colspan=2>Dimension</td><td colspan=2>Target Member</td><td colspan=2>Members to be Summed</td></tr>\n";
    echo "<tr class='b c bg0'><td>PropId</td><td>Name</td><td>PMemId</td><td>Name</td><td>PMemId</td><td>Name</td></tr>\n";
  }
  $num = 0;
  foreach ($treeA as $tarPMemId => $pMemIdsA)
    $num += count($pMemIdsA);
  $propName = $PropNamesA[$PMemsA[$tarPMemId][PMemI_PropId]];

  echo "<tr><td class='c top' rowspan=$num>$propId</td><td class=top rowspan=$num>$propName</td>";
  $tr = '';
  foreach ($treeA as $tarPMemId => $pMemIdsA) {
    $num = count($pMemIdsA);
    echo "$tr<td class='c top' rowspan=$num>$tarPMemId</td><td class=top rowspan=$num>{$PMemNamesA[$tarPMemId]}</td>";
    $tr = '';
    foreach ($pMemIdsA as $pMemId) {
      echo "$tr<td class=c>$pMemId</td><td>{$PMemNamesA[$pMemId]}</td></tr>\n";
      $tr = '<tr>';
      ++$n;
    }
  }
}
echo "</table>\n";

# Bros
#foreach ($BroSumTypesGA as $dataTypeN)
#  ListSummingTree($dataTypeN);

Footer(true, true);
#####

# $BroSumTreesA 3 dimensional array of [DataTypeN => [BroId of Target Bro => [BroIds of Bros to sum]]
function ListSummingTree($dataTypeN) {
  global $DB, $BroSumTreesA, $BroNamesA, $BroInfoA;
  echo "<h3 class=c>" .  DataTypeStr($dataTypeN) . ' Bros</h3>';
  $treeA = $BroSumTreesA[$dataTypeN];
  if ($treeA === 0) {
    echo "<p class=c>None</p>\n";
    return;
  }
  echo "<table class=mc>\n";
  $n = 50;
  foreach ($treeA as $setId => $listA) {
    if ($n >= 50) {
      $n = 0;
      echo "<tr class='b c bg0'><td colspan=2>Target Set Bro</td><td colspan=3>Bros to be Summed</td></tr>\n";
      echo "<tr class='b c bg0'><td>Id</td><td>Bro Name</td><td>Id</td><td>Bro Name</td><td style=min-width:50px>Type</td></tr>\n";
    }
    $setName = $BroNamesA[$setId];
    /* 30.11.11 Removed as this was only use of BroI_RelatedId. Not worth having it just for this.
    if ($relatedId = $BroInfoA[$setId][BroI_RelatedId]) {
      $bits = $BroInfoA[$setId][BroI_Bits];
      if ($bits & BroB_Equal)
        $setName .= '<br>Equal To: ' . $BroNamesA[$relatedId];
      else if ($bits & BroB_EqualOpp)
        $setName .= '<br>Equal &amp; Opp To: ' . $BroNamesA[$relatedId];
      else
        $setName .= "<br><span class='L b'>CheckId $relatedId is set but no relationship bit is set";
    } */
    $firstB = true;
    $num    = count($listA);
    foreach ($listA as $broId) {
      if ($firstB) {
        $firstB = false;
        echo "<tr><td rowspan=$num class='r top'>$setId</td><td rowspan=$num class='top wball'>$setName</td>";
        $tr='';
      }else
        $tr='<tr>';
      $memName = $BroNamesA[$broId];
      $type    = BroTypeStr($BroInfoA[$broId][BroI_Bits]);
      echo "$tr<td class=r>$broId</td><td class=wball>$memName</td><td>$type</td></tr>\n";
      ++$n;
    }
  }
  echo "</table>\n";
}

