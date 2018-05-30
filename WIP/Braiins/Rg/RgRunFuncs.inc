<?php /* Copyright 2011-2013 Braiins Ltd

RgRunFuncs.inc
Report Generator Run time functions

History:
09.03.11 djh Started

ToDo djh??
----
Add tuple(s) in row detection to compiler?

Cell data with tag
# djh?? Add colSum processing to CellDat
#       Add with tag version

Add support for column types c and p
named rows by copying row data?
row[0] -> different array to avoid the need for all the col tests for not zero
          or use a map of col # -> row index which would avoid the need to ksort the row, or unset all unused col cells.
          But could not then foreach thru the row with &$cellA

*/

# CreateRTFunctions(['','CellDat(2,1)',....'$rowA','CellThis($rowA,4,1)-CellThis($rowA,3,1)']);
# Passed an array of paramters and code pairs, with code evaluation result expected to be returned
# Used for cell expressions to be evaluated during TableEnd() processing rather than as a Col() parameter expression.
# Functions are created at RT using create_function()
# This mechanism could possibly be used for header and footer functions too.
function CreateRTFunctions($fnsA) {
  global $FnsA;
  $n = count($fnsA);
  for ($i=0; $i<$n;++$i)
  #echo "create_function({$fnsA[$i++]}, return {$fnsA[$i]};<br>";
   $FnsA[] = create_function($fnsA[$i++], "return {$fnsA[$i]};");
}

function RgFooter() {
  global $FooterFn;
  if ($FooterFn) {
    //AddP1Line('<footer>');
    $FooterFn();
    //AddP1Line('</footer>');
  }
}

function RgHeader() {
  global $HeaderFn;
  if ($HeaderFn)
    $HeaderFn();
}

#function EndLine() {
#}

function Expr($expr) {
  if ($expr)
    P($expr);
}

function Lines($n=1) {
  global $TableA; #, $RowA;
  $lines = str_repeat('<br/>', $n);
  if ($TableA) {
   #Row();
   #Col(1, 0, $lines);
    Row(0, [1, 0, $lines]);
  }else
    AddP1Line($lines);
}

function NewPage() {
  global $P1LinesA;
  static $P1LinesAtLastNp=0;
  if (($p1Lines = count($P1LinesA)) > $P1LinesAtLastNp) { # have had output since last NP
    RgFooter();
    AddP1Line('N');
    RgHeader();
    $P1LinesAtLastNp = $p1Lines;
  }
}

# [h2 c:c CompanyRegistrationNumberH ":" [nl] SchInputEntity.IdentifyingCodes.UKHouseRegisteredNumber " (" SchInputEntity.LegalForm.CountryFormationOrIncorporation ")"] Corp/cover.b line 24
# H(2,$HeadingsA['CompanyRegistrationNumberH'].":".'<br/>'.DataWithTag(3)." (".DataWithTag(4).")",'c');
# [h2 c:c DirectorsApostropheS' Report'[nl]'for the Period Ended ' [date f SchInputBRI.DatesPeriods.End]] Corp/dirReport.b line 20
# H(2,$DirectorsApostropheS.' Report'.'<br/>'.'for the Period Ended '.DateStrWithTag(18),'c');
# <h2 class='c'>Directors&#8217; Report<br/>for the Period Ended 31 December 2009</h2>
function H($hLevel, $txt, $css = 0) {
  if ($css)
    AddP1Line("<h$hLevel class='$css'>$txt</h$hLevel>");
  else
    AddP1Line("<h$hLevel>$txt</h$hLevel>");
}

# [p ..
function P($txt, $css = 0) {
  $class = ($css ? " class='$css'" : '');
  if ($txt)
    AddP1Line("<p$class>$txt</p>");
}

# [p with a BroLoopRef
function PBroLoop($broRef, $numLoops, $loopType, $testIdDelta, $css = 0) {
  $class = ($css ? " class='$css'" : '');
  if (is_string($broRef)) {
    MapBroRef($broRef);
    $broRefA = explode(',', $broRef);
  }else
    $broRefA = 0;
  for ($loop = 0; $loop<$numLoops; ++$loop) {
    if ($loop) {
      # increment the broRef after skipping the increment the first time thru
      if ($broRefA) {
        ++$broRefA[$loopType]; # increment BroId or MnDiMeId according to type
        $broRef = implode(',', $broRefA);
      }else
        ++$broRef; # only broId
    }
    if ($testIdDelta) {
      if ($broRefA) {
        $testRefA = $broRefA;
        $testRefA[0] += $testIdDelta;
        $testRef = implode(',', $testRefA);
      }else
        $testRef = $broRef + $testIdDelta;
      if (!Data($testRef))
        continue;
    }
    if ($dat = DataWithTag($broRef))
      AddP1Line("<p$class>$dat</p>");
    #echo "loop=$loop broRef=$broRef dat=$dat<br>";
  }
}

// Set zone sign from [zone{s} statement
function ZoneSign($signN) {
  global $ZoneSignN;
  $ZoneSignN = $signN;
}

// String Generation
// =================

// Date string wo tag, used by compiler when dD is not a BRO
function DateStr($dD, $typeN = DATE_Full, $css = 0) {
  switch ($typeN) {
    case DATE_Full:  $f = '%d %B %Y'; break; // f: full  form e.g. 03 February 2011
    case DATE_Short: $f = '%d/%m/%Y'; break; // s: short form e.g. 03 Feb 2011 or 03/02/2011 or 03.02.2011 as per preferences
    case DATE_iXBRL: $f = '%Y-%m-%d'; break; // x: iXBRL form e.g. 2011-02-03
    case DATE_Year:  $f = '%Y';       break; // y: year  form e.g. 2011
  }
  if ($dS = eeDtoStr($dD, $f)) {
    if ($css) $dS = "<span class='$css'>$dS</span>";
  }
  return $dS;
}

// Date string with tag, used by compiler when dD is a BRO
function DateStrWithTag($id, $typeN = DATE_Full, $year=0, $css = 0) {
  $dD = (int)Data($id, $year);
  switch ($typeN) {
    case DATE_Full:  $f = '%d %B %Y'; break; // f: full  form e.g. 03 February 2011
    case DATE_Short: $f = '%d/%m/%Y'; break; // s: short form e.g. 03 Feb 2011 or 03/02/2011 or 03.02.2011 as per preferences
    case DATE_iXBRL: $f = '%Y-%m-%d'; break; // x: iXBRL form e.g. 2011-02-03
    case DATE_Year:  $f = '%Y';       break; // y: year  form e.g. 2011
  }
  if ($dS = eeDtoStr($dD, $f)) {
    if ($typeN != DATE_Year)
      $dS = Tag($id, $dS, $year);
    if ($css) $dS = "<span class='$css'>$dS</span>";
  }
  return $dS;
}

function PageStr($css = 0) {
  global $SolChr;
  $SolChr = 'P';
  if ($css)
    return "<span class='$css'></span>";
  return '';
}

// [span ..
// span string
function SpanStr($txt, $css = 0) {
  if ($txt) {
    if ($css)
      $s = "<span class='$css'>$txt</span>";
    else
      $s = "<span>$txt</span>";
  }else
    $s = '';
  return $s;
}

function BlockStart() {
}
function BlockEnd() {
}

# Table related functions
/* [table {cols:<t|d|n|0-3>...} {center} {c:css} {noOutput}]

$NamedRowsA array row num => table row copied after processing

TableA array
  Columns info derived from [table statement:
  NCols       Number of columns
  DCol        d or descr col #
  ColYearA    col => 0 | 1 | 2 | 3  to provide mapping between col # and year
  ColTypeA    col => 0 | CT_Text | CT_Descr | CT_Note | CT_Money | CT_Calc | CT_Perc
  MoneyColsB  true if there are money columns

  Attributes:
  Center   center
  Css      css
  NoOutput noOutput

  Rows:
  RowsA numerically indexed array of Rows with each a RowA array of cell arrays as below indexed by col #
        Only named rows are kept once a table has been processed.
    RowA[0] holds information about a row with numerical keys as below:
    Row0I_BroRef   [row b: attribute = BroRef for all cells in the row unless overridden
    Row0I_Options  [row <dr|cr> <keep|keepHide> <ul|dul> <aul|adul> attributes
    Row0I_RowNum   [row n:RowName attribute -> row number. If defined the row is copied to $NamedRowsA[] when completed
    Row0I_FrCols   [row cols: from columns csv list
    Row0I_ToCols   [row cols: to columns csv list
    Row0I_Mcss     [row c: attribute  = css to be applied to Money cell td tags
    Row0I_Rcss     [row rc: attribute = css to be applied to the Row tr tag
    Row0I_AltNum   [row alt:RowName attribute -> alternative text processing row number i.e. different row to use for the balance/sign logic
    End of possible options from format passed to Row()
    Row0I_ZSignN   ZoneSignN at Row() time i.e. at the start of the row
    Row0I_Keep     Initialised to 1. TableEnd() money cols loop sets this to 0 if the row is not to be kept, or to 2 if it is be kept but hidden.

    Added by RowEnd() after processing of a row is complete:
    Row0I_SolChr   Start of line character re possible Xrefs etc in the row
   #TableAR  A reference to the enclosing TableA if the row is a named row to keep the whole table available and specifically the column info for row arith purposes

    Added during TableEnd() processing:
    Row0I_ColSumA    Running column sum to this row
    Row0I_MovedColsA col => moved col, set if subtotal moves a cell, used by CellDat
    Row0I_YrBrackets String of ' ', '1' and '0' by year # for whether the year balnce is used, is to be bracketed or not for alt title processing.

  CellA array with numeric CellI_* keys as below:
    Data added (initially) by Col() or ColBroLoop():
    CellI_Dat      dat (raw i.e. not formatted or sign adjusted). false = undefined to be handled differently
    CellI_BroRef   broRef, set to -1 to flag a blank subtotal/total cell apart from possible aul
    CellI_Restated restated if dat is a restated balance
    CellI_Options  options
    CellI_Css      css
    CellI_Span     span
    Added during TableEnd() processing:
    CellI_Year     Set for a col money balance. Mainly for cell reference with tag purposes to avoid needing to store the table info
    CellI_Brackets Set to 1 for a non zero/false col money balance if balance is to be bracketed, 0 if not
    CellI_XSign    Set to 1 for a non zero/false col money balance if tag is to include a sign attribute.
                   Set if a tag is to be generated (i.e. involving a Tx based Bro) and the balance is adverse
                   Sign info comes from the Bro, Row options, Cell options, and the balance itself.

    Both cell and row BroRef zero means no BRO is involved.

With Braiins Dimensions need to use the Map Bro for tagging but the Main bro for balances via dimensions.
Possible ways of handling this:
- add another

*/

# Row[0] inner array indices
const Row0I_BroRef  = 0;
const Row0I_Options = 1;
const Row0I_RowNum  = 2;
const Row0I_FrCols  = 3; # from columns csv list
const Row0I_ToCols  = 4; # to   columns csv list
const Row0I_Mcss    = 5; # css to be applied to Money cell td tags
const Row0I_Rcss    = 6; # css to be applied to the Row tr tag
const Row0I_AltNum  = 7; # altRowNum
const Row0I_ZSignN  = 8;
const Row0I_Keep    = 9;
const Row0I_SolChr  = 10;
const Row0I_ColSumA = 11;
const Row0I_MovedColsA = 12;
const Row0I_YrBrackets = 13;

# Table Cell inner array indices
# [dat, broRef, restated, options, css, span{, year, brackets, xsign}]
# [CellI_Dat, CellI_BroRef, CellI_Restated, CellI_Options, CellI_Css, CellI_Span{, CellI_Year, CellI_Brackets, CellI_XSign}]
# Data added (initially) by Col() or ColBroLoop()
const CellI_Dat      = 0;
const CellI_BroRef   = 1;
const CellI_Restated = 2;
const CellI_Options  = 3;
const CellI_Css      = 4;
const CellI_Span     = 5;
# Added during TableEnd() processing
const CellI_Year     = 6;
const CellI_Brackets = 7;
const CellI_XSign    = 8;

function TableStart($cols=0, $center=0, $css=0, $noOutput=0) {
  global $TableA;
  $dCol  = $moneyColsB = 0;
  if ($cols)
    $nCols = strlen($cols);
  else
    $nCols = 29;
  $colYearA = array_fill(1, $nCols, 0); # col => 0 | 1 | 2 | 3  to provide mapping between col # and year
  $colTypeA = array_fill(1, $nCols, 0); # col => 0 | CT_Text | CT_Descr | CT_Note | CT_Money | CT_Calc | CT_Perc
  if ($nCols) {
    for ($i=0; $i<$nCols;) {
      $c = $cols[$i++]; # $i is now the col # base 1
      if (ctype_digit($c)) {
        $colYearA[$i] = (int)$c;
        $colTypeA[$i] = CT_Money;
        $moneyColsB = true;
      }else
        switch ($c) {
          case 't': $colTypeA[$i] = CT_Text; break;
          case 'd': $colTypeA[$i] = CT_Descr;
            if (!$dCol) $dCol = $i;
            break;
          case 'n': $colTypeA[$i] = CT_Note; break;
          case 'c': $colTypeA[$i] = CT_Calc; break;
          case 'p': $colTypeA[$i] = CT_Perc; break;
        }
    }
  }
  $TableA = [
   #'Cols'       => $cols,
    'NCols'      => $nCols,
    'DCol'       => $dCol,
    'ColYearA'   => $colYearA,
    'ColTypeA'   => $colTypeA,
    'MoneyColsB' => $moneyColsB,
    'Center'     => $center,
    'Css'        => $css,
    'NoOutput'   => $noOutput,
    'RowsA'      => []
  ];
}

# RowEnd()
# Store a row into TableA['RowsA'] after all Col() calls have completed
# Initialise $RowA for the next row
function RowEnd() {
  global $TableA, $RowA, $SolChr;
  if (count($RowA) > 1) {
    # Dump("RowA ". count($TableA['RowsA']) . " in RowEnd", $RowA);
    $RowA[0][Row0I_SolChr] = $SolChr;
    $TableA['RowsA'][] = $RowA;
    $RowA = [];
  }
}

# Passed a tuple T.all BroRef with Inst = Tu_AllInst = 7999 -> BroRef of BroId,9999,...
# and a progressive max instance, possibly for different tuples in one table row -> the max of all of them
function TupleMaxInstant($broRef, &$maxInst) {
  global $BrosA;
  static $TupMaxInstsA;
  if (!$TupMaxInstsA) {
    # First call so build $TupMaxInstsA
    $TupMaxInstsA = array_fill(1, Max_TupleId, 0);
    for ($year=0; $year<=1; ++$year) # Assumed to only ever be tuple looping for this and comparative years so <=1 rather than <=Max_RelYear
      foreach ($BrosA[$year] as $broId => $brO)
        if ($brO->IsTuple())
          # $brO->MaxInst does not distinguish between Start and End Insts
          $TupMaxInstsA[$brO->TupId] = max($TupMaxInstsA[$brO->TupId], $brO->MaxInst);
  }
  $broId = (int)$broRef;
  for ($year=0; $year<=1; ++$year) { # Assumed to only ever be tuple looping for this and comparative years so <=1 rather than <=Max_RelYear
    if (isset($BrosA[$year][$broId])) {
      $tupId = $BrosA[$year][$broId]->TupId;
      if ($TupMaxInstsA[$tupId] > $maxInst)
        $maxInst = $TupMaxInstsA[$tupId];
      return;
    }
  }
}

# Row(rowOptionsA, colArgsA{,colArgsA...)) generates a table row, or multiple rows for tuple row looping.
# It is Called with a variable length list of arguments as a result of a [row statement and optionally one or more [col statements:
# [row {b:BroRef} {n:RowName} {alt:RowName} {<dr|cr>} {<keep|keepHide>} {<ul|dul>} {<aul|adul>} {mc:css} {rc:css} {<subtotal|total>} {cols:cols list}]
# [col{cols list} {<dr|cr>} {<keep|keepHide>} {<ul|dul>} {<aul|adul>} {restatedHdg} {repeat:intExpr} {span:#} {hor} {c:css} {b:BroRef} {expr}]
# It also performs the row end function i.e. it ends one row and starts the new row
# At least two arguments are expected, rowOptionsA and colArgsA
# rowOptionsA
#  This is zero if there are no row options, not an empty array.
#  Otherwise is is an array of as many (left to right) as are defined of:
#    ($broRef=0, $options=0, $rowNum=0, $frCols=0, $toCols=0, $mcss=0, $rcss=0, $altRowNum = 0) or
#    (Row0I_BroRef, Row0I_Options, Row0I_RowNum, Row0I_FrCols, Row0I_ToCols, Row0I_Mcss, Row0I_Rcss, Row0I_AltNum) default 0 in all cases
# colArgsA
#  Array to specify a row column (cell), with as many (left to right) as are defined of:
#  ($col, $datType=0, $dat=0, $options=0, $css=0, $repeat=0, $span=0)
#  i.e. $col is mandatory, all others optional, default 0.
# colArgsA repeats for additional columns. The array are sorted into ascending column # order by the compiler.
function Row() {
  global $TableA, $RowA, $BroInfoA, $BroDatA, $RestatedYearsA, $ZoneSignN;
  RowEnd(); # finish (store) the previous row if any
  $args  = func_get_args(); # compiler expected to ensure always at least 2
  $arg0  = array_shift($args); # row options or 0 if none. args is now just the cols
  $row0A = [0, 0, 0, 0, 0, 0, 0, 0, $ZoneSignN, 1]; # Row0I_BroRef, Row0I_Options, Row0I_RowNum, Row0I_FrCols, Row0I_ToCols, Row0I_Mcss, Row0I_Rcss, Row0I_AltNum, Row0I_ZSignN, Row0I_Keep
  if ($arg0) $row0A = $arg0 + $row0A; # Union of the arrays if have args for the row
  # Check for tuple loops. djh?? Move some of this back to the compiler.
  # A T.all tuple loop is indicated by a BroRef with Inst = Tu_AllInst = 7999 -> BroRef of BroId,9999,...
  $maxInst = 0;
  if (is_string($rowBroRef = $row0A[Row0I_BroRef]) && strpos($rowBroRef, ',9999,')) # leading , prevents match on a BroId of 9999
    TupleMaxInstant($rowBroRef, $maxInst);
  foreach ($args as $arg) {
    if (isset($arg[1])) { # $datType
      $datType = $arg[1];
      $dat     = isset($arg[2]) ? $arg[2] : 0;
      if ($datType & CDtB_BroRef) { # as per Col() code below. See that for comments.
        $broRef = ($datType & CDtB_RowBroRef) ? $rowBroRef : (($datType & CDtB_ColBroRef) ? $dat : $dat[0]);
        if (strpos($broRef, ',9999,'))
          TupleMaxInstant($broRef, $maxInst);
      }
    }
  }
  for ($inst = $maxInst ? 1 : 0; $inst <= $maxInst; ++$inst) { # once for non-tuple loop cases with $inst = $maxInst = 0
    if ($inst > 1)
      RowEnd();
    $RowA[0] = $row0A;
    # Add the columns to $RowA
    # Prior to the 13.12.11 rearrangment cols were added via calls to Col() with arguments:
    #  ($col, $datType=0, $dat=0, $options=0, $css=0, $repeat=0, $span=0)
    # i.e. $col is mandatory, all others optional, default 0. These arguments are now passed via arrays, indexed in the same order.
    foreach ($args as $arg) {
      list($col, $datType, $dat, $options, $css, $repeat, $span) = ($arg + [1 => 0, 0, 0, 0, 0, 0]); # array union to give 0 defaults from $datType upwards
      $broRef = $restated = 0;
      if ($datType) { # no $datType means dat is the data via a parameter expression
        # Bits for dt parameter to [col statement Col() call :
        #                      dat passed     ->
        #                      param expr     dat from param expr with tag if Row BroRef is defined
        # CDtB_ColBroRef     1 Col BroRef     Col Bro for dat and tag
        # CDtB_ColBroRefExpr 2 [BroRef, Expr] Bro from dat[0] for tag, dat from parameter expression in dat[1], or Expr = fnNum for later evaluation if CDtB_CellExpr is also set
        # CDtB_RowBroRef     4 0              Row Bro for dat and tag
        # CDtB_Mapping       8 -              BroRef is to be checked for possible mapping
        # CDtB_Descr        16 -              BroRef is to be used for a Descr() call, else for Bro data
        # CDtB_RowOp        32 0              Dat is to come from a row operation - subtotal, total, rowExpr
        # CDtB_CellExpr     64 fnNum          Dat is to come from a call to $FnsA[fnNum], evaluated in TableEnd() pass when running col sums and RowOp values are available.
                             #                Can also be set for a CDtB_ColBroRefExpr Expr
        # Combos
        # CDtB_BroRef         CDtB_ColBroRef | CDtB_ColBroRefExpr | CDtB_RowBroRef
        # CDtB_ColBroRefDescr CDtB_ColBroRef | CDtB_Descr
        # CDtB_RowBroRefDescr CDtB_RowBroRef | CDtB_Descr
        # CDtB_ColBroRef      use Col BroRef from dat for dat and tag
        if ($datType & CDtB_BroRef) { # CDtB_ColBroRef | CDtB_ColBroRefExpr | CDtB_RowBroRef
          # CDtB_RowBroRef      BroRef = RowBroRef /- in order of expected frequency of use
          # CDtB_ColBroRef      BroRef = $dat      |
          # CDtB_ColBroRefExpr  BroRef = $dat[0]   |
          $broRef = ($datType & CDtB_RowBroRef) ? $RowA[0][Row0I_BroRef] : (($datType & CDtB_ColBroRef) ? $dat : $dat[0]);
          if ($datType & CDtB_Mapping)
            MapBroRef($broRef);
          if ($inst && strpos($broRef, ',9999,')) {
            $broRef = str_replace(',9999,', ','.(2000+$inst).',', $broRef);
          }
        } # else no Col or Row BroRef, only expected for a RowOp
        if ($datType & CDtB_CellExpr) #  Dat is to come from a call to fnNum, evaluated in TableEnd() pass when running col sums and RowOp values are available.
          $dat = ($datType & CDtB_ColBroRefExpr) ? $dat[1] : $dat;
        else if ($datType & CDtB_RowOp) # Dat is to come from a row expression at TableEnd() time so just set to false here
          $dat = false;
        else{
          if ($datType & CDtB_Descr) {
            $dat = Descr($broRef);
            $broRef = 0; # no tag
          }else{
            if ($datType & CDtB_ColBroRefExpr)
              $dat = $dat[1];
            else{
              # Bro data
              $year = $TableA['ColYearA'][$col];
              if (isset($BroDatA[$year][$broRef]))
                $dat = $BroDatA[$year][$broRef];
              else
                $dat = false;
              if ($RestatedYearsA[$year]) {
                # have restated data for this year
                $rYear = $year + Pya_Year_Offset;
                if (isset($BroDatA[$rYear][$broRef])) {
                  $rDat = $BroDatA[$rYear][$broRef];
                  if ($rDat !== $dat) { # Restated
                    $dat = $rDat;
                    $restated = 1;
                  }
                }else
                  $dat = false;
              }
              # If this is a date Bro ref convert to string here to simplify TableEnd() code
              if ($BroInfoA[(int)$broRef][BroI_DataTypeN] == DT_Date)
                $dat = eeDtoStr($dat, '%Y');
              #else if ($dat !== false && !is_int($dat))
              #  $dat = htmlspecialchars($dat, ENT_NOQUOTES); # djh?? Sort out the html special characters at import/posting time
            }
          }
        }
      }else # else dat from parameter expression with $restated = 0
        $broRef = $RowA[0][Row0I_BroRef]; # and $broRef = Row BroRef which could = 0 = no tag

      # [dat, broRef, restated, options, css, span]
      # [CellI_Dat, CellI_BroRef, CellI_Restated, CellI_Options, CellI_Css, CellI_Span]
      if ($repeat) {
        for ($i = 0; $i < $repeat; ++$i) {
          $RowA[$col++] = [$dat, $broRef, $restated, $options, $css, $span];
          $span = 0;
        }
      }else # not repeat
        $RowA[$col] = [$dat, $broRef, $restated, $options, $css, $span];
    } # end of columns loop
  }
}

# [col with a BroLoopRef
function ColBroLoop($col, $broRef, $numLoops, $loopType, $testIdDelta, $css=0, $options=0) {
  global $TableA, $RowA;
  if (is_string($broRef)) {
    MapBroRef($broRef);
    $broRefA = explode(',', $broRef);
  }else
    $broRefA = 0;
  for ($loop = 0; $loop<$numLoops; ++$loop) {
    if ($loop) {
      # increment the broRef after skipping the increment the first time thru
      if ($broRefA) {
        ++$broRefA[$loopType]; # increment BroId or MnDiMeId according to type
        $broRef = implode(',', $broRefA);
      }else
        ++$broRef; # only broId
    }
    if ($testIdDelta) {
      if ($broRefA) {
        $testRefA = $broRefA;
        $testRefA[0] += $testIdDelta;
        $testRef = implode(',', $testRefA);
      }else
        $testRef = $broRef + $testIdDelta;
      if (!Data($testRef))
        continue;
    }
    if ($dat = Data($broRef)) {
      if ($options & OptB_hor) # Horizontal loop
        $RowA[$col++] = [$dat, $broRef, 0, 0, $css, 0]; # [dat, broRef, restated, options, css, span]
      else{ # Vertical loop
        if ($loop) {
          RowEnd(); # Finish the previous row and start a new row.
          $RowA[0] = $row0A; # restore the row settings
        }else
          $row0A = $RowA[0]; # preserve the row settings first time thru
        $RowA[$col] = [$dat, $broRef, 0, 0, $css, 0]; # first time thru adds this col; creates just this col on subsequent loops
      }
    }
  }
}

# TableEnd() - where it all happens...
function TableEnd() {
  global $TableA, $SolChr, $RestatedYearsA, $BracketedWordPairsA, $BroInfoA, $RunMsg, $NamedRowsA, $PreferencesA, $FnsA;
  #echo "TableEnd() start<br>";
  RowEnd();
  $tableStartedB = false; # could conceivably have an empty table so use this to prevent output of table tag in that case.
  $nCols         =  $TableA['NCols'];
  $dCol          =  $TableA['DCol'];
  $colYearA      = &$TableA['ColYearA'];
  $colTypeA      = &$TableA['ColTypeA'];
  $moneyColsB    =  $TableA['MoneyColsB'];
  $rowsA         = &$TableA['RowsA'];
  $colRestatedA  = array_fill(1, $nCols, 0); # col => 0 | 1 if col is a money col which includes a restated balance
  if ($moneyColsB) {
    # This table has money columns so pass thru all rows and:
    # - Compute the running column sums
    # - Increment col use counts
    # - Perform any row arithmetic, subtotals, totals (when defined)
    # - Set the sign of money balances in preparation for possible alternative title processing and subsequent formatting
    # - Set $colRestatedA
    # - Delete unused columns
    $colSumA = array_fill(1, $nCols, false); # col => running sum for money cols
    $colUseA = array_fill(1, $nCols, false); # Incremented when a non false balance is deposited to the column. Reset to 0 on a subtotal
    $prevVisibleSubtotalRowi = -1;            #  if not false. false at the end means col never used apart from for possible headings.
    foreach ($rowsA as $rowi => &$rowA) { # reference to $rowA so that it can be updated
      #if ($rowi == 26) Dump("row $rowi of rowsA", $rowA);
      $cellA      = &$rowA[0];
      $cellA[Row0I_MovedColsA] = array_fill(1, $nCols, 0); # col => moved col, set if subtotal moves a cell, used by CellDat
      $rowNum     = $cellA[Row0I_RowNum];
      $rowAltNum  = $cellA[Row0I_AltNum];
      $rowOptions = $cellA[Row0I_Options];
      $rowZSignN  = $cellA[Row0I_ZSignN]; # $ZoneSignN at start of row
      $rowKeepOpts= $rowOptions & OptB_AnyKeep;
      $rowKeep    = 1; # = $rowA[0][Row0I_Keep] which is always 1 at this point
      $rowKeepB   = $gotMoneyColB = false; # $rowKeepB is set if have anything other than zero incl text in a money column re keeping or discarding the rolw
      $yrBrackets = str_repeat(' ', 4);    # $gotMoneyColB is set if the row has any money cols in use i.e. not just t, d, or n
      if ($rowOptions & OptB_cellExpr)     # brackets string by year for alt text purposes with ' ' = no year result
        # Have at least one CellExpr in the row. Do before possible subtotal/total
        foreach ($rowA as $col => &$cellA)
          if ($col && ($cellA[CellI_Options] & OptB_cellExpr))
            $cellA[CellI_Dat] = $FnsA[$cellA[CellI_Dat]]();
      if ($rowOp = $rowOptions & OptB_AnyTotalOp) {
        # subtotal and total. total is the same as subtotal here. Later, after possible thisExprs evaluation, total has the extra step of zeroing the to col sums
        #Dump("colSumA before rowOp $rowOp on row $rowi", $colSumA);
        $frCols = $cellA[Row0I_FrCols];
        # Could have frCols, toCols cases, but not just toCols:
        # frCols toCols Action
        #               Subtotal the money cols
        #   *           Subtotal the frCols etc sum any the other money cols (rare) as the sum won't be done in the next loop
        #   *      *    Subtotal the frCols, create col entries for the toCols cols, sort the cols, sum the toCols, and sum any other money cols (rare).
        if ($frCols && ($toCols = $cellA[Row0I_ToCols])) {
          # frCols and toCols case
          $frColsA = CsListToIntA($frCols);
          $toColsA = CsListToIntA($toCols);
          $toCol = 0;
          foreach ($rowA as $col => &$cellA) # [dat...]
            if ($col && $colTypeA[$col] == CT_Money && $col > $toCol) { # the $col > $toCol test is because just added toCols came up in the foreach thanks to reference to $cellA
              list($dat, $broRef, , $options, $css) = $cellA; # [dat, broRef, restated, options, css, span] with restated and span skipped
              if (($frColi = array_search($col, $frColsA)) !== false) {
                # col is in the frCols list with index $frColi
                $dat = $colSumA[$col]; # the fr colSum
                $restated = $colRestatedA[$col];
                if (($toCol = $toColsA[$frColi]) == $col) {
                  # toCol == the frCol. Not all fr can == to (equal lists prevented by compiler) but some could be
                  $cellA[CellI_Dat] = $dat;      # deposit the colSum for to == fr
                  $cellA[CellI_Restated] = $restated; # and update restated
                }else{
                  # toCol is different from the frCol
                  # frCol (col)
                  # -----
                  if ($colUseA[$col] == 1) {
                    # If only one balance has been deposited to the fr col, move that balance to the to col and do the subtotal in just the to col.
                    for ($i=$rowi-1; $i>=0; --$i) {
                      if (isset($rowsA[$i][$col]) && $rowsA[$i][$col][CellI_Dat]==$dat) {
                        # toCol of the $i row
                        if (isset($rowsA[$i][$toCol])) die("Die with toCol $toCol defined in row $i with balance to be moved on subtotal with one balance in column"); #djh Temporary
                        $rowsA[$i][$toCol] = $rowsA[$i][$col];
                        $rowsA[$i][0][Row0I_MovedColsA][$col] = $toCol;
                        unset($rowsA[$i][$col]);
                        ksort($rowsA[$i]);
                        if ($dat !== false) {
                          $colSumA[$toCol] += $dat;
                          $colUseA[$toCol] += 1;
                        }
                        # Set $colSumA and $colUseA for the old col to 0 or false.
                        $use = false;
                        for (--$i; $i>=0; --$i) {
                          if (isset($rowsA[$i][$col]) && is_int($rowsA[$i][$col][CellI_Dat])) {
                            $use = 0;
                            break;
                          }
                        }
                        $colSumA[$col] = $colUseA[$col] = $use;
                        # blank the unused fr col in the subtotal row
                        $cellA[CellI_Dat] = false; # dat is not used for output but set to false re checking equal subtotals for use on subtotal col moves
                        $cellA[CellI_BroRef] = -1; # set broRef to -1 to flag this as a subtotal from col that is not to be output
                        $cellA[CellI_Options] &= ~OptB_AnyUl; # zap all underlines
                        # now the subtotal in the to row. No add as that has been done above for the moved balance.
                        $dat = $colSumA[$toCol];
                        $restated = $colRestatedA[$toCol];
                        if (isset($rowA[$toCol])) die("Die with toCol $toCol defined in subtotal row"); #djh Temporary
                        $rowA[$toCol] = [$dat, $broRef, $restated, $options, $css, 0]; # 0 for span
                        if ($dat !== false)
                          $colUseA[$toCol] += 1;
                        continue 2; # out of the look back thru the rows loop and continue the cell loop for the current row
                      }
                    }
                    die("Die - didn't find expected row for moving on row $rowi subtotal of col $col with only 1 balance deposited");
                  }
                  # else normal fr to subtotal/total with more than 1 balance in the fr col
                  # Set the broRef to -1 to flag this as a subtotal from col that is not to be output apart from auls
                  if ($colSumA[$col] !== false) # zero the fr col sum and count if something had been deposited to the col
                    $colUseA[$col] = $colSumA[$col] = 0;
                  $cellA[CellI_Dat] = false; # dat is not used for output but set to false re checking equal subtotals for use on subtotal col moves
                  $cellA[CellI_BroRef] = -1; # set broRef to -1 to flag this as a subtotal from col that is not to be output apart from auls
                 #$cellA[CellI_Restated] =  0; # restated not used
                  $cellA[CellI_Options] &= ~OptB_AnyBul; # zap below underlines
                  # toCol different from frCol
                  # -----
                  if (isset($rowA[$toCol])) die("Die with toCol $toCol defined in subtotal row"); #djh Temporary
                  $options &= ~OptB_AnyAul; # zap above underlines
                  $rowA[$toCol] = [$dat, $broRef, $restated, $options, $css, 0]; # 0 for span
                  if ($restated) $colRestatedA[$toCol] = 1;
                  if ($dat !== false) {
                    $colSumA[$toCol] += $dat;
                    $colUseA[$toCol] += 1;
                  }
                }
              }else # Have a money col which isn't in the frCols list so sum it if not false and not a thisExpr cell
                if ($dat !== false && !($options & OptB_thisExpr)) {
                  $colSumA[$col] += $dat;
                  $colUseA[$col] += 1;
                 }
            }
          unset($cellA);
          ksort($rowA); # to get cols back into col # order after the addition of to cols
          $prevVisibleSubtotalRowi = $rowi;
          # End of fr to subtotal/total
        }else{
          # Same cols subtotal or total i.e. no toCols. Could have frCols tho.
          # frCols Action
          #        Deposit to all money cols
          #   *    Deposit to frCols & sum any other money cols (rare) as the sum won't be done in the next loop = same as subtotal with just frCols
          if ($frCols) {
            # subtotal/total same cols for the fr cols
            $frColsA = CsListToIntA($frCols);
            foreach ($rowA as $col => &$cellA) # [dat...]
              if ($col && $colTypeA[$col] == CT_Money) {
                $dat = $colSumA[$col]; # the fr colSum
                if (in_array($col, $frColsA)) { # col is in the frCols list
                  $cellA[CellI_Dat] = $dat;                     # deposit the colSum
                  $cellA[CellI_Restated] = $colRestatedA[$col]; # and update restated
                  if ($dat !== false)
                    $colUseA[$col] += 1;
                }else # Have a money col which isn't in the frCols list so sum it if not false and not a thisExpr cell
                  if ($dat !== false && !($cellA[CellI_Options] & OptB_thisExpr)) {
                    $colSumA[$col] += $dat;
                    $colUseA[$col] += 1;
                  }
              }
          }else{
            # subtotal same cols for all money cols
            foreach ($rowA as $col => &$cellA) # [dat...]
              if ($col && $colTypeA[$col] == CT_Money && !($cellA[CellI_Options] & OptB_thisExpr)) {
                $cellA[CellI_Dat] = $dat = $colSumA[$col];    # deposit the colSum
                $cellA[CellI_Restated] = $colRestatedA[$col]; # and update restated
                if ($dat !== false)
                  $colUseA[$col] += 1;
              }
          }
          unset($cellA);
          if ($rowOp == OptB_subtotal) {
            # Subtotal discard/hide check. Done only for same cols subtotals. The same cols subtotals could, however, equal a prior fr to one.
            # If row is not a keep row see if subtotal is a repeat of a subtotal with no change
            # -> discard it or the previous visible one if it had no title and this one does, if no tags, or hide it if is has tags
            $visibleSubtotalRowi = $rowi; # this one to start
            if (!$rowKeepOpts && $prevVisibleSubtotalRowi >= 0) {
              # Not a keep row and have a previous visible subtotal
              # If there is nothing between it and this subtotal other than discarded or hidden rows then check this subtotal.
              $zappaSubtotalB = true;
              for ($i=$prevVisibleSubtotalRowi+1; $i<$rowi; ++$i)
                if ($rowsA[$i][0][Row0I_Keep] == 1) {
                  $zappaSubtotalB = false; # got a row to be output so stop checking
                  break;
                }
              if ($zappaSubtotalB) {
                foreach ($rowA as $col => $cellA) { # [$dat, broRef, ...]
                  if ($col && $colTypeA[$col] == CT_Money && !($cellA[CellI_Options] & OptB_thisExpr) &&
                      (!isset($rowsA[$prevVisibleSubtotalRowi][$col]) || $cellA[CellI_Dat] != $rowsA[$prevVisibleSubtotalRowi][$col][CellI_Dat])) {
                    # Money col different, either does not exist in previous subtotal row or the balance is different
                    $zappaSubtotalB = false; # this subtotal is different
                    break;
                  }
                }
                if ($zappaSubtotalB) {
                  # Which to discard/hide?
                  if ($dCol && !isset($rowsA[$prevVisibleSubtotalRowi][$dCol]) && isset($rowA[$dCol]))
                    # Discard/hide the previous visible one as it had no title whereas this one does
                    $zapi = $prevVisibleSubtotalRowi;
                  else{
                    # Discard/hide this one as the previous visible one has a title, or both don't have titles
                    $zapi = $rowi;
                    $visibleSubtotalRowi = $prevVisibleSubtotalRowi; # so $prevVisibleSubtotalRowi remains unchanged below
                  }
                  # Discard zapi row if no tags, or hide it if the row has tags
                  $tagsB = false;
                  foreach ($rowsA[$zapi] as $col => $cellA) { # [$dat, broRef, ...]
                    if ($col && ($broRef = $cellA[CellI_BroRef]) && $broRef != -1) {
                      if (TagToCome($broRef)) { # a tag will be forthcoming
                        $tagsB = true;
                        break;
                      }
                    }
                  }
                  $rowsA[$zapi][0][Row0I_Keep] = $flag = ($tagsB ? 2 : 0);  # Hide/Discard the zapi subtotal
                  if ($zapi == $rowi) # this row
                    $rowKeep = $flag;
                }
              }
            }
            $prevVisibleSubtotalRowi = $visibleSubtotalRowi;
          }
          # End of same cols subtotal/total
        }
        # End of subtotal/total
        #Dump("colSumA after rowOp $rowOp on row $rowi", $colSumA);
        #Dump("colUseA after rowOp $rowOp on row $rowi", $colUseA);
        #if ($rowi == 26) Dump("row $rowi of rowsA after subtotal", $rowA);
      }
      if ($rowKeep || $rowNum) { # Only do the rest if the row is to be kept or it is a named row. (Subtotal could have set the row to be discarded.)
        foreach ($rowA as $col => &$cellA) {
          if (!$col || $colTypeA[$col] != CT_Money)
            continue; # cell loop
          $gotMoneyColB = true;
          $cellA[CellI_Year] = $year = $colYearA[$col];
          list($dat, $broRef, $restated, $options) = $cellA; # [dat, broRef, restated, options, css, span]
          if ($options & OptB_thisExpr) {                  # Process a thisExpr here where colSumA has been updated for cols to the left
            $rowA[0][Row0I_ColSumA] = $colSumA;                # If this is ever required for cell values to the right then will have to
            $cellA[CellI_Dat] = $dat = $FnsA[$dat]($rowA); # loop afterwards and skip colSumA etc updating. $rowA passed as parameter when a this row Expr
            if ($dat !== false)
              $colUseA[$col] += 1;
            if (is_string($dat))
              # string from thisExpr
              continue; # the cell loop
          }else{
            # Not a thisExpr col
            # Dump("dat in cell loop with broRef $broRef", $dat);
            if (is_string($dat) || $broRef == -1) {
              # string or empty subtotal/total from cell
              $rowKeepB = true;
              continue; # the cell loop
            }
            # numeric
            if (!$dat && !$restated) { # dat not false if restated is set tho it could be a real 0
              # dat is 0 or false
              if ($options & OptB_AnyKeep) # even tho balance is false/zero with cell keep options set we need to keep the cell and thus the row
                $rowKeepB = true;
              if (!$rowOp && $dat !== false) {
                $colSumA[$col] += 0; # just to switch a potentially still false sum to int
                $colUseA[$col] += 1; # increment for a real 0. ++ doesn't change false to 1 whereas += 1 does
              }
              if ($yrBrackets[$year] == ' ')
                $yrBrackets[$year] = '0';
              continue; # the cell loop
            }
            # got a balance or a real 0 restated
            $rowKeepB = true;
            if ($restated) $colRestatedA[$col] = 1;
            if (!$rowOp) {
              $colSumA[$col] += $dat;
              $colUseA[$col] += 1; # ++ doesn't change false to 1 whereas += 1 does
            }
          }
          $balSignN  = $dat <= 0 ? BS_Cr : BS_Dr;
          $cellSignN = $options & OptB_AnySign; # cell sign is set to the row sign at CT if not defined, tho row sign might not be defined either.
          if ($broRef) {
            # Bro involved and not set to -1 for a subtotal or total from col which is detected above
            $broAR = &$BroInfoA[(int)$broRef];
            $broSignN = (($broAR[BroI_SignN] ? : $cellSignN) ? : $rowZSignN); # bro sign = Bro SignN fall back to cellSignN fall back to rowZSignN
            if (TagToCome($broRef)) { # a tag will be forthcoming
              if (!$broSignN) {
                $broSignN = $balSignN;
                $RunMsg .= "\nNo BRO, Zone, or Row sign set when generating tag for BRO Ref $broRef column $col and " . ($balSignN == BS_Cr ? 'Credit' : 'Debit') .
                           ' money balance ' . number_format($dat) . '. Sign of balance used';
              }
              $cellA[CellI_XSign] = $balSignN != $broSignN;
            }else
              $broSignN = $broSignN ? : $balSignN;
            $bSignN = (($cellSignN ? : $rowZSignN) ? : $broSignN); # bracketting sign = cellSignN fall back to rowZSignN fall back to broSignN
            #echo "dat $dat bSignN=$bSignN, balSignN=$balSignN, cellSignN $cellSignN, rowZSignN=$rowZSignN, broSignN=$broSignN<br>";
          }else
            # No tag to come
            $bSignN = (($cellSignN ? : $rowZSignN) ? : $balSignN); # bracketting sign = cellSignN fall back to rowZSignN fall back to balSignN
          #echo "dat $dat bSignN=$bSignN, balSignN=$balSignN, cellSignN $cellSignN, rowZSignN=$rowZSignN<br>";
          $cellA[CellI_Brackets] = $b = ($balSignN != $bSignN ? 1 : 0); # 1 if bracketted, 0 if not
          if ($yrBrackets[$year] == ' ')
            $yrBrackets[$year] = chr($b + 48);
        } # end of cells loop
        $rowA[0][Row0I_ColSumA] = $colSumA; # stored for subsequent access by cell expressions
      /*if ($rowOptions & OptB_thisExpr) { Replaced by code within the above loop which is faster if only this values to the left are all that is ever required.
          # Have at least one thisExpr in the row.  If values to the right are ever needed this will need to be restored and code added above to skip
          foreach ($rowA as $col => &$cellA)        unwanted bits.
            if ($col && ($cellA[CellI_Options] & OptB_thisExpr)) {
              $cellA[CellI_Dat] = $dat = $FnsA[$cellA[CellI_Dat]]($rowA);
              if ($dat != false) {
                $colSumA[$col] += $dat;
                $rowA[0][Row0I_ColSumA] = $colSumA;
                $colUseA[$col] += 1;
              }
            }
        } */
        unset($cellA);
        if ($rowOp == OptB_total) {
          # Final total step of zeroing the col sums and counts once any possible this exprs have been evaluated.
          # Could have frCols, toCols cases, but not just toCols:
          # frCols toCols Action
          #               Zero money col sums and counts
          #   *           Zero frCols    sums and counts
          #   *      *    Zero toCols    sums and counts
          if ($frCols && $toCols) {
            # frCols and toCols case
            # Zero toCols sums and counts
            foreach ($toColsA as $col)
              if ($colSumA[$col] !== false) # zero the col sum and count if something had been deposited to the col
                $colUseA[$col] = $colSumA[$col] = 0;
            # End of fr to total
          }else{
            # Same cols total i.e. no toCols. Could have frCols tho.
            # frCols Action
            #        Zero money col sums and counts
            #   *    Zero frCols    sums and counts
            if ($frCols) {
              # Zero frCols sums and counts
              foreach ($frColsA as $col)
                if ($colSumA[$col] !== false) # zero the col sum and count if something had been deposited to the col
                  $colUseA[$col] = $colSumA[$col] = 0;
            }else
              # Zero money col sums and counts
              foreach ($rowA as $col => $cellA)
                if ($col && $colTypeA[$col] == CT_Money &&
                    $colSumA[$col] !== false)              # zero the col sum and count if something had been deposited to the col
                      $colUseA[$col] = $colSumA[$col] = 0;
          } # End of same cols total
        } # End of total post processing
        $rowA[0][Row0I_YrBrackets] = $yrBrackets;

        # Set Row Keep to 0 if the row is not to be kept, or to 2 if it is be kept but hidden.
        if ($rowKeep == 1 && !$rowKeepB && $gotMoneyColB) { # $gotMoneyColB test so as not to zap row if just title text and or note output
          # Row Keep not already set to 0 or 2 for a subtotal
          # no non zero balances unless restated and no zero cells with cell keep, and not just text
          if (!$rowKeepOpts) # empty (money) row not set for row keep so flag it as not for output
            $rowKeep = 0;    # Discard the row. Flag used rather than unset($rowsA[$rowsi] in case this is a named row. Even if it wasn't a
          else               # named row the possibility of row not being output would mean the balances having to be chcked again. Thus flag it.
            # else rowKeepOpts is OptB_keep or OptB_keepHide
            if ($rowKeepOpts & OptB_keepHide)
              $rowKeep = 2; # keep the row but hide it
            #else leave Row Keep = 1 for keep
        } #else leave Row Keep = 1 as initialised for a text row or a row with balances or 0 restated, or set to 0 or 2 for a subtotal
        $rowA[0][Row0I_Keep] = $rowKeep;
        # Alternative text if applicable
        if ($rowKeep && $dCol && isset($rowA[$dCol]) && # If there are money columns and row is being kept and have a d column see if the d column decription contains (*) and may need to be modified
           ($lb = strpos($descr = $rowA[$dCol][CellI_Dat], '(')) !== false && ($rb = strpos($descr, ')', $lb))) { # got ( ) so could be
          $bWord = substr($descr, $lb+1, $rb-$lb-1); # excluding the ()s
          #echo "row $rowi bWord=|$bWord|<br>";
          $bWordLc = strtolower($bWord);
          if (isset($BracketedWordPairsA[$bWordLc])) {
            $oWord = $BracketedWordPairsA[$bWordLc];
            if (is_array($oWord)) {
              foreach ($oWord as $oWord)
                if (($owp = stripos($descr, $oWord)) !== false) {
                  $oLen = strlen($oWord);
                  if (($owp < $lb && $lb - $owp > $oLen + 4) || ($owp > $rb && $owp - $rb > 4))
                    $owp = 0; # not adjacent with allowance for up to 3 separator characters e.g. ' / '
                  else
                    break;
                }
            }else
              if (($owp = stripos($descr, $oWord)) !== false) {
                $oLen = strlen($oWord);
                if (($owp < $lb && $lb - $owp > $oLen + 4) || ($owp > $rb && $owp - $rb > 4))
                  $owp = 0;
              }
            if ($owp !== false) { # have $oWord adjacent to $bWord
              $oWord = substr($descr, $owp, $oLen); # $oWord in case of source
              if ($owp < $lb) {
                $pp = $owp;
                $pair = substr($descr, $pp, $rb-$owp+1); # including the final )
              }else{
                $pp = $lb;
                $pair = substr($descr, $pp, $owp+$oLen-$lb); # including the initial (
              }
              $sep = str_replace(["($bWord)", $oWord], '', $pair); # sep = all that is left.
              # echo "row $rowi bWord=|$bWord| oWord=|$oWord| pair=|$pair| sep=|$sep|<br>";
              # Now to massage the text, using the brackets string for this or the alt row
              # Cases:
              # All 1 = all bracketed
              # All 0 = all not bracketed
              # 1 - 0 = b - not
              # 0 - 1 = not - b
              $altBrackets = ($rowAltNum ? $NamedRowsA[$rowAltNum][0][Row0I_YrBrackets] : $yrBrackets);
              #if ($rowAltNum) echo "roq $rowi altBrackets=$altBrackets<br>";
              if ($altBrackets[0]) {
                # all b or  b - not
                if (strpos($altBrackets, '0'))
                  # b - not: replace the pair by bWord oWord
                  $descr = str_ireplace($pair, "($bWord)$sep$oWord", $descr);
                else
                  # all b: replace the pair by the bWord
                  $descr = str_ireplace($pair, $bWord, $descr);
              }else{
                # all not or not -b
                if (strpos($altBrackets, '1'))
                  # not - b: replace the pair by oWord bWord
                  $descr = str_ireplace($pair, "$oWord$sep($bWord)", $descr);
                else
                  # all not: replace the pair by the oWord
                  $descr = str_ireplace($pair, $oWord, $descr);
              }
              # Capitalise the first letter of descr if pair was at the start
              if (!$pp) {
                $i = 0 + ($descr[0] === '(');
                $descr[$i] = strtoupper($descr[$i]);
              }
              $rowA[$dCol][0] = $descr;
            } # end of if have other word adjacent
          } # end of if have a bracketed title word
        } # end of if alt title
      } # end of if ($rowKeep || $rowNum) { # skip the rest if subtotal set to be discarded unless it is a named row
      if ($rowNum)
        $NamedRowsA[$rowNum] = $rowA;  # Copy the finished row to NamedRowsA
      #Dump("colSumA after row $rowi", $colSumA);
      #Dump("colUseA after row $rowi", $colUseA);
    } # end of money row loop through TableA['RowsA]'
    # Check for unused columns and zap any found
    foreach ($colTypeA as $col => $colTypeN)
      if ($colTypeN == CT_Money && $colUseA[$col] === false) {
        # unused column - zap it. Could check for multiple cols and then zap all in one row loop.
        foreach ($rowsA as &$rowA) # reference to $rowA so that it can be updated
          unset($rowA[$col]); # don't need to test i.e. if (isset($rowA[$col])) as unset does not error if the var doesn't exist
      }                       # don't need to do anything about colSumA and colUseA
  } # end of if ($monyeyColsB) block
  unset($rowA); # finished with the row reference. Note: For two loops above
  if ($TableA['NoOutput'])
    return; # Finished for a noOutput table

  # Output loop through rows
  foreach ($rowsA as $rowA) {
    $cellA   = $rowA[0];
    $rowMCss = $cellA[Row0I_Mcss];
    $rowRCss = $cellA[Row0I_Rcss];
    $SolChr  = $cellA[Row0I_SolChr]; # for the AddP1Line() call
    if ($moneyColsB)
      $keep = $cellA[Row0I_Keep]; # $keep could be 0: discard, 1: normal or keep, 2: keepHide
    else{
      # No money cols table - check if row should be kept
      $keep = 0;
      foreach ($rowA as $col => $cellA)
        if ($col && $cellA[CellI_Dat]) {
          $keep = 1;
          break;
        }
    }
    if (!$keep)
      continue; # continue the output rows loop without any output as the row has been flagged as not to be output
    # $keep could be 1 = normal or keep; 2 = keepHide
    # Build the row html
    $row = ($keep == 2 ? "<tr class='hide'>" : ($rowRCss ? "<tr class='$rowRCss'>" : '<tr>')); # $keep == 2 -> keepHide row
    $prevCol = 0;
    foreach ($rowA as $col => $cellA) { # [dat, broRef, restated, options, css, span]
      if (!$col)
        continue;
      list($dat, $broRef, $restated, $options, $css, $span) = $cellA;
      $atts = '';
      if ($span) {
        if (($span = min($span, $col - $prevCol)) > 1) { # checked by the compiler for first col of a cols list but might not apply for subsequent columns of the list
          $atts = " colspan='$span'";
          $prevCol = $col - $span; # re skipping below
        }
      }
      if ($col - $prevCol > 1) { # col(s) skipped
        while ($prevCol < $col-1) {
          ++$prevCol;
          $row .= '<td></td>';
          if ($colTypeA[$prevCol] == CT_Money)
            $row .= '<td></td>'; # another one for the ) column
        }
      }
      $prevCol = $col;
      switch ($colTypeN = $colTypeA[$col]) {
        case 0: # undefined
        case CT_Text:
        case CT_Descr:
          if ($dat !== false && $dat !== '') { # output cell content if not false or ''
            $atts .= " class='t"     . ($css ? " $css'": SQ);
            if ($dat && $broRef && TagToCome($broRef))
              $dat = Tag($broRef, $dat);
            $row .= "<td$atts>$dat</td>";
          }else
            $row .= '<td></td>';
          break;
        case CT_Note:
          $atts .= " class='t r b" . ($css ? " $css'": SQ);
          $row .= "<td$atts>$dat</td>"; # never a tag for note col
          break;
        case CT_Money:
        case CT_Calc: # /- djh?? Temporary. Need to handle these appropriately
        case CT_Perc: # |
          # Money column. Add the extra ) column
          # Tag to come? (if not a date bro for heading year)
          if ($broRef && $broRef != -1 && !TagToCome($broRef))
            $broRef = 0; # Bro involved and not a subtotal/total from col but not a BroRef -> a tag => no tag
          # else no BroRef or BroRef = -1 for a subtotal or total from column = no tag or a Tx Bro = tag
         #$year = $colYearA[$col] ;     /- should give same result
          $year = $cellA[CellI_Year]; # |
          $pcss = $year ? $PreferencesA['PriorYearColCss'] : $PreferencesA['ThisYearColCss'];
          if ($rowMCss == $pcss) $rowMCss = '';
          if ($css == $pcss || $css == $rowMCss) $css = '';
          if ($css = trim("$pcss $rowMCss $css"))
            $css = " $css";
          if (is_string($dat)) {
            if (($options & OptB_pyaHdg) && $colRestatedA[$col])
              $dat .= "<br><span class='s'>{$GLOBALS['HeadingsA']['RestatedH']}</span>";
            if ($broRef)
              $dat = Tag($broRef, $dat, $year);
            $row .= "<td$atts class='y$css'>$dat</td><td class='yk'></td>";  # + empty ) col
          }else{
            # numeric or false
            if ($dat) {
              # numeric output. $broRef won't be -1 to signal subtotal/total from col as then $dat is false.
              $b   = $cellA[CellI_Brackets]; # 1 if brackets, 0 if not
              $dat = $broRef ? Tag($broRef, $dat, $year, $cellA[CellI_XSign]) : number_format(abs($dat)); # tag if have BroRef
            }else{
              # false, real 0, cell keepHide, or cell empty for subtotal
              $b = 0;
              if ($broRef == -1)
                # subtotal or total from column with no cell content or tag. Any bul options will have been unset by the subtotal code.
                $dat = '';
              else{
                # cell keepHide, false, or real 0
                if ($options & OptB_keepHide)
                  $dat = '';
                else{
                  # false or real 0
                  if ($dat === false)
                    $dat = $PreferencesA['Undefined'];
                  else
                    $dat = $broRef ? Tag($broRef, 0, $year + $restated * Pya_Year_Offset) : $PreferencesA['RealZero'];
                }
              }
            }
            $mcss = $css;
            if ($ul = $options & OptB_AnyAul) $mcss .= (($ul & OptB_aul) ? ' asb' : ' adb'); #.asb{border-top:1px solid #000}    or .adb{border-top:3px double #000}
            if ($ul = $options & OptB_AnyBul) $mcss .= (($ul & OptB_ul)  ? ' bsb' : ' bdb'); #.bsb{border-bottom:1px solid #000} or .bdb{border-bottom:3px double #000}
            if ($b) # 1 if brackets, 0 if not
              $row .= "<td$atts class='y$mcss'>($dat</td><td class='yk$css'>)</td>";  # ( and ) in the ) col with css but not the ul css
            else
              $row .= "<td$atts class='y$mcss'>$dat</td><td class='yk'></td>";  # + empty ) col
          }
          break;
      } # end of col type switch
    }
    # add the row
    if (!$tableStartedB) {
      if ($TableA['Center'])
        AddP1Line("<table class='mc'>");
      else
        AddP1Line('<table>');
      $tableStartedB = true;
    }
    AddP1Line($row . '</tr>');
  }
  if ($tableStartedB)
    AddP1Line('</table>');
  $TableA = 0;
  #echo "TableEnd() end<br>";
}

// ToC related functions
function TocStart() {
  TableStart(0,1); # cols 0, center 1
 #Row();
 #Col(2, 0, 'Page', 0, 'r');
  Row(0, [2, 0, 'Page', 0, 'r']); # array is col args of ($col, $datType=0, $dat=0, $options=0, $css=0, $repeat=0, $span=0)
}
// [toc infoX] Corp/contents.b line 28
// Toc('infoX');
function Toc($name, $css1=0, $css2=0) {
  global $XrefsA;
  if (!isset($XrefsA[$name]))
    $XrefsA[$name] = [0 , 0, 0]; // bits, Page, text: XrefI_Bits, XrefI_Page, XrefI_Text
  $XrefsA[$name][XrefI_Bits] |= XrefB_Both | XrefB_Link; // djh?? This could be done at compile time if two passes were made.
 #Row();
 #Col(1, 0, XrefStr(XrefB_Link, $name), 0, $css1);
 #Col(2, 0, XrefStr(XrefB_Page, $name), 0, ($css2 ? : 'r'));
  Row(0, [1, 0, XrefStr(XrefB_Link, $name), 0, $css1], [2, 0, XrefStr(XrefB_Page, $name), 0, ($css2 ? : 'r')]);
}

// Xref functions
// ==============
// Insert the P1 Xref to be replaced in P2
// [xref target infoX] Corp/info.b line 20
// Xref('infoX',0);
// [xref <target|text|page|both|link> {c:css} nameX]
function XrefSt($name, $bits, $css = 0) {
  global $XrefsA;
  if (!isset($XrefsA[$name]))
    $XrefsA[$name] = [0, 0, 0]; // bits, Page, text: XrefI_Bits, XrefI_Page, XrefI_Text
  if ($bits) { // bits are set if not a target Xref
    // Set bits re what type of target info will be needed and return txt to be inserted in the output by the p or toc or whatever
    $XrefsA[$name][XrefI_Bits] |= $bits; // djh?? This could be done at compile time if two passes were made.
    $xS = XrefStr($bits, $name);
    if ($css)
     return "<span class='$css'>$xS</span>";
    return $xS;
  }
  AddP1Line("T$name"); // target
}

// Generate an Xref string for inclusion in output to be procssed in pass 3
// Also sets $SolChr to X to be inserted at the start of the line as a flag for pass 3
// \1blnnnnn
function XrefStr($bits, $name) {
  global $SolChr;
  $SolChr = 'X';
  $len = chr(strlen($name)); // length of name as an ascii char
  return "$bits$len$name"; // bits never > 9, but len could be thus chr() for len
}

# Data functions
# ==============
# CellDatWithTag() is used by h, p, span statement expressions referencing table cell data
function CellDatWithTag($rowNum, $col, $colSum=0) {
  global $NamedRowsA, $PreferencesA;
  $rowA = &$NamedRowsA[$rowNum];
  if ($movedCol = $rowA[0][Row0I_MovedColsA][$col])
    $col = $movedCol;
  if (!isset($rowA[$col]))
    return $PreferencesA['Undefined'];
  $cellA = $rowA[$col];
  $dat   = $colSum ? $rowA[0][Row0I_ColSumA][$col] : $cellA[CellI_Dat];
  if (!($broRef = $cellA[CellI_BroRef])) {
   # no broRef = no tag
   if (is_int($dat))             # djh?? Will need to improve this re formatting and sign
      return number_format(abs($dat));
    return $dat;
  }
  $restated = $cellA[CellI_Restated];
  if (isset($cellA[CellI_Year])) # money col
    $year = $cellA[CellI_Year];
  else
    $year = 0;
  if (is_string($broRef))
    MapBroRef($broRef);
  if ($restated)
    $year += Pya_Year_Offset;
  #if (!is_int($dat))
  #  $dat = htmlspecialchars($dat, ENT_NOQUOTES); # djh?? Sort out the html special characters at import/posting time
  return Tag($broRef, $dat, $year);
}

# Cell dat for the current row
# Currently will only give correct result for cells to the left of the calling cell.
# Compiler should ensure that col is defined.
# Subtotal fr->to moving a cell not a problem here as this would be called before the move.
function CellThis($rowA, $col, $colSum=0) {
/*global $ThisRowA;                    Using a global did work but only if copied before every call to get the updated values.
Dump('ThisRowA CellThis', $ThisRowA);  Didn't work as a reference. Ended up with null here. ??
  if (isset($ThisRowA[$col]))
    return $ThisRowA[$col][CellI_Dat];
  return false; */
  return ($colSum ? $rowA[0][Row0I_ColSumA][$col] : $rowA[$col][CellI_Dat]);
}

# Cell dat for a named row cell reference
# Need to adjust for possible subtotal fr->to moving a cell
function CellDat($rowNum, $col, $colSum=0) {
  global $NamedRowsA;
  $rowA = &$NamedRowsA[$rowNum];
  if ($movedCol = $rowA[0][Row0I_MovedColsA][$col])
    $col = $movedCol;
  # Need to use isset() for the cell one as the col could have been deleted
  return ($colSum ? $rowA[0][Row0I_ColSumA][$col] : (isset($rowA[$col]) ? $rowA[$col][CellI_Dat] : false));
}

# Data($broRef, $year=-1)
# Returns raw Bro dat i.e. no manipulation and no tag
# $broRef is the int BroId if it is an End BroRef and no tuple instance or dimensions are involved, or a string BroRef otherwise
# Called from:
# DateStrWithTag() for the date D
# PBroLoop()   for [p with a BroLoopRef   re the optional test value for a Bro Loop
# ColBroLoop() for [col with a BroLoopRef re the optional test value for a Bro Loop
# ColBroLoop() for [col with a BroLoopRef for the data value
# Returns the value or false if not defined
function Data($broRef, $year=-1) {
  global $BroDatA, $Year, $RestatedYearsA;
  if ($year < 0) $year = $Year; # no year passed
  if (is_string($broRef))
    MapBroRef($broRef);
  if ($RestatedYearsA[$year])
    $year += Pya_Year_Offset;
  if (isset($BroDatA[$year][$broRef])) {
    #if (!is_int($dat =  $BroDatA[$year][$broRef]))
    #  $dat = htmlspecialchars($dat, ENT_NOQUOTES); # djh?? Sort out the html special characters at import/posting time
    #return $dat;
    return $BroDatA[$year][$broRef];
  }
  return false;
}

# Called from:
# PBroLoop()   for [p with a BroLoopRef   for the data value
# Compiled code for BroRefs in (string) expressions
# Returns Bro dat with tag if applicable
function DataWithTag($broRef, $year=-1) {
  global $BroDatA, $Year, $RestatedYearsA, $PreferencesA;
  if ($year < 0) $year = $Year;    // no year passed
  if (is_string($broRef))
    MapBroRef($broRef);
  if (isset($BroDatA[$year][$broRef]))
    $dat = $BroDatA[$year][$broRef];
  else
    $dat = false;
  if ($RestatedYearsA[$year]) {
    # have restated data for this year
    $year += Pya_Year_Offset;
    if (!isset($BroDatA[$year][$broRef]))
      return $PreferencesA['Undefined'];
    $rDat = $BroDatA[$year][$broRef];
    if ($rDat !== $dat) # Restated
      $dat = $rDat;     # leaving year in + Pya_Year_Offset form
    else
      $year -= Pya_Year_Offset; # not restated so put year back
  }else{
    # year not restated
    if ($dat === false)
      return $PreferencesA['Undefined'];
  }
  #if (!is_int($dat))
  #  $dat = htmlspecialchars($dat, ENT_NOQUOTES); # djh?? Sort out the html special characters at import/posting time
  return Tag($broRef, $dat, $year);
}

# Descr($broRef)
# Returns Description for the Bro of $broRef
function Descr($broRef) {
  global $BroDescrsA;
 #return $BroDescrsA[is_string($broRef) ? (int)$broRef : $broRef];
  return $BroDescrsA[(int)$broRef];
}

# TagToCome($broRef)
# Returns > 0 if use of $broRef data will result in a tag, 0 o'w
# See Descr() for comments
function TagToCome($broRef) {
  global $BroInfoA;
  return $BroInfoA[$broId = (int)$broRef][BroI_TxId] && !($BroInfoA[$broId][BroI_Bits] & BroB_NoTags)];
}

# djh?? Better to be using Bros and pass the $brO? (-> Info, TupId, TuMeId
function Tag($broRef, $dat, $year=0, $xsign=0) { // $xsign set if money iXbrl sign is to be set to -
  global $BroInfoA, $BroTagsA, $DiMesRA, $TuMesA, $ContextsA, $TagsA, $TuplesA, $Country, $ZoneSignN, $PreferencesA, $AppN;
  #echo "Tag broRef=$broRef, dat=$dat, year=$year<br>";
  if ($dat === '') # no tag for ''
    return '';
  if (is_string($broRef)) {
    list($broId, $diMeIdsA, $inst, $broDatType) = UnpackBroRef($broRef); # returns [BroId, DiMeIdsA, Inst, BroDatType]
    $mDiMeId = ($DiMesRA[$diMeIdsA[0]][DiMeRI_Bits] & DiMeB_M) ? $diMeIdsA[0] : 0;
  }else{
    $broId = $broRef;
    $broA  = $BroInfoA[$broId];
    $mDiMeId = $numDiMes = $diMeIdsA = $inst = 0;
    $broDatType = BroDatT_End;
  }
  if (($bits = $broA[BroI_Bits]) & BroB_NoTags)
    return ''; # No tag if NoTags bit set
  $dataTypeN = $broA[BroI_DataTypeN];
  if ($dataTypeN != DT_Money && isset($TagsA[$broRef])) # no tag if not a money item and a tag has been generated previously
    return $dat;
  $TagsA[$broRef] = 1;
  $PeriodSEN = $broA[BroI_PeriodSEN];
  $contextN  = $broA[BroI_ContextN];
  $tag = $BroTagsA[$broId];
  if ($year > Pya_Year_Offset) {
    # Restated. See if BroRef is a Restated one and if not add the pya DiMe
    if ($numDiMes && $DiMesRA[$t = $diMeIdsA[$numDiMes-1]][DiMeRI_DimId] == DimId_Restated) # last one = potential pya DiMe
      $pyaDiMeId = $t; # last one is a pya DiMe
    else{
      $pyaDiMeId = DiMeId_PyaAmount; # 73
      if ($numDiMes)
        array_push($diMeIdsA, $pyaDiMeId);
      else
        $diMeIdsA = [$pyaDiMeId];
      ++$numDiMes;
    }
    $year -= Pya_Year_Offset;
  }
  # Build the context ref
  $ref = '';
  if ($numDiMes) { # Add DiMe sections
    # For summing Bros can have DiMes in a BroRef that are only valid further up the tree, via BroI_SumUsableDims being defined which means it is and different to BroI_BroUsableDims
    if ($broA[BroI_SumUsableDims]) {
      # BroI_SumUsableDims is defined so remove any DiMeIds that are not allowed by BroI_BroUsableDims
      $allowDims = $broA[BroI_BroUsableDims];
      foreach ($diMeIdsA as $i => $diMeId)
        if (!InChrList($diMeId, $allowDims))
          unset($diMeIdsA[$i]);
    }
    foreach ($diMeIdsA as $diMeId) {
      $sn = $DiMesRA[$diMeId][DiMeRI_DiMe_Name]; # Officers_Director1, TPAType_Accountants etc
      if ($diMeId === $mDiMeId)
        $sn = substr(strrchr($sn, '_'), 1); # For an M# DiMe can chop off the dimension name e.g. Officers_Director1 -> Director1
      else
        $sn = str_replace('_', '', $sn); # for the others remove the _
      $ref .= "_$sn";
    }
  }
  if ($mDiMeId) { # For a BroRef with an M# DiMe check for HighestPaidDirector case djh?? Correct?
    if ($contextN == RGC_Officer && $dataTypeN == DT_Money && isset($ContextsA[$ref])) { # djh?? Do only for DirectorsRemuneration?
      # Check for HighestPaidDirector case
      $contextA = $ContextsA[$ref];
      if ($DiMesRA[$mDiMeId][DiMeRI_PropSet] == MType_Director && ($contextA[CI_PropBits] & OTB_HighestPaidDirector)) {
        $ref .= '_HighestPaidDirector';
        $contextN = RGC_HighestPaidDirector;
      }
    }
  }else # finish context ref for a BroRef wo an M# DiMe
    switch ($contextN) {
      default:              LogIt("Unexpected ContextN $contextN encountered when generating tag for BRO Ref $broRef. Using RGC_Period");
                            $contextN = RGC_Period;
      case RGC_Period:      $ref .= '_Period';      break;
      case RGC_Officer:     $contextN = RGC_AllOfficers; # Officer with no N DiMe -> AllOfficers
      case RGC_AllOfficers: $ref .= '_AllEntityOfficers'; break;
      case RGC_RegOffice:   $ref .= '_RegisteredOffice'; break;
     #case RGC_TPA:         Not expected here as expect all to have an M# DiMe
      case RGC_UkCountry:   $ref .= "_UkCountry{$Country}"; break;
     #RGC_HighestPaidDirector Not here as not used with Bros and only used in code above
    }
  if ($PeriodSEN > BPT_Duration)
    # Instant period - remove first _ and append <End|Start>Y#
    $ref = substr($ref, 1) . ($broDatType === BroDatT_Start ? "StartY$year" : "EndY$year");
  else
    # Duration period - remove first _ and append Y#
    $ref = substr($ref, 1) . "Y$year";
  if (!isset($ContextsA[$ref]))
    $ContextsA[$ref] = [$contextN, $year, $diMeIdsA]; # [CI_ContextN, CI_Year, CI_DiMeIdsA

  if ($AppN == BDT_CFS) { # BDT_CFS = no tagging so just format $dat in cases requiring it
    switch ($dataTypeN) {
      case DT_Enum:
      case DT_Share:
      case DT_Integer:
        $dat = number_format($dat);
        break;
      case DT_Money:
        if (!$dat || is_string($dat)) { # DT_Money string expected to be a zero case
          if ($dat === 0)
            $dat = $PreferencesA['RealZero'];
        }else
          $dat = number_format(abs($dat));
        break;
      case DT_PerShare:
      case DT_Percent:
      case DT_Decimal: $dat = number_format($dat,2);
        break;
    }
    return $dat;
  }

  if ($contextN == RGC_UkCountry) # special case with no dat. Is actually type Fixed in Tx
    return "<ix:nonNumeric name='$tag' contextRef='$ref'/>";

  # TupRef
  if ($inst) {
    # TupRef = "TuMeId.i"
    # Braiins convention for the tupleId: T.n.i.Y#
    # where n  = running number for tuples in the FS - same for tuples with same name /- same for diff years
    #       i  = instance number                                                      |
    #       Y# = Y and year digit as used in contexts
    # $TuplesA [TupId][TupleID] = 1
    static $TupNum; # gives n in T.n.i.Y#
    $tuMeId = $broA[BroI_TuMeId];
    $tupId  = $TuMesA[$tuMeId][TuMeI_TupId];
    #echo "tupId $tupId, tuMeId $tuMeId, inst $inst, TupNum $TupNum<br>";
    if (!isset($TuplesA[$tupId]))
      ++$TupNum;
    $tupxID = "T.$TupNum.$inst.Y$year";
    $TuplesA[$tupId][$tupxID] = 1;
    $tupRef = " tupleRef='$tupxID' order='{$TuMesA[$tuMeId][TuMeI_Ordr]}'";  # for insertion in the tag
  }else
    $tupRef = '';

  switch ($dataTypeN) {
    case DT_Boolean:
    case DT_String:
      $dat = "<ix:nonNumeric name='$tag'$tupRef contextRef='$ref'>$dat</ix:nonNumeric>";
      break;
    case DT_Enum:
    case DT_Share:
    case DT_Integer:
      $dat = "<ix:nonFraction name='$tag'$tupRef contextRef='$ref' decimals='0' scale='0' format='ixt:numcommadot'>" . number_format($dat) . '</ix:nonFraction>';
      break;
    case DT_Money:
      if (!$dat || is_string($dat)) { # DT_Money string expected to be a zero case
        if ($dat === 0)
          $dat = $PreferencesA['RealZero'];
        if ($dat === '-') # via Agent option
          $dat = "<ix:nonFraction name='$tag'$tupRef contextRef='$ref' unitRef='GBP' decimals='0' scale='0' format='ixt:numdash'>-</ix:nonFraction>";
        else
          $dat = "<span class='hide'><ix:nonFraction name='$tag'$tupRef contextRef='$ref' unitRef='GBP' decimals='0' scale='0' format='ixt:numdash'>-</ix:nonFraction></span>$dat";
      }else{
        $sign = $xsign ? ' sign=\'-\'' : '';
        $dat = "<ix:nonFraction name='$tag'$tupRef contextRef='$ref' unitRef='GBP'$sign decimals='0' scale='0' format='ixt:numcommadot'>" . number_format(abs($dat)) . "</ix:nonFraction>";
      }
      break;
    case DT_PerShare:
    case DT_Percent:
    case DT_Decimal: $dat = "<ix:nonFraction name='$tag'$tupRef contextRef='$ref' decimals='2' scale='0' format='ixt:numcommadot'>" . number_format($dat,2) . '</ix:nonFraction>'; break;
    case DT_Date:
    /*switch ($dat[2]) { # v2
        case ' ': $f = " format='ixt:datedaymonthyearen'"; break; # 03 February 2011 or 03 Feb 2011
        case '/':                                                 # 03/02/2011
        case '.': $f = " format='ixt:datedaymonthyear'"; break;   # 03.02.2011 as per preferences BUT 03-02-2011 not covered here
        default:  $f = ''; break;
      } */
      switch ($dat[2]) {
        case ' ': $f = " format='ixt:datelonguk'";  break;
        case '/': $f = " format='ixt:dateslasheu'"; break;
        case '.': $f = " format='ixt:datedoteu'";  break;
        default:  $f = ''; break;
      }
      $dat = "<ix:nonNumeric name='$tag'$tupRef contextRef='$ref'$f>$dat</ix:nonNumeric>";
      break;
  }
  return $dat;
}
/*
http://www.xbrl.org/SpecRecommendations/
<ix:fraction  <ix:denominator  <ix:numerator
ix:denominator
ix:exclude
ix:footnote
ix:fraction
ix:header
ix:hidden
ix:nonFraction
ix:nonNumeric
ix:numerator
ix:references
ix:resources
ix:tuple

Table 2: Attributes
arcrole
contextRef
decimals
escape
footnoteID
footnoteLinkRole
footnoteRefs
footnoteRole
format
id
name
precision
order
scale
sign
target
title
tupleID
tupleRef
unitRef

Transformations
08.02.12 Changed from v1 to v2
v1              v2
numcommadot     numcommadecimal
numdash         zerodash
datelonguk      datedaymonthyearen
dateslasheu     datedaymonthyear
datedoteu       datedaymonthyear

*/

# MapBroRef(&$broRef)

# Mapping is about
# - CoSec or CoSecDirector  = different M# mDiMeId
# - Accountants or Auditors = different M# mDiMeId
# So it is just M type DiMeIds that needs to be mapped.

# A Bro Reference is BroId{,Bro Object BroDat/BroTupDat key} where the key is omitted if it is just BroDatT_End (2).
# MapBroRef() is only called for string BroRefs i.e. not is just an End BroId
# -> BroRef in form: BroId,{INST,}<1|2>{,DiMeRef} where 1 = Start and 2 = End and
# DiMeRef is comma sep DiMeIds, sorted into M type, Property, Others in ascending DiMeId order, Pya last
#          Tuple Inst        Non-Tuple Inst
# Base Ref 12345,2001,2      12345
# DiMe Ref 12345,2001,2,148  12345,2,148
#              0    1 2   3      0 1   2
function MapBroRef(&$broRef) {
  global $MDiMeIdsToMapA, # array of MDiMeIds to be mapped e.g. [DiMeId_CoSec, DiMeId_Accountants], or just one of them, or null if none
         $MMapOffsetsA;   # array of MDiMeId offsets corresponding to $MDiMeIdsToMapA
  if ($MDiMeIdsToMapA) { # Can have entities where no mapping is involved
    $broRefA = CsListToIntA($broRef);
    if (($i = 2 + ($broRefA[1] > 2000)) < ($n = count($broRefA))) { # $i = first DiMeId index
      if ($i < $n && (($j = array_search($broRefA[$i], $MDiMeIdsToMapA)) !== false)) {
        $broRefA[$i] += $MMapOffsetsA[$j];
        $broRef = implode(',', $broRefA);
      }
    }
  }
}

# UnpackBroRef($broRef)
# ---------------------
# BroRef = BroId{,Bro Object BroDat/BroTupDat key} where the key is omitted if it is just BroDatT_End (2) => only int BroRef.
# BroDatKey = {INST,}<1|2>{,DiMeRef} # End  where 1 = Start and 2 = End and
# DiMeRef is comma sep DiMeIds, sorted into M type, Property, Others in ascending DiMeId order, Pya last
# Returns [BroId, DiMeIdsA, Inst, BroDatType]
function UnpackBroRef($broRef) {
  #          Tuple Inst        Non-Tuple Inst
  # Base Ref 12345,2001,2      12345
  # DiMe Ref 12345,2001,2,148  12345,2,148
  if (is_int($broRef)) # Base Non-Tuple Ref of BroId
    return [$broRef, 0, 0, BroDatT_End];
  $diMeIdsA = CsListToIntA($broRef);
  $broId    = array_shift($diMeIdsA);
  $inst     = $diMeIdsA[0] > 2000 ? array_shift($diMeIdsA) - 2000 : 0;
  $broDatType = array_shift($diMeIdsA);
  if (!count($diMeIdsA))
    $diMeIdsA = 0;
  return [$broId, $diMeIdsA, $inst, $broDatType];
}

# Output of P1
function AddP1Line($line) {      # $SolChr = Start of line character
  global $P1LinesA, $SolChr;     #  Set to P if the line contains a page number to be resolved, marked by  \4
  $P1LinesA[] = $SolChr . $line; #  Set to X if the line contains an xref to be resolved, marked by  \1
  $SolChr = '';                  # Other SoL characters inserted directly are:
}                                #  N for new page
                                 #  T for target xref

