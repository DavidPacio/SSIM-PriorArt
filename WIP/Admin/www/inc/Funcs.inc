<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/inc/Funcs.inc

Braiins Admin functions which are independent of a particular Tx

History:
26.02.13 Copied from the UK-GAAP-DPL version, two versions to be split asunder as work on BRL proceeds
07.08.13 AcctTypesStr() deleted

ToDo
====

string Role($id)           Returns the Role str for the Role with Id = $id
string SignToStr($signN)   Returns $signN as a string: 'Not set', 'Debit', 'Credit', 'Either'

bool IsFolioSubset($xId, $yId)           Is Folio x a subset of Folio y? (xId & yId being FoIds) Needs Folios.inc to have been loaded for $FolioPropsA
bool IsPropsListSubset($xProps, $yProps) Is Props list x a subset of Props list y?

string ChrListToIdsOrShortNamesStr($chrList, $namesA=false) Converts a Chr List to string CS Ids or name form if $namesA is passed.
string PMemDiMesAStr($pMemDiMesA, $namesA=false)            Converts a PMemDiMesA array to string Ids form or name form if $namesA is passed
string BroShortNameOrName($broId)                           Returns Bro ShortName if defined, otherwise the Bro Name. Requires $BroNamesA and $BroShortNamesA to have been loaded
string RelatedStr($related)                                 Returns string source form of a Related string in TrelatedBroId format. Requires $BroNamesA and $BroShortNamesA to have been loaded
string [] PeriodSENStrA($periodSEN, $SeSumList=0)           Returns a string i array of Period and StartEnd values coded in the $PeriodSEN enum plus $SeSumList if defined. Requires $BroNamesA and $BroShortNamesA to have been loaded
string PeriodSENStr($periodSEN)                             Returns a string of Period and StartEnd values coded in the $PeriodSEN enum
string ZonesCsStrList($zoneIdsChrList)                      Converts a chr list of Zones Ids (BroInfo.Zones) to Zones Refs cs str list. Requires $ZonesA to have been loaded via Zones.inc
string MtypeInfo($BroBits, $pMemDiMeNme)                    Gives string of M Use PMem/DiMe info for a Bro
string CheckTestStr($checkTest)                             Converts a CheckTest string in TPYtargetBroId format to string form

void Head($title, $centerB=false)
void Footer($timeB=true, $topB=false, $notCentredB=false)

*/

$StartUtime= utime();

# Role($id)
# ----
# Returns the Role str for the Role with Id = $id
function Role($id) {
  global $DB;
  static $RolesSA;
  # On first call read and cache the Roles table
  if (!$RolesSA) {
    $RolesSA = [];
    $res = $DB->ResQuery('Select Id,Role From Roles Order by Id');
    while ($o = $res->fetch_object())
      $RolesSA[(int)$o->Id] = $o->Role;
    $res->free();
  }
  if (!isset($RolesSA[$id]))
    return '';
  return $RolesSA[$id];
}

function SignToStr($signN) {
  static $a = ['Not set', 'Debit', 'Credit', 'Either'];
  return $a[(int)$signN];
}


# Is Folio x a subset of Folio y? (xId & yId being FoIds)
# Needs Folios.inc to have been loaded for $FolioPropsA
function IsFolioSubset($xId, $yId) {
  global $FolioPropsA;
  $xProps = $FolioPropsA[$xId];
  $yProps = $FolioPropsA[$yId];
  $xLen = strlen($xProps);
  $yLen = strlen($yProps);
  if ($xLen > $yLen || !$xLen || !$yLen)
    return false;
  for ($k=0; $k<$xLen; ++$k)
    if (strpos($yProps, $xProps[$k]) === false)
      return false; # x Prop not in y
  return true;
}

# IsPropsListSubset
# -----------------
# Is Props list x a subset of Props list y?
function IsPropsListSubset($xProps, $yProps) {
  $xLen = strlen($xProps);
  $yLen = strlen($yProps);
  if ($xLen > $yLen || !$xLen || !$yLen)
    return false;
  for ($k=0; $k<$xLen; ++$k)
    if (strpos($yProps, $xProps[$k]) === false)
      return false; # x Prop not in y
  return true;
}

# ChrListToIdsOrShortNamesStr($chrList, $namesA=false)   djh?? Move this to FuncsGen ?
# ---------------------------
# Converts a Chr List to string CS Ids or short name form if $namesA is passed.
function ChrListToIdsOrShortNamesStr($chrList, $namesA=false) {
  if ($namesA) {
    if (!$chrList || !($n = strlen($chrList))) return '';
    $r = '';
    for ($i=0; $i<$n; ++$i)
      $r .= ',' . $namesA[(ord($chrList[$i]) - 48)];
    return substr($r, 1);
  }
  return ChrListToCsList($chrList);
}

# PMemDiMesAStr($pMemDiMesA, $namesA=false)
# -------------
# Converts a PMemDiMesA array to string Ids form or name form if $namesA is passed
function PMemDiMesAStr($pMemDiMesA, $namesA=false) {
  #Dump('$pMemDiMesA',$pMemDiMesA);
  if ($pMemDiMesA) {
    $prefixes = 'mdxa';
    #$pMemDiMesA = json_decode($pMemDiMesA);
    $r = '';
    for ($i=II_MandatsA ; $i<=II_AllowsA; ++$i)
      if (isset($pMemDiMesA[$i]) && $pMemDiMesA[$i])
        foreach ($pMemDiMesA[$i] as $pMemDiMeId)
          $r .= ",{$prefixes[$i]}:".($namesA ? $namesA[$pMemDiMeId] : $pMemDiMeId);
    return substr($r,1);
  }
  return '';
}

# BroShortNameOrName($broId)
# ------------------
# Returns Bro ShortName if defined, otherwise the Bro Name
# Requires $BroNamesA and $BroShortNamesA to have been loaded
function BroShortNameOrName($broId) {
  global $BroNamesA, $BroShortNamesA;
  return isset($BroShortNamesA[$broId]) ? $BroShortNamesA[$broId] : $BroNamesA[$broId];
}

# RelatedStr($related)
# ----------
# Returns string source form of a Related string in TrelatedBroId format
# TrelatedBroId => <M | O | U> RelatedBro <ShortName | Name>
# Requires $BroNamesA and $BroShortNamesA to have been loaded
function RelatedStr($related) {
  return $related ? $related[0].SP.BroShortNameOrName((int)substr($related, 1)) : '';
}

# PeriodSENStrA($periodSEN, $SeSumList=0)
# -------------
# Returns a string i array of Period and StartEnd values coded in the $PeriodSEN enum plus $SeSumList if defined
# Requires $BroNamesA and $BroShortNamesA to have been loaded
function PeriodSENStrA($PeriodSEN, $SeSumList=0) {
  static $sSA = [0, 0, '', 'SumEnd', 'PostEnd', 'Stock'];
  if ($PeriodSEN <= BPT_Duration) {
    $period   = 'Duration';
    $startEnd = '';
  }else{
    /* BroInfo.PeriodSEN enums
    const BPT_Duration    = 1; # Same as TPT_Duration
    const BPT_Instant     = 2; # Same as TPT_Instant
    const BPT_InstSumEnd  = 3; # Instant StartEnd SumEnd  type
    const BPT_InstPostEnd = 4; # Instant StartEnd PostEnd type
    const BPT_InstStock   = 5; # Instant StartEnd Stock   type */
    $period = 'Instant';
    $startEnd = $sSA[$PeriodSEN];
    if ($SeSumList) {
      $list = '';
      foreach (CsListToIntA($SeSumList) as $broId)
        $list .= COM.SP.BroShortNameOrName($broId);
      $startEnd .= SP.substr($list, 2);
    }
  }
  return [$period, $startEnd];
}

# PeriodSENStr($periodSEN)
# ------------
# Returns a string of Period and StartEnd values coded in the $PeriodSEN enum
function PeriodSENStr($PeriodSEN) {
  $A = PeriodSENStrA($PeriodSEN);
  $r = $A[0]; # Period
  if ($A[1]) # StartEnd
    $r .= ", $A[1]";
  return $r;
}


# ZonesCsStrList($zoneIdsChrList)
# --------------
# Convert a chr list of Zones Ids (BroInfo.Zones) to Zones Refs cs str list
# Requires $ZonesA to have been loaded via Zones.inc
function ZonesCsStrList($zoneIdsChrList) {
  global $ZonesA;
  if (!($n = strlen($zoneIdsChrList))) return '';
  $zones = '';
  for ($i=0; $i<$n; ++$i)
    $zones .= ',' . $ZonesA[ChrToInt($zoneIdsChrList[$i])][ZI_Ref];
  return substr($zones, 1);
}

# MtypeInfo($BroBits, $pMemDiMeNme)
# ---------
# Gives string of M Use PMem/DiMe info for a Bro
function MtypeInfo($BroBits, $pMemDiMeNme) {
  return ($BroBits & BroB_UseM) ? "M Use $pMemDiMeNme" : '';
}

# CheckTestStr($checkTest)
# ------------
# Converts a CheckTest string in TPYtargetBroId format to string form
# TargetBroId <Equal To | Equal & Opp To>{ <Either | Both | Check | Target>}{ Year#} TargetBroId Name
function CheckTestStr($checkTest) {
  static $checkTsSA = ['E' => 'Equal To', 'O' => 'Equal & Opp To'], $checkPsSA = ['E' => 'Either', 'B' => 'Both', 'C' => 'Check', 'T' => 'Target'];
  if ($checkTest) {
    $targetBroId = (int)substr($checkTest,3);
    $check = "$targetBroId {$checkTsSA[$checkTest[0]]} {$checkPsSA[$checkTest[1]]} ".($checkTest[2]!=='0' ? "Year$checkTest[2] " : '').BroShortNameOrName($targetBroId);
  }else
    $check = '';
  return $check;
}

############################
# Admin Modules Output Fns #
############################

function Head($title, $centerB=false) {
  global $CentredB;
  $CentredB = $centerB;
  echo <<< HEAD
<!DOCTYPE html>
<html lang=en>
<head>
<title>$title</title>
<meta charset=utf-8>
<link rel='shortcut icon' href=favicon.png>
<link rel=stylesheet type=text/css href=css/Site.css>
</head>
<body>
HEAD;
}

function Footer($timeB=true, $topB=false, $notCentredB=false) {
  global $StartUtime, $CentredB;
  $class=($CentredB && !$notCentredB ? ' class=c' : '');
  if ($timeB)
    echo "<p$class>Done in ", ElapsedTime($StartUtime), "</p>\n";
  else
    echo '<br>';
  if ($topB)
    echo "<div class=topB onclick=scroll(0,0)>Top</div><br>\n";
  echo "<p$class><a href=./>Home</a></p>
<br><br>
<div class=topl><a href=./>Home</a></div>
<div class=topr><a href=./>Home</a></div>
";
  echo "</body>\n</html>\n";
  exit;
}


