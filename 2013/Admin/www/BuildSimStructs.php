<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/BuildSimStructs.php

Build the SIM Structs

History:
18.03.13 djh Started
03.07.13 B R L -> SIM
19.07.13 I t e m -> Member

ToDo
====

Folios
======
$FolioPropsA  [FolioId => Chr list of Properties]
$FolioNamesA  [FolioId => Folio Name]

Properties and Property Members
===============================
$PropNamesA  [PropId => PropName]
$PMemNamesA  [PMemId => PMemName]
$PMemLabelsA [PMemId => PMemLabel]
$PMemsA      [PMemId => [PropId, Bits, MType, ETypeList, MuxList, SumList]
$PMemMapA    [PropName.PMemName => PMemId]
$PMemSumTreesA        3 dimensional array of [PMemId, [target PMemId, [PMemIds to sum]]] - used only by SummingTrees.php and here to generate $PMemTargetsA
$PMemTargetsA         [PMemId => [target PMemIds]] excludes the Restated PMems
$RestatedPMemTargetsA Version of $PMemTargetsA covering just the Restated PMems


Zones  These are built here but the process could be moved elsewhere as these structs only change if the Zones table is edited.
=====
$ZonesA         array of Zones Id => [ZI_Ref, ZI_SignN, ZI_AllowDims] Descr is not stored in the array
$ZoneRefsA      array          Ref => Id

Files
=====
Folios.inc        $FolioPropsA  $FolioNamesA
PropNamesA.inc    $PropNamesA
PMemNamesA.inc    $PMemNamesA
PMemLabelsA.inc   $PMemLabelsA
PMemsA.inc        $PMemsA
PMemMapA.inc      $PMemMapA
PMemSumTreesA.inc $PMemSumTreesA
PMemTargetsA.inc  $PMemTargetsA  $RestatedPMemTargetsA
Zones.inc         $ZonesA  $ZoneRefsA


*/

require 'BaseSIM.inc';

Head('Build SIM Structs');

echo "<h2 class=c>Build the SIM Structs</h2>
";

# Folios
# ------
$FolioPropsS = "\$FolioPropsA=[0,\n";
$FolioNamesS = "\$FolioNamesA=[0,\n";

$res = $DB->ResQuery("Select * From Folios Order by Id");
while ($fO = $res->fetch_object()) {
  $FolioPropsS .= SQ.addslashes($fO->Props).SQ.COM.NL;
  $FolioNamesS .= "'$fO->Name',\n";
}
FinishArrayString($FolioPropsS);
FinishArrayString($FolioNamesS);

file_put_contents(Com_Str.'Folios.inc', '<?php
'. $FolioPropsS
 . $FolioNamesS);


# Properties
# ----------
$PropNamesAS = "\$PropNamesA=[0,\n";
$PMemNamesAS = "\$PMemNamesA=[0,\n";
$PMemLabelsAS= "\$PMemLabelsA=[0,\n";
$PMemsAS     = "\$PMemsA=[0,\n";
$PMemMapAS   = "\$PMemMapA=[\n";

$pMemSumListsA = [];
$pMemKey = '';
$res = $DB->ResQuery("Select Id,Name From Properties Order by Id");
while ($pO = $res->fetch_object()) {
  $propId = (int)$pO->Id;
  $propName = $pO->Name;
  # PropNamesA
  $PropNamesAS .= "'$propName',\n";
  $re2 = $DB->ResQuery("Select * From PMembers Where PropId=$propId Order by Id");
  $piOsA = [];
  while ($piO = $re2->fetch_object())
    $piOsA[] = $piO;
  $re2->free();
  foreach ($piOsA as $i => $piO) {
    $pMemId   = (int)$piO->Id;
    $bits     = (int)$piO->Bits;
    $pMemName = $piO->Name; # This includes the PropName. prefix
    $pMemName = rtrim($pMemName, '#'); # strip trailing #
    $pMemLabel= $piO->Label;
    if ($pMemId === 999) $pMemKey = '999=>';
    # PMemNamesA and PMemLabelsA
    $PMemNamesAS  .= "$pMemKey'$pMemName',\n";
    $PMemLabelsAS .= InStr(SQ, $pMemLabel) ? "$pMemKey\"$pMemLabel\",\n" : "$pMemKey'$pMemLabel',\n";
    # PMemsA    [PMemId => [PropId, Bits, MuxListA, AddListA, ReqListA, RefTableN]]
    $pMemSumListsA[$pMemId] = ListStr($piOsA, $piO->SumList, $i, $pMemId, true); # true = sumListSkipRoKids
    $muxList = ListStr($piOsA, $piO->MuxList, $i, $pMemId);
    $addList = ListStr($piOsA, $piO->AddList, $i, $pMemId);
    $reqList = ListStr($piOsA, $piO->ReqList, $i, $pMemId);
    # PMemI_RefTableN  # Table enum T_B_* (T_B_People, T_B_Entities, T_B_ERefs, T_Sim_*) if the PMem is a Type D, I, Ei, Ee, R, C, U, X, L which have a # Ref, o'wise 0 which means pMem cannot have a # Ref
    if (($bits & PMemB_D) || ($bits & PMemB_H) || ($bits & PMemB_Sim)) {
      switch ($propId) {
        case PropId_Entity:
        case PropId_Superior:
        case PropId_Subord:
        case PropId_TPA:       $adoTableN = T_B_Entities; break;
        case PropId_Person:
        case PropId_Officer:   $adoTableN = T_B_People;   break;
        case PropId_Address:
        case PropId_Contact:   $adoTableN = T_B_ERefs;    break; # djh?? Table for these not ERefs
        case PropId_Regions:   $adoTableN = T_Sim_Regions   ; break;
        case PropId_Countries: $adoTableN = T_Sim_Countries ; break;
        case PropId_Currencies:$adoTableN = T_Sim_Currencies; break;
        case PropId_Exchanges: $adoTableN = T_Sim_Exchanges ; break;
        case PropId_Languages: $adoTableN = T_Sim_Languages ; break;
        case PropId_Industries:$adoTableN = T_Sim_Industries; break;
        default: die("PMem $pMemId Type D or I # Ref Table undefined");
      }
    }else if (($bits & PMemB_Ei) || ($bits & PMemB_Ee))
      $adoTableN = T_B_ERefs;
    else
      $adoTableN = 0;
    $PMemsAS .= sprintf("%s[$propId,$bits,%s,%s,%s,%d],\n", $pMemKey, $muxList, $addList, $reqList, $adoTableN);
    # PMemMapA
    $pMemName = rtrim($pMemName, '.#'); # strip trailing . as well i.e. .# gone
    $PMemMapAS .= "'$pMemName'=>$pMemId,\n";
  }
}
$res->free();
FinishArrayString($PropNamesAS);
FinishArrayString($PMemNamesAS);
FinishArrayString($PMemLabelsAS);
FinishArrayString($PMemsAS);
FinishArrayString($PMemMapAS);

eval($PMemsAS); # to get $PMemsA for use here

# $PMemSumTreesA 3 dimensional array of [PMemId, [target PMemId, [PMemIds to sum]]]
$PMemSumTreesAS = '$PMemSumTreesA=[
';
$propId = 0;
foreach ($PMemsA as $pMemId => $pMemA) {
  if (!$pMemId) continue;
  if ($pMemA[PMemI_PropId] != $propId) {
    if ($propId) # Leave Restated in here re Summing trees report. Handle special case via $PMemTargetsA and $RestatedPMemTargetsA
      BuildPropPMemSumTree($PMemSumTreesAS, $propId, $thisPropPMemSumListsA);
    $propId = $pMemA[PMemI_PropId];
    $thisPropPMemSumListsA = [];
  }
  if ($sumList = $pMemSumListsA[$pMemId])
    $thisPropPMemSumListsA[$pMemId] = $sumList;
}
BuildPropPMemSumTree($PMemSumTreesAS, $propId, $thisPropPMemSumListsA);
FinishArrayString($PMemSumTreesAS);

eval($PMemSumTreesAS); # to get the actual $PMemSumTreesA for use below

# From $PMemSumTreesA  3 dimensional array of [PMemId, [target PMemId, [PMemIds to sum]]]
# Build
# $PMemTargetsA [PMemId => [target PMemIds]] to simplify PMem summing
# This code uses $PMemSumTreesA so that needs to have been built first.
$PMemTargetsA = []; # [PMemId => [target PMemIds]
foreach ($PMemSumTreesA as $propId => $treeA)  # [target PMemId, [PMemIds to sum]]
  if ($propId !== PropId_Restated) # Skip Restated
    foreach ($treeA as $tarPMemId => $pMemIdsA) # thru the targets
      foreach ($pMemIdsA as $pMemId)            # thru the PMems summing to the target
        $PMemTargetsA[$pMemId][] = $tarPMemId;
#DumpExport('PMemTargetsA',$PMemTargetsA);
ksort($PMemTargetsA);
#DumpExport('$PMemTargetsA', $PMemTargetsA);

# Now generate the more compact string version of $PMemTargetsA
$PMemTargetsAS = '$PMemTargetsA=[
';
foreach ($PMemTargetsA as $pMemId => $pMemIdsA) {
  $PMemTargetsAS .= "$pMemId=>[";
  foreach ($pMemIdsA as $pMemId)
    $PMemTargetsAS .= "$pMemId,";
  $PMemTargetsAS = substr($PMemTargetsAS,0, -1) . "],\n";
}
FinishArrayString($PMemTargetsAS);

# Restated PMems targets array tho this could be generated as for PMemTargetsA:
# $RestatedPMemTargetsA=[
# 75=>[74,73],
# 76=>[74,73],
# 77=>[73]];
$RestatedPMemTargetsAS = '$RestatedPMemTargetsA=[
' . PMemId_PyaAcctPolicyIncr  . '=>[' . PMemId_PyaPriorPeriodIncr . ',' . PMemId_PyaAmount . '],
' . PMemId_PyaMaterialErrIncr . '=>[' . PMemId_PyaPriorPeriodIncr . ',' . PMemId_PyaAmount . '],
' . PMemId_PyaOriginalAmount  . '=>[' . PMemId_PyaAmount . ']];
';


file_put_contents(Com_Str.'PropNamesA.inc', '<?php
'. $PropNamesAS);
file_put_contents(Com_Str.'PMemNamesA.inc', '<?php
'. $PMemNamesAS);
file_put_contents(Com_Str.'PMemLabelsA.inc', '<?php
'. $PMemLabelsAS);
file_put_contents(Com_Str.'PMemsA.inc', '<?php
' . $PMemsAS);
file_put_contents(Com_Str.'PMemMapA.inc', '<?php
' . $PMemMapAS);
file_put_contents(Com_Str.'PMemSumTreesA.inc', '<?php
' . $PMemSumTreesAS);
file_put_contents(Com_Str.'PMemTargetsA.inc', '<?php
' . $PMemTargetsAS
  . $RestatedPMemTargetsAS);

# Zones
# -----
# $ZonesA         array of Zones  Id => [ZI_Ref, ZI_SignN, ZI_AllowDims] Descr is not stored in the array
# $ZoneRefsA      array          Ref => Id

$ZonesAS    = "\$ZonesA=[0,\n";
$ZoneRefsAS = "\$ZoneRefsA=[\n";
$res = $DB->ResQuery("Select * From Zones Order by Id");
while ($zO = $res->fetch_object()) {
  $id    = (int)$zO->Id;
  $signN = (int)$zO->SignN;
  $ref   =      $zO->Ref;
  # $ZonesA                            [ZI_Ref,ZI_SignN]
  $ZonesAS    .= str_replace("''", 0, "['$ref',$signN],\n"); # AllowPropDims 0 if not set
  # $ZoneRefsA
  $ZoneRefsAS .= "'$ref'=>$id,\n";
}
$res->free();
FinishArrayString($ZonesAS);
FinishArrayString($ZoneRefsAS);

file_put_contents(Com_Str.'Zones.inc', '<?php
' . $ZonesAS
  . $ZoneRefsAS);


Footer();

function ListStr($piOsA, $list, $i, $pMemId, $sumListSkipRoKids = 0) {
  #echo "ListStr list=$list, i=$i, pMemId=$pMemId, sumListSkipRoKids=$sumListSkipRoKids".BR;
  $listA = [];
  if (!$list)
    return 0;
  echo "PMemId $pMemId list $list:";
  foreach (explode(COM, $list) as $t) {
    if ($t === 'K') {
      # Kids
      $kids = 0;
      $level = (int)$piOsA[$i]->Level;
      $num = count($piOsA);
      echo " Kids:";
      for ($j=$i+1; $j < $num && (int)$piOsA[$j]->Level > $level; ++$j) {
        # For SumList case want to skip RO members as they will not have a posted value and so there is no need to sum them. They will have their own sum.
        # Members with Kids that are not RO but which have a MuxList should be included as they might or might not have a value depending on which member was posted to.
        if ($sumListSkipRoKids && ((int)$piOsA[$j]->Bits & PMemB_RO)) continue;
        $listA[] = (int)$piOsA[$j]->Id;
        echo SP,$piOsA[$j]->Id;
        ++$kids;
      }
      if (!$kids) die("No kids for PMemId $pMemId list $list");
    }else if (InStr('-', $t)) {
      # Range
      echo " Range:";
      $rangeA = explode('-', $t);
      if (count($rangeA) !== 2) die("Range count not 2 for $pMemId list $list");
      for ($id = (int)$rangeA[0];  $id <= (int)$rangeA[1]; ++$id) {
        $listA[] = $id;
        echo " $id";
      }
    }else{
      # single
      $listA[] = (int)$t;
      echo " $t";
    }
  }
  echo BR;
  return count($listA) ? '['.implode(COM, $listA).']' : die("no list");
}

# FinishArrayString
# =================
# Chop off the final , and \n then add the clos
#  - just the final \n if second last char is [ i.e. if nothing was added to the array
#  - the final , and \n if otherwise, assuming an array entry followed by ,\n
function FinishArrayString(&$arrayS) {
  $arrayS = (substr($arrayS,-2,1) === '[' ? substr($arrayS,0,-1) : substr($arrayS,0,-2)) . '
];
';
}

# Build PMem summing array string in pMemSumTreeAS for property $propId, with PMem SumLists in $thisPropPMemSumListsA and if anything, append it to $PMemSumTreesAS
# $PMemSumTreesA 3 dimensional array of [PMemId, [target PMemId, [PMemIds to sum]]]
function BuildPropPMemSumTree(&$PMemSumTreesAS, $propId, $thisPropPMemSumListsA) {
  if (!count($thisPropPMemSumListsA))
    return; # nothing to do here
  $pMemSumTreeAS = "$propId=>[";
  foreach ($thisPropPMemSumListsA as $pMemId => $sumList)
    $pMemSumTreeAS .= "$pMemId=>$sumList,\n";
  $PMemSumTreesAS .= substr($pMemSumTreeAS, 0, -2) . "],\n";
}


