<?php /* Copyright 2011-2013 Braiins Ltd

/Com/inc/UK-IFRS-DPL/ConstantsTx.inc

UK-IFRS-DPL taxonomy constants, globals, and to string functions

History:
26.06.13 Started based on UK-GAAP-DPL version
*/

# Taxonomy Constants
# ==================
# Taxonomy Role Id (Roles.Id) for the XBRL roles. NB! These need to be checked after a Taxonomy DB Build
# ----------------
const TR_StdLabel   = 1; # label             Standard Label
const TR_TotalLabel = 2; # totalLabel        Total Label
const TR_PSL        = 3; # periodStartLabel  Period Start Label
const TR_PEL        = 4; # periodEndLabel    Period End Label
const TR_Doc        = 5; # documentation     Documentation
const TR_TerseLabel = 6; # terseLabel        Terse Label
const TR_VB         = 7; # verboseLabel      Verbose Label
const TR_PosLabel   = 8; # positiveLabel     Positive Label
const TR_NegLabel   = 9; # negativeLabel     Negative Label
const TR_Ref       = 10; # reference         Reference
# ['link',             'labels & references', 'Label or Reference Link'],
# ['disclosureRef',    'references', 'Disclosure Reference'],
# ['recommendedDisclosureRef', 'references', 'Recommended Disclosure Reference'], # uk-ifrs, not uk-gaap
# ['presentationRef',  'references', 'Presentation Reference'], # http://www.xbrl.org/2003/role/presentationRef  uk-ifrs, not uk-gaap  Reference to documentation which details an explanation of the presentation, placement or labelling of this concept in the context of other concepts in one or more specific types of business reports
# ['definitionRef',    'references', 'Definition Reference'],
# ['measurementRef',   'references', 'Measurement Reference'],  # http://www.xbrl.org/2003/role/measurementRef   uk-ifrs, not uk-gaap  Reference concerning the method(s) required to be used when measuring values associated with this concept in business reports
# ['exampleRef',       'references', 'Example Reference'],      # http://www.xbrl.org/2003/role/exampleRef       uk-ifrs, not uk-gaap  Reference to documentation that illustrates by example the application of the concept that assists in determining appropriate usage.

const TR_FirstDimensionId = 36; # uk/Dimension-GroupAndCompany        100 - Group and Company
const TR_FirstHypercubeId = 86; # uk/Hypercube-Basic  definitionLink  600 - Hypercube - Basic

# Taxonomy Arcrole Id (Arcroles.Id) NB! These need to be checked after a Taxonomy DB Build
# -------------------
const TA_ParentChild  = 1; # parent-child             - Presentation arcs
const TA_EssenceAlias = 2; # essence-alias           /- Definition arcs
const TA_DimAll       = 3; # dim/all                 |  Hypercube dimension members required by source  Source (a primary item declaration) requires a combination of dimension members of the target (hypercube) to appear in the context of the primary item.
const TA_DimDefault   = 4; # dim/dimension-default   |  Dimension default member                        Source (a dimension) declares that there is a default member that is the target of the arc (a member).
const TA_DimDomain    = 5; # dim/dimension-domain    |  Dimension has only target domain as its domain  Source (a dimension) has only the target (a domain) as its domain.
const TA_DomainMember = 6; # dim/domain-member       |  Domain contains member                          Source (a domain) contains the target (a member).
const TA_HypercubeDim = 7; # dim/hypercube-dimension |  Hypercube contains dimension                    Source (a hypercube) contains the target (a dimension) among others.
#onst TA_DimNotAll      8; # dim/notAll              |  Not used by UK GAAP
#onst TA_CL             9; # concept-label            - Label arcs
#onst TA_CR            10; # concept-reference        - Reference arcs
/*
Definition Arc Numbers:
TA_EssenceAlias    2  E.1069 CreditorsDueAfterOneYear  -> E.3056 LiabilitiesDueAfterOneYear
                      E.1072 CreditorsDueWithinOneYear -> E.3057 LiabilitiesDueWithinOneYear
TA_DimAll         46  = one for each Hypercube with the Hypercubes as the ToId
TA_DimDefault     33  not 42 as not all dimensions have a default.
TA_DimDomain      42  = one for each dimension with the Dimension as the FromId
TA_DomainMember 6114
TA_HypercubeDim  191  Hypercubes -> Dimensions

*/
# Taxonomy Link Type enums
# ------------------------
const TLT_Presentation = 1; # <presentationLink
const TLT_Definition   = 2; # <definitionLink
const TLT_Label        = 3; # <labelLink
const TLT_Reference    = 4; # <referenceLink
#onst TLT_Calculation  = 5; # <calculationLink /- Not used by UK-IFRS
#onst TLT_Footnote     = 6; # <footnoteLink    |

# Taxonomy Elements.TypeN enum
# ----------------------------     UK GAAP                Num    and     UK GAAP Num
#onst TET_None             0; # NULL                      181
const TET_Money         =  1; # xbrli:monetaryItemType   3247
const TET_String        =  2; # xbrli:stringItemType     1362            string   55
const TET_Boolean       =  3; # xbrli:booleanItemType      28
const TET_Date          =  4; # xbrli:dateItemType         36
const TET_Decimal       =  5; # xbrli:decimalItemType      46            decimal   1
const TET_Integer       =  6; # xbrli:integerItemType       9
const TET_NonZeroDecimal=  7; # xbrli:nonZeroDecimal        1
const TET_Share         =  8; # xbrli:sharesItemType       35
const TET_Uri           =  9; # xbrli:anyURIItemType        4            anyURI    1
const TET_Domain        = 10; # uk-types:domainItemType             1226
const TET_EntityAccounts= 11; # uk-types:entityAccountsTypeItemType    1
const TET_EntityForm    = 12; # uk-types:entityFormItemType            1
const TET_Fixed         = 13; # uk-types:fixedItemType                14
const TET_Percent       = 14; # uk-types:percentItemType             114
const TET_PerShare      = 15; # uk-types:perShareItemType             40
const TET_ReportPeriod  = 16; # uk-types:reportPeriodItemType          1
const TET_Any           = 17; # anyType                     2
const TET_QName         = 18; # QName                       2
const TET_Arc           = 19; # xl:arcType                  5
const TET_Doc           = 20; # xl:documentationType        2
const TET_Extended      = 21; # xl:extendedType             1
const TET_Locator       = 22; # xl:locatorType              2
const TET_Resource      = 23; # xl:resourceType             1
const TET_Simple        = 24; # xl:simpleType               2            anySimpleType   1
const TET_Title         = 25; # xl:titleType                1
# UK-IFRS
const TET_investmentPropertyMeasurement = 26; # investmentPropertyMeasurementItemType, a complex type enum Cost model | Fair value model


# Taxonomy Element SubstGroupN enums replacing Elements.substitutionGroup
# ----------------------------------
#onst TSG_None        0; # NULL                 23  Removed from DB build 10.10.12
const TSG_Item      = 1; # xbrli:item         6076
const TSG_Tuple     = 2; # xbrli:tuple         158
const TSG_Dimension = 3; # xbrldt:dimensionItem 42
const TSG_Hypercube = 4; # xbrldt:hypercubeItem 46
const TSG_LinkPart  = 5; # link:part            56  /- Removed from DB build 10.10.12
const TSG_Arc       = 6; # xl:arc                6  |
const TSG_Doc       = 7; # xl:documentation      1  |
const TSG_Extended  = 8; # xl:extended           6  |
const TSG_Locator   = 9; # xl:locator            1  |
const TSG_Resource  =10; # xl:resource           3  |
const TSG_Simple    =11; # xl:simple             4  |

# Taxonomy Element PeriodTypeN enums replacing Elements.periodType
# ----------------------------------
#onst TPT_None       0; # NULL      258
const TPT_Duration = 1; # duration  5098
const TPT_Instant  = 2; # instant   1066

# Taxonomy Elements.SignN enums replacing Elements.balance
# -----------------------------
#onst TS_None   0; # NULL   3711
const TS_Dr   = 1; # debit  1835
const TS_Cr   = 2; # credit  876

# Taxonomy Use Arcs.ArcUseN enums
# -------------------------
#onst TU_None         0; # Null      11892
const TU_Optional   = 1; # optional  13911
const TU_Prohibited = 2; # prohibited   50

# Taxonomy Context enums
# ----------------------
#onst TC_None         0; # NULL    25807
const TC_Segment    = 1; # segment    46
#onst TC_Scenario   = 2; # scenario    0  Not used by UK GAAP

##############
# Dimensions #
##############
# Dimension Ids or DimIds
const DimId_Group         =  1;
const DimId_ConDis        =  2;
const DimId_Restated      =  3;
const DimId_Excepts       =  4;
#           EquityClasses    5
#           OpSegs           6
#           ProdsServs       7
#           MajCusts         8
#           BizCombos        9
const DimId_PPEClasses    = 10;
const DimId_IFAClasses    = 11;
#           FIs             15
#     DimId_MPeriods        20
const DimId_ProvClasses   = 22;
#           PensionSchemes  23
#           Subsidiaries    28
#           ShareClasses    34
#           ShareTypes      35
#           TPAType         37
#           TPAStatus       38
#           Countries       42
#           Currencies      43
#           Exchanges       44
#           Languages       45
#           Activity        46 /- DPL ones
#           ExpenseType     47 |
#           ExceptNon       48 |
#           Analysis        49 |
#           GroupTrans      50 |
const DimId_Max           = 50;

# DiMeIds
const DiMeId_PyaAmount           =  23;
const DiMeId_PyaPriorPeriodIncr  =  24; # IncrDecr
const DiMeId_PyaAcctPolicyIncr   =  25;
const DiMeId_PyaMaterialErrIncr  =  26;
const DiMeId_PyaOriginalAmount   =  27;
const DiMeId_Max                 = 1718; # Max
const DiMeId_Unallocated         = 9999; # Pseudo DiMe for summing use when
                                         # - a base only (no DiMes) Bro is added to a target which has DiMes so that the base of the target will = the sum of the primary postings
                                         # - a summed DiMe is not available in the target

# DimensionMembers.Bits and Dimensions Map DiMesA
#onst DiMeB_None    =   0; Bit
const DiMeB_Normal  =   0; #   N  Normal i.e. no settings
const DiMeB_Default =   1; # 0 D  Taxonomy default
const DiMeB_BD      =   2; # 1 B  Braiins Dimension
#onst DiMeB_M       =   4; # 2 M# Mandatory (in most cases) with specific details and # as per DiMesA[DiMeId][DiMeI_MType]. See table below.
#onst DiMeB_Prop    =   8; # 3 P  Property type for use with an M DiMe e.g. Officers.Chairman, Officers.CEO....
const DiMeB_RO      =  16; # 4 R  Report Only = usable for reporting but not for posting
const DiMeB_SumKids =  32; # 5 S  DiMe kids Sum - one level down
const DiMeB_SumList =  64; # 6 S  DiMe list Sum
const DiMeB_muX     = 128; # 7 X  Mutually exclusive parent/child conditions apply
const DiMeB_pYa     = 256; # 8 Y  PYA (Restated)
const DiMeB_Zilch   = 512; # 9 Z  Not ever user selectable being reserved for Braiins RG generation e.g. Officers.AllEntityOfficers. As per DiMeB_RO with extra restriction os not being usable in a format.

#onst MType_TFA_LandBldg= 2; # TFAClasses.LandBuildings - Buildings    -> TFAOwnership.OwnedOrFreehold, ShortLeaseholdProperties, and LongLeaseholdProperties (185, 187, 188)
#onst MType_TFA_Other   = 3; # TFAClasses.InvestProperties - Others    -> TFAOwnership.OwnedOrFreehold, Leased (185, 186)
#onst MType_Director    = 4; # Officers.Director1       - Director40   -> Officers.Chairman, CEO, ChairmanCEO, CoSecDirector, OfficerType.Executive, OfficerType.Nonexecutive  (419, 420, 421, 424, 425, 487, 488)
#onst MType_PartnerLLP  = 5; # Officers.PartnerLLP1     - PartnerLLP20 -> Officers.ChiefPartnerLLP (422)

# Inner indices for $DiMesA, array of Dimension Member Info by DiMeId -> [DimId, DiMeBits, PropSet, ETypeList, MuxList, SumList]
const DiMeI_DimId     = 0; # DimId Dimension Id
const DiMeI_Bits      = 1; # Bits  Dimension Member Bits
const DiMeI_MuxListA  = 2; # Array of DiMeIds with which this DiMe is Mutually Exclusive - DiMeB_muX bit is set when this applies
const DiMeI_SumListA  = 3; # Array of DiMeIds to be summed to this DiMe                  - DiMeB_Sum bit is set when this applies or has children

# Hypercube Ids
#onst HY_Id_Basic     =  1;
#onst HY_Id_Income    =  2;
#onst HY_Id_Officers  = 36;
#onst HY_Id_TPAs      = 39;
#onst HY_Id_Countries = 40;
const HyId_Max        = 43; # Excl Empty


##########
# Tuples #
##########

const Max_TupleId = 82;

# Tuple Use Code or TUC Enums
const TUC_O = 1; # O Optional once corresponding to Taxonomy minOccurs=0 and maxOccurs=1
const TUC_M = 2; # M Mandatory once if tuple used corresponding to Taxonomy minOccurs=1 and maxOccurs=1
const TUC_U = 3; # U Optional Unbounded corresponding to Taxonomy minOccurs=0 and maxOccurs=unbounded

# DimensionMembers.Bits
# ---------------------
# Constants from ConstantsRg.inc used

#############
# Functions #
#############

function ArcroleIdToStr($id) {
  static $a = ['', 'Presentation', 'Essence Alias', 'Definition: Hypercube<br>dimension members<br>required by source<br>(dimension-all)', 'Definition: Dimension<br>default member<br>(dimension-default)', 'Definition: Dimension<br>has only target<br>domain as its domain<br>(dimension-domain)', 'Definition: Domain<br>contains member<br>(domain-member)', 'Definition: Hypercube<br>contains dimension<br>(hypercube-dimension)','','Label','Reference'];
  return $a[(int)$id];
}

function ElementTypeToStr($typeN) {
  static $a = ['None', 'Money', 'String', 'Boolean', 'Date', 'Decimal', 'Integer', 'NonZeroDecimal', 'Share', 'Uri',
             'Domain', 'EntityAccounts', 'EntityForm', 'Fixed', 'Percent', 'PerShare', 'ReportPeriod', 'Any',
             'QName', 'Arc', 'Doc', 'Extended', 'Locator', 'Resource', 'Simple', 'Title', 'InvestmentPropertyMeasurement'];
  return $a[(int)$typeN];
}

function SubstGroupToStr($typeN) {
  static $a = ['', 'Item', 'Tuple', 'Dimension', 'Hypercube']; # , 'LinkPart', 'Arc', 'Documentation', 'Extended', 'Locator', 'Resource', 'Simple'
  return $a[(int)$typeN];
}

function LinkTypeToStr($typeN) {
  static $a = ['', 'Presentation', 'Definition', 'Label', 'Reference']; #, 'Calculation', 'Footnote'
  return $a[$typeN];
}

function PeriodTypeToStr($typeN) {
  static $a = ['', 'Duration', 'Instant'];
  return $a[(int)$typeN];
}

function UseTypeToStr($typeN) {
  static $a = ['', 'Optional', 'Prohibited'];
  return $a[(int)$typeN];
}

function ContextTypeToStr($typeN) {
  static $a = ['', 'Segment']; # , 'Scenario' Not used by UK GAAP
  return $a[(int)$typeN];
}

function BoolToStr($b) {
  # if ($b === '') return ''; # null == default whether true or false
  return $b ? 'true' : 'false';
}

function TUCNStr($enum) {
  static $cons = ' OMU';
  return $cons[(int)$enum];
}

###################
# Ids and Globals #
###################

const ElId_NotApplicable  = 4815;
const ElId_EmptyHypercube = 4459;

# List of Tx Elements which have StartEnd values. See Doc/Tx/UK-IFRS-DPL/StartEndPeriodNotes.txt for its derivation.
$StartEndTxIdsGA=[788, 1070, 1697, 2235, 2247, 4626, 4633, 4742, 4746, 5783, 6036, 7915, 8524];

# Array of Tuples for Members by TxId which are Members of Multiple Tuples whose use can be narrowed down by Hypercube.
# All such elements have a Tuple Use Code of O - Optional or 0,1 Occurs
# See Doc/BrosAndTx/TuplesNotes.txt for its derivation.
$TupIdsByMemberTxIdAndHyIdGA = [];
/*
$TupIdsByMemberTxIdAndHyIdGA = [ # [TupleMemberTxId (TuplePairs.MemTxId) => [HyId => TupId]]
  3237 => [22 => 111, 23 => 113, 24 => 140],
  3382 => [28 =>  44, 41 => 122],
  5186 => [28 =>  44, 40 => 146, 41 => 122]];
*/

?>

