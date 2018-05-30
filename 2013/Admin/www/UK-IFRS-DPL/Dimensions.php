<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/UK-IFRS-DPL/Dimensions.php

Lists The Dimensions

History:
29.06.13 Started based on the UK-GAAP-DPL version

*/
require 'BaseTx.inc';
require Com_Str_Tx.'DimNamesA.inc';  # $DimNamesA
Head("Dimensions List: $TxName", true);

echo "<h2 class=c>$TxName Dimensions</h2>
<p class=c>For Dimension Members see <a href=DiMes.php>Dimension Members</a>.</p>
<table class=mc>
<tr class='b bg0'><td>Id</td><td class=c>Id<br>Chr</td><td class=c>Tx<br>Id</td><td>Tx Name</td><td>Braiins Name</td><td>Label</td><td>Role</td></tr>
";

$res = $DB->ResQuery("Select D.*,E.name,T.Text From Dimensions D Join Elements E on E.Id=D.ElId Left Join Text T on T.Id=E.StdLabelTxtId");
while ($o = $res->fetch_object()) {
  $dimId = (int)$o->Id;
  $didC  = htmlspecialchars(IntToChr($dimId));
  $name  = $o->name;
  $label = $o->Text;
  $role = Role($o->RoleId, true); # true = no trailing [id]
  $bName = $DimNamesA[$dimId];
  echo "<tr><td class=r>$dimId</td><td class=c>$didC</td><td class=r>$o->ElId</td><td>$name</td><td>$bName</td><td>$label</td><td>$role</td></tr>\n";
}
echo '</table>
';
Footer(true, true);
########

