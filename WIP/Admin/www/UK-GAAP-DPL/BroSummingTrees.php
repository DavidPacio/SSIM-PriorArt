<?php /* Copyright (c) 2011 Brai7ins Ltd

Admin/www/Utils/UK-GAAP-DPL/BroSummingTrees.php

List the Bro Summing Trees

History:
30.09.12 Started as UK-GAAP version
07.10.12 Changes for DiMeShortNamesA now being wo leading DimShortName.

ToDo
====

*/
require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsRg.inc';
require Com_Str_Tx.'DiMeSumTreesA.inc';     # $DiMeSumTreesA
require Com_Str_Tx.'BroSumTreesA.inc';      # $BroSumTreesA $CheckBrosA $SumEndBrosA $PostEndBrosA $StockBrosA
require Com_Str_Tx.'DiMesA.inc';            # $DiMesA
require Com_Str_Tx.'DimNamesA.inc';    # $DimNamesA
require Com_Str_Tx.'DiMeNamesA.inc';# $DiMeNamesA
require Com_Str_Tx.'BroNamesA.inc';         # $BroNamesA
require Com_Str_Tx.'BroInfoA.inc';          # $BroInfoA

Head("Summing Trees: $TxName", true);

echo "<h2 class=c>Dimension and Bro Summing Trees: $TxName</h2>
<p class=c>This report lists how Money, Decimal, Integer, and Share type Dimension Members and Bros are summed, for each dimension in use.</p>
<h3 class=c>Dimension Members</h3>
<table class=mc>
";
$n = 50;
# $DiMeSumTreesA 3 dimensional array of [DimId, [target DiMeId, [DiMeIds to sum]]]
foreach ($DiMeSumTreesA as $dimId => $treeA) {
  if ($n >= 50) {
    $n = 0;
    echo "<tr class='b c bg0'><td colspan=2>Dimension</td><td colspan=2>Target DiMe</td><td colspan=2>DiMes to be Summed</td></tr>\n";
    echo "<tr class='b c bg0'><td>DimId</td><td>Name</td><td>DiMeId</td><td>Name</td><td>DiMeId</td><td>Name</td></tr>\n";
  }
  $num = 0;
  foreach ($treeA as $tarDiMeId => $diMeIdsA)
    $num += count($diMeIdsA);
  $dimName = $DimNamesA[$DiMesA[$tarDiMeId][DiMeI_DimId]];

  echo "<tr><td class='c top' rowspan=$num>$dimId</td><td class=top rowspan=$num>$dimName</td>";
  $tr = '';
  foreach ($treeA as $tarDiMeId => $diMeIdsA) {
    $num = count($diMeIdsA);
    echo "$tr<td class='c top' rowspan=$num>$tarDiMeId</td><td class=top rowspan=$num>{$DiMeNamesA[$tarDiMeId]}</td>";
    $tr = '';
    foreach ($diMeIdsA as $diMeId) {
      echo "$tr<td class=c>$diMeId</td><td>{$DiMeNamesA[$diMeId]}</td></tr>\n";
      $tr = '<tr>';
      ++$n;
    }
  }
}
echo "</table>\n";

# Bros
foreach ($BroSumTypesGA as $dataTypeN)
  ListSummingTree($dataTypeN);

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

