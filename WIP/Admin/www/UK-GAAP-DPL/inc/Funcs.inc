<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/Utils/UK-GAAP-DPL/inc/UtilsFuncs.inc

Braiins UK-GAAP-DPL Utils functions

History:
30.09.12 Started based on UK-GAAP version
07.10.12 BroRefToSrce() changes for leading "DimShortName." dropped from $DiMeShortNamesA
25.10.12 BroRefToSrce() changed to use DiMeNamesA wo need for DiMesA
09.11.12 BroRefToSrce() OoS with advent of Bro class and Checktest -> BroId only
         BroRefCmp()    OoS with advent of Bro Class
15.11.12 Hys, Dims, DiMes in Srce form back to Ids
17.03.13 Added option for Hys, Dims, DiMes to be in Srce form rather than Ids
21.04.13 Removed Braiins Dimension option from IsHypercubeSubset()

ToDo djh??
====

string  AcctTypesCsStrList($acctTypesChrList)
string  ZonesCsStrList($zoneIdsChrList)           Convert a chr list of Zones Ids (BroInfo.Zones) to Zones Refs cs str list. Requires ZonesA.inc to have been loaded
string  CheckTestToStr($CheckTest)                Convert a CheckTest string in TPYtargetBroId format to string form: TargetBroId <Equal To | Equal & Opp To>{ <Either | Both | Check | Target>}{ Year#} TargetBroId Name
string  MtypeDiMeInfo($BroBits)                   Gives string of M# type DiMe info for a Bro
string  HysChrListToSrce($hys, $srceB=false)      Convert a Hypercubes list in Chr List form to CS short name source form. Requires Hypercubes.inc to have been loaded for $HyNamesA
string  DimsChrListToSrce($dims, $srceB=false)    Convert a Dims list in Chr List form to CS short name source form. Requires DimNamesA.inc to have been loaded for $DimNamesA
string  BroDiMesAtoSrce($BroDiMesA, $srceB=false) Convert a Bro BroDiMesA property in json form to source form. Requires DiMeNamesA.inc to have been loaded for $DiMeNamesA
string  BuildTuplesList()                         Generates CS list of Tuple Ids for the Tuples that $txId is a member of
string  ExpandTuple($txId, $tupId)                More info on a Tuple stored in BroInfo.TupId, to include the TupTxId, TuMeId, TUC, and Tuple short name

void Head($title, $centerB=false)
void Footer($timeB=true, $topB=false, $notCentredB=false)

*/

$StartUtime= utime();

function ElName($id) {
  global $DB;
  return $DB->StrOneQuery("Select name From Elements Where Id=$id"); # as of 10.10.12
}

function Text($id) {
  global $DB;
  return $id ? $DB->StrOneQuery("Select Text from Text where Id=$id") : '';
}

// Element standard label Roles.Id == 1
// Often a join on StdLabelTxtId in a main E query can give this.
function ElStdLabel($id) {
  global $DB;
  return $DB->StrOneQuery("Select T.Text from Text T Join Elements E on T.Id=E.StdLabelTxtId Where E.Id=$id");
}

function Role($id, $noIdB=false) {
  global $DB;
  static $RolesSA;
  # On first call read and cache the Roles table
  if (!$RolesSA) {
    $RolesSA = [];
    $res = $DB->ResQuery('Select Id,definition From Roles Order by Id');
    while ($o = $res->fetch_object())
      $RolesSA[(int)$o->Id] = $o->definition;
    $res->free();
  }
  if (!isset($RolesSA[$id]))
    return '';
  $ret = $RolesSA[$id]; # $DB->StrOneQuery("Select definition from Roles where Id=$id");
  if (!$noIdB) {
    if ($id >= TR_FirstHypercubeId) {
      $id = $id - TR_FirstHypercubeId + 1;
      $ret .= " [$id]";
    }elseif ($id >= TR_FirstDimensionId) {
      $id = $id - TR_FirstDimensionId + 1;
      $ret .= " [$id]";
    }
  }
  return $ret;
}

/*
function Arcrole($id) {
  global $DB;
  return $id ? $DB->StrOneQuery("Select definition from Arcroles where Id=$id") : '';
} */

function SignToStr($typeN) { # Used by both Tx and Rg modules so here
  static $a = ['Not set', 'Debit', 'Credit', 'Either'];
  return $a[(int)$typeN];
}




# AcctTypesCsStrList($acctTypesChrList)
# Convert a chr list of account types (BroInfo.AcctTypes) to cs str list
# Account Types: '', 'Balance Sheet', 'Cash Flow', 'P&L', 'Detailed P&L', 'Historical P&L', 'STRGL', 'Notes', 'Info', 'Other'
function AcctTypesCsStrList($acctTypesChrList) {
  if (!($n = strlen($acctTypesChrList))) return 'Info';
  $acctTypes = '';
  for ($i=0; $i<$n; ++$i)
    $acctTypes .= ',' . AccountTypeStr(ChrToInt($acctTypesChrList[$i]));
  return substr($acctTypes, 1);
}

# ZonesCsStrList($zoneIdsChrList)
# Convert a chr list of Zones Ids (BroInfo.Zones) to Zones Refs cs str list
# Requires ZonesA.inc to have been loaded
function ZonesCsStrList($zoneIdsChrList) {
  global $ZonesA;
  if (!($n = strlen($zoneIdsChrList))) return '';
  $zones = '';
  for ($i=0; $i<$n; ++$i)
    $zones .= ',' . $ZonesA[ChrToInt($zoneIdsChrList[$i])][ZI_Ref];
  return substr($zones, 1);
}

# CheckTestToStr($CheckTest)
# Convert a CheckTest string in TPYtargetBroId format to string form
# TargetBroId <Equal To | Equal & Opp To>{ <Either | Both | Check | Target>}{ Year#} TargetBroId Name
function CheckTestToStr($CheckTest) {
  global $BroNamesA;
  static $checkTsSA = ['E' => 'Equal To', 'O' => 'Equal & Opp To'], $checkPsSA = ['E' => 'Either', 'B' => 'Both', 'C' => 'Check', 'T' => 'Target'];
  if ($CheckTest) {
    $targetBroId = (int)substr($CheckTest,3);
    $check = "$targetBroId {$checkTsSA[$CheckTest[0]]} {$checkPsSA[$CheckTest[1]]} ".($CheckTest[2]!=='0' ? "Year$CheckTest[2] " : '').$BroNamesA[$targetBroId];
  }else
    $check = '';
  return $check;
}

# MtypeDiMeInfo($BroBits)
# Gives string of M# type DiMe info for a Bro
function MtypeDiMeInfo($BroBits) {
  if ($BroBits & BroB_M) {
    $r = 'M# DiMes';
    if ($BroBits & BroB_2)
      $r .= ' + Prop';
    if ($BroBits & BroB_NoMok)
      $r .= ' (No M# DiMe OK)';
    return $r;
  }
  return '';
}

# HysChrListToSrce($hys, $srceB=false)
# Convert a Hypercubes list in Chr List form to CS short name source form
# Requires Hypercubes.inc to have been loaded for $HyNamesA
function HysChrListToSrce($hys, $srceB=false) {
  if ($srceB) {
    global $HyNamesA;
    if (!$hys || !($n = strlen($hys))) return '';
    $r = '';
    for ($i=0; $i<$n; ++$i)
      $r .= ',' . $HyNamesA[(ord($hys[$i]) - 48)];
    return substr($r, 1);
  }
  return ChrListToCsList($hys);
}

# DimsChrListToSrce($dims, $srceB=false)
# Convert a Dims list in Chr List form to CS short name source form
# Requires DimNamesA.inc to have been loaded for $DimNamesA
function DimsChrListToSrce($dims, $srceB=false) {
  if ($srceB) {
    global $DimNamesA;
    if (!$dims || !($n = strlen($dims))) return '';
    $r = '';
    for ($i=0; $i<$n; ++$i)
      $r .= ',' . $DimNamesA[(ord($dims[$i]) - 48)];
    return substr($r, 1);
  }
  return ChrListToCsList($dims);
}

# BroDiMesAtoSrce($BroDiMesA, $srceB=false)
# Convert a Bro BroDiMesA property array in json form to source form
# Requires DiMeNamesA.inc to have been loaded for $DiMeNamesA
# BroDiMesA =  Bro DiMes Overrides array [i => MandatsA, DefaultsA, ExcludesA, AllowsA]
# const II_MandatsA   = 0; # MandatsA  = i array of Mandatory DiMes, Dim in Bro Usable Dims or DiMe in AllowsA, only one per Dim, Mux with Defaults and Excludes
# const II_DefaultsA = 1; # DefaultsA = i array of Default   DiMes, Dim in Bro Usable Dims or DiMe in AllowsA, only one per Dim, Mux with Mandats  and Excludes
# const II_ExcludesA = 2; # ExcludesA = i array of Exclude DiMes,   Dim in Bro Usable Dims, Mux with Mandats, Defaults, and Allows
# const II_AllowsA   = 3; # AllowsA   = i array of Allow   DiMes,   Dim in Bro ExclDims,  Mux with Excludes
function BroDiMesAtoSrce($BroDiMesA, $srceB=false) {
  if ($BroDiMesA) {
    $prefixes = 'mdxa';
    $BroDiMesA = json_decode($BroDiMesA);
    $r = '';
    if ($srceB) {
      global $DiMeNamesA;
      for ($diMesi=II_MandatsA ; $diMesi<=II_AllowsA; ++$diMesi)
        if ($BroDiMesA[$diMesi])
          foreach ($BroDiMesA[$diMesi] as $diMeId) {
            if (isset($DiMeNamesA[$diMeId])) $diMeId=$DiMeNamesA[$diMeId];
            $r .= ",{$prefixes[$diMesi]}:$diMeId";
          }
    }else{
      for ($diMesi=II_MandatsA ; $diMesi<=II_AllowsA; ++$diMesi)
        if ($BroDiMesA[$diMesi])
          foreach ($BroDiMesA[$diMesi] as $diMeId)
            $r .= ",{$prefixes[$diMesi]}:$diMeId";
    }
    return substr($r,1);
  }
  return '';
}

# BuildTuplesList()
# Generates CS list of Tuple Ids for the Tuples that $txId is a member of
function BuildTuplesList($txId, $hyId) {
  global $DB, $TupIdsByMemberTxIdAndHyIdGA; # Array of Tuples for Members by TxId which are Members of Multiple Tuples whose use can be narrowed down by Hypercube defined in ConstantsTx.inc
  static $TupleListsA = []; # to cache results mainly for ElementLookup() where this can be called repeatedly for the same txId
  if ($hyId && isset($TupIdsByMemberTxIdAndHyIdGA[$txId])) { # [TupleMemberTxId (TuplePairs.MemTxId) => [HyId => TuMeId]]
    $tA = $TupIdsByMemberTxIdAndHyIdGA[$txId]; # [HyId => TupId]
    if (isset($tA[$hyId]))
      return $tA[$hyId]; # the TupId
    die("Die - Call to BuildTuplesList(txId $txId, hyId $hyId) with txId $txId in TupIdsByMemberTxIdAndHyIdA but no entry for HyId $hyId as expected");
  }
  if (!isset($TupleListsA[$txId])) {
    $res = $DB->ResQuery("Select TupId From TuplePairs Where MemTxId=$txId Order by Id");
    $TupIds = ''; # varchar(24) CS list of Tuples (TupIds from TuplePairs.TupId) which can be used with the Bro, if any. Longest is 6.
    while ($o = $res->fetch_object())
      $TupIds .= ",$o->TupId";
    $res->free();
    $TupleListsA[$txId] = substr($TupIds, 1);
  }
  return $TupleListsA[$txId];
}

# ExpandTuple($txId, $tupId)
# More info on a Tuple stored in BroInfo.TupId, to include the TupTxId, TuMeId, TUC, and Tuple short name
# $TuMesA  TuMeId => TuMe Info array: [TupId, Ordr, TUCN] TuMeI_TupId, TuMeI_Ordr, TuMeI_TUCN
function ExpandTuple($txId, $tupId) {
  global $DB, $TupNamesA, $TuMesA;
  $tuple = '';
  if ($tupId) {
    static $TuplesByTxIdA = []; # [TxId => [TupId => [i => TuMeId, TupTxId]]]
    if (!$TuplesByTxIdA) {
      $res = $DB->ResQuery('Select Id TuMeId,TupId,TupTxId,MemTxId from TuplePairs');
      while ($o = $res->fetch_object())
        $TuplesByTxIdA[(int)$o->MemTxId][(int)$o->TupId] = [(int)$o->TuMeId, (int)$o->TupTxId];
      $res->free();
    }
    if (!isset($TuplesByTxIdA[$txId][$tupId]))
      die("Die - TuplesByTxIdA[$txId][$tupId] not defined for ExpandTuple($txId, $tupId) call");
    $tuMeId  = $TuplesByTxIdA[$txId][$tupId][0];
    $tupTxId = $TuplesByTxIdA[$txId][$tupId][1];
    $TUCNchr = TUCNStr($TuMesA[$tuMeId][TuMeI_TUCN]);
    if (!isset($TupNamesA[$tupId]))
      die("Die - TupNamesA[$tupId] not defined for ExpandTuple($txId, $tupId) call");
    $tuple = "$tupId $tupTxId $tuMeId $TUCNchr {$TupNamesA[$tupId]}";
  }
  return $tuple;
}


// Generate a list of Hypercubes, using shortened role names, from a string list of Hypercubes.Ids, as used in Elements.Hypercubes
function HypercubesFromList($hypercubesChrList) {
  global $DB;
  static $rolesA;
  $hypersA = ChrListToIntA($hypercubesChrList);
  $ret = '';
  foreach ($hypersA as $hyId) {
    if (!isset($rolesA[$hyId]))
      $rolesA[$hyId] = ", [$hyId] ".$DB->StrOneQuery("Select R.definition from Roles R Join Hypercubes H on H.RoleId=R.Id Where H.Id=$hyId");
    $ret .= $rolesA[$hyId];
  }
  return str_replace('Hypercube - ', '', substr($ret, 2)); // Strip 'Hypercube - ' which appears in all Hypercube role names
}


# BroRefCmp($a, $b)
# =================
# Callback comparison function for uksort() for arrays indexed by BroRef
# Function should accept two parameters which will be pairs of array keys.
# The comparison function must return an integer less than, equal to, or greater than zero
# if the first argument is considered to be respectively less than, equal to, or greater than the second.
# BroRef can be just an int BroId or a string "BroId{,DiMeId}"
/*
function BroRefCmp($a, $b) {
  if (is_string($a) || is_string($b))
    # if either is a string BroRef compare as padded strings, 4 chars per Id
    # 04.03.12 If either is a string BroRef compare as strings. With change to BroIds always being 4 digits can skip the padding.
    # 19.08.12 Padded string use put back re 3 & 4 char DiMeIds until PHP 5's natural sort can be used.
    return strcmp(PaddedStrBroRef($a), PaddedStrBroRef($b));
    #return strcmp($a, $b);
  # Both ints so compare as ints
  if ($a < $b) return -1;
  if ($a > $b) return 1;
  return 0;
}

function PaddedStrBroRef($broRef) {
  $ref = '';
  foreach (explode(',', $broRef) as $id)
    $ref .= str_pad($id, 4, ' ', STR_PAD_LEFT);
  return $ref;
} */

/* 09.11.12 OoS with advent of Bro Class
# 23.08.12 Rewritten to handle the BroT_Start pseudo DiMe
# Want
# 1004,0
# to come before
# 1004
function BroRefCmp($a, $b) {
  if (is_int($a))
    $a = "$a,0"; # Append ',0' to just broId refs for comparison with BroT_Start cases of ',0' which are converted to ', ' below
  else if (substr($a,4,2)===',0')
    $a[5] = ' '; # as ', ' comes before ',0'
  if (is_int($b))
    $b = "$b,0";
  else if (substr($b,4,2)===',0')
    $b[5] = ' ';
  return strnatcmp($a, $b); # nat re DiMeIds of different lengths = no need for padding as used in previous version of BroRefCmp
} */

############################
# Utils Modules Output Fns #
############################

function Fold($txt, $len) {
  $remain = $txt;
  $folded = '';
  while (strlen($remain) > $len) {
    $folded .= substr($remain, 0, $len) . '<br>';
    $remain  = substr($remain, $len);
  }
  return $folded . $remain;
}
function FoldWithSpaces($txt, $len) {
  $segsA = explode(' ', $txt);
  foreach ($segsA as $i => $seg)
    if (strlen($seg) > $len)
      $segsA[$i] = Fold($seg, $len);
  return implode(' ', $segsA);
}

function Head($title, $centerB=false) {
  global $CentredB;
  $CentredB = $centerB;
  echo <<< HEAD
<!DOCTYPE html>
<html lang=en>
<head>
<title>$title</title>
<meta charset=utf-8>
<link rel='shortcut icon' href=../favicon.png>
<link rel=stylesheet type=text/css href=css/Utils.css>
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

function Abort($msg) {
  echo "\n<p>$msg</p>";
  Footer(false);
  #exit;
}


