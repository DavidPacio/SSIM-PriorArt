<?php /* Copyright 2011-2013 Braiins Ltd

HypercubesSubset.php

Checks whether two Hypercubes to see if either is a subset of the other

History:
14.11.11 Written
21.04.13 Removed Braiins Dimension check

*/
require 'BaseTx.inc';
require Com_Str_Tx.'Hypercubes.inc';       # $HyNamesA

$t = "Hypercubes Subset Check $TxName";
Head($t, true);
echo "<h2 class=c>$t</h2>\n";

if (!isset($_POST['Hys']))
  Form();
  #######

$Hys = Clean($_POST['Hys'], FT_STR);
$hysA = explode(',', $Hys);
if (count($hysA) == 2 && is_numeric($a = $hysA[0]) && is_numeric($b = $hysA[1])) {
  # Is hy i a subset of hy j?
  if (IsHypercubeSubset($a, $b))
      echo "<p class=c>Hypercube $a is a subset of hypercube $b.</p>";
  else if (IsHypercubeSubset($b, $a))
    echo "<p class=c>Hypercube $b is a subset of hypercube $a.</p>";
  else
    echo "<p class=c>Neither hypercube $a nor $b is a subset of the other.</p>";
}else
  echo "<p class='c mb0 navy b'>2 hypercube Ids are required</p>";
  Form(false,false);

Form();
######

function Form() {
  global $Hys;
  echo "<form method=post>
<p class=c>Enter two Hypercube Ids comma separated: <input type=text name=Hys size=5 maxlength=5 value=$Hys></p>
<p class=c><button class='on m05'>Hypercubes Subset Check</button></p>
</form>
";
  Footer(false, false);
}
