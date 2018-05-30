<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/UK-IFRS-DPL/Roles.php

List the Roles

History:
29.06.13 Copied from the UK-GAAP-DPL one

*/
require 'BaseTx.inc';

Head("Roles: $TxName", true);

# Roles
echo "<h2 class=c>Roles: $TxName</h2>
<p class=c>This is a simplified set of Roles based on roleType elements and role uris used by the taxonomy.</p>
<table class=mc>
";
$res = $DB->ResQuery('Select R.*,E.name From Roles R Left Join Elements E on E.Id=R.ElId');
$n = 0;
while ($o = $res->fetch_object()) {
  if (!($n%50))
    echo "<tr class='b bg0 c'><td rowspan=2>Id</td><td rowspan=2>Role</td><td rowspan=2>Used On</td><td rowspan=2>Definition</td><td colspan=2>Associated Dimension or Hypercube</td></tr><tr class='b bg0 c'><td>Id</td><td>Name</td></tr>\n";
  echo "<tr><td class=r>$o->Id</td><td>$o->Role</td><td>$o->usedOn</td><td>$o->definition</td>";
  if ($o->ElId)
    echo "<td class=r>$o->ElId</td><td>$o->name</td>";
  echo "</tr>\n";
  $n++;
}
$res->free();
echo "</table>
";
Footer(true,true);
exit;
