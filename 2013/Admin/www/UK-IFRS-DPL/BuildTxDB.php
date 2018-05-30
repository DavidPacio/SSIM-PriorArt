<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/UK-IFRS-DPL/BuildTxDB.php

Reads UK-IFRS-DPL Taxonomy xsd and xml files and stores the info in DB tables.
Builds Braiins specific Tx based tables and structs.

Uses taxonomies from HMRC's CT2013-2.0.0.zip

Not
UK-ALL-2009-09-01-dated-2010-12-31.zip
or
UK-IFRS-2009-09-01-dated-2010-12-31.zip
from
http://www.xbrl.org.uk/techguidance/taxonomies.html

History:
26.06.13 Started based on UK-GAAP-DPL version

ToDo djh??
====

See ///// Functions which are candidates for removal

*/

require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsTx.inc';
require Com_Inc_Tx.'TxElementsSkipped.inc';
require 'inc/BuildTxStructs.inc';

Head("Build $TxName DB");

if (!isset($_POST['Sure']) || strtolower($_POST['Sure']) != 'yes') {
  echo <<< FORM
<h2 class=c>Build the UK-IFRS-DPL DB</h2>
<p class=c>Running this will empty all the UK-IFRS-DPL Tables and then rebuild them from the Taxonomy. The process will take about 30 seconds.<br>Once started, the process should be allowed to run to completion.</p>
<form method=post>
<p class=c><input class=radio type=checkbox name=Skip value=1 checked=checked> Skip the list of elements not needed for UK-IFRS-DPL. Uncheck this box to build the full UK-IFRS-DPL DB.</p>
<div class=c>Sure? (Enter Yes if you are.) <input name=Sure size=3 value=Yes> <button class=on>Go</button></div>
</form>
FORM;
  $CentredB = true;
  Footer(false);
  exit;
}

$SkipB = isset($_POST['Skip']);
if (!$SkipB) $TxElementsSkippedA = null; # so can just test on $TxElementsSkippedA

set_time_limit(60);
$RolesMapA =     # [Role  => [Id, usedOn, definition, ElId, FileId, Uses]]
$ArcrRolesMapA = # [Arcrole => [Id, usedOn, definition, cyclesAllowed, FileId, Uses]]
$NsMapA    =     # [namespace => [NsId, Prefix, File, Num]
$NamesMapA =     # [NsId.name =>  ElId]
$XidsMapA  =     # [xid   => ElId]  Not written to a table
$DocMapA   =     # [Label => [ElId, ArcId, DocId]] where label is the To of Arcs and the Label of Labels and references; ElId is the FromId of the Arc.
$TextMapA  =     # [Text  => [TextId, Uses]]
$TuplesA   = []; # [tupleName => complexContent for the tuple element in $complexA]

$XR = new XMLReader();

$tablesA = [
  'Arcroles',
  'Arcs',
  'Doc',
  'Elements',
  'Imports',
  'LinkbaseRefs',
  'Namespaces',
  'Roles',
  'Schema',
  'Text',
  'Hypercubes',
  'Dimensions',
  'DimensionMembers',
  'TuplePairs'
];

$RefsA = [
  'Name' => 0,              # ref:Name
  'Number' => 0,            # ref:Number
  'Paragraph' => 0,         # ref:Paragraph
  'Subparagraph' => 0,      # ref:Subparagraph
  'Section' => 0,           # ref:Section
  'Subsection' => 0,        # ref:Subsection
  'Clause' => 0,            # ref:Clause
  'Appendix' => 0,          # ref:Appendix
  'Publisher' => 0,         # ref:Publisher
  'IssueDate' => 0,         # ref:IssueDate  uk-ifrs
  'Example' => 0,           # ref:Example    uk-ifrs
  'Schedule' => '',         # uk-gaap-ref:Schedule
 #'Part' => '',             # uk-gaap-ref:Part
  'Abstract' => '',         # uk-gaap-ref:Abstract
  'Year' => '',             # uk-gaap-ref:Year
  'ISOName' => 0,           # uk-cd-ref:ISOName
  'Code' => 0,              # uk-cd-ref:Code
  'AlternativeCode' => 0,   # uk-cd-ref:AlternativeCode
  'Date' => 0,              # uk-cd-ref:Date
  'Description' => 0,       # uk-cd-ref:Description
  'ExchangeAcronym' => 0,   # uk-cd-ref:ExchangeAcronym
  'HomeCity' => 0,          # uk-cd-ref:HomeCity
  'HomeCountry' => 0,       # uk-cd-ref:HomeCountry
  'HomeCountryCode' => 0,   # uk-cd-ref:HomeCountryCode
  'MarketIdentificationCode' # uk-cd-ref:MarketIdentificationCode
  => 0];

echo '<h2 class=c>Building the Braiins '.($SkipB? 'UK-IFRS-DPL less unwanted Elements' : 'Full UK-IFRS-DPL').' Database</h2>
<b>Resetting the DB to Empty, truncating table:</b><br>
';
# Empty the DB
foreach ($tablesA as $table) {
  echo $table, '<br>';
  $DB->StQuery("Truncate Table `$table`"); # MySQL complained about Schema without the backticks
}

# Change directory and set the imports. Used to only do the first one but now set all in order to stick with the UK-GAAP order for UK-GAAP-DPL re element Ids
$cwd = getcwd(); # to return when finished with Tx reading
chdir('../../Taxonomies/CT2013-2.0.0/'); # from /Admin/www/Utils/UK-IFRS-DPL to Taxonomy base dir

#DB->StQuery("Insert Imports set Location='uk-gaap-main-2009-09-01.xsd'");
#DB->StQuery("Insert Imports set Location='www.hmrc.gov.uk/schemas/ct/dpl/2012-10-01/dpl-gaap-main-2012-10-01.xsd'");      # DPL (GAAP) entry point
#DB->StQuery("Insert Imports set Location='www.hmrc.gov.uk/schemas/ct/combined/2012-10-01/full-gaap-dpl-2012-10-01.xsd'"); # UK GAAP (full) + DPL (GAAP) entry point
$DB->StQuery("Insert Imports set Location='www.hmrc.gov.uk/schemas/ct/combined/2013-02-01/full-ifrs-dpl-2013-02-01.xsd'"); # UK IFRS (full) + DPL (GAAP) entry point

# Add the Roles to get them in desired Id order to avoid needing to add an 'Order" column
# The first 6 are defined as constants, plus TR_FirstHypercubeId for the first hypercube.

# NB: Do not change these without also changing the TR_ constants which are the Ids from these inserts.

# Sources:
# .../uk-ifrs       B:\Admin\Taxonomies\CT2013-2.0.0\www.xbrl.org\uk\ifrs\core\2009-09-01\uk-ifrs-2009-09-01.xsd
# .../uk-bus        B:\Admin\Taxonomies\CT2013-2.0.0\www.xbrl.org\uk\cd\business\2009-09-01\uk-bus-2009-09-01.xsd
# .../uk-countries  B:\Admin\Taxonomies\CT2013-2.0.0\www.xbrl.org\uk\cd\countries-regions\2009-09-01\uk-countries-2009-09-01.xsd
# .../uk-currencies B:\Admin\Taxonomies\CT2013-2.0.0\www.xbrl.org\uk\cd\currencies\2009-09-01\uk-currencies-2009-09-01.xsd
# .../uk-exchanges  B:\Admin\Taxonomies\CT2013-2.0.0\www.xbrl.org\uk\cd\exchanges\2009-09-01\uk-exchanges-2009-09-01.xsd
# .../uk-languages  B:\Admin\Taxonomies\CT2013-2.0.0\www.xbrl.org\uk\cd\languages\2009-09-01\uk-languages-2009-09-01.xsd
# .../uk-aurep      B:\Admin\Taxonomies\CT2013-2.0.0\www.xbrl.org\uk\reports\aurep\2009-09-01\uk-aurep-2009-09-01.xsd
# .../uk-dirrep     B:\Admin\Taxonomies\CT2013-2.0.0\www.xbrl.org\uk\reports\direp\2009-09-01\uk-direp-2009-09-01.xsd
# .../dpl-core      B:\Admin\Taxonomies\CT2013-2.0.0\www.hmrc.gov.uk\schemas\ct\dpl\2013-02-01\dpl-core\2013-02-01\dpl-2013-02-01.xsd
# .../ifrs-roles    B:\Admin\Taxonomies\CT2013-2.0.0\xbrl.iasb.org\taxonomy\2009-04-01\ifrs-roles_2009-04-01.xsd
$rolesA = [
  ['label',            'labels',     'Standard Label'], # role, usedOn definition              Some of the Table 8 label roles from B:/Info/Specs/XBRL/XBRL-RECOMMENDATION-2003-12-31+Corrected-Errata-2008-07-02.htm
  ['totalLabel',       'labels',     'Total Label'],    # http://www.xbrl.org/2003/role/totalLabel  uk-ifrs, not uk-gaap
  ['periodStartLabel', 'labels',     'Period Start Label'],
  ['periodEndLabel',   'labels',     'Period End Label'],
  ['documentation',    'labels',     'Documentation'],
  ['terseLabel',       'labels',     'Terse Label'],    # http://www.xbrl.org/2003/role/terseLabel  uk-ifrs, not uk-gaap  Short label for a concept, often omitting text that should be inferable when the concept is reported in the context of other related concepts.
  ['verboseLabel',     'labels',     'Verbose Label'],
  ['positiveLabel',    'labels',     'Positive Label'], # http://www.xbrl.org/2003/role/positiveLabel  uk-ifrs, not uk-gaap
  ['negativeLabel',    'labels',     'Negative Label'], # http://www.xbrl.org/2003/role/negativeLabel  uk-ifrs, not uk-gaap
  ['reference',        'references', 'Reference'],
  ['link',             'labels & references', 'Label or Reference Link'],
  ['disclosureRef',    'references', 'Disclosure Reference'],
  ['recommendedDisclosureRef', 'references', 'Recommended Disclosure Reference'], # uk-ifrs, not uk-gaap
  ['presentationRef',  'references', 'Presentation Reference'], # http://www.xbrl.org/2003/role/presentationRef  uk-ifrs, not uk-gaap  Reference to documentation which details an explanation of the presentation, placement or labelling of this concept in the context of other concepts in one or more specific types of business reports
  ['definitionRef',    'references', 'Definition Reference'],
  ['measurementRef',   'references', 'Measurement Reference'],  # http://www.xbrl.org/2003/role/measurementRef   uk-ifrs, not uk-gaap  Reference concerning the method(s) required to be used when measuring values associated with this concept in business reports
  ['exampleRef',       'references', 'Example Reference'],      # http://www.xbrl.org/2003/role/exampleRef       uk-ifrs, not uk-gaap  Reference to documentation that illustrates by example the application of the concept that assists in determining appropriate usage.
  'uk/cd/Entity-Information',                    # 01 - Entity Information                      .../uk-bus
  'uk/cd/Business-Report-Information',           # 02 - Business Report Information             .../uk-bus
  'uk/DirectorsReport',                          # 05 - Directors' Report                       .../uk-dirrep
  'uk/AuditorsReport',                           # 06 - Auditor's Report                        .../uk-aurep
  'uk/IncomeStatement',                          # 10 - Income Statement (Profit and Loss Account)                                  .../uk-ifrs
  'uk/StatementOfComprehensiveIncome',           # 11 - Statement of Comprehensive Income                                           .../uk-ifrs
  'uk/StatementOfFinancialPosition',             # 12 - Statement of Financial Position                                             .../uk-ifrs
  'uk/StatementOfChangesInEquity',               # 13 - Statement of Changes in Equity                                              .../uk-ifrs
  'uk/CashFlowStatement',                        # 14 - Cash Flow Statement                                                         .../uk-ifrs
  'uk/NotesAndDetailedDisclosures',              # 20 - Notes and Detailed Disclosures                                              .../uk-ifrs
  'dpl-core/FullDetailedProfitandLoss',          # 31 - Full Detailed Profit and Loss                           .../dpl-core
  'uk/AdditionalIndustrySectorData',             # 50 - Additional Industry Sector Data                                             .../uk-ifrs
  'uk/all/CountriesAndRegions',                  # 94 - Countries and Regions                   .../uk-countries
  'uk/all/StockExchanges',                       # 95 - Stock Exchanges                         .../uk-exchanges
  'uk/all/Currencies',                           # 96 - Currencies                              .../uk-currencies
  'uk/all/Languages',                            # 97 - Languages                               .../uk-languages
  'uk/cd/General-Purpose-Contact-Information',   # 98 - General Purpose Contact Information     .../uk-bus
  'uk/cd/XBRL-Document-Information',             # 99 - XBRL Document Information               .../uk-bus
  'uk/Dimension-GroupAndCompany',                # 100 - Group and Company                                                          .../uk-ifrs
  'uk/Dimension-ContinuingAndDiscontinued',      # 105 - Dimension - Continuing and Discontinued                                    .../uk-ifrs
  'uk/Dimension-Restatements',                   # 110 - Dimension - Restatements                                                   .../uk-ifrs
  'uk/Dimension-Exceptionals',                   # 120 - Dimension - Exceptionals                                                   .../uk-ifrs
  'uk/Dimension-EquityClasses',                  # 130 - Dimension - Equity Classes                                                 .../uk-ifrs
  'uk/Dimension-OperatingSegments',              # 150 - Dimension - Operating Segments                                             .../uk-ifrs
  'uk/Dimension-ProductsAndServices',            # 170 - Dimension - Products And Services                                          .../uk-ifrs
  'uk/Dimension-MajorCustomers',                 # 180 - Dimension - Major Customers                                                .../uk-ifrs
  'uk/Dimension-BusinessCombinations',           # 190 - Dimension - Business Combinations                                          .../uk-ifrs
  'uk/Dimension-PPEClasses',                     # 200 - Dimension - PPE Classes                                                    .../uk-ifrs
  'uk/Dimension-IntangibleAssetClasses',         # 210 - Dimension - Intangible Asset Classes                                       .../uk-ifrs
  'uk/Dimension-IntangibleAssetsGenerationType', # 220 - Dimension - Intangible Assets Generation Type                              .../uk-ifrs
  'uk/Dimension-ImpairmentAllowanceAccountType', # 225 - Dimension - Impairment Allowance Account Type                              .../uk-ifrs
  'uk/Dimension-FinancialInstrumentValueType',   # 230 - Dimension - Financial Instrument Value Type                                .../uk-ifrs
  'uk/Dimension-FICurrent-Non-Current',          # 232 - Dimension - Financial Instrument Current and Non-Current                   .../uk-ifrs
  'uk/Dimension-FinancialInstrumentLevel',       # 233 - Dimension - Financial Instrument Level                                     .../uk-ifrs
  'uk/Dimension-FinInstrumentMovements',         # 234 - Dimension - Financial Instrument Movements                                 .../uk-ifrs
  'uk/Dimension-IndustryConcentrationRisk',      # 235 - Dimension - Industry Concentration Risk                                    .../uk-ifrs
  'uk/Dimension-CreditRatings',                  # 238 - Dimension - Credit Ratings                                                 .../uk-ifrs
  'uk/Dimension-MaturitiesOrExpirationPeriods',  # 240 - Dimension - Maturities Or Expiration Periods                               .../uk-ifrs
  'uk/Dimension-DeferredTaxClasses',             # 250 - Dimension - Deferred Tax Classes                                           .../uk-ifrs
  'uk/Dimension-ProvisionsClasses',              # 260 - Dimension - Provisions Classes                                             .../uk-ifrs
  'uk/Dimension-PensionSchemes',                 # 270 - Dimension - Pension Schemes                                                .../uk-ifrs
  'uk/Dimension-Share-basedPaymentSchemes',      # 280 - Dimension - Share-based Payment Schemes                                    .../uk-ifrs
  'uk/Dimension-ContingentLiabilitiesClasses',   # 290 - Dimension - Contingent Liabilities Classes                                 .../uk-ifrs
  'uk/Dimension-RelatedParties',                 # 300 - Dimension - Related Parties                                                .../uk-ifrs
  'uk/Dimension-RelatedPartyTransactionType',    # 310 - Dimension - Related Party Transaction Type                                 .../uk-ifrs
  'uk/Dimension-Subsidiaries',                   # 320 - Dimension - Subsidiaries                                                   .../uk-ifrs
  'uk/Dimension-Associates',                     # 330 - Dimension - Associates                                                     .../uk-ifrs
  'uk/Dimension-Joint-Ventures',                 # 340 - Dimension - Joint-Ventures                                                 .../uk-ifrs
  'uk/Dimension-DataNotForUse',                  # 399 - Dimension - Data Not For Use                                               .../uk-ifrs
  'uk/cd/Dimension-EntityOfficers',              # 500 - Dimension - Entity Officers            .../uk-bus
  'uk/cd/Dimension-EntityOfficerType',           # 501 - Dimension - Entity Officer Type        .../uk-bus
  'uk/cd/Dimension-ShareClasses',                # 502 - Dimension - Share Classes              .../uk-bus
  'uk/cd/Dimension-ShareTypes',                  # 503 - Dimension - Share Types                .../uk-bus
  'uk/cd/Dimension-EntityContactType',           # 505 - Dimension - Entity Contact Type        .../uk-bus
  'uk/cd/Dimension-ThirdPartyAgentType',         # 508 - Dimension - Third Party Agent Type     .../uk-bus
  'uk/cd/Dimension-ThirdPartyAgentStatus',       # 509 - Dimension - Third Party Agent Status   .../uk-bus
  'uk/cd/Dimension-FormOfContact',               # 511 - Dimension - Form of Contact            .../uk-bus
  'uk/cd/Dimension-AddressType',                 # 512 - Dimension - Address Type               .../uk-bus
  'uk/cd/Dimension-PhoneNumberType',             # 513 - Dimension - Phone Number Type          .../uk-bus
  'uk/all/Dimension-CountriesAndRegions',        # 520 - Dimension - Countries and Regions      .../uk-countries
  'uk/all/Dimension-Currencies',                 # 521 - Dimension - Currencies                 .../uk-currencies
  'uk/all/Dimension-StockExchanges',             # 522 - Dimension - Stock Exchanges            .../uk-exchanges
  'uk/all/Dimension-Languages',                  # 523 - Dimension - Languages                  .../uk-languages
  'dpl-core/Dimension-Activity',                     # 550 - Dimension - Activity                               .../dpl-core
  'dpl-core/Dimension-ExpenseType',                  # 551 - Dimension - Expense Type                           .../dpl-core
  'dpl-core/Dimension-ExceptionalNonExceptionaltems',# 552 - Dimension - Exceptional and Non-Exceptional Items  .../dpl-core
  'dpl-core/Dimension-DetailedAnalysis',             # 553 - Dimension - Detailed Analysis                      .../dpl-core
  'dpl-core/Dimension-IntraExtraGroupTransactions',  # 554 - Dimension - Intra / extra group transactions       .../dpl-core
  'uk/Hypercube-Basic',                          # 600 - Hypercube - Basic                 id=Dimensions-GeneralData                .../uk-ifrs
  'uk/Hypercube-Income-Cash-Data',               # 610 - Hypercube - Income - Cash - Data  id=Dimensions-ContinuingAndDiscontinued  .../uk-ifrs
  'uk/Hypercube-BalanceSheetData',               # 612 - Hypercube - Balance Sheet Data    id=Hypercube-Balance-Cash-Primary        .../uk-ifrs
  'uk/Hypercube-Equity',                         # 620 - Hypercube - Equity                id=Dimensions-Equity                     .../uk-ifrs
  'uk/Hypercube-ContinuingDiscontinued',         # 625 - Hypercube - Continuing Discontinued Operations                             .../uk-ifrs
  'uk/Hypercube-OperatingSegments',              # 630 - Hypercube - Operating Segments    id=Hypercube-Segments                    .../uk-ifrs
  'uk/Hypercube-ProductsServicesSegments',       # 631 - Hypercube - Product and Services Segments                                  .../uk-ifrs
  'uk/Hypercube-MajorCustomerSegments',          # 632 - Hypercube - Major Customer Segments                                        .../uk-ifrs
  'uk/Hypercube-BusinessCombinations',           # 640 - Hypercube - Business Combinations                                          .../uk-ifrs
  'uk/Hypercube-BusinessCombinationsIntangibles',# 641 - Hypercube - Business Combinations - Intangibles                            .../uk-ifrs
  'uk/Hypercube-BusinessCombinationsPPE',        # 642 - Hypercube - Business Combinations - PPE                                    .../uk-ifrs
  'uk/Hypercube-BusinessCombinationsProvisions', # 643 - Hypercube - Business Combinations - Provisions                             .../uk-ifrs
  'uk/Hypercube-BusinessCombinationsContingentLiabilities', # 644 - Hypercube - Business Combinations - Contingent Liabilities      .../uk-ifrs
  'uk/Hypercube-PPEIncomeItems',                 # 650 - Hypercube - PPE Income Items                                               .../uk-ifrs
  'uk/Hypercube-IntangiblesIncomeItems',         # 651 - Hypercube - Intangibles Income Items                                           .../uk-ifrs
  'uk/Hypercube-PropertyPlantEquipment',         # 660 - Hypercube - Property, Plant and Equipment id=Dimensions-PropertyPlantEquipment .../uk-ifrs
  'uk/Hypercube-IntangibleAssets',               # 665 - Hypercube - Intangible Assets             id=Dimensions-IntangibleAssets       .../uk-ifrs
  'uk/Hypercube-ImpairmentAllowanceAccount',     # 667 - Hypercube - Impairment Allowance Account                                       .../uk-ifrs
  'uk/Hypercube-FinancialInstruments',           # 670 - Hypercube - Financial Instruments         id=Dimensions-FinancialInstruments                .../uk-ifrs
  'uk/Hypercube-FinancialInstrumentsConcentrationRisk', # 671 - Hypercube - Financial Instruments Concentration Risk                                 .../uk-ifrs
  'uk/Hypercube-FinancialInstrumentMovements',   # 672 - Hypercube - Financial Instrument Movements                                                  .../uk-ifrs
  'uk/Hypercube-CreditRatings',                  # 674 - Hypercube - Credit Ratings                                                                  .../uk-ifrs
  'uk/Hypercube-Maturities',                     # 675 - Hypercube - Maturities                                                                      .../uk-ifrs
  'uk/Hypercube-DeferredTaxAssetsLiabilities',   # 680 - Hypercube - Deferred Tax Assets and Liabilities  id=Dimensions-DeferredTaxAssetsLiabilities .../uk-ifrs
  'uk/Hypercube-Provisions',                     # 690 - Hypercube - Provisions             id=Dimensions-Provisions                                 .../uk-ifrs
  'uk/Hypercube-ContingentLiabilities',          # 700 - Hypercube - Contingent Liabilities id=Dimensions-ContingentLiabilities                      .../uk-ifrs
  'uk/Hypercube-PensionSchemes',                 # 730 - Hypercube - Pension Schemes        id=Dimensions-PensionSchemes                  .../uk-ifrs
  'uk/Hypercube-Share-basedPaymentSchemes',      # 740 - Hypercube - Share-based Payment Schemes  id=Dimensions-Share-basedPaymentSchemes .../uk-ifrs
  'uk/Hypercube-ShareCapitalAndDividends',       # 750 - Hypercube - Share Capital and Dividends                                          .../uk-ifrs
  'uk/Hypercube-RelatedParties',                 # 760 - Hypercube - Related Parties                                                      .../uk-ifrs
  'uk/Hypercube-Subsidiaries',                   # 770 - Hypercube - Subsidiaries                                                         .../uk-ifrs
  'uk/Hypercube-Associates',                     # 780 - Hypercube - Associates                                                           .../uk-ifrs
  'uk/Hypercube-Joint-Ventures',                 # 790 - Hypercube - Joint-Ventures                                                       .../uk-ifrs
  'dpl-core/Hypercube-DetailedProfitAndLoss',    # 850 - Hypercube - Detailed Profit and Loss               .../dpl-core
  'uk/Hypercube-DataNotForUse',                  # 899 - Hypercube - Data Not For Use                                                     .../uk-ifrs
  'uk/cd/Hypercube-EntityOfficers',              # 900 - Hypercube - Entity Officers            .../uk-bus
  'uk/cd/Hypercube-Shares',                      # 901 - Hypercube - Shares                     .../uk-bus
  'uk/cd/Hypercube-EntityContactInfo',           # 905 - Hypercube - Entity Contact Info        .../uk-bus
  'uk/cd/Hypercube-ThirdPartyAgents',            # 906 - Hypercube - Third Party Agents         .../uk-bus
  'uk/cd/Hypercube-Countries',                   # 910 - Hypercube - Countries                  .../uk-bus
  'uk/cd/Hypercube-Currencies',                  # 911 - Hypercube - Currencies                 .../uk-bus
  'uk/cd/Hypercube-StockExchanges',              # 912 - Hypercube - Stock Exchanges            .../uk-bus
  'uk/cd/Hypercube-Languages',                   # 913 - Hypercube - Languages                  .../uk-bus
  'uk/cd/Hypercube-Empty',                       # 999 - Hypercube - Empty                      .../uk-bus
  'uk/Dimension-ShareClasses',                   # 450 - Dimension - Share Classes              .../uk-dirrep Not used i.e. no Braiins Dimension so moved out of sequence so that Dimension Roles are parallel to DimIds
  'uk/Dimension-ShareTypes',                     # 451 - Dimension - Share Types                .../uk-dirrep Not used
  'xbrl.iasb.reference/commonPracticeRef',           # Reference for common practice disclosure     .../ifrs-roles
  'xbrl.iasb.reference/nonauthoritativeLiterature',  # Non-authoritative literature                 .../ifrs-roles
  'xbrl.iasb.reference/recognitionRef',              # Reference for recognition and derecognition  .../ifrs-roles
  'xbrl.iasb.label/labelNegated',                    # Negated standard label                       .../ifrs-roles
  'xbrl.iasb.label/netLabel',                        # Net label                                    .../ifrs-roles
  'xbrl.iasb.label/netLabelNegated',                 # Negated net label                            .../ifrs-roles
  'xbrl.iasb.label/periodEndLabelNegated',           # Negated period end label                     .../ifrs-roles
  'xbrl.iasb.label/periodStartLabelNegated',         # Negated period start label                   .../ifrs-roles
  'xbrl.iasb.label/presentationGuidanceNegated',     # Negated presentation guidance                .../ifrs-roles
  'xbrl.iasb.label/terseLabelNegated',               # Negated terse label                          .../ifrs-roles
  'xbrl.iasb.label/totalLabelNegated',               # Negated total label                          .../ifrs-roles
];
/*
  'uk/STRGL',                                  # 13 - Statement of Total Recognised Gains and Losses
  'uk/NoteHistoricalCostProfits',              # 14 - Note of Historical Cost Profits and Losses
  'uk/ReconciliationMovementsShareholderFunds',# 15 - Reconciliation of Movements in Shareholder Funds
  'uk/Notes',                                  # 20 - Notes and Detailed Disclosures
  'uk/DetailedProfitLoss',                     # 30 - Detailed Profit and Loss
  'uk/Dimension-ShareClasses',                 # 450 - Dimension - Share Classes  /- Out of order because they are not used by UK GAAP i.e. no Element for these.
  'uk/Dimension-ShareTypes',                   # 451 - Dimension - Share Types    |  Putting these here means that Roles are parallel to Dimensions & Hypercubes
  'uk/Dimension-Consolidation',                # 105 - Dimension - Consolidation
  'uk/Dimension-Restatements',                 # 110 - Dimension - Restatements
  'uk/Dimension-OperatingActivities',          # 120 - Dimension - Operating Activities
  'uk/Dimension-ExceptionalItemAdjustments',   # 150 - Dimension - Exceptional Item Adjustments
  'uk/Dimension-AmortisationImpairmentAdjustments', # 160 - Dimension - Amortisation and Impairment Adjustments
  'uk/Dimension-BusinessSegments',             # 170 - Dimension - Business Segments
  'uk/Dimension-ProvisionsClasses',            # 190 - Dimension - Provisions Classes
  'uk/Dimension-IntangibleFixedAssetClasses',  # 200 - Dimension - Intangible Fixed Asset Classes
  'uk/Dimension-TangibleFixedAssetClasses',    # 210 - Dimension - Tangible Fixed Asset Classes
  'uk/Dimension-TangibleFixedAssetOwnership',  # 220 - Dimension - Tangible Fixed Asset Ownership
  'uk/Dimension-FixedAssetInvestmentHoldings', # 230 - Dimension - Fixed Asset Investment Holdings
  'uk/Dimension-FixedAssetInvestmentTypes',    # 240 - Dimension - Fixed Asset Investment Types
  'uk/Dimension-Dividends',                    # 250 - Dimension - Dividends
  'uk/Dimension-PensionSchemes',               # 260 - Dimension - PensionSchemes
  'uk/Dimension-Share-basedPaymentSchemes',    # 265 - Dimension - Share-based Payment Schemes
  'uk/Dimension-FinancialInstrumentValueType', # 270 - Dimension - Financial Instrument Value Type
  'uk/Dimension-FinancialInstrumentCurrentNon-Current', # 271 - Dimension - Financial Instrument Current and Non-Current
  'uk/Dimension-FinancialInstrumentLevel',              # 272 - Dimension - Financial Instrument Level
  'uk/Dimension-FinancialInstrumentMovements', # 273 - Dimension - Financial Instrument Movements
  'uk/Dimension-MaturitiesExpirationPeriods',  # 275 - Dimension - Maturities or Expiration Periods
  'uk/Dimension-Acquisitions',                 # 300 - Dimension - Acquisitions
  'uk/Dimension-AcquisitionAssetsLiabilities', # 305 - Dimension - Acquisition Assets and Liabilities
  'uk/Dimension-Disposals',                    # 310 - Dimension - Disposals
  'uk/Dimension-Joint-Ventures',               # 320 - Dimension - Joint-Ventures
  'uk/Dimension-Associates',                   # 325 - Dimension - Associates
  'uk/Dimension-Subsidiaries',                 # 330 - Dimension - Subsidiaries
  'uk/Dimension-OtherInterests-Investments',   # 336 - Dimension - Other Interests - Investments
  'uk/Hypercube-IncomeDataAllDimensions',            # 610 - Hypercube - Income Data All Dimensions
  'uk/Hypercube-TangibleAssetDisposal',              # 612 - Hypercube - Tangible Asset Disposal
  'uk/Hypercube-IntangibleAssetDisposal',            # 613 - Hypercube - Intangible Asset Disposal
  'uk/Hypercube-TangibleAssetExpenses',              # 614 - Hypercube - Tangible Asset Expenses
  'uk/Hypercube-IntangibleAssetExpenses',            # 615 - Hypercube - Intangible Asset Expenses
  'uk/Hypercube-ExceptionalsAmortisation',           # 620 - Hypercube - Exceptionals, Amortisation
  'uk/Hypercube-ConsolExceptionalsAmortisation',     # 630 - Hypercube - Consolidation, Exceptionals, Amortisation
  'uk/Hypercube-OperatingActivitiesExceptionals',    # 640 - Hypercube - Op Activities, Exceptionals
  'uk/Hypercube-OperatingActivitiesExceptionalsAmortisation', # 650 - Hypercube - Op Activities, Exceptionals, Amortisation
  'uk/Hypercube-OperatingActivitiesConsolGeogBusiness',       # 660 - Hypercube - Op Activities, Consolidation, Geog, Business
  'uk/Hypercube-OperatingActivitiesConsol',          # 670 - Hypercube - Op Activities, Consolidation
  'uk/Hypercube-OperatingActivities',                # 680 - Hypercube - Operating Activities
  'uk/Hypercube-Consolidation',                      # 685 - Hypercube - Consolidation
  'uk/Hypercube-BusinessSegmentsIncomeData',         # 690 - Hypercube - Business Segments Income Data
  'uk/Hypercube-GeographicSegmentsIncomeData',       # 700 - Hypercube - Geographic Segments Income Data
  'uk/Hypercube-BusinessSegmentsAssetData',          # 710 - Hypercube - Business Segments Asset Data
  'uk/Hypercube-GeographicSegmentsAssetData',        # 720 - Hypercube - Geographic Segments Asset Data
  'uk/Hypercube-BusinessSegmentsBasic',              # 730 - Hypercube - Business Segments Basic
  'uk/Hypercube-GeographicSegmentsBasic',            # 740 - Hypercube - Geographic Segments Basic
  'uk/Hypercube-Provisions',                         # 750 - Hypercube - Provisions
  'uk/Hypercube-IntangibleFixedAssets',              # 760 - Hypercube - Intangible Fixed Assets
  'uk/Hypercube-TangibleFixedAssets',                # 770 - Hypercube - Tangible Fixed Assets
  'uk/Hypercube-FixedAssetInvestments',              # 780 - Hypercube - Fixed Asset Investments
  'uk/Hypercube-FixedAssetInvestmentLoans',          # 790 - Hypercube - Fixed Asset Investment Loans
  'uk/Hypercube-Dividends',                          # 800 - Hypercube - Dividends
  'uk/Hypercube-PensionSchemes',                     # 810 - Hypercube - Pension Schemes
  'uk/Hypercube-Share-basedPaymentSchemes',          # 811 - Hypercube - Share-based Payment Schemes
  'uk/Hypercube-FinancialInstruments',               # 820 - Hypercube - Financial Instruments
  'uk/Hypercube-FinancialInstrumentMovements',       # 821 - Hypercube - Financial Instrument Movements
  'uk/Hypercube-Acquisitions',                       # 830 - Hypercube - Acquisitions
  'uk/Hypercube-Shares-Acquisitions',                # 831 - Hypercube - Shares - Acquisitions
  'uk/Hypercube-Disposals',                          # 834 - Hypercube - Disposals
  'uk/Hypercube-Joint-Ventures',                     # 840 - Hypercube - Joint-Ventures
  'uk/Hypercube-Associates',                         # 841 - Hypercube - Associates
  'uk/Hypercube-Subsidiaries',                       # 842 - Hypercube - Subsidiaries
  'uk/Hypercube-OtherInterests-Investments',         # 844 - Hypercube - Other Interests - Investments
  'dpl-gaap/Hypercube-DetailedProfitAndLossReserve', # 851 - Hypercube - Detailed Profit and Loss Account Reserve
  'uk/Essence-AliasLinks'                            # 9991 - Essence-Alias Links
*/
$id=0;
foreach ($rolesA as $role) {
  ++$id;
  if (is_array($role))
    list($role, $usedOn, $definition) = $role;
  else
    $usedOn = $definition = 0;
  $RolesMapA[$role] = ['Id' => $id, 'usedOn' => $usedOn, 'definition' => $definition, 'ElId' => 0, 'FileId' => 0, 'Uses' => 0];
}
unset($rolesA);

$arcrolesA = array(
  'parent-child',
  'essence-alias',
  'dim/all',
  'dim/dimension-default',
  'dim/dimension-domain',
  'dim/domain-member',
  'dim/hypercube-dimension',
  'dim/notAll',
  'concept-label',
  'concept-reference'
);
$id=0;
foreach ($arcrolesA as $arcrole)
  $ArcrRolesMapA[$arcrole] = array('Id' => ++$id, 'usedOn' => 0, 'definition' => 0, 'cyclesAllowed' => 0, 'FileId' => 0, 'Uses' => 0);
unset($arcrolesA);

$DB->autocommit(false);
echo '<br><b>Importing Schemas</b><br>';
$totalNodes = $FileId = 0;
while ($o = $DB->OptObjQuery("Select Id,Location From Imports where Id>$FileId Order by Id Limit 1")) {
  $FileId = (int)$o->Id;
  $File   =      $o->Location;
  #Echo "Importing $FileId: $File<br>";
  if (InStr('ref-2004-08-10.xsd', $File)) {
    # Skip due to duplicate Ids and no new or newer elements
    echo "$FileId: $File skipped due to duplicate Ids with no new or newer elements<br>";
    continue;
  }
  $NodesA = [];
  $NodeNum = 0;
  if (@$XR->open($File) === false) die("Die - unable to open $File");
  #LogIt("Reading $File Nodes");
  Nodes($XR);
  $XR->close();
  $NumNodes = $NodeNum;
  $NodeNum  = 0;
  $totalNodes += $NumNodes;
  echo "$FileId: $File, $NumNodes nodes read -> Total nodes = ", number_format($totalNodes), '<br>';
  flush();
  Schema();
}
# Now the Tuples table
#Dump('TuplesMap',$TuplesA);
$tupId = 0;
foreach ($TuplesA as $tuple => $complexA) { # array NsId.tupleName => complexContent for the tuple element in $complexA
++$tupId;
  if (!isset($NamesMapA[$tuple])) die("Die - Tuple $tuple not in NamesMapA as expected");
  $tupTxId = $NamesMapA[$tuple];
  if ($TxElementsSkippedA && in_array($tupTxId, $TxElementsSkippedA)) continue; # Skip adding the tuple
  #$elsA = []; # in order to insert with the element Ids in ascending order. The tupleIds are sorted as they come but not the elements.
                    # Sorting by member TxId removed with addition of Ordr - leave sorted by that
  $order = 1;
  foreach ($complexA as $nodeA) {
    if ($nodeA['tag'] == 'element') {
      $attributesA = $nodeA['attributes'];
      $ref       = $attributesA['ref']; # uk-gaap:DescriptionChangeInAccountingPolicyItsImpact, uk-direp:ExercisePriceOption
      $minOccurs = $attributesA['minOccurs']; # 0 or 1           Only ever have 0,1         => O
      $maxOccurs = $attributesA['maxOccurs']; # 1 or unbounded                  1,1            M
      if ($maxOccurs == 'unbounded') { #                                        1,unbounded    U
        $maxOccurs = 255;
        $TUCN = TUC_U; # 3 U Optional Unbounded corresponding to Taxonomy minOccurs=0 and maxOccurs=unbounded
      }else{
        if ($minOccurs)
          $TUCN = TUC_M; # 2 M Mandatory once if tuple used corresponding to Taxonomy minOccurs=1 and maxOccurs=1
        else
          $TUCN = TUC_O; # 1 O, Optional once corresponding to Taxonomy minOccurs=0 and maxOccurs=1
      }
     #$elName = substr($ref, strpos($ref, ':')+1);  # after uk-gaap:, uk-direp:
      $elNameSegsA = explode(':', $ref); # ns and name
      $nsId=0;
      foreach ($NsMapA as $nsA)
        if ($nsA['Prefix'] == $elNameSegsA[0]) {
          $nsId = $nsA['NsId'];
          break;
        }
      if (!$nsId) die("Die - No namespace forund for Tuple member ref $ref");
      $elName = "$nsId.$elNameSegsA[1]";
      if (!isset($NamesMapA[$elName])) die("Die - Tuple $tuple not in NamesMapA as expected");
      $memTxId = $NamesMapA[$elName];
      if ($TxElementsSkippedA && in_array($memTxId, $TxElementsSkippedA)) continue; # Skip adding the member
      $DB->StQuery("Insert TuplePairs Set TupId=$tupId,TupTxId=$tupTxId,MemTxId=$memTxId,Ordr=$order,minOccurs=$minOccurs,maxOccurs=$maxOccurs,TUCN=$TUCN");
      #$elsA[$memTxId] = array($order, $minOccurs, $maxOccurs);
      ++$order;
    }
  }
  #ksort($elsA);
  #foreach ($elsA as $memTxId => $propsA)
  #  $DB->StQuery("Insert Tuples Set TupTxId=$tupTxId,MemTxId=$memTxId,Ordr=$propsA[0],minOccurs=$propsA[1],maxOccurs=$propsA[2]");
}

echo '<br><b>Importing Linkbases</b><br>';
$res = $DB->ResQuery("Select Id,href From LinkbaseRefs");
while ($o = $res->fetch_object()) {
  $LinkbaseId = (int)$o->Id;
  $FileId = $LinkbaseId + 100; # for namespaces table
  $File   = $o->href;
  $NodesA = [];
  $NodeNum = 0;
  if (@$XR->open($File) === false)
    die("Die - unable to open $File");
  #LogIt("Reading $File Nodes");
  Nodes($XR);
  $XR->close();
  $NumNodes = $NodeNum;
  $NodeNum  = 0;
  $totalNodes += $NumNodes;
  echo "$LinkbaseId: $File, $NumNodes nodes read -> Total nodes = ", number_format($totalNodes), '<br>';
  flush();
  Linkbase();
}
$res->free();

echo "<br>Elements and Arcs inserted.<br>";

# Insert the Roles
# $RolesMapA [Role => [Id, usedOn, definition, ElId, FileId, Uses]]
foreach ($RolesMapA as $role => $roleA) {
  $id = $DB->InsertQuery("Insert Roles Set Role='$role',usedOn='$roleA[usedOn]',definition='$roleA[definition]',ElId=$roleA[ElId],FileId=$roleA[FileId],Uses=$roleA[Uses]");
  if ($id != $roleA['Id']) die("Die Id $id on Role Insert not $roleA[Id] as expected");
}

# Insert the Arcroles
# $ArcrRolesMapA [Arcrole => [Id, usedOn, definition, cyclesAllowed, FileId, Uses]]
foreach ($ArcrRolesMapA as $arcrole => $arcroleA) {
  $set = "Arcrole='$arcrole',FileId=$arcroleA[FileId],Uses=$arcroleA[Uses]";
  if ($arcroleA['usedOn'])        $set .= ",usedOn='$arcroleA[usedOn]'";
  if ($arcroleA['definition'])    $set .= ",definition='$arcroleA[definition]'";
  if ($arcroleA['cyclesAllowed']) $set .= ",cyclesAllowed='$arcroleA[cyclesAllowed]'";
  $id = $DB->InsertQuery("Insert Arcroles Set $set");
  if ($id != $arcroleA['Id']) die("Die Id $id on Arcrole Insert not $arcroleA[Id] as expected");
}

# Insert the Namespaces
# $NsMapA [namespace => [NsId, Prefix, File, Num]
foreach ($NsMapA as $ns => $nsA) {
  $id = $DB->InsertQuery("Insert Namespaces Set namespace='$ns',Prefix='$nsA[Prefix]',File='$nsA[File]',Num=$nsA[Num]");
  if ($id != $nsA['NsId']) die("Die Id $id on Namespaces Insert not $nsA[NsId] as expected");
}

# Insert Text
foreach ($TextMapA as $text => $textA) { # array Text => [TextId, Uses]
  $id = $DB->InsertQuery("Insert Text Set Text='$text',Uses=$textA[Uses]");
  if ($id != $textA['TextId']) die("Die Id $id on Text Insert not $textA[TextId] as expected");
}

# Update the Arc Label and Reference ToIds
# $DocMapA [Label => [ElId, ArcId, DocId]]
foreach ($DocMapA as $docMapA) # [ElId, ArcId, DocId]
  if ($docMapA['ArcId']) # 0 for skipped label/ref arcs that are in $docMapA re skipping the labels/refs
    $DB->StQuery("Update Arcs Set ToId=$docMapA[DocId] Where Id=$docMapA[ArcId]");


echo "<br>Roles, Arcroles, Namespaces, and Text inserted; Arc From and To fields updated.<br>";
$DB->commit();

/////////////////////////
// Post Main Build Ops //
/////////////////////////

echo '<br><b>Taxonomy Fixups</b><br>';
echo 'None so far for UK-IFRS<br>';

# Delete redundant Arcs

/* Prohibited
   ----------
50 definition relationships are defined in cd, then cancelled in gaap by an arc with a use="prohibited" term.
Gaap defines another relationship instead. (Priority and order also come into it).
The 50 all related to Hypercube - Emtpy elements.
Why define them at all then? Presumably because cd was once stand alone.
For my working DB I might remove these relationships which are cancelled as I see no point in having them.

07.11.11 PRoleId and ArcroleId equivalence added to the delete query. Without this 13 other wanted arcs were beling deleted.
4 Arcs deleted with FromId=5254 and ToId=5129
3 Arcs deleted with FromId=5129 and ToId=5152
3 Arcs deleted with FromId=5129 and ToId=5124
3 Arcs deleted with FromId=5129 and ToId=5256
4 Arcs deleted with FromId=5256 and ToId=5181
3 Arcs deleted with FromId=5181 and ToId=5209
3 Arcs deleted with FromId=5181 and ToId=5118
4 Arcs deleted with FromId=5256 and ToId=5215
4 Arcs deleted with FromId=5254 and ToId=5255

All 2 now as below.
*/

/*
echo '<br><b>Deleting Redundant Arcs</b><br>Arcs cancelled by a Prohibited ArcUse attribute:<br>';
$nArcs = 0;
$res = $DB->ResQuery('Select FromId,ToId,PRoleId,ArcroleId from Arcs Where ArcUseN=2');
while ($o = $res->fetch_object()) {
  $DB->StQuery("Delete from Arcs Where FromId=$o->FromId And ToId=$o->ToId And PRoleId=$o->PRoleId And ArcroleId=$o->ArcroleId");
  echo "$DB->affected_rows Arcs deleted with FromId=$o->FromId and ToId=$o->ToId<br>";
  $nArcs += $DB->affected_rows;
}
$res->free();
echo "$nArcs Arcs deleted where a Prohibited ArcUse attribute was used to cancel an Arc.<br>";

*/

/* 4062 ProvisionsForLiabilitiesCharges as per Multiple Taxonomy Linkages but why? email of 04 April 2011 in Email folder Taxonomy

My conclusions:

1. When two arcs are identical except for order and priority, the higher order/priority one overrides the other. This would remove one of the Hypercube 750 ones.

2. If two arcs are identical except for the 'preferredLabel' with the preferredLabel being period related, and if the to concept is of duration "instant" (i.e. can have different period contexts), then keep both arcs. This means that in my RgNames table I need to allow for identical tag names with different preferredLabels (or contexts) for such cases.

Re 1) I could:
a) Include code for deciding on override conditions when fetching relationship info. (This is also required for the cases mentioned in "Findings" where some arcs are cancelled by a higher priority one with "prohibited" set.)

b) Delete lower order/priority (overridden) arcs from the DB, thus avoiding the need to do unnecessary fetches and override calcs on every relationship lookup.


I wanted an SQL query which would give me definition arcs (not concerned about presentation ones) of the same type for which there are duplicates.

When programming it, a brute force method could be used i.e. check each arc in turn, but with 26,740 of them (18,432 being definition type ones) that would not be very smart.

So I wrote a single query to find them:

Select Distinct A.Id,A.FromId,A.ToId from Arcs A Join Arcs B on B.FromId=A.FromId and B.ToId=A.ToId where B.Id <> A.Id and A.ArcRoleId>1 and A.ArcRoleId = B.ArcRoleId order by A.ToId,A.FromId

or
Select Distinct A.Id, A.FromId, A.ToId from Arcs A
 Join Arcs B on B.FromId=A.FromId and B.ToId=A.ToId
 where B.Id <> A.Id and A.ArcRoleId>1 and A.ArcRoleId = B.ArcRoleId
 order by A.ToId,A.FromId

Note how I have joined Arcs with Arcs to get this working. (It is possible to join a table with itself.)
ArcRoleId>1 excludes presentation arcs.

- use of Distinct to say only show me results that are different
- joining a table to itself. Arcs to Arcs in this case. When doing this it is necessary to use a an "as" name for at least one of the tables or there would be no way to tell one from the other in the query

Select Distinct A.Id,A.FromId,A.ToId,A.priority,A.ArcOrder from Arcs A Join Arcs B on B.FromId=A.FromId and B.ToId=A.ToId Join Elements E on E.Id=A.ToId Where B.Id <> A.Id and A.ArcRoleId>1 and A.ArcRoleId = B.ArcRoleId and E.PeriodN=2 Order by A.ToId,A.FromId,priority desc,ArcOrder desc

 Query with no Arc deletes done in Tx Build

 170 with PeriodN=1 incl 511, 4062, 5230 and 5234        Instant
 159 with PeriodN=2 not incl 511, 4062, 5230 and 5234    Duration

 And if the ArcUseN=2 deletes are done

 152 with PeriodN=1 incl 511, 4062, 5230 and 5234        Instant
  68 with PeriodN=2 not incl 511, 4062, 5230 and 5234    Duration

 And with PRoleId equivalence added:
 134 with PeriodN=1 incl 511, 4062, 5230 and 5234        Instant
  14 with PeriodN=2 not incl 511, 4062, 5230 and 5234    Duration as below

SQL query: Select Distinct A.Id,A.FromId,A.ToId,A.priority,A.ArcOrder from Arcs A Join Arcs B on B.FromId=A.FromId and B.ToId=A.ToId Join Elements E on E.Id=A.ToId Where B.Id <> A.Id and A.ArcRoleId>1 and A.ArcRoleId = B.ArcRoleId and A.PRoleId=B.PRoleId And E.PeriodN=2 Order by A.ToId,A.FromId,priority desc,ArcOrder desc;
Rows: 14
Id   FromId  ToId   priority ArcOrder
9054   2561  1537   1        10000000  String
8607   2561  1537   NULL     8000000
11004  4538  4547   1        15000000  Money with PeriodStart/End preferred labels for a Duration item?
9723   4538  4547   NULL     13000000
10999  4540  4548   1        15000000  Money with PeriodStart/End preferred labels for a Duration item?
9689   4540  4548   NULL     13000000
10994  4543  4549   1        15000000  Money with PeriodStart/End preferred labels for a Duration item?
9659   4543  4549   NULL     13000000
10989  4545  4550   1        15000000  Money with PeriodStart/End preferred labels for a Duration item?
9630   4545  4550   NULL     13000000
9059   904   4617   3        23000000  String
9058   904   4617   2        19000000
9056   904   4617   1        17000000
8921   904   4617   NULL     11000000

 And E.TypeN=2 for String ones

07.11.11 Changed to delete just the 4 String ones of the 14 duration ones as above

*/

/*
echo "<br>Deleting Duplicate Arcs:<br>";
# 07.11.11 Changed to delete just the 4 String ones of the 14 duration ones as above
# $res = $DB->ResQuery('Select Distinct A.Id,A.FromId,A.ToId,A.priority,A.ArcOrder from Arcs A Join Arcs B on B.FromId=A.FromId and B.ToId=A.ToId where B.Id <> A.Id and A.ArcRoleId>1 and A.ArcRoleId = B.ArcRoleId order by A.ToId,A.FromId,priority desc,ArcOrder desc');
$res = $DB->ResQuery('Select Distinct A.Id,A.FromId,A.ToId,A.priority,A.ArcOrder from Arcs A Join Arcs B on B.FromId=A.FromId and B.ToId=A.ToId Join Elements E on E.Id=A.ToId Where B.Id <> A.Id and A.ArcRoleId>1 and A.ArcRoleId = B.ArcRoleId and A.PRoleId=B.PRoleId And E.PeriodN=2 And E.TypeN=2 Order by A.ToId,A.FromId,priority desc,ArcOrder desc');
$prevToId = $n = 0;
while ($o = $res->fetch_object()) {
  $id   = (int)$o->Id;
  $toId = (int)$o->ToId;
  if ($toId == $prevToId) {
    $DB->StQuery("Delete from Arcs Where Id=$id");
    echo "Arc $id with FromId=$o->FromId, ToId=$toId, priority=$o->priority, ArcOrder=$o->ArcOrder deleted<br>";
    $n++;
  }else
    $prevToId = $toId;
}
$res->free();
$nArcs += $n;
echo "$n duplicate Arcs deleted.<br>$nArcs arcs deleted in total<br><br>";
*/

# Fix label wording/spelling errors
/*
echo '<b>Fixing label wording/spelling errors</b><br>';
# 410   (Benefits paid) related to the defined benefit scheme
$DB->StQuery("Update Text Set Text='Benefits paid related to the defined benefit scheme' Where Text='(Benefits paid) related to the defined benefit scheme'");
echo "'(Benefits paid) related to the defined benefit scheme' changed to 'Benefits paid related to the defined benefit scheme'<br>";

# ( loss) -> (loss)
$res = $DB->ResQuery("Select Id,Text from Text Where Text like '%( loss)%'");
while ($o = $res->fetch_object()) {
  $id = (int)$o->Id;
  $text = str_replace('( loss)', '(loss)', $o->Text);
  $DB->StQuery("Update Text Set Text='$text' Where Id=$id");
  echo "'( loss)' changed to '(loss)' in Text.Id $id $text<br>";
}
$res->free();
# 4659 Tangible fixed assets, depreciation, increase (decease) from acquisitions
$DB->StQuery("Update Text Set Text='Tangible fixed assets, depreciation, increase (decrease) from acquisitions' Where Text='Tangible fixed assets, depreciation, increase (decease) from acquisitions'");
echo "'Tangible fixed assets, depreciation, increase (decease) from acquisitions' changed to 'Tangible fixed assets, depreciation, increase (decrease) from acquisitions'<br>";
*/

# Update Elements.StdLabelTxtId  # djh?? get smarter and do this in one query
echo "<br><b>Updating Elements.StdLabelTxtId to speed Standard Label Fetches</b><br>";
$res = $DB->ResQuery('Select Id From Elements Where SubstGroupN>0');
$n = 0;
while ($o = $res->fetch_object()) {
  $id = (int)$o->Id; # A.TypeN=3 is A.TypeN=TLT_Label; L.RoleId=1 is L.RoleId=TR_StdLabel; 02.10.12 Group by TextId added re the 5729 Country Dimension having a Verbose Label defined by DPL with a different LinkBaseId from the Standard Label => 2 results here rather than 1.
 #if ($txtId = $DB->OneQuery("Select L.TextId from Arcs A Join Labels L on L.LabelId=A.ToId Where A.TypeN=3 And A.FromId=$id And L.RoleId=1 Group by TextId")) {
 #if ($txtId = $DB->OneQuery("Select TextId from Doc Where RoleId=1 And ElId=$id")) { # UK-IFRS 6253 ContinuingAndDiscontinuedOperationsDimension has two Standard labels so this gave nothing
  if ($txtId = $DB->OneQuery("Select TextId from Doc Where RoleId=1 And ElId=$id Order by ElId Limit 1")) { # Take the first one re the above
    $DB->StQuery("Update Elements Set StdLabelTxtId=$txtId Where Id=$id");
    $n++;
  }
}
$res->free();
echo "$n Elements updated.<br><br>\n";

# Build Dimension Tables
# ======================
echo "<b>Building Dimension Tables</b><br>\n";
$dimensionElId = $n = $defaultId = $NumDiMes = $DiMeId = 0;
$res = $DB->ResQuery("Select A.* From Arcs A Join Elements E on E.Id=A.FromId Where E.SubstGroupN=3 And A.ArcroleId>1 And A.ArcroleId<=7 Order by A.PRoleId,A.FromId,A.ArcroleId");
while ($o = $res->fetch_object()) {
  $fromId  = (int)$o->FromId;
  $toId    = (int)$o->ToId;
  $pRoleId = (int)$o->PRoleId;
  if ($fromId != $dimensionElId) {
    if ($dimensionElId)
      InsertDimensionMembers($dimId, $defaultId);
    $dimensionElId = $fromId;
    $DB->StQuery("Update Roles Set ElId=$dimensionElId Where Id=$pRoleId");
    $dimId = $DB->InsertQuery("Insert Dimensions Set ElId=$dimensionElId,RoleId=$pRoleId");
    echo "Dim $dimId ", ElName($fromId), " $fromId (", Role($pRoleId, true), ")<br>\n";
    $defaultId = 0;
    $DiMeIdsA = [];
    $n++;
  }
  $name = ElName($toId);
  switch ($o->ArcroleId) {
    case TA_DimDefault: # dim/dimension-default   |  Dimension default member                        Source (a dimension) declares that there is a default member that is the target of the arc (a member).
      RecordDimensionMember(0, $name, $toId);
      $defaultId = $toId;
      break;

    case TA_DimDomain:  # dim/dimension-domain    |  Dimension has only target domain as its domain  Source (a dimension) has only the target (a domain) as its domain.
      if (strpos($name, 'Heading') === false)
        RecordDimensionMember($level=0, $name, $toId);
      else
        $level = -1; # incremented to 0 in FromTrees2()
      FromTrees2($toId, $pRoleId, $level);
      break;

    default: die("Die - Unexpected ArcroleId $o->ArcroleId in Arc $o->Id From = $fromId, To=$toId - Arc ignored");
  }
}
InsertDimensionMembers($dimId, $defaultId);
$res->free();

echo "<br>$n Dimensions and $NumDiMes Dimension Members added<br>\n";

function FromTrees2($fromId, $pRoleId, $level) {
  global $DB;
  $res = $DB->ResQuery('Select ToId,PRoleId from Arcs Where ArcroleId=' . TA_DomainMember . " and FromId=$fromId and PRoleId=$pRoleId Order by ArcroleId,TargetRoleId,ArcOrder");
  if ($res->num_rows) {
    ++$level;
    while ($o = $res->fetch_object()) {
      $toId = (int)$o->ToId;
      $name = ElName($toId);
      if (strpos($name, 'Heading') === false)
        RecordDimensionMember($level, $name, $toId);
      FromTrees2($toId, $pRoleId, $level);
    }
  }
  $res->free();
}

function RecordDimensionMember($level, $name, $elId) {
  global $DiMeIdsA;
  $DiMeIdsA[] = array('level' => $level, 'name' => $name, 'elId' =>$elId);
}

function InsertDimensionMembers($dimId, $defaultId) {
  global $DB, $DiMeIdsA, $DiMeId;
  $insertedElsA = [];
  $ta_DimDomainB = false;
  if ($defaultId && $defaultId != ElId_NotApplicable) # don't skip TA_DimDefault for NotApplicable
    foreach ($DiMeIdsA as $i => $diMeA) # [level, name, elId]  # Only 1 in the end - Dim 3 RestatementsDimension
      if ($i && $diMeA['elId'] == $defaultId) {
        $ta_DimDomainB = true;
        break;
      }
  foreach ($DiMeIdsA as $i => $diMeA) { # [level, name, elId]
    if (!$i && $ta_DimDomainB) # skip the TA_DimDefault default if also in TA_DimDomain
      continue;
    extract($diMeA); # -> $level, $name, $elId
    if (isset($insertedElsA[$elId]))
      echo "&nbsp;&nbsp;&nbsp;&nbsp;$name $elId already inserted for Dim $dimId<br>";
    else{
      if ($elId === ElId_NotApplicable) { # NotApplicable is always default
        $bits = DiMeB_Default;
        $name .= ' (Default)';
      }else if (strpos($name, 'Default') !== false)
        $bits = DiMeB_Default;
      else
        $bits = DiMeB_Normal;
      if (!$defaultId && $bits == DiMeB_Default)
        $defaultId = $elId;
      /*
      if (in_array($elId, array(408, 409, 924, 4055))) # pull back those with only 1 indented item
        --$level;                                      # DiMeIds: 88, 85, 178, 128
     #if ($dimId ==  DimId_OpActivities || $dimId == DimId_TPAStatus) # All OpActivities and TPAStatus -> level 0 as don't want sum
      if ($dimId ==  DimId_OpActivities) # 4 All OpActivities From level 1 -> level 0 as don't want sum
        $level = 0;
      if (in_array($elId, array(3285, 3287, 3288))) # Bump DiMeIds 251, 252, 253 FIMvts.NetIncrInLevel3Purchases, FIMvts.NetIncrInLevel3Sales, FIMvts.NetIncrInLevel3Settlements up a level so will kids sum to 250 3286 FIMvts.NetIncrInLevel3PurchasesSalesSettlements
        ++$level;                                   # Added 06.10.12 after noticing need while working on shortening names
      if ($elId >= 3775 && $elId <= 3782) # Bump PensionScheme1 - PensionScheme8 up a level so will kids sum
        ++$level;
      if ($elId >= 5543 && $elId <= 5562) # Pull PartnerLLP1 - PartnerLLP20 back a level to avoid a kids sum to Director 40
        --$level;
      # NB: At this point $DiMeId is the Id of the previous DiMe
      if ($DiMeId >= 265  && $DiMeId <=  268) # Bump Periods.BetweenOneFiveYears - Periods.MoreThanFiveYears up a level so will kids sum to Periods.AfterOneYear
        ++$level;                             # Added 17.11.11 after observation by Charles that 266 and 269 were wrongly at same level as 265.
      # NB: At this point $DiMeId is the Id of the previous DiMe
      if ($dimId == DimId_Consol && $DiMeId > 3) # pull all Consol after Consol.Consol back a level as we don't want them summing to Consol.Consol
        --$level;
      */
      $DiMeId = $DB->InsertQuery("Insert DimensionMembers Set DimId=$dimId,ElId=$elId,Bits=$bits,Level=$level");
      OutputDiMe($level, $name, $elId);
      # djh?? 5461 Entity accountants or auditors fudge!
      #if ($DiMeId == DiMeId_Accountants) { # Fudge for [A] 5461 Entity accountants or auditors to allow Accountants and Auditors separately in the Dimensions Map
      #  $DB->StQuery("Insert DimensionMembers Set DimId=$dimId,ElId=$elId,Bits=$bits,Level=$level"); # repeat the insert to become DiMeId_Auditors
      #  OutputDiMe($level, $name, $elId);
      #}
      $insertedElsA[$elId] = 1;
    }
  }
  if (!$defaultId) echo "&nbsp;&nbsp;&nbsp;&nbsp;No default<br>\n";
}

function OutputDiMe($level, $name, $elId) {
  global $NumDiMes, $DiMeId;
  ++$NumDiMes;
  #for ($i=0; $i <= $level; $i++)
  #  echo '&nbsp;&nbsp;';
  #echo "$DiMeId $level $name $elId<br>";
}

# Build Hypercubes Table
# ======================
echo "<br><b>Building Hypercube Table</b><br>\n<p>Dimensions listed apply to the Hypercube which comes after them in the list.</p>";
# Read the Dimensions
$dimensions = []; # dimensions by Elements.Id
$res = $DB->ResQuery('Select * From Dimensions');
while ($o = $res->fetch_object())
  $dimensions[(int)$o->ElId] = (int)$o->Id;
$res->free();

# Hypercubes => Dimensions
$hypercubeElId = 0;
$dimsS = '';
$res = $DB->ResQuery("Select * From Arcs Where ArcroleId=7 Order by PRoleId,FromId,TargetRoleId");
while ($o = $res->fetch_object()) {
  $fromId  = (int)$o->FromId;
  $toId    = (int)$o->ToId;
  if ($fromId != $hypercubeElId) {
    if ($hypercubeElId) {
      $DB->StQuery("Update Roles Set ElId=$hypercubeElId Where Id=$pRoleId");
      $dimsS = addslashes($dimsS); # 02.10.12 added re Hy 44 \
      $hId = $DB->InsertQuery("Insert Hypercubes Set ElId=$hypercubeElId,RoleId=$pRoleId,Dimensions='$dimsS'");
      echo "$hId $hypercubeElId ", ElName($hypercubeElId), ' (', Role($pRoleId), ")<br>\n";
    }
    $hypercubeElId = $fromId;
    $pRoleId = (int)$o->PRoleId;
    $dimsS = '';
  }
  echo "&nbsp;&nbsp;&nbsp;&nbsp;$toId ", ElName($toId), ' (', Role($o->TargetRoleId), ")<br>\n";
  $dimsS .= chr($dimensions[$toId]+48);
}
$res->free();
$DB->StQuery("Update Roles Set ElId=$hypercubeElId Where Id=$pRoleId");
$hId = $DB->InsertQuery("Insert Hypercubes Set ElId=$hypercubeElId,RoleId=$pRoleId,Dimensions='$dimsS'");
echo "$hId $hypercubeElId ", ElName($hypercubeElId), ' (', Role($pRoleId), ")<br>\n";
$hId = $DB->InsertQuery("Insert Hypercubes Set ElId=".ElId_EmptyHypercube.",RoleId=0,Dimensions=''"); # The Empty Hypercube
echo "$hId ",ElId_EmptyHypercube,' ', ElName(ElId_EmptyHypercube), ' (', Role(0), ")<br>\n";

# Add Hypercube Lists to Concrete Item Elements
# =============================================
echo "<br><b>Updating Elements.Hypercubes for Concrete Item Elements.</b></br>\n";
$roleIdToHyIdA = [];
$res = $DB->ResQuery('Select Id,RoleId From Hypercubes');
while ($o = $res->fetch_object())
  $roleIdToHyIdA[(int)$o->RoleId] = (int)$o->Id;
$res->free();

$n = 0;
$res = $DB->ResQuery('Select Id From Elements Where abstract is null and SubstGroupN=1');
while ($o = $res->fetch_object()) {
  $id       = (int)$o->Id;
  $txRolesA = GetParentRoles($id, 0, true);
  $num = count($txRolesA);
  if ($num) {
    if ($num > 5)
      die("Die - $num hypercubes have been found but a maximum of 5 is expected. Something is wrong...");
    $listA = [];
    foreach ($txRolesA as $roleId => $t)
      #echo "$roleId ";
      if (isset($roleIdToHyIdA[$roleId])) # 30.09.12 Added because with DPL got $roleId=125 uk/cd/Hypercube-Empty cases (131 for UK-IFRS)
        $listA[] = $roleIdToHyIdA[$roleId];
    $t = IntAToChrList($listA); # IntAToChrList() sorts and eliminates duplicates
    $list = addslashes($t);
    $DB->StQuery("Update Elements Set Hypercubes='$list' Where Id=$id");
    $n++;
  }
}
$res->free();
echo "Done for $n elements.<br>";
$DB->commit();

# Build the Taxonomy Based Structs
chdir($cwd); # from Taxonomy base dir back to prog dir so that Com_Inc_Tx is correct in BuildTxBasedStructs()
BuildTxBasedStructs();

Footer();
#########

function GetParentRoles($toId, $pRoleId, $first=false) {
  global $DB;
  static $parentRolesA, $level;
  if ($first) {
    $parentRolesA = [];
    $level = 1;
  }else
    $level++;
  # echo "GetParentRoles() toId $toId, pRoleId $pRoleId, level $level<br>";
  if ($pRoleId < TR_FirstHypercubeId) {
    $res = $DB->ResQuery("Select FromId,PRoleId From Arcs Where ArcroleId In(3,4,5,6,7) and ToId=$toId");
    if ($res->num_rows)
      while ($o = $res->fetch_object())
        GetParentRoles((int)$o->FromId, (int)$o->PRoleId);
  }
  if ($pRoleId >= TR_FirstHypercubeId) # 28.05.11 >= TR_FirstHypercubeId added RegisteredOffice ending up with 62 505 - Dimension - Entity Contact Type in the list
    $parentRolesA[$pRoleId] = 1; # 27.05.11 Changed from [] so count(array) = # hypers
  $level--;
  # echo "GetParentRoles() evel $level<br>";
  if (!$level)
    return $parentRolesA;
}

######################
## Schema functions ##
######################

function Schema() {
  global $NodesA, $NumNodes, $NodeNum, $File, $SchemaId;
  $tag  = $NodesA[0]['tag']; # could have a prefix e.g. xs:schema
  $set  = "Location='$File'";
  $node = $NodesA[$NodeNum++]; # schema node and step over the <schena tag
  $nsId = AddNamespace('', $node['attributes']['targetNamespace']);
  foreach ($node['attributes'] as $a => $v) {
    if (strpos($a, 'xmlns') === 0) { # namespace
      AddNamespace($a, $v);    # 'xmlns' => 'http://www.w3.org/2001/XMLSchema',
      continue;                # 'xmlns:uk-gaap-all' => 'http://www.xbrl.org/uk/gaap/core-all',
    }
    if (!strlen($v)) {
      echo "Ignoring empty attribute '$a' in NodeNum $NodeNum in Schema $File <br>";
      continue;
    }
    $set .= ",$a='$v'";
  }
  $SchemaId = Insert('Schema', $set);
  while ($NodeNum < $NumNodes) {
    switch (LessPrefix($NodesA[$NodeNum]['tag'])) {
      case 'annotation':
        $NodeNum++; # over the annotation
        while ($NodeNum < $NumNodes && $NodesA[$NodeNum]['depth'] > 1) { # <annotation has a depth of 1
          switch (LessPrefix($NodesA[$NodeNum]['tag'])) {
            case 'appinfo':
              $NodeNum++; # over the appinfo
              while ($NodeNum < $NumNodes && $NodesA[$NodeNum]['depth'] > 2) { # <appinfo has a depth of 2
                switch ($NodesA[$NodeNum]['tag']) {
                  case 'link:linkbaseRef': LinkbaseRef(); break;
                  case 'link:roleType':    RoleType();    break;
                  case 'arcroleType':      ArcroleType(); break;
                  default: die("Die - unknown schema annotation appinfo tag {$NodesA[$NodeNum]['tag']}<br>");
                }
              }
              break;
            case 'documentation': $NodeNum++; break; # skip
            default: die("Die - unknown schema annotation tag {$NodesA[$NodeNum]['tag']}<br>");
          }
        }
        break;
      case 'attribute':      Attribute();    break;
      case 'attributeGroup': AttributeGroup(); break;
      case 'complexType':    ComplexType();  break;
      case 'element':        Element($nsId); break;
      case 'import':         Import();       break;
      case 'simpleType':     SimpleType();   break;
      default: die("Die - unknown schema tag {$NodesA[$NodeNum]['tag']}<br>");
    }
  }
}


function Element($nsId) {
  global $NodesA, $NumNodes, $NodeNum, $XidsMapA, $NamesMapA, $TxElementsSkippedA;
  static $ElIdS=0; # re skipping elements and preserving full build element Ids
  ++$ElIdS;
  $node = $NodesA[$NodeNum++];
  $depth = $node['depth'];
  $set = "Id=$ElIdS,NsId='$nsId'";
  $name = $xid = $SubstGroupN = $tuple = 0; # $tuple is set to '$nsId.$name' for the taple case for passing to ComplexType()
  foreach ($node['attributes'] as $a => $v) {
    switch ($a) {
      case 'id':   $xid =$v; continue 2; # SetIdDef($xid=$v, $set); continue 2; # IdId
      case 'name': $name=$v; continue 2;
      case 'type':
        $a = 'TypeN';
        switch ($v) {
          case 'xbrli:monetaryItemType': $v = TET_Money; break;
          case 'string':
          case 'xbrli:stringItemType':   $v = TET_String; break;
          case 'xbrli:booleanItemType':  $v = TET_Boolean;break;
          case 'xbrli:dateItemType':     $v = TET_Date;   break;
          case 'decimal':
          case 'xbrli:decimalItemType':  $v = TET_Decimal; break;
          case 'xbrli:integerItemType':  $v = TET_Integer; break;
          case 'xbrli:nonZeroDecimal':   $v = TET_NonZeroDecimal; break;
          case 'xbrli:sharesItemType':   $v = TET_Share; break;
          case 'anyURI':
          case 'xbrli:anyURIItemType':   $v = TET_Uri;    break;
          case 'uk-types:domainItemType':$v = TET_Domain; break;
          case 'uk-types:entityAccountsTypeItemType': $v = TET_EntityAccounts; break;
          case 'uk-types:entityFormItemType':  $v = TET_EntityForm;  break;
          case 'uk-types:fixedItemType':       $v = TET_Fixed;       break;
          case 'uk-types:percentItemType':     $v = TET_Percent;     break;
          case 'uk-types:perShareItemType':    $v = TET_PerShare;    break;
          case 'uk-types:reportPeriodItemType':$v = TET_ReportPeriod;break;
          case 'anyType':             $v = TET_Any;   break;
          case 'QName':               $v = TET_QName; break;
          case 'xl:arcType':          $v = TET_Arc;   break;
          case 'xl:documentationType':$v = TET_Doc;   break;
          case 'xl:extendedType':     $v = TET_Extended; break;
          case 'xl:locatorType':      $v = TET_Locator;  break;
          case 'xl:resourceType':     $v = TET_Resource; break;
          case 'anySimpleType':
          case 'xl:simpleType':       $v = TET_Simple; break;
          case 'xl:titleType':        $v = TET_Title;  break;
          # UK-IFRS
          case 'uk-ifrs:investmentPropertyMeasurementItemType': $v = TET_investmentPropertyMeasurement; break;
          default: Dump('node',$node); die("Die - unknown element type $v in NodeNum $NodeNum");
        }
        break;
      case 'substitutionGroup':
        $a = 'SubstGroupN';
        switch ($v) {
          case 'xbrli:item'          : $v = TSG_Item;     break;
          case 'xbrli:tuple'         : $v = TSG_Tuple; $tuple="$nsId.$name"; break;
          case 'xbrldt:dimensionItem': $v = TSG_Dimension;break;
          case 'xbrldt:hypercubeItem': $v = TSG_Hypercube;break;
          case 'link:part'           : $v = TSG_LinkPart; break;
          case 'xl:arc'              : $v = TSG_Arc;      break;
          case 'xl:documentation'    : $v = TSG_Doc;      break;
          case 'xl:extended'         : $v = TSG_Extended; break;
          case 'xl:locator'          : $v = TSG_Locator;  break;
          case 'xl:resource'         : $v = TSG_Resource; break;
          case 'xl:simple'           : $v = TSG_Simple;   break;
          default: die("Die - unknown element substitutionGroup $v");
        }
        $SubstGroupN = $v;
        break;
      case 'xbrli:periodType':
        $a = 'PeriodN';
        switch ($v) {
          case 'instant':  $v = TPT_Instant;  break;
          case 'duration': $v = TPT_Duration; break;
          default: die("Die - unknown element periodType $v");
        }
        break;
      case 'xbrli:balance':
        $a = 'SignN';
        switch ($v) {
          case 'debit':  $v = TS_Dr; break;
          case 'credit': $v = TS_Cr; break;
          default: die("Die - unknown element balance $v");
        }
        break;
      case 'abstract':
      case 'nillable':
        if ($v === 'false') continue 2; # default so skip it
        if ($v !== 'true') die("Die $a=|$v| in $name when true or false expected");
        $v=1;
        break;
      case 'info:creationID':
        # IFRS versioning attribute www.eurofiling.info/.../HPhilippXBRLVersioningForIFRSTaxonomy.ppt
        # Skipped as at 28.06.13
        continue 2;
      default:
        Dump('node', $node);
        die("Unknown attribute $a in NoneNum $NodeNum");
    }
    $set .= ",$a='$v'";
  }
  while ($NodeNum < $NumNodes && $NodesA[$NodeNum]['depth'] > $depth) {
    switch ($NodesA[$NodeNum]['tag']) {
      case 'annotation':    $NodeNum++; break; # / - skip as spec says not required to show doc other than via labels
      case 'documentation': $NodeNum++; break; # |
      case 'complexType':   ComplexType($tuple); break; # $set .= (',ComplexTypeId=' . ComplexType()); break; /- 29.04.11 Changed to skip these
      case 'simpleType':    SimpleType();  break; # $set .= (',SimpleTypeId='  . SimpleType());  break;       |
      default: die("Die - unknown element tag {$NodesA[$NodeNum]['tag']}");
    }
  }

  if (!$SubstGroupN || $SubstGroupN>=TSG_LinkPart) return; # 10.10.12 Taken out of build as not needed
  /*const TSG_LinkPart  = 5; # link:part            56  /- Removed from DB build 10.10.12
    const TSG_Arc       = 6; # xl:arc                6  |
    const TSG_Doc       = 7; # xl:documentation      1  |
    const TSG_Extended  = 8; # xl:extended           6  |
    const TSG_Locator   = 9; # xl:locator            1  |
    const TSG_Resource  =10; # xl:resource           3  |
    const TSG_Simple    =11; # xl:simple             4  | */

  # $NamesMapA [NsId.name => ElId]
  if (!$name) die('Die - no name for element');
  $nsname = "$nsId.$name";
  if (isset($NamesMapA[$nsname])) die("Die - Duplicate NsId.name $nsname");
  $NamesMapA[$nsname] = $ElIdS;
  if ($xid)  $XidsMapA[$xid] = $ElIdS;

  if ($TxElementsSkippedA && in_array($ElIdS, $TxElementsSkippedA)) return; # Skip adding the element

  $set .= ",name='$name'";
  InsertFromSchema('Elements', $set);
}


function Import() {
  global $NodesA, $NodeNum;
  $node = $NodesA[$NodeNum++];
  $ns  = $node['attributes']['namespace'];
  $loc = $node['attributes']['schemaLocation'];
  AddNamespace('', $ns);
  $loc = FileAdjustRelative($loc);
  $set = "Location='$loc'";
  InsertOrUpdate('Imports', 'Location', $loc, $set);
}

function LinkbaseRef() {
  global $NodesA, $NumNodes, $NodeNum;
  $node = $NodesA[$NodeNum++];
  if (@$node['attributes']['xlink:type'] != 'simple')    die('Die - LinkbaseRef type not simple');
  if (@$node['attributes']['xlink:arcrole'] != 'http://www.w3.org/1999/xlink/properties/linkbase') die('Die - LinkbaseRef arcrole not http://www.w3.org/1999/xlink/properties/linkbase');
  $set = '';
  foreach ($node['attributes'] as $a => $v) {
    $a = str_replace('xlink:', '', $a); # strip xlink: prefix
    switch ($a) {
      case 'type':             # skip as always simple
      case 'arcrole':          # skip as always http://www.w3.org/1999/xlink/properties/linkbase
      case 'role': continue 2; # skip as doesn't provide any useful info, just presentationLinkbaseRef etc which we don't need
      case 'href':  $v = FileAdjustRelative($v); break;
      case 'title': $v = addslashes($v); break;
      default: die("Die - unknown linkbaseref attribute $a");
    }
    $set .= ",$a='$v'";
  }
  InsertFromSchema('LinkbaseRefs', $set);
}

function RoleType() {
  global $NodesA, $NumNodes, $NodeNum;
  $node = $NodesA[$NodeNum++];
  if (!@$roleURI=$node['attributes']['roleURI']) die('Die - roleType roleURI missing');
  if (!@$id=$node['attributes']['id'])           die('Die - roleType id missing');
  # Now expect
  #  <link:definition>10 - Profit and Loss Account</link:definition>
  #  <link:usedOn>link:presentationLink</link:usedOn>
  if ($NodesA[$NodeNum]['tag'] != 'link:definition') die("Die - {$NodesA[$NodeNum]['tag']} tag found rather than expected link:definition");
  $definition = addslashes($NodesA[$NodeNum]['txt']);
  $NodeNum++;
  if ($NodesA[$NodeNum]['tag'] != 'link:usedOn')     die("Die - {$NodesA[$NodeNum]['tag']} tag found rather than expected link:usedOn");
  $usedOn = str_replace('link:', '', $NodesA[$NodeNum]['txt']); # strip link: prefix
  $NodeNum++;
  UpdateRole($roleURI, $usedOn, $definition);
}

function ArcroleType() {
  global $NodesA, $NumNodes, $NodeNum;
  $node = $NodesA[$NodeNum++];
  if (!@$arcroleURI=$node['attributes']['arcroleURI'])       die('Die - arcroleType arcroleURI missing');
  if (!@$id=$node['attributes']['id'])                       die('Die - arcroleType id missing');
  if (!@$cyclesAllowed=$node['attributes']['cyclesAllowed']) die('Die - arcroleType cyclesAllowed missing');
  # Now expect
  #  <definition></definition>
  #  <usedOn>definitionArc</usedOn>
  if ($NodesA[$NodeNum]['tag'] != 'definition')      die("Die - {$NodesA[$NodeNum]['tag']} tag found rather than definition");
  $definition = addslashes($NodesA[$NodeNum]['txt']);
  $NodeNum++;
  if ($NodesA[$NodeNum]['tag'] != 'usedOn')          die("Die - {$NodesA[$NodeNum]['tag']} tag found rather than expected usedOn");
  $usedOn = $NodesA[$NodeNum]['txt'];
  $NodeNum++;
  UpdateArcrole($arcroleURI, $usedOn, $definition, $cyclesAllowed);
}

/*######################
## Linkbase functions ##
######################*/

function Linkbase() {
  global $NodesA, $NumNodes, $NodeNum;
  $node = $NodesA[$NodeNum++]; # linkbase node and step over the <linkbase tag
  # No insert for linkbase itself as already have location info in LinkbaseRefs
  # Just update the namespaces. (Info only as no new ones.)
  foreach ($node['attributes'] as $a => $v) {
    if (strpos($a, 'xmlns') === 0)
      AddNamespace($a, $v);
    else if ($a == 'xsi:schemaLocation') {
      # space separated namespace | xsd, either once or multiple times
      $A = explode(' ', trim($v));
      $n = count($A);
      #Dump("xsi:schemaLocation $n", $A);
      for ($i=0; $i < $n; ) {
        AddNamespace('', $A[$i++]);
        $loc = FileAdjustRelative($A[$i++]);
        $set = "Location='$loc'";
        InsertOrUpdate('Imports', 'Location', $loc, $set);
      }
    }else
      die ("Die - Unknown <linkbase attribute $a");
  }
  while ($NodeNum < $NumNodes) {
    $tag = str_replace('link:', '', $NodesA[$NodeNum]['tag']); # strip leading link: if present as it is for CT2013-2.0.0\xbrl.iasb.org\taxonomy\2009-04-01\ifrs\ias_12_2009-04-01\cal_ias_12_2009-04-01_role-835110.xml etc
    switch ($tag) {
      case 'roleRef':                             # RoleRef();     break; # Skipped as of 31.03.11
      case 'arcroleRef':       $NodeNum++; break; # ArcroleRef();  break; # Skipped as of 31.03.11
      case 'presentationLink': XLink(TLT_Presentation); break; # plus <loc and <presentationArc  (link:documentation is not used by UK GAAP)
      case 'definitionLink':   XLink(TLT_Definition);   break; # plus <loc and <definitionArc
      case 'labelLink':        XLink(TLT_Label);        break; # plus <loc and <labelArc
      case 'referenceLink':    XLink(TLT_Reference);    break; # plus <loc and <referenceArc
      case 'documentation':    $NodeNum++; break; # Ignored. There is only 1 of these: <documentation>Entity Information</documentation>
                                                  # in uk-gaap-2009-09-01/cd/business/2009-09-01/uk-bus-2009-09-01-presentation.xml
      default: die("Die - unknown linkbase tag $tag<br>");
    }
  }
}
/* Removed as of 31.03.11. See Wip 8 if required again
function RoleRef() {
function ArcroleRef() {
*/

function XLink($typeN) { # For <presentationLink, <definitionLink, <labelLink, <referenceLink
  global $NodesA, $NumNodes, $NodeNum, $LocLabelToIdA;
  $LocLabelToIdA = [];
  $node = $NodesA[$NodeNum++];
  if (@$node['attributes']['xlink:type'] != 'extended') die('Die - ...link type not extended');
  if (!($role = @$node['attributes']['xlink:role']))    die('Die - ...link xlink:role attribute not set');
  $roleId = UpdateRole($role, $node['tag']);
  $depth1 = $node['depth']+1;
  # For Label and Resource arcs need to make sure the Arcs are processed first re the Label() and Reference() use of $DocMapA info.
  # Arcs came first in GAAP but not DPL.
  # So just do loc and Arcs first, which is everything for Presentation and Definition Arcs.
  $startNodeNum = $NodeNum;
  while ($NodeNum < $NumNodes && $NodesA[$NodeNum]['depth'] === $depth1) {
    $tag = str_replace('link:', '', $NodesA[$NodeNum]['tag']); # strip leading link: if present
    switch ($tag) {
      case 'loc':
        # Locator
        $node = $NodesA[$NodeNum++];
        if ($node['attributes']['xlink:type'] != 'locator') die('Die - loc type not locator');
        if (!$href = $node['attributes']['xlink:href'])     die('Die - loc xlink:href attribute not set');
        if (!$label = $node['attributes']['xlink:label'])   die('Die - loc xlink:label attribute not set');
        if (!$p = strpos($href, '#'))                       die("Die - No # in locator href $href");
        # The #... of the href is the id of the element with the href part up to the # being the xsd that defines the element
        # For UK-GAAP the id was always the same as the locator label as used by Arcs and so the from and to of Arcs could be used directly as ids.
        # Not necessarily so for UK-IFRS (or in general) so need to go Arc From -> Locator Label -> Element Id
        $LocLabelToIdA[$label] = substr($href, $p+1); # Locator Label = Element Id
        break;
      case 'presentationArc':
      case 'definitionArc':
      case 'labelArc':
      case 'referenceArc':  Arc($typeN, $roleId); break;
      case 'label':         ++$NodeNum; break;
      case 'reference':
        # step over the ref:Name etc tags
        for (++$NodeNum; $NodeNum < $NumNodes && $NodesA[$NodeNum]['depth'] == $depth1+1; ++$NodeNum)
          ;
        break;
      default: die("Die - unknown xlink tag $tag<br>");
    }
  }
  if ($typeN == TLT_Label || $typeN == TLT_Reference) {
    # Now the Labels and References
    $NodeNum = $startNodeNum;
    while ($NodeNum < $NumNodes && $NodesA[$NodeNum]['depth'] == $depth1) {
      switch ($NodesA[$NodeNum]['tag']) {
        case 'link:label':
        case 'label':     Label();     break;
        case 'link:reference':
        case 'reference': Reference(); break;
        default: ++$NodeNum; break;
      }
    }
  }
}

/*
3.5.3.9  Arcs  B:/Info/Specs/XBRL/XBRL-RECOMMENDATION-2003-12-31+Corrected-Errata-2008-07-02.htm#_3.5.3.9
All XBRL extended links MAY contain arcs. Arcs document relationships between resources identified by locators in extended links
or occurring as resources in extended links.

Arcs represent relationships between the XML fragments referenced by their [XLINK] attributes: xlink:from and xlink:to. The xlink:from
and the xlink:to attributes represent each side of the arc.  These two attributes contain the xlink:label attribute values of locators
and resources within the same extended link as the arc itself.  For a locator, the referenced XML fragments comprise the set of XML
elements identified by the xlink:href attribute on the locator. For a resource, the referenced XML fragment is the resource element itself.

An arc MAY reference multiple XML fragments on each side (“from” and “to”) of the arc. This can occur if there are multiple locators and/or
resources in the extended link with the same xlink:label attribute value identified by the xlink:from or xlink:to attribute of the arc.
Such arcs represent a set of one-to-one relationships between each of the XML fragments on their “from” side and each of the XML fragments
on their “to” side.

Example 2. One-to-One arc relationships [XLINK] [Simplified with unnec stuff removed]
---------------------
This presentation link contains an arc that relates one XBRL concept to one other XBRL concept.
The XML fragment on the “from” side is the conceptA element definition, found in the example.xsd taxonomy schema.
The XML fragment on the “to” side is the conceptB element definition, also found in the example.xsd taxonomy schema.

<presentationLink  role="link">
  <loc type="locator" label="a" href="example.xsd#conceptA"/>
  <loc type="locator" label="b" href="example.xsd#conceptB"/>
  <presentationArc from="a" to="b" arcrole="parent-child" order="1"/>
</presentationLink>

*/

function Arc($typeN, $pRoleId) {
  global $LinkbaseId, $NodesA, $NodeNum, $LocLabelToIdA, $XidsMapA, $DocMapA, $TxElementsSkippedA; # $XidsMapA [XId  => ElId], $DocMapA [Label => [ElId, ArcId, DocId]]
  $node = $NodesA[$NodeNum++];
  if ($node['attributes']['xlink:type'] != 'arc')   die('Die - arc type not arc');
  if (!isset($node['attributes']['xlink:from']))    die('Die - arc xlink:from attribute not set');
  if (!isset($node['attributes']['xlink:to']))      die('Die - arc xlink:to attribute not set');
  if (!isset($node['attributes']['xlink:arcrole'])) die('Die - arc xlink:arcrole attribute not set');
  $set = "TypeN='$typeN',PRoleId='$pRoleId'";
  foreach ($node['attributes'] as $a => $v) {
    $a = str_replace('xlink:', '', $a); # strip xlink: prefix
    switch ($a) {
      case 'type': continue 2; # skip
      case 'from':
       #SetElId($v, $set, 'From');
        if (!isset($LocLabelToIdA[$v])) die("Die - Locator label \$LocLabelToIdA['$v']) not set for Arc From=$v");
        $elId = $LocLabelToIdA[$v];
        if (!isset($XidsMapA[$elId])) die("Die - \$XidsMapA['$v']) not set for Arc From=$v -> ElId=$elId");
        $fromId = $XidsMapA[$elId];
        $set .= ",FromId=$fromId";
        continue 2;
      case 'to':
        switch ($typeN) {
          case TLT_Presentation:
          case TLT_Definition:#  SetElId($v, $set, 'To'); continue 3; # Expect 'to' to be a concept (element)
            if (!isset($LocLabelToIdA[$v])) die("Die - Locator label \$LocLabelToIdA['$v']) not set for Arc To=$v");
            $elId = $LocLabelToIdA[$v];
            if (!isset($XidsMapA[$elId])) die("Die - \$XidsMapA['$v']) not set for Arc To=$v -> ElId=$elId");
            $toId = $XidsMapA[$elId];
            $set .= ",ToId=$toId";
            continue 3;
          case TLT_Label:
          case TLT_Reference: $toLabel = $v; continue 3; # forward name use so have to resolve later # SetNameUse($v, $set); continue 3; # All label and references tos are name use
        }
        die("typeN $typeN unknown in Arc()");
      case 'arcrole':           $a = 'ArcroleId';       $v = UpdateArcrole($v); break;
      case 'preferredLabel':    $a = 'PrefLabelRoleId'; $v = UpdateRole($v);    break; #, $node['tag']);
      case 'xbrldt:targetRole': $a = 'TargetRoleId';    $v = UpdateRole($v);    break;
     #case 'title': SetText(str_replace('definition: ', '', $v), $set, 'Title'); continue 2; # 'definition: ' stripped from Arc titles. Taken out of use 08.10.12
      case 'title': continue 2; # skip
      case 'order': $a = 'ArcOrder'; $v *= 1000000; break; # * 1000000 for storage as int with up to 6 decimals e.g. 1.999795
      case 'use':
        switch ($v) {
          case 'optional':   $v = TU_Optional;   break;
          case 'prohibited': $v = TU_Prohibited; break;
          default: die("Die - unknown use value $v");
        }
        $a = 'ArcUseN';  break;
      case 'priority':   break;
      case 'xbrldt:closed':
        if ($v != 'true')    die("Die - 'xbrldt:closed' ($v) not true");
        $a = 'ClosedB';  $v = 1;  break;
      case 'xbrldt:contextElement':
        if ($v != 'segment') die("Die - 'xbrldt:contextElement' ($v) not segment");
        $a = 'ContextN'; $v = TC_Segment; break;
      case 'xbrldt:usable':
        if ($v != 'false')   die("Die - 'xbrldt:usable' ($v) not false");
        $a = 'UsableB';  $v = 0;  break;
      default: die("Die - unknown arc attribute $a");
    }
    $set .= ",$a='$v'";
  }

  if ($TxElementsSkippedA &&
      (in_array($fromId, $TxElementsSkippedA) ||                     # Skip adding the Arc if its FromId is in the skip list
       ($typeN <= TLT_Definition && in_array($toId, $TxElementsSkippedA)))) { # Skip adding Presentation or Definition Arcs if their ToId is in the skip list
    if ($typeN  >= TLT_Label)
      $DocMapA[$toLabel.$LinkbaseId] = array('ElId' => $fromId, 'ArcId'=>0, 'DocId'=>0); # So label/ref can get $fromId and skip
    return;
  }

  /* Fudge to remove the repeat of dimension-domain and dimension-default of ContinuingAndDiscontinuedOperationsDimension with PRole of Hypercube-DetailedProfitAndLoss in
  linkbase #13 B:\Admin\Taxonomies\CT2013-2.0.0\www.hmrc.gov.uk\schemas\ct\dpl\2013-02-01\dpl-ifrs\2013-02-01\dpl-ifrs-2013-02-01-definition.xml
  <definitionLink xlink:type="extended" xlink:role="http://www.govtalk.gov.uk/uk/fr/tax/dpl-core/2013-02-01/role/Hypercube-DetailedProfitAndLoss">
    <loc xlink:type="locator" xlink:href="http://xbrl.iasb.org/taxonomy/2009-04-01/ifrs-cor_2009-04-01.xsd#ifrs_ContinuingAndDiscontinuedOperationsDimension" xlink:label="ContinuingAndDiscontinuedOperationsDimension" xlink:title="ContinuingAndDiscontinuedOperationsDimension"/>
    <loc xlink:type="locator" xlink:href="http://www.xbrl.org/uk/ifrs/core/2009-09-01/uk-ifrs-2009-09-01.xsd#uk-ifrs_ContinuingDiscontinuedOperationsTotalDefault" xlink:label="ContinuingDiscontinuedOperationsTotalDefault"/>
    <definitionArc xlink:type="arc" xlink:arcrole="http://xbrl.org/int/dim/arcrole/dimension-domain" xlink:from="ContinuingAndDiscontinuedOperationsDimension" xlink:to="ContinuingDiscontinuedOperationsTotalDefault" xlink:title="definition: ContinuingAndDiscontinuedOperationsDimension to ContinuingDiscontinuedOperationsTotalDefault" use="optional" order="1.0"/>
    <loc xlink:type="locator" xlink:href="http://www.xbrl.org/uk/ifrs/core/2009-09-01/uk-ifrs-2009-09-01.xsd#uk-ifrs_ContinuingDiscontinuedOperationsTotalDefault" xlink:label="ContinuingDiscontinuedOperationsTotalDefault_2" xlink:title="definition: ContinuingAndDiscontinuedOperationsDimension to ContinuingDiscontinuedOperationsTotalDefault"/>
    <definitionArc xlink:type="arc" xlink:arcrole="http://xbrl.org/int/dim/arcrole/dimension-default" xlink:from="ContinuingAndDiscontinuedOperationsDimension" xlink:to="ContinuingDiscontinuedOperationsTotalDefault_2" xlink:title="definition: ContinuingAndDiscontinuedOperationsDimension to ContinuingDiscontinuedOperationsTotalDefault" use="optional" priority="1" order="2.0"/>

  when already done in:

  linkbase #9 B:\Admin\Taxonomies\CT2013-2.0.0\www.xbrl.org\uk\ifrs\core\2009-09-01\uk-ifrs-2009-09-01-definition.xml
  <definitionLink xlink:type="extended" xlink:role="http://www.xbrl.org/uk/role/Dimension-ContinuingAndDiscontinued">
    <loc xlink:type="locator" xlink:href="http://xbrl.iasb.org/taxonomy/2009-04-01/ifrs-cor_2009-04-01.xsd#ifrs_ContinuingAndDiscontinuedOperationsDimension" xlink:label="ifrs_ContinuingAndDiscontinuedOperationsDimension" />
    <loc xlink:type="locator" xlink:href="uk-ifrs-2009-09-01.xsd#uk-ifrs_ContinuingDiscontinuedOperationsTotalDefault" xlink:label="uk-ifrs_ContinuingDiscontinuedOperationsTotalDefault" />
    <definitionArc xlink:type="arc" xlink:arcrole="http://xbrl.org/int/dim/arcrole/dimension-domain" xlink:from="ifrs_ContinuingAndDiscontinuedOperationsDimension" xlink:to="uk-ifrs_ContinuingDiscontinuedOperationsTotalDefault" order="2" use="optional" />

  <definitionLink xlink:type="extended" xlink:role="http://www.xbrl.org/uk/role/Dimension-ContinuingAndDiscontinued">
    <loc xlink:type="locator" xlink:href="http://xbrl.iasb.org/taxonomy/2009-04-01/ifrs-cor_2009-04-01.xsd#ifrs_ContinuingAndDiscontinuedOperationsDimension" xlink:label="ifrs_ContinuingAndDiscontinuedOperationsDimension" xlink:title="definition: ContinuingAndDiscontinuedOperationsDimension to ContinuingDiscontinuedOperationsTotalDefault" />
    <loc xlink:type="locator" xlink:href="uk-ifrs-2009-09-01.xsd#uk-ifrs_ContinuingDiscontinuedOperationsTotalDefault" xlink:label="uk-ifrs_ContinuingDiscontinuedOperationsTotalDefault" xlink:title="definition: ContinuingAndDiscontinuedOperationsDimension to ContinuingDiscontinuedOperationsTotalDefault" />
    <definitionArc xlink:type="arc" xlink:arcrole="http://xbrl.org/int/dim/arcrole/dimension-default" xlink:from="ifrs_ContinuingAndDiscontinuedOperationsDimension" xlink:to="uk-ifrs_ContinuingDiscontinuedOperationsTotalDefault" xlink:title="definition: ContinuingAndDiscontinuedOperationsDimension to ContinuingDiscontinuedOperationsTotalDefault" order="3" priority="1" use="optional" />
  */
  if ($pRoleId === 119 && $fromId === 6253) return;

  $id = InsertFromLinkbase('Arcs', $set);
  switch ($typeN) {
    case TLT_Label:
    case TLT_Reference:
      $DocMapA[$toLabel.$LinkbaseId] = array('ElId' => $fromId, 'ArcId'=>$id, 'DocId'=>0); # $DocMapA [Label => [ElId, ArcId, DocId]]
  }
}

function Label() {
  global $LinkbaseId, $NodesA, $NodeNum, $DocMapA, $TxElementsSkippedA;
  $node = $NodesA[$NodeNum++];
  if (@$node['attributes']['xlink:type'] != 'resource') die('Die - label type not resource');
  if (!@$label = $node['attributes']['xlink:label'])    die('Die - label xlink:label attribute not set');
  if (!($txt = $node['txt']))                           die('Die - label txt not set');
  $label=$label.$LinkbaseId; #Re same label used in different linkbase files e.g. for CountriesDimension verbose label added by DPL
  $set = 'TypeN='.TLT_Label;
  SetText($txt, $set);
  foreach ($node['attributes'] as $a => $v) {
    switch ($a) {
      case 'xlink:type':
      case 'xml:lang':    break; # skip
      case 'id':          break; # SetIdDef($v, $set);break; # Removed 08.10.12 as not useful
      case 'xlink:label':
        if (!isset($DocMapA[$label])) die("Die - \$DocMapA['$label'] not set in Label()");
        $elId = $DocMapA[$label]['ElId'];
        break;
      case 'xlink:role':  SetRole($v, $set, 'label'); break;
     #case 'xlink:title': SetText($v, $set, 'Title'); break; # Removed 08.10.12 as not useful
      case 'xlink:title': break; # skip
      default: die("Die - unknown label attribute $a");
    }
  }
  if ($TxElementsSkippedA && in_array($elId, $TxElementsSkippedA)) return; # Skip adding the Label
  $set .= ",ElId=$elId";
  $id = InsertFromLinkbase('Doc', $set);
  # $DocMapA [Label => [ElId, ArcId, DocId]]
  if (!isset($DocMapA[$label])) die("Die - \$DocMapA[$label] not set in Label()");
  $DocMapA[$label]['DocId'] = $id;
}

function Reference() {
  global $LinkbaseId, $NodesA, $NumNodes, $NodeNum, $RefsA, $DocMapA, $TxElementsSkippedA;
  $node = $NodesA[$NodeNum++];
  if (@$node['attributes']['xlink:type'] != 'resource') die('Die - reference type not resource');
  if (!@$label = $node['attributes']['xlink:label'])    die('Die - reference xlink:label attribute not set');
  if (@$txt = $node['txt'])                             die('Die - reference txt is set');
  $label=$label.$LinkbaseId;
  $set = 'TypeN='.TLT_Reference;
  foreach ($node['attributes'] as $a => $v) {
    switch ($a) {
      case 'xlink:type':  break; # skip
      case 'id':          break; # SetIdDef($v, $set); break; # IdId  Removed 08.10.12 as not used
      case 'xlink:label':
        if (!isset($DocMapA[$label])) die("Die - \$DocMapA['$label'] not set in Reference()");
        $elId = $DocMapA[$label]['ElId'];
        break;
      case 'xlink:role':  SetRole($v, $set, 'reference'); break;
      default: die("Die - unknown reference attribute $a");
    }
  }
  $depth1 = $node['depth']+1;
  $refsA = $RefsA;
  for (; $NodeNum < $NumNodes && $NodesA[$NodeNum]['depth'] == $depth1; $NodeNum++) {
    $tag = $NodesA[$NodeNum]['tag'];
    if (($p=strpos($tag, ':')) === false) die("Die - Ref subnode without expected :");
    $tag = substr($tag, $p+1);
    if (!isset($refsA[$tag])) die("Die - unknown reference subnode $tag from ".$NodesA[$NodeNum]['tag']);
    if (isset($NodesA[$NodeNum]['txt']))
      $refsA[$tag] .= ', ' . $NodesA[$NodeNum]['txt']; # addslashes() only to the completed json via SetText() or any \ gets slashed
  }

  if ($TxElementsSkippedA && in_array($elId, $TxElementsSkippedA)) return; # Skip adding the Label
  $set .= ",ElId=$elId";

  foreach ($refsA as $a => $v)
    if ($v)
      $refsA[$a] = substr($v,2);
    else
      unset($refsA[$a]);
  SetText(json_encode($refsA), $set); # associative array is encoded as an object

  $id = InsertFromLinkbase('Doc', $set);
  # $DocMapA [Label => [ElId, ArcId, DocId]]
  if (!isset($DocMapA[$label])) die("Die - \$DocMapA[$label] not set in Reference()");
  $DocMapA[$label]['DocId'] = $id;
}

# Step over annotation & documentation nodes
/*
function StepOver() {
  global $NodesA, $NumNodes, $NodeNum;
  while ($NodeNum < $NumNodes && strpos('annotation,documentation', $NodesA[$NodeNum]['tag']) !== false)
    $NodeNum++;
}

function GetDoc() {
  global $NodesA, $NumNodes, $NodeNum;
  $doc = '';
  $depth = $NodesA[$NodeNum]['depth']; # current depth e.g. 0 for schema, 1 for element
  for ($n=$NodeNum+1; $n < $NumNodes; $n++) {
    $node = $NodesA[$n];
    if ($node['depth'] == $depth) # back to depth of the parent tag
      break;
    if ($node['tag'] == 'documentation' && $node['depth'] == $depth+2)
     $doc .= '; ' . $node['txt'];
  }
  return ($doc > '' ? ("Doc='" . addslashes(substr($doc, 2)) . SQ) : '');
} */

function AddNamespace($prefix, $ns) {
  global $FileId, $NsMapA; # $NsMapA [namespace => [NsId, Prefix, File, Num]
  static $NsIdS = 0;
  if (isset($NsMapA[$ns])) {
    $NsMapA[$ns]['File'] .= ",$FileId";
  ++$NsMapA[$ns]['Num'];
    return $NsMapA[$ns]['NsId'];
  }
  $prefix = ($prefix > 'xmlns' && ($colon = strpos($prefix, ':')) > 0) ? substr($prefix, $colon+1) : '';
  $NsMapA[$ns] = array('NsId' => ++$NsIdS, 'Prefix'=>$prefix, 'File'=>$FileId, 'Num'=>1);
  return $NsIdS;
}

function Nodes() {
  global $XR, $NodesA, $NodeNum;
  while($XR->read()) {
    switch ($XR->nodeType) {
      case XMLReader::END_ELEMENT: break;
      case XMLReader::ELEMENT:
        #echo "Element start $XR->name<br>";
        $node = array('tag' => $XR->name, 'depth' => $XR->depth);
        if ($XR->hasAttributes)
          while($XR->moveToNextAttribute())
            $node['attributes'][$XR->name] = $XR->value;
        $NodesA[] = $node;
        $NodeNum++;
        break;
      case XMLReader::TEXT:
      case XMLReader::CDATA:
       #$NodesA[$NodeNum-1]['txt'] = trim(addslashes(preg_replace('/\s\s+/m', ' ', $XR->value)));
        $NodesA[$NodeNum-1]['txt'] = trim(preg_replace('/\s\s+/m', ' ', $XR->value)); # addslashes() removed to avoid probs with json_encode doing it also
    }
  }
  return;
}

function Insert($table, $set) {
  global $DB;
  if ($set[0] == ',')  # $set may or may not have a leading comma
    $set = substr($set,1);
  return $DB->InsertQuery("Insert `$table` Set $set");
}

function InsertOrUpdate($table, $key, $kv, $set) {
  global $DB, $FileId;
  if ($o = $DB->OptObjQuery("Select Id,File From $table where $key='$kv'")) {
    $DB->StQuery("Update $table Set Num=Num+1,File='" . $o->File . ',' . $FileId . "' Where Id=$o->Id");
    return $o->Id;
  }else
    return Insert($table, $set . ",File=$FileId");
}

function InsertFromSchema($table, $set) {
  global $DB;
  if ($set[0] == ',')
    $set = substr($set,1);
  return $DB->InsertQuery("Insert `$table` Set $set");
}

function InsertFromLinkbase($table, $set) {
  global $DB, $LinkbaseId;
  if ($set[0] == ',')
    $set = substr($set,1);
  return $DB->InsertQuery("Insert `$table` Set LinkbaseId=$LinkbaseId,$set");
}

function UpdateRole($role, $usedOn=0, $definition=0) {
  global $FileId, $RolesMapA; # $RolesMapA [Role => [Id, usedOn, definition, ElId, FileId, Uses]]
  # http://www.xbrl./uk/role/ProftAndLossAccount => uk/ProftAndLossAccount
  # http://www.govtalk.gov.uk/uk/fr/tax/dpl-gaap/2012-10-01/role/Hypercube-DetailedProfitAndLossReserve => 'dpl-gaap/Hypercube-DetailedProfitAndLossReserve'
  if (strpos($role, 'http://') !== 0)   die("Die - non uri $role passed to UpdateRole()");
  $role = str_replace(['http://', 'www.', 'xbrl.org/', '2003/', 'role/', 'int/', 'org/', 'govtalk.gov.uk/uk/fr/tax/','2013-02-01/'], '',  $role); # strip http:// etc 'org/' for the anomoly of uk/org/role/BalanceSheetStatement
  if (!isset($RolesMapA[$role])) die("Die - Role $role not defined on UpdateRole() call as expected");
  if ($usedOn     && !$RolesMapA[$role]['usedOn'])     $RolesMapA[$role]['usedOn']     = $usedOn;
  if ($definition && !$RolesMapA[$role]['definition']) $RolesMapA[$role]['definition'] = $definition;
  if                (!$RolesMapA[$role]['FileId'])     $RolesMapA[$role]['FileId']     = $FileId;
  ++$RolesMapA[$role]['Uses'];
  return $RolesMapA[$role]['Id'];
}

function SetRole($role, &$callingSet, $usedOn) {
  global $FileId, $RolesMapA; # $RolesMapA [Role => [Id, usedOn, definition, ElId, FileId, Uses]]
  # http://www.xbrl.org/uk/role/ProftAndLossAccount => uk/ProftAndLossAccount
  if (strpos($role, 'http://') !== 0)   die("Die - non uri $role passed to SetRole()");
  $role = str_replace(array('http://', 'www.', 'xbrl.org/', '2003/', 'role/', 'int/', 'org/'), '',  $role); # strip http:// etc 'org/' for the anomoly of uk/org/role/BalanceSheetStatement
  if (!isset($RolesMapA[$role])) die("Die - Role $role not defined on SetRole() call as expected");
  if (!$RolesMapA[$role]['usedOn']) $RolesMapA[$role]['usedOn'] = $usedOn;
  if (!$RolesMapA[$role]['FileId']) $RolesMapA[$role]['FileId'] = $FileId;
  ++$RolesMapA[$role]['Uses'];
  $callingSet .= ",RoleId={$RolesMapA[$role]['Id']}";
}

function UpdateArcrole($arcrole, $usedOn=0, $definition=0, $cyclesAllowed=0) {
  global $FileId, $ArcrRolesMapA; # $ArcrRolesMapA [Arcrole => [Id, usedOn, definition, cyclesAllowed, FileId, Uses]]
  # http://www.xbrl.org/2003/arcrole/parent-child => parent-child
  if (strpos($arcrole, 'http://') !== 0)   die("Die - non uri $arcrole passed to UpdateArcrole()");
  $arcrole = str_replace(array('http://', 'www.', 'xbrl.org/', '2003/', 'arcrole/', 'int/'), '',  $arcrole); # strip http:// etc
  if (!isset($ArcrRolesMapA[$arcrole])) die("Die - Role $arcrole not defined on UpdateArcrole() call as expected");
  if ($usedOn        && !$ArcrRolesMapA[$arcrole]['usedOn'])        $ArcrRolesMapA[$arcrole]['usedOn']        = $usedOn;
  if ($definition    && !$ArcrRolesMapA[$arcrole]['definition'])    $ArcrRolesMapA[$arcrole]['definition']    = $definition;
  if ($cyclesAllowed && !$ArcrRolesMapA[$arcrole]['cyclesAllowed']) $ArcrRolesMapA[$arcrole]['cyclesAllowed'] = $cyclesAllowed;
  if                   (!$ArcrRolesMapA[$arcrole]['FileId'])        $ArcrRolesMapA[$arcrole]['FileId']        = $FileId;
  ++$ArcrRolesMapA[$arcrole]['Uses'];
  return $ArcrRolesMapA[$arcrole]['Id'];
}

# Labels     TextId   # Text.Id  for the content of the label     /- Only these two as of 08.10.12
# References TextId   # Text.Id  for Refs content stored as json  |
function SetText($text, &$callingSet) {
  global $TextMapA; # $TextMapA text => [TextId, Uses]
  static $TextIdS=0;
  $text = addslashes($text);
  if (isset($TextMapA[$text])) {
    $id = $TextMapA[$text]['TextId'];
    ++$TextMapA[$text]['Uses'];
  }else{
    $id = ++$TextIdS;
    $TextMapA[$text] = array('TextId'=>$id, 'Uses'=>1);
  }
  $callingSet .= ",TextId=$id";
}

###########
## Names ## For xsd:NCName
###########
# Elements.name    # name              [0..1] xsd:NCName
# Elements NameId  # Names.Id for name [0..1] xsd:NCName
# (xsd:NCName values in Labels, References, and Arc To fields for label and reference arcs are not stored but are just used during the build to link Labels and references to Elements.)
/* Taken OoS 10.10.12 with change to store name in the Elements table
function SetName($name, &$callingSet) {
  global $NamesMapA; # $NamesMapA [name => [NameId, ElId, Uses]]
  static $NamesIdS=0;
  $name = FixNameCase($name);
  if (isset($NamesMapA[$name])) {
    $id = $NamesMapA[$name]['NameId'];
    ++$NamesMapA[$name]['Uses'];
  }else{
    $id = ++$NamesIdS;
    $NamesMapA[$name] = array('NameId'=>$id, 'ElId'=>0, 'Uses'=>1);
  }
  $callingSet .= ",NameId=$id";
}
function FixNameCase($name) { Not needed after removal of LinkPart elements from build
  # re Footnote and footnote
  static $NameFixesSA = array('Footnote' => 'footnote', 'Part' => 'part');
  if (isset($NameFixesSA[$name])) return $NameFixesSA[$name];
  return $name;
} */

function FileAdjustRelative($loc) {
  global $File;
  $oLoc=$loc;
  # CT2013 rewrite rules CT2013-2.0.0 unchanged from CT2013-1.0 for which this was originally written re UK-GAAP-DPL
/* remappingsCatalog.xml
<?xml version="1.0" encoding="UTF-8"?>
<catalog xmlns="urn:oasis:names:tc:entity:xmlns:xml:catalog" prefer="public">
  <rewriteURI
    rewritePrefix="../www.xbrl.org/uk/"
    uriStartString="http://www.xbrl.org/uk/" />
  <rewriteURI
    rewritePrefix="../xbrl.iasb.org/taxonomy/2009-04-01/"
    uriStartString="http://xbrl.iasb.org/taxonomy/2009-04-01/" />
  <rewriteURI
    rewritePrefix="../www.hmrc.gov.uk/schemas/ct/"
    uriStartString="http://www.hmrc.gov.uk/schemas/ct/" />
  <rewriteURI
    rewritePrefix="../www.xbrl.org/dtr/type/"
    uriStartString="http://www.xbrl.org/dtr/type/" />
</catalog>
*/
  if (($loc = str_replace(['http://www.xbrl.org/uk/','http://xbrl.iasb.org/taxonomy/2009-04-01/', 'http://www.hmrc.gov.uk/schemas/ct/','http://www.xbrl.org/dtr/type/'],
                          [       'www.xbrl.org/uk/',       'xbrl.iasb.org/taxonomy/2009-04-01/',     '../www.hmrc.gov.uk/schemas/ct/',    '../www.xbrl.org/dtr/type/'], $loc)) != $oLoc)
    return $loc;

  if (!strncasecmp($loc, 'http:', 5))
    # 01234567
    # http://www.xbrl.org/2006/ref-2006-02-27.xsd -> /web/www.xbrl.org 2006 ref-2006-02-27.xsd
    return 'web/'.substr($loc, 7);

  # adjust loc for relative position
  # File: gaap/core/2009-09-01/uk-gaap-full-2009-09-01.xsd
  # loc:  uk-gaap-2009-09-01-presentation.xml
  # -->   gaap/core/2009-09-01/uk-gaap-2009-09-01-presentation.xml
  # loc:  href="../../../cd/business/2009-09-01/uk-bus-2009-09-01-presentation.xml
  # -->   cd/business/2009-09-01/uk-bus-2009-09-01-presentation.xml
  # http://www.xbrl.org/uk/reports/direp/2009-09-01/uk-direp-2009-09-01.xsd
  $fileA = explode('/', $File);
  $locA  = explode('/', $loc);
  $if    = count($fileA)-1; # last index
  unset($fileA[$if--]);     # chop off the file name
  $il    = 0;
  while ($locA[$il] == '..') {
    unset($locA[$il++]);   # chop off the ..
    unset($fileA[$if--]);  # and the corresponding dir
  }
  $loc = ($if >= 0 ? (implode('/', $fileA) . '/') : '') . implode('/', $locA);
  return $loc;
}

# Return tag stripped of prefix if any
function LessPrefix($tag) {
  if (($p = strpos($tag, ':')) > 0) # strip any prefix
    $tag = substr($tag, $p+1);
  return $tag;
}

///// Functions which are candidates for removal
function Attribute() {
  global $NodesA, $NumNodes, $NodeNum;
  $node = $NodesA[$NodeNum++];
  if (!@$name = $node['attributes']['name']) die('no name for primary attribute');
  $set = "name='$name'";
  while ($NodeNum < $NumNodes && $NodesA[$NodeNum]['depth'] > 1) { # <attribute has a depth of 1
    switch ($NodesA[$NodeNum]['tag']) {
      case 'annotation':
      case 'documentation': $NodeNum++; break;
      case 'simpleType':    $set .= (',SimpleTypeId=' . SimpleType()); break;
      default: die("Die - unknown tag {$node['tag']} in <attribute<br>");
    }
  }
  # InsertFromSchema('Attributes', $set); 29.01.11 skip
}

function AttributeGroup() {
  global $NodesA, $NumNodes, $NodeNum;
  if (!$name = $NodesA[$NodeNum]['attributes']['name'])
    die('no name for attributeGroup');
  $set = "name='$name'";
  $NodeNum++; # over <attributeGroup
  $attributesA = []; # there can be multiple <attribute subnodes
  while ($NodeNum < $NumNodes && $NodesA[$NodeNum]['depth'] > 1) {
    switch ($NodesA[$NodeNum]['tag']) {
      case 'annotation':
      case 'documentation': $NodeNum++; break;
      case 'attribute':      # <attribute name="precision" type="xbrli:precisionType" use="optional" />
        $attributesA[] = $NodesA[$NodeNum]['attributes'];
        break;
      case 'attributeGroup': # <attributeGroup ref="xbrli:essentialNumericItemAttrs" />
        $set .= ",ref='{$NodesA[$NodeNum]['attributes']['ref']}'";
        break;
      case 'anyAttribute':
        $set .= ",anyAttributeJson='" . json_encode($NodesA[$NodeNum]['attributes']) . SQ;
        break;
      default: die("Die - unknown tag {$node['tag']} in <attributeGroup<br>");
    }
    $NodeNum++; # all single tags so can do this
  }
  if (count($attributesA))
    $set .= ",attributeJson='" . json_encode($attributesA) . SQ;
  # InsertFromSchema('AttributeGroups', $set);  29.01.11 skip
}

function ComplexType($tuple=0) {
  global $DB, $NodesA, $NumNodes, $NodeNum, $SchemaId, $TuplesA;
  $node = $NodesA[$NodeNum++];
  $set = '';
  if (isset($node['attributes']['name'])) {
    $name = $node['attributes']['name'];
  }else
    $name = false;

  if (isset($node['attributes'])) {
    #if (!$name  = @$node['attributes']['name'])
    #  die('Die - No name for complexType with attributes');
    foreach ($node['attributes'] as $a => $v) {
      if ($a == 'mixed' && $v == 'true')
        $v = 1;
      $set .= ",$a='$v'";
    }
  }
  $depth = $node['depth']; # depth of the <complexType node - need this as ComplexType() is not called just when at depth 1
  $attributesA =     # for a set of <attribute tags
  $choiceA     =     # for a <choice list
  $complexA    =     # for <complexContent
  $simpleA     =     # for <simpleContent
  $sequenceA   = []; # for a <sequence list
  while ($NodeNum < $NumNodes && $NodesA[$NodeNum]['depth'] > $depth) {
    switch ($NodesA[$NodeNum]['tag']) {
      case 'annotation':
      case 'documentation': $NodeNum++; break;
      case 'anyAttribute':  $set .= ",anyAttributeJson='" . json_encode($NodesA[$NodeNum]['attributes']) . SQ;
        $NodeNum++;
        break;
      case 'attributeGroup': $set .= ",attributeGroupRef='{$NodesA[$NodeNum]['attributes']['ref']}'";
        $NodeNum++;
        break;
      case 'attribute':      # <attribute name="id" type="ID" use="required" />
        $attributes = $NodesA[$NodeNum]['attributes'];
        $NodeNum++;
        if ($NodesA[$NodeNum]['tag'] == 'simpleType' && $NodesA[$NodeNum]['depth'] == $depth+2)
          # attribute has simpleType subnode as for <element name="arcroleType"> ... <attribute name="cyclesAllowed" use="required"> <simpleType>
          # Add it to the json via $attributesA[]
          $attributes['simpleTypeId'] = SimpleType();
        $attributesA[] = $attributes;
        break;
      case 'choice':
        $NodeNum++; # over the choice
        for (; $NodeNum < $NumNodes && $NodesA[$NodeNum]['depth'] > $depth+1; $NodeNum++)
          $choiceA[] = $NodesA[$NodeNum];
        break;
      case 'complexContent':
        $NodeNum++; # over the complexContent
        for (; $NodeNum < $NumNodes && $NodesA[$NodeNum]['depth'] > $depth+1; $NodeNum++)
          $complexA[] = $NodesA[$NodeNum];
        #if ($tuple) DumpExport("Tuple complex content complexA for $tuple", $complexA);
        if ($tuple) $TuplesA[$tuple] = $complexA;
        break;
      case 'simpleContent':
        $NodeNum++; # over the simpleContent
        for (; $NodeNum < $NumNodes && $NodesA[$NodeNum]['depth'] > $depth+1; $NodeNum++)
          $simpleA[] = $NodesA[$NodeNum];
        break;
      case 'sequence':
        $NodeNum++; # over the sequence
        for (; $NodeNum < $NumNodes && $NodesA[$NodeNum]['depth'] > $depth+1; $NodeNum++)
          $sequenceA[] = $NodesA[$NodeNum];
        break;
      default: die("Die - unknown complex type tag {$NodesA[$NodeNum]['tag']}");
    }
  }
  return; /* 29.01.11 skip
  if (count($attributesA)) {
    #if (count($attributesA) > 1)
    #  echo 'comlexType attribute set count =' . count($attributesA) . '<br>';
    $set .= ",attributesJson='" . json_encode($attributesA) . SQ;
  }
  if (count($choiceA))
    $set .= ",choiceJson='" . json_encode($choiceA) . SQ;
  if (count($complexA))
    $set .= ",complexContentJson='" . json_encode($complexA) . SQ;
  if (count($simpleA))
    $set .= ",simpleContentJson='" . json_encode($simpleA) . SQ;
  if (count($sequenceA))
    $set .= ",sequenceJson='" . json_encode($sequenceA) . SQ;
  # In the no name case use $set as a where clause with , => and to see if this simpleType has already been defined
  if (!$name) {
    if ($set[0] == ',')  # $set may have a leading comma
      $set = substr($set, 1);
    if ($o = $DB->OptObjQuery('Select Id,SchemaId From ComplexTypes where ' . str_replace("',", "' and ", $set))) {
      $DB->StQuery("Update ComplexTypes Set SchemaId='" . $o->SchemaId . ',' . $SchemaId . "' Where Id=$o->Id");
      return $o->Id;
    }
  }
  return InsertFromSchema('ComplexTypes', $set); */
}

function SimpleType() {
  global $DB, $NodesA, $NumNodes, $NodeNum, $SchemaId;
  $node = $NodesA[$NodeNum++];
  $set = '';
  if (isset($node['attributes'])) {
    if (!$name = $node['attributes']['name']) die('Die - No name for SimpleType with attributes');
    $set .= ",name='$name'";
  }else
    $name = false;
  $depth = $node['depth']; # depth of the <simpleType node - need this as SimpleType() is not called just when at depth 1
  # Skip over any annotation & documentation nodes
  while ($NodeNum < $NumNodes && strpos('annotation,documentation', $NodesA[$NodeNum]['tag']) !== false)
    $NodeNum++;
  $node  = $NodesA[$NodeNum];
  switch (LessPrefix($node['tag'])) { # expect restriction or union
    case 'restriction':
      if (!$base = @$node['attributes']['base'])  die('Die - simpleType restriction base not found as expected');
      $set .= ",base='$base'";
      $NodeNum++; # Over`the <restriction node
      switch (LessPrefix($base)) {
        case 'anyURI': # expect minLength
          if ($NodesA[$NodeNum]['depth'] == $depth+2) { # +2 for restriction then minLength
            $set .= ",{$NodesA[$NodeNum]['tag']}={$NodesA[$NodeNum]['attributes']['value']}";
            $NodeNum++;
          }
          break;
        case 'token':   # /- expect a set of enumeration values
        case 'NMTOKEN': # |
        case 'string':  # |
          $enums = '';
          while ($NodeNum < $NumNodes && LessPrefix($NodesA[$NodeNum]['tag']) == 'enumeration') {
            $enums .= ',' . $NodesA[$NodeNum]['attributes']['value'];
            $NodeNum++;
          }
          if (!($enums = substr($enums, 1))) die("Die - no enum list for simpleType base=$base");
          $set .= ",EnumList='$enums'";
          break;
        case 'decimal': # expect nothing or minExclusive or maxExclusive. Put them straight it
          if ($NodesA[$NodeNum]['depth'] == $depth+2) { # +2 for restriction then minExclusive or maxExclusive
            $set .= ",{$NodesA[$NodeNum]['tag']}={$NodesA[$NodeNum]['attributes']['value']}";
            $NodeNum++;
          }
          break;
        default: die("Die - SimpleType restriction base of $base not known");
      }
      #echo "set=$set<br>";
      if ($NodeNum < $NumNodes && $NodesA[$NodeNum]['depth'] > $depth) die('Die - SimpleType end not back to parent depth');
      break;

    case 'union':
      $set .= (',unionId=' . Union());
      break;
    default: die('Die - restriction or unions not found after simpleType as expected');
  }
  # In the no name case use $set as a where clause with , => and to see if this simpleType has already been defined
  return; /* 29.01.11 skip
  if (!$name) {
    if ($set[0] == ',')  # $set may have a leading comma
      $set = substr($set, 1);
    if ($o = $DB->OptObjQuery('Select Id,SchemaId From SimpleTypes where ' . str_replace("',", "' and ", $set))) {
      $DB->StQuery("Update SimpleTypes Set SchemaId='" . $o->SchemaId . ',' . $SchemaId . "' Where Id=$o->Id");
      return $o->Id;
    }
  }
  return InsertFromSchema('SimpleTypes', $set); */
}

function Union() {
  global $DB, $NodesA, $NumNodes, $NodeNum, $SchemaId;
  $node = $NodesA[$NodeNum++];
  $set = '';
  if (isset($node['attributes'])) {
    if (!$memberTypes = $node['attributes']['memberTypes']) die('Die - No memberTypes for Union with attributes');
    $set .= ",memberTypes='$memberTypes'";
  }
  $depth = $node['depth']; # depth of the union
  # expect 0, 1 or 2 simpleTypes
  if ($NodeNum < $NumNodes && $NodesA[$NodeNum]['tag'] == 'simpleType')
    $set .= ',SimpleType1Id=' . SimpleType();
  if ($NodeNum < $NumNodes && $NodesA[$NodeNum]['tag'] == 'simpleType')
    $set .= ',SimpleType2Id=' . SimpleType();
  if ($NodeNum < $NumNodes && $NodesA[$NodeNum]['depth'] > $depth) Die('Die - Union end not back to union depth');
  # See if this union has already been defined
  return; /* 29.01.11 skip
  $set = substr($set, 1);
  if ($o = $DB->OptObjQuery('Select Id,SchemaId From Unions where ' . str_replace("',", "' and ", $set))) {
    $DB->StQuery("Update Unions Set SchemaId='" . $o->SchemaId . ',' . $SchemaId . "' Where Id=$o->Id");
    return $o->Id;
  }
  return InsertFromSchema('Unions', $set); */
}

