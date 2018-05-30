<?php /* Copyright 2011-2013 Braiins Ltd

BuildTxsETsESs.php

Build
Taxonomies
EntityTypes
EntitySizes

tables.

History:
18.04.13 Written
03.07.13 B R L -> SIM, UK-IFRS-DPL added

*/
require 'BaseSIM.inc';

Head('Build Tx ETs ESs Tables', true);

echo "<br><br><b>Building the SIM Taxonomies table</b><br>";
/*
################
## Taxonomies ## Taxonomies supported by Braiins
################
CREATE TABLE IF NOT EXISTS Taxonomies (
  Id      tinyint unsigned not null auto_increment, # = TaxnId to keep usage clear re use of TxId with Bros
  Name    varchar(20)      not null, # Name of Taxonomy as used in short form, no spaces e.g. UK-GAAP-DPL. Verbose name is held in Descr
  Bits    tinyint unsigned not null, # Bit settings for the Taxonomy
  Data    text             not null, # The jsonised data as below, retrived via EntityType($o) as an Object
  Primary Key (Id)
) Engine = InnoDB DEFAULT CHARSET=utf8;

Max length Name = 11

Bits
  TaxnB_DB      = 1; # 0 DB for the Taxonomy is Live

Data =
  Descr         string  Description of the Taxonomy
  EntityTypesA  [] of ETypeIds which apply to the Taxonomy
  HeadingsAA    AA of Braiins Master Headings    for the Taxonomy
  PreferencesAA AA of Braiins Master Preferences for the Taxonomy

Initial values:
  HeadingsAA = [
    'AccountsFullUnauditedH'     => 'Report of the Directors and Unaudited Financial Statements',
    'AccountsFullAuditedH'       => 'Report of the Directors and Audited Financial Statements',
    'AccountsShortH'             => 'Financial Statements',
    'AccountsPeriodH'            => 'Period of Accounts',
    'CompanyInformationH'        => 'Company Information',
    'CompanyRegistrationNumberH' => 'Company Registration Number',
    'RegisteredOfficeH'          => 'Registered Office Address',
    'RestatedH'                  => '(Restated)'
  ]
  PreferencesAA = [
    'RealZero'  => '&#8211;', # en dash
    'Undefined' => '',        # for a false balance
    'ThisYearColCss' => 'b',
    'PriorYearColCss' => ''
  ]
*/

#    0      1             2       3
# Name | Bits | EntityTypes | Descr
$rowsS = '
UK-GAAP
UK-GAAP-DPL|1|5,6,7,8,9|2009 UK-GAAP with 2013 HMRC Detailed Profit & Loss
UK-IFRS-DPL|1|5|2009 UK-IFRS with 2013 HMRC Detailed Profit & Loss
';
$rowsA = explode(NL, substr($rowsS, 1, -1));

$maxLenName = 0;
$DB->StQuery("Truncate Taxonomies");
$DB->autocommit(false);
foreach ($rowsA as $rowi => $row) {
  $colsA = explode('|', $row);
  $name         = trim($colsA[0]);
  $bits         = isset($colsA[1]) ? (int)$colsA[1] : 0;
  $entityTypesA = isset($colsA[2]) ? explode(COM, trim($colsA[2])) : [];
  $descr        = isset($colsA[3]) ? trim($colsA[3]) : '';
  $maxLenName   =  max($maxLenName,  strlen($name));
  if ($bits) {
    $HeadingsAA = [
      'AccountsFullUnauditedH'     => 'Report of the Directors and Unaudited Financial Statements',
      'AccountsFullAuditedH'       => 'Report of the Directors and Audited Financial Statements',
      'AccountsShortH'             => 'Financial Statements',
      'AccountsPeriodH'            => 'Period of Accounts',
      'CompanyInformationH'        => 'Company Information',
      'CompanyRegistrationNumberH' => 'Company Registration Number',
      'RegisteredOfficeH'          => 'Registered Office Address',
      'RestatedH'                  => '(Restated)'
    ];
    $PreferencesAA = [
      'RealZero'  => '&#8211;', # en dash
      'Undefined' => '',        # for a false balance
      'ThisYearColCss' => 'b',
      'PriorYearColCss' => ''
    ];
  }else
    $HeadingsAA = $PreferencesAA = [];
  $dataA = [
    $entityTypesA,
    $HeadingsAA,
    $PreferencesAA,
    $descr
  ];
  $DB->StQuery("Insert into Taxonomies Set Name='$name',Bits=$bits,Data='".json_encode($dataA, JSON_NUMERIC_CHECK).SQ);
  echo "$name<br>";
}
$DB->commit();
echo "<br>Done<br>Max length Name = $maxLenName<br>";

echo "<br><b>Building the SIM EntityTypes table</b><br>";
/*
#################
## EntityTypes ## Entity Types
#################
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

# Entities.ETypeN Enums         Base Credits for creating a New Entity
/*nst ET_Sole             = 1; #  1 Sole Proprietorship/Individual
const ET_Partnership      = 2; #  2 Partnership
const ET_LLP              = 3; #  2 Limited Liability Partnership
const ET_Charity          = 4; #  4 Charity
const ET_PrivateLtdCo     = 5; #  2 Private Limited Company
const ET_PrivateUnltdCo   = 6; #  2 Private Unlimited Company
const ET_PrivateLtdGuarCo = 7; #  2 Private Limited by Guarantee Company
const ET_CommInterestCo   = 8; #  2 Community Interest Company
const ET_PLC              = 9; # 30 Public Limited Company
const ET_Other           = 10; #  5 Other
const ET_Num             = 10;

$EntityTypeCreditsA = [0,1,2,2,4,2,2,2,2,30,5];
*/

# 25.04.13 Switched to use of a jsonised array with Generic, Ident, VatNum terms added
#    0       1         2      3              4              5          6           7       8          9        10      11       12       13       14        15        16         17
# Name | SName | Credits | Bits | ChairmanName | OfficerNames | CeoNames | CosecName | Sizes | Comments | Generic | Ident | TaxNum | VatNum | SIdent | STaxNum | SVatNum | IdentURL
$rowsS = '
Sole Proprietorship/Individual|Sole Trader    |1|0|Proprietor   |Proprietor        |Proprietor   ||||Proprietorship|Registration Number|CT Number|VAT Number
Partnership                   |Partnership    |2|0|Chief Partner|Partner,Partners  |Chief Partner||||Partnership|Registration Number|CT Number|VAT Number
Limited Liability Partnership |LLP Partnership|2|0|Chief Partner|Partner,Partners  |Chief Partner||||Partnership|Registration Number|CT Number|VAT Number
Charity                       |Charity        |4|3|Chairman     |Director,Directors|Managing Director,Managing Directors,Joint Managing Director|Company Secretary|||Company|Registered Charity Number|CT Number|VAT Number|RCN|CTN|VN|http://www.charity-commission.gov.uk/
Private Limited Company       |Private Company|2|3|Chairman     |Director,Directors|Managing Director,Managing Directors,Joint Managing Director|Company Secretary|1,2,3,4|UK-GAAP specific with Small FRSSE size included|Company|Company Registration Number|CT Number|VAT Number|CRN|CTN|VN|http://www.companieshouse.gov.uk/
Private Unlimited Company     |PUC            |2|3|Chairman     |Director,Directors|Managing Director,Managing Directors,Joint Managing Director|Company Secretary|1,2,3,4|UK-GAAP specific with Small FRSSE size included|Company|Company Registration Number|CT Number|VAT Number|CRN|CTN|VN|http://www.companieshouse.gov.uk/
Private Limited by Guarantee Company|PLGC     |2|3|Chairman     |Director,Directors|Managing Director,Managing Directors,Joint Managing Director|Company Secretary|1,2,3,4|UK-GAAP specific with Small FRSSE size included|Company|Company Registration Number|CT Number|VAT Number|CRN|CTN|VN|http://www.companieshouse.gov.uk/
Community Interest Company    |CIC            |2|3|Chairman     |Director,Directors|Managing Director,Managing Directors,Joint Managing Director|Company Secretary|1,2,3,4|UK-GAAP specific with Small FRSSE size included|Company|Company Registration Number|CT Number|VAT Number|CRN|CTN|VN|http://www.companieshouse.gov.uk/
Public Limited Company        |PLC           |30|3|Chairman     |Director,Directors|Chief Executuve Officer,Chief Executuve Officers,Joint Chief Executuve Officer|Company Secretary|4||Company|Company Registration Number|CT Number|VAT Number|CRN|CTN|VN|http://www.companieshouse.gov.uk/
';
$rowsA = explode(NL, substr($rowsS, 1, -1));

$maxLenData = 0;
$DB->StQuery("Truncate EntityTypes");
$DB->autocommit(false);
foreach ($rowsA as $rowi => $row) {
  $colsA = explode('|', $row);
  $name    = trim($colsA[0]);
  $sName   = trim($colsA[1]);
  $credits = (int)$colsA[2];
  $bits    = (int)$colsA[3];
  $cName   = trim($colsA[4]);
  $oNames  = trim($colsA[5]);
  $ceoNames  = trim($colsA[6]);
  $cosec     = trim($colsA[7]) ? : 0;
  $sizes     = trim($colsA[8]);
  $comment   = trim($colsA[9]) ? : 0;
  $generic   = trim($colsA[10]);
  $ident     = trim($colsA[11]);
  $taxNum    = trim($colsA[12]);
  $vatNum    = trim($colsA[13]);
  $sIdent    = isset($colsA[14]) ? trim($colsA[14]) : 0;
  $staxNum   = isset($colsA[15]) ? trim($colsA[15]) : 0;
  $svatNum   = isset($colsA[16]) ? trim($colsA[16]) : 0;
  $IdentURL  = isset($colsA[17]) ? trim($colsA[17]) : 0;

  $sizesA    = explode(COM, $sizes);
  $Officer   = StrField($oNames, COM, 0);
  $Officers  = StrField($oNames, COM, 1) ? : 0;
  $Ceo       = StrField($ceoNames, COM, 0);
  $Ceos      = StrField($ceoNames, COM, 1) ? : 0;
  $CeoJoint  = StrField($ceoNames, COM, 2) ? : 0;
  $DataA = [
    $credits,  # Credits     int      Credits for the Entity Type - one set of financial statement credits charge
    $bits,     # Bits        int      Bit settings for the Entity Type: ETB_Incorporated, ETB_CoSecDirRpt
    $name,     # Name        string   Full name of entity type
    $sName,    # SName       string   Short name of entity type
    $IdentURL, # IdentURL    string   Identifier Scheme URL
    [CTRY_UK], # CtryIdsA    int i [] of CtryIds for Countries to which this Entity Type applies
    [],        # TaxnIdsA    int i [] of TaxnIds for Taxonomies the Entity Type applies to; 0 not defined yet. Should cross check with Taxonomies.EntityTypes
    $sizesA,   # SizeIdsA    int i [] of ESizeIds which apply to the Entity Type; 0 = none
    $comment,  # Comment     string   Comment
    # Terms
    $generic,  # Generic     string   Generic name for the entity type e.g. Proprietorship, Partnership, Company
    $cName,    # Chairman    string   Chairman name e.g. Chairman or Chief Partner; no plural
    $Officer,  # Officer     string   Singular Officer name e.g. Director
    $Officers, # Officers    string   Plural Officer names e.g. Directors
    $Ceo,      # Ceo         string   Singular CEO name
    $Ceos,     # Ceos        string   Plural CEO names
    $CeoJoint, # CeoJoint    string   One of two CEO names e.g. Joint Managing Director
    $cosec,    # CoSec       string   Name for Company Secretary; null = no such role
    $ident,    # Ident       string   Name for entity context identifier e.g. registration number e.g. Company Registration Number, Australian Business Number
    $sIdent,   # SIdent      string   Short Name for entity context identifier e.g. ABN
    $taxNum,   # TaxNum      string   Name for entity corporate/income tax registration number e.g. Corporate Tax Number, Business Tax File Number
    $staxNum,  # STaxNum     string   Short Name for entity corporate/income tax registration number e.g. CTN, TFN
    $vatNum,   # VatNum      string   Name for entity VAT/GST/ST registration number e.g. VAT Number, GST Number
    $svatNum   # SVatNum     string   Short Name for entity registration number e.g. VN, GTN
  ];
  $DB->StQuery("Insert into EntityTypes Set Data='".json_encode($DataA, JSON_NUMERIC_CHECK).SQ);
  echo "$name<br>";
}
# Set EntityTypes.Data TaxnIdsA
$res = $DB->ResQuery('Select Id,Data From Taxonomies Where Bits>0');
while ($o = $res->fetch_object()) {
  $TaxnId = (int)$o->Id;
  $dataA = json_decode($o->Data);
  foreach ($dataA[0] as $ETypeId) {
    $etA = json_decode($DB->StrOneQuery("Select Data from EntityTypes Where Id=$ETypeId"));
    $etA[ETI_TaxnIdsA][] = $TaxnId;
    $DB->StQuery(sprintf("Update EntityTypes Set Data='%s' Where Id=%d", json_encode($etA, JSON_NUMERIC_CHECK), $ETypeId));
  }
}
$res->free();
$DB->commit();
echo "<br>Done<br>";

/*
{"Credits":2,"Bits":3,"Name":"Private Limited Company","SName":"Private Company","Generic":"Company","Chairman":"Chairman","Officer":"Director","Ceo":"Managing Director","Ident":"Company Registration Number","TaxNum":"CT Number","VatNum":"VAT Number","SizeIdsA":[1,2,3,4],"Comment":"UK specific with Small FRSSE size included","Officers":"Directors","Ceos":"Managing Directors","CeoJoint":"Joint Managing Director","CoSec":"Company Secretary","SIdent":"CRN","STaxNum":"CTN","SVatNum":"VN"}
{"Credits":2,"Bits":3,"Name":"Private Limited Company","SName":"Private Company","Generic":"Company","Chairman":"Chairman","Officer":"Director","Ceo":"Managing Director","Ident":"Company Registration Number","TaxNum":"CT Number","VatNum":"VAT Number","SizeIdsA":[1,2,3,4],"Comment":"UK specific with Small FRSSE size included","Officers":"Directors","Ceos":"Managing Directors","CeoJoint":"Joint Managing Director","CoSec":"Company Secretary","SIdent":"CRN","STaxNum":"CTN","SVatNum":"VN","TaxnIdsA":[2]}
{"Credits":2,"Bits":3,"Name":"Private Limited Company","SName":"Private Company","Generic":"Company","Chairman":"Chairman","Officer":"Director","Ceo":"Managing Director","Ident":"Company Registration Number","TaxNum":"CT Number","VatNum":"VAT Number","IdentURL":"http://www.companieshouse.gov.uk/","SizeIdsA":[1,2,3,4],"Comment":"UK-GAAP specific with Small FRSSE size included","Officers":"Directors","Ceos":"Managing Directors","CeoJoint":"Joint Managing Director","CoSec":"Company Secretary","SIdent":"CRN","STaxNum":"CTN","SVatNum":"VN","TaxnIdsA":[2]}
*/



echo "<br><b>Building the SIM EntitySizes table</b><br>";
/*
#################
## EntitySizes ## Entity Sizes
#################
CREATE TABLE IF NOT EXISTS EntitySizes (
  Id        tinyint unsigned not null auto_increment, # = ESizeId
  Name      varchar(40)      not null, # Full name of entity size   e.g. Small FRSSE
  SName     varchar(20)      not null, # Short name of entity size  e.g. FRSSE
  Credits   tinyint unsigned not null, # Credits for the Entity Size - in addition to the Entity Type Credits
  Bits      tinyint unsigned not null, # Bit settings for the Entity Size
  Comment   varchar(250)         null, # Comment free text
  Primary Key (Id)
) Engine = InnoDB DEFAULT CHARSET=utf8;

Max length Name = 11
Max length SName = 6
Max length Comment = 16

# Entity Size Enums
# -----------------
const ES_Small      = 1; # /- UK GAAP
const ES_SmallFRSSE = 2; # |
const ES_Medium     = 3; # |
const ES_Large      = 4; # |
const ES_IFRS_SME   = 5; # IFRS
                       # 1 2 3  4 5
$EntitySizeCreditsA     = [0,0,0,5,10,0];
*/

#    0       1         2      3         4
# Name | SName | Credits | Bits | Comment
$rowsS = '
Small              |Small  |0|0|
Small FRSSE Applied|FRSSE  |0|0|UK-GAAP Specific
Medium             |Medium |5|0|
Large              |Large |10|0|
';
$rowsA = explode(NL, substr($rowsS, 1, -1));

$maxLenName = $maxLenSName = $maxLenComment = 0;
$DB->StQuery("Truncate EntitySizes");
$DB->autocommit(false);
foreach ($rowsA as $rowi => $row) {
  $colsA = explode('|', $row);
  $name    = trim($colsA[0]);
  $sName   = trim($colsA[1]);
  $credits = (int)$colsA[2];
  $bits    = (int)$colsA[3];
  $comment = trim($colsA[4]);
  $maxLenName =  max($maxLenName,  strlen($name));
  $maxLenSName = max($maxLenSName, strlen($sName));
  $set = "Name='$name',SName='$sName',Credits=$credits,Bits=$bits";
  if ($comment) {
    $set .= ",Comment='$comment'";
    $maxLenComment = max($maxLenComment, strlen($comment));
  }
  $DB->StQuery("Insert into EntitySizes Set $set");
  echo "$name<br>";
}
$DB->commit();
echo "<br>Done<br>Max length Name = $maxLenName<br>
Max length SName = $maxLenSName<br>
Max length Comment   = $maxLenComment<br>";

Footer();
