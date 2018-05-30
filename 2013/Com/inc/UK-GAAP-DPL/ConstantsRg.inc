<?php /* Copyright 2011-2013 Braiins Ltd

/Com/inc/UK-GAAP-DPL/ConstantsRg.inc

UK-GAAP-DPL Report Generator constants used in more places than just the compiler e.g. utils or run time
plus some arrays and enum to string function

History:
20.09.12 Started based on UK-GAAP/ConstantsRg.inc
27.10.12 Added Bro Bit BroB_Summing
14.11.12 BroI_Level -> BroI_SetMaxId
         BroI_AllowDims -> BroI_BroUsableDims
         BroI_SumUsablePropDims added
         Php 5.4 []s changes
12.12.12 $NonSummingBroExclDimsChrsGA added
15.02.13 BD Maps removed
16.02.13 PeriodN added to BroInfoA replacing some Bit values
20.04.13 DiMeI_ETypeList removed as Entity Types are now a BRL matter.
         MType also
21.04.13 Braiins Dimension removed

ToDo
----
Replace Level in $BroInfoA by SetMaxId
Add DefaultDiMeId to $BroInfoA
Add Set is StartEnd and kids are not e.g. Tx542 re Import StartEnd processing.

*/

const BRAIINS_RG_VERSION = 'Braiins Report Generator v.UK-GAAP-DPL.1.0';

############
# BroInfoA #
############

# Inner indices for $BroInfoA, the array of Bro Info by Id
const BroI_Bits          =  0;
const BroI_SetMaxId      =  1; #   Max BroId of a Set Bro, used in down the tree mux check during posting. Also is truthy re bro being a Set.
const BroI_DataTypeN     =  2;
const BroI_DadId         =  3; #   BroInfo.Id of Parent if Bro has a parent = all except Level 0 Sets
const BroI_PeriodSEN     =  4; #   Provides StartEnd and Period info
const BroI_SignN         =  5; #   0|1|2 for undefined | Dr | Cr
const BroI_BroUsableDims =  6; #   Usable Dims chr list for just this Bro = tagable Dims. 0 if not applicable or is an Ele Slave without UsableDims filtering.
                               #   Remains set for a Set Slave re summing and SumUsableDims of any non-slave set members.
const BroI_SumUsableDims =  7; #   Usable Dims chr list for Posting/Summing = BroUsableDims + any extra Dims in SumUp ancestors. 0 if same as BroUsableDims (always for non-summing or RO Bros)
const BroI_DiMesA        =  8; #   Bro DiMe Overrides [] as below, 0 if empty
const BroI_Zones         =  9; #
const BroI_TxId          = 10; #
# Ends here if non-Tx, not Master, not Slave
const BroI_SlaveIdsA     = 11; #   Array of BroInfo.Ids of Slaves of this Bro if a Master /- slot shared
const BroI_SlaveYear     = 11; #   Rel Year for a Prior Year Slave 1-3        if a Slave  |
const BroI_TuMeId        = 12; # * TuMeId which applies if the Bro involves Tuple use. Converted from BroInfo.TupId by Build Structs
const BroI_ContextN      = 13; # * Deduced by Build Structs for TxId Bros
/*                             # * - only if Tx Based
BroDiMesA =  Bro DiMes Overrides array [i => MandatsA, DefaultsA, ExcludesA, AllowsA]
Inner indices for BroDiMesA */
const II_MandatsA  = 0; # MandatsA  = i array of Mandatory DiMes, Dim in Bro Usable Dims or DiMe in AllowsA, only one per Dim, Mux with Defaults and Excludes
const II_DefaultsA = 1; # DefaultsA = i array of Default   DiMes, Dim in Bro Usable Dims or DiMe in AllowsA, only one per Dim, Mux with Mandats  and Excludes
const II_ExcludesA = 2; # ExcludesA = i array of Exclude   DiMes, Dim in Bro Usable Dims, Mux with Mandats, Defaults, and Allows
const II_AllowsA   = 3; # AllowsA   = i array of Allow     DiMes, No Usable Dims, or Dim in Bro ExclDims, Mux with Excludes


# Check info is not stored in $BroInfoA. All is done via $CheckBrosA. See Braiins RG DB.txt and BuildStructs.php and Import.php

# BroInfo.Bits
# ------------               Bit
const BroB_Set     =    1; #  0 Is a Set      /- MUX
const BroB_Ele     =    2; #  1 Is an Element |  Could have used !BroB_Set but made explicit for testing simplicity
const BroB_Type    =    3; #    Combo for Type masking BroB_Set | BroB_Ele
const BroB_Slave   =    4; #  2 Is a Slave       Also if MasterId col is set /- MUX |
const BroB_Master  =    8; #  3 Has Slave kid(s) Also if SlaveIds col is set |
const BroB_DE      =   16; #  4 Posting Type for Money Bros: Set if is a DE or CoA type. Unset = Schedule
const BroB_RO      =   32; #  5 Set if is a Report Only Bro i.e. usable for reporting but not for posting i.e. is Non-Posting
const BroB_Summing =   64; #  6 Set if is a Summing Bro
const BroB_M       =  128; #  7 Set if Bro is not RO and the Bro's Usable Dims includes one of the Dims which include M# Type DiMes
const BroB_2       =  256; #  8 Set if BroB_M is set and Bro requires a Property DiMe, with the M0 DiMe 423 CoSec being an exception which is tested for in BroRefPost()
                           #    Is not set for a Bro whose AllowsDims includes 12 FAIHoldings or 34 TPAType (all M1 type DiMes), if the next Dim (13 FAITypes or 35 TPAStatus) has been excluded from the Bro.
const BroB_NoMok   =  512; #  9 Set for Officers, Money Type, 423 CoSec excluded for which a post wo an M# type DiMe is OK
const BroB_NoTags  = 1024; # 10 Set if No iXBRL Tags are to be generated on use of the Bro in the RG. Duplicate HyId/TxId/TupId allowed.
const BroB_SFilter = 2048; # 11 Set for a Slave if filtering applies
const BroB_Except  = 4096; # 12 Set if is for an exceptional item re Dim 5 ExceptionalItemsAdjustmentsDimension       summing
const BroB_Amort   = 8192; # 13 Set if is for amortisation etc    re Dim 6 AmortisationImpairmentAdjustmentsDimension summing

/* Instant Bits and Instant/StartEnd Types
   Apply to Bros based on Instant Tx elements

const InB_NotSE    M Instant but not StartEnd = based on an Instant Tx element not StartEnd as per the Taxonomy.
const InB_SE       M Has Start and End. Mux with InB_NotSE. (Have both so that any Instant has BroB_Instant set).
                     Start = Previous period End and is RO, except for first year of data in SumEnd case only.
                     Uses BroT_Start for Start handling, with End the default.
                     Bro's Tx element is StartEnd as per the Taxonomy, listed in $StartEndTxIdsGA
                     Has a SE List, only 1 member long in case of Stock
const InB_SumEnd     End is RO and = Start + the Sum of the SE List of Bros
                     TB includes: Start balance AND the SE List Bro (Duration or End) values
const InB_PostEnd    End is to be posted. End is not RO.
const InB_Stock      Is a Stock type Bro. See 'Stock' StartEnd case below for significance.
                   |- M = pair of bits Mux
The four Instant/StartEnd Types:
SE
Type    Bits
No      InB_NotSE    Bro based on an Instant Tx element that is not StartEnd as per the Taxonomy, nor in an accounting sense
                      i.e. a Bro that really has just One instant value, such as average number of staff.
                     No SE List
                     No Start = no use of BroT_Start = just End effectively
                     Included in TB
                     Tag: End (or a specific date??)

SumEnd  InB_SE       Bro based on an Instant Tx element that is StartEnd as per the Taxonomy, listed in $StartEndTxIdsGA
        InB_SumEnd   Has a SE List
                     Start = previous period End and is RO unless for the first year of data in Braiins i.e. no prior year
                     End = Start + the Sum of the SE List of Bros, all of which have Duration Period (?? 136 for 3019 and 222 are Instant)
                     TB includes: Start balance AND the SE List Bros
                     Tag: Start or End according to the Bro Reference
                     Uses BroT_Start for Start handling, with End the default.

PostEnd InB_SE       Bro based on an Instant Tx element that is StartEnd as per the Taxonomy, listed in $StartEndTxIdsGA
        InB_PostEnd  Has a SE List
                     Start = previous period End and is RO, wo the the first year of data in Braiins exception for SumEnd
                     End is to be posted.
                     The SE List provides a check, or means of calculating missing values.
                     TB includes: End balance but NOT the SE List Bros.
                     Tag: Start or End according to the Bro Reference
                     Uses BroT_Start for Start handling, with End the default.

Stock   InB_SE       Similar to PostEnd with the additional property of being a Stock Bro.
        InB_PostEnd  Differences from PostEnd
        InB_Stock    TB includes: P&L: Start and End with sign reversed
                                   BS:  End
                     Movement to be taken into account when checking that a posting journal balances.
*/

# BroInfo.DataTypeN enums
# --------------------
const DT_None     = 0;
const DT_String   = 1; # S String. All types > DT_String are numerical
const DT_Integer  = 2; # I Integer number
#onst DT_Money    = 3; # M Money number (processed internally as a 64 bit integer with 2 places of implied decimals, used to avoid rounding issues)
const DT_Money    = 3; # M Money number (processed internally as a 64 bit integer with 0 places of decimals (whole pounds assumed).
const DT_Decimal  = 4; # E Decimal or floating point number for percentages, ratios, or other calculated values with possible decimal fractions
const DT_Date     = 5; # D Date
const DT_Boolean  = 6; # B Boolean value 1 or 0 i.e. True or False, Yes or No. (Actually anything other than 0 means True/Yes.)
const DT_Enum     = 7; # N Enumerator
const DT_Share    = 8;
const DT_PerShare = 9;
const DT_Percent  = 10;

# BroInfo.SignN enums
# -------------------
#onst BS_None  = 0;
const BS_Dr    = 1; # debit  same as Tx constant TS_Dr
const BS_Cr    = 2; # credit same as Tx constant TS_Cr
const BS_Either= 3; # Equiv of Tx 'Not Set' via BS_None but made specirfic for RG = not undefined

# BroInfo.PeriodSEN enums
# -----------------------
#onst BPT_None          0;
const BPT_Duration    = 1; # Same as TPT_Duration
const BPT_Instant     = 2; # Same as TPT_Instant
const BPT_InstSumEnd  = 3; # Instant StartEnd SumEnd  type
const BPT_InstPostEnd = 4; # Instant StartEnd PostEnd type
const BPT_InstStock   = 5; # Instant StartEnd Stock   type

# BroInfo.SumUp enums
# -------------------
#onst BroSumUp_None = 0; # Not a summing Bro
const BroSumUp_Yes  = 1; # + or Yes or Sum Up
const BroSumUp_No   = 2; # No or Stop summing
const BroSumUp_NA   = 3; # NA because Kid has diff data type from Dad

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
const BroId_Info_EntityName              = 5628;
const BroId_Info_RegistrationNumber      = 5634;
const BroId_Info_IncorporationCountry    = 5644;
const BroId_Info_Audited                 = 5659;
const BroId_Info_Dormant                 = 5660;
const BroId_Info_Trading                 = 5661;
const BroId_Dates_YearStartDate          = 5730;
const BroId_Dates_YearEndDate            = 5731;
const BroId_Dates_SigningDirectorsReport = 5905;
const BroId_Dates_BS                     = 5727;
const BroId_Dates_ApprovalAccounts       = 5728;
const BroId_Statements_NoOtherGainsOrLosses  = 5430;
const BroId_Officers_Properties              = 5670;
const BroId_Officers_Name                    = 5671;
const BroId_Officers_DirectorSigningReport   = 5904;
const BroId_Officers_DirectorSigningAccounts = 5672;
const BroId_TPAs_AccountantsAndAuditors      = 5696;

# Hypercube Ids
#onst HY_Id_Basic        =  1;
#onst HY_Id_IncomeData   =  2;
#onst HY_Id_Officers     = 38;
#onst HY_Id_TPAs         = 41;
#onst HY_Id_Countries    = 42;
const HyId_Max           = 47; # Excl Empty

const TxElId_NotApplicable = 5339;

# Numbers of ...
# ----------
const BRO_NumAddressLines =  6; # Number of address lines: AddressLine1 to PostCode, Elements.Id 5341,5342,5343,5576,5377,5568 as per InitRGDB.php
const BRO_NumDirectors    = 40;

# Bro Account Type Enums
# ----------------------
const BAT_BS           = 1; # Balance Sheet
const BAT_CF           = 2; # Cash Flow
const BAT_PL           = 3; # P&L
const BAT_DetailedPL   = 4; # Detailed P&L
const BAT_HistoricalPL = 5; # Historical P&L
const BAT_STRGL        = 6; # STRGL
const BAT_Notes        = 7; # Notes
const BAT_Info         = 8; # Info
const BAT_Other        = 9; # Other

# BRO Reference Bits
# ------------------
const BRefB_DiMeMan = 1; # Bro Ref contains an M or N type DiMe
const BRefB_DiMeMux = 2; # Bro Ref contains a DiMe with a Mux list
const BRefB_DiMePya = 4; # Bro Ref contains a PYA DiMe
const BRefB_Start   = 8; # Bro Ref is Start which is allowed for in some data import cases. In RG BroRefs Start is detected by presence of BroT_Start, the Pseudo DiMe for Start

# Inner indices for $ZonesA, the array of Zones by Id
const ZI_Ref       = 0;
const ZI_SignN     = 1;
const ZI_AllowDims = 2;

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
const DimId_Max           = 47;

# DiMeIds
#onst DiMeId_Start               =   0; # Pseudo DiMe for StartEnd Start handling. Removed 28.12.12 on completion of RG conversion to Bro Class use.
const DiMeId_PyaAmount           =  73;
const DiMeId_PyaPriorPeriodIncr  =  74; # IncrDecr
const DiMeId_PyaAcctPolicyIncr   =  75;
const DiMeId_PyaMaterialErrIncr  =  76;
const DiMeId_PyaOriginalAmount   =  77;
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

#onst MType_TFA_LandBldg= 2; # TFAClasses.LandBuildings - Buildings    -> TFAOwnership.OwnedOrFreehold, ShortLeaseholdProperties, and LongLeaseholdProperties (185, 187, 188)
#onst MType_TFA_Other   = 3; # TFAClasses.InvestProperties - Others    -> TFAOwnership.OwnedOrFreehold, Leased (185, 186)
#onst MType_Director    = 4; # Officers.Director1       - Director40   -> Officers.Chairman, CEO, ChairmanCEO, CoSecDirector, OfficerType.Executive, OfficerType.Nonexecutive  (419, 420, 421, 424, 425, 487, 488)
#onst MType_PartnerLLP  = 5; # Officers.PartnerLLP1     - PartnerLLP20 -> Officers.ChiefPartnerLLP (422)

# Inner indices for $DiMesA, array of Dimension Member Info by DiMeId -> [DimId, DiMeBits, PropSet, ETypeList, MuxList, SumList]
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
$BroSumTypesGA  = [DT_Integer, DT_Money, DT_Decimal, DT_Share]; # Data Types for which Bro summing is to be performed
#$BroSumTypesGA = [2,3,4,8];

# Dims to exclude from Summing Bros
$SummingBroExclDimsChrsGA = ['X', 'Y', 'Z']; # DimId_Currencies, DimId_Exchanges, DimId_Languages or 40, 41, 42
# Dims to exclude from Non-Summing Bros
$NonSummingBroExclDimsGA = [DimId_Restated];

#############
# Functions #
#############

function BroTypeStr($bits) {
  $type = ($bits&BroB_Set) ? 'Set' : 'Ele';
  if ($bits&BroB_Slave)  $type .= ' Slave';
  if ($bits&BroB_Master) $type .= ' Master';
  return $type;
}

function DataTypeStr($typeN) {
  static $types = ['None', 'String', 'Integer', 'Money', 'Decimal', 'Date', 'Boolean', 'Enum', 'Share', 'PerShare', 'Percent'];
  return $types[(int)$typeN];
}

function SumUpStr($sumUp) {
  static $types = ['', ' +', 'No', 'NA']; # space before + re importing into OO calc
  return $types[(int)$sumUp];
}

/* function ContextStr($enum) {
  static $cons = ['None', 'Period', 'Officer', 'AllOfficers', 'TPA', 'RegOffice', 'UkCountry'];
  return $cons[(int)$enum];
} */

function AccountTypeStr($enum) {
  static $cons = ['', 'BS', 'CF', 'PL', 'DetailedPL', 'Historical PL', 'STRGL', 'Notes', 'Info', 'Other'];
  return $cons[(int)$enum];
}

function TUCNStr($enum) {
  static $cons = ' OMU';
  return $cons[(int)$enum];
}

