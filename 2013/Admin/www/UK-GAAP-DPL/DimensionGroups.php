<?php /* Copyright 2011-2013 Braiins Ltd

DimensionGroups.php

Lists The Dimension Groups

History:
21.04.12 Written

*/
require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsRg.inc';
require Com_Str_Tx.'DimNamesA.inc';  # $DimNamesA

Head("Dimension Groups: $TxName", true);
echo "<h2 class=c>$TxName Dimension Groups<br>or Reporting Requirements</h2>
<table class=mc>
<tr class='b bg0'><td class=c>#</td><td>Name</td><td>Credits</td><td>U</td><td>S</td><td>Dimension in the Group (DimId Dimension Short Name{, ...})</td><td>New/Edit Entity Tip</td></tr>
";

for ($dg=0; $dg<DG_Num; ++$dg) {
  $dgA = $DimGroupsA[$dg];
  $u = $dgA[DGI_User] ? 'U' : '';
  $f = $dgA[DGI_ExSmall] ? 'S' : '';
  $t = $dgA[DGI_Tip];
  $dims = '';
  foreach ($dgA[DGI_DimsA] as $dimId)
    $dims .= ", $dimId ".$DimNamesA[$dimId];
  $dims = substr($dims, 2);
  echo "<tr><td class=c>$dg</td><td>{$dgA[DGI_Name]}</td><td class=c>{$dgA[DGI_Credits]}</td><td>$u</td><td>$f</td><td>$dims</td><td>$t</td></tr>\n";
}

echo "</table>
<div class=mc style=width:845px>
<h3>Notes</h3>
<p>Dimension Groups provide global control on an Entity basis over use of some dimensions for particular structural and other reporting requirements. Entity type control over dimension use is handled via the <a href=DimensionsMap.php>Dimensions Map</a>.</p>
<p>The Credits shown apply in New Entity and Edit Entity when a group is selected or deselected.</p>
<p>The U column shows 'U' when the group is User selectable in New and Edit Entity. All groups should be 'U' when Braiins is complete for $TxName.</p>
<p>The S column shows 'S' for groups which cannot be used with a Small Company entity.</p>
<p>The New/Edit Entity Tip column shows the tip which appears in New Entity or Edit Entity on hovering over the checkbox for the group. If a group has been disabled for a Small Company, the tip has ' (Disabled for Small Company.)' appended to it.</p>
</div>
";

Footer(true, true);
########

