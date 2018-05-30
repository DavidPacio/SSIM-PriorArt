<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/UK-IFRS-DPL/inc/BuildTxStructs.inc

Build the Taxonomy Based Structs

History:
29.06.13 Started based on UK-GAAP-DPL version

To Do djh??
-----

NameSpaces
##########
$NamespacesTxA  array NsId => [Prefix, Namespace]
$NamespacesRgA  array NsId => Prefix

Hypercubes
##########
$HyDimsA [HyId => Dimensions in chr list form]
$HyNamesA  [HyId => Hy Short Name] Names need to be unique. Flipped version $HyShortNamesMapA is used by BrosImport.php

Dimensions
##########
Map of Dimensions, and Dimension Members -> Dimension Member # Unique # for the Dimension and Dimension Member = DimensionMembers.Id. = DiMeId
So need map of DimensionShortName.MemberShortName -> DiMeId and info
Also need info the other way for building contexts.

$DiMeMapA             [DimShortName.DiMeShortName => DiMeId]  with default DiMes as just the DimShortName
$DiMesA               [DiMeId => DiMe Info array: [DimId, DiMeBits, PropSet, MuxList, SumList]
$DiMesRA              [DiMeId => DiMe Info array: [DimId, DiMeBits, PropSet, NsId, DimensionShortName_MemberShortName, DimensionName, MemberName] with 'Dimension' stripped from end of DimensionName to same space in struct. To be put back when building contexts.
$DimNamesA            [DimId  => DimName]
$DiMeNamesA           [DiMeId => DimShortName.DiMeShortName]  Could be obtained by flipping $DiMeMapA except for the DiMeShortNames missing in the default DiMe cases.
$DiMeLabelsA          [DiMeId => Dimension Member Std Labels stripped of ' [default]' used for building full dimension names as in the TB]
$DiMeSumTreesA        3 dimensional array of [DimId, [target DiMeId, [DiMeIds to sum]]] - used only by BroSummingTrees.php and here to generate $DiMeTargetsA
$DiMeTargetsA         [DiMeId => [target DiMeIds]] excludes the Restated DiMes
$RestatedDiMeTargetsA Version of $DiMeTargetsA covering just the Restated DiMes

Tuples
######
$TuMesA        TuMeId => TuMe Info array: [TupId, Ordr, TUCN] TuMeI_TupId, TuMeI_Ordr, TuMeI_TUCN
$TupNamesA     TupId  => TuShortName as used in BroRefs
$TupFullNamesA TupId  => Tx Name incl NS  for Tx Tuple Names used by Bros for use with <ix:tuple name=
$TupLabelsA    TupId  => Tuple Std Labels stripped of ' [grouping]' used for building full BroRefs as in the TB


Files in /Com/str/TxName/:

NamespacesTxA.inc $NamespacesTxA
NamespacesRgA.inc $NamespacesRgA

Hypercubes.inc    $HyDimsA  $HyNamesA

DiMesA.inc               $DiMesA
DiMesRA.inc              $DiMesRA
DimNamesA.inc            $DimNamesA
DiMeNamesA.inc           $DiMeNamesA
DiMeLabelsA.inc          $DiMeLabelsA
DiMeMapA.inc             $DiMeMapA
DiMeSumTreesA.inc        $DiMeSumTreesA
DiMeTargetsA.inc         $DiMeTargetsA  $RestatedDiMeTargetsA

TuMesA.inc               $TuMesA
TupNamesA.inc            $TupNamesA
TupFullNamesA.inc        $TupFullNamesA
TupLabelsA.inc           $TupLabelsA

*/

require '../inc/BuildStructsFuncs.inc';

function BuildTxBasedStructs() {
  global $DB, $diMeTargets1A, $diMeIdsA, $TxElementsSkippedA;
  echo "<br><b>Building Taxonomy Based Structs</b></br>\n";

# NameSpace Arrays
# ----------------
# $NamespacesTxA  array NsId => [Prefix, Namespace]
# $NamespacesRgA  array NsId => Prefix
$NamespacesTxAS = '$NamespacesTxA=[
0,
';
$NamespacesRgA  = [0];
$NamespacesRgAS = '$NamespacesRgA=[
0,
';
$res = $DB->ResQuery("Select * From Namespaces Order by Id");
while ($o = $res->fetch_object()) {
  $prefix = $o->Prefix;
  $ns     = $o->namespace;
  $NamespacesTxAS .= "['$prefix','$ns'],\n";
  $NamespacesRgA[] = $prefix;
  $NamespacesRgAS .= "'$prefix',\n";
}
FinishArrayString($NamespacesTxAS);
FinishArrayString($NamespacesRgAS);

file_put_contents(Com_Str_Tx.'NamespacesTxA.inc', '<?php
' . $NamespacesTxAS);

file_put_contents(Com_Str_Tx.'NamespacesRgA.inc', '<?php
' . $NamespacesRgAS);

echo 'Namespace Structs built.<br>';

# Hypercube Arrays
##################
/*
$HyDimsA=[0,
'13',         #  1
'1234678Z',   #  2
'123678Z',    #  3
'1235',       #  4
'123',        #  5
'136',        #  6
'137',        #  7
'138',        #  8
'139',        #  9
'139;<',      # 10
'139:',       # 11
'139F',       # 12
'139I',       # 13
'1234678:Z',  # 14
'1234678;<Z', # 15
'123:',       # 16
'123;<',      # 17
'13=',        # 18
'13>?@D[',    # 19
'13>BZ[',     # 20
'13A',        # 21
'13C',        # 22
'13D',        # 23
'123E',       # 24
'13F',        # 25
'13I',        # 26
'13G',        # 27
'13H',        # 28
'13RS',       # 29
'13JK',       # 30
'13L',        # 31
'13M',        # 32
'13N',        # 33
'23Z^_`ab',   # 34
'O',          # 35
'13PQ',       # 36
'13RS',       # 37
'1TWXYZ]',    # 38
'13UVWXYZ]',  # 39
'13Z',        # 40
'1[',         # 41
'1\\',        # 42
'1]',         # 43
''            # 44
]; */

# $HyDimsA [HyId => Dimensions in chr list form]
$HyDimsAS = '$HyDimsA=[0,'.NL;
$res = $DB->ResQuery('Select Id,Dimensions From Hypercubes Order by Id');
while ($o = $res->fetch_object())
  $HyDimsAS .= SQ.addslashes($o->Dimensions).SQ.COM.NL;
FinishArrayString($HyDimsAS);

# $HyNamesA  [HyId => Hy Short Name] Names need to be unique. Flipped version $HyShortNamesMapA is used by BrosImport.php
$HyNamesA = [0,
'Basic',              # 600 - Hypercube - Basic                 id=Dimensions-GeneralData
'Income',             # 610 - Hypercube - Income - Cash - Data  id=Dimensions-ContinuingAndDiscontinued
'BS',                 # 612 - Hypercube - Balance Sheet Data    id=Hypercube-Balance-Cash-Primary
'Equity',             # 620 - Hypercube - Equity                id=Dimensions-Equity
'ConDis',             # 625 - Hypercube - Continuing Discontinued Operations
'OpSegs',             # 630 - Hypercube - Operating Segments    id=Hypercube-Segments
'ProdServSegs',       # 631 - Hypercube - Product and Services Segments
'MajCustSegs',        # 632 - Hypercube - Major Customer Segments
'BizCombos',          # 640 - Hypercube - Business Combinations
'BizCombosIntan',     # 641 - Hypercube - Business Combinations - Intangibles
'BizCombosPPE',       # 642 - Hypercube - Business Combinations - PPE
'BizCombosProvs',     # 643 - Hypercube - Business Combinations - Provisions
'BizCombosContLiabs', # 644 - Hypercube - Business Combinations - Contingent Liabilities
'IncomePPE',          # 650 - Hypercube - PPE Income Items
'IncomeIntan',        # 651 - Hypercube - Intangibles Income Items
'PPEs',               # 660 - Hypercube - Property, Plant and Equipment id=Dimensions-PropertyPlantEquipment
'IFAs',               # 665 - Hypercube - Intangible Assets             id=Dimensions-IntangibleAssets
'ImpairAllow',        # 667 - Hypercube - Impairment Allowance Account
'FIs',                # 670 - Hypercube - Financial Instruments         id=Dimensions-FinancialInstruments
'FICRisk',            # 671 - Hypercube - Financial Instruments Concentration Risk
'FIMvts',             # 672 - Hypercube - Financial Instrument Movements
'CrRatings',          # 674 - Hypercube - Credit Ratings
'Maturities',         # 675 - Hypercube - Maturities
'DefTaxAssets',       # 680 - Hypercube - Deferred Tax Assets and Liabilities  id=Dimensions-DeferredTaxAssetsLiabilities
'Provs',              # 690 - Hypercube - Provisions             id=Dimensions-Provisions
'ContLiabs',          # 700 - Hypercube - Contingent Liabilities id=Dimensions-ContingentLiabilities
'Pensions',           # 730 - Hypercube - Pension Schemes        id=Dimensions-PensionSchemes
'SharePayment',       # 740 - Hypercube - Share-based Payment Schemes  id=Dimensions-Share-basedPaymentSchemes
'ShareDividend',      # 750 - Hypercube - Share Capital and Dividends
'RelParties',         # 760 - Hypercube - Related Parties
'Subsids',            # 770 - Hypercube - Subsidiaries
'Assocs',             # 780 - Hypercube - Associates
'JVs',                # 790 - Hypercube - Joint-Ventures
'DPL',                # 850 - Hypercube - Detailed Profit and Loss
'DataNot',            # 899 - Hypercube - Data Not For Use
'Officers',           # 900 - Hypercube - Entity Officers
'Shares',             # 901 - Hypercube - Shares
'ContactInfo',        # 905 - Hypercube - Entity Contact Info
'TPAs',               # 906 - Hypercube - Third Party Agents
'Countries',          # 910 - Hypercube - Countries
'Currencies',         # 911 - Hypercube - Currencies
'Exchanges',          # 912 - Hypercube - Stock Exchanges
'Languages',          # 913 - Hypercube - Languages
'Empty'               # 999 - Hypercube - Empty
];

$HyNamesAS = Build1DimensionaliArrayString('$HyNamesA=[', $HyNamesA);
file_put_contents(Com_Str_Tx.'Hypercubes.inc', '<?php'.NL.$HyDimsAS.$HyNamesAS);


# Dimension Arrays
# ----------------
# Strings for $DiMeMapA ...
$DiMeMapAS = '$DiMeMapA=['.NL;
$DiMesAS   = '$DiMesA=[0,'.NL;
#DiMesRAS  = '$DiMesRA=[0,'.NL
$DiMeNamesAS = '$DiMeNamesA=[0,'.NL;
$DiMeLabelsAS = '$DiMeLabelsA=[0,'.NL;

$DimNamesA = [0,
'Group',           # 100 - Group and Company
'ConDis',          # 105 - Dimension - Continuing and Discontinued
'Restated',        # 110 - Dimension - Restatements
'Excepts',         # 120 - Dimension - Exceptionals
'EquityClasses',   # 130 - Dimension - Equity Classes
'OpSegs',          # 150 - Dimension - Operating Segments
'ProdsServs',      # 170 - Dimension - Products And Services
'MajCusts',        # 180 - Dimension - Major Customers
'BizCombos',       # 190 - Dimension - Business Combinations
'PPEClasses',      # 200 - Dimension - PPE Classes
'IFAClasses',      # 210 - Dimension - Intangible Asset Classes
'IFAGType',        # 220 - Dimension - Intangible Assets Generation Type
'ImpairAllowType', # 225 - Dimension - Impairment Allowance Account Type
'FIValueType',     # 230 - Dimension - Financial Instrument Value Type
'FIs',             # 232 - Dimension - Financial Instrument Current and Non-Current
'FILevel',         # 233 - Dimension - Financial Instrument Level
'FIMvts',          # 234 - Dimension - Financial Instrument Movements
'ICRisk',          # 235 - Dimension - Industry Concentration Risk
'CrRatings',       # 238 - Dimension - Credit Ratings
'MPeriods',        # 240 - Dimension - Maturities Or Expiration Periods
'DefTaxClasses',   # 250 - Dimension - Deferred Tax Classes
'ProvClasses',     # 260 - Dimension - Provisions Classes
'PensionSchemes',  # 270 - Dimension - Pension Schemes
'SharePayment',    # 280 - Dimension - Share-based Payment Schemes
'ContLiabClasses', # 290 - Dimension - Contingent Liabilities Classes
'RelatedParties',  # 300 - Dimension - Related Parties
'RPTranType',      # 310 - Dimension - Related Party Transaction Type
'Subsids',         # 320 - Dimension - Subsidiaries
'Assocs',          # 330 - Dimension - Associates
'JVs',             # 340 - Dimension - Joint-Ventures
'DataNot',         # 399 - Dimension - Data Not For Use
'Officers',        # 500 - Dimension - Entity Officers
'OfficerType',     # 501 - Dimension - Entity Officer Type
'ShareClasses',    # 502 - Dimension - Share Classes
'ShareTypes',      # 503 - Dimension - Share Types
'ContactType',     # 505 - Dimension - Entity Contact Type
'TPAType',         # 508 - Dimension - Third Party Agent Type
'TPAStatus',       # 509 - Dimension - Third Party Agent Status
'Contact',         # 511 - Dimension - Form of Contact
'AddressType',     # 512 - Dimension - Address Type
'TelNumType',      # 513 - Dimension - Phone Number Type
'Countries',       # 520 - Dimension - Countries and Regions
'Currencies',      # 521 - Dimension - Currencies
'Exchanges',       # 522 - Dimension - Stock Exchanges
'Languages',       # 523 - Dimension - Languages
'Activity',        # 550 - Dimension - Activity
'ExpenseType',     # 551 - Dimension - Expense Type
'ExceptNon',       # 552 - Dimension - Exceptional and Non-Exceptional Items
'Analysis',        # 553 - Dimension - Detailed Analysis
'GroupTrans',      # 554 - Dimension - Intra / extra group transactions
];

$DimNamesAS = Build1DimensionaliArrayString('$DimNamesA=[', $DimNamesA);
file_put_contents(Com_Str_Tx.'DimNamesA.inc',  '<?php'.NL.$DimNamesAS);

$diMeId = $dimId = $bdDiMei = 0; # $prevLevel =
$DiMesA = []; # Build actual array as well as string version for use when building $DiMeSumTreesA
$dimsA  = []; # to hold the select results so that level look ahead can be done
$res = $DB->ResQuery("Select M.*,E.NsId,E.name,D.ElId DimElId From DimensionMembers M Join Elements E on E.Id=ElId Join Dimensions D on D.Id=M.DimId Order by M.Id");
while ($m = $res->fetch_object())
  $dimsA[] = $m;
$res->free();
$maxi = count($dimsA) - 1;
foreach ($dimsA as $i => $m) {
  $diMeId++;
  if ($diMeId != $m->Id) {
    echo "diMeId $diMeId != DimensionMembers.Id $m->Id<br>";
    exit;
  }
  if ($m->DimId != $dimId) {
    $dimId = (int)$m->DimId;
    $dimEleName      = ElName((int)$m->DimElId);
    if (substr($dimEleName, -9) != 'Dimension')
      echo "dimEleName $dimEleName does not end in Dimension<br>";
    else
      $dimEleName = substr($dimEleName, 0, -9); # Strip Dimension from end of name to same space in struct. To be put back when building contexts.
    $dimName  = $DimNamesA[$dimId];
    $diMeBits = 0;
  }
  $diMeEleName = $m->name;
  $bits   = (int)$m->Bits;
  $meElId = (int)$m->ElId;
  $diMeShortName = BroNameFromTxName($diMeEleName, $dimEleName);
  $diMeStdLabel = str_replace(' [default]', '', ElStdLabel($meElId)); # with [default] removed
  if (($bits & DiMeB_Default) && InStr('Total', $diMeShortName)) $diMeShortName='Total'; # Set all default Totalxxxx cases to just Total

  $muxList = $sumList = 0;   # reset for each one, unlike $diMeBits which stay set until changed or the next DimId
  $level = (int)$m->Level;
  $diMeBits &= ~(DiMeB_pYa | DiMeB_RO | DiMeB_SumKids | DiMeB_SumList | DiMeB_muX | DiMeB_Zilch); # unset from previous round, if set
  if ($i < $maxi && $dimsA[$i+1]->Level > $level)
    $diMeBits |= DiMeB_SumKids;
  if ($meElId === ElId_NotApplicable)
    $diMeBits |= DiMeB_Zilch;

  switch ($dimId) {
    case DimId_ConDis:            ## 2 DisCon
      switch ($diMeId) {
        case 4: $diMeShortName = 'ConOps'; break;                  # 4 1 ConDis.Ops from ContinuingOperations
        case 5: $diMeShortName = 'DisOps'; break;                  # 5 1 ConDis.Ops      DiscontinuedOperations
        case 6:                                                    # 6 2 ConDis.Specific1 from Specific discontinued operation 1
        case 7:
        case 8:
        case 9:
        case 10:
        case 11:
        case 12:
        case 13: $diMeShortName = str_ireplace('Specific', 'SpecificDisOp', $diMeShortName); break;
        case 14: $diMeShortName = 'DisposalGroupsHeldForSale'; break; # Disposal groups classified as held for sale
      }
      break;

    case DimId_Restated:          ## 3 Restated
      $diMeBits |= DiMeB_pYa;
      if ($diMeId === DiMeId_PyaOriginalAmount) $diMeBits |= DiMeB_RO; # Restated [OriginalAmount]
      break;

    case DimId_Excepts:           ## 4 Excepts
      switch ($diMeId) {
        case 29: $diMeShortName = 'After';  break; # 29 AfterAnyAdjustmentsForExceptionalItems
        case 30: $diMeShortName = 'Before'; break; # 30 BeforeAnyAdjustmentsForExceptionalItems
      }
     #$diMeBits |= DiMeB_RO;
      break;

    case 9:                       ## 9 BizCombos
      switch ($diMeId) {
        case 153: $diMeShortName = 'TotalIndividuallyImmaterial';                        break; # 153 TotalIndividuallyImmaterialBizCombos
        default:  $diMeShortName = str_ireplace('SpecificBizCombo', '', $diMeShortName); break; # 154 SpecificBizCombo1
      }
      break;

    case DimId_PPEClasses:        ## 10 PPEClasses
      switch ($diMeId) {
        case 183: $diMeShortName = 'ExplorationOrEvaluationAssets'; break; # 183 PPEClasses.TangibleExplorationEvaluationAssets
      }
      break;

    case DimId_IFAClasses:        ## 11 IFAClasses
    #if (substr($diMeShortName, -4) === 'IFAs') $diMeShortName = substr($diMeShortName, 0, -4); # Strip IFAs from the end
     $diMeShortName = str_ireplace('IFAs', '', $diMeShortName); # Strip IFAs
      break;

    case 12:                      ## 12 IFAGType
      switch ($diMeId) {
        case 213: $diMeShortName = 'Internal';break;          # 213 IFAGType.InternallyGeneratedIFAs
        case 214: $diMeShortName = 'External';break;          # 214 IFAGType.ExternallyGeneratedIFAs
      }
      break;

    case 14:                    ## 14 FIValueType
      switch ($diMeId) {
        case 239: break;                           # 239 FIValueType [CarryingAmount]
        default:                                   # 242 FIValueType.PastDueOrImpairedCarrying
                                                   # etc
          $diMeShortName=str_replace('Carrying','',$diMeShortName); # Strip Carrying
      }
      break;

    case 15:                      ## 15 FIs
      $diMeShortName = str_replace('Instrs', '', $diMeShortName); # Strip Instrs
      break;

    case 17:                      ## 17 FIMvts
      $diMeShortName = str_replace(['InLevel','From'], ['Level',''], $diMeShortName); # InLevl -> Level, strip From
      switch ($diMeId) {
        case 261: $diMeShortName = 'FairGainRecorded';break;        # 261 FIMvts.FairGainRecordedIncomeStatement
        case 270: $diMeShortName = 'GainLevel3HeldEndPeriod';break; # 270 FIMvts.GainProfitOrLossForLevel3InstrumentsHeldEndPeriod  Gain (loss) in profit or loss for level 3 instruments held at end of period
        case 271: $diMeShortName = 'GainLevel3BizCombo';break;      # 271 FIMvts.GainForLevel3InstrumentsBizCombo                   Gain (loss) for level 3 instruments from business combination
        case 272: $diMeShortName = 'RealisedGainLevel3';break;      # 272 FIMvts.RealisedGainForLevel3InstrumentsIncomeStatement    Realised gain (loss) for level 3 instruments in income statement
        case 273: $diMeShortName = 'UnrealisedGainLevel3';break;    # 273 FIMvts.UnrealisedGainForLevel3InstrumentsIncomeStatement  Unrealised gain (loss) for level 3 instruments in income statement
      }
      break;

    case 19:                      ## 19 CrRatings
      switch ($diMeId) {
       #case 295                                                    # 295 CrRatings.AAAOrEquivalent         AAA rating, or equivalent
        case 296: $diMeShortName = 'AAOrEquivalent';break;          # 296 CrRatings.AAToAAOrEquivalent      AA- to AA+ rating, or equivalent
        case 297: $diMeShortName = 'AOrEquivalent'; break;          # 297 CrRatings.AToOrEquivalent         A- to A+ rating, or equivalent
       #case 298                                                    # 298 CrRatings.LowerThanAOrEquivalent  Lower than A- rating, or equivalent
        case 299: $diMeShortName = 'BBBplusOrEquivalent';break;     # 299 CrRatings.BBBOrEquivalent         BBB+ rating, or equivalent
        case 300: $diMeShortName = 'BBBOrEquivalent';break;         # 300 CrRatings.BBBToBBBOrEquivalent    BBB- to BBB rating, or equivalent
        case 301: $diMeShortName = 'BBplusOrEquivalent';break;      # 301 CrRatings.BBOrEquivalent          BB+ rating, or equivalent
        case 302: $diMeShortName = 'BBOrEquivalent';break;          # 302 CrRatings.BBToBBOrEquivalent      BB- to BB rating, or equivalent
        case 303: $diMeShortName = 'BOrEquivalent';break;           # 303 CrRatings.BToBOrEquivalent        B- to B+ rating, or equivalent
        case 304: $diMeShortName = 'CCCplusOrEquivalent';break;     # 304 CrRatings.CCCOrEquivalent         CCC+ rating, or equivalent
        case 305: $diMeShortName = 'CCCOrEquivalent';break;         # 305 CrRatings.CCCToCCCOrEquivalent    CCC- to CCC rating, or equivalent
       #case 306                                                    # 306 CrRatings.CToCCOrEquivalent       C to CC rating, or equivalent
       #case 307                                                    # 307 CrRatings.DOrEquivalent           D rating, or equivalent
       #case 308                                                    # 308 CrRatings.Unrated                 Unrated
      }
      break;

    case 20:                    ## 20 MPeriods                                  # 257 0 MPeriods
     #switch ($diMeId) {                                                        # 258 0 MPeriods.All
     #  case 259: $muxList = '260,261,262,263,264'; $diMeShortName = '<1'; break; # 259 1   MPeriods.WithinOneYear
     #  case 260:                                                               # 260 2     MPeriods.Within30Days
     #  case 261:                                                               # 261 2     MPeriods.From30To60Days
     #  case 262:                                                               # 262 2     MPeriods.From60To90Days
     #  case 263:                                                               # 263 2     MPeriods.Within6Months
     #  case 264: $muxList = '259'; break;                                      # 264 2     MPeriods.Between6MonthsOneYear
     #  case 265: $muxList = '267,268,269'; $diMeShortName = '>1';  break;        # 265 1   MPeriods.AfterOneYear
     #  case 266:                           $diMeShortName = '1-5'; break;        # 266 2     MPeriods.BetweenOneFiveYears
     #  case 267: $muxList = '265';         $diMeShortName = '1-2'; break;        # 267 3       MPeriods.BetweenOneTwoYears
     #  case 268: $muxList = '265';         $diMeShortName = '2-5'; break;        # 268 3       MPeriods.BetweenTwoFiveYears
     #  case 269: $muxList = '265';         $diMeShortName = '>5';  break;        # 269 2     MPeriods.MoreThanFiveYears
     #}
      $diMeShortName = str_replace(['Within', 'From', 'Between'], '', $diMeShortName); # strip Within, From, Between
      break;


    case DimId_ProvClasses:       ## 22 ProvClasses
    case 25:                      ## 25 ContLiabClasses
      switch ($diMeId) {
        case 368:                                                   # 368 ProvClasses.RestructuringOrReorganisation
        case 415: $diMeShortName = 'RestructuringOrReorg';break;    # 415 ContLiabClasses.RestructuringOrReorganisation
        case 372:                                                   # 372 ProvClasses.TaxationInclDeferredTaxation
        case 419: $diMeShortName = 'TaxationInclDeferred';break;    # 419 ContLiabClasses.TaxationInclDeferredTaxation
      }
      break;

    case 24:                      ## 24 SharePayment
      $diMeShortName = str_replace('Based', '', $diMeShortName); # Strip Based -> 1 to 8
      break;

    case 37:                      ## 37 TPAType
      if ($diMeId === 869)  $diMeShortName = 'LegalAdvisors'; # 869 TPAType.LawyersOrLegalAdvisers
      break;

    case 47:                      ## 47  Expense type                          ExpenseTypeDimension
      $diMeShortName = str_replace(['DistribCosts', 'AdminExpenses'], ['Distrib', 'Admin'], $diMeShortName);
      break;

    case 49:                      ## 49 Detailed analysis                     DetailedAnalysisDimension
      $diMeShortName = str_replace('Item', '', $diMeShortName); # Strip Item
      break;

/*

    case DimId_OfficerType:     ## 33 OfficerType
      $diMeBits = ($bits == DiMeB_Default ? DiMeB_Zilch : 0);
      break;

    case 39:                    ## 39 Countries
      switch ($diMeId) {
        case 557: $diMeShortName = 'UK'; break;            # 557 Countries.UK
        case 610: $diMeShortName = 'Bosnia'; break;        # 610 Countries.BosniaHerzegovina
        case 615: $diMeShortName = 'Brunei'; break;        # 615 Countries.BruneiDarussalam
        case 633: $diMeShortName = 'DRCongo'; break;       # 633 Countries.CongoDemocraticRepublic
        case 652: $diMeShortName = 'Falklands';break;      # 652 Countries.FalklandIslandsMalvinas
        case 676: $diMeShortName = 'HeardMcDonald';break;  # 676 Countries.HeardIslandMcDonaldIsland
        case 677: $diMeShortName = 'Vatican';break;        # 677 Countries.HolySeeVaticanCityState
        case 684: $diMeShortName = 'Iran';break;           # 684 Countries.IranIslamicRepublic
        case 695: $diMeShortName = 'KoreaNorth';break;     # 695 Countries.KoreaDemocraticPeoplesRepublic
        case 696: $diMeShortName = 'KoreaSouth';break;     # 696 Countries.KoreaRepublic
        case 699: $diMeShortName = 'Laos';break;           # 699 Countries.LaoPeoplesDemocraticRepublic
        case 709: $diMeShortName = 'MacedoniaRepublic';break;# 709 Countries.MacedoniaFormerYugoslavRepublic
        case 722: $diMeShortName = 'Micronesia';break;     # 722 Countries.MicronesiaFederatedStates
        case 723: $diMeShortName = 'Moldova';break;        # 723 Countries.MoldovaRepublic
        case 742: $diMeShortName = 'NorthernMariana';break;# 742 Countries.NorthernMarianaIslands
        case 747: $diMeShortName = 'Palestine';break;      # 747 Countries.PalestinianTerritoryOccupied
        case 760: $diMeShortName = 'Russia';break;         # 760 Countries.RussianFederation
        case 762: $diMeShortName = 'StHelena';break;       # 762 Countries.SaintHelena
        case 763: $diMeShortName = 'StKittsNevis';break;   # 763 Countries.SaintsKittsNevis
        case 764: $diMeShortName = 'StLucia';break;        # 764 Countries.SaintLucia
        case 765: $diMeShortName = 'StPierre';break;       # 765 Countries.SaintPierreMiquelon
        case 766: $diMeShortName = 'StVincent';break;      # 766 Countries.SaintVincentGrenadines
        case 781: $diMeShortName = 'SouthGeorgia';break;   # 781 Countries.SouthGeorgiaSouthSandwichIslands
        case 786: $diMeShortName = 'Svalbard';break;       # 786 Countries.SvalbardJanMayen
        case 790: $diMeShortName = 'Syria';break;          # 790 Countries.SyrianArabRepublic
        case 791: $diMeShortName = 'Taiwan';break;         # 791 Countries.TaiwanProvinceChina
        case 793: $diMeShortName = 'Tanzania';break;       # 793 Countries.TanzaniaUnitedRepublic
        case 803: $diMeShortName = 'TurksCaicos';break;    # 803 Countries.TurksCaicosIslands
        case 809: $diMeShortName = 'USAMinorIslands';break;# 809 Countries.UnitedStatesMinorOutlyingIslands
      }
      break;

    case 40:                    ## 40 Currencies
      switch ($diMeId) {
        case 846: $diMeShortName = 'EURCO';break;            # 846 Currencies.BondMarketsUnitsEuropeanCompositeUnitEURCO
        case 878: $diMeShortName = 'EMU6';break;             # 878 Currencies.EuropeanMonetaryUnitEMU6
        case 879: $diMeShortName = 'EUA17';break;            # 879 Currencies.EuropeanUnitAccount17EUA17
        case 880: $diMeShortName = 'EUA9';break;             # 880 Currencies.EuropeanUnitAccount9EUA9
        case 881: $diMeShortName = 'FalklandPound';break;    # 881 Currencies.FalklandIslandsPound
        case 926: $diMeShortName = 'MexicanUID';break;       # 926 Currencies.MexicanUnidadDeInversionUID
        case 934: $diMeShortName = 'AntillianGuilder';break; # 934 Currencies.NetherlandsAntillianGuilder
        case 964: $diMeShortName = 'StHelenaPound';break;    # 964 Currencies.SaintHelenaPound
      }
      break;

    case 41:                    ## 41 Exchanges
      $diMeBits = 0; # Overriding kids summing
      break;

    case 42:                    ## 42 Languages
      $diMeBits = 0; # Overriding kids summing
      switch ($diMeId) {
       #case 1183: $diMeShortName = 'NotApplic';        # 1183 Languages [NotApplicable]
        case 1246: $diMeShortName = 'Nauruan'; break;   # 1246 Nauru
      }
      break;

    case 43:                    ## 43 6436 Activity                      ActivityDimension
      switch ($diMeId) {
        case 1288: $diMeShortName = 'AgriForestryFishing'; break;                 # 1288 AAgricultureForestryFishing
        case 1289: $diMeShortName = 'MiningQuarrying'; break;                     # 1289 BMiningQuarrying
        case 1290: $diMeShortName = 'Manufacturing'; break;                       # 1290 CManufacturing
        case 1291: $diMeShortName = 'ElectricGasSteamAirCon'; break;              # 1291 DElectricityGasSteamAirConditioningSupply
        case 1292: $diMeShortName = 'WaterSewerageWasteMngmt'; break;             # 1292 EWaterSupplySewerageWasteManagementRemediationFacilities
        case 1293: $diMeShortName = 'Construction'; break;                        # 1293 FConstruction
        case 1294: $diMeShortName = 'WholesaleRetailTradeRepairMotors'; break;    # 1294 GWholesaleRetailTradeRepairMotorVehiclesMotorcycles
        case 1295: $diMeShortName = 'TransportStorage'; break;                    # 1295 HTransportationStorage
        case 1296: $diMeShortName = 'AccommodationFoodService'; break;            # 1296 IAccommodationFoodServiceActivities
        case 1297: $diMeShortName = 'InfoCommunication'; break;                   # 1297 JInfoCommunication
        case 1298: $diMeShortName = 'FinancialInsurance'; break;                  # 1298 KFinancialInsuranceActivities
        case 1299: $diMeShortName = 'RealEstate'; break;                          # 1299 LRealEstateActivities
        case 1300: $diMeShortName = 'ProfessionalScientificTechnical'; break;     # 1300 MProfessionalScientificTechnicalActivities
        case 1301: $diMeShortName = 'AdminSupportService'; break;                 # 1301 NAdminSupportServiceActivities
        case 1302: $diMeShortName = 'PublicAdminDefenceSocSecurity'; break;       # 1302 OPublicAdminDefenceCompulsorySocialSecurity
        case 1303: $diMeShortName = 'Education'; break;                           # 1303 PEducation
        case 1304: $diMeShortName = 'HealthSocialWork'; break;                    # 1304 QHumanHealthSocialWorkActivities
        case 1305: $diMeShortName = 'ArtsEntertainRecreation'; break;             # 1305 RArtsEntertainmentRecreation
        case 1306: $diMeShortName = 'OtherServiceActivities'; break;              # 1306 SOtherServiceActivities
        case 1307: $diMeShortName = 'HouseholdsAsEmployersProducersOwnUse';break; # 1307 THouseholdsAsEmployersUndifferentiatedGoodsServiceproductionActivitiesHouseholdsForOwnUse
        case 1308: $diMeShortName = 'ExtraterritorialOrgsBodies'; break;          # 1308 UExtraterritorialOrgsBodies
        case 1309: $diMeShortName = 'CombinedXsectorActivs1'; break;              # 1309 CombinedCrosssectorActivities1
        case 1310: $diMeShortName = 'CombinedXsectorActivs2'; break;              # 1310 CombinedCrosssectorActivities2
        case 1311: $diMeShortName = 'CombinedXsectorActivs3'; break;              # 1311 CombinedCrosssectorActivities3
        case 1312: $diMeShortName = 'CombinedXsectorActivs4'; break;              # 1312 CombinedCrosssectorActivities4
        case 1313: $diMeShortName = 'OtherSpecific1'; break;                      # 1313 OtherSpecific1
        case 1314: $diMeShortName = 'OtherSpecific2'; break;                      # 1314 OtherSpecific2
        case 1315: $diMeShortName = 'OtherSpecific3'; break;                      # 1315 OtherSpecific3
        case 1316: $diMeShortName = 'OtherSpecific4'; break;                      # 1316 OtherSpecific4
        case 1317: $diMeShortName = 'OtherSpecific5'; break;                      # 1317 OtherSpecific5
      }
      break;

*/
    #default:
    #  $diMeBits = 0;
  }
  #$prevLevel = $level;

  if ($muxList) {
    $muxList = "[$muxList]";
    $diMeBits |= DiMeB_muX;
  }
  if ($sumList) {
    $sumList = "[$sumList]";
    $diMeBits |= DiMeB_SumList;
  }
  if (($diMeBits & (DiMeB_SumKids | DiMeB_SumList)) && !($diMeBits & DiMeB_muX))
    $diMeBits |= DiMeB_RO;
  if ($diMeBits & DiMeB_Zilch)
    $diMeBits = DiMeB_Zilch; # remove anything else except for possible default below

  $bits |= $diMeBits; # to include the original DB DiMeB_Default bit if set

  # $DiMeMapA
  $ref = (($bits & DiMeB_Default) ? $dimName : "$dimName.$diMeShortName");
  $DiMeMapAS .= "'$ref'=>$diMeId,\n";

  # $DiMesA              PMemI_PropId, DiMeI_Bits, DiMeI_MuxListA, DiMeI_SumListA
  $DiMesA[$diMeId] =    [$dimId,$bits,$muxList,$sumList,$level]; # incl $level only for use here in building $DiMeSumTreesA
 #$DiMesAS .= "$diMeId=>[$dimId,$bits,$muxList,$sumList],\n";
  $DiMesAS .=          "[$dimId,$bits,$muxList,$sumList],\n";

  # $DiMesRA        DiMeRI_DimId, DiMeRI_Bits, DiMeRI_NsId, DiMeRI_DiMe_Name, DiMeRI_DimEleName, DiMeRI_DiMeEleName
 #$DiMesRAS .= "$diMeId=>[$dimId,$bits,$m->NsId,'{$dimName}_$diMeShortName','$dimEleName','$diMeEleName'],\n";
 #$DiMesRAS .=          "[$dimId,$bits,$m->NsId,'{$dimName}_$diMeShortName','$dimEleName','$diMeEleName'],\n";

  # DiMeNamesA
  $DiMeNamesAS .= "'$dimName.$diMeShortName',\n";

  # $DiMeLabelsA
 #$DiMeLabelsAS .= InStr(SQ, $diMeStdLabel) ? "$diMeId=>\"$diMeStdLabel\",\n" : "$diMeId=>'$diMeStdLabel',\n";
  $DiMeLabelsAS .= InStr(SQ, $diMeStdLabel) ?          "\"$diMeStdLabel\",\n" :          "'$diMeStdLabel',\n";

}
$maxDiMeId = $diMeId;
FinishArrayString($DiMeMapAS);
#inishArrayString($DiMesRAS);

# Finish $DiMesAS, $DiMeNamesAS and $DiMeLabelsAS with DiMeId_Unallocated (9999) the  Pseudo DiMe for summing use re unallocated DiMe balances
$DiMesAS .= "9999=>[0]];
";
$DiMeNamesAS .= "9999=>'Unallocated'];
";
$DiMeLabelsAS .= "9999=>'Unallocated'];
";

# Tuple arrays
# ############
# Two passes is a hangover from when these array were built in BuildStructs.php for just those Tuples and Tuple Members in use in Bros.
# Here the building could be done in one pass.
$TuMesA       =          # Array of all TuMes   [TuMeId => TuMe Info array: [TupId, Ordr, TUCN]] TuMeI_TupId, TuMeI_Ordr, TuMeI_TUCN
$TupNamesA    =          # TupId  => TuShortName as used in BroRefs
$TupFullNamesA=          # TupId  => Tx Name incl NS  for Tx Tuple Names used by Bros for use with <ix:tuple name=
$TupLabelsA   = []; # TupId  => Tuple Std Labels stripped of ' [grouping]' used for building full BroRefs as in the TB
$prevTupId = 0;
$res = $DB->ResQuery('Select P.Id,TupId,TupTxId,MemTxId,Ordr,TUCN,E.NsId,E.name,T.Text from TuplePairs P Join Elements E on E.Id=P.TupTxId Join Text T on T.Id=E.StdLabelTxtId Order by P.Id');
while ($o = $res->fetch_object()) {
  $TuMeId  = (int)$o->Id;
  $TupId   = (int)$o->TupId;
  $TupTxId = (int)$o->TupTxId;
  $MemTxId = (int)$o->MemTxId;
  if ($TxElementsSkippedA && (in_array($MemTxId, $TxElementsSkippedA) || in_array($TupTxId, $TxElementsSkippedA))) continue; # Skipped for Bros
  $Ordr    = (int)$o->Ordr;
  $TUCN    = (int)$o->TUCN;
  if ($TupId > $prevTupId) {
    $prevTupId = $TupId;
    $TupNamesA[$TupId]    = BroNameFromTxName($o->name, 'Grouping');
    $TupFullNamesA[$TupId]= $NamespacesRgA[(int)$o->NsId].':'.$o->name; # Tx Name incl NS  for Tx Tuple Names used by Bros for use with <ix:tuple name=
    $TupLabelsA[$TupId]   = str_replace(' [grouping]', '', $o->Text); # with [grouping] removed
  }
  $TuMesA[$TuMeId] = [$TupId, $Ordr, $TUCN];
  $TuplesByTxIdA[$MemTxId][$TupId] = $TuMeId;
}
$res->free();

# Now generate the more compact string version of the tuple arrays
$TuMesAS      = '$TuMesA=['.NL;
$TupNamesAS   = '$TupNamesA=['.NL;
$TupFullNamesAS = '$TupFullNamesA=['.NL;
$TupLabelsAS  = '$TupLabelsA=['.NL;
foreach ($TuMesA as $TuMeId => $tuMeA)
  $TuMesAS .= "$TuMeId=>[" . implode(',', $tuMeA) . "],\n";
foreach ($TupNamesA as $TupId => $name) {
  $TupNamesAS     .= "$TupId=>'$name',\n";
  $TupFullNamesAS .= "$TupId=>'$TupFullNamesA[$TupId]',\n";
  $tupLabel = $TupLabelsA[$TupId];
  if (strpos($tupLabel, SQ) !== false) # re ' in Directors' in some labels
    $TupLabelsAS .= "$TupId=>\"$tupLabel\",\n";
  else
    $TupLabelsAS .= "$TupId=>'$tupLabel',\n";
}
FinishArrayString($TuMesAS);
FinishArrayString($TupNamesAS);
FinishArrayString($TupFullNamesAS);
FinishArrayString($TupLabelsAS);

/*
# $DiMeSumTreesA 3 dimensional array of [DimId, [target DiMeId, [DiMeIds to sum]]]
$DiMeSumTreesAS = '$DiMeSumTreesA=['.NL;
$dimId = 0;
foreach ($DiMesA as $diMeId => $diMeA) {
  if ($diMeA[PMemI_PropId] != $dimId) {
    if ($dimId) # && $dimId != DimId_Restated) # skip Restated as that is summed differently. Nope. Leave Restated in here re Summing trees report. Handle special case via $DiMeTargetsA and $RestatedDiMeTargetsA
      BuildDimDiMeSumTree($DiMeSumTreesAS, $dimId, $dimDiMesA, $sumKids, $sumList);
    $dimId = $diMeA[PMemI_PropId];
    $dimDiMesA = [];
    $sumKids = $sumList = 0;
  }
  $dimDiMesA[$diMeId] = $diMeA;
  $bits = $diMeA[DiMeI_Bits];
  $sumKids |= ($bits & DiMeB_SumKids);
  $sumList |= ($bits & DiMeB_SumList);
}
BuildDimDiMeSumTree($DiMeSumTreesAS, $dimId, $dimDiMesA, $sumKids, $sumList);
FinishArrayString($DiMeSumTreesAS);

eval($DiMeSumTreesAS); # to get the actual $DiMeSumTreesA for use below

# $DiMeTargetsA [DiMeId => [target DiMeIds]
# From $DiMeSumTreesA  3 dimensional array of [DimId, [target DiMeId, [DiMeIds to sum]]]
# Build
# $DiMeTargetsA [DiMeId => [target DiMeIds]] to simplify DiMe summing
# This code uses $DiMeSumTreesA so that needs to have been built first.
# First build the simple or one step DiMe Targets array from
$diMeTargets1A = []; # [DiMeId => [target DiMeIds]
foreach ($DiMeSumTreesA as $dimId => $treeA)  # [target DiMeId, [DiMeIds to sum]]
  foreach ($treeA as $tarDiMeId => $diMeIdsA) # thru the targets
    foreach ($diMeIdsA as $diMeId)            # thru the DiMes summing to the target
      $diMeTargets1A[$diMeId][] = $tarDiMeId; # 8 7
# DumpExport('diMeTargets1A',$diMeTargets1A);
# Now expand the targets to all that a DiMe adds to
$DiMeTargetsA = [];
foreach ($diMeTargets1A as $diMeId => $tarDiMeIdsA) {
  # Exclude $diMeId if it is a DiMeB_RO type as that would be a duplication. Example:
  # No need for
  # 74=>[73]
  # as the next ones do all that is needed since 74 can't ever have a posted value
  # 75=>[74,73]
  # 76=>[74,73]
  # 77=>[73] <=== though this one gets eliminated too when it is needed in the sum, but accept that here as this Restated summing is handled as a special case.
  if ($DiMesA[$diMeId][DiMeI_Bits] & DiMeB_RO)
    continue;
  # Further to the comment above about Restated summing being handled as a special case, we can skip 75 and 76 too here
  if ($diMeId == DiMeId_PyaAcctPolicyIncr  || $diMeId == DiMeId_PyaMaterialErrIncr) # 75, 76
    continue;
  $diMeIdsA = [];
  foreach ($tarDiMeIdsA as $tarDiMeId)
    AddTarget($tarDiMeId);
  $DiMeTargetsA[$diMeId] = $diMeIdsA;
}
ksort($DiMeTargetsA);
#DumpExport('$DiMeTargetsA', $DiMeTargetsA);

# Special case Restated DiMe summary array:
# $RestatedDiMeTargetsA=[
# 75=>[74,73],
# 76=>[74,73],
# 77=>[73]];
$RestatedDiMeTargetsAS = '$RestatedDiMeTargetsA=[
' . DiMeId_PyaAcctPolicyIncr   . '=>[' . DiMeId_PyaPriorPeriodIncr . ',' . DiMeId_PyaAmount . '],
' . DiMeId_PyaMaterialErrIncr . '=>[' . DiMeId_PyaPriorPeriodIncr . ',' . DiMeId_PyaAmount . '],
' . DiMeId_PyaOriginalAmount   . '=>[' . DiMeId_PyaAmount . ']];
';

# Now generate the more compact string version
$DiMeTargetsAS = '$DiMeTargetsA=[
';
foreach ($DiMeTargetsA as $diMeId => $diMeIdsA) {
  $DiMeTargetsAS .= "$diMeId=>[";
  foreach ($diMeIdsA as $diMeId)
    $DiMeTargetsAS .= "$diMeId,";
  $DiMeTargetsAS = substr($DiMeTargetsAS,0, -1) . "],\n";
}
FinishArrayString($DiMeTargetsAS);
*/

file_put_contents(Com_Str_Tx.'DiMesA.inc',     '<?php'.NL.$DiMesAS);
#ile_put_contents(Com_Str_Tx.'DiMesRA.inc',    '<?php'.NL.$DiMesRAS);
file_put_contents(Com_Str_Tx.'DiMeNamesA.inc', '<?php'.NL.$DiMeNamesAS);
file_put_contents(Com_Str_Tx.'DiMeLabelsA.inc','<?php'.NL.$DiMeLabelsAS);
file_put_contents(Com_Str_Tx.'DiMeMapA.inc',   '<?php'.NL.$DiMeMapAS);
file_put_contents(Com_Str_Tx.'TuMesA.inc',     '<?php'.NL.$TuMesAS);
file_put_contents(Com_Str_Tx.'TupNamesA.inc',  '<?php'.NL.$TupNamesAS);
file_put_contents(Com_Str_Tx.'TupFullNamesA.inc','<?php'.NL.$TupFullNamesAS);
file_put_contents(Com_Str_Tx.'TupLabelsA.inc',   '<?php'.NL.$TupLabelsAS);
#ile_put_contents(Com_Str_Tx.'DiMeSumTreesA.inc','<?php'.NL.$DiMeSumTreesAS);
#ile_put_contents(Com_Str_Tx.'DiMeTargetsA.inc', '<?php'.NL.$DiMeTargetsAS.$RestatedDiMeTargetsAS);

echo "Dimension and Tuple Structs built. Max DiMeId = $maxDiMeId<br>";
echo '<br>';

}

# Build DiMe summing array string in diMeSumTreeAS for dimension $dimId, with DiMes in $dimDiMesA and if anything, append it to $DiMeSumTreesAS
# $sumKids and $sumList are set if there are any kids/list sums in the dim
# $DiMeSumTreesA 3 dimensional array of [DimId, [target DiMeId, [DiMeIds to sum]]]
function BuildDimDiMeSumTree(&$DiMeSumTreesAS, $dimId, $dimDiMesA, $sumKids, $sumList) {
#echo "$dimId, , $sumKids, $sumList<br>";
  if (!$sumKids && !$sumList)
    return; # nothing to do here
  $diMeSumTreeAS = "$dimId=>[";
  if ($sumKids) { # got kids sum or sums
    #end($dimDiMesA);                     # /- to get the final DiMeId key
    #list($maxDiMeId) = each($dimDiMesA); # |, the [0] return from each() being the key
    $leveli = DiMeI_SumListA + 1; # level is appended to DiMesA entries for use here
    # first the kids sums working backwards through the levels
    for ($level=3; $level>=0; --$level) { # 3 is the max sumKids level for TFAs
      $addLevel = 0;
      foreach ($dimDiMesA as $diMeId => $diMeA) {
        $thisLevel = $diMeA[$leveli];
        if ($addLevel) {
          if ($thisLevel == $addLevel)
            $diMeSumTreeAS .= "$diMeId,";
          else if ($thisLevel < $addLevel) {
            $diMeSumTreeAS = substr($diMeSumTreeAS, 0, -1) . "],\n";
            $addLevel = 0;
          }
        }
        if (!$addLevel && $thisLevel == $level && ($diMeA[DiMeI_Bits] & DiMeB_SumKids)) {
          # next level DiMes - keep going until level comes back to <= the target level or the end is reached
          $diMeSumTreeAS .= "$diMeId=>[";
          $addLevel = $level + 1;
        }
      }
      if ($addLevel)
        $diMeSumTreeAS = substr($diMeSumTreeAS, 0, -1) . "],\n";
    }
  }
  if ($sumList) { # got list sum or sums
    foreach ($dimDiMesA as $diMeId => $diMeA) {
      if ($diMeA[DiMeI_SumListA])
        $diMeSumTreeAS .= "$diMeId=>{$diMeA[DiMeI_SumListA]},\n";
    }
  }
  $DiMeSumTreesAS .= substr($diMeSumTreeAS, 0, -2) . "],\n";
}

function AddTarget($tarDiMeId) {
  global $diMeTargets1A, $diMeIdsA;
  $diMeIdsA[] = $tarDiMeId;
  if (isset($diMeTargets1A[$tarDiMeId]))
    foreach ($diMeTargets1A[$tarDiMeId] as $tarDiMeId)
      AddTarget($tarDiMeId);
}
