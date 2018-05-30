<?php /* Copyright 2011-2013 Braiins Ltd

/Com/inc/ConstantsRgWip.inc

UK-GAAP-DPL ConstantsRg.inc being reworked as items are moved to ConstaantsBRL.inc

History:
22.03.13 Started based on UK-GAAP-DPL/ConstantsRg.inc

ToDo
----
Replace Level in $BroInfoA by SetMaxId
Add DefaultDiMeId to $BroInfoA
Add Set is StartEnd and kids are not e.g. Tx542 re Import StartEnd processing.

*/

const BRAIINS_RG_VERSION = 'Braiins Report Generator v.UK-GAAP-DPL.1.0';

# ContextN Enums for BroInfoA[BroI_ContextN] (Not in DB)
# --------------
const RGC_Period       = 1; # Default or standard. Applies to both Instant and Duration Tx elements.
const RGC_Officer      = 2; # Officer -> CompanySecretaryY#, Director#Y#, AllEntityOfficersY#, HighestPaidDirectorY#
const RGC_AllOfficers  = 3;
const RGC_TPA          = 4; # TPA     -> AccountantsY#, AuditorsY# etc
const RGC_RegOffice    = 5;
const RGC_UkCountry    = 6; # UkCountry[Country]Y#
const RGC_HighestPaidDirector = 7; # RG use only


# BRO DB Constants
# ================
# BroInfo.Id and related constants
# -----------------------------
#const BroId_Info_EntityName              = 5628; /- In Entity ADO
#const BroId_Info_RegistrationNumber      = 5634; |
#const BroId_Info_IncorporationCountry    = 5644; |
const BroId_Info_Audited                 = 5659;  IFRS too?
const BroId_Info_Dormant                 = 5660;  IFRS too?
const BroId_Info_Trading                 = 5661;  IFRS too?
##nst BroId_Dates_YearStartDate          = 5730; /- Move to Entity ADO plus full set of periods?
##nst BroId_Dates_YearEndDate            = 5731; |
##nst BroId_Dates_SigningDirectorsReport = 5905; |
##nst BroId_Dates_BS                     = 5727; |
##nst BroId_Dates_ApprovalAccounts       = 5728; |
const BroId_Statements_NoOtherGainsOrLosses  = 5430; IFRS too?
#onst BroId_Officers_Properties              = 5670; /- All via Officers ADOs => 1 BroId constant for Officers
#onst BroId_Officers_Name                    = 5671; |
#onst BroId_Officers_DirectorSigningReport   = 5904; |
#onst BroId_Officers_DirectorSigningAccounts = 5672; |
#onst BroId_TPAs_AccountantsAndAuditors      = 5696; 

# Numbers of ...
# ----------
const BRO_NumAddressLines =  6; # Number of address lines: AddressLine1 to PostCode, Elements.Id 5341,5342,5343,5576,5377,5568 as per InitRGDB.php
const BRO_NumDirectors    = 40;


##############
# Dimensions #
##############
# Dimension Ids or DimIds
const DimId_Group         =  1;
const DimId_Consol        =  2;
const DimId_Restated      =  3;
const DimId_OpActivities  =  4;
const DimId_ExceptAdjusts =  5;
const DimId_AmortAdjusts  =  6;
#           BizSegs          7
#           ProvisionClasses 8
const DimId_IFAClasses    =  9;
const DimId_TFAClasses    = 10;
const DimId_TFAOwnership  = 11;
const DimId_FAIHoldings   = 12;
const DimId_FAITypes      = 13;
#           Dividends       14
#           PensionSchemes  15
const DimId_MPeriods      = 21;
#           Subsidiaries    27
const DimId_Officers      = 29;
const DimId_OfficerType   = 30;
#           ShareClasses    31
#           ShareTypes      32
#           ContactType     33
const DimId_TPAType       = 34;
const DimId_TPAStatus     = 35;
#           FormContact     36
#           AddressType     37
#           TelNumType      38
#onst DimId_Countries       39
#onst DimId_Currencies      40
#onst DimId_Exchanges       41
#onst DimId_Languages       42
#           Activty         43 /- DPL ones
#           ExpenseType     44 |
#           ExceptNon       45 |
#           Analysis        46 |
#           GroupTrans      47 |

# DiMeIds
const DiMeId_AllOfficers         = 418;
const DiMeId_Chairman            = 419;
const DiMeId_CEO                 = 420;
const DiMeId_ChairmanCEO         = 421;
const DiMeId_ChiefPartnerLLP     = 422;
const DiMeId_CoSec               = 423;
const DiMeId_CoSecDirector       = 424;
const DiMeId_HighestPaidDirector = 425;
const DiMeId_Director1           = 426;
const DiMeId_Director40          = 465;
const DiMeId_PartnerLLP1         = 466;
const DiMeId_PartnerLLP20        = 485;
const DiMeId_ExecutiveOfficer    = 487;
const DiMeId_NonexecOfficer      = 488;
const DiMeId_RegOffice           = 511;
const DiMeId_Accountants         = 522; # /- Re fudge for lack of separate Accountants and Auditors Dimensions in Taxonomy.
const DiMeId_Auditors            = 523; # |  [A] 5461 Entity accountants or auditors */
const DiMeId_Receivers           = 530;
const DiMeId_Countries_England   = 558;
const DiMeId_Max                 = 1373; # Max
const DiMeId_Unallocated         = 9999; # Pseudo DiMe for summing use when
                                         # - a base only (no DiMes) Bro is added to a target which has DiMes so that the base of the target will = the sum of the primary postings
                                         # - a summed DiMe is not available in the target

# DimensionMembers.Bits and Dimensions Map DiMesA and DiMesRA Bits
#nst DiMeB_None     =   0; Bit
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

# Inner indices for $DiMesA, array of Dimension Member Info by DiMeId -> [DimId, DiMeBits, PropSet, MuxList, SumList]
const DiMeI_DimId     = 0; # DimId Dimension Id
const DiMeI_Bits      = 1; # Bits  Dimension Member Bits
const DiMeI_MuxListA  = 2; # Array of DiMeIds with which this DiMe is Mutually Exclusive - DiMeB_muX bit is set when this applies
const DiMeI_SumListA  = 3; # Array of DiMeIds to be summed to this DiMe                  - DiMeB_Sum bit is set when this applies or has children

# Inner indices for $DiMesRA, RG Run time array of Dimension Member Info by DiMeId
const DiMeRI_DimId     = 0; # DimId Dimension Id
const DiMeRI_Bits      = 1; # Bits  Dimension Member Bits as in DiMesA
const DiMeRI_NsId      = 2; # NsId          - dimension member NS -> context explicit member NSs (twice)
const DiMeRI_DiMe_Name = 3; # DimName_MemberNormalisedName - used in the context ref
const DiMeRI_DimEleName  = 4; # Dim Element Name - gives context explicit dimension when NS: is prepended with 'Dimension' stripped from end to same space in struct. To be put back when building contexts.
const DiMeRI_DiMeEleName = 5; # DiMe Element Name - gives context explicit member   when NS: is prepended

##########
# Tuples #
##########

const Max_TupleId = 156; # 26.12.12 changed from 158

# Tuple Use Code or TUC Enums
const TUC_O = 1; # O Optional once corresponding to Taxonomy minOccurs=0 and maxOccurs=1
const TUC_M = 2; # M Mandatory once if tuple used corresponding to Taxonomy minOccurs=1 and maxOccurs=1
const TUC_U = 3; # U Optional Unbounded corresponding to Taxonomy minOccurs=0 and maxOccurs=unbounded

/* Tuple related arrays:
$TuMesA        TuMeId => TuMe Info array: [TupId, Ordr, TUCN] TuMeI_TupId, TuMeI_Ordr, TuMeI_TUCN
$TupNamesA     TupId  => TuShortName as used in BroRefs
$TupFullNamesA TupId  => Tx Name incl NS  for Tx Tuple Names used by Bros for use with <ix:tuple name=
$TupLabelsA    TupId  => Tuple Std Labels stripped of ' [grouping]' used for building full BroRefs as in the TB
*/

# Inner indices for $TuMesA, array of TuMe info by TuMeId => [TupId, Ordr, TUCN]
const TuMeI_TupId = 0;
const TuMeI_Ordr  = 1;
const TuMeI_TUCN  = 2;

########
## RG ## RG Language Constants used both at Compile and Run time
########

# Xref attribute bits derived from one of target|text|page|both|link
# Bits values are the same as if these were enums
const XrefB_Target = 0;
const XrefB_Text   = 1; # /- both set for both
const XrefB_Page   = 2; # |
const XrefB_Both   = 3; #
const XrefB_Link   = 4; #

# [date attribute enums: one of f|s|x|y
const DATE_Full   = 0; # f: full  form e.g. 03 February 2011
const DATE_Short  = 1; # s: short form e.g. 03 Feb 2011 or 03/02/2011 or 03.02.2011 as per preferences
const DATE_iXBRL  = 2; # x: iXBRL form e.g. 2011-02-03
const DATE_Year   = 3; # y: year  form e.g. 2011

# Table Column Types
const CT_Text  = 1;
const CT_Descr = 2;
const CT_Note  = 3;
const CT_Money = 4;
const CT_Calc  = 5; # For calculated results e.g. variances formatted like CT_Money i.e. potentially with brackets, but no tag.
const CT_Perc  = 6; # Percent

# Col Datatype Bits re the dat parameter to [col statement Col() calls
# No bits set means that dat is the data via a parameter expression, with tag via Row BroRef if defined, o'wise no tag
#                                dat passed     ->
#                           0    param expr     dat from param expr with tag if Row BroRef is defined
const CDtB_ColBroRef     =  1; # Col BroRef     Col Bro for dat and tag
const CDtB_ColBroRefExpr =  2; # [BroRef, Expr] Bro from dat[0] for tag, dat from parameter expression in dat[1], or Expr = fnNum for later eveualtion if CDtB_CellExpr is also set
const CDtB_RowBroRef     =  4; # 0              Row Bro for dat and tag
const CDtB_Mapping       =  8; # -              BroRef is to be checked for possible mapping
const CDtB_Descr         = 16; # -              BroRef is to be used for a Descr() call, else for Bro data
const CDtB_RowOp         = 32; # 0              Dat is to come from a row operation - subtotal, total, rowExpr
const CDtB_CellExpr      = 64; # fnNum          Dat is to come from a call to fnNum, evaluated in TableEnd() pass when running col sums and RowOp values are available.
                               #                Can also be set for a CDtB_ColBroRefExpr Expr
# Combos
const CDtB_BroRef         =  7; # CDtB_ColBroRef | CDtB_ColBroRefExpr | CDtB_RowBroRef
const CDtB_ColBroRefDescr = 17; # CDtB_ColBroRef | CDtB_Descr
const CDtB_RowBroRefDescr = 20; # CDtB_RowBroRef | CDtB_Descr

# Statement options bit settings            row col row -> col at CT
const OptB_dr          =    1; # dr          *   *   *         Same as BS_Dr
const OptB_cr          =    2; # cr          *   *   *         Same as BS_Cr
const OptB_ul          =    4; # ul          *   *   *
const OptB_dul         =    8; # dul         *   *   *
const OptB_aul         =   16; # aul         *   *   *
const OptB_adul        =   32; # adul        *   *   *
const OptB_keep        =   64; # keep        *   *
const OptB_keepHide    =  128; # keepHide    *   *
const OptB_subtotal    =  256; # subtotal    *
const OptB_total       =  512; # total       *
#onst OptB_rowExpr             # rowExpr     *      Not implemented
const OptB_cellExpr    = 1024; # cellExpr    *   *  cell expr not involving this row. Set for row if any col includes a cellExpr
const OptB_thisExpr    = 2048; # thisExpr    *   *  cell expr involving this row.
const OptB_pyaHdg      = 4096; # restatedHdg     *
const OptB_hor         = 8192; # hor             *
# Combos
const OptB_AnySign     =    3; # OptB_dr | OptB_cr
const OptB_AnyBul      =   12; # OptB_ul | OptB_dUl
const OptB_AnyAul      =   48; # OptB_aul  | OptB_adul
const OptB_AnyUl       =   60; # OptB_ul   | OptB_dUl | OptB_aul | OptB_adul
const OptB_AnyKeep     =  192; # OptB_keep | OptB_keepHide
#onst OptB_AnyRowOp    = 1792; # OptB_subtotal | OptB_total | OptB_rowExpr = 256 + 512 + 1024
const OptB_AnyTotalOp  =  768; # OptB_subtotal | OptB_total = 256 + 512

###########
# Globals #
###########

# Dims to exclude from Summing Bros
# $SummingBroExclDimsChrsGA = ['X', 'Y', 'Z']; # DimId_Currencies, DimId_Exchanges, DimId_Languages or 40, 41, 42.  26.03.13 No. Removed this. Could want sums by these.
# Dims to exclude from Non-Summing Bros
$NonSummingBroExclDimsChrsGA = ['3']; # DimId_Restated

 $DimsWithMtypeDiMesA = [DimId_IFAClasses, DimId_TFAClasses, DimId_FAIHoldings, DimId_Officers, DimId_TPAType]; # 9, 10, 12, 29, 34 Array of Dims which have M type DiMes
#$DimsReqPropDiMesA   = [                  DimId_TFAClasses, DimId_FAIHoldings, DimId_Officers, DimId_TPAType]; #    10, 12, 29, 34 Array of Dims which require a Property


#############
# Functions #
#############


/* function ContextStr($enum) {
  static $cons = ['None', 'Period', 'Officer', 'AllOfficers', 'TPA', 'RegOffice', 'UkCountry'];
  return $cons[(int)$enum];
} */

function TUCNStr($enum) {
  static $cons = ' OMU';
  return $cons[(int)$enum];
}

