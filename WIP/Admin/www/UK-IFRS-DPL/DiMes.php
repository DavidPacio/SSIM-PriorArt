<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/UK-IFRS-DPL/DiMes.php

Lists Dimensions and Dimension Member info.

History:
30.06.13 Started based on UK-GAAP-DPL version

ToDo
----

*/
require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsTx.inc';
require Com_Str_Tx.'DiMesA.inc';     # $DiMesA
require Com_Str_Tx.'DimNamesA.inc';  # $DimNamesA
require Com_Str_Tx.'DiMeNamesA.inc'; # $DiMeNamesA

switch (isset($_POST['Sel']) ? Clean($_POST['Sel'], FT_INT) : 1) {  #default to Short first time in
  case 1:
    $ShortB = true;
    $titleExtra = 'Short';
    break;
  case 2:
    $ShortB = false;
    $titleExtra = 'Full';
}

$NotesB = isset($_POST['Notes']);

Head("DiMes: $TxName", true);
echo "<h2 class=c>$TxName Dimension Members $titleExtra Listing</h2>
<p class=c>For a simple list of Dimensions without the Dimension Member detail see <a href='Dimensions.php'>Dimensions</a>.<br>",
($NotesB ? "For Notes on the meaning of the columns and codes see the end of the report." : "For Notes on the meaning of the columns and codes check the 'Include Notes' option at the end and repeat the report."),
'</p>
<table class=mc>
';

$n = 0;
$res = $DB->ResQuery('Select D.*,E.name,T.Text From Dimensions D Join Elements E on E.Id=D.ElId Join Text T on T.Id=E.StdLabelTxtId');
while ($o = $res->fetch_object()) {
  if ($n <= 0) {
    $n = 50;
    echo "<tr class='b bg0'><td>Id</td><td>Dimension<br>Braiins Name / TxId Label / Tx Name / Role</td><td>Dimension{.Dimension Member} Name</td><td class=c>Lev<br>el</td><td class=c>DiMe<br>Type</td><td class=c>DiMe<br>Id</td><td class=c>Sum</td><td class=c>Mux List</td><td class=c>E.Id</td></tr>\n";
  }
  $dimId = (int)$o->Id;
  $didC  = htmlspecialchars(IntToChr($dimId));
  $txName  = $o->name;
  $label = "$o->ElId $o->Text";
  $dimShortName = $DimNamesA[$dimId];
  $role = Role($o->RoleId, true);
  $re2 = $DB->ResQuery("Select Id,ElId,Level From DimensionMembers Where DimId=$dimId");
  $numEles = $numRows = $re2->num_rows;
  if ($ShortB && $numEles>25)
    $numRows = 14; # 10 at the start + ... + 3 at the end
  $ne = 0;
  $firstB = true;
  while ($dmO = $re2->fetch_object()) {
    ++$ne;
    if ($ShortB && $numRows < $numEles && $ne>10) {
      if ($ne==11) {
        $indent = str_pad('', $level*6*2, '&nbsp;'); # 2 nb spaces per level
        echo "$tr<td class=l colspan=7>$indent....</td></tr>\n";
        --$n;
      }
      if ($ne < $numEles-2)
        continue;
    }
    $diMeId = (int)$dmO->Id;
    $dmElId = (int)$dmO->ElId;
    $level  = (int)$dmO->Level;
    $diMeA    = $DiMesA[$diMeId];
    $bits     = $diMeA[DiMeI_Bits];
    $type     = DiMeInfo($bits);
    $diMeName = $DiMeNamesA[$diMeId];
    if ($bits & DiMeB_Default) $diMeName = "$dimShortName [".StrField($diMeName,'.',1).']'; # with the Dim name preface and the DiMe name in []s if the default
    $indent   = str_pad('', $level*6*2, '&nbsp;'); # 2 nb spaces per level
    $muxList  = ($diMeA[DiMeI_MuxListA] ? implode(',', $diMeA[DiMeI_MuxListA]) : '');
    $sumList  = '';
    if ($bits & DiMeB_SumList)
      $sumList = implode(',', $diMeA[DiMeI_SumListA]);
    else if ($bits & DiMeB_SumKids)  # Don't ever have both
      $sumList .= 'Kids';
    if ($firstB) {
      $firstB = false;
      echo "<tr class='c brdt2'><td rowspan=$numRows class=top>$dimId<br>$didC</td><td rowspan=$numRows class='l top'>$dimShortName<br>$label<br>$txName<br>$role</td>";
      $tr = '';
    }else
      $tr = '<tr class=c>';
    echo "$tr<td class=l>$indent$diMeName</td><td>$level</td><td>$type</td><td>$diMeId</td><td>$sumList</td><td>$muxList</td><td class=r>$dmElId</td></tr>\n";
    --$n;
  }
  $re2->free();
}

echo '<tr class=c><td></td><td></td><td class=l>Unallocated (Pseudo Dimension Member)</td><td></td><td>Z</td><td>9999</td><td></td><td></td><td></td></tr>
</table>
';
if ($NotesB) {
  echo "<div class=mc style=width:1215px>
<h3>Notes</h3>
<p>The Dimension{.Dimension Member} References in the third column above are used in formats if a dimension is involved,<br>
by appending them to a Bro name after a comma e.g. PL.Revenue.GeoSeg.ByDestination,Countries.UK</p>
<p>The first part of the reference is the Dimension short name. The 2nd part, if included, is the normalised, short version of the Dimension Member Taxonomy Name.<br>
Entries with no {.Dimension Member} component to their references are defaults, where just the Dimension short name gives the default. The actual default name is shown in []s.</p>
<p class=mb0><b>Dimension Member Type Codes</b>, if present, mean:</p>
<table class=itran>
<tr><td>D</td><td>Default in Taxonomy</td></tr>
<tr><td>B</td><td>Braiins dimension (member)</td></tr>
<tr><td>R</td><td>Report type - only for Reporting use e.g. of summed values. Thus is Non-Posting</td></tr>
<tr><td>Y</td><td>prior Year adjustment (Restated) type</td></tr>
<tr><td>Z</td><td>Reserved for Braiins internal use</td></tr>
</table>

<p class=mt05>Dimension members without  DiMe and Prop Type codes may be used with any Bro whose Hypercube includes the Dim in question, unless excluded by other settings.</p>

<p><b>Sum</b>: 'Kids' or list of DiMeIds to be summed. Kids means the children of this Dimension Member i.e. those below it that have a higher level number and are indented.</p>

<p><b>Mux List</b>: List of mutually exclusive DiMeIds i.e. a DiMe with a Mux List can't be used if one the DiMes in the Mux List is already in use.</p>
";
}

$ShortChecked = ($ShortB  ? ' checked' : '');
$FullChecked  = (!$ShortB ? ' checked' : '');
$NotesChecked = ($NotesB  ? ' checked' : '');

echo "<div class=mc style=width:430px>
<form method=post>
<input id=i1 type=radio class=radio name=Sel value=1$ShortChecked> <label for=i1>Short Listing with Dimension Members in Shortened List form</label><br>
<input id=i2 type=radio class=radio name=Sel value=2$FullChecked> <label for=i2>Full Listing including All Dimension Members</label><br>
<input id=i3 type=checkbox class=radio name=Notes value=1$NotesChecked> <label for=i3>Include Notes</label><br>
<p class='c mb0'><button class='on m05'>Dimension Members</button></p>
</form>
</div>
";
Footer(true,true);
##################


function DiMeInfo($bits) {
  $type = '';
  for ($b=1; $b<=DiMeB_Zilch; $b*=2) {
    if ($bits & $b)
    switch ($b) {
      case DiMeB_Default:$type  = 'D,'; break;
      case DiMeB_BD:     $type .= 'B,'; break;
      case DiMeB_RO:     $type .= 'RO,'; break;
      case DiMeB_SumKids:
      case DiMeB_SumList:
      case DiMeB_muX:                   break;
      case DiMeB_pYa:    $type .= 'Y,'; break;
      case DiMeB_Zilch:  $type .= 'Z,'; break;
      default:           $type .= 'Unknown type,'; break;
    }
  }
  return substr($type, 0, -1);
}
