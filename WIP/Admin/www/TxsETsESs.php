<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/TxsETsESs.php

Lists the SIM Taxonomies, EntityTypes, and EntitySizes Tables

History:
18.04.13 Written
03.07.13 B R L -> SIM

*/
require 'BaseSIM.inc';
require Com_Inc.'FuncsSIM.inc';

Head('SIM Txs ETs ESs', true);

/* Taxonomies
   ==========
CREATE TABLE IF NOT EXISTS Taxonomies (
  Id      tinyint unsigned not null auto_increment, # = TaxnId to keep usage clear re use of TxId with Bros
  Name    varchar(20)      not null, # Name of Taxonomy as used in short form, no spaces e.g. UK-GAAP-DPL. Verbose name is held in Descr
  Bits    tinyint unsigned not null, # Bit settings for the Taxonomy
  Data    text             not null, # The jsonised data as below, retrived via EntityType($o) as an Object
  Primary Key (Id)
) Engine = InnoDB DEFAULT CHARSET=utf8;

Bits
  TaxnB_DB       = 1; # 0 DB for the Taxonomy is Live

Data =
  0 EntityTypesA  [] of ETypeIds which apply to the Taxonomy
  1 HeadingsAA    AA of Braiins Master Headings    for the Taxonomy
  2 PreferencesAA AA of Braiins Master Preferences for the Taxonomy
  3 Descr         string  Description of the Taxonomy
*/
echo "<h1 class=c>SIM Taxonomies, EntityTypes, and EntitySizes</h1>
<h2 class=c>SIM Taxonomies</h2>
<table class=mc>
<tr class='b bg0 c'><td>Id</td><td>Name</td><td>Entity Types</td><td>DB Live</td><td>Has Masters for</td><td>Description</td></tr>
";
$res = $DB->ResQuery('Select * From Taxonomies Order by Id');
while ($o = $res->fetch_object()) {
  $id    = (int)$o->Id;
  $bits  = (int)$o->Bits;
  $dataA = json_decode($o->Data);
  $entityTypes = '';
  if ($dataA[TDI_EntityTypesA]) {
    foreach ($dataA[TDI_EntityTypesA] as $ETypeId)
      $entityTypes .= sprintf('<br>%d %s', $ETypeId, EntityTypeStr($ETypeId));
    $entityTypes = substr($entityTypes, 4);
  }
  $live = ($bits & TaxnB_DB) ? 'Yes' : '';
  $masters = '';
  if (count($dataA[TDI_HeadingsAA])) $masters = 'Headings<br>Preferences'; # Assumed to be both if one
  $descr = $dataA[TDI_Descr];
  echo "<tr><td class='c top'>$id</td><td class=top>$o->Name</td><td class=top>$entityTypes</td><td class='c top'>$live</td><td class='c top'>$masters</td><td class=top>$descr</td></tr>\n";
}
$res->free();
echo "</table>
";


/* EntityTypes
   ===========
CREATE TABLE IF NOT EXISTS EntityTypes (
  Id      tinyint  unsigned not null auto_increment, # = ETypeId
  Data    text              not null, # The jsonised data as below
  Primary Key (Id)
) Engine = InnoDB DEFAULT CHARSET=utf8;

Data =
  Credits     int      Credits for the Entity Type - one set of financial statement credits charge
  Bits        int      Bit settings for the Entity Type: ETB_Incorporated, ETB_CoSecDirRpt
  Name        string   Full name of entity type
  SName       string   Short name of entity type
  IdentURL    string   Identifier Scheme URL
  CtryIdsA    int i [] of CtryIds for Countries to which this Entity Type applies
  TaxnIdsA    int i [] of TaxnIds for Taxonomies the Entity Type applies to; 0 not defined yet. Should cross check with Taxonomies.EntityTypes
  SizeIdsA    int i [] of ESizeIds which apply to the Entity Type; 0 = none
  Comment     string   Comment
  # Terms
  Generic     string   Generic name for the entity type e.g. Proprietorship, Partnership, Company
  Chairman    string   Chairman name e.g. Chairman or Chief Partner; no plural
  Officer     string   Singular Officer name e.g. Director
  Officers    string   Plural Officer names e.g. Directors
  Ceo         string   Singular CEO name
  Ceos        string   Plural CEO names
  CeoJoint    string   One of two CEO names e.g. Joint Managing Director
  CoSec       string   Name for Company Secretary; null = no such role
  Ident       string   Name for entity context identifier e.g. registration number e.g. Company Registration Number, Australian Business Number
  SIdent      string   Short Name for entity context identifier e.g. ABN
  TaxNum      string   Name for entity corporate/income tax registration number e.g. Corporate Tax Number, Business Tax File Number
  STaxNum     string   Short Name for entity corporate/income tax registration number e.g. CTN, TFN
  VatNum      string   Name for entity VAT/GST/ST registration number e.g. VAT Number, GST Number
  SVatNum     string   Short Name for entity registration number e.g. VN, GTN

State tax numbers?

const ETI_Credits  = 0;
const ETI_Bits     = 1;
const ETI_Name     = 2;
const ETI_SName    = 3;
const ETI_IdentURL = 4;
const ETI_CtryIdsA = 5;
const ETI_TaxnIdsA = 6;
const ETI_SizeIdsA = 7;
const ETI_Comment  = 8;
# Terms
const ETI_Generic  =  9;
const ETI_Chairman = 10;
const ETI_Officer  = 11;
const ETI_Officers = 12;
const ETI_Ceo      = 13;
const ETI_Ceos     = 14;
const ETI_CeoJoint = 15;
const ETI_CoSec    = 16;
const ETI_Ident    = 17;
const ETI_SIdent   = 18;
const ETI_TaxNum   = 19;
const ETI_STaxNum  = 20;
const ETI_VatNum   = 21;
const ETI_SVatNum  = 22;
*/
echo "<h2 class=c>SIM Entity Types</h2>
<table class=mc>
";
$res = $DB->ResQuery('Select * From EntityTypes Order by Id');
$n = 0;
while ($o = $res->fetch_object()) {
  if ($n<1) {
    echo "<tr class='b bg0 c'><td rowspan=2>Id</td><td rowspan=2>Countries</td><td rowspan=2>Name</td><td rowspan=2>Short Name</td><td rowspan=2>Credits</td><td rowspan=2>Taxonomies</td><td rowspan=2>Sizes</td><td rowspan=2>Properties</td><td rowspan=2>Identifier Scheme URL</td><td colspan=2>Terms</td><td rowspan=2>Comments</td></tr>
<tr class='b bg0 c'><td>Term</td><td>For This Entity Type</td></td></tr>\n";
    $n = 50;
  }
  --$n;
  $id = (int)$o->Id;
  $dataA = json_decode($o->Data); # The EntityType Data
  $ctrys = $txs = $sizes = $props = '';
  if ($dataA[ETI_CtryIdsA]) {
    foreach ($dataA[ETI_CtryIdsA] as $CtryId)
      $ctrys .= BR.CountryShortName($CtryId);
    $ctrys = substr($ctrys, 4);
  }
  if ($dataA[ETI_TaxnIdsA]) {
    foreach ($dataA[ETI_TaxnIdsA] as $TaxnId)
      $txs .= BR.TaxnStr($TaxnId);
    $txs = substr($txs, 4);
  }
  $termsA = [
    [$dataA[ETI_Generic],  'Generic name for entity type'],
    [$dataA[ETI_Chairman], 'Chairman'],
    [$dataA[ETI_Officer],  'Officer singular']];
  if ($dataA[ETI_Officers]) $termsA[] = [$dataA[ETI_Officers], 'Officers plural'];
                            $termsA[] = [$dataA[ETI_Ceo],      'CEO singular'];
  if ($dataA[ETI_Ceos])     $termsA[] = [$dataA[ETI_Ceos],     'CEOs plural'];
  if ($dataA[ETI_CeoJoint]) $termsA[] = [$dataA[ETI_CeoJoint], 'CEO joint of 2'];
  if ($dataA[ETI_CoSec])    $termsA[] = [$dataA[ETI_CoSec],    'Company Secretary'];
                            $termsA[] = [$dataA[ETI_Ident],    'Context identifier'];      # Company Registration Number
  if ($dataA[ETI_SIdent])   $termsA[] = [$dataA[ETI_SIdent],   'Context identifier short'];# CRN
                            $termsA[] = [$dataA[ETI_TaxNum],   'Corporate/income tax number'];
  if ($dataA[ETI_STaxNum])  $termsA[] = [$dataA[ETI_STaxNum],  'Corporate/income tax number short'];
                            $termsA[] = [$dataA[ETI_VatNum],   'VAT/GST/ST number'];
  if ($dataA[ETI_SVatNum])  $termsA[] = [$dataA[ETI_SVatNum],  'VAT/GST/ST number short'];
  $numRows = count($termsA);
  $bits = $dataA[ETI_Bits];
  if ($dataA[ETI_SizeIdsA]) {
    foreach ($dataA[ETI_SizeIdsA] as $ESizeId)
      $sizes .= BR.EntitySizeStr($ESizeId);
    $sizes = substr($sizes, 4);
  }
  $ident   = $dataA[ETI_IdentURL] ? : '';
  $comment = $dataA[ETI_Comment]  ? : '';
  # Bits even if all off re Unincorporated
  # const ETB_Incorporated = 1; # 0 Set if Entity is an Incorporated type; Unincororated o'wise
  # const ETB_CoSecDirRpt  = 2; # 1 Set if CoSec can sign Directors' Report
  $props = BR.(($bits & ETB_Incorporated) ? 'Incorporated' : 'Unincorporated');
  if ($bits & ETB_CoSecDirRpt)  $props .= "<br>Company Secretary can sign Directors' Report";
  $props = substr($props, 4);
  echo "<tr><td class='c top' rowspan=$numRows>$id</td><td class='c top' rowspan=$numRows>$ctrys</td><td class=top rowspan=$numRows>{$dataA[ETI_Name]}</td><td class=top rowspan=$numRows>{$dataA[ETI_SName]}</td><td class='c top' rowspan=$numRows>{$dataA[ETI_Credits]}</td><td class=top rowspan=$numRows>$txs</td><td class=top rowspan=$numRows>$sizes</td><td class=top rowspan=$numRows>$props</td><td class=top rowspan=$numRows>$ident</td><td>{$termsA[0][1]}</td><td>{$termsA[0][0]}</td><td class=top rowspan=$numRows>$comment</td></tr>\n";
  for ($i=1; $i<$numRows; ++$i) {
    echo "<tr><td>{$termsA[$i][1]}</td><td>{$termsA[$i][0]}</td></tr>\n";
    --$n;
  }
}
$res->free();
echo "</table>
";

# EntitySizes
# ===========
echo "<h2 class=c>SIM Entity Sizes</h2>
<table class=mc>
<tr class='b bg0 c'><td>Id</td><td>Name</td><td>Short Name</td><td>Credits</td><td>Properties</td><td>Comments</td></tr>
";
$res = $DB->ResQuery('Select * From EntitySizes Order by Id');
while ($o = $res->fetch_object()) {
  $id   = (int)$o->Id;
  $bits = (int)$o->Bits;
  echo "<tr><td class=c>$id</td><td>$o->Name</td><td>$o->SName</td><td class=c>$o->Credits</td><td></td><td>$o->Comment</td></tr>\n";
}
$res->free();
echo "</table>
";

Footer(true,true);
exit;
