<?php /* Copyright 2011-2013 Braiins Ltd

/Com/inc/FuncsSIM.inc

SIM Funcs - DB

History:
25.04.13 Started
03.07.13 B R L -> SIM

string TaxnStr($id)          Returns the Name of the Taxonomy with Id = $id
string EntityTypeStr($id)    Returns the Name of the Entity Type with Id = $id
string EntitySizeStr($id)    Returns the Name of the Entity Size with Id = $id
string CountryShortName($id) Returns the Ref or ShortName of the Country with Id = $id
string DboFieldStr($n)       Returns the DboField Name for enum $n
string IdArraysStrViaFn($aA, $nA, $fn, $sep=BR) Used to convert two arrays, second one a 'Non' array, to string form via fn with $sep as the separator default BR

*/

# TaxnStr($id)
# ------
# Returns the Name of the Taxonomy with Id = $id
function TaxnStr($id) {
 #global $DB;
 #return $id ? $DB->StrOneQuery(sprintf('Select Name From %s.Taxonomies Where Id=%d', DB_SIM, $id)) : '';
  static $sSA = ['', 'UK-GAAP', 'UK-GAAP-DPL', 'UK-IFRS-DPL']; # /- Temporary speed up instead of the above until more Taxonomies are put to use
  return $sSA[(int)$id];                                       # |
}

# EntityTypeStr($id)
# -------------
# Returns the Name of the Entity Type with Id = $id
function EntityTypeStr($id) {
  global $DB;
  return $id ? json_decode($DB->StrOneQuery(sprintf('Select Data from %s.EntityTypes Where id=%d', DB_SIM, $id)))[ETI_Name] : '';
}

# EntitySizeStr($id)
# -------------
# Returns the Name of the Entity Size with Id = $id
function EntitySizeStr($id) {
  global $DB;
  return $id ? $DB->StrOneQuery(sprintf('Select Name from %s.EntitySizes Where id=%d',DB_SIM, $id)) : '';
}

# CountryShortName($id)
# Returns the short country Name (the Ref field) with Id = $id
function CountryShortName($id) {
  global $DB;
  if (!$id) return '';
  return $DB->StrOneQuery("Select Ref From Countries Where Id=$id");
}

# DboFieldStr($n)
# Returns the DboField Name for enum $n
function DboFieldStr($n) {
  static $sSA = ['', 'Entity.Ref', 'Entity.Name', 'Entity.Identifier', 'Entity.CtryId'];
  return $sSA[(int)$n];
}

# IdArraysStrViaFn($aA, $nA, $fn, $sep=BR)
# Used to convert two arrays, second one a 'Non' array, to string form via fn with $sep as the separator default BR
function IdArraysStrViaFn($aA, $nA, $fn, $sep=BR) {
  $str = '';
  if ($aA)
    foreach ($aA as $id)
      $str .= $sep.$id.SP.$fn($id);
  if ($nA)
    foreach ($nA as $id)
      $str .= $sep.'-'.$id.SP.$fn($id);
  if ($str) $str = substr($str, strlen($sep));
  return $str;
}

