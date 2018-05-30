<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/UK-IFRS-DPL/Tuples.php

Lists Tuples and Tuple Members

History:
30.06.13 Copied from UK-GAAP-DPL version

*/
require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsTx.inc';
require Com_Inc_Tx.'TxElementsSkipped.inc';
require Com_Str_Tx.'TupNamesA.inc';  # $TupNamesA
require Com_Str_Tx.'TupLabelsA.inc'; # $TupLabelsA

Head("Tuples: $TxName", true);

echo "<h2 class=c>$TxName Tuples</h2>
<p class=c>For the significance of the minOccurs and maxOccurs columns see section 13 \"Groupings / tuples - further information\" in \"UK Detailed Tagging Information Version 1.0, dated 1 May 2011\".<br>
The Different column shows Tuple or Abtract for those few Tuple Members which are themselves Tuples rather than Substitution Group Item, or which are Abstract rather than Concrete.</p>
<table class=mc>
<tr><td colspan=2 class=c>Tuple Use Code (TUC)</td></tr>
<tr><td>O</td><td>Optional once corresponding to Taxonomy minOccurs=0 and maxOccurs=1</td></tr>
<tr><td>M</td><td>Mandatory once if tuple used corresponding to Taxonomy minOccurs=1 and maxOccurs=1</td></tr>
<tr><td>U</td><td>Optional Unbounded corresponding to Taxonomy minOccurs=0 and maxOccurs=unbounded</td></tr>
</table>
<table class=mc>
";
$tupId = 0;
$tuplesA  =     # tupId   => [TupTxId, Members]
$membersA = []; # memTxId => [TuMeId, MLabel, abstract, SubstGroupN, TypeN, Ordr, TUCN]
#res = $DB->ResQuery('Select T.*,TE.name,TT.Text TText,MT.Text MText,ME.abstract,ME.SubstGroupN,ME.TypeN From TuplePairs T Join Elements TE on TE.Id=T.TupTxId Join Elements ME on ME.Id=T.MemTxId Join Text TT on TT.Id=TE.StdLabelTxtId Join Text MT on MT.Id=ME.StdLabelTxtId');
$res = $DB->ResQuery('Select T.*,MT.Text MText,ME.abstract,ME.SubstGroupN,ME.TypeN From TuplePairs T Join Elements ME on ME.Id=T.MemTxId Join Text MT on MT.Id=ME.StdLabelTxtId');
while ($o = $res->fetch_object()) {
  $thisTupId = (int)$o->TupId;
  $memTxId   = (int)$o->MemTxId;
  if ($thisTupId > $tupId) {
    if ($tupId) $tuplesA[$tupId] = ['TupTxId' => $tupTxId, 'Members' => $membersA];
    $tupId    = $thisTupId;
    $tupTxId  = (int)$o->TupTxId;
    $membersA = [];
  }
  $membersA[$memTxId] = ['TuMeId' => (int)$o->Id, 'MLabel' => $o->MText, 'abstract' => $o->abstract, 'SubstGroupN' => $o->SubstGroupN, 'TypeN' => $o->TypeN, 'Ordr' => (int)$o->Ordr, 'TUCN' => (int)$o->TUCN];
}
if ($tupId) $tuplesA[$tupId] = ['TupTxId' => $tupTxId, 'Members' => $membersA];
$res->free();

$n =
$tuplesSkiped =
$tupleMembersSkipped = 0;

foreach ($tuplesA as $tupId => $tupleA) {
  if ($n <= 0) {
    $n = 25;
    #      Tuple             |                                           Tuple Member
    # Id | Tx Id | Label | * | TuMeId | Tx Id | Order | Type | Different | Other Tuples also Member Of | Label | Occurs Code
    echo "<tr class='b bg0 c'><td colspan=4>Tuple</td><td colspan=8>Tuple Member</tr>\n",
         "<tr class='b bg0 c'><td>Id</td><td>Tx Id</td><td class=l>Label / Short Name</td><td>S<br>k</td><td>TuMe<br>Id</td><td>Ord<br>er</td><td>Tx Id</td><td>Type</td><td>Diff<br>erent</td><td class=l>Other Tuples also<br>Member Of by Id / Tx Id</td><td class=l>Label</td><td>TUC</td></tr>\n";
  }
  $tupTxId  = $tupleA['TupTxId'];
  $membersA = $tupleA['Members'];
  $tupName  = $TupNamesA[$tupId];
  $tupLabel = $TupLabelsA[$tupId].' [grouping]'; # $TupLabelsA labels have had ' [grouping]' stipped off
  $rows = count($membersA);
  if (in_array($tupTxId, $TxElementsSkippedA)) {
    $skipped='*';
    ++$tuplesSkiped;
    $tupleMembersSkipped+=$rows;
  }else
    $skipped='';
  echo "<tr><td rowspan=$rows class='c top'>$tupId</td><td rowspan=$rows class='c top'>$tupTxId</td><td rowspan=$rows class=top>$tupLabel<br>$tupName</td><td rowspan=$rows class=top>$skipped</td>";
  # Members
  $firstB = true;
  foreach ($membersA as $memTxId => $memberA) {
    extract($memberA); # -> $TuMeId, $MLabel, $abstract, $SubstGroupN, $Ordr, $TUCN
    $type = '';
    if ($abstract)
      $different = 'Abstract';
    else if ($SubstGroupN != TSG_Item)
      $different =  SubstGroupToStr($SubstGroupN);
    else{
      $different = '';
      $type = ElementTypeToStr($TypeN);
    }
    $occursCode = TUCNStr($TUCN);
    $other = $other2 = '';
    foreach ($tuplesA as $tupleId2 => $tuple2A)
      if ($tupleId2 != $tupId && isset($tuple2A['Members'][$memTxId])) {
        $other  .= ", $tupleId2";
        $other2 .= ",$tuple2A[TupTxId]";
      }
    if ($other) $other = substr($other, 2) . '<br>' . substr($other2, 1);
    echo ($firstB ? '' : '<tr>') . "<td class=c>$TuMeId</td><td class=c>$Ordr</td><td class=c>$memTxId</td><td class=c>$type</td><td class=c>$different</td><td>$other</td><td>$MLabel</td><td class=c>$occursCode</td></tr>\n";
    --$n;
    $firstB = false;
  }
}
echo "</table>
<p>The 'Sk' column shows Tuples Skipped for Bros, marked with an *. ($tuplesSkiped Tuples and $tupleMembersSkipped Members in Total are being skipped.)</p>
";
Footer(true,true);
######
