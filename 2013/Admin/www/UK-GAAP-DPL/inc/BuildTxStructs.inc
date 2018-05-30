<?php /* Copyright 2011-2013 Braiins Ltd

... UK-GAAP-DPL/inc/BuildTxStructs.inc

Build the Taxonomy Based Structs which don't change with Bro or Zones changes

History:
01.10.12 Started based on UK-GAAP/inc/BuildTxStructs.inc
07.10.12 Name adjustments
08.10.12 Use of $TxElementsSkippedA removed with the removal of unwanted elements from the DB.
25.10.12 Added DimDiMeShortNamesA and removed DiMeShortNamesA
09.11.12 Use of Start pseudo DiMeId removed with advent of the Bro Class.
27.11.12 Php 5.4 []s changes
         Removed $DiMesA from DiMeMapA.inc allowing ClassBro.inc to load DiMesA.inc
05.04.13 DimShortNamesA => DimNamesA; DimDiMeShortNamesA => DiMeNamesA; UniqueDiMeRefsA removed
20.04.13 Everything to do with DiMesA EType list removed as this is now a BRL matter.
         And MType
21.04.13 Removed Braiins Dimension Ageing

To Do ??djh-----

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
$HyDimsA = array (
  '',
  '13',        #  1
  '234567W',   #  2
  '234567:;W', #  3
  '2345679W',  #  4
  '1345:;',    #  5
  '13459',     #  6
  '1356',      #  7
  '2356',      #  8
  '1345',      #  9
  '13456',     # 10
  '2347W',     # 11
  '234',       # 12
  '134',       # 13
  '23',        # 14
  '23567',     # 15
  '2356W',     # 16
  '2347',      # 17
  '234W',      # 18
  '137',       # 19
  '13W',       # 20
  '1348',      # 21
  '139',       # 22
  '13:;',      # 23
  '23<=',      # 24
  '13<',       # 25
  '13>OP',     # 26
  '13?',       # 27
  '13@',       # 28
  '13ABCEX',   # 29
  '13D',       # 30
  '13FG',      # 31
  '13FOP',     # 32
  '13H',       # 33
  '13IW',      # 34
  '13JW',      # 35
  '13KW',      # 36
  '13L',       # 37
  '34W[\\]^_', # 38
  '3[^',       # 39
  '13MN',      # 40
  '13OP',      # 41
  '1QTUVWZ',   # 42
  '13RSTUVWZ', # 43
  '13W',       # 44
  '13X',       # 45
  '1Y',        # 46
  '1Z',        # 47
  '');         # 48 */

# $HyDimsA [HyId => Dimensions in chr list form]

$HyDimsAS = '$HyDimsA=[
0,
';
$res = $DB->ResQuery('Select Id,Dimensions From Hypercubes Order by Id');
while ($o = $res->fetch_object())
  $HyDimsAS .= "'$o->Dimensions',\n";
FinishArrayString($HyDimsAS);

# $HyNamesA  [HyId => Hy Short Name] Names need to be unique. Flipped version $HyShortNamesMapA is used by BrosImport.php

$HyNamesAS = "\$HyNamesA=[
0,
'Basic',
'Income',
'TADisposal',
'IADisposal',
'TAExpenses',
'IAExpenses',
'ExceptsAmort',
'JVsExceptsAmort',
'OpActivsExcepts',
'OpActivsExceptsAmort',
'OpActivsJVsGeoBiz',
'OpActivsConsol',
'OpActivs',
'Consol',
'BizSegsIncome',
'GeoSegsIncome',
'BizSegsAssets',
'GeoSegsAssets',
'BizSegsBasic',
'GeoSegsBasic',
'Provisions',
'IFAs',
'TFAs',
'FAIs',
'FAILoans',
'Dividends',
'PensionSchemes',
'ShareSchemes',
'FIs',
'FIMvts',
'Acqs',
'ShareAcqs',
'Disposals',
'JVs',
'Assocs',
'Subsids',
'OtherInterests',
'DPL',
'DPLRes',
'Officers',
'Shares',
'ContactInfo',
'TPAs',
'Countries',
'Currencies',
'StockExs',
'Languages',
'Empty'];";

file_put_contents(Com_Str_Tx.'Hypercubes.inc', '<?php
' . $HyDimsAS
  . $HyNamesAS);


# Dimension Arrays
# ----------------
# Strings for $DiMeMapA ...
$DiMeMapAS = '$DiMeMapA=[
';
$DiMesAS = '$DiMesA=[
0,
';
$DiMesRAS = '$DiMesRA=[
0,
';
$DiMeNamesAS = "\$DiMeNamesA=[
0,
";
$DiMeLabelsAS = "\$DiMeLabelsA=[
0,
";

$DimNamesAS = "\$DimNamesA=[
0,
'Group',
'Consol',
'Restated',
'OpActivs',
'ExceptAdjusts',
'AmortAdjusts',
'BizSegs',
'ProvisionClasses',
'IFAClasses',
'TFAClasses',
'TFAOwnership',
'FAIHoldings',
'FAITypes',
'Dividends',
'PensionSchemes',
'ShareSchemes',
'FIValueType',
'FIs',
'FILevel',
'FIMvts',
'MPeriods',
'Acqs',
'AcqAssetsLiabs',
'Disposals',
'JVs',
'Assocs',
'Subsids',
'OtherInterests',
'Officers',
'OfficerType',
'ShareClasses',
'ShareTypes',
'ContactType',
'TPAType',
'TPAStatus',
'Contact',
'AddressType',
'TelNumType',
'Countries',
'Currencies',
'StockExs',
'Languages',
'Activity',
'ExpenseType',
'ExceptNon',
'Analysis',
'GroupTrans'];";

eval($DimNamesAS); # to get $DimNamesA for use here

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
 #switch ($dimId) {
   #case DimId_BraiinsFunction:
  /*case DimId_BraiinsAgeing:
      $bDiMesA = [
        # diMeName           diMeShortName label
        ['All',                 'All', 'All periods'],                # 0 Ageing.All
        ['WithinOneYear',       '<1',  'Within one year'],            # 1   Ageing.WithinOneYear
        ['AfterOneYear',        '>1',  'After one year'],             # 1   Ageing.AfterOneYear
        ['BetweenOneFiveYears', '1-5', 'Between one and five years'], # 2     Ageing.BetweenOneFiveYears
        ['BetweenOneTwoYears',  '1-2', 'Between one and two years'],  # 3       Ageing.BetweenOneTwoYears
        ['BetweenTwoFiveYears', '2-5', 'Between two and five years'], # 3       Ageing.BetweenTwoFiveYears
        ['MoreThanFiveYears',   '>5',  'More than five years']        # 2     Ageing.MoreThanFiveYears
      ];
      list($diMeEleName, $diMeShortName, $diMeStdLabel) = $bDiMesA[$bdDiMei++];
      $bits   = DiMeB_BD;
      $meElId = 0;
      break; */
   #default:
      $diMeEleName = $m->name;
      $bits   = (int)$m->Bits;
      $meElId = (int)$m->ElId;
      if ($diMeId == DiMeId_Accountants)   # /- Fudge for [A] 5461 Entity accountants or auditors to allow Accountants and Auditors separately in the Dimensions Map
        $diMeShortName = 'Accountants';    # |  Leaving $diMeEleName as 'EntityAccountantsOrAuditors'
      else if ($diMeId == DiMeId_Auditors) # |
        $diMeShortName = 'Auditors';       # |
      else
        $diMeShortName = BroNameFromTxName($diMeEleName, $dimName);
      $diMeStdLabel = str_replace(' [default]', '', ElStdLabel($meElId)); # with [default] removed
 #}
  if (($bits & DiMeB_Default) && InStr('Total', $diMeShortName)) $diMeShortName='Total'; # Set all default Totalxxxx cases to just Total

  $muxList = $sumList = 0;   # reset for each one, unlike $diMeBits which stay set until changed or the next DimId
  $level = (int)$m->Level;
  $diMeBits &= ~(DiMeB_pYa | DiMeB_RO | DiMeB_SumKids | DiMeB_SumList | DiMeB_muX | DiMeB_Zilch); # unset from previous round, if set
  if ($i < $maxi && $dimsA[$i+1]->Level > $level)
    $diMeBits |= DiMeB_SumKids;
  if ($meElId == TxElId_NotApplicable)
    $diMeBits |= DiMeB_Zilch;

  switch ($dimId) {
    case DimId_Group:             ## 1 Group
    case DimId_Consol:            ## 2 Consol
      switch ($diMeId) {
        case 5:  $sumList = '3,4,7';  break; #  5 Consol.GroupShareJVsExclAssocsTurnoverOnly
        case 6:  $sumList = '7,40';   break; #  6 Consol.ShareJVsAssocs
        case 39: $sumList = '3,4,40'; break; # 39 Consol.ShareJVsAssocs
        case 72: $sumList = '3,4';    break; # 72 Consol.GroupBeforeAssocsJVs
      }
      break;

    case DimId_Restated:          ## 3 Restated
      $diMeBits |= DiMeB_pYa;
      if ($diMeId == 77) $diMeBits |= DiMeB_RO; # 77 Restated [OriginalAmount]
      break;

    case DimId_OpActivities:      ## 4 OpActivities
      $diMeShortName = str_replace('Ops', '', $diMeShortName); # Strop Ops
      switch ($diMeId) {
        case 79: $sumList = '80,81';break;            # 79 OpActivs.ContinuingOpsInclAcqs
       #case 80:                                      # 80 OpActivs.Acqs
        case 81: $diMeShortName = 'Continuing';break; # 81 OpActivs.ContinuingOpsExclAcqs
       #case 82:                                      # 82 OpActivs.DiscontinuedOps
      }
      break;

    case DimId_ExceptAdjusts:     ## 5 ExceptAdjusts
    case DimId_AmortAdjusts:      ## 6 AmortAdjusts
      switch ($diMeId) {
        case 84:                                   # 84 AfterAnyAdjustmentsForExceptionalItems
        case 87: $diMeShortName = 'After'; break;  # 87 AfterAnyAdjustmentsForAmortisationImpairment
        case 85:                                   # 85 BeforeAnyAdjustmentsForExceptionalItems
        case 88: $diMeShortName = 'Before';break;  # 88 BeforeAnyAdjustmentsForAmortisationImpairment
      }
      $diMeBits |= DiMeB_RO;
      break;

    case 7:                       ## 7 BizSegs
      break;

    case 8:                       ## 8 ProvisionClasses
      $diMeShortName = str_replace('ProvisionsFor', '', $diMeShortName); # they all had leading 'ProvisionsFor'
      switch ($diMeId) {
        case 124: $diMeShortName = "DBPensionSchemes"; break;             # 124 ProvisionClasses.DefinedBenefitPensionSchemes
        case 125: $diMeShortName = "DCPensionSchemes"; break;             # 125 ProvisionClasses.DefinedContribPensionSchemes
        case 126: $diMeShortName = "PostRetireBenefitsNonPension"; break; # 126 ProvisionClasses.PostRetireBenefitsOtherThanPensions
        case 127: $diMeShortName = "TaxationInclDeferred";break;          # 127 ProvisionClasses.TaxationInclDeferredTaxation
        case 129: $diMeShortName = "RestructuringOrReorg";break;          # 129 ProvisionClasses.RestructuringOrReorganisation
        case 133: $diMeShortName = "DecommissionRestorationRehab"; break; # 133 ProvisionClasses.DecommissioningRestorationRehab
      }
      break;

    case DimId_IFAClasses:        ##  9 IFAClasses
      switch ($diMeId) {
        case 143: $muxList = '144,145'; break;                    # 143 1 IFAClasses.NetGoodwill 144,145	Kids
        case 144:                                                 # 144  2 IFAClasses.PositiveGoodwill
        case 145: $muxList = '143'; break;                        # 145  2 IFAClasses.NegativeGoodwill
        case 147: $muxList = '148,149,150,151'; break;            # 147 1 IFAClasses.DevelopmentCostsPatentsSimilar
        case 148: $muxList = '147'; break;                        # 148  2 IFAClasses.DevelopmentCosts
        case 149: $muxList = '147,150,151'; break;                # 149  2 IFAClasses.PatentsConcessionsLicencesTradeMarksSimilar
        case 150:                                                 # 150   3 IFAClasses.ConcessionsLicencesFranchises
        case 151: $muxList = '147,149'; break;                    # 151   3 IFAClasses.PatentsCopyrightsTrademarksSimilar
        case 157:                                                 # 157 1 IFAClasses.PaymentsOnAcct
        case 158:                                                 # 158 1 IFAClasses.Others
      }
      break;

    case DimId_TFAClasses:        ## 10 TFAClasses
      switch ($diMeId) {
        case 160: $muxList = '161,162'; break;                     # 160 1 TFAClasses.LandBuildings
        case 161:                                                  # 161  2 TFAClasses.Land
        case 162: $muxList = '160'; break;                         # 162  2 TFAClasses.Buildings
        case 165: $muxList = '166,167,168,169,170,171,172,173'; break; # 165 1 TFAClasses.VehiclesPlantMachinery
        case 166: $muxList = '165'; break;                         # 166  2 TFAClasses.PlantMachinery
        case 167: $muxList = '165,168,169,170,171,172,173'; break; # 167  2 TFAClasses.Vehicles
        case 168:                                                  # 168   3 TFAClasses.Aircraft
        case 169: $muxList = '165,167'; break;                     # 169   3 TFAClasses.Boats
        case 170: $muxList = '165,167,171,172'; break;             # 170   3 TFAClasses.MotorVehicles
        case 171:                                                  # 171    4 TFAClasses.CommercialMotorVehicles
        case 172: $muxList = '165,167,170'; break;                 # 172    4 TFAClasses.MotorCars
        case 173: $muxList = '165,167'; break;                     # 173   3 TFAClasses.OtherVehicles
        case 174: $muxList = '175,176'; break;                     # 174 1 TFAClasses.FixturesFittingsToolsEquipment
        case 175:                                                  # 175  2 TFAClasses.FixturesFittings
        case 176: $muxList = '174'; break;                         # 176  2 TFAClasses.ToolsEquipment
        case 179: $muxList = '180,181'; break;                     # 179 1 TFAClasses.PaymentsOnAccountAssetsUnderDevelopment
        case 180: $muxList = '179'; break;                         # 180  2 IFAClasses.PaymentsOnAcct
        case 181: $muxList = '179';                                # 181  2 TFAClasses.ImprovementsOrDevelopmentAssets
                  $diMeShortName = 'ImprovementOrDevelopmentAssets'; break;
        case 182: $diMeShortName = 'ExplorationOrEvaluationAssets'; break; # 182 TFAClasses.TangibleExplorationOrEvaluationAssets
        case 183: $dimName.$diMeShortName;   # 183 1 TFAClasses.Others
      }
      break;

    case DimId_FAIHoldings:     ## 12 FAIHoldings
      $diMeShortName = str_replace('Invests', 'Invest', $diMeShortName); # some start Invests and other Invest => all to Invest
      if ($diMeId==197) $dimName.$diMeShortName; # 197 FAIHoldings.Others
      break;

    case DimId_TFAOwnership:    ## 11 TFAOwnership  Prop
      break;

    case DimId_FAITypes:        ## 13 FAITypes
      switch ($diMeId) {
        case 198: $diMeShortName = 'All'; break;   # 198 FAITypes [AllTypesFAI]
        case 202: $diMeShortName = 'Loans'; break; # 202 FAITypes.LoansHeldAsFAIs
        default:                                   # 199 FAITypes.SharesFAIs         /- strip FAIs
                                                   # 200 FAITypes.SharesListedFAIs   v
                                                   # 201 FAITypes.SharesUnlistedFAIs
                                                   # 203 FAITypes.FAIsOtherThanSharesInclLoans
                                                   # 204 FAITypes.FAIsOtherThanSharesNotInclLoans
                                                   # 205 FAITypes.FAIsOtherThanLoans
          $diMeShortName=str_replace('FAIs','',$diMeShortName);
      }
      break;

    case 14:                    ## 14 Dividends
      break;

    case 15:                    ## 15 PensionSchemes
      break;

    case 16:                    ## 16 ShareSchemes
      $diMeShortName = str_replace('Based', '', $diMeShortName); # Strip Based -> 1 to 8
      break;

    case 17:                    ## 17 FIValueType
      switch ($diMeId) {
        case 232: break;                           # 232 FIValueType [CarryingAmount]
        default:                                   # 233 FIValueType.Value
                                                   # 234 FIValueType.PastDueOrImpairedCarrying
                                                   # 235 FIValueType.PastDueButNotImpairedCarrying
                                                   # 236 FIValueType.ImpairedCarrying
                                                   # 237 FIValueType.NeitherPastDueNorImpairedCarrying
                                                   # 238 FIValueType.NotionalAmount
          $diMeShortName=str_replace('Carrying','',$diMeShortName);
      }
      break;

    case 18:                    ## 18 FIs
      $diMeShortName = str_replace('FinInstrs', '', $diMeShortName); # Strip FinInstrs
      # fall thru
    case 19:                    ## 19 FILevel
      break;

    case 20:                    ## 20 FIMvts
      $diMeShortName = str_replace(['InLevel','From'], ['Level',''], $diMeShortName); # InLevl -> Level, strip From
      switch ($diMeId) {
        case 250: $muxList = '251,252,253'; break; # 250 FIMvts.NetIncrInLevel3PurchasesSalesSettlements
        case 251:                                  # 251 FIMvts.NetIncrInLevel3Purchases
        case 252:                                  # 252 FIMvts.NetIncrInLevel3Sales
        case 253: $muxList = '250'; break;         # 253 FIMvts.NetIncrInLevel3Settlements
      }
      break;

    case DimId_MPeriods:        ## 21 MPeriods                                  # 257 0 MPeriods
      switch ($diMeId) {                                                        # 258 0 MPeriods.All
        case 259: $muxList = '260,261,262,263,264'; $diMeShortName = '<1'; break; # 259 1   MPeriods.WithinOneYear
        case 260:                                                               # 260 2     MPeriods.Within30Days
        case 261:                                                               # 261 2     MPeriods.From30To60Days
        case 262:                                                               # 262 2     MPeriods.From60To90Days
        case 263:                                                               # 263 2     MPeriods.Within6Months
        case 264: $muxList = '259'; break;                                      # 264 2     MPeriods.Between6MonthsOneYear
        case 265: $muxList = '267,268,269'; $diMeShortName = '>1';  break;        # 265 1   MPeriods.AfterOneYear
        case 266:                           $diMeShortName = '1-5'; break;        # 266 2     MPeriods.BetweenOneFiveYears
        case 267: $muxList = '265';         $diMeShortName = '1-2'; break;        # 267 3       MPeriods.BetweenOneTwoYears
        case 268: $muxList = '265';         $diMeShortName = '2-5'; break;        # 268 3       MPeriods.BetweenTwoFiveYears
        case 269: $muxList = '265';         $diMeShortName = '>5';  break;        # 269 2     MPeriods.MoreThanFiveYears
      }
      $diMeShortName = str_replace(['Within', 'From', 'Between'], '', $diMeShortName); # strip Within, From, Between
      break;

    case 22:                    ## 22 Acqs
    case 24:                    ## 24 Disposals
    case 25:                    ## 25 JVs
    case 26:                    ## 26 Assocs
      break;

    case 23:                    ## 23 AcqAssetsLiabs
      $diMeShortName = str_replace(['Alignment', 'Significant'], ['Align', 'Sig'], $diMeShortName); # shorten Alignment and Significant
      break;

    case 27:                    ## 27 Subsidiaries
      $diMeShortName = str_replace('Subsidiary', '', $diMeShortName); # Strip Subsidiary
      break;

    case DimId_Officers:        ## 29 Officers      Mixed
      switch ($diMeId) {
        case DiMeId_AllOfficers:         $diMeBits = DiMeB_Zilch; break;
        case DiMeId_Chairman:
        case DiMeId_CEO:
        case DiMeId_ChairmanCEO:         break;
       #case DiMeId_HighestPaidDirector: break;
        case DiMeId_HighestPaidDirector: break;
        case DiMeId_CoSecDirector:       break;
        case DiMeId_ChiefPartnerLLP:     break;
        case DiMeId_CoSec:               break;
      }
      break;

    case DimId_OfficerType:     ## 30 OfficerType
      $diMeBits = ($bits == DiMeB_Default ? DiMeB_Zilch : 0);
      break;

    case 31:                    ## 31 ShareClasses
    case 32:                    ## 32 ShareTypes
      $diMeShortName = str_replace(['Cumulative','Shares'], ['Cum',''], $diMeShortName); # Shorten Cumulative, strip Shares
      if ($diMeId==509) $dimName.$diMeShortName; # 509 ShareTypes.Others
      break;

    case 33:                    ## 33 [Entity]ContactType
      $diMeBits = 0; # Overriding kids summing
      #if ($diMeShortName == 'RegisteredOffice') 21.06.12 Removed re C's Bro tree that does not use a specific registeredOffice Bro
      #  $diMeBits = DiMeB_Zilch;
      break;

    case DimId_TPAType:         ## 34 TPAType
      if ($diMeId==525)
        $diMeShortName = 'LegalAdvisors'; # 525 TPAType.LawyersOrLegalAdvisers
      break;

    case DimId_TPAStatus:       ## 35 TPAStatus
      break;

    case 36:                    ## 36 Contact
      $diMeShortName = str_replace('Form', '', $diMeShortName); # Strip Form
      break;

    case 37:                    ## 37 AddressType
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

    case 44:                    ## 44 6474 Expense type                          ExpenseTypeDimension
      $diMeShortName = str_replace(['DistribCosts', 'AdminExpenses'], ['Distrib', 'Admin'], $diMeShortName);
      break;

    case 45:                    ## 45 6473 Exceptional and non-exceptional items ExceptionalNon-exceptionalItemsDimension
      break;

    case 46:                    ## 46 6464 Detailed analysis                     DetailedAnalysisDimension
      $diMeShortName = str_replace('Item', '', $diMeShortName); # Strip Item
      break;

    case 47:                    ## 47 6505 Intra / extra group transactions      IntraExtraGroupTransactionsDimension
      switch ($diMeId) {
        case 1369: $diMeShortName = 'SubsidUndertakings'; break; # 1369 SubsidiariesOtherGroupUndertakings
        case 1373: $diMeShortName = 'TPs'; break;                # 1373 ThirdPartiesOutsideGroup
      }
      break;

  /*case DimId_BraiinsAgeing:   ## 48 Ageing Braiins dimension                                   Old GAAP #s
      switch ($diMeId) {                               # 1374 0 Ageing.All                       1291
       #case 1375:                                     # 1375 1   Ageing.WithinOneYear           1292
        case 1376: $muxList = '1378,1379,1380'; break; # 1376 1   Ageing.AfterOneYear            1293
       #case 1377:                                     # 1377 2     Ageing.BetweenOneFiveYears   1294
        case 1378:                                     # 1378 3       Ageing.BetweenOneTwoYears  1295
        case 1379:                                     # 1379 3       Ageing.BetweenTwoFiveYears 1296
        case 1380: $muxList = '1376'; break;           # 1380 2     Ageing.MoreThanFiveYears     1297
      }
      break; */

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

  # $DiMesA              ItemI_PropId, DiMeI_Bits, DiMeI_MuxListA, DiMeI_SumListA
  $DiMesA[$diMeId] =    [$dimId,$bits,$muxList,$sumList,$level]; # incl $level only for use here in building $DiMeSumTreesA
 #$DiMesAS .= "$diMeId=>[$dimId,$bits,$muxList,$sumList],\n";
  $DiMesAS .=          "[$dimId,$bits,$muxList,$sumList],\n";

  # $DiMesRA        DiMeRI_DimId, DiMeRI_Bits, DiMeRI_NsId, DiMeRI_DiMe_Name, DiMeRI_DimEleName, DiMeRI_DiMeEleName
 #$DiMesRAS .= "$diMeId=>[$dimId,$bits,$m->NsId,'{$dimName}_$diMeShortName','$dimEleName','$diMeEleName'],\n";
  $DiMesRAS .=          "[$dimId,$bits,$m->NsId,'{$dimName}_$diMeShortName','$dimEleName','$diMeEleName'],\n";

  # DiMeNamesA
  $DiMeNamesAS .= "'$dimName.$diMeShortName',\n";

  # $DiMeLabelsA
 #$DiMeLabelsAS .= InStr(SQ, $diMeStdLabel) ? "$diMeId=>\"$diMeStdLabel\",\n" : "$diMeId=>'$diMeStdLabel',\n";
  $DiMeLabelsAS .= InStr(SQ, $diMeStdLabel) ?          "\"$diMeStdLabel\",\n" :          "'$diMeStdLabel',\n";

}
$maxDiMeId = $diMeId;
FinishArrayString($DimNamesAS);
FinishArrayString($DiMeMapAS);
FinishArrayString($DiMesRAS);

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
$TuMesAS      = '$TuMesA=[
';
$TupNamesAS   = '$TupNamesA=[
';
$TupFullNamesAS = '$TupFullNamesA=[
';
$TupLabelsAS  = '$TupLabelsA=[
';
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


# $DiMeSumTreesA 3 dimensional array of [DimId, [target DiMeId, [DiMeIds to sum]]]
$DiMeSumTreesAS = '$DiMeSumTreesA=[
';
$dimId = 0;
foreach ($DiMesA as $diMeId => $diMeA) {
  if ($diMeA[ItemI_PropId] != $dimId) {
    if ($dimId) # && $dimId != DimId_Restated) # skip Restated as that is summed differently. Nope. Leave Restated in here re Summing trees report. Handle special case via $DiMeTargetsA and $RestatedDiMeTargetsA
      BuildDimDiMeSumTree($DiMeSumTreesAS, $dimId, $dimDiMesA, $sumKids, $sumList);
    $dimId = $diMeA[ItemI_PropId];
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

file_put_contents(Com_Str_Tx.'DiMesA.inc', '<?php
' . $DiMesAS);
file_put_contents(Com_Str_Tx.'DiMesRA.inc',  '<?php
' . $DiMesRAS);
file_put_contents(Com_Str_Tx.'DiMeNamesA.inc', '<?php
'. $DiMeNamesAS);
file_put_contents(Com_Str_Tx.'DimNamesA.inc', '<?php
'. $DimNamesAS);
file_put_contents(Com_Str_Tx.'DiMeLabelsA.inc', '<?php
' . $DiMeLabelsAS);
file_put_contents(Com_Str_Tx.'DiMeMapA.inc', '<?php
' . $DiMeMapAS);
file_put_contents(Com_Str_Tx.'TuMesA.inc', '<?php
' . $TuMesAS);
file_put_contents(Com_Str_Tx.'TupNamesA.inc', '<?php
' . $TupNamesAS);
file_put_contents(Com_Str_Tx.'TupFullNamesA.inc', '<?php
' . $TupFullNamesAS);
file_put_contents(Com_Str_Tx.'TupLabelsA.inc', '<?php
' . $TupLabelsAS);


file_put_contents(Com_Str_Tx.'DiMeSumTreesA.inc', '<?php
' . $DiMeSumTreesAS);
file_put_contents(Com_Str_Tx.'DiMeTargetsA.inc', '<?php
' . $DiMeTargetsAS
  . $RestatedDiMeTargetsAS);

echo "Dimension and Tuple Structs built. Max DiMeId = $maxDiMeId<br>";

echo '<br>';

}

# Chop off the final , and \n then add the clos
#  - just the final \n if second last char is [ i.e. if nothing was added to the array
#  - the final , and \n if otherwise, assuming an array entry followed by ,\n
function FinishArrayString(&$arrayS) {
  $arrayS = (substr($arrayS,-2,1) === '[' ? substr($arrayS,0,-1) : substr($arrayS,0,-2)) . '
];
';
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
