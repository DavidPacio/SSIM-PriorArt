<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/Utils/UK-GAAP-DPL/BrosLookup.php

Display info about a Bro or Bros

ToDo
----
History:
30.09.12 Started as the UK-GAAP version
04.10.12 Added display of Hy info in source (short name) form
05.10.12 Added display of Dims in source (short name) form
07.10.12 Requires updated for updated BroRefToSrce() requirements
17.01.13 NoTags column added
12.02.13 Added check for no defined UsableDims (no Hy, TxId, AllowDims) for slave -> not a usable dims slave filtering case
15.02.13 BD Maps removed
17.02.13 ContextN -> PeriodSEN, SeBroIds removed
17.03.13 Added Srce as well as Ids to Hys, Dims, DiMes

*/
require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsTx.inc';
require Com_Inc_Tx.'ConstantsRg.inc';
require Com_Str_Tx.'NamespacesRgA.inc';   # $NamespacesRgA
require Com_Str_Tx.'BroInfoA.inc';        # $BroInfoA
require Com_Str_Tx.'BroSumTreesA.inc';    # $BroSumTreesA $CheckBrosA $SumEndBrosA $PostEndBrosA $StockBrosA
require Com_Str_Tx.'BroNamesA.inc';       # $BroNamesA      re CheckTestToStr()
require Com_Str_Tx.'DimNamesA.inc';  # $DimNamesA re DimsChrListToSrce()
require Com_Str_Tx.'DiMeNamesA.inc';      # $DiMeNamesA     re BroDiMesAtoSrce()
require Com_Str_Tx.'ZonesA.inc';          # $ZonesA         re ZonesCsStrList()
require Com_Str_Tx.'TupNamesA.inc';       # $TupNamesA
require Com_Str_Tx.'TuMesA.inc';          # $TuMesA
require Com_Str_Tx.'Hypercubes.inc';     # $HyNamesA re HysChrListToSrce()

require 'inc/BroInfo.inc';

Head("Bros Lookup: $TxName", true);

echo "<h2 class=c>$TxName Bros Lookup</h2>\n";
if (!isset($_POST['BroInput']) && !isset($_POST['TxInput']))
  Form(false,false);
  #######

Clean($_POST['BroInput'], FT_STR, true, $BroInput);
Clean($_POST['TxInput'],  FT_STR, true, $TxInput);

$broObjsA = []; # Id => BroInfo table onject
foreach (explode(',', $BroInput) as $broInputSeg) {
  # Bro Id, a Bro Name, a Bro ShortName, a Filter (part of a Bro Name or ShortName with wildcards):
  if ($broInputSeg = trim($broInputSeg))
  if (ctype_digit($broInputSeg)) {
    if ($o = $DB->OptObjQuery("Select * from BroInfo Where Id=$broInputSeg"))
      $broObjsA[(int)$o->Id] = $o;
    else
      echo "<p class=c>Bro with Id $broInputSeg not found.</p>\n";
  }else{
    # a Bro Name, a Bro ShortName, a Filter (part of a Bro Name or ShortName with wildcards):
    $like = str_replace('*', '%', $broInputSeg);
    $res = $DB->ResQuery("Select * from BroInfo Where Name like '$like'");
    if ($res->num_rows) {
      while ($o = $res->fetch_object())
        $broObjsA[(int)$o->Id] = $o;
    }else{
      $res = $DB->ResQuery("Select * from BroInfo Where ShortName like '$like'");
      if ($res->num_rows) {
        while ($o = $res->fetch_object())
          $broObjsA[(int)$o->Id] = $o;
      }else
        echo "<p class=c>No Bros found for Bro Name or Filter of <i>$broInputSeg</i>.</p>\n";
    }
    $res->free();
  }
};
foreach (explode(',', $TxInput) as $txInputSeg) {
  # Tx Id
  if ($txInputSeg = trim($txInputSeg))
  if (ctype_digit($txInputSeg)) {
    $res = $DB->ResQuery("Select * from BroInfo Where TxId=$txInputSeg");
    if ($res->num_rows) {
      while ($o = $res->fetch_object())
        $broObjsA[(int)$o->Id] = $o;
    }
    $res->free();
  }else
    echo "<p class=c>No Bros found for Tx Id of <i>$txInputSeg</i>. (Value should be an integer number that is a Concrete Element Tx Id.)</p>\n";
};
if ($n=count($broObjsA)) {
  echo '<p class=c>';
  if ($n > 1)
    echo "Lookups for $n Bros with Ids: ";
  else
    echo 'Lookup for Bro with Id ';
  $ids = '';
  ksort($broObjsA);
  foreach ($broObjsA as $id => $o)
    $ids .= ", $id";
  echo substr($ids, 2), ":</p>\n";
}else{
  echo "<p class=c>No Bros found.</p>\n";
  Form(false,false);
  #######
}

# The lookups
# First read all the Bros for SE Sum List BroId to TxId conversion
$broIdsToTxIdsA = [];
$res = $DB->ResQuery('Select Id,TxId From BroInfo Where TxId is not Null');
while ($o = $res->fetch_object())
  $broIdsToTxIdsA[(int)$o->Id] = (int)$o->TxId;
$res->free();

foreach ($broObjsA as $o) {
  extract(BroInfo($o));
  # -> $Id, $Name, $Level, $DadId, $Bits, $DataTypeN, $AcctTypes, $SumUp, $TxId, $Hys, $TupId, $SignN, $ShortName, $Ref, $PeriodSEN, $ExclDims, $AllowDims, $BroDiMesA,
  #    $MasterId, $CheckTest, $SortOrder, $Zones, $Descr, $Comment, $SlaveIds, $SlaveYear, $UsableDims, $Scratch, $RowComments
  #    $xO - Derived data

  $broType = BroTypeStr($Bits);
  $level = $Level ? $level = "$Level with DadId = $DadId" : $Level;

  # Master/Slave
  $slaves = '';
  if ($MasterId) {
    #########
    # Slave # Only Id, Type, Level, Name, Master, Ref, TxId, Hys, check, zones, descr, comment
    #########
    $slave = 1;
    $master = ($SlaveYear ? "Year$SlaveYear " : '')."$MasterId {$BroNamesA[$MasterId]}";
    # Slave filtering could apply if UsableDims is different, but only if the Master UsableDims is not a subset of the Slave's.
    $masterUsableDims = $BroInfoA[$MasterId][BroI_BroUsableDims];
    # (Master UsableDims can be a subset of the Slave's if ExclDims has been used with the Master but not the Slave = no filtering applies.)
    if ($UsableDims && ($UsableDims !== $masterUsableDims && !IsDimsListSubset($masterUsableDims, $UsableDims))) {
      $slaveFiltering = ', Usable Dims';
      $broUsableDims = Dims($UsableDims);
    }else{
      $slaveFiltering = '';
      # No UsableDims for an Ele Slave without UsableDims filtering
      $broUsableDims = ($Bits & BroB_Ele) ? '' : Dims($UsableDims);
    }
    if ($BroDiMesA) {
      $slaveFiltering .= ', DiMes';
      $diMes = BroDiMes($BroDiMesA);
    }else
      $diMes = '';
    $slaveFiltering = $slaveFiltering ? substr($slaveFiltering, 2) : 'No';
    $sumUsableDims = '';
  }else{
    #############
    # Non-Slave # = Std Bro. Could be Master.
    #############
    $slave = 0;
    $master = ''; # $SlaveIds could be be CS list of Slave Ids
    if ($SlaveIds) {
      foreach (CsListToIntA($SlaveIds) as $slaveId)
        $slaves .= "<br>$slaveId {$BroNamesA[$slaveId]}";
      $slaves = substr($slaves, 4);
    }
    $broUsableDims = Dims($UsableDims);
    $sumUsableDims = Dims($BroInfoA[$Id][BroI_SumUsableDims]);
  }
  # ShortName as it comes
  # Ref as it comes
  # TxId ....
  $tuplesTitleExtra = '';
  if ($TxId) {
    # Taxonomy based
    $noTags = ($Bits & BroB_NoTags) ? 'Yes' : '';
    $txId = $TxId;
    $tag      = "{$NamespacesRgA[(int)$xO->NsId]}:$xO->name";
    $txDescr  = $xO->Text;
    $txType   = ElementTypeToStr($xO->TypeN);
    $subst    = SubstGroupToStr($xO->SubstGroupN);
    $txPeriod = PeriodTypeToStr($xO->PeriodN);
    $txSign   = $xO->TypeN == TET_Money ? SignToStr($xO->SignN) : '';
    $txHys    = ($hypercubes = $xO->Hypercubes) ? Hys($hypercubes) : '';
  }else
    $txId = $startEnd = $noTags = '';
  $hys = Hys($Hys);
  if ($TupId) {
    $tupId = $TupId;
    $tuple = ExpandTuple($TxId, $TupId); # Tuple expanded with TuMeId TUCN and tuple short names
    $tupleTitleExtra = ' TupId TupTxId TuMeId TUC T Short name';
  }else
    $tupId = $tuple = $tupleTitleExtra = '';
  $dataType = DataTypeStr($DataTypeN);
  if ($DataTypeN == DT_Money) {
    $sign = SignToStr($SignN);
    $postType = ($Bits & BroB_DE) ? 'DE' : 'Sch';
  }else
    $sign = $postType = '';
  $acctTypes= AcctTypesCsStrList($AcctTypes);
  $ro       = ($Bits & BroB_RO) ? 'RO' : '';
  $exclDims = Dims($ExclDims);
  $allowDims= Dims($AllowDims);
  $diMes    = BroDiMes($BroDiMesA);
  $mDiMeInfo= MtypeDiMeInfo($Bits);
  $except   = ($Bits & BroB_Except) ? 'Yes' : '';
  $amort    = ($Bits & BroB_Amort)  ? 'Yes' : '';
  $sumUp    = SumUpStr($SumUp);
  $zones    = ZonesCsStrList($Zones);
  $sortOrder= ZeroToEmpty($SortOrder);
  $check    = CheckTestToStr($CheckTest);
  if ($PeriodSEN === BPT_Duration) {
    $period = 'Duration';
    $startEnd = '';
  }else{
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

  echo "<table class=mc>
<tr class='b bg0'><td>Property</td><td>Value</td></tr>
<tr><td>Bro Id</td><td>$Id</td></tr>
<tr><td>Type</td><td>$broType</td></tr>
<tr><td>Level</td><td>$level</td></tr>
<tr><td>Name</td><td>$Name</td></tr>
<tr><td>Short Name</td><td>$ShortName</td></tr>
<tr><td>Master</td><td>$master</td></tr>
<tr><td>Slave(s)</td><td>$slaves</td></tr>
<tr><td>Ref</td><td>$Ref</td></tr>
<tr><td>Tx Id</td><td>$txId</td></tr>
<tr><td>Hypercube(s)</td><td>$hys</td></tr>
<tr><td>TupId</td><td>$tupId</td></tr>
<tr><td>Data Type</td><td>$dataType</td></tr>
<tr><td>Account Types</td><td>$acctTypes</td></tr>
<tr><td>Post Type</td><td>$postType</td></tr>
<tr><td>RO (Report Only)</td><td>$ro</td></tr>
<tr><td>Sign</td><td>$sign</td></tr>
<tr><td>No Tags</td><td>$noTags</td></tr>
<tr><td>Exceptional Item</td><td>$except</td></tr>
<tr><td>Amortisation Adj.</td><td>$amort</td></tr>
<tr><td>Sum Up</td><td>$sumUp</td></tr>
<tr><td>Check</td><td>$check</td></tr>
<tr><td>Period</td><td>$period</td></tr>
<tr><td>StartEnd</td><td>$startEnd</td></tr>
<tr><td>ExclDims</td><td>$exclDims</td></tr>
<tr><td>AllowDims</td><td>$allowDims</td></tr>
<tr><td>Usable Dims</td><td>$broUsableDims</td></tr>
<tr><td>Post Usable Dims</td><td>$sumUsableDims</td></tr>
<tr><td>DiMes</td><td>$diMes</td></tr>
<tr><td>M# Type DiMe Info</td><td>$mDiMeInfo</td></tr>
";
if ($slave)
  echo "<tr><td>Slave Filtering</td><td>$slaveFiltering</td></tr>
";
  echo "<tr><td>Zones</td><td>$zones</td></tr>
<tr><td>Order</td><td>$sortOrder</td></tr>
<tr><td>Descr</td><td>$Descr</td></tr>
<tr><td>Comment</td><td>$Comment</td></tr>
";
if ($TxId)
  echo "<tr class='b bg0'><td colspan=2>Taxonomy Info</td></tr>
<tr><td>Std Label</td><td>$txDescr</td></tr>
<tr><td>Tag</td><td>$tag</td></tr>
<tr><td>Type</td><td>$txType</td></tr>
<tr><td>Subst Group</td><td>$subst</td></tr>
<tr><td>Period</td><td>$txPeriod</td></tr>
<tr><td>Sign</td><td>$txSign</td></tr>
<tr><td>Hypercubes</td><td>$txHys</td></tr>
<tr><td>Tuple$tupleTitleExtra</td><td>$tuple</td></tr>
";
  echo "</table>\n";
}
Form(true,true);
#######


function Form($timeB, $topB) {
  global $BroInput, $TxInput;
echo <<< FORM
<div class=mc style=width:900px>
<p class=c>For a Lookup of one or multiple Bros, enter a single value, or a comma seprated list of values, into one or both of the following fields and click Lookup.</p>
<form method=post>
<table class=itran>
<tr><td class=r>Bro Id, a Bro Name, a Bro ShortName,<br> or a Filter (part of a Bro Name or ShortName with wildcards):</td><td><input type=text name=BroInput  autofocus size=75 maxlength=155 value='$BroInput'></td></tr>
<tr><td class=r>Taxonomy Id:</td><td><input type=text name=TxInput size=75 maxlength=155 value='$TxInput'></td></tr>
</table>
<p class='c mb0'><button class='c on m10'>Lookup</button></p>
</form>
</div>
FORM;
Footer($timeB, $topB);
exit;
}

function Dims($dims) {
  return $dims ? DimsChrListToSrce($dims).' ('.DimsChrListToSrce($dims, true).')' : '';
}

function BroDiMes($broDiMesA) {
  return $broDiMesA ? BroDiMesAtoSrce($broDiMesA).' ('.BroDiMesAtoSrce($broDiMesA, true).')' : '';
}

function Hys($hys) {
  return $hys ? HysChrListToSrce($hys).' ('.HysChrListToSrce($hys, true).')' : '';
}
