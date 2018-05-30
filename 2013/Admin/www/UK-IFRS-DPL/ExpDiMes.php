<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/UK-GAAP-DPL/ExpDiMes.php

Temporary Export to produce a start for the BRL Dimension Members

History:
16.03.13 Written

*/
require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsTx.inc';
#require Com_Inc_Tx.'ConstantsRg.inc';
require Com_Str_Tx.'DiMesA.inc';             # $DiMesA
require Com_Str_Tx.'DimNamesA.inc';     # $DimShortNamesA
#equire Com_Str_Tx.'DimDiMeShortNamesA.inc'; # $DimDiMeShortNamesA
#equire Com_Str_Tx.'UniqueDiMeRefsA.inc';    # $UniqueDiMeRefsA

Head("DiMes Export", true);
echo "<h2 class=c>Dimension Members Export</h2>
<p>DimId, Dimension Name / Short Name / Role<br>
Dimension Member Name, Short Name, Label, Level, Bits, DiMeId, MType, Sum List, Mux List, Entity Types</p>
";

$file = 'ExpDiMes.txt';
$Fh = fopen('../'.$file, 'w');
#fwrite($Fh, "\xEF\xBB\xBF"); # Write UTF-8 BOM

$res = $DB->ResQuery('Select D.*,E.name,T.Text From Dimensions D Join Elements E on E.Id=D.ElId Join Text T on T.Id=E.StdLabelTxtId');
while ($o = $res->fetch_object()) {
  $dimId = (int)$o->Id;
  $didC  = htmlspecialchars(IntToChr($dimId));
  $name  = $o->name;
  $label = $o->Text;
  $dimShortName = "dimName $dimId"; # $DimShortNamesA[$dimId];
  $role = Role($o->RoleId, true);
  $name  = str_replace(['Share-based', 'Non-current','Joint-ventures','Dimension'],['ShareBased', 'NonCurrent','JointVentures',''], $name);
  $label = str_replace(['Share-based', 'Joint-ventures',' [Dimension]'],['Share Based', 'Joint Ventures',''], $label);
  $role  = str_replace(['100 - Group and Company', 'Share-based Payment','Share-based', 'Joint-Ventures','Dimension', 'Hypercube'], ['100 - Dimension - Group and Company', 'Share Based Payment', 'Share based', 'Joint Ventures', 'Property', 'Folio'], Role($o->RoleId, true)); # true = no trailing [id]

  $re2 = $DB->ResQuery("Select M.Id,M.Level,T.Text From DimensionMembers M Join Elements E on E.Id=M.ElId Join Text T on T.Id=E.StdLabelTxtId Where DimId=$dimId");
  $firstB = true;
  while ($dmO = $re2->fetch_object()) {
    $diMeId = (int)$dmO->Id;
    $level  = (int)$dmO->Level;
    $diMeA    = $DiMesA[$diMeId];
    $bits     = $diMeA[DiMeI_Bits];
    $diMeName = "diMeName $diMeId"; # $DimDiMeShortNamesA[$diMeId];
   #$diMeLabel= "$dimShortName.$dmO->Text";
    $diMeLabel= str_replace(['Share-based', 'share-based', 'Joint-Venture','oint-venture'], ['Share based', 'share based', 'Joint Venture', 'oint venture'], "$dimShortName.$dmO->Text");
   #if ($bits & DiMeB_Default) $diMeName = "$dimShortName [".StrField($diMeName,'.',1).']'; # with the Dim name preface and the DiMe name in []s if the default
    $mType    = 0; # $diMeA[DiMeI_MType];
    $etList   = 0; # $diMeA[DiMeI_ETypeList];
    $muxList  = $diMeA[DiMeI_MuxListA] ? implode(',', $diMeA[DiMeI_MuxListA]) : 0;
    if ($bits & DiMeB_SumList)
      $sumList = implode(',', $diMeA[DiMeI_SumListA]);
    else
      $sumList = 0;
   #if (!($uniqueDiMeRef = $UniqueDiMeRefsA[$diMeId])) $uniqueDiMeRef = '';
    $uniqueDiMeRef = '';
    if ($firstB) {
      $firstB = false;
      echo "$dimId| $label| $name| $dimShortName| $role<br>";
      fwrite($Fh, "$dimId	$label	$name	$dimShortName	$role\n");
    }
   #echo "$diMeName| $uniqueDiMeRef| $diMeLabel| $level| $bits| $diMeId| $mType| $sumList| $muxList| $etList<br>";
   #fwrite($Fh, "$diMeName	$uniqueDiMeRef	$diMeLabel	$level	$bits	$diMeId	$mType	$sumList	$muxList	$etList\n");
    echo "$diMeName| $uniqueDiMeRef| $diMeLabel<br>";
    fwrite($Fh, "$diMeName	$uniqueDiMeRef	$diMeLabel\n");
  }
  $re2->free();
}

echo '49| Unallocated| Unallocated| Unallocated| 120 - Operating Activities<br>
Unallocated|Unallocated|Unallocated|0|512|9999|0|0|0<br>';
fwrite($Fh, "49	Unallocated	Unallocated	Unallocated	120 - Operating Activities
Unallocated	Unallocated	Unallocated	0	512	9999	0	0	0	0\n");
fclose($Fh);

Footer(true,true);
##################

