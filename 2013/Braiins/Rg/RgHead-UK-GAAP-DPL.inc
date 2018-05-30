<?php /* Copyright 2011-2013 Braiins Ltd

UK-GAAP-DPL Accounts head elements

A4   210 mm x 297 mm   8.27 in x 11.69 in   595 pt x 842 pt

<meta http-equiv="content-type" content="text/html; charset=utf-8" />

$HeadStartS = "<!DOCTYPE HTML>
<html>
*/
if ($AppN == BDT_CFS) # Content for separate CFS (Current entity Financial Statements) tab Main div in conjunction with CFS.css
  $P2LinesA = array("<style type='text/css'>
h1 {font-size:120%;color:#000;margin:0 0 .5em}
h2 {font-size:115%;color:#000;margin:0 0 .3em}
h3 {font-size:110%;color:#000;margin:0 0 .2em}
h4 {font-size:105%;color:#000}");
else{ # Not BDT_CFS = stand alone xhtml
  $title = "$entityNameS " . DateStr((int)Data(BroId_Dates_YearEndDate), DATE_Year);
  $P2LinesA = array("<?xml version='1.0' encoding='utf-8'?>
<html
xmlns='http://www.w3.org/1999/xhtml'
xmlns:iso4217='http://www.xbrl.org/2003/iso4217'
xmlns:ix='http://www.xbrl.org/2008/inlineXBRL'
xmlns:ixt='http://www.xbrl.org/inlineXBRL/transformation/2010-04-20'
xmlns:link='http://www.xbrl.org/2003/linkbase'
xmlns:uk-bus='http://www.xbrl.org/uk/cd/business/2009-09-01'
xmlns:uk-countries='http://www.xbrl.org/uk/cd/countries/2009-09-01'
xmlns:uk-direp='http://www.xbrl.org/uk/reports/direp/2009-09-01'
xmlns:uk-gaap='http://www.xbrl.org/uk/gaap/core/2009-09-01'
xmlns:xbrldi='http://xbrl.org/2006/xbrldi'
xmlns:xbrli='http://www.xbrl.org/2003/instance'
xmlns:xlink='http://www.w3.org/1999/xlink'>
<head>
<meta http-equiv='Content-Type' content='application/xhtml+xml; charset=UTF-8'/>
<title>$title</title>
<style type='text/css'>
* {margin:0;padding:0}
body {font:normal 11pt Verdana,Arial,sans-serif;color:#000;background:#fff;padding:8px}
p {margin:0 0 1em}
h1 {font-size:120%;color:#000;margin:0 0 .5em}
h2 {font-size:115%;color:#000;margin:0 0 .3em}
h3 {font-size:110%;color:#000;margin:0 0 .2em}
h4 {font-size:105%;color:#000}");
}
$P2LinesA[] = '.page {width:550pt;margin-left:auto;margin-right:auto;padding:0.5em 1em;border:1px solid #ccc}
.break{page-break-before:always}
.hide{display:none}
.mc{margin-left:auto;margin-right:auto}
.b {font-weight:bold}
.nb{font-weight:normal}
.c {text-align:center}
.r {text-align:right}
.i {font-style:italic}
.L {font-size:1.2em}
.s {font-size:0.8em}
.u {text-decoration:underline}
.pt10{padding-top:1em}
.m0{margin:0}
.w100{width:10em}
.asp1{border-top:1em solid #fff}
.asb{border-top:1px solid #000}
.adb{border-top:3px double #000}
.bsb{border-bottom:1px solid #000}
.bdb{border-bottom:3px double #000}
table{font-size:1em;border-collapse:collapse;margin:0 0 1em}
td   {vertical-align:top}
td.t {padding-right:.5em}
td.n {padding-right:.5em}
td.y {text-align:right}
td.yk{width:.8em}
td.pl{padding-left:1em}
</style>';
# Start $Header which in the BDT_CFS case will be discarded, but still go through the process re $RunMsg
$Header = $AppN == BDT_CFS ? '' : "</head>
<body>
<div class='hide'>
<ix:header>
<ix:hidden>
";
/*
4.16  Compulsory tags
=====================
RULE:  The following tags MUST be used in XBRL accounts to aid in processing:
* 1.  Entity current legal or registered name
* 2.  Companies House registered number (where applicable)
* 3.  Company dormant / not dormant
* 4.  Company trading / not trading
* 5.  Start date for period covered by report
* 6.  End date for period covered by report
* 7.  Directors report date of signing
* 8.  Directors signing directors report
* 9.  Balance sheet date
* 10.  Balance sheet date of approval
  11.  Balance sheet approving directors / authority
  12.  All declarations under ‘Statements in Directors’ Report’ and ‘Directors' or entity's declarations’, which apply to the entity concerned.

Software should also automatically add tags to an XBRL reports as follows:

13.  Software SHOULD generate an occurrence of the tuple ‘XBRL document author’ containing the name and version of software tagged with the ‘Name of author’ tag and the text ‘Software’ tagged with the ‘Description or title of author’ tag.
*/
$Year = 0;
if ($FTypeN==RGFT_Stat) {
  AddHiddenTagIfNotUsed(BroId_Info_EntityName, 'Entity Name');
  AddHiddenTagIfNotUsed(BroId_Info_RegistrationNumber, 'Registration Number');
  AddHiddenTag(BroId_Info_Dormant, 'Dormant Status');
  AddHiddenTag(BroId_Info_Trading, 'Trading Status');
  AddHiddenTag(BroId_Info_IncorporationCountry, 'Incorporation Country');
  AddHiddenTagIfNotUsed(BroId_Dates_YearStartDate, 'Year Start Date');
  AddHiddenTagIfNotUsed(BroId_Dates_YearEndDate,   'Year End Date');
  AddHiddenTagIfNotUsed(BroId_Dates_SigningDirectorsReport, 'Date Signing Directors Report');
  # NameDirectorSigningAccounts, possibly multiple ones, plus DirectorSigningReport
  $P2LinesA[] = trim($signingHiddenTags);
  AddHiddenTagIfNotUsed(BroId_Dates_BS, 'Balance Sheet Date');
  AddHiddenTagIfNotUsed(BroId_Dates_ApprovalAccounts, 'Accounts Approval Date');
  AddHiddenTag(BroId_Statements_NoOtherGainsOrLosses,'No Other Gains Or Losses Statement');
}
# The Tuples
# Braiins convention for the tupleId: T.n.i.Y#
# where n  = running number for tuples in the FS - same for tuples with same name /- same for diff years
#       i  = instance number                                                      |
#       Y# = Y and year digit as used in contexts
# <ix:tuple name="uk-direp:SpecificEUPoliticalDonationGrouping" tupleID="T.n.i.Y#"/>
# $TuplesA [TupId][TupleID] = 1 where TupleID = "T.n.i.Y#" where n is running tuple use # and i is the instance
foreach ($TuplesA as $tupId => $tupxIdsA)
  foreach ($tupxIdsA as $tupxID => $t)
    $Header .= "<ix:tuple name='$TupFullNamesA[$tupId]' tupleID='$tupxID'/>
";
$v = BRAIINS_RG_VERSION;
$Header .=  "<ix:tuple name='uk-bus:XBRLDocumentAuthorGrouping' tupleID='AuthorTuple'>
<ix:nonNumeric name='uk-bus:NameAuthor' contextRef='PeriodY0' order='1'>$v</ix:nonNumeric>
<ix:nonNumeric name='uk-bus:DescriptionOrTitleAuthor' contextRef='PeriodY0' order='2'>Braiins Limited</ix:nonNumeric>
<ix:nonNumeric name='uk-bus:PrincipleDocumentAuthor' contextRef='PeriodY0' order='3'>true</ix:nonNumeric>
</ix:tuple>
</ix:hidden>
<ix:references>
<link:schemaRef xlink:type='simple' xlink:href='http://www.xbrl.org/uk/gaap/core/2009-09-01/uk-gaap-full-2009-09-01.xsd'/>
</ix:references>
<ix:resources>
";

# The Contexts
# ============
#ksort($ContextsA);
$regNum = Data(BroId_Info_RegistrationNumber);
$yearDatesA = array(); # for years 0-2 [start][end]
for ($year=0; $year<3; ++$year) {
  $yearDatesA[$year][0] = DateStr((int)Data(BroId_Dates_YearStartDate, $year), DATE_iXBRL);
  $yearDatesA[$year][1] = DateStr((int)Data(BroId_Dates_YearEndDate, $year),   DATE_iXBRL);
}
#Dump('ContextsA', $ContextsA);
foreach ($ContextsA as $ref => $contextA) { # $contextA = [CI_ContextN, CI_Year, CI_DiMeIdsA]
  $con = "<xbrli:context id='$ref'>
  <xbrli:entity>
    <xbrli:identifier scheme='http://www.companieshouse.gov.uk/'>$regNum</xbrli:identifier>
";
  $startYearB = $endYearB = true; # default for most cases
  if (strpos($ref, 'StartY'))     # djh?? Or pass info via $contextA?
    $endYearB = false;
  elseif (strpos($ref, 'EndY'))
    $startYearB = false;
  $year      = $contextA[CI_Year];
  $diMeIdsA  = $contextA[CI_DiMeIdsA];
  $exps      = 0; # possible array of explicit dimensions by DiMeId
  switch ($contextA[CI_ContextN]) { # contextN
    case RGC_Period:      break;
    case RGC_Officer:     $manDiMeId = array_shift($diMeIdsA);
                          $exps = array($manDiMeId); # DiMeId for Officers_Director1 etc
                          switch ($DiMesRA[$manDiMeId][DiMeRI_MType]) { # DiMeRI_MType = DiMe M# Type number 0-5, applicable when the DiMeB_M Bit is set
                            case 0; break; # CoSec
                            case MType_Director:
                              $propBits = $PropBitsA[$manDiMeId];
                              if ($propBits & OTB_Chairman)      $exps = array(DiMeId_Chairman); # replacing Director1 etc
                              if ($propBits & OTB_CEO)           $exps = array(DiMeId_CEO);
                              if ($propBits & OTB_ChairmanCEO)   $exps = array(DiMeId_ChairmanCEO);
                              if ($propBits & OTB_CoSecDirector) $exps = array(DiMeId_CoSecDirector);
                              # Not OTB_HighestPaidDirector which is handled in Tag() with ContextN set to RGC_HighestPaidDirector
                              if ($propBits & OTB_Executive)     $exps[] = DiMeId_ExecutiveOfficer;
                              if ($propBits & OTB_Nonexec)       $exps[] = DiMeId_NonexecOfficer;
                              break;
                            case MType_PartnerLLP: break;
                          }
                          break;
    case RGC_AllOfficers: $exps = array(DiMeId_AllOfficers); break;
    case RGC_RegOffice:   $exps = array(DiMeId_RegOffice); break;
    case RGC_TPA:         $manDiMeId = array_shift($diMeIdsA);
                          $exps = array($manDiMeId); break;
    case RGC_UkCountry:   $exps = array($CountryDiMeId); break;
    case RGC_HighestPaidDirector:
                          $exps = array(DiMeId_HighestPaidDirector); break;
  }
  if ($diMeIdsA)
    foreach ($diMeIdsA as $diMeId)
      if ($exps)
        $exps[] = $diMeId;
      else
        $exps = array($diMeId);
  if ($exps) {
    $con .= '    <xbrli:segment>
';
    foreach ($exps as $diMeId) {
      $diMeA = $DiMesRA[$diMeId]; # DiMeRI_DimId, DiMeRI_Bits, DiMeRI_MType, DiMeRI_NsId, DiMeRI_DiMe_Name, DiMeRI_DimEleName, DiMeRI_DiMeEleName with 'Dimension' stripped from end of DiMeRI_DimEleName so put that back here.
      $ns    = $NamespacesRgA[$diMeA[DiMeRI_NsId]];
      $con  .= "       <xbrldi:explicitMember dimension='$ns:{$diMeA[DiMeRI_DimEleName]}Dimension'>$ns:{$diMeA[DiMeRI_DiMeEleName]}</xbrldi:explicitMember>
";
    }
    $con .= '    </xbrli:segment>
';
  }
  $con .= '  </xbrli:entity>
';
  if ($endYearB || $startYearB) {
    $con .= '  <xbrli:period>
';
    if ($startYearB && $endYearB)
      $con .= "    <xbrli:startDate>{$yearDatesA[$year][0]}</xbrli:startDate>
    <xbrli:endDate>{$yearDatesA[$year][1]}</xbrli:endDate>
";
    else
      $con .= '    <xbrli:instant>{$yearDatesA[$year][1]}</xbrli:instant>
'; # end assumed
    $con .= '  </xbrli:period>
';
  }
  $Header .= $con . '</xbrli:context>
';
}

# Add the units resources, end of the hidden div and the div to start the first page
$Header .= "<xbrli:unit id='GBP'><xbrli:measure>iso4217:GBP</xbrli:measure></xbrli:unit>
<xbrli:unit id='Pure'><xbrli:measure>xbrli:pure</xbrli:measure></xbrli:unit>
<xbrli:unit id='Shares'><xbrli:measure>xbrli:shares</xbrli:measure></xbrli:unit>
</ix:resources>
</ix:header>
</div>";
if ($AppN != BDT_CFS) # Not BDT_CFS = stand alone xhtml incl iXBRL hidden section
  $P2LinesA[] = $Header;
$P2LinesA[] = "<div class='page w'>";

function AddHiddenTagIfNotUsed($broId, $descr) {
  global $TagsA, $RunMsg;
  if (!isset($TagsA[$broId])) {
    $RunMsg .= "\n$descr has not been used in the accounts as expected. Tag added to hidden section.";
    AddHiddenTag($broId, $descr);
  }
}
function AddHiddenTag($broId, $descr) {
  global $Header, $BroInfoA, $RunMsg;
  if (!isset($BroInfoA[$broId])) {
    $RunMsg .= "\nData for $descr not found when adding hidden tags";
    return;
  }
  $dat = str_replace('&', '&amp;', Data($broId)); # wrong if data is &amp;
  switch ($BroInfoA[$broId][BroI_DataTypeN]) {
    case DT_Date:    $dat = DateStr((int)$dat, DATE_iXBRL); break;
    case DT_Boolean: $dat = ($dat ? 'true' : 'false'); break;
  }
  if ($dat)  # year 0 tag
    $Header .= Tag($broId, $dat).NL;
}

