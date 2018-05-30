<?php /* Copyright 2011-2013 Braiins Ltd

FormatCompile.php

History:
02.12.11 Started as format selection front end to previous /srv/Compile.php
23.10.12 Started revisions for Bro changes
24.12.11 Started revisions for use of Bro Class
11.01.13 Added str_replace() to remove some unnecessary string concatenations\\
         Fixed use of single quoted strings in $RTFunctionsA cell expressions to be evaluated during TableEnd() processing rather than as a Col() parameter expression.
18.02.13 BD Maps removed

To Do djh??
=====

Move tuple loop detection back here from Row() in RgRunFunctions.inc?

Support embedded [] expressions in strings

Add this to the right check

Make use of token_get_all() ?
 http://php.net/manual/en/function.token-get-all.php
 http://www.php.net/manual/en/tokenizer.examples.php

add spacing options to [row rather than c:asp1
add cell formatting options re decimals etc
Allow c cols to be in frCols and toCols lists and to be totalled?

Col() calls with same paramters apart from col # -> a Cols() call with cols list?

Row arithmetic. (But it can be done using cell expressions)

Add processing of Zones AllowPropDims

Remove comparison and logical Operators support from IntExpr()?

Track use of variables, functions, paras...

Second pass compiler to?
  - merge adjacent strings. 11.01.13 a bit done in first pass via str_replace of '.' => '' etc.
  - convert " to ' strings where possible
  - remove unused functions
  - allow forward refs to functions and paras
  ??

*/

require 'BaseBraiins.inc';
$startUtime= utime();
$TxName = 'UK-GAAP-DPL';
define('DB_Tx', DB_Prefix.str_replace('-', '_', $TxName));
define('Com_Inc_Tx', Com_Inc."$TxName/");
define('Com_Str_Tx', Com_Str."$TxName/");
require Com_Inc_Tx.'ConstantsRg.inc';
require Com_Str_Tx.'BroNamesMapA.inc'; # $BroNamesMapA incl ShortNames
require Com_Str_Tx.'DiMeMapA.inc';     # $DiMeMapA $ManagedPropDiMeIdsA
require Com_Str_Tx.'ZonesA.inc';       # $ZonesA, $ZoneRefsA
require Com_Inc.'FuncsSIM.inc';
require Com_Inc.'ClassBro.inc';        # $BroInfoA $DiMesA $BroNamesA $BroShortNamesA $DiMeNamesA $DiMeTargetsA $RestatedDiMeTargetsA

$DB->Bits = MB_OK | AP_All | AP_Compile;
# Check permission. Must be OK member with All std APs and Compile permission
$perms = MB_OK | AP_All | AP_Compile;
(($DB->Bits & $perms) == $perms) or Error(ERR_NOT_AUTH);

# Constants
# =========
const RGC_LOG               = '../../Logs/RGCompiler.log';
const RG_FormatPath         = '../../Formats/';
const RG_CompiledFormatPath = '../../../Braiins/Rg/Exes/';
const RG_DEBUG              = 1;

Head('Compile', true);
if (!isset($_POST['Format']))
  Form();
  #######

echo "<h2 class=c>Compiling</h2>
";

$FormatId = Clean($_POST['Format'], FT_INT);

# $FormatId is the Formats.Id of the selected format
$fO = $DB->ObjQuery("Select * From Formats Where Id=$FormatId");
$format = $fO->FileName;
#pathsA = [0=>'All', TxN_UK_GAAP => 'UK-GAAP/', TxN_UK_GAAP_DPL => 'UK-GAAP-DPL/', TxN_UK_IFRS => 'UK-IFRS/'];
$pathsA = ['All', 'UK-GAAP/', 'UK-GAAP-DPL/', 'UK-IFRS/'];
$FormatPath = RG_FormatPath.$pathsA[$fO->TaxnId].($fO->ETypeId >= ET_PrivateLtdCo ? 'Corp/' : 'Unincorp/'); # djh?? Change this to use EntityTypes Incorporated property

$RgcLog = gmstrftime("%Y.%m.%d %H:%M:%S") . # for the log
#" ------------
"Compiling Format $format
";
# Used by Error() if called
#$ErrorHdr="Format $format could not be compiled. See the RGCompiler log entry: " . substr($RgcLog, 0, 24);

echo "Compiling Format $format<br>
";

# Rg Data Type enums in Com/inc/ConstantsRg.inc
# ------------------
/*nst DT_None    = 0;
const DT_String  = 1; # S String. All types > DT_String are numerical
const DT_Integer = 2; # I Integer number
#onst DT_Money   = 3; # M Money number (processed internally as a 64 bit integer with 2 places of implied decimals, used to avoid rounding issues)
const DT_Money   = 3; # M Money number (processed internally as a 64 bit integer with 0 places of decimals (whole pounds assumed).
const DT_Decimal = 4; # E Decimal or floating point number for percentages, ratios, or other calculated values with possible decimal fractions
const DT_Date    = 5; # D Date
const DT_Boolean = 6; # B Boolean value 1 or 0 i.e. True or False, Yes or No. (Actually anything other than 0 means True/Yes.)
const DT_Enum    = 7; # N Enumerator -> I for RG expression purposes
const DT_Share   = 8;    -> I for RG expression purposes
const DT_PerShare= 9;    -> E "
const DT_PerCent = 10;   -> E " */

# Expression Type enums    Bro Data Type Equivalents
const ExT_None    = 0;
const ExT_String  = 1; # S DT_String
const ExT_Integer = 2; # I DT_Integer, DT_Enum, DT_Share
const ExT_Money   = 3; # M DT_Money
const ExT_Decimal = 4; # E DT_Decimal, DT_PerShare, DT_PerCent
const ExT_Date    = 5; # D DT_Date
const ExT_Boolean = 6; # B DT_Boolean
const ExT_Numeric = 7; #   Any of ExT_Money to ExT_Boolean
# Final name character letters
#                     0123456
#                     _SIMEDB

# RG Symbol Table enums
const ST_Any      =  0; # |- Required final letter of name
const ST_Header   =  1; # F /- anonymous re format but given names by compiler HeaderFn1F etc
const ST_Footer   =  2; # F |                                                  FooterFn1F etc
const ST_Function =  3; # F
const ST_Para     =  4; # P
const ST_Constant =  5; # C
const ST_Heading  =  6; # H
const ST_Xref     =  7; # X Has both ExT_String and ExT_Integer values at run time.
const ST_Var      =  8; # S,I,M,E,D,B according to var type
#onst ST_Array    =  9; # S,I,M,E,D,B according to array type
#onst ST_BRO_Ele  = 10; # No final letter requirement
#onst ST_BRO_Set  = 11; # No final letter requirement
# Final name character letters up to ST_Xref
#                     01234567
#                     _FFFPCHX

# Indices for $SymbolTableA[][]
const STI_SType      = 0; # Symbol type using ST_ constants
const STI_EType      = 1; # Expression (result or value) type using ExT_ constants
#onst STI_Id         = 2; # BroInfo.Id for ST_BRO entries
const STI_Val        = 2; # Value of symbol for constants using same slot as STI_Id
const STI_Uses       = 3; # Count of times used in format. Used for Xref to say target case has happened.
const STI_FormatAndLine = 4; # Format and line string of where the symbol was defined, 0 = system

# Level type Enums
const LT_Header     = 1; # /- parallels the Symbol Table types
const LT_Footer     = 2; # |
const LT_Function   = 3; # |
const LT_Para       = 4; # |
const LT_Block      = 5;
const LT_If         = 6;
const LT_Else_If    = 7;
const LT_Else       = 8;
const LT_Table      = 9;
const LT_Toc        =10;

# Indices for $LevelsInfoA[][]
const LII_Type          = 0; # LT_Header etc as above
const LII_FormatAndLine = 1; # Format and line where the level started

# Indices for $XrefUsesA[][]
const XUI_Name          = 0; # xref name
const XUI_FormatAndLine = 1; # Format and line where the xref was used

# Token tYpe enums
const TY_CHAR     =  1; # one character not ' or "
const TY_SQ       =  2; # '
const TY_DQ       =  3; # "
const TY_SQSTRING =  4; # 'string'
const TY_DQSTRING =  5; # "string"
const TY_SQSTART  =  6; # 'string
const TY_DQSTART  =  7; # "string
const TY_STRING   =  8; # string
const TY_INTSTR   =  9; # 1234
const TY_NUMSTR   = 10; # 12.34

# BroRefOrExpr() return type values
const BRT_Descr   = 1; # descr BroRef i.e. broRef for a Descr($broRef) call
const BRT_BroRef  = 2; # BroRef with optional year expr
const BRT_Expr    = 3; # Expression as the parameter -> $dat as evaluated for the call
const BRT_BroLoop = 4; # Only applicable to BroRefOrExpr() which returns an array of Bro loop info in this case

# Start
# =====

# Globals
# =======
$ETypeId   = ET_PrivateLtdCo;   # /- Defaults. Can be changed by format by setting constant ETypeC
$ETypeChr = IntToChr($ETypeId); # |
$ESizeId  = 0;                 # Can be changed by format by setting constant CoSizeC
BuildGlobalDimGroupExclDimsChrList();

$FormatsA     =     # Formats
$ZonesNowA    =     # Array of the currently set zones [Zones.Id]
$SymbolTableA =     # Symbol table
$LevelsInfoA  =     # Records current stack of levels info with entries from 1 to $Level. Slot 0 is not used.
$RowNamesA    =     # RowName => [FormatAndLine string, $TableA, $rowNum] where rowNum = the count + 1 i.e. base 1 used as the RT $RowsA key
$XrefUsesA    =     # List of xref uses to enable a check of whether the target has been defined
$P1LinesA     =     # Pass 1 compiled lines
$FuncLinesA   =     # Compiled function lines for all functions
$RTFunctionsA = []; # Code for functions created at RT using create_function(). Used for cell expressions to be evaluated during TableEnd() processing rather than as a Col() parameter expression. This mechanism could possibly be used for header and footer functions too.
$FormatIdx    = -1;    # Index in $FormatsA[] of the format currently being compiled. -1 == system
$LineNum      = -1;    # Number of line (base 0) currently being complied.            -1 == system
$InFuncB      = false; # Set when compiling a 'function': block, header, footer, function, para
$FormatLevel  = -1;    # Format level
$Level        = 0;     # Incremented when compiling a function, if branch, table, or toc with start info recorded in $LevlsInfo[][]
                       # and decremented when the function etc ends. Is also used with $FormatLevel for code indenting purposes.
$TableA       = 0;     # Assoc array of info about the current table during compilation of a table, 0 when not in a table

# Add the master headings to the symbol table
/*
$res = $DB->ResQuery("Select Ref From Headings where AgentId=1");if (!$res->num_rows)
  Error('RG Compilation error: No master headings found');
while ($o = $res->fetch_object())
  AddSymbolToST($o->Ref, ST_Heading);
$res->free(); */

#$Dat = $DB->StrOneQuery("Select Data From AgentPrefs Where AgentId=1");
$HeadingsA = json_decode($DB->StrOneQuery("Select Data From AgentData Where AgentId=1 and TypeN=".ADT_Headings), true);
#Dump('HeadingsA', $HeadingsA);
foreach ($HeadingsA as $ref => $val)
  AddSymbolToST($ref, ST_Heading);

# Add the built-in variables to the symbol table
# Strings
$builtInVarsA = [
  'IncorporationCountryS',
  'DirectorsS',
  'DirectorsApostropheS',
  'DraftS',
  'FullAccountsS',
  'CoSecStatusS'
];
foreach ($builtInVarsA as $name)
  AddSymbolToST($name, ST_Var);

# Integers
$builtInVarsA = [
  'ETypeI',
  'CoSizeI',
  'NumDirectorsI',
  'NumDirectorsSigningAccountsI',
  'CoSecDirectorI'
];
foreach ($builtInVarsA as $name)
  AddSymbolToST($name, ST_Var, ExT_Integer);

# Booleans
$builtInVarsA = [
  'AccountantsAndAuditorsSameB',
];
foreach ($builtInVarsA as $name)
  AddSymbolToST($name, ST_Var, ExT_Boolean);

# The Real Work
CompileFormat($format);
# Finished the Real Work

if ($Errors >= 0) {
  $FormatIdx = -1; # re any possible error message here
  for (; $Level; $Level--)
    RGError('The ' . LevelInfo() . ' has not been terminated by an [end] statement');

  if ($FormatLevel != -1)                # /- should never see this
    RGError("FormatLevel=$FormatLevel"); # |

  foreach ($XrefUsesA as $xrefUseA) {
    $xrefName = $xrefUseA[XUI_Name];
    if (!$SymbolTableA[$xrefName][STI_Uses])
      RGError("The xref $xrefName used in {$xrefUseA[XUI_FormatAndLine]} has not been defined i.e. it has not been used in an [xref target ... statement");
  }
}

if (!$Errors) {
  # No compilation errors so write the compiled file
  if (count($RTFunctionsA))
    array_unshift($P1LinesA, "# Cell Expression Functions\n", "CreateRTFunctions([" . implode(",", $RTFunctionsA) . "]);\n");
  if (count($FuncLinesA))
    array_push($P1LinesA, NL,"# Format Functions\n");
  file_put_contents (RG_CompiledFormatPath . $format . '.inc', array_merge(
  #file('RgRunStart.inc', FILE_SKIP_EMPTY_LINES),
    ["<?php\n"],
    $P1LinesA,
    $FuncLinesA)
  ); # [, int $flags = 0 [, resource $context ]] )  # LOCK_EX
  $DB->StQuery("Update Formats Set Status=(Status|1),EditT=$DB->TnS Where Id=$FormatId");
  #$RgcLog .= "Update Formats Set Status=(Status|1),EditT=$DB->TnS Where Id=$FormatId\n";
  $RgcLog .= "Format $format compiled without errors in " . ElapsedTime($startUtime);
}

/*
if ($fh = fopen(RG_CompiledFormatPath . $format . '.php', 'w')) {
  $P1LinesA,
  foreach ($P1LinesA as $line)
    fwrite($fh, $line);
  fclose($fh);
}else
  Unable to open file
*/

echo 'Log:<br>', str_replace(NL, '<br>', $RgcLog), '<br>';
LogIt($RgcLog);
/*
if (!$Errors)
  if ($_SERVER['DOCUMENT_ROOT'][0] == '/') # hosted
    echo "<br><a href='http://www.Braiins.com/rg/formats/$format.php'>Run $format</a><br>";
  else
    echo "<br><a href='" . RG_CompiledFormatPath . "$format.php'>Run $format</a><br>";
*/
Form(true); # time
###########

# CompileFormat($format)
# ----------------------
# Called recursively to compile formats
function CompileFormat($format) { # called recursively
  global $FormatPath, $FormatsA, $FormatIdx, $FormatLevel, $LineNum, $Line, $LineLen, $P, $Errors, $EolCode, $TableA;
  # trim any unnecessary '/' or '\' characters from the format name
  $format = trim($format, '/\\');
  # Read the format not with | FILE_SKIP_EMPTY_LINES so as to keep the line # info for error messages
  if (!$linesA = file($FormatPath.$format, FILE_IGNORE_NEW_LINES))
    return RGError("Format $format not found");
  $FormatsA[] = $format;
  $FormatIdx  = $formatIdx = count($FormatsA) - 1;  # global for RGError() use
  $FormatLevel++;
  AddP1Comment('Start of Format ' . $format . " with index $FormatIdx and level $FormatLevel");
  # Loop through the format
  for ($lineNum=0,$numLines=count($linesA); $lineNum<$numLines && $Errors>=0; $lineNum+=$inc) { # $Errors is < 0 after r errors
    $LineNum = $lineNum; # global for RGError() use
    $inc = 1;
    $Line = trim($linesA[$lineNum]);
    if (!strlen($Line) || substr($Line, 0, 2) == '//') # skip empty lines or ones starting with //
      continue;
    while (LastChrMatch($Line, '&')) # line ends with a continuation char
      if ($lineNum+$inc < $numLines)
        $Line = rtrim($Line, '&') . trim($linesA[$lineNum + $inc++]);
    $P = 0;
    if ($Line[0] == '#') { # Expect a compiler command
      CompilerCommand();
      $FormatIdx = $formatIdx;  # reset global $FormatIdx in case the compiler command was #include
    }else{
      # Not a compiler command
      if (InStr('[', $Line)) {
        # Expect statement(s)
        # echo $Line, '<br>';
        $Line = preg_replace('/\s\s+/m', ' ', $Line); # reduce internal excess ws to a single space. djh?? Expand regex to do the following
       #$Line = str_replace(['[ ', ' ]', '( ',' )', ', '], ['[', ']', '(',')', ','], $Line); # Remove space from '[ ' ' ]' '( ' ' )' ', '
        $Line = str_replace(['[ ', ' ]', '( ',' )'      ], ['[', ']', '(',')'     ], $Line); # Remove space from '[ ' ' ]' '( ' ' )'
        $LineLen = strlen($Line);
        $compiledLine = $EolCode = ''; # $EolCode is set to '}' for a one line if statement
        while ($P < $LineLen && ($P = strpos($Line, '[', $P)) !== false)
          $compiledLine .= CompileStatement();
        if ($TableA && $TableA['RowA']['RowParamters'])
          AddTableRow();
        else
          if ($compiledLine)
            AddP1Line($compiledLine . $EolCode);  # $EolCode is set to '}' for a one line if statement
      }
    }
  }
  AddP1Comment('End of Format ' . $format);
  $FormatLevel--;
}

# CompileStatement()
# ------------------
# Called with $P pointing to a [
function CompileStatement() {
  global $Line, $LineLen, $C, $P, $NoErrorB;
  $cs = ''; # compiled statement
  if (Step() !== false) { # step over the [ and check for an expression in case this is [expr]
    $p = $P;
    #$NoErrorB = true; # to return false on error but no output or incr of $Errors
    #$expr    = Expr();
    #$NoErrorB = false;
    #if ($expr !== false)
    #  $cs = "Expr($expr)";
    #else{
      # not an expr
      PBack($p);
      switch ($Line[$P]) {
        case 'b': $cs = Bstatements(); break; # [block]
        case 'c': $cs = Cstatements(); break; # [col
        case 'd': $cs = Dstatements(); break; # [date
        case 'e': $cs = Estatements(); break; # [else]   [else if     [end]
        case 'f': $cs = Fstatements(); break; # [footer] [function
        case 'h': $cs = Hstatements(); break; # [header] [h#
        case 'i': $cs = Istatements(); break; # [if
        case 'l': $cs = Lstatements(); break; # [line]   [lines
        case 'n': $cs = Nstatements(); break; # [nl]     [np]
        case 'p': $cs = Pstatements(); break; # [p       [page#       [para nameP]
        case 'r': $cs = Rstatements(); break; # [row
        case 's': $cs = Sstatements(); break; # [span
        case 't': $cs = Tstatements(); break; # [table]  [toc
        case 'x': $cs = Xstatements(); break; # [xref
        case 'z': $cs = Zstatements(); break; # [zone
        case false: break; # EOL
        # default:  break;
      }
    #}
    #echo "cs=$cs, C=$C<br>";
    #Dump('cs',$cs);
    #Dump('C',$C);
    if ($cs === false) { # error has been logged already
      MoveToStatementEnd();
      $cs = '';
    }else if ($C != ']') { # Expect to be at the closing ]
      $p = $P;
      MoveToStatementEnd();
      RGError($C == ']' ? 'Unexpected stuff before the ]' : 'A closing ] is missing', $p);
      $cs = '';
    }
  }
  return $cs;
}

# [block]
function Bstatements() {
  if (MatchStep('block')) return IncrementLevel(LT_Block, 'BlockStart();'); # [block]
  return RGError('Statement not recognised');
}

# [col{cols list} {<dr|cr>} {<keep|keepHide>} {<ul|dul>} {<aul|adul>} {restatedHdg} {repeat:intExpr} {span:#} {hor} {c:css} {b:BroRef} {expr}]
function Cstatements() {
  global $C, $P, $Line, $TableA;
  if (MatchStep('col')) { # [col
    if (!$TableA) return RGError('Not currently in a table which is required for a col statement to be used');
    $rowA     = &$TableA['RowA'];
    $colTypeA = &$TableA['ColTypeA'];
    $p = $P;
    # echo "$p for [col $Line|| ", substr($Line, $P), '<br>';
    if (isset($Line[$P+1]) && (ctype_digit($C) || ($C == 't' && substr($Line, $P, 4) != 'this') || ($C == 'c' && $Line[$P+1] != ':') ||
        InStr($C, 'dnp') || ($C == 'y' && isset($Line[$P+1]) && ctype_digit($Line[$P+1])))) {
      # # or t or d or n or c or p or y# but not y: as in an expr year prefix or this , so expect cols list
      if (($colsA = ColList()) ==- false) return false;
    }else{
      if ($p <= 5)
        $rowA['PrevCol'] = 0; # no cols list and first col statement on line -> new row
      $colsA = [$rowA['PrevCol']+1];
    }
    $col = $colsA[0];
    $ncols = count($colsA);
    if (($col <= $rowA['PrevCol'] && $p<=5) || !$rowA['PrevCol']) { # start of a new row with this the first statement on the line
      if (!$rowA['RowParamters']) {
        TableRowInit();
        $rowA['RowParamters'] = 1; # -> empty Row() call
      } # else have $rowA['RowParamters'] from [row with RowA values set
    }
    # [col ...
    $colTypeN   = ($colTypeA ? $colTypeA[$col] : CT_Text);
    $colTypeS   = ColTypeToStr($colTypeN);
    $rowOptions = $rowA['Options'];
    $rowBroRef  = $rowA['BroRef'];
    $rowFrCols  = $rowA['FrCols'];
    $rowToCols  = $rowA['ToCols'];
    $broRef = $dat = $options = $css = $rExpr = $span = 0;
    $dt = ($rowOptions & OptB_AnyTotalOp) ? CDtB_RowOp : 0; # dat type
    # col options 0 to 8 are the same as for the row statement and 9 is c: vs mc:
    #                  0     1     2           3       4     5      6      7       8     9     10         11       12     13
    $optionsA = ['dr', 'cr', 'keepHide', 'keep', 'ul', 'dul', 'aul', 'adul', 'b:', 'c:', 'repeat:', 'span:', 'hor', 'restatedHdg'];
    for ($p=$P; ($i = MatchOneStep($optionsA)) !== false; $p=$P) {
      switch ($i) {
        case 0: # col dr
          if ($C == '.') { # DR. Bro start
            PBack($p);
            break 2;
          }
        case 1: # col cr
          if ($colTypeN != CT_Money && $ncols == 1) return RGError("The &lt;dr|cr> attribute can only be used for money columns, but this is a $colTypeS column", $p);
          if ($options & OptB_AnySign) return RGError('Only one of dr or cr should be set', $p);
          $options |= ($i ? OptB_cr : OptB_dr);
          if (($options & OptB_AnySign) == ($rowOptions & OptB_AnySign)) return RGError("This &lt;dr|cr> attribute is the same as the row one so is not needed", $p);
          break;
        case 2: # col keepHide
        case 3: # col keep
          if ($colTypeN != CT_Money && $ncols == 1) return RGError("The &lt;keep|keepHide> attribute can only be used for money columns, but this is a $colTypeS column", $p);
          if ($options & OptB_AnyKeep) return RGError('Only one of keep or keepHide should be set', $p);
          $options |= ($i==2 ? OptB_keepHide : OptB_keep);
          break;
        case 4: # col ul
        case 5: # col dul
          if ($colTypeN != CT_Money && $ncols == 1) return RGError("The &lt;ul|dul> attribute can only be used for money columns, but this is a $colTypeS column", $p);
          if ($options & OptB_AnyBul) return RGError('Only one of ul or dul should be set', $p);
          $options |= ($i==4 ? OptB_ul : OptB_dul);
          if (($options & OptB_AnyBul) == ($rowOptions & OptB_AnyBul)) return RGError("This &lt;ul|dul> attribute is the same as the row one so is not needed", $p);
          break;
        case 6: # col aul
        case 7: # col adul
          if ($colTypeN != CT_Money && $ncols == 1) return RGError("The &lt;aul|adul> attribute can only be used for money columns, but this is a $colTypeS column", $p);
          if ($options & OptB_AnyAul) return RGError('Only one of aul or adul should be set', $p);
          $options |= ($i==6 ? OptB_aul : OptB_adul);
          if (($options & OptB_AnyAul) == ($rowOptions & OptB_AnyAul)) return RGError("This &lt;ul|dul> attribute is the same as the row one so is not needed", $p);
          break;
       case 8: # col b:BroRef
         if (($tok=GetToken(' ]')) === false) return RGError('b: attribute but no BroName found', $p);
         if (($broRef = BroRef($tok)) === false) return false; # BroRef error
          # valid BroRef
          if ($broRef === $rowBroRef) return RGError("This b:BroRef attribute is the same as the row b:BroRef attribute and so is not needed", $p);
          $dt |= CDtB_ColBroRef; # could also have CDtB_RowOp
          $rowA['ColBroRef'] = $dat = $broRef; # Set $rowA['ColBroRef'] in case a following [col statement is without a b:BroRef or expression
          break;
       case 9: # col c:css
          if (($css = CssAtribute2()) === false) return false;
          if ($colTypeN == CT_Money && $css === $rowA['Mcss']) return RGError("This css $css attribute for a money column is the same as the row mc:css $rowA[Mcss] attribute which applies to money columns, so is not needed", $p);
          break;
        case 10: # col r: expect a repeat intExpr
          if (($rExpr = IntExpr(' ')) === false) return false;
          break;
        case 11: # col s: expect a span #
          if (($span=GetToken(' ')) === false || !ctype_digit($span) || $span==1) return RGError('span # > 1 expected but not found');
          if ($col - $rowA['PrevCol'] < $span) return RGError("Difference between col $col and previous column {$rowA['PrevCol']} not sufficient for span of $span", $p);
          break;
        case 12: # col hor
          $options |= OptB_hor;
          break;
        case 13: # col restatedHdg
          $options |= OptB_pyaHdg;
          break;
      }
    }
    if ($C !== false && $C != ']') {
      # [col ... not at the end so expect a BroRef or an Expr
      if (($retA = BroRefOrExpr()) === false) return false; # Bro loop allowed, Bro or Cell Data not With Tag
      #Dump('expr retA',$retA);
      # 0: type
      #    BRT_Descr   = 1; # descr BroRef i.e. broRef for a Descr($broRef) call
      #    BRT_BroRef  = 2; # BroRef with optional year expr
      #    BRT_Expr    = 3; # Expression as the parameter -> $dat as evaluated for the call
      #    BRT_BroLoop = 4; # Only applicable to BroRefOrExpr() which returns an array of Bro loop info in this case
      # 1: BroRef, BroLoopParams array, or Expr return array according to type
      # 2: optional $yExpr for BRT_BroRef type
      # Bits for dt parameter to [col statement Col() call :
      #                    dat passed     ->
      #                    param expr     dat from param expr with tag if Row BroRef is defined
      # CDtB_ColBroRef     Col BroRef     Col Bro for dat and tag
      # CDtB_ColBroRefExpr [BroRef, Expr] Bro from dat[0] for tag, dat from parameter expression in dat[1], or Expr = fnNum for later eveualtion if CDtB_CellExpr is also set
      # CDtB_RowBroRef     0              Row Bro for dat and tag
      # CDtB_Mapping       -              BroRef is to be checked for possible mapping
      # CDtB_Descr         -              BroRef is to be used for a Descr() call, else for Bro data
      # CDtB_RowOp         0              Dat is to come from a row operation - subtotal, total, rowExpr
      # CDtB_CellExpr      fnNum          Dat is to come from a call to fnNum, evaluated in TableEnd() pass when running col sums and RowOp values are available.
      # Combos             #              Can also be set for a CDtB_ColBroRefExpr Expr
      # CDtB_BroRef         CDtB_ColBroRef | CDtB_ColBroRefExpr | CDtB_RowBroRef
      # CDtB_ColBroRefDescr CDtB_ColBroRef | CDtB_Descr
      # CDtB_RowBroRefDescr CDtB_RowBroRef | CDtB_Descr
      $dat = $retA[1]; # BroRef, BroLoopParams array, or Expr return array according to type
      switch ($retA[0]) {
        case BRT_Descr: # descr BroRef i.e. broRef for a Descr($broRef) call
          if ($broRef) return RGError('Cannot have both a b:BroRef and an expression that is just descr BroRef in a col statement. Remove one of them', $p);
          $broRef = $dat;
          $dt = ($rowBroRef == $broRef ? CDtB_RowBroRefDescr : CDtB_ColBroRefDescr); # no CDtB_RowOp
          break;
        case BRT_BroRef: # BroRef with optional year expr
          if ($broRef) return RGError('Cannot have both a b:BroRef and an expression that is just {y:#} BroRef in a col statement. Remove one of them', $p);
          $broRef = $dat;
          if (!$retA[2]) { # no $yExpr so don't need a parameter expression
            # data to come from Bro with year from column info
            if ($broRef == $rowBroRef) return RGError("This expr of a single BroRef without a year prefix is the same as the row b:BroRef attribute and so is not needed", $p);
            $dt |= CDtB_ColBroRef; # could also have CDtB_RowOp
            break;
          }
          # else have a year expression with the BroRef so use a parameter expression
          if ($colTypeN == CT_Money && $dt == CDtB_RowOp) return RGError('Cannot have both a money column expression and a row operation. A b:BroRef without a year prefix is OK to specify a tag. If you need a column expression here, remove the row operation', $p);
          $dt = CDtB_ColBroRefExpr; # use BroRef from dat[0] for tag with dat from parameter expression passed as dat[1] - param dat = [BroRef, Expr] # CDtB_RowBroRef use Row BroRef
          $dat = 'Data(' . PBroRef($broRef) . "$retA[2])";
          break;
        case BRT_Expr: # Expr return array [expr type, expr code]
          # ignore the expr return type here
          # Expr can be any general expr potentially including Bro and/or cell references
          # if ($colTypeN == CT_Money && $dt == CDtB_RowOp) return RGError('Cannot have both a money column expression and a row operation. A b:BroRef without a year prefix is OK to specify a tag. If you need a column expression here, remove the row operation', $p);
          $dt = $broRef ? CDtB_ColBroRefExpr : 0;
          $dat = $dat[1];
          # Row();Col(1,0,CellDat(1,1));Col(3,0,2*CellDat(1,4));Col(4,0,2*CellDat(1,6));Col(5,0,CellThis(4)-CellThis(3));
          if (strpos($dat, 'Cell') !== false) {
            if (strpos($dat, 'CellThis(') !== false) {
              $dt              |= CDtB_CellExpr;
              $options         |= OptB_thisExpr;
             #$rowA['Options'] |= OptB_thisExpr; # also set for the row re TableEnd() processing. Not used.
              $dat = FnNum($dat, true); # true = a this expr
            }else if (strpos($dat, 'CellDat(') !== false) {
              # This would be OK as a param expr if the row being referenced is in a previous table,                       djh?? Optimise this?
              # or if the cell being referenced in this table does not involve a rowOp or thisExpr. But for now make all delayed CellExprs
              $dt              |= CDtB_CellExpr;
              $options         |= OptB_cellExpr;
              $rowA['Options'] |= OptB_cellExpr; # also set for the row re TableEnd() processing
              $dat = FnNum($dat);
            } # else some other 'Cell' ?
          }
          break;
        case BRT_BroLoop: # BroLoop
          if ($broRef)                 return RGError('Cannot have both a b:BroRef and Bro loop', $p);
          if ($ncols > 1)              return RGError('Only one column can be specified for a Bro loop', $p);
          if ($options & OptB_AnyUl)   return RGError('Bro loop [col statements cannot use the ul, dul, aul, adul attributes. Css is OK', $p);
          if ($options & OptB_AnyKeep) return RGError('Bro loop [col statements cannot use the keep and keepHide attributes. Empty values are skipped', $p);
          if ($options & OptB_pyaHdg)  return RGError('Bro loop [col statements cannot use the restatedHdg attribute', $p);
          if ($rExpr)                  return RGError('Bro loop [col statements cannot use the repeat attribute', $p);
          if ($span)                   return RGError('Bro loop [col statements cannot use the span attribute', $p);
          if ($dt == CDtB_RowOp)       return RGError('Cannot have both a Bro loop and a row operation', $p);
          # $dat is an array of BroLoop parameters
          #   pBroRef     = BroRef of first element of loop e.g. SchInputEntity.Officers.Name@Directors in parameter form
          #   num         = number of times to loop
          #   type        = 0 if BroId loop as for addresses, 1 if ManDiMeId loop as for Directors
          #   testIdDelta = optional id delta from BroId to the BroId of a boolean element of the BRO set with output only if true
          if (strpos($Line, '[col', $P)) return RGError('A col statement with a Bro Loop needs to be the last statement on the line');
          $rowA['ColStatementsA'][999] = "ColBroLoop($col," . implode(',', $dat) . AddParamStr('%s);', [$css, $options]); # 'col' 999 to flag this as ColBroLoop
          $rowA['PrevCol'] = $col;
        ++$rowA['ColUsedA'][$col];
          return '';
      }
      unset($retA);
    }else{
      # [col ... no BroRef/Expr but could have a b:BroRef
      if (!$broRef) { # if $broRef is set had a b:BroRef and $dt = CDtB_ColBroRef | CDtB_RowOp or CDtB_ColBroRefExpr with $dat = the expr
        # don't have a b:BroRef or BroRef/Expr
        if ($rowBroRef)
          $dt = CDtB_RowBroRef; # for the RT code to use $rowBroRef with the column year
        else if (!($dt & CDtB_RowOp)) {
          # don't have a b:BroRef, BroRef/Expr, or RowBroRef and not a RowOp
          if ($rowA['ColBroRef']) { # repeat a col BroRef
            $dt  = CDtB_ColBroRef;
            $dat = $rowA['ColBroRef'];
          }else
            return RGError('No col expression, b:BroRef or row operation but one of these is needed');
        }
      }
    }
    if ($options & OptB_hor)  return RGError('The hor attribute is not applicable to a [col statements that is not a Bro loop', $p);

    if ($dt & CDtB_BroRef) { # CDtB_ColBroRef | CDtB_ColBroRefExpr | CDtB_RowBroRef
      # Mapping needed? Plus set dat
      if ($dt & CDtB_RowBroRef) {
        $broRef = $rowBroRef; # not p form
        $dat = 0;
      }else if ($dt & CDtB_ColBroRef)
        $dat = PBroRef($broRef);
      else if ($dt & CDtB_ColBroRefExpr) # with expr in $dat
        $dat = '[' . PBroRef($broRef) . ",$dat]"; # [Col BroRef, Expr]
      if (is_string($broRef)) {
        $broRefA = explode(',', $broRef);
        # if (!isset($broRefA[1])) echo"broRef=$broRef<br>";
        if (($mnDiMeId = (int)$broRefA[1]) == DiMeId_CoSec || $mnDiMeId == DiMeId_Accountants)
          $dt |= CDtB_Mapping;
      }
    }
    # [col ... repeat for the cols list
    foreach ($colsA as $col) {
      $colTypeN = ($colTypeA ? $colTypeA[$col] : CT_Text);
      $thisDt   = $dt;
      $thisOpts = $options;
      switch ($colTypeN) {
        case CT_Text:
          $thisDt   &= ~CDtB_RowOp; # For a text col unset CDtB_RowOp if that was set
          $thisOpts &= ~(OptB_AnyUl | OptB_AnySign | OptB_AnyKeep); # turn off any ul, sign, keep attributes for a t column when set via a cols list
          if ($thisOpts & OptB_pyaHdg) return RGError("The restatedHdg attribute can only be used in a money column, but column $col is a text column", $p);
          break;
        case CT_Descr:
          if ($thisDt & (CDtB_ColBroRef | CDtB_RowBroRef))
            $thisDt |= CDtB_Descr;  # Set descr column to descr for Bro based cols in a table with defined columns, default for for no defined columns being Text
          $thisDt   &= ~CDtB_RowOp; # For a descr col unset CDtB_RowOp if that was set
          $thisOpts &= ~(OptB_AnyUl | OptB_AnySign | OptB_AnyKeep); # turn off any ul, sign, keep attributes for a d column when set via a cols list
          if ($thisOpts & OptB_pyaHdg) return RGError("The restatedHdg attribute can only be used in a money column, but column $col is a descr column", $p);
          break;
        case CT_Note:
          if ($thisDt) return RGError("Column $col is a note column for which an expression (usually an xref statement) is required to define the note number", $p);
          if ($thisOpts & OptB_pyaHdg) return RGError("The restatedHdg attribute can only be used in a money column, but column $col is a note column", $p);
          $thisDt   &= ~CDtB_RowOp; # Unset CDtB_RowOp if that was set for a note col
          $thisOpts &= ~(OptB_AnyUl | OptB_AnySign | OptB_AnyKeep); # turn off any ul, sign, keep attributes for a note column when set via a cols list
          break;
        case CT_Money:
          if (($thisDt & CDtB_RowOp) && $rowFrCols && strpos(",$rowFrCols,", ",$col,") !== false) # RowOp with money col also in row frCols
            return RGError("Col $col is a money column but the subtotal/total row statement on this format line included a cols:$rowA[Cols] attribute which included this column (col # $col). Edit the row cols: attribute or remove this col statement", $p);
          if (($thisDt & CDtB_RowOp) && $rowToCols && strpos(",$rowToCols,", ",$col,") !== false) # Money col also in row toCols
            return RGError("Col $col is a money column but the subtotal/total row statement on this format line included a cols:$rowA[Cols] attribute which included this column (col # $col) as a to (->) column. Edit the row cols: attribute or remove this col statement", $p);
          if ($thisOpts & OptB_pyaHdg && $TableA['Cols'][$col] === '0')
            $thisOpts &= ~OptB_pyaHdg; # zap restatedHdg for money year 0 column
          if ($rowOptions) # Set the money col options for <dr|cr> <ul|dul> <aul|adul> to the row settings if col values not set
            $thisOpts |= (($thisOpts & OptB_AnySign) ? : ($rowOptions & OptB_AnySign)) |
                         (($thisOpts & OptB_AnyAul)  ? : ($rowOptions & OptB_AnyAul))  |
                         (($thisOpts & OptB_AnyBul)  ? : ($rowOptions & OptB_AnyBul));
          break;
      }
      # echo "Col($col, thisDt=$thisDt, dat=$dat, thisOpts=$thisOpts, css=$css, rExpr=$rExpr, span=$span<br>";
      $rowA['ColStatementsA'][$col] = AddParamStr("$col%s", [$thisDt, $dat, $thisOpts, $css, $rExpr, $span]); # 1, 2, 3, 4, 5, 6, or 7 parameters
      $rowA['PrevCol'] = $col;
    ++$rowA['ColUsedA'][$col];
      if ($thisDt & CDtB_RowBroRef)
        ++$rowA['RowBroRefUsedA'][$col];
      $rExpr = $span = 0;
    }
    return '';
  } # end of col
  return RGError('Statement not recognised');
}

# [date {<f|s|x|y>} {y:year} {c:css} dD]
function Dstatements() {
  global $TableA;
  if ($TableA) return RGError('In a table where date statements cannot be used stand alone. They may be used as part of a col expression');
  if (($dCodeA = ParseDateStatement()) === false) return false; # returns [type | code] where type can be ExT_String or ExT_Integer, or false
  # ignore type in $dCodeA[0] here
  return "P({$dCodeA[1]});"; # wrap stand alone [date ...] in a <p> tag
}

# ParseDateStatement()
# Returns [type | code] where type can be ExT_String or ExT_Integer, or false
# [date {<f|s|x|y>} {y:year} {c:css} dD]
function ParseDateStatement() {
  global $C, $P, $Line, $BroInfoA, $InFuncB;
  if (MatchStep('date')) {
    # $a = DATE_Full | DATE_Short | DATE_iXBRL | DATE_Year
    if ($C && isset($Line[$P+1]) && $Line[$P+1] == ' ') {
      # looks like we have an f|s|x|y attribute
      if (($a = strpos('fsxy', $C)) === false) return RGError("Unknown date attribute $C - expecting one of f|s|x|y");
      Step(); # over the f|s|x|y
    }else $a = DATE_Full; # 0
    $css = $yExpr = 0;
    while (($i = MatchOneStep(['c:', 'y:'])) !== false) {
      switch ($i) {
        case 0: # c:
          if (($css = CssAtribute2()) === false) return false;
          break;
        case 1: # y: expect a year intExpr
          if (($yExpr = IntExpr(' ')) === false) return false;
          break;
      }
    }
    # dD
    $p = $P;
    if (($tok = GetToken(' ]')) !== false) {
      # Got a tok. Expect a date type Bro element or a date var
      $exprType = ($a == DATE_Year ? ExT_Integer : ExT_String);
      if (($broRef = BroRef($tok, true)) !== false) { # no BroRef error
        # Bro but is it a date type?
        BroRefToBroIdAndParamForm($broRef, $broId, $pBroRef);
        if ($BroInfoA[$broId][BroI_DataTypeN] != ExT_Date) #  DT_Date == ExT_Date
          return RGError("$tok is a Bro but data type " . ExprTypeToStr($BroInfoA[$broId][BroI_DataTypeN]) . ", not date as required", $p);
        # Got a date type Bro
        # return array of [type | code]
        return [$exprType, AddParamStr("DateStrWithTag($pBroRef%s)", [$a, $yExpr, $css])];
      }
      if (($symbolA = STlookup($tok)) !== false) {
        if ($symbolA[STI_EType] == ExT_Date) { # Got a date type. Should be an ST_Var
          # return array of [type | code]
          if ($symbolA[STI_SType] != ST_Var) RGError("$tok is of type date but is not a Bro or Var. This is an unexpected error. Please report it to djh if seen.", $p);
          if ($yExpr) return RGError('A year expression can only be used with a Bro date, not a variable', $p);
          return [$exprType, 'DateStr($' . ($InFuncB ? "GLOBALS['$tok']" : $tok) . AddParamStr('%s)', [$a, $css])];
        }
        return RGError("$tok is data type " . ExprTypeToStr($symbolA[STI_EType]) . ", not date as required", $p);
      }
      return RGError("$tok has not been defined", $p);
    }
    return RGError('Date variable or date type Bro missing', $p);
  }
  return RGError('Statement not recognised');
}

# [else]
# [else if ... {this}]
# [end]
function Estatements() {
  global $Level, $LevelsInfoA, $C, $P;
  if (MatchStep('end')) return ProcessEnd(); # [end]
  $p = $P;
  if (MatchStep('else')) {
    # [else] or [else if ...]
    if (!$Level)
      return RGError("This [else is out of place", $p);
    $infoA = $LevelsInfoA[$Level];
    $typeN = $infoA[LII_Type];
    if ($typeN != LT_If && $typeN != LT_Else_If)
      return RGError('Previous control statement [' . LevelInfo() . ' is not an [if or [else if as required for an [else or [else if', $p);
    $Level--;
    $p = $p;
    if (MatchStep('if')) {
      # [else if ... {this}]
      if (($retA = Expr()) === false) return false; # error in Expr
      # $retA = [expr type, code]
      $expr = $retA[1]; # accept any expr type here
      # expect then or ]
      MatchStep('then');
      if ($C == ']')
        return IncrementLevel(LT_Else_If, "}else if ($expr) {");
    }else{
      # [else]
      return IncrementLevel(LT_Else, '}else{');
    }
    return RGError('syntax error', $p);
  }
  return RGError('Statement not recognised');
}

# [footer]
# [function nameF{(...)}]
function Fstatements() {
  if (MatchStep('footer')) return StartFunction(ST_Footer); # [footer
  # else expect [function... statement
  # djh??
  return RGError('Statement not recognised');
}

# [header]
# [h<1|..|4> {c:css} Expr]
function Hstatements() {
  global $C;
  if (MatchStep('header')) return StartFunction(ST_Header); # [header
  # else expect
  # [h<1|..|4> {c:css} expr]
  if (Step() !== false) { # step over the h and not at eol
    if ($C < '1' || $C > '4')
      return RGError("h$C found but h1, h2, h3, or h4 expected");
    $n = (int)$C;
    if (Step() !== false) { # step over the digit and not at eol
      # {c:css} or expr
      if (($css = CssAtribute()) === false) return false; # error in css attribute
      # Got css or it was not specified. Now expect expr
      if (($retA = BroRefOrExpr(false, true)) === false) return false; # false = Bro loop not allowed, true = Bro or Cell Data With Tag
      # 0: type
      # 1: BroRef, or Expr return array according to type
      # 2: optional $yExpr for BRT_BroRef type
      $dat = $retA[1];
      switch ($retA[0]) {
        case BRT_Descr: # descr BroRef i.e. broRef for a Descr($broRef) call
          $dat = "Descr($dat)";
          break;
        case BRT_BroRef: # BroRef with optional year expr
          $dat = 'DataWithTag(' . PBroRef($dat) . "$retA[2])";
          break;
        case BRT_Expr: # Expr return array
          if ($dat[0] != ExT_String) return RGError('Expression is not a Bro reference or a string type as expected for an [h... statement');
          $dat = $dat[1];
          break;
      }
      return "H($n,$dat$css);";
    }
  }
  return RGError('Statement not complete');
}

# [if expr {then}]
function Istatements() {
  global $Line, $P, $C, $EolCode;
  if (MatchStep('if')) {
    if (($retA = Expr()) === false) return false; # error in Expr
    # $retA = [expr type, code]
    # expect then or ]
    $expr = $retA[1]; # accept any expr type here
    MatchStep('then');
    if ($C == ']') {
      if (strpos($Line, '[', $P)) {
        # more statements on this line so assume a one line if
        $EolCode = '}';
        return "if ($expr) {";
      }else # no more statements so assume block
        return IncrementLevel(LT_If, "if ($expr) {");
    }
    return RGError('Unexpected stuff');
  }
  return RGError('Statement not recognised');
}

# [line]
# [lines intExpr]
function Lstatements() {
  global $C, $TableA;
  if (MatchStep('line')) { # [line], [lines intExpr]
    if ($TableA) $TableA['RowA']['PrevCol'] = 0;  # RT Lines() makes Row() calls for a table
    if ($C == ']') return 'Lines();';
    # [lines intExpr]
    if ($C == 's' && Step()) { # step over the s and not at eol
      if (($expr = IntExpr()) === false) return false; # error in intExpr
      return "Lines($expr);";
    }
    return RGError('syntax error');
  }
  return RGError('Statement not recognised');
}

# [nl]
# [np]
function Nstatements() {
  global $TableA;
  # if (MatchStep('nl')) return 'EndLine();';
  if (MatchStep('np')) {
    if ($TableA) return RGError('In a table where np statements cannot be used. Let David know if a carried forward operation across a page break is needed');
    return 'NewPage();';
  }
  return RGError('Statement not recognised');
}

# [para nameP]
# [page# {c:css}]
# [p {c:css} strExpr]
function Pstatements() {
  global $C, $TableA;
  if (MatchStep('para')) { # [para nameP]
    if ($TableA) return RGError('In a table where para statements cannot be used');
    if (($name = GetToken(']')) === false)
      return RGError('para statement is missing a name');
    return StartFunction(ST_Para, $name); # [para nameP]
  }
  if (MatchStep('page#')) { # [page# {c:css}]
    if ($TableA) return RGError('In a table where page statements cannot be used');
    if (($css = CssAtribute(false)) === false) return false; # error in css attribute. false in CssAtribute() call to avoid leading , to css
    return "P(PageStr($css));"; # wrap stand alone [page# ...] in a <p> tag
  }
  # Expect [p ...
  # [p {c:css} strExpr]
  if (Step() !== false) { # step over the p
    if ($TableA) return RGError('In a table where p statements cannot be used. Use a col statement or move the p statement outside the table');
    if (($css = CssAtribute()) === false) return false; # error in css attribute
    # Got css or it was not specified. Now expect expr
    if (($retA = BroRefOrExpr(true, true)) === false) return false; # true = Bro loop allowed, true = Bro or Cell Data With Tag
    # 0: type
    # 1: BroRef, BroLoopParams array, or Expr return array according to type
    # 2: optional $yExpr for BRT_BroRef type
    $dat = $retA[1];
    switch ($retA[0]) {
      case BRT_Descr: # descr BroRef i.e. broRef for a Descr($broRef) call
        $dat = "Descr($dat)";
        break;
      case BRT_BroRef: # BroRef with optional year expr
        $dat = 'DataWithTag(' . PBroRef($dat) . "$retA[2])";
        break;
      case BRT_Expr: # Expr return array
        if ($dat[0] != ExT_String) return RGError('Expression is not a Bro reference, a Bro loop, or a string type as expected for a [p... statement');
        $dat = $dat[1];
        break;
      case BRT_BroLoop: # BroLoop
        # $dat is an array of BroLoop parameters
        #   pBroRef     = BroRef of first element of loop e.g. SchInputEntity.Officers.Name@Directors in parameter form
        #   num         = number of times to loop
        #   type        = 0 if BroId loop as for addresses, 1 if ManDiMeId loop as for Directors
        #   testIdDelta = optional id delta from BroId to the BroId of a boolean element of the BRO set with output only if true
        return 'PBroLoop(' . implode(',', $dat) . "$css);";
    }
    return "P($dat$css);";
  }
  return RGError('[p ... syntax error');
}

function TableInit($cols=0) {
  global $TableA;
  $dCol = $moneyColsB = 0;
  if ($cols) {
    $n = strlen($cols);
    $cols = ' ' . $cols; # -> base 1 for CT. Not done RT.
    $colTypeA = []; # col => CT_Text | CT_Descr | CT_Note | CT_Money | CT_Calc | CT_Perc   Different from RT where the equivalent array is initialised to 29 x 0 values
    for ($col=1; $col<=$n;++$col) {
      $c = $cols[$col];
      if (ctype_digit($c)) {
        $colTypeA[$col] = CT_Money;
        $moneyColsB = true;
      }else
        switch ($c) {
          case 't': $colTypeA[$col] = CT_Text; break;
          case 'd': $colTypeA[$col] = CT_Descr;
            if (!$dCol) $dCol = $col;
            break;
          case 'n': $colTypeA[$col] = CT_Note; break;
          case 'c': $colTypeA[$col] = CT_Calc; break;
          case 'p': $colTypeA[$col] = CT_Perc; break;
        }
    }
  }else
    $colTypeA = $n = 0;

  $TableA = [
    'Cols'       => $cols,
    'NCols'      => $n,
    'ColTypeA'   => $colTypeA,
    'DCol'       => $dCol,
    'MoneyColsB' => $moneyColsB,
  ];
  TableRowInit();
}

function TableRowInit() {
  global $TableA;
  $nCols = $TableA['NCols'] ? : 29;
  $TableA['RowA'] = [
    'BroRef'         => 0, # [row b:BroRef
    'Options'        => 0, # [row {<dr|cr>} {<keep|keepHide>} {<ul|dul>} {<aul|adul>} {<subtotal|total>} attributes
    'Mcss'           => 0, # [row mc:css attribute
    'Cols'           => 0, # [row cols: attribute in source form
    'FrCols'         => 0, # [row cols: derived frCols cs list
    'ToCols'         => 0, # [row cols: derived toCols cs list
    'ColBroRef'      => 0, # [col b:BroRef in case is to be copied to a following [col statement
    'PrevCol'        => 0, # Used by [col to move the col # along if the col statement has no cols list
    'ColUsedA'       => array_fill(1, $nCols, 0), # to enable checking of duplicate col use, and for use by AddTableRow() re adding columns
    'RowBroRefUsedA' => array_fill(1, $nCols, 0), # to enable checking of RowBroRef use by AddTableRow() re adding columns
    'RowParamters'   => 0,      # /- Used to sort col statements per row line into ascending col # order
    'ColStatementsA' => [] # |
  ];
}

# [row {b:BroRef} {n:RowName} {alt:RowName} {<dr|cr>} {<keep|keepHide>} {<ul|dul>} {<aul|adul>} {mc:css} {rc:css} {<subtotal|total>} {cols:cols list}]
function Rstatements() {
  global $C, $P, $Line, $TableA, $RowNamesA, $BroNamesMapA;
  if (MatchStep('row')) { # [row
    if (!$TableA) return RGError('Not currently in a table which is required for a row statement to be used');
    TableRowInit();
    $rowA     = &$TableA['RowA'];
    $colTypeA = &$TableA['ColTypeA'];
    $broRef = $options = $rowNum = $frCols = $toCols = $mcss = $rcss = $altRowNum = 0;
    $noMoneyColsB = !$TableA['MoneyColsB'];
    # row options 0 to 8 are the same as for the col statement and 9 is mc: vs c:
    #              0     1     2           3       4     5      6      7       8     9      10     11    12     13          14         15
    $optionsA = ['dr', 'cr', 'keepHide', 'keep', 'ul', 'dul', 'aul', 'adul', 'b:', 'mc:', 'rc:', 'n:', 'alt:', 'subtotal', 'total', 'cols:'];
    for ($p=$P; ($i = MatchOneStep($optionsA)) !== false; $p=$P) {
      switch ($i) {
        case 0: # row dr
        case 1: # row cr
          if ($noMoneyColsB) return RGError('The &lt;dr|cr> attribute can only be used for a row with money columns, but this table does not have any money columns', $p);
          if ($options & OptB_AnySign) return RGError('Only one of dr or cr should be set', $p);
          $options |= ($i ? OptB_cr : OptB_dr);
          break;
        case 2: # row keepHide
        case 3: # row keep
          if ($noMoneyColsB) return RGError('The &lt;keep|keepHide> attribute can only be used for a row with money columns, but this table does not have any money columns', $p);
          if ($options & OptB_AnyKeep) return RGError('Only one of keep or keepHide should be set', $p);
          $options |= ($i==2 ? OptB_keepHide : OptB_keep);
          break;
        case 4: # row ul
        case 5: # row dul
          if ($noMoneyColsB) return RGError('The &lt;ul|dul> attribute can only be used for a row with money columns, but this table does not have any money columns', $p);
          if ($options & OptB_AnyBul) return RGError('Only one of ul or dul should be set', $p);
          $options |= ($i==4 ? OptB_ul : OptB_dul);
          break;
        case 6: # row aul
        case 7: # row adul
          if ($noMoneyColsB) return RGError('The &lt;aul|adul> attribute can only be used for a row with money columns, but this table does not have any money columns', $p);
          if ($options & OptB_AnyAul) return RGError('Only one of aul or adul should be set', $p);
          $options |= ($i==6 ? OptB_aul : OptB_adul);
          break;
        case 8: # row b: BroRef
          if (($tok=GetToken(' ]')) === false) return RGError('b: attribute but no BroName found', $p);
          if (($broRef = BroRef($tok)) === false) return false; # BroRef error
          # valid BroRef
          $rowA['BroRef'] = $broRef;
          break;
        case 9: # row mc: css to be applied to Money cell td tags
          if ($noMoneyColsB) return RGError('The mc:css attribute can only be used for a row with money columns, but this table does not have any money columns', $p);
          if (($mcss = CssAtribute2()) === false) return false;
          $rowA['Mcss'] = $mcss;
          break;
        case 10: # row rc: css to be applied to the Row tr tag
          if (($rcss = CssAtribute2()) === false) return false;
          break;
        case 11: # row n: RowName
          if ($noMoneyColsB) return RGError('The n:RowName attribute can only be used for a row with money columns, but this table does not have any money columns', $p);
          if (($tok=GetToken(' ]')) === false) return RGError('n: attribute but no row name found', $p);
          if (!IsValidName($tok, 0, 0)) return false; # just PHP name check
          if (strpos($tok, ':') !== false) return RGError("$tok contains a : which is an illegal character in a row name", $p);
          if (isset($RowNamesA[$tok]))     return RGError("$tok is already in use as a row name in {$RowNamesA[$tok][0]}", $p);
          if (isset($BroNamesMapA[$tok])) return RGError("$tok is a BroName and so cannot be used as a row name", $p);
          if (($symbolA = STlookup($tok)) !== false) return RGError("$tok is already in use for a " . SymbolTypeToStr($symbolA[STI_SType]) . ' name and so cannot be used as a row name', $p);
          $rowNum = count($RowNamesA) + 1; # base 1
          $RowNamesA[$tok] = [FormatAndLine(), $TableA, $rowNum];
          break;
        case 12: # row alt: RowName
          $ap = $p;
          if ($noMoneyColsB) return RGError('The alt:RowName attribute can only be used for a row with money columns, but this table does not have any money columns', $p);
          if (($tok=GetToken(' ]')) === false) return RGError('alt: attribute but no alternative title row name found', $p);
          if (!isset($RowNamesA[$tok])) return RGError("$tok is not a known row name", $p);
          $altRowNum = $RowNamesA[$tok][2];
          break;
        case 13: # row subtotal
        case 14: # row total
          if ($noMoneyColsB) return RGError('The &lt;subtotal|total> attribute can only be used for a row with money columns, but this table does not have any money columns', $p);
          if ($options & OptB_AnyTotalOp) return RGError('Only one &lt;subtotal|total> attribute should be set', $p);
          $options |= ($i == 13 ? OptB_subtotal : OptB_total);
          break;
        case 15: # row cols: => frCols and optionally toCols
          $cp = $p;
          if ($noMoneyColsB) return RGError('The cols: attribute can only be used for a row with money columns, but this table does not have any money columns', $p);
          if (($retA = ColListFrTo()) === false) return false; # false or [source list, [fr cols] {,[to cols]}]
          $rowA['Cols'] = $retA[0];
          $frColsA = $retA[1];
          if (isset($retA[2])) {
            # Have fr and to cols
            if (!($options & OptB_AnyTotalOp)) return RGError("This cols: list contains '->' or 'to' columns but that is only valid for a subtotal or total row operation", $p);
            #echo "For $Line fr=", implode(',', $frColsA[0]), ' to=', implode(',', $frColsA[1]), '<br>';
            $toColsA = $retA[2];
            $rowA['ToCols'] = $toCols = implode(',', $toColsA);
          } # else just frColsA returned
          $tableCols = $TableA['Cols'];
          foreach ($frColsA as $col)
            $tableCols[$col] = 'x';
          if ($tableCols == str_replace(['0', '1', '2', '3'], '', $tableCols))
            return RGError("The default is all the money columns, so this cols: list which includes all the money columns is not needed", $p);
          $rowA['FrCols'] = $frCols = implode(',', $frColsA);
          break;
      }
    }
    if ($C != ']') return RGError('Unexpected stuff before the ]. The row syntax is [row {b:BroRef} {n:RowName} {alt:RowName} {&lt;dr|cr>} {&lt;keep|keepHide>} {&lt;ul|dul>} {&lt;aul|adul>} {mc:css} {rc:css} {&lt;subtotal|total>} {cols:cols list}]');
    if ($frCols && !$broRef && !($options & OptB_AnyTotalOp)) return RGError("The cols: list serves no purpose without a b:BroRef or a &lt;subtotal|total> choice so remove it", $cp);
    if ($rowNum && $rowNum == $altRowNum) return RGError("$tok is the name of the current row, so alt:$tok is not needed", $ap);
    # Set Keep for a total if a keep option isn't already set
    if (($options & OptB_total) && !($options & OptB_AnyKeep))
      $options |= OptB_keep;
    $rowA['Options'] = $options; # with sign and ul options for potential inclusion in Col() calls
                                 # sign and ul options are unset in AddTableRow() for the Row() call as these are transferred to the Col() calls.
                                 # OptB_cellExpr can be set by a Col() statement
    $rowA['RowParamters'] = [$broRef, $rowNum, $frCols, $toCols, $mcss, $rcss, $altRowNum];
    return ''; # [row
  }
  return RGError('Statement not recognised');
}

/* AddTableRow()
   -------------
Called when end of a format line is reached when $TableA is defined and there is a row statement
to add col statements if needed, and output the row.

The row code prevents frCols being used without a RowBroRef or RowOp so those cases don't reach here.
frCols and toCols are always money cols. (djh?? Allow c cols?)

Add col statement cases: (Plus some n, c, p checks)
  Ca col R R f
  se d m B O C Action re Adding Cols or Comment
   1 *         Nothing. This will usually be the BroLoop case if the table has defined cols.
   2 *         Nothing
   3 * *       Nothing
   4 * *   *   Nothing
   5 * *   * * Add the frCols as CDtB_RowOp and zero the row frCols param if no toCols. (Addition of m cols in frCols is prevented by col add loop code.)
     * *     * Na as frcols but no BroRef or RowOp
   6 * * *     Nothing but error if RowBroRef is not used for at least one of the columns
   7 * * * *   Nothing but error if RowBroRef is not used for at least one of the columns
   8 * * * * * Add the frCols as CDtB_RowBroRef | CDtB_RowOp and zero the row frCols param if no toCols. (Addition of m cols in frCols is prevented by col add loop code.)
   9 * * *   * Add any missing frCols as CDtB_RowBroRef and zero the row frCols param.
  10 *     *   Add all money cols as CDtB_RowOp
  11 *     * * Add the frCols as CDtB_RowOp and zero the row frCols param if no toCols
     *       * Na as frcols but no BroRef or RowOp
  12 *   *     Add all money cols as CDtB_RowBroRef
  13 *   * *   Add all money cols as CDtB_RowBroRef | CDtB_RowOp
  14 *   * * * Add the frCols as CDtB_RowBroRef | CDtB_RowOp and zero the row frCols param if no toCols
  15 *   *   * Add the frCols as CDtB_RowBroRef and zero the row frCols param
  Ca col R R f
  se d m B O C Action re Adding Cols or Comment
  21           Error with no cols - need something
  22           Error with just an n, c, or p cols
  23   *       Nothing
  24   *   *   Nothing
  25   *   * * Add the frCols as CDtB_RowOp and zero the row frCols param if no toCols. (Addition of m cols in frCols is prevented by col add loop code.)
       *     * Na as frcols but no BroRef or RowOp
  26   * *     Nothing but error if RowBroRef is not used for at least one of the columns
  27   * * *   Nothing but error if RowBroRef is not used for at least one of the columns
  28   * * * * Add the frCols as CDtB_RowBroRef | CDtB_RowOp and the t col as CDtB_RowBroRefDescr and zero the row frCols param if no toCols. (Addition of m cols in frCols is prevented by col add loop code.)
  29   * *   * Add any missing frCols as CDtB_RowBroRef and the t col as CDtB_RowBroRefDescr and zero the row frCols param.
  30       *   Add all money cols as CDtB_RowOp
  31       * * Add the frCols as CDtB_RowOp and zero the row frCols param if no toCols
             * Na as frcols but no BroRef or RowOp
  32     *     Add all money cols as CDtB_RowBroRef and the t col as CDtB_RowBroRefDesc
  33     * *   Add all money cols as CDtB_RowBroRef | CDtB_RowOp and the t col as CDtB_RowBroRefDesc
  34     * * * Add the frCols as CDtB_RowBroRef | CDtB_RowOp and the t col as CDtB_RowBroRefDescr and zero the row frCols param if no toCols
  35     *   * Add the frCols as CDtB_RowBroRef and the t col as CDtB_RowBroRefDescr and zero the row frCols param
*/
function AddTableRow() { # Called at end of a format line when $TableA is defined and there is a RowParamters entry
  global $TableA, $Errors;
  $nCols =  $TableA['NCols'];
  $rowA  = &$TableA['RowA'];
  if ($rowA['RowParamters'] == 1) # empty Row() call
    $rowBroRef = $rowNum = $frCols = $toCols = $mcss = $rcss = $altRowNum = 0;
  else
    list($rowBroRef, $rowNum, $frCols, $toCols, $mcss, $rcss, $altRowNum) = $rowA['RowParamters'];
  $rowOptions = $rowA['Options']; # OptB_cellExpr can be set by a Col() statement
  if ($nCols) { # table has defined columns
    $dCol     =  $TableA['DCol'];
    $colTypeA = &$TableA['ColTypeA'];
    $colUsedA       = &$rowA['ColUsedA']; # to enable checking of duplicate col use, and for use by AddTableRow() re adding columns
    $rowBroRefUsedA = &$rowA['RowBroRefUsedA']; # by col which would enable checks by col type but not currently done
    $rowOp = $rowOptions & OptB_AnyTotalOp;
    $tot = $nt = $nd = $nn = $nm = $nc = $np = $rowBroRefsUses = 0; # number of t, n, m, c, p cols defined by col statements
    for ($col=1; $col<=$nCols; ++$col) {
      if (($uses = $colUsedA[$col]) > 1) return ATRError("Col $col has been used $uses times in this format line but a col should only be used once per line");
      if ($uses) { # $col used once
        ++$tot;
        switch ($colTypeA[$col]) {
          case CT_Text:  ++$nt; break;
          case CT_Descr: ++$nd; break;
          case CT_Note:  ++$nn; break;
          case CT_Money: ++$nm; break;
          case CT_Calc:  ++$nc; break;
          case CT_Perc:  ++$np; break;
        }
        $rowBroRefsUses += $rowBroRefUsedA[$col];
      }
    }
    # $tot = total columns used should be the same as count($rowA['ColStatementsA'])
    #echo "nt=$nt nd=$nd nn=$nn nm=$nm tot=$tot<br>";
    $colsToAddCase = 0;
    if ($nd || !$dCol) { # d col or a d col isn't defined for the table = cases 1 to 15
      /*Ca col R R f
        se d m B O C Action re Adding Cols or Comment
         1 *         Nothing. This will usually be the BroLoop case if the table has defined cols.
         2 *         Nothing
         3 * *       Nothing
         4 * *   *   Nothing
         5 * *   * * Add the frCols as CDtB_RowOp and zero the row frCols param if no toCols. (Addition of m cols in frCols is prevented by col add loop code.)
           * *     * Na as frcols but no BroRef or RowOp
         6 * * *     Nothing but error if RowBroRef is not used for at least one of the columns
         7 * * * *   Nothing but error if RowBroRef is not used for at least one of the columns
         8 * * * * * Add the frCols as CDtB_RowBroRef | CDtB_RowOp and zero the row frCols param if no toCols. (Addition of m cols in frCols is prevented by col add loop code.)
         9 * * *   * Add any missing frCols as CDtB_RowBroRef and zero the row frCols param.
        10 *     *   Add all money cols as CDtB_RowOp
        11 *     * * Add the frCols as CDtB_RowOp and zero the row frCols param if no toCols
           *       * Na as frcols but no BroRef or RowOp
        12 *   *     Add all money cols as CDtB_RowBroRef
        13 *   * *   Add all money cols as CDtB_RowBroRef | CDtB_RowOp
        14 *   * * * Add the frCols as CDtB_RowBroRef | CDtB_RowOp and zero the row frCols param if no toCols
        15 *   *   * Add the frCols as CDtB_RowBroRef and zero the row frCols param */
      if ($nm) {
        # d and m cols = cases 3 to 9
        if ($frCols) # d, m cols, and frCols = cases 5, 8, 9
          $colsToAddCase = ($rowBroRef ? ($rowOp ? 8 : 9) : 5);
        # else Nothing for cases 3, 4, 6, 7. (RowBroRef use for cases 6 and 7 is checked at the end)
      }else{
        # d col, !m col = cases 1, 2, 10 to 15
        if ($rowBroRef)
          # d col, !m col, RowBroRef = cases 12 to 15, 14 & 15 with frCols, 12 & 13 wo
          $colsToAddCase = ($frCols ? ($rowOp ? 14 : 15) : ($rowOp ? 13 : 12));
        else{
          # d col, !m col, !RowBroRef = cases 1, 2, 10, 11
          if ($rowOp)
            # d col, !m col, !RowBroRef, RowOp = cases 10, 11
            $colsToAddCase = $frCols ? 11 : 10;
          # else d col, !m col, !RowBroRef, !RowOp = cases 1, 2 with nothing to do
        }
      }
      # finished the t col branch
    }else{
      # !d col = cases 13 to 24
      /*Ca col R R f
        se d m B O C Action re Adding Cols or Comment
        21           Error with no cols - need something
        22           Error with just an n, c, or p cols
        23   *       Nothing
        24   *   *   Nothing
        25   *   * * Add the frCols as CDtB_RowOp and zero the row frCols param if no toCols. (Addition of m cols in frCols is prevented by col add loop code.)
             *     * Na as frcols but no BroRef or RowOp
        26   * *     Nothing but error if RowBroRef is not used for at least one of the columns
        27   * * *   Nothing but error if RowBroRef is not used for at least one of the columns
        28   * * * * Add the frCols as CDtB_RowBroRef | CDtB_RowOp and the t col as CDtB_RowBroRefDescr and zero the row frCols param if no toCols. (Addition of m cols in frCols is prevented by col add loop code.)
        29   * *   * Add any missing frCols as CDtB_RowBroRef and the t col as CDtB_RowBroRefDescr and zero the row frCols param.
        30       *   Add all money cols as CDtB_RowOp
        31       * * Add the frCols as CDtB_RowOp and zero the row frCols param if no toCols
                   * Na as frcols but no BroRef or RowOp
        32     *     Add all money cols as CDtB_RowBroRef and the d col as CDtB_RowBroRefDesc
        33     * *   Add all money cols as CDtB_RowBroRef | CDtB_RowOp and the d col as CDtB_RowBroRefDesc
        34     * * * Add the frCols as CDtB_RowBroRef | CDtB_RowOp and the d col as CDtB_RowBroRefDescr and zero the row frCols param if no toCols
        35     *   * Add the frCols as CDtB_RowBroRef and the d col as CDtB_RowBroRefDescr and zero the row frCols param */
      if ($nm) {
        # !d and m cols = cases 23 to 29
        if ($frCols) # !d, m cols, and frCols = cases 25, 28, 29
          $colsToAddCase = ($rowBroRef ? ($rowOp ? 28 : 29) : 25);
        # else Nothing for cases 23, 24, 26, 27. (RowBroRef use for cases 26 and 27 is checked at the end)
      }else{
        # !d col, !m col = cases 21, 22, 30 to 35
        if ($rowBroRef)
          # !d col, !m col, RowBroRef = cases 32 to 35, 34 & 35 with frCols, 32 & 33 wo
          $colsToAddCase = ($frCols ? ($rowOp ? 34 : 35) : ($rowOp ? 33 : 32));
        else{
          # !d col, !m col, !RowBroRef = cases 21, 22, 30, 31
          if ($rowOp)
            # !d col, !m col, !RowBroRef, RowOp = cases 30, 31
            $colsToAddCase = $frCols ? 31 : 30;
          else{
            # !d col, !m col, !RowBroRef, !RowOp = cases 21, 22 -> errors
            if (!$tot) return ATRError('There is a Row statement but no Col statements on this format line and nothing for the compiler to use to generate automatic col statements');
            if ($nn && !$nt && !$nc && !$np)
              return ATRError('There is only a note column col statement on this format line, but a note by itself is not expected. If you have a need for this, let David know.');
            return ATRError("There is a Row statement but no title or money Col statements on this format line, just $nn note column(s), $nc calculation column(s), and $np percentage column(s), which is not an expected column combination. If you have a need for this, let David know.");
          }
        }
      }
      # finished the !d col branch
    }
    if ($colsToAddCase) {
      $d = $m = $f = $dt = 0; # d for add d as CDtB_RowBroRefDescr, m for add all money as $dt, f for add frCols as $dt
      switch ($colsToAddCase) {
                         # Ca col R R f
                         # se d m B O C Action re Adding Cols or Comment
        case 10:         # 10 *     *   Add all money cols as CDtB_RowOp
        case 30: $m = 1; # 30       *   Add all money cols as CDtB_RowOp
          $dt = CDtB_RowOp;
          break;
        case  5:         #  5 * *   * * Add the frCols as CDtB_RowOp and zero the row frCols param if no toCols. (Addition of m cols in frCols is prevented by col add loop code.)
        case 11:         # 11 *     * * Add the frCols as CDtB_RowOp and zero the row frCols param if no toCols
        case 25:         # 25   *   * * Add the frCols as CDtB_RowOp and zero the row frCols param if no toCols. (Addition of m cols in frCols is prevented by col add loop code.)
        case 31: $f = 1; # 31       * * Add the frCols as CDtB_RowOp and zero the row frCols param if no toCols
          $dt = CDtB_RowOp;
          break;
        case 32: $d = 1; # 32     *     Add all money cols as CDtB_RowBroRef and the d col as CDtB_RowBroRefDesc
        case 12: $m = 1; # 12 *   *     Add all money cols as CDtB_RowBroRef
          $dt = CDtB_RowBroRef;
          break;
        case 33: $d = 1; # 33     * *   Add all money cols as CDtB_RowBroRef | CDtB_RowOp and the d col as CDtB_RowBroRefDesc
        case 13: $m = 1; # 13 *   * *   Add all money cols as CDtB_RowBroRef | CDtB_RowOp
          $dt = CDtB_RowBroRef | CDtB_RowOp;
          break;
        case 28:         # 28   * * * * Add the frCols as CDtB_RowBroRef | CDtB_RowOp and the d col as CDtB_RowBroRefDescr and zero the row frCols param if no toCols. (Addition of m cols in frCols is prevented by col add loop code.)
        case 34: $d = 1; # 34     * * * Add the frCols as CDtB_RowBroRef | CDtB_RowOp and the d col as CDtB_RowBroRefDescr and zero the row frCols param if no toCols
        case  8:         #  8 * * * * * Add the frCols as CDtB_RowBroRef | CDtB_RowOp and zero the row frCols param if no toCols. (Addition of m cols in frCols is prevented by col add loop code.)
        case 14: $f = 1; # 14 *   * * * Add the frCols as CDtB_RowBroRef | CDtB_RowOp and zero the row frCols param if no toCols
          $dt = CDtB_RowBroRef | CDtB_RowOp;
          break;
        case 29:         # 29   * *   * Add any missing frCols as CDtB_RowBroRef and the d col as CDtB_RowBroRefDescr and zero the row frCols param.
        case 35: $d = 1; # 35     *   * Add the frCols as CDtB_RowBroRef and the d col as CDtB_RowBroRefDescr and zero the row frCols param
        case  9:         #  9 * * *   * Add any missing frCols as CDtB_RowBroRef and zero the row frCols param.
        case 15: $f = 1; # 15 *   *   * Add the frCols as CDtB_RowBroRef and zero the row frCols param
          $dt = CDtB_RowBroRef;
          break;
      }
      # Add the auto col statements
      if ($d) { # add the d col as CDtB_RowBroRefDescr
        $rowA['ColStatementsA'][$dCol] = "$dCol," . CDtB_RowBroRefDescr;
        ++$rowBroRefsUses;
      }
      # Mapping needed?
      if ($dt & CDtB_RowBroRef) {
        ++$rowBroRefsUses;
        if (is_string($rowBroRef)) {
          $broRefA = explode(',', $rowBroRef);
          if (($mnDiMeId = (int)$broRefA[1]) == DiMeId_CoSec || $mnDiMeId == DiMeId_Accountants)
            $dt |= CDtB_Mapping;
        }
      }
      # Set the money col options for <dr|cr> <ul|dul> <aul|adul> to the row settings
      $options = ($rowOptions & OptB_AnySign) | ($rowOptions & OptB_AnyUl);
      if ($m) { # Add all money cols
        foreach ($colTypeA as $col => $colTypeN)
          if ($colTypeN == CT_Money)
            $rowA['ColStatementsA'][$col] = AddParamStr("$col%s", [$dt, 0, $options]); # 0 for dat
      }else if ($f) {
        foreach (CsListToIntA($frCols) as $col)
          if (!$colUsedA[$col]) # re cases 9 and 29 to Add any missing frCols...
            $rowA['ColStatementsA'][$col] = AddParamStr("$col%s", [$dt, 0, $options]);
      }else
        die("Die - invalid m $m and f $f in AddTableRow()");
      # zap frCols if applicable
      switch ($colsToAddCase) {
                 # Ca col R R f
                 # se d m B O C Action re Adding Cols or Comment
        case  5: #  5 * *   * * Add the frCols as CDtB_RowOp and zero the row frCols param if no toCols. (Addition of m cols in frCols is prevented by col add loop code.)
        case 11: # 11 *     * * Add the frCols as CDtB_RowOp and zero the row frCols param if no toCols
        case 25: # 25   *   * * Add the frCols as CDtB_RowOp and zero the row frCols param if no toCols. (Addition of m cols in frCols is prevented by col add loop code.)
        case 31: # 31       * * Add the frCols as CDtB_RowOp and zero the row frCols param if no toCols
        case 28: # 28   * * * * Add the frCols as CDtB_RowBroRef | CDtB_RowOp and the d col as CDtB_RowBroRefDescr and zero the row frCols param if no toCols. (Addition of m cols in frCols is prevented by col add loop code.)
        case 34: # 34     * * * Add the frCols as CDtB_RowBroRef | CDtB_RowOp and the d col as CDtB_RowBroRefDescr and zero the row frCols param if no toCols
        case  8: #  8 * * * * * Add the frCols as CDtB_RowBroRef | CDtB_RowOp and zero the row frCols param if no toCols. (Addition of m cols in frCols is prevented by col add loop code.)
        case 14: # 14 *   * * * Add the frCols as CDtB_RowBroRef | CDtB_RowOp and zero the row frCols param if no toCols
        case 29: # 29   * *   * Add any missing frCols as CDtB_RowBroRef and the d col as CDtB_RowBroRefDescr and zero the row frCols param.
        case 35: # 35     *   * Add the frCols as CDtB_RowBroRef and the d col as CDtB_RowBroRefDescr and zero the row frCols param
        case  9: #  9 * * *   * Add any missing frCols as CDtB_RowBroRef and zero the row frCols param.
        case 15: # 15 *   *   * Add the frCols as CDtB_RowBroRef and zero the row frCols param
          if (!$toCols) $frCols = 0; # toCols test ok in all cases
          break;
      }
    }
    if ($rowBroRef && !$rowBroRefsUses)
      return ATRError('The row b:BroRef attribute on this format line has not been used by any column and so should be removed');
  }else
    # No defined cols. Just check for at least one col statement
    if (!count($rowA['ColStatementsA'])) return ATRError('There is a Row statement but no Col statements on this format line and nothing for the compiler to use to generate automatic col statements');

  ksort($rowA['ColStatementsA']);
  $frCols = $frCols ? "'$frCols'" : 0;
  $toCols = $toCols ? "'$toCols'" : 0;
  $rowOptions &= ~(OptB_AnySign | OptB_AnyUl); # unset sign and ul options for the Row() call as these are transferred to the Col() calls.
 #$rowStatement = AddParamStr('Row(%s);', [PBroRef($rowBroRef), $rowOptions, $rowNum, $frCols, $toCols, $mcss, $rcss, $altRowNum], true); # true = no leading comma
 #AddP1Line($rowStatement . implode('',$rowA['ColStatementsA']));
  $rowParamStr = AddParamStr('%s', [PBroRef($rowBroRef), $rowOptions, $rowNum, $frCols, $toCols, $mcss, $rcss, $altRowNum], true); # true = no leading comma
  $rowStatement = $rowParamStr ? "Row([$rowParamStr]" : 'Row(0';
  $ColBroLoop = '';
  foreach ($rowA['ColStatementsA'] as $col => $colSt) {
    # Row(0,[1,0,$DirectorsS.":",0,'b']);ColBroLoop(2,'290,426',40,1,0);
    if ($col == 999) # ColBroLoop
      $ColBroLoop = $colSt;
    else
      $rowStatement .= ",[$colSt]";
  }
  AddP1Line($rowStatement . ');' . $ColBroLoop);
  TableRowInit();
}

# AddTableRowError
function ATRError($errS) {
  TableRowInit();
  return RGError($errS);
}

# [span {c:css} strExpr]
function Sstatements() {
  global $TableA;
  if (MatchStep('span')) {
    if ($TableA) return RGError('In a table where span statements cannot be used stand alone. They may be used as part of a col expression');
    if (($span = Span()) === false) return false;
    return "P($span);"; # wrap stand alone [span ...] in a <p> tag
  }
  return RGError('Statement not recognised');
}

function Span() {
  if (($css = CssAtribute()) === false) return false; # error in css attribute
  # Got css or it was not specified. Now expect expr
  if (($retA = BroRefOrExpr(false, true)) === false) return false; # false = Bro loop not allowed, true = Bro or cell Data With Tag
  # 0: type
  # 1: BroRef, or Expr return array according to type
  # 2: optional $yExpr for BRT_BroRef type
  $dat = $retA[1];
  switch ($retA[0]) {
    case BRT_Descr: # descr BroRef i.e. broRef for a Descr($broRef) call
      $dat = "Descr($dat)";
      break;
    case BRT_BroRef: # BroRef with optional year expr
      $dat = 'DataWithTag(' . PBroRef($dat) . "$retA[2])";
      break;
    case BRT_Expr: # Expr return array
    if ($dat[0] != ExT_String) return RGError('Expression is not a Bro reference, or a string type as expected for a [span... statement');
    $dat = $dat[1];
    break;
  }
  return "SpanStr($dat$css)";
}

# [table {cols:<t|d|n|0-3|c|p>...} {center} {c:css} {noOutput}]
# [toc {c:css} {c:css} {xrefNameX}]
function Tstatements() {
  global $C, $XrefUsesA, $TableA;
  if (MatchStep('table')) {
    if ($TableA) return RGError('Already in a table and tables cannot (currently anyway) be nested');
    $cols = $pcols = $css = $center = $noOutput = 0;
    while (($i = MatchOneStep(['cols:', 'center', 'c:', 'noOutput'])) !== false) {
      switch ($i) {
        case 0: # cols: expect a cols type list
          if (($cols=GetToken(' ]')) === false) return RGError('cols: attribute but no cols type list found');
          if (($len = strlen($cols))> 29)       return RGError('cols: attribute list longer than 29 characters - can only have up to 29 columns');
          if (strlen(str_replace(['t','d','n','0','1','2','3','c','p'], '', $cols))) return RGError('cols: attribute list should consist of just t, d, n, 0, 1, 2, 3, c, and p characters with no spaces or separators');
          $pcols = "'$cols'";
          break;
        case 1: # center
          $center = 1;
          break;
        case 2: # c:
          if (($css = CssAtribute2()) === false) return false;
          break;
        case 3: # noOutput
          $noOutput = 1;
          break;
      }
    }
    if ($noOutput) {
      if ($center) return RGError('The center attribute achieves nothing for a noOutput table so should be removed');
      if ($css)    return RGError('The css attribute achieves nothing for a noOutput table so should be removed');
    }
    TableInit($cols);
    return IncrementLevel(LT_Table, AddParamStr('TableStart(%s);', [$pcols, $center, $css, $noOutput], true)); # true = no leading comma
  }
  if (MatchStep('toc')) {
    if ($C == ']') {
      # [toc] = ToC start
      if ($TableA) return RGError('Already in a table and tables of contents cannot (currently anyway) be nested within a table');
      TableInit(); # to prevent a table being started and to allow [row and [col statements tho they are not normally expected.
      return IncrementLevel(LT_Toc, 'TocStart();');
    }
    # [toc {c:css} {c:css} {xrefNameX}]
    if (!$TableA) return RGError('Table of Contents has not been started via a [toc] statement or else a toc has been ended prematurely.');
    if (($css1 = CssAtribute()) === false) return false;
    if ($css1) {
      if (($css2 = CssAtribute()) === false) return false;
    }else
      $css2='';
    if (($name = GetToken(']')) === false) return RGError('This toc statement which is more than just [toc] is missing a name');
    if (($symbolA = STlookup($name)) === false)
      # name not in ST so add it
      if (AddSymbolToST($name, ST_Xref) === false) return false;
    # Add xref to list of uses
    $XrefUsesA[] = [$name, FormatAndLine()]; # [XUI_Name | XUI_FormatAndLine]
    return "Toc('$name'$css1$css2);";
  }
  return RGError('Statement not recognised');
}

# [xref <target|text|page|both|link> {c:css} nameX]
function Xstatements() {
  global $TableA;
  if ($TableA) return RGError('In a table where xref statements cannot be used stand alone. They may be used as part of a col expression');
  if (($xCodeA = ParseXrefStatement()) === false) return false; # returns [type | code] where type is XrefB_Target etc
  if (!$xCodeA[0]) # Target
    return $xCodeA[1] . ';';
  # wrap stand alone [xref ...] other than target ones in a <p> tag
  return "P({$xCodeA[1]});";
}

# Returns [type | code wo trailing ;]
function ParseXrefStatement() {
  global $C, $P, $SymbolTableA, $XrefUsesA;
  if (MatchStep('xref')) {
    $p = $P;
    # expect one of target|text|page|both|link -> XrefB_Target, XrefB_Text, XrefB_Page, XrefB_Both, XrefB_Link
    if (($a = GetTokenAndMatch(' ]', ['target', 'text', 'page', 'both', 'link'])) === false)
      return RGError('One of target|text|page|both|link is required for the first xref attribute but none of these was found', $p);
    # refNameX
    if (($css = CssAtribute()) === false) return false;
    $p = $P;
    if (($name = GetToken(']')) === false) return RGError('Name as second xref attribute not found', $p);
    if (($symbolA = STlookup($name)) === false) {
      # not in ST so add it
      if (AddSymbolToST($name, ST_Xref, ExT_String) === false) return false;
      if (!$a)
        $SymbolTableA[$name][STI_Uses] = 1; # set Uses if target
    }else{
      # in ST so in target case check for duplicate target use
      if (!$a) { # target
        if ($symbolA[STI_Uses])
          return RGError("The xref $name has already been used as used as a target in format {$symbolA[STI_FormatAndLine]} so cannot be used again",$p);
        # Set STI_Uses and update format info to the target use
        $SymbolTableA[$name][STI_Uses]          = 1;
        $SymbolTableA[$name][STI_FormatAndLine] = FormatAndLine();
      }
    }
    if ($a)
      # Add xref to list of uses if not a target case
      $XrefUsesA[] = [$name, FormatAndLine()]; # [XUI_Name | XUI_FormatAndLine]
    # if ($a == XrefB_Link) $a |= XrefB_Text; Bits not working in RegRunEnd.inc
    return [$a, "XrefSt('$name',$a$css)"]; # [type | code wo trailing ;]
  }
  return RGError('Statement not recognised');
}

# [zone{s} {zzz1{,zzz2...}}]
function Zstatements() {
  global $C, $P, $ZonesA, $ZoneRefsA, $ZonesNowA, $P;
  if (MatchStep('zone')) {
    if ($C == 's') Step(); # step over the s if present
    if ($C == ']') {
      # empty list so just cancel
      $ZonesNowA = 0;
      return "ZoneSign(0);";
    }
    $ZonesNowA = [];
    $signN = 0;
    $p = $P;
    while ($tok = GetToken(',]')) {
      if (isset($ZoneRefsA[$tok])) {
        $ZonesNowA[] = $id = $ZoneRefsA[$tok]; # Zones.Id
        $zSignN      = $ZonesA[$id][ZI_SignN]; # Zones.SignN
        if (!$signN && $zSignN)
          $signN = $zSignN;
      }else
        return RGError("Unknown Zone <i>$tok</i> specified", $p);
      if ($C == ',') Step();
      $p = $P;
    }
    return "ZoneSign($signN);";
  }
  return RGError('Statement not recognised');
}

# array ColList()
# ---------------
# For [table cols:dn0101tn01 center]
# Process cols list in form: <t{#}|d{#}|n{#}|<y0{#}-y3{#}>|c{#}|p{#}> with empty elements meaning prev col # + 1
# [col t][col t1][col t2,n,y02,y03,y1,y12,y13,n2]
# ->   1      1       7  2 5   9   4  6   10  8
# Returns an array of the column #s
function ColList() {
  global $P, $TableA;
  $p = $P;
  if (($colsList=GetToken(' ]')) === false) return RGError('cols list expected but not found', $p);
  $colsA = explode(',', $colsList);
  if (substr($colsList, -1) == ',')  # remove empty case if last one e.g. for list like like y0,,
    array_pop($colsA);
  $colsListA = [];
  $tableCols = $TableA['Cols'];
  $nCols     = $TableA['NCols'] ? : 29;
  $prevCol   = $TableA['RowA']['PrevCol'];
  foreach ($colsA as $col) {
    if (ctype_digit($col)) {
      if ($col < 1 || $col > $nCols) {
        if ($tableCols)
          return RGError("cols list member $col is out of the allowable range of 1 to $nCols for this table with a cols:$tableCols attribute", $p);
        else
          return RGError("cols list member $col is out of the allowable range of 1 to 29", $p);
      }
      $coli = $col;
    }else{
      # expect <t{#}|d{#}|n{#}|<y0{#}-y3{#}>|c{#}|p{#}> or empty
      if ($col > '') {
        if (!$tableCols) return RGError('The non numeric form of col reference in a cols list can only be used when the [table statement has a cols: attribute, but the current table does not', $p);
        $n = 1; # index for #
        $c = $col[0];
        switch ($c) {
          case 'y':
            if (!isset($col[1])) return RGError("No year character after the y for cols list member $col", $p);
            $n = 2;
            $c = $col[1]; # 0, 1, 2, 3 expected
            # fall thru
          case 't':
          case 'd':
          case 'n':
          case 'c':
          case 'p':
            $coli = strpos($tableCols, $c); # $c = t, d, n, 0, 1, 2, 3, c, p expected
            break;
          default:  $coli = false;
        }
        if ($coli === false)
          return RGError("No |$c| col for cols list member $col in the table cols list of$tableCols", $p);
        else{
          if (isset($col[$n])) {
            # Got a # chr
            $r = $col[$n++]; # n now pts to char after the # which should not be set
            if (ctype_digit($r) && $r >= 1) {
              # Got a # digit
              $rn = (int)$r;
              while ($rn > 1 && $coli !== false) { # nothing to do for $rn == 1
                $coli = strpos($tableCols, $c, $coli+1);
                --$rn;
              }
              if ($coli === false) return RGError("No |$r| col of type |$c| found for cols list member $col in the table cols list of$tableCols", $p);
            }else{
              # not a # digit
              ++$n;
              return RGError("Character number $n (a '$r') cols list member $col not a digit >= 1 as expected for col in form &lt;t{#}|d{#}|n{#}|&lt;y0{#}-y3{#}>|c{#}|p{#}>", $p);
            }
            if (isset($col[$n])) {
              $c = $col[$n++];
              return RGError("Unexpected character number $n (a '$c') in cols list member $col", $p);
            }
          }
        }
      }else { # empty list entry
        $coli = $prevCol + 1;
        if ($coli > $nCols) return RGError("The cols list is too long, specifing more than the $nCols columns defined for this table with a cols:$tableCols attribute", $p);
      }
    }
    if (in_array($coli, $colsListA)) return RGError("This cols list contains a duplicate entry for col $col, a '{$tableCols[$col]}' column in the table cols list of$tableCols", $p);
    $colsListA[] = $prevCol = $coli;
    $p += strlen($col) + 1;
  }
  sort($colsListA);
  return $colsListA;
}

# array ColListFrTo()
# -------------------
# As per ColList() with the addition of an optional to column {y#{#{->#}} after a money fr column
# Applicable only to the cols: attribute of row subtotal and total statements
# Does not get called for a table without money columns
# [table cols:tn0011 center]
# [row subtotal aul cols:y01->y02,y11->y12] /- same result
# [row subtotal aul cols:y01->2,y11->2]     |
# [row subtotal aul cols:y11->y12,y01->y02] |
# [row subtotal aul cols:y11->2,y01->2]     |
# [row subtotal aul cols:y0->y02,y1->y12]   |
# [row subtotal aul cols:3->4,5->6]         |
# Returns false or [source list, [fr cols] {,[to cols]}]
function ColListFrTo() {
  global $P, $TableA;
  $p = $P;
  if (($colsList=GetToken(' ]')) === false) return RGError('cols list expected but not found', $p);
  $colsA = explode(',', $colsList);
  if (substr($colsList, -1) == ',')  # remove empty case if last one e.g. for list like like y0,,
    array_pop($colsA);
  $colsFrA   =
  $colsToA   = [];
  $tableCols =  $TableA['Cols'];
  $colTypeA  = &$TableA['ColTypeA'];
  $nCols     =  $TableA['NCols'] ? : 29;
  $prevCol   =  $TableA['RowA']['PrevCol'];
  $toColsB   = false;
  foreach ($colsA as $li =>$fullCol) { #li = index in list plus in $colsFrA and $colsToA
    $colFrToA = explode('->', $fullCol);
    if (count($colFrToA) > 2) return RGError("cols list member $ftCol contains more than one ->", $p);
    foreach ($colFrToA as $i => $col) { # i = 0 or 1 from Fr and To
      if (!$i) { # fr col
        $frNumericB = true;
        if (ctype_digit($col)) {
          if ($col < 1 || $col > $nCols)
            return RGError("cols list member $col is out of the allowable range of 1 to $nCols for this table with a cols:$tableCols attribute", $p);
          $frCol = $col;
        }else{
          # expect <t{#}|d{#}|n{#}|<y0{#}-y3{#}>|c{#}|p{#}> or empty
          if ($col > '') {
            $frNumericB = false;
            $n = 1; # index for #
            $c = $col[0];
            switch ($c) {
              case 't':
              case 'd':
              case 'n':
              case 'c':
              case 'p':
                return RGError("Col $col is a '$c' column not a Money column as required for a row cols: list", $p);
              case 'y':
                if (!isset($col[1])) return RGError("No year character after the y for cols list member $col", $p);
                $n = 2;
                $y = $col[1]; # 0, 1, 2, 3 expected
                if (($coli = strpos($tableCols, $y)) === false) return RGError("No |$y| col for cols list member $col in the table cols list of$tableCols", $p);
                if (isset($col[$n])) {
                  # Got a # chr
                  $r = $col[$n++]; # n now pts to char after the # which should not be set
                  if (ctype_digit($r) && $r >= 1) {
                    # Got a # digit
                    $rn = (int)$r;
                    while ($rn > 1 && $coli !== false) { # nothing to do for $rn == 1
                    $coli = strpos($tableCols, $y, $coli+1);
                      --$rn;
                    }
                    if ($coli === false) return RGError("No # $r col for y$y found for cols list member $col in the table cols list of$tableCols", $p);
                  }else{
                    # not a # digit
                    ++$n;
                    return RGError("Character number $n (a '$r') cols list member $col not a digit >= 1 as expected for col in form y0{#}-y3{#}", $p);
                  }
                  if (isset($col[$n])) {
                    $c = $col[$n++];
                    return RGError("Unexpected character number $n (a '$c') in cols list member $col", $p);
                  }
                }
                break;
              default:
                return RGError("Col $col is an unknown type - expecting a y", $p);
            }
            $frCol = $coli;
          }else{ # empty list entry
            $frCol = $prevCol + 1;
            if ($frCol > $nCols) return RGError("The cols list is too long, specifing more than the $nCols columns defined for this table with a cols:$tableCols attribute", $p);
          }
        }
        # fr
        if ($colTypeA[$frCol] != CT_Money) return RGError("Col $col is not a Money column as required for a row cols: list", $p);
        if (in_array($frCol, $colsFrA)) return RGError("This cols list contains a duplicate entry for col $col", $p);
        $colsFrA[$li] = $colsToA[$li] = $frCol; # both with $colsToA[$li] being overwritten above below if there is a -> col
      }else{
        # to col
        $msgCol = "->$col in $fullCol";
        if ($frNumericB) {
          # fr col was a col # so allow 1 - nCols here as a col #
          if (!ctype_digit($col))        return RGError("As the from col $frCol in $msgCol was a col #, not a y type col referenece, a col # is expected for the -> (to) col but $col is not that", $p);
          if ($col < 1 || $col > $nCols) return RGError("The -> (to) col # of $col in $msgCol is out of the allowable range of 1 to $nCols for this table with a cols:$tableCols attribute", $p);
          $toCol = $col;
          if ($tableCols[$frCol] != $tableCols[$toCol]) return RGError("The from and -> columns are for different years ({$tableCols[$frCol]} and {$tableCols[$toCol]}) which is not allowed", $p);
          if (in_array($toCol, $colsToA)) return RGError("This cols list contains a duplicate -> entry for col $col", $p);
      }else{
          # fr col was y#{#} form so only a single digit is legal here
          $y = $tableCols[$frCol]; # year digit of the fr col
          if (!ctype_digit($col) || strlen($col) > 1) return RGError("A single digit is expected after the -> in a y# type column reference to define the # of the -> (to) y$y column", $p);
          # Got a single digit
          $rn = $r = (int)$col;
          $coli = strpos($tableCols, $y);
          while ($rn > 1 && $coli !== false) { # nothing to do for $rn == 1
            $coli = strpos($tableCols, $y, $coli+1);
            --$rn;
          }
          if ($coli === false) return RGError("No # $r col for y$y found for $msgCol in the table cols list of$tableCols", $p);
          $toCol = $coli;
          if (in_array($toCol, $colsToA)) return RGError("This cols list contains a duplicate -> entry for col y$y$col", $p);
        }
        if ($toCol == $frCol)  return RGError("The -> col is the same as the from col. If this is what you want, remove the '->$col'", $p);
        $colsToA[$li] = $toCol;
        $toColsB = true;
      }
    }
    $p += strlen($fullCol) + 1;
  }
  if ($toColsB) {
    # Have $colsFrA  $colsToA. Want to sort $colsFrA while keeping $colsToA parallel.
    $sortedFrA = $colsFrA;
    sort($sortedFrA);
    $parallelToA = [];
    foreach ($sortedFrA as $col)
      $parallelToA[] = $colsToA[array_search($col, $colsFrA)];
    return [$colsList, $sortedFrA, $parallelToA]; # [source list, [fr cols], [to cols]]
  }
  # Just fr cols so return only $colsFrA
  sort($colsFrA);
  return [$colsList, $colsFrA]; # [source list, [fr cols]]
}

# string CssAtribute()
# --------------------
# Get optional c:css css csv class attribute if present
# Returns:
# '' if not found
# space separated classes list if found and ok, as |,'list'| for use as a fn parameter or |'list'| if false is passed for $leadingCommaB
# false if c: found but no no classes list
# false if a class included is not known <=== djh?? To be added
function CssAtribute($leadingCommaB = true) {
  $css = '';
  if (MatchStep('c:')) {
    # Got c: so now expect a comma separated class list, no quotes
    /*while (preg_match('/[a-zA-Z_0-9,]/',$C)){ # a-z A-Z _ 9-0 ,
      $css .= $C;
      Step();
    }*/
    if (($css=GetToken(' ]')) === false) return RGError('c: attribute but no css class list found');
    $css = ($leadingCommaB ? ',\'' : SQ) . trim(str_replace(',', ' ', $css)) . SQ; # commas to spaces for css use
    # djh?? Add check that classes are valid
  }
  return $css;
}
# string CssAtribute2()
# --------------------
# Similar to CssAtribute() but called after c: has been found. No leading comma
function CssAtribute2() {
  if (($css=GetToken(' ]')) === false) return RGError('c: attribute but no css class list found');
  return SQ . trim(str_replace(',', ' ', $css)) . SQ; # commas to spaces for css use
}

# array Expr($broOrCellDatWithTagB=false)
# ---------------------------------------
# Parses an expression terminating on ] or then where the expression can be of ExT_String or ExT_Numeric
# $broOrCellDatWithTagB set to true for Bro or Col References to use the ...WithTag() call version - used for h, p, span statements
# Optionally passed a set of terminating characters, default ]
# Also terminates on 'then' for if statement use
# Returns false on error or [type | code] where type can be ExT_String or ExT_Numeric
function Expr($broOrCellDatWithTagB=false) {
  global $Line, $C, $P, $InFuncB, $RowNamesA, $LastNoErrorErrorS;
  $expr = $yExpr = ''; # with $g used for expr segment
  $eType = $prevType = $lbs = $rbs = 0;
  $pStart = $P;
  while ($C !== false) {
    if ($C == ']' || MatchStep('then')) { # at the end
      if ($lbs != $rbs)
        return RGError("Brackets missmatch. There are $lbs (s but $rbs )s in the expression", $pStart);
      # Remove some unnecessary string concatenation
      # H(2,"Alternative text Profit and Loss Test".'<br/>'."for the Period Ended ".DateStrWithTag(5731),'c');
      # H(2,"Contents of the ".$DraftS.$HeadingsA['AccountsShortH'].'<br/>'."for the Period Ended ".DateStrWithTag(5731),'c');
      # H(2,$HeadingsA['CompanyRegistrationNumberH'].':'.'<br/>'.DataWithTag(5634)." (".$IncorporationCountryS.")",'c');
      # ".'<br/>'." => <br/>
      #  .'<br/>'." => ."<br/>
      # '.'         => ''
      # "."         => ''
      $expr = str_replace(['".\'<br/>\'."', '.\'<br/>\'."', '\'.\'', '"."'], ['<br/>', '."<br/>', ''], $expr);
      return [$eType, $expr];
    }
    $p = $P;
    if ($C == DQ or $C == SQ) {
      # expect string
      if (($g = GetString()) === false) return false; # closing quote not found
      $gType = ExT_String; # got string
    }else if ($C == '[') { # [nl], [date ...], [page#, [span, [xref are legal
      if (MatchStep('[nl]')) {
      /*if ($prevType === ExT_String && (($prevEndCh=substr($expr,-1)) === SQ || $prevEndCh === DQ)) {
          $expr = substr($expr, 0, -1) . '<br/>' . $prevEndCh;
          continue;
        } */
        $g = '\'<br/>\'';
        $gType = ExT_String;
      }else if (substr($Line, $P, 6) == '[date ') {
        # looks like start of a [date ...] statement
        Step(); # over the [
        if (($dCodeA = ParseDateStatement()) === false) return false; # returns [type | code] where type can be ExT_String or ExT_Integer, or false
        $gType = $dCodeA[0];
        $g     = $dCodeA[1];
        Step(); # over the closing ]
      }else if (MatchStep('[page#')) { # [page# {c:css}]
        if (($css = CssAtribute(false)) === false) return false; # error in css attribute. false in CssAtribute() call to avoid leading , to css
        $g = "PageStr($css)";
        $gType = ExT_String;
        Step(); # over the closing ]
      }else if (MatchStep('[span')) {
        if (($g = Span()) === false) return false;
        $gType = ExT_String;
        Step(); # over the closing ]
      }else if (substr($Line, $P, 5) == '[xref') {
        # looks like start of an [xref ...] statement
        Step(); # over the [
        if (($xCodeA = ParseXrefStatement()) === false) return false; # returns [type | code] where type is XrefB_Target etc
        if (!$xCodeA[0]) RGError('Target Xref cannot be used within an expression', $p);
        $g = $xCodeA[1];
        $gType = ExT_String;
        Step(); # over the closing ]
      }else
        return RGError('Invalid [ statement in expression. [nl], [date ...], [page# ...], [span ...], [xref ...] are valid', $p);
    }else{
      # Expr: Not a string or [. y:# or descr or Token?
      if (MatchStep('y:')) {
        # y: expect a year intExpr
        if (($yExpr = IntExpr(' ', true)) === false) return false; # with leading comma
        continue;
      }
      if (MatchStep('descr')) {
        # expect a BroRef
        if (($tok = GetToken(' "\'[](),')) === false) return RGError("Bro reference expected after descr but not found", $p);
        if (($broRef = BroRef($tok)) === false) return false;
        # if (is_string($broRef)) return RGError("$tok is a Bro reference with dimensions but after descr a BroName without any dimensions is expected", $p);
        $g = "Descr($broRef)";
        $gType = ExT_String;
      }else
      if (($tok = GetToken(' "\'[]()+-*/%!<>=&|')) !== false) { # djh?? 25.12.12 removed , from end re change to use of , as segment sep in BroRefs from : But is this ok re other things?
        # got a tok.
        # Expect
        # and, or, number, constant, heading, RG built in variable, format variable, Bro, function or para call
        # ? Allow for built in PHP functions? And PHP variables?
        if ($tok == 'and' || $tok == 'or') {
          $expr .= " $tok "; # code works wo the spaces but it doesn't look nice
          $prevType = '&';
          continue;
        }
        # number, constant, heading, RG built in variable, Bro, RowName{, format variable, function or para call}
        $c = $tok[0];
        if (ctype_digit($c) || $c=='.') {
          # digit or '.' so expect a number
          $g = $tok; # in anticipation
          if (ctype_digit($tok))
            $gType = ExT_Integer;
          else if (is_numeric($tok))
            $gType = ExT_Numeric;
          else {
            if ($c == '.')
              return RGError("$tok starts with a '.' but is not a number as expected", $p);
            return RGError("$tok starts with a digit but is not a number as expected", $p);
          }
          $gType = ExT_Numeric;
        }else
        # Expr: Not a number so try a BroRef
        if (($broRef = BroRef($tok, true)) !== false) { # true = no BroRef error
          BroRefToBroIdAndParamForm($broRef, $broId, $pBroRef);
          $g     = $broOrCellDatWithTagB ? "DataWithTag($pBroRef$yExpr)" : "Data($pBroRef$yExpr)";
          $gType = BroExprType($broId);
        }else
        # Expr: Not a BroRef so try a CellRef
        if (($cellRefA = CellRef($tok)) !== false) { # Returns false or [RowNum, Col, Expr type, 0|1 re colSum] with RowNum = 0 for this
          list($rowNum, $col, $gType, $colSum) = $cellRefA;
          $colSum = $colSum ? ',1' : '';
          if ($broOrCellDatWithTagB) {
            if (!$rowNum) return RGError("$tok is a cell reference using 'this' but 'this' can only be used with a col statement in a table", $p);
            $g = "CellDatWithTag($rowNum,$col$colSum)";
          }else
            # col expr
            $g = $rowNum ? "CellDat($rowNum,$col$colSum)" : "CellThis(\$rowA,$col$colSum)";
        }else
        # Expr: not a CellRef so try the symbol table
        if (($symbolA = STlookup($tok)) !== false) { # match
          switch ($symbolA[STI_SType]) {
            case ST_Constant: $g = $symbolA[STI_Val]; break; # Or could use name i.e. $tok
            case ST_Heading:  $g = '$' . ($InFuncB ? "GLOBALS['HeadingsA']['$tok']" : "HeadingsA['$tok']"); break;
            case ST_Function: $g = "$tok()"; break; # djh?? + parameters.... djh?? Add type
            case ST_Para:     $g = "$tok()"; break;
            case ST_Xref:     $g = "Xref('$tok',ExT_String)"; break; # using the ExT_String value
            case ST_Var:      $g = '$' . ($InFuncB ? "GLOBALS['$tok']" : $tok); break;
            default: return RGError("$tok is a " . SymbolTypeToStr($symbolA[STI_SType]) . " which cannot be used in an expression", $p);
          }
          $gType = $symbolA[STI_EType];
        }else if (defined($tok)) {
          # not in the symbol table but is a PHP constant
          $g     = constant($tok);
          $gType = VarType($g);
        }else
          # nothing so rather than call BroRef() again with errors enabled just give a general no go error
          return RGError("Expression expected but found <i>$tok</i> which is not a valid expression as $LastNoErrorErrorS", $p);
      }else{
        # Expr: not a tok, expect ( ) + - * / % ! < > = & | but not ] or , as they should be in $termChs when valid
        if (InStr($C, '()+-*/%!<>=&|')) {
          # put straight in
          $expr .= $C;
          $prevType = $C;
          Step();
          switch ($prevType) { # C before the Step
            case '(': $lbs++; break;
            case ')': $rbs++; break;
            case '!': # could be ! or != or !==
              for ($i=0; $i<2 && $C == '='; $i++) {
                $expr .= $C;
                Step();
              }
              break;
            case '<': # could be < <> or <=
              if ($C == '>' || $C == '=') {
                $expr .= $C;
                Step();
              }
              break;
            case '>': # could be > or >=
              if ($C == '=') {
                $expr .= $C;
                Step();
              }
              break;
            case '=': # Expect == or ===
              if ($C != '=')
                return RGError('Assignment operator = within expression. Equals comparison operator == intended?', $p);
              for ($i=0; $i<2 && $C == '='; $i++) {
                $expr .= $C;
                Step();
              }
              break;
            case '&': # Expect &&
              if ($C != '&')
                return RGError('Unexpected single &. And operator && intended?', $p);
              $expr .= $C;
              Step();
              break;
            case '|': # Expect ||
              if ($C != '|')
                return RGError('Unexpected single |. Or operator || intended?', $p);
              $expr .= $C;
              Step();
              break;
          }
          continue;
        }
        return RGError("Unexpected character $C in expression", $p);
      }
    }
    # Expr: Got expr segment in $g with expression type in $eType: 0 ExT_String ExT_Numeric
    # this segment type in $gType: ExT_String ExT_Integer+
    # and previous segment type in $prevType: 0 ExT_String ExT_Numeric ( ) + - * / % ! < > = & |
    # echo "expr=$expr, eType=$eType, gType=$gType, prevType=$prevType<br>";
    if ($gType == ExT_String) {
      if ($eType && $eType != ExT_String)
        return RGError("Invalid mixture of types - string $g found in " . ExprTypeToStr($eType) . ' expression', $p);
      $eType = ExT_String;
      if ($prevType == ExT_String || $prevType == ExT_Numeric || $prevType === ')') # ExT_String ExT_Numeric )
        $expr .= '.' . $g; # concatenate
      else if (!$prevType || $prevType === '(') # 0 (
        $expr .= $g; # straight in
      else # + - * / % ! < > = & |
        return RGError("Expression syntax error: a $prevType operator is followed by a string",$p);
      $prevType = ExT_String;
    }else{ # expect ExT_Integer+
      if (!$gType)
        return RGError("No type for segement $g", $p);
      # ExT_Numeric with $prevType: 0 ExT_String ExT_Numeric ( ) + - * / % ! < > = & |
      if (!$eType)
        $eType = ExT_Numeric;
      if ($prevType == ExT_Numeric || $prevType === ')') # ExT_Numeric )
        return RGError('An operator is missing before the numerical expression', $p);
      if ($prevType == ExT_String) # ExT_String
        $expr .= '.' . $g; # concatenate
      else # 0 ( + - * / % ! < > = & |
        $expr .= $g; # straight in
      $prevType = ExT_Numeric;
    }
    #echo "$expr<br>";
  }
  # return false;
  return RGError('Expression not terminated by ] or then', $pStart);
}

# array BroRefOrExpr()
# --------------------
# Always last in a statement i.e. term ch is ]
# returns an array of the result
# 0: type
#    BRT_Descr    descr BroRef i.e. broRef for a Descr($broRef) call
#    BRT_BroRef   BroRef with optional year expr
#    BRT_Expr     Expr
#    BRT_BroLoop  BroLoop
# 1: BroRef, BroLoopParams array, or Expr return array according to type
# 2: optional $yExpr for BRT_BroRef type
function BroRefOrExpr($broLoopRefAllowedB = true, $broOrCellDatWithTagB=false) {
  global $Line, $C, $P;
  $yExpr = '';
  $descr = 0;
  $p = $P;
  # Try for a BroRef which could be preceded by y:# or descr
  if (MatchStep('y:')) {
    # y: expect a year intExpr
    if (($yExpr = IntExpr(' ')) === false) return false; # PBack($pStart);
  }
  if (MatchStep('descr')) $descr = 1;
  if (($tok = GetToken(']')) !== false) { # djh?? [ added 25.12.12 Correct?
    # Have a tok to end of statement, or wrongly to closing ] of [nl] in a string expr
    if (($broRef = BroRef($tok, true)) !== false) { # no errors BroRef() call
      # got a BroRef
      if ($descr) {
        if ($yExpr)             return RGError("y:# and descr attributes should not be used together", $p);
        return [BRT_Descr, $broRef];
      }
      return [BRT_BroRef, $broRef, $yExpr]; # BroRef with optional year expr
    }
    # Is it a BroLoopRef?
    if (strpos($tok, '@') !== false && ($retA = BroLoopRef($tok)) !== false) {
      # Bro Loop
      if (!$broLoopRefAllowedB) return RGError('A Bro loop is only allowed in [p and [col statements', $p);
      if ($yExpr)               return RGError('A y:# attribute is not valid with a Bro loop reference', $p);
      if ($descr)               return RGError('A descr attribute is not valid with a Bro loop reference', $p);
      return [BRT_BroLoop, $retA];
    }
  }
  PBack($p);
  # Not a valid BroRef so try for Expr which will error on an invalid BroRef
  if (($retA = Expr($broOrCellDatWithTagB)) === false) return false;
  # [expr type, code]
  return [BRT_Expr, $retA];
  return RGError('No BroRef or Expr found', $p); # don't expect to see this. Fn is only called when there is something so should have
}                                                # found a BroRef or Expr or an error


# string IntExpr($termChs=']')
# ----------------------------
# Parses an integer expression
# Optionally passed a set of terminating characters, default ] with ' ' allowed for space terminated attribute expressions
# Returns the code for the expression, optioanlly with a leading comma, or false on an error.
# [lines intExpr]
function IntExpr($termChs=']', $leadingCommaB=false) {
  global $Line, $C, $P, $InFuncB;
  if (InStr(' ', $termChs)) {
    if (!$termChs = str_replace(' ', '', $termChs))
      $termChs = ']';
    $terminateOnSpaceB = true;
  }else
    $terminateOnSpaceB = false;
  $expr = $yExpr = ''; # with $g used for expr segment
  $prevType = $lbs = $rbs = 0;
  $pStart = $P;
  while ($C !== false) {
    if (InStr($C, $termChs) || ($terminateOnSpaceB && $Line[$P-1]==' ')) { # at the end
      if ($lbs != $rbs)
        return RGError("Brackets missmatch. There are $lbs (s but $rbs )s in the expression", $pStart);
      return ($leadingCommaB ? ',' : '') . $expr;
    }
    $p = $P;
    if ($C == '[') { # [date y ...]
      if (substr($Line, $P, 6) == '[date ') {
        # looks like start of a [date ...] statement
        Step();
        if (($dCodeA = ParseDateStatement()) === false) return false; # returns [type | code] where type can be ExT_String or ExT_Integer, or false
        if ($dCodeA[0] != ExT_Integer) return RGError('Only the [date y ...] (year) form of the date statement can be used in an integer expression. Use date Bro s directly for date day difference arithmetic');
        $g = $dCodeA[1];
        Step(); # over the closing ]
      }else
        return RGError('Invalid [ statement found. Only [date y ...] statements can be used in an integer expression.', $p);
    }else{
      # IntExpr: Not [. y:# or Token?
      if (MatchStep('y:')) {
        # y: expect a year intExpr
        if (($yExpr = IntExpr(' ', true)) === false) return false; # with leading comma
        continue;
      }
      if (InStr($C, '"\'.')) return RGError("Invalid $C character in integer expression", $p);
      # Token?
      if (($tok = GetToken(' ]()+-*/%!<>=&|,')) !== false) {
        # got a tok.
        # Expect
        # and, or, number, constant, int RG built in variable, int format variable, function or para call
        if ($tok == 'and' || $tok == 'or') {
          $expr .= " $tok "; # code works wo the spaces but it doesn't look nice
          $prevType = '&';
          continue;
        }
        if (ctype_digit($tok[0])) {
          if (!ctype_digit($tok)) return RGError("$tok starts with a digit but is not an integer number as expected", $p);
          $g = $tok;
        }else{
          # IntExpr: Not a number so try a BroRef
          if (($broRef = BroRef($tok, true)) !== false) { # true for no BroRef errors
            BroRefToBroIdAndParamForm($broRef, $broId, $pBroRef);
            $g     = "Data($pBroRef$yExpr)";
            $gType = BroExprType($broId);
          }else
          # IntExpr: not a BroRef so try the symbol table
          if (($symbolA = STlookup($tok)) !== false) {
            switch ($symbolA[STI_SType]) {
              case ST_Constant: $g = $symbolA[STI_Val]; break;
              case ST_Function: $g = "$tok()"; break;
              case ST_Var:      $g = '$' . ($InFuncB ? "GLOBALS['$tok']" : $tok); break;
              default: return RGError("Invalid integer expression as $tok is a " . SymbolTypeToStr($symbolA[STI_SType]) . " which cannot be used in an integer expression");
            }
            if ($symbolA[STI_EType] != ExT_Integer)
              return RGError("$tok is not an integer data type as required in an integer expression", $p);
          }else if (defined($tok)) {
            # not in the symbol table but is a PHP constant
            $g = constant($tok);
            if (!is_int($g)) return RGError("PHP Constant $tok with value |$g| is not an integer as required in an integer expression", $p);
          }else
            # return RGError("$tok has not been defined or cannot be used here", $p);
            # nothing so call BroRef() again with errors enabled
            return BroRef($tok);
        }
      }else{
        # IntExpr: not a tok, expect ( ) + - * / % ! < > = & | but not ] or , as they should be in $termChs when valid
        if (InStr($C, '()+-*/%!<>=&|')) {
          # put straight in
          $expr .= $C;
          $prevType = $C;
          Step();
          switch ($prevType) { # C before the Step
            case '(': $lbs++; break;
            case ')': $rbs++; break;
            case '!': # could be ! or != or !==
              for ($i=0; $i<2 && $C == '='; $i++) {
                $expr .= $C;
                Step();
              }
              break;
            case '<': # could be < <> or <=
              if ($C == '>' || $C == '=') {
                $expr .= $C;
                Step();
              }
              break;
            case '>': # could be > or >=
              if ($C == '=') {
                $expr .= $C;
                Step();
              }
              break;
            case '=': # Expect == or ===
              if ($C != '=')
                return RGError('Assignment operator = found within an expression. Equals comparison operator == intended?', $p);
              for ($i=0; $i<2 && $C == '='; $i++) {
                $expr .= $C;
                Step();
              }
              break;
            case '&': # Expect &&
              if ($C != '&')
                return RGError('Unexpected single &. And operator && intended?', $p);
              $expr .= $C;
              Step();
              break;
            case '|': # Expect ||
              if ($C != '|')
                return RGError('Unexpected single |. Or operator || intended?', $p);
              $expr .= $C;
              Step();
              break;
          }
          continue;
        }
        return RGError("Unexpected character $C in integer expression", $p);
      }
    }
    # IntExpr: Got expr segment in $g of type ExT_Integer
    # and previous segment type in $prevType: 0 ExT_Integer ( ) + - * / % ! < > = & |
    # echo "expr=$expr prevType=$prevType g=$g C $C Line[p] ". substr($Line, $P). "<br>";
    if ($prevType == ExT_Integer || $prevType === ')') # ExT_Integer )
      return RGError("An operator is missing before the $g part of the integer expression", $p);
    # $prevType = 0 ( + - * / % ! < > = & |
    $expr .= $g; # straight in
    $prevType = ExT_Integer;
  }
  return false;
}

# string StartFunction($typeN)
# Start the compilation of a 'function' of $typeN: block, header, footer, function, para
function StartFunction($typeN, $name='') {
  global $Line, $InFuncB, $Level;
  static $headerNum, $footerNum;

  if ($Level) return RGError('is illegal within the ' . LevelInfo(), -1);

  $parameters = '';
  $eType = ExT_None;
  switch ($typeN) {
    case ST_Header: $headerNum++; $name = "HeaderFn{$headerNum}F"; AddP1Line("\$HeaderFn='$name';"); break; # $HeaderFn='HeaderFn1F';
    case ST_Footer: $footerNum++; $name = "FooterFn{$footerNum}F"; AddP1Line("\$FooterFn='$name';"); break; # $FooterFn='FooterFn1F';
    case ST_Function:
    case ST_Para:   $eType = ExT_String; break; # Assume ExT_String for functions and paras
    default: return RGError('StartFunction called with unknown function type', -1);
  }
  if (AddSymbolToST($name, $typeN, $eType) === false) return false;
  $InFuncB = true;
  return IncrementLevel($typeN, "function $name($parameters) {");
}

# string ProcessEnd()
# Process an [end] statement, decrementing level
function ProcessEnd() {
  global $InFuncB, $Level, $LevelsInfoA, $TableA;
  if (!$Level)
    return RGError("This [end] is out of place", -1);

  switch ($LevelsInfoA[$Level][LII_Type]) {
    case LT_Block:
      $cs = 'BlockEnd();';
      break;
    case LT_Toc:
    case LT_Table:
      $cs = 'TableEnd();';
      $TableA = 0;
      break;
    default:
      $cs = '}';
  }
  $Level--;
  AddP1Line($cs);
  $InFuncB = false;
  return '';
}

function IncrementLevel($typeN, $line) {
  global $Level, $LevelsInfoA;
  AddP1Line($line);
  $LevelsInfoA[++$Level] = [$typeN, FormatAndLine()]; # [LII_Type | LII_FormatAndLine]
  return '';
}

function LevelInfo() {
  global $Level, $LevelsInfoA;
  $infoA = $LevelsInfoA[$Level];
  return LevelTypeToStr($infoA[LII_Type]) . ' starting at ' .  $infoA[LII_FormatAndLine];
}


############
# Compiler Functions #
############

# void AddP1Line($line)
# ----------------------
# Adds a line to the Pass 1 array
function AddP1Line($line) {
  global $P1LinesA, $FuncLinesA, $InFuncB, $FormatLevel, $Level, $Line;
  $indent = $Level*2 + (!$InFuncB)*$FormatLevel*2;
  $indent = $indent > 0 ? str_pad('', $indent) : '';
  if (RG_DEBUG) { # Output the source line
    $comment = ' ' . FormatAndLine() . NL;
    if ($InFuncB)
      $FuncLinesA[] = $indent . '# ' .$Line . $comment;
    else
      $P1LinesA[] = $indent . '# '. $Line . $comment;;
    $comment = NL;
  }else
    $comment = ' # ' . FormatAndLine() . NL;
  if ($InFuncB)
    $FuncLinesA[] = $indent . $line . $comment;
  else
    $P1LinesA[] = $indent . $line . $comment;
}

# Add a format comment line. Not used for Func lines
function AddP1Comment($line) {
  global $P1LinesA, $FormatLevel;
  $P1LinesA[] = ($FormatLevel*2 > 0 ? str_pad('', $FormatLevel*2) : '') . '# ' . $line . NL;
}

# array SpaceTokenize($s)
# -----------------------
# Split a string into an array of space-delimited tokens, and return the array,
#  taking single or double quoted strings into account, leaving them in quoted form,
#  with the count of tokens found in slot 0
# djh?? Change this to work on $Line from $P?
function SpaceTokenize($s) {
  for ($toksA=[], $toksA[0]=0, $tok=strtok($s, ' '); $tok!==false; $tok=strtok(' ')) {
    $chr0 = $tok[0];
    if ($chr0 == DQ || $chr0 == SQ) {
      # Quoted string
      $lTok = strlen($tok);               # one word quoted
      $tok = ($lTok>1 && $tok[$lTok-1]==$chr0) ? $tok : ($tok . ' ' . strtok($chr0) .$chr0);
    }
    $toksA[] = $tok;
  }
  $toksA[0] = count($toksA)-1;
  return $toksA;
}

# int TokenType($tok)
# -----------------
# Returns the type of $tok as a TY_ enum
function TokenType($tok) {
  if (ctype_digit($tok)) return TY_INTSTR;
  if (is_numeric($tok))  return TY_NUMSTR;
  if ($len = strlen($tok)) {
    $chr0 = $tok[0];
    if ($len== 1) {
      if ($chr0 == DQ)  return TY_DQ;
      if ($chr0 == SQ) return TY_SQ;
      return TY_CHAR;
    }
    if ($chr0 == DQ) { # starts with "
      if ($tok[$len-1] == DQ)  return TY_DQSTRING;
      else                      return TY_DQSTART;
    }else if ($chr0 == SQ) {  # starts with '
      if ($tok[$len-1] == SQ) return TY_SQSTRING;
      else                      return TY_SQSTART;
    }
    return TY_STRING;
  }
  return 0;
}

function VarType($var) {
  if (is_string($var)) return ExT_String;
  if (is_int($var))    return ExT_Integer;
  if (is_float($var))  return ExT_Decimal;
  if (is_bool($var))   return ExT_Boolean;
#if (is_numeric($var))return ExT_Numeric;
#if (is_null($var))   return "NULL";
#if (is_array($var))  return "array";
#if (is_object($var)) return "object";
#if (is_resource($var)) return "resource";
  return ExT_None;
}

# bool IsValidName($name, $typeN)
# -------------------------------
# Returns true if $name is a valid PHP variable or constant name with the correct RG final letter, false otherwise
# http:#www.php.net/manual/en/language.variables.basics.php
function IsValidName($name, $sTypeN, $exTypeN) {
  static $SyTypeLettersS = '_FFFPCHX', # F ST_Header, F ST_Footer, F ST_Function, P ST_Para, C ST_Constant, H ST_Heading, X ST_Xref
         $ExTypeLettersS = '_SIMEDB';  # S ExT_String, I ExT_Integer, M ExT_Money, E ExT_Decimal, D ExT_Date, B ExT_Boolean
  #echo "|$name| pm=", preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/',$name),
  #     ' lc=', (substr($name,-1) == $SyTypeLettersS[$typeN]),
  #     ' res=' ,preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/',$name) && (substr($name,-1) == $SyTypeLettersS[$typeN]), '<br>';
  if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/',$name))
    return RGError("|$name| is not valid as a PHP name");
  if ($sTypeN) { # allows this to be called to check a RowName with no rules other than PHP name
    if ($sTypeN <= ST_Xref) {
      if (substr($name,-1) != $SyTypeLettersS[$sTypeN])
        return RGError("|$name| is invalid for a ". SymbolTypeToStr($sTypeN) . '. Final letter should be an '. $SyTypeLettersS[$sTypeN]);
    }else if (substr($name,-1) != $ExTypeLettersS[$exTypeN]) # for a var
      return RGError("|$name| is invalid for a ". ExprTypeToStr($exTypeN) . ' variable. Final letter should be an '. $ExTypeLettersS[$exTypeN]);
  }
  return true;
}

# bool AddSymbolToST($name, $sTypeN, $exTypeN=ExT_String, $val=0)
# ------------------------------------------------------------
# Adds symbol of type $typeN to the global symbol table array
# Returns true if this could be done,
#         false if there is a name clash. (Same name for different types not allowed.)
function AddSymbolToST($name, $sTypeN, $exTypeN=ExT_String, $val=0) {
  global $SymbolTableA, $BroNamesMapA;
  # echo "AddSymbolToST('$name', $sTypeN, $exTypeN, $val)<br>";
  if (!IsValidName($name, $sTypeN, $exTypeN)) return false;
  if (isset($SymbolTableA[$name])) return RGError("$name is already in use for a " . SymbolTypeToStr($SymbolTableA[$name][STI_SType]) . ' name and so cannot be used here');
  if (isset($BroNamesMapA[$name]))return RGError("$name is a BroName and so cannot be used here");
  if (defined($name))              return RGError("$name is already in use for a PHP program constant and so cannot be used here");
  if (isset($GLOBALS[$name]))      return RGError("$name is already in use for a PHP program variable and so cannot be used here");
  if (function_exists($name))      return RGError("$name is already in use for a PHP function and so cannot be used here");
                         # [STI_SType | STI_EType | STI_Val | STI_Uses | STI_FormatAndLine]
  $SymbolTableA[$name] = [$sTypeN, $exTypeN, $val, 0, 0];
  return true;
}

function STlookup($name) {
  global $SymbolTableA;
  if (isset($SymbolTableA[$name]))
    return $SymbolTableA[$name];
  return false;
}

# Build $GlobalDimGroupExclDimsChrList for the S case for use by BroRef() but not U for compiling
function BuildGlobalDimGroupExclDimsChrList() {
  global $GlobalDimGroupExclDimsChrList, $ESizeId, $DimGroupsA;
  $GlobalDimGroupExclDimsChrList = '';
  if ($ESizeId <= ES_SmallFRSSE)
    for ($dg=0; $dg<DG_Num; ++$dg)
      if ($DimGroupsA[$dg][DGI_ExSmall]) # S Small Company case
        foreach ($DimGroupsA[$dg][DGI_DimsA] as $dimId)
          $GlobalDimGroupExclDimsChrList .= IntToChr($dimId);
}

function BroExprType($broId) {
  global $BroInfoA;
  if (($exTypeN = $BroInfoA[$broId][BroI_DataTypeN]) >= DT_Enum)
    switch ($exTypeN) {
      case DT_Enum:
      case DT_Share: $exTypeN = ExT_Integer; break; # DT_Enum and DT_Share       -> I for RG expression purposes
      default:       $exTypeN = ExT_Decimal; break; # DT_PerShare and DT_PerCent -> E for RG expression purposes
    }
  return $exTypeN;
}

function BroRefToBroIdAndParamForm($broRef, &$broId, &$pBroRef) {
  if (is_string($broRef)) {
    $broId = (int)$broRef;
    $pBroRef = "'$broRef'";
  }else
    $broId = $pBroRef = $broRef;
}

function PBroRef($broRef) {
  if (is_string($broRef))
    return "'$broRef'";
  return $broRef;
}

/* array CellRef($ref)
   -------------------
where
 $ref is expected to be <RowName|this>:<t{#}|d{#}|<y0{#}-y3{#}>|c{#}|p{#}>{:colSum} with <t{#}|d{#}|<y0{#}-y3{#}>|c{#}|p{#}> to define the column
Returns false or [RowNum, Col, Expr type, 0|1 re colSum] with RowNum = 0 for this

Examples
  R.PL.Revenue:t
  R.PL.Revenue:y02
  But not R.PL.Revenue:n as n will normally be an xref
  Don't allow numeric form of col # */
function CellRef($ref) {
  global $Line, $C, $P, $RowNamesA;
  $p = $P - strlen($ref);
  # Check for : col ref
  $segs = explode(':', $ref);
  $rName = $segs[0];  # RowName expected
  $thisFormatAndLine = FormatAndLine();
  if ($rName == 'this')
    $rowInfoA = [$thisFormatAndLine, $GLOBALS['TableA'], 0]; # [FormatAndLine string, $TableA, $rowNum]
  else{
    if (!isset($RowNamesA[$rName])) # If row name doesn't exist possibly isn't a Cellref so just return false without error
      return false;
    # RowName is known so assume this is supposed to be a Cellref and error if anything is wrong from here onwards.
    $rowInfoA = $RowNamesA[$rName]; # [FormatAndLine string, $TableA, $rowNum]
    if ($rowInfoA[0] == $thisFormatAndLine)
      return RGError("$rName is the name of the current Row. Use 'this' instead of the name for cell references to the left on the current row. (Cell references to the right on the current row are not currently supported. If you need that, let David know.) Only previous rows, and any cell, can be addressed by name", $p);
  }
  #Dump('rowInfoA',$rowInfoA);
  $n = count($segs);
  if ($n==1) return RGError("$rName is a known Row Name defined in $rowInfoA[0] but a column reference following that in the form :&lt;t{#}|d{#}|&lt;y0{#}-y3{#}>|c{#}|p{#}> is missing", $p);
  if ($n>3)  return RGError("$rName is a known Row Name defined in $rowInfoA[0] but that should be followed by one or two ':' delimited segments to specify the column and optionally colSum", $p);
  if ($n==3) {
    if (strcasecmp($segs[2], 'colSum')) return RGError("$rName is a known Row Name defined in $rowInfoA[0] but only 'colSum' is valid as the third segment of a cell reference, not $segs[2]", $p);
    $colSum = 1;
  }else
    $colSum = 0;
  # Expect a column reference of <t{#}|d{#}|<y0{#}-y3{#}>|c{#}|p{#}>
  $col = $segs[1];
  if (!$col)             return RGError("$rName is a known Row Name defined in {$RowNamesA[$tok][0]} but a column reference following that in the form :&lt;t{#}|d{#}|&lt;y0{#}-y3{#}>|c{#}|p{#}> is missing", $p);
  if (ctype_digit($col)) return RGError("A col # for the column part of a cell reference is not permitted. A cell reference is expected to be of the form &lt;RowName|this>:&lt;t{#}|d{#}|&lt;y0{#}-y3{#}>|c{#}|p{#}&gt{:colSum}; e.g. $rName:y0>", $p);
  $tableAR   = &$rowInfoA[1];
  $tableCols =  $tableAR['Cols'];
  $nCols     =  $tableAR['NCols'];
  $n = 1; # index for #
  $c = $col[0];
  switch ($c) {
    case 'n': return RGError("An 'n' for the column part of a cell reference is not permitted. Note columns generally contain xrefs so if you want to insert a note number here use an xref. A cell reference is expected to be of the form &lt;RowName|this>:&lt;t{#}|d{#}|&lt;y0{#}-y3{#}>> e.g. R.PL.GrossProfit:y0>", $p);
    case 'y':
      if (!isset($col[1])) return RGError("No year character after the y in the column part ($col) of the cell reference", $p);
      $n = 2;
      $c = $col[1]; # 0, 1, 2, 3 expected
      # fall thru
    case 't':
    case 'd':
    case 'c':
    case 'p':
      $coli = strpos($tableCols, $c); # $c = t, d, 0, 1, 2, 3, c, p expected
      break;
    default: $coli = false;
  }
  if ($coli === false)
    return RGError("There is no |$c| col for the column part ($col) of the cell reference in the table cols list of$tableCols", $p);
  else{
    if (isset($col[$n])) {
      # Got a # chr
      $r = $col[$n++]; # n now pts to char after the # which should not be set
      if (ctype_digit($r) && $r >= 1) {
        # Got a # digit
        $rn = (int)$r;
        while ($rn > 1 && $coli !== false) { # nothing to do for $rn == 1
          $coli = strpos($tableCols, $c, $coli+1);
          --$rn;
        }
        if ($coli === false) return RGError("No |$r| col of type |$c| found for column part ($col) of the cell reference in the table cols list of$tableCols", $p);
      }else{
        # not a # digit
        ++$n;
        return RGError("Character number $n (a '$r') in the column part ($col) of the cell reference not a digit >= 1 as expected for in form &lt;t{#}|d{#}|n{#}|&lt;y0{#}-y3{#}>|c{#}|p{#}>", $p);
      }
      if (isset($col[$n])) {
        $c = $col[$n++];
        return RGError("Unexpected character number $n (a '$c') in the column part ($col) of the cell reference", $p);
      }
    }
  }
  $colTypeN = $tableAR['ColTypeA'][$coli];
  if ($colSum && $colTypeN != CT_Money && $colTypeN != CT_Calc)
    return RGError(":colSum is only applicable to Money or Calculated columns but $col is a " . ColTypeToStr($colTypeN) . ' column', $p);
  return [$rowInfoA[2], $coli, $colTypeN <= CT_Descr ? ExT_String : ExT_Numeric, $colSum]; # [RowNum, Col, Expr type, 0|1 re colSum] with RowNum = 0 for this
}

# FnNum($expr)
# Return number (index) of RT Function for $expr, adding it to $RTFunctionsA if not already there.
# Used for cell expressions to be evaluated during TableEnd() processing rather than as a Col() parameter expression.
# Functions are created at RT using create_function()
# This mechanism could possibly be used for header and footer functions too.
function FnNum($expr, $thisExprB=false) {
  global $RTFunctionsA;
  $param = $thisExprB ? '$rowA' : '';
  $entry = "'$param','".str_replace(SQ, QSQ, $expr).SQ; # pair of parameters, code with ' => \' e.g. 'CellDat(2,1).' times 2'' => '','CellDat(2,1).\' times 2\''
  if (($i = array_search($entry, $RTFunctionsA)) === false) {
    $i = count($RTFunctionsA);
    $RTFunctionsA[] = $entry;
  }
  return $i;
}


# BroLoopRef()
# ------------
# Check for a Bro loop reference like:
#  SchInputEntity.MeansContact.Address:ContactType.RegisteredOffice@Address
#  SchInputEntity.ThirdPartyAgents.MeansContact.Address:TPAType.Accountants@Address
#  SchInputEntity.Officers.Name@Directors
#  SchInputEntity.Officers.Name@Directors@DirectorSigningAccounts
# Returns array of BroLoop parameters
#   pBroRef     = BroRef of first element of loop e.g. SchInputEntity.Officers.Name@Directors in parameter form
#   num         = number of times to loop
#   type        = 0 if BroId loop as for addresses, 1 if ManDiMeId loop as for Directors
#   testIdDelta = optional id delta from BroId to the BroId of a boolean element of the BRO set with output only if true
# or false

function BroLoopRef($tok) {
  global $BroInfoA;
  $segs = explode('@', $tok);    # function BroRef($ref,   $noErrorB=false)
  if (($n = count($segs)) > 1 && ($broRef = BroRef($seg0 = $segs[0], false)) !== false) { # Got '@' segs && RegisteredOffice or SchInputEntity.Officers.Name is a Bro
    $broId = (int)$broRef;  # (int) to get BroId in case of dimensions
    #echo "BroLoopRef $tok $broId segs[1]=$segs[1]<br>";
    $broBits = $BroInfoA[$broId][BroI_Bits];
    #if (($dataTypeN = $BroInfoA[$broId][BroI_DataTypeN]) != DT_String)
    #  return RGError("The Bro $seg0 is type $dataTypeN ". DataTypeStr($dataTypeN).' not string as a Bro loop requires');
    switch ($segs[1]) {
      case 'Address':
        # RegisteredOffice@Address                                                     -> RegisteredOffice.AddressLine1
        # TPAs:TPAType.Accountants@Address                                             -> TPAs.AddressLine1:TPAType.Accountants
        # SchInputEntity.ThirdPartyAgents.MeansContact.Address,TPAType.Accountants@Address -> SchInputEntity.ThirdPartyAgents.MeansContact.Address.Line1:TPAType.Accountants
        # SchInputEntity.MeansContact.Address,ContactType.RegisteredOffice                 -> SchInputEntity.MeansContact.Address.Line1:ContactType.RegisteredOffice
        if ($broBits&BroB_Ele) return RGError("The @Address loop option is only valid with a Bro Set but $seg0 is not a Set");
        if (strpos($seg0, ',') !== false)
          $first = str_replace('Address,', 'Address.Line1,', $seg0); # SchInputEntity.ThirdPartyAgents.MeansContact.Address:TPAType.Accountants@Address -> SchInputEntity.ThirdPartyAgents.MeansContact.Address.Line1:TPAType.Accountants
        else
          return RGError("The @Address loop option expects a Bro Set name ending in '.Address' followed by ,TPAType. ... or :ContactType. ...");
        # echo "first=$first<br>";
        if (($broRef = BroRef($first)) === false) return RGError("The Bro $first does not exist but is required for the @Address loop option");
        # pBroRef     = BroRef of first element of loop e.g. SchInputEntity.Officers.Name@Directors in parameter form
        # num         = number of times to loop
        # type        = 0 if BroId loop as for addresses, 1 if ManDiMeId loop as for Directors
        # testIdDelta = optional id delta from BroId to the BroId of a boolean element of the BRO set with output only if true
        return [PBroRef($broRef), BRO_NumAddressLines, 0, 0];

      case 'Directors':
        if ($broBits&BroB_Set) return RGError("The @Directors loop option is only valid with a Bro Element but $seg0 is a Set");
        $fullName = $seg0 . ',.Director1'; # SchInputEntity.Officers.Name,.Director1
        $num      = BRO_NumDirectors;
        $type     = 1;
        break;

      default: return RGError("Unknown name $segs[1] for @ loop option");
    }

    if (($broRef = BroRef($fullName)) === false) return RGError("The Bro $fullName does not exist but is required for the @{$segs[1]} loop option");
    BroRefToBroIdAndParamForm($broRef, $broId, $pBroRef);
    $testIdDelta = 0;
    if ($n > 2) {
      # SchInputEntity.Officers.Name@Directors@DirectorSigningAccounts
      # Check for the part of 0 minus the last level name & seg[2] & dim from fullname -> SchInputEntity.Officers.DirectorSigningAccounts:.Director1
      # echo "$set.1.{$segs[1]}<br>";
      $testBroName = substr($seg0, 0, strrpos($seg0, '.')+1) . $segs[2] . strrchr($fullName, ':');
      if (($testBroRef = BroRef($testBroName)) === false) return RGError("Bro loop test element $testBroName not found");
      $testBroId = (int)$testBroRef;
      if ($BroInfoA[$testBroId][BroI_DataTypeN] != DT_Boolean) return RGError("Bro loop test element $testBroName not a boolean");
      $testIdDelta = $testBroId - $broId;
    }
    return [$pBroRef, $num, $type, $testIdDelta];
  }
  return false;
}

# Returns string of current format and line number
function FormatAndLine() {
  global $FormatsA, $FormatIdx, $LineNum;
  return $FormatsA[$FormatIdx] . ' line '. ($LineNum+1);
}

function SymbolTypeToStr($typeN) {
  static $types = ['', 'header', 'footer', 'function', 'para', 'constant', 'heading', 'xref', 'variable', 'array', 'Bro element', 'Bro set'];
  if (!isset($types[$typeN])) return $typeN;
  return $types[$typeN];
}

function ExprTypeToStr($typeN) {
  static $types = ['', 'string', 'integer', 'money', 'decimal', 'date', 'bool', 'numeric'];
  return $types[$typeN];
}

function LevelTypeToStr($typeN) {
  static $types = ['', 'header', 'footer', 'function', 'para', 'block', 'if', 'else if', 'else', 'table', 'toc'];
  return $types[$typeN];
}

#####################
# Parsing Functions #
#####################

function CompilerCommand() {
  global $Line, $P, $ETypeId, $ETypeChr, $ESizeId, $FuncLinesA, $RgcLog;
  # #include  name comment
  # #constant name {=} value comment
  if (MatchStep('#include')) {
    # name comment
    $toksA = SpaceTokenize(substr($Line,$P));
    $n = $toksA[0];
    if ($n < 1)
      return RGError('No format name specified for #include command');
    CompileFormat(trim($toksA[1],'"\'')); # with quotes stripped if used
    return;
  }
  if (MatchStep('#constant')) {
    # name {=} value {comment} where value must be a number, a quoted string, or an already defined constant
    $line  = str_replace('=', ' ', substr($Line,$P)); # To avoid hassles re #constant name=value or #constant name= value or #constant name =value
    $toksA = SpaceTokenize($line);         #  Replacing = by ' ' would go wrong for #constant name 'colour = red' but that is unlikely
    $n = $toksA[0];
    if ($n < 2)
      return RGError('Name and value tokens not found for a #constant command. Expect: #constant name {=} value');
    $name = $toksA[1];
    $val  = $toksA[2];
    $exTypeN = 0;
    switch (TokenType($val)) {
      case TY_SQSTRING:
      case TY_DQSTRING:
        $val    = substr($val, 1, -1);  # remove the quotes for insertion in the symbol table
        $exTypeN = ExT_String;
        break;
      case TY_STRING: # Not a quoted string or number
        if (($symbolA = STlookup($val)) !== false && $symbolA[STI_SType] == ST_Constant) {
          $val    = $symbolA[STI_Val];
          $exTypeN = $symbolA[STI_EType];
        }
        if (defined($val)) {
          # not in the symbol table but is a PHP constant
          $val    = constant($val);
          $exTypeN = VarType($val);
        }
        break;
      case TY_INTSTR: $exTypeN = ExT_Integer; break;
      case TY_NUMSTR: $exTypeN = ExT_Numeric; break;
    }
    if (!$exTypeN)
      return RGError('Invalid value for #constant command. Expect a single number, quoted string, or already defined constant');
    if (AddSymbolToST($name, ST_Constant, $exTypeN, $val)) { # AddSymbolToST calls RGError if there is name clash or other problem with the name
      # const constant = value;
      if ($exTypeN == ExT_String) {
        if (InStr(SQ, $val))
          AddP1Line("const $name = \"$val\";");
        else
          AddP1Line("const $name = '$val';");
      }else
        AddP1Line("const $name = $val;");
      # Check for the Predefined Constants
      switch (Match($name, ['ETypeC', 'CoSizeC'])) {
        case 1: # ETypeC
          if ($exTypeN != ExT_Integer || $val < 1 || $val > ET_Num)
            return RGError('Invalid value for constant ETypeC. Must be one of &lt;ET_Sole | ET_Partnership | ET_LLP | ET_Charity | ET_PrivateLtdCo | ET_PrivateUnltdCo | ET_PrivateLtdGuarCo | ET_CommInterestCo | ET_PLC | ET_Other> or an integer or constant with an integer value between 1 and '.ET_Num);
          if ($val != $ETypeId && count($FuncLinesA))
            return RGError('Constant ETypeC cannot be set after format lines have been compiled. Move this line to near the start of the format or format set');
          $ETypeId   = $val;
          $ETypeChr = IntToChr($ETypeId);
          if ($ETypeId != ET_PrivateLtdCo && $ESizeId == ES_SmallFRSSE)
            $ESizeId = ES_Small;
          BuildGlobalDimGroupExclDimsChrList();
          $RgcLog .= 'Format Entity Type set to '.EntityTypeStr($ETypeId)."\n";
          break;
        case 2: # CoSizeC
          if ($exTypeN != ExT_Integer || $val < 1 || $val > ES_IFRS_SME)
            return RGError('Invalid value for constant CoSizeC. Must be one of &lt; ES_Small | ES_SmallFRSSE | ES_Medium | ES_Large | ES_IFRS_SME> or an integer or constant with an integer value between 1 and '.ES_IFRS_SME);
          if ($val==ES_SmallFRSSE && $ETypeId != ET_PrivateLtdCo)
            return RGError('constant CoSizeC cannot be set to ES_SmallFRSSE as the entity type is '.EntityTypeStr($ETypeId).' not Private Limited Company');
          if ($val != $ESizeId && count($FuncLinesA))
            return RGError('Constant CoSizeC cannot be set after format lines have been compiled. Move this line to near the start of the format or format set');
          $ESizeId = $val;
          $RgcLog .= 'Format Company Size set to '.EntitySizeStr($ESizeId)."\n";
          BuildGlobalDimGroupExclDimsChrList();
          break;
      }
    }
    return;
  }
  return RGError('Line starts with # but no compiler command keyword found. Expect: #include ... or #constant ...');
} # End of CompilerCommand fn


#################
# Gen Functions #
#################

function LastChrMatch($s, $c) {
  return substr($s,-1) == $c;
}

# function Quote(&$str) {
#   $str = SQ.$str.SQ;
# }

# bool MatchStep($v)
# -----------------
# If $v matches the characters from the current parse position $P in $Line,
#  steps along over $v & a following space if present (expect only 1 max) and returns true.
# If there is no match returns false with $P unchanged.
function MatchStep($v) {
  global $Line, $P;
  $len = strlen($v);
  if (substr($Line,$P,$len) == $v) {
    Step($len);
    return true;
  }
  return false;
}

function MatchOneStep($matchA) {
  global $Line, $P;
  foreach ($matchA as $i => $v) {
    $len = strlen($v);
    if (!strcasecmp(substr($Line,$P,$len), $v)) {
      Step($len);
      return $i;
    }
  }
  return false;
}


# char/bool Step($n=1)
# --------------------
# Steps $P along by $n (default 1), plus space if present, and sets the new $C, returning it
function Step($n=1) {
  global $Line, $LineLen, $C, $P;
  return $C = (($P += ($n + (isset($Line[$P+$n]) && $Line[$P+$n] == ' '))) < $LineLen) ? $Line[$P] : false;
}

# char/bool StepIfSpace()
# -----------------------
# Steps one character if the current character is a space and sets $C to the next character or false if at EoL
# Returns $C which is false if at EOL
/*
function StepIfSpace() {
  global $Line, $LineLen, $P, $C;
  if ($C == ' ')
    $C = (++$P < $LineLen) ? $Line[$P] : false;
  return $C;
} */

# char/bool StepSpace()
# -------------------------
# Steps $P if character is a space, returning $C
# Returns false if character is not a space.
/*
function StepSpace() {
  global $Line, $C, $P;
  if ($C == ' ')
    return Step();
  return false;
} */

function MoveToStatementEnd() {
  global $C, $P;
  while ($C !== false && $C != ']')
    Step();
}

function PBack($p) {
  global $Line, $P, $C;
  $P = $p;
  $C = $Line[$P];
  return false;
}

# Called when $C is ' or "
function GetString() {
  global $Line, $C, $P;
  $sA = explode($C, substr($Line, $P));
  if (count($sA) < 3)
    return RGError("No closing $C for string $C{$sA[1]}");
  $s = $C . $sA[1] . $C;
  Step(strlen($s));
#if (strpos($s, '&') !== false)  # wrong if &amp;  djh?? Do better. Went wrong re inclusion of &#163;
#  $s = str_replace('&', '&amp;', $s);
# $s = str_replace(['&', '£'], ['&amp;', '&#163;'], $s);
  # 29.12.11 change for all utf-8 for high chars like £
  # Encode the html/xml special characters & < > ' ". No have some html tags in strings e.g. <span> re note numbers so jsu do &
  # return $c . htmlspecialchars($s, ENT_QUOTES) . $c;
  return str_replace('&', '&amp;', $s); # djh?? To be improved re quotes and strings
}

# false/string GetToken($seps)
# ----------------------------
# Gets a token starting from the current position ($P & chr $C) in $Line to one of the seps chrs, excluding the seps chr.
# If a token is found returns the token with $P & $C set to the sep chr unless that was a space in which case the space is stepped over.
# Returns false with $P and $C unchanged if no token is found, including for the case where $P/$C = one of the seps chars and something follows,
# which causes strtok() to return the something, but GetToken() detecs this and returns false.
# If GetToken() is being used to move through an expression $P/$C as the separator should be processed by the calling i.e. be moved over
# before GetToken() is called again.
# Does not ever return an empty string.
function GetToken($seps) {
  global $Line, $P, $C;
  if (($tok = strtok(substr($Line,$P), $seps)) === false || $C != $tok[0]) return false; # no token found by strtok || token but not OK as below.
  Step(strlen($tok)); # If strtok() finds a token that might or might not be OK. If the first char(s) of substr($Line,$P) is in $seps and there
  return $tok;        # is something after it/them strtok() skips over the sep char(s) and returns the somthing as the token, as per the 4.1 new
}                     # behaviour, but this is NBG for our needs as the token found does not start from the current position in Line.
                      # This is detected by the $C != $tok[0] check => false with $P and $C unchanged.

/** http://www.php.net/manual/en/function.strtok.php
 * The TokenIterator class allows you to iterate through string tokens using
 * the familiar foreach control structure.
 *
 * Example:
 * <code>
 * <?php
 * $string = 'This is a test.';
 * $delimiters = ' ';
 * $ti = new TokenIterator($string, $delimiters);
 *
 * foreach ($ti as $count => $token) {
 *     echo sprintf("%d, %s\n", $count, $token);
 * }
 *
 * // Prints the following output:
 * // 0. This
 * // 1. is
 * // 2. a
 * // 3. test.
 * </code>
class TokenIterator implements Iterator {
    # The string to tokenize.
    # @var string
    protected $_string;

    # The token delimiters.
    # @var string
    protected $_delims;

    # Stores the current token.
    # @var mixed
    protected $_token;

    # Internal token counter.
    # @var int
    protected $_counter = 0;

    # Constructor.
    # @param string $string The string to tokenize.
    # @param string $delims The token delimiters.
    public function __construct($string, $delims) {
        $this->_string = $string;
        $this->_delims = $delims;
        $this->_token = strtok($string, $delims);
    }

    # @see Iterator::current()
    public function current() {
        return $this->_token;
    }

    # @see Iterator::key()
    public function key() {
        return $this->_counter;
    }

    # @see Iterator::next()
    public function next() {
        $this->_token = strtok($this->_delims);
        if ($this->valid()) {
            ++$this->_counter;
        }
    }

    # @see Iterator::rewind()
    public function rewind() {
        $this->_counter = 0;
        $this->_token   = strtok($this->_string, $this->_delims);
    }

    # @see Iterator::valid()
    public function valid() {
        return $this->_token !== FALSE;
    }
}
*/

function GetTokenAndMatch($seps, $matchA) {
  if (($tok = GetToken($seps)) !== false) {
    foreach ($matchA as $i => $val)
      if ($tok == $val)
        return $i;
  }
  return false;
}

# AddParamStr()
# -------------
# Add a parameter string to a statement.
# Called with:
# $st               Statement or code segment with must contain %s where the parameter string is to go,
# $paramsA          An array of parameter values, set to 0 if not defined, not 0 if defined,
# $noLeadingCommaB  Set if parameter string is not to have a leading zero, default false.
# Returns $st with %s replaced by a parameters string for calling a function with parameter defaults of 0.
# The paremeter string inserted is '' if all parameters are 0. This is not affected by the $noLeadingCommaB setting.
function AddParamStr($st, $paramsA, $noLeadingCommaB=false) {
  for ($i = count($paramsA) - 1; $i >= 0; --$i) {
    if ($paramsA[$i]) { # got a value so all params up to this one need a comma and a value
      for ( ; $i >= 0; --$i) {
        if ($i || !$noLeadingCommaB) # leave $paramsA[0] untouched for $noLeadingCommaB so as not to add the comma here
          $paramsA[$i] = $paramsA[$i] ? ",{$paramsA[$i]}" : ',0';
      }
    }else
      $paramsA[$i] = '';
  }
  return sprintf($st, implode('', $paramsA));
}

function ColTypeToStr($typeN) {
  static $types = ['', 'Text', 'Descr', 'Note', 'Money', 'Calculated', 'Percent'];
  return $types[(int)$typeN];
}


##########
# Errors #
##########

# p: +ve = position in line for highlighting
#      0 = use $P
#     -1 = no highlighting (p not known)
function RGError($errS, $p=0, $noErrorB=false) {
  global $RgcLog, $FormatIdx, $Line, $P, $Errors, $LastNoErrorErrorS; #, $NoErrorB;

#echo "RGError($errS<br>"; # djh??
# if (InStr('Unexpected character , in expression', $errS))
#   xdebug_print_function_stack( 'Unexpected character , in expression' );

 #if ($NoErrorB || $noErrorB)
  if ($noErrorB) {
    $LastNoErrorErrorS = $errS;
    return false;
  }
  ++$Errors;
  if ($FormatIdx >= 0) {
    if ($p == -1)
      $errS = 'Error in ' . FormatAndLine() . " |$Line| $errS.\n";
    else{
      if (!$p) $p=$P;
      $errS = 'Error in ' . FormatAndLine() . ' |' . substr($Line,0,$p) . "<span class='b u'>" . substr($Line,$p,5)  . '</span>' . substr($Line,$p+5) . "| $errS.\n";
    }
    if ($Errors === 5) {
      $RgcLog .= "Compilation aborted after 5 errors.\n";
      $Errors = -$Errors;
    }
  }else
    $errS = "Format error: $errS.\n";
  # Error($errS);
 #$RgcLog .= str_replace(['&', '£'], ['&amp;', '&#163;'], $errS);
  $RgcLog .= str_replace('£', '&#163;', $errS);
  return false;
}

function Form($timeB=false, $topB=false) {
  global $DB, $FormatId;
  echo '<h2 class=c>Compile Format</h2>
<p class=c>Select the Format to Compile and click Compile</p>
<div class=mc style=width:450px>
<form method=post>
';
  $res=$DB->ResQuery('Select * from Formats Order By SortKey');
  while ($o = $res->fetch_object()) {
    $id = (int)$o->Id;
    $checked = $FormatId==$id ? ' checked' : '';
    echo "<input id=f$id type=radio class=radio name=Format value=$id$checked> <label for=f$id>$o->Name, $o->Descr</label><br>\n";
  }
  $res->free();
  /*
  $dir = RG_FormatPath . 'Corp/';
  $filesA = scandir($dir);
  $i = 0;
  $files = '';
  foreach ($filesA as $file) {
    if ((strpos($file, '.bp') || strpos($file, '.p')) && !strpos($file, '.bak') && !is_dir($dir.$file)) {
      $checked = $i==$FormatId ? ' checked' : '';
      echo "<input type=radio class=radio name=File value=$i$checked> ", $file, "<br>\n";
      $files .= ",$file";
      $checked = '';
      ++$i;
    }
  }
  $files = substr($files, 1);
  echo "<input type=hidden name=Files value='$files'> */
  echo "<p class=c><button class='on mt10'>Compile</button></p>
</form>
</div>
";
  Footer($timeB, $topB); # Footer($timeB=true, $topB=false, $notCentredB=false) {
}


/* string BroRef($ref, $noErrorB=false)
   -----------------------------------
where
 $ref can include dimension references and or a tuple ref
Checks ref for BroName and dimensions, and for validity incl re Zones if found.
Return false on an error. Does not generate errors if $noErrorB is true
If OK, returns a Bro Reference of:
  BroId{,Bro Object BroDat/BroTupDat key} where the key is omitted if it is just BroDatT_End (2).
  With Inst === Tu_AllInst (7999) for a Tuple ref of T.all -> 9999 in the BroDatKey

djh?? Add Zones allowable dimensions check?

Needs
  $ETypeId, $ETypeChr, $GlobalDimGroupExclDimsChrList set to S not U case only by a call to BuildGlobalDimGroupExclDimsChrList(), $ESizeId to have been set
  $BroInfoA, $BroNamesA, $BroNamesMapA, $DiMeMapA, $DiMesA, $ManagedPropDiMeIdsA, $DiMeNamesA, $DimGroupsA and $ZonesA to have been loaded

Examples
  PL.Revenue.GeoSeg.ByDestination,Countries.UK
  SchInputEntity.Officers.Properties,Officers.Director1,OfficerType.Executive
  SchInputEntity.Officers.Name,.Director1 */

function BroRef($ref, $noErrorB=false) {
  global $P, $ETypeId, $ETypeChr, $GlobalDimGroupExclDimsChrList, $ESizeId, $BroInfoA, $BroNamesA, $BroNamesMapA, $DiMeMapA, $DiMesA, $ManagedPropDiMeIdsA, $DimGroupsA, $ZonesA, $ZonesNowA;
  $refBits = $propSet = $manDiMeId = $manDiMei = $proDiMeId = $proDiMei = $pyaDiMeId = $pyaDiMei = $inst = 0;
  $diMeIdsA = $stdDimsA = [];
  $broDatType = BroDatT_End;
  $p = $P - strlen($ref);
  # echo "BroRef($ref, $noErrorB), P=$P, p=$p<br>";
  # BroName{,DimRef...}{,<end|start>}{,T.#}
  $segs  = explode(',', $ref);
  $bName = $segs[0];
  if (isset($BroNamesMapA[$bName])) {
    $broId = $BroNamesMapA[$bName];
    $broA = $BroInfoA[$broId];
    $bits = $broA[BroI_Bits];
    $bName = $BroNamesA[$broId]; # to get full name in case Shortname was used
  }else
    return RGError("BroName <i>$bName</i> not known", $p, $noErrorB);
  if (($n = count($segs)) > 1) {
    # More than BroName/ShortName so extract...
    array_shift($segs); # shift off BroName/ShortName leaving just {,DimRef...}{,<end|start>}{,T.#} in $segs
    # $manDiMei, $proDiMei = index in $segs for M/N, property types
    foreach ($segs as $ref) {
      $ref = trim($ref);
      if (!$ref) return RGError('Empty Bro Reference segment', $p, $noErrorB);
      # Tuple Ref?
      if (!strncasecmp($ref, 'T.', 2)) {
        # Should be Tuple Ref, as no DiMe is named just 'T'
        if (!$broA[BroI_TuMeId]) return RGError("Tuple reference <i>$ref</i> included but the Bro is not a Tuple Bro", $p, $noErrorB);
        if (!strncasecmp($ref, 'T.all', 5)) {
          $inst = Tu_AllInst; # 7999 all tuple instances
          continue;
        }
        $inst=substr($ref,2); # chop off T.
        if (ctype_digit($inst)) {
          if (!InRange($inst=(int)$inst, 1, Tu_MaxInst)) # 7998
            return RGError("Tuple instance <i>$inst</i> is not in the allowable range of 1 to ".Tu_MaxInst, $p, $noErrorB); # 7998
        }else
          return RGError("Tuple instance <i>$inst</i> is not an integer", $p, $noErrorB);
        continue;
      }
      # start|end?
      if (($m = Match($ref, ['start','end']))) {
        $broDatType = $m; # 1 for Start = BroDatT_Start, 2 for End = BroDatT_End
        continue;
      }
      # DiMe expected
      if ($ref[0] == '.') # ,.Director1 form
        foreach (explode('.', $bName) as $bSeg) # See if one of the BroName segments gives a DiMe match
          if (isset($DiMeMapA[$bSeg.$ref])) {
            $ref = $bSeg.$ref;
            break;
          }
      if (!isset($DiMeMapA[$ref])) return RGError("Dimension reference <i>$ref</i> not known", $p, $noErrorB);
      if (in_array($diMeId = $DiMeMapA[$ref], $diMeIdsA)) continue; # get diMeId from ref, skip duplicates
      $diMeIdsA[] = $diMeId;
    }
  }
  # Bro.BroI_PMemDiMesA: PMemDiMesA = Bro DiMes Overrides array [i => MandatsA, DefaultsA, ExcludesA, AllowsA]
  if ($broA[BroI_PMemDiMesA]) { # Have Bro DiMes
    $PMemDiMesA = $broA[BroI_PMemDiMesA];
    $broDiMeAllowsA = $PMemDiMesA[II_AllowsA];
    if ($PMemDiMesA[II_MandatsA ]) # Got Mandatory DiMes
      foreach ($PMemDiMesA[II_MandatsA ] as $broDiMesDiMeId)
        if (!in_array($broDiMesDiMeId, $diMeIdsA)) {
          # Mandatory DiMe not included. Error if DiMe from same Dim is included. Add it o'wise.
          $broDiMesDimId = $DiMesA[$broDiMesDiMeId][DiMeI_DimId];
          foreach ($diMeIdsA as $diMeId)
            if ($DiMesA[$diMeId][DiMeI_DimId]===$broDiMesDimId) return DError([$diMeId, "Dim$diMeId",$broDiMesDiMeId], "is not allowable for Bro <i>$broId $bName</i> as the %s DiMe to be used with it must be %s", $p, $noErrorB);
          # Add $broDiMesDiMeId
          $diMeIdsA[] = $broDiMesDiMeId;
        }
    if ($PMemDiMesA[II_DefaultsA]) # Got Default DiMes
      foreach ($PMemDiMesA[II_DefaultsA] as $broDiMesDiMeId)
        if (!in_array($broDiMesDiMeId, $diMeIdsA)) {
          # Default DiMe not included. Add it if don't have another DiMe from same Dim.
          $broDiMesDimId = $DiMesA[$broDiMesDiMeId][DiMeI_DimId];
          foreach ($diMeIdsA as $diMeId)
            if ($DiMesA[$diMeId][DiMeI_DimId]===$broDiMesDimId) {
              $broDiMesDiMeId = 0; # already have a DiMe in the Default's Dim so skip it
              break;
            }
          if ($broDiMesDiMeId) # Add the Default
            $diMeIdsA[] = $broDiMesDiMeId;
        }
    if ($PMemDiMesA[II_ExcludesA]) { # Got Exclude DiMes
      $excludedA = array_intersect($diMeIdsA, $PMemDiMesA[II_ExcludesA]);
      if (count($excludedA)) {
        $diMeId = current($excludedA);
        return DError($diMeId, "is excluded from use with Bro <i>$broId $bName</i>", $p, $noErrorB);
      }
    }
  }else
    $broDiMeAllowsA = 0;

  # broA checks
  if (!$broA[BroI_DataTypeN]) return RGError("Bro has no Data Type and so cannot be used in a report", $p, $noErrorB);
  # DiMes checks
  if (count($diMeIdsA)) {
    $UsablePropDims = $broA[BroI_SumUsablePropDims] ? : $broA[BroI_BroUsableProp];
    foreach ($diMeIdsA as $i => $diMeId) {
      $dimId = $DiMesA[$diMeId][DiMeI_DimId];
      if ($UsablePropDims) {
        if (!InChrList($dimId, $UsablePropDims)) {
          if ($broDiMeAllowsA) {
            if (!in_array($diMeId, $broDiMeAllowsA)) return DError([$diMeId, "Dim$diMeId"], "from %s is not allowable for use with Bro <i>$broId $bName</i> whose allowable dimensions currently are " . ChrListToCsList($UsablePropDims)."; nor is DiMe $diMeId an Allow DiMe", $p, $noErrorB);
          }else
            return DError([$diMeId, "Dim$diMeId"], "from %s is not allowable for use with Bro <i>$broId $bName</i> whose allowable dimensions currently are ".ChrListToCsList($UsablePropDims), $p, $noErrorB);
        }
      }else if ($broDiMeAllowsA && !in_array($diMeId, $broDiMeAllowsA)) return DError($diMeId, "is not allowable for use with Bro <i>$broId $bName</i> as it is not one of the ".implode(',',$broDiMeAllowsA)." Allow DiMes of the Bro");
      $diMeA    = $DiMesA[$diMeId]; # [DiMeI_DimId, DiMeI_Bits, DiMeI_PropSet, DiMeI_ETypeList, DiMeI_MuxListA, DiMeI_SumListA]
      $dimId    = $diMeA[DiMeI_DimId];
      $diMeBits = $diMeA[DiMeI_Bits];
      if ($diMeA[DiMeI_ETypeList] && strpos($diMeA[DiMeI_ETypeList], $ETypeChr) === false) return DError($diMeId, "is not allowable for Bro <i>$broId $bName</i> for an Entity of type ".EntityTypeStr($ETypeId), $p, $noErrorB);
      if ($diMeBits & DiMeB_Zilch) return DError($diMeId, "is a 'Z' type reserved for RG internal use", $p, $noErrorB);
     #if ($diMeBits & DiMeB_RO)    return DError($diMeId, "is an 'R' type for RG reporting use which cannot be used for posting");
      if (InChrList($dimId, $GlobalDimGroupExclDimsChrList)) { # Must be an S case as U cases have been excluded from $GlobalDimGroupExclDimsChrList for compiling. See $DimGroupsA in Constants.inc
        for ($dg=0; $dg<DG_Num; ++$dg)
          if (in_array($dimId, $DimGroupsA[$dg][DGI_DimsA])) # dim is in this Dim Group
            return DError($diMeId, "involves {$DimGroupsA[$dg][DGI_Name]} which is not a Reporting Requirement applicable to a ".EntitySizeStr($ESizeId).' Company', $p, $noErrorB);
      }
      # Check special DiMe cases
      if ($diMeBits & DiMeB_M) {
        if ($manDiMeId) return DError($diMeId, "is a duplicate 'M#' Mandatory type but only one such type can be used", $p, $noErrorB);
        $manDiMeId = $diMeId;
        $manDiMei  = $i;
        $mType   = $diMeA[DiMeI_MType];
        $refBits  |= BRefB_DiMeMan;
      }else if ($diMeBits & DiMeB_pYa) {
        $pyaDiMeId = $diMeId;
        $pyaDiMei  = $i;
        $refBits  |= BRefB_DiMePya;
      }else if ($diMeBits & DiMeB_Prop) {
        if ($proDiMeId) return DError($diMeId, "is a duplicate 'P' or Property type but only one such type can be used", $p, $noErrorB);
        $proDiMeId = $diMeId;
        $proDiMei  = $i;
      }else{
        # else standard DiMe. Check for 2 from same Dim
        if (in_array($dimId, $stdDimsA)) return DError([$diMeId, "Dim$diMeId"], "is a duplicate use of Dim %s but only one dimension member per Dim can be used", $p, $noErrorB);
        $stdDimsA[]  = $dimId;
      }
      if ($diMeBits & DiMeB_muX)
        $refBits |= BRefB_DiMeMux;
    }
  }
  # M# Checks
  if ($bits & BroB_M) { # Bro is not RO and the Bro's Usable Dims includes one of the Dims which include M# Type DiMes
    if ($manDiMeId) {
      if (($bits & BroB_2) && $mType) { # Bro requires a Property DiMe, except for the M0 423 CoSec case which is covered by the $mType test as that is 0 for the 423 M0 DiMe, or for M4/5 cases where the property DiMe is optional
        if (!$proDiMeId && $mType<4) return DError($manDiMeId, " is an 'M$mType' type Mandatory dimension member which requires a Property type dimension member as well but this is missing", $p, $noErrorB);
        if ($proDiMeId && $mType>1) {
          # The M# type is 2-5 which means it has a managed Property DiMes list
          $allowDiMeIdsA = $ManagedPropDiMeIdsA[$mType];
          if (!in_array($proDiMeId, $allowDiMeIdsA))
            return DError([$proDiMeId, $manDiMeId], "is not one of the allowable Property dimension members (".implode(',', $allowDiMeIdsA).") for Mandatory dimension member <i>%s</i>", $p, $noErrorB);
        }
      }
    }else{
      # No M# DiMe
      if ($proDiMeId) # As a property dimension has been used, an M# DiMe is mandatory
        return DError($proDiMeId, "is a Property dimension member but no 'M#' type Mandatory dimension member is present, as is required when a Property dimension member is used", $p, $noErrorB);
      # No M# DiMe and no prop which is legal only if for Officers post to Bro with DataType Money with DiMe 423 CoSec excluded
      # Not in compiler case re SchInputEntity.Officers.Name@Directors
      # if (!($bits & BroB_NoMok)) # Set for Officers, Money Type, 423 CoSec excluded
      #   return RGError("Bro <i>$broId $bName</i> requires an 'M#' type Mandatory dimension member but this is missing", $p, $noErrorB);
    }
  }
  if (count($diMeIdsA)) {
    # all OK so sort DiMes to get $manDiMeId{,$proDiMeId} first in the array, with $pyaDiMeId last, and any others sorted in between so that refs will be consistent
    if ($manDiMeId) {
      unset($diMeIdsA[$manDiMei]);
      if ($proDiMeId)
        unset($diMeIdsA[$proDiMei]);
    }
    if ($pyaDiMeId)
      unset($diMeIdsA[$pyaDiMei]);
    count($diMeIdsA) ? sort($diMeIdsA) : $diMeIdsA=[]; # if just sort() and $diMeIdsA -> empty, first i afterwards is not 0
    if ($manDiMeId) {
      if ($proDiMeId)
        array_unshift($diMeIdsA, $proDiMeId);
      array_unshift($diMeIdsA, $manDiMeId);
    }
    if ($pyaDiMeId) $diMeIdsA[] = $pyaDiMeId;
    $ref = "$broId,".BuildBroDatKey($broDatType, $inst, $diMeIdsA);
  }else
    $ref = ($broDatType === BroDatT_End && !$inst) ? $broId : "$broId,".BuildBroDatKey($broDatType, $inst); # ref = just int BroId for End case with no DiMes and no Inst

  if ($ZonesNowA) {
    # Zones Check
    # See if the Bro has zones defined
    if ($zones = $BroInfoA[$broId][BroI_Zones]) {
      foreach ($ZonesNowA as $zoneId)
        if (InChrList($zoneId, $zones))
          return $ref; # OK, zone set
      # Zones fail
      $broZonesA = ChrListToIntA($zones);
      $broZonesS = $setZonesS = '';
      foreach ($broZonesA as $zoneId)
        $broZonesS .= ', ' . $ZonesA[$zoneId][ZI_Ref];
      foreach ($ZonesNowA as $zoneId)
        $setZonesS .= ', ' . $ZonesA[$zoneId][ZI_Ref];
      return RGError("Bro <i>$name</i> can only be used in zone".(count($broZonesA)>1 ? 's ' : ' ').substr($broZonesS, 2) .
                     ' but the currently set zone'.(count($ZonesNowA)>1 ? 's are ' : ' is ').substr($setZonesS, 2), $p, $noErrorB);
    }
    # No zones set so accept it
  }
  # echo "BroRef() return ref=$ref<br>";
  return $ref;
}

function DError($argsA, $msg, $p, $noErrorB) {
  global $DiMesA, $DiMeNamesA;
  if (!is_array($argsA))
    $argsA = [$argsA];
  foreach ($argsA as $i => $arg)
    if (is_int($arg)) # DiMeId
      $argsA[$i] = $DiMeNamesA[$arg]." ($arg)";
    else if (!strncmp($arg, 'Dim', 3)) {
      $diMeId = (int)substr($arg,3);
      $dimId  = $DiMesA[$diMeId][DiMeI_DimId];
      $argsA[$i] = StrField($DiMeNamesA[$diMeId], '.', 0)." (Dim $dimId)";
    }
  return RGError(vsprintf("Dimension member <i>%s</i> ".$msg, $argsA), $p, $noErrorB);
}

