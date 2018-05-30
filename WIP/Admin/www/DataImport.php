<?php /* Copyright 2011-2013 Braiins Ltd

DataImport.php utf-8

SIM Data Import for Testing

History:
13.05.13 SIM version started based on UK-GAAP-DPL Import.php
03.07.13 B R L -> SIM

ToDo djh??
====

- DBO only 1 post

- Mux Checks

- Make Set Slave != Master an error? Then could skip storing Set Slaves and recreate them from their Masters as for other Slaves.

- all the djh??s within the code

- SumList() to handle dim 1/2 2/1 mapping as per SumBros()?

- Remove debug echoing

- Handle equivalence check re StartEnd Bros
- Remove die() statements

- Test Slave of a prior year with PYA


BDT Posting
===========
Need to add locking including of Refs

Notes
=====
File:
The files in /Admin/Imports other than .bak ones are presented for a radio button choice. The file is expected to be a text file but may have any extension other than .bak
If the import contains any non-ascii characters e.g. £ ¥ € ® ™ © it should be created as a utf-8 file.

Alternatively, the data may be pasted.

File format:
An import consists of lines or rows of text.

Lines are trimmed i.e. leading and trailing whitespace is discarded.

Other than for the command options listed below, lines which start with a # or which do not have
  a tab character in them, including blank lines, are skipped. The # option allow import lines to
  be commented out. Lines which start with # are not echoed.
  Blank lines and comment lines which do not start with # are echoed.

Mandatory Import Command:
BroSet: nnnnnnn

Optional Import Commands:
No DE Check
Debug: # default 0
StartYear: # default 0
ImportType: <Complete | Replace> default Complete

Data Lines or Rows:
Data Import rows are a Bro Reference, a tab, and tab separated values for up to 4 (0 - 3) years.
 Values can be empty -> no record, not a zero one.
 Spaces between values in addition to tab characters are allowed e.g. for formatting.
 A # immediately after a tab indicates the start of a line comment. It is ignored for input but is echoed in the Comment column.
 Numeric values may include comma thousands separators.
 Enclosing ()s may be used as well as leading - to indicate negative (credit) money values.

Command Explanations:
BroSet: nnnnnnn
nnnnnnn must be the name of a defined MaIn-BroSet.

No DE Check is an optional command which should only be used during development. If it is set an import continues even if the DE postings do not balance. DE out of balance messages still appear.

Debug: # for level 0, 1, 2, or 3 of Debugging output. 0 = none.

StartYear: # is an optional starting year (0 to 6) for the tab separated values in following data rows. The default is 0.
        Only one StartYear: # statement can be used per import.
        If StartYear is > 3 (4 | 5 | 6) it means that this is a Restated import for just that year i.e. a Restated/Pya import can be for only one year with only one data value per row.

ImportType: <Complete | Replace> is an optional command to specify the type of import. The default is Complete.

ImportType impacts how previous entries for a year are handled. If there are no previous entries, i.e. for the first import for a given year then ImportType has no meaning.

When there are previous entries for a year i.e. for a repeat import then:
Complete means:
 The import data is to be a complete set of data for the year. Accordingly:
 C.1 If an import Bro matches a previous Bros (all BroDats), the previous Bro is left untouched.
 C.2 If an import Bro differs from a previous Bro, the previous Bro is updated (replaced)
     and a BroTrans table BDTT_ImportUpdate (Audit Trail) tran generated.
 C.3 If there is no import Bro corresponding to a previous Bro, the previous Bro is updated (set) to empty (BroStr='')
     and a BroTrans table BDTT_ImportUpdate (Audit Trail) tran generated.

Replace means:
 All Bros and BroTrans for any year for which the import includes data, including restated year data if any, are deleted.
 Import for the years with data then proceeds as a fresh import.
 The only record of previous imports for the year(s) with data will be via DBLog.
 Previous years without data in this import are not touched.

A Bro reference takes the form of a Bro Name optionally followed by any number of property member references and/or an end|start property in any order.

See /Com/inc/FuncsPost.inc or /Doc/BrosAndTx/Bros.docx for full Bro Reference details.

*/

# BroRefPost($ref) in FuncsPost.inc needs:
# $GlobalDimGroupExclDimsChrList, and $ESizeId to have been set
# $BroInfoA, $BroNamesA, $BroNamesMapA, $PMemMapA, $PMemsA, $PMemNamesA, $DimGroupsA to have been loaded

require 'BaseBraiins.inc';          # $DimGroupsA via Constants.inc
require Com_Inc.'ConstantsSIM.inc';
require Com_Inc.'FuncsSIM.inc';
require Com_Inc.'ClassBro.inc';     # $PMemsA, $PMemNamesA
require Com_Inc.'FuncsPost.inc';
require Com_Inc.'FuncsBraiins.inc';
require Com_Inc.'DateTime.inc';
require Com_Str.'PMemMapA.inc';     # $PMemMapA
#require $BroSetPath.'DiMeTargetsA.inc';   # $DiMeTargetsA $RestatedDiMeTargetsA

Bro::SetErrorCallbackFn('BError');
Bro::SetNewPostingPMemBroDatCallbackFn('SetDimGroups'); # To callback SetDimGroups when a new posting PMem BroDat is added.

$AgentId  = 1; # Braiins
$EntityId = 2; # AAAAA
$Dir = '../Imports/';

Head('Data Import', true);

$IDebug = false;
if (!isset($_POST['Files'])) {
  $Filei  = 0;
  Form();
  #######
}

echo "<h2 class=c>Importing Data</h2>
";

if (ctype_digit($_POST['Debug']))
  $IDebug = Clean($_POST['Debug'], FT_INT);

if (strlen($_POST['Dat'])) {
  $LinesA = explode(NL, $_POST['Dat']);
  $Filei = 99999; # recorded in the ETA_Import tran
}else {
  # File selected
  $files = Clean($_POST['Files'], FT_STR);
  $Filei = Clean($_POST['File'], FT_INT);

  # $Filei is the index of the selected file in the dir of imp subdir
  $filesA = explode(',', $files);
  $file   = $filesA[$Filei];

  # The Import file
  if (($LinesA = file("$Dir/$file", FILE_IGNORE_NEW_LINES)) == false) {
    echo "File $file not found.<br>";
    Footer(false);
  }
  echo "File $file read.<br>";
  if (strncmp($LinesA[0], "\xEF\xBB\xBF", 3)===0) # Remove UTF-8 BOM if present
    $LinesA[0] = substr($LinesA[0], 3);
}

$EA = $DB->AaQuery("Select ETypeId,ESizeId,Bits,CurrYear,DGsInUse,DGsAllowed From Entities Where Id=$EntityId");
foreach ($EA as $k => $v)
  $$k = (int)$v; # -> $ETypeId, $ESizeId, $Bits, $CurrYear, $DGsInUse, $DGsAllowed as ints
$EBits = $Bits;
# Set up $GlobalDimGroupExclDimsChrList for all cases (U, S) with BroRefPost() to work out which it was if an error occurs
$globalDimGroupExclDimsA = [];
for ($dg=0,$bit=1; $dg<DG_Num; ++$dg, $bit *= 2)
  if (!($DGsAllowed & $bit))
    foreach ($DimGroupsA[$dg][DGI_DimsA] as $dimId)
      $globalDimGroupExclDimsA[] = $dimId;
$GlobalDimGroupExclDimsChrList = IntAToChrList($globalDimGroupExclDimsA);
# And initialise $DGsInUseA
$DGsInUseA = array_fill(0, DG_Num, 0);

const IT_Complete = 1; # ImportType:Complete
const IT_Replace  = 2; # ImportType:Replace

$Errors = $BroSetId = $numStdYears = $startYear = $startYearB = $pyaImportB = $firstDatLen = $NoDEcheck = 0; # $startYearB so that a second StartYear: # command can be detected.
$Debug = $IDebug;
# All the years here are relYears
$importType  = IT_Complete; # default to Complete
$importTypeS = 'Complete';
$BroRefsA    = # Processed BroRefs for data rows [LineNum => BroRefA]
$PrevBrosA   = # previous Bros          [year => [BroId => BrO]] for years with previous data except for years being replaced by an InportType:Replace import
$BrosA       = # import/sum/show Bros   [year => [BroId => BrO]]
$deSumsA     = []; # DE Dr and Cr sums as  [year][BS_Dr|BS_Cr] of entries for a DE balance check

# Year arrays which are all 7 member (i 0 - 6) arrays, initialised here to 0
$importYearsA = # [year => b] set for years with import data
$prevYearsA   = # [year => b] set for years with previous data except for years being replaced in an InportType:Replace import
$priorYearsA  = # [year => prior year #] taking pya years into account, 0 if no prior year which is ok as year 0 is never a prior year
$sumYearsA    = # [year => b] set for years to be summed
$broYearsA    = # [year => b] set for years with BrosA data
$pyaDelYearsA = # [year => b] set for Pya years to be deleted as a result of re-import of corresponding standard year
 array_fill(0, 7, 0);  # Max_RelYear+1 = EData_NumYears + Pya_Year_Offset = 7 years
$retrievePrevYearsiA  =       # Entity years to get from Bros DB table if available, = all years except for import years being replaced [i => EntYear] includes IT_Complete import years
$prevNonImportYearsiA = null; # Rel years read from DB that are not import (IT_Complete) ones [i => RelYear]

###################
# Pass 1, Table 1 #
###################
$Table=1;
# Loop through the import:
# - skip # lines
# - extract commands
# - extract and check BroRefs
# - find number of years involved in the import in $numStdYears if this is a standard years import
# - store processed BroRefs in $BroRefsA by LineNum
# Dump lines if an error is found in this pass, and abort after the pass if so
$prevLineEmpty = 0;
foreach ($LinesA as $LineNum => &$lineR) {
  $lineR = trim(preg_replace('/(  +)/m', ' ', $lineR));       # trim and reduce internal spaces to one space /- djh?? Do in one preg_replace?
  $lineR = preg_replace('/( 	 )|(	 )|( 	)/m', TAB, $lineR); # trim spaces around tabs                      |
  if ($Errors) {
    if (!strncmp($lineR, '#', 1)) { # Output comment lines starting with # without putting into columns
      echo "<tr><td colspan=11>$lineR</td></tr>\n";
      continue;
    }
    if (!strlen($lineR)) { # skip empty lines
      if (!$prevLineEmpty)
        echo "<tr><td colspan=11></td></tr>\n";
      $prevLineEmpty = 1;
      continue;
    }
    if (InStr(TAB, $line))
      echo '<tr><td>'.str_replace(TAB, '</td><td>', $lineR)."</td></tr>\n";
    else
      echo "<tr><td colspan=11>$lineR</td></tr>\n";
    $prevLineEmpty = 0;
  }else
    if (!strncmp($lineR, '#', 1)) # skip lines starting with # without echoing them when in no errors mode
      continue;

  # Remove line comment but leave in lineR for Pass2
  if (($p=strpos($lineR, '	#')) !== false)
    $line = rtrim(substr($lineR, 0, $p));
  else
    $line = $lineR;
  if (!strlen($line)) # skip empty lines
    continue;
  # BroSet: nnnnnn
  if (!strncmp($line, 'BroSet:', 7)) {
    ##########
    # BroSet # BroSet nnnnnn {# Comment}
    ##########
    if ($BroSetId)
      ErrorExit(sprintf('BroSet: command found on line %d but a BroSet: command has already been procssed. A data import works with only one BroSet', $LineNum+1));
      #########

    # Chop off comment if present.
    if ($p = strpos($line, '#'))
      $line = substr($line, 0, $p);
    $nme = trim(substr($line, 7)); # Name
    $msg = "BroSet '$nme' in the BroSet: command on line ".($LineNum+1);
    if (!$bsO = $DB->OptObjQuery("Select Id,BTypeN,TaxnId,Status from BroSets Where Name='$nme'"))
      ErrorExit("$msg is not a known Bro BroSet. Either correct the name or import the '$nme' BroSet and then try this data import again");
    $BroSetId = (int)$bsO->Id;
    if ($bsO->Status != Status_OK)
      ErrorExit("$msg has errors or warnings about missing defined Bros and so cannot be used for data import. Correct BroSet '$nme' and then try this data import again");
      #########
    switch ((int)$bsO->BTypeN) {
      case BroSet_In_Main:  # OK
      case BroSet_Out_Main: # OK
        break;
      case BroSet_In_Incl:  # NBG
      case BroSet_Out_Incl: # NBG
        ErrorExit(sprintf("$msg is an %s BroSet which cannot be used for data import. Only In-Main or Out-MaIn-BroSets can be used.", BroSetTypeStr((int)$bsO->BTypeN)));
        #########
    }
    $BroSetPath = Com_Str."BroSet$BroSetId/";
   #require Com_Inc_Tx.'ConstantsRg.inc';  # $BroSumTypesGA
   #require Com_Str_Tx.'TupLabelsA.inc';   # $TupLabelsA
    require $BroSetPath.'BroSumTreesA.inc';   # $BroSumTreesA $CheckBrosA $SumEndBrosA $PostEndBrosA $StockBrosA
    require $BroSetPath.'BroInfoA.inc';       # $BroInfoA
    require $BroSetPath.'BroNamesA.inc';      # $BroNamesA
    require $BroSetPath.'BroNamesMapA.inc';   # $BroNamesMapA incl ShortNames
    require $BroSetPath.'BroShortNamesA.inc'; # $BroShortNamesA
   #require $BroSetPath.'TuMesA.inc';         # $TuMesA
    continue;
  }

  # StartYear: #
  if (!strncasecmp($line, 'StartYear:', 10)) {
    if (count($BroRefsA))
      BError("$dataRows data row(s) occur before this StartYear: command, but a StartYear: command should come before any data rows");
    if ($startYearB)
      BError('A StartYear: command line has already been processed. There should be only one per import file');
    $startYearB = 1;
    #if ($importTypeSetB)
    #  BError('A StartYear: command line should come before an ImportType: command line, but an ImportType: command line has already been processed');
    $dat = trim(substr($line, 10));
    if (ctype_digit($dat)) {
      if (InRange($dat=(int)$dat, 0, Max_RelYear)) { # 6
        $startYear = $dat;
        $pyaImportB = $startYear > Max_StdRelYear; # 3
      }
      else
        BError('The StartYear: # year should be in the range 0 to 6');
    }else
      BError('StartYear: should be followed by a digit in the range 0 to 6');
    continue;
  }

  # ImportType: Complete
  # ImportType: Replace
  if (!strncasecmp($line, 'ImportType:', 11)) {
    $typesA = ['Complete', 'Replace'];
    if ($importType=Match(trim(substr($line, 11)), $typesA))
      $importTypeS = $typesA[$importType-1];
    else
      BError('Unknown ImportType. ImportType:Complete, or ImportType:Replace expected');
    continue;
  }

  # No DE Check
  if (!strncasecmp($line, 'No DE Check', 11)) {
    $NoDEcheck = 1;
    continue;
  }

  # Debug: #
  if (!strncasecmp($line, 'Debug:', 6)) {
    if ($IDebug===false) $Debug = (int)substr($line, 6);
    continue;
  }

  # Data Row expected
  $colsA = explode(TAB, $line);
  if (count($colsA) < 2) # skip lines without a tab after the command line checks above
    continue;

  if (!$BroSetId)
    ErrorExit('No BroSet: command line found before the first data line. A BroSet: nnnnnn command is mandatory and must come before any data');
    #########
  # Expect a BroRef and the value(s)
  # BroName{,PropPMemRef...}{,<end|start>}	Value(s)
  # |-------------- BroRef ----------------|-- v --...
  if (($broRefA = BroRefPost($colsA[0])) !== false) {  # BroRefPost() returns [BroId, PMemRefsA, RefBits] or false, with PMemRefsA = 0 if no PMems i.e. not []
    $BroRefsA[$LineNum] = $broRefA;
    $years = count($colsA)-1;
    $pya = $broRefA[2] & BRefB_PMemPya; # $broRefA[2]=RefBits
    if ($pyaImportB) {
      # Pya year import
      if ($years > 1)
        BError("Only data for one year is expected for a Restated Import, but this line includes data for $years years");
      else if (!$pya && ($BroInfoA[$broRefA[0]][BroI_Bits] & BroB_Summing)) # $broRefA[0]=BroId
        # Not a Restated posting only ok if for a Non-Summing Bro
        BError('For a Restated Import a Summing Bro reference should include a Restated reference, but that is missing');
    }else{
      # Std year(s) import
      if (($year=$startYear+$years-1) > Max_StdRelYear)
        BError("Only data for years $startYear to 3 is expected, but this line includes data for year $year");
      if ($pya)
        BError('Restated can only be used with a Restated Import');
      $numStdYears = max($numStdYears, $years);
    }
    $firstDatLen = max($firstDatLen, strlen($colsA[1]));
  }
} # end of Pass 1 import line loop
unset($lineR);

if ($Errors) {
  # Had Pass 1 BroRef errors
  echo '</table><p class=b><br>The import failed due to the ',NumPluralWord($Errors, 'error'), " reported above.</p>\n";
  Form(); # time, topB
  #######
}
# End of Pass 1. No BroRef errors

######################
# Pass 2 Preparation #
######################
$Table=0; # Not in a table here re possible Errors
$impYears  = $pyaImportB ? 1 : $numStdYears; # number of years in the import
# Can now set importYearsA and start sumYearsA
$yrs = '';
if ($numStdYears) { # Can have either $numStdYears or 1 Pya Year, not both
  # Std years
  $lastImportYear = $startYear + $numStdYears - 1;
  foreach ([0,1,2,3] as $year) # Std years
    if ($year <= $lastImportYear) {
      $sumYearsA[$year] = $broYearsA[$year] = 1; # For Std years always sum and save up to year 0.
      if ($year >= $startYear) {
        $importYearsA[$year] = 1;
        $yrs .= ", $year";
      }
    }
  $yrs = substr($yrs,2);
}else{
  # Pya year with  $startYear 4 | 5 | 6
  $importYearsA[$startYear] = 1;
  $yrs = YearStr($startYear);
  foreach ([4,5,6] as $year) # Pya years
    if ($year <= $startYear)
      $sumYearsA[$year] = $broYearsA[$year] = 1; # For Pya years always sum and save up to year 4.
}

# Build retrievePrevYearsiA for years not being replaced in an IT_Replace import
# Initialise the DE arrays which involve += and so need a 0 start; and set BrosA for the rare case of no import data e.g. single row :start  test
for ($year=0; $year<=Max_RelYear; ++$year) { # All years
  if ($importType === IT_Complete || !$importYearsA[$year])
    $retrievePrevYearsiA[] = $CurrYear - $year;
  $deSumsA[$year] = [BS_Dr => 0, BS_Cr => 0];
  $BrosA[$year]   = []; # for the rare case of no import data e.g. single row :start  test
}

echo "StartYear: $startYear<br>
ImportType: $importTypeS<br>
Import is for ",PluralWord($impYears, 'year'),": $yrs<br>\n";
if ($NoDEcheck) echo "No DE Check<br>\n";
if ($Debug) { $DebugMsgs=''; echo "Debug: $Debug<br>\n";}

# Read the Bros for years not being replaced in an IT_Replace import i.e. for IT_Complete years and non-import years
#  add to PrevBrosA & set prevYearsA
#  add to BrosA if not an import year
if ($retrievePrevYearsiA) { # All years except for replace years. Unlikely to be empty but possible
  if ($Debug) DebugMsg("Retrieving Previous Data");
  $res = $DB->ResQuery("Select EntYear,BroId,BroStr From Bros Where EntityId=$EntityId And EntYear In".ArrayToBracketedCsList($retrievePrevYearsiA).' Order By EntYear,BroId');
  while ($o = $res->fetch_object()) {
    $year = $CurrYear - (int)$o->EntYear; # relYear in reverse date order
    if ($year > Max_StdRelYear && !$pyaImportB && $importYearsA[$year-Pya_Year_Offset]) {
      # Pya year Bro but re-importing the corresponding standard year so this Pya year's data is to be deleted if the import is a success
      $pyaDelYearsA[$year] = 1;
      continue;
    }
    $prevYearsA[$year] = 1;
    $broId = (int)$o->BroId;
    if ($Debug) DebugMsg("Previous Bro $broId Year $year");
    $brO   = NewBroFromString($broId, $o->BroStr); # SetDimGroups() is called if appropriate via the callback fn. Includes PMem Summing which can be needed for 'd' PyaOA values.
    # add to $PrevBrosA and $BrosA
    $PrevBrosA[$year][$broId] = $brO;
    if (!$importYearsA[$year]) {
      # Not an import year so add previous year data to $BrosA for summing or showing purposes
      if ($Debug) DebugMsg("Previous Bro $broId Year $year copied to BrosA");
      $BrosA[$year][$broId] = $brO->Copy();
      $broYearsA[$year] = 1;
    }
    if ($brO->IsMaster()) {
      foreach ($brO->InfoA[BroI_SlaveIdsA] as $slaveId) { # create the Slaves of this master
        if ($BroInfoA[$slaveId][BroI_Bits] & BroB_Ele) {
          if ($Debug) DebugMsg("Previous Slave Bro $slaveId Year $year copied from $brO->BroId");
          $PrevBrosA[$year][$slaveId] = $slavebrO = $brO->CopyToSlave(new Bro($slaveId));
          if (!$importYearsA[$year]) {
            if ($Debug) DebugMsg("Previous Slave Bro $slaveId Year $year copied to BrosA");
            $BrosA[$year][$slaveId] = $slavebrO->Copy();
          }
        }else
          # Set Slave so just create the empty Bro to be summed to
          $PrevBrosA[$year][$slaveId] = new Bro($slaveId);

      }
    }
  }
  $res->free();
}

# Can now complete year arrays priorYearsA and sumYearsA
# Set priorYearsA working in reverse age order, skipping 3/6 for which there is no prior year
# 0 1 2 3 | 4 5 6 =>
# 2 5
# 1 4
# 0    -> 5 2 4 1 0 order
$anySumYear = 0; # set once any year has been set to sum via a prior year being set for summing -> all subsequent years set to sum, re prior year slaves
foreach ([5,2,4,1,0] as $year) {
  $priorYear = $year + 1;   # 6,3,5,2,1   1,2,3, 5,6
  if ($year > Max_StdRelYear) { # 3
    # Pya years 5,4
    if ($broYearsA[$priorYear]) # year: 5,4 -> priorYear: 6,5
      $priorYearsA[$year] = $priorYear;
    else if ($broYearsA[$priorYear-Pya_Year_Offset]) { # 3,2
      $priorYear = $priorYear+Pya_Year_Offset; # 3,2
      $priorYearsA[$year] = $priorYear;
    }
  }else{
    # Std years 3,2,1
    if ($broYearsA[$priorYear+Pya_Year_Offset]) { # 3,2,1 -> 6,5,4
      $priorYear = $priorYear+Pya_Year_Offset; # 6,5,4
      $priorYearsA[$year] = $priorYear;
    }else if ($broYearsA[$priorYear]) # 3,2,1
      $priorYearsA[$year] = $priorYear;
  }
  if ($anySumYear || $sumYearsA[$priorYear]) $sumYearsA[$year] = $anySumYear = 1;
}

foreach ($broYearsA as $year => $b)
  if ($b && !$importYearsA[$year])
    $prevNonImportYearsiA[] = $year; # years read from DB that are not import (IT_Complete) ones

# Debug
if ($Debug) {
  function ArrayToStr($aA) { return ($aA ? implode(' ', $aA) : ''); }
  DebugMsg("<br>CurrYear=$CurrYear, impYears=$impYears");
  DebugMsg('importYearsA = '. ArrayToStr($importYearsA));
  DebugMsg('broYearsA = '.    ArrayToStr($broYearsA));
  DebugMsg('sumYearsA = '.    ArrayToStr($sumYearsA));
  DebugMsg('prevYearsA = '.   ArrayToStr($prevYearsA));
  DebugMsg('priorYearsA = '.  ArrayToStr($priorYearsA));
  DebugMsg('pyaDelYearsA = '. ArrayToStr($pyaDelYearsA));
  DebugMsg('retrievePrevYearsiA = '.ArrayToStr($retrievePrevYearsiA)).
  DebugMsg('prevNonImportYearsiA = '. ArrayToStr($prevNonImportYearsiA).'<br>');
}

# Copy Std year Bros to Pya year Bros if required i.e. if the Pya year is to be summed and Std year data exists
#  Pya postings are the increase/decrease in the balance, not the restated amount.
#  Restated entries and summing is handled at the end of the summing loop as a special case.
foreach ([4,5,6] as $pyaYear) # 4, 5, 6
  # If the pya year is to be summed and data exists for the Std year copy the Std year Bros to the Pya year as the start for the Pya year
  if ($sumYearsA[$pyaYear])
    if ($prevYearsA[$year = $pyaYear - Pya_Year_Offset]) {
      if ($Debug) DebugMsg("Copying year $year Bros to Pya year $pyaYear");
      foreach ($BrosA[$year] as $broId => $brO) # thru the std year Bros
        $BrosA[$pyaYear][$broId] = $brO->Copy(); # Copy std year Bro to Pya year
    }else
      BError('There is no Standard Year $year data available for a %s Import so this Restated Import cannot proceed');

if ($Errors) {
  echo '<p class=b><br>The import failed due to the ',NumPluralWord($Errors, 'error'), " reported above.</p>\n";
  Form(); # time, topB
  #######
}

if ($Debug) { echo $DebugMsgs; $DebugMsgs = ''; ResultsTable('After Preparation and Reading of Previous Data, before Import'); DebugMsg("Import Pass");}

$Table = 2;
Bro::SetErrorCallbackFn('StackMsg'); # re possible duplicate posting errors
# Start import dump table
#       Data Import Row                    DE Running Bals        Processed BroRef
# BroRef   Year 0   Year 1  Year ...   Year 0   Year 1   Year ... if different
$firstWidth = max(min($firstDatLen*10, 200), 75);
$yearsHdg = $yearsDeHdg = '';
foreach ($importYearsA as $year => $b)
  if ($b) {
    $year = YearStr($year);
    if (!$yearsHdg)
      $yearsHdg = "<td><div class=c style='width:{$firstWidth}px'>$year</div></td>";
    else
      $yearsHdg .= "<td class=c>$year</td>";
    $yearsDeHdg .= "<td class=c>$year</td>";
  }
$diSpan = 1 + $impYears;
echo "<table>
<tr class='b bg0'><td colspan=$diSpan class=c>Data Import Row</td><td colspan=$impYears class=c>DE Running Balances</td><td rowspan=2>Processed BroRef if different</td><td rowspan=2>Comments</td></tr>
<tr class='b bg0'><td>BroRef</td>", $yearsHdg, $yearsDeHdg, '</tr>
';
##########
# Pass 2 # through the data rows via the parsed BroRefs array to extract values
##########
foreach ($BroRefsA as $lineNum => $broRefA) { # $BroRefsA = Processed BroRefs for data rows [LineNum => BroRefA]
  $row = $LinesA[$lineNum]; # has been trimmed etc
  if (($p=strpos($row, '	#')) !== false) {
    $comment = substr($row, $p+2);
    $row = rtrim(substr($row, 0, $p));
  }else
    $comment = '';

  $colsA = explode(TAB, $row);
  $srceBroRef = array_shift($colsA);  # source BroRef shifted off leaving just the values in $colsA
  list($broId, $pMemRefsA, $refBits) = $broRefA;# [BroId, PMemRefsA, RefBits] with PMemRefsA = 0 if no PMems i.e. not []
  $bName     = $BroNamesA[$broId];
  $broA      = $BroInfoA[$broId];
  $dataTypeN = $broA[BroI_DataTypeN];
  $bits      = $broA[BroI_Bits];
  $deBro     = $bits & BroB_DE; #  Posting Type for Money Bros: Set if is a DE or CoA type. Unset = Schedule
  if ($refBits & BRefB_PMemRef) {
    # Convert any HashRefs to Refs.RefId form
    foreach ($pMemRefsA as $i => $pMemRef)
      if (is_string($pMemRef)) {
        # pMemId:Id for D, I, R, C, U, X, L types
        # pMemId:#  for Ee and Ei types
        list($pMemId, $ref) = explode(':', $pMemRef); # $ref is HashRef for Ee and Ei types, TableId for the others
        $pMemId = (int)$pMemId;
        $tableN = $PMemsA[$pMemId][PMemI_RefTableN];
        if ($tableN === T_B_ERefs) { # Ei or Ee which can be defined during posting
          if (!$tableId = $DB->OneQuery("Select Id from ERefs Where Ref='$ref' And EntityId=$EntityId"))
            # Not in ERefs so add it
            $tableId = $DB->InsertQuery("Insert ERefs Values(Null, $EntityId, $pMemId, '$ref', $DB->TnS)");
        }else
          $tableId = (int)$ref;
        if (!$refId = $DB->OneQuery("Select RefId from Refs Where EntityId=$EntityId And PMemId=$pMemId And TableId=$tableId")) {
          # Not already in Refs so add it
          # Need the double subquery with a subquery in the From to get around the MySQL subquery restriction on selecting from the same table that is being updated
          # See http://dev.mysql.com/doc/refman/5.1/en/subquery-restrictions.html
         #$DB->InsertQuery("Insert Refs Values(Null, $EntityId, (Select RefId+1 From Refs Where EntityId=$EntityId Order by RefId Desc Limit 1), $pMemId, $tableN, $ref, $DB->TnS)");
          $DB->InsertQuery("Insert Refs Values(Null, $EntityId, (Select * From (Select RefId+1 From Refs Where EntityId=$EntityId Order by RefId Desc Limit 1) as T), $pMemId, $tableN, $tableId, $DB->TnS)");
          $refId = $DB->OneQuery("Select RefId from Refs Where EntityId=$EntityId And PMemId=$pMemId And TableId=$tableId");
        }
        $pMemRefsA[$i] = "$pMemId:$refId"; # PMemId:RefId
      }
    $broRef = "$broId,".implode(',', $pMemRefsA);
  }else
    $broRef = $pMemRefsA ? "$broId,".implode(',', $pMemRefsA) : $broId;

  # op for the Bro Add Data calls. Usually BroAddDataOp_Unique but BroAddDataOp_Replace in case of Pya post to a Non-Summing Bro e.g. a string Bro
  $op = ($pyaImportB && !($bits & BroB_Summing)) ? BroAddDataOp_Replace : BroAddDataOp_Unique;

  $broDatType = ($refBits & BRefB_Start) ? BroDatT_Start : BroDatT_End;

  if ($bits&BroB_Set) {
    # Determine the Set BroId range for down the tree Mux checking.
    $setMinId = $broId+1;
    $setMaxId = $broA[BroI_SetMaxId];
  }
  $rowh = "<tr class=r><td class=l>$srceBroRef</td>";
  $deh  = '';
  $cells = $skip = 0;
  $class=''; # default for right, only string -> left
  $broDatO = false;
  foreach ($colsA as $year => $dat) {
    if (strlen($dat)) {
      $year = $startYear + $year;
      $src  = $dat;
      $dat2 = '';
      if (($refBits & BRefB_Start) && $priorYearsA[$year]) {
        StackMsg(sprintf('Year %s value <i>%s</i> ignored as the Start value will be obtained from the prior year end value', YearStr($year), $dat), 1); # 1 = notice
        if ($deBro) {
          if ($skip) $deh .= "<td colspan=$skip></td>";
          $deh .= '<td></td>';
        }
      }else{
        # ok i.e. not a start skip
        $yrBrOsRA   = &$BrosA[$year];    # Bros for the year so far    [BroId => Bro]
       #$prevBrosRA = &$PrevBrosA[$year]; # previous Bros for the year  [BroId => Bro] with array empty if none
        switch ($dataTypeN) {
          case DT_String:
            # Stringify the html/xml special characters & < > ' "
            $dat = htmlspecialchars($dat, ENT_QUOTES);
            $class = ' class=l';
            break;
          case DT_Integer:
          case DT_Enum:
          case DT_Share:
            if (ctype_digit($dat))
              $dat = (int)$dat;
            else
              StackMsg(sprintf("Year %s <i>$dat</i> value not valid numeric data for Bro <i>$bName</i> which has a DataType of %s", YearStr($year), DataTypeStr($dataTypeN)));
            $src=$dat;
            break;
          case DT_MoneyString:
            if (($refBits & BRefB_PMemERef) || InStr('|', $dat)) { # string not mandatory if no Ei or Ee member used
              # Expect money # | string
              $tA = explode('|', $dat);
              if (count($tA) !== 2) {
                StackMsg(sprintf("Year %s <i>$src</i> value not valid 'money | string (narrative)' data for Bro <i>$bName</i> as required for a Bro with a MoneyString DataType when used with an Ei or Ee type Property Member", YearStr($year)));
                $tA = [1, 't'];
              }else if (!$tA[1])
                StackMsg(sprintf("Year %s <i>$src</i> value does not include string (narrative) data for Bro <i>$bName</i> which is required for a Bro with a MoneyString DataType when used with an Ei or Ee type Property Member", YearStr($year)));
              $dat  = $tA[0];
              $dat2 = '|'.$tA[1];
            }
            # fall thru for dat as Money
          case DT_Money:
            if (($dat=FormattedNumberToInt($dat)) !== false) { # FormattedNumberToInt() changes enclosing ()s to leading - and strips thou seps
              $src = $dat.$dat2;
              if ($deBro) {
                $deSumsA[$year][BS_Dr + ($dat<0)] += $dat;
                if ($skip) $deh .= "<td colspan=$skip></td>";
                $deh .= sprintf('<td>%s</td>', number_format($deSumsA[$year][BS_Dr]+$deSumsA[$year][BS_Cr]));
              }
            }else{
              StackMsg(sprintf("Year %s <i>$src</i> value not valid integer numeric data for Bro <i>$bName</i> which has a DataType of %s", YearStr($year), DataTypeStr($dataTypeN)));
              if ($deBro) {
                if ($skip) $deh .= "<td colspan=$skip></td>";
                $deh .= '<td></td>';
              }
            }
            break;
          case DT_Decimal:
          case DT_PerShare:
          case DT_Percent:
            if (is_numeric($dat = str_replace(IS_ThouSep, '', $dat))) # remove thou sep
              $dat = (int)round($dat * 10000); # 4 places of implied decimals
            else
              StackMsg(sprintf("Year %s <i>$dat</i> value not valid numeric data for Bro <i>$bName</i> which has a DataType of %s", YearStr($year), DataTypeStr($dataTypeN)));
            break;
          case DT_Date:
            if (!$dat = StrToDate($dat))
              StackMsg(sprintf("Year %s date not valid for Bro <i>$bName</i> which has a date DataType", YearStr($year)));
            break;
          case DT_Boolean:
            switch (strtolower($dat)) {
              case '0':
              case 'n':
              case 'no':
              case 'false': $dat = 0; break;
              case '1':
              case 'y':
              case 'yes':
              case 'true':  $dat = 1; break;
              default: StackMsg(sprintf("Year %s requires one of 0, n, no, false, 1, y, yes, true (case insensitive) as Bro <i>$bName</i> has a DataType of boolean", YearStr($year)));
            }
            break;
          default: StackMsg("Unknown DataTypeN $dataTypeN for Bro $bName");
        }
        if ($yrBrOsRA) {
          # Check for Mux unless first entry in year. All Bros (entries) are primary at this point except for BroDatSrce_b b Base = sum of PMems ones.
          # #############
          # Bro Set Mux
          # -----------
          # All the way up and down the tree
          # Check up the tree, which applies for both Set and Element postings
          # Reject if there is a posting to a dad Bro    djh?? Add SumUp check
          for ($tBroA = $broA; $dadId = $tBroA[BroI_DadId]; $tBroA = $BroInfoA[$dadId])
            foreach ($yrBrOsRA as $broId2 => $brO2) # for each Bro in the year so far
              if ($broId2 === $dadId && $brO2->HasPosting())
                  StackMsg(sprintf('Year %s posting is invalid as a posting exists to the mutually exclusive ancestor Set %s', YearStr($year), $brO2->BroRefFull()));
          # Check down the tree for posting to a Set Bro i.e. all descendants.
          if ($bits & BroB_Set)   #  djh?? Add SumUp check
            foreach ($yrBrOsRA as $broId2 => $brO2) # for each Brory in the year so far
              if ($broId2 >= $setMinId && $broId2 <= $setMaxId)
                StackMsg(sprintf('Year %s posting is invalid as a posting exists to the mutually exclusive descendant %s', YearStr($year), $brO2->BroRefFull()));
          # PMem Mux
          # --------
          if ($refBits & BRefB_PMemMux)  # if this entry incudes a member that is part of a Mux List
            foreach ($pMemRefsA as $diMeId) # for all the entry's PMems djh?? Check re Ref
              if ($PMemsA[$diMeId][PMemI_MuxListA]) # if this PMem has a Mux List
                foreach ($PMemsA[$diMeId][PMemI_MuxListA] as $muxDiMeId) # for each of the Mux list PMems
                  foreach ($yrBrOsRA as $broId2 => $brO2)    # for each Bro in the year so far
                    foreach ($brO2->PrimaryPMemBroDatAs() as $datA) # [BroDatKey => [0 => Bal, 1 => PMemRefsA]] for all the Primary PMem BroDats i.e. incl Start ones
                      foreach ($datA[1] as $diMeId2)  # for each PMem in the prior PMem posting
                        if ($diMeId2 == $muxDiMeId)
                          StackMsg(sprintf("Year %s posting using Member {$PMemNamesA[$diMeId]} ($diMeId) is invalid as a posting to <i>%s</i> uses the mutually exclusive Member {$PMemNamesA[$diMeId2]} ($diMeId2)", YearStr($year), $brO2->BroRefFull()));
          /* 24.11.12
          # NoDiMe vs PMem Mux
          # ------------------
          if ($pMemRefsA) {
            # PMem posting
            foreach ($yrBrOsRA as $broId2 => $brO2) # for each Bro in the year so far
              if ($broId2 == $broId && !$brO2->NumEndDiMes())
                StackMsg("Year $postYear posting with dimensions is invalid as a posting exists to <i>".$brO2->BroRefFull().'</i> without dimensions');
          }else{
            # NoDiMe
            foreach ($yrBrOsRA as $broId2 => $brO2) # for each Bro in the year so far
              if ($broId2 == $broId && $brO2->NumEndDiMes())
                StackMsg("Year $postYear posting with no dimensions is invalid as a posting exists to <i>".$brO2->BroRefFull().'</i> using dimensions');
          } */
        }
        # Add to $BrosA[] as one BroDat with all PMems. SetDimGroups() is called if appropriate via the callback fn, unless Inst has not been determined
        if (!isset($yrBrOsRA[$broId]))
          $yrBrOsRA[$broId] = new Bro($broId);
        $broDatO = $yrBrOsRA[$broId]($broDatType, $dat.$dat2, BroDatSrce_P, $pMemRefsA, $op); # add data ($broDatTypeOrBroDatKey, $dat, $srceN, $pMemRefOrPMemRefsA=0, $op=BroAddDataOp_Unique) Any duplicate posting errors will be stacked via Bro ErrorCallbackFn = 'StackMsg';
      } # end of dat to process block
      # Add to row html
      $cells += $skip + 1;
      if ($skip) {
        $rowh .= "<td colspan=$skip></td>";
        $skip = 0;
      }
      if (is_int($src))
        $rowh .= '<td>'.number_format($src).'</td>';
      else
        $rowh .= "<td$class>$src</td>";
    }else
      # null dat
      ++$skip;
  } # end cols loop in Pass 2
  if ($cells < $impYears) {
    $t = sprintf('<td colspan=%d></td>', $impYears-$cells);
    $rowh .= $t;
    if ($deh) $deh .= $t;
  }
  $rowh .= $deh ? : "<td colspan=$impYears></td>";
  # Processed BroRef col
  $procBroRef = $broDatO !== false ? $broDatO->BroRefSrce() : '';
  $rowh .= (str_replace(SP, '', $procBroRef) === str_replace(SP, '', $srceBroRef) ? '<td></td>' : "<td class=l>$procBroRef</td>");
  if ($Debug<2) echo $rowh."<td class=l>$comment</td></tr>\n";
  StackMsg(); # output errors if any stacked
} # end Pass 2 loop
unset($yrBrOsRA); #, $prevBrosRA);
echo '</table>
';

if ($Debug) { echo $DebugMsgs; $DebugMsgs = ''; }

##########
# Checks #
##########
$Table=0;
Bro::SetErrorCallbackFn('BError'); # re possible errors

# Mandatory Tuple Member Checks
# $tupInstsA   = # Tuple Instances in use [BroId => [year => [i => Inst]]]
/* djh??
foreach ($TuMesA as $tuMeManId => $tuMeA) # [TupId, Ordr, TUCN] TuMeI_TupId, TuMeI_Ordr, TuMeI_TUCN.
  if ($tuMeA[TuMeI_TUCN] === TUC_M) { # Now $tuMeManId really is a Mandatory TuMeId
    $tupWithManTuMeId = $tuMeA[TuMeI_TupId]; # TupId of the Mandatory TuMeId
    foreach ($tupInstsA as $broId => $yearInstsA)
      if ($TuMesA[$BroInfoA[$broId][BroI_TuMeId]][TuMeI_TupId] === $tupWithManTuMeId) {
        # The Tuple with Mandatory TuMeId is in use so check that each instance includes the Mandatory TuMeId
        # Find the Bro for the Mandatory TuMeId $tuMeManId
        foreach ($BroInfoA as $broId => $broA)
          if ($broA[BroI_TuMeId] === $tuMeManId)
            break;
        foreach ($yearInstsA as $year => $instsA)
          foreach ($instsA as $inst)
            if (!isset($tupInstsA[$broId][$year]) || !in_array($inst, $tupInstsA[$broId][$year]))
              BError(sprintf("A post to $BroNamesA[$broId],T.$inst in %s is required to provide the mandatory Tuple Member $tupWithManTuMeId,$tuMeManId (%s, %s) since other members of the Tuple set are in use.", YearStr($year), $TupLabelsA[$tupWithManTuMeId], TupMemLabel($tuMeManId)));
      }
  }

function TupMemLabel($tuMeId) {
  global $DB;
  $txDB = DB_Tx;
  return $DB->StrOneQuery("Select T.Text From $txDB.TuplePairs P Join $txDB.Elements E on E.Id=P.MemTxId Join $txDB.Text T on T.Id=E.StdLabelTxtId Where P.Id=$tuMeId");
}

*/

if ($Errors) {
  # Had Pass 2 errors
  echo '<p class=b><br>The import failed due to the ',NumPluralWord($Errors, 'error'), " reported above.</p>\n";
  Form(); # time, topB
  #######
}

# Import OK or OK with Warnings
# Finished with some arrays:
unset($LinesA, $BroRefsA);

############################
# Summing Data Preparation #
############################

###########
# Summing # Through the years to be summed moving forwards in time = decreasing Rel Years
###########

if ($Debug) ResultsTable('After import and at beginning of Summing');

$WarningsA=['Num' => 0, 'Msg' => ''];
# for ($year=Max_RelYear; $year>=0; --$year) { # 6 -> 0 No. Order could be wrong re Pya Prior years
# Sum in reverse age order, taking pya years into account
# 3 6
# 2 5
# 1 4
# 0    -> 6 3 5 2 4 1 0 order
foreach ([6,3,5,2,4,1,0] as $year) {
  if (!$sumYearsA[$year]) continue;
  # Year to be summed
  if ($Debug) echo "<br><b>***** Year $year Summing</b><br>";

  Bro::SetIsPyaYearB($year > Pya_Year_Offset);

  $yrBrOsA = $BrosA[$year];
  $yearStr = YearStr($year);

  # Sum by Summing DataType
  # All Primary entries until after set summing and summing loop
  foreach ($BroSumTypesGA as $dataTypeN) { # [DT_Integer, DT_Money, DT_Decimal, DT_Share] = [2,3,4,8];
    if ($Debug) { if ($dataTypeN != DT_Money) continue; if ($Debug) echo "<p class='b mt10 mb0'>Summing ", DataTypeStr($dataTypeN), " $yearStr</p>\n"; }
    # Build $BrosThisDataTypeA as an array of Bro use for this DataType: [BroId => 1]
    $BrosThisDataTypeA = null;
    foreach ($yrBrOsA as $broId => $brO)
      if ($brO->DataTypeN === $dataTypeN || ($brO->DataTypeN === DT_MoneyString && $dataTypeN === DT_Money))
        $BrosThisDataTypeA[$broId] = 1;
    if ($Debug>=2) {
      echo "At beginning of year $year Summing<br>";
      if ($Debug==3) Dump("yrBrOsA at beginning of year $year Summing", $yrBrOsA);
      Dump("BrosThisDataTypeA at beginning of year $year Summing",$BrosThisDataTypeA);
    }

    ######### Get Element Non-Slave Primary StartEnd Start Balances from the prior period End values if a prior year exists. Only applies to Money case.
    # Start # Set values are left to Summing
    ######### Already have first year of data Start values in $yrBrOsA
    if ($dataTypeN === DT_Money && $priorYear = $priorYearsA[$year]) {
      if ($Debug) Debug("StartEnd Start Processing, copying Money Element Non-Slave End values from Year $priorYear to Start values in $yearStr");
      # Prior year has data. Pass through it looking for End values. Could make this Element only and leave Set ones to Summing unless Set is StartEnd and kids are not e.g. Tx542 but don't have "kids are not" info in $BrosInfoA
      foreach ($BrosA[$priorYear] as $seBroId => $priorSeBrO) {
        if ($priorSeBrO->IsStartEnd() &&  # is StartEnd Bro
          #($priorSeBrO->IsEle() || $priorSeBrO->IsSetWithNoStartEndKids()) &&  # Element or a Set without any StartEnd kids e.g. Tx542. djh?? Instead of this allowed add data replace to accept changing a PE BroDat to a Sum one.
           !$priorSeBrO->IsSlave()    &&  # is not a Slave djh?? Could or should this be IsEleSlave()?
            $priorSeBrO->HasPrimary())    # includes Primary data
          foreach ($priorSeBrO->EndPrimaryBroDatOs() as $datO) { # [BroDatKey => BroDatO] of End Primary BroDats
            $seBrO = isset($yrBrOsA[$seBroId]) ? $seBrO = $yrBrOsA[$seBroId] : $yrBrOsA[$seBroId] = new Bro($seBroId);
            $BrosThisDataTypeA[$seBroId] = 1;
            $seBrO($datO->BroDatType - BroDatT_Start, $datO->Dat, BroDatSrce_PE, $datO->PMemRefsA); # add data to Bro as a Start BroDat ($broDatTypeOrBroDatKey, $dat, $srceN, $pMemRefOrPMemRefsA=0, $op=BroAddDataOp_Unique)
            if ($Debug) DebugMsg('End Bro '.$datO->BroRef()." in year $priorYear with value = $datO->Dat -> {$seBroId}s as Start value");
            if ($importYearsA[$year] && ($seBrO->InfoBits & BroB_DE) && $seBrO->InfoA[BroI_PeriodSEN] === BPT_InstSumEnd) {
              $deSumsA[$year][BS_Dr + ($bal<0)] += $bal;
              if ($Debug) {$rb=$deSumsA[$year][BS_Dr]+$deSumsA[$year][BS_Cr]; echo "Start DE Year $year $startBroRef Bal=$bal RB=$rb source=",$datO->Source(),'<br>';}
            }
          }
      }
    }

    if (!$BrosThisDataTypeA)
      continue; # No data to sum for this DataType

    if (!$Debug) echo "<p class='b mt10 mb0'>Summing ", DataTypeStr($dataTypeN), " $yearStr</p>\n";
    # Have data with Start processing done.
    Warning(true, $yearStr, $dataTypeN); # call to seed the Warning() heading
    if ($SumEndBrosA[$dataTypeN]) {
      ##########
      # SumEnd # Sum the Element Non-Slave SumEnd Bro End Balances via their SumList
      ##########
      Debug("SumEnd Summing year $year");
      foreach ($SumEndBrosA[$dataTypeN] as $tarBroId => $sumListA) { # [BroId => [i => BroId to sum]]
        # SumEnd Bro
        # First the Start values
        # Build the beginning of the sum in $sumA from the Start values for passing to SumBros() to complete the summing of the SumList
        $sumA = [];
        if (isset($yrBrOsA[$tarBroId]))
          foreach ($yrBrOsA[$tarBroId]->StartPrimaryBroDatAs() as $broDatKey => $datA) {  # [BroDatKey => [0 => Bal, 1 => SrceN]] for the Start Primary BroDats, only Pya BroDats when in a Pya year
            AdjustBroDatKey($broDatKey, BroDatT_Start); # Start -> normal i.e. End
            $sumA[$broDatKey] = $datA; # [BroDatKey => [0 => Bal, 1 => SrceN]]
            if ($Debug) DebugMsg("SumEnd Summing year $year $tarBroId,$broDatKey Bal $datA[0] created");
          }
        # Now the SumList
        if ($Debug && count($sumA)) DebugMsg( "SumEnd call to SumBros for $tarBroId");
        SumBros($tarBroId, $sumListA, BroDatSrce_SE, $sumA); # function SumBros($tarBroId, $broIdsA, $sumType, $sumA=[])
      }
      Debug();
    }
    if ($PostEndBrosA[$dataTypeN]) {
      ###################
      # Set PostEnd Mvt #
      ###################
      # 1614 Tx 542  PostEnd 2646,2648,2647,2649  PostEnd Bros 5228,5229,5230,5231
      # 5285 Tx 1110 PostEnd  558,2655,2657,2656  PostEnd Bros 5286,5287,5288,5289
      # Allocate the movement in the PostEnd Bro (End - Start) to the first of the list that is empty (minus the others),
      # or give a warning if all in the list have values but their sum does not equal the movement.
      Debug("PostEnd Processing Year $year");
      foreach ($PostEndBrosA[$dataTypeN] as $seBroId => $listA) {
        # If the PostEnd Bro is a Set, Set Sum it before anything else. Tx542 is such a Set with members Tx575 and Tx541.
        # NB: But this wouldn't work if multiple levels are involved. Could then move this inside the summing loop.
        if (isset($BroSumTreesA[$dataTypeN][$seBroId]))
          SumBros($seBroId, $BroSumTreesA[$dataTypeN][$seBroId], BroDatSrce_S); # function SumBros($tarBroId, $broIdsA, $sumType, $sumA=[])
        # Mvt = End - Start
        if ($mvtA = Movement($seBroId)) { # false if nothing
          if ($Debug) DebugMsg("PostEnd Bro $seBroId has Movement of: ".var_export($mvtA, true));
          # If any in list is zero or empty set as Mvt - the sum of the others
          $done = 0;
          foreach ($listA as $i => $broId)
            if (IsBroZero($broId)) {
              if ($Debug) DebugMsg("PostEnd Bro $seBroId list member $broId is zero/undefined so set $broId to Movement - list sum");
              unset($listA[$i]);
              SetBroToAminusSumList($broId, $mvtA, $listA);
              $done=1;
              break;
            }
          if (!$done && ($sum = BalsEqualSum($mvtA, $listA))!==true)
            # None of the list members are zero/empty and their sum is not == Mvt as should be the case. Only get a PostEnd sum warning re this case. Diff way of expressing it.
            Warning($seBroId, ['BN', $mvtA[0], implode(',', $listA), $sum], # sum is the base sum only
                    "PostEnd Bro $seBroId %s has movement of %s and all of its SumList (%s) members have balances but their sum %s does not equal the movement");
        }
      }
      Debug();
    }
    if ($StockBrosA[$dataTypeN]) {
      ################# Set the Stock movements for non-set StartEnd Stock Bros.
      # Set Stock Mvt # The Stock Mvt Bro, like any SumList member, is not a slave and so can be processed here before Master -> Slave copying in the summing loop.
      ################# Bros Import checks that Stock SE Bros and Mvt Bros match as to Set/Ele/Main type
      Debug("Stock Movements Year $year");
      foreach ($StockBrosA[$dataTypeN] as $seBroId => $mvBroId) # [BroId => Stock Movement BroId]. Only ever one of them.
        if (!($BroInfoA[$seBroId][BroI_Bits] & BroB_Set)) {
          # Stock not a Set
          # Movement = Start + PL End  or Start - BS End (as assumed here with Stock expected to be posted as Dr)  or -(BS End – Start) = - normal Mvt of End -  Start
          if ($mvtA = Movement($seBroId)) { # false if nothing
            if ($Debug) DebugMsg("Stock Bro $seBroId has Movement of: ".var_export($mvtA, true));
            foreach ($mvtA as $broDatKey => $bal) { # End Base or End PMem, no Start
              $bal = -$bal;
              if ($broDatKey === BroDatT_End) {
                $srceN = count($mvtA)>1 ? BroDatSrce_b : BroDatSrce_S;
                if ($Debug) echo "StartEnd Stock Movement $mvBroId bal $bal created<br>";
              }else{
                $srceN = BroDatSrce_S;
                if ($Debug) echo "StartEnd Stock Movement $mvBroId,$broDatKey bal $bal created<br>";
              }
              if ($importYearsA[$year]) {
                $deSumsA[$year][BS_Dr + ($bal<0)] += $bal;
                if ($Debug) {$rb=$deSumsA[$year][BS_Dr]+$deSumsA[$year][BS_Cr]; echo "DE - Stock Mvt Year $year $seBroId Bal=$bal RB=$rb<br>";}
              }
              AddData($mvBroId, $broDatKey, $bal, $srceN); # AddData($broId, $broDatKey, $dat, $srceN)
            }
          }
        }
      Debug();
    }

 /* Need to copy masters to slaves
     - before summing as some sums include both std and slave bros
     - but also after summing as some masters (sets) won't have their values until after summing
     - then summing again for sums involving slaves
    => Loop until summing doesn't result in any changes */
    $WarningsRestoreA = $WarningsA;
    for ($sumPass=1; true; ++$sumPass) { # Infinite loop check based on $sumPass=== 9 is done at the end
      Bro::ResetChanges();
      if ($Debug) echo '<br>', DataTypeStr($dataTypeN), " $yearStr summing loop $sumPass<br>";
      $WarningsA = $WarningsRestoreA;

      ################################
      # Copy Master values to Slaves #
      ################################
      Debug("Copying Masters to Slaves Year $year on summing loop $sumPass");
     #foreach ($BrosThisDataTypeA as $broId => $t) No because could have a prior year master re Year Slaves that is not a master in this year.
      foreach ($BroInfoA as $broId => $broA) # djh?? Speed this by having an array of Master Bros?
        if (($broA[BroI_Bits] & BroB_Master) && $broA[BroI_DataTypeN] === $dataTypeN) {
          # Master Bro of right DataType
          foreach ($broA[BroI_SlaveIdsA] as $slaveId) { # through the slaves of this master
            if ($BroInfoA[$slaveId][BroI_Bits] & BroB_Set) continue; # skip set slaves.
            if (isset($BroInfoA[$slaveId][BroI_SlaveYear])) {
              # Slave is a prior year slave
              $slaveYearBefore = $year + $BroInfoA[$slaveId][BroI_SlaveYear] - 1; # 0-8 given 1-3 range for SlaveYear. 'Before' so $priorYearsA can be used to get the prior year which could be a std year or a pya year
              if ($slaveYearBefore>=Max_RelYear  || # OoR year or Max_RelYear which never has prior data
                  !$priorYearsA[$slaveYearBefore]) # prior year not available
                continue; # No data available for the slave year
              $slavePriorYear = $priorYearsA[$slaveYearBefore];
              if (!isset($BrosA[$slavePriorYear][$broId])) continue; # No data available for the master of this slave in $slavePriorYear
              $masterBrO = $BrosA[$slavePriorYear][$broId];
            }else{
              # Current year slave
              if (!isset($yrBrOsA[$broId])) continue; # No data available for the master of this slave
              $masterBrO = $yrBrOsA[$broId];
              $slavePriorYear = 0;
            }
            if (isset($yrBrOsA[$slaveId])) { # expected on repeat summing loop
              $slaveBrO = $yrBrOsA[$slaveId];
              if ($Debug) DebugMsg("Copying Master Bro $masterBrO->BroId (slavePriorYear=$slavePriorYear) to Slave $slaveId which exists");
            }else{
              $BrosThisDataTypeA[$slaveId] = 1;
              $yrBrOsA[$slaveId] = $slaveBrO = new Bro($slaveId);
              if ($Debug) DebugMsg("Copying Master Bro $masterBrO->BroId (slavePriorYear=$slavePriorYear) to Slave $slaveId which has been created.");
            }
            $masterBrO->CopyToSlave($slaveBrO);
          }
        }
      Debug();

      ###################
      # Bro Set Summing # via Bro Summing Tree
      ###################
      Debug("Set Summing Year $year on summing loop $sumPass");
      # Sum Bros by dimensions working up the tree using $BroSumTreesA 3 dimensional array of [DataTypeN, [BroId of Target Bro => [BroIds of Bros to sum]]]
      $treeA = $BroSumTreesA[$dataTypeN]; # [BroId of Target Bro, [BroIds of Bros to sum]
      if ($treeA !== 0) # Check that there is a summing tree for this DataType
        foreach ($treeA as $tarId => $listA) # Thru the summing tree [BroId of Target Bro => [BroIds of Bros to sum]]
          SumBros($tarId, $listA, BroDatSrce_S); # function SumBros($tarBroId, $broIdsA, $sumType, $sumA=[])
      Debug();

      ###########################
      # Summing Loop Finished ? # See if Copying Masters to Slaves, Base Summing, and Set Summing is finished i.e. if no change since previous loop
      ###########################
      if ($Debug) echo "<br>End of ", DataTypeStr($dataTypeN), " $yearStr summing loop $sumPass with count of yrBrOsA=",count($yrBrOsA),' BroChanges=',Bro::Changes(), '<br>';

      if (!Bro::Changes()) {
        if ($Debug) echo "No change on summing loop $sumPass for year $year so summing is finished<br>";
        break;
      }
      if ($sumPass===9) {
        BError("$yearStr summing failed to converge after 9 iterations");
        break;
      }
    } # end of $sumPass loop

    if ($PostEndBrosA[$dataTypeN]) {
      #################
      # Check PostEnd #
      #################
      #$WarningsA['SubHead'] = 'StartEnd PostEnd Sum checks gave:';
      /*$PostEndBrosA=[
        2245=>[3907,3908,3909,3910],
        2514=>[3911,3912,3913,3914],
        2515=>[3915,3916,3917,3918],
        2516=>[3919,3920,3921,3922],
        5285=>[5286,5287,5288,5289]
        ]; */
      foreach ($PostEndBrosA[$dataTypeN] as $seBroId => $listA) { # [BroId => [i => BroId]]
        if (isset($yrBrOsA[$seBroId])) {
          $seBrO= $yrBrOsA[$seBroId];
          $startSum = $seBrO->StartBase();
          $endSum   = $seBrO->EndBase();
        }else
          $startSum = $endSum = 0;
        if ($Debug==3) echo "$seBroId PostEnd Start (base) = $startSum, End (base) = $endSum<br>";
        $sum = $startSum;
        foreach ($listA as $broId)
          if (isset($yrBrOsA[$broId])) $sum += $yrBrOsA[$broId]->EndBase();
        if (($startSum || $endSum) && $sum != $startSum && $sum != $endSum)
          Warning($seBroId, ['BN', $endSum, $startSum, implode(',', $listA), $sum],
            "PostEnd Bro $seBroId %s has an End balance of %s but its Start balance of %s plus the SumList (%s) is %s which does not equal the End balance");
      }
    }
    if ($StockBrosA[$dataTypeN]) {
      ###############
      # Check Stock # Check that End = Start - Mvt
      ###############
      #$WarningsA['SubHead'] = 'StartEnd Stock Sum checks gave:';
      foreach ($StockBrosA[$dataTypeN] as $seBroId => $broId) { # [BroId => Stock Movement BroId]
        if (isset($yrBrOsA[$seBroId])) {
          $seBrO= $yrBrOsA[$seBroId];
          $startSum = $seBrO->StartBase();
          $endSum   = $seBrO->EndBase();
        }else
          $startSum = $endSum = 0;
        $mvt = isset($yrBrOsA[$broId]) ? $yrBrOsA[$broId]->EndBase() : 0;
        if ($startSum || $endSum || $mvt) {
          if ($Debug) echo "$seBroId Stock Start (base) = $startSum, End (base) = $endSum, Movement in $broId = $mvt<br>";
          $sum = $startSum - $mvt;
          if ($sum != $endSum)
            Warning($seBroId, ['BN', $startSum, $mvt, "$broId", BroName($broId), $sum, $endSum],
                      "$seBroId %s Start balance of %s minus movement of %s from %s %s is %s which does not equal the End balance of %s");
        }
      }
    }
    if ($CheckBrosA[$dataTypeN]) {
      /*##########################
      # Check Check Equivalences #
      ############################
      # 26.09.11 Changed to do base only
      # 24.10.11 Code simplified re change to base only
      # xx.xx.12 Changed to TPtargetBroRef then TPYtargetBroRef then TPYtargetBroId
       CheckTest = TPYtargetBroId
        where
         T is a one letter code to define the Type of check:
          E : Test is Equal
          O : Test is equal and Opposite
         P is a one letter code to define when the check is to be Performed:
          E : if Either Bro(Ref) has a value (was always this before 07.09.12)
          B : if Both Bro(Ref)s have a value
          C : if the Check Bro has a value
          T : if the Target BroRef has a value
         Y is digit 0-3 to indicate the relative year, default 0
         targetBroId is the BroId of the target */
      $WarningsA['SubHead'] = 'Bro Equivalence checks gave:';
      # $CheckBrosA 2 dimensional array of [DataTypeN, [BroId => CheckTest]]

      foreach ($CheckBrosA[$dataTypeN] as $broId => $CheckTest) {
        if ($checkYear = (int)$CheckTest[2]) {
          $checkYearBefore = $year + $checkYear - 1; # 0-8 given 1-3 range for checkYear. 'Before' so $priorYearsA can be used to get the prior year which could be a std year or a pya year
          if ($checkYearBefore>=Max_RelYear  || # OoR year or Max_RelYear which never has prior data
            !$priorYearsA[$checkYearBefore]) # prior year not available
            continue; # No data available for the check year
          $checkPriorYear = $priorYearsA[$checkYearBefore];
        }else
          $checkPriorYear = 0;
       #$tarRef = substr($CheckTest, 3);
        $tarId = (int)substr($CheckTest, 3);
        # Compare $broId bal with $tarId. Skip cases where both are empty
        $broB = isset($yrBrOsA[$broId]);
        $tarB = $checkPriorYear ? isset($BrosA[$checkPriorYear][$tarId]) : isset($yrBrOsA[$tarId]);
        if ($broB || $tarB) {
          switch ($CheckTest[1]) {
           #case 'E':                               # if Either Bro(Ref) has a value (was always this before 07.09.12)
            case 'B': $goB = $broB && $tarB; break; # if Both Bro(Ref)s have a value
            case 'C': $goB = $broB; break;          # if the Check Bro has a value
            case 'T': $goB = $tarB; break;          # if the Target BroRef has a value
            default:  $goB = true;                  # 'E' case and unknown code char
          }
          if ($goB) {
            $broBal = $broB ? $yrBrOsA[$broId]->EndBase() : false;
            $tarBal = $tarB ? ($checkPriorYear ? $BrosA[$checkPriorYear][$tarId]->EndBase() : $yrBrOsA[$tarId]->EndBase()) : false;
            switch ($CheckTest[0]) {
              case 'O': $resB = ($broBal === -$tarBal); break; # Equal & Opp To
              default:  $resB = ($broBal ===  $tarBal); break; # Equal and unknown code
            }
            if (!$resB)
              if ($broB && $yrBrOsA[$broId]->IsSetSlave())
                BError(sprintf("Set Slave Bro $broId %s has a balance of %s which is not equal to the %s value of its master $tarId %s", BroName($broId), number_format($broBal), number_format($tarBal), BroName($tarId)));
              else
                Warning($broId, ['BN', $broBal, $CheckTest[0]=='O' ? 'be Equal &amp; Opp to' : 'Equal', BroName($tarId), $tarBal],
                        "$broId %s has a balance of %s which is required to %s the $tarId %s balance but that is %s");
          }
        }
      }
    } # End of Check testing

    ################
    # PMem Summing # which includes Intermediate PMem Summing and Pya PMem Summing if applicable
    ################
    Debug("PMem Summing year $year");
    $stdYear = $year - Pya_Year_Offset; # > 0 if applicable i.e. in a Pya year
    foreach ($BrosThisDataTypeA as $broId => $t)
      $yrBrOsA[$broId]->PMemSumming($stdYear>0 ? (isset($BrosA[$stdYear][$broId]) ? $BrosA[$stdYear][$broId] : null) : null); # Passed the StdYear BrO for a Pya year, or null if not applicable or not defined
    Debug();

  } # end of DataType loop

  # Copy non-summing Masters to Slaves
  if ($Debug) echo "<p class='mt10 mb0'>Copying Non-summing Masters to Slaves for $yearStr</p>\n";
  foreach ($yrBrOsA as $broId => $masterBrO) # potential master Bro
    if ($masterBrO->IsMaster() && !$masterBrO->IsSumming())
      # Non-summing Master Bro
      foreach ($masterBrO->InfoA[BroI_SlaveIdsA] as $slaveId) # through the slaves of this master
        $yrBrOsA[$slaveId] = $masterBrO->CopyToSlave(new Bro($slaveId));

  ksort($yrBrOsA); # sort by BroId
  $BrosA[$year] = $yrBrOsA;
} # end of year summing loop

if ($Errors) {
  # Had summing errors
  echo '<p class=b><br>The import failed due to the ',NumPluralWord($Errors, 'error'), " reported above.</p>\n";
  Form(); # time, topB
  #######
}

# Any DE errors?
foreach ($deSumsA as $year => $deSumA) {
  if ($tot = $deSumA[BS_Dr] + $deSumA[BS_Cr]) {
    if (!$year) echo '<br>';
    $tot = number_format($tot);
    BError('The DE Postings for ' . YearStr($year) . " do not balance by $tot. The totals are: Dr = " . number_format($deSumA[BS_Dr]) . ' &nbsp;Cr = ' . number_format($deSumA[BS_Cr]) . " &nbsp;Dr+Cr = $tot");
  }
}

if ($Errors) {
  # Had DE errors
  echo '<p class=b><br>The import failed due to the ',NumPluralWord($Errors, 'error'), ' reported above'.($NoDEcheck ? ' but the import is continuing because No DE Check is set':'').".</p>\n";
  if (!$NoDEcheck)
    Form(); # time, topB
  #######
}

# No Errors

# Derived values for year 0
$derivedMsg='';
/* djh??
if (!$startYear) {
  if (!isset($BrosA[0][BroId_Dates_BS]) && isset($BrosA[0][BroId_Dates_YearEndDate])) {
    $BrosA[0][BroId_Dates_BS] = $brO = new Bro(BroId_Dates_BS);
    $brO(BroDatT_End, $BrosA[0][BroId_Dates_YearEndDate]->EndBase(), BroDatSrce_e);
    $derivedMsg = '<p>Balance Sheet Date was not defined. Set to YearEndDate</p>';
  }
  if (!isset($BrosA[0][BroId_Dates_ApprovalAccounts]) && isset($BrosA[0][BroId_Dates_YearEndDate])) {
    $BrosA[0][BroId_Dates_ApprovalAccounts] = $brO = new Bro(BroId_Dates_ApprovalAccounts);
    $brO(BroDatT_End, $BrosA[0][BroId_Dates_YearEndDate]->EndBase() + 30, BroDatSrce_e);
    $derivedMsg .= '<p>Accounts Approval Sheet Date was not defined. Set to YearEndDate + 30 days</p>';
  }
}
*/

/*##########
## DB Ops ## Bros and BroTrans deletes, updates/entries for the primary postings before storing import results
############
ImportType impacts how previous entries for a year are handled. If there are no previous entries, i.e. for the first import for a given year then ImportType has no meaning.

When there are previous entries for a year i.e. for a repeat import then:
Complete means:
 The import data is to be a complete set of data for the year. Accordingly:
 C.1 If an import Bro matches a previous Bros (all BroDats), the previous Bro is left untouched.
 C.2 If an import Bro differs from a previous Bro, the previous Bro is updated (replaced)
     and a BroTrans table BDTT_ImportUpdate (Audit Trail) tran generated.
 C.3 If there is no import Bro corresponding to a previous Bro, the previous Bro is updated (set) to empty (BroStr='')
     and a BroTrans table BDTT_ImportUpdate (Audit Trail) tran generated.

Replace means:
 All Bros and BroTrans for any year for which the import includes data, including restated year data if any, are deleted.
 Import for the years with data then proceeds as a fresh import.
 The only record of previous imports for the year(s) with data will be via DBLog.
 Previous years without data in this import are not touched.

*/

# djh? Delete Refs?
function DeleteYears($yearsA) {
  global $DB, $CurrYear, $EntityId;
  $delYearsiA = null;
  foreach ($yearsA as $year => $b) # year is relYear
    if ($b)
      $delYearsiA[] = $CurrYear-$year;
  if ($delYearsiA) {
    # Got years to delete
    $prevdId = $prevYear = -9; # $entYear can be -ve, min value -6
    # Using a Left Outer Join as Bros that are not Posting Bros don't have corresponding BroTrans records
    DebugMsg("Select B.Id BID,EntYear,BroId,T.Id TId from Bros B Left Join BroTrans T On T.BrosId=B.Id Where EntityId=$EntityId And EntYear In".ArrayToBracketedCsList($delYearsiA).' Order by EntYear,B.Id');
    $res = $DB->ResQuery("Select B.Id BID,EntYear,BroId,T.Id TId from Bros B Left Join BroTrans T On T.BrosId=B.Id Where EntityId=$EntityId And EntYear In".ArrayToBracketedCsList($delYearsiA).' Order by EntYear,B.Id');
    while ($o = $res->fetch_object()) {
      if ($o->EntYear > $prevYear) {
        $prevYear = (int)$o->EntYear;
        $prevdId  = 0;
      }
      if (($brosId = (int)$o->BID) > $prevdId) { # could have multiple trans per BID
        $DB->DeleteMaster(T_B_Bros, $brosId, DBLOG_Delete_ReplaceImport);
        $prevdId = $brosId;
      }
      if ($o->TId) # is null as result of Left Join if no matching tran in error
        $DB->DeleteMaster(T_B_BroTrans, (int)$o->TId, DBLOG_Delete_ReplaceImport);
    }
    $res->free();
   }
 }

$DB->autocommit(false);

# Delete any Pya years set for deletion as a result of the standard year being re-imported
DeleteYears($pyaDelYearsA);
# Delete any Import years if this is a replace import
if ($importType === IT_Replace)
  DeleteYears($importYearsA);

foreach ($sumYearsA as $year => $b) { # year is relYear
  if (!$b) continue;
  $entYear     = $CurrYear-$year;
  $yrBrOsRA    = &$BrosA[$year];     # data for the year [BroId => BrO]
  $prevBrosRA  = &$PrevBrosA[$year]; # previous data for the year [BroId => BrO]
  $importYearB = $importYearsA[$year];
  if ($importYearB && ($importType === IT_Complete && $prevYearsA[$year])) {
    # Import year for ImportType:Complete with previous data in the year
    # C.1 and C.2 are done below in the all $yrBrOsRA insert loop
    # Do C.3 If there is no import Bro corresponding to a previous Bro, the previous Bro is updated (set) to empty (BroStr='')
    #        and a BroTrans table BDTT_ImportUpdate (Audit Trail) tran generated.
    foreach ($prevBrosRA as $broId => $t) # previous data as  [year => [BroId => BrO]]  year in range 0 - 6 i.e. incl pya years
      if (!isset($yrBrOsRA[$broId])) {
        # Previous Bro but none now so update it to empty
        $pA = $DB->AaQuery("Select Id,BroStr From Bros Where EntityId=$EntityId And EntYear=$entYear And BroId=$broId");
        $DB->UpdateMaster(T_B_Bros, ['BroStr' => ''], $pA);
        # Insert the BroTrans tran
        $colsAA = [
          'BrosId' => (int)$pA['Id'],
          'MemId'  => $DB->MemId,
          'TypeN'  => BDTT_ImportUpdate,
          'TransSet' => ''];
        $DB->InsertMaster(T_B_BroTrans, $colsAA);
      }
  }

  # Loop to Insert the Bros. Also does C.1 and C.2 for ImportType:Complete
  # C.1 If an import Bro matches a previous Bros (all BroDats), the previous Bro is left untouched.
  # C.2 If an import Bro differs from a previous Bro, the previous Bro is updated (replaced)
  #     and a BroTrans table BDTT_ImportUpdate (Audit Trail) tran generated.
  foreach ($yrBrOsRA as $broId => $brO) {
    if ($brO->IsEleSlave()) continue; # Skip Ele Slaves. Not Set ones because Set Slaves are stored to avoid the need for Set summing in RG etc.
    if ((!$importYearB || $importType === IT_Complete) && isset($prevBrosRA[$broId])) {
      # Not an Import Year (could have Start diff or PYA diff due to base diff) or is ImportType:Complete with a previous Bro
      $prevBrO = $prevBrosRA[$broId];
      # echo "thisBroStr = ",$brO->Stringify()," prevBro = ",$prevBrO->Stringify(),BR;
      if (($thisBroStr = $brO->Stringify()) !== $prevBrO->Stringify()) {
        # Has changed = C.2 case, so need to Update this and create a tran for any posting changes
        # Update the Bro
        $pA = $DB->AaQuery("Select Id,BroStr From Bros Where EntityId=$EntityId And EntYear=$entYear And BroId=$broId");
        $DB->UpdateMaster(T_B_Bros, ['BroStr' => $thisBroStr], $pA);
        # Create a tran for any posting changes if not a Slave (HasPosting() returns false for a Slave). None if not an import year.
        if ($importYearB && ($brO->HasPosting() || $prevBrO->HasPosting())) {
          #echo "$broId thisBroStr=$thisBroStr<br>$broId prevBroStr=".$prevBrO->Stringify().'<br>';
          $thisDatOsA = $brO->PostingBroDatOs(); # [BroDatKey -> BroDatO]
          $prevDatOsA = $prevBrO->PostingBroDatOs();
          $broDatsA = []; # [BroDayKey => TranStr] to form BroTrans.TransSet, keyed by BroDatKey so that it can be sorted in the normal way
          foreach ($prevDatOsA as $broDatKey => $datO) {
            if (isset($thisDatOsA[$broDatKey])) {
              # In both. Remove if unchanged.
              if ($datO->IsEqual($thisDatOsA[$broDatKey]))
                unset($thisDatOsA[$broDatKey]);
            }else{
              # $datO in previous but not this, so add a 'deleted' BroDat with Dat of 
              #   DatType {PMemRef D2} Dat
              $broDatsA[$broDatKey] = is_int($broDatKey) ? "{$this->BroDatType}" : $this->BroDatType.substr($this->BroDatKey,2).'';
            }
          }
          # Complete $broDatsA from what is left in $thisDatOsA
          foreach ($thisDatOsA as $broDatKey => $datO)
            $broDatsA[$broDatKey] = substr($datO->Stringify(), 1); # substr to chop off the leading SrceNChr that is not needed for Trans BroDats that are all BroDatSrce_P (Posting) type here.

          ksort($broDatsA, SORT_NATURAL);
          $colsAA = [
            'BrosId' => (int)$pA['Id'],
            'MemId'  => $DB->MemId,
            'TypeN'  => BDTT_ImportUpdate,
            'TransSet' => implode(D1, $broDatsA)];
          $DB->InsertMaster(T_B_BroTrans, $colsAA);
        }
      } # else Bros the same so nothing to do = C.1 case
    }else{
      # ImportType:Replace or no previous entry so insert
      # Done expect to be here for non import year case
      if (!$importYearB) die("Die - insert for Bro $broId in non-import year $year, which should not be");
      $colsAA = [
        'EntityId' => $EntityId,
        'BroSetId' => $BroSetId,
        'EntYear'  => $entYear,
        'BroId'    => $broId,
        'BroStr'   => $brO->Stringify()];
      list($brosId) = $DB->InsertMaster(T_B_Bros, $colsAA);
      # Insert the BroTrans tran for the Postings, if any
      if ($brO->HasPosting()) { # false for Slaves
        $A = [];
        foreach ($brO->PostingBroDatOs() as $datO)
          $A[] = substr($datO->Stringify(), 1); # substr to chop off the leading SrceNChr that is not needed for Trans BroDats that are all BroDatSrce_P (Posting) type here.
        $colsAA = [
          'BrosId' => $brosId,
          'MemId'  => $DB->MemId,
          'TypeN'  => BDTT_ImportInsert,
          'TransSet' => implode(D1, $A)];
        $DB->InsertMaster(T_B_BroTrans, $colsAA);
      }
    }
  }
}
unset($yrBrOsRA, $prevBrosRA);

# Update Entities.DataState and Entities.DGsInUse if changed
$colsAA = [];
# Bits for Entities.Bits re DataState
# DataState Bits. None set = no postings
#onst EB_DS_OK       =   512; #  9 OK
#onst EB_DS_Errors   =  1024; # 10 Critical errors found
#onst EB_DS_Warnings =  2048; # 11 Warnings found
#     EB_DS   EB_DS_OK | EB_DS_Errors | EB_DS_Warnings
$actioN2 = $info2 = 0;
$preDataState = $EBits & EB_DS;
$newDataState = $WarningsA['Num'] ? $preDataState | EB_DS_Warnings : EB_DS_OK;
if ($newDataState != $preDataState) {
  $colsAA['Bits'] = ($EBits & ~EB_DS) | $newDataState;
  $actioN2 = ETA_SetDataState;
  $info2   = $newDataState;
}
# DGsInUse...
$dimGroupsInUse = 0;
for ($dg=0,$bit=1; $dg<DG_Num; ++$dg, $bit *= 2)
  if ($DGsInUseA[$dg]) # Been built by SetDimGroups()
    $dimGroupsInUse |= $bit;
if ($dimGroupsInUse != $DGsInUse)
  $colsAA['DGsInUse'] = $dimGroupsInUse;

if (count($colsAA))
  $DB->UpdateMaster(T_B_Entities, $colsAA, $EA, $EntityId);

# And finally add the Import Tran
AddEntityTran($EntityId, $CurrYear, $AppEnum, ETA_Import, $Filei, $actioN2, $info2);

$DB->commit();

# End of DB Updating
# ==================

###################################
# Data Import and Summing Results #
###################################

ResultsTable('Data Import and Summing Results');
if (!$Debug)
echo "<p class=mb0>Where the 'Src' column shows the Source of the values as follows:</p>
<table class=itran>
<tr><td>&bull;</td><td>P</td><td>Posting or Import</td></tr>
<tr><td>&bull;</td><td>PE</td><td>Prior year End value as Start for a startend Bro</td></tr>
<tr><td>&bull;</td><td>R</td><td>Restated Orignal Value - included in 'b' Base Sum and 'd' Restated Amount Sum</td></tr>
<tr><td>&bull;</td><td>S</td><td>Summed</td></tr>
<tr><td>&bull;</td><td>SE</td><td>Sum End calculation for a startend Bro of type SumEnd</td></tr>
<tr><td>&bull;</td><td>b</td><td>Base (no properties) value = sum of the primary property values for the Bro</td></tr>
<tr><td>&bull;</td><td>d</td><td>Dimension summing value as per the Dimensions Map</td></tr>
<tr><td>&bull;</td><td>e</td><td>dErived or dEduced value</td></tr>
<tr><td>&bull;</td><td>i</td><td>Intermediate dimension for a posting with multiple properties</td></tr>
<tr><td>&bull;</td><td>r</td><td>Restated Orignal Value - included in 'd' Restated Amount Sum</td></tr>
</table>
<p>An upper case Source code indicates that this is a Primary entry. In the case of Summing Bros a Primary entry is one which contributes to the Bro's Base total i.e. its no dimensions value. Lower case codes are for information values derived from the primary values.<br>
A prefix of 'm' means that the value has been copied from the Master, which also means that the Bro in question is a Slave Bro.<br>
'i' and 't' codes can appear after a Primary code when a combination is involved.<br>
If different Source codes apply in different years, the column contains a comma separated list for all years, otherwise a single code is shown which applies to all years.</p>
";

if ($derivedMsg)
  echo "<p class='b mt10 mb0'>Year 0 Derived Value(s)</p>$derivedMsg";

if ($warnings=$WarningsA['Num']) {
  if ($warnings===1)
    echo "{$WarningsA['Msg']}<p><br>The import has been processed but there was a <span class='L b'>warning</span> issued. See above. This should be investigated.</p>";
  else
    echo "{$WarningsA['Msg']}<p><br>The import has been processed but there were <span class='L b'>$warnings warnings</span> issued. See above. These should be investigated.</p>";
}else
  echo '<p><br>The import has been completed without errors or warnings.</p>';

Form(true, true); # time, topB
#################

# SumBros($tarBroId, $broIdsA, $sumType, $sumA=[])
# ================================================
# Sums the Bros of $broIdsA to $tarBroId, starting with $baseSum, $diMesSum, and $sumA as the Start values for a SumEnd op,
# with $sumType = BroDatSrce_S or BroDatSrce_SE.
# $sumA = [BroDatKey => [0 => Bal, 1 => SrceN]]
# Should only be called when have only Primary entries
# djh?? remove the 1,2 3,4 fudge
function SumBros($tarBroId, $broIdsA, $sumType, $sumA=[]) { # $sumA=[] for a Set summing op with base initialised to false
  global $PMemsA, $yrBrOsA, $BrosThisDataTypeA, $year, $Debug;
  $numDiMesA = array_fill(BroDatT_Start, BroDatT_End, 0); # [ , 0, 0]
  foreach ($broIdsA as $broId) {   # thru the Bros that sum to the target $tarBroId
    if (isset($yrBrOsA[$broId])) { # There is data for this $broId. Sum to the target $sumA[]
      foreach ($yrBrOsA[$broId]->SummingBroDatAs() as $broDatKey => $datA) { # [BroDatKey => [0 => Bal, 1 => SrceN]] for the Primary BroDats, and only Pya BroDats when in a Pya year. Should only be called for a Non-StartEnd Summing Bro.
       #echo "broDatKey |$broDatKey| => [{$datA[0]},{$datA[1]}]<br>";
        $bal = $datA[0];
        if (isset($sumA[$broDatKey])) {
          $sumA[$broDatKey][0] += $bal;
          if ($Debug) {$sumTypeS = $sumType==BroDatSrce_S ? 'Set' : 'SumEnd'; DebugMsg("$sumTypeS Summing year $year $tarBroId,$broDatKey Bal {$sumA[$broDatKey][0]} after adding Bro $broId,$broDatKey bal $bal");}
        }else{
          if (is_string($broDatKey)) ++$numDiMesA[(int)$broDatKey];
          $sumA[$broDatKey] = [$bal, BroDatSrce_S]; # [BroDatKey => [0 => Bal, 1 => SrceN]]
          if ($Debug) {$sumTypeS = $sumType==BroDatSrce_S ? 'Set' : 'SumEnd'; DebugMsg("$sumTypeS Summing year $year $tarBroId,$broDatKey Bal $bal created from Bro $broId,$broDatKey bal $bal");}
        }
      }
    }
  }
  if (!count($sumA))
    return; # nothing summed

  # Got summed data
  # If there a Base sum or sums and there are PMems involved, then the Base or Bases need adjustment.
  # the target Bro's Mandatory/Default PMem if it has one, o'wise to DiMeId_Unallocated
  $tarBrO = isset($yrBrOsA[$tarBroId]) ? $yrBrOsA[$tarBroId] : $yrBrOsA[$tarBroId] = new Bro($tarBroId);
  $tarUsableDims = $tarBrO->InfoA[BroI_SumUsablePropDims];
  if ($tarUsableDims && ($numDiMesA[BroDatT_End] || $numDiMesA[BroDatT_Start])) {
    # Target has Usable Dims and PMems are involved, so check that that PMems in the sum are OK for the target's usable dims.
    # have 1 or more PMem sums
    # First check if target has Group (dimId == 1) but sum has a dim Consol (dimId == 2) balance, and vice versa.
    # which is OK if the PMem is Consol or Consol.Consol (PMemIds 3,4) which are the same as Group.Consol or Group (PMemIds 2,1)
    # and if so change the Consol 3,4 sum to Group 2,1
    if ($tarUsableDims[0]==='1') { # will be first if present as usable dims are sorted
      foreach ($sumA as $broDatKey => $sumDatA) # [0 => Bal, 1 => SrceN]
        if (is_string($broDatKey)) { # only do PMems i.e. skip StartBase and Base sums
          # '3' -> '2', or 'x,3' -> 'x,2' or 'x,3,y' -> 'x,2,y' and ditto for 4->1 i.e. ref length doesn't change and only 1 char each time
          list($broDatType, $pMemRefsA) = UnpackBroDatKey($broDatKey);
          foreach ($pMemRefsA as $i => $diMeId) # djh?? Only need to check first 2?
            if ($diMeId===3 || $diMeId===4) { # 3 or 4
              $pMemRefsA[$i] = $diMeId===3 ? 2 : 1; # 3->2, 4->1
              $newDatKey = substr($broDatKey,0,2).implode(',', $pMemRefsA);
              if (isset($sumA[$newDatKey]))
                $sumA[$newDatKey][0] += $sumDatA[0];
              else
                $sumA[$newDatKey] = $sumDatA;
              unset($sumA[$broDatKey]);
              if ($Debug) DebugMsg("Changed $broDatKey to $newDatKey (Consol to Group re Target Usable Dims of $tarUsableDims");
              break;
            }
        }
    }else if ($tarUsableDims[0]==='2') { # will be first. Never have both 1 and 2
      # If target has Consol (dimId == 2) but sum has a dim Group (dimId == 1) balance, and vice versa.
      # which is OK if the PMem is Group.Consol or Group (PMemIds 2,1) which are the same as Consol or Consol.Consol (PMemIds 3,4)
      # and if so change the Group 2,1 sum to Consol 3,4
      foreach ($sumA as $broDatKey => $sumDatA) # [0 => Bal, 1 => SrceN]
        # '1' -> '4', or 'x,1' -> 'x,4' or 'x,1,y' -> 'x,4,y' and ditto for 2->3 i.e. ref length doesn't change and only 1 char each time
        if (is_string($broDatKey)) { # only do PMems i.e. skip StartBase and Base sums
          list($broDatType, $pMemRefsA) = UnpackBroDatKey($broDatKey);
          foreach ($pMemRefsA as $i => $diMeId) # djh?? Only need to check first 2?
            if ($diMeId===1 || $diMeId===2) { # 1 or 2
              $pMemRefsA[$i] = $diMeId===1 ? 4 : 3; # 1->4, 2->3
              $newDatKey = substr($broDatKey,0,2).implode(',', $pMemRefsA);
              if (isset($sumA[$newDatKey]))
                $sumA[$newDatKey][0] += $sumDatA[0];
              else
                $sumA[$newDatKey] = $sumDatA;
              unset($sumA[$broDatKey]);
              if ($Debug) DebugMsg("Changed $broDatKey to $newDatKey (Group to Consol re Target Usable Dims of $tarUsableDims");
              break;
            }
        }
    }
    # Now check the Dim/PMem use and adjust if necessary
    foreach ($sumA as $broDatKey => $sumDatA) { # [0 => Bal, 1 => SrceN]
      if (is_string($broDatKey)) { # only do PMems i.e. skip StartBase and Base sums
        list($broDatType, $pMemRefsA) = UnpackBroDatKey($broDatKey);
        foreach ($pMemRefsA as $diMeId)
          if ($diMeId<DiMeId_Unallocated) { # skip the unallocated cases
            $dimId = $PMemsA[$diMeId][PMemI_PropId];
            if (!InChrList($dimId, $tarUsableDims) && !($broDiMesA && $broDiMesA[II_AllowsA] && in_array($diMeId, $broDiMesA[II_AllowsA]))) {
              if ($sumDatA[0] && IsPrimary($sumDatA[1])) { # skip if zero or not a primary entry but still zap the $sumA entry
                # Not an allowed Dim in Target so -> Unallocated
                $newDatKey = "$broDatType,".DiMeId_Unallocated;
                if (isset($sumA[$newDatKey])) {
                  $sumA[$newDatKey][0] += $sumDatA[0];
                  if ($Debug) DebugMsg("$sumTypeS Summing year $year $tarBroId Unallocated Bal {$sumA[$newDatKey][0]} after adding $tarBroId,$broDatKey bal {$sumDatA[0]} as $broDatKey (Dim $dimId) is not available in $tarBroId)");
                }else{
                  $sumA[$newDatKey] = [$sumDatA[0], BroDatSrce_S]; # [0 => Bal, 1 => SrceN]
                  ++$numDiMesA[$broDatType];
                  if ($Debug) DebugMsg("$sumTypeS Summing year $year $tarBroId Unallocated Bal {$sumDatA[0]} created from $tarBroId,$broDatKey as $broDatKey (Dim $dimId) is not available in $tarBroId");
                }
              }
              unset($sumA[$broDatKey]);
              --$numDiMesA[$broDatType];
              break;
            }
          }
      }
    }
    # Move unallocated entry to Base if that is all that is left
    foreach ([BroDatT_Start, BroDatT_End] as $broDatType) {
      if ($numDiMesA[$broDatType]===1 && isset($sumA[$unallocDatKey = "$broDatType,".DiMeId_Unallocated])) {
        $sumA[$broDatType] = [$sumA[$unallocDatKey][0], BroDatSrce_S]; # [0 => Bal, 1 => SrceN]
        if ($Debug) DebugMsg("$sumTypeS Summing year $year $tarBroId Base Bal {$sumA[$broDatType][0]} created from all PMems -> unallocated");
        unset($sumA[$unallocDatKey]);
        --$numDiMesA[$broDatType];
      }
    }
  }
  # If there are PMems and Base as can happen from some Bros in the sum being Base only and some with PMems, move the Base to the default PMem
  # Really only need to do this here if the default PMem also has a balance as o'wise Bro::add data could handle the reallocation of Base to default.
  # But as a DefaultPMemId() call to get the default PMem is needed anyway, do the switch here to avoid another DefaultPMemId() call in Bro:add data
  # (If this switch is not done here where there are both Base and default PMem sums, on a repeat loop Bro::add data replaces the default bal twice -> 2 changes even if final result is as it was before.)
  foreach ([BroDatT_Start, BroDatT_End] as $broDatType) {
    if ($numDiMesA[$broDatType] && isset($sumA[$broDatType])) {
      $defaultPMemId = $tarBrO->DefaultPMemId();
      $defaultDatKey = "$broDatType,$defaultPMemId";
      if (isset($sumA[$defaultDatKey])) {
        $sumA[$defaultDatKey][0] += $sumA[$broDatType][0];
        if ($Debug) DebugMsg("$sumTypeS Summing year $year $tarBroId Base Bal {$sumA[$broDatType][0]} -> Default PMem $defaultPMemId -> bal {$sumA[$defaultDatKey][0]}");
      }else{
        $sumA[$defaultDatKey] = [$sumA[$broDatType][0], BroDatSrce_S]; # [0 => Bal, 1 => SrceN]
       #++$numDiMesA[$broDatType];
        if ($Debug) DebugMsg("$sumTypeS Summing year $year $tarBroId default $defaultPMemId Bal {$sumA[$broDatType][0]} created from Base");
      }
      unset($sumA[$broDatType]);
    }
  }
  #if ($Debug && count($sumA))  foreach ($sumA as $broDatKey => $sumDatA)  DebugMsg("$tarBroId sumA[$broDatKey] Bal = {$sumDatA[0]}");
  # Add Sum values to $BrosThisDataTypeA and $yrBrOsA
  $BrosThisDataTypeA[$tarBroId] = 1;
  $tarIsSe = $tarBrO->IsStartEnd();
  # echo "Summing to $tarBroId tarIsSe=$tarIsSe<br>";
  foreach ($sumA as $broDatKey => $sumDatA) # [0 => Bal, 1 => SrceN]
    if (IsEndBroDatKey($broDatKey) || $tarIsSe) # add data if not Start or Start too if target is StartEnd
      $tarBrO($broDatKey, $sumDatA[0], $sumDatA[1], 0, BroAddDataOp_Replace); # add data ($broDatTypeOrBroDatKey, $dat, $srceN, $pMemRefOrPMemRefsA=0, $op=BroAddDataOp_Unique)
}

# IsBroZero($broId)
# =================
# Returns:
# true  if Bro is defined and base balance === 0
# 1     if Bro is not defined
# false if Bro is defined and base balance !== 0
function IsBroZero($broId) {
  global $yrBrOsA;
  return isset($yrBrOsA[$broId]) ? $yrBrOsA[$broId]->Bal() === 0 : 1; # true/false if Bro is defined, 1 o'wise for undefined == true
}

# AddData($broId, $broDatKey, $dat, $srceN, $pMemRefOrPMemRefsA=0)
# ==============================================================
# Adds data to a $yrBrOsA Bro for the $broDatKey, $dat, $srceN case, Inst=0, creating a new Bro or adding to an existing one as appropriate.
# Returns the BroDat
function AddData($broId, $broDatKey, $dat, $srceN, $pMemRefOrPMemRefsA=0) {
  global $yrBrOsA, $BrosThisDataTypeA;
  if (!isset($yrBrOsA[$broId])) {
    $yrBrOsA[$broId] = new Bro($broId);
    $BrosThisDataTypeA[$broId] = 1;
  }
  return $yrBrOsA[$broId]($broDatKey, $dat, $srceN, $pMemRefOrPMemRefsA); # add data ($broDatTypeOrBroDatKey, $dat, $srceN, $pMemRefOrPMemRefsA=0, $op=BroAddDataOp_Unique)
}

# Movement($broId)
# ================
# Returns the End - Start Movement in the Bro as [BroDatKey => mvt], false if Bro is not set
# The Bro is expected to be a StartEnd Bro
function Movement($broId) {
  global $yrBrOsA;
  # Mvt = End - Start
  if (!isset($yrBrOsA[$broId])) return false;
  $mvtA = BroEndBals($broId); # End Bals, [] if undefined
  foreach ($yrBrOsA[$broId]->StartPrimaryBroDatAs() as $broDatKey => $datA) { # [BroDatKey => [0 => Bal, 1 => SrceN]] for the Start Primary BroDats, only Pya BroDats when in a Pya year
    AdjustBroDatKey($broDatKey, BroDatT_Start); # Start -> End
    if (isset($mvtA[$broDatKey]))
      $mvtA[$broDatKey] -= $datA[0];
    else
      $mvtA[$broDatKey] = -$datA[0];
  }
  return $mvtA;
}

# BroEndBals($broId)
# ==================
# Returns the End balances of Bro broId, wo Base if there are PMems, as [BroDatKey => Bal]
# or [] if the Bro is not defined
function BroEndBals($broId) {
  global $yrBrOsA;
  $balsA = [];
  if (isset($yrBrOsA[$broId]))
    foreach ($yrBrOsA[$broId]->EndPrimaryBroDatAs() as $broDatKey => $bal) # [BroDatKey => Bal] for the End Primary BroDats, only Pya BroDats when in a Pya year
      $balsA[$broDatKey] = $bal;
  return $balsA;
}

# SumList($listA)
# ===============
# Returns the sum of the balances of the listA Bros (assumed to be Non-StartEnd) as a [BroDatKey => Bal] array. Expected to be all End balances.
function SumList($listA) {
  global $yrBrOsA;
  $sumA = [];
  foreach ($listA as $i => $broId)
    if (isset($yrBrOsA[$broId]))
      foreach ($yrBrOsA[$broId]->SummingBroDatAs() as $broDatKey => $datA) # [BroDatKey => [0 => Bal, 1 => SrceN]] for the Primary BroDats, and only Pya BroDats when in a Pya year. Should only be called for a Non-StartEnd Summing Bro.
        if (isset($sumA[$broDatKey]))
          $sumA[$broDatKey] += $datA[0];
        else
          $sumA[$broDatKey]  = $datA[0];
  return $sumA;
}

# SetBroToAminusSumList($tarBroId, $aA, $listA)
# Set target Bro tarBroId to aA - the sum of listA
# where aA is a [BroDatKey => Bal] balances array like those returned by BroEndBals()
function SetBroToAminusSumList($tarBroId, $aA, $listA) {
  global $yrBrOsA;
  # aA - sum of list
  $sumA = SumList($listA);
  #Dump("SetBroToAminusSumList($tarBroId... sumA",$sumA);

  foreach ($sumA as $broDatKey => $bal)
    if (isset($aA[$broDatKey]))
      $aA[$broDatKey] -= $bal;
    else
      $aA[$broDatKey] = -$bal;

  # If there are PMems and Base as can happen from some Bros in the sum being Base only and some with PMems, move the Base to Unallocated.
  # djh?? If this happens often, a better method would be to go back a level and move each Base to its Default PMem.
  $unallocDatKey = BroDatT_End.','.DiMeId_Unallocated;
  if (count($aA)>1 && isset($aA[BroDatT_End])) {
    if (isset($aA[$unallocDatKey]))
      $aA[$unallocDatKey][0] += $aA[BroDatT_End];
    else
      $aA[$unallocDatKey] = $aA[BroDatT_End];
    unset($aA[BroDatT_End]);
  }

  # zap any zero ones
  foreach ($aA as $broDatKey => $bal)
    if (!$bal)
      unset($aA[$broDatKey]);

  # Move unallocated entry to Base if that is all that is left
  if (count($aA)===1 && isset($aA[$unallocDatKey])) {
    $aA[BroDatT_End] = $aA[$unallocDatKey];
    unset($aA[$unallocDatKey]);
  }

  # Set Set target Bro tarBroId
  foreach ($aA as $broDatKey => $bal)
    AddData($tarBroId, $broDatKey, $bal, BroDatSrce_S); # AddData($broId, $broDatKey, $dat, $srceN)
}

# BalsEqualSum($balsA, $listA)
# Returns true if balsA == sum of listA, o'wise the base sum value
function BalsEqualSum($balsA, $listA) {
  $sumA = SumList($listA);
#Dump('BalsEqualSum balsA',$balsA);
#Dump('BalsEqualSum sumA',$sumA);
  return $balsA == $sumA ? true : $sumA[0];
}

# SetDimGroups()
# Set Dim Group use in $DGsInUseA to be used to build $DGsInUse with bit settings

function SetDimGroups($pMemRefsA) {
  global $PMemsA, $DimGroupsA, $DGsInUseA;

  return; # djh??

  static $dimToDimGroupSA;
  # echo "SetDimGroups(",implode(',', $pMemRefsA),')<br>';
  if (!$dimToDimGroupSA) {
    # Build static $dimToDimGroupSA from $DimGroupsA
    $dimToDimGroupSA = array_fill(1, DimId_Max, -1); # DimId -> DimGroup enum, default -1
    foreach ($DimGroupsA as $dg => $dgA)
      foreach ($dgA[DGI_DimsA] as $dimId)
        $dimToDimGroupSA[$dimId] = $dg;
  }
  foreach ($pMemRefsA as $diMeId) {
    # PMemId -> DimId -> DimGroup
    if ($diMeId < DiMeId_Unallocated && ($dg = $dimToDimGroupSA[$PMemsA[$diMeId][PMemI_PropId]]) >= 0)
      $DGsInUseA[$dg] = 1;
  }
}

# Warning()
# $msg: vprinft format with %s's for the balances to be formatted, or strings in $argsA
#       The args passed via $argsA can be a balance (incl false), a string, or a string "BN" for $broId in name form
#       If there is only one arg it can be passed as a single value.
# $broId set to true = Seed case for heading to be output if any call comes along, with
#                       $yearStr and $dataTypeN passed as 2nd and 3rd parameters
function Warning($broId, $argsA, $msg) {
  global $WarningsA;

  if ($broId === true) {
    # Seed case
    $WarningsA['YearStr']   = $argsA;
    $WarningsA['DataTypeN'] = $msg;
    $WarningsA['OutputHdgB']= true;
    $WarningsA['SubHead']   = 0;
    return;
  }
  if ($WarningsA['OutputHdgB']) {
    $WarningsA['Msg'] .= "<p class='b mt10 mb0'>Summing Warnings for " . DataTypeStr($WarningsA['DataTypeN']) . " $WarningsA[YearStr]</p>\n";
    $WarningsA['OutputHdgB'] = false;
  }
  if ($WarningsA['SubHead']) {
    $WarningsA['Msg'] .= "<p class='mt05 mb0'>$WarningsA[SubHead]</p>";
    $WarningsA['SubHead'] = 0;
  }
  if (!is_array($argsA))
    $argsA = [$argsA];
  foreach ($argsA as $i => $bal) {
    if ($bal === false)
      $argsA[$i] = "'undefined' (no posting)";
    else if (is_int($bal)) {
      if ($bal === 0)
        $argsA[$i] = 'zero';
      else
        switch ($WarningsA['DataTypeN']) {
          case DT_Decimal: $argsA[$i] = number_format((float)$bal/10000, 2); break;
          default:         $argsA[$i] = number_format($bal); break;
        }
    }else if (is_string($bal)) {
      if ($bal == 'BN')
        $argsA[$i] = BroName($broId);
    }
  }
  #array_unshift($argsA, $bRef);
  $WarningsA['Msg'] .= '<p class=mb0>'.vsprintf($msg, $argsA). '.</p>';
  $WarningsA['Num']++;
}


function YearStr($year) {
  if ($year <= Pya_Year_Offset)
    return "Year $year";
  return 'Year ' . ($year - Pya_Year_Offset) . ' (Restated)';
}

function BError($msg) {
  global $Table, $Errors, $LineNum, $LinesA;

  if ($Table === 1 && !$Errors) {
    # first call in Table 1 (Pass 1) so dump import lines up to this point
    echo '<table>';
    for ($lineNum=0,$prevLineEmpty=0; $lineNum<=$LineNum; ++$lineNum) {
      $line = trim(preg_replace('/(  +)/m', ' ', $LinesA[$lineNum])); # trim and reduce internal spaces to one space /- djh?? Do in one preg_replace?
      $line = preg_replace('/( 	 )|(	 )|( 	)/m', TAB, $line);        # trim spaces around tabs                      |
      if (!strncmp($line, '#', 1)) { # Output comment lines starting with # without putting into columns
        echo "<tr><td colspan=11>$line</td></tr>\n";
        continue;
      }
      if (!strlen($line)) { # skip empty lines
        if (!$prevLineEmpty)
          echo "<tr><td colspan=11></td></tr>\n";
        $prevLineEmpty = 1;
        continue;
      }
      if (InStr(TAB, $line))
        echo '<tr><td>'.str_replace(TAB, '</td><td>', $line)."</td></tr>\n";
      else
        echo "<tr><td colspan=11>$line</td></tr>\n";
      $prevLineEmpty = 0;
    }
  }
  ++$Errors;
  if ($Table)
    echo "<tr><td colspan=19><span class='L b'>Error $Errors</span>: $msg</td></tr>\n";
  else
    echo "<span class='L b'>Error $Errors</span>: $msg<br>\n"; # during summing or other
  return false;
}

function StackMsg($msg=0, $notice=0) {
  global $Errors, $Debug;
  static $msgsAS;
  if ($msg) {
    if ($Debug) DebugMsg($msg);
    if ($notice)
      $msgsAS[] = "Notice</span>: $msg";
    else{
      ++$Errors;
      $msgsAS[] = "Error $Errors</span>: $msg";
    }
  }else{
    # output i.e. pop when called with no msg
    if ($msgsAS) {
      foreach ($msgsAS as $msg)
        echo "<tr><td colspan=19><span class='L b'>$msg.</td></tr>\n";
      $msgsAS = null;
    }
  }
}

# Results Table
# =============
function ResultsTable($hdg) {
  global $BrosA, $broYearsA;
  # Bro Data for all years re gaps in some years
  $brosUsedA = [];# a 2 dimensional array of Bro use: [broId => [i => year]]
  $numCols = 5; # Ref, SN, Bro Ref, PT, Src
  $yearsHdg = '';
  foreach ($broYearsA as $year => $b) { # 0 - 6
    if (!$b) continue;
    ++$numCols;
    $yearsHdg .= '<td class=r>' . YearStr($year) . '</td>';
    foreach ($BrosA[$year] as $broId => $t)
      $brosUsedA[$broId][] = $year;
  }
  ksort($brosUsedA); # Sort by BroId

  # Through the Bros
  echo "<br><table class=mc>
";
  $n = -1;
  foreach ($brosUsedA as $broId => $yearsA) { # $brosUsedA = [broId => [i => year]]
    $broShortName = BroShortName($broId);

# if ($broId != 9066 && $broId != 1002 && $broId != 9002) continue;
# if ($broId != 9000) continue;

    # Assemble the individual BroDats for this Bro
    $broDatOsA = []; # a 2 dimensional array of Bro entries [DiMeRef => [year => Bro]]
    foreach ($broYearsA as $year => $b) { # 0 - 6
      if (!$b) continue;
      if (in_array($year, $yearsA))
        foreach ($BrosA[$year][$broId]->AllBroDatOs() as $broDatKey => $datO)  # [BroDatKey => BroDatO]
          $broDatOsA[$broDatKey][$year] = $datO;
    }
   #uksort($broDatOsA, 'strnatcmp'); # sort the BroDats for this Bro
    ksort($broDatOsA, SORT_NATURAL);
    $num = count($broDatOsA);
    if ($n-($num>1 ? $num : 0)<0) {
      echo "<tr class='b c bg0'><td colspan=$numCols>$hdg</td></tr>
<tr class='b bg0'><td>Ref</td><td>Short Bro Name</td><td style=min-width:900px>Bro Reference</td><td class=c>PT</td><td class=c>Src</td>$yearsHdg</tr>
";    $n = 50;
    }
    foreach ($broDatOsA as $broDatKey => $yearBroDatOsA) {
      $firstB = $oneSrceB = true;
      foreach ($yearBroDatOsA as $year => $datO) {
        $srce = $datO->Source();
        if ($firstB) {
          $datTds    = '';
          $nextYear  = 0;
          $postType  = $datO->DadBrO->PostTypeStr();
          $firstSrce = $srce;
          $srcStr    = ",$srce";
          $firstB    = false;
        }else{
          if ($srce !== $firstSrce) $oneSrceB = false;
          $srcStr .= ",$srce";
        }
        for (; $nextYear < $year; ++$nextYear)
          if ($broYearsA[$nextYear])
            $datTds .= '<td></td>';
        $datTds .= $datO->FormattedDatTd();
        ++$nextYear;
      }
      $srcStr = $oneSrceB ? StrField($srcStr, ',', 1) : substr($srcStr,1);
      echo '<tr><td>',$datO->BroRef(),"</td><td>$broShortName</td><td class=wball>",$datO->BroRefSrce(),"</td><td class=c>$postType</td><td class=c>$srcStr</td>$datTds</tr>\n";
      --$n;
    }
  }
  echo '</table>
';
}

function ErrorExit($err) {
  echo "<p><span class='b L mt05 mb0'>Error:</span><br>$err.</p>";
  Form();
  ####
}

function Form($timeB=false, $topB=false) {
  global $Dir, $IDebug, $Filei;
  if ($Filei==9999) $Filei = 0;
  $debugVal = $IDebug===false ? '' : " value=$IDebug";
  echo '<h2 class=c>Data Import</h2>
<p class=c>Select the File to import OR paste the Import Data below and click Import</p>
<form method=post>
<div class=mc style=width:300px>
';
  $filesA = scandir($Dir);
  $i = 0;
  $files = '';
  foreach ($filesA as $file) {
    if ($file != '.' && $file != '..' && !is_dir($Dir.$file) && !InStr('.bak', $file) && !InStr('~', $file)) {
      $checked = ($i==$Filei ? ' checked' : '');
      echo "<input id=f$i type=radio class=radio name=File value=$i$checked> <label for=f$i>$file</label><br>\n";
      $files .= ",$file";
      ++$i;
    }
  }
  $files = substr($files, 1);
  echo "</div><br><div class=c><textarea name=Dat placeholder='Or Paste Import Data here' rows=10 cols=80></textarea></div>
<input type=hidden name=Files value='$files'>
<p class='c mt05'>Debug option: <input name=Debug size=2 maxlength=1$debugVal> (Nothing to use import comammnd setting, 0-3 to override the import setting.)</p>

<p class='c mb0'><button class=on>Import</button></p>
</form>
";
  Footer($timeB, $topB); # Footer($timeB=true, $topB=false, $notCentredB=false) {
}

function Debug($msg=0) {
  global $Debug, $DebugMsgs, $yrBrOsA, $BrosA, $year, $BrosThisDataTypeA, $Errors;
  static $msgS, $BroChanges, $ErrorsS;
  if ($Debug) {
    if ($msg) {
      $msgS = $msg;
      echo "<br>$msgS<br>";
      $BroChanges = Bro::Changes();
      $ErrorsS   = $Errors;
      $DebugMsgs = '';
      return;
    }
    if (Bro::Changes() === $BroChanges && $ErrorsS === $Errors)
      echo "No change for $msgS, BroChanges=",Bro::Changes(), '<br>';
    else{
      echo $DebugMsgs,'BroChanges=',Bro::Changes(), '<br>';
      $BrosA[$year] = $yrBrOsA;
      ResultsTable("After $msgS<br>");
      if ($Debug==3) Dump("yrBrOsA after $msgS", $yrBrOsA);
      if ($Debug>=2) Dump("BrosThisDataTypeA after $msgS",$BrosThisDataTypeA);
    }
  }
}

function DebugMsg($msg) {
  global $DebugMsgs;
  $DebugMsgs .= $msg.'<br>';
}

