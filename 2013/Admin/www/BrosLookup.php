<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/BrosLookup.php

Display info about a Bro or Bros

ToDo djh??
----
Use BroInfoA
Use right Main re Master bits

History:
04.04.13 Started based on the UK-GAAP-DPL version
03.07.13 B R L -> SIM
07.08.13 AcctTypes removed

*/

require 'BaseSIM.inc';
require './inc/BroInfo.inc';
require Com_Inc.'FuncsSIM.inc';
require Com_Inc.'DateTime.inc';

if (isset($_POST['BroInput'])) {
  Clean($_POST['BroInput'], FT_STR, true, $BroInput);
  Clean($_POST['TxInput'],  FT_STR, true, $TxInput);
}

if (!isset($_POST['BroSet'])) {
  Head('Lookup Bros', true);
  echo "<h2 class=c>Bros Lookup</h2>\n";
  Form(false, false);
  ####
}

$BroSetId = Clean($_POST['BroSet'], FT_INT);
$bsO = $DB->ObjQuery(sprintf("Select * From %s.BroSets Where Id=$BroSetId", DB_Braiins));
$BTypeN = (int)$bsO->BTypeN;
$BroSetName = BroSetTypeStr($BTypeN).' BroSet '.$bsO->Name;
if ($BTypeN >= BroSet_Out_Main) {
  # Out-BroSet
  $BroSetTaxnId = json_decode($bsO->Data)[BSI_TaxnIdsA][0];
  $TxName = TaxnStr($BroSetTaxnId);
  define('DB_Tx', DB_Prefix.str_replace('-', '_', $TxName));
  define('Com_Inc_Tx', Com_Inc."$TxName/");
  define('Com_Str_Tx', Com_Str."$TxName/");
  require Com_Inc_Tx.'ConstantsTx.inc';
  require "./$TxName/inc/TxFuncs.inc";    # Tx specific funcs
  require Com_Str_Tx.'NamespacesRgA.inc'; # $NamespacesRgA
  require Com_Str_Tx.'TupNamesA.inc';     # $TupNamesA
  require Com_Str_Tx.'TuMesA.inc';        # $TuMesA
  require Com_Str_Tx.'Hypercubes.inc';    # $HyDimsA  $HyNamesA
  require Com_Str_Tx.'DimNamesA.inc';     # $DimNamesA
  require Com_Str_Tx.'DiMeNamesA.inc';    # $DiMeNamesA
  $FolioHyNamesA  = &$HyNamesA; unset($HyDimsA);
  $PropDimNamesA  = &$DimNamesA;
  $PMemDiMeNamesA = &$DiMeNamesA;
  $FolioHyNme  = 'Hypercube(s)';
  $PropDimNmes = 'Dims';
  $PMemDiMeNme = 'DiMe';
  $PMemDiMeNmes= 'DiMes';
  $IsListSubSetFn = 'IsDimsListSubset';
}else{
  # In-BroSet
  $BroSetTaxnId = 0;
  require Com_Str.'Folios.inc';     # $FolioPropsA  $FolioNamesA
  require Com_Str.'PropNamesA.inc'; # $PropNamesA
  require Com_Str.'PMemNamesA.inc'; # $PMemNamesA
  $FolioHyNamesA  = &$FolioNamesA; unset($FolioPropsA);
  $PropDimNamesA  = &$PropNamesA;
  $PMemDiMeNamesA = &$PMemNamesA;
  $FolioHyNme  = 'Folio';
  $PropDimNmes = 'Props';
  $PMemDiMeNme = 'PMem';
  $PMemDiMeNmes= 'PMems';
  $IsListSubSetFn = 'IsPropsListSubset';
}
require Com_Str.'Zones.inc'; # $ZonesA  $ZoneRefsA re ZonesCsStrList()
unset ($ZoneRefsA);          # Just $ZonesA is used by ZonesCsStrList()

$BroSetPath = Com_Str."BroSet$BroSetId/";
require $BroSetPath.'BroInfoA.inc';      # $BroInfoA
require $BroSetPath.'BroNamesA.inc';     # $BroNamesA
require $BroSetPath.'BroShortNamesA.inc';# $BroShortNamesA
require $BroSetPath.'BroSumTreesA.inc';  # $BroSumTreesA $CheckBrosA {$SumEndBrosA $PostEndBrosA $StockBrosA}

Head("Lookup $BroSetName Bros", true);

echo "<h2 class=c>Bros Lookup for $BroSetName</h2>\n";

$broObjsA = []; # Id => BroInfo table onject
foreach (explode(',', $BroInput) as $broInputSeg) {
  # Bro Id, a BroName, a Bro ShortName, a Filter (part of a BroName or ShortName with wildcards):
  if ($broInputSeg = trim($broInputSeg))
  if (ctype_digit($broInputSeg)) {
    if ($o = $DB->OptObjQuery("Select * from BroInfo Where BroSetId=$BroSetId And BroId=$broInputSeg"))
      $broObjsA[(int)$o->BroId] = $o;
    else
      echo "<p class=c>Bro with BroId $broInputSeg not found.</p>\n";
  }else{
    # a BroName, a Bro ShortName, a Filter (part of a BroName or ShortName with wildcards):
    $like = str_replace('*', '%', $broInputSeg);
    $res = $DB->ResQuery("Select * from BroInfo Where BroSetId=$BroSetId And Name like '$like'");
    if ($res->num_rows) {
      while ($o = $res->fetch_object())
        $broObjsA[(int)$o->BroId] = $o;
    }else{
      $res = $DB->ResQuery("Select * from BroInfo Where BroSetId=$BroSetId And ShortName like '$like'");
      if ($res->num_rows) {
        while ($o = $res->fetch_object())
          $broObjsA[(int)$o->BroId] = $o;
      }else
        echo "<p class=c>No Bros found for BroName or Filter of <i>$broInputSeg</i>.</p>\n";
    }
    $res->free();
  }
};
if ($BroSetTaxnId)
foreach (explode(',', $TxInput) as $txInputSeg) {
  # TxId
  if ($txInputSeg = trim($txInputSeg))
  if (ctype_digit($txInputSeg)) {
    $res = $DB->ResQuery("Select * from BroInfo Where BroSetId=$BroSetId And TxId=$txInputSeg");
    if ($res->num_rows) {
      while ($o = $res->fetch_object())
        $broObjsA[(int)$o->BroId] = $o;
    }
    $res->free();
  }else
    echo "<p class=c>No Bros found for TxId of <i>$txInputSeg</i>. (Value should be an integer number that is a Concrete Element TxId.)</p>\n";
};
if ($n=count($broObjsA)) {
  echo '<p class=c>';
  if ($n > 1)
    echo "Lookups for $n Bros with BroIds: ";
  else
    echo 'Lookup for Bro with BroId ';
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
if ($BroSetTaxnId) {
  $broIdsToTxIdsA = [];
  $res = $DB->ResQuery('Select Id,TxId From BroInfo Where TxId is not Null');
  while ($o = $res->fetch_object())
    $broIdsToTxIdsA[(int)$o->Id] = (int)$o->TxId;
  $res->free();
}
foreach ($broObjsA as $o) {
  extract(BroInfo($o));
  # -> $BroId, $Name, $Level, $DadId, $Bits, $DataTypeN, $DboFieldN, $SignN, $SumUp, $PeriodSEN, $SortOrder, $FolioHys,
  #    $ExclPropDims, $AllowPropDims, $PMemDiMesA, $UsablePropDims, $Related, $SeSumList, $MasterId, $SlaveYear, $CheckTest, $Zones, $ShortName, $Ref, $Descr, $Data
  #    {, $TxId {, $TupId, $ManDims, $xA}}
  $PMemDiMesA = $PMemDiMesA ? json_decode($PMemDiMesA) : 0;

  $Bits = $BroInfoA[$BroId][BroI_Bits]; # Use the BroInfoA Bits re the BroB_Master setting that might only be set in the struct if the master came via an include
  $broType = BroTypeStr($Bits);
  $level = $Level ? $level = "$Level with DadId = $DadId" : $Level;

  # Master/Slave
  $slaves = '';
  if ($MasterId) {
    #########
    # Slave #
    #########
    $slave = 1;
    $masterBroA = $BroInfoA[$MasterId]; #  $brosA[$MasterId];
    $master = ($SlaveYear ? "Year$SlaveYear " : '').$MasterId.SP.BroShortNameOrName($MasterId);
    # Slave filtering could apply if Usable list is different, but only if the Master Usable list is not a subset of the Slave's.
    # (Master Usable list can be a subset of the Slave's if ExclPropDims has been used with the Master but not the Slave = no filtering applies.)
    if ($UsablePropDims && ($UsablePropDims !== $masterBroA[BroI_BroUsablePropDims] && !$IsListSubSetFn($masterBroA[BroI_BroUsablePropDims], $UsablePropDims))) {
      $slaveFiltering = ", Usable $PropDimNmes";
      $broUsablePropDims = PropDimsStr($UsablePropDims, $PropDimNamesA);
    }else{
      $slaveFiltering = '';
      # No UsableDims for an Ele Slave without Usable list filtering
      $broUsablePropDims = ($Bits & BroB_Ele) ? '' : PropDimsStr($UsablePropDims, $PropDimNamesA);
    }
    if ($PMemDiMesA) {
      $slaveFiltering .= ", $PropDimNmes";
      $pMemDiMes = PMemDiMesStr($PMemDiMesA, $PMemDiMeNamesA);
    }else
      $pMemDiMes = '';
    $slaveFiltering = $slaveFiltering ? substr($slaveFiltering, 2) : 'No';
    $sumUsablePropDims = $slaveIds = '';
  }else{
    #############
    # Non-Slave # = Std Bro. Could be Master.
    #############
    $slave = 0;
    $master = $slaveFiltering = '';
    if ($Bits & BroB_Master) {
      foreach ($BroInfoA[$BroId][BroI_SlaveIdsA] as $slaveId)
        $slaves .= "<br>$slaveId {$BroNamesA[$slaveId]}";
      $slaves = substr($slaves, 4);
    }
    $pMemDiMes         = PMemDiMesStr($PMemDiMesA, $PMemDiMeNamesA);
    $broUsablePropDims = PropDimsStr($UsablePropDims, $PropDimNamesA);
    $sumUsablePropDims = PropDimsStr($BroInfoA[$BroId][BroI_SumUsablePropDims], $PropDimNamesA);
  }
  # ShortName as it comes
  # Ref as it comes

  if ($DataTypeN === DT_Money) {
    $sign = SignToStr($SignN);
    $postType = ($Bits & BroB_DE) ? 'DE' : 'Sch';
  }else
    $sign = $postType = '';

  $folioHys  = FolioHysStr($FolioHys, $FolioHyNamesA);
  $dataType  = DataTypeStr($DataTypeN);
  $DboField  = DboFieldStr($DboFieldN);
  $exclPropDims  = PropDimsStr($ExclPropDims,  $PropDimNamesA);
  $allowPropDims = PropDimsStr($AllowPropDims, $PropDimNamesA);
  $zones     = ZonesCsStrList($Zones);
  $sortOrder = ZeroToEmpty($SortOrder);
  $sumUp     = SumUpStr($SumUp);
  $check     = CheckTestStr($CheckTest);
  list($period, $startEnd) = PeriodSENStrA($PeriodSEN, $SeSumList);
  $mDiMeInfo= MtypeInfo($Bits, $PMemDiMeNme);
  # Data
  if ($Data) {
    $DataA = json_decode($Data);
    $Taxonomies  = IdArraysStrViaFn($DataA[BII_TaxnIdsA],  $DataA[BII_NotTaxnIdsA],  'TaxnStr', ', ');
    $Countries   = IdArraysStrViaFn($DataA[BII_CtryIdsA],  $DataA[BII_NotCtryIdsA],  'CountryShortName', ', ');
    $EntityTypes = IdArraysStrViaFn($DataA[BII_ETypeIdsA], $DataA[BII_NotETypeIdsA], 'EntityTypeStr', ', ');
  }else
    $Taxonomies = $Countries = $EntityTypes = '';


  if ($BroSetTaxnId) {
    # Out-BroSet
    # TxId
    if ($TxId) {
      # Taxonomy based
      $noTags  = ($Bits & BroB_NoTags) ? 'Yes' : '';
      $tag     = $NamespacesRgA[(int)$xA['NsId']].':'.$xA['name'];
      $txDescr = $xA['Text'];
      $txType  = ElementTypeToStr($xA['TypeN']);
      $subst   = SubstGroupToStr($xA['SubstGroupN']);
      $txPeriod= PeriodTypeToStr($xA['PeriodN']);
      $txSign  = $xA['TypeN'] == TET_Money ? SignToStr($xA['SignN']) : '';
      $txHys   = FolioHysStr($xA['Hypercubes'], $FolioHyNamesA);
      if ($TupId) {
        $tupId = $TupId;
        $tuple = ExpandTuple($TxId, $TupId); # Tuple expanded with TuMeId TUCN and tuple short names
        $tupleTitleExtra = ' TupId TupTxId TuMeId TUC T Short name';
      }else
        $tupId = $tuple = $tupleTitleExtra = '';
      $manDims = PropDimsStr($ManDims, $PropDimNamesA);
    }else
      $noTags = $manDims = $tag = $txDescr = $txType = $startEnd = $startEndInfo = $txSign = $txHys = $tupId = $tuple = '';
  }else{
    # In-BroSet
    $TxId = 0;
    $ro   = ($Bits & BroB_RO) ? 'RO' : '';
    $related = RelatedStr($Related);
   #$startEndInfo = $startEnd; # djh?? Expand for list via Set ??
    $startEnd .= $SeSumList ? BR.StrField($startEnd, SP, 0).SP.$SeSumList : ''; # With SumList as BroIds
  }

  echo "<table class=mc>
<tr class='b bg0'><td>Property</td><td>Value</td></tr>
<tr><td>Bro Id</td><td>$BroId</td></tr>
<tr><td>Type</td><td>$broType</td></tr>
<tr><td>Level</td><td>$level</td></tr>
<tr><td>Name</td><td>$Name</td></tr>
<tr><td>Short Name</td><td>$ShortName</td></tr>
<tr><td>Master</td><td>$master</td></tr>
<tr><td>Slave(s)</td><td>$slaves</td></tr>
<tr><td>Ref</td><td>$Ref</td></tr>
";
if ($TxId) echo "<tr><td>TxId</td><td>$TxId</td></tr>
";
echo "<tr><td>$FolioHyNme</td><td>$folioHys</td></tr>
";
if ($TxId) echo "<tr><td>TupId</td><td>$tupId</td></tr>
";
echo "<tr><td>DataType</td><td>$dataType</td></tr>
<tr><td>DboField</td><td>$DboField</td></tr>
<tr><td>PostType</td><td>$postType</td></tr>
";
if (!$BroSetTaxnId) echo "<tr><td>RO (Report Only)</td><td>$ro</td></tr>
";
echo "<tr><td>Sign</td><td>$sign</td></tr>
<tr><td>Sum Up</td><td>$sumUp</td></tr>
<tr><td>Check</td><td>$check</td></tr>
";
if (!$BroSetTaxnId) echo "<tr><td>Related</td><td>$related</td></tr>
";
echo "<tr><td>Period</td><td>$period</td></tr>
<tr><td>StartEnd</td><td>$startEnd</td></tr>
<tr><td>Excl$PropDimNmes</td><td>$exclPropDims</td></tr>
<tr><td>Allow$PropDimNmes</td><td>$allowPropDims</td></tr>
<tr><td>Usable$PropDimNmes</td><td>$broUsablePropDims</td></tr>
<tr><td>Sum Usable$PropDimNmes</td><td>$sumUsablePropDims</td></tr>
<tr><td>$PMemDiMeNmes</td><td>$pMemDiMes</td></tr>
<tr><td>M Use $PMemDiMeNme Info</td><td>$mDiMeInfo</td></tr>\n";
if ($BroSetTaxnId)
  echo "<tr><td>ManDims</td><td>$manDims</td></tr>
<tr><td>No Tags</td><td>$noTags</td></tr>\n";
if ($slave)
  echo "<tr><td>Slave Filtering</td><td>$slaveFiltering</td></tr>\n";
echo "<tr><td>Zones</td><td>$zones</td></tr>
<tr><td>Order</td><td>$sortOrder</td></tr>
<tr><td>Descr</td><td>$Descr</td></tr>\n";
if (!$BroSetTaxnId)
  echo "<tr><td>Taxonomies</td><td>$Taxonomies</td></tr>\n";
echo "<tr><td>Countries</td><td>$Countries</td></tr>
<tr><td>EntityTypes</td><td>$EntityTypes</td></tr>
<tr><td>Comment</td><td>$Comment</td></tr>\n";
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

/*
function Form($timeB, $topB) {
  global $BroInput, $TxInput;
echo <<< FORM
<div class=mc style=width:900px>
<p class=c>For a Lookup of one or multiple Bros, enter a single value, or a comma seprated list of values, into one or both of the following fields and click Lookup.</p>
<form method=post>
<table class=itran>
<tr><td class=r>Bro Id, a BroName, a Bro ShortName,<br> or a Filter (part of a BroName or ShortName with wildcards):</td><td><input type=text name=BroInput  autofocus size=75 maxlength=155 value='$BroInput'></td></tr>
<tr><td class=r>Taxonomy Id:</td><td><input type=text name=TxInput size=75 maxlength=155 value='$TxInput'></td></tr>
</table>
<p class='c mb0'><button class='c on m10'>Lookup</button></p>
</form>
</div>
FORM;
Footer($timeB, $topB);
exit;
} */

function Form($timeB, $topB) {
  global $DB, $BroSetId, $BroInput, $TxInput;
  echo "<p class=c>Select the BroSet to Lookup, enter a single value, or a comma seprated list of values, into one or both of the following fields and click Lookup.</p>
<form method=post>
<table class=mc>
<tr class='b bg0'><td></td><td>Id</td><td>Type</td><td>Name</td><td>Description</td><td>Taxonomies</td></tr>
";
  $res=$DB->ResQuery(sprintf('Select * from %s.BroSets Order By SortKey', DB_Braiins));
  while ($bsO = $res->fetch_object()) {
    $id      = (int)$bsO->Id;
    $status  = (int)$bsO->Status;
    $typeS   = BroSetTypeStr($BTypeN = (int)$bsO->BTypeN);
    $BSDataA = json_decode($bsO->Data);
    $taxns   = IdArraysStrViaFn($BSDataA[BSI_TaxnIdsA],  $BSDataA[BSI_NotTaxnIdsA],  'TaxnStr');
    if ($status & (Status_OK | Status_DefBroErrs)) {
      # BroSet that has imported without errors or has imported with Defined Bro Errors
      $checked = $BroSetId==$id ? ' checked' : '';
      echo "<tr><td><input id=f$id type=radio class=radio name=BroSet value=$id$checked></td><td class=c><label for=f$id>$id</label></td><td><label for=f$id>$typeS</label></td><td><label for=f$id>$bsO->Name</label></td><td><label for=f$id>$bsO->Descr</label></td><td>$taxns</td><tr>\n";
    }else
      echo "<tr><td></td><td class=c>$id</td><td>$typeS</td><td>$bsO->Name</td><td>$bsO->Descr</td><td>$taxns</td><tr>\n";
  }
  $res->free();
  echo "</table>
<table class='mc itran mb0'>
<tr><td class=r>Bro Id, a BroName, a Bro ShortName,<br> or a Filter (part of a BroName or ShortName with wildcards):</td><td><input type=text name=BroInput autofocus size=75 maxlength=155 value='$BroInput'></td></tr>
<tr><td class=r>Taxonomy Id:</td><td><input type=text name=TxInput size=75 maxlength=155 value='$TxInput'></td></tr>
</table>
<p class='c mb0'><button class='c on m10'>Lookup</button></p>
</form>
";
  Footer($timeB, $topB); # Footer($timeB=true, $topB=false, $notCentredB=false) {
}

function FolioHysStr($folioHys, $folioHyNamesA) {
  return $folioHys ? ChrListToIdsOrShortNamesStr($folioHys).' ('.ChrListToIdsOrShortNamesStr($folioHys, $folioHyNamesA).')' : '';
}

function PropDimsStr($propDims, $propDimNamesA) {
  return $propDims ? ChrListToIdsOrShortNamesStr($propDims).' ('.ChrListToIdsOrShortNamesStr($propDims, $propDimNamesA).')' : '';
}

function PMemDiMesStr($pMemDiMesA, $pMemDiMeShortNamesA) {
  return $pMemDiMesA ? PMemDiMesAStr($pMemDiMesA).' ('.PMemDiMesAStr($pMemDiMesA, $pMemDiMeShortNamesA).')' : '';
}

