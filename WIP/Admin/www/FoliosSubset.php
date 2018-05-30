<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/FoliosSubset.php

Checks two Folios to see if either is a subset of the other

History:
22.03.13 Written based on UK-GAAP-DPL HypercubesSubset.php
03.07.13 B R L -> SIM

*/
require 'BaseSIM.inc';
require Com_Str.'Folios.inc'; # $FolioPropsA

$t = 'Folios Subset Check';
Head($t, true);
echo "<h2 class=c>$t</h2>\n";

if (!isset($_POST['Fos']))
  Form();
  #######

$Fos = Clean($_POST['Fos'], FT_STR);
$fosA = explode(',', $Fos);
if (count($fosA) == 2 && is_numeric($a = $fosA[0]) && is_numeric($b = $fosA[1])) {
  # Is Folio a a subset of Folio b?
  if (IsFolioSubset($a, $b))
    echo "<p class=c>Folio $a is a subset of Folio $b.</p>";
  else if (IsFolioSubset($b, $a))
    echo "<p class=c>Folio $b is a subset of Folio $a.</p>";
  else
    echo "<p class=c>Neither Folio $a nor $b is a subset of the other.</p>";
}else
  echo "<p class='c mb0 navy b'>2 Folio Ids are required.</p>";
  Form(false,false);

Form();
######

function Form() {
  global $Fos;
  echo "<form method=post>
<p class=c>Enter two Folio Ids comma separated: <input type=text name=Fos size=5 maxlength=5 value=$Fos></p>
<p class=c><button class='on m05'>Folios Subset Check</button></p>
</form>
";
  Footer(false, false);
}
