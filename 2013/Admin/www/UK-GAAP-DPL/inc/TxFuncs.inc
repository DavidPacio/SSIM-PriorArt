<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/UK-GAAP-DPL/inc/TxFuncs.inc

Tx functions to be loaded by BRL modules as well as UK-GAAP-DPL ones

History:
26.03.13 Started with functions extracted from UtilsFuncs.inc

ToDo djh??
====

boolean IsHypercubeSubset($xId, $yId)    Is Hypercube x a subset of Hypercube y? (xId & yId being HyIds) Needs Hypercubes.inc to have been loaded for $HyDimsA
ChrList EliminateHypercubeSubsets($hypercubesChrList)
boolean IsDimsListSubset($xDims, $yDims) Is Dims list x a subset of Dims list y?
boolean IsDimSubset($dc, $dList)         Is Dim (chr $dc) a subset of a Dim in the $dList of Dims (char list)

string  BroNameFromTxName($name, $dadName=0)

*/

# IsHypercubeSubset
# -----------------
# Is Hypercube x a subset of Hypercube y? (xId & yId being HyIds)
# Needs Hypercubes.inc to have been loaded for $HyDimsA
function IsHypercubeSubset($xId, $yId) {
  global $HyDimsA;
  $xDims = $HyDimsA[$xId];
  $yDims = $HyDimsA[$yId];
  $xLen = strlen($xDims);
  $yLen = strlen($yDims);
  if ($xLen > $yLen || !$xLen || !$yLen)
    return false;
  for ($k=0; $k<$xLen; ++$k) {
    $c = $xDims[$k];
    if (strpos($yDims, $c) === false) {
      # c from x not in y but check for Dim 1 being a subset of Dim 2. Warning - this is UK-GAAP-DPL specific
      if ($c !== '1' || $yDims[0] !== '2')
        return false;
    }
  }
  return true;
}

# EliminateHypercubeSubsets
# -------------------------
function EliminateHypercubeSubsets($hypercubesChrList) {
  $nHys = strlen($hypercubesChrList); # expect this to be > 1 if this is called
  $new = '';
  for ($i=0; $i<$nHys; ++$i) {
    $okB = true;
    $a = ChrToInt($hypercubesChrList[$i]);
    for ($j=0; $j<$nHys; ++$j) {
      if ($j != $i && IsHypercubeSubset($a, ChrToInt($hypercubesChrList[$j]))) {
        $okB = false;
        break;
      }
    }
    if ($okB)
      $new .= $hypercubesChrList[$i];
  }
  return $new; # No need to sort this. Tx Hypercube lists are sorted, and this won't upset the order.
}


# IsDimsListSubset
# ----------------
# Is Dims list x a subset of Dims list y?
function IsDimsListSubset($xDims, $yDims) {
  $xLen = strlen($xDims);
  $yLen = strlen($yDims);
  if ($xLen > $yLen || !$xLen || !$yLen)
    return false;
  for ($k=0; $k<$xLen; ++$k) {
    $c = $xDims[$k];
    if (strpos($yDims, $c) === false) {
      # c from x not in y but check for Dim 1 being a subset of Dim 2. Warning - this is UK-GAAP-DPL specific
      if ($c !== '1' || $yDims[0] !== '2')
        return false;
    }
  }
  return true;
}

# IsDimSubset
# -----------
# Is Dim (chr $dc) a subset of a Dim in the $dList of Dims (char list)
function IsDimSubset($dc, $dList) {
  return $dc === '1' && $dList[0] === '2'; # For UK-GAAP-DPL Dim 1 is a subset of Dim 2, which will always be first in $dList. (Would be calling this if $dList contained '1')
}

# BroNameFromTxName($name, $dadName=0)
# Only used by BroImport.php and BuildTxStructs.inc as of 12.01.13
# Generate a BRO Name from a Taxonomy Name, stripping invalid characters and shortening it
# $dadName = prior segments of the name for a level 1 and up names, allowing camel case words in name that are already in dadName to be removed.
# e.g. name = GeographicSegmentRevenueByDestination and dadName = PL.Revenue.GeoSeg
#           -> ByDestination
function BroNameFromTxName($name, $dadName=0) {
  static $rep = array(
    'AccountingPolicy',                               # -> AcctPolicy
    'Accounts',                                       # -> Accts
    'Acquisition',                                    # -> Acq
    'Activities',                                     # -> Activs
    'Adjustment',                                     # -> Adjust
    'Administration',                                 # -> Admin
    'Administrative',                                 # -> Admin
    'Amortisation',                                   # -> Amort
    'AssetsEntity',                                   # -> Assets
    'AssetsObtainedByTakingPossessionCollateral',     # -> AssetsPossessionCollateral
    'Associate',                                      # -> Assoc
    'Attributable',                                   # -> Attrib
    'Availabe',                                       # -> Avail    re Tx spelling error in 5 names
    'Available',                                      # -> Avail
    'BadDebtsWrittenOff',                             # -> BadDebts
    'BalanceSheet',                                   # -> BS
    'BusinessSegment',                                # -> BizSeg
    'Business',                                       # -> Biz
    'GeographicSegment',                              # -> GeoSeg
    'CashFlowStatement',                              # -> CashFlow
    'ChangeInFairValue',                              # -> ChangeValue
    'ChangesInFairValue',                             # -> ChangesValue
    'ChiefExecutive',                                 # -> CEO
    'CompanySecretary',                               # -> CoSec
    'Company',                                        # -> Co
    'Companies',                                      # -> Co
    'ComputerITEquipment',                            # -> ComputerHardware
    'Consideration',                                  # -> Consid
    'Consolidated',                                   # -> Consol
    'Consolidation',                                  # -> Consol
    'Contribution',                                   # -> Contrib
    'AgreedContrib',                                  # -> Contrib
    'CostSales',                                      # -> CoS
    'CostAdminExpenses',                              # -> CostAdmin  Administrative -> Admin already
    'CostDistributionCosts',                          # -> CostDistrib
    'DebtInstrument',                                 # -> DebtInstr
    'Depreciation',                                   # -> Depn
    'DescriptionOrOtherInformationOnThirdPartyAgent', # -> Descr
    'Description',                                    # -> Descr
    'Distribution',                                   # -> Distrib
    'Exceptional',                                    # -> Except
    'exceptional',                                    # -> except
    'ExecutiveOfficers',                              # -> Exec
    'Extraordinary',                                  # -> Extraord
    'EquityInstrument',                               # -> EquityInstr
    'FairValue',                                      # -> Value
    'FinanceArrangement',                             # -> FinArrange
    'FinancialAsset',                                 # -> FinAsset
    'FinancialInstrument',                            # -> FinInstr
    'FinancialLiabilities',                           # -> FinLiabs
    'FinancialYear',                                  # -> FinYear
    'FixedAssetInvestmentHoldings',                   # -> FAIHoldings
    'FixedAssetInvestmentLoans',                      # -> FAILoans
    'FixedAssetInvestmentTypes',                      # -> FAITypes
    'FixedAssetInvestment',                           # -> FAI
    'ForAll',                                         # -> All
    'ForCredit',                                      # -> Credit
    'ForFuture',                                      # -> Future
    'IntangibleFixedAssetClasses',                    # -> IFAClasses
    'IntangibleFixedAsset',                           # -> IFA
    'IntangibleAssets',                               # -> IFAs
    'Increase',                                       # -> Incr
    'Decrease',                                       # -> Decr
    'Impairment',                                     # -> Impair
    'Including',                                      # -> Incl
    'Excluding',                                      # -> Excl
    'Information',                                    # -> Info
    'Investment',                                     # -> Invest
    'JointVenture',                                   # -> JV
    'MovementAnalysis',                               # -> MvtAnalysis
    'Movement',                                       # -> Mvt
    'LimitedLiabilityPartnership',                    # -> LLP
    'Liabilities',                                    # -> Liabs
    'Liability',                                      # -> Liab
    'LoanLoans',                                      # -> Loans
    'NameDirectorSigningAccts',                       # -> DirectorSigningAccts
    'NameEntityOfficer',                              # -> Name
    'EntityOfficer',                                  # -> Officer
    'NameThirdPartyAgent',                            # -> Name
    'NonexecutiveOfficers',                           # -> Nonexec
    'NomineesOrTrusteesEntity',                       # -> Nominees
    'Operating',                                      # -> Op
    'Operational',                                    # -> Op
    'Operation',                                      # -> Op
    'Organisation',                                   # -> Org
    'OtherParticipatingInterests',                    # -> OtherInterests
    'OtherParticipating',                             # -> Participating
    'ParticipatingInterest',                          # -> ParticInterest
    'PostalCodeZip',                                  # -> PostCode
    'PrincipalLocationCityOrTown',                    # -> CityOrTown
    'ProfitLossAccount',                              # -> PL
    'ProfitLossItems',                                # -> PLitems
    'Rehabilitation',                                 # -> Rehab
    'Restatements',                                   # -> Restated
    'Retirement',                                     # -> Retire
    'StatementRecognisedGainsLosses',                 # -> SRGL
    'Subsidiaries',                                   # -> Subsids
    'TangibleFixedAssetClasses',                      # -> TFAClasses
    'TangibleFixedAssetOwnership',                    # -> TFAOwnership
    'TangibleFixedAsset',                             # -> TFA
    'Telecommunications',                             # -> Telecoms
    'ThirdPartyAgentType',                            # -> TPAType
    'ThirdPartyAgentStatus',                          # -> TPAStatus
    'ThirdPartyAgent',                                # -> TPA
    'TotalAllPensionSchemesThisTypeOnlyEntityPensionSchemeThisType', # -> TotalAllPensionSchemesThisType       ForAll has already been reduced to All before this one
    'TotalAllShareBasedPaymentSchemesOnlyShareBasedPaymentScheme',   # -> TotalAllShareBasedPaymentSchemes
    'TotalOtherInvests',                              # -> Others
    'Transactions',                                   # -> Trans
    'Unexceptional',                                  # -> Unexcept
    'GainLosses',                                     # -> Gain
    'GainLoss',                                       # -> Gain
    'GainsLosses',                                    # -> Gains
    'GainsLoss',                                      # -> Gains
    'IncrDecr',                                       # -> Incr
    'ContributingTo',                                 # -> ''
    'ForEntity',                                      # -> ''
    'HaveBeen',                                       # -> ''
    'Default',                                        # -> ''
    'ValueFromValuationTechniqueGrouping'             # -> '' Only with Tx elements 2742 2743
  ), $by = array(
    'AcctPolicy',
    'Accts',
    'Acq',
    'Activs',
    'Adjust',
    'Admin',
    'Admin',
    'Amort',
    'Assets',
    'AssetsPossessionCollateral',
    'Assoc',
    'Attrib',
    'Avail',
    'Avail',
    'BadDebts',
    'BS',
    'BizSeg',
    'Biz',
    'GeoSeg',
    'CashFlow',
    'ChangeValue',
    'ChangesValue',
    'CEO',
    'CoSec',
    'Co',
    'Co',
    'ComputerHardware',
    'Consid',
    'Consol',
    'Consol',
    'Contrib',
    'Contrib',
    'CoS',
    'CostAdmin',
    'CostDistrib',
    'DebtInstr',
    'Depn',
    'Descr',
    'Descr',
    'Distrib',
    'Except',
    'except',
    'Exec',
    'Extraord',
    'EquityInstr',
    'Value',
    'FinArrange',
    'FinAsset',
    'FinInstr',
    'FinLiabs',
    'FinYear',
    'FAIHoldings',
    'FAILoans',
    'FAITypes',
    'FAI',
    'All',
    'Credit',
    'Future',
    'IFAClasses',
    'IFA',
    'IFAs',
    'Incr',
    'Decr',
    'Impair',
    'Incl',
    'Excl',
    'Info',
    'Invest',
    'JV',
    'MvtAnalysis',
    'Mvt',
    'LLP',
    'Liabs',
    'Liab',
    'Loans',
    'DirectorSigningAccts',
    'Name',
    'Officer',
    'Name',
    'Nonexec',
    'Nominees',
    'Op',
    'Op',
    'Op',
    'Org',
    'OtherInterests',
    'Participating',
    'ParticInterest',
    'PostCode',
    'CityOrTown',
    'PL',
    'PLitems',
    'Rehab',
    'Restated',
    'Retire',
    'SRGL',
    'Subsids',
    'TFAClasses',
    'TFAOwnership',
    'TFA',
    'Telecoms',
    'TPAType',
    'TPAStatus',
    'TPA',
    'TotalAllPensionSchemesThisType',
    'TotalAllShareBasedPaymentSchemes',
    'Others',
    'Trans',
    'Unexcept',
    'Gain',
    'Gain',
    'Gains',
    'Gains',
    'Incr',
    '' # from ContributingTo onwards
  );
  // Remove '-' in cases where following letter is to be capitalised
  $sName = str_replace(['-based', '-cumul', '-current', '-east', '-employ', '-for', '-redeem', '-retire', '-sale', '-subsid', '-unwinding', '-venture'],
                       ['Based',  'Cumul',  'Current',  'East',  'Employ', 'For',  'Redeem',  'Retire',  'Sale',  'Subsid',  'Unwinding',  'Venture'], $name);
  $sName = preg_replace('/[^a-zA-Z0-9_\?]/', '', $sName); // strip any remaining non BRO chars e.g. '-' and '.'
  $sName = str_replace($rep, $by, $sName);
  # Other* with everything except for a trailing s, after the Other in dadName -> Others
  if (strpos($sName, 'Other') === 0  && strlen($sName) > 6 &&
      substr($sName, -1) == 's' && strpos($dadName, substr($sName, 5,-1)) !== false) return 'Others';
  # JV* with JV in dadName -> remove JV
  if (strpos($sName, 'JV') === 0  && strlen($sName) > 2 && strpos($dadName, 'JV') !== false) $sName = str_replace('JV', '', $sName);
  # Subsidiary in sName with Subsidiaries in dadName -> remove Subsidiary
  if (strpos($sName, 'Subsidiary') !== false  && strlen($sName) > 10 && strpos($dadName, 'Subsidiaries') !== false) $sName = str_replace('Subsidiary', '', $sName);
  # IFAs in sName with IFA in dadName -> remove IFAs
  if (strpos($sName, 'IFAs') !== false  && strlen($sName) > 4 && strpos($dadName, 'IFA') !== false) $sName = str_replace('IFAs', '', $sName);
  # TFAs in sName with TFA in dadName -> remove TFAs
  if (strpos($sName, 'TFAs') !== false  && strlen($sName) > 4 && strpos($dadName, 'TFA') !== false) $sName = str_replace('TFAs', '', $sName);
  # FinInstrs in sName with FinInstr in dadName -> remove FinInstrs
  if (strpos($sName, 'FinInstrs') !== false  && strlen($sName) > 9 && strpos($dadName, 'FinInstr') !== false) $sName = str_replace('FinInstrs', '', $sName);
  # Exchanges ...
  if ($dadName === 'StockExs')
    $sName = str_replace(array(
      'UKLondon',                # -> UKLSE
      'UKAlternateInvestMarket', # -> UKAIM
      'USNewYork',               # -> USNYSE
      'Berliner',                # -> Berlin
      'LithuaniaNational',       # -> Lithuania
      'KyrgyzstanKyrgyz',        # -> .Kyrgyzstan
      'StockExchange',           # -> '' from here on
      'Exchange',
      'OMStockholm',
      'Euronext',
      'Amsterdam', # Euronext
      'Brussels',  # Euronext
      'Paris',     # Euronext
      'Abidjan',
      'Algiers',
      'Amman',
      'Armenian',
      'Asuncion',
      'Athens',
      'BanjaLuka',
      'Beirut',
      'Belgrade',
      'Bucharest',
      'Budapest',
      'CairoAlexandria',
      'Caracas',
      'Casablanca',
      'Colombo',
      'Copenhagen',
      'Doha',
      'Helsinki',
      'Irish',
      'aItaliana', # re Italy
      'Istanbul',
      'JSE',
      'Khartoum',
      'KievUniversal',
      'Lima',
      'Lisbon',
      'Ljubljana',
      'Lusaka',
      'Maputo',
      'Mexican',
      'Montevideo',
      'Muscat',
      'Nairobi',
      'Oslo',
      'Prague',
      'PortMoresby',
      'Riga',
      'SlovakSKRMS',
      'SouthPacific',
      'Tallinn',
      'DarEsSalaam',
      'Teheran',
      'TelAviv',
      'Tirana',
      'Warsaw',
      'Wiener',
      'Zagreb',
      'boerse',
      'Boerse',
      'Bolsa',
      'DeValoresNacional',
      'NacionalDeValores',
      'DeValores',
      'Continuo',
      'DeComercio',
      'BoerseInternational',
      'Bors',
      'BorsaItaliana',
      'Bourse',
      'Bursa',
      'DeValeurs',
      'DesValeurs',
      'LaBolsa',
      'YProductos',
      'Mobilieres',
      'QuotingSystemInc',
      'Regionale',
      'SecuritiesMarket',
      'Market',
      'InternationalSecurities',
      'Securities',
      'Trading',
      'Wertpapier',
      'RheinisheWestfaelische',
      'Hanseatische',
      'Niedersaechsische',
      'BadenWuerttembergische'),
      array(
      'UKLSE',
      'UKAIM',
      'USNYSE',
      'Berlin',
      'Lithuania',
      'Kyrgyzstan',
      ''), $sName);
  $dadName .= 'An'; // strip CC word 'An'
  $camelCaseWords = preg_split('/(?<=\\w)(?=[A-Z,0-9])/', $sName); // digits too
  foreach ($camelCaseWords as $i => $word)
    if (strlen($word) > 1 && $word != 'Net' && $word != 'Non' && $word != 'Current'  && $word != 'Participating' && strpos($dadName, $word) !== false) unset($camelCaseWords[$i]);
  $tName = implode('', $camelCaseWords);
  if (!$tName) { // if name went to nothing
    // $sName = $word; // last CC word
    // $dadSegs = explode('.', substr($dadName, 0, -2)); // substr to remove 'An' added above /- last segment of dadName
    // $sName = $dadSegs[count($dadSegs)-1];                                                  |
    $camelCaseWords = preg_split('/(?<=\\w)(?=[A-Z,0-9])/', $sName); # /- first CC word
    $sName = $camelCaseWords[0];                                     # |
    // echo "Name after -> nothing = $sName<br>";
  }else
    $sName = $tName;
  # All* with everything,  or with everything except for a trailing s, after the All in dadName -> All
  if (strpos($sName, 'All') === 0  && strlen($sName) > 3 &&
     ((strpos($dadName, substr($sName, 3)) !== false) || (substr($sName, -1) == 's' && strpos($dadName, substr($sName, 3,-1)) !== false))) return 'All';
  # Starts: Entity -> remove
  if (strpos($sName, 'Entity') === 0  && strlen($sName) > 6) $sName = substr($sName, 6);
  # Starts: For with next letter uc -> remove
  if (strpos($sName, 'For') === 0  && strlen($sName) > 3 && ctype_upper($sName[3])) $sName = substr($sName, 3);
  # Starts: On with next letter uc -> remove
  if (strpos($sName, 'On') === 0  && strlen($sName) > 2 && ctype_upper($sName[2])) $sName = substr($sName, 2);
  # Starts: TotalAll -> Total
  if (strpos($sName, 'TotalAll') === 0) $sName = str_replace('All', '', $sName);
  # Endings zapped: To, For
  if (substr($sName, -2) == 'To'  && strlen($sName) > 2) $sName = substr($sName, 0, -2);
  if (substr($sName, -3) == 'For' && strlen($sName) > 3) $sName = substr($sName, 0, -3);
  # Endings: ProfitLoss -> PL, Account -> Acct
  if (substr($sName, -10) == 'ProfitLoss' && strlen($sName) > 10) $sName = substr($sName, 0, -10) . 'PL';
  if (substr($sName,  -7) == 'Account'    && strlen($sName) >  7) $sName = substr($sName, 0,  -7) . 'Acct';
  # if ($sName != $name)
  #  echo "$name -> $sName<br>";
  if ($sName == 'Other' && substr($name,-1) == 's') return 'Others';
  return $sName;
}

