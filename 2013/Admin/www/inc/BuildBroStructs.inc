<?php /* Copyright 2011-2013 Braiins Ltd

Admin /inc/BuildBroStructs.inc

Inc for building the BRL Bro structs.

Required by BuildBroStructs.php and BrosImport.php

History:
30.03.13 Started based on UK-GAAP-DPL BuildStructs.inc

ToDo djh??
----
- Add deduction of contextN - BroI_ContextN

- Fix RgRT.inc

- Fix the summing structs

- Fix the constants check

- Build StartEnd SumLists from Set structure to provide the equivalent of the old SumLists?

- Add Headings?

Needs:

Com_Inc_Tx.ConstantsTx.inc   /- Already included by both calling modules
Com_Inc_Tx.ConstantsRg.inc   |
Com_Inc_Tx.DiMesA.inc        |
*/


/*#################
# The Bro Structs # Arrays actually. All are by BroSet where just MaIn-BroSets have structs.
###################

Both CoA and Out-BroSets
-----------------------
$BroInfoA       array of BroInfo Info BroId => [BroI_Bits, ...]
$BroNamesA      array BroNames   BroId => Name (full names)
$BroShortNamesA array ShortNames BroId => ShortName
$BroNamesMapA   array BroNames   Name  => BroId
                         and ShortName => BroId for defined ShortNames, thus $BroNamesMapA is not a flip of $BroNamesA
$BroDescrsA     array of Braiins Master Bro Descriptions by Id which gets merged with Agent and Entity Descriptions at RT
$BroRptOrderA   2 dimensional array of Set BroIds => array of set member Money BroIds in report order i.e. Bros sorted by SortOrder, Name within the Set
$BroSumTreesA   3 dimensional array of [DataTypeN => [BroId of Target Bro => [BroIds of Bros to sum]]]
$CheckBrosA     2 dimensional array of [DataTypeN => [BroId => CheckTest]]

In-BroSets Only
----------------
$SumEndBrosA    array of StartEnd SumEnd type Bros  [DataTypeN => [BroId => [BroIds of Bros to sum]]]
$PostEndBrosA   array of StartEnd PostEnd type Bros [DataTypeN => [BroId => [BroIds of List Member Bros]]]
$StockBrosA     array of StartEnd Stock type Bros   [DataTypeN => [BroId => Stock Movement Bro BroId]] Only 1 Movement Bro per StartEnd Stock Bro

Out-BroSets Only
---------------
$BroTagsA       array of Tx Tags used by Bros [BroId => Tx Tag]

Files in /Com/str/BroSet{BroSetId}/:
BroInfoA.inc       $BroInfoA
BroNamesA.inc      $BroNamesA
BroNamesMapA.inc   $BroNamesMapA
BroShortNamesA.inc $BroShortNamesA
BroDescrsA.inc     $BroDescrsA
BroTagsA.inc       $BroTagsA
BroRptOrderA.inc   $BroRptOrderA
BroSumTreesA.inc   $BroSumTreesA $CheckBrosA {$SumEndBrosA $PostEndBrosA $StockBrosA}

RgRT.inc use at RG Run Time
  $DiMesRA             /-  Created by BuildTxStructs.inc
  $NamespacesRgA       |
  $TupFullNamesA       |
  $BroDescrsA
  $BroTagsA
*/

#  $BroSumTypesGA, $BroSetId, $BroSetTaxnId, Com_Str_Tx, $NamespacesRgA;

require 'BuildStructsFuncs.inc';

$BroSetPath = Com_Str."BroSet$BroSetId/";
if (!is_dir($BroSetPath))
  mkdir($BroSetPath);

if ($BroSetTaxnId) {
  require Com_Str_Tx.'NamespacesRgA.inc'; # $NamespacesRgA
  # Tuple info for converting Bro TupId to TuMeId
  $TuplesByTxIdA = []; # [TxId => [TupId => TuMeId]]
  $res = $DB->ResQuery(sprintf('Select Id,TupId,MemTxId from %s.TuplePairs', DB_Tx));
  while ($o = $res->fetch_object()) {
    $TuMeId  = (int)$o->Id;
    $TupId   = (int)$o->TupId;
    $MemTxId = (int)$o->MemTxId;
    $TuplesByTxIdA[$MemTxId][$TupId] = $TuMeId;
  }
  $res->free();
  unset($o);
}

# Strings for building the arrays in string form which are built directly
$BroInfoAS      = '$BroInfoA=[';      # /- Both
$BroNamesAS     = '$BroNamesA=[';     # |
$BroShortNamesAS= '$BroShortNamesA=[';
$BroNamesMapAS  = '$BroNamesMapA=[';
$BroDescrsAS    = '$BroDescrsA=[';
$BroRptOrderAS  = '$BroRptOrderA=[';
$BroSumTreesAS  = '$BroSumTreesA=[';
$BroTagsAS      = '$BroTagsA=[';     # Out-BroSet

# Initialising those arrays which are built as arrays then converted to string form
$CheckBrosA   =
$SumEndBrosA  =
$PostEndBrosA =
$StockBrosA   = [];
foreach ($BroSumTypesGA as $dataTypeN)
  $CheckBrosA[$dataTypeN] = $SumEndBrosA[$dataTypeN] = $PostEndBrosA[$dataTypeN] = $StockBrosA[$dataTypeN] = [];

########################################################################################
# If editing this code remember the similar code in BrosExport.php, and BrosLookup.php #
########################################################################################
$BrosA = []; # Array of Bro arrays

# Read the Bros recursively re Includes
ReadBros($BroSetId);

function ReadBros($broSetId) {
  global $DB, $BrosA;
  $res = $DB->ResQuery("Select * From BroInfo Where BroSetId=$broSetId Order By Id");
  while ($o = $res->fetch_object()) {
    if ($o->InclBroSetId)
      ReadBros($o->InclBroSetId);
    else{
      $broA  = BroInfo($o);
      $broId = $broA['BroId'];
      unset($broA['InclBroSetId'], $broA['BroId'], $broA['Comments']); # unset unused ones to save memory
      $BrosA[$broId] = $broA;
    }
  }
  $res->free();
}
#$maxBroId = end($BrosA)['BroId'];
end($BrosA);
$maxBroId = key($BrosA);

# Build $MasterSlaveIdsA
$MasterSlaveIdsA = []; # [MasterBroId => [i SlaveBroIds]]
foreach ($BrosA as $BroId => $broA)
  if ($broA['MasterId']) {
    # Slave
    $MasterId = $broA['MasterId'];
    if (isset($MasterSlaveIdsA[$MasterId]))
      $MasterSlaveIdsA[$MasterId][] = $BroId; # subsequent
    else{
      $MasterSlaveIdsA[$MasterId] = [$BroId]; # first one
      $BrosA[$MasterId]['Bits'] |= BroB_Master; # as might not be set if Master came via an include
    }
  }

foreach ($BrosA as $BroId => $broA) {
  extract($broA);
  # -> $BroId, $Name, $Level, $DadId, $Bits, $DataTypeN, $DboFieldN, $SignN, $SumUp, $PeriodSEN, $SortOrder, $FolioHys,
  #    $ExclPropDims, $AllowPropDims, $PMemDiMesA, $UsablePropDims, $Related, $SeSumList, $MasterId, $SlaveYear, $CheckTest, $Zones, $ShortName, $Ref, $Descr, $Scratch, $RowComments
  #    {, $TxId {, $TupId, $ManDims, $xA}}

  # djh?? $DboFieldN
  # djh?? $Related

  $BroSetTxB = $Bits & BroB_Out; # Can be different from Main-BroSet e.g. a Tx MaIn-BroSet including a In-BroSet
  if ($BroSetTxB) {
    # Out-BroSet
    if ($TxId) {
      if (!$Descr) $Descr = $xA['Text']; # the Std Label
      $tag = $NamespacesRgA[(int)$xA['NsId']].':'.$xA['name'];
      $tuMeId = $TupId ? $TuplesByTxIdA[$TxId][$TupId] : 0;
      $contextN = RGC_Period; # djh?? To be deduced from Bro info
    }else{
      if (!$Descr)
       #$segs  = explode('.', $Name);
       #$Descr = BroNameToWords($segs[count($segs)-1]);
       #$Descr = BroNameToWords($Name); # Using full BroName rather than just the last segment
        $Descr = $Name; # 23.04.12 Using just the full BroName as a prompt to say 'Hey, fix this' either by addinga Descr or hard coding the desired text in the format
      $tuMeId = $contextN = 0;
    }
    $IsListSubSetFn = 'IsDimsListSubset';
  }else{
    # In-BroSet
    $TxId =    # Not stored for In-BroSet Bro but could form part of BroInfoA if master/slave stuff follows
    $tuMeId = $contextN = 0;
    $IsListSubSetFn = 'IsPropsListSubset';
  }

  if (!$PMemDiMesA)
    $PMemDiMesA = 0; # php 5.4 version of above, using the json e.g. [[148],0,0,0] directly

  $BrosA[$BroId]['SumUsablePropDims'] = $UsablePropDims; # just for internal use here if a Dad

  $sumUsablePropDims = 0;
  if (!$MasterId && ($Bits & BroB_Summing)) {
    # Non-Slave Summing Bro
    if ($DadId && $SumUp === BroSumUp_Yes && $UsablePropDims) {
      $sumUsablePropDims = $BrosA[$BroId]['SumUsablePropDims'] = IntAToChrList(ChrListToIntA($UsablePropDims.$BrosA[$DadId]['SumUsablePropDims'])); # IntAToChrList() sorts the dims and eliminates duplicates
      # Remove $ExclPropDims if there are any - don't want to put them back into SumUsablePropDims courtesy of ancestor $UsablePropDims
      if ($ExclPropDims)
        $sumUsablePropDims = str_replace(str_split($ExclPropDims), '', $sumUsablePropDims);
      if ($sumUsablePropDims === $UsablePropDims)
        $sumUsablePropDims = 0;
      #else
      #  echo "$BroId SumUsablePropDims=$sumUsablePropDims vs BroUsableDims=$UsablePropDims<br>";
    }
  }

  $setMaxId = $master = $slave = 0;
  if ($MasterId) {
    #########
    # Slave #
    #########
    $slave = 1;
    if (($Bits & BroB_Ele) && ($UsablePropDims === ($masterUsableDims = $BrosA[$MasterId]['UsablePropDims']) || $IsListSubSetFn($masterUsableDims, $UsablePropDims)))
      # Unset UsablePropDims for an Ele Slave without Usable list filtering
      $UsablePropDims = 0;
  }else{
    #############
    # Non-Slave # = Std Bro. Could be Master.
    #############
    if ($Bits & BroB_Master) $master = 1;

    if ($Bits & BroB_Set) {
      # Determine the Set BroId range for down the tree Mux checking.
      $setMaxId = $maxBroId;
      for ($id = $setMinId = $BroId+1; $id <= $maxBroId; ++$id) {
        if (!isset($BrosA[$id])) continue; # gap in Bro Ids
        if ($BrosA[$id]['Level'] <= $Level) {
          $setMaxId = $id-1;
          break; # past the Set and descendant
        }
      }
    }
  }

  $broUsablePropDimsS = $UsablePropDims    ? (SQ.addslashes($UsablePropDims).SQ) : 0;    # addslashes() re Prop/Dim 44 = \
  $sumUsablePropDimsS = $sumUsablePropDims ? (SQ.addslashes($sumUsablePropDims).SQ) : 0; # addslashes() re Prop/Dim 44 = \
  $zonesS = $Zones ? "'$Zones'" : 0;
  /* BroInfoA
  const BroI_Bits      =  0; # BroInfo.Bits
  const BroI_SetMaxId  =  1; # Max BroId of a Set Bro, used in down the tree mux check during posting. Also is truthy re bro being a Set.
  const BroI_DataTypeN =  2;
  const BroI_DadId     =  3; # BroInfo.Id of Parent if Bro has a parent = all except Level 0 Sets
  const BroI_PeriodSEN =  4; # Provides StartEnd and Period info
  const BroI_SignN     =  5; # 0|1|2 for undefined | Dr | Cr
  const BroI_BroUsablePropDims = 6; # C Usable Props chr list for just this Bro. 0 if not applicable or is an Ele Slave without Usable list filtering.
                                    #   Remains set for a Set Slave re summing and SumUsablePropDims of any non-slave set members.
                                    # T Usable Dims chr list for just this Bro = tagable Dims. 0 if not applicable or is an Ele Slave without Usable list filtering.
                                    #   Remains set for a Set Slave re summing and SumUsablePropDims of any non-slave set members.
  const BroI_SumUsablePropDims = 7; # C Usable Dims  chr list for Summing/Posting = BroUsableProps + any extra Props in SumUp ancestors. 0 if same as BroUsableProps (always for non-summing Bros)
                                    # T Usable Props chr list for /Summing        = BroUsableDims  + any extra Dims  in SumUp ancestors. 0 if same as BroUsableDims (always for non-summing Bros)
  const BroI_PMemDiMesA        = 8; # C Bro PMem Overrides [] as below, 0 if empty
                                    # T Bro DiMe Overrides [] as below, 0 if empty
  const BroI_Zones     =  9; #
  # Ends here if In-BroSet, not Master, not Slave
  const BroI_SlaveIdsA = 10; # Array of BroInfo.Ids of Slaves of this Bro if a Master /- slot shared
  const BroI_SlaveYear = 10; # Rel Year for a Prior Year Slave 1-3        if a Slave  |
  # Ends here if not Out-BroSet
  const BroI_TxId      = 11; #
  const BroI_ContextN  = 12; # Deduced by Build Structs for TxId Bros
  const BroI_TuMeId    = 13; # TuMeId which applies if the Bro involves Tuple use. Converted from BroInfo.TupId by Build Structs */

  $row = "$BroId=>[$Bits,$setMaxId,$DataTypeN,$DadId,$PeriodSEN,$SignN,$broUsablePropDimsS,$sumUsablePropDimsS,$PMemDiMesA,$zonesS"; # as far as BroI_Zones which applies in all cases
  if ($BroSetTxB || $master || $slave) {
    # Have more...
    if ($master)
      $row .= ',['.implode(',', $MasterSlaveIdsA[$BroId]).']'; # BroI_SlaveIdsA
    else if ($slave)
      $row .= ",$SlaveYear"; # BroI_SlaveYear
    else if ($BroSetTxB)
      $row .= ',0'; # BroI_SlaveIdsA/BroI_SlaveYear when not master or slave but Tx stuff is to follow
    if ($BroSetTxB)
      $row .= ",$TxId,$contextN,$tuMeId"; # BroI_TxId, BroI_ContextN, BroI_TuMeId
  }
  $BroInfoAS .= $row."],\n";

  # $BroNamesA      array BroNames   BroId => Name (full names)
  # $BroShortNamesA array ShortNames BroId => ShortName for defined ShortNames.
  # $BroNamesMapA   array BroNames   Name  => BroId
  #                          and ShortName => BroId for defined ShortNames, thus $BroNamesMapA is not a flip of $BroNamesA
  $BroNamesAS    .= "$BroId=>'$Name',\n";
  $BroNamesMapAS .= "'$Name'=>$BroId,\n";

  if ($ShortName) {
    $BroNamesMapAS   .= "'$ShortName'=>$BroId,\n";
    $BroShortNamesAS .= "$BroId=>'$ShortName',\n";
  }

  # $BroDescrsA
  if (strpos($Descr, SQ) !== false) # re ' in Director's in some labels
    $BroDescrsAS .= "$BroId=>\"$Descr\",\n";
  else
    $BroDescrsAS .= "$BroId=>'$Descr',\n";

  # Check
  if ($CheckTest)
    $CheckBrosA[$DataTypeN][$BroId] = "'$CheckTest'";

  # StartEnd Bros
  if ($PeriodSEN >= BPT_InstSumEnd && !$BroSetTxB) {
    # In-BroSet StartEnd Bro which should have a SumList
    if (!$SeSumList) die("Die - In-BroSet StartEnd Bro BroId with PeriodSEN=$PeriodSEN >= BPT_InstSumEnd expected to have a SeSumList but it doesn't");
    switch ($PeriodSEN) { # Temporary form of arrays with lists in CsList form pending building of the arrays in string form after the loop ends
      case BPT_InstSumEnd:  $SumEndBrosA[$DataTypeN][$BroId]  = "[$SeSumList]"; break; # [DataTypeN => [BroId => [BroIds of Bros to sum]]]
      case BPT_InstPostEnd: $PostEndBrosA[$DataTypeN][$BroId] = "[$SeSumList]"; break; # [DataTypeN => [BroId => [BroIds of List Member Bros]]]
      case BPT_InstStock:   $StockBrosA[$DataTypeN][$BroId]   = $SeSumList;            # [DataTypeN => [BroId => Stock Movement Bro BroId]] Only 1 Movement Bro per StartEnd Stock Bro
        if (InStr(',', $SeSumList))
          die("Die - In-BroSet StartEnd Stock Bro $BroId has a SumList longer than the expected 1");
    }
  }

  # BroTagsA
  if ($TxId && !($Bits & BroB_NoTags))
    $BroTagsAS .= "$BroId=>'$tag',\n";
}

unset($TuplesByTxIdA);
FinishArrayString($BroInfoAS);
FinishArrayString($BroNamesAS);
FinishArrayString($BroShortNamesAS);
FinishArrayString($BroNamesMapAS);
FinishArrayString($BroDescrsAS);
FinishArrayString($BroTagsAS);
FinishArrayString($SumEndBrosAS);
FinishArrayString($PostEndBrosAS);
FinishArrayString($StockBrosAS);

# Build the string forms for
# $CheckBrosA     2 dimensional array of [DataTypeN => [BroId => CheckTest]]
# $SumEndBrosA    array of StartEnd SumEnd type Bros  [DataTypeN => [BroId => [BroIds of Bros to sum]]]
# $PostEndBrosA   array of StartEnd PostEnd type Bros [DataTypeN => [BroId => [BroIds of List Member Bros]]]
# $StockBrosA     array of StartEnd Stock type Bros   [DataTypeN => [BroId => Stock Movement Bro BroId]] Only 1 Movement Bro per StartEnd Stock Bro

$CheckBrosAS   = Build2DimensionalArrayString('$CheckBrosA=[',   $CheckBrosA);
$SumEndBrosAS  = Build2DimensionalArrayString('$SumEndBrosA=[',  $SumEndBrosA);
$PostEndBrosAS = Build2DimensionalArrayString('$PostEndBrosA=[', $PostEndBrosA);
$StockBrosAS   = Build2DimensionalArrayString('$StockBrosA=[',   $StockBrosA);

# The Bro indexing and summing arrays
# -----------------------------------
# $BroRptOrderA   2 dimensional array of Set BroIds => array of set member Money BroIds in report order i.e. Bros sorted by SortOrder, Name within the Set
# $BroSumTreesA   3 dimensional array of [DataTypeN => [BroId of Target Bro => [BroIds of Bros to sum]]
# $CheckBrosA     2 dimensional array of [DataTypeN, [BroId => CheckTest]]

# $BroRptOrderA
/* djh?? Fix
$res = $DB->ResQuery('Select * From BroInfo Where Bits&1 Order By Id'); # Bits&1 = Set
while ($o = $res->fetch_object()) {
  $id = (int)$o->Id;
  $rsm = $DB->ResQuery("Select Id From BroInfo Where DadId=$id And DataTypeN=" . DT_Money . ' And Bits&2 Order By SortOrder,Name'); # Bits&2 = Ele
  if ($rsm->num_rows) {
    $setMembersAS = '[';
    while ($m = $rsm->fetch_object())
      $setMembersAS .= "$m->Id,";
    $setMembersAS = substr($setMembersAS, 0, -1) . ']'; # chop off the last , and close the array
  }else
    $setMembersAS = 0;
  $BroRptOrderAS .= "$id=>$setMembersAS,\n";
}
$rsm->free();
$res->free();
*/
FinishArrayString($BroRptOrderAS);

# $BroSumTreesA
foreach ($BroSumTypesGA as $dataTypeN)
  $BroSumTreesAS .= "$dataTypeN=>" . BuildBroSumArray($dataTypeN);
FinishArrayString($BroSumTreesAS);


#####################
# Write the Structs #
#####################
/*
Both CoA and Out-BroSets
-----------------------
$BroInfoA
$BroNamesA
$BroShortNamesA
$BroNamesMapA
$BroDescrsA
$BroRptOrderA
$BroSumTreesA Included in BroSumTreesA.inc
$CheckBrosA   "
*/
file_put_contents($BroSetPath.'BroInfoA.inc', '<?php
' . $BroInfoAS);
file_put_contents($BroSetPath.'BroNamesA.inc', '<?php
' . $BroNamesAS);
file_put_contents($BroSetPath.'BroShortNamesA.inc', '<?php
' . $BroShortNamesAS);
file_put_contents($BroSetPath.'BroNamesMapA.inc', '<?php
' . $BroNamesMapAS);
file_put_contents($BroSetPath.'BroDescrsA.inc', '<?php
' . $BroDescrsAS);
file_put_contents($BroSetPath.'BroRptOrderA.inc', '<?php
' . $BroRptOrderAS);

/* RT needs
  $DiMesRA             /-  Created by BuildTxStructs.inc
  $NamespacesRgA       |
  $TupFullNamesA       |
  $BroDescrsA
  $BroTagsA
*/

/*
In-BroSets Only
----------------
$SumEndBrosA
$PostEndBrosA
$StockBrosA

Out-BroSets Only
---------------
$BroTagsA
*/

if ($BroSetTaxnId) {
  # Out-BroSet
  # Out-BroSet version of BroSumTreesA.inc, BroTagsA.inc, and Tx version of RgRT.inc
  file_put_contents($BroSetPath.'BroSumTreesA.inc', '<?php
' . $BroSumTreesAS
  . $CheckBrosAS);

  file_put_contents($BroSetPath.'BroTagsA.inc', '<?php
' . $BroTagsAS);

  file_put_contents($BroSetPath.'RgRT.inc', '<?php
' . substr(file_get_contents(Com_Str_Tx.'DiMesRA.inc'), 6)  # substr to chop off the leading <?php\n
  . substr(file_get_contents(Com_Str_Tx.'NamespacesRgA.inc'), 6)
  . substr(file_get_contents(Com_Str_Tx.'TupFullNamesA.inc'), 6)
  . $BroDescrsAS
  . $BroTagsAS);
}else{
  # In-BroSet
  # In-BroSet version of BroSumTreesA.inc and CoA version of RgRT.inc
  file_put_contents($BroSetPath.'BroSumTreesA.inc', '<?php
' . $BroSumTreesAS
  . $CheckBrosAS
  . $SumEndBrosAS
  . $PostEndBrosAS
  . $StockBrosAS);

  file_put_contents($BroSetPath.'RgRT.inc', '<?php
' # . substr(file_get_contents(Com_Str_Tx.'DiMesRA.inc'), 6)  # substr to chop off the leading <?php\n   djh?? fix
  . $BroDescrsAS
  . $BroTagsAS);
}


echo "Structs written to $BroSetPath<br><br>";

/* djh?? Fix
echo "<p class=b>Checking Bro Related Constants</p>\n";
  IdConstantCheck('BroId_Info_EntityName',             'SchInputEntity.Names.CurrentLegalOrRegistered');
  IdConstantCheck('BroId_Info_RegistrationNumber',     'SchInputEntity.IdentifyingCodes.UKCompaniesHouseRegisteredNumber');
  IdConstantCheck('BroId_Info_IncorporationCountry',   'SchInputEntity.LegalForm.CountryFormationOrIncorporation');
  IdConstantCheck('BroId_Info_Audited',                'SchInputEntity.OfficialOperationalStatus.Audited');
  IdConstantCheck('BroId_Info_Dormant',                'SchInputEntity.OfficialOperationalStatus.Dormant');
  IdConstantCheck('BroId_Info_Trading',                'SchInputEntity.OfficialOperationalStatus.Trading');
* IdConstantCheck('BroId_Dates_YearStartDate',         'SchInputBRI.DatesPeriods.Start');
* IdConstantCheck('BroId_Dates_YearEndDate',           'SchInputBRI.DatesPeriods.End');
* IdConstantCheck('BroId_Dates_SigningDirectorsReport','SchInputDirRep.DateSigningDirectorsReport');
* IdConstantCheck('BroId_Dates_BS',                    'SchInputBRI.DatesPeriods.BS');
* IdConstantCheck('BroId_Dates_ApprovalAccounts',      'SchInputBRI.DatesPeriods.ApprovalAccounts');
  IdConstantCheck('BroId_Statements_NoOtherGainsOrLosses', 'STRGL.StatementThatThereWereNoGainsInPeriodOtherThanThoseInPL');
  IdConstantCheck('BroId_Officers_Properties',             'SchInputEntity.Officers.Properties');
  IdConstantCheck('BroId_Officers_Name',                   'SchInputEntity.Officers.Name');
  IdConstantCheck('BroId_Officers_DirectorSigningReport',  'SchInputDirRep.DirectorSigningReport');
  IdConstantCheck('BroId_Officers_DirectorSigningAccounts','SchInputEntity.Officers.DirectorSigningAccounts');
  IdConstantCheck('BroId_TPAs_AccountantsAndAuditors',     'SchInputEntity.ThirdPartyAgents.AccountantsAndAuditors');

|- * = done via Defined Bros In-BroSet Import

echo '<br>';
*/


# } # End of BuildStructs()
######

function IdConstantCheck($constName, $name) {
  global $DB;
  if (!$id = $DB->OneQuery("Select Id from BroInfo where Name='$name'")) {
    echo "<span class='L b'>Error:</span> Bro $name not found for Id Constant Check<br>\n";
    return;
  }
 #$constName = "BroId_" . str_replace('.', '_', $name);
  $constVal  = constant($constName);
  if ($id == $constVal)
    echo "$name check OK: BroId $id = constant $constName<br>";
  else
    echo "<span class='L b'>Error:</span> $name BroId $id not equal to constant $constName value of ", $constVal, '<br>';
}

# Build the Bro summing array string in broSumTreeAS for type $dataTypeN
function BuildBroSumArray($dataTypeN) {
  global $BrosA;
  # Dump('BrosA',$BrosA);
  # First extract the SumUp Bros for this DataType
  $sumUpBrosA = [];
  foreach ($BrosA as $BroId => $broA) {
    $broDataTypeN = $broA['DataTypeN'];
    if (($broDataTypeN === $dataTypeN || ($broDataTypeN === DT_MoneyString && $dataTypeN === DT_Money)) && $broA['SumUp'] == BroSumUp_Yes)
      $sumUpBrosA[] = [$BroId, $broA['DadId'], $broA['Level']]; # BroId, DadId, Level
  }
  # Sort them into order of Descending Level, Ascending DadId, Ascending BroId
  # Dump("sumUpBrosA for DataTypeN=$dataTypeN", $sumUpBrosA);
  usort($sumUpBrosA, 'SortSumUpBrosCmp');
  # Build array in string form
  $setId = $setMembersAS = 0;
  $broSumTreeAS='[';
  foreach ($sumUpBrosA as $suBroA) {
    list($id, $dadId, $level) = $suBroA;
    if ($dadId != $setId) {
      #new set
      if ($setId && $setMembersAS)
        $broSumTreeAS .= "$setId=>[" . substr($setMembersAS,1) . "],\n";
      $setMembersAS = '';
      $setId = $dadId;
    }
    $setMembersAS .= ",$id";
  }
  if ($setId && $setMembersAS)
    $broSumTreeAS .= "$setId=>[" . substr($setMembersAS,1) . "]],\n";
  else
    $broSumTreeAS="0,\n";
  return $broSumTreeAS;
}

function SortSumUpBrosCmp($a, $b) {
  # a and b are [0:BroId, 1:DadId, 2:Level]
  # Want to sort into order of Descending Level, Ascending DadId, Ascending BroId
  if ($a[2] === $b[2]) {
    # Level same
    if ($a[1] === $b[1])
      # DadId same
      return $a[0] < $b[0] ? -1 : 1; # no 0 or equal case as BroIds don't repeat
    # Level same DadId different
    return $a[1] < $b[1] ? -1 : 1;
  }
  # Level different
  return $a[2] > $b[2] ? -1 : 1; # > re Descending sort
}

