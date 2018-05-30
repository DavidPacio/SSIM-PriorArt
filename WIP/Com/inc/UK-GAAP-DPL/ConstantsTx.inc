<?php /* Copyright 2011-2013 Braiins Ltd

/Com/inc/UK-GAAP-DPL/ConstantsTx.inc

UK-GAAP-DPL taxonomy constants, globals, and to string functions

History:
29.09.12 Started based on UK-GAAP/ConstantsTx.inc
16.02.13 TPT_Duration and TPT_Instant reversed to suit extra PeriodSEN Instant values added to in ConstantsRg.inc so that >= TPT_Instant means instant for a Bro
*/

# Taxonomy Constants
# ==================
# Taxonomy Role Id (Roles.Id) for the XBRL roles. NB! These need to be checked after a Taxonomy DB Build
# ----------------
const TR_StdLabel = 1; # label             Standard Label
const TR_Doc      = 2; # documentation     Documentation
const TR_PSL      = 3; # periodStartLabel  Period Start Label
const TR_PEL      = 4; # periodEndLabel    Period End Label
const TR_VB       = 5; # verboseLabel      Verbose Label
const TR_Ref      = 6; # reference         Reference
const TR_FirstDimensionId = 31; # uk/Dimension-GroupAndCompany        100 - Group and Company
const TR_FirstHypercubeId = 79; # uk/Hypercube-Basic  definitionLink  600 - Hypercube - Basic

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
#onst TLT_Calculation  = 5; # <calculationLink /- Not used by UK GAAP
#onst TLT_Footnote     = 6; # <footnoteLink    |

# Taxonomy Elements.TypeN enum replacing Elements.type
# ----------------------------     UK GAAP                   Num    and     UK GAAP Num
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
             'QName', 'Arc', 'Doc', 'Extended', 'Locator', 'Resource', 'Simple', 'Title'];
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

###################
# Ids and Globals #
###################

const ElId_EmptyHypercube = 5459;
const ElId_LastGAAP       = 6401; # Id of the last UK-GAAP element. Anything after this is a DPL element.

# List of Tx Elements which have StartEnd values. See Doc/BrosAndTx/StartEndPeriodNotes.txt for its derivation.
# These => SumEnd, PostEnd or Stock, Bros as per 25.12.11 section in Doc/BrosAndTx/StartEndPeriodNotes.txt
# 25.12.11 541 and 575, which were not SE by Tx but were added on 16.11.11 re their usage, removed as these now => Acc type Bros
$StartEndTxIdsGA=[82,145,146,222,511,542,1110,1170,1172,1242,2002,2004,2120,2132,2155,2167,2223,2467,2805,2815,3019,3090,3092,3198,3278,3377,3379,3387,3388,3389,3465,3642,3696,3697,3984,4062,4239,4265,4267,4269,4397,4403,4536,4546,4551,4654,4655,4810,5022,5078,5092,5114,5121,5230,5234];

# Array of Tuples for Members by TxId which are Members of Multiple Tuples whose use can be narrowed down by Hypercube.
# All such elements have a Tuple Use Code of O - Optional or 0,1 Occurs
# See Doc/BrosAndTx/TuplesNotes.txt for its derivation.
$TupIdsByMemberTxIdAndHyIdGA = [ # [TupleMemberTxId (TuplePairs.MemTxId) => [HyId => TupId]]
  3237 => [22 => 111, 23 => 113, 24 => 140],
  3382 => [28 =>  44, 41 => 122],
  5186 => [28 =>  44, 40 => 146, 41 => 122]];

?>

