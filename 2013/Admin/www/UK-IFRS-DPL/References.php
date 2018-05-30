<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/UK-IFRS-DPL/References.php

Lists References

History:
30.06.13 Copied from the UK-GAAP-DPL version with the Fold() calls removed

*/
require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsTx.inc';

Head("References: $TxName", true);

if (!isset($_POST['Lwhere'])) {
  echo "<h2 class=c>List $TxName References</h2>\n";
  $Lwhere = 'limit 10';
  Form();
}

$Lwhere = Clean($_POST['Lwhere'], FT_STR);

// Used by Error() if called
$ErrorHdr="List References errored with:";

if (empty($Lwhere))
  $Lwhere = 'limit 10';

$where = trim($Lwhere);
if (strncasecmp($where, 'limit', 5) && strncasecmp($where, 'where', 5) && strncasecmp($where, 'order', 5))
  $where = 'where ' . $where;

# References
$n = $DB->OneQuery('Select count(*) from Doc Where TypeN=4');
if ($res = $DB->ResQuery("Select D.*,E.name From Doc D Join Elements E on D.TypeN=4 and E.Id=D.ElId $where")) {
  echo "<h2 class=c>$TxName References ($res->num_rows of $n with '$where')</h2>\n<table class=mc>\n";
  $n = 0;
  while ($o = $res->fetch_object()) {
    if (!($n%50))
      echo "<tr class='b c bg0'><td class=mid>Id</td><td>Role</td><td>Element</td><td>Reference</td><td>Linkbase</t</tr>";
    echo "<tr><td>$o->Id</td><td>", Role($o->RoleId), "</td><td>E.$o->ElId: $o->name</td><td>", Text($o->TextId), "</td><td>$o->LinkbaseId</td></tr>\n";
    $n++;
  }
  $res->free();
  echo "</table>\n";
}else
  Echo "<br><br>\n";
Form();
//==========

function Form() {
  global $Lwhere, $TxName;
  echo <<< FORM
<div class=mc style=width:700px>
<p>Enter Where, Limit, Order clause for a References listing.<br><br>
Examples:<br>
limit 10<br>
ElId=5708 Order by RoleId<br><br>
Field names are ElId, RoleId, TextId, LinkbaseId.<br><br>
<b>Warnings:</b> No validity checking is performed i.e. invalid SQL will cause an error.</p>
<form method=post>
Where/Limit: <input type=text name=Lwhere size=75 maxlength=100 value="$Lwhere"> <button class=on>List References</button>
</form>
<br></div>
FORM;
Footer(true,true);
exit;
}

function ErrorCallBack($err, $errS) {
  global $TxName;
  echo "<h2 class=c>List $TxName References</h2>\n<p class=c>$errS</p>\n";
  Form();
}
