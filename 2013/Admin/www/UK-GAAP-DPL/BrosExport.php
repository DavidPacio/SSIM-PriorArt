<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/Utils/UK-GAAP-DPL/BrosExport.php

Export Bros (Bro Info records actually)

Assumptions:
- Bro Id order = set and desired Bros order
  i.e. no inserts out of order done.
  (New Bros created via BrosImport.php run from empty tables.)
- complete pass in Id order as for BuildStructs.php

History:
30.09.12 Started based on UK-GAAP version
04.10.12 Added export of Hy info in source (short name) form
05.10.12 Added export of Dims in source (short name) form
07.10.12 Requires updated for updated BroRefToSrce() requirements
17.01.13 NoTags column added
12.02.13 Added check for no defined UsableDims (no Hy, TxId, AllowDims) for slave -> not a usable dims slave filtering case
15.02.13 BD Maps removed
17.02.13 ContextN -> PeriodSEN, SeBroIds removed
*/

require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsTx.inc';
require Com_Inc_Tx.'ConstantsRg.inc';
require Com_Str_Tx.'NamespacesRgA.inc';   # $NamespacesRgA
require Com_Str_Tx.'BroSumTreesA.inc';    # $BroSumTreesA $CheckBrosA $SumEndBrosA $PostEndBrosA $StockBrosA
require Com_Str_Tx.'BroInfoA.inc';        # $BroInfoA
require Com_Str_Tx.'BroNamesA.inc';       # $BroNamesA      re CheckTestToStr()
require Com_Str_Tx.'DimNamesA.inc';  # $DimNamesA re DimsChrListToSrce()
require Com_Str_Tx.'DiMeNamesA.inc';      # $DiMeNamesA     re BroDiMesAtoSrce()
require Com_Str_Tx.'ZonesA.inc';          # $ZonesA         re ZonesCsStrList()
require Com_Str_Tx.'TupNamesA.inc';       # $TupNamesA
require Com_Str_Tx.'TuMesA.inc';          # $TuMesA
require Com_Str_Tx.'Hypercubes.inc';     # $HyNamesA  re HysChrListToSrce()
require 'inc/BroInfo.inc';

const Num_Name_Cols = 9;

Head("BROs Export: $TxName", true);
echo "<h2 class=c>Braiins Report Objects Export: $TxName</h2>
";

if (!isset($_POST['form']))
  Form();
  ######

$HysDimsDiMesByNameB = isset($_POST['HysDimsDiMesByName']);

$file = "/BrosAndTx/Bros-$TxName-".gmstrftime('%Y-%m-%d_%H_%M').'.txt';
$Fh = fopen('../..'.$file, 'w');
fwrite($Fh, "\xEF\xBB\xBF"); # Write UTF-8 BOM

echo "<p class=c>to the file <a href='Show.php?$file'>Admin$file</a> in tab delimited form.</p>
";

$mainHyId = $prevId = $postRowComments = 0;
$prevName = '';

$brosA = [];
$res = $DB->ResQuery('Select * From BroInfo Order by Id');
while ($o = $res->fetch_object())
  $brosA[(int)$o->Id] = BroInfo($o);
$res->free();
fwrite($Fh, "Id	Type	Level	Bro Name	Name 0	N 1	N 2	N 3	N 4	N 5	N 6	N 7	N 8	ShortName	Master	Ref	TxId	Hys	TupId	Data Type	Sign	Acct Types	Post Type	RO	ExclDims	AllowDims	DiMes	NoTags	Except	Amort	Sum Up	Check	Period	StartEnd	Zones	Order	Descr	Comment	Scratch	I Tx Std Label	I Usable Dims	I Post Usable Dims	I M# DiMe Info	I Tag	I Tx Type	I StartEnd	I Tx Sign	I Tx Hys	I Tuple	I Slave Ids	I Slave Filtering
");
########################################################################################################
# If editing this code remember the similar code in BuildStructs.php, BrosList.php, and BrosLookup.php #
########################################################################################################
foreach ($brosA as $broA) {
  extract($broA);
  # -> $Id, $Name, $Level, $DadId, $Bits, $DataTypeN, $AcctTypes, $SumUp, $TxId, $Hys, $TupId, $SignN, $ShortName, $Ref, $PeriodSEN, $ExclDims, $AllowDims, $BroDiMesA,
  #    $MasterId, $CheckTest, $SortOrder, $Zones, $Descr, $Comment, $SlaveIds, $SlaveYear, $UsableDims, $Scratch, $RowComments
  #    $xO - Derived data

  # echo $Id, '<br>';

  if ($DadId) $dadA = $brosA[$DadId];

  # Fields for all cases
  $id = ($Id === $prevId+1) ? $Id : " =$Id";
  $broType = BroTypeStr($Bits);
  $noTags  = ($Bits & BroB_NoTags) ? 'NoTags' : '';
  # Level as it comes
  # Name, Name Segs, BD
  $prevSegsA = array_pad(explode('.', $prevName), Num_Name_Cols, ''); # previous Name
  $name  = str_repeat(' ', $Level*2) . $Name;
  $segsA = explode('.', $Name);
  $prevName = $Name;
  $segs = '';
  foreach ($segsA as $i => $seg) {
    if ($seg == $prevSegsA[$i])
      $seg=$i?'':' '; # space for the first one to stop full name overflow in SS
    $segs .= "	$seg";
  }
  if ($Level > 8)
    die("More than the 8 allowed for levels in $Id $Name");
  if ($Level < 8) {
   #$segs .= "	 ";                     /- with space in next col
   #$segs .= str_repeat("	", 4-$Level); |
    $segs .= str_repeat(TAB, 8-$Level);
  }

  # Master, Ref, TxId (& Tx related fields)
  if ($MasterId) {
    #########
    # Slave #
    #########
    $masterBroA = $brosA[$MasterId];
    $master = ($SlaveYear ? "Year$SlaveYear " : '')."$MasterId {$masterBroA['Name']}";
    # Slave filtering could apply if UsableDims is different, but only if the Master UsableDims is not a subset of the Slave's.
    # (Master UsableDims can be a subset of the Slave's if ExclDims has been used with the Master but not the Slave = no filtering applies.)
    if ($UsableDims && ($UsableDims !== $masterBroA['UsableDims'] && !IsDimsListSubset($masterBroA['UsableDims'], $UsableDims))) {
      $slaveFiltering = ', Usable Dims';
      $broUsableDims = DimsChrListToSrce($UsableDims, $HysDimsDiMesByNameB);
    }else{
      $slaveFiltering = '';
      # No UsableDims for an Ele Slave without UsableDims filtering
      $broUsableDims = ($Bits & BroB_Ele) ? '' : DimsChrListToSrce($UsableDims, $HysDimsDiMesByNameB);
    }
    if ($BroDiMesA) {
      $slaveFiltering .= ', DiMes';
      $diMes = BroDiMesAtoSrce($BroDiMesA, $HysDimsDiMesByNameB);
    }else
      $diMes = '';
    $slaveFiltering = $slaveFiltering ? substr($slaveFiltering, 2) : '';
    $sumUsableDims = '';

  }else{
    #############
    # Non-Slave # = Std Bro. Could be Master.
    #############
    $master = $MasterId = $slaveFiltering = ''; # $SlaveIds could be be CS list of Slave Ids
    if ($SlaveIds) $SlaveIds = str_replace(',', ', ', $SlaveIds); # Expand , to ', ' so that a simple import into Excel doesn't treat multiple CS Ids as a big number
    $diMes          = BroDiMesAtoSrce($BroDiMesA, $HysDimsDiMesByNameB);
    $broUsableDims = DimsChrListToSrce($UsableDims, $HysDimsDiMesByNameB);
    $sumUsableDims = DimsChrListToSrce($BroInfoA[$Id][BroI_SumUsableDims], $HysDimsDiMesByNameB);
  }

  # Ref as it comes
  # TxId
  if ($TxId) {
    # Taxonomy based
    $txId  = $TxId;
    $tag      = "{$NamespacesRgA[(int)$xO->NsId]}:$xO->name";
    $txDescr  = $xO->Text;
    $txType   = ElementTypeToStr($xO->TypeN);
    $txSign   = $xO->TypeN == TET_Money ? SignToStr($xO->SignN) : '';
    $txHys    = HysChrListToSrce($xO->Hypercubes, $HysDimsDiMesByNameB);
  }else
    $txId = $tag = $txDescr = $txType = $startEnd = $startEndInfo = $txSign = $txHys = '';

  if ($TupId) {
    $tupId = $TupId;
    $tuple = ExpandTuple($TxId, $TupId); # Tuple expanded with TuMeId TUCN and tuple short names
  }else
    $tupId = $tuple = '';

  if ($DataTypeN == DT_Money) {
    $sign = SignToStr($SignN);
    $postType = ($Bits & BroB_DE) ? 'DE' : 'Sch';
  }else
    $sign = $postType = '';

  $hys       = HysChrListToSrce($Hys, $HysDimsDiMesByNameB);
  $dataType  = DataTypeStr($DataTypeN);
  $acctTypes = AcctTypesCsStrList($AcctTypes);
  $ro        = ($Bits & BroB_RO) ? 'RO' : '';
  $exclDims  = DimsChrListToSrce($ExclDims, $HysDimsDiMesByNameB);
  $allowDims = DimsChrListToSrce($AllowDims, $HysDimsDiMesByNameB);
  $except    = ($Bits & BroB_Except) ? 'Except' : '';
  $amort     = ($Bits & BroB_Amort)  ? 'Amort'  : '';
  $zones     = ZonesCsStrList($Zones);
  $sortOrder = ZeroToEmpty($SortOrder);
  $sumUp     = SumUpStr($SumUp);
  $check     = CheckTestToStr($CheckTest);
  if ($PeriodSEN === BPT_Duration)
    $period = $startEnd = ''; # Duration -> '' for export
  else{
    /* BroInfo.PeriodSEN enums
    const BPT_Duration    = 1; # Same as TPT_Duration
    const BPT_Instant     = 2; # Same as TPT_Instant
    const BPT_InstSumEnd  = 3; # Instant StartEnd SumEnd  type
    const BPT_InstPostEnd = 4; # Instant StartEnd PostEnd type
    const BPT_InstStock   = 5; # Instant StartEnd Stock   type */
    $period = 'Instant';
    static $PeriodSENtypes = [0, 0, '', 'SumEnd', 'PostEnd', 'Stock'];
    $startEnd = $PeriodSENtypes[$PeriodSEN];
  }
  $startEndInfo = $startEnd; # djh?? Expand for list via Set ??
  $mDiMeInfo= MtypeDiMeInfo($Bits);

  # Descr, Comment, and Scratch as they come

  # RowComments
  # row comment{row comment...}{row comment{row comment...}}
  if ($RowComments) {
    # Got some row comments
    $commentsA = explode('', $RowComments); # split before and after
    if (count($commentsA)>1) $postRowComments=$commentsA[1]; # after
    foreach (explode('', $commentsA[0]) as $c)
      fwrite($Fh, $c."\n");
  }

  fwrite($Fh, rtrim("$id	$broType	$Level	$name$segs	$ShortName	$master	$Ref	$txId	$hys	$tupId	$dataType	$sign	$acctTypes	$postType	$ro	$exclDims	$allowDims	$diMes	$noTags	$except	$amort	$sumUp	$check	$period	$startEnd	$zones	$sortOrder	$Descr	$Comment	$Scratch	$txDescr	$broUsableDims	$sumUsableDims	$mDiMeInfo	$tag	$txType	$startEndInfo	$txSign	$txHys	$tuple	$SlaveIds	$slaveFiltering")."\n");
  $prevId = $Id;
}
# Post Comment row comment{row comment...}
if ($postRowComments)
  foreach (explode('', $postRowComments) as $c)
    fwrite($Fh, $c."\n");
fclose($Fh);
Footer();

function Form() {
  echo "<div>
<form class=c method=post>
  <input type=hidden name=form>
  <p><input class=radio type=checkbox name=HysDimsDiMesByName value=1> Export Hys, Dims and DiMes by Name rather than Ids</p>
  <button class='c on m10'>Export Bros</button>
</form>
</div>
";
  Footer(false);
}
