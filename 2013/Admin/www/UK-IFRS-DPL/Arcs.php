<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/UK-IFRS-DPL/Arcs.php

Lists Arcs

History:
29.06.13 Copied from the UK-GAAP-DPL version with Fold() calls removed

*/
require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsTx.inc';

Head("Arcs: $TxName", true);

if (!isset($_POST['Awhere'])) {
  echo "<h2 class=c>List $TxName Arcs</h2>\n";
  $Awhere = 'limit 10';
  Form();
}

// Used by Error() if called
$ErrorHdr="List Arcs errored with:";

$Awhere = Clean($_POST['Awhere'], FT_STR);
//$Awhere = 'where ToId=5729';
//$Awhere = 'where TargetRoleId>0';
//$Awhere = 'where FromId=2414';

if (empty($Awhere))
  $Awhere = 'limit 10';

$where = trim($Awhere);
if (strncasecmp($where, 'limit', 5) != 0 && strncasecmp($where, 'where', 5) != 0)
  $where = 'where ' . $where;

# Arcs
$n = $DB->OneQuery('Select count(*) from Arcs');
if ($res = $DB->ResQuery("Select A.*,E.name From Arcs A Join Elements E on E.Id=A.FromId $where")) {
  echo "<h2 class=c>$TxName Arcs ($res->num_rows of $n with '$where')</h2>\n<table class=mc>\n";
  $n = 0;
  while ($o = $res->fetch_object()) {
    if (!($n%50))
      echo "<tr class='b c bg0'><td class=mid>Id</td><td>Arc<br>Type</td><td class=mid>From</td><td class=mid>To</td><td class=mid>Parent Role</td><td class=mid>Arc<br>Role</td><td>Or<br>der</td><td class=mid>Use</td><td>Prio<br>rity</td><td>Pref..<br>Label</td><td>Clo<br>sed</td><td>Cont<br>ext</td><td>Usa<br>ble</td><td>Target<br>Role</td></tr>\n";
    echo "<tr><td>$o->Id</td><td class=c>", LinkTypeToStr($o->TypeN),
      "</td><td>E.{$o->FromId}<br>$o->name</td><td>", To($o->TypeN, $o->ToId),
      '</td><td>', Role($o->PRoleId), '</td><td style="width:150px">', ArcroleIdToStr($o->ArcroleId),
      '</td><td>', Order($o->ArcOrder),
      '</td><td class=c>', UseTypeToStr($o->ArcUseN), '</td><td>', $o->priority,
      '</td><td>', str_replace(' Label', '', Role($o->PrefLabelRoleId)),
      '</td><td>', BoolToStr($o->ClosedB), '</td><td>', ContextTypeToStr($o->ContextN),
      '</td><td>', BoolToStr($o->UsableB), '</td><td>', Role($o->TargetRoleId), "</td></tr>\n";
    $n++;
  }
  $res->free();
  echo "</table>\n";
}else
  Echo "<br><br>\n";
Form();
####

function Form() {
global $Awhere, $TxName;
echo <<< FORM
<div class=mc style=width:575px>
<p>Enter Where, Limits, and/or Grouping clause(s) for an Arcs table listing.<br>
<br>
Examples:<br>
<span class=sinf>limit 10</span><br>
<span class=sinf>where FromId=2414</span> or <span class=sinf>FromId=2414</span> (The 'where' is optional.)<br>
<span class=sinf>FromId=2414 and TargetRoleId>0 order by TargetRoleId</span><br>
<span class=sinf>(FromId=2414 or ToId=2414) and A.TypeN=2 order by FromId,TargetRoleId</span><br>
<span class=sinf>TargetRoleId>0 order by TargetRoleId,FromId</span><br>
<span class=sinf>ToId=5729</span><br>
<span class=sinf>FromId=5729 or ToId=5729</span><br>
<span class=sinf>PrefLabelRoleId In (3,4) Order by ToId, PrefLabelRoleId</span><br>
<br>
See phpMyAdmin or Doc/DBs/Braiins TX DB.txt for column (field) names<br>and see /Com/inc/$TxName/ConstantsTx.inc for Taxonomy related constants.<br><br>
<b>Warning:</b> No validity checking is performed i.e. invalid SQL will cause an error.</p>
<form method=post>
<input type=text name=Awhere size=75 maxlength=300 value="$Awhere"> <button class=on>List Arcs</button>
</form>
</div><br>
FORM;
Footer(true, true);
exit;
}

function To($typeN, $id) {
  global $DB;
  switch ($typeN) {
    case TLT_Presentation:
    case TLT_Definition: return "E.$id<br>" . ElName($id);
    case TLT_Label:
    case TLT_Reference:  return "D.$id<br>" . $DB->StrOneQuery("Select T.Text from Doc D Join Text T on T.Id=D.TextId where D.Id=$id");
  }
  return $id;
}

function Order($order) {
  if (!$order) {
    if ($order === '0') return '0';
    return '';
  }
  return $order/1000000;
}

function ErrorCallBack($err, $errS) {
  global $TxName;
  echo "<h2 class=c>List $TxName Arcs</h2>\n<p class=c>$errS</p>\n";
  Form();
}
