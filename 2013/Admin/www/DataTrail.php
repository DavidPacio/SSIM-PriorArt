<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/Utils/UK-GAAP-DPL/DataTrail.php

List the Bro Data and lots of other stuff too

ToDo
----
0 in Audit Trail for year 0 509,572	SchInputPL.SegAnalysisRevCostsProfits.Geography.RevenueByDestination.Ge:Countries.NorthAmerica

Adjust Date & Time for PC time zone. [Leave as UK time for now.]
Check the Bro data table with data for all possible years = 7 years with 3 restated ones.
Check the Bro data table with data for a mixture of no restated and restated years.

Add a record of change of manager.
Add a record of change of year end day/month.

History:
15.05.12 SIM version started based in UK-GAAP-DPL version
03.07.13 B R L -> SIM

*/

require 'BaseBraiins.inc';          # $DimGroupsA via Constants.inc
require Com_Inc.'ConstantsSIM.inc';
require Com_Inc.'FuncsSIM.inc';
require Com_Inc.'ClassBro.inc';     # $PMemsA, $PMemNamesA
#equire Com_Inc_Tx.'ConstantsRg.inc';
require Com_Inc.'DateTime.inc';

Head('Data Trail', true);

$AgentId  = 1; # Braiins
$EntityId = 2; # AAAAA

# Get Entity Info and Agent Name
extract($DB->AaQuery("Select E.Ref,E.EName,E.ETypeId,E.ESizeId,E.Bits,E.CurrYear,E.MngrId,E.DGsInUse,E.DGsAllowed,E.Data,A.EName AName From Entities E Join Entities A on A.Id=E.AgentId Where E.Id=$EntityId"));
# -> $Ref, $EName, $ETypeId, $ESizeId, $CurrYear, $MngrId, $DGsInUse, $DGsAllowed, $Data, $AName
$CurrYear   = (int)$CurrYear;
$DGsInUse   = (int)$DGsInUse;
$DGsAllowed = (int)$DGsAllowed;
$EDataA = json_decode($Data);
#Dump('$EDataA',$EDataA);

# Member -> People Info
$PeopleA = ['Admin'];  # People's names  [MemId => Name] with MemId = 0 meaning Admin
$res = $DB->ResQuery(sprintf('Select Id,DName from People Where AgentId=%d And Bits&%d=%d', $AgentId, MB_STD_BITS | PB_Member, MB_OK | PB_Member));
while ($o = $res->fetch_object())
  $PeopleA[(int)$o->Id] = $o->DName;
$res->free();

echo "<h2 class=c>Data Trail for $EName</h2>
<table class=mc>
<tr><td>Entity Ref</td><td>$Ref</td></tr>
<tr><td>Entity Name</td><td>$EName</td></tr>
<tr><td>Entity Type</td><td>", EntityTypeStr($ETypeId), '</td></tr>
<tr><td>Company Size</td><td>', EntitySizeStr($ESizeId), '</td></tr>
<tr><td>Description</td><td>', $EDataA[DboI_E_Descr], '</td></tr>
<tr><td>Comments</td><td>', str_replace(D2, BR, $EDataA[DboI_E_Comments]), "</td></tr>
<tr><td>$AName Manager</td><td>{$PeopleA[$MngrId]}</td></tr>
</table>
<table class=mc>
<tr class='b c bg0'><td colspan=4>Reporting Requirements Use</td></tr>
<tr class='b bg1'><td>Reporting Requirement</td><td>In Use</td><td>Enabled</td><td>Comment</td></tr>
";
$bit = 1;
foreach ($DimGroupsA as $dg => $dgA) {
  $iu = ($DGsInUse & $bit) ? '*' : '';
  $en = ($DGsAllowed & $bit) ? '*' : '';
  echo "<tr><td>{$dgA[DGI_Name]}</td><td class=c>$iu</td><td class=c>$en</td><td>{$dgA[DGI_Tip]}</td></tr>\n";
  $bit *= 2;
}
echo '</table>
';

# Loop for each BroSet in use
$BroSetIdsA = [];
$res = $DB->ResQuery('Select BroSetId From Bros Group By BroSetId');
while ($o = $res->fetch_object())
  $BroSetIdsA[] = (int)$o->BroSetId;
$res->free();

foreach ($BroSetIdsA as $BroSetId) {
  $BrosA     =     # The Bros        [year => [BroId => BrO]] year in range 0 - 6 i.e. incl pya years
  $datYearsA = []; # to record years with data [relYear => 1] year in range 0 - 6 i.e. incl pya years

  $bsO = $DB->ObjQuery("Select Descr,Data from BroSets Where Id=$BroSetId");
  $BSDataA = json_decode($bsO->Data);
  $PeriodStartDateBroId = $BSDataA[BSI_DatePeriodStartBroId];
  $PeriodEndDateBroId   = $BSDataA[BSI_DatePeriodEndBroId];

  if (count($BroSetIdsA) > 1)
    echo "<h2 class=c>Data Trail for BroSet $bsO->Descr</h2>\n";
  $BroSetPath = Com_Str."BroSet$BroSetId/";
  require $BroSetPath.'BroInfoA.inc';       # $BroInfoA
  require $BroSetPath.'BroNamesA.inc';      # $BroNamesA
  require $BroSetPath.'BroShortNamesA.inc'; # $BroShortNamesA

  # Read and build the Bros
  $res = $DB->ResQuery("Select EntYear,BroId,BroStr From Bros Where EntityId=$EntityId And BroSetId=$BroSetId Order By BroId");
  while ($o = $res->fetch_object()) {
    $year  = $CurrYear - (int)$o->EntYear; # relYear
    $broId = (int)$o->BroId;
    $BrosA[$year][$broId] = $brO = NewBroFromString($broId, $o->BroStr);
    if ($brO->IsMaster())
      foreach ($brO->InfoA[BroI_SlaveIdsA] as $slaveId) # Create the non Set Slaves of this master. Not Set ones because Set Slaves are stored to avoid the need for summing here.
        if ($BroInfoA[$slaveId][BroI_Bits] & BroB_Ele)
          $BrosA[$year][$slaveId] = $brO->CopyToSlave(new Bro($slaveId));
    $datYearsA[$year] = 1;
  }
  $res->free();
  ksort($datYearsA);

  # Import and Postings Audit Trail
  # Entries
  $entriesA = []; # [Year => [BroId => [i => [TransSet, MemId, TypeN, AddT]]]]
  #res = $DB->ResQuery("Select EntYear,BroId,MemId,TypeN,TransSet,UNIX_TIMESTAMP(T.AddT) AddT from Bros B Join BroTrans T on T.BrosId=B.Id Where B.EntityId=$EntityId Order by EntYear Desc,B.Id,T.Id");
  $res = $DB->ResQuery("Select EntYear,BroId,MemId,TypeN,TransSet,T.AddT from Bros B Join BroTrans T on T.BrosId=B.Id Where B.EntityId=$EntityId And BroSetId=$BroSetId Order by EntYear Desc,B.Id,T.Id");
  while ($o = $res->fetch_object())
    $entriesA[$CurrYear-(int)$o->EntYear][(int)$o->BroId][] = [$o->TransSet, (int)$o->MemId, (int)$o->TypeN, $o->AddT];
  $res->free();
  # Output
  echo "<table class=mc>\n";
  foreach ($datYearsA as $year => $t) {
    $yearStr = YearStr($year, true);
    echo "<tr class='b c bg0'><td colspan=7>Import and Postings Audit Trail</td></tr>
<tr class='b c'><td colspan=7>$yearStr</td></tr>
<tr class='b bg1'><td>Ref</td><td style='min-width:900px'>Bro Reference</td><td class=r>Value</td><td class=c>PT</td><td class=c>Tran Type</td><td class=c>Date &amp; Time</td><td class=c>Person</td></tr>
";
    foreach ($entriesA[$year] as $broId => $transSetA) { # transSetA = [i => [TransSet, MemId, TypeN, AddT]
      $brO   = $BrosA[$year][$broId];
      $postType = $brO->PostTypeStr();
      $entsA = [];
      # djh?? Need to use BrodatKeys via BuildBroDatKey() and then UnpackBroDatKey() here?
      foreach ($transSetA as $transA) { # [0 => TransSet, 1 => MemId, 2 => TypeN, 3 => AddT]
        foreach (explode(D1, $transA[0]) as $tranStr) {
          # Non-Tuple: DatType {DiMeRef} Dat
          # with Dat ==  for a deleted tran BroDat
          $broDatType = (int)$tranStr[0];
          $dat = substr($tranStr, 1);
          $dA = explode(D2, $dat);
          if (count($dA)===2) {
            $pMemIdsA = PMemRefToA($dA[0]);
            $dat = $dA[1];
          }else
            $pMemIdsA = 0; # with $dat already set
          if (is_numeric($dat)) $dat = (int)$dat;
          $entsA[BuildBroDatKey($broDatType, $pMemIdsA)][] = [$dat, $transA[1], $transA[2], $transA[3]]; # [BroDatKey => [i => [0 => Dat, 1 => MemId, 2 => TypeN, 3 => AddT]]]
        }
      }
      ksort($entsA, SORT_NATURAL);
      foreach ($entsA as $broDatKey => $transA) { # [i => [0 => Dat, 1 => MemId, 2 => TypeN, 3 => AddT]]
        list($broDatType, $pMemIdsA) = UnpackBroDatKey($broDatKey);
        if ($pMemIdsA) {
#echo "broDatKey=$broDatKey".BR;
#Dump('$pMemIdsA',$pMemIdsA);
          $broRef = $broId.COM.str_replace(':', DOT, implode(COM, $pMemIdsA)); # with PMemId:RefId -> PMemId.RefId
          $broRefSrce = BroName($broId).PMemRefSrce($pMemIdsA);
        }else{
          $broRef = $broId;
          $broRefSrce = BroName($broId);
        }
        $rows = count($transA);
        foreach ($transA as $row => $tranA) {
          $datTd    = $brO->FormattedDatTd($tranA[0]);
          $person   = $PeopleA[$tranA[1]];
          $tranType = BroTransTypeStr($tranA[2]);
          $addT     = $tranA[3];
         #$addT     = str_replace(' ', '&nbsp;', gmstrftime(REPORT_DateTimeFormat, $tranA[3]-$tzos)); # djh?? Gret rid of str_replace. nbsp into format?
          if ($row)
            echo "<tr>$datTd<td class=c>$postType</td><td class=c>$tranType</td><td>$addT</td><td class=c>$person</td></tr>\n";
          else{
            if ($rows > 1)
              echo "<tr><td class=top rowspan=$rows>$broRef</td><td class='top wball' rowspan=$rows>$broRefSrce</td>$datTd<td class=c>$postType</td><td class=c>$tranType</td><td>$addT</td><td class=c>$person</td></tr>\n";
            else
              echo "<tr><td>$broRef</td><td class=wball>$broRefSrce</td>$datTd<td class=c>$postType</td><td class=c>$tranType</td><td>$addT</td><td class=c>$person</td></tr>\n";
          }
        }
      }
    }
  }
  echo "</table>\n";

  # Bro Data
  $hdg = 'Bro Data Including Summed and Derived Values';
  # Bro Data for all years re gaps in some years
  $brosUsedA = []; # a 2 dimensional array of Bro use: [broId => [i => year]]
  $numCols = 5; # Ref, SN, Bro Ref, PT, Src
  $yearsHdg = '';
  foreach ($datYearsA as $year => $t) {
    ++$numCols;
    $yearsHdg .= '<td class=r>' . YearStr($year) . '</td>';
    foreach ($BrosA[$year] as $broId => $t)
      $brosUsedA[$broId][] = $year;
  }
  ksort($brosUsedA); # Sort by BroId

  # Through the Bros
  echo "<br><table class=mc>\n";
  $n = -1;
  foreach ($brosUsedA as $broId => $yearsA) { # $brosUsedA = [broId => [i => year]]
    $broShortName = BroShortName($broId);
    # Assemble the individual BroDats for this Bro
    $broDatOsA = []; # a 2 dimensional array of Bro entries [DiMeRef => [year => Bro]]
    foreach ($datYearsA as $year => $t) { # 0 - 6
      if (in_array($year, $yearsA))
        foreach ($BrosA[$year][$broId]->AllBroDatOs() as $broDatKey => $datO)  # [BroDatKey => BroDatO]
          $broDatOsA[$broDatKey][$year] = $datO;
    }
    ksort($broDatOsA, SORT_NATURAL); # sort the BroDats for this Bro
    $num = count($broDatOsA);
    if ($n-($num>1 ? $num : 0)<0) {
      echo "<tr class='b c bg0'><td colspan=$numCols>$hdg</td></tr>\n<tr class='b bg0'><td>Ref</td><td>Short Bro Name</td><td style=min-width:900px>Bro Reference</td><td class=c>PT</td><td class=c>Src</td>$yearsHdg</tr>\n";
      $n = 50;
    }
    foreach ($broDatOsA as $broDatKey => $yearBroDatOsA) {
      $firstB = $oneSrceB = true;
      foreach ($yearBroDatOsA as $year => $datO) {
        $srce = $datO->Source();
        if ($firstB) {
          $datTds    = '';
          $nextYear  = 0;
          $postType  = $datO->DadBrO->PostTypeStr();
          $firstSrce = $srce;
          $srcStr    = ",$srce";
          $firstB    = false;
        }else{
          if ($srce !== $firstSrce) $oneSrceB = false;
          $srcStr .= ",$srce";
        }
        for (; $nextYear < $year; ++$nextYear)
         if (isset($datYearsA[$nextYear]))
           $datTds .= '<td></td>';
        $datTds .= $datO->FormattedDatTd();
        ++$nextYear;
      }
      $srcStr = $oneSrceB ? StrField($srcStr, COM, 1) : substr($srcStr,1);
      echo '<tr><td>',$datO->BroRef(),"</td><td>$broShortName</td><td class=wball>",$datO->BroRefSrce(),"</td><td class=c>$postType</td><td class=c>$srcStr</td>$datTds</tr>\n";
      --$n;
    }
  }
  echo "</table>\n";
} # end of loop through the BroSetIds

echo "<p class=mb0>Where the 'PT' column shows the Post Type, DE for Double Entry or Sch for Schedule,<br>and the 'Src' column shows the Source of the values as follows:</p>
<table class=itran>
<tr><td>&bull;</td><td>P</td><td>Posting or Import</td></tr>
<tr><td>&bull;</td><td>PE</td><td>Prior year End value as Start for a startend Bro</td></tr>
<tr><td>&bull;</td><td>R</td><td>Restated Orignal Value - included in 'b' Base Sum and 'd' Restated Amount Sum</td></tr>
<tr><td>&bull;</td><td>S</td><td>Summed</td></tr>
<tr><td>&bull;</td><td>SE</td><td>Sum End calculation for a startend Bro of type SumEnd</td></tr>
<tr><td>&bull;</td><td>b</td><td>Base (no properties) value = sum of the primary property values for the Bro</td></tr>
<tr><td>&bull;</td><td>d</td><td>Dimension summing value as per the Dimensions Map</td></tr>
<tr><td>&bull;</td><td>e</td><td>dErived or dEduced value</td></tr>
<tr><td>&bull;</td><td>i</td><td>Intermediate dimension for a posting with multiple properties</td></tr>
<tr><td>&bull;</td><td>r</td><td>Restated Orignal Value - included in 'd' Restated Amount Sum</td></tr>
</table>
<p>An upper case Source code indicates that this is a Primary entry. In the case of Summing Bros a Primary entry is one which contributes to the Bro's Base total i.e. its no dimensions value. Lower case codes are for information values derived from the primary values.<br>
A prefix of 'm' means that the value has been copied from the Master, which also means that the Bro in question is a Slave Bro.<br>
'i' and 't' codes can appear after a Primary code when a combination is involved.<br>
If different Source codes apply in different years, the column contains a comma separated list for all years, otherwise a single code is shown which applies to all years.</p>
";

Footer(true,true);
#########

function YearStr($yearRel, $datesB=false) {
  global $PeriodStartDateBroId, $PeriodEndDateBroId;
  if ($yearRel < Pya_Year_Offset)
    $yearStr = "Year $yearRel";
  else
    $yearStr = 'Year ' . ($yearRel -= Pya_Year_Offset) . ' (Restated)';
  if ($datesB)
    $yearStr .= ' from '.eeDtoStr(BroData($PeriodStartDateBroId, $yearRel)).' to '.eeDtoStr(BroData($PeriodEndDateBroId, $yearRel));
  return $yearStr;
}

function BroData($broId, $yearRel=0) {
  global $BrosA;
  return isset($BrosA[$yearRel][$broId]) ? $BrosA[$yearRel][$broId]->EndBase() : '';
}

function DebugMsg() {
  # djh?? Temporary re BroClass
}

