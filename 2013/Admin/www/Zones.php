<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/Zones.php

Lists the SIM Zones

History:
22.03.13 Converted from the UK-GAAP-DPL version
29.03.13 AllowDims removed
03.07.13 B R L -> SIM

*/
require 'BaseSIM.inc';

Head('Zones', true);

echo "<h2 class=c>Zones</h2><table class=mc>\n";
$n = 0;
$res = $DB->ResQuery('Select * From Zones Order by Ref');
while ($o = $res->fetch_object()) {
  if (!($n%50))
    echo "<tr class='b bg0'><td>Id</td><td>Ref</td><td>Expected Sign</td><td>Description</td></tr>\n";
  echo "<tr><td>$o->Id</td><td>$o->Ref</td><td>",
    SignToStr($o->SignN),
    "</td><td>$o->Descr</td></tr>\n";
  $n++;
}
$res->free();
echo "</table>\n";
Footer();
exit;
