<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/UK-IFRS-DPL/Hypercubes.php

Lists Hypercubes plus Dimensions and optionally the Dimension Elements

History:
29.06.13 Started based on UK-GAAP-DPL version

*/
require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsTx.inc';
require Com_Str_Tx.'DimNamesA.inc';  # $DimNamesA
require Com_Str_Tx.'Hypercubes.inc'; # $HyNamesA

Head("Hypercubes List $TxName", true);

$IdsAndSubsetInfoB = $GraphicalB = $HysAndDimsNoDiMesB = $InclDiMesB = $ShortenedDiMesListsB = false;

$sel = isset($_POST['Sel']) ? Clean($_POST['Sel'], FT_INT) : 3; #default to Short first time in

switch ($sel) {
  case 1: # Short Version with Dims as just Ids, plus Subset Info
    $IdsAndSubsetInfoB = true;
    $titleExtra = ' Short Listing with Dims as just Ids, plus Subset Info';
    break;
  case 2: # 'Graphical' View of the Dims
    $GraphicalB = true;
    $titleExtra = " Listing with a 'Graphical' View of the Dims";
    break;
  case 3: # Hypercubes and Dimensions without Dimension Members
    $HysAndDimsNoDiMesB = true;
    $titleExtra = ' Short Listing';
    break;
  case 4: # Full Listing including All Dimension Members
  case 5: # As above with Dimension Members in Shortened List form
    $InclDiMesB = true;
    if ($sel === 5) $ShortenedDiMesListsB = true;
    $titleExtra = ' Including Dimension Members' . ($ShortenedDiMesListsB ? ' (Shortened Lists)' : '');
    $hdgTxt     = ' Braiins Name / Tx Id Label / Tx Name / Role';
}
echo "<h2 class=c>$TxName Hypercubes$titleExtra</h2>
<table class=mc>
";
$res = $DB->ResQuery('Select H.*,E.name,T.Text From Hypercubes H Join Elements E on E.Id=H.ElId Join Text T on T.Id=E.StdLabelTxtId');
$tot = 0;
$n = 50; // just for headings output purposes
while ($o = $res->fetch_object()) {
  if ($n >= 50) {
    $n = 0;
    if ($IdsAndSubsetInfoB)
      echo "<tr class='b bg0'><td colspan=2 class=c>Hypercube</td><td rowspan=2 class=c>Dimension Ids</td><td rowspan=2 class=c>Hypercube is Subset of Hypercubes:</td><td rowspan=2 class=c>Hypercube Has Hypercube Subsets:</td></tr>\n",
           "<tr class='b bg0'><td class=c>Id</td><td>Braiins Name</td></tr>\n";
    else if ($GraphicalB) {
      $hdg = "<tr class='b bg0'><td colspan=2 class=c>Hypercubes</td><td colspan=50 class=c>Dimension Ids</td></tr>
<tr class='b bg0'><td class=c>Id</td><td>Braiins Name</td>";
      for ($i=1;$i<=DimId_Max; ++$i)
        $hdg .= "<td>$i</td>";
      echo $hdg."</tr>\n";
    }else if ($HysAndDimsNoDiMesB)
      echo "<tr class='b bg0'><td colspan=3 class=c>Hypercubes</td><td colspan=4 class=c>Dimensions</td></tr>\n",
           "<tr class='b bg0'><td class=c>Id</td><td>TxId</td><td>Braiins Name / Tx Name / Role</td><td>Id</td><td>Tx Id</td><td>Braiins Name</td><td>Role</td></tr>\n";
    else
      echo "<tr class='b bg0'><td colspan=2 class=c>Hypercubes</td><td colspan=2 class=c>Dimensions</td></tr>\n",
           "<tr class='b bg0'><td class=c>Id</td><td>$hdgTxt</td><td>Id</td><td>$hdgTxt / Dimension Members as DiMeId TxId Label</td></tr>\n";
  }
  $hyId   = (int)$o->Id;
  $hidC  = IntToChr($hyId);
  $dimsS = $o->Dimensions;
  $nDims = strlen($dimsS);
  $txName  = $o->name;
  $hyName = $HyNamesA[$hyId];
  if ($nDims) {
    if ($IdsAndSubsetInfoB || $GraphicalB) {
      echo "<tr><td class=c>$hyId</td><td>$hyName</td>";
    }else{
      echo "<tr><td rowspan=$nDims class='c top'>$hyId<br>'$hidC'</td><td rowspan=$nDims ";
      if ($HysAndDimsNoDiMesB)
        echo "class='r top'>$o->ElId</td><td rowspan=$nDims class=top>$hyName<br>$txName<br>", Role($o->RoleId, true), '</td>';
      else
        echo "class=top>$hyName<br>$o->ElId $o->Text<br>$txName<br>", Role($o->RoleId, true), '</td>';
    }
    // Dimensions
    if ($GraphicalB) {
      $ds = '';
      for ($i=0,$d=1; $i<$nDims; $i++) {
        $dimId = ChrToInt($dimsS[$i]);
        while ($d < $dimId) {
          $ds .= '<td></td>';
          ++$d;
        }
        $ds .= "<td>#</td>";
        ++$d;
      }
      for ( ;$d<=DimId_Max; ++$d)
        $ds .= '<td></td>';
      echo $ds."</tr>\n";
    }else{
      # Not Graphical
      if ($IdsAndSubsetInfoB)
        echo '<td>';
      for ($i=0; $i<$nDims; $i++) {
        $dimId = ChrToInt($dimsS[$i]);
        if ($IdsAndSubsetInfoB)
          echo ($i ? ', ' : '') . $dimId;
        else{
          $d = $DB->ObjQuery("Select D.*,E.name,T.Text From Dimensions D Join Elements E on E.Id=D.ElId Join Text T on T.Id=E.StdLabelTxtId Where D.Id=$dimId");
          $name = $d->name;
          $dimName = $DimNamesA[$dimId];
          $role = Role($d->RoleId, true); # true = no trailing [id]
          if ($HysAndDimsNoDiMesB)
            echo ($i ? '<tr>' : '') . "<td class=r>$dimId</td><td>$d->ElId</td><td>$dimName</td><td>$role";
          else
            echo ($i ? '<tr>' : '') . "<td class='r top'>$dimId</td><td class=top>$dimName<br>$d->ElId $d->Text<br>$name<br>$role";
          $n++;
          if ($InclDiMesB) {
            $firstB = true;
            $r3 = $DB->ResQuery("Select M.*,T.Text From DimensionMembers M Join Elements E on E.Id=ElId Join Text T on T.Id=E.StdLabelTxtId Where M.DimId=$dimId");
            $numEles = $r3->num_rows;
            $ne = 0;
            while ($m = $r3->fetch_object()) {
              $bits = (int)$m->Bits;
              if ($firstB && !($bits & DiMeB_Default)) echo '<br>&nbsp;&nbsp;&nbsp;No default';
              if (!$ShortenedDiMesListsB || $ne < 10 || $numEles - $ne < 4) {
                echo "<br>&nbsp;&nbsp;&nbsp;$m->Id $m->ElId $m->Text";
              }else if ($ne == 10)
                echo '<br>&nbsp;&nbsp;&nbsp;....';
              $firstB = false;
              $ne++;
              $n++;
              $tot++;
            }
            $r3->free();
          }
          echo "</td></tr>\n";
        }
      } # end of dimensions loop
      if ($IdsAndSubsetInfoB) {
        $subOf = $hasSubs = '';
        for ($i=1; $i<=HyId_Max; ++$i)
          if ($i != $hyId) {
            if (IsHypercubeSubset($hyId, $i)) $subOf   .= ", $i"; # is hyId a subset of i?
            if (IsHypercubeSubset($i, $hyId)) $hasSubs .= ", $i"; # is i a subset of hyId?
          }
        $subOf = substr($subOf, 2);
        $hasSubs = substr($hasSubs, 2);
        echo "</td><td style='width:216px'>$subOf</td><td style='width:216px'>$hasSubs</td></tr>\n";
      }
    } # end of not graphical
  }else{ # empty hypercube
    if ($IdsAndSubsetInfoB || $GraphicalB)
      echo "<tr><td class=c>$hyId</td><td>$hyName</td><td colspan=50></td></tr>\n";
    else{
      echo "<tr><td class='c top'>$hyId<br>'$hidC'</td>";
      if ($HysAndDimsNoDiMesB)
        echo "<td>$o->ElId</td><td>$hyName</td><td>None</td><td colspan=4></td></tr>\n";
      else
        echo "<td>$hyName<br>$o->ElId $o->Text<br>$txName<br>None</td><td colspan=2></td></tr>\n";
    }
  }
}
$res->free();
echo "</table>\n";
if ($InclDiMesB)
  echo "<p class=c>Total number of Hypercubes -> Dimensions -> Members = $tot</p>\n";
else
echo "<br>\n";

echo "<div class=mc style=width:450px>
<form method=post>
<input id=i1 type=radio class=radio name=Sel value=1";
if ($IdsAndSubsetInfoB) echo " checked";
echo "> <label for=i1>Short Version with Dims as just Ids, plus Subset Info</label><br>
<input id=i2 type=radio class=radio name=Sel value=2";
if ($GraphicalB) echo " checked";
echo "> <label for=i2>'Graphical' View of the Dims</label><br>
<input id=i3 type=radio class=radio name=Sel value=3";
if ($HysAndDimsNoDiMesB) echo " checked";
echo "> <label for=i3>Hypercubes and Dimensions without Dimension Members</label><br>
<input id=i4 type=radio class=radio name=Sel value=4";
if ($InclDiMesB) echo " checked";
echo "> <label for=i4>Full Listing including All Dimension Members</label><br>
<input id=i5 type=radio class=radio name=Sel value=5";
if ($ShortenedDiMesListsB) echo " checked";
echo "> <label for=i5>As above with Dimension Members in Shortened List form</label><br>
<p class=c><button class='on m05'>List Hypercubes</button></p>
</form>
</div>
";
Footer(true,true);
##################

