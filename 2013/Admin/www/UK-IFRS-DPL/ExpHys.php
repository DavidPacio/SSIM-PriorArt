<?php /* Copyright 2011-2013 Braiins Ltd

ExpHys.php

Temporary Export to produce a start for SIM Folios

History:
20.03.13 Written
04.07.13 Revised for UK-IFRS-DPL

*/
require 'BaseTx.inc';
require Com_Str_Tx.'DimNamesA.inc';    # $DimNamesA
require Com_Str_Tx.'Hypercubes.inc';   # $HyNamesA

Head("Exp Hys", true);
echo "<h2 class=c>Hys Export</h2>
<p>Name | BraiinsName | Label | Role | Dimensions</p>
";

$file = 'ExpHys.txt';
$Fh = fopen('../'.$file, 'w');

$res = $DB->ResQuery('Select H.*,E.name,T.Text From Hypercubes H Join Elements E on E.Id=H.ElId Join Text T on T.Id=E.StdLabelTxtId');
while ($o = $res->fetch_object()) {
  $hyId  = (int)$o->Id;
  $name  = $o->name;
  $BName = $HyNamesA[$hyId];
  $label = $o->Text;
  $role  = Role($o->RoleId, true); # true = no trailing [id]
 #$name  = str_replace(['Share-based', 'Non-current','Joint-ventures','Non-exceptionalItems','Hypercube','-'],['ShareBased', 'NonCurrent','JointVentures','NonExceptionalItems',''], $name);
  $name  = str_replace(['Hypercube'],[''], $name);
 #$label = str_replace(['Share-based', 'Joint-ventures',' [Hypercube]'],['Share Based', 'Joint Ventures',''], $label);
  $label = str_replace([' [Hypercube]'],[''], $label);
 #$role  = str_replace(['Share-based', 'Joint-Ventures'], ['Share Based', 'Joint Ventures'], $role);
 #$dimsS = ChrListToCsList($o->Dimensions);
  $dimsS = DimsChrListToSrce($o->Dimensions, true);
  echo "$name | $BName | $label | $role | $dimsS<br>";
  fwrite($Fh, "$name	$BName	$label	$role	$dimsS\n");
}
fclose($Fh);

Footer();
#########

