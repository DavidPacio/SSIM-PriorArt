<?php /* Copyright 2011-2013 Braiins Ltd

TB.php

Trial Balance

ToDo
----

History:
01.11.11 Started
09.08.12 Changed to output 0 balances as 0 in the Bro sign column rather than as blank.
08.11.12 Changed to use Bro Class

*/
require 'BaseBraiins.inc';
require Com_Inc_Tx.'ConstantsRg.inc';
require Com_Str_Tx.'BroDescrsA.inc';  # $BroDescrsA
require Com_Str_Tx.'DiMeLabelsA.inc'; # $DiMeLabels~A
require Com_Str_Tx.'TupLabelsA.inc';  # $TupLabelsA
require Com_Inc.'FuncsSIM.inc';
require Com_Inc.'ClassBro.inc';  # $BroInfoA $DiMesA $BroNamesA $BroShortNamesA $DiMeNamesA $DiMeTargetsA $RestatedDiMeTargetsA $TuMesA
require Com_Inc.'DateTime.inc';

# Bro::SetErrorCallbackFn('');
# Bro::SetNewPostingDiMeBroDatCallbackFn('');

Head('Trial Balance', true);

$AgentId  = 1; # Braiins
$EntityId = 2; # AAAAA

# Globals
# =======
$BrosA     =          # The Bros        [year => [BroId => BrO]] year in range 0 - 6 i.e. incl pya years
$datYearsA = array(); # to record years with data [relYear => 1] year in range 0 - 6 i.e. incl pya years

# Get Entity Info
extract($DB->AaQuery("Select Ref,EName,ETypeId,ESizeId,StatusN,CurrYear,Level,ManagerId,DataState,AcctsState,Comments From Entities Where Id=$EntityId"));
# -> $Ref, $EName, $ETypeId, $ESizeId, $StatusN, $CurrYear, $Level, $ManagerId, $DataState, $AcctsState, $Comments

# Get Agent Info from AgentData ADT_AgentInfo record
$data = $DB->StrOneQuery("Select Data From AgentData Where AgentId=$AgentId And TypeN=". ADT_AgentInfo);
$AgentInfoA = json_decode($data, true);

# Read and build the Bros
$res = $DB->ResQuery("Select EntYear,BroId,BroStr From Bros Where EntityId=$EntityId Order By BroId");
while ($o = $res->fetch_object()) {
  $relYear = $CurrYear - (int)$o->EntYear; # relYear
  $broId  = (int)$o->BroId;
  #if ($BroInfoA[$broId][BroI_DataTypeN] === DT_Money) { No. Want the Year End dates also
    $BrosA[$relYear][$broId] = NewBroFromString($broId, $o->BroStr);
    $datYearsA[$relYear] = 1;
  #}
}
$res->free();
ksort($datYearsA);

echo "<h2 class=c>Trial Balance for $EName</h2>
<table class=mc>
<tr><td>Agent</td><td>$AgentInfoA[Name], $AgentInfoA[Description]</td></tr>
<tr><td>Entity Ref</td><td>$Ref</td></tr>
<tr><td>Entity Name</td><td>$EName</td></tr>
<tr><td>Entity Type</td><td>", EntityTypeStr($ETypeId), '</td></tr>
<tr><td>Company Size</td><td>', EntitySizeStr($ESizeId), '</td></tr>
<tr><td>Comments</td><td>', str_replace('', '<br>', $Comments), '</td></tr>
</table>
';

# The TBs

# Output
echo "<table class=mc>
";
$drSum = $crSum = $drSumSch = $crSumSch = 0;
foreach ($datYearsA as $year => $t) {
  if ($drSum || $crSum) {
    TotalsRow($drSum, $crSum, $drSumSch, $crSumSch);
    $drSum = $crSum = $drSumSch = $crSumSch = 0;
  }
  $yearStr = YearStr($year, true);
  echo "<tr class='b c bg0'><td colspan=8>Trial Balance $yearStr</td></tr>
<tr class='b bg1'><td rowspan=2>Ref</td><td rowspan=2>Title</td><td class=c colspan=2>Double Entry</td><td class='c inf' colspan=2>Schedule</td></tr>
<tr class='b bg1'><td class='r w50x'>Dr</td><td class='r w50x'>Cr</td><td class='r inf w50x'>Dr</td><td class='r inf w50x'>Cr</td></tr>
";
  foreach ($BrosA[$year] as $broId => $brO) {
    if ($brO->IsMoney() && !$brO->IsSlave()) {
      $deBro = $brO->IsDE(); #  Posting Type for Money Bros: Set if is a DE or CoA type. Unset = Schedule
      $sign  = $brO->Sign();
      foreach ($brO->PostingBroDatOs() as $broRefKey => $datO) {
        $broRef = $datO->BroRef();
        $title  = Title($broId, $datO->DiMeIdsA);
        $dat    = $datO->Dat;
        if ($deBro) {
          $drSch = $crSch = '';
          if ($dat === 0) {
            if ($sign == BS_Dr) {
              $dr = '0';$cr = '';
            }else{
              $dr = '';$cr = '0';
            }
          }else if ($dat < 0) {
            $dr = '';
            $cr = FormatMoney(-$dat);
            $crSum -= $dat;
          }else{
            $cr = '';
            $dr = FormatMoney($dat);
            $drSum += $dat;
          }
        }else{
          # Sch
          $dr = $cr = '';
          if ($dat === 0) {
            if ($sign == BS_Dr) {
              $drSch = '0';$crSch = '';
            }else{
              $drSch = '';$crSch = '0';
            }
          }else if ($dat < 0) {
            $drSch = '';
            $crSch = FormatMoney(-$dat);
            $crSumSch -= $dat;
          }else{
            $crSch = '';
            $drSch = FormatMoney($dat);
            $drSumSch += $dat;
          }
        }
        echo "<tr><td>$broRef</td><td>$title</td><td class=r>$dr</td><td class=r>$cr</td><td class='r inf'>$drSch</td><td class='r inf'>$crSch</td></tr>";
      }
    }
  }
}
TotalsRow($drSum, $crSum, $drSumSch, $crSumSch);
echo '</table>
';

Footer(true,true);
#########

function TotalsRow($dr, $cr, $drSch, $crSch) {
  $dr    = FormatMoney($dr);
  $cr    = FormatMoney($cr);
  $drSch = FormatMoney($drSch);
  $crSch = FormatMoney($crSch);
  echo "<tr class=b><td colspan=2></td><td class=r>$dr</td><td class=r>$cr</td><td class='r inf'>$drSch</td><td class='r inf'>$crSch</td></tr>";
}

function FormatMoney($n) {
  if (!$n) return '&ndash;&nbsp;';
  return number_format($n);
}

function YearStr($yearRel, $datesB=false) {
  if ($yearRel < Pya_Year_Offset)
    $yearStr = "Year $yearRel";
  else
    $yearStr = 'Year ' . ($yearRel -= Pya_Year_Offset) . ' (Restated)';
  if ($datesB)
    $yearStr .= ' from '.eeDtoStr(BroData(BroId_Dates_YearStartDate, $yearRel)).' to '.eeDtoStr(BroData(BroId_Dates_YearEndDate, $yearRel));
  return $yearStr;
}

function BroData($broId, $yearRel) {
  global $BrosA;
  return isset($BrosA[$yearRel][$broId]) ? $BrosA[$yearRel][$broId]->EndBase() : '';
}

function Title($broId, $diMeIdsA) {
  global $BroDescrsA, $DiMeLabelsA, $TupLabelsA, $TuMesA;
  $title = $BroDescrsA[$broId];
  if ($diMeIdsA)
  foreach ($diMeIdsA as $diMeId)
   #$title .= ', ' . (ctype_digit($diMeId) ? $DiMeLabelsA[$diMeId] : "T.{$TupLabelsA[$TuMesA[(int)$diMeId][TuMeI_TupId]]}".strrchr($diMeId, '.'));
    $title .= ', ' . $DiMeLabelsA[$diMeId];
  return $title;
}

