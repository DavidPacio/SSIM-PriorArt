<?php /* Copyright 2011-2013 Braiins Ltd

Roles.php

List the Roles

History:
30.03.11 Started

*/
require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsTx.inc';

Head("Arcroles: $TxName", true);

// Roles
echo "<h2 class=c>$TxName Arcroles</h2>
<p class=c>This is a simplified set of Arcroles based on arcroleType elements and arcrole uris used by the taxonomy.</p>
<table class=mc>
";
$res = $DB->ResQuery('Select * From Arcroles');
echo "<tr class='b bg0'><td>Id</td><td>Arcrole</td><td>Braiins Wording</td><td>XBRL Definition</td><td>cyclesAllowed</td></tr>\n";
while ($o = $res->fetch_object())
  echo "<tr><td>$o->Id</td><td>$o->Arcrole</td><td>". ArcroleIdToStr($o->Id), "</td><td>$o->definition</td><td>$o->cyclesAllowed</td></tr>\n";
$res->free();
echo "</table>
<br>
";
Footer();
exit;
