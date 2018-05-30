<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/Members.php

Lists the SIM Property Members.

History:
17.03.13 Started based on the UK-GAAP-DPL version
25.06.13 Notes updated; Short options removed as that is not needed without the long lists of Directors, Countries etc members.
03.07.13 B R L -> SIM
19.07.13 I t e m -> Member

ToDo
----

*/
require 'BaseSIM.inc';

Head('SIM Members', true);

$NotesB = isset($_POST['Notes']);

echo "<h2 class=c>SIM Property Members</h2>
<p class=c>For a simple list of Properties without the Members see <a href=Props.php>Properties</a>.<br>",
($NotesB ? "For Notes on the meaning of the columns and codes see the end of the report." : "For Notes on the meaning of the columns and codes check the 'Include Notes' option at the end and repeat the report."),
'</p>
<table class=mc>
';

/*

CREATE TABLE IF NOT EXISTS Properties (
  Id        tinyint unsigned not null auto_increment,
  Name      varchar(20)      not null, #
  Label     varchar(50)      not null, #
  RoleId    tinyint unsigned     null, # Roles.Id foreign key
  Primary Key (Id),
  Unique Key (Name)
) Engine = InnoDB DEFAULT CHARSET=utf8;

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
  ReqList varchar(16)           null, # Related List in SS form
  Comment varchar(100)          null, # Comment free text
  Primary Key (Id),
  Unique Key (Name)
) Engine = InnoDB DEFAULT CHARSET=utf8;

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

$n = 0;
$res = $DB->ResQuery("Select P.Id,P.Name,P.Label,R.Role as Role From Properties P Left Join Roles R on R.Id=P.RoleId Order by Id");
while ($o = $res->fetch_object()) {
  if ($n <= 0) {
    $n = 50;
    echo "<tr class='b bg0'><td>Id</td><td>Property&nbsp;Name&nbsp;/&nbsp;Label&nbsp;/&nbsp;Role</td><td>Property Member Name</td><td>Label</td><td class=c>Lev<br>el</td><td class=c>Mem<br>ber<br>Type</td><td class=c>PMem<br>Id</td><td class=c>Sum List</td><td class=c>Mux List</td><td class=c>Additional<br>To List</td><td class=c>Required<br>List</td><td class=c>Member Use</td><td class=c>Member<br>Excl<br>Rule</td><td class=c>Comment</td></tr>\n";
  }
  $propId = (int)$o->Id;
  $name  = $o->Name;
  $label = $o->Label;
  $role  = $o->Role;
  $re2 = $DB->ResQuery("Select * From PMembers Where PropId=$propId Order by Id");
  $numEles = $numRows = $re2->num_rows;
  $piOsA = [];
  $numBreaks = 0;
  while ($piO = $re2->fetch_object()) {
    $piOsA[] = $piO;
    if ((int)$piO->Bits & PMemB_Break)
      ++$numBreaks;
  }
  $re2->free();
  foreach ($piOsA as $ne => $piO) {
    $piId    = (int)$piO->Id;
    $piName  = $piO->Name;
    if (InStr(DOT, $piName)) $piName  = substr($piName, strpos($piName, '.')+1);
    $piLabel = $piO->Label;
    $bits    = (int)$piO->Bits;
    $level   = (int)$piO->Level;
    $indent  = str_pad('', $level*6*2, '&nbsp;'); # 2 nb spaces per level
    $level   = $level ? : '';
    list($type, $uses, $ier) = BitsStrs($bits, $propId);
    $sumList = ListStr($piO->SumList);
    $muxList = ListStr($piO->MuxList);
    $addList = ListStr($piO->AddList);
    $reqList = ListStr($piO->ReqList);
    if (!$ne) {
      $numRows += $numBreaks;
      $start = "<tr class='c brdt2'><td rowspan=$numRows class=top>$propId</td><td rowspan=$numRows class='l top'>$name<br>$label<br>$role</td>";
    }else{
      if ($bits & PMemB_Break) {
        $tA = explode(TAB, $piO->Break);
        $breakName  = $tA[0];
        $breakLabel = $tA[1];
       #echo "<tr class='l s bgg'><td style=padding:'0 3px'><i>$breakName</i></td><td colspan=9 style=padding:'0 3px'><i>$breakLabel</i></td></tr>";
        echo "<tr class='l s bgg'><td>$breakName</td><td colspan=11>$breakLabel</td></tr>";
      }
      $start = '<tr class=c>';
    }
    echo "$start<td class=l>$indent$piName</td><td class=l>$piLabel</td><td>$level</td><td>$type</td><td>$piId</td><td>$sumList</td><td>$muxList</td><td>$addList</td><td>$reqList</td><td>$uses</td><td>$ier</td><td class=l>$piO->Comment</td></tr>\n";
    --$n;
  }
}
echo '</table>
';
if ($NotesB) {
  echo "<div class=mc style=width:1290px>
<h3>Notes</h3>
<h4>Columns</h4>
<table>
<tr class='b bg0'><td>Column Name</td><td>Description</td></tr>
<tr><td>Property&nbsp;Name&nbsp;/&nbsp;Label&nbsp;/&nbsp;Role</td><td>Property Name, Property Label, and Property's Role shown on separate rows. The Members for the Property appear to the right.</td></tr>
<tr><td>Property Member Name</td><td>When being used with Bros the Names shown here are prefixed by the Property Name and a '.' e.g. Entity.# for an EntityRef, Superior.CP etc.<br>Names are indented according to Level for presentation purposes only i.e. the leading spaces do not form part of the Name.</td></tr>
<tr><td>Label</td><td>When being used in reports the Labels shown here are prefixed by the Property Name and a '.' as for Property Member Names</td></tr>
<tr><td>Level</td><td>Level from 0 to 4. A blank entry is equivalent to level 0.</td></tr>
<tr><td>Member Type</td><td>See Member Type Codes below</td></tr>
<tr><td>PMemId</td><td>The Id of the Property Member. PMemIds are used in the following four columns, Sum List, Mux List, Additional To, and Required List, comma separated. A range of PMemIds is shown as two PMemIds separated by ' - ' e.g. 7 - 10</td></tr>
<tr><td>Sum List</td><td>List of Members to be summed, if any, for summing Bros. Can be a specific list of PMemIds or 'Kids' meaning the Members below at a higher level.</td></tr>
<tr><td>Mux List</td><td>List of Members that are mutually exclusive for posting, if any. If a range is used, the range can include the member itself, meaning that no other in the range can be used with the member. 'Kids' means the members below at a higher level.</td></tr>
<tr><td>Additional To</td><td>If a member has an 'Additional To' list the member is an 'Additional' one which cannot be used by itself. It must be used as an additional member to one of the 'Additional To' list members.</td></tr>
<tr><td>Required List</td><td>List of Required members, if any. The Member Use entry (next column) specifies how a Required List entry is used.</td></tr>
<tr><td>Member Use</td><td>See Member Use Codes below</td></tr>
<tr><td>Member Excl Rule</td><td>The Member Excl Rule column defines under what conditions the member can be excluded from use with a Bro via its Members x: property. See the Member Excl Rule Codes below.</td></tr>
<tr><td>Comments</td><td>Information only column</td></tr>
</table>
<h4>DataBase Objects (DBOs)</h4>
<p>SIM Properties and Property Members make use of data stored in the Braiins database on an Agent basis for Entities, People, Addresses, and Contacts via DataBase Objects (DBOs).</p>
<p>DBO data is accessible to Bros and the Report Generator either directly or via Bros.</p>
<p>DBOs are referenced via the alphanumeric reference used in the Database e.g. the Ref or Entity reference for an entity or officer.</p>
<p>DBO references also act as members to describe other data e.g. accounting data applicable to a particular entity or officer.</p>
<p>DBOs provide more natural groupings of information than the XBRL approach, whilst allowing more flexibility, especially as to naming, cross entity usage for Groups, and avoiding any limits on numbers.</p>
<h4>Primary DBOs</h4>
<p>There are four Primary DBO types:
<ul>
<li><p>Entity</p>
<p>The reference for an Entity is the Ref, typically client code, used by the Agent in the BDT. In use as a member this is prefixed by 'Entity.' e.g. Entity.XyzCorp</p>
<p>All data for an entity is available to the RG, as listed in the Braiins BroSets manual for the Bro DboField column.</p></li>
<li><p>Person</p>
<p>The reference for a Person is the Ref as used by the Agent in the BDT. In use as a member this is prefaced by 'Person.' e.g. Person.XyzCorp</p>
<p>All data for a person is available to the RG, as listed in the Braiins BroSets manual for the Bro DboField column.</p></li>
<li><p>Address</p>
<p>Both Entities and People may have one or more Address DBOs.</p>
<p>For an entity or person with just one address the address is referenced as Address.Ref e.g. Address.XyzCorp or Address.BondJ</p>
<p>For an entity or person with multiple addresses, addresses other than the first or main one have a second identifier as used in the BDT in square brackets after the Ref e.g. Address.XyzCorp[RegOffice] or Address.BondJ[BeachHouse]</p>
<p>All data for an address is available to the RG, as listed in the Braiins BroSets manual for the Bro DboField column.</p>
<p>The Address Property includes Members to optionally override some of the data for an Address DBO.</p></li>
<li>Contact</p>
Both Entities and People may have one or more Contact DBOs.</p>
<p>For an entity or person with just one contact the contact is referenced as Contact.Ref e.g. Contact.XyzCorp or Contact.BondJ</p>
<p>For an entity or person with multiple contacts, contacts other than the first or main one have a second identifier as used in the BDT in square brackets after the Ref e.g. Contact.XyzCorp[RegOffice] or Contact.BondJ[BeachHouse]</p>
<p>All data for a contact is available to the RG, as listed in the Braiins BroSets manual for the Bro DboField column.</p>
<p>The Contact Property includes Members to add additional data to a Contact DBO.</p></li>
</ul></p>
<h4>Inherited DBOs</h4>
<p>There are also four Inherited DBO Types for Superior Entities, Subordinate Entities, Third Party Agents and Officers.</p>
<p>Inherited DBOs inherit all the properties of a Primary DBO and potentially add some more.</p>
<p>SIM uses Inherited objects rather than having everything in the primary object to facilitate inclusion/exclusion via Folios or a Bro's ExclProps and AllowProps attributes. (A Bro could use the Members proprty but properties are cleaer and simpler.)</p>
<p>Inherited objects are referenced by the same Ref as their Primary object e.g. 'XyzCorp' for a Subordinate Subsidiary of 'XyzCorp' or 'SmithF' for Officer Fred Smith.</p>
<h4>DBO Bro Usage</h4>
<p>If DBO data is potentially to be changed or added to via posting, then an In-BroSet should include one Bro, called a DBO Posting Bro, for each such DBO. There is no need to add such DBO Posting Bros for DBOs that will not be changed via posting e.g. for a person.</p>
<p>DBO Posting Bros must have a DataType of Boolean and just one UsableProp, the DBO Property, or a Members column value which allows a member for the DBO Property. The members for such Bros can then be set or unset via posting.</p>
<p>When a DBO Property is one of a number of AllowProps e.g. for any Bro other than a DBO Posting Bro, only the Ref member can be used e.g. Officer.BondJ, all other Members being automatically excluded.</p>
<p>When posting, a # Ref must always be used with a DBO Property. In the RG for a summing Bro a DBO Property may be used without a # Ref meaning the sum of all the uses of the Property.</p>
<h4>Entity Specific Properties</h4>
<p>In addition to DBOs which are Entity Specific, SIM allows for normal properties that describe other data but do not themselves store data, to also be Entity Specific.</p>
<p>Entity specific properties provide the SIM equivalent of XBRL tuples in a more flexible way, and also allow for Entity Extension of properties.</p>
<p>Entity specific properties are shown in the members list above with '.#' ending the property name where # is an alphanumeric reference used to identify the property.</p>
<p>A BroRef with an Entity Specific Property Member Ref used without a '.#' ending means the sum of all the members for a summing Bro. This means that there is no need for a separate member for the sum of the kids. No '.#' ending is illegal for a non-summing Bro.</p>
<p>Thera are two types of Entity Specific Properties:
<ul>
<li><p>Ei <b>E</b>ntity specific <b>I</b>nstance</p>
<p>The Ref of an Ei or Entity Instance property identifies a specific instance of the property. There is no limit as to the number of instances, though the taxonomy in use could have such limits.</p>
<p>Ei instance references must be unique in BroRefs for unrelated Bros i.e. Bros with no Related property, or Bros in a different Related Bros list. The same instance reference should be used in BroRefs for related data for related Bros i.e. Bros in the same Related Bros list.</p>
<p>Ei properties are mapped to Taxonomy concrete elements, dimension members, or tuples according to the structure of the taxonomy in question.</p>
<p>'Other' should be used as the # of any non-specific instance. That will be mapped to the appropriate Taxonomy 'Other' or 'Misc' dimension/concrete element if defined.</p></li>
<li><p>Ee <b>E</b>ntity specific <b>E</b>xtension</p>
<p>Ee or Entity Extension properties allow an Entity to add properties specific to the entity which are not 'instance' or tuple like. The Bro Related list property is not applicable to Ee properties.</p>
<p>Ee properties proved a more comparable entity extension ability than the X of XBRL in that the sum of Ee properties (for summing Bros) remain directly comparable across entities.</p>
<p>As at July 2103 no Ee properties have been defined.</p></li>
</ul>
<h4>Member Type Codes</h4>
<table>
<tr class='b bg0'><td>Code</td><td>Meaning</td></tr>
<tr><td class=c>D</td><td>Reference for a <b>D</b>ataBase Obect i.e. an Entity, Person, Address, or Contact.</td></tr>
<tr><td class=c>Ei</td><td><b>E</b>ntity specific <b>I</b>nstance - see Entity Specific Properties above.</td></tr>
<tr><td class=c>Ee</td><td><b>E</b>ntity specific <b>E</b>xtension - see Entity Specific Properties above.</td></tr>
<tr><td class=c>H</td><td>Reference for an in<b>H</b>erited DataBase Object i.e. Superior, Subord, TPA, Officer</td></tr>
<tr><td class=c>O</td><td>Can be used to <b>O</b>verride a DataBase Object value</td></tr>
<tr><td class=c>C</td><td>Ref (ShortName) of one of the SIM <b>C</b>ountries</td></tr>
<tr><td class=c>I</td><td>Ref of one of the <b>I</b>ndustry Codes</td></tr>
<tr><td class=c>L</td><td>Name of one of the SIM <b>L</b>anguages</td></tr>
<tr><td class=c>R</td><td>Ref (ShortName) of one of the SIM <b>R</b>egions</td></tr>
<tr><td class=c>U</td><td>ISO 4217 Code of one of the SIM c<b>U</b>rrencies</td></tr>
<tr><td class=c>X</td><td>Ref (ShortName) of one of the SIM Stock e<b>X</b>changes</td></tr>
<tr><td class=c>RO</td><td><b>R</b>ead <b>O</b>nly i.e. can't be posted to</td></tr>
</table>
<h4>Member Use Codes</h4>
<table>
<tr class='b bg0'><td>Code</td><td>Meaning</td></tr>
<tr><td></td><td>No code means that the member may be used or not as appropriate. It can be included in Bro Members lists.</td></tr>
<tr><td class=c>M</td><td>Properties with 'M' Member Use Code members always have multiple members with an 'M' code. It is Mandatory to include one of the 'M' members. 'M' codes can be followed by '+RL1' or '+RLn' codes.</td></tr>
<tr><td class=c>+RL1</td><td>If the member is included, one additional member chosen from the Required list is mandatory</td></tr>
<tr><td class=c>+RLn</td><td>If the member is included. one or more additional members chosen from the Required list are mandatory</td></tr>
</table>
<h4>Member Excl Rule Codes</h4>
<table>
<tr class='b bg0'><td>Code</td><td>Meaning</td></tr>
<tr><td></td><td>No code means that the member can be excluded from use with a Bro via its Members x: attribute.<br>
  As per DBO Bro Usage above, when a DBO Property is one of a list of UsableProps of a Bro all members other than the 'Ref' member are autmatically excluded.</td></tr>
<tr><td class=c>N</td><td>The member cannot be excluded from use with a Bro via its Members x: attribute.</td></tr>
<tr><td class=c>1</td><td> '1' Member Excl Rule codes apply only to members with 'Additional To' list entries. A group of such members can be reduced in number, potentially to 1, but not to zero unless all members in its Additional To list have been excluded.</td></tr>
<tr><td class=c>ET</td><td>The member is automatically excluded if the Entity's Entity Type excludes use of the member.</td></tr>
</table>
</div>";
}


$NotesChecked = ($NotesB  ? ' checked' : '');
echo "<form method=post>
<p class=c><input id=i3 type=checkbox class=radio name=Notes value=1$NotesChecked> <label for=i3>Check to Include Notes</label></p>
<p class='c mb0'><button class='on m05'>Property Members</button></p>
</form>
</div>
";
Footer(true,true);
##################


function BitsStrs($bits, $propId) {
  $type = $uses = $ier = '';
  for ($b=1; $b<=PMemB_IER_ET; $b*=2) {
    if ($bits & $b)
    switch ($b) {
      # Member Type
      case PMemB_D:     $type  = ' D'; break;
      case PMemB_H:     $type .= ',H'; break;
      case PMemB_Ei:    $type .= ',Ei'; break;
      case PMemB_Ee:    $type .= ',Ee'; break;
      case PMemB_O:     $type .= ',O';  break;
      case PMemB_RO:    $type .= ',RO'; break;
      case PMemB_pYa:   $type .= ',Y'; break;
      case PMemB_Zilch: $type .= ',Z'; break;
      case PMemB_Break: break;
      case PMemB_Sim:
        switch ($propId) {
          case PropId_Regions:   $type .= ' R'; break;
          case PropId_Countries: $type .= ' C'; break;
          case PropId_Currencies:$type .= ' U'; break;
          case PropId_Exchanges: $type .= ' X'; break;
          case PropId_Languages: $type .= ' L'; break;
          case PropId_Industries:$type .= ' I'; break;
          default:               $type .= ' Unknown PropId';
        }
        break;

      # Member Use Bits
      case PMemB_UseM:   $uses  = 'M';     break;
      case PMemB_UseRL1: $uses .= '+RL1';  break;
      case PMemB_UseRLn: $uses .= '+RLn';  break;

      # Member Excl Rule
      case PMemB_IER_N:  $ier   = ' N'; break;
      case PMemB_IER_1:  $ier  .= ',1'; break;
      case PMemB_IER_ET: $ier  .= ',ET'; break;

      default:           $type .= ',Unknown type'; break;
    }
  }
  return [substr($type, 1), $uses, substr($ier, 1)];
}

function ListStr($list) {
  return str_replace(['K', '-', ','], ['Kids', ' - ', ',&nbsp;'], $list);
}
