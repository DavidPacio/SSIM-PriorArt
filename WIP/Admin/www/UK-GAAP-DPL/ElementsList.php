<?php /* Copyright 2011-2013 Braiins Ltd

ElementsList.php

Lists Elements

History:
30.03.11 Started
18.07.11 Tidied up for html5

*/
require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsTx.inc';
require Com_Str_Tx.'NamespacesRgA.inc'; # $NamespacesRgA

Head("Elements List $TxName", true);

if (!isset($_POST['Ewhere'])) {
  echo "<h2 class=c>List $TxName Taxonomy Element(s)</h2>\n";
  $Ewhere = 'limit 10';
  Form();
  ######
}

$Ewhere = Clean($_POST['Ewhere'], FT_STR);

// Used by Error() if called
$ErrorHdr="List Elements errored with:";

$where = trim(str_replace("%\%", "%\\\\\\%", $Ewhere)); // Re Hypercube \

if (empty($where))
  $Ewhere = $where = 'limit 10';

if (strncasecmp($where, 'limit', 5) && strncasecmp($where, 'where', 5) && strncasecmp($where, 'order', 5))
  $where = 'where ' . $where;

// Elements
$n = $DB->OneQuery('Select count(*) from Elements');
if ($res = $DB->ResQuery("Select E.*,T.Text From Elements E Join Text T on T.Id=E.StdLabelTxtId $where")) {
  echo "<h2 class=c>$TxName Elements ($res->num_rows of $n with '$Ewhere')</h2>\n<table class=mc>\n";
  $n = 0;
  while ($o = $res->fetch_object()) {
    $ns   = $NamespacesRgA[(int)$o->NsId];
    $name = $o->name; #Fold($o->name, 70);
    if (!($n%50))
      echo "<tr class='b c bg0'><td class=mid>Id</td><td>NS<br> Prefix</td><td class=mid>Name / Standard Label</td><td class=mid>Hypercubes</td><td>Abstract<br>Concrete</td><td class=mid>Type</td><td>Subst.<br>Group</td><td class=mid>Period</td><td>Sign</td><td>Nill<br>able</td></tr>\n"; // <td>Sch<br>ema</td></tr>\n";
    echo "<tr><td>$o->Id</td><td>$ns</td><td>$name<br>$o->Text</td><td class=c>", ChrListToCsList($o->Hypercubes),
      '</td><td class=c>', ($o->abstract ? 'Abstract' : 'Concrete'),
      '</td><td class=c>', ElementTypeToStr($o->TypeN),
      '</td><td class=c>', SubstGroupToStr($o->SubstGroupN),
      '</td><td class=c>', PeriodTypeToStr($o->PeriodN),
      '</td><td class=c>', SignToStr($o->SignN),
      '</td><td class=c>', BoolToStr($o->nillable),"</td></tr>\n";
    //  '</td><td>', $o->SchemaId, "</td></tr>\n";
    $n++;
  }
  $res->free();
  echo "</table>\n";
}else
  Echo "<br><br>\n";
Form();
######

function Form() {
global $Ewhere, $n, $TxName;
echo <<< FORM
<div class=mc style='width:600px'>
<p>Enter Where, Limits, and/or Grouping clause(s) for an Elements table listing.<br><br>
Examples:<br>
<span class=sinf>limit 10</span><br>
<span class=inlb style=width:112px>To list Tuples:</span><span class=sinf>where SubstGroupN = 2 Order by name</span> (The 'where ' is optional.)<br>
<span class=inlb style=width:112px>or:</span><span class=sinf>Name like '%grouping' order by name</span><br>
<span class=inlb style=width:112px>To list Dimensions:</span><span class=sinf>SubstGroupN = 3 Order by name</span><br>
<span class=inlb style=width:112px>To list Hypercubes:</span><span class=sinf>SubstGroupN = 4</span><br>
<span class=sinf>E.Id in (3361,3358,4063,4031,5282,5283)</span><br>
<span class=sinf>TypeN=1 group by NsId</span><br>
<span class=sinf>SubstGroupN=1 and E.Id not in (select ToId from Arcs where TypeN in (1,2))</span><br>
<span class=sinf>SubstGroupN=1 and abstract is null and E.Id not in (Select TxId from BroInfo where TxId>0)</span><br>
<span class=sinf>Hypercubes like '%@%'</span><br>
<span class=sinf>name like '%heading'</span><br><br>
See phpMyAdmin or Doc/DBs/Braiins TX DB.txt for column (field) names<br>and see /Com/inc/$TxName/ConstantsTx.inc for Taxonomy related constants.<br><br>
<b>Warnings:</b><br>
- Use single quotes for strings as in the final example above<br>
- No validity checking is performed i.e. invalid SQL will cause an error.</p>
<form method=post>
<input type=text name=Ewhere size=75 maxlength=300 value="$Ewhere"> <button class=on>List Elements</button>
</form>
FORM;
echo "<br></div>
";
Footer(true, $n>50);
exit;
}

function ErrorCallBack($err, $errS) {
  global $TxName;
  echo "<h2 class=c>List $TxName Elements</h2>\n<p class=c>$errS</p>\n";
  Form();
}
