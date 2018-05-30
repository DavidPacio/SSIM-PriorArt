<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/Folios.php

Lists the SIM Folios plus Properties and optionally the Property Members

ToDo
====
Take account of PMemsA for listings other than the Short one.

History:
21.03.12 Started based on UK-GAAP-DPL HypercubesList.php
22.05.13 Members column added to Short Listing re PMemsA
03.07.13 B R L -> SIM
19.07.13 I t e m -> Member

*/
require 'BaseSIM.inc';
require Com_Str.'Folios.inc';     # $FolioPropsA used by IsFolioSubset()
require Com_Str.'PMemNamesA.inc'; # $PMemNamesA

Head('SIM Folios', true);

$IdsAndSubsetInfoB = $GraphicalB = $ShortB = $InclElementsB = $ShortenB = false;

$sel = isset($_POST['Sel']) ? Clean($_POST['Sel'], FT_INT) : 3; #default to Short first time in

switch ($sel) {
  case 1:
    $IdsAndSubsetInfoB = true;
    $titleExtra = ' Short Listing with Properties as just Ids, plus Subset Info';
    $foIdMax    = $DB->OneQuery('Select Id from Folios Order by Id Desc Limit 1');
    break;
  case 2:
    $GraphicalB = true;
    $titleExtra = " Listing with a 'Graphical' View of the Properties";
    $propIdMax  = $DB->OneQuery('Select Id from Properties Order by Id Desc Limit 1');
    break;
  case 3:
    $ShortB     = true;
    $titleExtra = ' Short Listing';
    # Chop off the Property prefix from PMemNamesA For the Members col as the members are displayed in the property row
    foreach ($PMemNamesA as $i => $name)
      if ($i && Instr(DOT, $name))
       list( , $PMemNamesA[$i]) = explode(DOT, $name);
    break;
  case 4:
  case 5:
    $InclElementsB = true;
    if ($sel == 5) $ShortenB = true;
    $titleExtra = ' Including Property Members' . ($ShortenB ? ' (Shortened Lists)' : '');
    $hdgTxt     = ' Name / Label / Role';
}
echo "<h2 class=c>Folios$titleExtra</h2>
<table class=mc>
";
$res = $DB->ResQuery('Select * From Folios Order by Id');
$tot = 0;
$n = 50; // just for headings output purposes
while ($o = $res->fetch_object()) {
  if ($n >= 50) {
    $n = 0;
    if ($IdsAndSubsetInfoB)
      echo "<tr class='b bg0'><td colspan=2 class=c>Folio</td><td rowspan=2 class=c>Property Ids</td><td rowspan=2 class=c>Folio is Subset of Folios:</td><td rowspan=2 class=c>Folio Has Folio Subsets:</td></tr>\n",
           "<tr class='b bg0'><td class=c>Id</td><td>Name</td></tr>\n";
    else if ($GraphicalB) {
      $hdg = "<tr class='b bg0'><td colspan=2 class=c>Folios</td><td colspan=$propIdMax class=c>Property Ids</td></tr>
<tr class='b bg0'><td class=c>Id</td><td>Name</td>";
      for ($i=1;$i<=$propIdMax; ++$i)
        $hdg .= "<td>$i</td>";
      echo $hdg."</tr>\n";
    }else if ($ShortB)
      echo "<tr class='b bg0'><td colspan=2 class=c>Folios</td><td colspan=4 class=c>Properties</td></tr>\n",
           "<tr class='b bg0'><td class=c>Id</td><td>Name / Label / Role</td><td>Id</td><td>Name</td><td>Members</td><td>Role</td></tr>\n";
    else
      echo "<tr class='b bg0'><td colspan=2 class=c>Folios</td><td colspan=2 class=c>Properties</td></tr>\n",
           "<tr class='b bg0'><td class=c>Id</td><td>$hdgTxt</td><td>Id</td><td>$hdgTxt / Property Members as PMemId Label</td></tr>\n";
  }
  $foId   = (int)$o->Id;
  $fName  = $o->Name;
  $propsS = $o->Props;
  $nProps = strlen($propsS);
  $pMemsA = !$o->PMemsA ? : json_decode($o->PMemsA);
  if ($nProps) {
    if ($IdsAndSubsetInfoB || $GraphicalB) {
      echo "<tr><td class=c>$foId</td><td>$fName</td>";
    }else{
      $role = Role($o->RoleId);
     #echo "<tr><td rowspan=$nProps class='c top'>$foId<br>'$foIdC'</td><td rowspan=$nProps class=top>$fName<br>$o->Label<br>$role</td>";
      echo "<tr><td rowspan=$nProps class='c top'>$foId</td><td rowspan=$nProps class=top>$fName<br>$o->Label<br>$role</td>";
    }
    # Properties
    if ($GraphicalB) {
      $ps = '';
      for ($i=0,$p=1; $i<$nProps; $i++) {
        $propId = ChrToInt($propsS[$i]);
        while ($p < $propId) {
          $ps .= '<td></td>';
          ++$p;
        }
        $ps .= "<td>#</td>";
        ++$p;
      }
      for ( ;$p<=$propIdMax; ++$p)
        $ps .= '<td></td>';
      echo $ps."</tr>\n";
    }else{
      # Not Graphical
      if ($IdsAndSubsetInfoB)
        echo '<td>';
      for ($i=0; $i<$nProps; $i++) {
        $propId = ChrToInt($propsS[$i]);
        if ($IdsAndSubsetInfoB)
          echo ($i ? ', ' : '') . $propId;
        else{
          $p = $DB->ObjQuery("Select * From Properties Where Id=$propId");
          $pName = $p->Name;
          $role  = Role($p->RoleId);
          if ($ShortB) {
            if ($pMemsA && isset($pMemsA[$i])) {
              #Dump('$pMemsA[$i]',$pMemsA[$i]);
              $pMem = PMemDiMesAStr($pMemsA[$i], $PMemNamesA);
            }else
              $pMem = '';
            echo ($i ? '<tr>' : '') . "<td class=r>$propId</td><td>$pName</td><td>$pMem</td><td>$role";

          }else
            echo ($i ? '<tr>' : '') . "<td class='r top'>$propId</td><td class=top>$pName<br>$p->Label<br>$role";
          $n++;
          if ($InclElementsB) {
            $firstB = true;
            $r3 = $DB->ResQuery("Select Id,Label From PMembers Where PropId=$propId");
            $numPMems = $r3->num_rows;
            $nPMem = 0;
            while ($pi = $r3->fetch_object()) {
             #$bits = (int)$pi->Bits;
              if (!$ShortenB || $nPMem < 10 || $numPMems - $nPMem < 4)
                echo "<br>&nbsp;&nbsp;&nbsp;$pi->Id $pi->Label";
              else if ($nPMem == 10)
                echo '<br>&nbsp;&nbsp;&nbsp;....';
              $firstB = false;
              ++$nPMem;
              ++$n;
              ++$tot;
            }
            $r3->free();
          }
          echo "</td></tr>\n";
        }
      } # end of properties loop
      if ($IdsAndSubsetInfoB) {
        $subOf = $hasSubs = '';
        for ($i=1; $i<=$foIdMax; ++$i)
          if ($i != $foId) {
            if (IsFolioSubset($foId, $i))  $subOf   .= ", $i"; # is foId a subset of i?
            if (IsFolioSubset($i, $foId))  $hasSubs .= ", $i"; # is i a subset of foId?
          }
        $subOf   = substr($subOf, 2);
        $hasSubs = substr($hasSubs, 2);
       #echo "</td><td style=width:260px>$subOf</td><td style=width:300px>$hasSubs</td></tr>\n";
        echo "</td><td>$subOf</td><td>$hasSubs</td></tr>\n";
      }
    } # end of not graphical
  }
}
$res->free();
echo "</table>\n";
if ($InclElementsB)
  echo "<p class=c>Total number of Folios -> Properties -> Members = $tot</p>\n";
else
echo "<br>\n";

echo "<div class=mc style=width:450px>
<form method=post>
<input id=i1 type=radio class=radio name=Sel value=1";
if ($IdsAndSubsetInfoB) echo " checked";
echo "> <label for=i1>Short Version with Properties as just Ids, plus Subset Info</label><br>
<input id=i2 type=radio class=radio name=Sel value=2";
if ($GraphicalB) echo " checked";
echo "> <label for=i2>'Graphical' View of the Properties</label><br>
<input id=i3 type=radio class=radio name=Sel value=3";
if ($ShortB) echo " checked";
echo "> <label for=i3>Short Version of Listing</label><br>
<input id=i4 type=radio class=radio name=Sel value=4";
if ($InclElementsB) echo " checked";
echo "> <label for=i4>Full Listing including All Property Members</label><br>
<input id=i5 type=radio class=radio name=Sel value=5";
if ($ShortenB) echo " checked";
echo "> <label for=i5>As above with Property Members in Shortened List form</label><br>
<p class=c><button class='on m05'>List Folios</button></p>
</form>
</div>
";
Footer(true,true);
##################

