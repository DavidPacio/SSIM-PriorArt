<?php /* Copyright 2011-2013 Braiins Ltd

Admin Utils/UK-GAAP-DPL/Labels.php

Lists Labels

History:
30.03.11 Started for UK-GAAP
08.10.12 Revised for UK-GAAP-DPL with IdId and TitleId removed
10.10.12 Revised for Labels and References tables being replaced by Doc

*/
require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsTx.inc';

Head("Labels: $TxName", true);

if (!isset($_POST['Lwhere'])) {
  echo "<h2 class=c>List $TxName Labels</h2>\n";
  $Lwhere = 'limit 10';
  Form();
}

$Lwhere = Clean($_POST['Lwhere'], FT_STR);

// Used by Error() if called
$ErrorHdr="List Labels errored with:";

if (empty($Lwhere))
  $Lwhere = 'limit 10';

$where = trim($Lwhere);
if (strncasecmp($where, 'limit', 5) && strncasecmp($where, 'where', 5) && strncasecmp($where, 'order', 5))
  $where = 'where ' . $where;

# Labels
$n = $DB->OneQuery('Select count(*) from Doc Where TypeN=3');
if ($res = $DB->ResQuery("Select D.*,E.name From Doc D Join Elements E on D.TypeN=3 and E.Id=D.ElId $where")) {
  echo "<h2 class=c>$TxName Labels ($res->num_rows of $n with '$where')</h2>\n<table class=mc>\n";
  $n = 0;
  while ($o = $res->fetch_object()) {
    if (!($n%50))
      echo "<tr class='b c bg0'><td class=mid>Id</td><td>Role</td><td>Element</td><td>Label</td><td>Linkbase</t</tr>";
    echo "<tr><td>$o->Id</td><td>", Role($o->RoleId), '</td><td>', Fold("E.$o->ElId: $o->name", 75), '</td><td>', Text($o->TextId), '</td><td>', $o->LinkbaseId, '</td></tr>';
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
<div class=mc style=width:660px>
<p>Enter Where, Limit, Order clause for a Labels listing.<br><br>
Examples:<br>
limit 10<br>
ElId=5708 Order by RoleId<br><br>
Field names are ElId, RoleId, TextId, LinkbaseId.<br><br>
<b>Warnings:</b> No validity checking is performed i.e. invalid SQL will cause an error.</p>
<form method=post>
Where/Limit: <input type=text name=Lwhere size=75 maxlength=100 value="$Lwhere"> <button class=on>List Labels</button>
</form>
<br></div>
FORM;
Footer(true,true);
exit;
}

function ErrorCallBack($err, $errS) {
  global $TxName;
  echo "<h2 class=c>List $TxName Labels</h2>\n<p class=c>$errS</p>\n";
  Form();
}
