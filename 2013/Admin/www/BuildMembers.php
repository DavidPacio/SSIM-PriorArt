<?php /* Copyright 2011-2013 Braiins Ltd

BuildMembers.php

Builds SIM.PMembers from txt file

History:
16.03.13 Written in UK-GAAP-DPL version as BuildPItems.php and ExpDiMes.txt
15.04.13 Re-written as BuildItems.php using ExpItems.txt from the SIM Roles Folios Property Items.xlsx SS
03.07.13 B R L -> SIM
19.07.13 I t e m -> PMem | PMember | Member

*/
require 'BaseSIM.inc';

Head('Build SIM Members', true);

$file = 'MembersI.txt';
$linesA = file($file, FILE_IGNORE_NEW_LINES);
/*
CREATE TABLE IF NOT EXISTS PMembers (
  Id      smallint unsigned not null auto_increment, # Used as PMemId
  PropId  tinyint  unsigned not null, # Properties.Id foreign key
  Name    varchar(50)       not null, # Includes Property "Name." prefix
  Label   varchar(120)      not null, #
  Break   varchar(75)           null, # Name	Label of a break or separator row stored with next PMem
  Bits    smallint unsigned not null, # Property Member bits: PMemB_... defined in ConstantsSIM.inc
  Level   tinyint  unsigned not null, # Level of the PMem from 0 upwards
  SumList varchar(8)            null, # Sum List in SS form with Kids reduced to K
  MuxList varchar(12)           null, # Sum List in SS form with Kids reduced to K
  AddList varchar(8)            null, # Additional To list in SS form
  ReqList varchar(16)           null, # Required List in SS form
  Comment varchar(120)          null, # Comment free text
  Primary Key (Id),
  Unique Key (Name)
) Engine = InnoDB DEFAULT CHARSET=utf8;

Max length PMem Name = 45
Max length Label   = 112
Max length Break   = 50
Max length SumList = 5
Max length MuxList = 11
Max length AddList = 7
Max length ReqList = 11
Max length Comment = 111

# PMembers.Bits
# -------------              Bit
const PMemB_D       =    1; #  0 D  DBO Ref
const PMemB_H       =    2; #  1 H  inHerited  DBO Ref
const PMemB_Ei      =    4; #  2 Ei Entity specific Instance
const PMemB_Ee      =    8; #  3 Ee Entity specific Extension
const PMemB_O       =   16; #  4 O  Override of DBO property
const PMemB_RO      =   32; #  5 RO Report Only = usable for reporting but not for posting
const PMemB_pYa     =   64; #  6 Y  PYA (Restated)
const PMemB_Zilch   =  128; #  7 Z  Not ever user selectable being reserved for Braiins operation e..g. Unallocated
const PMemB_Break   =  256; #  8    Break = start of a Group within the Property Member listing, shown by a different style row with Name and Label captions stored in the Break col of PMembers
const PMemB_Sim     =  512; #  9 ?  SIM type with specific one as per PropId: PropId_Regionss, PropId_Countries, PropId_Currencies, PropId_Exchanges, PropId_Languages, PropId_Industries
# Member Use Bits
const PMemB_UseM   =  1024; # 10 M  Properties with 'M" Member Use Codes always have multiple members with an 'M' code. It is Mandatory to include one of the 'M' members. 'M' codes can be followed by '+RL1' or '+RLn' codes.
const PMemB_UseRL1 =  2048; # 11 +RL1  If the member is included, one additional member chosen from the related list is mandatory
const PMemB_UseRLn =  4096; # 12 +RLn  If the member is included. one or more additional members chosen from the related list are mandatory
# Member Excl Rule Codes
const PMemB_IER_N  =  8192; # 13 N  The member cannot be excluded from use with a Bro via its Member x: attribute unless the member is an 'A' Member Use member for which all members in its Required List have been excluded. All 'RO' (Read Only) Type members are also 'N' Member Excl Rule members.
const PMemB_IER_1  = 16384; # 14 1  '1' Member Excl Rule codes apply only to members with 'A' Member Use codes. A group of such members can be reduced in number, potentially to 1, but not to zero unless all members in its Required List have been excluded.
const PMemB_IER_ET = 32768; # 15 ET The member is automatically excluded if the Entity's Entity Type excludes use of the member.
*/
echo "<br><b>Building the SIM Property Members table</b><br>";
$maxLenIName = $maxLenLabel = $maxLenBreak = $maxLenMuxList = $maxLenAddList = $maxLenSumList = $maxLenReqList = $maxLenComment =
$propId = $pMemId = $prevLineBreak = 0;
$DB->StQuery("Truncate PMembers");
$DB->autocommit(false);
foreach ($linesA as $row => $line) {
  ++$row;
  # echo "Row $row Line-$line<br>";
  if (strlen($line) < 14)
    continue; # blank line
  $pMemA = explode(TAB, $line);
  #  0              1                  2      3     4         5       6        7         8              9            10       11              12        13
  # Id	Property Name	Property Member Name	Label	Level	PMem Type	PMem Id	Sum List	Mux List	Additional To	Required List	Member Use	Member Excl Rule	Comments
  if (!$pMemA[0] && !$pMemA[1] && $pMemA[2] && $pMemA[3] && !$pMemA[6]) {
    $prevLineBreak = 1;
    $breakName  = trim($pMemA[2]);
    $breakLabel = trim($pMemA[3]);
    continue;
  }
  ++$pMemId;
  $bits = 0;
  $n = count($pMemA);
  if ($n != 14) die("field count $n wrong in row $row $line");
  if ($v = $pMemA[0]) {
    # New Property
    $propId = (int)$v;
    $pName  = trim($pMemA[1]);
  }else if (!$propId)
    die("No PropId for row $row $line");

  $iName = str_replace(['None.None', 'Unallocated.Unallocated'], ['None', 'Unallocated'], "$pName.".trim($pMemA[2], '  ')); # space and nb space
  $label = trim($pMemA[3], '  '); # space and nb space
  # Level
  $level = ($v = $pMemA[4]) ? (int)$v : 0;
  $set = "PropId=$propId,Name='$iName',Label=\"$label\",Level=$level";

  # PMem Type
  if ($v = $pMemA[5]) {
    $typesA = explode(COM, $v);
    foreach ($typesA as $t) {
      switch ($t) {
        case 'D':  $bits |= PMemB_D;  break;
        case 'H':  $bits |= PMemB_H;  break;
        case 'Ei': $bits |= PMemB_Ei; break;
        case 'Ee': $bits |= PMemB_Ee; break;
        case 'O':  $bits |= PMemB_O;  break;
        case 'R':
        case 'C':
        case 'U':
        case 'X':
        case 'L':
        case 'I':  $bits |= PMemB_Sim; break;
        case 'RO': $bits |= PMemB_RO;  break;
        case 'Y':  $bits |= PMemB_pYa; break;
        case 'Z':  $bits |= PMemB_Zilch; break;
        default: die("Unknown PMem Type $t in DataType $v in row $row $line");
      }
    }
  }

  # PMemId
  if (($v = (int)$pMemA[6]) !== $pMemId) {
    if ($v !== 999)
      die("PMemId of $v not the expected $pMemId in row $row $line");
    $pMemId = $v;
    $set .= ",Id=$v";
  }
  # SumList
  if ($v = $pMemA[7]) {
    $list = str_replace(['Kids', SP], ['K',''], $v);
    $set .= ",SumList='$list'";
    $maxLenSumList = max($maxLenSumList, strlen($list));
  }

  # MuxList
  if ($v = $pMemA[8]) {
    $list = str_replace(['Kids', SP], ['K',''], $v);
    $set .= ",MuxList='$list'";
    $maxLenMuxList = max($maxLenMuxList, strlen($list));
  }

  # Additional To List
  if ($v = $pMemA[9]) {
    $list = str_replace(SP,'', $v);
    $set .= ",AddList='$list'";
    $maxLenAddList = max($maxLenAddList, strlen($list));
  }

  # ReqList
  $reqList = 0;
  if ($v = $pMemA[10]) {
    $reqList = str_replace(SP,'', $v);
    $set .= ",ReqList='$reqList'";
    $maxLenReqList = max($maxLenReqList, strlen($reqList));
  }

  # Member Use Bits
  # const PMemB_UseM   = 1024; # 10 M  Properties with 'M" Member Use Codes always have multiple members with an 'M' code. It is Mandatory to include one of the 'M' members. 'M' codes can be followed by '+RL1' or '+RLn' codes.
  # const PMemB_UseRL1 = 2048; # 11 +RL1  If the member is included, one additional member chosen from the related list is mandatory
  # const PMemB_UseRLn = 4096; # 12 +RLn  If the member is included. one or more additional members chosen from the related list are mandatory
  if ($v = $pMemA[11])
    foreach (explode('+', $v) as $t)
      if ($t)
      switch ($t) {
        case 'M':   $bits |= PMemB_UseM;   break;
        case 'RL1': $bits |= PMemB_UseRL1; if ($bits & PMemB_UseRLn) die("Member $pMemId has Member Use codes of both +RL1 and +RLn but only one is allowed");break;
        case 'RLn': $bits |= PMemB_UseRLn; if ($bits & PMemB_UseRL1) die("Member $pMemId has Member Use codes of both +RL1 and +RLn but only one is allowed");break;
        default: die("Unknown Member Use code $t in Member Use $v in row $row $line");
      }
  # Check reqList vs +RL1 or +RLn
  if ($reqList && !($bits & (PMemB_UseRL1 | PMemB_UseRLn))) die("PMem $pMemId has a Required List but no +RL1 or +RLn Member Use code");

  # Member Excl Rule Codes
  # const PMemB_IER_N  =  8192; # 13 N  The member cannot be excluded from use with a Bro via its Member x: attribute unless the member is an 'A' Member Use member for which all members in its Required List have been excluded. All 'RO' (Read Only) Type members are also 'N' Member Excl Rule members.
  # const PMemB_IER_1  = 16384; # 14 1  '1' Member Excl Rule codes apply only to members with 'A' Member Use codes. A group of such members can be reduced in number, potentially to 1, but not to zero unless all members in its Required List have been excluded.
  # const PMemB_IER_ET = 32768; # 15 ET The member is automatically excluded if the Entity's Entity Type excludes use of the member.
  if ($v = $pMemA[12])
    foreach (explode(COM, $v) as $t)
      switch ($t) {
        case 'N':  $bits |= PMemB_IER_N;  break;
        case '1':  $bits |= PMemB_IER_1;  break;
        case 'ET': $bits |= PMemB_IER_ET; break;
        default: die("Unknown Member Excl Rule Code $t in $v in row $row $line");
      }

  # Comment
  if ($v = $pMemA[13]) {
    $comment = $v;
    $set .= ',Comment='.SQ.addslashes($comment).SQ;
    $maxLenComment = max($maxLenComment, strlen($comment));
  }
  $maxLenIName = max($maxLenIName, strlen($iName));
  $maxLenLabel = max($maxLenLabel, strlen($label));
  if ($prevLineBreak) {
    $break = $breakName.TAB.$breakLabel;
    $set .= ",Break='$break'";
    $bits |= PMemB_Break;
    $maxLenBreak = max($maxLenBreak, strlen($break));
  }
  $DB->StQuery("Insert into PMembers Set $set,Bits=$bits");
  echo "$pMemId $iName<br>";
  $prevLineBreak = 0;
}
$DB->commit();
echo "<br>Done<br>Max length PMem Name = $maxLenIName<br>
Max length Label = $maxLenLabel<br>
Max length Break = $maxLenBreak<br>
Max length SumList = $maxLenSumList<br>
Max length MuxList = $maxLenMuxList<br>
Max length AddList = $maxLenAddList<br>
Max length ReqList = $maxLenReqList<br>
Max length Comment = $maxLenComment<br>";

Footer();

