<?php /* Copyright 2011-2013 Braiins Ltd

/inc/ClassBro.inc

Class Bro and Helper Functions for BRL Bro ops

Implements Classes Bro and BroDat with Bro being the main one.

History:
15.05.13 BRL version started

ToDo
~~~~

Handle StartEnd in Slaves
Handle M PMems in Slaves

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

A Bro object holds all data for a given BroId i.e. the Base, Start (if applicable), and Property Member data.

All data is held in BroDat objects keyed by BroDatKey where:

Case  BroDatKey      Descr
Start 1{,PMemRef...} Start vale for a Bro. 's' appended to the BroId for external showing
End   2{,PMemRef...} End or normal value for a Bro

where the 1,2 of the BroDatKey are the constants BroDatT_Start, BroDatT_End

Base BroDatKeys are ints (1 or 2). The PMem ones are always strings.

Constructor and __invoke()
--------------------------
BroO          Bro::constructor($broId)
BroDatO/false Bro::__invoke($broDatTypeOrBroDatKey, $dat, $srceN, $pMemRefOrPMemRefsA=0, $op=BroAddDataOp_Unique) Function run when Bro object is called as a function = add data to Bro

Class Bro Public Member functions
---------------------------------
void            static SetErrorCallbackFn($cbFn)
void            static SetNewPostingPMemBroDatCallbackFn($cbFn)
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
boolean         IsMoney()
0/BroB_DE       IsDE()
0|BS_Dr|BS_Dr   Sign()           Returns 0|1|2 for undefined | BS_Dr | BS_Cr
String          PostTypeStr()
0/BroCB_Primary HasPrimary()     Returns non-zero (BroCB_Primary) if the Bro includes Primary data
0/BroCB_Pya     HasPya()         Returns non-zero (BroCB_Pya) if the Bro includes Pya data
boolean         HasPosting()     Returns true if the Bro includes Posting data and is not a Slave
int             DefaultPMemId()  Returns the Bro's default PMemId to use when changing a Summing Bro Base BroDat to a PMem BroDat = the Mandatory/Default PMem if there is one, and first one of list if there is a list, o'wise PMemId_Unallocated.
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

array  AllBroDatOs()          Returns [BroDatKey => BroDatO] for all the BroDa objects or an empty array if none tho there will only be none if a Bro was created wo data and no add data call was made.

The following *Dat*s Functions are applicable to Summing Bros only

array  PrimaryPMemBroDatAs()  Returns [BroDatKey => [0 => Bal, 1 => PMemRefsA]] for all the Primary PMem BroDats i.e. incl Start ones
array  StartPrimaryBroDatAs() Returns [BroDatKey => [0 => Bal, 1 => SrceN]] for the Start Primary BroDats, only Pya BroDats when in a Pya year
array  EndPrimaryBroDatOs()   Returns [BroDatKey => BroDatO] for the End Primary BroDats
array  EndPrimaryBroDatAs()   Returns [BroDatKey => Bal] for the End Primary BroDats, only Pya BroDats when in a Pya year
array  SummingBroDatAs()      Returns [BroDatKey => [0 => Bal, 1 => SrceN]] for the Primary BroDats, and only Pya BroDats when in a Pya year. Should only be called for a Non-StartEnd Summing Bro.
array  PostingBroDatOs()      Returns [BroDatKey => BroDatO] for the Posting BroDats

BrO    Copy()                 Copies this Bro to a new Bro and returns the new BrO
BrO    CopyToSlave($slaveBrO) Copies this Bro which should be a Master Bro to $slaveBroO. Returns $slaveBrO.
void   PMemSumming($stdYearBrO=null) Performs Intermediate PMem, PMem Summing, and Pya PMem Summing, inserting PyaOA BroDats if necessary using $stdYearBrO, with $stdYearBrO passed as false if inserting PyaOA BroDats is to be skipped as for a Bro being restored from string
string Stringify()            Returns the string form of the Bro for DB storage. Gives a much shorter string than serialize or json_encode

################
# Class BroDat # Bro Data
################

BroDaatO  BroDat::constructor($dadBrO, $broDatType, $dat, $srceN, $pMemRefsA, $bits)

Class BroDat Public Member functions
------------------------------------
0/BroCB_Primary IsPrimary()     Returns non-zero (BroCB_Primary) if the BroDat is a Primary one
0/BroCB_Posting IsPosting()     Returns non-zero (BroCB_Posting) if the BroDat is a Posting one
0/BroCB_Pya     IsPya()         Returns non-zero (BroCB_Pya) if the BroDat is a Pya one which means that it includes PMemId = PMemId_PyaAcctPolicyIncr or PMemId_PyaMaterialErrIncr
0/BroCB_PyaOAA  IsPyaOAA()      Returns non-zero (BroCB_PyaOAA) if the Original Amount PMemId_PyaOriginalAmount BroDat has been Added, corresponding to this Pya (BroCB_Pya) BroDat. Set on same BroDat as Pya.
0/BroCB_PyaOA   IsPyaOA()       Returns non-zero (BroCB_PyaOA) if the Brodat is an Original Amount PMemId_PyaOriginalAmount one.
boolean         IsEnd()
boolean         IsStart()
boolean         IsSumBase()
boolean         IsPMem()
int/string      BroRef()
string          BroRefSrce()
string          BroRefFull()
string          FormattedDat()   Returns formatted Dat
string          FormattedDatTd() Returns BroDat's Dat as a formatted td html
string          Source($srceN=false)
string          BroDatTypeStr()  Returns Returns one of 'Start', 'End'
string          Stringify()      Returns string for a BroDat in the form used for DB storage:
boolean         IsEqual($datO)   Returns boolean for this BroDat == $datO

####################
# Helper Functions #
####################

Bro        NewBroFromString($broId, $broStr)   Returns a Bro object created from a Bro string

int/string BuildBroDatKey($broDatType, $pMemRefsA=0) Returns a BroDatKey for $broDatType, $pMemRefsA
void       AdjustBroDatKey(&$broDatKey, $adj)        Called to adjust the Bro BroDatType part of a Bro BroDatKey by $adj (+ or -ve)
[]         UnpackBroDatKey($broDatKey)               Returns [$broDatType, $pMemRefsA] for use: list($broDatType, $pMemRefsA) = UnpackBroDatKey($broDatKey);
boolean    IsEndBroDatKey($broDatKey)

string     BroName($broId)            Requires $BroNamesA to have been loaded
string     BroShortName($broId)       Requires $BroShortNamesA to have been loaded
string     PMemName($pMemId)          Requires $PMemNamesA to have been loaded

string     PMemRefSrce($pMemRefsA)     Returns source reference for the PMem defined by $pMemRefsA

boolean    IsPrimary($srceN)

string     BroDatTypeStr($broDatType)  Returns one of 'Start', 'End'

####################

#############
# Bro class #
#############

*/

# Bro class constants
# BroDat BroDatType and first char of BroDatKey, as they are for BroDatKey sorting purposes.
const BroDatT_Start = 1; # Start BroDat
const BroDatT_End   = 2; # End or Normal BroDat

# Bits
const BroCB_Primary = 1; # For BroDat Bits means BroDatO is a Primary BroDat;  For Bro Bits means Bro includes Primary BroDats
const BroCB_Posting = 2; # For BroDat Bits means BroDatO is a Posting Brodat;  For Bro Bits means Bro includes Posting BroDats
const BroCB_Pya     = 4; # For BroDat Bits means BroDatO is a Pya Posting Brodat; For Bro Bits means Bro includes Pya BroDats. (Includes PMemId = PMemId_PyaAcctPolicyIncr or PMemId_PyaMaterialErrIncr)
const BroCB_PyaOA   = 8; # For BroDat Bits means is a an Original Amount (77 or PMemId_PyaOriginalAmount) BroDat one. Not used at Bro level. (Can have multiple 77s in a Bro)
const BroCB_PyaOAA  =16; # Used with a Pya BroDat to mean that the corresponding PyaOA (Original Amount or 77 PMemId_PyaOriginalAmount) BroDat has been Added. Not used at Bro level.

# Source Enums
const BroDatSrce_P  =  1; # * Posting
const BroDatSrce_PE =  2; # * Prior year End
const BroDatSrce_SE =  3; # * SumEnd
const BroDatSrce_S  =  4; # * Summed
const BroDatSrce_R  =  5; # * Restated - the Original Amount when included in the BaseSum of the Bro e.g. when Base only restated
const BroDatSrce_r  =  6; # * Restated - the Original Amount when not included in the BaseSum of the Bro e.g. when other members are in use -> Base sum, and this Original Amount is for info purposes only.
const BroDatSrce_e  =  7; # * dErived or dEduced
const BroDatSrce_b  =  8; #   BaseSum = sum of the Primary Members
const BroDatSrce_d  =  9; #   Member summing djh?? Change to m
const BroDatSrce_i  = 10; #   Intermediate member which can be stand alone or impact a primary summing BroDat -> different view bal from primary bal
                          # |- * = included in stringing

# AddData Op Enums
const BroAddDataOp_Unique  = 1; # default -> Duplicate posting error if same add repeated
const BroAddDataOp_Replace = 2; # Replace the previous Dat if same add repeated as in summing iterations
const BroAddDataOp_Sum     = 3; # Add to the  previous Dat2 if same add repeated as in creating intermediate members

#equire Com_Str_Tx.'BroInfoA.inc';       # $BroInfoA
#equire Com_Str_Tx.'BroNamesA.inc';      # $BroNamesA
#equire Com_Str_Tx.'BroShortNamesA.inc'; # $BroShortNamesA
require Com_Str.'PMemsA.inc';       # $PMemsA
require Com_Str.'PMemNamesA.inc';   # $PMemNamesA
require Com_Str.'PMemTargetsA.inc'; # $PMemTargetsA $RestatedPMemTargetsA

#######
# Bro #
#######

class Bro {
  private static $Changes = 0, $ErrorMsg, $ErrorCallbackFn, $NewPostingPMemBroDatCallbackFn,
    $IsPyaYearB; # Set via SetIsPyaYearB($b) to indicate that current Bros being processed are for a Pya year if true, or not if false. Affects summung via Bro::SummingBroDatAs()

  # None of these members are encoded into a BroString - all is recreated by NewBroFromString()
  public $BroId, $InfoA, $InfoBits, $DataTypeN;

  private $Bits,
    $NumPMemsA,  # count of PMem BroDats
    $BaseDatOsA, # the Base BroDats
    $BroDatOsA;  # [BroDatKey -> BroDatO] always defined even if just []

  # Bros::Set statics
  public static function SetErrorCallbackFn($cbFn) { self::$ErrorCallbackFn = $cbFn; }
  public static function SetNewPostingPMemBroDatCallbackFn($cbFn) { self::$NewPostingPMemBroDatCallbackFn = $cbFn; }
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
    global $BroInfoA;
    DebugMsg("Bro::constructor broId=$broId, Changes before this=".self::$Changes);
    ++self::$Changes;
    $this->BroId     = $broId; # expected to be int
    $this->Bits      = 0;
    $this->NumPMemsA = $this->BaseDatOsA = [0, 0, 0];
    $this->BroDatOsA   = [];
    $broA = $BroInfoA[$broId];
    $this->InfoBits  = $broA[BroI_Bits];
    $this->DataTypeN = $broA[BroI_DataTypeN];
    unset($broA[BroI_Bits]); # unset the ones transferred to member properties for speed/frequency of use
    unset($broA[BroI_DataTypeN]);
    $this->InfoA = $broA;
  }

  # Bro::__invoke($broDatTypeOrBroDatKey, $dat, $srceN, $pMemRefOrPMemRefsA=0, $op=BroAddDataOp_Unique) Function run when Bro object is called as a function = add data to Bro
  # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  # Adds data (post, entry, PMem, No PMem) to the Bro, according to $op
  # Returns BroDatO if OK, false if an error has occurred.
  # $broDatTypeOrBroDatKey, $dat and $srceN are always required
  # $pMemRefOrPMemRefsA Can be 0, an int non-zero PMemId, or an array of PMemIds
  # Sums to the Base in case of a PMem being added to a summing Bro.
  #
  # Ca <------------------------------ Passed ----------------> + $scrceN always
  # se $broDatTypeOrBroDatKey  $pMemRefOrPMemRefsA
  #  1 int BroDatT_Start       0 or int PMemId or [PMemRefsA]
  #  2 int BroDatT_End         "
  #  3 str BroDatT_*,PMemRef   0                               Only used with summing ops where different SrceN types are not expected

  public function __invoke($broDatTypeOrBroDatKey, $dat, $srceN, $pMemRefOrPMemRefsA=0, $op=BroAddDataOp_Unique) {
    DebugMsg("Bro::add data to Bro $this->BroId, broDatTypeOrBroDatKey=$broDatTypeOrBroDatKey, dat=$dat, srceN=$srceN, pMemRefOrPMemRefsA=".ArrayOrIntToStr($pMemRefOrPMemRefsA).", Op=$op; Changes before=".self::$Changes);
    if ($dat && !$srceN) return $this->Error('srceN not set when dat=|%s| for add dadat call to Bro %s', [$dat, $this->BroId]);

    # Derive $broDatType and $broDatKey plus $pMemRefsA if required.
    # $broDatKey is set by the BroDat constrctor but need it here too to check for duplicates.
    if (is_int($broDatTypeOrBroDatKey)) {
      # Cases 1 - 2
      # Ca <------------------------ Passed -------------------> + $scrceN always
      # se $broDatTypeOrBroDatKey  $pMemRefOrPMemRefsA
      #  1 int BroDatT_Start       0 or int PMemId or [PMemRefsA]
      #  2 int BroDatT_End         "
      $broDatType = $broDatTypeOrBroDatKey;
      if (!$pMemRefOrPMemRefsA)
        $pMemRefsA = 0;
      else if (is_array($pMemRefOrPMemRefsA))
        $pMemRefsA = $pMemRefOrPMemRefsA;
      else # Expected to be a PMemId or a PMemRef
        $pMemRefsA = [$pMemRefOrPMemRefsA];
    }else if (is_string($broDatTypeOrBroDatKey)) {
      # Case 3
      # Ca <---------------------- Passed ----------> + $scrceN always
      # se $broDatTypeOrBroDatKey  $pMemRefOrPMemRefsA
      #  3 str BroDatT_*,PMemRef   0                  Only used with summing ops where different SrceN types are not expected
      $broDatType = (int)$broDatTypeOrBroDatKey;
      $pMemRefsA  = PMemRefToA(substr($broDatTypeOrBroDatKey, 2));
    }else
      return $this->Error('Add data broDatTypeOrBroDatKey argument %s invalid', $broDatTypeOrBroDatKey);

    $broDatKey = BuildBroDatKey($broDatType, $pMemRefsA);
    $baseDatO  = $this->BaseDatOsA[$broDatType]; # 0 until Base has been created

    if (!$pMemRefsA) {
      ############
      # Base Add #
      ############

      if (!$baseDatO)
        # Base Add: No Base yet, so add as Base
        return $this->NewBaseBroDat($broDatType, $dat, $srceN); # NewBaseBroDat($broDatType, $dat=0, $srceN=0)

      # Base Add: With Base

      # Base Add: With Base:
      # a. Bro is a Summing Bro with Base and PMems added then this No PMem Base Add has come along, so convert this add to a PMem one
      # b. Base is being replaced
      # Otherwise it is a duplicate add or arg error
      if ($this->IsSumming() && $this->NumPMemsA[$broDatType]) {
        # Base Add: a. With Base, Summing, Has PMems, so convert this add to a PMem one
        DebugMsg("Converting $dat post to $this->BroId to a PMem posting to ".$this->DefaultPMemId()." as the Bro already has other PMem postings");
        return $this($broDatType, $dat, $srceN, $this->DefaultPMemId(), $op);
      }

      switch ($op) {
        case BroAddDataOp_Unique: return $this->Error('Duplicate posting to %s', $datO->BroRefFull()); # default -> Duplicate posting error if same add repeated

        case BroAddDataOp_Replace: # Replace the previous Dat if same add repeated as in summing iterations
          # Base Add: b. With Base, Base is being replaced
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
      return $this->Error('Invalid Bro add data Op 1 argument %s ', $op);
    } # end of Base Add

    ############
    # PMem Add #  Start or End PMem Add. Could be a Pya one too. Have $pMemRefsA
    ############
    DebugMsg("Bro::add PMem Add");
    if (isset($this->BroDatOsA[$broDatKey])) {
      # PMem Add: BroDat obj exits already. Bases will exist.
      DebugMsg("Bro::add PMem Add BroDat obj exits already. Bases will exist.");
      $datO = $this->BroDatOsA[$broDatKey];
      switch ($op) {
        case BroAddDataOp_Unique: return $this->Error('Duplicate posting to %s', $datO->BroRefFull()); # default -> Duplicate posting error if same add repeated

        case BroAddDataOp_Replace: # PMem Add: Replace the previous Dat if same add repeated as in summing iterations
          if ($datO->SrceN !== $srceN) {
            # Allow if previous was PE and this is Sum re StartEnd Sets e.g. Tx542
            if ($datO->SrceN === BroDatSrce_PE && $srceN === BroDatSrce_S) {
              ++self::$Changes;
              $datO->SrceN = $srceN;
            }else
              return $this->Error('Replace add data call for %s has different source (%s) from the previous one (%s)', [$datO->BroRefFull(), $datO->Source($srceN), $datO->Source()]);
          }
          if ($dat !== $datO->Dat) {
            DebugMsg("Replace change ".$datO->BroRefFull()." from $datO->Dat to $dat");
            # changed
            ++self::$Changes;
            $diff = $dat - $datO->Dat;
            $datO->Dat = $dat;
            if ($datO->IsPrimary() && $this->IsSumming()) {
              # Set Dat2 also for a Primary Summing Replace
              DebugMsg($datO->BroRef()." setting Dat2 to $dat");
              $datO->Dat2 = $dat;
              # Update the Base whose key is just the BroDatType
              $this->BroDatOsA[$broDatType]->Dat += $diff;
            }
          }
          return $datO;

        case BroAddDataOp_Sum: # Add to the  previous Dat2 if same add repeated as in creating intermediate PMems, unless is an i Brodat in which case add to Dat
          ++self::$Changes;
          $datO->SrceN === BroDatSrce_i ? $datO->Dat += $dat : $datO->Dat2 += $dat;
          return $datO;

      }
      return $this->Error('Invalid Bro add data Op 2 argument %s ', $op);
    } # PMem Add: End of BroDat obj exits already

    # PMem Add: New PMem, Start or End BroDat Add. Have $pMemRefsA
    DebugMsg('Bro::add PMem Add: New PMem, Start or End BroDat Add. Have $pMemRefsA');
    $datO = $this->NewBroDat($broDatType, $dat, $srceN, $pMemRefsA); # NewBroDat($broDatType, $dat, $srceN, $pMemRefsA=0)

    if ($datO->IsPrimary() && $this->IsSumming()) {
      # PMem Add: PMem Primary Summing so Update Base
      DebugMsg('Bro::add PMem Add: PMem Primary Summing so Update Base');
      if (!$baseDatO) {
        # PMem Add: PMem Primary Summing, No Base yet so create a zero SumBase
        DebugMsg("No Base yet so add a zero SumBase");
        $baseDatO = $this->NewBaseBroDat($broDatType, 0, BroDatSrce_b); # NewBaseBroDat($broDatType, $dat=0, $srceN=0)
      }else if ($baseDatO->SrceN !== BroDatSrce_b) {
        # PMem Add: New Primary Summing PMem, Base not a SumBase
        if (!$baseDatO->IsPrimary()) return $this->Error('PMem post %s to Summing Bro but Bro had a prior No PMem posting which was not a Primary one.', $datO->BroRefFull());
        # PMem Add: New Primary Summing PMem, Primary Base not a SumBase
        # .. add a PMem BroDat for the previous Base BroDat using the default PMem unless this is a Pya post in which case use OA in R (primary) form
        if ($datO->IsPya()) {
          $srceN  = BroDatSrce_R;
          $pMemId = PMemId_PyaOriginalAmount; # 77
          DebugMsg("Adding a Primary Restated Original Amount PMem with previous Base so add $this->BroId,$pMemId BroDat for the previous Base Dat and change the previous Base to a SumBase");
          $datO->Bits |= BroCB_PyaOAA; # to record that the appropriate 77 PMemId_PyaOriginalAmount BroDat has been added
        }else{
          $srceN  = $baseDatO->SrceN;
          $pMemId = $this->DefaultPMemId();
          DebugMsg("Adding a Primary Summing PMem with previous Base so add $this->BroId,$pMemId (default PMem) BroDat for the previous Base Dat and change the previous Base to a SumBase");
        }
        $this->NewBroDat($broDatType, $baseDatO->Dat, $srceN, [$pMemId]); # NewBroDat($broDatType, $dat, $srceN, $pMemRefsA=0)
        $baseDatO->SrceN = BroDatSrce_b;
        $baseDatO->Bits  = 0;
      }
      $baseDatO->Dat += $dat; # works for DT_MoneyString of 1000|Save the Children as the 1000 is added
    }
    return $datO;
    # end of PMem Add
  }

  # Bro::New*() private fns called only from __invoke() to add data to Bro
  # Bro::NewBaseBroDat() Called from __invoke() to add a Base BroDat
  private function NewBaseBroDat($broDatType, $dat=0, $srceN=0) {
    return $this->BaseDatOsA[$broDatType] = $this->NewBroDat($broDatType, $dat, $srceN);
  }

  # Bro::NewBroDat() Called from __invoke() in PMem case or via NewBaseBroDat() in the case of a Base BroDat
  private function NewBroDat($broDatType, $dat, $srceN, $pMemRefsA=0) {
    $datO = new BroDat($this, $broDatType, $dat, $srceN, $pMemRefsA);
    $this->BroDatOsA[$datO->BroDatKey] = $datO;
    ksort($this->BroDatOsA, SORT_NATURAL);
    $this->Bits |= $datO->Bits; # BroCB_Primary | BroCB_Posting
    if ($pMemRefsA) {
      ++$this->NumPMemsA[$broDatType];
      if (self::$NewPostingPMemBroDatCallbackFn && ($datO->Bits & BroCB_Posting)) {
        $cbFn = self::$NewPostingPMemBroDatCallbackFn;
        $cbFn($pMemRefsA);
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
  public function IsMoney()     { return ($this->DataTypeN === DT_Money); }
  public function IsDE()        { return ($this->InfoBits & BroB_DE); }
  public function Sign()        { return $this->InfoA[BroI_SignN]; } # Returns 0|1|2 for undefined | BS_Dr | BS_Cr
  public function PostTypeStr() { return ($this->InfoBits & BroB_DE) ? 'DE' : 'Sch'; }
 #public function NumBroDats()    { return count($this->BroDatOsA); }          # Returns the number of BroDats
 #public function NumStartDiMes() { return $this->NumPMemsA[BroDatT_Start]; }  # Returns the number of BroDatT_Start PMem BroDats = truthy re the Bro having Start PMem BroDats == HasStartDiMes
 #public function NumEndDiMes()   { return $this->NumPMemsA[BroDatT_End]; }    # Returns the number of BroDatT_End   PMem BroDats = truthy re the Bro having End   PMem BroDats == HasEndDiMes
  public function HasPrimary()    { return $this->Bits & BroCB_Primary; }    # Returns non-zero (BroCB_Primary) if the Bro includes Primary data
  public function HasPya()        { return $this->Bits & BroCB_Pya; }        # Returns non-zero (BroCB_Pya) if the Bro includes Pya data
  public function HasPosting()    { return ($this->Bits & BroCB_Posting) && !$this->IsSlave(); } # Returns true if the Bro includes Posting data and is not a Slave


  # Bro::DefaultPMemId() Returns the Bro's default PMemId to use when changing a Summing Bro Base BroDat to a PMem BroDat = the Mandatory/Default PMem if there is one, and first one of list if there is a list, o'wise PMemId_Unallocated.
  public function DefaultPMemId() {
    if (($broDiMesA = $this->InfoA[BroI_PMemDiMesA]) && ($broDiMesA[II_MandatsA ] || $broDiMesA[II_DefaultsA]))
      # Bro has Mandatory/Default PMems
      return $broDiMesA[II_MandatsA ] ? $broDiMesA[II_MandatsA ][0] : $broDiMesA[II_DefaultsA][0]; # use first one in each case
    # else
    return PMemId_Unallocated;
  }

  # Bro::__toString()
  public function __toString() {
    $s = "Bro $this->BroId PostType ".$this->PostTypeStr().($this->IsEle() ? ', Element' : ', Set');
    if ($this->IsSumming())   $s .= ', Summing';
    if ($this->IsStartEnd())  $s .= ', StartEnd';
    if ($this->IsMaster())    $s .= ', Master';
    if ($this->IsSlave())     $s .= ', Slave';
    if ($this->BaseDatOsA[BroDatT_End])  $s .= ', has Base';
    if ($this->BaseDatOsA[BroDatT_Start]) $s .= ', has Start Base';
    $s .= ', has '.NumPluralWord(count($this->BroDatOsA), 'BroDat');
    if ($this->NumPMemsA[BroDatT_End])   $s .= ', has '.NumPluralWord($this->NumPMemsA[BroDatT_End], 'PMem');
    if ($this->NumPMemsA[BroDatT_Start]) $s .= ', has '.NumPluralWord($this->NumPMemsA[BroDatT_Start], 'Start PMem');
    if ($this->HasPrimary())  $s .= ', includes Primary data';
    return $s;
  }

  # Bro::BroRef* and ShortName fns
  public function BroRef()       { return $this->BroId; }
  public function BroRefSrce()   { return BroName($this->BroId); }
  public function BroRefFull()   { return "$this->BroId ".BroName($this->BroId); }
  public function BroShortName() { return BroShortName($this->BroId); } # Bro ShortName if defined

  # Bro::BroDatTypeStr($broDatType) to obtain a BroDatTypeStr to be i.e. before the BroDat has been constructed for error msg purposes.
  public function BroDatTypeStr($broDatType) { return BroDatTypeStr($broDatType); }

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
      case DT_MoneyString: if (InStr('|', $dat)) { $tA = explode('|', $dat); $dat = number_format($tA[0])."|$tA[1]"; break; }
                       # else fall thru when there is no text segment as for a SumBase (no string part) or if the BroRef did not use an Ee or Ei Property Member in which case the string is optional
      default:         $dat = number_format($dat); break;
    }
    return "<td class=r>$dat</td>";
  }

  # Bro::*Base() Returns the *Base DatO or false if not defined
  public function StartBase() { return $this->BaseDatOsA[BroDatT_Start] ? $this->BaseDatOsA[BroDatT_Start]->Dat : false; }
  public function EndBase()   { return $this->BaseDatOsA[BroDatT_End]   ? $this->BaseDatOsA[BroDatT_End]->Dat : false; }

  # Bro::AllBroDatOs()
  # Returns [BroDatKey => BroDatO] for all the BroDat objects or an empty array if none tho there will only be none if a Bro was created wo data and no add data call was made.
  public function AllBroDatOs() {
    return $this->BroDatOsA; # [BroDatKey -> BroDatO]
  }

  #####################################################################
  # The following *Dat*s Functions are applicable to Summing Bros only #
  #####################################################################

  # Bro::PrimaryPMemBroDatAs()
  # Returns [BroDatKey => [0 => Bal, 1 => PMemRefsA]] for all the Primary PMem BroDats i.e. incl Start ones
  public function PrimaryPMemBroDatAs() {
    $datOsA = [];
    # Thru all the BroDats, selecting Primary PMem ones
    foreach ($this->BroDatOsA as $broDatKey => $datO) {
      if ($datO->IsPrimary() && $datO->IsPMem())
        $datOsA[$broDatKey] = [$datO->Dat, $datO->PMemRefsA]; # [BroDatKey => [0 => Bal, 1 => PMemRefsA]]
    }
    return $datOsA;
  }

  # Bro::StartPrimaryBroDatAs()()
  # Returns [BroDatKey => [0 => Bal, 1 => SrceN]] for the Start Primary BroDats, only Pya BroDats when in a Pya year
  public function StartPrimaryBroDatAs() {
    $datAsA = [];
    foreach ($this->BroDatOsA as $broDatKey => $datO)
      if ($datO->IsStart() && $datO->IsPrimary() && (!self::$IsPyaYearB || $datO->IsPya()))
        $datAsA[$broDatKey] = [$datO->Dat, $datO->SrceN]; # [BroDatKey => [0 => Bal, 1 => SrceN]]
    return $datAsA;
  }

  # Bro::EndPrimaryBroDatOs()
  # Returns [BroDatKey => BroDatO] for the End Primary BroDats
  public function EndPrimaryBroDatOs() {
    $datOsA = [];
    foreach ($this->BroDatOsA as $broDatKey => $datO)
      if ($datO->IsEnd() && $datO->IsPrimary())
        $datOsA[$broDatKey] = $datO;
    return $datOsA;
  }

  # Bro::EndPrimaryBroDatAs()
  # Returns [BroDatKey => Bal] for the End Primary BroDats, only Pya BroDats when in a Pya year
  public function EndPrimaryBroDatAs() {
    $datAsA = [];
    foreach ($this->BroDatOsA as $broDatKey => $datO)
      if ($datO->IsEnd() && $datO->IsPrimary() && (!self::$IsPyaYearB || $datO->IsPya()))
        $datAsA[$broDatKey] = $datO->Dat; # [BroDatKey => Bal]
    return $datAsA;
  }

  # Bro::SummingBroDatAs()
  # Returns [BroDatKey => [0 => Bal, 1 => SrceN]] for the Primary BroDats, and only Pya BroDats when in a Pya year
  # Should only be called for a Non-StartEnd Summing Bro.
  public function SummingBroDatAs() {
    $datAsA = [];
    foreach ($this->BroDatOsA as $broDatKey => $datO)
      if ($datO->IsPrimary() && (!self::$IsPyaYearB || $datO->IsPya()))
        $datAsA[$broDatKey] = [(int)$datO->Dat, $datO->SrceN]; # [BroDatKey => [0 => Bal, 1 => SrceN]] cast of Dat so as to skip the test part if for a DT_MoneyString being summed
    return $datAsA;
  }

  # Bro::PostingBroDatOs()
  # Returns [BroDatKey => BroDatO] for the Posting BroDats
  public function PostingBroDatOs() {
    $datOsA = [];
    foreach ($this->BroDatOsA as $broDatKey => $datO)
      if ($datO->IsPosting())
        $datOsA[$broDatKey] = $datO;
    return $datOsA;
  }

  # Bro::Copy()  Copies this Bro to a new Bro and returns the new BrO. Use of php clone didn't work - too much recursion.
  public function Copy() {
    $cpyBrO = new Bro($this->BroId);
    foreach ($this->BroDatOsA as $broDatKey => $datO) {
      $broDatType = $datO->BroDatType;
      $srceN      = $datO->SrceN;
      $cpyDatO = new BroDat($cpyBrO, $broDatType, $datO->Dat, $srceN, $datO->PMemRefsA);
      $cpyDatO->Bits = $datO->Bits;
      $cpyDatO->Dat2 = $datO->Dat2;
      if ($cpyDatO->BroDatKey !== $broDatKey) die("Die in Copy with cpyDatO->BroDatKey |$cpyDatO->BroDatKey| !== broDatKey |$broDatKey|");
      $cpyBrO->BroDatOsA[$broDatKey] = $cpyDatO;
      if (!$datO->PMemRefsA && ($srceN === BroDatSrce_b || !$cpyBrO->BaseDatOsA[$broDatType]))
        $cpyBrO->BaseDatOsA[$broDatType] = $cpyDatO;
    }
    $cpyBrO->Bits      = $this->Bits;
    $cpyBrO->NumPMemsA = $this->NumPMemsA;
    ++self::$Changes;
    return $cpyBrO;
  }

  # Bro::CopyToSlave($slaveBrO)
  # Copies this Bro which should be a Master Bro to $slaveBroO. Is never called for Set Slaves.
  # Returns $slaveBro
  public function CopyToSlave($slaveBrO) {
    global $DiMesA;
    if ($filtering = ($slaveBrO->Bits & BroB_SFilter)) {
      # Slave Filtering applies.
      $slaveUsableDims = $slaveBrO->InfoA[BroI_BroUsablePropDims]; # 0 if no Usable Dims filtering
      if ($broDiMesA = $slaveBrO->InfoA[BroI_PMemDiMesA]) {
        $diMeExcludesA = $broDiMesA[II_ExcludesA];
        $diMeAllowsA   = $broDiMesA[II_AllowsA];
      }
    }
    foreach ($this->BroDatOsA as $broDatKey => $datO) {
      if ($filtering && $datO->PMemRefsA)
        # Filtering applies and the BroDat has PMems
        foreach ($datO->PMemRefsA as $pMemId)
          if (($slaveUsableDims && !InChrList($DiMesA[$pMemId][PMemI_PropId], $slaveUsableDims)) || # UsableDims filtering
              ($broDiMesA && (($diMeExcludesA && in_array($pMemId, $diMeExcludesA)) || ($diMeAllowsA && !in_array($pMemId, $diMeAllowsA))))) # PMems filtering
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
          ++self::$Changes;
        }
      }else{
        $cpyDatO = new BroDat($slaveBrO, $broDatType, $datO->Dat, $srceN, $datO->PMemRefsA);
        $cpyDatO->Bits = $datO->Bits;
        $cpyDatO->Dat2 = $datO->Dat2;
        $slaveBrO->BroDatOsA[$broDatKey] = $cpyDatO;
        if (!$datO->PMemRefsA && ($srceN === BroDatSrce_b || !isset($cpyBrO->BaseDatOsA[$broDatType])))
          $slaveBrO->BaseDatOsA[$broDatType] = $cpyDatO;
        ++self::$Changes;
      }
    }
    $slaveBrO->Bits      = $this->Bits;
    $slaveBrO->NumPMemsA = $this->NumPMemsA;
    return $slaveBrO;
  }

  # Bro::PMemSumming($stdYearBrO=null)
  # Performs Intermediate PMem, PMem Summing, and Pya PMem Summing, inserting PyaOA BroDats if necessary using $stdYearBrO,
  #  with $stdYearBrO passed as false if inserting PyaOA BroDats is to be skipped as for a Bro being restored from string
  public function PMemSumming($stdYearBrO=null) {
    global $PMemTargetsA, $RestatedPMemTargetsA;

    # Intermediate PMems
    # This Intermediate PMem op should be performed on a Bro only once because of the BroAddDataOp_Sum ops.
    # This should be the case as PMemSumming() is either called when the Bro is re-created on reading from the DB, or as part of summing, not both.
    # Through the Primary PMem BroDats
    foreach ($this->PrimaryPMemBroDatAs() as $broDatKey => $datA) { # [BroDatKey => [0 => Bal, 1 => PMemRefsA]] for all the Primary PMem BroDats i.e. incl Start ones
      if (($n = count($datA[1])) > 1) {
        #                     j pMemRef 1st add       2nd add
        # xxxx,162,177     => 0 162     -             xxxx,162
        #                     1 177     -             xxxx,177
        # xxxx,162,177,186 => 0 162     -             xxxx,162
        #                     1 177     xxxx,162,177  xxxx,177
        #                     2 186     -             xxxx,186
        $bal = $datA[0]; # won't be false, could be 0
        $iPMemRefsA = [];
        $lastj      = $n - 1;
        foreach ($datA[1] as $j => $pMemRef) {
          $iPMemRefsA[] = $pMemRef; # tho $iPMemRefsA is never used for the n==2 case
          if ($j && $j < $lastj) {
            # is a PMem along the way to the full primary entry but not the first one which is added belwo
            DebugMsg("Intermediate PMem $pMemRef for $this->BroId,".implode(COM,$iPMemRefsA)." Bal=$bal");
            $this((int)$broDatKey, $bal, BroDatSrce_i, $iPMemRefsA, BroAddDataOp_Sum); # add data ($broDatTypeOrBroDatKey, $dat, $srceN, $pMemRefOrPMemRefsA=0, $op=BroAddDataOp_Unique)
          }
          DebugMsg("Intermediate PMem for $this->BroId,$pMemRef Bal=$bal");
          $this((int)$broDatKey, $bal, BroDatSrce_i, $pMemRef, BroAddDataOp_Sum); # add data ($broDatTypeOrBroDatKey, $dat, $srceN, $pMemRefOrPMemRefsA=0, $op=BroAddDataOp_Unique)
        }
      }
    }

    # PMem Summing  djh?? Combine with above loop?
    $sumA = []; # [BroDatKey => Bal]
    foreach ($this->PrimaryPMemBroDatAs() as $broDatKey => $datA) { # [BroDatKey => [0 => Bal, 1 => PMemRefsA]] for all the PMem BroDats i.e. incl Start ones
      if ($bal = $datA[0]) { # won't be false, could be 0
        $pMemRefsA = $datA[1];
        $n = count($pMemRefsA);
        $iPMemRefsA = [];
        foreach ($pMemRefsA as $j => $pMemRef) { # 557; 557, 75; 556 etc  $PMemTargetsA excludes the Restated PMems but 75 & 76 are added here if a prior PMemId is in $PMemTargetsA e.g. 557 => 556
          $iPMemRefsA[] = $pMemRef; # djh?? Case of pMemRef a string?
          if (isset($PMemTargetsA[$pMemRef])) # $PMemTargetsA [PMemId => [target PMemIds]] excludes the Restated PMems
            foreach ($PMemTargetsA[$pMemRef] as $tarPMemId) {
              $tarPMemIdsA = $iPMemRefsA;
              $tarPMemIdsA[$j] = $tarPMemId;
              for ($k = $j; $k<$n; ++$k) { # e.g. for 2040,160,185 as BroDat -> d for 2040,159 and 2040,159,185
                if ($k>$j) $tarPMemIdsA[] = $pMemRefsA[$k];
                $broDatKey = substr($broDatKey,0,2).implode(COM, $tarPMemIdsA);
                isset($sumA[$broDatKey]) ? $sumA[$broDatKey] += $bal : $sumA[$broDatKey] = $bal;
              }
            }
        }
      }
    }
    foreach ($sumA as $broDatKey => $bal) {
      DebugMsg("PMem Summing for $this->BroId,$broDatKey Bal=$bal");
      $this($broDatKey, $bal, BroDatSrce_d, 0, BroAddDataOp_Replace); # add data ($broDatTypeOrBroDatKey, $dat, $srceN, $pMemRefOrPMemRefsA=0, $op=BroAddDataOp_Unique)
   }

    if ($this->HasPya()) {
      # Restated special case handling.
      # 110 - Dimension - Restatements [3]                 Dimension Members
      #   [A] 4245 Restated amount                         73  Restated.Amount               PMemId_PyaAmount
      #     [A] 3965 Prior period increase (decrease)      74  Restated.PriorPeriodIncr      PMemId_PyaPriorPeriodIncr
      #       [A] 50 Accounting policy increase (decrease) 75  Restated.AccountingPolicyIncr PMemId_PyaAcctPolicyIncr
      #       [A] 3178 Material error increase (decrease)  76  Restated.MaterialErrorIncr    PMemId_PyaMaterialErrIncr
      #     [A] 3463 Original amount [default]             77  Restated [OriginalAmount]     PMemId_PyaOriginalAmount

      # Add the 77 PMemId_PyaOriginalAmount BroDat for each Pya BroDat, with original values from $stdYearBrO which is null if undefined
      # It should be possible to avoid the need for this routine if the BroDatSrce_r BroDats were added during Intermediate PMem summing and PMem summing.
      # An attempt was made on 29.11.12 (intermediate PMems only) in ClassBroWipAJ.inc and ImportWipAJ.php, but it was incomplete and needed the PMem summing cases to be added.
      # Also PMem summing of already added 77s went wrong and would have needed to be suppressed. As all this could have complicated restoring such Bros from string, I stayed with this less than ideal method.
      if ($stdYearBrO !== false) # Passed as false if inserting PyaOA BroDats is to be skipped as for a Bro being restored from string
      foreach ($this->BroDatOsA as $broDatKey => $datO)
        if ($datO->IsPya() && !$datO->IsPyaOAA()) { # BroDat is a Pya one which means that it includes PMemId = 75 or 76, PMemId_PyaAcctPolicyIncr or PMemId_PyaMaterialErrIncr, and the corresponding 77 PMemId_PyaOriginalAmount BroDat has not been added.
          # Add the appropriate 77 PMemId_PyaOriginalAmount BroDat
          # As the Pya posting has been made, there will be a SumBase
          $broDatType = $datO->BroDatType;
          $pMemRefsA   = $datO->PMemRefsA;
          # 9065,557,75  create 9065,557,77 with original value of 9065,557 or create 9065,557,77 of 0 if none
          # 9065,75      create 9065,77     with original value of 9065
          if (count($pMemRefsA) > 1) {
            # 9065,557,75  create 9065,557,77 with original value of 9065,557 or create 9065,557,77 of 0 if none
            array_pop($pMemRefsA); # pop off the 75 e.g. [557]
            $broDatKey  = BuildBroDatKey($broDatType, $pMemRefsA); # 557
            $pMemRefsA[] = PMemId_PyaOriginalAmount; # push on 77
          }else{
            # 9065,75  create 9065,77 with original value of 9065
            $broDatKey = $broDatType; # BuildBroDatKey($broDatType, 0); # Base
            $pMemRefsA  = [PMemId_PyaOriginalAmount];
          }
          if ($stdYearBrO && isset($stdYearBrO->BroDatOsA[$broDatKey])) { # 6136,211,T.1 or 6376,T.1 or 9065,557 or 9065
            $dat2O = $stdYearBrO->BroDatOsA[$broDatKey];
            $dat = $dat2O->Dat;
          }else # no Std Year BroDat so add a zero 77 one
            $dat = 0;
          DebugMsg("AddPyaOriginalAmountBroDats to Bro $this->BroId,$broDatKey for 77 Bal=$dat");
          $this->NewBroDat($broDatType, $dat, BroDatSrce_r, $pMemRefsA); # NewBroDat($broDatType, $dat, $srceN, $pMemRefsA=0)
          $datO->Bits |= BroCB_PyaOAA; # to record that it has been done
        }

      # Do the Pya PMem Summing
      # PMem summing for Restated
      # $RestatedPMemTargetsA=[
      # 75=>[74,73],
      # 76=>[74,73],
      # 77=>[73]];
      $sumA = []; # [BroDatKey => [Bal, BroDatType, PMemRefsA]
      # Thru all the BroDats processing only the Pya and PysOA ones
      foreach ($this->BroDatOsA as $broDatKey => $datO) {
        if ($datO->IsPya() || $datO->IsPyaOA()) {
          $brodatType = $datO->BroDatType;
          $bal        = $datO->Dat2 ? : $datO->Dat;
          $pMemRefsA   = $datO->PMemRefsA;
          foreach ($pMemRefsA as $j => $pMemRef) {
            if (isset($RestatedPMemTargetsA[$pMemRef])) # $RestatedPMemTargetsA [PMemId => [target PMemIds]]  Only 75, 76, 77
              foreach ($RestatedPMemTargetsA[$pMemRef] as $tarPMemId) {
                $tarPMemIdsA     = $pMemRefsA;
                $tarPMemIdsA[$j] = $tarPMemId;
                $broDatKey = BuildBroDatKey($brodatType, $inst, $tarPMemIdsA);
                isset($sumA[$broDatKey]) ? $sumA[$broDatKey][0] += $bal : $sumA[$broDatKey] = [$bal, $brodatType, $tarPMemIdsA]; # [Bal, BroDatType, PMemRefsA]
                DebugMsg("Pya PMem Summing prep for $this->BroId,$broDatKey Bal=$bal -> {$sumA[$broDatKey][0]}");
              }
          }
        }
      }
      foreach ($sumA as $broDatKey => $datA) { # [Bal, BroDatType, PMemRefsA]
        DebugMsg("Pya PMem Summing for $this->BroId,$broDatKey Bal={$datA[0]}}");
        $this($datA[1], $datA[0], BroDatSrce_d, $datA[1], BroAddDataOp_Replace); # add data ($broDatTypeOrBroDatKey, $dat, $srceN, $pMemRefOrPMemRefsA=0, $op=BroAddDataOp_Unique)
      }
    } # End HasPya
  }

  # Bro::Stringify()
  # ~~~~~~~~~~~~~~~~
  # Returns the string form of the Bro for DB storage. Gives a much shorter string than serialize or json_encode
  # String form:
  #    SrceN DatType {PMemRef D2} Dat {D1 SrceN DatType {PMemRef D2} Dat ....} for BroDats with SrceN as below
  # The coded string does not include BroId on the assumption that the BroId is stored anyway either in the DB or as an array index.
  # BroId needs to be passed to NewBroFromString() as well as the string built here to recreate the Bro i.e. $brO = NewBroFromString($broId, $broStr)
  /*const BroDatSrce_P  =  1; # * Posting
    const BroDatSrce_PE =  2; # * Prior year End
    const BroDatSrce_SE =  3; # * SumEnd
    const BroDatSrce_S  =  4; # * Summed
    const BroDatSrce_R  =  5; # * Restated - the Original Amount PMem when included in the BaseSum of the Bro e.g. when Base only restated
    const BroDatSrce_r  =  6; # * Restated - the Original Amount PMem when not included in the BaseSum of the Bro e.g. when other PMems are in use -> Base sum, and this Original Amount is for info purposes only.
    const BroDatSrce_e  =  7; # * dErived or dEduced
    const BroDatSrce_b  =  8; #   BaseSum = sum of the Primary PMems
    const BroDatSrce_d  =  9; #   PMem summing
    const BroDatSrce_i  = 10; #   Intermediate PMem which can be stand alone or impact a primary summing BroDat -> different view bal from primary bal
                              # |- * = included in stringing */
  public function Stringify() {
    if ($this->IsEleSlave()) return null; # Not expected to be called for an Ele Slave which can be recreated from its Master
    $A = [];
    foreach ($this->BroDatOsA as $broDatKey => $datO)
      if ($datO->SrceN <= BroDatSrce_e)
        $A[] = $datO->Stringify();
    return implode(D1, $A);
  }

} # end of Bro::


##########
# BroDat # Bro Data to hold data for a Bro
##########
# Should only be instantiated via class Bro. How can a direct new be prevented? Using Interface? djh??
#
class BroDat {
  public $Dat, $SrceN, $Dat2, # the data, with Dat2 set to Dat in the case of a Primary Summing BroDat, as the value which could be varied via an intermediate PMem. Summing uses Dat. Viewing uses Dat2
    $BroDatType, $BroDatKey, $Bits,
    $PMemRefsA, # The PMemIds of the BroDat, null if none
    $DadBrO;   # Reference to Bro that this BroDat is holding data for, though not a real Dad in the parent class sense.

  # BroDat::constructor
  public function __construct($dadBrO, $broDatType, $dat, $srceN, $pMemRefsA) {
    DebugMsg("BroDat::constructor Bro $dadBrO->BroId broDatType=$broDatType, dat=$dat, srceN=$srceN, pMemRefsA=".ArrayOrIntToStr($pMemRefsA)."; Changes before=".Bro::Changes());
    $this->DadBrO = $dadBrO;
    $this->Dat    = $dat;
    $this->SrceN  = $srceN;
    $this->BroDatType = $broDatType;
    if (IsPrimary($srceN)) # BroDatSrce_P  P Posting, BroDatSrce_PE PE Prior year End, BroDatSrce_SE SE SumEnd, BroDatSrce_S  S Summed, BroDatSrce_R R Restated
      $this->Bits = BroCB_Primary | ($srceN === BroDatSrce_P ? BroCB_Posting : 0);
    else
      $this->Bits = 0;
    if ($pMemRefsA) {
      $this->PMemRefsA = $pMemRefsA; # expect $pMemRefsA to be passed
      $this->BroDatKey = "$broDatType,".implode(COM, $pMemRefsA);
      if ($this->IsPrimary() && $dadBrO->IsSumming()) {
        $this->Dat2 = $dat;
        DebugMsg($this->BroRef()." Dat2 set to $dat");
      }
      if (($pyaDiMeId=end($pMemRefsA)) === PMemId_PyaAcctPolicyIncr || $pyaDiMeId === PMemId_PyaMaterialErrIncr) # The Pya PMemId is the last PMem in $pMemRefsA if present
        $this->Bits |= BroCB_Pya;
      if ($pyaDiMeId === PMemId_PyaOriginalAmount)
        $this->Bits |= BroCB_PyaOA;
    }else
      $this->BroDatKey = $broDatType;  # No PMemRefsA
    DebugMsg("BroDat::constructor Bits=$this->Bits, IsPrimary()=".$this->IsPrimary()." IsSumming=".$dadBrO->IsSumming());
  }

  # BroDat::Is*
  public function IsPrimary()   { return $this->Bits & BroCB_Primary; } # Returns non-zero (BroCB_Primary) if the BroDat is a Primary one
  public function IsPosting()   { return $this->Bits & BroCB_Posting; } # Returns non-zero (BroCB_Posting) if the BroDat is a Posting one. Could use $this->ScreN === BroDatSrce_P
  public function IsPya()       { return $this->Bits & BroCB_Pya; }     # Returns non-zero (BroCB_Pya)     if the BroDat is a Pya one which means that it includes PMemId = 75 or 76, PMemId_PyaAcctPolicyIncr or PMemId_PyaMaterialErrIncr
  public function IsPyaOA()     { return $this->Bits & BroCB_PyaOA; }   # Returns non-zero (BroCB_PyaOA)   if the BroDat is an Original Amount 77 PMemId_PyaOriginalAmount one
  public function IsPyaOAA()    { return $this->Bits & BroCB_PyaOAA; }  # Returns non-zero (BroCB_PyaOAA)  if the Original Amount 77 PMemId_PyaOriginalAmount BroDat has been Added, corresponding to this Pya (BroCB_Pya) BroDat
  public function IsEnd()       { return $this->BroDatType === BroDatT_End; }
  public function IsStart()     { return $this->BroDatType === BroDatT_Start; }
  public function IsSumBase()   { return $this->SrceN === BroDatSrce_b; }
  public function IsPMem(){ return $this->PMemRefsA; } # either a Start PMem or an End PMem

  # BroDat::BroRef*
  public function BroRef() {
    $ref = $this->DadBrO->BroId;
    if ($this->IsStart())
      $ref .= 's';
    if ($this->PMemRefsA)
      $ref .= COM.str_replace(':', DOT, implode(COM, $this->PMemRefsA)); # with PMemId:RefId -> PMemId.RefId
    return $ref;
  }
  public function BroRefSrce() {
    $ref  = BroName($this->DadBrO->BroId);
    if ($this->IsStart())
      $ref .= ',Start';
    if ($this->PMemRefsA)
      $ref .= PMemRefSrce($this->PMemRefsA);
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
    static $sourcesA = [0, 'P', 'PE', 'SE', 'S', 'R', 'r', 'e', 'b', 'd', 'i'];
    # Prefix source with 'm' for Master as the source if this is a Slave; append 't' or 'i' if is Summing Bro and Dat2 !== Dat which means there have been 't' or 'i' postings to change Dat2
    return ($this->DadBrO->IsEleSlave() ? 'm' : '').$sourcesA[$srceN ? : $this->SrceN].($this->Dat2 && $this->Dat2 !== $this->Dat ? 'i' : '');
  }

  # BroDat::BroDatTypeStr()
 #public function BroDatTypeStr() { return BroDatTypeStr($this->BroDatType, false); }
  public function BroDatTypeStr() { return BroDatTypeStr($this->BroDatType); }

  # BroDat::Stringify()
  # Returns string for a BroDat in the form used for DB storage:
  #    SrceN DatType {PMemRef D2} Dat
  # where Dat is the Primary value in the case of BroDat whose Dat has been modified by an intermediate PMem
  # Without the leading SrceN for Trans BroDats that are all BroDatSrce_P (Posting) type.
  # and Dat of  for a deleted tran BroDat. (Created by Import.php.) Only applicable to Data Trail, not full Bro storage and retrieval.
  public function Stringify() {
    return is_int($this->BroDatKey) ? $this->SrceN.$this->BroDatType.$this->Dat : $this->SrceN.$this->BroDatType.substr($this->BroDatKey,2).D2.$this->Dat; # SrceN DatType {PMemRef D2} Dat
  }

  # BroDat::IsEqual($datO) Returns boolean for this BroDat == $datO
  # BroDats can't be compared directly in php code because of a recursion failure due to the DadBrOs, so this fn does by comparing the data.
  # Is expected to be called only for BroDats with same BroDatKey for same BroId
  public function IsEqual($datO) {
    return $this->Dat === $datO->Dat && $this->SrceN === $datO->SrceN && $this->Dat2 === $datO->Dat2; # && $this->BroDatKey === $datO->BroDatKey && $this->DadBrO->BroId === $datO->DadBrO->BroId ;
  }

} # end of class BroDat


####################
# Helper Functions #
####################

# NewBroFromString($broId, $broStr)
# ---------------------------------
# Returns a Bro object created from a Bro string
#   SrceN DatType {PMemRef D2} Dat {D1 SrceN DatType {PMemRef D2} Dat ....}
# for BroDats where:
/*const BroDatSrce_P  =  1; # * Posting
  const BroDatSrce_PE =  2; # * Prior year End
  const BroDatSrce_SE =  3; # * SumEnd
  const BroDatSrce_S  =  4; # * Summed
  const BroDatSrce_R  =  5; # * Restated - the Original Amount PMem when included in the BaseSum of the Bro e.g. when Base only restated
  const BroDatSrce_r  =  6; # * Restated - the Original Amount PMem when not included in the BaseSum of the Bro e.g. when other PMems are in use -> Base sum, and this Original Amount is for info purposes only.
  const BroDatSrce_e  =  7; # * dErived or dEduced
  const BroDatSrce_b  =  8; #   BaseSum = sum of the Primary PMems
  const BroDatSrce_d  =  9; #   PMem summing
  const BroDatSrce_i  = 10; #   Intermediate PMem which can be stand alone or impact a primary summing BroDat -> different view bal from primary bal
                            # |- * = included in stringing */

function NewBroFromString($broId, $broStr) {
  $brO = new Bro($broId);
 #foreach (array_reverse(explode(D1, $broStr)) as $a) { # array_reverse() to do PMem ones first before possible issues for 75 creating 77.
  foreach (explode(D1, $broStr) as $a) {
    # SrceN DatType {PMemRef D2} Dat {D1 SrceN DatType {PMemRef D2} Dat ....}
    $broDatType = (int)$a[1];
    $dat = substr($a, 2);
    $dA = explode(D2, $dat);
    if (count($dA)===2) {
      $pMemRefsA = PMemRefToA($dA[0]);
      $dat = $dA[1];
    }else
      $pMemRefsA = 0; # with $dat already set
    if (is_numeric($dat)) $dat = (int)$dat;
    $brO($broDatType, $dat, (int)$a[0], $pMemRefsA); # add data ($broDatTypeOrBroDatKey, $dat, $srceN, $pMemRefOrPMemRefsA=0, $op=BroAddDataOp_Unique)
  }
  if ($brO->IsSumming()) {
    if ($brO->HasPya()) {
      # Set the PyaOAA bits
      $broDatOsA = $brO->AllBroDatOs();
      foreach ($broDatOsA as $broDatKey => $datO)
        if ($datO->IsPya()) {
          if ($datO->IsPyaOAA()) die("Die - IsPyaOAA() already set for Pya BroDat in NewBroFromString for Bro $broId, broDatKey=$broDatKey");
          # BroDat is a Pya one which means that it includes PMemId = 75 or 76, PMemId_PyaAcctPolicyIncr or PMemId_PyaMaterialErrIncr
          # The corresponding 77 PMemId_PyaOriginalAmount BroDat should be present. Check and set the bit.
          $broDatKey77 = substr($broDatKey, 0, -2).PMemId_PyaOriginalAmount; # replace the final 75 or 76 by 77
          if (isset($broDatOsA[$broDatKey77])) {
            $datO->Bits |= BroCB_PyaOAA;
            DebugMsg("In NewBroFromString for Bro $broId, PyaOriginalAmount Added bit set for key=$broDatKey");
          }else
            die("Die - In NewBroFromString for Bro $broId, PyaOriginalAmount BroDat key=$broDatKey77 not present as expected");
        }
    }
    $brO->PMemSumming(false); # false to skip Original Amount 77 creation as these BroDats are stored by Stringify()
  }
  return $brO;
}

# BuildBroDatKey($broDatType, $pMemRefsA=0)
# ----------------------------------------
# Returns a BroDatKey for $broDatType, $pMemRefsA
# Case  BroDatKey
# Start 1{,PMemRef} Start Base (no PMemRef) and PMem value for a StartEnd Bro shown externally as {BroId}s or {BroId}s,PMemRef e.g. 1234s or 1234s,145
# End   2{,PMemRef} End or Normal value for a Bro, Base (no PMemRef) and PMem value, shown externally as BroId or BrodId,PMemRef e.g. 1234,145,185{,T.x}
# where the 1,2 in the BroDatKey are the constants BroDatT_Start, BroDatT_End
function BuildBroDatKey($broDatType, $pMemRefsA=0) {
  if ($pMemRefsA) {
    $key = "$broDatType,".implode(COM, $pMemRefsA);
  }else
    $key = $broDatType;
  return $key;
}

# AdjustBroDatKey(&$broDatKey, $adj)
# ----------------------------------
# Called to adjust the Bro BroDatType part of a Bro BroDatKey by $adj (+ or -ve)
# e.g. +BroDatT_Start (1) to change Start to End
#      -BroDatT_Start (1) to change End to Start
function AdjustBroDatKey(&$broDatKey, $adj) {
  if (is_int($broDatKey)) # BroDatKey is just the BroDatType
    $broDatKey += $adj;
  else # string BroDatKey with BroDatType as first character
    $broDatKey[0] = chr(ord($broDatKey[0]) + $adj);
}

# UnpackBroDatKey($broDatKey) Called by DataTrail.php
# ---------------------------
# Case  BroDatKey
# Start 1{,PMemRef}
# End   2{,PMemRef}
# Returns [$broDatType, $pMemRefsA] for use: list($broDatType, $pMemRefsA) = UnpackBroDatKey($broDatKey);
function UnpackBroDatKey($broDatKey) {
  # Base Key 2
  # PMem key 2,148
  if (is_int($broDatKey)) # Base key of BroDatType
    return [$broDatKey, 0];
  $pMemRefsA = PMemRefToA($broDatKey);
  $broDatType = array_shift($pMemRefsA);
  return [$broDatType, $pMemRefsA];
}

function IsEndBroDatKey($broDatKey) {
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

function PMemName($pMemId) {
  global $PMemNamesA;
  return $PMemNamesA[$pMemId];
}

# PMemRefSrce($pMemRefsA)  Returns source reference for the PMem defined by $pMemRefsA
# djh?? Compact this code
function PMemRefSrce($pMemRefsA) {
  # Dump('pMemRefsA',$pMemRefsA);
  $ref = $prevProp = '';
  if ($pMemRefsA)
    foreach ($pMemRefsA as $pMemRef) {
      if (is_string($pMemRef)) {
        # pMemId.hashId
        $pMemId   = (int)$pMemRef;
        $pMemName = PMemName($pMemId).HashRef($pMemId, (int)substr(strrchr($pMemRef, ':'), 1));
      }else
        $pMemName = PMemName($pMemRef);
      list($prop) = explode(DOT, $pMemName);
      if ($prop === $prevProp)
        $pMemName = substr($pMemName, strlen($prop)+1);
      else
        $prevProp = $prop;
      $ref .= ", $pMemName";
    }
  return $ref;
}

function HashRef($pMemId, $refId) {
  global $DB, $PMemsA, $EntityId;
  return $DB->StrOneQuery("Select Ref from %s T Join Refs R On R.TableId=T.Id Where R.EntityId=$EntityId And R.RefId=$refId", $PMemsA[$pMemId][PMemI_RefTableN]);
}

function IsPrimary($srceN) {
  switch ($srceN) {
    case BroDatSrce_P:  #  P Posting
    case BroDatSrce_PE: # PE Prior year End
    case BroDatSrce_SE: # SE SumEnd
    case BroDatSrce_S:  #  S Summed
    case BroDatSrce_R:  #  R Restated - the Original Amount PMem when included in sum for BaseSum of the Bro e.g. when Base only restated
      return true;
   #case BroDatSrce_r:  #  r Restated - the Original Amount PMem when not included in sum for BaseSum of the Bro e.g. when other PMems are in use -> Base sum, and this Original Amount is for info purposes only.
   #case BroDatSrce_e:  #  e Derived or Deduced
   #case BroDatSrce_b:  #  b Base = sum of PMems
   #case BroDatSrce_d:  #  d PMem summing
   #case BroDatSrce_i : #  i intermediate PMem
  }
  return false;
}

function BroDatTypeStr($broDatType) {
  static $typesA = [0, 'Start', 'End'];
  return $typesA[$broDatType].' BroDat';
}


# PMemRefToA($csList)
# Returns a PMemRef CS string array with the pure PMemIds as ints, and the PMemId.RefId values as strings
function PMemRefToA($csList) {
  $aA = explode(COM, $csList);
  foreach ($aA as $i => $v)
   #if (strpos($v, DOT) === false)
    if (is_numeric($v))
      $aA[$i] = (int)$v;
  return $aA;
}
