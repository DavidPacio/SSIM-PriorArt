<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/BroSetExport.php

Export a BroSet or BroSets

Assumptions:

History:
31.03.13 Started based on UK-GAAP-DPL version
06.04.13 Renamed from BrosExport.php
03.07.13 B R L -> SIM
07.08.13 AcctTypes removed

ToDo
----

*/

require 'BaseSIM.inc';
require './inc/BroInfo.inc';
require Com_Inc.'FuncsSIM.inc';
require Com_Inc.'DateTime.inc';
const Num_Name_Cols = 9;

Head('Export BroSet', true);
echo "<h2 class=c>BroSet Export</h2>
";

if (!isset($_POST['BroSet']))
  Form();
  #######

$BroSetId = Clean($_POST['BroSet'], FT_INT);
$FoliosPropsPMemsByNameB = isset($_POST['FoliosPropsPMemsByName']);
$IncludedBroSetsInFullB  = isset($_POST['IncludedBroSetsInFull']);

$bsO = $DB->ObjQuery(sprintf("Select * From %s.BroSets Where Id=$BroSetId", DB_Braiins));
$BTypeN = (int)$bsO->BTypeN;
if ($BTypeN >= BroSet_Out_Main) {
  # Out-BroSet
  $TxName = TaxnStr(json_decode($bsO->Data)[BSI_TaxnIdsA][0]);
  define('DB_Tx', DB_Prefix.str_replace('-', '_', $TxName));
  define('Com_Inc_Tx', Com_Inc."$TxName/");
  define('Com_Str_Tx', Com_Str."$TxName/");
  require Com_Inc_Tx.'ConstantsTx.inc';
  require "./$TxName/inc/TxFuncs.inc";    # Tx specific funcs
  require Com_Str_Tx.'NamespacesRgA.inc'; # $NamespacesRgA
  require Com_Str_Tx.'TupNamesA.inc';     # $TupNamesA
  require Com_Str_Tx.'TuMesA.inc';        # $TuMesA
  if ($FoliosPropsPMemsByNameB) {
    require Com_Str_Tx.'Hypercubes.inc';  # $HyDimsA  $HyNamesA
    require Com_Str_Tx.'DimNamesA.inc';   # $DimNamesA
    require Com_Str_Tx.'DiMeNamesA.inc';  # $DiMeNamesA
    $FolioHyNamesA  = &$HyNamesA; unset($HyDimsA);
    $PropDimNamesA  = &$DimNamesA;
    $PMemDiMeNamesA = &$DiMeNamesA;
  }
}else{
  # In-BroSet
  if ($FoliosPropsPMemsByNameB) {
    require Com_Str.'Folios.inc';     # $FolioPropsA  $FolioNamesA
    require Com_Str.'PropNamesA.inc'; # $PropNamesA
    require Com_Str.'PMemNamesA.inc'; # $PMemNamesA
    $FolioHyNamesA  = &$FolioNamesA; unset($FolioPropsA);
    $PropDimNamesA  = &$PropNamesA;
    $PMemDiMeNamesA = &$PMemNamesA;
  }
}
if (!$FoliosPropsPMemsByNameB)
  $FolioHyNamesA = $PropDimNamesA = $PMemDiMeNamesA = false;

require Com_Str.'Zones.inc'; # $ZonesA  $ZoneRefsA re ZonesCsStrList()
unset ($ZoneRefsA);          # Just $ZonesA is used by ZonesCsStrList()

$BroSetPath = Com_Str."BroSet$BroSetId/";
require $BroSetPath.'BroInfoA.inc';      # $BroInfoA
require $BroSetPath.'BroNamesA.inc';     # $BroNamesA
require $BroSetPath.'BroShortNamesA.inc';# $BroShortNamesA
require $BroSetPath.'BroSumTreesA.inc';  # $BroSumTreesA $CheckBrosA {$SumEndBrosA $PostEndBrosA $StockBrosA}

#file = "/BrosAndTx/BroSet-$bsO->Name-".gmstrftime('%Y-%m-%d_%H_%M').'.txt';
$file = sprintf('/BrosAndTx/BroSet-%s-%s-%s.txt', $bsO->Name, $FoliosPropsPMemsByNameB ? 'Names' : 'Ids', gmstrftime('%Y-%m-%d_%H_%M'));
$Fh = fopen('../'.$file, 'w');
fwrite($Fh, "\xEF\xBB\xBF"); # Write UTF-8 BOM

$typeS = BroSetTypeStr($BTypeN);
echo "<p class=c>Exporting the $typeS BroSet $bsO->Name, $bsO->Descr<br>
to the file <a href='Show.php?$file'>Admin$file</a> in tab delimited form.</p>
";

ExportBroSet($BroSetId, $bsO);
fclose($Fh);
Form();

function ExportBroSet($broSetId, $bsO=0) {
  global $DB, $BroInfoA, $BroNamesA, $Fh, $NamespacesRgA, $FolioHyNamesA, $PropDimNamesA, $PMemDiMeNamesA, $IncludedBroSetsInFullB;
  static $BroSetLevel;
  ++$BroSetLevel;

  if (!$bsO) # an Include
    $bsO = $DB->ObjQuery(sprintf("Select * From %s.BroSets Where Id=$broSetId", DB_Braiins));
  $BSDataA = json_decode($bsO->Data);
  if ($bsO->BTypeN >= BroSet_Out_Main) {
    # Out-BroSet
    $OutBroSetB = 1;
    $hdg = 'BroId	Type	Level	BroName	Name0	N1	N2	N3	N4	N5	N6	N7	N8	ShortName	Master	Ref	TxId	Hys	TupId	DataType	DboField	Sign	PostType	ExclDims	AllowDims	DiMes	ManDims	NoTags	SumUp	Check	Period	StartEnd	Zones	Order	Descr	Countries	EntityTypes	Comment	Scratch	I Tx Std Label	I UsableDims	I Sum UsableDims	I M# DiMe Info	I Tag	I Tx Type	I Tx Sign	I Tx Hys	I Tuple	I Slave Ids	I Slave Filtering';
    $PropDimNmes = 'Dims';
    $PMemDiMeNme = 'DiMe';
    $IsListSubSetFn = 'IsDimsListSubset';
  }else{
    # In-BroSet
    $OutBroSetB = 0;
    $hdg = 'BroId	Type	Level	BroName	Name0	N1	N2	N3	N4	N5	N6	N7	N8	ShortName	Master	Ref	Folio	DataType	DboField	Sign	PostType	RO	ExclProps	AllowProps	Members	SumUp	Check	Related	Period	StartEnd	Zones	Order	Descr	Taxonomies	Countries	EntityTypes	Comment	Scratch	I UsableProps	I Sum UsableProps	I M Use Member Info	I StartEnd	I Slave Ids	I Slave Filtering';
    $PropDimNmes = 'Props';
    $PMemDiMeNme = 'PMem';
    $IsListSubSetFn = 'IsPropsListSubset';
  }
  # The BroSet Row is output via comments
  # Define Bro rows if applicable are output via comments

  $prevId = $postRowComments = 0;
  $prevName = '';

  $brosA = [];
  $res = $DB->ResQuery("Select * From BroInfo Where BroSetId=$broSetId Order By Id");
  if ($res->num_rows > count($BSDataA[BSI_IncludesA]))
    # If the number of rows is > the number of includes output the heading row. The BroSet row comes via RowComments
    fwrite($Fh, $hdg.NL);
  while ($o = $res->fetch_object()) {
    if ($o->InclBroSetId) {
      if ($o->RowComments && ($BroSetLevel === 1 || $IncludedBroSetsInFullB)) {
        # Got some row comments
        # row comment{row comment...}{row comment{row comment...}}
        $commentsA = explode('', $o->RowComments); # split before and after
        if (count($commentsA)>1) $postRowComments=$commentsA[1]; # after
        if ($commentsA[0])
          foreach (explode('', $commentsA[0]) as $c)
            fwrite($Fh, $c.NL);
      }
      if ($IncludedBroSetsInFullB)
        ExportBroSet($o->InclBroSetId);
      else{
        if ($BroSetLevel === 1) {
          $row = sprintf("Include BroSet %s", $DB->StrOneQuery(sprintf("Select Name From %s.BroSets Where Id=$o->InclBroSetId", DB_Braiins)));
          if ($o->Comment)
            $row .= SP.$o->Comment;
          fwrite($Fh, $row.NL);
        }
      }
    }else{
      $broA  = BroInfo($o, true); # true = include Scratch and RowComments
      $broId = $broA['BroId'];
      unset($broA['InclBroSetId'], $broA['BroId'], $broA['Comments']); # unset unused ones to save memory
      $brosA[$broId] = $broA;
    }
  }
  $res->free();

  #########################################################################################
  # If editing this code remember the similar code in BuildStructs.php and BrosLookup.php #
  #########################################################################################
  foreach ($brosA as $BroId => $broA) {
    extract($broA);
    # -> $BroId, $Name, $Level, $DadId, $Bits, $DataTypeN, $DboFieldN, $SignN, $SumUp, $PeriodSEN, $SortOrder, $FolioHys,
    #    $ExclPropDims, $AllowPropDims, $PMemDiMesA, $UsablePropDims, $Related, $SeSumList, $MasterId, $SlaveYear, $CheckTest, $Zones, $ShortName, $Ref, $Descr, $Data, $Scratch, $RowComments
    #    {, $TxId {, $TupId, $ManDims, $xA}}
    $PMemDiMesA = $PMemDiMesA ? json_decode($PMemDiMesA) : 0;

    # echo $BroId, '<br>';
    $Bits = $BroInfoA[$BroId][BroI_Bits]; # Use the BroInfoA Bits re the BroB_Master setting that might only be set in the struct if the master came via an include

    if ($DadId) $dadA = $brosA[$DadId];

    # Fields for all cases
    $id = ($BroId === $prevId+1) ? $BroId : " =$BroId";
    $broType = BroTypeStr($Bits);
    # Level as is
    # Name, Name Segs
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
      die("More than the 8 allowed for levels in $BroId $Name");
    if ($Level < 8)
      $segs .= str_repeat(TAB, 8-$Level);
    # ShortName as is

    # Master/Slave
    if ($MasterId) {
      #########
      # Slave #
      #########
      $masterBroA = $BroInfoA[$MasterId]; #  $brosA[$MasterId];
      $master = ($SlaveYear ? "Year$SlaveYear " : '').$MasterId.SP.BroShortNameOrName($MasterId);
      # Slave filtering could apply if Usable list is different, but only if the Master Usable list is not a subset of the Slave's.
      # (Master Usable list can be a subset of the Slave's if ExclPropDims has been used with the Master but not the Slave = no filtering applies.)
      if ($UsablePropDims && ($UsablePropDims !== $masterBroA[BroI_BroUsablePropDims] && !$IsListSubSetFn($masterBroA[BroI_BroUsablePropDims], $UsablePropDims))) {
        $slaveFiltering = ", Usable $PropDimNmes";
        $broUsablePropDims = ChrListToIdsOrShortNamesStr($UsablePropDims, $PropDimNamesA);
      }else{
        $slaveFiltering = '';
        # No UsableDims for an Ele Slave without Usable list filtering
        $broUsablePropDims = ($Bits & BroB_Ele) ? '' : ChrListToIdsOrShortNamesStr($UsablePropDims, $PropDimNamesA);
      }
      if ($PMemDiMesA) {
        $slaveFiltering .= ", $PropDimNmes";
        $pMemDiMes = PMemDiMesAStr($PMemDiMesA, $PMemDiMeNamesA);
      }else
        $pMemDiMes = '';
      $slaveFiltering = $slaveFiltering ? substr($slaveFiltering, 2) : '';
      $sumUsablePropDims = $slaveIds = '';
    }else{
      #############
      # Non-Slave # = Std Bro. Could be Master.
      #############
      $master = $MasterId = $slaveFiltering = '';
      $slaveIds = ($Bits & BroB_Master) ? implode(', ', $BroInfoA[$BroId][BroI_SlaveIdsA]) : ''; # implode sep of ', ' so that a simple import into Excel doesn't treat multiple CS Ids as a big number
      $pMemDiMes         = PMemDiMesAStr($PMemDiMesA, $PMemDiMeNamesA);
      $broUsablePropDims = ChrListToIdsOrShortNamesStr($UsablePropDims, $PropDimNamesA);
      $sumUsablePropDims = ChrListToIdsOrShortNamesStr($BroInfoA[$BroId][BroI_SumUsablePropDims], $PropDimNamesA);
    }

    # Ref as it comes

    if ($DataTypeN === DT_Money) {
      $sign = SignToStr($SignN);
      $postType = ($Bits & BroB_DE) ? 'DE' : 'Sch';
    }else
      $sign = $postType = '';

    $folioHys  = ChrListToIdsOrShortNamesStr($FolioHys, $FolioHyNamesA);
    $dataType  = $DataTypeN ? DataTypeStr($DataTypeN) : ''; # ? to avoid 'None' for Export in the 0 case
    $DboField  = DboFieldStr($DboFieldN);
    $exclPropDims  = ChrListToIdsOrShortNamesStr($ExclPropDims, $PropDimNamesA);
    $allowPropDims = ChrListToIdsOrShortNamesStr($AllowPropDims, $PropDimNamesA);
    $zones     = ZonesCsStrList($Zones);
    $sortOrder = ZeroToEmpty($SortOrder);
    $sumUp     = SumUpStr($SumUp);
    $check     = CheckTestStr($CheckTest);
    if ($PeriodSEN === BPT_Duration)
      $period = $startEnd = ''; # Duration -> '' for export
    else
      list($period, $startEnd) = PeriodSENStrA($PeriodSEN, $SeSumList);
    $mDiMeInfo= MtypeInfo($Bits, $PMemDiMeNme);

    # Descr, Comment, and Scratch as they come

    # Data
    if ($Data) {
      $DataA = json_decode($Data);
      $Taxonomies  = IdArraysStrViaFn($DataA[BII_TaxnIdsA],  $DataA[BII_NotTaxnIdsA],  'TaxnStr', ', ');
      $Countries   = IdArraysStrViaFn($DataA[BII_CtryIdsA],  $DataA[BII_NotCtryIdsA],  'CountryShortName', ', ');
      $EntityTypes = IdArraysStrViaFn($DataA[BII_ETypeIdsA], $DataA[BII_NotETypeIdsA], 'EntityTypeStr', ', ');
    }else
      $Taxonomies = $Countries = $EntityTypes = '';

    # RowComments
    # row comment{row comment...}{row comment{row comment...}}
    if ($RowComments) {
      # Got some row comments
      $commentsA = explode('', $RowComments); # split before and after
      if (count($commentsA)>1) $postRowComments=$commentsA[1]; # after
      if ($commentsA[0])
        foreach (explode('', $commentsA[0]) as $c)
          fwrite($Fh, $c.NL);
    }

    if ($OutBroSetB) {
      # Out-BroSet
      # TxId
      if ($TxId) {
        # Taxonomy based
        $noTags  = ($Bits & BroB_NoTags) ? 'NoTags' : '';
        $txId    = $TxId;
        $tag     = $NamespacesRgA[(int)$xA['NsId']].':'.$xA['name'];
        $txDescr = $xA['Text'];
        $txType  = ElementTypeToStr($xA['TypeN']);
        $txSign  = $xA['TypeN'] == TET_Money ? SignToStr($xA['SignN']) : '';
        $txHys   = ChrListToIdsOrShortNamesStr($xA['Hypercubes'], $FolioHyNamesA);
        if ($TupId) {
          $tupId = $TupId;
          $tuple = ExpandTuple($TxId, $TupId); # Tuple expanded with TuMeId TUCN and tuple short names
        }else
          $tupId = $tuple = '';
        $manDims = ChrListToIdsOrShortNamesStr($ManDims, $PropDimNamesA);
      }else
        $txId = $noTags = $manDims = $tag = $txDescr = $txType = $startEnd = $startEndInfo = $txSign = $txHys = $tupId = $tuple = '';

      # Out-BroSet:    BroId	Type	Level	BroName	Name0	N1	N2	N3	N4	N5	N6	N7	N8	ShortName	Master	Ref	TxId	Hys	TupId	DataType	DboField	Sign	PostType	ExclDims	AllowDims	DiMes	ManDims	NoTags	SumUp	Check	Period	StartEnd	Zones	Order	Descr	Countries	EntityTypes	Comment	Scratch	I Tx Std Label	I UsableDims	I Sum UsableDims	I M# DiMe Info	I Tag	I Tx Type	I Tx Sign	I Tx Hys	I Tuple	I Slave Ids	I Slave Filtering
      fwrite($Fh, rtrim("$id	$broType	$Level	$name$segs	$ShortName	$master	$Ref	$txId	$folioHys	$tupId	$dataType	$DboField	$sign	$postType	$exclPropDims	$allowPropDims	$pMemDiMes	$manDims	$noTags	$sumUp	$check	$period	$startEnd	$zones	$sortOrder	$Descr	$Countries	$EntityTypes	$Comment	$Scratch	$txDescr	$broUsablePropDims	$sumUsablePropDims	$mDiMeInfo	$tag	$txType	$txSign	$txHys	$tuple	$slaveIds	$slaveFiltering").NL);
    }else{
      # In-BroSet
      $ro    = ($Bits & BroB_RO) ? 'RO' : '';
      $related  = RelatedStr($Related);
      $startEndInfo = $SeSumList ? StrField($startEnd, SP, 0).SP.$SeSumList : ''; # With SumList as BroIds
      # In-BroSet:     BroId	Type	Level	BroName	Name0	N1	N2	N3	N4	N5	N6	N7	N8	ShortName	Master	Ref	Folio	DataType	DboField	Sign	PostType	RO	ExclProps	AllowProps	Members	SumUp	Check	Related	Period	StartEnd	Zones	Order	Descr	Taxonomies	Countries	EntityTypes	Comment	Scratch	I UsableProps	I Sum UsableProps	I M Use Member Info	I StartEnd	I Slave Ids	I Slave Filtering
      fwrite($Fh, rtrim("$id	$broType	$Level	$name$segs	$ShortName	$master	$Ref	$folioHys	$dataType	$DboField	$sign	$postType	$ro	$exclPropDims	$allowPropDims	$pMemDiMes	$sumUp	$check	$related	$period	$startEnd	$zones	$sortOrder	$Descr	$Taxonomies	$Countries	$EntityTypes	$Comment	$Scratch	$broUsablePropDims	$sumUsablePropDims	$mDiMeInfo	$startEndInfo	$slaveIds	$slaveFiltering").NL);
    }
    $prevId = $BroId;
  } # End of loop thru Bros
  # Post Comment row comment{row comment...}
  if ($postRowComments)
    foreach (explode('', $postRowComments) as $c)
      fwrite($Fh, $c.NL);
  --$BroSetLevel;
} # End of function ExportBroSet

function Form() {
  global $DB, $BroSetId;
  echo "<p class=c>Select the BroSet to Export, check any options required and click Export BroSet<br>(Only BroSets which have imported without error or with just Defined Bro errors can be selected.)</p>
<form method=post>
<table class=mc>
<tr class='b bg0'><td></td><td>Id</td><td>Type</td><td>Name</td><td>Description</td><td>Taxonomies</td></tr>
";
  $res=$DB->ResQuery(sprintf('Select * from %s.BroSets Order By SortKey', DB_Braiins));
  while ($bsO = $res->fetch_object()) {
    $id      = (int)$bsO->Id;
    $status  = (int)$bsO->Status;
    $typeS   = BroSetTypeStr($bsO->BTypeN);
    $BSDataA = json_decode($bsO->Data);
    $taxns   = IdArraysStrViaFn($BSDataA[BSI_TaxnIdsA],  $BSDataA[BSI_NotTaxnIdsA], 'TaxnStr');
    if ($status & (Status_OK | Status_DefBroErrs)) {
      # BroSet that has imported without errors or has imported with Defined Bro Errors
      $checked = $BroSetId==$id ? ' checked' : '';
      echo "<tr><td><input id=f$id type=radio class=radio name=BroSet value=$id$checked></td><td class=c><label for=f$id>$id</label></td><td><label for=f$id>$typeS</label></td><td><label for=f$id>$bsO->Name</label></td><td><label for=f$id>$bsO->Descr</label></td><td>$taxns</td><tr>\n";
    }else
      echo "<tr><td></td><td class=c>$id</td><td>$typeS</td><td>$bsO->Name</td><td>$bsO->Descr</td><td>$taxns</td><tr>\n";
  }
  $res->free();
  echo "</table>
  <div class=mc style=width:430px>
  <p><input id=cbf class=radio type=checkbox name=FoliosPropsPMemsByName value=1 checked> <label for=cbf>Export Folios, Properties and Members by Name rather than Ids</label><br>
     <input id=cbl class=radio type=checkbox name=IncludedBroSetsInFull value=1> <label for=cbl>Export any included BroSets in full</label></p>
  </div>
<p class=c><button class='on mt10'>Export BroSet</button></p>
</form>
";
  Footer(false); # Footer($timeB=true, $topB=false, $notCentredB=false) {
}
