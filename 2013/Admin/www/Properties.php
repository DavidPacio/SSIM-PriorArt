<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/Properties.php

Lists the SIM Properties

History:
15.03.13 Started based on the UK-GAAP-DPL version
03.07.13 B R L -> SIM

*/
require 'BaseSIM.inc';
Head('SIM Props', true);

echo "<h2 class=c>SIM Properties</h2>
<p class=c>For SIM Property Members see <a href=Members.php>Property Members</a>.</p>
<table class=mc>
<tr class='b bg0'><td>Id</td><td>Name</td><td>Label</td><td>Role</td></tr>
";

$res = $DB->ResQuery("Select P.Id,P.Name,P.Label,R.Role as Role From Properties P Left Join Roles R on R.Id=P.RoleId Order by Id");
while ($o = $res->fetch_object()) {
  $propId= (int)$o->Id;
  $name  = $o->Name;
  $label = $o->Label;
  $role  = $o->Role;
  echo "<tr><td class=r>$propId</td><td>$name</td><td>$label</td><td>$role</td></tr>\n";
}
echo '</table>
';
Footer(true, true);
########

