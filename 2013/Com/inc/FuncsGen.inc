<?php /* Copyright 2011-2013 Braiins Ltd

/Com/inc/FuncsGen.inc

Logging, Time, ChrList and Misc functions

History:
11.02.11 Started based on the NMS, TARI, and originally LeLotto versions
30.06.11 ChrList functions moved here from UtilsFuncs.inc and FuncsRG.inc
26.11.11 Match() moved here from UtilsFuncs.inc for use by Compile.php
09.12.11 IsPosNegIntStr() added
21.04.12 StrField() added
07.05.12 ZeroToEmpty() added
08.05.12 PluralS() added
31.05.12 Moved Contains() here from FormatCompile.php
01.06.12 InRange() added
24.07.12 FormattedNumberToInt() added
23.08.12 CsListToIntA() added
03.09.12 Rationalised array pack/unpack function names
08.09.12 Matchn() added
11.10.12 Contains() replaced by InStr()
12.10.12 ArrayToBracketedCsList() added.
25.10.12 Split off from ClassBDB.inc
31.10.12 IntStr() added
01.11.12 NumPluralWord() added and PluralS() changed to PluralWord() with arg order reversed to match NumPluralWord()
11.03.12 AccumLog() and FlushLog() removed
28.03.13 IntegeriseA() added

Logging Functions
-----------------
void   LogIt($m)                   Write $m to the text log file

Time Functions
--------------
double utime()
string ElapsedTime($startUtime)

ChrList Functions
-----------------
chr     IntToChr($i)
tinyint ChrToInt($c)
string  IntAToChrList($aA)         Generates a sorted duplicates excluded chr list of array int values expected to fit into one chartacter coded as chr(val + 48) e.g. dimension or hypercube Ids
array   ChrListToIntA($chrList)    Returns an int array of chr list ints, [] if the list is empty
bool    InChrList($i, $chrList)    Returns true if int i is in the Chr List, false o'wise
string  ChrListToCsList($chrList)  Returns a CS list of the ints in the Chr List

Misc Functions
--------------
int        Match($val, $matchA)         Case insensitive match of $val against an array of possible values. Returns the index+1 on a match, 0 o'wise
int        Matchn($val, $matchA)        Case insensitive match of $val against an array of possible match values for the length of the possible match values. Returns the index+1 on a match, 0 o'wise
string     StrField($s, $sep, $n)       Returns field $n base zero from a string $s with $sep separators
int/string IntStr($s)                   Returns $s as an int if it numeric o'wise as passed
int        IsPosNegIntStr($s)           Tests whether a string holds either a positive or negative integer. Returns 0 or 1.
int/false  FormattedNumberToInt($n)     Converts enclosing ()s to leading -; strips thou seps; returns int n if valid +- int; otherwise returns false
bool       InRange($v, $a, $b)          Returns true/false for $v being in the range $a to $b
bool       InStr($needle, $haystack)    Returns true/false for needle being in haystack, case sensitive
mixed      ZeroToEmpty($v)              Returns '' if $v === 0 o'wise $v
string     PluralWord($n, $word)        Returns $word or "{$word}s" depending on whether $n is > 1 or not
string     NumPluralWord($n, $word)     Returns "$n $word" or "$n {$word}s" depending on whether $n is > 1 or not
array      CsListToIntA($csList)        Returns an Int CS List as an array of ints
string     ArrayToBracketedCsList($aA)  Returns $aA as a bracketed CS list e.g. $aA = [1,2,3] -> "(1,2,3)"
string     ArrayOrIntToStr($aA)         Returns $aA as a string of either an int or array e.g. "123" or "[1,2,3]"
void       IntegeriseA(&$AR)            Converts any array elements of $AR which are numeric strings to ints, useful for an array retrieved from DB. Should not be used if the numeric values could be anything other than ints.

*/


#####################
# Logging Functions #
#####################

# LogIt
# ~~~~~
# Write $m to the text log file, along with any previously accumulated log messages
function LogIt($m) {
  $m = strftime('%Y.%m.%d %H:%M:%S ') . $m . NL;
  if ($fh = fopen(LOG_FILE, 'a')) {
    if (fwrite($fh, $m) !== false)
      $m = 0;
    fclose($fh);
  }
  if ($m)
    mail(WEBMASTER_EMAIL, 'Log failure', $m, "From:Error@braiins.com\r\n");
}

##################
# Time Functions #
##################

# utime
# ~~~~~
# Current micro time
function utime() {
#list($usec, $sec) = explode(" ",microtime());
#return $usec + $sec;
  return microtime(true); # true = return time as a float as of PHP 5.0
}

# ElapsedTime
# ~~~~~~~~~~~
# Returns string of elapsed time since $StartUtime obtained from a utime() call
function ElapsedTime($startUtime) {
  $tsecs = utime() - $startUtime;
  if ($tsecs >= 1)
    $t = round($tsecs, 1) . ' secs';
  else if ($tsecs >= 0.001)
    $t = round($tsecs*1000, 0) . ' msecs';
  else
    $t = round($tsecs*1000000, 0) . ' usecs';
  return $t;
}

#####################
# ChrList Functions #
#####################
# Fns to do with storing small integers in characters coded as chr(val + 48) to make 1 = '1' etc

function IntToChr($i) {
  return chr($i + 48);
}
function ChrToInt($c) {
  return ord($c) - 48;
}

# Generate a sorted duplicates excluded chr list of array int values expected to fit into one chartacter coded as chr(val + 48) e.g. dimension or hypercube Ids
# $aA = [# => Id]
function IntAToChrList($aA) {
  $chrList = '';
  sort($aA, SORT_NUMERIC);
  foreach (array_unique($aA, SORT_NUMERIC) as $i)
    $chrList .= chr($i + 48);
  return $chrList;
}

# Returns an int array of chr list ints, [] if the list is empty
function ChrListToIntA($chrList) {
  $aA = [];
  if ($n = strlen($chrList))
    for ($i=0; $i<$n; ++$i)
      $aA[] = ord($chrList[$i]) - 48;
  return $aA;
}

# Returns true if int i is in the Chr List, false o'wise
function InChrList($i, $chrList) {
  return $chrList ? strpos($chrList, chr($i + 48)) !== false : false;
}

# Returns a CS list of the ints in the Chr List
function ChrListToCsList($chrList) {
  if (!$chrList || !($n = strlen($chrList))) return '';
  $csList = '';
  for ($i=0; $i<$n; ++$i)
    $csList .= ',' . (ord($chrList[$i]) - 48);
  return substr($csList, 1);
}

##################
# Misc Functions #
##################
# Match
# -----
# Case insensitive match of $val against an array of possible values
# Returns the index+1 on a match, 0 o'wise
function Match($val, $matchA) {
  foreach ($matchA as $i => $m) {
    if (!strcasecmp($val, $m))
      return $i+1;
  }
  return 0; # false;
}

# Matchn
# ------
# Case insensitive match of $val against an array of possible match values for the length of the possible match values
# Returns the index+1 on a match, 0 o'wise
function Matchn($val, $matchA) {
  foreach ($matchA as $i => $m) {
    if (!strncasecmp($val, $m, strlen($m)))
      return $i+1;
  }
  return 0; # false;
}

# preg_match attempts.
# Matchr1 was the slowest
# Matchr2 faster from match = around 4 and up

# function Matchr1($val, $matchA) {
#   if ($res = preg_match('/^\b(' . implode('|', $matchA) . ')\b/i', $val, $matches)) {
#     if (!strcasecmp($val, $matches[1])) # to check that the match is the whole of $val
#       return array_search(strtolower($matches[0]), $matchA)+1;
#   }
#   return 0; # false;
# }

# function Matchr2($val, $matchA) {
#   if ($res = preg_match('/^\b(' . implode('|', $matchA) . ')\b.*/i', $val, $matches)) { # /^\b(end|start)\b.*/i with .* to pick up any trailing characters -> fail by array_search()
#    return ($i=array_search(strtolower($matches[0]), $matchA)) === false ? 0 : $i+1;
#  }
#  return 0; # false;
# }

# StrField
# --------
# Returns field $n base zero from a string $s with $sep separators
function StrField($s, $sep, $n) {
  $fldsA=explode($sep, $s);
  if ($n < count($fldsA))
    return $fldsA[$n];
  return '';
}

# IsPosNegIntStr
# --------------
# Tests whether a string holds either a positive or negative integer. Returns 0 or 1.
# For just positive could use regex /^\d+$/ but better is to use ctype_digit()
# For just negative could use regex /^-\d+$/ or this fn and then test for < 0
function IsPosNegIntStr($s) {
  return preg_match('/^-{0,1}\d+$/', $s);
}

# IntStr($s)
# ----------
# Return $s as an int if it numeric o'wise as passed
function IntStr($s) {
  if (is_numeric($s))
    return (int)$s;
  return $s;
}


# FormattedNumberToInt($n)
# ------------------------
# Converts enclosing ()s to leading -
# Strips thou seps
# returns int n if valid +- int
# Otherwise returns false
function FormattedNumberToInt($n) {
  if ($n) {
    if (is_string($n)) {
      if ($n[0] === '(') {
        if (substr($n, -1) !== ')')  return false;
        $n = '-'.substr($n, 1, -1);
      }
      $n = str_replace(IS_ThouSep, '', $n);
      if (IsPosNegIntStr($n)) return (int)$n;
      return false;
    }
    if (is_int($n)) return $n;
    return false;
  }
  return 0;
}

/* Contains() Replaced by InStr() with argument order reversed 11.10.12
# Returns true/false re whether haystack contains needle
function Contains($haystack, $needle) {
  return strpos($haystack, $needle) !== false;
} */

# InStr($needle, $haystack)
# -------------------------
# Returns true/false for needle being in haystack, case sensitive
function InStr($needle, $haystack) {
  return strpos($haystack, $needle) !== false;
}

# InRange
# -------
# Returns true/false for $v being in the range $a to $b
function InRange($v, $a, $b) {
  return $v >= $a && $v <= $b;
}

# ZeroToEmpty
# -----------
# Returns '' if $v === 0 o'wise $v
function ZeroToEmpty($v) {
  return ($v === 0) ? '' : $v;
}

# PluralWord
# ----------
# Returns $word or "{$word}s" depending on whether $n is > 1 or not
function PluralWord($n, $word) {
  return $n>1 ? "{$word}s" : $word;
}

# NumPluralWord($n, $word)
# ------------------------
# Returns "$n $word" or "$n {$word}s" depending on whether $n is > 1 or not
function NumPluralWord($n, $word) {
  return $n>1 ? "$n {$word}s" : "$n $word";
}

# CsListToIntA($csList)
# Returns an Int CS List as an array of ints
function CsListToIntA($csList) {
  $aA = [];
  foreach (explode(',', $csList) as $v)
    $aA[] = (int)$v;
  return $aA;
}

# ArrayToBracketedCsList($aA)
# Returns $aA as a bracketed CS list e.g. $aA = [1,2,3] -> "(1,2,3)"
function ArrayToBracketedCsList($aA) {
  return '('.implode(',',$aA).')';
}

# ArrayOrIntToStr($aA)
# Returns $aA as a string of either an int or array e.g. "123" or "[1,2,3]"
function ArrayOrIntToStr($aA) {
  if (is_array($aA)) return '['.implode(',', $aA).']';
  if (is_int($aA)) return $aA;
  if (is_null($aA)) return 'null';
  #Dump('ArrayOrIntToStr', $aA);
  return $aA;
}

# IntegeriseA(&$AR)
# Converts any array elements of $AR which are numeric strings to ints, useful for an array retrieved from DB
# NB: Should not be used if the numeric values could be anything other than ints.
function IntegeriseA(&$AR) {
  foreach ($AR as &$valR)
    if (is_string($valR) && is_numeric($valR))
      $valR = (int)$valR;
}

# Debug fns
function Dump($what, $var) {
  echo $what,' = ';
  var_dump($var);
  echo '<br>';
}

function DumpExport($what, $var) {
  echo $what,' = ',str_replace(array(' ',NL), array('&nbsp;','<br>'), var_export($var, true)) , '<br>';
#echo $what,' = ', var_export($var, true) . '<br>';
# $s = var_export($var, true);
# $s = str_replace(array(NL,'  ',',)',' ('), array('','',')','('), $s);
# echo $what,' = ',$s, '<br>';
}

function DumpLog($what, $var) {
  LogIt($what.' = '.var_export($var, true));
}
