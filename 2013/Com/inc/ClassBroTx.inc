<?php /* Copyright 2011-2013 Braiins Ltd

/inc/ClassBro.inc

Class Bro and Helper Functions for Braiins Bro ops

Implements Classes Bro, BroDat and BroTupDat with Bro being the main one.

History:
25.10.12 Started
10.11.12 Stabilised
23.11.12 BroTupDat fully implemented
20.12.12 Finally passes tests...
26.12.12 Added TupId and MaxInst members to Bro
24.01.13 Slave Filtering; Set Slave sum with no filtering != Master => error
18.02.13 BD Maps removed

ToDo
~~~~

Removed BD Map stuff
Handle StartEnd in Slaves
Handle M# DiMes in Slaves

Handle equivalent of following frpm old BrosImport when copying Masters to Tx Slaves
    # If Bro is not a Slave and is a Posting Bro (not RO) that is Tx based, check if if its Tx Hy element is a Hy subset of another Posting Bro with same TupId
    # $TxIdsA: [TxId => [i =>RowNum]] for Tx based Posting (not RO) Bros for use in checking hypercube subset use
    if (!$slave && $TxId && !($Bits&BroB_RO) && count($TxIdsA[$TxId])>1) { # $TxIdsA[$TxId] will have at least one entry, the one for this $TxId
      # Posting Bros have only one Hy
      $hyId = ChrToInt($FolioHys);
      foreach ($TxIdsA[$TxId] as $rowNum)
        if ($rowNum != $RowNum && $IbrosA[$rowNum]['TupId']==$TupId)
          foreach (ChrListToIntA($IbrosA[$rowNum]['FolioHys']) as $oHyId)
            if ($oHyId !== $hyId && IsHypercubeSubset($hyId, $oHyId)) # $oHyId = other HyId. == case excluded as that would have been checked re duplicate Hy.Tx.Tup
              BIError2("TxId $TxId Hy $hyId Posting (not RO) use is covered by Posting Bro {$IbrosA[$rowNum]['Name']} at row $rowNum with Hy $oHyId of which Hy $hyId is a subset");
    } # end TxId check



#############
# Class Bro #
#############

A Bro object holds all data for a given BroId i.e. the Base, Start (if applicable), and Dimension Member data.

All data is held in BroDat or BroTupDat objects keyed by BroDatKey where:

Case  BroDatKey          Descr
Start {INST,}1{,DiMeRef} Start Base (no DiMeRef) and DiMe value for a StartEnd Bro shown externally as {BroId}s or {BroId}s,DiMeRef e.g. 1234s or 1234s,145
End   {INST,}2{,DiMeRef} End or normal value for a Bro, Base (no DiMeRef) and DiMe value, shown externally as BroId or BrodId,DiMeRef e.g. 1234,145,185{,T.x}

where the 1,2 of the BroDatKey are the constants BroDatT_Start, BroDatT_End
INST is 2000 + the instance for a Tuple BroTupDat with an instance #. (DiMe sum, Intermediate DiMe and SumBase BroTupDats do not have an instance number.)
The 2000 ensures that an INST is > than any DiMeId, amd provides a fixed length INST (4 chars or 5 with ,)
 for Insts in the range of 1-7998 = 2001-9998 with 7999 -> 9999 reserved for 'all' in the RG.
e.g. 2001,2,75
     0123456
NB: If a Tuple Bro BroTupDat has no Inst e.g. for a Base BroDat no {INST,} is included.

Base Non-Tuple BroDatKeys are ints (1 or 2). The DiMe ones are always strings.
Base     Tuple BroDatKeys for instances are 6 characters long with one comma e.g. 2001,2  The DiMe ones are strings longer than 6 characters, with two or more commas.
Base     Tuple BroDatkeys wo  instances are ints as per Non-Tuple Brodats.

Constructor and __invoke()
--------------------------
BroO          Bro::constructor($broId)
BroDatO/false Bro::__invoke($broDatTypeOrBroDatKey, $dat, $srceN, $inst=0, $diMeIdOrDiMeIdsA=0, $op=BroAddDataOp_Unique) Function run when Bro object is called as a function = add data to Bro

Class Bro Public Member functions
---------------------------------
void            static SetErrorCallbackFn($cbFn)
void            static SetNewPostingDiMeBroDatCallbackFn($cbFn)
void            static SetIsPyaYearB($b)
void            static ResetChanges()
int             static Changes()

string          ErrorMsg()

0/BroB_Master   IsMaster()
0/BroB_Slave    IsSlave()
boolean         IsSetSlave()
boolean         IsEleSlave()
0/BroB_Ele      IsEle()
0/BroB_Summing  IsSumming()
boolean         IsStartEnd()
0/TuMeId        IsTuple()
boolean         IsMoney()
0/BroB_DE       IsDE()
0|BS_Dr|BS_Dr   Sign()           Returns 0|1|2 for undefined | BS_Dr | BS_Cr
String          PostTypeStr()
0/BroCB_Primary HasPrimary()     Returns non-zero (BroCB_Primary) if the Bro includes Primary data
0/BroCB_Pya     HasPya()         Returns non-zero (BroCB_Pya) if the Bro includes Pya data
boolean         HasPosting()     Returns true if the Bro includes Posting data and is not a Slave
int             DefaultDiMeId()  Returns the Bro's default mDiMeId to use when changing a Summing Bro Base BroDat to a DiMe BroDat = the Mandatory/Default DiMe if there is one, and first one of list if there is a list, o'wise DiMeId_Unallocated.
string          __toString()     Called by PHP to convert a Bro to string format in a string context e.g. echo
int             BroRef()         BroId
string          BroRefSrce()     BroName
string          BroRefFull()     "BroId BroName"
string          BroShortName()   Bro ShortName if defined
string          BroDatTypeStr($broDatType) to obtain a BroDatTypeStr to be i.e. before the BroDat has been constructed for error msg purposes.
string          FormattedDat($dat)   Returns $dat formatted
string          FormattedDatTd($dat) Returns $dat formatted as an html td
BroDatO/false   StartBase()      Returns the StartBase BroDatO or false if not defined
BroDatO/false   EndBase()        Returns the EndBase BroDatO or false if not defined

array           AllBroDatOs()            Returns [BroDatKey => BroDatO] for all the BroDat/BroTupDat objects or an empty array if none tho there will only be none if a Bro was created wo data and no add data call was made.
array           AllPrimaryDiMeBroDatAs() Returns [AmalBroDatKey => [0 => Bal, 1 => DiMeIdsA]] for all the Primary DiMe BroDats i.e. incl Start ones with Tuples amalgamated

The following *Dat*s Functions are applicale to Summing Bros only

array           StartPrimaryBroDatAs() Returns [AmalBroDatKey => [0 => Bal, 1 => SrceN]] for the Start Summing BroDats i.e. excluding the End, Base one(s) if there are DiMes or Tuple instances, non-Primary ones, and in a Pya year, all non-Pya BroDats.
                                       For Tuples, the Inst # is stripped from the key and same key results wo Inst are summed i.e. Tuples are amalgamated
array           EndPrimaryBroDatOs()   Returns [BroDatKey => BroDatO] for the End Primary BroDats
array           EndPrimaryBroDatAs()   Returns [AmalBroDatKey => Bal] for the End Summing BroDats i.e. excluding the Start ones, Base one(s) if there are DiMes or Tuple instances, non-Primary ones, and in a Pya year, all non-Pya BroDats.
                                       For Tuples, the Inst # is stripped from the key and same key results wo Inst are summed i.e. Tuples are amalgamated
array           SummingBroDatAs()      Returns [AmalBroDatKey => [0 => Bal, 1 => SrceN]] for the Summing BroDats i.e. excluding the Base one(s) if there are DiMes or Tuple instances, non-Primary ones, and in a Pya year, all non-Pya BroDats.
                                       Should only be called for a Non-StartEnd Summing Bro.
                                       For Tuples, the Inst # is stripped from the key and same key results wo Inst are summed i.e. Tuples are amalgamated
array           BroDatOsExclSumBase()  Returns [i => BroDatO] for BroDats excluding the Sum Base one(s) i.e. if this is a Summing Bro and there are DiMes or other Base Instances.
array           PostingBroDatOs()      Returns [BroDatKey => BroDatO] for the Posting BroDats. Any Summing Base one(s) are excluded as they are not Posting type.

BrO             Copy()                 Copies this Bro to a new Bro and returns the new BrO
BrO             CopyToSlave($slaveBrO) Copies this Bro which should be a Master Bro to $slaveBroO. Returns $slaveBrO.
void            DiMeSumming($stdYearBrO=null) Performs Intermediate DiMe, DiMe Summing, and Pya DiMe Summing, inserting PyaOA BroDats if necessary using $stdYearBrO, with $stdYearBrO passed as false if inserting PyaOA BroDats is to be skipped as for a Bro being restored from string
string          Stringify()            Returns the string form of the Bro for DB storage. Gives a much shorter string than serialize or json_encode

################
# Class BroDat # Bro Data
################

BroDaatO  BroDat::constructor($dadBrO, $broDatType, $dat, $srceN, $diMeIdsA, $bits)

Class BroDat Public Member functions
------------------------------------
0/BroCB_Primary IsPrimary()     Returns non-zero (BroCB_Primary) if the BroDat is a Primary one
0/BroCB_Posting IsPosting()     Returns non-zero (BroCB_Posting) if the BroDat is a Posting one
0/BroCB_Pya     IsPya()         Returns non-zero (BroCB_Pya) if the BroDat is a Pya one which means that it includes DiMeId = 75 or 76, DiMeId_PyaAcctPolicyIncr or DiMeId_PyaMaterialErrIncr
0/BroCB_PyaOAA  IsPyaOAA()      Returns non-zero (BroCB_PyaOAA) if the Original Amount 77 DiMeId_PyaOriginalAmount BroDat has been Added, corresponding to this Pya (BroCB_Pya) BroDat. Set on same BroDat as Pya.
0/BroCB_PyaOA   IsPyaOA()       Returns non-zero (BroCB_PyaOA) if the Brodat is an Original Amount 77 DiMeId_PyaOriginalAmount one.
boolean         IsEnd()
boolean         IsStart()
boolean         IsSumBase()
boolean         IsEitherDiMe()
int/string      BroRef()
string          BroRefSrce()
string          BroRefFull()
string          FormattedDat()   Returns formatted Dat
string          FormattedDatTd() Returns BroDat's Dat as a formatted td html
string          Source($srceN=false)
string          BroDatTypeStr()  Returns Returns one of 'Start', 'End', prefixed by 'Tuple ' if object is a BroTupDat.
string          Stringify()      Returns string for a BroDat in the form used for DB storage:
boolean         IsEqual($datO)   Returns boolean for this BroDat == $datO

###################
# Class BroTupDat # Bro Data for a Tuple Bro
###################

BroTupDatO BroTupDat::constructor($dadBrO, $broDatType, $dat, $srceN, $inst, $diMeIdsA, $bits)

Class BroTupDat Public Member functions
---------------------------------------
string  BroRef()
string  BroRefSrce()
string  Stringify()     Returns string for a BroTupDat in the form used for DB storage
boolean IsEqual($datO)  Returns boolean for this BroTupDat == $datO

####################
# Helper Functions #
####################

Bro        NewBroFromString($broId, $broStr)   Returns a Bro object created from a Bro string

int/string BuildBroDatKey($broDatType, $inst, $diMeIdsA=0) Returns a BroDatKey for $broDatType, $inst, $diMeIdsA
void       AdjustAmalBroDatKey(&$broDatKey, $adj)    Called to adjust the Bro BroDatType part of a Bro BroDatKey by $adj (+ or -ve)
[]         UnpackBroDatKey($broDatKey)               Returns [$broDatType, $diMeIdsA, $inst] for use: list($broDatType, $diMeIdsA, $inst) = UnpackBroDatKey($broDatKey);
[]         UnpackAmalBroDatKey($amalBroDatKey)       Returns [$broDatType, $diMeIdsA] for use: list($broDatType, $diMeIdsA) = UnpackAmalBroDatKey($amalBroDatKey)
boolean    IsEndAmalBroDatKey($broDatKey)

string     BroName($broId)            Requires $BroNamesA to have been loaded
string     BroShortName($broId)       Requires $BroShortNamesA to have been loaded
string     DimDiMeShortName($diMeId)  Requires $PMemNamesA to have been loaded

string     DiMeRefSrce($diMeIdsA)     Returns source reference for the DiMe defined by $diMeIdsA

boolean    IsPrimary($srceN)

string     BroDatTypeStr($broDatType, $tuple)  Returns one of 'Start', 'End'  prefixed by 'Tuple ' if object is a BroTupDat.

####################

#############
# Bro class #
#############

*/

# Bro class constants
# BroDat BroDatType and first char of BroDatKey, as they are for BroDatKey sorting purposes.
const BroDatT_Start = 1; # Start BroDat
const BroDatT_End   = 2; # End or Normal BroDat
const Tu_MaxInst = 7998; # Max Tuple Inst.
const Tu_AllInst = 7999; # Tuple Inst for T.all as used by RG

# Bits
const BroCB_Primary = 1; # For BroDat Bits means BroDatO is a Primary BroDat;  For Bro Bits means Bro includes Primary BroDats
const BroCB_Posting = 2; # For BroDat Bits means BroDatO is a Posting Brodat;  For Bro Bits means Bro includes Posting BroDats
const BroCB_Pya     = 4; # For BroDat Bits means BroDatO is a Pya Posting Brodat; For Bro Bits means Bro includes Pya BroDats. (Includes DiMeId = 75 or 76, DiMeId_PyaAcctPolicyIncr or DiMeId_PyaMaterialErrIncr)
const BroCB_PyaOA   = 8; # For BroDat Bits means is a an Original Amount (77 or DiMeId_PyaOriginalAmount) BroDat one. Not used at Bro level. (Can have multiple 77s in a Bro)
const BroCB_PyaOAA  =16; # Used with a Pya BroDat to mean that the corresponding PyaOA (Original Amount or 77 DiMeId_PyaOriginalAmount) BroDat has been Added. Not used at Bro level.

# Source Enums
const BroDatSrce_P  =  1; # * Posting
const BroDatSrce_PE =  2; # * Prior year End
const BroDatSrce_SE =  3; # * SumEnd
const BroDatSrce_S  =  4; # * Summed
const BroDatSrce_R  =  5; # * Restated - the Original Amount DiMe when included in the BaseSum of the Bro e.g. when Base only restated
const BroDatSrce_r  =  6; # * Restated - the Original Amount DiMe when not included in the BaseSum of the Bro e.g. when other DiMes are in use -> Base sum, and this Original Amount is for info purposes only.
const BroDatSrce_e  =  7; # * dErived or dEduced
const BroDatSrce_b  =  8; #   BaseSum = sum of the Primary DiMes
const BroDatSrce_t  =  9; #   Base Tuple
const BroDatSrce_d  = 10; #   DiMe summing
const BroDatSrce_i  = 11; #   Intermediate DiMe which can be stand alone or impact a primary summing BroDat -> different view bal from primary bal
                          # |- * = included in stringing

# AddData Op Enums
const BroAddDataOp_Unique  = 1; # default -> Duplicate posting error if same add repeated
const BroAddDataOp_Replace = 2; # Replace the previous Dat if same add repeated as in summing iterations
const BroAddDataOp_Sum     = 3; # Add to the  previous Dat2 if same add repeated as in creating intermediate DiMes

#equire Com_Str_Tx.'BroInfoA.inc';          # $BroInfoA
#equire Com_Str_Tx.'BroNamesA.inc';         # $BroNamesA
#equire Com_Str_Tx.'BroShortNamesA.inc';    # $BroShortNamesA
#equire Com_Str_Tx.'DiMeTargetsA.inc';      # $DiMeTargetsA $RestatedDiMeTargetsA
#equire Com_Str_Tx.'TuMesA.inc';            # $TuMesA
require Com_Str.'PMemsA.inc';      # $PMemsA ($DiMesA)
require Com_Str.'PMemNamesA.inc';  # $PMemNamesA ($DiMeNamesA)

#######
# Bro #
#######

class Bro {
  private static $Changes = 0, $ErrorMsg, $ErrorCallbackFn, $NewPostingDiMeBroDatCallbackFn,
    $IsPyaYearB; # Set via SetIsPyaYearB($b) to indicate that current Bros being processed are for a Pya year if tru, or not if false. Affects summung via Bro::SummingBroDatAs()

  # None of these members are encoded into a BroString - all is recreated by NewBroFromString()
  public $BroId, $InfoA, $InfoBits, $DataTypeN, $TuMeId, $TupId,
    $MaxInst; # The max Start or End Inst in use for a Tuple Bro. Separate Start and End Max Insts are not kept.

  private $Bits,
    $NumDiMesA,  # count of DiMe BroDats/BroTupDats
    $BaseDatOsA, # the Base BroDats/BroTupDats
    $BroDatOsA;  # [BroDatKey -> BroDatO/BroTupDatO] always defined even if just []

  # Bros::Set statics
  public static function SetErrorCallbackFn($cbFn) { self::$ErrorCallbackFn = $cbFn; }
  public static function SetNewPostingDiMeBroDatCallbackFn($cbFn) { self::$NewPostingDiMeBroDatCallbackFn = $cbFn; }
  public static function SetIsPyaYearB($b) { self::$IsPyaYearB = $b; }

  # Bro:: re Changes for summing iteration testing
  public static function ResetChanges()   { self::$Changes = 0; }
  public static function Changes() { return self::$Changes; }

  # Bro::Error()

  private function Error($msg, $argsA) {
    self::$ErrorMsg = $msg = vsprintf($msg, is_array($argsA) ? $argsA : [$argsA]);
    if ($cbFn = self::$ErrorCallbackFn) $cbFn($msg); # need to use var or the fn call fails with Notice: Undefined variable: ErrorCallbackFn and  Fatal error: Function name must be a string
    return false;
  }
  public function ErrorMsg() { return self::$ErrorMsg;}

  # Bro::constructor($broId)
  # ~~~~~~~~~~~~~~~~~~~~~~~~
  # broId is always required
  public function __construct($broId) {
    global $BroInfoA, $TuMesA;
    # DebugMsg("Bro::constructor broId=$broId, Changes before this=".self::$Changes);
    ++self::$Changes;
    $this->BroId     = $broId; # expected to be int
    $this->Bits      = $this->MaxInst = 0;
    $this->NumDiMesA = $this->BaseDatOsA = [0, 0, 0];
    $this->BroDatOsA   = [];
    $broA = $BroInfoA[$broId];
    $this->InfoBits  = $broA[BroI_Bits];
    $this->DataTypeN = $broA[BroI_DataTypeN];
    if ($this->TuMeId = $broA[BroI_TuMeId])
      $this->TupId = $TuMesA[$this->TuMeId][TuMeI_TupId];
    unset($broA[BroI_Bits]); # unset the ones transferred to member properties for speed/frequency of use
    unset($broA[BroI_DataTypeN]);
    unset($broA[BroI_TuMeId]);
    $this->InfoA = $broA;
  }

  # Bro::__invoke($broDatTypeOrBroDatKey, $dat, $srceN, $inst=0, $diMeIdOrDiMeIdsA=0, $op=BroAddDataOp_Unique) Function run when Bro object is called as a function = add data to Bro
  # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  # Adds data (post, entry, DiMe, No DiMe) to the Bro, according to $op
  # Returns BroDatO/BroTupDatO if OK, false if an error has occurred.
  # $broDatTypeOrBroDatKey, $dat and $srceN are always required
  # $inst is required if the Bro is a Tuple aand this is a Primary add
  # $diMeIdOrDiMeIdsA Can be 0, an int non-zero DiMeId, or an array of DiMeIds
  # Sums to the Base in case of a DiMe or Tuple instance being added to a summing Bro.
  #
  # Ca <------------------------------ Passed ---------------------> + $scrceN always
  # se $broDatTypeOrBroDatKey  $diMeIdOrDiMeIdsA              $inst
  #  1 int BroDatT_Start       0 or int DiMeId or [DiMeIdsA]  # or 0
  #  2 int BroDatT_End         "                              "
  #  3 str BroDatT_*,DiMeRef   0                              0      Only used with summing ops where Tuple info has been amalgamated and different SrceN types are not expected

  public function __invoke($broDatTypeOrBroDatKey, $dat, $srceN, $inst=0, $diMeIdOrDiMeIdsA=0, $op=BroAddDataOp_Unique) {
    # DebugMsg("Bro::add data to Bro $this->BroId, broDatTypeOrBroDatKey=$broDatTypeOrBroDatKey, dat=$dat, srceN=$srceN, inst=$inst, diMeIdOrDiMeIdsA=".ArrayOrIntToStr($diMeIdOrDiMeIdsA).", Op=$op; Changes before=".self::$Changes);
    if ($dat && !$srceN) return $this->Error('srceN not set when dat=|%s| for add dadat call to Bro %s', [$dat, $this->BroId]);

    # Derive $broDatType and $broDatKey plus $diMeIdsA if required.
    # $broDatKey is set by the BroDat/BroTupDat constrctor but need it here too to check for duplicates.
    if (is_int($broDatTypeOrBroDatKey)) {
      # Cases 1 - 2
      # Ca <------------------------------ Passed ---------------------> + $scrceN always
      # se $broDatTypeOrBroDatKey  $diMeIdOrDiMeIdsA              $inst
      #  1 int BroDatT_Start       0 or int DiMeId or [DiMeIdsA]  # or 0
      #  2 int BroDatT_End         "                              "
      $broDatType = $broDatTypeOrBroDatKey;
      if (!$diMeIdOrDiMeIdsA)
        $diMeIdsA = 0;
      else if (is_int($diMeIdOrDiMeIdsA))
        $diMeIdsA = [$diMeIdOrDiMeIdsA];
      else if (is_array($diMeIdOrDiMeIdsA))
        $diMeIdsA = $diMeIdOrDiMeIdsA;
      else
        return $this->Error('Add data diMeIdOrDiMeIdsA argument %s invalid', $diMeIdOrDiMeIdsA);
    }else if (is_string($broDatTypeOrBroDatKey)) {
      # Case 3
      # Ca <------------------------------ Passed ---------------------> + $scrceN always
      # se $broDatTypeOrBroDatKey  $diMeIdOrDiMeIdsA              $inst
      #  3 str BroDatT_*,DiMeRef   0                              0      Only used with summing ops where Tuple info has been amalgamated and different SrceN types are not expected
      $broDatType = (int)$broDatTypeOrBroDatKey;
      $diMeIdsA = CsListToIntA(substr($broDatTypeOrBroDatKey, 2));
    }else
      return $this->Error('Add data broDatTypeOrBroDatKey argument %s invalid', $broDatTypeOrBroDatKey);

    if ($this->IsTuple()) {
      if (!$inst && IsPrimary($srceN)) return $this->Error('No instance number for a primary add data call to Tuple Bro %s', $this->BroId);
      $tupleInstB = $inst; # not set for non inst case
    }else{
      if ($inst) return $this->Error('Instance number %s passed for an add data call to Non-Tuple Bro %s', [$inst, $this->BroId]);
      $tupleInstB = 0;
    }

    $broDatKey = BuildBroDatKey($broDatType, $inst, $diMeIdsA);
    $baseDatO  = $this->BaseDatOsA[$broDatType]; # 0 until Base has been created

    if (!$diMeIdsA) {
      ############
      # Base Add #
      ############

      if (!$baseDatO)
        # Base Add: No Base yet, so add as Base
        return $this->NewBaseBroDat($broDatType, $dat, $srceN, $inst); # NewBaseBroDat($broDatType, $dat=0, $srceN=0, $inst=0)

      # Base Add: With Base. Cases are different for Tuple Inst vs Non-Tuple Inst
      if ($tupleInstB) {
        # Base Add: With Base, Tuple Inst cases:
        # a. Tuple Base same inst that is being replaced
        # b. Tuple Base diff inst -> new BroTupDat
        # Otherwise it is a duplicate add or arg error
        if (isset($this->BroDatOsA[$broDatKey])) {
          # Base Add: With Base, Tuple Inst, BroTupDat obj exits already = duplicate inst
          $datO = $this->BroDatOsA[$broDatKey];
          switch ($op) {
            case BroAddDataOp_Unique: return $this->Error('Duplicate posting to %s', $datO->BroRefFull()); # default -> Duplicate posting error if same add repeated
            case BroAddDataOp_Replace: # Replace the previous Dat if same add repeated as in summing iterations
              # Base Add: a. Tuple Base same inst that is being replaced
              if ($datO->SrceN !== $srceN) {
                # Allow if previous was PE and this is Sum re StartEnd Sets e.g. Tx542
                if ($datO->SrceN === BroDatSrce_PE && $srceN === BroDatSrce_S) {
                  ++self::$Changes;
                  $datO->SrceN = $srceN;
                }else
                  return $this->Error('Replace add data call for %s has different source (%s) from the previous one (%s)', [$datO->BroRefFull(), $datO->Source($srceN), $datO->Source()]);
              }
              if ($dat !== $datO->Dat) {
                # DebugMsg("Tuple Base add change ".$datO->BroRefFull()." from $datO->Dat to $dat");
                # changed
                ++self::$Changes;
                if ($datO->IsPrimary() && $this->IsSumming())
                  $baseDatO->Dat += $dat - $datO->Dat; # adjust base if Primary Summing
                $datO->Dat = $dat;
              }
              return $datO;
          }
          return $this->Error('Invalid Bro add data Op argument %s ', $op);

        }
        # Base Add: b. Tuple Base diff inst -> new BroTupDat
        $datO = $this->NewBroDat($broDatType, $dat, $srceN, $inst); # NewBroDat($broDatType, $dat, $srceN, $inst, $diMeIdsA=0)
        if ($datO->IsPrimary() && $this->IsSumming()) {
          if ($baseDatO->Inst) {
            # Existing Tuple Base for Summing Bro is not a SumBase one, for which Inst=0, so add a SumBase
            # DebugMsg("Adding a new Tuple Base BroTupDat, different Inst => Add a Tuple SumBase for $this->BroId");
            $baseDatO = $this->NewBaseBroDat($broDatType, $baseDatO->Dat, BroDatSrce_b, 0); # NewBaseBroDat($broDatType, $dat=0, $srceN=0, $inst=0)
          }
          $baseDatO->Dat += $dat;
        }
        return $datO;
        # End of Tuple Inst Base Add with Base already
      }

      # Base Add: With Base, Non-Tuple Inst cases:
      # a. Bro is a Summing Bro with Base and DiMes added then this No DiMe Base Add has come along, so convert this add to a DiMe one
      # b. Base is being replaced
      # Otherwise it is a duplicate add or arg error
      if ($this->IsSumming() && $this->NumDiMesA[$broDatType]) {
        # Base Add: a. With Base, Non-Tuple Inst, Summing, Has DiMes, so convert this add to a DiMe one
        # DebugMsg("Converting Non-Tuple Instant $dat post to $this->BroId to a DiMe posting to ".$this->DefaultDiMeId()." as the Bro already has other DiMe postings");
        return $this($broDatType, $dat, $srceN, $inst, $this->DefaultDiMeId(), $op);
      }

      switch ($op) {
        case BroAddDataOp_Unique: return $this->Error('Duplicate posting to %s', $datO->BroRefFull()); # default -> Duplicate posting error if same add repeated

        case BroAddDataOp_Replace: # Replace the previous Dat if same add repeated as in summing iterations
          # Base Add: b. With Base, Non-Tuple Inst, Base is being replaced
          if ($baseDatO->SrceN !== $srceN) {
            # Allow if previous was PE and this is Sum re StartEnd Sets e.g. Tx542
            if ($baseDatO->SrceN === BroDatSrce_PE && $srceN === BroDatSrce_S) {
              ++self::$Changes;
              $baseDatO->SrceN = $srceN;
            }else
              return $this->Error('Replace add data call for %s has different source (%s) from the previous one (%s)', [$this->BroRefFull(), $datO->Source($srceN), $baseDatO->Source()]);
          }
          if ($dat !== $baseDatO->Dat) {
            # changed
            ++self::$Changes;
            $baseDatO->Dat = $dat;
          }
          return $baseDatO;

      }
      return $this->Error('Invalid Bro add data Op argument %s ', $op);
    } # end of Base Add

    ############
    # DiMe Add #  Start or End, Tuple Inst, or Non-Tuple Inst DiMe Add. Could be a Pya one too. Have $diMeIdsA
    ############

    if (isset($this->BroDatOsA[$broDatKey])) {
      # DiMe Add: BroDat/BroTupDat obj exits already. Bases will exist.
      $datO = $this->BroDatOsA[$broDatKey];
      switch ($op) {
        case BroAddDataOp_Unique: return $this->Error('Duplicate posting to %s', $datO->BroRefFull()); # default -> Duplicate posting error if same add repeated

        case BroAddDataOp_Replace: # DiMe Add: Replace the previous Dat if same add repeated as in summing iterations
          if ($datO->SrceN !== $srceN) {
            # Allow if previous was PE and this is Sum re StartEnd Sets e.g. Tx542
            if ($datO->SrceN === BroDatSrce_PE && $srceN === BroDatSrce_S) {
              ++self::$Changes;
              $datO->SrceN = $srceN;
            }else
              return $this->Error('Replace add data call for %s has different source (%s) from the previous one (%s)', [$datO->BroRefFull(), $datO->Source($srceN), $datO->Source()]);
          }
          if ($dat !== $datO->Dat) {
            # DebugMsg("Replace change ".$datO->BroRefFull()." from $datO->Dat to $dat");
            # changed
            ++self::$Changes;
            $diff = $dat - $datO->Dat;
            $datO->Dat = $dat;
            if ($datO->IsPrimary() && $this->IsSumming()) {
              # Set Dat2 also for a Primary Summing Replace
              # DebugMsg($datO->BroRef()." setting Dat2 to $dat");
              $datO->Dat2 = $dat;
              # Update the base BroDats also
              if ($tupleInstB) {
                # 6136,211,T.1  update 6136,211 t and 6136,T.1 t and 6136 b
                # Strip the instance # from the key for 6136,211 t case
                $broDatKey = substr($broDatKey, 5);
                $this->BroDatOsA[$broDatKey]->Dat += $diff; # 6136,211
                # Now 6136,T.1 t
                $broDatKey = BuildBroDatKey($broDatType, $inst);
                $this->BroDatOsA[$broDatKey]->Dat += $diff; # 6136,T.1 t
              }
              # Update the Base whose key is just the BroDatType
              $this->BroDatOsA[$broDatType]->Dat += $diff;
            }
          }
          return $datO;

        case BroAddDataOp_Sum: # Add to the  previous Dat2 if same add repeated as in creating intermediate DiMes, unless is an i Brodat in which case add to Dat
          ++self::$Changes;
          $datO->SrceN === BroDatSrce_i ? $datO->Dat += $dat : $datO->Dat2 += $dat;
          return $datO;

      }
      return $this->Error('Invalid Bro add data Op argument %s ', $op);
    } # DiMe Add: End of BroDat/BroTupDat obj exits already

    # DiMe Add: New DiMe, Start or End, Tuple Inst, or Non-Tuple Inst BroDat/BroTupDat Add. Have $diMeIdsA
    $datO = $this->NewBroDat($broDatType, $dat, $srceN, $inst, $diMeIdsA); # NewBroDat($broDatType, $dat, $srceN, $inst, $diMeIdsA=0)

    if ($datO->IsPrimary() && $this->IsSumming()) {
      # DiMe Add: DiMe Primary Summing so Update Base
      if (!$baseDatO) {
        # DiMe Add: DiMe Primary Summing, No Base yet so create a zero SumBase
        # DebugMsg("No Base yet so add a zero SumBase");
        $baseDatO = $this->NewBaseBroDat($broDatType, 0, BroDatSrce_b); # NewBaseBroDat($broDatType, $dat=0, $srceN=0, $inst=0)
      }else if ($baseDatO->SrceN !== BroDatSrce_b) {
        # DiMe Add: New Primary Summing DiMe, Base not a SumBase
        if (!$baseDatO->IsPrimary()) return $this->Error('DiMe post %s to Summing Bro but Bro had a prior No DiMe posting which was not a Primary one.', $datO->BroRefFull());
        # DiMe Add: New Primary Summing DiMe, Primary Base not a SumBase
        if ($tupleInstB) {
          # DiMe Add: New Primary Summing DiMe, Tuple Inst, Primary Base not a SumBase - must be a Tuple Inst
          # .. add a 0 Inst SumBase one as the new SumBase
          # DebugMsg("Adding a Primary Summing Tuple Inst DiMe => add a Bro $this->BroId Inst 0 SumBase as there is already a $this->BroId,T.$baseDatO->Inst Base BroTupDat");
          $baseDatO = $this->NewBaseBroDat($broDatType, $baseDatO->Dat, BroDatSrce_b, 0); # NewBaseBroDat($broDatType, $dat=0, $srceN=0, $inst=0)
        }else{
          # DiMe Add: New Primary Summing DiMe, Non-Tuple Inst, Primary Base not a SumBase - must be a Non-Tuple Inst Base
          # .. add a DiMe BroDat for the previous Base BroDat using the default DiMe unless this is a Pya post in which case use OA in R (primary) form
          if ($datO->IsPya()) {
            $srceN  = BroDatSrce_R;
            $diMeId = DiMeId_PyaOriginalAmount; # 77
            # DebugMsg("Adding a Primary Restated Non-Tuple Inst Original Amount DiMe with previous Base so add $this->BroId,$diMeId BroDat for the previous Base Dat and change the previous Base to a SumBase");
            $datO->Bits |= BroCB_PyaOAA; # to record that the appropriate 77 DiMeId_PyaOriginalAmount BroDat has been added
          }else{
            $srceN  = $baseDatO->SrceN;
            $diMeId = $this->DefaultDiMeId();
            # DebugMsg("Adding a Primary Summing Non-Tuple DiMe with previous Base so add $this->BroId,$diMeId (default DiMe) BroDat for the previous Base Dat and change the previous Base to a SumBase");
          }
          $this->NewBroDat($broDatType, $baseDatO->Dat, $srceN, 0, [$diMeId]); # NewBroDat($broDatType, $dat, $srceN, $inst, $diMeIdsA=0)
          $baseDatO->SrceN = BroDatSrce_b;
          $baseDatO->Bits  = 0;
        }
      }
      $baseDatO->Dat += $dat;
      if ($tupleInstB) {
        # DiMe Add: DiMe Primary Summing Tuple Inst
        # Process Base tuple(s): add to it (Dat2) if it exists as other than a t type, or create it o'wise
        # DebugMsg("Process $this->BroId Base Tuple Inst");
        # 6136,211,T.1     update/create 6136,211 t     and 6136,T.1 t
        # 6136,211,75,T.1  update/create 6136,211,T.1 t and 6136,75 t and 6136,T.1 t
        # 6376,75,T.21     update/create 6376,75 t      and 6376,T.21 t
        if ($datO->IsPya()) {
          # Pya BroDats are not processed by Intermediate DiMe Summing. Equivalent 't' processing is done here.
          $pyaDiMeId = array_pop($diMeIdsA); # pop off the 75/76
          if (count($diMeIdsA)) {
            # 6136,211,75,T.1  update/create 6136,211,T.1 t
            $broDatKey  = BuildBroDatKey($broDatType, $inst, $diMeIdsA); # 211,T.1
            # Add the Pya (75) value to the DiMe Tuple Inst e.g. 6136,211,T.1
            $broDatO = $this->BroDatOsA[$broDatKey];
            $broDatO->SrceN === BroDatSrce_t ? $broDatO->Dat += $datO->Dat : $broDatO->Dat2 += $datO->Dat;
            # DebugMsg("Adding Pya value to the DiMe Tuple Inst $this->BroId,$broDatKey, Dat=$broDatO->Dat, Dat2=$broDatO->Dat2");
          }
          # 6136,211,75,T.1  update/create 6136,75 t
          # 6376,75,T.21     update/create 6376,75 t
          $broDatKey = BuildBroDatKey($broDatType, 0, [$pyaDiMeId]); # 6136,75
          if (isset($this->BroDatOsA[$broDatKey])) {
            $broDatO = $this->BroDatOsA[$broDatKey];
            $broDatO->SrceN === BroDatSrce_t ? $broDatO->Dat += $dat : $broDatO->Dat2 += $dat; # 6136,211
            # DebugMsg("$this->BroId Base Tuple Inst broDatKey=$broDatKey, Dat=$broDatO->Dat, Dat2=$broDatO->Dat2");
          }else # add 6136,75 t
            $this->NewBroDat($broDatType, $dat, BroDatSrce_t, 0, [$pyaDiMeId]); # NewBroDat($broDatType, $dat, $srceN, $inst, $diMeIdsA=0)
          # continue below with $diMeIdsA less its 75
        }
        if (count($diMeIdsA)) {
          # 6136,211,T.1
          $broDatKey = BuildBroDatKey($broDatType, 0, $diMeIdsA); # 6136,211
          if (isset($this->BroDatOsA[$broDatKey])) {
            $broDatO = $this->BroDatOsA[$broDatKey];
            $broDatO->SrceN === BroDatSrce_t ? $broDatO->Dat += $dat : $broDatO->Dat2 += $dat; # 6136,211
            # DebugMsg("$this->BroId Base Tuple Inst broDatKey=$broDatKey, Dat=$broDatO->Dat, Dat2=$broDatO->Dat2");
          }else # add 6136,211 t
            $this->NewBroDat($broDatType, $dat, BroDatSrce_t, 0, $diMeIdsA); # NewBroDat($broDatType, $dat, $srceN, $inst, $diMeIdsA=0)
        }
        # Now 6136,T.1 t
        $broDatKey = BuildBroDatKey($broDatType, $inst);
        if (isset($this->BroDatOsA[$broDatKey])) {
          $broDatO = $this->BroDatOsA[$broDatKey];
          $broDatO->SrceN === BroDatSrce_t ? $broDatO->Dat += $dat : $broDatO->Dat2 += $dat; # 6136,T.1 t
          # DebugMsg("Adding $dat to Base Tuple $this->BroId,$broDatKey, Dat=$broDatO->Dat, Dat2=$broDatO->Dat2");
        }else{ # add 6136,T.1 t
          # DebugMsg("Creating Base Tuple $this->BroId,$broDatKey = $dat");
          $this->NewBroDat($broDatType, $dat, BroDatSrce_t, $inst); # NewBroDat($broDatType, $dat, $srceN, $inst, $diMeIdsA=0)
        }
      }
    }
    return $datO;
    # end of DiMe Add
  }

  # Bro::New*() private fns called only from __invoke() to add data to Bro
  # Bro::NewBaseBroDat() Called from __invoke() to add a Base BroDat
  private function NewBaseBroDat($broDatType, $dat=0, $srceN=0, $inst=0) {
    return $this->BaseDatOsA[$broDatType] = $this->NewBroDat($broDatType, $dat, $srceN, $inst);
  }

  # Bro::NewBroDat() Called from __invoke() in DiMe case or via NewBaseBroDat() in the case of a Base BroDat
  private function NewBroDat($broDatType, $dat, $srceN, $inst, $diMeIdsA=0) {
    $datO = $this->IsTuple() ? new BroTupDat($this, $broDatType, $dat, $srceN, $inst, $diMeIdsA) : new BroDat($this, $broDatType, $dat, $srceN, $diMeIdsA);
    $this->BroDatOsA[$datO->BroDatKey] = $datO;
   #uksort($this->BroDatOsA, 'strnatcmp');
    ksort($this->BroDatOsA, SORT_NATURAL);
    $this->Bits |= $datO->Bits; # BroCB_Primary | BroCB_Posting
    if ($diMeIdsA) {
      ++$this->NumDiMesA[$broDatType];
     #if ($datO->Bits & BroCB_Posting) SetDimGroups($diMeIdsA);
      if (self::$NewPostingDiMeBroDatCallbackFn && ($datO->Bits & BroCB_Posting)) {
        $cbFn = self::$NewPostingDiMeBroDatCallbackFn;
        $cbFn($diMeIdsA);
      }
    }
    ++self::$Changes;
    return $datO;
  }

  # Bro:: Info/State fns
  public function IsMaster()    { return $this->InfoBits & BroB_Master; }
  public function IsSlave()     { return $this->InfoBits & BroB_Slave; }
  public function IsSetSlave()  { return ($this->InfoBits & BroB_Slave) && ($this->InfoBits & BroB_Set); }
  public function IsEleSlave()  { return ($this->InfoBits & BroB_Slave) && ($this->InfoBits & BroB_Ele); }
  public function IsEle()       { return $this->InfoBits & BroB_Ele; }    # not IsSet as php confuses this with isset
  public function IsSumming()   { return $this->InfoBits & BroB_Summing;}
  public function IsStartEnd()  { return $this->InfoA[BroI_PeriodSEN] >= BPT_InstSumEnd; }
  public function IsTuple()     { return $this->TuMeId; }
  public function IsMoney()     { return ($this->DataTypeN === DT_Money); }
  public function IsDE()        { return ($this->InfoBits & BroB_DE); }
  public function Sign()        { return $this->InfoA[BroI_SignN]; } # Returns 0|1|2 for undefined | BS_Dr | BS_Cr
  public function PostTypeStr() { return ($this->InfoBits & BroB_DE) ? 'DE' : 'Sch'; }
 #public function NumBroDats()    { return count($this->BroDatOsA); }          # Returns the number of BroDats
 #public function NumStartDiMes() { return $this->NumDiMesA[BroDatT_Start]; }  # Returns the number of BroDatT_Start DiMe BroDats = truthy re the Bro having Start DiMe BroDats == HasStartDiMes
 #public function NumEndDiMes()   { return $this->NumDiMesA[BroDatT_End]; }    # Returns the number of BroDatT_End   DiMe BroDats = truthy re the Bro having End   DiMe BroDats == HasEndDiMes
  public function HasPrimary()    { return $this->Bits & BroCB_Primary; }    # Returns non-zero (BroCB_Primary) if the Bro includes Primary data
  public function HasPya()        { return $this->Bits & BroCB_Pya; }        # Returns non-zero (BroCB_Pya) if the Bro includes Pya data
  public function HasPosting()    { return ($this->Bits & BroCB_Posting) && !$this->IsSlave(); } # Returns true if the Bro includes Posting data and is not a Slave


  # Bro::DefaultDiMeId() Returns the Bro's default mDiMeId to use when changing a Summing Bro Base BroDat to a DiMe BroDat = the Mandatory/Default DiMe if there is one, and first one of list if there is a list, o'wise DiMeId_Unallocated.
  public function DefaultDiMeId() {
    if (($broDiMesA = $this->InfoA[BroI_PMemDiMesA]) && ($broDiMesA[II_MandatsA ] || $broDiMesA[II_DefaultsA]))
      # Bro has Mandatory/Default DiMes
      return $broDiMesA[II_MandatsA ] ? $broDiMesA[II_MandatsA ][0] : $broDiMesA[II_DefaultsA][0]; # use first one in each case
    # else
    return DiMeId_Unallocated;
  }

  # Bro::__toString()
  public function __toString() {
    $s = "Bro $this->BroId PostType ".$this->PostTypeStr().($this->IsEle() ? ', Element' : ', Set');
    if ($this->IsSumming())   $s .= ', Summing';
    if ($this->IsStartEnd())  $s .= ', StartEnd';
    if ($this->IsTuple())     $s .= ", Tuple with Max Inst=$this->MaxInst";
    if ($this->IsMaster())    $s .= ', Master';
    if ($this->IsSlave())     $s .= ', Slave';
    if ($this->BaseDatOsA[BroDatT_End])  $s .= ', has Base';
    if ($this->BaseDatOsA[BroDatT_Start]) $s .= ', has Start Base';
    $s .= ', has '.NumPluralWord(count($this->BroDatOsA), 'BroDat');
    if ($this->NumDiMesA[BroDatT_End])   $s .= ', has '.NumPluralWord($this->NumDiMesA[BroDatT_End], 'DiMe');
    if ($this->NumDiMesA[BroDatT_Start]) $s .= ', has '.NumPluralWord($this->NumDiMesA[BroDatT_Start], 'Start DiMe');
    if ($this->HasPrimary())  $s .= ', includes Primary data';
    return $s;
  }

  # Bro::BroRef* and ShortName fns
  public function BroRef()       { return $this->BroId; }
  public function BroRefSrce()   { return BroName($this->BroId); }
  public function BroRefFull()   { return "$this->BroId ".BroName($this->BroId); }
  public function BroShortName() { return BroShortName($this->BroId); } # Bro ShortName if defined

  # Bro::BroDatTypeStr($broDatType) to obtain a BroDatTypeStr to be i.e. before the BroDat has been constructed for error msg purposes.
  public function BroDatTypeStr($broDatType) { return BroDatTypeStr($broDatType, $this->IsTuple()); }

  # Bro::FormattedDat($dat) Returns $dat formatted
  public function FormattedDat($dat) {
    if ($dat !== '')
      switch ($this->DataTypeN) {
        case DT_String: break;
        case DT_Decimal:
        case DT_PerShare:
        case DT_Percent: $dat = number_format((float)$dat/10000, 2); break;
        case DT_Date:    $dat = eeDtoStr($dat); break;
        case DT_Boolean: $dat = $dat ? 'true' : 'false'; break;
        case DT_Enum:    $dat = "enum $dat"; break;
        default:         $dat = number_format($dat); break;
      }
    return $dat;
  }

  # Bro::FormattedDatTd($dat) Returns $dat formatted as an html td
  public function FormattedDatTd($dat) {
    if ($dat === '') return '<td></td>';
    switch ($this->DataTypeN) {
      case DT_String:  return "<td class=l>$dat</td>";
      case DT_Decimal:
      case DT_PerShare:
      case DT_Percent: $dat = number_format((float)$dat/10000, 2); break;
      case DT_Date:    $dat = eeDtoStr($dat); break;
      case DT_Boolean: $dat = $dat ? 'true' : 'false'; break;
      case DT_Enum:    $dat = "enum $dat"; break;
      default:         $dat = number_format($dat); break;
    }
    return "<td class=r>$dat</td>";
  }

  # Bro::*Base() Returns the *Base DatO or false if not defined
  public function StartBase() { return $this->BaseDatOsA[BroDatT_Start] ? $this->BaseDatOsA[BroDatT_Start]->Dat : false; }
  public function EndBase()   { return $this->BaseDatOsA[BroDatT_End]   ? $this->BaseDatOsA[BroDatT_End]->Dat : false; }

  # Bro::AllBroDatOs() Returns [BroDatKey => BroDatO] for all the BroDat/BroTupDat objects or an empty array if none tho there will only be none if a Bro was created wo data and no add data call was made.
  public function AllBroDatOs() {
    return $this->BroDatOsA; # [BroDatKey -> BroDatO]
  }

  # Bro::AllPrimaryDiMeBroDatAs()
  # Returns [AmalBroDatKey => [0 => Bal, 1 => DiMeIdsA]] for all the Primary DiMe BroDats i.e. incl Start ones with Tuples amalgamated
  public function AllPrimaryDiMeBroDatAs() {
    $datOsA = [];
    # Thru all the BroDats, skipping Non-Primary and Non-DiMe ones
    foreach ($this->BroDatOsA as $broDatKey => $datO) {
      if ($datO->IsPrimary() && $datO->IsEitherDiMe())
        if ($datO->Inst) {
          # In Tuple Inst case strip the instance # from the key and sum for duplicate keys after the Instance # is removed
          # {INST,}2,DiMeRef 2000,2,DiMeRef
          #                  012345678
          $amalBroDatKey = substr($broDatKey, 5);
          if (isset($datOsA[$amalBroDatKey]))
            $datOsA[$amalBroDatKey][0] += $datO->Dat;
          else
            $datOsA[$amalBroDatKey] = [$datO->Dat, $datO->DiMeIdsA]; # [AmalBroDatKey => [0 => Bal, 1 => DiMeIdsA]]
        }else
          # In Non-Tuple case key no summing needed here.
          $datOsA[$broDatKey] = [$datO->Dat, $datO->DiMeIdsA]; # [AmalBroDatKey => [0 => Bal, 1 => DiMeIdsA]]
    }
    return $datOsA;
  }

  # Bro::PrimaryDiMeExclTuplePyaBroDatAs  ()
  # Returns [AmalBroDatKey => [0 => Bal, 1 => DiMeIdsA]] for all the Primary DiMe BroDats Excl Tuple Tpa ones i.e. incl Start ones with Tuples amalgamated
  public function PrimaryDiMeExclTuplePyaBroDatAs  () {
    $datOsA = [];
    # Thru all the BroDats, skipping Non-Primary and Non-DiMe ones
    foreach ($this->BroDatOsA as $broDatKey => $datO) {
      if ($datO->IsPrimary() && $datO->IsEitherDiMe())
        if ($datO->Inst) {
          if (!$datO->IsPya()) {
            # In Tuple Inst case strip the instance # from the key and sum for duplicate keys after the Instance # is removed
            # {INST,}2,DiMeRef 2000,2,DiMeRef
            #                  012345678
            $amalBroDatKey = substr($broDatKey, 5);
            if (isset($datOsA[$amalBroDatKey]))
              $datOsA[$amalBroDatKey][0] += $datO->Dat;
            else
              $datOsA[$amalBroDatKey] = [$datO->Dat, $datO->DiMeIdsA]; # [AmalBroDatKey => [0 => Bal, 1 => DiMeIdsA]]
          }
        }else
          # In Non-Tuple case key no summing needed here.
          $datOsA[$broDatKey] = [$datO->Dat, $datO->DiMeIdsA]; # [AmalBroDatKey => [0 => Bal, 1 => DiMeIdsA]]
    }
    return $datOsA;
  }

  # The following *DatOs Functions are applicale to Summing Bros only

  # Bro::StartPrimaryBroDatAs()()
  # Returns [AmalBroDatKey => [0 => Bal, 1 => SrceN]] for the Start Summing BroDats i.e. excluding the End, Base one(s) if there are DiMes or Tuple instances, non-Primary ones, and in a Pya year, all non-Pya BroDats.
  # For Tuples, the Inst # is stripped from the key and same key results wo Inst are summed.
  public function StartPrimaryBroDatAs() {
    $datAsA = [];
    # Thru all the BroDats, skipping End, SumBase, Non-Primary ones, and  everything not a Pya (75, 76) BroDat when in a Pya year
    foreach ($this->BroDatOsA as $broDatKey => $datO) {
      if ($datO->IsEnd() || !$datO->IsPrimary() || (self::$IsPyaYearB && !$datO->IsPya())) continue;
      if ($datO->Inst) {
        $amalBroDatKey = substr($broDatKey, 5);
        if (isset($datAsA[$amalBroDatKey]))
          $datAsA[$amalBroDatKey][0] += $datO->Dat;
        else
          $datAsA[$amalBroDatKey] = [$datO->Dat, $datO->SrceN]; # [AmalBroDatKey => [0 => Bal, 1 => SrceN]]
      }else
        $datAsA[$broDatKey] = [$datO->Dat, $datO->SrceN]; # [AmalBroDatKey => [0 => Bal, 1 => SrceN]]
    }
    return $datAsA;
  }

  # Bro::EndPrimaryBroDatOs() Returns [BroDatKey => BroDatO] for the End Primary BroDats
  public function EndPrimaryBroDatOs() {
    $datOsA = [];
    foreach ($this->BroDatOsA as $broDatKey => $datO)
      if ($datO->IsEnd() && $datO->IsPrimary())
        $datOsA[$broDatKey] = $datO;
    return $datOsA;
  }

  # Bro::EndPrimaryBroDatAs()
  # Returns [AmalBroDatKey => Bal] for the End Summing BroDats i.e. excluding the Start ones, Base one(s) if there are DiMes or Tuple instances, non-Primary ones, and in a Pya year, all the non-Pya BroDats.
  # For Tuples, the Inst # is stripped from the key and same key results wo Inst are summed.
  public function EndPrimaryBroDatAs() {
    $datAsA = [];
    # Thru all the BroDats, skipping Start, SumBase, Non-Primary ones, and  everything not a Pya (75, 76) BroDat when in a Pya year
    foreach ($this->BroDatOsA as $broDatKey => $datO) {
      if ($datO->IsStart() || !$datO->IsPrimary() || (self::$IsPyaYearB && !$datO->IsPya())) continue;
      if ($datO->Inst) {
        $amalBroDatKey = substr($broDatKey, 5);
        if (isset($datAsA[$amalBroDatKey]))
          $datAsA[$amalBroDatKey] += $datO->Dat;
        else
          $datAsA[$amalBroDatKey] = $datO->Dat; # [AmalBroDatKey => Bal]
      }else
        $datAsA[$broDatKey] = $datO->Dat; # [AmalBroDatKey => Bal]
    }
    return $datAsA;
  }

  # Bro::SummingBroDatAs()
  # Returns [AmalBroDatKey => [0 => Bal, 1 => SrceN]] for the Summing BroDats i.e. excluding the Base one(s) if there are DiMes or Tuple instances, non-Primary ones, and in a Pya year, all non-Pya BroDats.
  # Should only be called for a Non-StartEnd Summing Bro.
  # For Tuples, the Inst # is stripped from the key and same key results wo Inst are summed.
  public function SummingBroDatAs() {
    $datAsA = [];
    # Thru all the BroDats, skipping SumBase, Non-Primary ones, and  everything not a Pya (75, 76) BroDat when in a Pya year
    foreach ($this->BroDatOsA as $broDatKey => $datO) {
      if ($datO->IsSumBase() || !$datO->IsPrimary() || (self::$IsPyaYearB && !$datO->IsPya())) continue;
      if ($datO->Inst) {
        $amalBroDatKey = substr($broDatKey, 5);
        if (isset($datAsA[$amalBroDatKey]))
          $datAsA[$amalBroDatKey][0] += $datO->Dat;
        else
          $datAsA[$amalBroDatKey] = [$datO->Dat, $datO->SrceN]; # [AmalBroDatKey => [0 => Bal, 1 => SrceN]]
      }else
        $datAsA[$broDatKey] = [$datO->Dat, $datO->SrceN]; # [AmalBroDatKey => [0 => Bal, 1 => SrceN]]
    }
    return $datAsA;
  }

  # Bro::BroDatOsExclSumBase() Returns [i => BroDatO] for BroDats excluding the Sum Base one(s) i.e. if this is a Summing Bro and there are DiMes or other Base Instances.
  public function BroDatOsExclSumBase() {
    $datOsA = [];
    foreach ($this->BroDatOsA as $broDatKey => $datO)
      if (!$datO->IsSumBase())
        $datOsA[] = $datO; # Only return [] that is 'i' not $broDatKey
    return $datOsA;
  }

  # Bro::PostingBroDatOs() Returns [BroDatKey => BroDatO] for the Posting BroDats. Any Summing Base one(s) are excluded as they are not Posting type.
  public function PostingBroDatOs() {
    $datOsA = [];
    foreach ($this->BroDatOsA as $broDatKey => $datO)
      if ($datO->IsPosting())
        $datOsA[$broDatKey] = $datO;
    return $datOsA;
  }

  # Bro::Copy()  Copies this Bro to a new Bro and returns the new BrO. Use of php clone didn't work - too much recursion.
  public function Copy() {
    $tuple  = $this->IsTuple();
    $cpyBrO = new Bro($this->BroId);
    foreach ($this->BroDatOsA as $broDatKey => $datO) {
      $broDatType = $datO->BroDatType;
      $srceN      = $datO->SrceN;
      $cpyDatO = $tuple? new BroTupDat($cpyBrO, $broDatType, $datO->Dat, $srceN, $datO->Inst, $datO->DiMeIdsA) : new BroDat($cpyBrO, $broDatType, $datO->Dat, $srceN, $datO->DiMeIdsA);
      $cpyDatO->Bits = $datO->Bits;
      $cpyDatO->Dat2 = $datO->Dat2;
      if ($cpyDatO->BroDatKey !== $broDatKey) die("Die in Copy with cpyDatO->BroDatKey |$cpyDatO->BroDatKey| !== broDatKey |$broDatKey|");
      $cpyBrO->BroDatOsA[$broDatKey] = $cpyDatO;
      if (!$datO->DiMeIdsA && ($srceN === BroDatSrce_b || !$cpyBrO->BaseDatOsA[$broDatType]))
        $cpyBrO->BaseDatOsA[$broDatType] = $cpyDatO;
    }
    $cpyBrO->Bits      = $this->Bits;
    $cpyBrO->NumDiMesA = $this->NumDiMesA;
    ++self::$Changes;
    return $cpyBrO;
  }

  # Bro::CopyToSlave($slaveBrO)
  # Copies this Bro which should be a Master Bro to $slaveBroO. Is never called for Set Slaves.
  # Returns $slaveBro
  public function CopyToSlave($slaveBrO) {
    global $DiMesA;
    $tuple = $this->IsTuple();
    if ($filtering = ($slaveBrO->Bits & BroB_SFilter)) {
      # Slave Filtering applies.
      $slaveUsableDims = $slaveBrO->InfoA[BroI_BroUsablePropDims]; # 0 if no Usable Dims filtering
      if ($broDiMesA = $slaveBrO->InfoA[BroI_PMemDiMesA]) {
        $diMeExcludesA = $broDiMesA[II_ExcludesA];
        $diMeAllowsA   = $broDiMesA[II_AllowsA];
      }
    }
    foreach ($this->BroDatOsA as $broDatKey => $datO) {
      if ($filtering && $datO->DiMeIdsA)
        # Filtering applies and the BroDat has DiMes
        foreach ($datO->DiMeIdsA as $diMeId)
          if (($slaveUsableDims && !InChrList($DiMesA[$diMeId][PMemI_PropId], $slaveUsableDims)) || # UsableDims filtering
              ($broDiMesA && (($diMeExcludesA && in_array($diMeId, $diMeExcludesA)) || ($diMeAllowsA && !in_array($diMeId, $diMeAllowsA))))) # DiMes filtering
            continue 2;
      $broDatType = $datO->BroDatType;
      $srceN      = $datO->SrceN;
      if (isset($slaveBrO->BroDatOsA[$broDatKey])) {
        # BroDat exists already, update only if different
        $cpyDatO = $slaveBrO->BroDatOsA[$broDatKey];
        if (!$datO->IsEqual($cpyDatO)) {
          $cpyDatO->Bits  = $datO->Bits;
          $cpyDatO->Dat   = $datO->Dat;
          $cpyDatO->Dat2  = $datO->Dat2;
          $cpyDatO->SrceN = $srceN;
         #$cpyDatO->Inst  = $datO->Inst; Key wouldn't be the same if Inst was different
          ++self::$Changes;
        }
      }else{
        $cpyDatO = $tuple? new BroTupDat($slaveBrO, $broDatType, $datO->Dat, $srceN, $datO->Inst, $datO->DiMeIdsA) : new BroDat($slaveBrO, $broDatType, $datO->Dat, $srceN, $datO->DiMeIdsA);
        $cpyDatO->Bits = $datO->Bits;
        $cpyDatO->Dat2 = $datO->Dat2;
        $slaveBrO->BroDatOsA[$broDatKey] = $cpyDatO;
        if (!$datO->DiMeIdsA && ($srceN === BroDatSrce_b || !isset($cpyBrO->BaseDatOsA[$broDatType])))
          $slaveBrO->BaseDatOsA[$broDatType] = $cpyDatO;
        ++self::$Changes;
      }
    }
    $slaveBrO->Bits      = $this->Bits;
    $slaveBrO->NumDiMesA = $this->NumDiMesA;
    return $slaveBrO;
  }

  # Bro::DiMeSumming($stdYearBrO=null)
  # Performs Intermediate DiMe, DiMe Summing, and Pya DiMe Summing, inserting PyaOA BroDats if necessary using $stdYearBrO,
  #  with $stdYearBrO passed as false if inserting PyaOA BroDats is to be skipped as for a Bro being restored from string
  public function DiMeSumming($stdYearBrO=null) {
    global $DiMeTargetsA, $RestatedDiMeTargetsA;

    # Intermediate DiMes
    # This Intermediate DiMe op should be performed on a Bro only once because of the BroAddDataOp_Sum ops.
    # This should be the case as DiMeSumming() is either called when the Bro is re-crated on reading from the DB, or as part of summing, not both.
    # Through the Primary DiMe BroDats Excl Tuple Pya ones for which the equivalent of this is done as a t op on adding, with Tuples amalgamated [AmalBroDatKey => [0 => Bal, 1 => DiMeIdsA]] for all the Primary DiMe BroDats i.e. incl Start ones
    foreach ($this->PrimaryDiMeExclTuplePyaBroDatAs() as $amalBroDatKey => $datA) {
      if (count($datA[1])>1) {
        # 557      No i
        # xxxx,162,186 => i's of xxxx,162       3 x i's
        #                        xxxx,162,186
        #                        xxxx,186
        $bal = $datA[0]; # won't be false, could be 0
        $diMeIdsA  = $datA[1];
        $idiMeIdsA = [];
        $lastDiMeId = end($diMeIdsA);
        foreach ($diMeIdsA as $i => $diMeId) {
          if ($diMeId != $lastDiMeId) {
            # is a DiMe along the way to the full primary entry - add the intermediate entry. No Inst in Tuple case
            $idiMeIdsA[] = $diMeId;
            # DebugMsg("Intermediate DiMe for $this->BroId,".implode(',',$idiMeIdsA)." Bal=$bal");
            $this((int)$amalBroDatKey, $bal, BroDatSrce_i, 0, $idiMeIdsA, BroAddDataOp_Sum); # add data ($broDatTypeOrBroDatKey, $dat, $srceN, $inst=0, $diMeIdOrDiMeIdsA=0, $op=BroAddDataOp_Unique)
          }
          if ($i) { # skip this for i==0 for which "$broId,$diMeId" has been done above
            # DebugMsg("Intermediate DiMe for $this->BroId,$diMeId Bal=$bal");
            $this((int)$amalBroDatKey, $bal, BroDatSrce_i, 0, $diMeId, BroAddDataOp_Sum); # add data ($broDatTypeOrBroDatKey, $dat, $srceN, $inst=0, $diMeIdOrDiMeIdsA=0, $op=BroAddDataOp_Unique)
          }
        }
      }
    }

    # DiMe Summing
    $sumA = []; # [BroDatKey => Bal]
    foreach ($this->AllPrimaryDiMeBroDatAs() as $amalBroDatKey => $datA) { # [AmalBroDatKey => [0 => Bal, 1 => DiMeIdsA]] for all the DiMe BroDats i.e. incl Start ones
      if ($bal = $datA[0]) { # won't be false, could be 0
        $diMeIdsA = $datA[1];
        $n = count($diMeIdsA);
        $idiMeIdsA = [];
        foreach ($diMeIdsA as $i => $diMeId) { # 557; 557, 75; 556 etc  $DiMeTargetsA excludes the Restated DiMes but 75 & 76 are added here if a prior DiMeId is in $DiMeTargetsA e.g. 557 => 556
          $idiMeIdsA[] = $diMeId;
          if (isset($DiMeTargetsA[$diMeId])) # $DiMeTargetsA [DiMeId => [target DiMeIds]] excludes the Restated DiMes
            foreach ($DiMeTargetsA[$diMeId] as $tarDiMeId) {
              $tarDiMeIdsA = $idiMeIdsA;
              $tarDiMeIdsA[$i] = $tarDiMeId;
              for ($j = $i; $j<$n; ++$j) { # e.g. for 2040,160,185 as BroDat -> d for 2040,159 and 2040,159,185
                if ($j>$i) $tarDiMeIdsA[] = $diMeIdsA[$j];
                $broDatKey = substr($amalBroDatKey,0,2).implode(',', $tarDiMeIdsA);
                isset($sumA[$broDatKey]) ? $sumA[$broDatKey] += $bal : $sumA[$broDatKey] = $bal;
              }
            }
        }
      }
    }
    foreach ($sumA as $broDatKey => $bal) {
      # DebugMsg("DiMe Summing for $this->BroId,$broDatKey Bal=$bal");
      $this($broDatKey, $bal, BroDatSrce_d, 0, 0, BroAddDataOp_Replace); # add data ($broDatTypeOrBroDatKey, $dat, $srceN, $inst=0, $diMeIdOrDiMeIdsA=0, $op=BroAddDataOp_Unique)
   }

    if ($this->HasPya()) {
      # Restated special case handling.
      # 110 - Dimension - Restatements [3]                 Dimension Members
      #   [A] 4245 Restated amount                         73  Restated.Amount               DiMeId_PyaAmount
      #     [A] 3965 Prior period increase (decrease)      74  Restated.PriorPeriodIncr      DiMeId_PyaPriorPeriodIncr
      #       [A] 50 Accounting policy increase (decrease) 75  Restated.AccountingPolicyIncr DiMeId_PyaAcctPolicyIncr
      #       [A] 3178 Material error increase (decrease)  76  Restated.MaterialErrorIncr    DiMeId_PyaMaterialErrIncr
      #     [A] 3463 Original amount [default]             77  Restated [OriginalAmount]     DiMeId_PyaOriginalAmount

      # Add the 77 DiMeId_PyaOriginalAmount BroDat for each Pya BroDat, with original values from $stdYearBrO which is null if undefined
      # It should be possible to avoid the need for this routine if the BroDatSrce_r BroDats were added during Intermediate DiMe summing and DiMe summing.
      # An attempt was made on 29.11.12 (intermediate DiMes only) in ClassBroWipAJ.inc and ImportWipAJ.php, but it was incomplete and needed the DiMe summing cases to be added.
      # Also DiMe summing of already added 77s went wrong and would have needed to be suppressed. As all this could have complicated restoring such Bros from string, I stayed with this less than ideal method.
      if ($stdYearBrO !== false) # Passed as false if inserting PyaOA BroDats is to be skipped as for a Bro being restored from string
      foreach ($this->BroDatOsA as $broDatKey => $datO)
        if ($datO->IsPya() && !$datO->IsPyaOAA()) { # BroDat is a Pya one which means that it includes DiMeId = 75 or 76, DiMeId_PyaAcctPolicyIncr or DiMeId_PyaMaterialErrIncr, and the corresponding 77 DiMeId_PyaOriginalAmount BroDat has not been added.
          # Add the appropriate 77 DiMeId_PyaOriginalAmount BroDat
          # As the Pya posting has been made, there will be a SumBase
          $broDatType = $datO->BroDatType;
          $inst       = $datO->Inst;
          $diMeIdsA   = $datO->DiMeIdsA;
          if ($this->IsTuple() && $inst) {
            # Tuple case - Tuple Bro and BroTupDat includes Inst
            # 6136,211,75,T.1  create 6136,211,77,T.1 with original value of 6136,211,T.1 or create 6136,211,77,T.1 of 0 if none
            # 6376,75,T.1      create 6376,77,T.1     with original value of 6376,T.1
            if (count($diMeIdsA) > 1) {
              # 6136,211,75,T.1  create 6136,211,77,T.1 with original value of 6136,211,T.1 or create 6136,211,77,T.1 of 0 if none
              array_pop($diMeIdsA); # pop off the 75 e.g. [211]
              $broDatKey  = BuildBroDatKey($broDatType, $inst, $diMeIdsA); # 211,T.1
              $diMeIdsA[] = DiMeId_PyaOriginalAmount; # push on 77
            }else{
              # 6376,75,T.1  create 6376,77,T.1 with original value of 6376,T.1
              $broDatKey = BuildBroDatKey($broDatType, $inst); # Base 6376,T.1
              $diMeIdsA  = [DiMeId_PyaOriginalAmount];
            }
          }else{
            # Non-Tuple case
            # 9065,557,75  create 9065,557,77 with original value of 9065,557 or create 9065,557,77 of 0 if none
            # 9065,75      create 9065,77     with original value of 9065
            if (count($diMeIdsA) > 1) {
              # 9065,557,75  create 9065,557,77 with original value of 9065,557 or create 9065,557,77 of 0 if none
              array_pop($diMeIdsA); # pop off the 75 e.g. [557]
              $broDatKey  = BuildBroDatKey($broDatType, 0, $diMeIdsA); # 557
              $diMeIdsA[] = DiMeId_PyaOriginalAmount; # push on 77
            }else{
              # 9065,75  create 9065,77 with original value of 9065
              $broDatKey = $broDatType; # BuildBroDatKey($broDatType, 0); # Base
              $diMeIdsA  = [DiMeId_PyaOriginalAmount];
            }
          }
          if ($stdYearBrO && isset($stdYearBrO->BroDatOsA[$broDatKey])) { # 6136,211,T.1 or 6376,T.1 or 9065,557 or 9065
            $dat2O = $stdYearBrO->BroDatOsA[$broDatKey];
            $dat = $dat2O->Dat;
          }else # no Std Year BroDat so add a zero 77 one
            $dat = 0;
          # DebugMsg("AddPyaOriginalAmountBroDats to Bro $this->BroId,$broDatKey for 77 Bal=$dat, Inst=$inst");
          $this->NewBroDat($broDatType, $dat, BroDatSrce_r, $inst, $diMeIdsA); # NewBroDat($broDatType, $dat, $srceN, $inst, $diMeIdsA=0)
          $datO->Bits |= BroCB_PyaOAA; # to record that it has been done
        }

      # Do the Pya DiMe Summing
      # DiMe summing for Restated
      # $RestatedDiMeTargetsA=[
      # 75=>[74,73],
      # 76=>[74,73],
      # 77=>[73]];
      $sumA = []; # [BroDatKeyInclINST => [Bal, BroDatType, Inst, DiMeIdsA]
      # Thru all the BroDats processing only the Pya and PysOA ones
      foreach ($this->BroDatOsA as $broDatKey => $datO) {
        if ($datO->IsPya() || $datO->IsPyaOA()) {
          $brodatType = $datO->BroDatType;
          $bal        = $datO->Dat2 ? : $datO->Dat;
          $inst       = $datO->Inst;
          $diMeIdsA   = $datO->DiMeIdsA;
          foreach ($diMeIdsA as $i => $diMeId) {
            if (isset($RestatedDiMeTargetsA[$diMeId])) # $RestatedDiMeTargetsA [DiMeId => [target DiMeIds]]  Only 75, 76, 77
              foreach ($RestatedDiMeTargetsA[$diMeId] as $tarDiMeId) {
                $tarDiMeIdsA     = $diMeIdsA;
                $tarDiMeIdsA[$i] = $tarDiMeId;
                $broDatKey = BuildBroDatKey($brodatType, $inst, $tarDiMeIdsA);
                isset($sumA[$broDatKey]) ? $sumA[$broDatKey][0] += $bal : $sumA[$broDatKey] = [$bal, $brodatType, $inst, $tarDiMeIdsA]; # [Bal, BroDatType, Inst, DiMeIdsA]
                # DebugMsg("Pya DiMe Summing prep for $this->BroId,$broDatKey Bal=$bal Inst=$inst -> {$sumA[$broDatKey][0]}");
              }
          }
        }
      }
      foreach ($sumA as $broDatKey => $datA) { # [Bal, BroDatType, Inst, DiMeIdsA]
        # DebugMsg("Pya DiMe Summing for $this->BroId,$broDatKey Bal={$datA[0]}, Inst={$datA[1]}");
        $this($datA[1], $datA[0], BroDatSrce_d, $datA[2], $datA[3], BroAddDataOp_Replace); # add data ($broDatTypeOrBroDatKey, $dat, $srceN, $inst=0, $diMeIdOrDiMeIdsA=0, $op=BroAddDataOp_Unique)
      }
    } # End HasPya
  }

  # Bro::Stringify()
  # ~~~~~~~~~~~~~~~~
  # Returns the string form of the Bro for DB storage. Gives a much shorter string than serialize or json_encode
  # String form:
  #    SrceN DatType {DiMeRef} Dat {SrceN DatType {DiMeRef} Dat ....} for BroDats with SrceN as below
  # The coded string does not include BroId on the assumption that the BroId is stored anyway either in the DB or as an array index.
  # BroId needs to be passed to NewBroFromString() as well as the string built here to recreate the Bro i.e. $brO = NewBroFromString($broId, $broStr)
  /*const BroDatSrce_P  =  1; # * Posting
    const BroDatSrce_PE =  2; # * Prior year End
    const BroDatSrce_SE =  3; # * SumEnd
    const BroDatSrce_S  =  4; # * Summed
    const BroDatSrce_R  =  5; # * Restated - the Original Amount DiMe when included in the BaseSum of the Bro e.g. when Base only restated
    const BroDatSrce_r  =  6; # * Restated - the Original Amount DiMe when not included in the BaseSum of the Bro e.g. when other DiMes are in use -> Base sum, and this Original Amount is for info purposes only.
    const BroDatSrce_e  =  7; # * dErived or dEduced
    const BroDatSrce_b  =  8; #   BaseSum = sum of the Primary DiMes
    const BroDatSrce_t  =  9; #   Base Tuple
    const BroDatSrce_d  = 10; #   DiMe summing
    const BroDatSrce_i  = 11; #   Intermediate DiMe which can be stand alone or impact a primary summing BroDat -> different view bal from primary bal
                              # |- * = included in stringing */
  public function Stringify() {
    if ($this->IsEleSlave()) return null; # Not expected to be called for an Ele Slave which can be recreated from its Master
    $A = [];
    foreach ($this->BroDatOsA as $broDatKey => $datO)
      if ($datO->SrceN <= BroDatSrce_e)
        $A[] = $datO->Stringify();
    return implode('', $A);
  }

} # end of Bro::


##########
# BroDat # Bro Data to hold data for a Bro that is not a Tuple. (Tuple data is held in BroTupDat objects.)
##########
# Should only be instantiated via class Bro. How can a direct new be prevented? Using Interface? djh??
#
class BroDat {
  public $Dat, $SrceN, $Inst, $Dat2, # the data, with Dat2 set to Dat in the case of a Primary Summing BroDat, as the value which could be varied via an intermediate DiMe, or via a Tuple Base update re a DiMe posting. Summing uses Dat. Viewing uses Dat2
    $BroDatType, $BroDatKey, $Bits,
    $DiMeIdsA, # The DiMeIds of the BroDat, null if none
    $DadBrO;   # Reference to Bro that this BroDat is holding data for, though not a real Dad in the parent class sense.

  # BroDat::constructor
  public function __construct($dadBrO, $broDatType, $dat, $srceN, $diMeIdsA) {
    # DebugMsg("BroDat::constructor Bro $dadBrO->BroId broDatType=$broDatType, dat=$dat, srceN=$srceN, diMeIdsA=".ArrayOrIntToStr($diMeIdsA)."; Changes before=".Bro::Changes());
    $this->DadBrO = $dadBrO;
    $this->Dat    = $dat;
    $this->SrceN  = $srceN;
    $this->Inst   = 0;
    $this->BroDatType = $broDatType;
    if (IsPrimary($srceN)) {
      $this->Bits = BroCB_Primary | ($srceN === BroDatSrce_P ? BroCB_Posting : 0);
    }else
      $this->Bits = 0;
    if ($diMeIdsA) {
      $this->DiMeIdsA  = $diMeIdsA; # expect $diMeIdsA to be passed
      $this->BroDatKey = "$broDatType,".implode(',', $diMeIdsA);
      if ($this->IsPrimary() && $dadBrO->IsSumming()) {
        $this->Dat2 = $dat;
        # DebugMsg($this->BroRef()." Dat2 set to $dat");
      }
      if (($pyaDiMeId=end($diMeIdsA)) === DiMeId_PyaAcctPolicyIncr || $pyaDiMeId === DiMeId_PyaMaterialErrIncr) # The Pya DiMeId is the last DiMe in $diMeIdsA if present
        $this->Bits |= BroCB_Pya;
      if ($pyaDiMeId === DiMeId_PyaOriginalAmount) # 77
        $this->Bits |= BroCB_PyaOA;
    }else
      $this->BroDatKey = $broDatType;  # No DiMeIdsA
  }

  # BroDat::Is*
  public function IsPrimary()   { return $this->Bits & BroCB_Primary; } # Returns non-zero (BroCB_Primary) if the BroDat is a Primary one
  public function IsPosting()   { return $this->Bits & BroCB_Posting; } # Returns non-zero (BroCB_Posting) if the BroDat is a Posting one. Could use $this->ScreN === BroDatSrce_P
  public function IsPya()       { return $this->Bits & BroCB_Pya; }     # Returns non-zero (BroCB_Pya)     if the BroDat is a Pya one which means that it includes DiMeId = 75 or 76, DiMeId_PyaAcctPolicyIncr or DiMeId_PyaMaterialErrIncr
  public function IsPyaOA()     { return $this->Bits & BroCB_PyaOA; }   # Returns non-zero (BroCB_PyaOA)   if the BroDat is an Original Amount 77 DiMeId_PyaOriginalAmount one
  public function IsPyaOAA()    { return $this->Bits & BroCB_PyaOAA; }  # Returns non-zero (BroCB_PyaOAA)  if the Original Amount 77 DiMeId_PyaOriginalAmount BroDat has been Added, corresponding to this Pya (BroCB_Pya) BroDat
  public function IsEnd()       { return $this->BroDatType === BroDatT_End; }
  public function IsStart()     { return $this->BroDatType === BroDatT_Start; }
  public function IsSumBase()   { return $this->SrceN === BroDatSrce_b; }
  public function IsEitherDiMe(){ return $this->DiMeIdsA; } # either a Start DiMe or an End DiMe

  # BroDat::BroRef*
  public function BroRef() {
    $ref = $this->DadBrO->BroId;
    if ($this->IsStart())
      $ref .= 's';
    if ($this->DiMeIdsA)
      $ref .= ','.implode(',', $this->DiMeIdsA);
    return $ref;
  }
  public function BroRefSrce() {
    $ref  = BroName($this->DadBrO->BroId);
    if ($this->IsStart())
      $ref .= ',Start';
    if ($this->DiMeIdsA)
      $ref .= DiMeRefSrce($this->DiMeIdsA);
    return $ref;
  }
  public function BroRefFull() {
    return $this->BroRef().' '.$this->BroRefSrce();
  }

  # BroDat::FormattedDat() Returns formatted Dat2/Dat
  public function FormattedDat() {
    return $this->DadBrO->FormattedDat($this->Dat2 ? : $this->Dat);
  }

  # BroDat::FormattedDatTd() Returns BroDat's Dat2/Dat as a formatted td html
  public function FormattedDatTd() {
    return $this->DadBrO->FormattedDatTd($this->Dat2 ? : $this->Dat);
  }

  # BroDat::Source($srceN=false)
  public function Source($srceN=false) {
    static $sourcesA = [0, 'P', 'PE', 'SE', 'S', 'R', 'r', 'e', 'b', 't', 'd', 'i'];
    # Prefix source with 'm' for Master as the source if this is a Slave; append 't' or 'i' if is Summing Bro and Dat2 !== Dat which means there have been 't' or 'i' postings to change Dat2
    return ($this->DadBrO->IsEleSlave() ? 'm' : '').$sourcesA[$srceN ? : $this->SrceN].($this->Dat2 && $this->Dat2 !== $this->Dat ? ($this->Inst ? 't' :'i') : '');
  }

  # BroDat::BroDatTypeStr()
 #public function BroDatTypeStr() { return BroDatTypeStr($this->BroDatType, false); }
  public function BroDatTypeStr() { return BroDatTypeStr($this->BroDatType, $this->DadBrO->IsTuple()); } # to do the job for both BroDat and BroTupDat

  # BroDat::Stringify()
  # Returns string for a BroDat in the form used for DB storage:
  #    SrceN DatType {DiMeRef} Dat
  # where Dat is the Primary value in the case of BroDat whose Dat has been modified by an intermediate DiMe
  # Without the leading SrceN for Trans BroDats that are all BroDatSrce_P (Posting) type.
  # and Dat of  for a deleted tran BroDat. (Created by Import.php.) Only applicable to Data Trail, not full Bro storage and retrieval.
  public function Stringify() {
    return is_int($this->BroDatKey) ? $this->SrceN.$this->BroDatType.$this->Dat : $this->SrceN.$this->BroDatType.substr($this->BroDatKey,2).''.$this->Dat; # SrceN DatType {DiMeRef} Dat
  }

  # BroDat::IsEqual($datO) Returns boolean for this BroDat == $datO
  # BroDats can't be compared directly in php code because of a recursion failure due to the DadBrOs, so this fn does by comparing the data.
  # Is expected to be called only for BroDats with same BroDatKey for same BroId
  public function IsEqual($datO) {
    return $this->Dat === $datO->Dat && $this->SrceN === $datO->SrceN && $this->Dat2 === $datO->Dat2; # && $this->BroDatKey === $datO->BroDatKey && $this->DadBrO->BroId === $datO->DadBrO->BroId ;
  }

} # end of class BroDat

#############
# BroTupDat # Bro Data for a Tuple Bro
#############
# Should only be instantiated via class Bro. How can a direct new be prevented? Using Interface? djh??

class BroTupDat extends BroDat {

  # BroTupDat::constructor
  public function __construct($dadBrO, $broDatType, $dat, $srceN, $inst, $diMeIdsA) {
    # DebugMsg("BroTupDat::constructor Bro $dadBrO->BroId broDatType=$broDatType, dat=$dat, srceN=$srceN, inst=$inst, diMeIdsA=".ArrayOrIntToStr($diMeIdsA)."; Changes before=".Bro::Changes());
    $this->DadBrO = $dadBrO;
    $this->Dat    = $dat;
    $this->SrceN  = $srceN;
    $this->Inst   = $inst;
    $this->BroDatType = $broDatType;
    if (IsPrimary($srceN)) {
      $this->Bits = BroCB_Primary | ($srceN === BroDatSrce_P ? BroCB_Posting : 0);
      if ($dadBrO->IsSumming()) { # Both Base and DiMe in Tuple case
        # DebugMsg($this->BroRef()." Dat2 set to $dat");
        $this->Dat2 = $dat;
      }
    }else
      $this->Bits = 0;
    if ($diMeIdsA) {
      $this->DiMeIdsA = $diMeIdsA; # expect $diMeIdsA to be passed
      if ($inst) {
        $dadBrO->MaxInst = max($dadBrO->MaxInst, $inst);
        $this->BroDatKey = (2000+$inst).",$broDatType,".implode(',', $diMeIdsA);
      }else
        $this->BroDatKey = "$broDatType,".implode(',', $diMeIdsA);
      if (($pyaDiMeId=end($diMeIdsA)) === DiMeId_PyaAcctPolicyIncr || $pyaDiMeId === DiMeId_PyaMaterialErrIncr) # The Pya DiMeId is the last DiMe in $diMeIdsA if present
        $this->Bits |= BroCB_Pya;
      if ($pyaDiMeId === DiMeId_PyaOriginalAmount) # 77
        $this->Bits |= BroCB_PyaOA;
    }else{
      # No DiMeIdsA
      if ($inst) {
        $dadBrO->MaxInst = max($dadBrO->MaxInst, $inst);
        $this->BroDatKey = (2000+$inst).",$broDatType";
      }else
        $this->BroDatKey = $broDatType;
    }
  }

  # BroTupDat::BroRef()
  public function BroRef() {
    return parent::BroRef().($this->Inst ? ",T.$this->Inst" : '');
  }
  # BroTupDat::BroRefSrce()
  public function BroRefSrce() {
    return parent::BroRefSrce().($this->Inst ? ",T.$this->Inst" : '');
  }

  # BroTupDat::Stringify()
  # Returns string for a BroTupDat in the form used for DB storage:
  #    SrceN DatType Inst{DiMeRef} Dat
  # where Dat is the Primary value in the case of BroDat whose Dat has been modified by an intermediate DiMe
  # Without the leading SrceN for Trans BroDats that are all BroDatSrce_P (Posting) type.
  # and Dat of  for a deleted tran BroDat. (Created by Import.php.) Only applicable to Data Trail, not full Bro storage and retrieval.
  public function Stringify() {
    return (is_int($this->BroDatKey) || strlen($this->BroDatKey)===6) ? "$this->SrceN$this->BroDatType$this->Inst".$this->Dat : "$this->SrceN$this->BroDatType$this->Inst".implode(',', $this->DiMeIdsA).''.$this->Dat;
  }

  # BroTupDat::IsEqual($datO) Returns boolean for this BroTupDat == $datO
  public function IsEqual($datO) {
    return $this->Dat === $datO->Dat && $this->SrceN === $datO->SrceN && $this->Dat2 === $datO->Dat2 && $this->Inst === $datO->Inst;
  }

}

####################
# Helper Functions #
####################

# NewBroFromString($broId, $broStr)
# ---------------------------------
# Returns a Bro object created from a Bro string
# Non-Tuple: SrceN DatType {DiMeRef} Dat {SrceN DatType {DiMeRef} Dat ....}
# Tuple:     SrceN DatType Inst{DiMeRef} Dat {SrceN DatType Inst{DiMeRef} Dat ....}
# for BroDats/BroTupDats where:
/*const BroDatSrce_P  =  1; # * Posting
  const BroDatSrce_PE =  2; # * Prior year End
  const BroDatSrce_SE =  3; # * SumEnd
  const BroDatSrce_S  =  4; # * Summed
  const BroDatSrce_R  =  5; # * Restated - the Original Amount DiMe when included in the BaseSum of the Bro e.g. when Base only restated
  const BroDatSrce_r  =  6; # * Restated - the Original Amount DiMe when not included in the BaseSum of the Bro e.g. when other DiMes are in use -> Base sum, and this Original Amount is for info purposes only.
  const BroDatSrce_e  =  7; # * dErived or dEduced
  const BroDatSrce_b  =  8; #   BaseSum = sum of the Primary DiMes
  const BroDatSrce_t  =  9; #   Base Tuple
  const BroDatSrce_d  = 10; #   DiMe summing
  const BroDatSrce_i  = 11; #   Intermediate DiMe which can be stand alone or impact a primary summing BroDat -> different view bal from primary bal
                            # |- * = included in stringing */

function NewBroFromString($broId, $broStr) {
  $brO = new Bro($broId);
 #foreach (array_reverse(explode('', $broStr)) as $a) { # array_reverse() to do DiMe ones first before possible issues for 75 creating 77.
  foreach (explode('', $broStr) as $a) {
    # Non-Tuple: SrceN DatType {DiMeRef} Dat {SrceN DatType {DiMeRef} Dat ....}
    # Tuple:     SrceN DatType Inst{DiMeRef} Dat {SrceN DatType Inst{DiMeRef} Dat ....}
    $broDatType = (int)$a[1];
    $dat = substr($a, 2);
    if ($brO->IsTuple()) {
      # $dat is Inst Dat or InstDiMeRef Dat
      $dA = explode('', $dat);
      $inst = (int)$dA[0];
      if (count($dA)===3) {
        $diMeIdsA = CsListToIntA($dA[1]);
        $dat = $dA[2];
      }else{
        $diMeIdsA = 0;
        $dat = $dA[1];
      }
    }else{ # $dat is Dat or DiMeRef} Dat
      $inst = 0;
      $dA = explode('', $dat);
      if (count($dA)===2) {
        $diMeIdsA = CsListToIntA($dA[0]);
        $dat = $dA[1];
      }else
        $diMeIdsA = 0; # with $dat already set
    }
    if (is_numeric($dat)) $dat = (int)$dat;
    $brO($broDatType, $dat, (int)$a[0], $inst, $diMeIdsA); # add data ($broDatTypeOrBroDatKey, $dat, $srceN, $inst=0, $diMeIdOrDiMeIdsA=0, $op=BroAddDataOp_Unique)
  }
  if ($brO->IsSumming()) {
    if ($brO->HasPya()) {
      # Set the PyaOAA bits
      $broDatOsA = $brO->AllBroDatOs();
      foreach ($broDatOsA as $broDatKey => $datO)
        if ($datO->IsPya()) {
          if ($datO->IsPyaOAA()) die("Die - IsPyaOAA() already set for Pya BroDat in NewBroFromString for Bro $broId, broDatKey=$broDatKey");
          # BroDat is a Pya one which means that it includes DiMeId = 75 or 76, DiMeId_PyaAcctPolicyIncr or DiMeId_PyaMaterialErrIncr
          # The corresponding 77 DiMeId_PyaOriginalAmount BroDat should be present. Check and set the bit.
          $broDatKey77 = substr($broDatKey, 0, -2).DiMeId_PyaOriginalAmount; # replace the final 75 or 76 by 77
          if (isset($broDatOsA[$broDatKey77])) {
            $datO->Bits |= BroCB_PyaOAA;
            # DebugMsg("In NewBroFromString for Bro $broId, PyaOriginalAmount Added bit set for key=$broDatKey");
          }else
            die("Die - In NewBroFromString for Bro $broId, PyaOriginalAmount BroDat key=$broDatKey77 not present as expected");
        }
    }
    $brO->DiMeSumming(false); # false to skip Original Amount 77 creation as these BroDats are stored by Stringify()
  }
  return $brO;
}

# BuildBroDatKey($broDatType, $inst, $diMeIdsA=0)
# -----------------------------------------------
# Returns a BroDatKey for $broDatType, $inst, $diMeIdsA
# Case  BroDatKey
# Start {INST,}1{,DiMeRef} Start Base (no DiMeRef) and DiMe value for a StartEnd Bro shown externally as {BroId}s or {BroId}s,DiMeRef e.g. 1234s or 1234s,145
# End   {INST,}2{,DiMeRef} End or Normal value for a Bro, Base (no DiMeRef) and DiMe value, shown externally as BroId or BrodId,DiMeRef e.g. 1234,145,185{,T.x}
# where the 1,2f the BroDatKey are the constants BroDatT_Start, BroDatT_End
# INST is the instance # for a Tuple Bro + 2000

function BuildBroDatKey($broDatType, $inst, $diMeIdsA=0) {
  if ($diMeIdsA) {
    if ($inst)
      $key = (2000+$inst).",$broDatType,".implode(',', $diMeIdsA);
    else
      $key = "$broDatType,".implode(',', $diMeIdsA);
  }else
    if ($inst)
      $key = (2000+$inst).",$broDatType";
    else
      $key = $broDatType;
  return $key;
}


# AdjustAmalBroDatKey(&$broDatKey, $adj)
# ----------------------------------
# Called to adjust the Bro BroDatType part of a Bro AmalBroDatKey (no INST) by $adj (+ or -ve)
# e.g. +BroDatT_Start (1) to change Start to End
#      -BroDatT_Start (1) to change End to Start
function AdjustAmalBroDatKey(&$broDatKey, $adj) {
  if (is_int($broDatKey)) # BroDatKey is just the BroDatType
    $broDatKey += $adj;
  else # string BroDatKey with BroDatType as first character
    $broDatKey[0] = chr(ord($broDatKey[0]) + $adj);
}

# UnpackBroDatKey($broDatKey) Called by DataTrail.php
# ---------------------------
# Case  BroDatKey
# Start {INST,}1{,DiMeRef}
# End   {INST,}2{,DiMeRef}
# Returns [$broDatType, $diMeIdsA, $inst] for use: list($broDatType, $diMeIdsA, $inst) = UnpackBroDatKey($broDatKey);
function UnpackBroDatKey($broDatKey) {
  #          Tuple       Non-Tuple
  # Base Key 1123,2      2
  # DiMe key 1123,2,148  2,148
  if (is_int($broDatKey)) # Base Non-Tuple key of BroDatType
    return [$broDatKey, 0, 0];
  $diMeIdsA = CsListToIntA($broDatKey);
  $inst     = $diMeIdsA[0] > 2000 ? array_shift($diMeIdsA) - 2000 : 0;
  $broDatType = array_shift($diMeIdsA);
  if (!count($diMeIdsA))
    $diMeIdsA = 0;
  return [$broDatType, $diMeIdsA, $inst];
}

# UnpackAmalBroDatKey($amalBroDatKey)
# ------------------------------------------
# Returns [$broDatType, $diMeIdsA] for use: list($broDatType, $diMeIdsA) = UnpackAmalBroDatKey($amalBroDatKey);
function UnpackAmalBroDatKey($broDatKey) {
  # Base Key 2
  # DiMe key 2,148
  if (is_int($broDatKey)) # Base key of BroDatType
    return [$broDatKey, 0];
  $broDatType = (int)$broDatKey;
  $diMeIdsA   = CsListToIntA(substr($broDatKey, 2));
  return [$broDatType, $diMeIdsA];
}

function IsEndAmalBroDatKey($broDatKey) {
  return (int)$broDatKey === BroDatT_End;
}

function BroName($broId) {
  global $BroNamesA;
  return $BroNamesA[$broId];
}

function BroShortName($broId) {
  global $BroShortNamesA;
  return isset($BroShortNamesA[$broId]) ? $BroShortNamesA[$broId] : '';
}

function DimDiMeShortName($diMeId) {
  global $PMemNamesA;
  return $PMemNamesA[$diMeId];
}

# DiMeRefSrce($diMeIdsA)  Returns source reference for the DiMe defined by $diMeIdsA
function DiMeRefSrce($diMeIdsA) {
  $ref = '';
  if ($diMeIdsA)
    foreach ($diMeIdsA as $diMeId)
      $ref .= ','.DimDiMeShortName($diMeId);
  return $ref;
}

function IsPrimary($srceN) {
  switch ($srceN) {
    case BroDatSrce_P:  #  P Posting
    case BroDatSrce_PE: # PE Prior year End
    case BroDatSrce_SE: # SE SumEnd
    case BroDatSrce_S:  #  S Summed
    case BroDatSrce_R:  #  R Restated - the Original Amount DiMe when included in sum for BaseSum of the Bro e.g. when Base only restated
      return true;
   #case BroDatSrce_r:  #  r Restated - the Original Amount DiMe when not included in sum for BaseSum of the Bro e.g. when other DiMes are in use -> Base sum, and this Original Amount is for info purposes only.
   #case BroDatSrce_e:  #  e Derived or Deduced
   #case BroDatSrce_b:  #  b Base = sum of DiMes
   #case BroDatSrce_t:  #  t Base Tuple
   #case BroDatSrce_d:  #  d DiMe summing
   #case BroDatSrce_i : #  i intermediate DiMe
  }
  return false;
}

function BroDatTypeStr($broDatType, $tuple) {
  static $typesA = [0, 'Start', 'End'];
  return ($tuple ? 'Tuple ' : '').$typesA[$broDatType].' BroDat';
}



