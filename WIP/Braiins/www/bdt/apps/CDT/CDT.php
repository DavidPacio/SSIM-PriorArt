<?php /* Copyright 2011-2013 Braiins Ltd

/bdt/apps/CDT/CDT.php
Server ops for CDT Current entity Data Trail

History:
31.12.12 Started
13.03.13 Added locking

ToDo
====

Fix times. Getting UK times.

*/
$AppAid  = 'CDT';
$AppName = 'Data Trail';
require '../../Base.inc';
$AppEnum = BDT_CDT;
require Com_Inc_Tx.'ConstantsRg.inc';
require Com_Inc.'FuncsBRL.inc';
require Com_Inc.'ClassBro.inc';  # $BroInfoA $DiMesA $BroNamesA $BroShortNamesA $DiMeNamesA $DiMeTargetsA $RestatedDiMeTargetsA $TuMesA
require Com_Inc.'DateTime.inc';
Start();
SessionOpenBDT(); # BD->*, AgentId, EntityId, TZO, MLevel

# Op       Dat Rec       Ret  Dat Returned
# I Init   []            0|1  Report Options table body
# R Report [Selections]  0    6 fields: [Title  Heading  Ante  Main  Post  RT Messages]

# Check permissions
($DB->Bits & AP_Read) || Error(ERR_NOT_AUTH);

# Globals for both ops
# =======
$BrosA     =       # The Bros        [year => [BroId => BrO]] year in range 0 - 6 i.e. incl pya years
$datYearsA = null; # to record years with data [relYear => 1] year in range 0 - 6 i.e. incl pya years

# Read locks for Agent, Entity, Bros which covers BroTrans
$locksA = [
  [T_B_Agents,   $AgentId,  Lock_Read],
  [T_B_Entities, $EntityId, Lock_Read],
  [T_B_Bros,     $EntityId, Lock_Read]
];

# I R
if ($Op === Op_I) {
  # Init. Rec: No Dat. Ret: Report Options table body
  # Get the Read locks
  $DB->GetBDbLocks($locksA, 'LockFail');
  $EA = $DB->AaQuery("Select StatusN,CurrYear,Level,DataState,Bits From Entities Where Id=$EntityId");
  foreach ($EA as $k => $v)
    $$k = (int)$v; # -> $StatusN, $CurrYear, $Level, $DataState, $Bits
  # Check whether login to this entity still OK
  (($Bits & MB_STD_BITS) === MB_OK && $Level <= $MLevel && $StatusN < EStatus_Dormant) || Error(ERR_EntityLoginNok); # Entity Delete bit set, or OK bit not set, or Level changed, or StatusN not one of the active ones
  $ret = 1; # default not OK return
  if ($DataState) {
    /* Dat: table rows
    <tr class=c>      <td>0</td><td>2009-01-01</td><td>2009-12-31</td><td></td>                                                   <td><input type=checkbox></td><td><input type=checkbox></td></tr>
    <tr class='c odd'><td>1</td><td>2008-01-01</td><td>2008-12-31</td><td class=pb0><img src=img/ok22.png height=16 width=16></td><td><input type=checkbox></td><td><input type=checkbox></td></tr>
    <tr class=c>      <td>2</td><td>2007-01-01</td><td>2007-12-31</td><td></td>                                                   <td><input type=checkbox></td><td><input type=checkbox></td></tr>
    <tr class='c odd'><td>3</td><td>2006-01-01</td><td>2006-12-31</td><td></td>                                                   <td><input type=checkbox></td><td><input type=checkbox></td></tr> */
    # Read and build the Year Start/End Bros
    $res = $DB->ResQuery(sprintf("Select EntYear,BroId,BroStr From Bros Where EntityId=$EntityId And BroId In(%d,%d) Order By EntYear Desc", BroId_Dates_YearStartDate, BroId_Dates_YearEndDate));
    $DB->RelBDbLocks(); # Release the Read locks
    while ($o = $res->fetch_object()) {
      $year  = $CurrYear - (int)$o->EntYear; # relYear in Asc order thanks to Order By EntYear Desc
      if ($year > Max_StdRelYear) {
        # Pya year
        $year -= Pya_Year_Offset; # std year
        if (isset($datYearsA[$year])) # expect std year to have been set. Skip if not.
          $datYearsA[$year] = 2;
      }else{
        $broId = (int)$o->BroId;
        $BrosA[$year][$broId] = NewBroFromString($broId, $o->BroStr);
        $datYearsA[$year] = 1;
      }
    }
    $res->free();
    if ($datYearsA) {
      ksort($datYearsA);
      $dat = '';
      $i = 0;
      foreach ($datYearsA as $year => $b) {
        $dat .= sprintf("<tr class=%s><td>$i</td><td>%s</td><td>%s</td>%s<td><input type=checkbox></td><td><input type=checkbox></td></tr>/n",
                       ($i%2 ? "'c odd'" : 'c'), eeDtoStr(BroData(BroId_Dates_YearStartDate, $year)), eeDtoStr(BroData(BroId_Dates_YearEndDate, $year)),
                       ($b===2 ? '<td class=pb0><img src=img/CheckGrey.png height=12 width=12></td>' : '<td></td>'));
        ++$i;
      }
      $ret = OK;
    }else
      $dat = '<tr class=c><td colspan=7>There are no valid Year Start and Year End dates defined for <span class=ERef></span> so no Data Trail is available.</td></tr>';
  }else
    $dat = '<tr class=c><td colspan=7>There is no Accounting Data for <span class=ERef></span> so no Data Trail is available.</td></tr>';
  AjaxReturn($ret, $dat);
} # end of I

if ($Op !== Op_R) Error(ERR_Op);

# R - Report
# Dat=R0475186401001  Audit Trail | Data Listing by year from 0

# Globals plus $BrosA and $datYearsA declared above
# =======
$ATYearsA =              # Audit Trail Years  [year => <0 | 1 | 2>] 0 = no, 1 = std year only, 2 = std & pya years
$DLYearsA =              # Data Listing Years [year => <0 | 1 | 2>] 0 = no, 1 = std year only, 2 = std & pya years
$retrieveYearsiA = null; # Years of Bros/Trans to retrieve
$PeopleA  = ['Admin'];   # People's names  [MemId => Name] with MemId = 0 meaning Admin
$nat = $ndl = 0;         # Number of AT years, DL years, not incl PYA years
$TZOs  = $TZO*60;        # browser TZO in secss. gmstrftime(time() - $TZOs) -> local time now

$n = count($DatA);
if (!$n) {
  LogIt('CDT.php Report Error: - No Report Options Data');
  Error(ERR_CLIENT_DATA);
}
if ($n>8) { # 2 x 4 yrs
  LogIt("CDT.php Report Error: - $n Report Options > max 8 expected");
  Error(ERR_CLIENT_DATA);
}
if ($n%2) {
  LogIt("CDT.php Report Error: - $n Report Options not even number expected");
  Error(ERR_CLIENT_DATA);
}
$nYears = $n/2;

$year = 0;
foreach ($DatA as $i => $v) {
  if ($i%2) {
    $DLYearsA[$year] = Clean($v, FT_INT);
    ++$year;
  }else
    $ATYearsA[$year] = Clean($v, FT_INT);
}
# Finised with $DatA
#DumpLog('$DatA',$DatA);
#DumpLog('$ATYearsA',$ATYearsA);

# Member -> People Info - before the locks
$res = $DB->ResQuery(sprintf('Select Id,DName from People Where AgentId=%d And Bits&%d=%d', $AgentId, MB_STD_BITS | PB_Member, MB_OK | PB_Member));
while ($o = $res->fetch_object())
  $PeopleA[(int)$o->Id] = $o->DName;
$res->free();

# Get the Read locks
$DB->GetBDbLocks($locksA, 'LockFail');
# Get Entity Info and Agent Name
$eA = $DB->AaQuery("Select Ref,EName,ETypeId,ESizeId,StatusN,CurrYear,Level,ManagerId,DataState,AcctsState,DGsInUse,DGsAllowed,E.Bits,Comments,A.AName From Entities E Join Agents A on E.AgentId=A.Id Where E.Id=$EntityId");
foreach ($eA as $k => $v)
  if (is_numeric($v))
    $$k = (int)$v; # -> $ETypeId, $ESizeId, $StatusN, $CurrYear, $Level, $ManagerId, $DataState, $AcctsState, $DGsInUse, $DGsAllowed, $Bits as ints
  else
    $$k = $v; # -> $Ref, $EName, $Comments, $AName as strings

# Check whether login to this entity is still OK
(($Bits & MB_STD_BITS) === MB_OK && $Level <= $MLevel && $StatusN < EStatus_Dormant) || Error(ERR_EntityLoginNok); # Entity Delete bit set, or OK bit not set, or Level changed, or StatusN not one of the active ones
$DataState || AjaxReturn(ERR_CLIENT_DATA);

# Read the transactionsif have Audit Trails
if ($nat) { # have Audit Trail(s)
  # Build retrieveYearsiA for Trans
  $retrieveYearsiA = [];
  for ($year=0; $year<$nYears; ++$year) {
    if ($ATYearsA[$year]) {
      $retrieveYearsiA[] = $CurrYear - $year;
      if ($ATYearsA[$year] === 2) $retrieveYearsiA[] = $CurrYear - $year - Pya_Year_Offset;
    }
  }
  sort($retrieveYearsiA);
  $entriesA = []; # [Year => [BroId => [i => [TransSet, MemId, TypeN, AddT]]]]
  $res = $DB->ResQuery("Select EntYear,BroId,MemId,TypeN,TransSet,UNIX_TIMESTAMP(T.AddT) AddT from Bros B Join BroTrans T on T.BrosId=B.Id Where B.EntityId=$EntityId And EntYear In".ArrayToBracketedCsList($retrieveYearsiA).' Order by EntYear Desc,B.Id,T.Id');
  while ($o = $res->fetch_object())
    $entriesA[$CurrYear-(int)$o->EntYear][(int)$o->BroId][] = [$o->TransSet, (int)$o->MemId, (int)$o->TypeN, $o->AddT];
  $res->free();
}

# Build retrieveYearsiA for Bros
$retrieveYearsiA = [];
for ($year=0; $year<$nYears; ++$year) {
  if ($ATYearsA[$year] || $DLYearsA[$year]) {
    $retrieveYearsiA[] = $CurrYear - $year;
    $retrieveYearsiA[] = $CurrYear - $year - Pya_Year_Offset;
  }
}
sort($retrieveYearsiA);
# Read and build the Bros
$res = $DB->ResQuery("Select EntYear,BroId,BroStr From Bros Where EntityId=$EntityId And EntYear In".ArrayToBracketedCsList($retrieveYearsiA).' Order By EntYear Desc,BroId');
$DB->RelBDbLocks(); # Release the Read locks - finished reading data
while ($o = $res->fetch_object()) {
  $year  = $CurrYear - (int)$o->EntYear; # relYear in Asc order thanks to Order By EntYear Desc
  if ($year > Max_StdRelYear) {
    # Pya year
    $stdYear = $year - Pya_Year_Offset;
    if ($ATYearsA[$stdYear]) $ATYearsA[$stdYear] = 2; # Has Pya
    if ($DLYearsA[$stdYear]) $DLYearsA[$stdYear] = 2; # Has Pya
  }
  $broId = (int)$o->BroId;
  $BrosA[$year][$broId] = $brO = NewBroFromString($broId, $o->BroStr);
  if ($brO->IsMaster())
    foreach ($brO->InfoA[BroI_SlaveIdsA] as $slaveId) # create the non Set Slaves of this master. Not Set ones because Set Slaves are currently being stored when the Set Slave == Master condition is not being enforced.
      if ($BroInfoA[$slaveId][BroI_Bits] & BroB_Ele)
        $BrosA[$year][$slaveId] = $brO->CopyToSlave(new Bro($slaveId));
  $datYearsA[$year] = 1;
}
$res->free();
ksort($datYearsA);



# Output

$title = "Data Trail for [EName]";
$hdg   = "Data Trail for [EName] as at ".gmstrftime(REPORT_DateTimeFormat, time()-$TZOs);

########
# Ante #
########
$s = '';
foreach ($ATYearsA as $year => $b)
  if ($b) {
    $s .= ", $year";
    ++$nat;
  }
$ante = $nat ? ('(Audit '.PluralWord($nat, 'Trail').' for '.PluralWord($nat, 'Year').substr($s,1)) : '';
$s = '';
foreach ($DLYearsA as $year => $b)
  if ($b) {
    $s .= ", $year";
    ++$ndl;
  }
if ($ndl) {
  $dlForYears = ' for '.PluralWord($ndl, 'Year').substr($s,1); # used in Data Listing heading also
  $ante .= ($ante ? ' and ' : '(').'Data Listing'.$dlForYears;
}
$ante .= ')<br>Report generated by <span class=DName></span>, <span class=AName></span>.';

########
# Main #
########
# Entity Info
# -----------
$main = '<table class=mc>
<tr class="c ui-widget-header"><td colspan=2>Entity Information</td></tr>
<tr><td>Ref</td><td><span class=ERef></span></td></tr>
<tr class=odd><td>Name</td><td><span class=EName></span></td></tr>
<tr><td>Type</td><td>'. EntityTypeStr($ETypeId). '</td></tr>
<tr class=odd><td>Company Size</td><td>'. EntitySizeStr($ESizeId). '</td></tr>
<tr><td>Comments</td><td>'. str_replace('', '<br>', $Comments). '</td></tr>
<tr class=odd><td><span class=AName></span> Manager</td><td>' . Person($ManagerId) . '</td></tr>
</table>
<table class="mc w">
<tr class="c ui-widget-header"><td colspan=4>Entity Reporting Requirements Use</td></tr>
<tr class=ui-widget-header><td>Reporting Requirement</td><td>In Use</td><td>Enabled</td><td>Comment</td></tr>
';
$bit = 1;
foreach ($DimGroupsA as $dg => $dgA) {
  $iu = ($DGsInUse & $bit) ? '*' : '';
  $en = ($DGsAllowed & $bit) ? '*' : '';
  $main .= '<tr'.($dg%2 ? ' class=odd' : '')."><td>{$dgA[DGI_Name]}</td><td class=c>$iu</td><td class=c>$en</td><td>{$dgA[DGI_Tip]}</td></tr>\n";
  $bit *= 2;
}
$main .= '</table>
';

# Audit Trail(s)
# --------------
if ($nat) { # have Audit Trail(s)
  # Have the transaction in $entriesA = []; # [Year => [BroId => [i => [TransSet, MemId, TypeN, AddT]]]]

  foreach ($ATYearsA as $year => $yrsn) { # [year => <0 | 1 | 2>] 0 = no, 1 = std year only, 2 = std & pya years
    if (!$yrsn) continue;
    for ($yrsi=0; $yrsi<$yrsn; ++$yrsi) { # once if just std year, twice if std & pya years
      if ($yrsi) $year += Pya_Year_Offset;
      $yearStr = YearStr($year, true);
      $rep = "<br><table class=mc>
<tr class='c ui-widget-header w'><td colspan=7>Audit Trail for $yearStr</td></tr>
<tr class=ui-widget-header><td>Ref</td><td style=min-width:900px>Bro Reference</td><td class=r>Value</td><td class=c>PT</td><td class=c>Tran Type</td><td class=c>Date &amp; Time</td><td class=c>Person</td></tr>
";
      $rn = 0;
      foreach ($entriesA[$year] as $broId => $transSetA) { # transSetA = [i => [TransSet, MemId, TypeN, AddT]
        $brO   = $BrosA[$year][$broId];
        $postType = $brO->PostTypeStr();
        $entsA = [];
        # djh?? Need to use BrodatKeys via BuildBroDatKey() and then UnpackBroDatKey() here?
        foreach ($transSetA as $transA) { # [0 => TransSet, 1 => MemId, 2 => TypeN, 3 => AddT]
          foreach (explode('', $transA[0]) as $tranStr) {
            # Non-Tuple: DatType {DiMeRef} Dat
            # Tuple:     DatType Inst{DiMeRef} Dat
            # with Dat ==  for a deleted tran BroDat
            $broDatType = (int)$tranStr[0];
            $dat = substr($tranStr, 1);
            if ($brO->IsTuple()) {
              # $dat is Inst Dat or InstDiMeRef Dat
              $dA = explode('', $dat);
              $inst = (int)$dA[0];
              if (count($dA)===3) {
                $diMeIdsA = CsListToIntA($dA[1]);
                $dat = $dA[2];
              }else{
                $diMeIdsA = 0;
                $dat = $dA[1];
              }
            }else{ # $dat is Dat or DiMeRef} Dat
              $inst = 0;
              $dA = explode('', $dat);
              if (count($dA)===2) {
                $diMeIdsA = CsListToIntA($dA[0]);
                $dat = $dA[1];
              }else
                $diMeIdsA = 0; # with $dat already set
            }
            if (is_numeric($dat)) $dat = (int)$dat;
            $entsA[BuildBroDatKey($broDatType, $inst, $diMeIdsA)][] = [$dat, $transA[1], $transA[2], $transA[3]]; # [BroDatKey => [i => [0 => Dat, 1 => MemId, 2 => TypeN, 3 => AddT]]]
          }
        }
       #uksort($entsA, 'strnatcmp');
        ksort($entsA, SORT_NATURAL);
        foreach ($entsA as $broDatKey => $transA) { # [i => [0 => Dat, 1 => MemId, 2 => TypeN, 3 => AddT]]
          list($broDatType, $diMeIdsA, $inst) = UnpackBroDatKey($broDatKey);
          if ($diMeIdsA) {
            $diMeRef = implode(',', $diMeIdsA);
            $broRef = "$broId,$diMeRef";
            $broRefSrce = BroName($broId).DiMeRefSrce($diMeIdsA);
          }else{
            $broRef = $broId;
            $broRefSrce = BroName($broId);
          }
          if ($inst) {
            $broRef .= ",T.$inst";
            $broRefSrce .= ",T.$inst";
          }
          $rows = count($transA);
          foreach ($transA as $row => $tranA) {
            $datTd    = $brO->FormattedDatTd($tranA[0]);
            $person   = Person($tranA[1]);
            $tranType = BroTransTypeStr($tranA[2]);
           #$addT     = $tranA[3];
            $addT     = str_replace(' ', '&nbsp;', gmstrftime(REPORT_DateTimeFormat, $tranA[3]-$TZOs)); # djh?? Gret rid of str_replace. nbsp into format?
            $trClass  = $rn%2 ? ' class=odd' : '';
            ++$rn;
            if ($row)
              $rep .= "<tr$trClass>$datTd<td class=c>$postType</td><td class=c>$tranType</td><td>$addT</td><td class=c>$person</td></tr>";
            else{
              if ($rows > 1)
                $rep .= "<tr$trClass><td class=top rowspan=$rows>$broRef</td><td class='top wball' rowspan=$rows>$broRefSrce</td>$datTd<td class=c>$postType</td><td class=c>$tranType</td><td>$addT</td><td class=c>$person</td></tr>";
              else
                $rep .= "<tr$trClass><td>$broRef</td><td class=wball>$broRefSrce</td>$datTd<td class=c>$postType</td><td class=c>$tranType</td><td>$addT</td><td class=c>$person</td></tr>";
            }
          }
        }
      }
      $rep .= '</table>
';
    $main .= $rep;
    } # end of std/pya year loop
  } # end of years loop
} # end of Audit Trail Block

# Data Listing
# ------------
if ($ndl) { # have Data Listing
  $datYearsA =
  $brosUsedA = []; # a 2 dimensional array of Bro use: [broId => [i => year]]
  foreach ($DLYearsA as $year => $yrsn) { # [year => <0 | 1 | 2>] 0 = no, 1 = std year only, 2 = std & pya years
    if (!$yrsn) continue;
    $datYearsA[$year] = 1;
    if ($yrsn === 2) $datYearsA[$year + Pya_Year_Offset] = 1;
  }
  # Bro Data for all years re gaps in some years
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
  $rep = "<br><table class=mc>
  ";
  $n = -1;
  $rn = 0;
  foreach ($brosUsedA as $broId => $yearsA) { # $brosUsedA = [broId => [i => year]]
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
      $rep .= "<tr class='c ui-widget-header w'><td colspan=$numCols>Data Listing Including Summed and Derived Values$dlForYears</td></tr>
  <tr class=ui-widget-header w><td>Ref</td><td>Short Bro Name</td><td style=min-width:900px>Bro Reference</td><td class=c>PT</td><td class=c>Src</td>$yearsHdg</tr>
  ";    $n = 50;
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
      $srcStr = $oneSrceB ? StrField($srcStr, ',', 1) : substr($srcStr,1);
      $trClass  = $rn%2 ? ' class=odd' : '';
      ++$rn;
      --$n;
      $rep .= "<tr$trClass><td>".$datO->BroRef().'</td><td>'.$datO->BroShortName().'</td><td class=wball>'.$datO->BroRefSrce()."</td><td class=c>$postType</td><td class=c>$srcStr</td>$datTds</tr>\n";
    }
  }
  $main .= $rep. "</table>
  ";
}

########
# Post #
########
$post = "<h4>Notes:</h4>
<p class=mb0>The 'PT' column shows the Post Type, DE for Double Entry or Sch for Schedule,<br>and the 'Src' column shows the Source of the values as follows:</p>
<table class=itran>
<tr><td>&bull;</td><td>P</td><td>Posting or Import</td></tr>
<tr><td>&bull;</td><td>PE</td><td>Prior year End value as Start for a StartEnd Bro</td></tr>
<tr><td>&bull;</td><td>R</td><td>Restated Orignal Value - included in 'b' Base Sum and 'd' Restated Amount Sum</td></tr>
<tr><td>&bull;</td><td>S</td><td>Summed</td></tr>
<tr><td>&bull;</td><td>SE</td><td>Sum End calculation for a startend Bro of type SumEnd</td></tr>
<tr><td>&bull;</td><td>b</td><td>Base (no dims) value = sum of the primary dimension values for the Bro</td></tr>
<tr><td>&bull;</td><td>t</td><td>Base (no dims) value for a Tuple</td></tr>
<tr><td>&bull;</td><td>d</td><td>Dimension summing value as per the Dimensions Map</td></tr>
<tr><td>&bull;</td><td>e</td><td>dErived or dEduced value</td></tr>
<tr><td>&bull;</td><td>i</td><td>Intermediate dimension for a posting with multiple dimensions</td></tr>
<tr><td>&bull;</td><td>r</td><td>Restated Orignal Value - included in 'd' Restated Amount Sum</td></tr>
</table>
<p>An upper case Source code indicates that this is a Primary entry. In the case of Summing Bros a Primary entry is one which contributes to the Bro's Base total i.e. its no dimensions value. Lower case codes are for information values derived from the primary values.<br>
A prefix of 'm' means that the value has been copied from the Master, which also means that the Bro in question is a Slave Bro.<br>
'i' and 't' codes can appear after a Primary code when a combination is involved.<br>
If different Source codes apply in different years, the column contains a comma separated list for all years, otherwise a single code is shown which applies to all years.</p>
";

AjaxReturn(OK, "$title$hdg".$ante.''.$main.''.$post); # 5 fields: Title  Heading  Ante  Main  Post

function YearStr($yearRel, $datesB=false) {
  if ($yearRel < Pya_Year_Offset)
    $yearStr = "Year $yearRel";
  else
    $yearStr = 'Year ' . ($yearRel -= Pya_Year_Offset) . ' (Restated)';
  if ($datesB)
    $yearStr .= ' from '.eeDtoStr(BroData(BroId_Dates_YearStartDate, $yearRel)).' to '.eeDtoStr(BroData(BroId_Dates_YearEndDate, $yearRel));
  return $yearStr;
}

function BroData($broId, $yearRel=0) {
  global $BrosA;
  return isset($BrosA[$yearRel][$broId]) ? $BrosA[$yearRel][$broId]->EndBase() : '';
}

function Person($memId) {
  global $PeopleA;
  return isset($PeopleA[$memId]) ? $PeopleA[$memId] : 'Unknown';
}

function LockFail ($why){
  Error("Sorry, Data Trail is not available currently as $why.");
}


