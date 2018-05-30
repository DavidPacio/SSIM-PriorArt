<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/UK-IFRS-DPL/inc/UtilsFuncs.inc

Braiins UK-IFRS-DPL functions

History:
26.06.13 Started based on UK-GAAP-DPL version

ToDo djh??
====

string  DimsChrListToSrce($dims, $srceB=false)    Convert a Dims list in Chr List form to CS short name source form. Requires DimNamesA.inc to have been loaded for $DimNamesA
string  BuildTuplesList()                         Generates CS list of Tuple Ids for the Tuples that $txId is a member of
string  HypercubesFromList($hypercubesChrList)    Generate a list of Hypercubes, using shortened role names, from a string list of Hypercubes.Ids, as used in Elements.Hypercubes

void Head($title, $centerB=false)
void Footer($timeB=true, $topB=false, $notCentredB=false)

*/

$StartUtime= utime();

function ElName($id) {
  global $DB;
  return $DB->StrOneQuery("Select name From Elements Where Id=$id");
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

# Generate a list of Hypercubes, using shortened role names, from a string list of Hypercubes.Ids, as used in Elements.Hypercubes
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
  return str_replace('Hypercube - ', '', substr($ret, 2)); # Strip 'Hypercube - ' which appears in all Hypercube role names
}



############################
# Utils Modules Output Fns #
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
<link rel='shortcut icon' href=../favicon.png>
<link rel=stylesheet type=text/css href=../css/Site.css>
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


