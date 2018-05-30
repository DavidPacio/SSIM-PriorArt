<?php /* Copyright 2011-2013 Braiins Ltd

Admin /utils/inc/BuildStructs.inc

Inc for building the Bro and Zone structs.

Required by BuildStructs.php and BrosImport.php

History:
31.05.11 Started using code from Compile.php
xx.06.11 More..
xx.07.11 More..
xx.xx.11 Braiins Dimensions and Bro changes...
19.11.11 Added Ageing dimension and revised Dim 21 MPeriods for Mux
29.11.11 NameSpace arrays build moved to BuildTaxonomyDB.php as these don't change after the DB Build
         Array of Map DiMeIds added to Main Bros
30.11.11 Changed SumToId and CheckId handling for BD Map possibility, and switched to all info required for Equivalence tests being in $CheckBrosA
06.12.11 Building of the Dimension and Tuple Tx Based structs moved to BuildTxStructs.inc
07.12.11 Tuple ones brought back re change to use BroId in the map, and so that only those tuples actually used are included in the structs.
19.01.12 Revised Bro checks a bit re BrosImport changes.
12.04.12 FinishArrayString() revised for the empty array case re partial Bro set imports
22.04.12 Changes to allow BD Maps without a TxId
31.05.12 Revised for addition of Slave Bros etc
02.06.12 Revised for addition of BroInfo.TupId
24.06.12 Revised for addition of BroInfo.ShortName
17.07.12 Added generation of $BroNamesMapA incl ShortName => BroId for those Bros with a ShortName defined, thus not a flip of $BroNamesA
         Removed generation of $BroShortNamesA
20.08.12 Added BroI_SlaveIdsA to $BroInfoA plus a bit of tidying
22.08.12 Removed creating extra StartEnd Bro with switch to DiMeId_Start Pseudo DiMe for Start handling
29.08.12 Made StartEnd SumLists which could include multiple lists use a double array.
         Removed the old commented out SumTo code
31.08.12 Restored generation of $BroShortNamesA for temporary (?) use in Data Import listings
08.09.12 CheckId -> CheckTest
09.09.12 Extended ShortNames for use with BD Maps
12.09.12 Removed $manDim, $propDim
17.09.12 Split off from BuildStructs.php as an inc for use by BrosImport.php as well.
19.09.12 Added SlaveYear
23.09.12 Removed use of SE Bit InB_Calcs
25.10.12 -> BroInfoA
14.11.12 BroI_Level -> BroI_SetMaxId, with determination of $setMaxId moved here from Import.php
         Php 5.4 []s changes
         SumUsableDims (BroI_SumUsableDims) added
26.12.12 $BroInfoA and $TuMesA removed from RgRT.inc as they are now included via ClassBro.inc
08.02.13 Bit of memory saving re Bros Import fail on memory
15.02.13 BD Maps removed
17.02.13 ContextN -> PeriodSEN, SeBroIds removed
22.03.13 Zones -> BuildBrlStructs.php

ToDo djh??
----
- Add deduction of contextN - BroI_ContextN

- Build StartEnd SumLists from Set structure to provide the equivalent of the old SumLists?

- Add Headings?

Needs:

Com_Inc_Tx.ConstantsTx.inc   /- Already included by both calling modules
Com_Inc_Tx.ConstantsRg.inc   |
Com_Inc_Tx.DiMesA.inc        |
Com_Inc_Tx.NamespacesRgA.inc |
*/

require Com_Str_Tx.'NamespacesRgA.inc'; # $NamespacesRgA
require 'BroInfo.inc';

/*############
# The Arrays #
##############

Bros
####
$BroInfoA       array of BroInfo Info BroId => [BroI_Bits, ...]
$BroNamesA      array Bro Names   BroId => Name (full names)
$BroShortNamesA array ShortNames  BroId => ShortName
$BroNamesMapA   array Bro Names   Name  => BroId
                          and ShortName => BroId for defined ShortNames, thus $BroNamesMapA is not a flip of $BroNamesA
$BroTagsA       array of Tx Tags used by Bros [BroId => Tx Tag]
$BroDescrsA     array of Braiins Master Bro Descriptions by Id which gets merged with Agent and Entity Descriptions at RT
$BroRptOrderA   2 dimensional array of Set BroIds => array of set member Money BroIds in report order i.e. Bros sorted by SortOrder, Name within the Set
$BroSumTypesGA   array of the Bro DataTypeN's for which summing is performed - [DT_Integer, DT_Money, DT_Decimal, DT_Share] 14.10.11 moved to ConstantsRg.inc
$BroSumTreesA   3 dimensional array of [DataTypeN => [BroId of Target Bro => [BroIds of Bros to sum]]
$CheckBrosA     2 dimensional array of [DataTypeN => [BroId => CheckTest]]
$SumEndBrosA    array of StartEnd SumEnd type Bros            [BroId => [i => BroId to sum]]
$PostEndBrosA   array of StartEnd PostEnd and Stock type Bros [BroId => [i => BroId]]
$StockBrosA     array of StartEnd Stock type Bros [BroId => Stock Movement BroId]


Files in /Com/str/TxName/:
BroInfoA.inc       $BroInfoA
BroNamesA.inc      $BroNamesA
BroNamesMapA.inc   $BroNamesMapA
BroShortNamesA.inc $BroShortNamesA
BroDescrsA.inc     $BroDescrsA
BroTagsA.inc       $BroTagsA
BroRptOrderA.inc   $BroRptOrderA
BroSumTreesA.inc   $BroSumTreesA $CheckBrosA $SumEndBrosA $PostEndBrosA $StockBrosA

RgRT.inc use at RG Run Time
  $DiMesRA             /-  Created by BuildTxStructs.inc
  $NamespacesRgA       |
  $TupFullNamesA       |
  $BroDescrsA
  $BroTagsA
*/

function BuildStructs() {
  global $DB, $TxName, $NamespacesRgA, $BroSumTypesGA;

########
# Bros #
########
echo '<br><b>Bro Structs</b><br>';

# Strings for $BroInfoA...
$BroInfoAS      = '$BroInfoA=[
';
$BroNamesAS     = '$BroNamesA=[
';
$BroShortNamesAS= '$BroShortNamesA=[
';
$BroNamesMapAS  = '$BroNamesMapA=[
';
$BroDescrsAS    = '$BroDescrsA=[
';
$BroTagsAS      = '$BroTagsA=[
';
$SumEndBrosAS   = '$SumEndBrosA=[';
$PostEndBrosAS  = '$PostEndBrosA=[';
$StockBrosAS    = '$StockBrosA=[';

# Tuple info for converting Bro TupId to TuMeId
$TuplesByTxIdA = []; # [TxId => [TupId => TuMeId]]
$res = $DB->ResQuery('Select Id,TupId,MemTxId from TuplePairs');
while ($o = $res->fetch_object()) {
  $TuMeId  = (int)$o->Id;
  $TupId   = (int)$o->TupId;
  $MemTxId = (int)$o->MemTxId;
  $TuplesByTxIdA[$MemTxId][$TupId] = $TuMeId;
}
$res->free();


######################################################################################################
# If editing this code remember the similar code in BrosExport.php, BrosList.php, and BrosLookup.php #
######################################################################################################
$brosA = []; # Array of Bro arrays
$res = $DB->ResQuery("Select * From BroInfo Order By Id");
while ($o = $res->fetch_object()) {
  $broA = BroInfo($o);
  unset($broA['Id'], $broA['Ref'], $broA['Scratch'], $broA['RowComments']); # unset ones unused here to save memory
  $brosA[(int)$o->Id] = $broA;
}
$res->free();
end($brosA);
$maxBroId = key($brosA);


foreach ($brosA as $Id => $broA) {
  extract($broA);
  # -> $Name, $Level, $DadId, $Bits, $DataTypeN, $AcctTypes, $SumUp, $TxId, $Hys, $TupId, $SignN, $ShortName, $PeriodSEN, $ExclDims, $AllowDims, $BroDiMesA,
  #    $MasterId, $CheckTest, $SortOrder, $Zones, $Descr, $Comment, $SlaveIds, $SlaveYear, $UsableDims
  #    $xO - Derived data

  if ($TxId) {
    if (!$Descr) $Descr = $xO->Text; # the Std Label
    $tag = $NamespacesRgA[(int)$xO->NsId].':'.$xO->name;
    $tuMeId = $TupId ? ",{$TuplesByTxIdA[$TxId][$TupId]}" : ',0';
    $contextN = ','.RGC_Period; # djh?? To be deduced from Bro info
  }else{
    if (!$Descr)
     #$segs  = explode('.', $Name);
     #$Descr = BroNameToWords($segs[count($segs)-1]);
     #$Descr = BroNameToWords($Name); # Using full Bro name rather than just the last segment
      $Descr = $Name; # 23.04.12 Using just the full Bro name as a prompt to say 'Hey, fix this' either by addinga Descr or hard coding the desired text in the format
    $tuMeId = $contextN = '';
  }

/*# BroDiMesA =  Bro DiMes Overrides array [i => MandatsA, DefaultsA, ExcludesA, AllowsA]
  if ($BroDiMesA)
  # $BroDiMesA = var_export($BroDiMesA, true); # $BroDiMesA is in json form. With php5.4 could use the json directly e.g. [[148],0,0,0]
    $BroDiMesA = str_replace(array('[',']'), array('array(',')'), $BroDiMesA); # [[148],0,0,0] -> array(array(148),0,0,0)
  else
    $BroDiMesA = 0; */

  if (!$BroDiMesA)
    $BroDiMesA = 0; # php 5.4 version of above, using the json e.g. [[148],0,0,0] directly

  $brosA[$Id]['SumUsableDims'] = $UsableDims; # just for internal use here if a Dad

  $sumUsableDims = 0;
  if (!$MasterId && ($Bits & BroB_Summing) && !($Bits & BroB_RO)) {
    # Non-Slave Non-RO Summing Bro
    if ($DadId && $SumUp === BroSumUp_Yes && $UsableDims) {
      $sumUsableDims = $brosA[$Id]['SumUsableDims'] = IntAToChrList(ChrListToIntA($UsableDims.$brosA[$DadId]['SumUsableDims'])); # IntAToChrList() sorts the dims and eliminates duplicates
      # Remove $ExclDims if there are any - don't want to put them back into SumUsableDims courtesy of ancestor $UsableDims
      if ($ExclDims)
        $sumUsableDims = str_replace(str_split($ExclDims), '', $sumUsableDims);
      if (!strncmp($sumUsableDims, '12', 2))         # /- reduce 12 to 2 since 1 is a subset of 2
        $sumUsableDims = substr($sumUsableDims, 1); # |
      if ($sumUsableDims === $UsableDims)
        $sumUsableDims = 0;
      #else
      #  echo "$Id SumUsableDims=$sumUsableDims vs BroUsableDims=$UsableDims<br>";
    }
  }

  $setMaxId = 0;
  if ($MasterId) {
    #########
    # Slave #
    #########
    if (($Bits & BroB_Ele) && ($UsableDims === ($masterUsableDims = $brosA[$MasterId]['UsableDims']) || IsDimsListSubset($masterUsableDims, $UsableDims)))
      # Unset UsableDims for an Ele Slave without UsableDims filtering
      $UsableDims = 0;
  }else{
    #############
    # Non-Slave # = Std Bro. Could be Master.
    #############
    if ($Bits & BroB_Set) {
      # Determine the Set BroId range for down the tree Mux checking.
      $setMaxId = $maxBroId;
      for ($id = $setMinId = $Id+1; $id <= $maxBroId; ++$id) {
        if (!isset($brosA[$id])) continue; # gap in Bro Ids
        if ($brosA[$id]['Level'] <= $Level) {
          $setMaxId = $id-1;
          break; # past the Set and descendant
        }
      }
    }
  }

  if ($UsableDims) {
    $UsableDims  = addslashes($UsableDims); # addslashes() re Dimension 44 = \
    $UsableDimsS = "'$UsableDims'";
  }else
    $UsableDimsS = 0;
  if ($sumUsableDims) $sumUsableDims = SQ.addslashes($sumUsableDims).SQ;

  $zonesS = $Zones ? "'$Zones'" : 0;
  $SlaveIdASlaveYearS = $SlaveIds ? ",[$SlaveIds]" : ($SlaveYear ? ",$SlaveYear" : ($TxId ? ',0' :'')); # ',0' if not Master or Slave and Tx info is to follow
  /* BroInfoA
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
  const BroI_ContextN      = 13; # * Deduced by Build Structs for TxId Bros */
  $BroInfoAS .= "$Id=>[$Bits,$setMaxId,$DataTypeN,$DadId,$PeriodSEN,$SignN,$UsableDimsS,$sumUsableDims,$BroDiMesA,$zonesS,$TxId$SlaveIdASlaveYearS$tuMeId$contextN],\n";

  # $BroNamesA      array Bro Names  BroId => Name (full names)
  # $BroShortNamesA array ShortNames BroId => ShortName for defined ShortNames.
  # $BroNamesMapA   array Bro Names  Name  => BroId
  #                          and ShortName => BroId for defined ShortNames, thus $BroNamesMapA is not a flip of $BroNamesA
  $BroNamesAS .= "$Id=>'$Name',\n";
  $BroNamesMapAS .= "'$Name'=>$Id,\n";

  if ($ShortName) {
    $BroNamesMapAS .= "'$ShortName'=>$Id,\n";
    $BroShortNamesAS .= "$Id=>'$ShortName',\n";
  }

  # $BroDescrsA
  if (strpos($Descr, '\'') !== false) # re ' in Director's in some labels
    $BroDescrsAS .= "$Id=>\"$Descr\",\n";
  else
    $BroDescrsAS .= "$Id=>'$Descr',\n";

  # BroTagsA
  if ($TxId && !($Bits & BroB_NoTags))
    $BroTagsAS .= "$Id=>'$tag',\n";

  # StartEnd Bros
  if ($PeriodSEN >= BPT_InstSumEnd)
    switch ($PeriodSEN) {
      case BPT_InstSumEnd:  $SumEndBrosAS  .= "$Id,"; break;
      case BPT_InstPostEnd: $PostEndBrosAS .= "$Id,"; break;
      case BPT_InstStock:   $StockBrosAS   .= "$Id,"; break;
    }

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

# The Bro indexing and summing arrays
# -----------------------------------
# $BroRptOrderA   2 dimensional array of Set BroIds => array of set member Money BroIds in report order i.e. Bros sorted by SortOrder, Name within the Set
# $BroSumTypesGA   array of the Bro DataTypeN's for which summing is performed - [DT_Integer, DT_Money, DT_Decimal, DT_Share] in ConstantsRg.inc
# $BroSumTreesA   3 dimensional array of [DataTypeN => [BroId of Target Bro => [BroIds of Bros to sum]]
# $CheckBrosA     2 dimensional array of [DataTypeN, [BroId => CheckTest]]

# $BroRptOrderA
$BroRptOrderAS='$BroRptOrderA=[
';
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
FinishArrayString($BroRptOrderAS);

# $BroSumTypesGA 14.10.11 Moved to ConstantsRg.inc
# $BroSumTypesGA  = [DT_Integer, DT_Money, DT_Decimal, DT_Share];
# $BroSumTypesGAS = '$BroSumTypesGA=[' . DT_Integer . ',' . DT_Money . ',' . DT_Decimal . ',' . DT_Share . '];
# ';

$BroSumTreesAS = '$BroSumTreesA=[
';
foreach ($BroSumTypesGA as $dataTypeN)
  $BroSumTreesAS .= "$dataTypeN=>" . BuildBroSumArray($dataTypeN);
FinishArrayString($BroSumTreesAS);

# $CheckBrosA 2 dimensional array of [DataTypeN, [BroId => CheckTest]]
$CheckBrosAS = '$CheckBrosA=[
';
foreach ($BroSumTypesGA as $dataTypeN) {
  $thisAS = '[';
  $res = $DB->ResQuery("Select Id,Bits,CheckTest from BroInfo Where DataTypeN=$dataTypeN And CheckTest is not null Order by Id");
  while ($o = $res->fetch_object()) {
    $thisAS .= "$o->Id=>'$o->CheckTest',";
  }
  $CheckBrosAS .= "$dataTypeN=>" . (substr($thisAS,-1) === ',' ? substr($thisAS,0,-1) : $thisAS) . "],\n";
}
FinishArrayString($CheckBrosAS);
/*
$CheckBrosA=[
2=>[],
3=>['1535,1292'=>'EE1516',1606=>'EE7180',2230=>'EE2201',2606=>'EE2672',2607=>'EE2605',2609=>'EE2608',2610=>'EE2716',2617=>'EE2611',2618=>'EE2612',2619=>'EE2614',2620=>'EE2616',2621=>'EE2752',2643=>'EE5413',2672=>'EE2606',2673=>'EE2605',2674=>'EE2608',2675=>'EE2651',2688=>'EE2605',2695=>'EE2608',2716=>'EE2610',2717=>'EE2611',2718=>'EE2612',2719=>'EE2613',2720=>'EE2614',2721=>'EE2615',2722=>'EE2616',2727=>'EE2611',2733=>'EE2612',2737=>'EE2614',2741=>'EE2613',2742=>'EE5413',2743=>'EE2644',2744=>'EE2645',2745=>'EE2646',2746=>'EE2646',2747=>'EE2648',2748=>'EE2649',2749=>'EE2650',2752=>'EE2621',2754=>'EE2623',2757=>'EE2616',2762=>'EE2615',2764=>'EE2624',2766=>'EE9501',2770=>'EE2640',2771=>'EE2641',2778=>'EE2652',2780=>'EE2654',2799=>'EE2625',3351=>'EE3347',4178=>'EE4270',4372=>'EE4319',4438=>'EE3351',4506=>'EE4526',4511=>'EE5038',4701=>'EE3503',5003=>'EE5037',5008=>'EE5124',5017=>'EE5020',5022=>'EE5267',5028=>'EE5293',5037=>'EE5003',5041=>'EE3845',5047=>'EE1535',5077=>'EE5175',5080=>'EE5172',5225=>'EE1629',5232=>'EE1629',5233=>'EE5226',5234=>'EE5227',5269=>'EE5264',5276=>'EE5271',5283=>'EE5278',5293=>'EE5028',7318=>'EE2102',7322=>'EE2103',7325=>'EE2103',7333=>'EE2111',7355=>'EE4234',7374=>'EE4235',7392=>'EE4236',7908=>'EE5420',7925=>'EE5421',7934=>'EE4200',8851=>'EE7631',9007=>'EE3630',9008=>'EE3633',9015=>'EE3845',9036=>'EE3630',9037=>'EE3633',9052=>'EE3845',9075=>'EE3630',9076=>'EE3633',9086=>'EE3845',9164=>'EE4742',9165=>'EE4743'],
4=>[],
8=>[]
];*/


#####################
# Write the Structs #
#####################

file_put_contents(Com_Str_Tx.'BroInfoA.inc', '<?php
' . $BroInfoAS);
file_put_contents(Com_Str_Tx.'BroNamesA.inc', '<?php
' . $BroNamesAS);
file_put_contents(Com_Str_Tx.'BroShortNamesA.inc', '<?php
' . $BroShortNamesAS);
file_put_contents(Com_Str_Tx.'BroNamesMapA.inc', '<?php
' . $BroNamesMapAS);
file_put_contents(Com_Str_Tx.'BroDescrsA.inc', '<?php
' . $BroDescrsAS);
file_put_contents(Com_Str_Tx.'BroTagsA.inc', '<?php
' . $BroTagsAS);
file_put_contents(Com_Str_Tx.'BroRptOrderA.inc', '<?php
' . $BroRptOrderAS);
file_put_contents(Com_Str_Tx.'BroSumTreesA.inc', '<?php
' . $BroSumTreesAS
  . $CheckBrosAS
  . $SumEndBrosAS
  . $PostEndBrosAS
  . $StockBrosAS);

/* RT needs
  $DiMesRA             /-  Created by BuildTxStructs.inc
  $NamespacesRgA       |
  $TupFullNamesA       |
  $BroDescrsA
  $BroTagsA
*/

file_put_contents(Com_Str_Tx.'RgRT.inc', '<?php
' . substr(file_get_contents(Com_Str_Tx.'DiMesRA.inc'), 6)  # substr to chop off the leading <?php\n
  . substr(file_get_contents(Com_Str_Tx.'NamespacesRgA.inc'), 6)
  . substr(file_get_contents(Com_Str_Tx.'TupFullNamesA.inc'), 6)
  . $BroDescrsAS
  . $BroTagsAS);

echo "<br>Structs written to /Com/str/$TxName/<br><br>";

echo "<p class=b>Checking Bro Related Constants</p>\n";
IdConstantCheck('BroId_Info_EntityName',             'SchInputEntity.Names.CurrentLegalOrRegistered');
IdConstantCheck('BroId_Info_RegistrationNumber',     'SchInputEntity.IdentifyingCodes.UKCompaniesHouseRegisteredNumber');
IdConstantCheck('BroId_Info_IncorporationCountry',   'SchInputEntity.LegalForm.CountryFormationOrIncorporation');
IdConstantCheck('BroId_Info_Audited',                'SchInputEntity.OfficialOperationalStatus.Audited');
IdConstantCheck('BroId_Info_Dormant',                'SchInputEntity.OfficialOperationalStatus.Dormant');
IdConstantCheck('BroId_Info_Trading',                'SchInputEntity.OfficialOperationalStatus.Trading');
IdConstantCheck('BroId_Dates_YearStartDate',         'SchInputBRI.DatesPeriods.Start');
IdConstantCheck('BroId_Dates_YearEndDate',           'SchInputBRI.DatesPeriods.End');
IdConstantCheck('BroId_Dates_SigningDirectorsReport','SchInputDirRep.DateSigningDirectorsReport');
IdConstantCheck('BroId_Dates_BS',                    'SchInputBRI.DatesPeriods.BS');
IdConstantCheck('BroId_Dates_ApprovalAccounts',      'SchInputBRI.DatesPeriods.ApprovalAccounts');
IdConstantCheck('BroId_Statements_NoOtherGainsOrLosses', 'STRGL.StatementThatThereWereNoGainsInPeriodOtherThanThoseInPL');
IdConstantCheck('BroId_Officers_Properties',             'SchInputEntity.Officers.Properties');
IdConstantCheck('BroId_Officers_Name',                   'SchInputEntity.Officers.Name');
IdConstantCheck('BroId_Officers_DirectorSigningReport',  'SchInputDirRep.DirectorSigningReport');
IdConstantCheck('BroId_Officers_DirectorSigningAccounts','SchInputEntity.Officers.DirectorSigningAccounts');
IdConstantCheck('BroId_TPAs_AccountantsAndAuditors',     'SchInputEntity.ThirdPartyAgents.AccountantsAndAuditors');

echo '<br>';

} # End of BuildStructs()
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
  global $DB;
  $setId = $setMembersAS = 0;
  $broSumTreeAS='[';
  # B = the Bro, D = the Dad Bro
  $res = $DB->ResQuery("Select B.Id,B.DadId,B.SumUp From BroInfo B Join BroInfo D On D.Id=B.DadId And B.DataTypeN=D.DataTypeN Where B.DataTypeN=$dataTypeN Order By B.Level Desc,B.DadId,B.Id");
  while ($o = $res->fetch_object()) {
    $id    = (int)$o->Id;
    $dadId = (int)$o->DadId;
    $SumUp = (int)$o->SumUp;
    if ($dadId != $setId) {
      #new set
      if ($setId && $setMembersAS)
        $broSumTreeAS .= "$setId=>[" . substr($setMembersAS,1) . "],\n";
      $setMembersAS = '';
      $setId = $dadId;
    }
   #if ($bits & BroB_SumUp)
    if ($SumUp == BroSumUp_Yes)
      $setMembersAS .= ",$id";
  }
  if ($setId && $setMembersAS)
    $broSumTreeAS .= "$setId=>[" . substr($setMembersAS,1) . "]],\n";
  else
    $broSumTreeAS="0,\n";
  return $broSumTreeAS;
}

# Add the closing ];\n after chopping off:
#  - nothing for a final [ for an empty single line array
#  - a final , for a single line array
#  - a final \n if second last char is [ i.e. if nothing was added to the array
#  - the final , and \n if otherwise, assuming an array entry followed by ,\n
function FinishArrayString(&$arrayS) {
  $arrayS = (substr($arrayS,-1) === '[' ? $arrayS : ((substr($arrayS,-1) === ',' || substr($arrayS,-2,1) === '[') ? substr($arrayS,0,-1) : substr($arrayS,0,-2))) . '
];
';
}

