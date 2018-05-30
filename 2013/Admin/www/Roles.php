<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/Roles.php

Lists the SIM Roles

History:
15.03.13 Started based on Tx Roles.php
02.07.13 Changed to SIM

*/
require 'BaseSIM.inc';

Head('SIM Roles', true);

/*
const Role_Report = 1;
const Role_Note   = 2;
const Role_Prop   = 3;
const Role_Folio  = 4; */

$usedWithA = [0,'Reports', 'Notes', 'Properties', 'Folios'];

echo "<h2 class=c>SIM Roles</h2>
<p class=c>This is a set of roles used with SIM for documentation purposes, mainly with Properties and Folios. They serve no functional purpose.<br>Multiple Properties or Folios may be assigned the same role of the right 'Used With' type.</p>
<table class=mc>
";
$res = $DB->ResQuery('Select * From Roles Order by Id');
$n = 0;
while ($o = $res->fetch_object()) {
  if (!($n%50))
    echo "<tr class='b bg0 c'><td rowspan=2>Id</td><td rowspan=2>The Role</td><td rowspan=2>Used With</td><td colspan=2>Associated Properties or Folios</td></tr><tr class='b bg0 c'><td>Id</td><td>Label</td></tr>\n";
  $id = (int)$o->Id;
  $roleN = (int)$o->RoleN;
  if ($id === 1) {
    # Fudge for temporary 16.04.13 use of Role 1 with property Objects
    $re2 = $DB->ResQuery("Select Id,Label From Properties Where RoleId=$id Order by Id");
    $numRows = $re2->num_rows;
    $roleN = Role_Prop;
  }else
  switch ($roleN) {
    case Role_Prop:  $re2 = $DB->ResQuery("Select Id,Label From Properties Where RoleId=$id Order by Id"); $numRows = $re2->num_rows; break;
    case Role_Folio: $re2 = $DB->ResQuery("Select Id,Label From Folios Where RoleId=$id Order by Id"); $numRows = $re2->num_rows; break;
    default: $numRows = 0; break;
  }
  $usedWith = $usedWithA[$roleN];
  if ($numRows > 1)
    echo "<tr><td rowspan=$numRows class=r>$id</td><td rowspan=$numRows>$o->Role</td><td rowspan=$numRows>$usedWith</td>";
  else
    echo "<tr><td class=r>$id</td><td>$o->Role</td><td>$usedWith</td>";
  $tr = '';
  if ($numRows) {
    while ($o = $re2->fetch_object()) {
      echo "$tr<td class=r>$o->Id</td><td>$o->Label</td>";
      $tr = "</tr>\n<tr>";
    }
    $re2->free();
  }
  echo "</tr>\n";
  $n++;
}
$res->free();
echo "</table>
";
Footer(true,true);
exit;
