<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/Utils/UK-GAAP-DPL/BrosImport.php

Import Bros from SS Ctrl A Copy Paste to Text Field (Bro Info records)

History:
05.10.12 Started based on UK-GAAP version
         BD Function removed
         Import of Dims by name added
27.10.12 Added setting of Bit BroB_Summing
30.10.12 Added check for Slave summing up to its Master
06.11.12 Removed option of Check on a BD Map re Bro Class use in in Import.php. The option could be restored if needed.
12.11.12 continues changed to use constant operands re php 5.4
23.11.12 Number of slaves per master limit increased from 6 to 9
12.12.12 Exclusion of $NonSummingBroExclDimsChrsGA dims to Non-Summing Bros added
14.01.13 BroId range extended from 1,000 - 9,999 to 1 - 99,999
17.01.13 NoTags column added
18.01.13 ExclDims, AllowDims, DiMes (Exclude, Allow) allowed for Slaves as Filters
06.02.13 Extended ExclDims and AllowDims length from 6 to 9 and then again to 20 on 07.02.13
12.02.13 Added check for no defined UsableDims (no Hy, TxId, AllowDims) for slave -> not a usable dims slave filtering case
         Added check that a non-slave summing Set is RO if any children are Ele Slaves
13.02.13 Started on removal of BD Maps as they are not needed for CW's change to Indirect Input CoA Bros -> filtered Slave Bros for the TxId values

To Do djh??
=====

Make <Either etc for Check optional as the doc says


*/
# ini_set('memory_limit','128M');
require 'BaseTx.inc';
require Com_Inc_Tx.'ConstantsTx.inc';
require Com_Inc_Tx.'ConstantsRg.inc';
require Com_Str_Tx.'DiMesA.inc';         # $DiMesA
require Com_Str_Tx.'DimNamesA.inc'; # $DimNamesA to be flipped
require Com_Str_Tx.'DiMeNamesA.inc';     # $DiMeNamesA to be flipped
require Com_Str_Tx.'ZonesA.inc';         # $ZonesA $ZoneRefsA
require Com_Str_Tx.'Hypercubes.inc';    # $HyNamesA

$HyShortNamesMapA  = array_flip($HyNamesA);
$DimShortNamesMapA = array_flip($DimNamesA);
$DiMeNamesMapA = array_flip($DiMeNamesA);
unset($HyNamesA, $DimNamesA, $DiMeNamesA);

# BuildStructs.inc
require 'inc/BuildStructs.inc';

const COL_Id        =  0;
const COL_Type      =  1;
const COL_Name0     =  4;
const COL_ShortName = 13;
const COL_Master    = 14;
const COL_Ref       = 15;
const COL_TxId      = 16;
const COL_Hys       = 17;
const COL_TupId     = 18;
const COL_DataType  = 19;
const COL_Sign      = 20;
const COL_AcctTypes = 21;
const COL_PostType  = 22;
const COL_RO        = 23;
const COL_ExclDims  = 24;
const COL_AllowDims = 25;
const COL_DiMes     = 26;
const COL_NoTags    = 27;
const COL_Except    = 28;
const COL_Amort     = 29;
const COL_SumUp     = 30;
const COL_Check     = 31;
const COL_Period    = 32;
const COL_StartEnd  = 33;
const COL_Zones     = 34;
const COL_Order     = 35;
const COL_Descr     = 36;
const COL_Comment   = 37;
const COL_Scratch   = 38;
const Num_Imp_Cols  = 39; # One more than COL_Scratch re 0 base. Used in checking for empty import cols ignoring the I columns at the right
const Num_Name_Cols =  9;

# Types just for use by Bros Import
const Slave_MasterExplicit = 1;
const Slave_MasterMatch    = 2;

 $DimsWithMtypeDiMesA = [DimId_IFAClasses, DimId_TFAClasses, DimId_FAIHoldings, DimId_Officers, DimId_TPAType]; # 9, 10, 12, 29, 34 Array of Dims which have M type DiMes
#$DimsReqPropDiMesA   = [                  DimId_TFAClasses, DimId_FAIHoldings, DimId_Officers, DimId_TPAType]; #    10, 12, 29, 34 Array of Dims which require a Property

Head("Bros Import: $TxName", true);
echo "<h2 class=c>Braiins Report Objects Import: $TxName</h2>
";

if (!isset($_POST['Bros']))
  Form();
  ######

$DimSumCheckB = isset($_POST['DimSumCheck']);

#bros= Clean($_POST['Bros'], FT_TEXTAREA);
$bros = trim(preg_replace('/(  +)/m', ' ', $_POST['Bros'])); # trim and reduce internal spaces to one space /- djh?? Do in one preg_replace?
$bros = preg_replace('/( 	 )|(	 )|( 	)/m', TAB, $bros);     # trim spaces around tabs                      |

$RowsA = explode(NL, $bros);
unset($bros);
$_POST      =     # to empty it to save space, not unset in case of an Error() call
$TxElesA    =          # [TxId => Tx Obj]
$TuplesByTxIdA =       # [TxId => [i => TupId]] Tx TupIds by TxId read from Tuplepairs
$IbrosA     =          # Import Bros    [RowNum => broA]
$NamesA     =          # Bro Names      [Name => RowNum]
$TxIdHyIdTupIdRowNumsA=[]; # [TxId][HyId][TupId] => RowNum for finding Masters for slaves using TxId.HyId.TupId matching, converting SE sum TxIds to BroIds, and for checking duplicate TxId HyId TupId use. Single dim array with string key used before 06.06.12 but little if any difference speed wise.
$row = trim($RowsA[0]); # trim re CR via paste
$hdg = "Id	Type	Level	Bro Name	Name 0	N 1	N 2	N 3	N 4	N 5	N 6	N 7	N 8	ShortName	Master	Ref	TxId	Hys	TupId	Data Type	Sign	Acct Types	Post Type	RO	ExclDims	AllowDims	DiMes	NoTags	Except	Amort	Sum Up	Check	Period	StartEnd	Zones	Order	Descr	Comment	Scratch"; # Import cols  only
$len=strlen($hdg);
if (substr($row,0,$len) != $hdg) {
  echo "<p><span class='b L mt05 mb0'>Error:</span><br>The first $len characters of the first row<br>".substr($row,0,$len)."<br>do not match the expected headings row for the import columns of: (Info cols don't matter.)<br>$hdg</p>";
  Form("<br>No DB changes have been made. Correct the error and try again.<br>");
  ########
}

# Read the Tx elements
$res = $DB->ResQuery('Select E.Id,abstract,SubstGroupN,Hypercubes,PeriodN,SignN,TypeN,name,T.Text from Elements E Join Text T on T.Id=E.StdLabelTxtId');
while ($o = $res->fetch_object())
  $TxElesA[(int)$o->Id] = $o;
$res->free();
# Read tuple Info
# $TuplesByTxIdA[TxId => [i => TupId]]
$res = $DB->ResQuery('Select TupId,MemTxId from TuplePairs');
while ($o = $res->fetch_object())
  $TuplesByTxIdA[(int)$o->MemTxId][] = (int)$o->TupId;
$res->free();

$Errors = 0;
$ErrorsA = $NoticesA = $WarningsA = [];
array_shift($RowsA); # throw away the headings row
Pass1(); # Extract data, perform checks, and build $IbrosA
if (!$Errors)
  Passes2and3();
if ($Errors) {
  if ($Errors==1)
    $t='1 Error';
  else if ($Errors<101)
    $t="$Errors Errors";
  else
    $t="$Errors Errors with listing truncated after 100";
  echo "<p class='b L mt05 mb0'>$t:</p>\n<table><tr class=b><td>Row</td><td>Start of Row</td><td>Error</td></tr>\n";
  sort($ErrorsA);
  foreach ($ErrorsA as $tA) {
    $rowNum = $tA[0];
    $start = str_replace(TAB, '&nbsp;', trim(substr($RowsA[$rowNum-2], 0, 30)));
    echo "<tr><td>$rowNum</td><td>$start</td><td>$tA[1]</td></tr>\n";
  }
  echo '</table>';
  Form("<br>$Errors error(s) were found. No DB changes have been made. Correct the errors and try again.<br>");
  ########
}

# No errors so do DB inserts after
$pass4Msg = Pass5(); # Truncate Bros Tables, do inserts to build new Bros tables, issuing warnings
if ($notices=count($NoticesA)) {
  echo sprintf("<p class='b L mt05 mb0'>$notices %s:</p>\n<table><tr class=b><td>Row</td><td>Start of Row</td><td>Notice</td></tr>\n", PluralWord($notices, 'Notice'));
  sort($NoticesA);
  foreach ($NoticesA as $tA) {
    $rowNum = $tA[0];
    $start = str_replace(TAB, '&nbsp;', trim(substr($RowsA[$rowNum-2], 0, 30)));
    echo "<tr><td>$rowNum</td><td>$start</td><td>$tA[1]</td></tr>\n";
  }
  echo '</table>';
}
if ($warnings = count($WarningsA)) {
  $warningWord = PluralWord($warnings, 'Warning');
  $msg = "Data read and passed critical validity checks, though see the $warnings $warningWord above which should be investigated.";
  echo "<p class='b L mt05 mb0'>$warnings $warningWord:</p>\n<table><tr class=b><td>Row</td><td>Start of Row</td><td>Warning</td></tr>\n";
  sort($WarningsA);
  foreach ($WarningsA as $tA) {
    $rowNum = $tA[0];
    $start = str_replace(TAB, '&nbsp;', trim(substr($RowsA[$rowNum-2], 0, 30)));
    echo "<tr><td>$rowNum</td><td>$start</td><td>$tA[1]</td></tr>\n";
  }
  echo '</table>';
}else
  $msg = "Data read and passed critical validity checks";

echo "<p class='b L mt05 mb0'>DB Update Done</p>
<p>DB table BroInfo Truncated.<br>$msg</p>$pass4Msg
<p class='b L mb0'>Building the Bro and Zone Structs</p>
";

# Finished with:
unset($HyShortNamesMapA, $DimShortNamesMapA, $DiMeNamesMapA, $Errors, $ErrorsA, $NoticesA, $WarningsA,
# Pass 1
$DimsWithMtypeDiMesA, $DiMesA, $ZoneRefsA, $RowsA, $RowNum, $TxElesA, $TuplesByTxIdA, $NamesA, $IbrosA, $StartEndTxIdsGA, $TxIdHyIdTupIdRowNumsA,

# Passes2 and 3
$SummingBroExclDimsChrsGA, $NonSummingBroExclDimsChrsGA, $DimSumCheckB);

BuildStructs(); # Uses global $DB, $TxName, $NamespacesRgA, $BroSumTypesGA;

echo 'Memory usage: ', number_format(memory_get_usage()/1024000,1) , ' Mb<br>',
     'Peak memory usage: ', number_format(memory_get_peak_usage()/1024000,1) , ' Mb<br>';

Footer(true, false, true); # time, no Top btn, not centred
######

# Pass1() Extract data, perform checks, and build $IbrosA
function Pass1() {
  global $DB, $DimsWithMtypeDiMesA, $BroSumTypesGA, $HyShortNamesMapA, $DimShortNamesMapA, $DiMeNamesMapA, $DiMesA, $ZoneRefsA, $RowsA, $RowNum, $TxElesA, $TuplesByTxIdA, $NamesA, $IbrosA, $StartEndTxIdsGA, $TxIdHyIdTupIdRowNumsA, $Errors;
  $ShortNamesA = # Bro ShortNames [ShortName => RowNum]

  $bdNamesA  = ['All', '<1', '>1', '1-5', '1-2', '2-5', '>5'];
  $broTypesA = ['Set', 'Ele', 'Set Slave', 'Ele Slave'];
  $broSignsA = ['Debit', 'Credit', 'Either'];
  $Id     = 0; # to start from 1
  $RowNum = $lastContentRow = 1;
  $setTxNamesA =
  $setMemNumsA = []; # row# => count, 0 for set +1 for each member i.e. Bro with the same $dadRow -> check for sets with count == 0
  $prevName = $RowComments = ''; # $RowComments can be multi line
  #$numRows=count($RowsA);
  foreach ($RowsA as $row) {
    ++$RowNum; # Starts at 2 to match SS content rows
    $row=rtrim($row); # re CRs via paste - only matter on short rows
    $len = strlen($row);
    if (($len && ($row[0]==='#' || $row[0]===';')) || ($len>1 && substr($row,0,2)==='//')) { # line commented out
      $RowComments .= ''.$row; # for inclusion with the next stored actual Bro or the last one if after the end
      $IbrosA[$RowNum] = 0; # set row to 0 to indicate blank
      continue;
    }
    if ($len <= 10) {
      # Assumed blank line
      $RowComments .= ''; # for inclusion with the next stored actual Bro or the last one if after the end
      $IbrosA[$RowNum] = 0; # set row to 0 to indicate blank
      continue;
    }

    $colsA = explode(TAB, $row);
    # Accept short rows, filling them in with blanks
    if (count($colsA) < Num_Imp_Cols)
      $colsA = array_pad ($colsA, Num_Imp_Cols, '');

    # defaults
    $idEqual = $UsableDims = $Bits = $txNames = $master = $ShortName = $Ref = $TxId = $nHys = $TupId
        = $DataTypeN = $AcctTypes = $dadRow = $postType = $SumUp  = $check  = $SignN
        = $SortOrder = $AllowDims = $BroDiMesA = $Zones = $Descr = $slave = $SlaveYear = 0; # $stdBro = 0;
    $Hys = $ExclDims = '';

    # Id
    if ($v = trim($colsA[COL_Id])) { # ltrim not done re poss leading tabs
      if ($v[0] === '=') { # Id to set to if in form '=3000'
        $v = substr($v, 1);
        if (ctype_digit($v)) {
          $idEqual = (int)$v;
          if ($idEqual<1 || $idEqual>99950)
            BIError2("Id to be set of $idEqual is out of the allowable range of 1 to 99950");
        }else
          BIError2("Id to be set, as indicated by a leading '=' is not in the expected form of '=nnnn' where nnnn is a 4 digit number");
      }
    }

    # Type
    # Can be deduced for a Slave if Master is set
    if ($t = Match(str_replace(' Master','',$colsA[COL_Type]), $broTypesA)) { # Set, Ele, Set Slave, Ele Slave after stripping out ' Master'
      switch ($t) {
        case 1: $Bits|=BroB_Set;break;          # Set
        case 2: $Bits|=BroB_Ele;break;          # Ele
        case 3: $Bits|=BroB_Set;$slave=1;break; # Set Slave
        case 4: $Bits|=BroB_Ele;$slave=1;break; # Ele Slave
      }
      if ($Bits & BroB_Set) $setMemNumsA[$RowNum]=0;
    }else
      if (!$colsA[COL_Master])
        BIError2("Type is not one of 'Set', 'Ele', 'Set Slave', 'Ele Slave' as expected, or for a Slave the Master Col is not set.");
      # Expect type bit(s) to be set later. If not then -> Error

    # Name and Level
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
          BIError2("Name $i |$v| is not a legal Bro name segment. Names must consist of only a-z,A-Z,0-9 or _ characters");
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
      $RowComments .= ''.$row; # for inclusion with the next stored actual Bro or the last one if after the end
      $IbrosA[$RowNum] = 0; # set row to 0 to indicate blank
      continue;
    }
    if (!ctype_alpha($Name[0]))
      BIError2("Name <i>$Name</i> does not start with a letter. Name 0 must start with a letter. The Names for other levels can start with a digit or be purely numeric");
    $nameA = explode('.',$Name);
    $Level = count($nameA) - 1;
    if ($Level > 8)
      die("Die at row $RowNum with Level $Level which is > limit of 8");

    ++$Id; # as it will be via the BroInfo Insert

    if ($idEqual) { # Have an Id from '=3000' type Id
      if ($idEqual < $Id) {
        BIWarning("Id to be set of <i>$idEqual</i> is < the current Id of $Id so the Set Id ('=') command has been ignored");
        $idEqual = 0;
      }else if ($idEqual == $Id)
        $idEqual = 0;
      else
        $Id = $idEqual;
    }
    if ($Id>99999)
      return BIError("Id has reached 100,000 i.e. 6 digits. Reduce the =nnnn Id value before this so that Ids stay within the 1 to 99,999 range.");

    # Master
    if ($v = $colsA[COL_Master]) {
      # Validity to be checked in Pass2 as could be a forward reference
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
              # A Money type Prior Year Slave must be Sch. Cannot be DE for a cross year figure. Checked in Pass3
            $v = ltrim(substr($v, 5));
          }
        }
        # Strip MasterId if present -> Master Bro Name in $master
       #$master = ctype_digit(substr($v,0,4)) ? ltrim(substr($v,4)) : $v;
        $master = ($p = strpos($v, ' ')) ? ltrim(substr($v, $p+1)) : $v;
        if (!strlen($master))
          BIError2("Master col <i>$v</i> not in expected format of &lt;Match | {Year#}{ MasterId} Master BroName>");
        $slave = Slave_MasterExplicit; # Slave is defined by Master to be checked. Doesn't need TxIdHyIdTupId info
      }else # Col === Match
        # Col entry of Match = instruction that this a Slave Bro with Master to be matched from TxId.HyId.TupId info
        $slave = $master = Slave_MasterMatch;
    }else if ($slave)
      # No Master col entry but Type Included Slave so set to Slave_MasterMatch
      $slave = $master = Slave_MasterMatch; # Slave Bro with Master to be matched from TxIdHyIdTupId info

    if ($slave) {
      $Bits |= BroB_Slave;
      $stdBro = 0;
    }else
      $stdBro = 1;

    # ShortName
    if ($v = $colsA[COL_ShortName]) {
      if (strlen($v) > 48)     BIError2('ShortName length of '.strlen($v).' exceeds the allowable length of 48 characters');
      if (!ctype_alpha($v[0])) BIError2("ShortName <i>$v</i> must start with a letter");
      if (InStr('.', $v))   BIError2("ShortName <i>$v</i> contains a '.' character which is not allowed in a Shortname");
      $ShortName = $v;
      if (isset($ShortNamesA[$ShortName]))
        BIError2("The Bro ShortName <i>$ShortName</i> is already in use as a ShortName at row {$ShortNamesA[$ShortName]}");
      if (isset($NamesA[$ShortName]))
        BIError2("The Bro ShortName <i>$ShortName</i> is already in use as a Bro Name at row {$NamesA[$ShortName]}");
      $ShortNamesA[$ShortName] = $RowNum;
    }

    # Ref
    if ($v = $colsA[COL_Ref]) {
      if (strlen($v) > 48) BIError2('Ref length of '.strlen($v).' exceeds the allowable length of 48 characters');
      $Ref = $v;
    }

    # TxId
    # t o o o = Can be none incl for a Slave if $slave == Slave_MasterExplicit
    if ($v = $colsA[COL_TxId]) {
      if (ctype_digit($v)) {
        $TxId = (int)$v;
        if (isset($TxElesA[$TxId])) {
          $xO = $TxElesA[$TxId];
          # Check that Tx is of correct type
          if ($xO->abstract) {
            BIError("TxId <i>$TxId</i> is abstract and so cannot be used with a Bro for which a concrete element is required");
            continue;
          }else
            if ((int)$xO->SubstGroupN != TSG_Item) {
              BIError("TxId <i>$TxId</i> has substitution group <i>" . SubstGroupToStr($xO->SubstGroupN) . "</i> so cannot be used with a Bro for which a substitution group of Item is required");
              continue;
            }
        }else{
          BIError("TxId <i>$TxId</i> not found in DB. (Elements in the Braiins Tx Skip List are not included in the DB.)");
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
          $nameA[$Level] = $seg = $segr = BroNameFromTxName($xO->name, implode('.', $nameA)); # name, dadName
          for ($i = 2; isset($NamesA[$Name = implode('.', $nameA)]); ++$i)
            $nameA[$Level] = ($segr = $seg . $i);
          if ($Bits & BroB_Set) $setTxNamesA[$Level] = $segr;
          # ListedInvestsIncludedInFANetBookValue
          # ValuationUnlistedInvestNotCarriedOnHistoricalCostBasis
          BINotice("Level $Level name <i>$segr</i> generated from Tx name <i>$xO->name</i>");
        }else{
          BIError("Final name segment of 'Tx' used with a non-Tx based Bro. Enter a name segment or add a TxId if that was intended.");
          continue;
        }
      }else
        # All Tx name segments replaced by previously generated Set ones, or there was an error
        $Name = implode('.', $nameA);
    } # end 'Tx' for auto name generation
    $prevName = $Name;
    # Name Checks
    # Std Bro or Slave
    if (isset($NamesA[$Name])) {
      BIError("The Bro Name <i>$Name</i> is a duplicate of the Name used at row {$NamesA[$Name]}");
      continue;
    }
    # Check Std Bro or Slave Name re Set use
    if ($Level) {
      unset($nameA[$Level]);
      $dadName  = implode('.', $nameA);
      $dadLevel = $Level - 1;
      for ($i=$RowNum-1; $i>1; --$i) {
        $broA = $IbrosA[$i];
        if (is_array($broA)) { # re empty or error rows
          if ($dadName == $broA['Name']) { # also means Level == dadLevel
            # Found Dad which is in $broA
            $dadRow = $i;
            if ($slave && !($Bits & (BroB_Set | BroB_Ele)))
              # Slave not set to Set or Ele so set to Ele. Gets set to Set later by following code if it has kids
              $Bits |= BroB_Ele;
            # Adjust Dad type if needed
            if (!(($dadBits=$broA['Bits']) & BroB_Set)) {
              $dadBits &= ~BroB_Ele; # Unset Ele  Could use $IbrosA[$dadRow]['Bits'] ^= BroB_Ele here as the bit is set
              $dadBits |= BroB_Set;
              $IbrosA[$dadRow]['Bits'] = $dadBits;
              $setMemNumsA[$dadRow]=0;
              BINotice("Row $dadRow type changed from Ele to Set as it has children");
            }
            ++$setMemNumsA[$dadRow];
            break; # out of rows loop
          }else
            # Not a name match
            if ($broA['Level'] <= $dadLevel) {
              BIError("Level $Level Bro <i>$Name</i> is out of Set sequence as the first Level $dadLevel (Dad level) Bro going back up is $broA[Name], not $dadName as expected from the name of the Bro");
              continue 2;
            }
        }else{
          # empty or error row
          if ($broA === -1) { # error row reached before Set found so bail out silently
            #$dadRow = -1; # as flag that no dadRow found due to row errors to avoid "No parent Bro found ..." error below and in further dad checks below
            break;
          }
          if ($broA !== 0) # let loop continue on empty
            die("Die - invalid Row $i value $broA found when looking back for Set check for row $RowNum");
        }
      }
      if (!$dadRow) {
        BIError("No valid dad Bro found for Level $Level Bro <i>$Name</i>. Name or Set name wrong or previous error?");
        continue;
      }
    }else{
      # Std Bro or Slave at level 0
      $setTxNamesA = [];
      if (!($Bits & BroB_Set)) {
          BINotice("Level 0 Bro <i>$Name</i> was defined as an ".BroTypeStr($Bits).' type, but level 0 Bros must be Sets so its type has been set to Set');
        $Bits &= ~BroB_Ele; # Unset Ele
        $Bits |= BroB_Set;
        $setMemNumsA[$RowNum]=0;
      }
    } # End of Name checks
    if (strlen($Name)>300)
      BIError2("The Bro Name <i>$Name</i> is ".strlen($Name)." characters long which exceeds the Bros DB Table Name field size of 300");
    if (isset($ShortNamesA[$Name]))
      BIError2("The Bro Name <i>$Name</i> is already in use as a ShortName at row {$ShortNamesA[$Name]}");
    $NamesA[$Name] = $RowNum;

    # Hys
    # h o o o s  Chr list of hypercube Ids for <members of this set|this element>. Can have wo TxId. Required if Tx based. Can only be more than 1 for a Set Bro.
    if ($v = $colsA[COL_Hys]) {
      $hyCol = $v;
      if ($stdBro && !$colsA[COL_DataType])
        BIError2("HyId $hyCol specified for a Bro of Data Type <i>None</i> which is an illegal combination. Either remove the HyId or change the Data Type");
      else if (!InStr(',', $hyCol)) {
        # Just one HyId
        if (ctype_digit($hyCol)) {
          if (InRange($hyId = (int)$hyCol, 1, HyId_Max))
            $Hys = IntToChr($hyId); # 1 chr
          else
            BIError2("The HyId <i>$hyId</i> is out of the allowable range of 1 to " . HyId_Max);
        }else if (isset($HyShortNamesMapA[$hyCol]))
          $Hys = IntToChr($HyShortNamesMapA[$hyCol]);
        else
          BIError2("The HyId value <i>$hyCol</i> is unknown. (Case matters in Hy Short Names.)");
        $nHys = 1;
      }else{
        # Multiple Hys which is valid for a Set Bro
        $hyIdsA = [];
        foreach (explode(',', $hyCol) as $t) {
          if (ctype_digit($t)) {
            if (InRange($t, 1, HyId_Max))
              $hyIdsA[] = $t;
            else
              BIError2("The HyId <i>$t</i> in the HyId field <i>$hyCol</i> is out of the allowable range of 1 to " . HyId_Max);
          }else if (isset($HyShortNamesMapA[$t]))
            $hyIdsA[] = $HyShortNamesMapA[$t];
          else
            BIError2("The HyId <i>$t</i> in the HyId field <i>$hyCol</i> is unknown. (Case matters in Hy Short Names.)");
        }
        $nHys = count($hyIdsA); # # of valid input HyIds
        $Hys = IntAToChrList($hyIdsA);
        if ($nHys>1) {
          # More than one input hypercube. Eliminate subsets.
          $Hys = EliminateHypercubeSubsets($Hys);
          $nHys = strlen($Hys); # # of input HyIds after eliminating subsets
          if ($nHys>1) {
            if ($Bits & BroB_Ele) {
              $hysDecList = ChrListToCsList($Hys);
              BIError2("$nHys HyIds ($hysDecList) after eliminating any subsets have been supplied for an Element Bro. Element Bros can have only one HyId");
              $nHys = 1; # just to carry one re other poss errors
              $Hys=$Hys[0];
            }
            $Bits |= BroB_RO; # Set with multiple Hys -> RO
          }
        }
      } # End of HyId supplied
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

    if ($nHys <= 1) $hyId = ($nHys === 1) ? ChrToInt($Hys) : 0;
    # $nHys and $Hys set if defined, and $hyId too if $nHys <= 1

    # TupId
    # t u u u =
    if ($stdBro || $slave === Slave_MasterMatch) {
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
          if ($nHys > 1) {
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

    # Build UsableDims if not a Slave. Hy checks are done later when the Duplicates check is done when BroDiMesA is available.
    if ($nHys) {
      if (!$TxId && $slave === Slave_MasterMatch) # Have Hy(s) but no TxId. Valid except for $slave === Slave_MasterMatch cases. Need both TxId and HyId for a Slave whose Master is to be worked out from its TxId.HyId info
        BIError2("Bro is a Slave whose Master is to be matched from its TxId.HyId{.TupId} info but no TxId has been supplied.");
      # Got Hy(s). Build UsableDims if not a Slave. Slave work is done in Pass2 when Masters are available.
      if (!$slave)
        $UsableDims = UsableDims($Hys, $nHys);
    }
    # End of HyId and TupId for All with $nHys, $Hys, and $UsableDims defined

    # Data Type
    # - m m m I  TxId not required
    if ($stdBro) {
      if ($v = $colsA[COL_DataType]) {
        if ($DataTypeN = Match($v, ['None', 'String', 'Integer', 'Money', 'Decimal', 'Date', 'Boolean', 'Enum', 'Share', 'PerShare', 'Percent'])) {
          --$DataTypeN;
          if (!$DataTypeN && $TxId)
            BIError2("TxId $TxId specified for a Bro of Data Type <i>None</i> which is an illegal combination. Either remove the TxId or change the Data Type");
        }else
          BIError2("Data Type <i>$v</i> unknown");
      }else
        BIError2('Data Type not supplied as required');
    }# else
      # Slave
      #if ($colsA[COL_DataType]) {
      # # BINotice('Data Type ignored as Slaves inherit Data Type from their Master'); # Postponed to Pass2 when Master is known. Silent as Export outputs this for info purposes.
      #}

    # Sign
    # - b b b I
    if ($v = $colsA[COL_Sign]) {
      if (!$SignN = Match($v, $broSignsA)) { # Debit, Credit, Either
        BIError2("Sign value <i>$v</i> is invalid - Debit, Credit, Either expected");
        $SignN = BS_Dr;
      }
      if ($stdBro && $DataTypeN !== DT_Money) {
        BINotice("Sign value <i>$v</i> ignored for this non-Money Bro for which Sign is inapplicable");
        $SignN = 0;
      }
      # Slave checking left to Pass2 when Slave DataTypeN is known
    }else if ($DataTypeN === DT_Money) { # can't be a slave as $DataTypeN isn't yet set for a Slave
      # No Sign but need Sign. Try for it from ancestors
      for ($row=$dadRow; $row && !$SignN && $IbrosA[$row]['DataTypeN'] === DT_Money; $row=$IbrosA[$row]['dadRow'])
        $SignN=$IbrosA[$row]['SignN'];
      if (!$SignN) {
        BIError2('No Sign supplied for a Standard Money Bro either directly or by inheritance');
        $SignN = BS_Dr; # but carry on, setting Sign to avoid more errors
      }
    }

    # Account Types
    # - m m m p
    if ($v = $colsA[COL_AcctTypes]) {
      # Expect cs list of up to 4 Account Types
      $acctTypeEnumsA = [];
      foreach (explode(',', $v) as $acctType) {
        if ($acctTypeN = Match($acctType, ['BS', 'CF', 'PL', 'DetailedPL', 'HistoricalPL', 'STRGL', 'Notes', 'Info', 'Other']))
          $acctTypeEnumsA[] = $acctTypeN;
        else
          BIError2("Account Type <i>$acctType</i> is not one of the defined Account Types");
      }
      if (strlen($AcctTypes = IntAToChrList($acctTypeEnumsA)) > 4) # $AcctTypes = sorted unique chr list
        BIError2("Account Types list <i>$v</i> length > max allowed of 4");
    }else if ($stdBro)
      BIError2('Account Type(s) not supplied as required');

    # Post Type
    # - b b b p
    if ($v = $colsA[COL_PostType])
      $postType = $v; # for processing in Pass3 as need to know DataTypeN and SumUp

    # RO
    # - n n n R
    if ($v = $colsA[COL_RO]) {
      if (MatchOne($v, 'RO')) {
        #if (!$slave && ($Bits & BroB_Ele)) 31.08.12 Removed re Stock calcs setting Stock Mvt ele Bros so that they should be made RO.
        #  BIWarning('RO ignored as Element Bros which are not a Slave should not have RO set.');
        #else
          $Bits |= BroB_RO;
      }else
        BIError2("RO (Report Only) value <i>$v</i> is invalid - RO or nothing expected");
    }else{
      # No RO
      if ($slave) {
        BINotice('RO has been set for this Slave as all Slaves are RO.');
        $Bits |= BroB_RO;
      }
    }

    # ExclDims  List of dimension Ids that the Bro cannot use. Requires HyId. Usable Dims list = Hy Dims - ExclDims
    # h o o o f
    if ($v = $colsA[COL_ExclDims]) {
      if (!$nHys)
        BINotice("ExclDims list <i>$v</i> ignored as a Bro without an HyId cannot have an ExclDims list");
      else{
        $exclDimsA = [];
        foreach (explode(',', $v) as $t)
          if (ctype_digit($t)) {
            if (InRange($t, 1, DimId_Max))
              $exclDimsA[] = $t;
            else
              BIError2("The ExclDims Dim <i>$t</i> of |$v| is out of the allowable range of 1 to " . DimId_Max);
          }else if (isset($DimShortNamesMapA[$t]))
            $exclDimsA[] = $DimShortNamesMapA[$t];
          else
            BIError2("The ExclDims Dim <i>$t</i> of |$v| is unknown. (Case matters in Dim Short Names.)");
        if (strlen($ExclDims = IntAToChrList($exclDimsA)) > 20) # $ExclDims = sorted unique chr list
          BIError2("ExclDims list <i>$v</i> length > max allowed of 20");
        if (!$slave) { # Slave checks done in Pass2
          # Check to see that the ExclDims are in the Bro's Usable Dims list
          $len = strlen($ExclDims);
          for ($i=0; $i<$len; ++$i)
            if (!InStr($ExclDims[$i], $UsableDims))
              BIError2('ExclDims Dim ' . ChrToInt($ExclDims[$i]) . " is not in the Bro's Usable Dims list " . ChrListToCsList($UsableDims) . ", so cannot be excluded".($slave?". This could be because the Dim is not one of the Master's Usable Dims.":''));
          # Remove $ExclDims from $UsableDims for subsequent checks
          $UsableDims = str_replace(str_split($ExclDims), '', $UsableDims);
        }
      }
    } # end ExclDims

    # AllowDims  List of dimension Ids that Tx or non-Tx Bros can use. Forms the Usable Dims list for such Bros.
    # - o o o f
    if ($v = $colsA[COL_AllowDims]) {
      if ($ExclDims)
        BIError2("AllowDims list <i>$v</i> specified for a Bro with ExclDims {$colsA[COL_ExclDims]} but ExclDims and AllowDims are mutually exclusive");
      else{
        $allowDimsA = [];
        foreach (explode(',', $v) as $t)
          if (ctype_digit($t)) {
            if (InRange($t, 1, DimId_Max))
              $allowDimsA[] = $t;
            else
              BIError2("The AllowDims Dim <i>$t</i> of |$v| is out of the allowable range of 1 to " . DimId_Max);
          }else if (isset($DimShortNamesMapA[$t]))
            $allowDimsA[] = $DimShortNamesMapA[$t];
          else
            BIError2("The AllowDims Dim <i>$t</i> of |$v| is unknown. (Case matters in Dim Short Names.)");
        if (strlen($AllowDims = IntAToChrList($allowDimsA)) > 20) # $AllowDims = sorted unique chr list
          BIError2("AllowDims list <i>$v</i> length > max allowed of 20");
        if (!$slave) { # Slave checks done in Pass2
          # If the Bro has an Hy and thus Usable Dims, check to see that the AllowDims are in the Usable dims list
          if ($UsableDims) {
            $len = strlen($AllowDims);
            for ($i=0; $i<$len; ++$i)
              if (!InStr($AllowDims[$i], $UsableDims))
                BIError2('AllowDims Dim ' . ChrToInt($AllowDims[$i]) . " is not in the Bro's Usable Dims list ".ChrListToCsList($UsableDims).", so cannot be allowed".($slave?". This could be because the Dim is not one of the Master's Usable Dims.":''));
          }
          $UsableDims = $AllowDims;
        }
      }
    } # end AllowDims

    # DiMes
    # - o o o f
    # BroDiMesA =  Bro DiMes Overrides array [i => MandatsA, DefaultsA, ExcludesA, AllowsA]
    # MandatsA  = i array of Mandatory DiMes, Dim in Bro Usable Dims or DiMe in AllowsA, only one per Dim, Mux with Defaults and Excludes
    # DefaultsA = i array of Default   DiMes, Dim in Bro Usable Dims or DiMe in AllowsA, only one per Dim, Mux with Mandats  and Excludes
    # ExcludesA = i array of Exclude   DiMes, Dim in Bro Usable Dims, Mux with Mandats, Defaults, and Allows
    # AllowsA   = i array of Allow     DiMes, Dim in Bro ExclDims,  Mux with Excludes
    # ExcludesA and AllowsA are allowable for Slave filter use.
    if ($v = $colsA[COL_DiMes]) {
     #if (!$nHys) BIError2("DiMes <i>$v</i> specified for a Bro without an HyId but an HyId is necessary for DiMes to be used");
     #if (!$UsableDims) BIError2("DiMes <i>$v</i> specified for a Bro without any Usable Dims but Usable Dims from an HyId or AllowDims are necessary for DiMes to be used");
      $diMeMandatsA = $diMeDefaultsA = $diMeExcludesA = $diMeAllowsA =
      $manDimsA = $defDimsA = []; # just for checking for duplicate Dim use
      $diMesA = explode(',', $v);
      sort($diMesA); # to get the Allow ones first re m and d available checks
      foreach ($diMesA as $diMe) {
        if (!($len=strlen($diMe))) continue;
        $c = strtolower($diMe[0]);
        if ($len<3 || $diMe[1]!==':') {
          BIError2("DiMes value <i>$diMe</i> is not in expected format of &lt;m | d | x | a>: followed by an integer DiMeId or a DiMe ShortName");
          continue;
        }
        $diMe = substr($diMe, 2); # after leading m:, d:, x:, a:
        if (ctype_digit($diMe))
          $diMeId = (int)$diMe;
        else if (isset($DiMeNamesMapA[$diMe]))
          $diMeId = $DiMeNamesMapA[$diMe];
        else{
          BIError2("DiMes value <i>$c:$diMe</i> is not in expected format of &lt;m | d | x | a>: followed by an integer DiMeId or a DiMe ShortName");
          continue;
        }
        if ($diMeId > DiMeId_Max) {
          BIError2("The DiMeId <i>$diMeId</i> is out of the allowable range for this Bro of 1 to " . DiMeId_Max);
          continue;
        }
        if ($diMeId <= 0) {
          BINotice($diMeId < 0 ? "Negative DiMeId $diMeId ignored" : 'Zero DiMeId ignored');
          continue;
        }
        if ($DiMesA[$diMeId][DiMeI_Bits] & (DiMeB_Zilch | DiMeB_RO)) {
          BINotice("DiMe $diMe has been ignored as it is a Z or R type and so cannot be posted to.");
          continue;
        }
        $dimId = $DiMesA[$diMeId][DiMeI_DimId];
        switch ($c) {
          case 'm': # Mandatory DiMes, Dim in Bro AllowDims or DiMe in AllowsA, only one per Dim, Mux with Defaults and Excludes
            if ($slave) BIError2("Mandatory DiMes m:$diMe specified for a Slave but only Exclude or Allow DiMes can be used with a Slave as filters");
            else if (!InChrList($dimId, $UsableDims) && !in_array($diMeId, $diMeAllowsA)) BIError2("Mandatory DiMes m:$diMe is a member of Dim $dimId which is not one of the Usable Dims for the Bro, nor is DiMe $diMe an Allow DiMe.");
            else if (in_array($dimId, $manDimsA)) BIError2("Mandatory DiMes m:$diMe is a member of Dim $dimId for which another Mandatory DiMe has been supplied, but a Dim can have only one Default DiMe.");
            else if (in_array($diMeId, $diMeDefaultsA)) BIError2("Mandatory DiMes m:$diMe is also a Default DiMe. It can't be both.");
            else if (in_array($diMeId, $diMeExcludesA)) BIError2("Mandatory DiMes m:$diMe is also an Exclude DiMe. It can't be both.");
            else{
              if (!in_array($diMeId, $diMeMandatsA)) # Silently discard duplicates
                $diMeMandatsA[] = $diMeId;
              $manDimsA[] = $dimId;
            }
            break;
          case 'd': # Default DiMes, Dim in Bro AllowDims or DiMe in AllowsA, only one per Dim, Mux with Mandats and Excludes
            if ($slave) BIError2("DiMes d:$diMe specified for a Slave but only Exclude or Allow DiMes can be used with a Slave as filters");
            else if (!InChrList($dimId, $UsableDims) && !in_array($diMeId, $diMeAllowsA)) BIError2("Default DiMes d:$diMe is a member of Dim $dimId which is not one of the Usable Dims for the Bro, nor is DiMe $diMe an Allow DiMe");
            else if (in_array($dimId, $defDimsA)) BIError2("Default DiMes d:$diMe is a member of Dim $dimId for which another Default DiMe has been supplied, but a Dim can have only one Default DiMe.");
            else if (in_array($diMeId, $diMeMandatsA))  BIError2("Default DiMes d:$diMe is also a Mandatory DiMe. It can't be both.");
            else if (in_array($diMeId, $diMeExcludesA)) BIError2("Default DiMes d:$diMe is also an Exclude DiMe. It can't be both.");
            else{
              if (!in_array($diMeId, $diMeDefaultsA))
                $diMeDefaultsA[] = $diMeId;
              $defDimsA[] = $dimId;
            }
            break;
          case 'x': # Exclude DiMes, Dim in Bro AllowDims, Mux with Mandats, Defaults, and Allows
            if ($slave) $diMeExcludesA[] = $diMeId; # Leave Slave x: checking to Pass2
            else if (!InChrList($dimId, $UsableDims))   BIError2("Exclude DiMes x:$diMe is a member of Dim $dimId which is not one of the Usable Dims for the Bro.");
            else if (InChrList($dimId, $ExclDims))      BIError2("Exclude DiMes x:$diMe is redundant as it is a member of Dim $dimId which is excluded via the ExclDims property of the Bro");
            else if (in_array($diMeId, $diMeMandatsA))  BIError2("Exclude DiMes x:$diMe is also a Mandatory DiMe. It can't be both.");
            else if (in_array($diMeId, $diMeDefaultsA)) BIError2("Exclude DiMes x:$diMe is also a Default DiMe. It can't be both.");
            else if (in_array($diMeId, $diMeAllowsA))   BIError2("Exclude DiMes x:$diMe is also an Allow DiMe. It can't be both.");
            else if (!in_array($diMeId, $diMeExcludesA))
              $diMeExcludesA[] = $diMeId;
            break;
          case 'a': # Allow DiMes, No Usable Dims, or Dim in Bro ExclDims, Mux with Excludes
            if ($slave) $diMeAllowsA[] = $diMeId; # Leave Slave a: checking to Pass2
            else if (InChrList($dimId, $AllowDims))     BIError2("Allow DiMes a:$diMe is redundant as it is a member of Dim $dimId which is allowed via the AllowDims property of the Bro");
            else if ($UsableDims && !InChrList($dimId, $ExclDims)) BIError2("Allow DiMes a:$diMe is a member of Dim $dimId which is not one of the ExclDims for the Bro, as is required for an Allow DiMe unless the Bro has no Usable Dims.");
            else if (in_array($diMeId, $diMeExcludesA)) BIError2("Allow DiMes a:$diMe is also an Exclude DiMe. It can't be both.");
            else if (!in_array($diMeId, $diMeAllowsA))
              $diMeAllowsA[] = $diMeId;
            break;
          default:
            BIError2("DiMes value <i>$c:$diMe</i> is not in expected format of &lt;m | d | e | a>: followed by an integer DiMeId or a DiMe ShortName");

        }
      }
      $BroDiMesA = [$diMeMandatsA, $diMeDefaultsA, $diMeExcludesA, $diMeAllowsA];
    }
    # Compact $BroDiMesA i.e. empty subarray -> 0
    if ($BroDiMesA) {
      $subArrays = 4;
      foreach ($BroDiMesA as &$diMesRA)
        if (!$diMesRA || !count($diMesRA)) {
          $diMesRA = 0; # Set empty DiMes arrays to 0
        --$subArrays;
        }
      unset($diMesRA);
      if (!$subArrays)
        $BroDiMesA = 0;
    }
    # end DiMes

    # NoTags
    # t o o o o  Stored in Bits. Can be set for a Bro with TxId to tell RG not to generate tags for this Bro = duplicate TxId.HyId{.TupId} allowed
    if ($v = $colsA[COL_NoTags]) {
      if (MatchOne($v, 'NoTags')) {
        if ($TxId)
          $Bits |= BroB_NoTags;
        else
          BINotice("NoTags ignored as NoTags only applies to Tx Bros");
      }else
        BIError2("NoTags value <i>$v</i> is invalid - NoTags or nothing expected");
    }

    # Hy and Duplicates checks if not NoTags
    if ($nHys && $TxId && !($Bits & BroB_NoTags)) {
      # Hys and TxId
      # Check Hys(s) re the TxId and build $TxIdHyIdTupIdRowNumsA
      $txHysChrList = $xO->Hypercubes; # chr list
      for ($i=0; $i<$nHys; ++$i) {
        $hyChr = $Hys[$i];
        $hyId  = ChrToInt($hyChr);
        if (InStr($hyChr, $txHysChrList)) {
          # Hy is in the Hypercube
          # Check for duplicate TxId.HyId.TupId.ManDiMeId if not a Slave
          if (!$slave) {
            if (isset($TxIdHyIdTupIdRowNumsA[$TxId][$hyId][$TupId])) {
              # Duplicate TxId.HyId.TupId but could still be unique based on ExclDims or Mandatory DiMes
              $row = $TxIdHyIdTupIdRowNumsA[$TxId][$hyId][$TupId];
              $otherBroA = $IbrosA[$row];
              if (DupBrosManExclDimsOrManDiMes($ExclDims, $BroDiMesA, $otherBroA['ExclDims'], $otherBroA['BroDiMesA'])) {
                # Duplicate (meaning same BroRef could be posted) i.e. not unique based on mandatory ExclDims or mandatory DiMes
                if ($TupId)
                  BIError2("Bro TxId $TxId HyId $hyId TupId $TupId is not unique and is not a Slave Bro - the Bro at row $row has the same TxId, HyId and TupId and is not made unique via Mandatory ExclDims or Mandatory DiMes");
                else
                  BIError2("Bro TxId $TxId HyId $hyId is not unique and is not a Slave Bro - the Bro at row $row has the same TxId and HyId and is not made unique via Mandatory ExclDims or Mandatory DiMes");
              }
            }
            $TxIdHyIdTupIdRowNumsA[$TxId][$hyId][$TupId] = $RowNum;
          }
        }else{ # Hy not in Hypercube
          $txHysCsDecList = ChrListToCsList($txHysChrList);
          if (strlen($txHysChrList) === 1)
            BIError2("HyId $hyId does not equal the TxId $TxId hypercube value of $txHysCsDecList");
          else
            BIError2("HyId $hyId is not included in the TxId $TxId hypercubes list of $txHysCsDecList");
        }
      }
    }

    # Except
    # h b b b I Stored in Bits. Set if is for an exceptional item re Dim 5 ExceptionalItemsAdjustmentsDimension
    if ($v = $colsA[COL_Except]) {
      if ($stdBro) {
        if ($DataTypeN === DT_Money) {
          if (!MatchOne($v, 'Except'))
            BIError2("Except value <i>$v</i> is invalid - Except or nothing expected");
          else if (strpos($UsableDims, IntToChr(DimId_ExceptAdjusts)) === false) # Dim 5
            BIError2("Except is set but the Exceptional Item Adjustments dimension [5] is not one of the Usable Dims for the Bro");
          $Bits |= BroB_Except;
        }else
          BINotice("Except ignored as it was supplied for a non-Money Bro for which Except is inapplicable");
      }else
        BINotice("Except ignored as Slaves inherit Except from their Master");
    }

    # Amort
    # h b b b I Stored in Bits. Set if is for amortisation etc    re Dim 6 AmortisationImpairmentAdjustmentsDimension
    if ($v = $colsA[COL_Amort]) {
      if ($stdBro) {
        if ($DataTypeN === DT_Money) {
          if (!MatchOne($v, 'Amort'))
            BIError2("Amort value <i>$v</i> is invalid - Amort or nothing expected");
          else if (strpos($UsableDims, IntToChr(DimId_AmortAdjusts)) === false) # Dim 6
            BIError2("Amort is set but the Amortisation and Impairment Adjustments dimension [6] is not one of the Usable Dims for the Bro");
          $Bits |= BroB_Amort;
        }else
          BINotice("Amort ignored as it was supplied for a non-Money Bro for which Amort is inapplicable");
      }else
        BINotice("Amort ignored as Slaves inherit Amort from their Master");
    }

    # Sum Up + - with Summing Bros.
    # - n n n n
    if ($v = $colsA[COL_SumUp]) {
      if ($Level)
        $SumUp = $v; # for processing in Pass3 as need to know if summing or not
      else
        BINotice("Sum Up value <i>$v</i> supplied for a Level 0 Bro has been ignored");
    }

    # Check
    # - o o o o
    # Only applicable to summing Bros. Processing left to Pass3 because of the possibility of forward references
    if ($v = $colsA[COL_Check]) {
      # Strip leading Check TargetBroId if present as after an Export
      # {TargetBroId }<Equal To | Equal & Opp To>{, <Either | Both | Check | Target>} TargetBroId Source
     #$check = ctype_digit(substr($v,0,4)) ? ltrim(substr($v,4)) : $v; # for processing in Pass3
      if (($p = strpos($v, ' ')) && ctype_digit(substr($v,0,$p)))
        $check = substr($v, $p+1); # for processing in Pass3
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

    # Period
    #   y y y y
    if ($v = $colsA[COL_Period]) {
      if (!($PeriodSEN = Match($v, ['Duration', 'Instant']))) {
        BIError2("Period value <i>$v</i> is invalid - one of Duration, Instant, or nothing is required");
        $PeriodSEN = BPT_Duration;
      }
    }else
      $PeriodSEN = BPT_Duration;
    if ($TxId && $xO->PeriodN != $PeriodSEN)
      BIError2(sprintf("Period value supplied of <i>%s</i> does not match the Taxonomy value for element TxId=$TxId of %s", PeriodTypeToStr($PeriodSEN), PeriodTypeToStr($xO->PeriodN)));

    # StartEnd
    #   y y y y
    # <SumEnd|PostEnd|Stock>
    if ($v = $colsA[COL_StartEnd]) {
      if ($PeriodSEN === BPT_Duration)
        BIError2("StartEnd value <i>$v</i> supplied but this Bro's TxId $TxId has Duration period so cannot have StartEnd");
      else{
        # Expect <|SumEnd|PostEnd|Stock>
        /* BroInfo.PeriodSEN enums
        const BPT_Duration    = 1; # Same as TPT_Duration
        const BPT_Instant     = 2; # Same as TPT_Instant
        const BPT_InstSumEnd  = 3; # Instant StartEnd SumEnd  type
        const BPT_InstPostEnd = 4; # Instant StartEnd PostEnd type
        const BPT_InstStock   = 5; # Instant StartEnd Stock   type */
        if ($t = Match($v, ['SumEnd', 'PostEnd', 'Stock']))
          $PeriodSEN = $t + 2;
        else
          BIError2("StartEnd value <i>$v</i> invalid - should be SumEnd, PostEnd, Stock, or nothing");
      }
      if (!$TxId && $PeriodSEN >= BPT_InstSumEnd && ($Bits & BroB_Ele))
        BIError2("A Non-Tx Input StartEnd Bro must be a Set, not an Element, with members of the Set providing movement values to be used in calculations/checking.");
    } # end StartEnd
    # Tx StartEnd check
    if ($TxId) {
      if ($PeriodSEN >= BPT_InstSumEnd) {
        # StartEnd
        if (!$slave)
          BIError2('A Tx StartEnd Bro must be a Slave of a CoA Non-Tx StartEnd Bro where the input and movement calculations are performed.');
      }else if (in_array($TxId, $StartEndTxIdsGA))
        BIError2("The Bro's TxId $TxId is a StartEnd one so StartEnd needs to be defined as SumEnd, PostEnd, or Stock");
    }

    # Zones
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

    # Order
    # - - o o o
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

    # Scratch
    # - o o o o
    $Scratch = $colsA[COL_Scratch];

    # Descr
    # - o o o p
    if ($v = $colsA[COL_Descr]) {
      if (!$TxId || $v != $xO->Text) {
        if (($TxId && $v === $xO->name))
          $v = '';
        if (strlen($v) > 400) BIError2('Descr length of '.strlen($v).' exceeds the allowable length of 400 characters');
        $Descr = $v;
      }
    }

    # Comment
    # - o o o o o
    if (($Comment = $colsA[COL_Comment]) && strlen($Comment) > 500) {
      BIError2('Comment length of '.strlen($Comment).' exceeds the allowable length of 500 characters');
      continue;
    }

    # Set and Ele Type Bits
    if (!($Bits & (BroB_Set | BroB_Ele)))
      BIError2('No Type has been defined for this Bro nor could it be deducted from Master settings');

    if ($RowComments && ($RowComments = substr($RowComments, 1)) == '') $RowComments=' '; # strip initial  then if empty (single blank row) set to ' ' to preserve a blank line

    $IbrosA[$RowNum] = [
      'Id'        => $Id,
      'dadRow'    => $dadRow,
      'postType'  => $postType,
      'UsableDims'=> $UsableDims,
      'master'    => $master, # 0, 2 or Bro name string
      'check'     => $check,  # 0 or Bro name string
      'Name'      => $Name,
      'Level'     => $Level,
      'Bits'      => $Bits,
      'DataTypeN' => $DataTypeN,
      'AcctTypes' => $AcctTypes,
      'SumUp'     => $SumUp,
      'TxId'      => $TxId,
      'Hys'       => $Hys,
      'TupId'     => $TupId,
      'SignN'     => $SignN,
      'ShortName' => $ShortName,
      'Ref'       => $Ref,
      'PeriodSEN' => $PeriodSEN,
      'SortOrder' => $SortOrder,
      'ExclDims'  => $ExclDims,
      'AllowDims' => $AllowDims,
      'BroDiMesA' => $BroDiMesA,
      'Zones'     => $Zones,
      'Descr'     => $Descr,
      'Comment'   => $Comment,
      'Scratch'   => $Scratch,
      'SlaveYear' => $SlaveYear,
      'RowComments'=>$RowComments
    ];
    $lastContentRow = $RowNum;
    $RowComments='';
  } # end of loop thru rows
  # echo "RowNum after Pass1 = $RowNum<br>";
  if ($RowComments) {
    # Had post RowComments
    # row comment{row comment...}{row comment{row comment...}}
    if ($RowComments = substr($RowComments, 1)); # strip initial  then skip if empty (i.e. single blank row after)
      $IbrosA[$lastContentRow]['RowComments'] .= ''.$RowComments;
    while (++$lastContentRow <= $RowNum)
      unset($IbrosA[$lastContentRow]);
  }
  # Check for sets with no members if there are no errors
  if (!$Errors)
    foreach ($setMemNumsA as $RowNum => $num)
      if (!$num) {
       if ($IbrosA[$RowNum]['Bits'] & BroB_Slave)
         BIError2('The Set Slave does not have any members, which is not legal. Either add members to the set or remove the set property');
       else
          BIWarning('The Set does not have any members. This is legal but not useful.');
      }
}

# Passes2and3() Process those Col values for which Master info is needed for Slaves
#   and for which forward references can occur e.g. Check; plus some checks
#   30.05.12 changed from 1 to 2 passes
function Passes2and3() {
  global $BroSumTypesGA, $SummingBroExclDimsChrsGA, $NonSummingBroExclDimsChrsGA, $DiMesA, $RowNum, $NamesA, $IbrosA, $TxIdHyIdTupIdRowNumsA, $Errors, $DimSumCheckB;

  $allowDimsCheckRowsA = # dadRow => [i => AllowDims that has given an error in the check of AllowDims vs Dad and Ancestor AllowDims
  $TxIdsA = []; # [TxId => [i =>RowNum]] for Tx based Posting (not RO) Bros for use in checking hypercube subset use
  $checkPerformsA = ['Either', 'Both', 'Check', 'Target'];

  # Pass2 for Master/Slave incl DataTypeN for Slaves
  foreach ($IbrosA as $RowNum => &$broRA) {
    if ($broRA===0) continue; # 0 for empty rows
    extract($broRA); # $Id, $dadRow, $postType, $UsableDims, $master, $check, $Name, $Level, $Bits, $DataTypeN, $AcctTypes, $SumUp, $TxId, $Hys, $SignN,
                     # $ShortName, $Ref, $PeriodSEN, $SortOrder, $ExclDims, $AllowDims, $BroDiMesA, $Zones, $Descr, $Comment, $Scratch, $SlaveYear, $RowComments
    if ($master) {
      # Slave whose Master is to be matched or checked
      $nHys = strlen($Hys);
      if ($master === Slave_MasterMatch) {
        # Master to be defined by TxId.HyId.TupId match. Check for Hy subsets. HyId to stay as input i.e. not inherited from Master.
        if ($nHys > 1) {
          BIError2("Slave/Master matching via TxId.HyId.TupId is not supported for multiple HyIds. Please specify the required Master explicitly.");
          continue;
        }
        $matchesA=[];
        $hyId = ChrToInt($Hys);
        if (isset($TxIdHyIdTupIdRowNumsA[$TxId][$hyId][$TupId]))
          $matchesA[] = $TxIdHyIdTupIdRowNumsA[$TxId][$hyId][$TupId];
        # Check via subsets as well
        for ($i=1; $i<=HyId_Max; ++$i)
          if ($i != $hyId && IsHypercubeSubset($hyId, $i)) {
            if (isset($TxIdHyIdTupIdRowNumsA[$TxId][$i][$TupId]))
              $matchesA[] = $TxIdHyIdTupIdRowNumsA[$TxId][$i][$TupId];
          }
        if ($matches=count($matchesA)) {
          if ($matches === 1) {
            $masterRow = $matchesA[0];
            $masterBroRA = &$IbrosA[$masterRow];
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
        # Explicit Master value supplied. Slave can have TxId, HyId, TupId supplied or not.
        if (!isset($NamesA[$master])) {
          BIError2("The nominated Master <i>$master</i> for this Slave does not exist. Either correct the Master name or remove it and use Slave TxId.HyId{.TupId} matching");
          continue;
        }
        $masterRow   = $NamesA[$master];
        $masterBroRA = &$IbrosA[$masterRow];
        if ($masterBroRA['Bits'] & BroB_Slave) {
          BIError2("The nominated Master $master for this Slave is also a Slave. A Master can have multiple Slaves but a chain of Slaves is not permitted. Either correct the Master name, or remove it to allow TxId.HyId{.TupId} matching.");
          continue;
        }
        # Can have TxId, HyId, TupId supplied or not. Use Master values if not, but check them vs Master if supplied.
        $masterTxId = $masterBroRA['TxId'];
        $masterHys  = $masterBroRA['Hys'];

        # Slave TxId
        if ($TxId) {
          if ($masterTxId && $TxId != $masterTxId) {
            BIError2("The nominated Master $master for this Slave has a TxId of $masterTxId but the Slave has a TxId of $TxId. Correct the Master name, or the Slave TxId, or remove the Master name to allow TxId.HyId{.TupId} matching, or remove the Slave TxId and HyId if you are sure the Master name is correct.");
            continue;
          }
        }else
          # No TxId so set to Master's if defined
          if ($masterTxId)
            $broRA['TxId'] = $TxId = $masterTxId;

        # Slave HyId
        if ($Hys) {
          if ($masterHys && $Hys != $masterHys) {
            $nMasterHys = strlen($masterHys);
            $nHys = strlen($Hys);
            if ($nMasterHys >= $nHys) {
              if ($nHys === 1) {
                # May be OK via subset matching
                $hyId = ChrToInt($Hys);
                $masterHyId = ChrToInt($masterHys);
                if (!IsHypercubeSubset($hyId, $masterHyId)) {
                  BIError2("The nominated Master $master for this Slave has HyId $masterHyId but the Slave has HyId $hyId which is not a subset of $masterHyId. Correct the Master name, or the Slave HyId, or remove the Master name to allow TxId.HyId{.TupId} matching, or remove the Slave HyIds and TxId if your are sure the Master name is correct.");
                  continue;
                }
              }else{
                BIError2("The nominated Master $master for this Slave has HyIds ".ChrListToCsList($masterHys)." but the Slave has HyIds ".ChrListToCsList($Hys).". Correct the Master name, or the Slave HyIds, or remove the Master name to allow TxId.HyId{.TupId} matching, or remove the Slave HyIds and TxId if your are sure the Master name is correct.");
                continue;
              }
            }else{
              BIError2("The nominated Master $master for this Slave has ".NumPluralWord($nMasterHys,'HyId').' '.ChrListToCsList($masterHys).' but the Slave has '.NumPluralWord($nHys, 'HyId').' '.ChrListToCsList($Hys).". Correct the Master name, or the Slave HyId(s), or remove the Master name to allow TxId.HyId matching, or remove the Slave HyId(s) and TxId if you are sure the Master name is correct.");
              continue;
            }
          }
        }else{
          # Explicit Master case with no Hys supplied
          $broRA['Hys'] = $Hys = $masterHys; # Set Slave Hys to Master value. Could be none.
          $nHys = strlen($Hys);
        }

        # Slave TupId
        if ($TupId) {
          if ($masterBroRA['TupId'] && $TupId != $masterBroRA['TupId']) {
            BIError2("The nominated Master $master for this Slave has a TupId of $masterBroRA[TupId] but the Slave has a TupId of $TupId. Correct the Master name, or the Slave TxId, or remove the Master name to allow TxId.HyId.TupId matching, or remove the Slave TxId, HyId and TupId if you are sure the Master name is correct.");
            continue;
          }
        }else
          # No TupId so set to Master's if defined
          if ($masterBroRA['TupId'])
            $broRA['TupId'] = $TupId = $masterBroRA['TupId'];
      }
      $broRA['master'] = $masterRow;

      # Update Master
      if ($masterBroRA['Bits'] & BroB_Master) {
        # Been here before
        # Add to SlaveIds in Slave's master
        $masterBroRA['SlaveIds'][] = $Id;
        if (count($masterBroRA['SlaveIds']) > 20) {
          BIError2("The number of Slaves for Master <i>$masterBroRA[Name]</i> exceeds the limit of 20.");
          continue;
        }
      }else{
        # First time for this master
        $masterBroRA['Bits'] |= BroB_Master;
        # Set SlaveIds in Slave's master
        $masterBroRA['SlaveIds'] = [$Id];
      }

      # Back to the Slave

      # Slave Data Type - done in Pass1 for all except Slave
      # - m m m I
      $broRA['DataTypeN'] = $DataTypeN = $masterBroRA['DataTypeN'];

      # Slave Sign
      # - b b b I
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
            for ($row=$dadRow; $row && !$SignN && $IbrosA[$row]['DataTypeN'] === DT_Money; $row=$IbrosA[$row]['dadRow'])
              $SignN=$IbrosA[$row]['SignN'];
            if (!$SignN) {
              BIError2('No Sign supplied for a Money Slave Bro either directly, from its Master, or by inheritance');
              $SignN = BS_Dr; # but carry on, setting Sign to avoid more errors
            }
          }
          $broRA['SignN'] = $SignN;
        }
      }

      # Slave PeriodSEN
      if ($TxId && $PeriodSEN !== $masterBroRA['PeriodSEN'])
        BIError2('The Period and StartEnd properties of a Slave must equal those of its Master. This may require adjustment in the Master rather than the Slave');

      # Build the Slave UsableDims
      if ($nHys) {
        $UsableDims = UsableDims($Hys, $nHys);
        $masterUsableDims = $masterBroRA['UsableDims'];
        if ($UsableDims !== $masterUsableDims) {
          # Slave UsableDims different from Master's because of Slave Hy being a subset of master's, or because of ExclDims/AllowDims use with Master.
          # Remove from Slave UsableDims any Dim which is not part of the Master's UsableDims as no data for that Dim can be posted or summed to the Master, and so will never be avaailable to be copied to the Slave.
          $slaveUsableDims = $UsableDims;
          $UsableDims = '';
          $sLen = strlen($slaveUsableDims);
          for ($i=0; $i<$sLen; ++$i)
            if (strpos($masterUsableDims, ($c = $slaveUsableDims[$i])) === false) {
              # Slave Dim not in Master's UsableDims but check for dim 1 being a subset of dim 2
              if ($c === '1' && strpos($masterUsableDims, '2') !== false)
                $UsableDims .= $c; # include 1 for Slave when Master has 2
            }else
              $UsableDims .= $c; # Slave Dim in Master's UsableDims
        }
        # Slave ExclDims
        if ($ExclDims) {
          # Check to see that the ExclDims are in the Bro's Usable Dims list
          $len = strlen($ExclDims);
          for ($i=0; $i<$len; ++$i)
            if (!InStr($ExclDims[$i], $UsableDims))
              BIError2('ExclDims Dim ' . ChrToInt($ExclDims[$i]) . " is not in the Bro's Usable Dims list " . ChrListToCsList($UsableDims) . ", so cannot be excluded. This could be because the Dim is not one of the Master's Usable Dims.");
          # Remove $ExclDims from $UsableDims for subsequent checks
          $UsableDims = str_replace(str_split($ExclDims), '', $UsableDims);
        }
        # Slave AllowDims
        if ($AllowDims) {
          # If the Bro has an Hy and thus Usable Dims, check to see that the AllowDims are in the Usable dims list
          if ($UsableDims) {
            $len = strlen($AllowDims);
            for ($i=0; $i<$len; ++$i)
              if (!InStr($AllowDims[$i], $UsableDims))
                BIError2('AllowDims Dim ' . ChrToInt($AllowDims[$i]) . " is not in the Bro's Usable Dims list ".ChrListToCsList($UsableDims).", so cannot be allowed. This could be because the Dim is not one of the Master's Usable Dims.");
          }
          $UsableDims = $AllowDims;
        }
        $broRA['UsableDims'] = $UsableDims;
      }

      # Check DiMes for the Slave
      if ($BroDiMesA) {
        $masterBroDiMesA = $masterBroRA['BroDiMesA'];
        $diMeExcludesA = $BroDiMesA[II_ExcludesA];
        $diMeAllowsA   = $BroDiMesA[II_AllowsA];

        # Slave DiMes Excludes x:
        if ($diMeExcludesA)
          foreach ($diMeExcludesA as $diMeId) {
            $dimId = $DiMesA[$diMeId][DiMeI_DimId];
            if (!InChrList($dimId, $UsableDims)) {
              # x:DiMeId not in UsableDims. Could still be ok if allowed by Master's DiMes
              if ($masterBroDiMesA && $masterBroDiMesA[II_AllowsA] && in_array($diMeId, $masterBroDiMesA[II_AllowsA]))
                continue;
              BIError2("Exclude DiMes x:$diMeId is a member of Dim $dimId which is not one of the Usable Dims for the Bro".(InChrList($dimId, $masterUsableDims) ? '.' : " because the Dim is not one of the Master's Usable Dims."));
            }else if (InChrList($dimId, $ExclDims)) BIError2("Exclude DiMes x:$diMeId is redundant as it is a member of Dim $dimId which is excluded via the ExclDims property of the Bro");
            else if ($diMeAllowsA && in_array($diMeId, $diMeAllowsA)) BIError2("Exclude DiMes x:$diMeId is also an Allow DiMe. It can't be both.");
          }

        # Slave DiMes Allows a:
        if ($diMeAllowsA)
          foreach ($diMeAllowsA as $diMeId) {
            $dimId = $DiMesA[$diMeId][DiMeI_DimId];
            if (!InChrList($dimId, $UsableDims)) {
              # a:DiMeId not in UsableDims. Redundant if allowed by Master's DiMes
              if ($masterBroDiMesA && $masterBroDiMesA[II_AllowsA] && in_array($diMeId, $masterBroDiMesA[II_AllowsA]))
                BIError2("Allow DiMes a:$diMeId is allowed by the Master's DiMes property so does not need to be repeated for the Slave.");
              else
                BIError2("Allow DiMes a:$diMeId is a member of Dim $dimId which is not one of the Usable Dims for the Bro".(InChrList($dimId, $masterUsableDims) ? '.' : " because the Dim is not one of the Master's Usable Dims."));
            }else if (InChrList($dimId, $AllowDims)) BIError2("Allow DiMes a:$diMeId is redundant as it is a member of Dim $dimId which is allowed via the AllowDims property of the Bro");
            else if (in_array($diMeId, $diMeExcludesA)) BIError2("Allow DiMes a:$diMeId is also an Exclude DiMe. It can't be both.");
          }
      }

      # Set the Slave's filtering bit if Slave Filtering is in use via DiMes or UsableDims
      # Slave filtering applies always if the Slave has DiMes settings.
      # Slave filtering via UsableDims applies if the Slave has UsableDims defined (can be none if no Hy, TxId, AllowDims)
      #  and there are any Dims in the Master's Usable Dims which are not in the Slave's Usable Dims.
      # The two Usable Dims must be different for this to be the case. The Usuable Dims filtering case can arise if:
      # - Slave Hy is a subset of the Master's (tho negated if Master has ExclDims for the Dims it has that are not in the subset Hy)
      # - Slave has ExclDims or AllowDims which remove Dims in the Master's UsableDims
      # It doesn't matter if the Slave's Usable Dims contains Dims which are not in the Master's Usable Dims.
      # ==> Usable Dims Slave filtering applies if the two UsableDims are different and if the Master UsableDims is not a subset of the Slave's UsableDims.
     #if (($usableDimsFiltering = $UsableDims !== $masterBroRA['UsableDims'] && !IsDimsListSubset($masterBroRA['UsableDims'], $UsableDims)) || $BroDiMesA)
      if ($BroDiMesA || ($UsableDims && ($UsableDims !== $masterBroRA['UsableDims'] && !IsDimsListSubset($masterBroRA['UsableDims'], $UsableDims))))
        $broRA['Bits'] |= BroB_SFilter;

      # Unset UsableDims for an Ele Slave without UsableDims filtering - no leave UsableDims so BrosExport and BuildStructs can repeat above $usableDimsFiltering calc
      # if (($Bits & BroB_Ele) && !$usableDimsFiltering)
      #  $broRA['UsableDims'] = 0;

    }else
      # Not a Slave. If Tx based and a posting Bro (not RO) add to $TxIdsA for the non-RO superset use check in the next loop below
      if ($TxId && !($Bits&BroB_RO)) $TxIdsA[$TxId][] = $RowNum;
  }
  if ($Errors) return;

  # Pass3 with Slaves and Masters done and specifically with DataTypeN inherited from Master for Slaves as needed for $summing (SumUp) and PostType
  # - SumUp
  # - Post Type
  # - Check
  # - some checks
  end($IbrosA);
  $lastRowNum = key($IbrosA);
  foreach ($IbrosA as $RowNum => &$broRA) {
    if ($broRA===0) continue; # 0 for empty rows
    extract($broRA); # $Id, $dadRow, $postType, $UsableDims, $master, $check, $Name, $Level, $Bits, $DataTypeN, $AcctTypes, $SumUp, $TxId, $Hys, $SignN,
                     # $ShortName, $Ref, $PeriodSEN, $SortOrder, $ExclDims, $AllowDims, $BroDiMesA, $Zones, $Descr, $Comment, $Scratch, $SlaveYear, $RowComments

    if ($slave = $Bits & BroB_Slave)
      $masterBroA = $IbrosA[$master];
    $stdBro = !$slave;

    if ($DataTypeN) {
      if ($summing = in_array($DataTypeN, $BroSumTypesGA)) {
        $broRA['Bits'] |= BroB_Summing;
        # If not a Slave, has UsableDims, and no AllowDims exclude the Dims not appropriate for a summing Bro if not already excluded
        if (!$slave && $UsableDims && !$AllowDims)
          foreach ($SummingBroExclDimsChrsGA as $dimChr) # X, Y, Z or 40, 41, 42 or Currencies, Exchanges, Languages
            if (InStr($dimChr, $UsableDims)) {
              $broRA['ExclDims'] = $ExclDims .= $dimChr; # No need to sort this as the $SummingBroExclDimsChrsGA dims are at the end of the line
              $broRA['UsableDims'] = $UsableDims = str_replace($dimChr,'',$UsableDims);
              BINotice(sprintf('Dim %d added to ExclDims to remove it from the Usable Dims as it is not applicable to a summing Bro', ChrToInt($dimChr)));
          }
      }
    }else
      $summing=0;

    if (!$summing && $PeriodSEN >= BPT_InstSumEnd)
      BIError2('A non-Summing Bro cannot have StartEnd set');

    # Exclude the Dims not appropriate for a non-summing non-slave Bro with UsableDims and no AllowDims if not already excluded
    if (!$summing && !$slave && $UsableDims && !$AllowDims)
      foreach ($NonSummingBroExclDimsChrsGA as $dimChr) # '3' or 3 or DimId_Restated
        if (InStr($dimChr, $UsableDims)) {
          $dim = ChrToInt($dimChr);                                   # /- To keep ExclDims sorted
          $exclDimsA = ChrListToIntA($ExclDims);                      # |
          $exclDimsA[] = $dim;                                        # |
          $broRA['ExclDims'] = $ExclDims = IntAToChrList($exclDimsA); # |
          $broRA['UsableDims'] = $UsableDims = str_replace($dimChr,'',$UsableDims);
          BINotice("Dim $dim added to ExclDims to remove it from the Usable Dims as it is not applicable to a Non-Summing Bro");
      }

    # Sum Up Pass3 with Summing Bros.
    # - n n n I n
    # Out of column sequence because of use of $SumUp with Post Type
    if ($SumUp) {
      if (!$summing) {
        BIError2("Sum Up value <i>$SumUp</i> used with non-Summing Bro.");
        continue;
      }else{
        $v=$SumUp;
        switch ($SumUp = Match($SumUp, ['+', 'No', 'NA'])) { # BroSumUp_Yes(1): +, BroSumUp_No(2): No, BroSumUp_NA(3): NA
          case BroSumUp_Yes: # +
            # Not level 0 is checked in Pass1 so should always have a Dad here
            if ($IbrosA[$dadRow]['DataTypeN'] != $DataTypeN) { # + not valid
              # only No valid due to Data Types being different
              # echo $RowNum, ' Sum Up value <i>+</i> supplied for a Bro with a Data Type of '. DataTypeStr($DataTypeN) . ' which is different from the Data Type of ' . DataTypeStr($IbrosA[$dadRow]['DataTypeN']) . " of its parent Set, so the Sum Up value should be 'No'. It has been set to that.<br>";
              BINotice('Sum Up value <i>+</i> supplied for a Bro with a Data Type of '. DataTypeStr($DataTypeN) . ' which is different from the Data Type of ' . DataTypeStr($IbrosA[$dadRow]['DataTypeN']) . " of its parent Set, so the Sum Up value should be 'No'. It has been set to that.");
              $SumUp = BroSumUp_No;
            }
            # Build PostAllowDims as AllowDims + any extra ones in Dad which in turn will have any extras from its dad and so on
            # No left to BuildStructs.php
            # $broRA['PostAllowDims'] = IntAToChrList($UsableDims .= $IbrosA[$dRow]['PostAllowDims']); # IntAToChrList() sorts the dims and eliminates duplicates
            if ($DimSumCheckB) {
              # Check AllowDims vs Dad and Ancestor AllowDims for which summing is to take place i.e. all the way to Level 0 or until SumUp is not BroSumUp_Yes,
              # skipping any sets without AllowDims i.e. non-Tx Sets for which any Dims are OK.
              # Skip the error for a given Set if the current AllowDims have already generated an error for it.
              for ($dRow=$dadRow,$dName='Dad'; $dRow; $dRow=$IbrosA[$dRow]['SumUp']==BroSumUp_Yes?$IbrosA[$dRow]['dadRow']:0,$dName='Ancestor')
                # Remove Dad/Ancestor AllowDims from $UsableDims. Should go to nothing if all this Bro's AllowDims are in the Dad/Ancestor ones
                # But OK if first DiMeId is 1 and first of Dad's is 2 as Dim 1 is a subset of 2
                if (($dadAllowDims=$IbrosA[$dRow]['UsableDims']) &&
                    ($t = str_replace(str_split($dadAllowDims),'', $UsableDims)) &&
                    ($t[0]!=='1' || $dadAllowDims[0]!=='2' || ($t=substr($t,1)))) {
                  if (isset($allowDimsCheckRowsA[$dRow]) && in_array($UsableDims, $allowDimsCheckRowsA[$dRow])) continue; # skip if already done
                  $n=strlen($t);
                  # Handle special case of first DiMeId is 2 and first of Dad's is 1 which could be ok at posting time if only DiMeIds 3 or 4 are used
                  if ($t[0]==='2' && $dadAllowDims[0]==='1') {
                    if ($n==1)
                      BIWarning(sprintf("Dim 2 of the %s Usable Dims of this SumUp '+' Bro is not a member of the %s Usable Dims of its $dName Set at row %d, but that could be OK at posting time if only DiMeId 3 or 4 is used as these are also available in Dim 1.", ChrListToCSDecList($UsableDims), ChrListToCSDecList($dadAllowDims), $dRow));
                    else # djh?? Should be BIError2
                      BIWarning(sprintf("Dims %s of the %s Usable Dims of this SumUp '+' Bro are not members of the %s Usable Dims of its $dName Set at row %d, but the Dim 2 could be OK at posting time if only DiMeId 3 or 4 is used as these are also available in Dim 1.", ChrListToCSDecList($t), ChrListToCSDecList($UsableDims), ChrListToCSDecList($dadAllowDims), $dRow));
                  }else # djh?? Should be BIError2
                    BIWarning(sprintf("%s %s of the %s Usable Dims of this SumUp '+' Bro %s of the %s Usable Dims of its $dName Set at row %d. SumUp '+' needs all of the Usable Dims of this Bro to also be Allowable for the $dName Set. Adjust the Usable Dims of this Bro or the $dName Set (via HyIds, ExclDims, AllowDims, DiMeIds as approriate); or rearrange the Bro Tree structure; or change the SumUp setting.", PluralWord($n, 'Dim'), ChrListToCSDecList($t), ChrListToCSDecList($UsableDims), $n>1?'are not members':'is not a member', ChrListToCSDecList($dadAllowDims), $dRow));
                  $allowDimsCheckRowsA[$dRow][]=$UsableDims;
                }
            }
            break; # end of Yes +
          case BroSumUp_No: # No
            break;
          case BroSumUp_NA: # NA - valid if Data Types are different
            if ($IbrosA[$dadRow]['DataTypeN'] == $DataTypeN) {
              BIError2("Sum Up value <i>$v</i> is invalid. + or No expected for this Bro which is a Summing Bro with the same Data Type as its parent Set");
              continue 2;
            }
            break;
          default:
            BIError2("Sum Up value <i>$v</i> is invalid. +, No, NA expected");
            continue 2;
        }
        $broRA['SumUp'] = $SumUp;
      }
    }else{
      if ($Level && $summing && $dadRow>0 && $IbrosA[$dadRow]['DataTypeN'] == $DataTypeN)
        $broRA['SumUp'] = BroSumUp_Yes; # + = default for summing Bro of same Data Type as dad Set
    }

    # Post Type Pass3
    # b b b I p
    if ($postType) {
      if ($DataTypeN == DT_Money) {
        $v = $postType;
        if ($postType = Match($postType, ['DE', 'Sch'])) { # 1: DE, 2: Sch
          if ($slave && $SlaveYear) {
            # A Money type Prior Year Slave must be Sch
            if ($postType !== 2) BINotice("A Money type Prior Year Slave must be Sch not $v. It has been set to Sch.");
            $postType = 2;
          }
          if ($SumUp == BroSumUp_Yes)
            # Check if DE/Sch type matches Dad's type for Sum Up case
            if ($dadRow && ($dadsPostType = $IbrosA[$dadRow]['postType']) != $postType) {
              # Dad's postType not same
              $dads = $dadsPostType == 1 ? 'DE' : 'Sch';
              BIError2("Money Bro has Sum Up = '+' but its Post Type <i>$v</i> is different from the <i>$dads</i> Post Type of its dad. They must be the same when Sum Up is '+'");
            }
        }else
          BIError2("Post Type <i>$postType</i> is invalid - DE or Sch is expected");
      }else
        BINotice("Post Type <i>$postType</i> ignored as Post Type is inapplicable to non-Money Bros");
    }else
      if ($slave) $postType = $masterBroA['postType'];
    $broRA['postType'] = $postType;
    if ($postType==1)
      $broRA['Bits'] |= BroB_DE;

    # Check Pass3
    # - o o o o o
    # Only applicable to summing Bros
    # Stored as TPYtargetBroId where T is the Type code, P the Performed when code, Y the Year digit, and targetBroId is the Check Target BroId

    # Add Check for Summing Set Slave as Data Import/Summing does not copy M to S for a Set Slave. Instead the set summing happens as for any other set, and so a check should be made.
    # But the check should not be added if any of the Bros that sum to the Set Slave involve filtering and the Set Slave isn't filtered itself, as then the Set Sum is not expected to equal the Master.
    if ($slave && $summing && ($Bits & BroB_Set)) {
     #$check='Equal To, Either'.$masterBroA['Name'];
      $broRA['check'] = 0; # to zap any previous check
      $tarBroA = $IbrosA[$master];
      $setMemberFiltered = 0;
      for ($rowNum=$RowNum+1; $rowNum <= $lastRowNum; ++$rowNum) {
        $broA = $IbrosA[$rowNum];
        if ($broA===0) continue; # 0 for empty rows
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
        $broRA['check'] = "EE$SlaveYear".$tarBroA['Id'];
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
      # <Equal To | Equal & Opp To>{, <Either | Both | Check | Target>} Bro Name
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

      # Now {, <Either | Both | Check | Target>}
      if (!strlen($check)) {
        BIError2('Check not in form &lt;Equal To | Equal & Opp To>{ &lt;Either | Both | Check | Target>}{ Year#} BroName');
        continue;
      }
      if (!strlen($check = trim(substr($check, 0+($check[0]===','))))) { # $check[0] === ',' to step over previous form with a comma. djh?? Can be removed once....
        BIError2('Check not in form &lt;Equal To | Equal & Opp To>{ &lt;Either | Both | Check | Target>}{ Year#} BroName');
        continue;
      }
      if (!($P = Matchn($check, $checkPerformsA))) {
        BIError2('Check not in form &lt;Equal To | Equal & Opp To>{ &lt;Either | Both | Check | Target>}{ Year#} BroName');
        continue;
      }
      $P = $checkPerformsA[$P-1]; # now Either | Both | Check | Target
      if (!strlen($check = ltrim(substr($check, strlen($P))))) {
        BIError2('Check not in form &lt;Equal To | Equal & Opp To>{ &lt;Either | Both | Check | Target>}{ Year#} BroName');
        continue;
      }
      $P = $P[0]; #  Now E | B | C | T
      # { Year#}
      if (!strncasecmp($check, 'Year', 4)) {
        if (strlen($check)<5 || !ctype_digit($check[4])) {
          BIError2('Check not in form &lt;Equal To | Equal & Opp To>{ &lt;Either | Both | Check | Target>}{ Year#} BroName');
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
      if (isset($NamesA[$check])) {
        $targetRow = $NamesA[$check];
        if ($targetRow == $RowNum) {
          BIWarning("Check <i>$check</i> is a reference to self (this Bro) which serves no purpose.");
          continue;
        }
        if ($master===$targetRow) {
          BIWarning("Check <i>$check</i> is a reference to the Master of this Bro so has been removed.".(($broRA['Bits']&BroB_RO)?' The RO setting for this Bro should be reviewed.':''));
          $broRA['check'] = 0;
        }else{
          $tarBroA = $IbrosA[$targetRow];
          if ($tarBroA['DataTypeN'] === $DataTypeN) {
            $broRA['check'] = "$T$P$Y".$tarBroA['Id'];
          }else{
            BIError2(sprintf("Check Target Bro at row $targetRow <i>$check</i> has Data Type %s which is different from this Bro's Data Type of %s.", DataTypeStr($tarBroA['DataTypeN']), DataTypeStr($DataTypeN)));
            continue;
          }
        }
      }else{
        BIError2("Check Target Bro <i>$check</i> does not exist");
        continue;
      }
    }

    # Checks Pass3
    ##############
    # If Bro is not a Slave and is a Posting Bro (not RO) that is Tx based, check if if its Tx Hy element is a Hy subset of another Posting Bro with same TupId
    # $TxIdsA: [TxId => [i =>RowNum]] for Tx based Posting (not RO) Bros for use in checking hypercube subset use
    if (!$slave && $TxId && !($Bits&BroB_RO) && count($TxIdsA[$TxId])>1) { # $TxIdsA[$TxId] will have at least one entry, the one for this $TxId
      # Posting Bros have only one Hy
      $hyId = ChrToInt($Hys);
      foreach ($TxIdsA[$TxId] as $rowNum)
        if ($rowNum != $RowNum && $IbrosA[$rowNum]['TupId']==$TupId)
          foreach (ChrListToIntA($IbrosA[$rowNum]['Hys']) as $oHyId)
            if ($oHyId !== $hyId && IsHypercubeSubset($hyId, $oHyId)) # $oHyId = other HyId. == case excluded as that would have been checked re duplicate Hy.Tx.Tup
              BIError2("TxId $TxId Hy $hyId Posting (not RO) use is covered by Posting Bro {$IbrosA[$rowNum]['Name']} at row $rowNum with Hy $oHyId of which Hy $hyId is a subset");
    } # end TxId check

    # Check that a Slave does not SumUp to its Master
    if ($slave)
      for ($dRow=$dadRow; $dRow; ) {
        if ($IbrosA[$dRow]['SumUp']==BroSumUp_Yes) {
          $dRow=$IbrosA[$dRow]['dadRow'];
          if ($dRow !== $master) continue;
          BIError2("Slave Bro sums up to its Master which would cause an infinite loop in summing. Either change the Master/Slave construction or break the SumUp chain.");
        }
        break;
      }

    # Summing Set checks
    if ($summing && ($Bits & BroB_Set)) {
      # Check that a non-slave summing Set is RO if any children are Ele Slaves. Added 12.02.13
      if (!($Bits & BroB_RO)) # will be RO if a Slave
        for ($rowNum=$RowNum+1; $rowNum <= $lastRowNum; ++$rowNum) {
          $broA = $IbrosA[$rowNum];
          if ($broA===0) continue; # 0 for empty rows
          if ($broA['Level'] <= $Level) break; # past the end of the Set
          $bits = $broA['Bits'];
          if (($bits & BroB_Slave) && ($bits & BroB_Ele)) {
            # Ele Slave so set the Set Bro to RO
            BINotice("RO has been set for this Set as it contains at least one Ele Slave at Row $rowNum");
            $broRA['Bits'] |= BroB_RO;
            break;
          }
        }

      # Check that a CoA StartEnd (= summing) Set Bro has children to only 1 level that are same DataType but Duration Period
      if (!$TxId && $PeriodSEN >= BPT_InstSumEnd)
        for ($rowNum=$RowNum+1; $rowNum <= $lastRowNum; ++$rowNum) {
          $broA = $IbrosA[$rowNum];
          if ($broA===0) continue; # 0 for empty rows
          if ($broA['Level'] <= $Level) break; # past the end of the Set
         #if ($broA['Level'] >  $Level) { djh?? Put this back once Charles has finihsed with his temporary StartEnd Sets
         #  BIError2("A CoA StartEnd Set Bro cannot have a Set child as a movement Bro, but the row $rowNum Bro is a Set. Charles, discuss if you want this please. D");
         #  break;
         #}
          if ($broA['Bits'] & BroB_Set) continue; # skip sets for now re CW's Stock usage
          if ($broA['DataTypeN'] != $DataTypeN) {
            BIError2(sprintf("CoA StartEnd Set Bro children (movement Bros) should all have the same Data Type (%s) as the StartEnd Set Bro, but the row $rowNum Bro Data Type is %s. Charles, discuss if you want this please. SE calcs could be made to skip Bros with a ifferent Data Type that were not SumUp. D", DataTypeStr($DataTypeN), DataTypeStr($broA['DataTypeN'])));
            break;
          }
          if ($broA['PeriodSEN'] !== BPT_Duration) {
            BIError2("CoA StartEnd Set Bro children (movement Bros) should all have Duration Period, but the row $rowNum Bro has Instant Period.");
            break;
          }
        }
    }
  }
  unset($broRA);

  if (!$Errors)
  # Pass4 with all cols processed check Slave Post Type
  foreach ($IbrosA as $RowNum => $broA) {
    if ($broA===0) continue; # 0 for empty rows
    if ($broA['Bits'] & BroB_Slave) {
      # Slave
      $masterBroA = $IbrosA[$broA['master']];
      if ($broA['postType']==1 && $masterBroA['postType'] != 1)
        BIError2(sprintf('Slave is Post Type DE whereas its Master (BroId %d at row %d) is Post Type Sch, which is not a legal combination.', $masterBroA['Id'], $broA['master']));
    }
  }
}

# Pass5() Truncate Bros Tables, do inserts to build new Bros tables, issuing warnings
function Pass5() {
  global $DB, $TxElesA, $NamesA, $IbrosA, $RowNum, $DimsWithMtypeDiMesA;

  # Truncate
  ZapTable('BroInfo');
  #$DB->StQuery('Alter Table BroInfo auto_increment=1000');

  # Insert
  $numStd = $numSlave = $numMaster = 0;
  $DB->autocommit(false);
  foreach ($IbrosA as $RowNum => $broA) {
    if ($broA===0) continue; # 0 for empty rows
    extract($broA); # $Id, $dadRow, $postType, $UsableDims, $master, $check, $Name, $Level, $Bits, $DataTypeN, $AcctTypes, $SumUp, $TxId, $Hys, $TupId, $SignN,
                    # $ShortName, $Ref, $PeriodSEN, $SortOrder, $ExclDims, $AllowDims, $BroDiMesA, $Zones, $Descr, $Comment, $Scratch, $SlaveYear, $RowComments{, $SlaveIds}
   # Set BroB_M, BroB_2, BroB_NoMok
   # BroB_M  Set if Bro is not RO and the Bro's UsableDims includes one of the Dims which include M# Type DiMes
   # BroB_2    Set if BroB_M is set and Bro requires a Property DiMe, with the M0 DiMe 423 CoSec being an exception which is tested for in BroRefPost()
   #              Is not set for a Bro whose AllowsDims includes 12 FAIHoldings or 34 TPAType (all M1 type DiMes), if the next Dim (13 FAITypes or 35 TPAStatus) has been excluded from the Bro.
   # BroB_NoMok Set for Officers, Money Type, 423 CoSec excluded for which a post wo an M# type DiMe is OK
   if ($UsableDims && !($Bits & BroB_RO))
      foreach ($DimsWithMtypeDiMesA as $dimId) # array of Dims which have M type DiMes
        if (InChrList($dimId, $UsableDims)) {
          $Bits |= BroB_M;
          switch ($dimId) {
           #case DimId_IFAClasses:  break; # No property required. All DiMe are type M0
            case DimId_FAIHoldings:                                               # |
            case DimId_TPAType:     if (!InChrList(++$dimId, $UsableDims)) break; # \- Don't set BroB_2 if next Dim has been excluded, o'wise fall thru to set it
            case DimId_TFAClasses:  $Bits |= BroB_2; break; # Always
            case DimId_Officers:    $Bits |= BroB_2; # Always
              if ($DataTypeN === DT_Money && $BroDiMesA && $BroDiMesA[II_ExcludesA] && in_array(DiMeId_CoSec, $BroDiMesA[II_ExcludesA]))
                $Bits |= BroB_NoMok; # Set for Officers, Money Type, 423 CoSec excluded
          }
          break;
        }
    # Jsonise $BroDiMesA if it is defined
    if ($BroDiMesA) $BroDiMesA = json_encode($BroDiMesA, JSON_NUMERIC_CHECK);

    if ($dadRow)
      $dadA=$IbrosA[$dadRow];
    if ($TxId) {
      # Tx warnings
      $xO = $TxElesA[$TxId];
      # Data Type
      $txTypeN = MapTxTypeToDataType($xO->TypeN);
      if ($DataTypeN != $txTypeN)
        BIWarning("Bro <i>$Name</i> Data Type of " . DataTypeStr($DataTypeN) . " is different from the Data Type of ". DataTypeStr($txTypeN) . " derived from the Tx Type of " . ElementTypeToStr($xO->TypeN));
      # Sign
      if ($DataTypeN === DT_Money) {
        if (!$SignN)
          $SignN = $xO->SignN;
        if (!$SignN && $dadRow && $dadA['DataTypeN'] == DT_Money)
          $SignN = $dadA['SignN'];
        if ($SignN != $xO->SignN && $xO->SignN)
          BIWarning("Bro <i>$Name</i> the Sign of " . SignToStr($SignN) . " is different from the TxId $TxId Sign of " . SignToStr($xO->SignN));
      }
    }else
      # Not Tx based. Sign wo TxId
      if ($DataTypeN == DT_Money && !$SignN && $dadRow && $dadA['DataTypeN'] == DT_Money)
        $SignN = $dadA['SignN'];

    # 2 Different inserts: Std Bro, Slave
    if (!($Bits & BroB_Slave)) {
      ################
      # Standard Bro # i.e. not a Slave
      ################
      $colsAA = [
        'Id'        => $Id, # specifically set Id as can have gaps
        'Name'      => $Name,
        'Level'     => $Level,
        'Bits'      => $Bits,
        'DataTypeN' => $DataTypeN,
        'AcctTypes' => $AcctTypes,
        'SumUp'     => $SumUp];
      if ($Bits & BroB_Master) {
        $colsAA['SlaveIds']  = implode(',', $SlaveIds);
        ++$numMaster;
      }
      ++$numStd;
      # end of Std Bro
    }else if ($Bits & BroB_Slave) {
      #########
      # Slave #
      #########
      $masterBroA = $IbrosA[$master];
      $Bits |= ($masterBroA['Bits'] & (BroB_Except | BroB_Amort)); #  p, I, I Set to Except, Amort if Master is Except, Amort.
      $colsAA = [
        'Id'        => $Id,
        'Name'      => $Name,
        'Level'     => $Level,
        'Bits'      => $Bits,
        'DataTypeN' => $DataTypeN, # Set from Master in Pass2                I
        'AcctTypes' => $AcctTypes ? $AcctTypes : $masterBroA['AcctTypes'], # p
        'SumUp'     => $SumUp,
        'MasterId'  => $masterBroA['Id']];  # Id
      $Ref   = $Ref   ? : $masterBroA['Ref'];   # /-  Inherited from Master if not set for the Slave
      $Zones = $Zones ? : $masterBroA['Zones']; # |
      $Descr = $Descr ? : $masterBroA['Descr']; # |
      if ($SlaveYear) $colsAA['SlaveYear'] = $SlaveYear;
      ++$numSlave;
    }else
      die("Die - Type if else failed on update for row $RowNum - not Std Bro or Slave");

    if ($dadRow)    $colsAA['DadId']     = $dadA['Id'];
    if ($TxId)      $colsAA['TxId']      = $TxId;
    if ($Hys)       $colsAA['Hys']       = $Hys;      # Can have Hys wo TxId
    if ($UsableDims)$colsAA['UsableDims']= $UsableDims;
    if ($TupId)     $colsAA['TupId']     = $TupId;
    if ($SignN)     $colsAA['SignN']     = $SignN;
    if ($ShortName) $colsAA['ShortName'] = $ShortName;
    if ($Ref)       $colsAA['Ref']       = $Ref;
    if ($PeriodSEN) $colsAA['PeriodSEN'] = $PeriodSEN;
    if ($ExclDims)  $colsAA['ExclDims']  = $ExclDims;
    if ($AllowDims) $colsAA['AllowDims'] = $AllowDims;
    if ($BroDiMesA) $colsAA['BroDiMesA'] = $BroDiMesA;
    if ($check)     $colsAA['CheckTest'] = $check; # TPYtargetBroId
    if ($Zones)     $colsAA['Zones']     = $Zones;
    if ($Descr)     $colsAA['Descr']     = $Descr;
    if ($SortOrder) $colsAA['SortOrder'] = $SortOrder;
    if ($Comment)   $colsAA['Comment']   = $Comment;
    if ($Scratch)   $colsAA['Scratch']   = $Scratch;
    if ($RowComments) $colsAA['RowComments'] = $RowComments;
    if (($id = InsertBro($colsAA)) != $Id)
      die("Die on Id error. BroInfo.Id=$id not $Id as expected");
  }
  $DB->commit(); // Commit
  return sprintf("<p>%s Bros, %s standard (incl %s Masters), and %s Slaves added.</p>", number_format($numStd+$numSlave), number_format($numStd), number_format($numMaster), number_format($numSlave));
}

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
  // Map Taxonomy Element Type to Rg Data Type
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

function UsableDims($Hys, $nHys) {
  global $HyDimsA;
  if ($nHys === 1) # One hypercube
    return $HyDimsA[ChrToInt($Hys)];
  # More than one hypercube so merge the dims
  $usableDims = '';
  for ($h=0; $h<$nHys; $h++)
    $usableDims .= $HyDimsA[ChrToInt($Hys[$h])];
  return IntAToChrList(ChrListToIntA($usableDims)); # IntAToChrList() sorts the dims and eliminates duplicates
}

# IsDuplicateDiMe($thisExclDims, $thisDiMesA, $otherExclDims, $otherDiMesA)
# Function to be called for a pair of Bros with Duplicate TxId.HyId.TupId to see if they could still be unique based on ExclDims or Mandatory DiMes.
# Returns 1 if duplicate meaning same BroRef could be posted, 0 if unique meaning same BroRef could not be posted.
# Depends on an ExclDim being Mandatory or a Mandatory DiMe.
function DupBrosManExclDimsOrManDiMes($thisExclDims, $thisDiMesA, $otherExclDims, $otherDiMesA) {
  return DupBrosManExclDimsOrManDiMes2($thisExclDims, $thisDiMesA, $otherExclDims, $otherDiMesA) && DupBrosManExclDimsOrManDiMes2($otherExclDims, $otherDiMesA, $thisExclDims, $thisDiMesA);
}
# *2() is the work fn which can give a different result when called for (this, other) vs (other, this) so it needs to be called the other way around if the first call returns 0.
function DupBrosManExclDimsOrManDiMes2($thisExclDims, $thisDiMesA, $otherExclDims, $otherDiMesA) {
  global $DiMesA, $DimsWithMtypeDiMesA;
  if ($otherExclDims != $thisExclDims)
    # ExclDims are different. If an extra Excl Dim in one is a mandatory (M#) dim then this is not a duplicate pair
    if ($extra = str_replace(str_split($otherExclDims), '', $thisExclDims))
      # $thisExclDims has $extra not in other Bro's Excl. If one of these is an (M#) dim then this is not a duplicate pair
      for ($i=strlen($extra)-1; $i>=0; --$i) {
        $dimId = ChrToInt($extra[$i]);
        if ($dimId != DimId_Officers && in_array($dimId, $DimsWithMtypeDiMesA)) # Skip Officers because of its special case - leave to DiMes for Officers
          return 0;
      }

  if ($thisDiMesA && $thisDiMesA[II_MandatsA ]) {
    # Not resolved on basis of ExclDims so try Mandat DiMes which this Bro has
    $diMeMandatsA = $thisDiMesA[II_MandatsA ];
    # This Bro has Mandatory DiMes so pair are not duplicates if other Bro has these DiMe excluded either as a result of having different Mandatory DiMes defined, or by having the the Mandataory DiMes excluded
    if ($otherDiMesA) {
      # Other Bro also has DiMes
      if ($otherDiMesA[II_MandatsA ]) {
        # Other Bro also has Mandat DiMes
        $all = 1; # assume all different
        foreach ($diMeMandatsA as $diMeId)
          if (in_array($diMeId, $otherDiMesA[II_MandatsA ]))
            $all = 0; # not all different
        if ($all)
          return 0; # not duplicates as all Mandats different
      }
      if ($otherDiMesA[II_ExcludesA]) {
        # Not resolved on basis of Mandats, try Excludes, which the other Bro has
        $all = 1; # assume all excluded
        foreach ($diMeMandatsA as $diMeId)
          if (!in_array($diMeId, $otherDiMesA[II_ExcludesA]))
            $all = 0; # not all excluded
        if ($all)
          return 0; # not duplicates as all Mandats excluded
      }
    } # end of other Bro has DiMes
    if ($otherExclDims) {
      # Not resolved on basis of DiMes, try ExclDims, which the other Bro has
      $all = 1; # assume all excluded
      foreach ($diMeMandatsA as $diMeId)
        if (!InChrList($DiMesA[$diMeId][DiMeI_DimId], $otherExclDims))
          $all = 0; # not all dims excluded
      if ($all)
        return 0; # not duplicates as all Mandats excluded via ExclDims
    }
  }
  return 1; # No unique condition found so duplicates
}

function ZapTable($table) {
  global $DB;
  $DB->StQuery("Truncate Table $table");
}

function InsertBro($colsAA) {
  global $DB;
  $set = '';
  foreach ($colsAA as $col => $dat) {
    $set .= ",$col=";
    if (is_numeric($dat))
      $set .= $dat;
    else
      $set .= '\'' . $DB->real_escape_string($dat) . '\''; # 'dat'
  }
  # Do the insert which is not expected to fail. (No mysql level unique key fields should come thru here unless the app has guarded against possible duplicate key clashes.)
  $set = substr($set, 1);
  return $DB->InsertQuery("Insert BroInfo Set $set");
}

function BIError($msg) {
  global $IbrosA, $ErrorsA, $Errors, $RowNum;
  ++$Errors;
 #$rtRowNum = str_pad($RowNum, 4, ' ', STR_PAD_LEFT); # so errors sort
 #$ErrorsA[] = "Row $rtRowNum ".substr($RowsA[$RowNum-2], 0, 30)."....&nbsp;&nbsp;$msg"; # -2 cos headings row shifted out and for the 0 base of the array
  if ($Errors<101)
    $ErrorsA[] = [$RowNum, $msg];
  $IbrosA[$RowNum] = -1; # set row to error indicator
 #return 1; # for continue use. Not valid with php 5.4
}

# Error wo setting row to -1
function BIError2($msg) {
  global $IbrosA, $ErrorsA, $Errors, $RowNum;
  ++$Errors;
  if ($Errors<101)
    $ErrorsA[] = [$RowNum, $msg];
 #return 1; # for continue use Not valid with php 5.4
}

# Notices are created before error checks are complete so store the notices to be issued only if there are no errors
function BINotice($msg) {
  global $NoticesA, $RowNum;
 #$NoticesA[] = "In row $RowNum " . substr($RowsA[$RowNum-2], 0, 30) . "....&nbsp;&nbsp;$msg";
  $NoticesA[] = [$RowNum, $msg];
 #return 1; # for continue use Not valid with php 5.4
}

function BIWarning($msg) {
  global $WarningsA, $RowNum;
 #$WarningsA[] = "In row $RowNum " . substr($RowsA[$RowNum-2], 0, 30) . "....&nbsp;&nbsp;$msg";
  $WarningsA[] = [$RowNum, $msg];
}

function Form($prefix='') {
  global $DimSumCheckB;
  $dimSumCheckChecked = ($DimSumCheckB  ? ' checked' : '');

  echo "<div>
<p class=c>{$prefix}Copy Paste the Bros SS up to the Scratch Column into the following Text Box and Click the Import Button:</p>
<form class=c method=post>
<textarea name=Bros autofocus placeholder='Paste SS Bros here' required rows=10 cols=80>
</textarea>
<br><br>
<p><input class=radio type=checkbox name=DimSumCheck value=1$dimSumCheckChecked> Include Check for Usable Dims when Set and StartEnd SumEnd Summing</p>

<button class='c on m10'>Import Bros</button>
</form>
</div>
";
  Footer(false);
}
