<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/Utils/UK-GAAP-DPL/ElementsLookup.php

Use the Taxonomy DB to display info about an element or elements

ToDo djh??
----
Do Labels and References in one query.

Read Roles first

Take hy and PrefLabelRoleId into account re Bros

Fix Clean call skip for $input re double quoted searches


History:
29.09.12 Started based on UK-GAAP version
08.10.12 Element Id option removed with removal Ids from the DB

*/
require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsTx.inc';
require Com_Inc_Tx.'TxElementsSkipped.inc';
require Com_Str_Tx.'NamespacesTxA.inc'; # $NamespacesTxA
Head("Elements Lookup: $TxName", true);

if (!isset($_POST['Input'])) {
  echo "<h2 class=c>Lookup of $TxName Taxonomy Element(s)</h2>\n";
  $TypeB = true;
  Form('');
}

$DataTypeFiltersA = [];
#Clean($_POST['Input'], FT_STR, true, $input);
$input    = Clean($_POST['Input'], FT_STR);
$TypeB    = isset($_POST['Type']);
$ExpandOnlyOwnToTreeB = isset($_POST['ExpandOnlyOwnToTree']);
$FromTreeB= isset($_POST['FromTree']);
$TreeChoice=Clean($_POST['TreeChoice'], FT_INT);
$NameB    = isset($_POST['Name']);
$SpacesB  = isset($_POST['Spaces']);
$SearchAB = isset($_POST['SearchA']);
$DataTypeFiltersA[TET_Money]    = isset($_POST['Money']);
$DataTypeFiltersA[TET_String]   = isset($_POST['String']);
$DataTypeFiltersA[TET_Boolean]  = isset($_POST['Bool']);
$DataTypeFiltersA[TET_Date]     = isset($_POST['Date']);
$DataTypeFiltersA[TET_Decimal]  = isset($_POST['Decimal']);
$DataTypeFiltersA[TET_Percent]  = isset($_POST['Percent']);
$DataTypeFiltersA[TET_Share]    = isset($_POST['Share']);
$DataTypeFiltersA[TET_PerShare] = isset($_POST['PerShare']);
$ShowBrosB = isset($_POST['ShowBros']);
$ExclBrosB = isset($_POST['ExclBros']);

$FilterByTypeB = 0;
foreach ($DataTypeFiltersA as $i)
  $FilterByTypeB += $i;
if ($FilterByTypeB) $TypeB = true;

$NumAbstract  = 0;
$AbstractElsA = [];
$TupIdToTupTxIdA = []; # TupId -> TupTxId
$res = $DB->ResQuery('Select TupId,TupTxId from TuplePairs Group by TupId');
while ($o = $res->fetch_object())
  $TupIdToTupTxIdA[(int)$o->TupId] = (int)$o->TupTxId;
$res->free();
$TupTxIdToTupIdA = array_flip($TupIdToTupTxIdA); # TupTxId -> TupId
if ($ShowBrosB || $ExclBrosB) {
  if ($ExclBrosB) $ShowBrosB = false;
  # Build ElementsUsedByBrosA as [TxId => 1]
  # Does not handle different hypercubes and tuples like Concrete Elements
  $ElementsUsedByBrosA = [];
  $res = $DB->ResQuery('Select TupId,TxId from BroInfo Where TxId is not null');
  while ($o = $res->fetch_object()) {
    $ElementsUsedByBrosA[(int)$o->TxId] = 1;
    if ($o->TupId)
      $ElementsUsedByBrosA[$TupIdToTupTxIdA[(int)$o->TupId]] = 1; # tupId -> TxId
  }
  $res->free();
}

$origInput = $input;
if ($input>'') {
  $inputsA = [];
  $sA = explode($c=DQ, $input);
  while (count($sA) > 2) {
    $t = $sA[1];
    $inputsA[] = trim($t);
    $input = str_replace("$c$t$c", '', $input);
    $sA = explode($c, $input);
  }
  $sA = explode(',', trim($input, ','));
  foreach ($sA as $t)
    if ($t = trim($t))
    $inputsA[] = $t;
}else{
  echo "<h2 class=c>Lookup of $TxName Taxonomy Element(s)</h2>\n";
  Form($origInput);
}

foreach ($inputsA as $input)
  Lookup($input);

Form($origInput);
# --------

function Lookup($input) {
  global $DB, $TxName, $TypeB, $SearchAB, $FilterByTypeB, $DataTypeFiltersA, $FromTreeB, $TreeChoice, $ShowBrosB, $ExclBrosB;
  $pHdg = '<p class=c>';
  if ($FilterByTypeB) {
    $list = '';
    foreach ($DataTypeFiltersA as $k => $v)
      if ($v) $list  .= ', ' . ElementTypeToStr($k);
    $pHdg .= 'Concrete tree elements filtered by Data Type to include only those of type' . (strlen($list)>10 ? 's ' : ' ') . substr($list, 2) . ' plus the rarely used ones.<br>';
  }
  if ($ExclBrosB)
    $pHdg .= 'Concrete tree elements used in Bros are excluded.<br>';

  if ($FromTreeB || $TreeChoice) {
    if ($FromTreeB)
      $pHdg .= "'From' Trees Only i.e. No 'To' trees.";
    if ($TreeChoice)
      $pHdg .= ($TreeChoice==1 ? 'Presentation' : 'Definition').' Trees Only.';
    $pHdg .= '<br>';
  }
  $pHdg .= 'Tree element Types are indicated by: [A] = Abstract, [C] = Concrete, prefixed by S if in the Bro skip list'
    . ($ShowBrosB ? ' or * if used in Bros' : '')
    . ',<br>followed by the El Id, and when applicable [H &amp; the Hy Id(s)] [T &amp; Tuple Id(s)]'
    . ($TypeB ? ' [Data Type &lt;Ns|Dr|Cr> &amp; Period]' : '') . ' {[&lt;Start Label|End Label|StartEnd>]}.</p>';
  if (is_numeric($input)) {
    # assume E.id
    echo "\n<h2 class=c>Lookup of $TxName Taxonomy Concept Element with Id $input</h2>\n$pHdg\n";
    if ($o = $DB->OptObjQuery("Select * From Elements where Id='$input'"))
      ElementInfo($o);
    else
      NoGo("Id $input not found");
  }else{
    # First search by name. Can have more than one match
    $res = $DB->ResQuery("Select * From Elements where name='$input'");
    if ($res->num_rows) {
      echo "\n<h2 class=c>Lookup of $TxName Taxonomy Concept Element(s) with Name '$input'</h2>\n<p>$pHdg\n";
      while ($o = $res->fetch_object())
        ElementInfo($o);
      $res->free();
    }else{
      # try search
      if ($SearchAB) # Search Abstract as well as Concrete
         $qry = "Select E.* From Elements E Join Text T on T.Id=E.StdLabelTxtId Where name like '%$input%' or Text like '%$input%'";
      else # only concrete
         $qry = "Select E.* From Elements E Join Text T on T.Id=E.StdLabelTxtId Where E.abstract is null and (name like '%$input%' or Text like '%$input%')";
      $res = $DB->ResQuery($qry);
      if ($res->num_rows) {
        echo "\n<h2 class=c>Lookup of $TxName Taxonomy " . ($SearchAB ? '' : 'Concrete ') . "Element(s) with Search String '$input'</h2>\n$pHdg\n<p class=c>" .
        $res->num_rows . ' Elements were found:';
        $elsA = [];
        while ($o = $res->fetch_object()) {
          echo " $o->Id";
          $elsA[] = $o;
        }
        $res->free();
        echo "</p>\n";
        foreach ($elsA as $o)
          ElementInfo($o);
      }else
        NoGo("No element found for '$input'.");
    }
  }
}

function ElementInfo($o) {
  global $DB, $NamespacesTxA, $TargetId, $FromTreeB, $TxElementsSkippedA, $StartEndTxIdsGA;
  $elId = (int)$o->Id;
  $name = $o->name;
  $substGroupN = (int)$o->SubstGroupN;
  if (!($ns = $NamespacesTxA[(int)$o->NsId][0])) NoGo("Element $elId with Name $name exists but has no namespace prefix so no info on it is available here");
  if (!$o->TypeN && $substGroupN!=TSG_Tuple) return NoGo("Element $elId with Name $name exists but is not a concept (it has no type) so no info on it is available here");
  # Element
  $abstract = (int)$o->abstract;
  echo "<table class='mc ElementsInfo'>\n<tr class='b bg0'><td colspan=2>Properties</td></tr>
<tr class=b><td>Property</td><td>Value</td></tr>
<tr><td>Elements.Id</td><td>$elId</td></tr>
<tr><td>Name</td><td>$o->name</td></tr>
<tr><td>Type</td><td>",($abstract ? 'Abstract' : 'Concrete'), "</td></tr>
<tr><td>Substitution Group</td><td>", SubstGroupToStr($substGroupN), '</td></tr>
';
if (!$abstract) {
  if (in_array($elId, $TxElementsSkippedA))
    echo '<tr><td>Skipped</td><td>This element is skipped for Bro use</td></tr>
';
  $hypercubes = HypercubesFromList($o->Hypercubes);
  if ($substGroupN != TSG_Tuple) {
    echo '<tr><td>Data Type</td><td>', ElementTypeToStr($o->TypeN), '</td></tr>
';
    if ($o->TypeN == TET_Money)
      echo '<tr><td>Sign</td><td>', SignToStr($o->SignN), '</td></tr>
';
    echo '<tr><td>Period</td><td>',  PeriodTypeToStr($o->PeriodN), in_array($elId, $StartEndTxIdsGA) ? ' with StartEnd Period use available' : '', "</td></tr>
<tr><td>Hypercube(s)<td>$hypercubes</td></tr>
";
  }
}
# Tuples. Can have tuples for abstract elements
# Similar to fn BuildTuplesList() but repeated here to exclude the HyId narrowing and to include the label(s). Can't use structs 'cos they are only for tuples used with Bros.
$res = $DB->ResQuery("Select TupId,TupTxId,Text From TuplePairs T Join Elements E on E.Id=T.TupTxId Join Text X on X.Id=E.StdLabelTxtId Where MemTxId=$elId Order by TupId");
$tuples = '';
if ($res->num_rows) {
  while ($t = $res->fetch_object())
    $tuples .= ", $t->TupId $t->TupTxId $t->Text";
  $tuples = substr($tuples, 2);
}
$res->free();
echo "<tr><td>Member of Tuple(s)</td><td>$tuples</td></tr>
";
if (!$abstract) {
  if ($substGroupN != TSG_Tuple)
    echo "<tr><td>Namespace</td><td>$ns ({$NamespacesTxA[(int)$o->NsId][1]})</td></tr>
<tr><td>Tag</td><td>$ns:$name</td></tr>
<tr><td>Nillable</td><td>",BoolToStr($o->nillable), '</td></tr>
';
}

  # Labels 02.10.12 Group by TextId added re the 5729 Country Dimension having a Verbose Label defined by DPL with a different LinkBaseId from the Standard Label => 4 results here rather than 2.
  echo "<tr class='b bg0'><td colspan=2>Labels</td></tr>
<tr class=b><td>Type</td><td>Label</td></tr>
";
 #$res = $DB->ResQuery("Select L.RoleId,T.Text from Arcs A Join Labels L on L.LabelId=A.ToId Join Text T on T.Id=L.TextId Where A.TypeN=3 And A.FromId=$elId Group by TextId Order by L.RoleId");
  $res = $DB->ResQuery("Select D.RoleId,T.Text from Doc D Join Text T on T.Id=D.TextId Where D.ElId=$elId And D.TypeN=3 Order by D.RoleId");
  if ($res->num_rows) {
    while ($o = $res->fetch_object())
      echo '<tr><td>', Role($o->RoleId), "</td><td>$o->Text</td></tr>\n";
  }else
    echo '<td colspan="2">None</td></tr>';
  $res->free();

  # References
  echo "<tr class='b bg0'><td colspan=2>References</td></tr>
<tr class=b><td>Type</td><td>Reference</td></tr>
";
 #$res = $DB->ResQuery("Select F.RoleId,T.Text from Arcs A Join `References` F on F.LabelId=A.ToId Join Text T on T.Id=F.RefsJsonId Where A.TypeN=4 And A.FromId=$elId");
  $res = $DB->ResQuery("Select D.RoleId,T.Text from Doc D Join Text T on T.Id=D.TextId Where D.ElId=$elId And D.TypeN=4");
  if ($res->num_rows) {
    while ($o = $res->fetch_object()) {
      $r = '';
      foreach (json_decode($o->Text) as $a => $v)
        $r .= "<br><span>$a</span>$v";
      echo '<tr><td class=mid>', Role($o->RoleId), '</td><td class=Ref>', substr($r, 4), "</td></tr>\n";
    }
  }else
    echo '<td colspan="2">None</td></tr>';
  $res->free();

  # Trees
  echo "<tr class='b bg0'><td colspan=2>Trees</td></tr>
<tr class=b><td>Type</td><td>Tree</td></tr>
";
  $TargetId = $elId; # Global for use by TreeElement()
  if (!$FromTreeB)
    ToTrees($elId);
  FromTrees($elId);
  echo "</table>\n";
}

function ToTrees($toId) {
  global $DB, $TargetId, $ToArcrolesA, $TreeChoice, $StartEndTxIdsGA, $HyId, $ExpandOnlyOwnToTreeB; # Target = $toId
  $ToArcrolesA = []; # to record arcs used by ToTrees() to exclude them from FromTrees() processing
  # Fetch arcs to $fromId for all arcroles.
  # 07.11.11 Added Distinct re duplicates e.g. for 4062. But note comments below vs Distinct and Group by.
  switch ($TreeChoice) { # All, Pres, Def
    case 0: $res = $DB->ResQuery("Select Distinct ArcroleId,FromId,PRoleId,TargetRoleId from Arcs Where ArcroleId In(1,2,3,4,5,6,7) and ToId=$toId Order by ArcroleId,PRoleId,TargetRoleId,ArcOrder"); break;
    case 1: $res = $DB->ResQuery("Select Distinct ArcroleId,FromId,PRoleId,TargetRoleId from Arcs Where ArcroleId=1 and ToId=$toId Order by PRoleId,TargetRoleId,ArcOrder"); break;
    case 2: $res = $DB->ResQuery("Select Distinct ArcroleId,FromId,PRoleId,TargetRoleId from Arcs Where ArcroleId In(2,3,4,5,6,7) and ToId=$toId Order by ArcroleId,PRoleId,TargetRoleId,ArcOrder"); break;
  }
  if ($res->num_rows) {
    $rn = $HyId = 0;
    $prevPRoleId = $pArcroleId = 0;
    while ($o = $res->fetch_object()) {
      #echo "T Arc FromId $o->FromId => $toId, ArcroleId $o->ArcroleId, PRoleId $o->PRoleId rn=$rn of $res->num_rows<br>";
      $arcroleId = (int)$o->ArcroleId;
      $ToArcrolesA[$arcroleId] = 1;
      if ($arcroleId != $pArcroleId) {
        OutputAbstractElements();
        echo ($rn ? "</td></tr>\n" : ''), "<tr><td class=top>", ArcroleIdToStr($pArcroleId = $arcroleId), '</td><td>';
        ++$rn;
        $prevPRoleId = $n = 0;
      }
      $id       = (int)$o->FromId;
      $pRoleId  = (int)$o->PRoleId;
      if ($pRoleId != $prevPRoleId) {
        OutputAbstractElements();
        echo ($n? '<br>' : ''), '<b>', Role($pRoleId), '</b><br>';
        $HyId = $pRoleId >= TR_FirstHypercubeId ? $pRoleId - TR_FirstHypercubeId + 1 : 0;
      }
      $prevPRoleId = $pRoleId;
      $nt = 1; # number of trees
      $trees = [[$id]]; # trees[n][arrays of element Id]
      $lasti = [0];     # last index (number of nodes -1) per tree
      $doneB = [false]; # set when tree is finished
      $anyB  = true;
      while($anyB) {
        $anyB = false;
        for ($ti=0; $ti<$nt; $ti++) {
          if (!$doneB[$ti]) {
            $id = $trees[$ti][$lasti[$ti]];
           # Distinct did not avoid a duplicate in the case of 5121 whereas Group by ToId did. Order and Priority are different tho not in the select list?
           # 25.04.13 But using Group by caused loss of a wanted branch in the 4788 case. Whereas all was as wanted with the Group by removed. And this made no difference to 5121. ??
           #$r2 = $DB->ResQuery("Select Distinct FromId from Arcs Where ArcroleId=$arcroleId and ToId=$id and PRoleId=$pRoleId");# Order by ArcOrder");
           #$r2 = $DB->ResQuery("Select FromId from Arcs Where ArcroleId=$arcroleId and ToId=$id and PRoleId=$pRoleId Group by ToId");# Order by ArcOrder");
            $r2 = $DB->ResQuery("Select FromId from Arcs Where ArcroleId=$arcroleId and ToId=$id and PRoleId=$pRoleId");
            if ($r2->num_rows) {
              $anyB = true;
              $a = $r2->fetch_object(); # for the $ti tree
             #$rn2 = 1; # tmp
             #echo "T2 Arc $a->Id, FromId $a->FromId => ToId $id, ArcroleId $arcroleId, PRoleId $pRoleId, $rn2 of $r2->num_rows, nt=$nt<br>";
              $fromTi = $a->FromId;
              if ($r2->num_rows > 1) {
                while ($a = $r2->fetch_object()) {
                  $trees[] = $trees[$ti];
                  $doneB[] = false;
                  $trees[$nt][] = $a->FromId;
                  $lasti[$nt] = $lasti[$ti]+1;
                  $nt++;
                 #$rn2++; # tmp
                 #$echo "T2 2 Arc $a->Id, FromId $a->FromId => ToId $id, ArcroleId $arcroleId, PRoleId $pRoleId, $rn2 of $r2->num_rows, nt=$nt<br>"; # tmp
                }
              }
              $trees[$ti][] = $fromTi;
              $lasti[$ti]++;
            }else
              $doneB[$ti] = true;
            $r2->free();
          }
        }
      }
      # Now backwards thru the trees arrays
      for ($ti=0; $ti<$nt; $ti++) {
        $inset = $p = 10; # p just to declare it
        if ($ti) $p = $lasti[$ti-1]; # previous
        $startedB = false;
        for ($i = $lasti[$ti]; $i>=0; $i--,$p-- ) {
          $id = $trees[$ti][$i];
          if ($startedB || !$ti || $p<0 || $id != $trees[$ti-1][$p]) {
            TreeElement($id, $inset); # No PrefLabelRoleId as this is a FromId
            $startedB = true;
          }
          $inset += 12;
        }
        # from Group by PrefLabelRoleId
       #echo "from 348 Select ToId,PrefLabelRoleId,TargetRoleId from Arcs Where ArcroleId=$arcroleId and PRoleId=$pRoleId and FromId=$id Order by TargetRoleId,ArcOrder<br>";
        $r2 = $DB->ResQuery("Select ToId,PrefLabelRoleId,TargetRoleId from Arcs Where ArcroleId=$arcroleId and PRoleId=$pRoleId and FromId=$id Order by TargetRoleId,ArcOrder");
        if ($r2->num_rows) {
          $startEndElementsA = [];
          while ($a = $r2->fetch_object()) {
            $id = (int)$a->ToId;
            if ($arcroleId > TLT_Presentation && isset($startEndElementsA[$id]))
              continue;
            if (in_array($id, $StartEndTxIdsGA))
              $startEndElementsA[$id] = 1;
            TreeElement($id, $inset, $a->PrefLabelRoleId, $a->TargetRoleId); # With PrefLabelRoleId as this is a ToId
            if (!$ExpandOnlyOwnToTreeB || $id === $TargetId)
              # Continue for target element if there are from arcs
              # echo "FromTrees2($id, $arcroleId, $pRoleId, $inset) call at 367<br>";
              FromTrees2($id, $arcroleId, $pRoleId, $inset);
          }
        }else
          die("Die - Target element not found in final To tree list as expected");
        $r2->free();
      }
      $n++;
    }
    OutputAbstractElements();
    echo "</td></tr>\n";
  }
  $res->free();
}

function FromTrees($fromId) {
  global $DB, $ToArcrolesA, $TreeChoice, $StartEndTxIdsGA, $HyId;
  # Fetch arcs from $fromId for all arcroles not already processed via ToTrees()
  $arcrolesA = [];
  switch ($TreeChoice) {
    case 0: $i=TA_ParentChild;  $end = TA_HypercubeDim; break; # All
    case 1: $i=TA_ParentChild;  $end = TA_ParentChild;  break; # Pres
    case 2: $i=TA_EssenceAlias; $end = TA_HypercubeDim; break; # Def
  }
  for ( ; $i <= $end; $i++)
    if (!isset($ToArcrolesA[$i]))
      $arcrolesA[] = $i;
  if (!count($arcrolesA)) return;
  $res = $DB->ResQuery("Select ArcroleId,ToId,PRoleId,PrefLabelRoleId,TargetRoleId from Arcs Where ArcroleId In(" .
                         implode(',', $arcrolesA) . ") and FromId=$fromId Order by ArcroleId,PRoleId,TargetRoleId,ArcOrder");
  if ($res->num_rows) {
    $rn = $HyId = 0;
    $prevPRoleId = $pArcroleId = 0;
    $startEndElementsA = [];
    while ($o = $res->fetch_object()) {
      #echo "F Arc $o->Id, ToId $o->ToId, ArcroleId $o->ArcroleId";
      $arcroleId = (int)$o->ArcroleId;
      if ($arcroleId != $pArcroleId) {
        OutputAbstractElements();
        echo ($rn ? "</td></tr>\n" : ''), "<tr><td class=top>", ArcroleIdToStr($pArcroleId = $arcroleId), '</td><td>';
        ++$rn;
        $prevPRoleId = $n = 0;
        $startEndElementsA = [];
      }
      $id      = (int)$o->ToId;
      $pRoleId = (int)$o->PRoleId;
      if ($pRoleId != $prevPRoleId) {
        OutputAbstractElements();
        echo ($n? '<br>' : ''), '<p><b>', Role($pRoleId), '</b></p>';
        $HyId = $pRoleId >= TR_FirstHypercubeId ? $pRoleId - TR_FirstHypercubeId + 1 : 0;
        TreeElement($fromId, 10); # No PrefLabelRoleId as this is a FromId
      }
      $prevPRoleId = $pRoleId;
      if ($arcroleId > TLT_Presentation && isset($startEndElementsA[$id]))
        continue;
      if (in_array($id, $StartEndTxIdsGA))
        $startEndElementsA[$id] = 1;
      TreeElement($id, 22, $o->PrefLabelRoleId, $o->TargetRoleId); # With PrefLabelRoleId as this is a ToId
      FromTrees2($id, $arcroleId, $pRoleId, 24);
      $n++;
    }
    OutputAbstractElements();
    echo "</td></tr>\n";
  }
  $res->free();
}

function FromTrees2($fromId, $arcroleId, $pRoleId, $inset) {
  global $DB, $StartEndTxIdsGA;
  $res = $DB->ResQuery("Select ToId,PrefLabelRoleId from Arcs Where ArcroleId=$arcroleId and FromId=$fromId and PRoleId=$pRoleId Order by ArcroleId,TargetRoleId,ArcOrder");
  if ($res->num_rows) {
    $inset += 12;
    $startEndElementsA = [];
    while ($a = $res->fetch_object()) {
      $id = (int)$a->ToId;
      #echo "FT2  Arc $a->Id fromId $fromId ToId $id";
      if ($arcroleId > TLT_Presentation && isset($startEndElementsA[$id]))
        continue;
      if (in_array($id, $StartEndTxIdsGA))
        $startEndElementsA[$id] = 1;
      TreeElement($id, $inset, $a->PrefLabelRoleId); # With PrefLabelRoleId as this is a ToId
      FromTrees2($id, $arcroleId, $pRoleId, $inset);
    }
  }
  $res->free();
}

# $prefLabelRoleId only applies in the case of ToId arcs intended to show Period Start and Period End where applicable
function TreeElement($id, $inset, $prefLabelRoleId=0, $targetRoleId = 0) {
  global $DB, $TypeB, $NameB, $AbstractElsA, $NumAbstract, $FilterByTypeB, $DataTypeFiltersA, $ShowBrosB, $ExclBrosB, $ElementsUsedByBrosA,$TxElementsSkippedA, $StartEndTxIdsGA, $HyId, $TupTxIdToTupIdA;
  static $prevInset = 0;
  if ($NameB) # Show Names rather than Standard Labels in Trees
    $qry = "Select TypeN,abstract,SubstGroupN,PeriodN,SignN,Hypercubes,name from Elements Where Id=$id";
  else
    $qry = "Select E.TypeN,E.abstract,E.SubstGroupN,E.PeriodN,SignN,Hypercubes,T.Text name from Elements E Join Text T on T.Id=E.StdLabelTxtId Where E.Id=$id";
  if (!$o = $DB->OptObjQuery($qry))
    die("Die - element $id not found in TreeElement()");
  if ($FilterByTypeB && $inset < $prevInset) {
    # When filtering by type, on moving back up discard any abstract elements at a lower level which haven't been output
    #  as a result of a concrete element coming along or a forced output
    #echo "Moving up NumAbstract = $NumAbstract -> ";
    foreach ($AbstractElsA as $i => $aA)
      if ($aA[1] > $inset) {
        unset($AbstractElsA[$i]);
        $NumAbstract--;
      }
    #echo "$NumAbstract<br>";
  }
  # Tuples. Here because can apply to abstract elements.
  if ($tupIds = BuildTuplesList($id, $HyId))
    $tupIds = " [T $tupIds]";
  $prevInset = $inset;
  if ($o->abstract) {
    # Abstract
    $AbstractElsA[] = array($id, $inset, "[A] $id$tupIds $o->name", $targetRoleId);
    $NumAbstract++;
    #echo "$NumAbstract<br>";
  }else{
    # Concrete
    if ($FilterByTypeB && isset($DataTypeFiltersA[$typeN = (int)$o->TypeN]) && !$DataTypeFiltersA[$typeN]) return;
   #if ($ExclBrosB && in_array($id, $ElementsUsedByBrosA)) return;
    if ($ExclBrosB && isset($ElementsUsedByBrosA[$id])) return;
    OutputAbstractElements(true); # true = force output
    if (in_array($id, $TxElementsSkippedA))
      $b = 'S ';
    else if ($ShowBrosB)
     #$b = (in_array($id, $ElementsUsedByBrosA) ? '* ' : '&nbsp; ');
      $b = (isset($ElementsUsedByBrosA[$id]) ? '* ' : '&nbsp; ');
    else
      $b='';
    if ($TypeB) {
      if ($o->SubstGroupN == TSG_Tuple)
        $typeS = " [Tuple $TupTxIdToTupIdA[$id]] ";
      else{
        if (($typeN = (int)$o->TypeN) == TET_Money)
          $typeS = ' [' . ElementTypeToStr($typeN) . ($o->SignN ? ($o->SignN==1? ' Dr ' : ' Cr ') : ' Ns ') . PeriodTypeToStr($o->PeriodN) . '] ';
        else
          $typeS = ' [' . ElementTypeToStr($typeN) . ' ' . PeriodTypeToStr($o->PeriodN) . '] ';
      }
    }else
      $typeS = '';
    if ($prefLabelRoleId == TR_PSL || $prefLabelRoleId == TR_PEL)
     #$startEnd = ' [' . str_replace(' Label', '', Role($prefLabelRoleId)) . '] '; # To show Period Start and Period End on To elements where set
      $startEnd = $prefLabelRoleId == TR_PSL ? ' [Start Label] ' : ' [End Label] '; # To show Start Period and End Period on To elements where set via PrefLabelRoleId - presentation trees only
    else
      $startEnd = in_array($id, $StartEndTxIdsGA) ? ' [StartEnd] ' : ''; # To show StartEnd possible for this element
    $hysS = $o->Hypercubes ? (' [H ' . ChrListToCsList($o->Hypercubes) . ']') : '';
    $txt = "{$b}[C] $id$hysS$tupIds$typeS$startEnd $o->name";
    OutputTreeElement($id, $inset, $txt, $targetRoleId);
  }
}

function OutputTreeElement($id, $inset, $txt, $targetRoleId) {
  global $TargetId, $SpacesB;
  if ($targetRoleId)
    $txt .= ' (' . Role($targetRoleId) . ')';
  if ($id == $TargetId)
    $txt = '<span class="b navy">' . $txt . '</span>';
  else if ($id > ElId_LastGAAP)
    $txt = '<span class="navy">' . $txt . '</span>';

  if ($SpacesB) {
    $pinset = 10;
    while ($pinset <= $inset) {
      echo '&nbsp;&nbsp;';
      $pinset += 12;
    }
    echo "$txt<br>\n";
  }else
    echo "<p style='padding-left:{$inset}px'>$txt</p>\n";
}

function OutputAbstractElements($forceB = false) {
  global $AbstractElsA, $NumAbstract, $FilterByTypeB;
  #echo "OutputAbstractElements() NumAbstract=$NumAbstract, count=",count($AbstractElsA), '<br>';
  if ($NumAbstract) {
    if ($forceB || !$FilterByTypeB)
      foreach ($AbstractElsA as $aA)
        OutputTreeElement($aA[0], $aA[1], $aA[2], $aA[3]);
    $AbstractElsA = [];
    $NumAbstract = 0;
  }
}

function NoGo($msg) {
  echo "\n<p class='c b'>$msg</p>\n";
}

function Form($input) {
  global $TypeB, $FromTreeB, $ExpandOnlyOwnToTreeB, $TreeChoice, $NameB, $SpacesB, $SearchAB, $DataTypeFiltersA, $ShowBrosB, $ExclBrosB;
  $typeChecked     = ($TypeB    ? ' checked' : '');
  $expandOwnChecked= ($ExpandOnlyOwnToTreeB? ' checked' : '');
  $fromTreeChecked = ($FromTreeB? ' checked' : '');
  $TreeChoice0Checked = ($TreeChoice==0 ? ' checked' : '');
  $TreeChoice1Checked = ($TreeChoice==1 ? ' checked' : '');
  $TreeChoice2Checked = ($TreeChoice==2 ? ' checked' : '');
  $nameChecked     = ($NameB    ? ' checked' : '');
  $spacesChecked   = ($SpacesB  ? ' checked' : '');
  $searchAChecked  = ($SearchAB ? ' checked' : '');
  $moneyChecked    = ($DataTypeFiltersA[TET_Money]    ? ' checked' : '');
  $stringChecked   = ($DataTypeFiltersA[TET_String]   ? ' checked' : '');
  $boolChecked     = ($DataTypeFiltersA[TET_Boolean]  ? ' checked' : '');
  $dateChecked     = ($DataTypeFiltersA[TET_Date]     ? ' checked' : '');
  $decimalChecked  = ($DataTypeFiltersA[TET_Decimal]  ? ' checked' : '');
  $percentChecked  = ($DataTypeFiltersA[TET_Percent]  ? ' checked' : '');
  $shareChecked    = ($DataTypeFiltersA[TET_Share]    ? ' checked' : '');
  $perShareChecked = ($DataTypeFiltersA[TET_PerShare] ? ' checked' : '');
  $showBrosChecked = ($ShowBrosB ? ' checked' : '');
  $exclBrosChecked = ($ExclBrosB ? ' checked' : '');

echo <<< FORM
<div class=mc style=width:1000px>
<p class=mb0>Enter a Concept Name, an Elements.Id number, a search string (doubly quoted to include a comma - name and standard label are searched), or a comma separated list of any of these e.g. EntityCurrentLegalOrRegisteredName or 5729 or 2414,4501,5409 or Dividends due or 2414,"Deferred tax, current asset" :</p>
<form method=post>
<table class=itran>
<tr><td class=r>Name, Elements.Id, Search string, or CS List:</td><td><input type=text name=Input size=75 maxlength=155 value='$input'></td></tr>
<tr><td class=r>Search Abstract Elements as well as Concrete Ones:</td><td><input class=radio type=checkbox name=SearchA value=1$searchAChecked> <span class=s>Applies when an entered value does not give an Id etc match and is used as a search string.</span></td></tr>
<tr><td class=r>Include Element Data Type and Duration in Trees:</td><td><input class=radio type=checkbox name=Type value=1$typeChecked></td></tr>
<tr><td class='r top'>Show only Concrete Elements of Checked Data Type(s) in Trees:</td><td><input class=radio type=checkbox name=Money value=1$moneyChecked> Money<input class=radio type=checkbox name='String' value=1$stringChecked> String<input class=radio type=checkbox name='Bool' value=1$boolChecked> Boolean<input class=radio type=checkbox name='Date' value=1$dateChecked> Date<input class=radio type=checkbox name='Decimal' value=1$decimalChecked> Decimal<input class=radio type=checkbox name='Percent' value=1$percentChecked> Percent<input class=radio type=checkbox name='Share' value=1$shareChecked> Share<input class=radio type=checkbox name='PerShare' value=1$perShareChecked> PerShare<br><span class=s>None checked means all. Rarely used types not listed above always appear if encountered.</span></td></tr>
<tr><td class=r>Expand only own branch not sibling ones in 'To' Trees:</td><td><input class=radio type=checkbox name=ExpandOnlyOwnToTree value=1$expandOwnChecked></td></tr>
<tr><td class=r>Show only 'From' Trees i.e. Omit 'To' Trees in following choice:</td><td><input class=radio type=checkbox name=FromTree value=1$fromTreeChecked></td></tr>
<tr><td class=r>All Trees:</td><td><input class=radio type=radio name=TreeChoice value=0$TreeChoice0Checked></td></tr>
<tr><td class=r>Only Presentation Trees:</td><td><input class=radio type=radio name=TreeChoice value=1$TreeChoice1Checked></td></tr>
<tr><td class=r>Only Definition Trees:</td><td><input class=radio type=radio name=TreeChoice value=2$TreeChoice2Checked></td></tr>
<tr><td class=r>Show Names rather than Standard Labels in Trees:</td><td><input class=radio type=checkbox name=Name value=1$nameChecked></td></tr>
<tr><td class=r>Use Spaces rather than CSS for indenting Trees:</td><td><input class=radio type=checkbox name=Spaces value=1$spacesChecked></td></tr>
<tr><td class=r>Mark concrete elements used in Bros with an * in Trees:</td><td><input class=radio type=checkbox name=ShowBros value=1$showBrosChecked></td></tr>
<tr><td class=r>Exclude concrete elements used in Bros from Trees:</td><td><input class=radio type=checkbox name=ExclBros value=1$exclBrosChecked></td></tr>
</table>
<p class='c mb0'><button class='c on m10'>Lookup</button></p>
</form>
</div>
FORM;
Footer();
exit;
}
