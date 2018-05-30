<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/BrosSetImport.php

Import a BroSet

History:
22.03.13 Started based on UK-GAAP-DPL version
06.04.13 Renamed from BrosImport.php
03.07.13 B R L -> SIM
07.08.13 Removed AcctTypes

To Do djh??
=====

Make Folios... Hys... ->  invalid if DboField is used i.e. -> No UsableProps

Make Period invalid if no DataType

Add Member Excl Rule Code handling

Add a check for DboField DataType versus DataTypeN

Make <Either etc for Check optional as the doc says

*/

require 'BaseSIM.inc';
require './inc/BroInfo.inc';
require Com_Inc.'FuncsSIM.inc';
require Com_Inc.'DateTime.inc';
require Com_Str.'Zones.inc';    # $ZonesA $ZoneRefsA
unset($ZonesA);

const COL_BroId     =  0;
const COL_Type      =  1;
const COL_Name0     =  4;
const COL_ShortName = 13;
const COL_Master    = 14;
const COL_Ref       = 15;
const Num_Name_Cols =  9;
# From here onwards the Cols are different for Out-BroSets vs In-BroSets so the remaining col constants are defined once the BroSet Type is known

# Types just for use by Bros Import
const Slave_MasterExplicit = 1;
const Slave_MasterMatch    = 2;

Head('Import BroSet', true);
echo "<h2 class=c>BroSet Import</h2>
";

if (!isset($_POST['Bros']))
  Form(false);
  ####

#bros= Clean($_POST['Bros'], FT_TEXTAREA);
$bros = trim(preg_replace('/(  +)/m', ' ', $_POST['Bros'])); # trim and reduce internal spaces to one space /- djh?? Do in one preg_replace?
$bros = preg_replace('/( 	 )|(	 )|( 	)/m', TAB, $bros);     # trim spaces around tabs                      |
$RowsA = explode(NL, $bros);
unset($bros);
$_POST =        # to empty it to save space, not unset in case of an Error() call
$BroSetNamesA = # [BroSetId => BroSet Name]
$TaxnNamesA   = # [TaxnId  => Taxonomy ShortName] for Live taxonomies
$CtryRefsA    = # [CtryId  => Country Ref (ShortName)]
$ETypesA      = # [ETypeId => [EntityTypes.Data.CtryIdsA, EntityTypes.Data.SName]]
$broSetPropsA = # temp during processing of the BroSet statement
$SetMemNumsA  = # BroId => count, 0 for set +1 for each member i.e. Bro with the same $DadId -> check for sets with count == 0
$IbrosA       = # Import Bros    [BroId  => BroA]  BroA records the BroSetId
$RowMapA      = # Rows to BroId  [RowNum => BroId] for all rows in this import (i.e. not for Included BroSets) with BroId = 0 for a blank/comment row, -IncludeBroSetId for an Include row, and -999999 for a BIError() row
$RowCommentsA = # RowCommenst    [RowNum => RowComments] for rows with RowComments i.e. not for every RowNum. Only RowNums which will result in a BroInfo entry are used i.e. Includes and Bros
$NamesA       = # BroNames       [Name   => BroId]
$ShortNamesA  = # Bro ShortNames [ShortName => BroId]
$ErrorsA = $NoticesA = $WarningsA = [];
$BroSetTypeN = $BroSetTaxnId = $BroSetTypeRow = $BroSetLastRow = $HeadingsRow = $RowNum = $InBroSetId = 0; # $BroSetTypeN and $BroSetTaxnId are used during the import
$BsDataA = [
  0, # InBroSetId int  In-MaIn-BroSet Id for a Out-MaIn-BroSet, 0 o'wise
  null, # IncludesA   i [] of the BroSetIds of the included BroSets, if any. Includes InBroSetId if that is defined.
  null, # TaxnIdsA    i [] of Taxonomies  that the BroSet applies to. OK if in this list || not in this list and not in NotTaxnIdsA
  null, # CtryIdsA    i [] of Countries   that the BroSet applies to. OK if in this list || not in this list and not in NotCtryIdsA
  null, # ETypesA     i [] of EntityTypes that the BroSet applies to. OK if in this list || not in this list and not in NotETypesA
  null, # NotTaxnIdsA i [] of Taxonomies  that the BroSet does not apply to
  null, # NotCtryIdsA i [] of Countries   that the BroSet does not apply to
  null, # NotETypesA  i [] of EntityTypes that the BroSet does not apply to
  0, # DateFrom   int  Date if can only be used with Entities with YearEnd on or after this,  0 o'wise
  0, # DateTo     int  Date if can only be used with Entities with YearEnd on or before this, 0 o'wise
  0, # BSI_OfficersBroId
  0  # BSI_TPAsBroId
];
$DefinedBroNamesA = [
  BSI_OfficersBroId  => 'OfficersBro',
  BSI_TPAsBroId      => 'TPAsBro'
];
/* BroSets.Data indices
const BSI_InBroSetId   = 0;
const BSI_IncludesA    = 1;
const BSI_TaxnIdsA     = 2;
const BSI_CtryIdsA     = 3;
const BSI_ETypeIdsA    = 4;
const BSI_NotTaxnIdsA  = 5;
const BSI_NotCtryIdsA  = 6;
const BSI_NotETypeIdsA = 7;
const BSI_DateFrom     = 8;
const BSI_DateTo       = 9;
const BSI_OfficersBroId = 10;
const BSI_TPAsBroId     = 11;
*/

# Read the BroSet Names
$res = $DB->ResQuery(sprintf('Select Id,Name from %s.BroSets', DB_Braiins));
while ($o = $res->fetch_object())
  $BroSetNamesA[(int)$o->Id] = $o->Name;
$res->free();
# Read the Live Taxonomy Names
$res = $DB->ResQuery(sprintf('Select Id,Name from Taxonomies Where Bits&%d>0', TaxnB_DB));
while ($o = $res->fetch_object())
  $TaxnNamesA[(int)$o->Id] = $o->Name;
$res->free();
# Read the Country Refs (ShortNames)
$res = $DB->ResQuery('Select Id,Ref from Countries');
while ($o = $res->fetch_object())
  $CtryRefsA[(int)$o->Id] = $o->Ref;
$res->free();
# Read the EntityTypes [ETypeId => [EntityTypes.Data.CtryIdsA, EntityTypes.Data.SName]]
$res = $DB->ResQuery('Select Id,Data from EntityTypes');
while ($o = $res->fetch_object()) {
  $dataA = json_decode($o->Data);
  $ETypesA[(int)$o->Id] = [$dataA[ETI_CtryIdsA], $dataA[ETI_SName]];
}
$res->free();
unset($o);

# Headings
$inHdg = "BroId	Type	Level	BroName	Name0	N1	N2	N3	N4	N5	N6	N7	N8	ShortName	Master	Ref	Folio	DataType	DboField	Sign	PostType	RO	ExclProps	AllowProps	Members	SumUp	Check	Related	Period	StartEnd	Zones	Order	Descr	Taxonomies	Countries	EntityTypes	Comment	Scratch"; # Import cols  only
$ouHdg = "BroId	Type	Level	BroName	Name0	N1	N2	N3	N4	N5	N6	N7	N8	ShortName	Master	Ref	TxId	Hys	TupId	DataType	DboField	Sign	PostType	ExclDims	AllowDims	DiMes	ManDims	NoTags	SumUp	Check	Period	StartEnd	Zones	Order	Descr	Countries	EntityTypes	Comment	Scratch"; # Import cols  only

# Look for Headings, BroSet statements, and Defined Bros
foreach ($RowsA as $Row) {
  ++$RowNum; # starting from 1
  $row = rtrim($Row); # not trim() re possible Bro row leading tab for an empty BroId col
  if (!strlen($row))
    continue;
  if (!strncmp('BroId', $row, 5)) {
    ############
    # Headings # Looks like start of a Headings row
    ############ Don't have to have a Headings row
    if (substr($row,0,strlen($inHdg)) === $inHdg) {
      if ($BroSetTypeRow && $BroSetTypeN >= BroSet_Out_Main)
        ErrorExit("The Row $RowNum Headings are In-BroSet ones which are inconsistent with the BroSet Type statement at Row $BroSetTypeRow which is an Out-BroSet one");
      $BroSetTypeN = BroSet_In_Main; # could become BroSet_In_Incl when #BroSet is processed
      $HeadingsRow = $RowNum;
    }else if (substr($row,0,strlen($ouHdg)) === $ouHdg) {
      if ($BroSetTypeRow && $BroSetTypeN < BroSet_Out_Main)
        ErrorExit("The Row $RowNum Headings are Out-BroSet ones which are inconsistent with the BroSet Type statement at Row $BroSetTypeRow which is an In-broSet one");
      $BroSetTypeN = BroSet_Out_Main;  # could become BroSet_Out_Incl  when #BroSet is processed
      $HeadingsRow = $RowNum;
    }else
      ErrorExit("The Row $RowNum Headings do not match either In or Out-BroSet ones. The following rows give: Import row / In Headings / Out Headings<br>$row<br>$inHdg<br>$ouHdg");
    continue;
  }
  if (!strncmp('BroSet', $row, 6)) {
    ###############
    # BroSet Rows #
    ###############
    #   Mandatory
    # 0 BroSet Type: tttttttt {# Comment - applies to all BroSet rows}
    # 1 BroSet Name: nnnnn
    # 2 BroSet Descr: ddddd
    #   Optional
    # 3 BroSet SortKey: kkkk
    # 4 BroSet Taxonomies: 2 UK-GAAP-DPL
    # 5 BroSet Countries: 1 UK
    # 6 BroSet EntityTypes: 5 UK Private Company
    # 7 BroSet DateFrom: yyyy-mm-dd
    # 8 BroSet DateTo: yyyy-mm-dd
    $BroSetLastRow = $RowNum;
    # Chop off comment if present. It will be stored courtesy of the whole row being stored as a RowComment in Pass 1
    if ($p = strpos($row, '#'))
      $row = substr($row, 0, $p);
    $row = trim(substr($row, 6));
    $broSetStatePropsA = ['Type', 'Name', 'Descr', 'SortKey', 'Taxonomies', 'Countries', 'EntityTypes', 'DateFrom', 'DateTo'];
    if (!$j = Matchn($row, $broSetStatePropsA))
      ErrorExit("Row not recognised as one of the BroSet statements which must start as BroSet followed by one of ".implode(', ', $broSetStatePropsA));
    $propNme = $prop = $broSetStatePropsA[--$j];
    $row  = trim(substr($row, strlen($prop)));
    if ($row[0] === ':') $row = trim(substr($row, 1)); # step over :
    if (!strlen($row))
      ErrorExit("No value(s) follow the BroSet $prop statement");
    $val = 0; # $val gets for those props not in DataA
    switch ($j) {
      case 0: # Type
        if (!$t = Match($row, ['In-Main', 'In-Incl', 'Out-Main', 'Out-Incl']))
          ErrorExit("The value |$row| in the BroSet Type statement is not a known BroSet type. The available types are: 'In-Main', 'In-Incl', 'Out-Main', 'Out-Incl'");
        if ($HeadingsRow && ($t !== $BroSetTypeN && $t !== $BroSetTypeN+1))
          ErrorExit("The BroSet Type statement is inconsistent with the headings in Row $HeadingsRow");
        $BroSetTypeRow = $RowNum;
        $broSetType  = $row;
        $BroSetTypeN = $val = $t;
        $propNme = 'BTypeN';
        break;
      case 1:  # Name
        $broSetName = $row;
      case 2:  # Descr
      case 3:  # SortKey
        $val = $row;
        break;
      case 4:  # Taxonomies
        foreach (explode(COM, $row) as $t) {
          $t = trim($t);
          if ($t[0] === '-') {
            $idx = BSI_NotTaxnIdsA;
            $t = substr($t, 1);
          }else
            $idx = BSI_TaxnIdsA;
          if ((!($id = (int)$t) || !isset($TaxnNamesA[$id])) && ($id = array_search($t, $TaxnNamesA)) === false)
            ErrorExit("The value |$t| in the BroSet $prop statement is not a known live Taxonomy. The available live taxonomies are: ".implode(', ',$TaxnNamesA));
          if (($BsDataA[BSI_TaxnIdsA] && in_array($id, $BsDataA[BSI_TaxnIdsA])) || ($BsDataA[BSI_NotTaxnIdsA] && in_array($id, $BsDataA[BSI_NotTaxnIdsA])))
            ErrorExit("Taxonomy $t in the BroSet $prop statement is duplicated. A Taxonomy can only be specified once in a BroSet $prop statement");
          $BsDataA[$idx][] = $id;
          $TxName = $TaxnNamesA[$id]; # for Out-BroSet use on assumption here of there being only one
        }
        break;
      case 5:  # Countries
        foreach (explode(COM, $row) as $t) {
          $t = trim($t);
          if ($t[0] === '-') {
            $idx = BSI_NotCtryIdsA;
            $t = substr($t, 1);
          }else
            $idx = BSI_CtryIdsA;
          if ((!($id = (int)$t) || !isset($CtryRefsA[$id])) && ($id = array_search($t, $CtryRefsA)) === false)
            ErrorExit("The value |$t| in the BroSet $prop statement is not a known Country Ref (ShortName). The available countries are: ".implode(', ',$CtryRefsA));
          if (($BsDataA[BSI_CtryIdsA] && in_array($id, $BsDataA[BSI_CtryIdsA])) || ($BsDataA[BSI_NotCtryIdsA] && in_array($id, $BsDataA[BSI_NotCtryIdsA])))
            ErrorExit("Country $t in the BroSet $prop statement is duplicated. A Country can only be specified once in a BroSet $prop statement");
          $BsDataA[$idx][] = $id;
        }
        break;
      case 6:  # EntityTypes
        foreach (explode(COM, $row) as $t) {
          $t = trim($t);
          if ($t[0] === '-') {
            $idx = BSI_NotETypeIdsA;
            $t = substr($t, 1);
          }else
            $idx = BSI_ETypeIdsA;
          # $ETypesA  [ETypeId => [EntityTypes.Data.CtryIdsA, EntityTypes.Data.SName]]
          if (!($id = (int)$t) || !isset($ETypesA[$id])) {
            $id = 0;
            foreach ($ETypesA as $ETypeId => $etA)
              foreach ($etA[0] as $ctryId)
                if ($CtryRefsA[$ctryId].SP.$etA[1] === $t) { # UK Private Company
                  $id = $ETypeId;
                  break;
                }
            if (!$id)
              ErrorExit("The value |$t| in the BroSet $prop statement is not a known EntityType. Use EntityType Id or CountryRef EntityTypeShortName e.g. 5 or UK Private Company");
            if (($BsDataA[BSI_ETypeIdsA] && in_array($id, $BsDataA[BSI_ETypeIdsA])) || ($BsDataA[BSI_NotETypeIdsA] && in_array($id, $BsDataA[BSI_NotETypeIdsA])))
              ErrorExit("EntityType $t in the BroSet $prop statement is duplicated. An EntityType can only be specified once in a BroSet $prop statement");
          }
          $BsDataA[$idx][] = $id;
        }
        break;
      case 7: # DateFrom
      case 8: # DateTo
        if (!$d = StrToDate($row))
          ErrorExit("|$row| in the BroSet $prop statement is not a valid date. Dates are expected to be in the {yy}yy-mm-dd format");
        $BsDataA[BSI_DateFrom + $j - 7] = $d;
        break;
    }
    if ($val) # not set for props stored in Data
      $broSetPropsA[$propNme] = $val;
    # End of BroSet statement processing
    continue;
  }
  # Not blank row, headings, or BroSet
  # Terminate this loop if it is an Include row or a Bro row which could start with Tab (empty BroId col), '=' (after a ltrim), or a BroId number
  if ($row[0] === TAB)
    break;
  $row = ltrim($row);
  if ($row[0] === '=' || # BroId col in form '=3000'
    !strncmp($row, 'Include BroSet', 14) || # Include statement
    ctype_digit(explode(TAB, $row)[0])) # digits in first TAB sep col
    break;
  # Check for Defined Bro Names
  foreach ($DefinedBroNamesA as $j => $broName) {
    $len = strlen($broName);
    if (!strncasecmp($broName, $row, $len)) {
      # Chop off comment if present. It will be stored courtesy of the whole row being stored as a RowComment in Pass 1
      if ($p = strpos($row, '#'))
        $row = substr($row, 0, $p);
      $BsDataA[$j] = trim(substr($row, $len), ' =	'); # Temporaily using the slot for the name, to be converted to BroId if found
      continue 2;
    }
  }
  # No matches - assumed comment to be handled in Pass 1
} # End of Headings, BroSet statements, and Defined Bros loop

# Check and save BroSet
if (!$BroSetLastRow)
  ErrorExit('Mandatory BroSet statements not found before this first Include or Bro row');

#Dump('$broSetPropsA',$broSetPropsA);

# Check that the mandatory props have been supplied
foreach (['BTypeN', 'Name', 'Descr'] as $prop)
  if (!isset($broSetPropsA[$prop]))
    ErrorExit("The BroSet statement(s) before this first Include or Bro row do not define the mandatory property $prop");
# Check $BroSetTaxnId
if ($broSetPropsA['BTypeN'] >= BroSet_Out_Main) {
  # Out-BroSet
  if ($BsDataA[BSI_TaxnIdsA]) {
    if (count($BsDataA[BSI_TaxnIdsA]) > 1)
      ErrorExit("The BroSet statement(s) before this first Include or Bro row specify an Out type BroSet but do not define a Taxonomy which is required for an Out-BroSet");
    $BroSetTaxnId = $BsDataA[BSI_TaxnIdsA][0];
  }else
    ErrorExit("The BroSet statement(s) before this first Include or Bro row specify an Out type BroSet but do not define a Taxonomy which is required for an Out-BroSet");
} # else In-BroSet

# Set SortKey to Name if not defined
if (!isset($broSetPropsA['SortKey'])) $broSetPropsA['SortKey'] = $broSetName;

# Get BroSetId and Add/Update the BroSet
$broSetPropsA['Data'] = json_encode($BsDataA, JSON_NUMERIC_CHECK);
$set = '';
if ($bsAA = $DB->OptAaQuery(sprintf("Select * From %s.BroSets Where Name='$broSetName'", DB_Braiins))) {
  $BroSetId = (int)$bsAA['Id'];
  # Update if any changes
  unset($bsAA['Id'], $bsAA['EditT'], $bsAA['AddT']);
  IntegeriseA($bsAA);
  foreach ($bsAA as $col => $dVal)
    if (isset($broSetPropsA[$col]) && # in both
        ($val = $broSetPropsA[$col]) !== $dVal) # not equal so update with import value. val = the import value, dVal = the DB val
      $set .= ",$col=" . (is_numeric($val) ? $val : SQ.addslashes($val).SQ);
  if ($set) {
    # Update without changing Status
    $set = substr($set, 1);
    #echo "Update query = ".sprintf("Update %s.BroSets Set $set,EditT=$DB->TnS Where Id=$BroSetId", DB_Braiins).'<br>';
    $DB->StQuery(sprintf("Update %s.BroSets Set $set,EditT=$DB->TnS Where Id=$BroSetId", DB_Braiins));
  }
}else{
  # Not found so add it with Status defaulting to 0 = no good
  foreach ($broSetPropsA as $col => $val)
    $set .= ",$col=" . (is_numeric($val) ? $val : SQ.addslashes($val).SQ);
  $set = substr($set, 1);
  #echo "Insert query = ".sprintf("Insert %s.BroSets Set $set,AddT=$DB->TnS", DB_Braiins).'<br>';
  $BroSetId = $DB->InsertQuery(sprintf("Insert %s.BroSets Set $set,AddT=$DB->TnS", DB_Braiins));
}
$broSetId = $BroSetId; # current BroSet
echo "<h2>Import of $broSetType BroSet $broSetName, $broSetPropsA[Descr]</h2>";
unset ($broSetPropsA, $broSetStatePropsA, $bsAA, $broSetName);

# Includes according to BroSet Type
if ($BroSetTaxnId) {
  # Out-BroSet
  # Following on from
  # const COL_Ref       = 15;
  define('COL_TxId',      16);
  define('COL_Hys',       17);
  define('COL_TupId',     18);
  define('COL_DataType',  19);
  define('COL_DboField',  20);
  define('COL_Sign',      21);
  define('COL_PostType',  22);
  define('COL_ExclPropDims',  23);
  define('COL_AllowPropDims', 24);
  define('COL_PMemDiMes',     25);
  define('COL_ManDims',    26);
  define('COL_NoTags',     27);
  define('COL_SumUp',      28);
  define('COL_Check',      29);
  define('COL_Period',     30);
  define('COL_StartEnd',   31);
  define('COL_Zones',      32);
  define('COL_Order',      33);
  define('COL_Descr',      34);
  define('COL_Countries',  35);
  define('COL_EntityTypes',36);
  define('COL_Comment',    37);
  define('COL_Scratch',    38);
  define('Num_Imp_Cols',   39); # One more than COL_Scratch re 0 base. Used in checking for empty import cols ignoring the I columns at the right

  define('DB_Tx', DB_Prefix.str_replace('-', '_', $TxName));
  define('Com_Inc_Tx', Com_Inc."$TxName/");
  define('Com_Str_Tx', Com_Str."$TxName/");
  require "./$TxName/inc/TxFuncs.inc";     # Tx specific funcs
  require Com_Inc_Tx.'ConstantsRgWip.inc'; # djh?? Rename.
  require Com_Inc_Tx.'ConstantsTx.inc';
  require Com_Str_Tx.'Hypercubes.inc'; # $HyDimsA  $HyNamesA
  require Com_Str_Tx.'DimNamesA.inc';  # $DimNamesA to be flipped
  require Com_Str_Tx.'DiMeNamesA.inc'; # $DiMeNamesA to be flipped
  require Com_Str_Tx.'DiMesA.inc';     # $DiMesA
  $FolioHyNamesMapA  = array_flip($HyNamesA);
  $PropDimNamesMapA  = array_flip($DimNamesA);
  $PMemDiMeNamesMapA = array_flip($DiMeNamesA);
  $PMemsDiMesAR = &$DiMesA;
  $NonSummingBroExclPropDimsAR = &$NonSummingBroExclDimsGA;
  unset($HyNamesA, $DimNamesA, $DiMeNamesA);

  $FolioHyNme   = 'Hypercube';
  $PropDimNme   = 'Dim';
  $PropDimNmes  = 'Dims';
  $PMemDiMeNme  = 'DiMe';
  $PMemDiMeNmes = 'DiMes';
  $PMemDiMeIdNme= 'DiMeId';
  $IsListSubSetFn = 'IsDimsListSubset';
  $IsFolioHySubsetFn = 'IsHypercubeSubset';

  # Out-BroSet only arrays
  $setTxNamesA =          # Used with Tx auto name generation
  $TxElesA =              # [TxId => Tx AA]
  $TxIdHyIdTupIdBroIdsA = # [TxId][HyId][TupId] => BroId for finding Masters for slaves using TxId.HyId.TupId matching, and for checking duplicate TxId HyId TupId use. Single dim array with string key used before 06.06.12 but little if any difference speed wise.
  $TuplesByTxIdA = [];    # [TxId => [i => TupId]] Tx TupIds by TxId read from TuplePairs

  # Read the Tx elements.
  $res = $DB->ResQuery(sprintf('Select Id,abstract,SubstGroupN,Hypercubes,PeriodN,SignN,TypeN,name From %s.Elements', DB_Tx));
  while ($xA = $res->fetch_assoc()) {
    $TxId = (int)$xA['Id'];
    unset($xA['Id']);
    $xA['abstract']   = (int)$xA['abstract'];
    $xA['SubstGroupN']= (int)$xA['SubstGroupN'];
    $xA['PeriodN']    = (int)$xA['PeriodN'];
    $xA['SignN']      = (int)$xA['SignN'];
    $xA['TypeN']      = (int)$xA['TypeN'];
    $TxElesA[$TxId] = $xA; # abstract, SubstGroupN, Hypercubes, PeriodN, SignN, TypeN, name
  }
  $res->free();
  unset($xA);
  # Read tuple Info
  # $TuplesByTxIdA[TxId => [i => TupId]]
  $res = $DB->ResQuery(sprintf('Select TupId,MemTxId from %s.TuplePairs', DB_Tx));
  while ($o = $res->fetch_object())
    $TuplesByTxIdA[(int)$o->MemTxId][] = (int)$o->TupId;
  $res->free();
  unset($o);
  $Related = $SeSumList = 0; # defaults which remain at this for Out-BroSet
  $PropDimMaxId  = count($PropDimNamesMapA)-1;
  $PMemDiMeMaxId = count($PMemsDiMesAR)-2; # -2 because of the final Unallocated
}else{
  # In-BroSet
  # Following on from
  # const COL_Ref       = 15;
  define('COL_Folio',     16);
  define('COL_DataType',  17);
  define('COL_DboField',  18);
  define('COL_Sign',      19);
  define('COL_PostType',  20);
  define('COL_RO',        21);
  define('COL_ExclPropDims', 22);
  define('COL_AllowPropDims',23);
  define('COL_PMemDiMes',    24);
  define('COL_SumUp',      25);
  define('COL_Check',      26);
  define('COL_Related',    27);
  define('COL_Period',     28);
  define('COL_StartEnd',   29);
  define('COL_Zones',      30);
  define('COL_Order',      31);
  define('COL_Descr',      32);
  define('COL_Taxonomies', 33);
  define('COL_Countries',  34);
  define('COL_EntityTypes',35);
  define('COL_Comment',    36);
  define('COL_Scratch',    37);
  define('Num_Imp_Cols',   38); # One more than COL_Scratch re 0 base. Used in checking for empty import cols ignoring the I columns at the right
  require Com_Str.'Folios.inc';     # $FolioPropsA  $FolioNamesA
  require Com_Str.'PropNamesA.inc'; # $PropNamesA
  require Com_Str.'PMemNamesA.inc'; # $PMemNamesA
  require Com_Str.'PMemsA.inc';     # $PMemsA

  $FolioHyNamesMapA  = array_flip($FolioNamesA);
  $PropDimNamesMapA  = array_flip($PropNamesA);
  $PMemDiMeNamesMapA = array_flip($PMemNamesA);
  $PMemsDiMesAR = &$PMemsA;
  $NonSummingBroExclPropDimsAR = &$NonSummingBroExclPropsGA;
  unset($FolioNamesA, $PropNamesA, $PMemNamesA);

  $FolioHyNme   = 'Folio';
  $PropDimNme   = 'Prop';
  $PropDimNmes  = 'Props';
  $PMemDiMeNme  = 'PMem';
  $PMemDiMeNmes = 'PMems';
  $PMemDiMeIdNme= 'PMemId';
  $IsListSubSetFn = 'IsPropsListSubset';
  $IsFolioHySubsetFn = 'IsFolioSubset';
  $nTxHys = $TxId = 0; # defaults which remain at this for In-BroSet
  $PropDimMaxId  = count($PropDimNamesMapA)-3; # -3 because of the final None and Unallocated
  $PMemDiMeMaxId = count($PMemsDiMesAR)-3;     # -3 because of the final None and Unallocated
}
$FolioHyMaxId  = count($FolioHyNamesMapA)-1;
#echo "FolioHyMaxId = $FolioHyMaxId<br>";
#echo "PropDimMaxId = $PropDimMaxId<br>";
#echo "PMemDiMeMaxId = $PMemDiMeMaxId<br>";

##########
# Pass 1 # Extract data, interpret commands, perform checks, and build $IbrosA
##########
{ # $FolioHyNamesMapA, $PropDimNamesMapA, $PMemDiMeNamesMapA, $PMemsDiMesAR, $ZoneRefsA, $RowsA, $TxElesA, $TuplesByTxIdA, $StartEndTxIdsGA, $TxIdHyIdTupIdBroIdsA
  $Errors = $BroId = $RowNum = $prevRowWasIncludeB = 0; # BroId and RowNum to start from 1
  $prevName = $RowComments = ''; # $RowComments can be multi line
  foreach ($RowsA as $Row) {
    ++$RowNum; # Starts at 1 to match SS content rows after headings and BroSet statemnent
    $Row = rtrim($Row); # not trim() re possible leading tab for a blank BroId col
    if (!strlen($Row)) {
      # blank row
      $RowComments .= ''; # for inclusion with the next stored Bro/Include or the last one if a fter the end
      $RowMapA[$RowNum] = 0; # set row map to 0 to indicate blank
      continue;
    }
    if ($Row[0] !== TAB) {
      # Not a presumed Bro row with a leading Tab, so ltrim it, then check for it being an Include or Bro row
      # with everything else being handled as a comment i.e. including Headings, BroSet, and defined Bro rows processed in the loop above
      $Row = ltrim($Row);
      if (!strncmp($Row, 'Include BroSet', 14)) {
        ###########
        # Include # Include BroSet nnnnnn {# Comment}
        ###########
        # Chop off comment if present. It will be stored in the BroInfo record by looking back at the Row
        if ($p = strpos($Row, '#'))
          $Row = substr($Row, 0, $p);
        $nme = trim(substr($Row, 14)); # Name
        $msg = "BroSet '$nme' in the Include BroSet statement";
        if (!$bsO = $DB->OptObjQuery(sprintf("Select Id,BTypeN,Status,Data from %s.BroSets Where Name='%s'", DB_Braiins, $nme)))
          ErrorExit("$msg is not a known Bro BroSet. Either correct the name or import '$nme' and then try this import again");
        $includeBroSetId = (int)$bsO->Id;
        if ($includeBroSetId === $BroSetId)
          ErrorExit("$msg is this BroSet. A BroSet cannot include itself. Correct the BroSet name");
        if ($bsO->Status != Status_OK) # djh?? Fix re defined bro warnings
          ErrorExit("$msg has errors and so cannot be included. Correct BroSet '$nme' and then try this import again");
        /* Legal Includes:
        In-Main: Can include In-Incl BroSets
        In-Incl: Can include In-Incl BroSets
        Out-Main: Must include a single In-MaIn-BroSet
                  Can include Out-Incl BroSets with the same TaxnId
        Out-Incl: Can include Out-Incl BroSets with the same TaxnId */
        switch ((int)$bsO->BTypeN) {
          case BroSet_In_Main:  # OK if one only is being included by an Out-MaIn-BroSet
            if ($BroSetTypeN !== BroSet_Out_Main)
              ErrorExit(sprintf("$msg is an In-MaIn-BroSet which can only be included by an Out-MaIn-BroSet but this BroSet is an %s type one", BroSetTypeStr($BroSetTypeN)));
            if ($InBroSetId)
              ErrorExit(sprintf("$msg is an In-MaIn-BroSet but only one In-MaIn-BroSet can be included by an Out-MaIn-BroSet (which this BroSet is), and the In-MaIn-BroSet $InBroSetId '%s' has already been included", $BroSetNamesA[$InBroSetId]));
            $BsDataA[BSI_InBroSetId] = $InBroSetId = $includeBroSetId;
            break;
          case BroSet_In_Incl: # Can be included by In-Main or In-Incl BroSets
            if ($BroSetTypeN >= BroSet_Out_Main)
              ErrorExit(sprintf("$msg is an In-Incl BroSet which can only be included by an In-Main or In-Incl BroSet but this BroSet is an %s type one", BroSetTypeStr($BroSetTypeN)));
            break;
          case BroSet_Out_Main: # Cannot be included
            ErrorExit("$msg is an Out-MaIn-BroSet which cannot be included by another BroSet");
            #########
          case BroSet_Out_Incl: # Can be included by Out-Main or Out-Incl BroSets with same TaxnId
            if ($BroSetTypeN < BroSet_Out_Main)
              ErrorExit(sprintf("$msg is an Out-Incl BroSet which can only be included by an Out-Main or Out-Incl BroSet but this BroSet is an %s type one", BroSetTypeStr($BroSetTypeN)));
            # This is an Out-BroSet
            $includeBroSetTaxnId = json_decode($bsO->Data)[BSI_TaxnIdsA][0];
            if ($includeBroSetTaxnId !== $BroSetTaxnId)
              ErrorExit(sprintf("$msg is an Out-Incl BroSet using Taxonomy %s which can only be included into a BroSet using the same Taxonomy but this BroSet uses %s", TaxnStr($includeBroSetTaxnId), TaxnStr($BroSetTaxnId)));
            break;
        }
        $BsDataA[BSI_IncludesA][] = $includeBroSetId;
        $RowMapA[$RowNum] = -$includeBroSetId;
        if ($RowComments) {
          if (($RowComments = substr($RowComments, 1)) == '') $RowComments=' '; # strip initial  then if empty (single blank row) set to ' ' to preserve a blank line
          $RowCommentsA[$RowNum] = $RowComments;
          $RowComments='';
        }

        # Read the Bros recursively re multi level Includes and add the Bros to $IbrosA, $NamesA, $ShortNamesA checking for clashes
        if (!$Errors) {
          IncludeBros($includeBroSetId);
          if ($Errors) Errors("in Row $RowNum Include in Pass 1"); # Does not return
        }
        unset($bsO);

        end($IbrosA);
        $BroId = key($IbrosA);
        $prevRowWasIncludeB = 1;
        continue; # End of Include statement
      }
      # else a comment or Bro row
      if ((!ctype_digit($Row[0]) && $Row[0] !== '=') || !InStr(TAB, $Row)) {
        # blank or comment or BroSet or Defined Bro row (starting with other than a digit or '=', or there are no Tabs in the row)
        if ($RowNum !== $HeadingsRow) # Don't store the headings row - BrosExport generates that
          $RowComments .= ''.$Row; # for inclusion with the next stored Bro/Include or the last one if a fter the end
        $RowMapA[$RowNum] = 0; # set row map to 0 to indicate blank
        continue;
      }
      # else row starts with a digit or '='
    } # else row starts with a Tab
    # Assumed Bro row

    $colsA = explode(TAB, $Row);
    # Accept short rows, filling them in with blanks
    if (count($colsA) < Num_Imp_Cols)
      $colsA = array_pad($colsA, Num_Imp_Cols, '');

    # defaults
    $idEqual = $master = $ShortName = $Ref = $FolioHys = $nFolioHys = $TupId = $DataTypeN = $DboFieldN = $DadId = $postType = $SumUp
      = $check = $SignN = $UsablePropDims = $AllowPropDims = $PMemDiMesA = $PeriodSEN = $SortOrder = $Zones = $Descr = $slave = $SlaveYear = 0;
    $ExclPropDims = '';
    $DataA = [
      null, # TaxnIdsA    int i [] of Taxonomies  that the Bro applies to. OK if in this list || this list is empty and not in NotTaxnIdsA
      null, # CtryIdsA    int i [] of Countries   that the Bro applies to. OK if in this list || this list is empty and not in NotCtryIdsA
      null, # ETypesA     int i [] of EntityTypes that the Bro applies to. OK if in this list || this list is empty and not in NotETypesA
      null, # NotTaxnIdsA int i [] of Taxonomies  that the Bro does not apply to
      null, # NotCtryIdsA int i [] of Countries   that the Bro does not apply to
      null  # NotETypesA  int i [] of EntityTypes that the Bro does not apply to
    ];
    if ($BroSetTaxnId) {
      # Out-BroSet only
      $txNames = $nTxHys = $TxId = $ManDims = 0;
      $Bits = BroB_Out; # Set if Bro is Out-BroSet type using Hys etc. Unset = SIM/In-BroSet type using Folios etc.
    }else
      # In-BroSet
      $Bits = $Related = $SeSumList = 0;

    # P1 IO BroId
    if ($v = trim($colsA[COL_BroId])) { # ltrim not done re poss leading tabs
      if ($v[0] === '=') { # BroId to set to if in form '=3000'
        $v = substr($v, 1);
        if (ctype_digit($v)) {
          $idEqual = (int)$v;
          if ($idEqual<1 || $idEqual>99950)
            BIError2("BroId to be set of $idEqual is out of the allowable range of 1 to 99950");
        }else
          BIError2("BroId to be set, as indicated by a leading '=' is not in the expected form of '=nnnn' where nnnn is a 1 to 6 digit number");
      }
    }

    # P1 IO Type
    # Can be deduced for a Slave if Master is set
    if (($t = array_search(str_replace(' Master','',$colsA[COL_Type]), ['Set', 'Ele', 'Set Slave', 'Ele Slave'])) !== false) { # Set, Ele, Set Slave, Ele Slave after stripping out ' Master'
      switch ($t) {
        case 0: $Bits|=BroB_Set;break;          # Set
        case 1: $Bits|=BroB_Ele;break;          # Ele
        case 2: $Bits|=BroB_Set;$slave=1;break; # Set Slave
        case 3: $Bits|=BroB_Ele;$slave=1;break; # Ele Slave
      }
    }else
      if (!$colsA[COL_Master])
        BIError2("Type is not one of 'Set', 'Ele', 'Set Slave', 'Ele Slave' as expected, or for a Slave the Master Col is not set.");
      # Expect type bit(s) to be set later. If not then -> Error

    # P1 IO Name and Level
    $nameA = array_pad(explode('.', $prevName), Num_Name_Cols, ''); # previous Name -> start of new nameA
    $Name = '';
    $diffB = $doneB = false;
    for ($i=0; $i<Num_Name_Cols; ++$i) {
      if ($v = $colsA[COL_Name0 + $i]) {
        if ($doneB) {
          BIError2("Name $i value $v found after apparent end of name");
          break;
        }
        if (!strcasecmp($v, 'Tx')) {
          $v = 'Tx'; # to ensure consistent case form re later testing
          ++$txNames;
        }
        if (preg_match('/[^a-zA-Z0-9_\?]/',$v))
          BIError2("Name $i |$v| is not a legal BroName segment. Names must consist of only a-z,A-Z,0-9 or _ characters");
        $Name .= ".$v";
        if ($v != $nameA[$i]) {
          $diffB = true;
          $nameA[$i] = $v;
        }
      }else{
        # blank segment - end if have had a difference, or a repeat of the one above o'wise
        if ($diffB)
          $doneB = true;
        else
          $Name .= '.'.$nameA[$i];
      }
    }
    $Name = trim($Name, '.');
    if (!strlen($Name)) {
      for ($i=0; $i<Num_Imp_Cols; ++$i) # No Name so check if all import cols are empty and if so skip the row
        if ($colsA[$i]) {
          BIError("No name supplied but every Bro must have a name");
          continue 2;
        }
      $RowComments .= ''.$Row; # for inclusion with the next stored actual Bro or the last one if after the end
      $RowMapA[$RowNum] = 0; # set row to 0 to indicate comment
      continue;
    }
    if (!ctype_alpha($Name[0]))
      BIError2("Name <i>$Name</i> does not start with a letter. Name0 must start with a letter. The Names for other levels can start with a digit or be purely numeric");
    $nameA = explode('.',$Name);
    $Level = count($nameA) - 1;
    if ($Level > 8)
      die("Die at row $RowNum with Level $Level which is > limit of 8");
    if ($prevRowWasIncludeB && $Level)
      BIError2("The Bro after an Include BroSet statement must be a Level 0 Set i.e. BroSets must be complete Sets and not be included in the middle of a Set");

    ++$BroId; # as it will be for the BroInfo Insert

    if ($idEqual) { # Have a BroId from '=3000' type BroId
      if ($idEqual < $BroId) {
        BIWarning("BroId to be set of <i>$idEqual</i> is < the current BroId of $BroId so the Set BroId ('=') command has been ignored");
        $idEqual = 0;
      }else if ($idEqual === $BroId)
        $idEqual = 0;
      else
        $BroId = $idEqual;
    }
    if ($BroId>99999) {
      BIError("BroId has reached 100,000 i.e. 6 digits. Reduce the =nnnnnn BroId value before this so that Ids stay within the 1 to 99,999 range.");
      Errors('in Pass 1');
      ######
    }

    # P1 IO Master
    # djh?? Need to remove Match option for In-BroSets
    if ($v = $colsA[COL_Master]) {
      # Validity to be checked in Pass 2 as could be a forward reference
      # Check for special case of entry == Match
      if (strcasecmp($v,'Match')) {
        # Col != Match i.e. Direct entry of Master in format {Year#}{ mmmm} Name
        if (!strncasecmp($v, 'Year', 4)) {
          # Expect Year#
          if (strlen($v)<5 || !ctype_digit($v[4]))
            BIError2("Master col <i>$v</i> not in expected format of &lt;Match | {Year#}{ MasterId} Master BroName>");
          else{
           #if (!($Bits & BroB_Set)) # Year has no meaning for Set Slaves which obtain their values by summing. 23.09.12 Removed use use of SlaveYear for the Check of a Set Slave
            if (!InRange($SlaveYear = (int)$v[4], 1, 3)) BIError2("Year# in Master col <i>$v</i> is not in the expected range of 1 to 3");
              # A Money type Prior Year Slave must be Sch. Cannot be DE for a cross year figure. Checked in Pass 3
            $v = ltrim(substr($v, 5));
          }
        }
        # Strip MasterId if present -> Master BroName in $master
        $master = ($p = strpos($v, ' ')) ? ltrim(substr($v, $p+1)) : $v;
        if (!strlen($master))
          BIError2("Master col <i>$v</i> not in expected format of &lt;Match | {Year#}{ MasterId} Master BroName>");
        $slave = Slave_MasterExplicit; # Slave is defined by Master to be checked. Doesn't need TxIdHyIdTupId info
      }else{ # Col === Match
        # Col entry of Match = instruction that this a Slave Bro with Master to be matched from TxId.HyId.TupId info
        if (!$BroSetTaxnId)
          BIError2("'Match' for a Slave Bro to be matched with a Master from TxId.HyId.TupId info can only be used with Out-BroSet Slaves");
        $slave = $master = Slave_MasterMatch;
      }
    }else if ($slave) {
      # No Master col entry but Type Included Slave so set to Slave_MasterMatch if Out-BroSet
      if (!$BroSetTaxnId)
        BIError2("No Master specified for this Slave Bro");
      $slave = $master = Slave_MasterMatch; # Slave Bro with Master to be matched from TxIdHyIdTupId info
    }

    if ($slave)
      $Bits |= BroB_Slave;
    # echo "RowNum=$RowNum BroId=$BroId Bits=$Bits slave=$slave<br>";

    # P1 IO ShortName
    if ($v = $colsA[COL_ShortName]) {
      if (strlen($v) > 48)     BIError2('ShortName length of '.strlen($v).' exceeds the allowable length of 48 characters');
      if (!ctype_alpha($v[0])) BIError2("ShortName <i>$v</i> must start with a letter");
      if (InStr('.', $v))   BIError2("ShortName <i>$v</i> contains a '.' character which is not allowed in a Shortname");
      $ShortName = $v;
      if (isset($ShortNamesA[$ShortName]))
        BIError2("The Bro ShortName <i>$ShortName</i> is already in use as a ShortName " . BroLocation($ShortNamesA[$ShortName]));
      if (isset($NamesA[$ShortName]))
        BIError2("The Bro ShortName <i>$ShortName</i> is already in use as a BroName " . BroLocation($NamesA[$ShortName]));
      $ShortNamesA[$ShortName] = $BroId;
    }

    # P1 IO Ref
    if ($v = $colsA[COL_Ref]) {
      if (strlen($v) > 48) BIError2('Ref length of '.strlen($v).' exceeds the allowable length of 48 characters');
      $Ref = $v;
    }

    if ($BroSetTaxnId) {
      # Out-BroSet
      # P1 -O TxId
      # t o o o = Can be none incl for a Slave if $slave == Slave_MasterExplicit
      if ($v = $colsA[COL_TxId]) {
        if (ctype_digit($v)) {
          $v = (int)$v;
          if (isset($TxElesA[$v])) {
            $xA = $TxElesA[$v];
            # Check that Tx is of correct type
            if ($xA['abstract']) {
              BIError("TxId <i>$v</i> is abstract and so cannot be used with a Bro for which a concrete element is required");
              continue;
            }else
              if ($xA['SubstGroupN'] != TSG_Item) {
                BIError("TxId <i>$v</i> has substitution group <i>" . SubstGroupToStr($xA['SubstGroupN']) . "</i> so cannot be used with a Bro for which a substitution group of Item is required");
                continue;
              }
            $TxId = $v;
            $txHysChrList = $xA['Hypercubes'];
            $nTxHys = strlen($txHysChrList);
          }else{
            BIError("TxId <i>$v</i> not found in DB. (Elements in the Braiins Tx Skip List are not included in the DB.)");
            continue;
          }
        }else
          BIError2("Unknown TxId value of $v. An integer TxId or nothing is expected");
      } # else - nothing as an empty TxId col is ok meaning Non Tx
      # end TxId

      # 'Tx' for auto name generation
      if ($txNames) {
        # Have one or more Tx name segments.
        # Expect previous Set use of Tx name(s) to provide the generated name(s) apart from a possible last segment name of Tx
        for ($i=0; $i<$Level; ++$i) { # not the last one
          if ($nameA[$i] == 'Tx') {
            if (isset($setTxNamesA[$i])) {
              $nameA[$i] = $setTxNamesA[$i];
            --$txNames;
            }else{
              # No Tx generated Set name segment available. Use previous name segment if available
              $prevNameA = array_pad(explode('.', $prevName), Num_Name_Cols, '');
              if (isset($prevNameA[$i])) {
                $nameA[$i] = $prevNameA[$i];
              --$txNames;
                BINotice("Previous Name $i segment {$prevNameA[$i]} used for 'Tx' in Name $i which is not the last one in the row and for which there was no matching 'Tx' on the previous row. This 'Tx' could be removed.");
              }else{
                BIError("No 'Tx' generated Set name or previous row segment name segment is available for use for the 'Tx' in Name $i. Review the 'Tx' usage here. A specific name may be needed.");
                continue 2;
              }
            }
          }
        }
        # Now expect $txNames to be 0 or 1. 1 means last segment name is to be generated
        if ($txNames === 1) {
          if ($TxId) {
            unset($nameA[$Level]);
            $nameA[$Level] = $seg = $segr = BroNameFromTxName($xA['name'], implode('.', $nameA)); # name, dadName
            for ($i = 2; isset($NamesA[$Name = implode('.', $nameA)]); ++$i)
              $nameA[$Level] = ($segr = $seg . $i);
            if ($Bits & BroB_Set) $setTxNamesA[$Level] = $segr;
            # ListedInvestsIncludedInFANetBookValue
            # ValuationUnlistedInvestNotCarriedOnHistoricalCostBasis
            BINotice("Level $Level name <i>$segr</i> generated from Tx name <i>$xA[name]</i>");
          }else{
            BIError("Final name segment of 'Tx' used with a non-Tx based Bro. Enter a name segment or add a TxId if that was intended.");
            continue;
          }
        }else
          # All Tx name segments replaced by previously generated Set ones, or there was an error
          $Name = implode('.', $nameA);
      } # end 'Tx' for auto name generation
    } # End of Out-BroSet section

    $prevName = $Name;
    # P1 IO Name Checks
    # Std Bro or Slave
    if (isset($NamesA[$Name])) {
      BIError("The BroName <i>$Name</i> is a duplicate of the Name used " . BroLocation($NamesA[$Name]));
      continue;
    }
    # P1 IO Check Std Bro or Slave Name re Set use
    if ($Level) {
      unset($nameA[$Level]);
      $dadName  = implode('.', $nameA);
      $dadLevel = $Level - 1;
      for ($rowNum=$RowNum-1; $rowNum; --$rowNum) {
        $broId = $RowMapA[$rowNum];
        if ($broId > 0) { # re empty/comment (0) include (< 0) or error (-999999) rows
          $broA = $IbrosA[$broId];
          if ($dadName === $broA['Name']) { # also means Level == dadLevel
            # Found Dad which is in $broA
            $DadId = $broId;
            if ($slave && !($Bits & (BroB_Set | BroB_Ele)))
              # Slave not set to Set or Ele so set to Ele. Gets set to Set later by following code if it has kids
              $Bits |= BroB_Ele;
            # Adjust Dad type if needed
            if (!(($dadBits=$broA['Bits']) & BroB_Set)) {
              $dadBits &= ~BroB_Ele; # Unset Ele  Could use $IbrosA[$DadId]['Bits'] ^= BroB_Ele here as the bit is set
              $dadBits |= BroB_Set;
              $IbrosA[$DadId]['Bits'] = $dadBits;
              $SetMemNumsA[$DadId]=0;
              BINotice(sprintf('Row %d (Bro %d) type changed from Ele to Set as it has children', $rowNum, $DadId));
            }
            ++$SetMemNumsA[$DadId];
            break; # out of rows loop
          }else
            # Not a name match
            if ($broA['Level'] <= $dadLevel) {
              BIError("Level $Level Bro <i>$Name</i> is out of Set sequence as the first Level $dadLevel (Dad level) Bro going back up is $broA[Name], not $dadName as expected from the name of the Bro");
              continue 2;
            }
        }else{
          # empty or error row
          if ($broId < 0) # error or include row reached before Set found so bail out silently
            break;
          # let loop continue on empty
        }
      }
      if (!$DadId) {
        BIError("No valid Dad Bro found for Level $Level Bro <i>$Name</i>. Name or Set name wrong or previous error?");
        continue;
      }
    }else{
      # Std Bro or Slave at level 0
      $setTxNamesA = [];
      if (!($Bits & BroB_Set)) {
          BINotice("Level 0 Bro <i>$Name</i> was defined as an ".BroTypeStr($Bits).' type, but level 0 Bros must be Sets so its type has been set to Set');
        $Bits &= ~BroB_Ele; # Unset Ele
        $Bits |= BroB_Set;
      }
    } # End of Name checks
    if (strlen($Name)>300)
      BIError2("The BroName <i>$Name</i> is ".strlen($Name)." characters long which exceeds the Bros DB Table Name field size of 300");
    if (isset($ShortNamesA[$Name]))
      BIError2("The BroName <i>$Name</i> is already in use as a ShortName " . BroLocation($ShortNamesA[$Name]));
    $NamesA[$Name] = $BroId;
    if ($Bits & BroB_Set) $SetMemNumsA[$BroId]=0;

    # P1 IO DataType
    # g m g I  TxId not required
    if (!$slave) {
      if ($v = $colsA[COL_DataType]) {
        if (($DataTypeN = array_search($v, ['None', 'String', 'Integer', 'Money', 'Decimal', 'Date', 'Boolean', 'Enum', 'Share', 'PerShare', 'Percent', 'MoneyString'])) !== false) {
          if ($DataTypeN && ($Bits & BroB_Set) && !in_array($DataTypeN, $BroSumTypesGA))
            BIError2("A Set Bro can have only a summing DataType or none, not $v");
        }else
          BIError2("DataType <i>$v</i> unknown");
      }else if ($Bits & BroB_Ele)
        BIError2('DataType not supplied for an Element Bro as required');
    }# else
      # Slave
      #if ($colsA[COL_DataType]) {
      # # BINotice('DataType ignored as Slaves inherit DataType from their Master'); # Postponed to Pass 2 when Master is known. Silent as Export outputs this for info purposes.
      #}

    if ($BroSetTaxnId) {
      # Out-BroSet section for Hys and TupId
      # P1 -O Hys
      # h o o o s  Chr list of hypercube Ids for <members of this set|this element>. Can have wo TxId. Required if Tx based. Can only be more than 1 for a Set Bro.
      if ($v = $colsA[COL_Hys]) {
        $hyCol = $v;
        if (!$slave && !$DataTypeN && ($Bits & BroB_Set)) # A no DataType error will have already been reported if this is an Ele so just give error in Set case
          BIError2("Hys $hyCol specified for a Set Bro with no DataType which is an illegal combination. Either remove the Hys or change the DataType");
        else if (!InStr(',', $hyCol)) {
          # Just one HyId
          if (ctype_digit($hyCol)) {
            if (InRange($hyId = (int)$hyCol, 1, $FolioHyMaxId))
              $FolioHys = IntToChr($hyId); # 1 chr
            else
              BIError2("The HyId <i>$hyId</i> is out of the allowable range of 1 to $FolioHyMaxId");
          }else if (isset($FolioHyNamesMapA[$hyCol]))
            $FolioHys = IntToChr($FolioHyNamesMapA[$hyCol]);
          else
            BIError2("The HyId value <i>$hyCol</i> is unknown. (Case matters in Hy Names.)");
          $nFolioHys = 1;
        }else{
          # Multiple Hys which is valid for a Set Bro
          $hyIdsA = [];
          foreach (explode(',', $hyCol) as $t) {
            if (ctype_digit($t)) {
              if (InRange($t, 1, $FolioHyMaxId))
                $hyIdsA[] = $t;
              else
                BIError2("The HyId <i>$t</i> in the Hys field <i>$hyCol</i> is out of the allowable range of 1 to $FolioHyMaxId");
            }else if (isset($FolioHyNamesMapA[$t]))
              $hyIdsA[] = $FolioHyNamesMapA[$t];
            else
              BIError2("The HyId <i>$t</i> in the Hys field <i>$hyCol</i> is unknown. (Case matters in Hy Names.)");
          }
          $nFolioHys = count($hyIdsA); # # of valid input HyIds
          $FolioHys = IntAToChrList($hyIdsA);
          if ($nFolioHys>1) {
            if ($Bits & BroB_Ele) {
              BIError2("$nFolioHys Hys have been supplied for an Element Bro but Element Bros can have only one Hypercube");
              $nFolioHys = 1; # just to carry one re other poss errors
              $FolioHys=$FolioHys[0];
            }else{
              # More than one input hypercube which is OK for a Set. Eliminate subsets.
              $FolioHys  = EliminateHypercubeSubsets($FolioHys);
              $nFolioHys = strlen($FolioHys); # # of input HyIds after eliminating subsets
            }
          }
        }
        # End of Hys supplied
      }else
        # No HyId
        if ($TxId) {
          # TxId but No HyId
          if (!$slave)
            BIError2("No HyId value has been supplied but the Bro is taxonomy based, and is not a Slave so an HyId is required");
          else if ($slave === Slave_MasterMatch) # Need both TxId and HyId for a Slave whose Master is to be matched from its TxId.HyId{.TupId} info
            BIError2("Bro is a Slave whose Master is to be matched from its TxId.HyId{.TupId} info but only a TxId has been supplied.");
        }else
          # No HyId, No TxId
          if ($slave === Slave_MasterMatch) # Need both TxId and HyId for a Slave whose Master is to be matched from its TxId.HyId{.TupId} info
            BIError2("Bro is a Slave whose Master is to be matched from its TxId.HyId{.TupId} info but neither TxId nor HyId have been supplied.");

      if ($nFolioHys <= 1) $hyId = ($nFolioHys === 1) ? ChrToInt($FolioHys) : 0;
      # $nFolioHys and $FolioHys set if defined, and $hyId too if $nFolioHys <= 1

      # P1 -O TupId
      # t u u u =
      if (!$slave || $slave === Slave_MasterMatch) {
        if ($v = $colsA[COL_TupId]) {
          if (!ctype_digit($v) || !InRange($v=(int)$v, 1, Max_TupleId))
            BIError2("Unknown TupId value of $v. A TupId is expected to be an integer in the range 1 to ".Max_TupleId);
          # Std Bro with TupId supplied
          else if ($TxId) {
            # Check that the supplied TupId is valid for the TxId
            if (!isset($TuplesByTxIdA[$TxId]))
              BINotice("TupId $v ignored as a Bro based on TxId $TxId does not need a TupId");
            else if (in_array($v, $TuplesByTxIdA[$TxId]))
              $TupId = $v;
            else
              BIError2("TupId $v is not a valid TupId for a Bro based on TxId $TxId");
          }else
            BINotice("TupId $v ignored as a non-Tx based Bro does not need a TupId");
        }else{
          # Std Bro or Slave 2, No TupId
          if ($TxId) {
            if ($nFolioHys > 1) {
              if (isset($TuplesByTxIdA[$TxId]) && count($TuplesByTxIdA[$TxId]) > 1)
                BIError2("This Bro's TxId $TxId is a member of Tuples ".implode(',', $TuplesByTxIdA[$TxId])." but no TupId has been supplied and the the appropriate TupId can't be identified as multiple Hypercubes are involved. Add the required TupId to the TupId column.");
            }else
              $TupId = TupId($TxId, $hyId); # fn returns 0 if no TupId applies, or on an error
          }
        }
      }# else
        # Slave with Master supplied = Slave_MasterExplicit
        #if ($colsA[COL_TupId]) { # TuPid supplied
        # # BINotice('TupId ignored as Slaves with Master specified inherit TupId from their Master'); # Silent as Export outputs this for info purposes.
        #} # else No TupId which is OK for a Slave_MasterExplicit Slave

      # Build UsableDims if not a Slave. Hy checks are done later when the Duplicates check is done when PMemDiMesA is available.
      if ($nFolioHys) {
        if (!$TxId && $slave === Slave_MasterMatch) # Have Hy(s) but no TxId. Valid except for $slave === Slave_MasterMatch cases. Need both TxId and HyId for a Slave whose Master is to be worked out from its TxId.HyId info
          BIError2("Bro is a Slave whose Master is to be matched from its TxId.HyId{.TupId} info but no TxId has been supplied.");
        # Got Hy(s). Build Usable list if not a Slave. Slave work is done in Pass 2 when Masters are available.
        if (!$slave)
          $UsablePropDims = UsableDims($FolioHys, $nFolioHys);
      }
      # End of HyId and TupId for All with $nFolioHys, $FolioHys, $UsablePropDims, $txHysChrList and $nTxHys defined
      # End of Out-BroSet section for Hys and TupId
    }else{
      # In-BroSet section for Folio and RO
      # P1 I- Folio
      # o o o s  Folio for <members of this set|this element>. Only 1 unlike Hys for Out-BroSet Bros
      if ($v = $colsA[COL_Folio]) {
        if (!$slave && !$DataTypeN && ($Bits & BroB_Set)) # A no DataType error will have already been reported if this is an Ele so just give error in Set case
          BIError2("Folio $v specified for a Set Bro with no DataType which is an illegal combination. Either remove the Folio or change the DataType");
        if (ctype_digit($v)) {
          if (!InRange($folioId = (int)$v, 1, $FolioHyMaxId)) {
            BIError2("The FolioH <i>$folioId</i> is out of the allowable range of 1 to $FolioHyMaxId");
            $folioId = 1;
          }
        }else if (isset($FolioHyNamesMapA[$v]))
          $folioId = $FolioHyNamesMapA[$v];
        else{
          BIError2("The Folio value <i>$v</i> is unknown. (Case matters in Folio Names.)");
          $folioId = 1;
        }
        $nFolioHys = 1;
        $FolioHys = IntToChr($folioId); # 1 chr
        # Set Usable list if not a Slave. Slave work is done in Pass 2 when Masters are available.
        if (!$slave)
          $UsablePropDims = $FolioPropsA[$folioId];
      } # no Folio
      # $nFolioHys, $FolioHys set.

      # P1 I- RO
      # n n n R
      if ($v = $colsA[COL_RO]) {
        if (MatchOne($v, 'RO')) {
          #if (!$slave && ($Bits & BroB_Ele)) No, Valid for DboField Bros or Stock Mvt ele Bros to be calculated indicated by being set to RO.
          #  BIWarning('RO ignored as Element Bros which are not a Slave should not have RO set.');
          #else
            $Bits |= BroB_RO;
        }else
          BIError2("RO (Report Only) value <i>$v</i> is invalid - RO or nothing expected");
      }else{
        # No RO
        if ($slave) {
          BINotice('RO has been set for this Slave as all Slaves are RO');
          $Bits |= BroB_RO;
        }
      }
      # End of In-BroSet section for Folio and RO
    }

    # P1 IO DboField
    # - o - -
    # Data not stored in Bros. Values are read from Entities. People, Addresses, Contacts DB at run time.
    # All these fields can also be accessed directly via the RG, so Bros are not needed except for tagging purposes in an Out-BroSet.
    # Entity.Ref, Entity.Name, Entity.Identifier, Entity.CtryId
    if ($v = $colsA[COL_DboField]) {
      if (!($Bits & BroB_Ele) || $slave)
        BIError2("DboField <i>$v</i> is invalid as DboField can only be used with non-slave Element Bros");
      else{
        if (($DboFieldN = array_search($v, ['Entity.Ref', 'Entity.Name', 'Entity.Identifier', 'Entity.CtryId'])) === false) {
          BIError2("DboField value <i>$v</i> is invalid - Entity.Ref, Entity.Name, Entity.Identifier, Entity.CtryId expected");
          $DboFieldN = 1;
        }else{
          ++$DboFieldN;
          if (!($Bits & BroB_RO)) {
            BINotice('RO has been set for this Bro due to its use of DboField');
            $Bits |= BroB_RO;
          }
        }
      }
    }

    # P1 IO Sign
    # - b b b I
    if ($v = $colsA[COL_Sign]) {
      if (($SignN = array_search($v, ['Debit', 'Credit', 'Either'])) === false) { # Debit, Credit, Either
        BIError2("Sign value <i>$v</i> is invalid - Debit, Credit, Either expected");
        $SignN = BS_Dr;
      }else
        ++$SignN;
      if (!$slave && $DataTypeN !== DT_Money && $DataTypeN !== DT_MoneyString) {
        BINotice("Sign <i>$v</i> ignored as Sign is inapplicable to non-Money Bros");
        $SignN = 0;
      }
      # Slave checking left to Pass 2 when Slave DataTypeN is known
    }else if ($DataTypeN === DT_Money || $DataTypeN === DT_MoneyString) { # can't be a slave as $DataTypeN isn't yet set for a Slave
      # No Sign but need Sign. Try for it from ancestors
      for ($broId=$DadId; $broId && !$SignN && $IbrosA[$broId]['DataTypeN'] === DT_Money; $broId=$IbrosA[$broId]['DadId'])
        $SignN=$IbrosA[$broId]['SignN'];
      if (!$SignN) {
        BIError2('No Sign supplied for a Non-Slave Money or MoneyString Bro either directly or by inheritance');
        $SignN = BS_Dr; # but carry on, setting Sign to avoid more errors
      }
    }

    # P1 IO PostType
    # - b b b p
    if ($v = $colsA[COL_PostType])
      $postType = $v; # for processing in Pass 3 as need to know DataTypeN and SumUp

    # P1 IO ExclProps List of PropIds that the Bro cannot use. Requires Folio. UsableProps list = Folio Props - ExclProps
    #       ExclDims  List of DimIds  that the Bro cannot use. Requires Hys.   UsableDims  list = Hy Dims - ExclPropDims
    # h o o o f
    if ($v = $colsA[COL_ExclPropDims]) {
      if (!$nFolioHys)
        BINotice("Excl$PropDimNmes list <i>$v</i> ignored as a Bro without a $FolioHyNme cannot have an Excl$PropDimNmes list");
      else{
        $exclPropDimsA = [];
        foreach (explode(',', $v) as $t)
          if (ctype_digit($t)) {
            if (InRange($t, 1, $PropDimMaxId))
              $exclPropDimsA[] = $t;
            else
              BIError2("The Excl$PropDimNmes $PropDimNme <i>$t</i> of |$v| is out of the allowable range of 1 to $PropDimMaxId");
          }else if (isset($PropDimNamesMapA[$t]))
            $exclPropDimsA[] = $PropDimNamesMapA[$t];
          else
            BIError2("The Excl$PropDimNmes $PropDimNme <i>$t</i> of |$v| is unknown. (Case matters in $PropDimNme Names.)");
        if (strlen($ExclPropDims = IntAToChrList($exclPropDimsA)) > 20) # $ExclPropDims = sorted unique chr list
          BIError2("Excl$PropDimNmes list <i>$v</i> length > max allowed of 20");
        if (!$slave && ($len = strlen($ExclPropDims))) { # Slave checks done in Pass 2
          # Check to see that the ExclPropDims are in the Bro's Usable list
          for ($i=0; $i<$len; ++$i)
            if (!InStr($ExclPropDims[$i], $UsablePropDims))
              BIError2(sprintf("Excl$PropDimNmes $PropDimNme %d is not in the Bro's Usable$PropDimNmes list %s, so cannot be excluded", ChrToInt($ExclPropDims[$i]), ChrListToCsList($UsablePropDims)));
          # Remove $ExclPropDims from $UsablePropDims for subsequent checks
          $UsablePropDims = str_replace(str_split($ExclPropDims), '', $UsablePropDims);
        }
      }
    } # end P1 ExclPropDims

    # P1 IO AllowProps List of PropIds that the Bro can use. Forms the UsableProps list for such Bros.
    #       AllowDims  List of DimIds  that the Bro can use. Forms the UsableDims  list for such Bros.
    # - o o o f
    if ($v = $colsA[COL_AllowPropDims]) {
      if ($ExclPropDims)
        BIError2("Allow$PropDimNmes list <i>$v</i> specified for a Bro with Excl$PropDimNmes {$colsA[COL_ExclPropDims]} but Excl$PropDimNmes and Allow$PropDimNmes are mutually exclusive");
      else{
        $allowPropsDimsA = [];
        foreach (explode(',', $v) as $t)
          if (ctype_digit($t)) {
            if (InRange($t, 1, $PropDimMaxId))
              $allowPropsDimsA[] = $t;
            else
              BIError2("The Allow$PropDimNmes $PropDimNme <i>$t</i> of |$v| is out of the allowable range of 1 to $PropDimMaxId");
          }else if (isset($PropDimNamesMapA[$t]))
            $allowPropsDimsA[] = $PropDimNamesMapA[$t];
          else
            BIError2("The Allow$PropDimNmes $PropDimNme <i>$t</i> of |$v| is unknown. (Case matters in $PropDimNme Names.)");
        if (strlen($AllowPropDims = IntAToChrList($allowPropsDimsA)) > 20) # $AllowPropDims = sorted unique chr list
          BIError2("Allow$PropDimNmes list <i>$v</i> length > max allowed of 20");
        if (!$slave) { # Slave checks done in Pass 2
          # If the Bro has UsableProps/UsableDims, check to see that the AllowProps/AllowPropDims are in the Usable list
          if ($UsablePropDims && ($len = strlen($AllowPropDims)))
            for ($i=0; $i<$len; ++$i)
              if (!InStr($AllowPropDims[$i], $UsablePropDims))
                BIError2(sprintf("Allow$PropDimNmes $PropDimNme %d is not in the Bro's Usable$PropDimNmes list %s, so cannot be allowed", ChrToInt($AllowPropDims[$i]), ChrListToCsList($UsablePropDims)));
          $UsablePropDims = $AllowPropDims;
        }
      }
    } # end P1 AllowProps/AllowPropDims

    # P1 IO Members/DiMes
    # - o o o f
    # PMemsA/DiMesA =  PMem/DiMe Overrides array [i => MandatsA, DefaultsA, ExcludesA, AllowsA]
    # MandatsA   i [] of Mandatory PMems/DiMes, Prop/Dim in Bro UsablePropDims or PMem/DiMe in AllowsA, only one per Prop/Dim, Mux with Defaults and Excludes
    # DefaultsA  i [] of Default   PMems/DiMes, Prop/Dim in Bro UsablePropDims or PMem/DiMe in AllowsA, only one per Prop/Dim, Mux with Mandats  and Excludes
    # ExcludesA  i [] of Exclude   PMems/DiMes, Prop/Dim in Bro UsablePropDims, Mux with Mandats, Defaults, and Allows
    # II_AllowsA i [] of Allow     PMems/DiMes, No UsablePropDims, or Prop/Dim in Bro ExclPropDims, Mux with Excludes. BroSetImport sets ExclPropDims if not set and removes from UsablePropDims
    # ExcludesA and AllowsA are allowable for Slave filter use.
    if ($v = $colsA[COL_PMemDiMes]) {
      $idMandatsA = $idDefaultsA = $idExcludesA = $idAllowsA =
      $manPropDimsA = $defPropDimsA = []; # just for checking for Prop/Dim duplicate use
      $valsA = explode(',', $v);
      sort($valsA); # to get the Allow ones first re m and d available checks
      foreach ($valsA as $val) {
        if (!($len=strlen($val))) continue;
        $c = strtolower($val[0]);
        if ($len<3 || $val[1]!==':') {
          BIError2("$PMemDiMeNmes value <i>$val</i> is not in expected format of &lt;m | d | x | a>: followed by an integer $PMemDiMeIdNme or $PMemDiMeNme Name");
          continue;
        }
        $pMemDiMe = substr($val, 2); # after leading m:, d:, x:, a:
        if (ctype_digit($pMemDiMe)) {
          $pMemDiMeId = (int)$pMemDiMe;
          if ($pMemDiMeId > $PMemDiMeMaxId) {
            BIError2("The $PMemDiMeIdNme <i>$pMemDiMeId</i> is out of the allowable range for this Bro of 1 to $PMemDiMeMaxId");
            continue;
          }
          if ($pMemDiMeId <= 0) {
            BINotice($pMemDiMeId < 0 ? "Negative $PMemDiMeIdNme $pMemDiMeId ignored" : "Zero $PMemDiMeIdNme ignored");
            continue;
          }
        }else if (isset($PMemDiMeNamesMapA[$pMemDiMe]))
          $pMemDiMeId = $PMemDiMeNamesMapA[$pMemDiMe];
        else{
          BIError2("$PMemDiMeNmes value <i>$val</i> is not in expected format of &lt;m | d | x | a>: followed by an integer $PMemDiMeIdNme or $PMemDiMeNme Name");
          continue;
        }
        if ($BroSetTaxnId) { # djh?? Remove this lot and allow any? Will have fewer Z and R types anyway.
          if ($PMemsDiMesAR[$pMemDiMeId][DiMeI_Bits] & DiMeB_Zilch) {
            BINotice("DiMes $val has been ignored as it is a Z or R type."); # djh?? Allow R for a slave re filtering?
            continue;
          }
        }else if ($PMemsDiMesAR[$pMemDiMeId][PMemI_Bits] & (PMemB_Zilch | PMemB_RO)) {
          BINotice("Members $val has been ignored as it is a Z or R type and so cannot be posted to."); # djh?? Posting not relevant to a slave so allow R for a slave re filtering?
          continue;
        }
        $propDimId = $PMemsDiMesAR[$pMemDiMeId][PMemI_PropId]; # PMemI_PropId = PMemI_PropId
        switch ($c) {
          case 'm': # Mandatory PMems/DiMes, Prop/Dim in Bro UsablePropDims or PMem/DiMe in AllowsA, only one per Prop/Dim, Mux with Defaults and Excludes
            if ($slave) BIError2("Mandatory $PMemDiMeNmes $val specified for a Slave but only Exclude or Allow $PMemDiMeNmes can be used with a Slave as filters");
            else if (!InChrList($propDimId, $UsablePropDims) && !in_array($pMemDiMeId, $idAllowsA)) BIError2("Mandatory $PMemDiMeNmes $val is a member of $PropDimNme $propDimId which is not one of the Usable$PropDimNmes for the Bro, nor is $PMemDiMeNme $pMemDiMe an Allow $PMemDiMeNme.");
            else if (in_array($propDimId, $manPropDimsA, true)) BIError2("Mandatory $PMemDiMeNmes $val is a member of $PropDimNme $propDimId for which another Mandatory $PMemDiMeNme has been supplied, but a $PropDimNme can have only one Default $PMemDiMeNme.");
            else if (in_array($pMemDiMeId, $idDefaultsA, true)) BIError2("Mandatory $PMemDiMeNmes $val is also a Default $PMemDiMeNme. It can't be both.");
            else if (in_array($pMemDiMeId, $idExcludesA, true)) BIError2("Mandatory $PMemDiMeNmes $val is also an Exclude $PMemDiMeNme. It can't be both.");
            else{
              if (!in_array($pMemDiMeId, $idMandatsA, true)) # Silently discard duplicates
                $idMandatsA[] = $pMemDiMeId;
              $manPropDimsA[] = $propDimId;
            }
            break;
          case 'd': # Default PMems/DiMes, Prop/Dim in Bro UsablePropDims or PMem/DiMe in AllowsA, only one per Prop/Dim, Mux with Mandats  and Excludes
            if ($slave) BIError2("$PMemDiMeNmes $val specified for a Slave but only Exclude or Allow $PMemDiMeNmes can be used with a Slave as filters");
            else if (!InChrList($propDimId, $UsablePropDims) && !in_array($pMemDiMeId, $idAllowsA)) BIError2("Default $PMemDiMeNmes $val is a member of $PropDimNme $propDimId which is not one of the Usable$PropDimNmes for the Bro, nor is $PMemDiMeNme $pMemDiMe an Allow $PMemDiMeNme.");
            else if (in_array($propDimId, $defPropDimsA, true)) BIError2("Default $PMemDiMeNmes $val is a member of $PropDimNme $propDimId for which another Default $PMemDiMeNme has been supplied, but a $PropDimNme can have only one Default $PMemDiMeNme.");
            else if (in_array($pMemDiMeId, $idMandatsA,  true)) BIError2("Default $PMemDiMeNmes $val is also a Mandatory $PMemDiMeNme. It can't be both.");
            else if (in_array($pMemDiMeId, $idExcludesA, true)) BIError2("Default $PMemDiMeNmes $val is also an Exclude $PMemDiMeNme. It can't be both.");
            else{
              if (!in_array($pMemDiMeId, $idDefaultsA, true))
                $idDefaultsA[] = $pMemDiMeId;
              $defPropDimsA[] = $propDimId;
            }
            break;
          case 'x': # Exclude PMems/DiMes, Prop/Dim in Bro UsablePropDims, Mux with Mandats, Defaults, and Allows
            if ($slave) $idExcludesA[] = $pMemDiMeId; # Leave Slave x: checking to Pass 2
            else if (!InChrList($propDimId, $UsablePropDims)) BIError2("Exclude $PMemDiMeNmes $val is a member of $PropDimNme $propDimId which is not one of the Usable$PropDimNmes for the Bro.");
            else if (InChrList($propDimId, $ExclPropDims))    BIError2("Exclude $PMemDiMeNmes $val is redundant as it is a member of $PropDimNme $propDimId which is excluded via the Excl$PropDimNmes property of the Bro");
            else if (in_array($pMemDiMeId, $idMandatsA, true))  BIError2("Exclude $PMemDiMeNmes $val is also a Mandatory $PMemDiMeNme. It can't be both.");
            else if (in_array($pMemDiMeId, $idDefaultsA, true)) BIError2("Exclude $PMemDiMeNmes $val is also a Default $PMemDiMeNme. It can't be both.");
            else if (in_array($pMemDiMeId, $idAllowsA, true))   BIError2("Exclude $PMemDiMeNmes $val is also an Allow $PMemDiMeNme. It can't be both.");
            else if (!in_array($pMemDiMeId, $idExcludesA, true))
              $idExcludesA[] = $pMemDiMeId;
            break;
          case 'a': # Allow PMems/DiMes, No UsablePropDims, or Prop/Dim in Bro ExclPropDims, Mux with Excludes. BroSetImport sets ExclPropDims if not set and removes from UsablePropDims
            if ($slave) $idAllowsA[] = $pMemDiMeId; # Leave Slave a: checking to Pass 2
            else if (in_array($pMemDiMeId, $idExcludesA, true)) BIError2("Allow $PMemDiMeNmes $val is also an Exclude $PMemDiMeNme. It can't be both.");
            else if (!in_array($pMemDiMeId, $idAllowsA, true)) {
              # Add the AllowPMemDiMe
              if ($UsablePropDims && InChrList($propDimId, $UsablePropDims)) {
                # Have UsablePropDims with the AllowPMemDiMe's PropDim in it, so remove it from $UsablePropDims and add it to $ExclPropDims if the Bro has a FolioHys and so can have ExclPropDims
                $c = IntToChr($propDimId);
                $UsablePropDims = str_replace($c, '', $UsablePropDims);
                if ($FolioHys) {
                  $ExclPropDims   = IntAToChrList(ChrListToIntA($ExclPropDims.$c)); # IntAToChrList(ChrListToIntA()) to keep ExclPropDims sorted
                  BINotice("$PropDimNme $propDimId added to Excl$PropDimNmes because of the use of Allow $PMemDiMeNmes $val");
                }
              }
              $idAllowsA[] = $pMemDiMeId;
            }
            break;
          default:
            BIError2("$PMemDiMeNmes value <i>$val</i> is not in expected format of &lt;m | d | e | a>: followed by an integer $PMemDiMeIdNme or a $PMemDiMeNme Name");
        }
      }
      $PMemDiMesA = [$idMandatsA, $idDefaultsA, $idExcludesA, $idAllowsA];
    }
    # Compact $PMemDiMesA i.e. empty subarray -> 0
    if ($PMemDiMesA) {
      $subArrays = 4;
      foreach ($PMemDiMesA as &$subRA)
        if (!$subRA || !count($subRA)) {
          $subRA = 0; # Set empty PMemDiMesA arrays to 0
        --$subArrays;
        }
      unset($subRA);
      if (!$subArrays)
        $PMemDiMesA = 0;
    }
    # End P1 PMems/DiMes

    if ($BroSetTaxnId) {
      # Out-BroSet for ManDims and NoTags and Hy Check

      # P1 -O Out-BroSet ManDims
      # - m - - Chr list of up to 4 DimIds use of one of which is mandatory if the Bro is an Ele Bro whose TxIt has multiple Hys. Checked below in Bro ManDims, Hy and Duplicates checks
      if (!$slave && ($Bits & BroB_Ele)) { # Ignore ManDims for a Slave or Set
        if ($v = $colsA[COL_ManDims]) {
          if ($nTxHys <= 1  || ($Bits & BroB_Set))
            BINotice("ManDim value <i>$v</i> ignored as ManDims are only applicable to Ele Bros with a TxId that has multiple Hypercubes");
          else{
            $manDimsA = [];
            foreach (explode(',', $v) as $t)
              if (ctype_digit($t)) {
                if (InRange($t, 1, $PropDimMaxId))
                  $manDimsA[] = $t;
                else
                  BIError2("The ManDims Dim <i>$t</i> of |$v| is out of the allowable range of 1 to $PropDimMaxId");
              }else if (isset($PropDimNamesMapA[$t]))
                $manDimsA[] = $PropDimNamesMapA[$t];
              else
                BIError2("The ManDims Dim <i>$t</i> of |$v| is unknown. (Case matters in Dim Names.)");
            if (strlen($ManDims = IntAToChrList($manDimsA)) > 4)
              BIError2("ManDims list <i>$v</i> length > max allowed of 4");
            # If the Bro has UsableDims, check to see that the ManDims are in the Usable list
            if ($UsablePropDims && $ManDims)
              for ($i=strlen($ManDims)-1; $i>=0; --$i)
                if (!InStr($ManDims[$i], $UsablePropDims))
                  BIError2(sprintf("ManDims Dim %d is not in the Bro's UsableDims list %s, so cannot be used", ChrToInt($ManDims[$i]), ChrListToCsList($UsablePropDims)));
          }
        }
        # P1 Out-BroSet Ele with TxId set - ManDims Check and possible AutoGeneration of ManDims
        if ($TxId && $nTxHys > 1) {
          # Ele Bro with TxId that has multiple Hys = need ManDims
          /* Build array of Hy Dims e.g. for TxId 85 with Hys 15, 16, 31
          Hy 15 -> 2,3,5,6,7   7 ok
          Hy 16 -> 2,3,5,6,39 39 ok
          Hy 31 -> 1,3,22,23  22,23 ok */
          $otherHyDims = ''; # chr list of the Hy dims for the other Hys e.g. 2,3,5,6,39,1,3,22,23 when doing Hy 15. Duplicates don't matter.
          $thisHyId    = ChrToInt($FolioHys);
          $thisHyDims  = $HyDimsA[$thisHyId];
          foreach (ChrListToIntA($txHysChrList) as $hyId)
            if ($hyId !== $thisHyId)
              $otherHyDims .= $HyDimsA[$hyId];
          if ($ManDims) {
            # ManDims supplied so check them for being unique across all Bros with this TxId
            $okB = 1;
            for ($i=strlen($ManDims)-1; $i>=0; --$i)
              if (InStr($thisHyDimCh = $ManDims[$i], $otherHyDims) || IsDimSubset($thisHyDimCh, $otherHyDims)) {
                BINotice(sprintf("ManDims Dim %d is not valid as another Hypercube of TxTd $TxId also includes it. BroSet Import will create a new ManDims entry", ChrToInt($ManDims[$i])));
                $okB = 0;
              }
            if (!$okB) $ManDims = 0;
          }
          if (!$ManDims) {
            # Generate ManDims entry
            $ManDims = $notUsableDims = '';
            for ($i=0, $len=strlen($thisHyDims); $i<$len; ++$i)
              if (!InStr($thisHyDimCh = $thisHyDims[$i], $otherHyDims) && !IsDimSubset($thisHyDimCh, $otherHyDims))
                if (InStr($thisHyDimCh, $UsablePropDims))
                  $ManDims .= $thisHyDimCh; # Add to ManDims as not in the other HyDims and in Usable list
                else
                  $notUsableDims .= $thisHyDimCh;
            if ($ManDims)
              BINotice(sprintf('ManDims generated of %s', ChrListToCsList($ManDims)));
            else{
              # No ManDims found
              $n = strlen($notUsableDims);
              BIError2(sprintf('No ManDims could be generated for the Bro as the possible %s %s %s excluded via the ExclDims of %s', PluralWord($n, 'Dim'), ChrListToCsList($notUsableDims), ($n>1 ? 'were' : 'was'), ChrListToCsList($ExclPropDims)));
            }
          }
        }
      }

      # P1 -O NoTags
      # o o o o  Stored in Bits. Can be set for a Bro with TxId to tell RG not to generate tags for this Bro = duplicate TxId.HyId{.TupId} allowed
      if ($v = $colsA[COL_NoTags]) {
        if (MatchOne($v, 'NoTags')) {
          if ($TxId)
            $Bits |= BroB_NoTags;
          else
            BINotice("NoTags ignored as NoTags only applies to Tx Bros");
        }else
          BIError2("NoTags value <i>$v</i> is invalid - NoTags or nothing expected");
      }

      # P1 -O Hy and Duplicates checks
      if ($TxId && $nFolioHys && !($Bits & BroB_NoTags))
        # TxId and Hys, not NoTags
        # Check Hys(s) re the TxId and build $TxIdHyIdTupIdBroIdsA
        for ($i=0; $i<$nFolioHys; ++$i) {
          $hyChr = $FolioHys[$i];
          $hyId  = ChrToInt($hyChr);
          if (InStr($hyChr, $txHysChrList)) {
            # Hy is in the Tx Hypercube
            # Check for duplicate TxId.HyId.TupId.ManDiMeId if not a Slave
            if (!$slave) {
              if (isset($TxIdHyIdTupIdBroIdsA[$TxId][$hyId][$TupId])) {
                # Duplicate TxId.HyId.TupId but could still be unique based on ExclPropDims or Mandatory DiMes
                $broId = $TxIdHyIdTupIdBroIdsA[$TxId][$hyId][$TupId];
                $otherBroA = $IbrosA[$broId];
                if (DupBrosManExclDimsOrManDiMes($ExclPropDims, $PMemDiMesA, $otherBroA['ExclPropDims'], $otherBroA['PMemDiMesA'])) {
                  # Duplicate (meaning same BroRef could be posted) i.e. not unique based on mandatory ExclPropDims or mandatory DiMes
                  if ($TupId)
                    BIError2(sprintf("Bro TxId $TxId HyId $hyId TupId $TupId is not unique and is not a Slave Bro - the Bro %s has the same TxId, HyId and TupId and is not made unique via Mandatory ExclPropDims or Mandatory $PMemDiMeNmes", BroLocation($broId)));
                  else
                    BIError2(sprintf("Bro TxId $TxId HyId $hyId is not unique and is not a Slave Bro - the Bro %s has the same TxId and HyId and is not made unique via Mandatory ExclPropDims or Mandatory $PMemDiMeNmes", BroLocation($broId)));
                }
                # Unique. djh?? But this replaces the previous entry?
              }
              $TxIdHyIdTupIdBroIdsA[$TxId][$hyId][$TupId] = $BroId;
            }
          }else # Hy not in Hypercube
            BIError2(($nTxHys === 1 ?"HyId $hyId does not equal the TxId $TxId hypercube value of " : "HyId $hyId is not included in the TxId $TxId hypercubes list of ") . ChrListToCsList($txHysChrList));
        }
    } # End of P1 Out-BroSet for ManDims and NoTags, plus Hy Check

    # P1 IO SumUp + - with Summing Bros.
    # n n n n
    if ($v = $colsA[COL_SumUp]) {
      if ($Level)
        $SumUp = $v; # for processing in Pass 3 as need to know if summing or not
      else
        BINotice("SumUp value <i>$v</i> supplied for a Level 0 Bro has been ignored");
    }

    # P1 IO Check
    # o o o o
    # Only applicable to summing Bros. Processing left to Pass 3 because of the possibility of forward references
    if ($v = $colsA[COL_Check]) {
      # Strip leading Check TargetBroId if present as after an Export
      # {TargetBroId }<Equal To | Equal & Opp To>{, <Either | Both | Check | Target>} TargetBroId Source
     #$check = ctype_digit(substr($v,0,4)) ? ltrim(substr($v,4)) : $v; # for processing in Pass 3
      if (($p = strpos($v, ' ')) && ctype_digit(substr($v,0,$p)))
        $check = substr($v, $p+1); # for processing in Pass 3
      else
        $check = $v;
      if (!strlen($check))
        BIError2('Check not in form: {TargetBroId} &lt;Equal To | Equal & Opp To>{ &lt;Either | Both | Check | Target>}{ Year#} BroName');
      else
        while ($check[0]===',') {
          for ($i=1; ctype_digit($check[$i]); ++$i)
            ;
          $check=ltrim(substr($check, $i));
        }
    }

    if (!$BroSetTaxnId && ($v = $colsA[COL_Related])) {
      # P1 I- Related
      # Related I   - o - - Source = <M | O | U> RelatedBro Name
      # Check for Ele, not slave, and 1st character here but leave the rest to Pass 3 as could be a forward ref
      if (!($Bits & BroB_Ele) || $slave)
        BIError2("Related <i>$v</i> is invalid as Related can only be used with non-slave Element Bros");
      else{
        if (InStr($v[0], 'MOU') && strlen($v) > 2 && $v[1] === SP)
          $Related = $v;
        else
          BIError2("Related <i>$v</i> is not in expected form of &lt;M | O | U> BroName");
      }
    }

    # P1 IO Period
    # y y y y
    if ($v = $colsA[COL_Period]) {
      if (!($PeriodSEN = Match($v, ['Duration', 'Instant']))) {
        BIError2("Period value <i>$v</i> is invalid - one of Duration, Instant, or nothing is required");
        $PeriodSEN = BPT_Duration;
      }
    }else
      $PeriodSEN = BPT_Duration;
    if ($TxId && $xA['PeriodN'] != $PeriodSEN)
      BIError2(sprintf("Period value supplied of <i>%s</i> does not match the Taxonomy value for element TxId=$TxId of %s", PeriodTypeToStr($PeriodSEN), PeriodTypeToStr($xA['PeriodN'])));

    # P1 IO StartEnd
    # o o o o
    # <SumEnd|PostEnd|Stock>{ SumList...} with SumList only applicable to Ele Bros
    if ($v = $colsA[COL_StartEnd]) {
      if ($PeriodSEN === BPT_Duration)
        BIError2("StartEnd value <i>$v</i> supplied but this Bro has Duration period so cannot have StartEnd");
      else{
        if (!in_array($DataTypeN, $BroSumTypesGA))
          BIError2("StartEnd value <i>$v</i> supplied but this Bro is not a Summing Bro so cannot have StartEnd");
        else{
          # Expect <SumEnd|PostEnd|Stock>{ SumList...} with SumList only applicable to Ele Bros
          /* BroInfo.PeriodSEN enums
          const BPT_Duration    = 1; # Same as TPT_Duration
          const BPT_Instant     = 2; # Same as TPT_Instant
          const BPT_InstSumEnd  = 3; # Instant StartEnd SumEnd  type
          const BPT_InstPostEnd = 4; # Instant StartEnd PostEnd type
          const BPT_InstStock   = 5; # Instant StartEnd Stock   type */
          $vA = explode(SP, str_replace(', ', ',', $v)); # with ,SP in the list as added by export reduced to ,
          if (($n=count($vA)) > 2)
            BIError2("StartEnd value <i>$v</i> not in expected format of &lt;SumEnd|PostEnd|Stock>{ SumList}");
          else{
            if ($t = Match($vA[0], ['SumEnd', 'PostEnd', 'Stock'])) {
              $PeriodSEN = $t + 2; # BPT_InstSumEnd | BPT_InstPostEnd | BPT_InstStock
              if ($n === 1) {
                if (!$BroSetTaxnId && ($Bits & BroB_Ele))
                  BIError2("StartEnd value <i>$v</i> invalid - a SumList is required for an In-BroSet StartEnd Element Bro");
              }else{
                # $n === 2
                if ($BroSetTaxnId)
                  BIError2('An Out-BroSet StartEnd Bro cannot have a SumList');
                else if ($Bits & BroB_Set)
                  BIError2("StartEnd value <i>$v</i> invalid - a SumList is not allowed for a StartEnd Set Bro");
                $SeSumList = $vA[1]; # SeSumList for processing when all Bros are available
                if ($PeriodSEN === BPT_InstStock && InStr(',', $SeSumList))
                  BIError2('A StartEnd Stock Bro is expected to have a SumList that is only one member long');
              }
            }else
              BIError2("StartEnd value <i>$v</i> invalid - should be &lt;SumEnd|PostEnd|Stock>{ SeSumList}");
          }
        }
      }
    } # end StartEnd
    # Tx StartEnd check
    if ($TxId) {
      if ($PeriodSEN >= BPT_InstSumEnd) {
        # StartEnd
        if (!$slave)
          BIError2('A Tx StartEnd Bro must be a Slave of an In-BroSet StartEnd Bro where the input and movement calculations are performed.');
      }else if (in_array($TxId, $StartEndTxIdsGA))
        BIError2("The Bro's TxId $TxId is a StartEnd one so StartEnd needs to be defined as SumEnd, PostEnd, or Stock");
    }

    # P1 IO Zones
    # o o o p
    if ($v = $colsA[COL_Zones]) {
      # Expect cs list of up to 10 Zones by Ref
      $zoneIdsA = [];
      foreach (explode(',', $v) as $zoneRef) {
        if (isset($ZoneRefsA[$zoneRef]))
          $zoneIdsA[] = $ZoneRefsA[$zoneRef]; # Zones.Id
        else
          BIError2("Zone <i>$zoneRef</i> is not one of the defined Zones");
      }
      if (strlen($Zones = IntAToChrList($zoneIdsA)) > 10) # $Zones = sorted unique chr list
        BIError2("Zones list <i>$v</i> length > max allowed of 10");
    }

    # P1 IO Order
    # - o o o
    if ($v = $colsA[COL_Order]) {
      if ($Level) {
        if (ctype_digit($v)) {
          $SortOrder = (int)$v;
          # djh?? Check for uniqueness within set?
        }else
          BIError2("Order value <i>$v</i> is not an integer as expected");
      }else
        BINotice('Order value ignored as Level 0 Sets cannot use the Order property');
    }

    # P1 IO Descr
    # o o o p
    if ($v = $colsA[COL_Descr]) {
      if (!$TxId || $v !== $DB->StrOneQuery(sprintf('Select T.Text From %s.Elements E Join %s.Text T on T.Id=E.StdLabelTxtId Where E.Id=%d', DB_Tx, DB_Tx, $TxId))) {
        if (strlen($v) > 400) BIError2('Descr length of '.strlen($v).' exceeds the allowable length of 400 characters');
        $Descr = $v;
      }
    }

    # P1 I- Taxonomies
    # o o o o
    if (!$BroSetTaxnId && $v = $colsA[COL_Taxonomies]) {
      foreach (explode(COM, $v) as $t) {
        $t = trim($t);
        if ($t[0] === '-') {
          $idx = BII_NotTaxnIdsA;
          $t = substr($t, 1);
        }else
          $idx = BII_TaxnIdsA;
        if ((!($id = (int)$t) || !isset($TaxnNamesA[$id])) && ($id = array_search($t, $TaxnNamesA)) === false)
          BIError2("Taxonomy $t is not a known live taxonomy. The available live taxonomies are: ".implode(', ', $TaxnNamesA));
        else if (($DataA[BII_TaxnIdsA] && in_array($id, $DataA[BII_TaxnIdsA])) || ($DataA[BII_NotTaxnIdsA] && in_array($id, $DataA[BII_NotTaxnIdsA])))
          BIError2("Taxonomy $t is duplicated. A taxonomy can only be specified once in the Taxonomies column");
        else if ($BsDataA[BSI_NotTaxnIdsA] && in_array($id, $BsDataA[BSI_NotTaxnIdsA]))
          # Taxn is in the BroSet Not list so there is no point in either including it or excluding it for the Bro
          BIError2(sprintf("The BroSet is excluded from use with taxonomy $t so it serves no purpose to %s with it",  $idx === BII_TaxnIdsA ? 'allow this Bro to be used' : 'exclude this Bro from use'));
        else if ($idx === BII_TaxnIdsA && $BsDataA[BSI_TaxnIdsA] && in_array($id, $BsDataA[BSI_TaxnIdsA]))
          # Taxn is in the BroSet list so there is no point in including it for the Bro. Excluding for the Bro is fine
          BIError2("The BroSet is allowed for use with Taxonomy $t so it serves no purpose to also allow this Bro to be used with it. Did you mean to exclude this Bro from use with caxonomy $t?");
        $DataA[$idx][] = $id;
      }
    }

    # P1 IO Countries
    # o o o o
    if ($v = $colsA[COL_Countries]) {
      foreach (explode(COM, $v) as $t) {
        $t = trim($t);
        if ($t[0] === '-') {
          $idx = BII_NotCtryIdsA;
          $t = substr($t, 1);
        }else
          $idx = BII_CtryIdsA;
        if ((!($id = (int)$t) || !isset($CtryRefsA[$id])) && ($id = array_search($t, $CtryRefsA)) === false)
          BIError2("Country $t is not a known country Ref (ShortName). The available countries are: ".implode(', ', $CtryRefsA));
        else if (($DataA[BII_CtryIdsA] && in_array($id, $DataA[BII_CtryIdsA])) || ($DataA[BII_NotCtryIdsA] && in_array($id, $DataA[BII_NotCtryIdsA])))
          BIError2("Country $t is duplicated. A country can only be specified once in the Countries column");
        else if ($BsDataA[BSI_NotCtryIdsA] && in_array($id, $BsDataA[BSI_NotCtryIdsA]))
          # Ctry is in the BroSet Not list so there is no point in either including it or excluding it for the Bro
          BIError2(sprintf("The BroSet is excluded from use with country $t so it serves no purpose to %s with it",  $idx === BII_CtryIdsA ? 'allow this Bro to be used' : 'exclude this Bro from use'));
        else if ($idx === BII_CtryIdsA && $BsDataA[BSI_CtryIdsA] && in_array($id, $BsDataA[BSI_CtryIdsA]))
          # Ctry is in the BroSet list so there is no point in including it for the Bro. Excluding for the Bro is fine
          BIError2("The BroSet is allowed for use with country $t so it serves no purpose to also allow this Bro to be used with it. Did you mean to exclude this Bro from use with country $t?");
        $DataA[$idx][] = $id;
      }
    }

    # P1 IO EntityTypes
    # o o o o
    if ($v = $colsA[COL_EntityTypes]) {
      foreach (explode(COM, $v) as $t) {
        $t = trim($t);
        if ($t[0] === '-') {
          $idx = BII_NotETypeIdsA;
          $t = substr($t, 1);
        }else
          $idx = BII_ETypeIdsA;
        if (!($id = (int)$t) || !isset($ETypesA[$id])) {
          $id = 0;
          foreach ($ETypesA as $ETypeId => $etA)
            foreach ($etA[0] as $ctryId)
              if ($CtryRefsA[$ctryId].SP.$etA[1] === $t) { # UK Private Company
                $id = $ETypeId;
                break;
              }
        }
        if (!$id)
          BIError2("EntityType $t is not a known EntityType. Use EntityType Id or CountryRef EntityTypeShortName e.g. 5 or UK Private Company");
        else if (($DataA[BII_ETypeIdsA] && in_array($id, $DataA[BII_ETypeIdsA])) || ($DataA[BII_NotETypeIdsA] && in_array($id, $DataA[BII_NotETypeIdsA])))
          BIError2("EntityType $t is duplicated. An EntityType can only be specified once in the EntityTypes column");
        else if ($BsDataA[BSI_NotETypeIdsA] && in_array($id, $BsDataA[BSI_NotETypeIdsA]))
          # EntityType is in the BroSet Not list so there is no point in either including it or excluding it for the Bro
          BIError2(sprintf("The BroSet is excluded from use with EntityType $t so it serves no purpose to %s with it",  $idx === BII_ETypeIdsA ? 'allow this Bro to be used' : 'exclude this Bro from use'));
        else if ($idx === BII_ETypeIdsA && $BsDataA[BSI_ETypeIdsA] && in_array($id, $BsDataA[BSI_ETypeIdsA]))
          # Ctry is in the BroSet list so there is no point in including it for the Bro. Excluding for the Bro is fine
          BIError2("The BroSet is allowed for use with EntityType $t so it serves no purpose to also allow this Bro to be used with it. Did you mean to exclude this Bro from use with EntityType $t?");
        $DataA[$idx][] = $id;
      }
    }

    # P1 IO Comment
    # o o o o o
    if (($Comment = $colsA[COL_Comment]) && strlen($Comment) > 500) {
      BIError2('Comment length of '.strlen($Comment).' exceeds the allowable length of 500 characters');
      continue;
    }

    # P1 IO Scratch
    # o o o o
    $Scratch = $colsA[COL_Scratch];

    # P1 Set and Ele Type Bits
    if (!($Bits & (BroB_Set | BroB_Ele)))
      BIError2('No Type has been defined for this Bro nor could it be deducted from Master settings');

    $tA = [
      'broSetId'  => $broSetId,
      'RowNum'    => $RowNum,
      'postType'  => $postType,
      'master'    => $master, # 0, 2 or BroName string. Becomes Master BroId once resolved
      'check'     => $check,  # 0 or BroName string
      'Name'      => $Name,
      'Level'     => $Level,
      'DadId'     => $DadId,
      'Bits'      => $Bits,
      'DataTypeN' => $DataTypeN,
      'DboFieldN' => $DboFieldN,
      'SignN'     => $SignN,
      'SumUp'     => $SumUp,
      'Related'   => $Related,
      'SeSumList' => $SeSumList, # list in Name or ShortName form. Becomes a broId list once resolved
      'PeriodSEN' => $PeriodSEN,
      'SortOrder' => $SortOrder,
      'FolioHys'      => $FolioHys,
      'ExclPropDims'  => $ExclPropDims,
      'AllowPropDims' => $AllowPropDims,
      'PMemDiMesA'    => $PMemDiMesA,
      'UsablePropDims'=> $UsablePropDims,
      'SlaveYear' => $SlaveYear,
      'Zones'     => $Zones,
      'ShortName' => $ShortName,
      'Ref'       => $Ref,
      'Descr'     => $Descr,
      'DataA'     => $DataA,
      'Comment'   => $Comment,
      'Scratch'   => $Scratch
    ];
    if ($BroSetTaxnId) {
      $tA['TxId']    = $TxId;
      $tA['TupId']   = $TupId;
      $tA['ManDims'] = $ManDims;
    }
    $IbrosA[$BroId] = $tA;

    $RowMapA[$RowNum] = $BroId;
    if ($RowComments) {
      if (($RowComments = substr($RowComments, 1)) == '') $RowComments=' '; # strip initial  then if empty (single blank row) set to ' ' to preserve a blank line
      $RowCommentsA[$RowNum] = $RowComments;
      $RowComments = '';
    }
    $prevRowWasIncludeB = 0;
  } # end of loop thru rows

  # Check for: Out-Main: Must include a single In-MaIn-BroSet
  if ($BroSetTypeN === BroSet_Out_Main && !$InBroSetId)
    ErrorExit("This is an Out-MaIn-BroSet which must include a single In-MaIn-BroSet but no such BroSet has been included");
    #########

  if (!$Errors) {
    end($RowMapA);
    $LastRowNum = key($RowMapA);
    while ($LastRowNum && !$RowMapA[$LastRowNum]) # Step back to the last row that isn't a blank or comment row
     --$LastRowNum;
    if ($RowComments) {
      # Had post RowComments
      # row comment{row comment...}{row comment{row comment...}}
      if ($LastRowNum && ($RowComments = substr($RowComments, 1))) # strip initial  then skip if empty (i.e. single blank row after)
        if (isset($RowCommentsA[$LastRowNum]))
          $RowCommentsA[$LastRowNum] .= ''.$RowComments;
        else
          $RowCommentsA[$LastRowNum] = ''.$RowComments;
    }
    # Check for sets with no members if there are no errors
    foreach ($SetMemNumsA as $broId => $num)
      if (!$num) {
        $RowNum = $IbrosA[$broId]['RowNum']; # For global use in BIError2 or BIWarning
        if ($IbrosA[$broId]['Bits'] & BroB_Slave)
          BIError2('The Set Slave does not have any members, which is not legal. Either add members to the set or remove the set property');
        else
          BIWarning('The Set does not have any members. This is legal but not useful.');
      }
  }
  # Convert the Defined Bro names to BroIds
  $finalStatus = Status_OK; # if all goes through with no defined Bro warnings
  $msg = '';
  foreach ($DefinedBroNamesA as $i => $definedBroName) {
    if ($BsDataA[$i]) {
      $broName = $BsDataA[$i];
      if ($BroSetTypeN !== BroSet_In_Main) {
        $msg .= "<br>The $definedBroName = $broName row near the start of this import should only be included in an In-MaIn-BroSet.";
        continue;
      }
      if (isset($NamesA[$broName]))
        $BsDataA[$i] = $NamesA[$broName];
      else
        $msg .= "<br>No Bro named <i>$broName</i> for the $definedBroName = $broName row near the start of this import was found";
    }else if ($BroSetTypeN === BroSet_In_Main) {
      $RowNum = $BroSetLastRow;
      BIWarning("$definedBroName = nnnnnnn where nnnnnnn is a full Bro Name should be included on a row after the BroSet rows for an In-Main-BroSet");
      $finalStatus = Status_DefBroErrs; # Not ok if have Defined Bro warnings, but allow saving i.e. not an error for wip
    }
  }
  if ($msg)
    ErrorExit(substr($msg, 4));
    #########

  if ($Errors) Errors('in Pass 1'); # Does not return

  # After Pass 1 have finished with:
  unset ($FolioHyNamesMapA, $PropDimNamesMapA, $PMemDiMeNamesMapA, $setTxNamesA, $SetMemNumsA,
         $ZoneRefsA, $TuplesByTxIdA, $TupIdsByMemberTxIdAndHyIdGA, $StartEndTxIdsGA);
} # end of Pass 1

##########
# Pass 2 # Process Slaves incl DataTypeN for Slaves
##########
{ # $PMemsDiMesAR, $NamesA, $IbrosA, $TxIdHyIdTupIdBroIdsA

  # Pass 2 for Master/Slave incl DataTypeN for Slaves
  foreach ($IbrosA as $BroId => &$broRA) {
    if ($broRA['broSetId'] !== $BroSetId) continue; # skip included Bros
    extract($broRA); # $broSetId, $RowNum, $postType, $master, $check, $Name, $Level, $DadId, $Bits, $DataTypeN, $DboFieldN, $SignN, $SumUp, $Related, $SeSumList, $PeriodSEN, $SortOrder,
                     # $FolioHys, $ExclPropDims, $AllowPropDims, $PMemDiMesA, $UsablePropDims, $SlaveYear, $Zones, $ShortName, $Ref, $Descr, $DataA, $Comment, $Scratch
                     # { $TxId, $TupId, $ManDims}
    if ($master) {
      # Slave whose Master is to be matched or checked
      $nFolioHys = strlen($FolioHys);
      if ($master === Slave_MasterMatch) {
        # Only for Out-BroSets
        # Master to be defined by TxId.HyId.TupId match. Check for Hy subsets. HyId to stay as input i.e. not inherited from Master.
        if ($nFolioHys > 1) {
          BIError2("Slave/Master matching via TxId.HyId.TupId is not supported for multiple HyIds. Please specify the required Master explicitly.");
          continue;
        }
        $matchesA=[];
        $hyId = ChrToInt($FolioHys);
        if (isset($TxIdHyIdTupIdBroIdsA[$TxId][$hyId][$TupId]))
          $matchesA[] = $TxIdHyIdTupIdBroIdsA[$TxId][$hyId][$TupId];
        # Check via subsets as well
        for ($i=1; $i<=$FolioHyMaxId; ++$i)
          if ($i != $hyId && IsHypercubeSubset($hyId, $i)) {
            if (isset($TxIdHyIdTupIdBroIdsA[$TxId][$i][$TupId]))
              $matchesA[] = $TxIdHyIdTupIdBroIdsA[$TxId][$i][$TupId];
          }
        if ($matches=count($matchesA)) {
          if ($matches === 1) {
            $masterBroId = $matchesA[0];
            $masterBroRA = &$IbrosA[$masterBroId];
          }else{
            if ($TupId)
              BIError2("For this Slave $matches possible Master matches have been found at rows ".implode(',',$matchesA)." based on $TxId.$hyId.$TupId TxId.HyId.TupId matching with hypercube subsets. Should another of these also be a Slave?");
            else
              BIError2("For this Slave $matches possible Master matches have been found at rows ".implode(',',$matchesA)." based on $TxId.$hyId TxId.HyId matching with hypercube subsets. Should another of these also be a Slave?");
            continue;
          }
        }else{
          if ($TupId)
            BIError2("No Master found for this Slave based on $TxId.$hyId.$TupId TxId.HyId.TupId matching. Add the missing Master or check the values for this Bro.");
          else
            BIError2("No Master found for this Slave based on $TxId.$hyId TxId.HyId matching. Add the missing Master or check the values for this Bro.");
          continue;
        }
      }else{
        # Either In or Out-BroSets
        # Explicit Master value supplied. Slave can have TxId, HyId, TupId supplied or not.
        if (isset($NamesA[$master]))
          $masterBroId = $NamesA[$master];
        else if (isset($ShortNamesA[$master]))
          $masterBroId = $ShortNamesA[$master];
        else{
          BIError2("Master <i>$master</i> for this Slave not found. Correct the Master &lt;Name | ShortName> or add an Include BroSet row if the Master is in another BroSet which isn't currently being included.");
          continue;
        }
        $masterBroRA = &$IbrosA[$masterBroId];
        if ($masterBroRA['Bits'] & BroB_Slave) {
          BIError2("The nominated Master $master for this Slave is also a Slave. A Master can have multiple Slaves but a chain of Slaves is not permitted.");
          continue;
        }
        # Can have TxId, HyId, TupId supplied or not. Use Master values if not, but check them vs Master if supplied.
        $mastFolioHys  = $masterBroRA['FolioHys'];

        # P2 Slave FolioHys
        if ($FolioHys) {
          if ($mastFolioHys && $FolioHys !== $mastFolioHys) {
            $nMastFolioHys = strlen($mastFolioHys);
            $nFolioHys  = strlen($FolioHys);
            if ($nMastFolioHys >= $nFolioHys) {
              if ($nFolioHys === 1) {
                # May be OK via subset matching
                $folioHyId     = ChrToInt($FolioHys);
                $mastFolioHyId = ChrToInt($mastFolioHys);
                if (!$IsFolioHySubsetFn($folioHyId, $mastFolioHyId)) {
                  BIError2("The Master $master for this Slave has $FolioHyNme $mastFolioHyId but the Slave has $FolioHyNme $folioHyId which is not a subset of $mastFolioHyId. Correct the Master name, or the Slave $FolioHyNme.");
                  continue;
                }
              }else{
                # Out-BroSet only if $nFolioHys !== 1
                BIError2("The Master $master for this Slave has HyIds ".ChrListToCsList($mastFolioHys)." but the Slave has HyIds ".ChrListToCsList($FolioHys).". Correct the Master name, or the Slave HyIds.");
                continue;
              }
            }else{
              BIError2("The Master $master for this Slave has ".NumPluralWord($nMastFolioHys, $FolioHyNme).' '.ChrListToCsList($mastFolioHys).' but the Slave has '.NumPluralWord($nFolioHys, $FolioHyNme).' '.ChrListToCsList($FolioHys).". Correct the Master name, or the Slave HyId(s).");
              continue;
            }
          }
        }else{
          # Explicit Master case with no Hys supplied
          $broRA['FolioHys'] = $FolioHys = $mastFolioHys; # Set Slave FolioHys to Master value. Could be none.
          $nFolioHys = strlen($FolioHys);
        }

        if ($BroSetTaxnId) {
          # Out-BroSet

          $masterTxId  = isset($masterBroRA['TxId'])  ? $masterBroRA['TxId']  : 0; # djh?? Do better
          $masterTupId = isset($masterBroRA['TupId']) ? $masterBroRA['TupId'] : 0; # djh?? Do better

          # Slave TxId
          if ($TxId) {
            if ($masterTxId && $TxId != $masterTxId) {
              BIError2("The Master $master for this Slave has a TxId of $masterTxId but the Slave has a TxId of $TxId. Correct the Master name, or the Slave TxId, or remove the Master name to allow TxId.HyId{.TupId} matching, or remove the Slave TxId and HyId if you are sure the Master name is correct.");
              continue;
            }
          }else
            # No TxId so set to Master's if defined
            if ($masterTxId)
              $broRA['TxId'] = $TxId = $masterTxId;

          # P2 Slave TupId
          if ($TupId) {
            if ($masterTupId && $TupId != $masterTupId) {
              BIError2("The Master $master for this Slave has a TupId of $masterBroRA[TupId] but the Slave has a TupId of $TupId. Correct the Master name, or the Slave TxId, or remove the Master name to allow TxId.HyId.TupId matching, or remove the Slave TxId, HyId and TupId if you are sure the Master name is correct.");
              continue;
            }
          }else
            # No TupId so set to Master's if defined
            if ($masterTupId)
              $broRA['TupId'] = $TupId = $masterTupId;
        }else
          $TxId = 0; # re tests below
      }
      $broRA['master'] = $masterBroId; # now BroId of the Master

      # Update Master
      $masterBroRA['Bits'] |= BroB_Master;
      /* No. SlaveIds Delegtaed to BuildStructs re includes
      if ($masterBroRA['Bits'] & BroB_Master) {
        # Been here before
        # Add to SlaveIds in Slave's master
        $masterBroRA['SlaveIds'][] = $BroId;
        if (count($masterBroRA['SlaveIds']) > 20) {
          BIError2("The number of Slaves for Master <i>$masterBroRA[Name]</i> exceeds the limit of 20.");
          continue;
        }
      }else{
        # First time for this master
        $masterBroRA['Bits'] |= BroB_Master;
         Set SlaveIds in Slave's master
         $masterBroRA['SlaveIds'] = [$BroId];
      } */

      # Back to the Slave

      # P2 Slave DataType - done in Pass 1 for all except Slave
      # m m m I
      $broRA['DataTypeN'] = $DataTypeN = $masterBroRA['DataTypeN'];

      # P2 Slave Sign
      # b b b I
      if ($DataTypeN === DT_Money) {
        $masterSignN = $masterBroRA['SignN'];
        if ($SignN) {
          if ($SignN !== $masterSignN)
            BIWarning("Slave Bro <i>$Name</i> Sign of ".SignToStr($SignN)." is different from the Master Sign of ".SignToStr($masterSignN));
        }else{
          # No Sign but need Sign. Try for it from Master and if not there then ancestors
          if ($masterSignN)
            $SignN = $masterSignN;
          else{
            for ($broId=$DadId; $broId && !$SignN && $IbrosA[$broId]['DataTypeN'] === DT_Money; $broId=$IbrosA[$broId]['DadId'])
              $SignN=$IbrosA[$broId]['SignN'];
            if (!$SignN) {
              BIError2('No Sign supplied for a Money Slave Bro either directly, from its Master, or by inheritance');
              $SignN = BS_Dr; # but carry on, setting Sign to avoid more errors
            }
          }
          $broRA['SignN'] = $SignN;
        }
      }

      # P2 Slave PeriodSEN
      if ($TxId && $PeriodSEN !== $masterBroRA['PeriodSEN'])
        BIError2(sprintf('The Period and StartEnd properties of a Slave (%s) must equal those of its Master (%s). This may require adjustment in the Master rather than the Slave', PeriodSENStr($PeriodSEN), PeriodSENStr($masterBroRA['PeriodSEN'])));

      # P2 Build the Slave UsableProps/UsableDims
      if ($nFolioHys) {
        $UsablePropDims   = $BroSetTaxnId ? UsableDims($FolioHys, $nFolioHys) : $FolioPropsA[ChrToInt($FolioHys)];
        $masterUsableDims = $masterBroRA['UsablePropDims'];
        if ($UsablePropDims !== $masterUsableDims) {
          # Slave Usable different from Master's because of Slave Folio/Hy being a subset of master's, or because of Excl/Allow PropDims use with Master.
          # Remove from Slave Usable any Prop/Dim which is not part of the Master's Usable as no data for that Prop/Dim can be posted or summed to the Master, and so will never be available to be copied to the Slave.
          $slaveUsablePropDims = '';
          $len = strlen($UsablePropDims);
          for ($i=0; $i<$len; ++$i)
            if (strpos($masterUsableDims, ($c = $UsablePropDims[$i])) === false) {
              # Slave Prop/Dim not in Master's Usable but in Dim case check for it being a subset, as Dim 1 is of Dim 2 for UK-GAAP
              if ($BroSetTaxnId && IsDimSubset($c, $masterUsableDims))
                $slaveUsablePropDims .= $c;
            }else
              $slaveUsablePropDims .= $c; # Slave Prop/Dim is in in Master's Usable
          $UsablePropDims = $slaveUsablePropDims;
        }
        # Slave ExclPropDims
        if ($ExclPropDims) {
          # Check to see that the ExclPropDims are in the Bro's Usable list
          $len = strlen($ExclPropDims);
          for ($i=0; $i<$len; ++$i)
            if (!InStr($ExclPropDims[$i], $UsablePropDims))
              BIError2(sprintf("Excl$PropDimNmes $PropDimNme %d is not in the Bro's Usable$PropDimNmes list %s, so cannot be excluded. This could be because the $PropDimNme is not one of the Master's Usable$PropDimNmes.", ChrToInt($ExclPropDims[$i]), ChrListToCsList($UsablePropDims)));
          # Remove $ExclPropDims from $UsablePropDims for subsequent checks
          $UsablePropDims = str_replace(str_split($ExclPropDims), '', $UsablePropDims);
        }
        # Slave AllowProps/AllowPropDims
        if ($AllowPropDims) {
          # If the Bro has a Usable list, check to see that the AllowProps/AllowPropDims are in the Usable list
          if ($UsablePropDims) {
            $len = strlen($AllowPropDims);
            for ($i=0; $i<$len; ++$i)
              if (!InStr($AllowPropDims[$i], $UsablePropDims))
                BIError2(sprintf("Allow$PropDimNmes $PropDimNme %d is not in the Bro's Usable$PropDimNmes list %s, so cannot be allowed. This could be because the $PropDimNm is not one of the Master's Usable$PropDimNmes.", ChrToInt($ExclPropDims[$i]), ChrListToCsList($UsablePropDims)));
          }
          $UsablePropDims = $AllowPropDims;
        }
        $broRA['UsablePropDims'] = $UsablePropDims;
      }

      # P2 Check PMems/DiMes for the Slave
      if ($PMemDiMesA) {
        $masterPMemDiMesA = $masterBroRA['PMemDiMesA'];
        $idExcludesA = $PMemDiMesA[II_ExcludesA];
        $idAllowsA   = $PMemDiMesA[II_AllowsA];

        # Slave PMem/DiMes Excludes x:
        if ($idExcludesA)
          foreach ($idExcludesA as $pMemDiMeId) {
            $propDimId = $PMemsDiMesAR[$pMemDiMeId][PMemI_PropId]; # PMemI_PropId = PMemI_PropId
            if (!InChrList($propDimId, $UsablePropDims)) {
              # x:PMemDiMeId not in Usable list. Could still be ok if allowed by Master's PMemDiMes
              if ($masterPMemDiMesA && $masterPMemDiMesA[II_AllowsA] && in_array($pMemDiMeId, $masterPMemDiMesA[II_AllowsA]))
                continue;
              BIError2("Exclude $PMemDiMeNmes x:$pMemDiMeId is a member of $PropDimNme $propDimId which is not one of the Usable$PropDimNmes for the Bro".(InChrList($propDimId, $masterUsableDims) ? '.' : " because the $PropDimNme is not one of the Master's Usable$PropDimNmes."));
            }else if (InChrList($propDimId, $ExclPropDims)) BIError2("Exclude $PMemDiMeNmes x:$pMemDiMeId is redundant as it is a member of $PropDimNme $propDimId which is excluded via the Excl$PropDimNmes property of the Bro");
            else if ($idAllowsA && in_array($pMemDiMeId, $idAllowsA)) BIError2("Exclude $PMemDiMeNmes x:$pMemDiMeId is also an Allow $PMemDiMeNme. It can't be both.");
          }

        # Slave DiMes Allows a:
        if ($idAllowsA)
          foreach ($idAllowsA as $pMemDiMeId) {
            $propDimId = $PMemsDiMesAR[$pMemDiMeId][PMemI_DimId];
            if (!InChrList($propDimId, $UsablePropDims)) {
              # a:PMemDiMeId not in Usable list. Redundant if allowed by Master's PMemDiMes
              if ($masterPMemDiMesA && $masterPMemDiMesA[II_AllowsA] && in_array($pMemDiMeId, $masterPMemDiMesA[II_AllowsA]))
                BIError2("Allow $PMemDiMeNmes a:$pMemDiMeId is allowed by the Master's $PMemDiMeNmes property so does not need to be repeated for the Slave.");
              else
                BIError2("Allow $PMemDiMeNmes a:$pMemDiMeId is a member of $PropDimNme $propDimId which is not one of the Usable$PropDimNmes for the Bro".(InChrList($propDimId, $masterUsableDims) ? '.' : " because the $PropDimNme is not one of the Master's Usable$PropDimNmes."));
            }else if (InChrList($propDimId, $AllowPropDims)) BIError2("Allow $PMemDiMeNmes a:$pMemDiMeId is redundant as it is a member of $PropDimNme $propDimId which is allowed via the Allow$PropDimNmes property of the Bro");
            else if (in_array($pMemDiMeId, $idExcludesA)) BIError2("Allow $PMemDiMeNmes a:$pMemDiMeId is also an Exclude $PMemDiMeNme. It can't be both.");
          }
      }

      # P2. Set the Slave's filtering bit if Slave Filtering is in use via PMems/DiMes or UsableProps/UsableDims
      # Slave filtering applies always if the Slave has PMems/DiMes settings.
      # Slave filtering via Usable*s applies if the Slave has a Usable list defined (can be none)
      #  and there are any PropDims in the Master's Usable list which are not in the Slave's Usable list.
      # The two Usable lists must be different for this to be the case. The Usuable list filtering case can arise if:
      # - Slave Folio/Hy is a subset of the Master's (tho negated if Master has Excl*s for the PropDims it has that are not in the subset Folio/Hy)
      # - Slave has Excl*s or Allow*s which remove Prop/Dims in the Master's Usable list
      # It doesn't matter if the Slave's Usable list contains PropDims which are not in the Master's Usable list.
      # ==> Usable list Slave filtering applies if the two Usable lists are different and if the Master list is not a subset of the Slave's Usable list.
      if ($PMemDiMesA || ($UsablePropDims && ($UsablePropDims !== $masterBroRA['UsablePropDims'] && !$IsListSubSetFn($masterBroRA['UsablePropDims'], $UsablePropDims))))
        $broRA['Bits'] |= BroB_SFilter;

      # Unset Usable list for an Ele Slave without Usable list filtering - no leave Usable list so that BrosExport and BuildStructs can repeat above Filtering calc
      # if (($Bits & BroB_Ele) && !$usableDimsFiltering)
      #  $broRA['UsablePropDims'] = 0;

    }# else not a Slave
  }
  if ($Errors) Errors('in Pass 2'); # Does not return
  unset ($PMemsDiMesAR, $DiMesA, $matchesA);
} # End of Pass 2

##########
# Pass 3 # with Slaves and Masters done and specifically with DataTypeN inherited from Master for Slaves as needed for $summing (SumUp) and PostType
##########
{ # $BroSumTypesGA, $MUsePropsGA, $NonSummingBroExclPropDimsAR, $PMemsA, $NamesA, $IbrosA
  # - SumUp
  # - PostType
  # - Check
  # - Related
  # - some checks
  $checkPerformsA = ['Either', 'Both', 'Check', 'Target'];

  foreach ($IbrosA as $BroId => &$broRA) {
    if ($broRA['broSetId'] !== $BroSetId) continue; # skip included Bros
    extract($broRA); # $broSetId, $RowNum, $postType, $master, $check, $Name, $Level, $DadId, $Bits, $DataTypeN, $DboFieldN, $SignN, $SumUp, $Related, $SeSumList, $PeriodSEN, $SortOrder,
                     # $FolioHys, $ExclPropDims, $AllowPropDims, $PMemDiMesA, $UsablePropDims, $SlaveYear, $Zones, $ShortName, $Ref, $Descr, $DataA, $Comment, $Scratch
                     # { $TxId, $TupId, $ManDims}

    if ($slave = $Bits & BroB_Slave)
      $masterBroA = $IbrosA[$master];

    $errors = $Errors;
    if ($DataTypeN) {
      if ($summing = in_array($DataTypeN, $BroSumTypesGA) || $DataTypeN === DT_MoneyString)
        $Bits |= BroB_Summing;
      # If have a DataType must have a Usable list or PMems/DiMes property
      if (!$UsablePropDims && !isset($PMemDiMesA[II_AllowsA]))
        BIError2("A Bro which can hold data (one with a DataType) must have Usable$PropDimNmes (a $FolioHyNme or Allow$PropDimNmes) or an $PMemDiMeNmes property with an Allow entry");
    }else
      $summing=0;

    if (!$summing) {
      # P3 Non-summing
      if ($Bits & BroB_Set) {
        # A non-summing Set cannot have a DataType or Folio or Members or UsableProps
        if ($DataTypeN)
          BIError2('Only summing DataTypes (Money, Integer, Decimal, Share), or none, can be used for a Set Bro');
        if ($FolioHys)
          BIError2("A non-summing Set Bro cannot have a $FolioHyNme");
        if ($UsablePropDims)
          BIError2("A non-summing Set Bro cannot have Usable$PropDimNmes");
        if ($PMemDiMesA)
          BIError2("A non-summing Set Bro cannot have an $PMemDiMeNmes attribute");
      }

      if ($PeriodSEN >= BPT_InstSumEnd)
        BIError2('A non-summing Bro cannot have StartEnd defined');

      if ($SumUp && $DataTypeN !== DT_MoneyString)
        BIError2("SumUp value <i>$SumUp</i> used with non-summing Bro.");

      # P3 Exclude the PropDims not appropriate for a non-summing non-slave Bro with Usable list and no AllowPropDims if not already excluded
      if (!$slave && $UsablePropDims && !$AllowPropDims)
        foreach ($NonSummingBroExclPropDimsAR as $propDimId) { # Restated
          $propDimChr = IntToChr($propDimId);
          if (InStr($propDimChr, $UsablePropDims)) {
            if ($FolioHys)
              $broRA['ExclPropDims'] = $ExclPropDims   = IntAToChrList(ChrListToIntA($ExclPropDims.$propDimChr)); # IntAToChrList(ChrListToIntA()) to keep ExclPropDims sorted
            $broRA['UsablePropDims'] = $UsablePropDims = str_replace($propDimChr,'',$UsablePropDims);
            BINotice("$PropDimNme $propDimId added to Excl$PropDimNmes to remove it from the Usable$PropDimNmes as it is not applicable to a Non-Summing Bro");
          }
        }
    }

    if ($Errors > $errors)
      continue;

    if (!$BroSetTaxnId && $DataTypeN) {
      # P3 In-BroSet and a data holding Bro

      # Set BroB_DBO if the Bro is a DBO Posting Type
      # - UsableProps == just one of the DBO Props
      # - No UsableProps but PMems Allow list includes only DBO Prop PMems
      if (strlen($UsablePropDims) <= 1) {
        # Could be a DBO Posting Bro
        if ($UsablePropDims)
          # Have one UsableProp so see if it is one of the DBO ones
          $dboBro = ChrToInt($UsablePropDims[0]) < PropId_FirstNonDbo;
        else{
          # Have no UsableProps so check if PMems Allow list includes only DBO Prop PMems
          $dboBro = 1;
          if (!isset($PMemDiMesA[II_AllowsA]))
            die("No Allow Members in DBO check which should not be as it has no UsableProps but has a DataType");
          foreach ($PMemDiMesA[II_AllowsA] as $pMemId)
            if ($pMemId >= PropId_FirstNonDbo) {
              $dboBro = 0; # Not just DBO PropPMems
              break;
            }
        }
      }else
        $dboBro = 0;

      if ($dboBro) {
        # The Bro is a DBO Posting Type so its DataType should be boolean
        if ($DataTypeN !== DT_Boolean)
          BIError2('The Bro is a DBO Posting Bro so its DataType should be Boolean not '.DataTypeStr($DataTypeN));
        if ($DboFieldN)
          BIError2('The Bro is a DBO Posting Bro so it cannot have DboField defined.');
        $Bits |= BroB_DBO;
      }else{
        # Not a DBO Bro, but if its UsableProps contains a DBO Property, then:
        # - AllowPMems for the Ref PMem
        # - Add the Property to ExclProps if the Bro has a Folio and so can have ExclProps
        # - Remove the Property from UsableProps
        if (ChrToInt($UsablePropDims[0]) < PropId_FirstNonDbo) {
          # Have at least one DBO Property
          if ($PMemDiMesA) {
            if (!$allowPMemsA = $PMemDiMesA[II_AllowsA])
              $allowPMemsA = [];
          }else{
            $PMemDiMesA  = [0, 0, 0, 0];
            $allowPMemsA = [];
          }
          # Loop thru the PMembers to get the Ref PMemIds as the first one of a new Prop
          for ($propId=0,$pMemId=1; true; ++$pMemId)
            if ($PMemsA[$pMemId][PMemI_PropId] !== $propId) {
              # First pMem of a new Prop = the Ref
              if (++$propId === PropId_FirstNonDbo)
                break;
              if (InChrList($propId, $UsablePropDims)) {
                # The DBO Prop is in the UsableProps
                $propChr = IntToChr($propId);
                if ($FolioHys)
                  $broRA['ExclPropDims'] = $ExclPropDims   = IntAToChrList(ChrListToIntA($ExclPropDims.$propChr)); # IntAToChrList(ChrListToIntA()) to keep ExclPropDims sorted
                $broRA['UsablePropDims'] = $UsablePropDims = str_replace($propChr,'',$UsablePropDims);
                $allowPMemsA[] = $pMemId;
              }
            }
          $PMemDiMesA[II_AllowsA] = $allowPMemsA;
          $broRA['PMemDiMesA'] = $PMemDiMesA;
        }
      }

      # Set BroB_UseM
      # BroB_UseM  Set if Bro is not RO and the Bro's Usable Props includes one of the Props which include Members with Member Use 'M' codes
      if (!($Bits & BroB_RO))
        foreach ($MUsePropsGA as $propId) # array of Props which include Members with Member Use 'M' codes
          if (InChrList($propId, $UsablePropDims)) {
            $Bits |= BroB_UseM;
            break;
          }
    }
    $broRA['Bits'] = $Bits;


    # P3 SumUp for Summing Bros. Out of column sequence because of use of $SumUp with PostType
    # n n n I n
    if ($SumUp) {
      $v = $SumUp;
      if ($DadId) { # or $Level
        $dadDataTypeN = $IbrosA[$DadId]['DataTypeN'];
        $dataTypesOK  = $dadDataTypeN === $DataTypeN || ($dadDataTypeN === DT_Money && $DataTypeN === DT_MoneyString);
        switch ($SumUp = Match($SumUp, ['+', 'No', 'NA'])) { # BroSumUp_Yes(1): +, BroSumUp_No(2): No, BroSumUp_NA(3): NA
          case BroSumUp_Yes: # +
            # Not level 0 is checked in Pass 1 so should always have a Dad here
            if (!$dataTypesOK) { # + not valid
              # only No is valid due to DataTypes being incompatible
              BINotice(sprintf("SumUp value <i>+</i> supplied for a Bro with a DataType of %s which is not compatible with the DataType of %s of its parent Set, so the SumUp value should be 'No'. It has been set to that.", DataTypeStr($DataTypeN), DataTypeStr($dadDataTypeN)));
              $SumUp = BroSumUp_No;
            }
            break; # end of Yes +
          case BroSumUp_No: # No
            break;
          case BroSumUp_NA: # NA - valid if DataTypes are incompatible
            if ($dataTypesOK) {
              BIError2("SumUp value <i>$v</i> is invalid. + or No expected for this Bro which is a Summing Bro with a DataType that can be summed to its parent Set");
              continue 2;
            }
            break;
          default:
            BIError2("SumUp value <i>$v</i> is invalid. +, No, NA expected");
            continue 2;
        }
      }else{ # SumUp but no Dad
        BINotice("SumUp value <i>$v</i> supplied for a Bro with no parent, so the SumUp value has been ignored");
        $SumUp = 0;
      }
      $broRA['SumUp'] = $SumUp;
    }else # No SumUp
      if ($Level && $summing && $DadId && $IbrosA[$DadId]['DataTypeN'] === $DataTypeN)
        $broRA['SumUp'] = BroSumUp_Yes; # + = default for summing Bro of same DataType as dad Set

    # P3 PostType
    # b b b I p
    if ($postType) {
      if ($DataTypeN === DT_Money || $DataTypeN === DT_MoneyString) {
        $v = $postType;
        if ($postType = Match($postType, ['DE', 'Sch'])) { # 1: DE, 2: Sch
          if ($slave && $SlaveYear) {
            # A Money type Prior Year Slave must be Sch
            if ($postType !== 2) BINotice("A Money type Prior Year Slave must be Sch not $v. It has been set to Sch.");
            $postType = 2;
          }
          if ($SumUp === BroSumUp_Yes)
            # Check if DE/Sch type matches Dad's type for SumUp case
            if ($DadId && ($dadsPostType = $IbrosA[$DadId]['postType']) != $postType) {
              # Dad's postType not same
              $dads = $dadsPostType === 1 ? 'DE' : 'Sch';
              BIError2("Money Bro has SumUp = '+' but its PostType <i>$v</i> is different from the <i>$dads</i> PostType of its dad. They must be the same when SumUp is '+'");
            }
        }else
          BIError2("PostType <i>$postType</i> is invalid - DE or Sch is expected");
      }else
        BINotice("PostType <i>$postType</i> ignored as PostType is inapplicable to non-Money Bros");
    }else
      if ($slave) $postType = $masterBroA['postType'];
    $broRA['postType'] = $postType;
    if ($postType == 1)
      $broRA['Bits'] |= BroB_DE;

    # P3 Check
    # o o o o o
    # Only applicable to summing Bros
    # Stored as TPYtargetBroId where T is the Type code, P the Performed when code, Y the Year digit, and targetBroId is the Check Target BroId

    # Add Check for Summing Set Slave as Data Import/Summing does not copy M to S for a Set Slave. Instead the set summing happens as for any other set, and so a check should be made.
    # But the check should not be added if any of the Bros that sum to the Set Slave involve filtering and the Set Slave isn't filtered itself, as then the Set Sum is not expected to equal the Master.
    if ($slave && $summing && ($Bits & BroB_Set)) {
     #$check='Equal To, Either'.$masterBroA['Name'];
      $broRA['check'] = 0; # to zap any previous check
      $tarBroA = $IbrosA[$master];
      $setMemberFiltered = 0;
      for ($rowNum=$RowNum+1; $rowNum <= $LastRowNum; ++$rowNum) {
        if (!($broId = $RowMapA[$rowNum])) continue; # empty or comment row
        if ($broId < 0) break; # include or error reached
        $broA = $IbrosA[$broId];
        if ($broA['Level'] <= $Level) break; # past the end of the Set
        $bits = $broA['Bits'];
        if (!($bits & BroB_Set) && ($bits & BroB_SFilter)) {
          # Non-Set Member of Set is being filtered
          $setMemberFiltered = BroB_SFilter;
          break;
        }
      }
     #if (($Bits & BroB_SFilter) === $setMemberFiltered) {
        # Either Set Slave filtered and set member(s) filtered, or neither filtered, so add Check
      if (!($Bits & BroB_SFilter) && !$setMemberFiltered) {
        # Set Slave not filtered and no set member filtered, so add Check
        $broRA['check'] = "EE$SlaveYear$master";
      }else{
        if ($setMemberFiltered)
          BINotice('Set Slave check versus Master not added as at least one Set member involves Slave Filtering and so the Set Sum is not expected to always equal the Master');
        else{
         #BIError2('This Set Slave involves filtering but no set member does so the filtering should be removed. (A Set Slave can include filtering only if one or more set members involve filtering. In most cases, however, it is likely to be both best and easiest to not filter the Set Slave, and just have it summing its members.)');
         #$continue;
          BINotice('Set Slave check versus Master not added as this Set Slave involves filtering but no set member does.');
        }
      }
    }else if ($check) {
      if (!$summing) {
        BIError2("Check value of <i>$check</i> specified for a non-summing Bro");
        continue;
      }
      # Check provides a means of specifying auto summing checks for Summing Bros by defining whether a Bro’s base should be equal to, or equal and opposite to, the base of a ‘Check’ Bro,
      # and also for specifying when the test should be Performed
      # <Equal To | Equal & Opp To>{ <Either | Both | Check | Target>} BroName
      if (!strncasecmp($check, 'Equal To', 8)) {
        $T = 'E';
        $check = ltrim(substr($check, 8+($check[8]===':'))); # ($check[8]===':') for 'Equal To:' form prior to 07.09.12
      }else if (!strncasecmp($check, 'Equal & Opp To', 14)) {
        $T = 'O';
        $check = ltrim(substr($check, 14+($check[14]===':'))); # ($check[14]===':') for 'Equal & Opp To:' form prior to 07.09.12
      }else{
        BIError2("Check does not start with one of 'Equal To' or 'Equal & Opp To'");
        continue;
      }
      # Now { <Either | Both | Check | Target>}
      if (!strlen($check)) {
        BIError2('Check not in form &lt;Equal To | Equal & Opp To>{ &lt;Either | Both | Check | Target>}{ Year#} <BroName | BroShortName>');
        continue;
      }
      if (!strlen($check = trim($check))) {
        BIError2('Check not in form &lt;Equal To | Equal & Opp To>{ &lt;Either | Both | Check | Target>}{ Year#} <BroName | BroShortName>');
        continue;
      }
      if (!($P = Matchn($check, $checkPerformsA))) {
        BIError2('Check not in form &lt;Equal To | Equal & Opp To>{ &lt;Either | Both | Check | Target>}{ Year#} <BroName | BroShortName>');
        continue;
      }
      $P = $checkPerformsA[$P-1]; # now Either | Both | Check | Target
      if (!strlen($check = ltrim(substr($check, strlen($P))))) {
        BIError2('Check not in form &lt;Equal To | Equal & Opp To>{ &lt;Either | Both | Check | Target>}{ Year#} <BroName | BroShortName>');
        continue;
      }
      $P = $P[0]; #  Now E | B | C | T
      # { Year#}
      if (!strncasecmp($check, 'Year', 4)) {
        if (strlen($check)<5 || !ctype_digit($check[4])) {
          BIError2('Check not in form &lt;Equal To | Equal & Opp To>{ &lt;Either | Both | Check | Target>}{ Year#} <BroName | BroShortName>');
          continue;
        }
        if (!InRange($Y = (int)$check[4], 1, 3)) {
          BIError2("Year# in Check col <i>$check</i> is not in the expected range of 1 to 3");
          continue;
        }
        $check = ltrim(substr($check, 5));
      }else
        $Y=0;
      # $check should now hold the Target BroRef
      if (isset($NamesA[$check]))
        $targetBroId = $NamesA[$check];
      else if (isset($ShortNamesA[$check]))
        $targetBroId = $ShortNamesA[$check];
      else{
        BIError2("Check Target Bro <i>$check</i> not found. Correct the Target BroName | BroShortName, or if the error is because the Target Bro is in another BroSet, add an Include BroSet row for that BroSet and run the import again.");
        continue;
      }
      if ($targetBroId === $BroId) {
        BIWarning("Check <i>$check</i> is a reference to self (this Bro) which serves no purpose.");
        continue;
      }
      if ($master === $targetBroId) {
        BIWarning("Check <i>$check</i> is a reference to the Master of this Bro so has been removed.");
        $broRA['check'] = 0;
      }else{
        $tarBroA = $IbrosA[$targetBroId];
        if ($tarBroA['DataTypeN'] === $DataTypeN)
          $broRA['check'] = "$T$P$Y$targetBroId";
        else{
          BIError2(sprintf("Check Target Bro %s <i>$check</i> has DataType %s which is different from this Bro's DataType of %s.", BroLocation($targetBroId), DataTypeStr($tarBroA['DataTypeN']), DataTypeStr($DataTypeN)));
          continue;
        }
      }
    }

    # P3 I- Related
    # - o - -
    # Only applicable to Element Bros
    # Source = <M | O | U> RelatedBro <Name | ShortName>
    # Check for Ele, <M | O | U> SP and len > 2 done in Pass 1
    if ($Related) {
      $relatedNme = trim(substr($Related, 2));
      if (isset($NamesA[$relatedNme]))
        $relatedBroId = $NamesA[$relatedNme];
      else if (isset($ShortNamesA[$relatedNme]))
        $relatedBroId = $ShortNamesA[$relatedNme];
      else{
        BIError2("Related Bro <i>$relatedNme</i> not found. Correct the Related BroName | BroShortName, or if the error is because the Related Bro is in another BroSet, add an Include BroSet row for that BroSet and run the import again.");
        continue;
      }
      if ($relatedBroId === $BroId) {
        BIError2("Releated <i>$Related</i> is a reference to self (this Bro) which serves no purpose.");
        continue;
      }
      $broRA['Related'] = $Related[0].$relatedBroId;
    }

    # P3 I- StartEnd SumList
    # - x - -
    # Only applicable to StartEnd Element Bros
    if ($SeSumList) {
      # Check and convert list from Names or ShortNames to BroIds
      $seSumListBroIdsA = [];
      foreach (explode(COM, $SeSumList) as $seListMemNme) { # SE list member, Name or ShortName
        if (isset($NamesA[$seListMemNme]))
          $seListMemBroId = $NamesA[$seListMemNme];
        else if (isset($ShortNamesA[$seListMemNme]))
          $seListMemBroId = $ShortNamesA[$seListMemNme];
        else{
          BIError2("StartEnd SumList member <i>$seListMemNme</i> in SumList $SeSumList not found. Correct the Name | ShortName for the list member, or, if the error is because the $seListMemNme Bro is in another BroSet, add an Include BroSet row for that BroSet and run the import again");
          continue;
        }
        # Check that the list member is not self
        if ($seListMemBroId === $BroId) {
          BIError2("StartEnd SumList member <i>$seListMemNme</i> in SumList $SeSumList is this Bro but a Bro's List should not include itself");
          continue;
        }
        $seListMemBroA = $IbrosA[$seListMemBroId];
        # Check that the list member has Duration Period
        if ($seListMemBroA['PeriodSEN'] !== BPT_Duration) {
          BIError2(sprintf("StartEnd SumList member <i>$seListMemNme</i> in SumList $SeSumList is %s Period, but all SumList members should be Duration Period", PeriodSENStr($seListMemBroA['PeriodSEN'])));
          continue;
        }
        # Check that the DataTypes are the same
        if ($seListMemBroA['DataTypeN'] !== $DataTypeN) {
          BIError2(sprintf("StartEnd SumList member <i>$seListMemNme</i> in SumList $SeSumList has %s DataType which does not match the % DataType of this StartEnd Bro", DataTypeStr($seListMemBroA['DataTypeN']), DataTypeStr($DataTypeN)));
          continue;
        }
        # Check UsableProps of the list member vs the SE Bro re SumEnd summing and PostEnd calcs.
        # Remove SE Bro's UsableProps from list member UsableProps. Should go to nothing if all of the list member UsableProps are in the SE Bro UsableProps
        if ($t = str_replace(str_split($UsablePropDims),'', $seListMemBroA['UsablePropDims'])) {
          $n=strlen($t);
          BIError2(sprintf("%s %s of the %s UsableProps of StartEnd SumList member <i>$seListMemNme</i> in SumList $SeSumList %s of the %s UsableProps of this StartEnd Bro", PluralWord($n, 'Prop'), ChrListToCSDecList($t), ChrListToCSDecList($seListMemBroA['UsablePropDims']), $n>1?'are not members':'is not a member', ChrListToCSDecList($UsablePropDims)));
          continue;
        }
        # Summing path check. As the StartEnd Bro is an Ele Bro there can be no summing path up to the SE Bro from the list member,
        #  but there could be a summing path up from the SE Bro to the list member if the SE Bro is SumUp and the the list member is a Set.
        if ($SumUp === BroSumUp_Yes && ($seListMemBroA['Bits'] & BroB_Set))
          for ($dId=$DadId; $dId; $dId = $IbrosA[$dId]['DadId']) {
            if ($dId === $seListMemBroId)
              BIError2("The StartEnd Bro sums up to SumList member <i>$seListMemNme</i> in SumList $SeSumList which is not allowable as it would cause an infinite summing loop");
            else if ($IbrosA[$dId]['SumUp'] === BroSumUp_Yes)
              continue;
            break;
          }

        $seSumListBroIdsA[] = $seListMemBroId;
      }
      # sort($seSumListBroIdsA); # No. Not sorted re PostEnd summing logic where the first in the list has meaning
      $broRA['SeSumList'] = implode(COM, $seSumListBroIdsA);
    }

    # Checks Pass 3
    # -------------

    # P3 Check that a Slave does not SumUp to its Master0
    if ($slave)
      for ($dId=$DadId; $dId; ) {
        if ($IbrosA[$dId]['SumUp'] === BroSumUp_Yes) {
          $dId=$IbrosA[$dId]['DadId'];
          if ($dId !== $master) continue;
          BIError2("Slave Bro sums up to its Master which would cause an infinite loop in summing. Either change the Master/Slave construction or break the SumUp chain.");
        }
        break;
      }

    if ($BroSetTaxnId) {
      # Out-BroSet

      if ($TxId) {
        # P3 Out-BroSet Tx warnings for TypeN and SignN differences
        $xA = $TxElesA[$TxId];
        # DataType
        $txTypeN = MapTxTypeToDataType($xA['TypeN']);
        if ($DataTypeN != $txTypeN)
          BIWarning("Bro <i>$Name</i> DataType of " . DataTypeStr($DataTypeN) . " is different from the DataType of ". DataTypeStr($txTypeN) . " derived from the Tx Type of " . ElementTypeToStr($xA['TypeN']));
        # Sign
        if ($DataTypeN === DT_Money) {
          if (!$SignN)
            $SignN = $xA['SignN'];
          if (!$SignN && $DadId && $dadA['DataTypeN'] == DT_Money)
            $SignN = $dadA['SignN'];
          if ($SignN != $xA['SignN'] && $xA['SignN'])
            BIWarning("Bro <i>$Name</i> the Sign of " . SignToStr($SignN) . " is different from the TxId $TxId Sign of " . SignToStr($xA['SignN']));
        }
      }
    }else{
      # In-BroSet

      # P3 Summing Set checks for In-BroSets
      if ($summing && ($Bits & BroB_Set)) {
        # In Summing Set
        # P3 Check that an In non-slave summing Set is RO if any children are Ele Slaves. Added 12.02.13
        if (!($Bits & BroB_RO)) # will be RO if a Slave
          for ($rowNum=$RowNum+1; $rowNum <= $LastRowNum; ++$rowNum) {
            if (!($broId = $RowMapA[$rowNum])) continue; # empty or comment row
            if ($broId < 0) break; # include or error reached
            $broA = $IbrosA[$broId];
            if ($broA['Level'] <= $Level) break; # past the end of the Set
            $bits = $broA['Bits'];
            if (($bits & BroB_Slave) && ($bits & BroB_Ele)) {
              # Ele Slave so set the Set Bro to RO
              BINotice("RO has been set for this Set as it contains an Ele Slave at Row $rowNum");
              $broRA['Bits'] |= BroB_RO;
              break;
            }
          }

      }
    }
  }
  if ($Errors) Errors('in Pass 3'); # Does not return
  unset($broRA, $PMemsA, $checkPerformsA, $NonSummingBroExclPropDimsAR, $NonSummingBroExcludePropChrsA, $NonSummingBroExclDimsChrsGA, $TxIdHyIdTupIdBroIdsA, $NamesA, $ShortNamesA, $TxElesA);
} # End of Pass 3

##########
# Pass 4 # with all cols processed check In-BroSet Slave PostType, and that Relateds loop back to self
##########
{
  if (!$BroSetTaxnId) {
    foreach ($IbrosA as $BroId => $broA) {
      if ($broA['broSetId'] !== $BroSetId) continue; # skip included Bros
      $RowNum = $broA['RowNum'];
      if ($broA['Bits'] & BroB_Slave) {
        # Slave
        if ($broA['postType']==1 && $IbrosA[$broA['master']]['postType'] != 1)
          BIError2(sprintf('Slave is PostType DE whereas its Master %s is PostType Sch, which is not a legal combination.', BroLocation($broA['master'])));
      }
      if ($broA['Related']) {
        $relBroIdsA = [$relBroId = (int)substr($broA['Related'], 1)];
        while (1) {
          if (!($related = $IbrosA[$relBroId]['Related'])) {
            BIError2(sprintf('Related does not loop back to iself. The BroId chain is %s.', implode(COM, $relBroIdsA)));
            break;
          }
          if (($relBroId = (int)substr($related, 1)) === $BroId)
            break; # Has looped back to self
          if (array_search($relBroId, $relBroIdsA) !== false) {
            BIError2(sprintf('The Related chain repeats a Bro. The BroId chain is %s,%d.', implode(COM, $relBroIdsA), $relBroId));
            break;
          }
          $relBroIdsA[] = $relBroId;
        }
      }
    }
    if ($Errors) Errors('in Pass 4'); # Does not return
  }
}
# No errors so do DB inserts

##########
# Pass 5 # Delete previous Bros if any, do inserts to for new Bros tables, issue notices and warnings
##########
{ # $RowMapA, $IbrosA, $PropsWithMtypePMemsA;

  $DB->autocommit(false);
  # Delete previous if any
  $DB->StQuery("Delete from BroInfo Where BroSetId=$BroSetId");
  # Reset AutoIncrement
  $DB->StQuery(sprintf('Alter Table BroInfo Auto_Increment=%d', $DB->OneQuery('Select Id from BroInfo Order by Id Desc Limit 1') + 1));

  # Insert
  $numStds = $numSlaves = $numMasters = $numIncludes = $currentIncludeBroSetId = 0;
  foreach ($RowMapA as $RowNum => $BroId) { # Does not include Included Bros
    if (!$BroId) continue; # comment or blank row
    if ($BroId < 0) {
      # Included BroSet
      ++$numIncludes;
      $colsAA = [
        'BroSetId'     => $BroSetId,
        'InclBroSetId' => -$BroId];
      # Add comment if there was one in the original Include row
      $row = $RowsA[$RowNum-1];
      if ($p = strpos($row, '#'))
        $colsAA['Comment'] = trim(substr($row, $p));
      if (isset($RowCommentsA[$RowNum])) $colsAA['RowComments'] = $RowCommentsA[$RowNum];
      InsertBro($colsAA);
      continue;
    }
    # Bro for this BroSet
    $broA = $IbrosA[$BroId];
    extract($broA); # $broSetId, $RowNum, $postType, $master, $check, $Name, $Level, $DadId, $Bits, $DataTypeN, $DboFieldN, $SignN, $SumUp, $Related, $SeSumList, $PeriodSEN, $SortOrder,
                    # $FolioHys, $ExclPropDims, $AllowPropDims, $PMemDiMesA, $UsablePropDims, $SlaveYear, $Zones, $ShortName, $Ref, $Descr, $DataA, $Comment, $Scratch
                    # { $TxId, $TupId, $ManDims}
    if ($DadId)
      $dadA=$IbrosA[$DadId];
    # Jsonise $PMemDiMesA if it is defined
    if ($PMemDiMesA) $PMemDiMesA = json_encode($PMemDiMesA, JSON_NUMERIC_CHECK);
    # Compact or Jsonise Data
    $Data = 0;
    foreach ($DataA as $t)
      if ($t) {
        $Data = json_encode($DataA, JSON_NUMERIC_CHECK);
        break;
      }

    # 2 Different inserts: Std Bro, Slave
    if (!($Bits & BroB_Slave)) {
      ################
      # Standard Bro # i.e. not a Slave
      ################
      $colsAA = [
        'BroSetId'  => $BroSetId,
        'BroId'     => $BroId,
        'Name'      => $Name,
        'Level'     => $Level,
        'Bits'      => $Bits,
        'DataTypeN' => $DataTypeN];
      if ($Bits & BroB_Master) ++$numMasters;
      ++$numStds;
      # end of Std Bro
    }else if ($Bits & BroB_Slave) {
      #########
      # Slave #
      #########
      $masterBroA = $IbrosA[$master];
      $colsAA = [
        'BroSetId'  => $BroSetId,
        'BroId'     => $BroId,
        'Name'      => $Name,
        'Level'     => $Level,
        'Bits'      => $Bits,
        'DataTypeN' => $DataTypeN, # Set from Master in Pass 2
        'MasterId'  => $master];   # Id
      $Ref   = $Ref   ? : $masterBroA['Ref'];   # /-  Inherited from Master if not set for the Slave
      $Zones = $Zones ? : $masterBroA['Zones']; # |
      $Descr = $Descr ? : $masterBroA['Descr']; # |
      if ($SlaveYear) $colsAA['SlaveYear'] = $SlaveYear;
      ++$numSlaves;
    }else
      die("Die - Type if else failed on update for row $RowNum - not Std Bro or Slave");

    if ($DadId)          $colsAA['DadId']          = $DadId;
    if ($DboFieldN)      $colsAA['DboFieldN']      = $DboFieldN;
    if ($SignN)          $colsAA['SignN']          = $SignN;
    if ($SumUp)          $colsAA['SumUp']          = $SumUp;
    if ($PeriodSEN > BPT_Duration) $colsAA['PeriodSEN'] = $PeriodSEN; # Leaving null as default for BPT_Duration
    if ($SortOrder)      $colsAA['SortOrder']      = $SortOrder;
    if ($FolioHys)       $colsAA['FolioHys']       = $FolioHys;      # Can have Hys wo TxId
    if ($ExclPropDims)   $colsAA['ExclPropDims']   = $ExclPropDims;
    if ($AllowPropDims)  $colsAA['AllowPropDims']  = $AllowPropDims;
    if ($PMemDiMesA)     $colsAA['PMemDiMesA']     = $PMemDiMesA;
    if ($UsablePropDims) $colsAA['UsablePropDims'] = $UsablePropDims;
    if ($BroSetTaxnId) {
      if ($TxId)         $colsAA['TxId']           = $TxId;
      if ($TupId)        $colsAA['TupId']          = $TupId;
      if ($ManDims)      $colsAA['ManDims']        = $ManDims;
    }else{
      if ($Related)      $colsAA['Related']        = $Related;
      if ($SeSumList)    $colsAA['SeSumList']      = $SeSumList;
    }
    if ($check)          $colsAA['CheckTest']      = $check; # TPYtargetBroId
    if ($Zones)          $colsAA['Zones']          = $Zones;
    if ($ShortName)      $colsAA['ShortName']      = $ShortName;
    if ($Ref)            $colsAA['Ref']            = $Ref;
    if ($Descr)          $colsAA['Descr']          = $Descr;
    if ($Data)           $colsAA['Data']           = $Data;
    if ($Comment)        $colsAA['Comment']        = $Comment;
    if ($Scratch)        $colsAA['Scratch']        = $Scratch;
    if (isset($RowCommentsA[$RowNum])) $colsAA['RowComments'] = $RowCommentsA[$RowNum];
    InsertBro($colsAA);
  }
  UpdateBroSetStatus($finalStatus);
  $DB->commit(); # Commit
  $pass5Msg = sprintf("<p>%s Bros, %s standard (incl %s Masters), and %s Slaves added. $numIncludes BroSets included.</p>", number_format($numStds+$numSlaves), number_format($numStds), number_format($numMasters), number_format($numSlaves));
} # End of Pass 5

if ($notices=count($NoticesA)) {
  echo sprintf("<p class='b L mt05 mb0'>$notices %s:</p>\n<table><tr class=b><td>Row</td><td>Start of Row</td><td>Notice</td></tr>\n", PluralWord($notices, 'Notice'));
  sort($NoticesA);
  foreach ($NoticesA as $tA)
    MsgRow($tA);
  echo '</table>';
}
if ($warnings = count($WarningsA)) {
  $warningWord = PluralWord($warnings, 'Warning');
  $msg = "Data read and passed critical validity checks, though see the $warnings $warningWord above which should be investigated.";
  echo "<p class='b L mt05 mb0'>$warnings $warningWord:</p>\n<table><tr class=b><td>Row</td><td>Start of Row</td><td>Warning</td></tr>\n";
  sort($WarningsA);
  foreach ($WarningsA as $tA)
    MsgRow($tA);
  echo '</table>';
}else
  $msg = "Data read and passed critical validity checks";

# Finished with:
unset($ErrorsA, $NoticesA, $WarningsA, $PropsWithMtypePMemsA, $DimsWithMtypeDiMesA, $RowsA, $RowMapA, $TuplesByTxIdA, $IbrosA);

echo "<p class='b L mt05 mb0'>DB Update Done</p>
<p>$msg</p>$pass5Msg
";

# Build the Structs in all cases as used for BroSetExport and Bro Lookup
#if ($BroSetTypeN === BroSet_In_Main || $BroSetTypeN === BroSet_Out_Main)
echo "<p class='b L'>Building the Bro Structs</p>\n";
require './inc/BuildBroStructs.inc'; # Uses $DB, $BroSumTypesGA, $BroSetId, $BroSetTaxnId, Com_Str_Tx, and $NamespacesRgA which it loads

echo 'Memory usage: ', number_format(memory_get_usage()/1024000,1) , ' Mb<br>',
     'Peak memory usage: ', number_format(memory_get_peak_usage()/1024000,1) , ' Mb<br>';

#Footer(true, false, true); # time, no Top btn, not centred
Form(true);
##########


function MatchOne($val, $one) {
  return !strcasecmp($val, $one);
}

# TupId()
# -------
# Returns TupId for $txId and $hyId, 0 if none needed, 0 if TupId can't be determined from $txId and $hys, with error output
function TupId($txId, $hyId) {
  global $TuplesByTxIdA, $TupIdsByMemberTxIdAndHyIdGA; # $TupIdsByMemberTxIdAndHyIdGA Array of Tuples for Members by TxId which are Members of Multiple Tuples whose use can be narrowed down by Hypercube, defined in ConstantsTx.inc
  if (!isset($TuplesByTxIdA[$txId]))     return 0; # not a tuple member so no TupId is required
  if (count($TuplesByTxIdA[$txId]) === 1) return $TuplesByTxIdA[$txId][0]; # just one tuple so return its TupId.
 #if (!isset($TuplesByTxIdA[$txId]) || count($TuplesByTxIdA[$txId]) == 1) return 0; # Not a tuple member or unique so no TupId is needed.
  # txId is a member of multiple tuples
  # OK if HyId allows unique identification
  if (!$hyId)
    die("Die - TupId() called for TxId $txId with no HyId");
  if (isset($TupIdsByMemberTxIdAndHyIdGA[$txId][$hyId])) # [TupleMemberTxId (TuplePairs.MemTxId) => [HyId => TupId]]
    return $TupIdsByMemberTxIdAndHyIdGA[$txId][$hyId]; # TupId could be derived from TxId and HyId
  BIError2("This Bro's TxId $txId is a member of Tuples ".implode(',', $TuplesByTxIdA[$txId])." but its HyId $hyId is not sufficient to identify which Tuple is appropriate. Add the required TupId to the TupId column.");
  return 0;
}

function MapTxTypeToDataType($txType) {
  # Map Taxonomy Element Type to Rg DataType
  switch ($txType) {
    case TET_Money:          $t = DT_Money;   break;
    case TET_String:         $t = DT_String;  break;
    case TET_Boolean:        $t = DT_Boolean; break;
    case TET_Fixed:          $t = DT_Boolean; break;
    case TET_Date:           $t = DT_Date;    break;
    case TET_Decimal:        $t = DT_Decimal; break;
    case TET_Integer:        $t = DT_Integer; break;
    case TET_NonZeroDecimal: $t = DT_Decimal; break;
    case TET_Share:          $t = DT_Share;   break;
    case TET_Percent:        $t = DT_Percent; break;
    case TET_PerShare:       $t = DT_PerShare;break;
  /*case TET_Uri:
    case TET_Domain:        10); // uk-types:domainItemType             1226
    case TET_EntityAccounts:11); // uk-types:entityAccountsTypeItemType    1
    case TET_EntityForm:    12); // uk-types:entityFormItemType            1
    case TET_ReportPeriod:  16); // uk-types:reportPeriodItemType          1
    case TET_Any:           17); // anyType                     2
    case TET_QName:         18); // QName                       2
    case TET_Arc:           19); // xl:arcType                  5
    case TET_Doc:           20); // xl:documentationType        2
    case TET_Extended:      21); // xl:extendedType             1
    case TET_Locator:       22); // xl:locatorType              2
    case TET_Resource:      23); // xl:resourceType             1
    case TET_Simple:        24); // xl:simpleType               2            anySimpleType   1
    case TET_Title:         25); // xl:titleType                1 */
    default:
      $t = DT_String;
  }
  return $t;
}

function UsableDims($hys, $nHys) {
  global $HyDimsA;
  if ($nHys === 1) # One hypercube
    return $HyDimsA[ChrToInt($hys)];
  # More than one hypercube so merge the dims
  $usableDims = '';
  for ($h=0; $h<$nHys; $h++)
    $usableDims .= $HyDimsA[ChrToInt($hys[$h])];
  return IntAToChrList(ChrListToIntA($usableDims)); # IntAToChrList() sorts the dims and eliminates duplicates
}

# IsDuplicateDiMe($thisExclDims, $thisDiMesA, $otherExclDims, $otherDiMesA)
# Function to be called for a pair of Tx Bros with Duplicate TxId.HyId.TupId to see if they could still be unique based on ExclPropDims or Mandatory DiMes.
# Returns 1 if duplicate meaning same BroRef could be posted, 0 if unique meaning same BroRef could not be posted.
# Depends on an ExclDim being Mandatory or a Mandatory DiMe.
function DupBrosManExclDimsOrManDiMes($thisExclDims, $thisDiMesA, $otherExclDims, $otherDiMesA) {
  return DupBrosManExclDimsOrManDiMes2($thisExclDims, $thisDiMesA, $otherExclDims, $otherDiMesA) && DupBrosManExclDimsOrManDiMes2($otherExclDims, $otherDiMesA, $thisExclDims, $thisDiMesA);
}
# *2() is the work fn which can give a different result when called for (this, other) vs (other, this) so it needs to be called the other way around if the first call returns 0.
# djh?? Review this re $DimsWithMtypeDiMesA use vs Prop equiv
# djh?? Bring ManDims into it for multiple Hys case?
function DupBrosManExclDimsOrManDiMes2($thisExclDims, $thisDiMesA, $otherExclDims, $otherDiMesA) {
  global $DiMesA, $DimsWithMtypeDiMesA;
  if ($otherExclDims != $thisExclDims)
    # ExclPropDims are different. If an extra Excl Dim in one is a mandatory (M#) dim then this is not a duplicate pair
    if ($extra = str_replace(str_split($otherExclDims), '', $thisExclDims))
      # $thisExclDims has $extra not in other Bro's Excl. If one of these is an (M#) dim then this is not a duplicate pair
      if ($len = strlen($extra))
        for ($i=0; $i<$len; ++$i) {
          $propDimId = ChrToInt($extra[$i]);
          if ($propDimId != DimId_Officers && in_array($propDimId, $DimsWithMtypeDiMesA)) # Skip Officers because of its special case - leave to DiMes for Officers
            return 0;
        }

  if ($thisDiMesA && $thisDiMesA[II_MandatsA ]) {
    # Not resolved on basis of ExclPropDims so try Mandat DiMes which this Bro has
    $idMandatsA = $thisDiMesA[II_MandatsA ];
    # This Bro has Mandatory DiMes so pair are not duplicates if other Bro has these DiMe excluded either as a result of having different Mandatory DiMes defined, or by having the the Mandataory DiMes excluded
    if ($otherDiMesA) {
      # Other Bro also has DiMes
      if ($otherDiMesA[II_MandatsA ]) {
        # Other Bro also has Mandat DiMes
        $all = 1; # assume all different
        foreach ($idMandatsA as $diMeId)
          if (in_array($diMeId, $otherDiMesA[II_MandatsA ]))
            $all = 0; # not all different
        if ($all)
          return 0; # not duplicates as all Mandats different
      }
      if ($otherDiMesA[II_ExcludesA]) {
        # Not resolved on basis of Mandats, try Excludes, which the other Bro has
        $all = 1; # assume all excluded
        foreach ($idMandatsA as $diMeId)
          if (!in_array($diMeId, $otherDiMesA[II_ExcludesA]))
            $all = 0; # not all excluded
        if ($all)
          return 0; # not duplicates as all Mandats excluded
      }
    } # end of other Bro has DiMes
    if ($otherExclDims) {
      # Not resolved on basis of DiMes, try ExclPropDims, which the other Bro has
      $all = 1; # assume all excluded
      foreach ($idMandatsA as $diMeId)
        if (!InChrList($DiMesA[$diMeId][PMemI_PropId], $otherExclDims))
          $all = 0; # not all dims excluded
      if ($all)
        return 0; # not duplicates as all Mandats excluded via ExclPropDims
    }
  }
  return 1; # No unique condition found so duplicates
}

function InsertBro($colsAA) {
  global $DB;
  $set = '';
  foreach ($colsAA as $col => $val)
    $set .= ",$col=" . (is_numeric($val) ? $val : SQ.addslashes($val).SQ);
  # Do the insert which is not expected to fail. (No mysql level unique key fields should come thru here unless the app has guarded against possible duplicate key clashes.)
  $set = substr($set, 1);
  $DB->StQuery("Insert BroInfo Set $set");
}

# BroLocation($broId)
# -------------------
# Returns:
#  'at row rrrr' for a Bro in this import
#  'BroId bbbbb In-BroSet nnnnnn' for a Bro in an included BroSet
function BroLocation($broId) {
  global $BroSetNamesA, $BroSetId, $IbrosA;
  if (isset($IbrosA[$broId])) {
    $broA = $IbrosA[$broId];
    if ($broA['broSetId'] === $BroSetId)
      # This import
      return 'at row ' . $broA['RowNum'];
    # else different included BroSet
    return "BroId $broId In-BroSet " . $BroSetNamesA[$broA['broSetId']];
  }
  return 'unknown';
}

function BIError($msg) {
  global $RowMapA, $ErrorsA, $Errors, $RowNum;
  ++$Errors;
  if ($Errors<101)
    $ErrorsA[] = [$RowNum, $msg];
  $RowMapA[$RowNum] = -999999; # set row to error indicator
}

# Error wo setting row to -1
function BIError2($msg) {
  global $ErrorsA, $Errors, $RowNum;
  ++$Errors;
  if ($Errors<101)
    $ErrorsA[] = [$RowNum, $msg];
}

# Notices are created before error checks are complete so store the notices to be issued only if there are no errors
function BINotice($msg) {
  global $NoticesA, $RowNum;
  $NoticesA[] = [$RowNum, $msg];
}

function BIWarning($msg) {
  global $WarningsA, $RowNum;
  $WarningsA[] = [$RowNum, $msg];
}

function ErrorExit($err) {
  global $RowsA, $RowNum, $HeadingsRow; # Assuming $HeadingsRow is 1 if headings are present. It is 0 if not.
  echo sprintf("<p><span class='b L mt05 mb0'>Error:</span> in Row %d: %s<br>$err.</p>", $RowNum + 1 - $HeadingsRow, $RowsA[$RowNum-1]);
  Form(false, '<br>No DB changes have been made. Correct the error and try again.<br>');
  ####
}

function Errors($passTxt) {
  global $Errors, $ErrorsA, $RowsA, $HeadingsRow;
  if ($Errors==1)
    $t='1 Error';
  else if ($Errors<101)
    $t="$Errors Errors";
  else
    $t="$Errors Errors with listing truncated after 100";
  echo "<p class='b L mt05 mb0'>$t $passTxt:</p>\n<table><tr class=b><td>Row</td><td>Start of Row</td><td>Error</td></tr>\n";
  # echo "HeadingsRow $HeadingsRow".BR;
  sort($ErrorsA);
  foreach ($ErrorsA as $tA)
    MsgRow($tA);
  echo '</table>';
  Form(false, "<br>$Errors error(s) were found. No DB changes have been made. Correct the errors and try again.<br>");
  ####
}

function MsgRow($tA) { # tA = [0 => RowNum, 1 => Msg]
  global $RowsA, $HeadingsRow; # Assuming $HeadingsRow is 1 if headings are present. It is 0 if not.
  $rowNum = $tA[0];
  $row = $RowsA[$rowNum-1];
  while (InStr('		', $row))
    $row = str_replace('		', TAB, $row);
  $start = str_replace(TAB, '&nbsp;', substr(trim($row), 0, 30));
  echo sprintf("<tr><td>%d</td><td>%s</td><td>%s</td></tr>\n", $rowNum + 1 - $HeadingsRow, $start, $tA[1]);
}

function UpdateBroSetStatus($status=0) {
  global $DB, $BroSetId, $BsDataA;
  if (isset($BroSetId) && $BroSetId) {
    $set = "Status=$status,EditT=$DB->TnS";
    if ($status === Status_OK)
        $set .= ",Data='".json_encode($BsDataA, JSON_NUMERIC_CHECK).SQ;
    $DB->StQuery(sprintf("Update %s.BroSets Set $set Where Id=$BroSetId", DB_Braiins));
  }
}

function IncludeBros($broSetId) {
  global $DB, $RowNum, $BroSetNamesA, $IbrosA, $NamesA, $ShortNamesA;
  static $IncludedBroSetIsA = [];
  $broSetName = $BroSetNamesA[$broSetId];
  if (in_array($broSetId, $IncludedBroSetIsA, true)) return; # Has already been included. Can happen if included by Main and Incl BroSets
  $IncludedBroSetIsA[] = $broSetId;
  echo "Including BroSet $broSetName<br>";
  $res = $DB->ResQuery("Select * From BroInfo Where BroSetId=$broSetId Order By Id");
  while ($o = $res->fetch_object()) {
    if ($o->InclBroSetId) {
      IncludeBros($o->InclBroSetId);
      continue;
    }
    $broA  = BroInfo($o);
    extract($broA); # $broSetId, $RowNum, $postType, $master, $check, $Name, $Level, $DadId, $Bits, $DataTypeN, $DboFieldN, $SignN, $SumUp, $Related, $SeSumList, $PeriodSEN, $SortOrder,
                    # $FolioHys, $ExclPropDims, $AllowPropDims, $PMemDiMesA, $UsablePropDims, $SlaveYear, $Zones, $ShortName, $Ref, $Descr, $Comment, $Scratch
                    # { $TxId, $TupId, $ManDims}
    $broId = $broA['BroId'];
    if (isset($IbrosA[$broId]))
      BIError2(sprintf("The Include BroSet statement on Row $RowNum has resulted in a BroId clash as BroId $broId is defined in both BroSet '%s' and BroSet '%s'. Correct the BroId range in use in one of these BroSets and try again.", $BroSetNamesA[$IbrosA[$broId]['broSetId']], $broSetName));
    if (isset($NamesA[$Name]))
      BIError2(sprintf("The Include BroSet statement on Row $RowNum has resulted in a BroName clash as $Name is defined in both BroSet '%s' and BroSet '%s'. Correct the BroName in use in one of these BroSets and try again.", $BroSetNamesA[$IbrosA[$NamesA[$Name]]['broSetId']], $broSetName));
    if (isset($ShortNamesA[$Name]))
      BIError2(sprintf("The Include BroSet statement on Row $RowNum has resulted in a BroName/ShortName clash as BroName $Name in '%s' is in use In-BroSet '%s' as a ShortName. Correct one of these and try again.", $broSetName, $BroSetNamesA[$IbrosA[$ShortNamesA[$Name]]['broSetId']]));
    $NamesA[$Name] = $broId;
    if ($ShortName) {
      if (isset($ShortNamesA[$ShortName]))
        BIError2(sprintf("The Include BroSet statement on Row $RowNum has resulted in a Bro ShortName clash as $ShortName is defined in both BroSet '%s' and BroSet '%s'. Correct the Bro ShortName in use in one of these BroSets and try again.", $BroSetNamesA[$IbrosA[$ShortNamesA[$ShortName]]['broSetId']], $broSetName));
      if (isset($NamesA[$ShortName]))
        BIError2(sprintf("The Include BroSet statement on Row $RowNum has resulted in a Bro ShortName/Name clash as ShortName $ShortName in '%s' is in use In-BroSet '%s' as a BroName. Correct one of these and try again.", $broSetName, $BroSetNamesA[$IbrosA[$NamesA[$ShortName]]['broSetId']]));
      $ShortNamesA[$ShortName] = $broId;
    }
    # Add the $IbrosA entry, which only needs to include those values referenced if the Bro is used as a Master, or as a Related Bro, or in a SumList, or as a Check target
    if ($Bits & BroB_Slave)
      $tA = [
        'broSetId'=> $broSetId,
        'Bits'    => $Bits]; # all that is needed for a slave as slaves can't be masters or be used in a SumList. djh?? But what about as a Check target?
    else{
      $tA = [
        'broSetId'  => $broSetId,
       #'RowNum'    => $RowNum,
        'postType'  => ($Bits &BroB_DE) ? 1 : 0, # $postType,
       #'master'    => $master, # 0, 2 or BroName string. Becomes Master BroId once resolved
       #'check'     => $check,  # 0 or BroName string
       #'Name'      => $Name,
       #'Level'     => $Level,
       #'DadId'     => $DadId,
        'Bits'      => $Bits,
        'DataTypeN' => $DataTypeN,
       #'DboFieldN' => $DboFieldN,
        'SignN'     => $SignN,
       #'SumUp'     => $SumUp,
       #'Related'   => $Related,
       #'SeSumList' => $SeSumList, # list in Name or ShortName form. Becomes a broId list once resolved
        'PeriodSEN' => $PeriodSEN,
       #'SortOrder' => $SortOrder,
        'FolioHys'      => $FolioHys,
       #'ExclPropDims'  => $ExclPropDims,
       #'AllowPropDims' => $AllowPropDims,
        'PMemDiMesA'    => $PMemDiMesA,
        'UsablePropDims'=> $UsablePropDims,
       #'SlaveYear' => $SlaveYear,
        'Zones'     => $Zones,
       #'ShortName' => $ShortName,
        'Ref'       => $Ref,
        'Descr'     => $Descr];
       #'Comment'   => $Comment,
       #'Scratch'   => $Scratch,
       #'RowComments'=>$RowComments,
       #'Data'      => $Data];
      if ($Bits & BroB_Out) {
        $tA['TxId']    = $TxId;
        $tA['TupId']   = $TupId;
       #$tA['ManDims'] = $ManDims;
      }
    }
    $IbrosA[$broId] = $tA;
  }
  $res->free();
} # End of function IncludeBros()


function Form($timeB, $prefix='') {
  echo "<div>
<p class=c>{$prefix}Copy Paste the BroSet SS up to the Scratch Column into the following Text Box and Click the Import Button:</p>
<form class=c method=post>
<textarea name=Bros autofocus placeholder='Paste BroSet SS here' required rows=10 cols=80>
</textarea>
<br><br>
<button class='c on m10'>Import BroSet</button>
</form>
</div>
";
  Footer($timeB); # Footer($timeB=true, $topB=false, $notCentredB=false)
  ##############
}
