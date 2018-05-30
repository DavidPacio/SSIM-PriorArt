<?php /* Copyright 2011-2013 Braiins Ltd

BrosRptOrderList.php

List Bros Rpeprt Order

History:
12.07.11 djh Written

ToDo
====

*/
require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsRg.inc';
require Com_Str_Tx.'BroRptOrderA.inc'; # $BroRptOrderA
require Com_Str_Tx.'BroNamesA.inc';    # $BroNamesA

Head("Bros Report Order List: $TxName", true);
echo "<h2 class=c>Bros Report Order List: $TxName</h2>
<div class=mc style='width:999px'>
<p>For each Set with Money BRO children, this Report lists the order in which Money Bros (elements or Sets) would list via an RG List statement.<br>
The report order is only likely to be relevant for Detailed P&amp;L type sets, but all are listed here in case.<br>
The order within a Set is SortOrder,Name Ascending so the order may be varied by editing the Sort Order, or Name, or both. If edits are made, run 'Build Maps &amp; Structs', and then this report again to see the result.</p>
</div>
<table class=mc>
";

$n = 50;
foreach ($BroRptOrderA as $setId => $brosA) {
  if ($brosA === 0)
    continue; # Set has no money children
  if ($n >= 50) {
    $n = 0;
    echo "<tr class='b c bg0'><td>Set BRO Id</td><td>Set Name</td><td>BRO Id</td><td>BRO Name</td><td>Sort Order</td><td>BRO Type</td></tr>\n";
  }
  $setName = $BroNamesA[$setId];
  $firstB  = true;
  $num     = count($brosA);
  foreach ($brosA as $memId) {
    if ($firstB) {
      $firstB = false;
      echo "<tr><td rowspan=$num class='r top'>$setId</td><td rowspan=$num class=top>$setName</td>";
      $tr='';
    }else
      $tr='<tr>';
    $memName = $BroNamesA[$memId];
    $mO = $DB->ObjQuery("Select TypeN,SortOrder From BroInfo Where Id=$memId");
    $sortOrder = ($mO->SortOrder ? : '');
    $type      = ($mO->TypeN == BT_Set ? 'Set' : 'Element');
    echo "$tr<td class=r>$memId</td><td>$memName</td><td>$sortOrder</td><td>$type</td></tr>\n";
    $n++;
  }
}
echo "</table>
";
Footer(true, true);

