<?php /* Copyright 2011-2013 Braiins Ltd

InitBraiinsDB.php

Program to initialise the Braiins DB, or part of it anyway.

History:
15.03.11 Started
06.05.11 CompanySecretaryH and DirectorH removed
13.08.11 Headings moved to InitHeadings.php
02.01.12 Extended to create a lot more test/dummy agents, entities, people, members
30.07.12 Added setting Braiins Agent to MB_MASTER
09.11.12 Revised for change to Bro class
29.04.13 Revised for Entities table changes
06.05.13 Revised for Agents table changes and Braiins Funcs arguments change to $colsAA
*/
require 'BaseBraiins.inc';
require Com_Inc.'FuncsBraiins.inc';
require Com_Inc.'ConstantsSIM.inc';

$EntityId = 0;
const Num_People = 10; # in addition to D and C
const Num_Agents =  5;
const Num_EntitiesAgent1      = 100;
const Num_EntitiesOtherAgents = 2;

Head('Braiins DB Initialise');

if (!isset($_POST['Sure']) || strtolower($_POST['Sure']) != 'yes') {
  Form();
  exit;
}

echo "<br><h2>Initialising Braiins DB Tables</h2>\n";

# Truncate
echo "<b>Truncating:</b><br>\n";
ZapTable('Agents');
ZapTable('AgentData');
ZapTable('AgentTrans');
ZapTable('BDTsessions');
ZapTable('Bros');
ZapTable('BroTrans');
ZapTable('DBLog');
ZapTable('Entities');
ZapTable('EntityTrans');
ZapTable('People');
ZapTable('Registry');
ZapTable('Refs');
ZapTable('ERefs');
ZapTable('Visits');
ZapTable('VisitTrans');
ZapTable('Locks');

$DB->autocommit(false); # Start transaction

##########
# Agents #
##########
echo "<br><b>Braiins</b><br>\n";

/* AddAgent($entityColsAA, $adminColsAA, $bits, $descr='') # Add an Agent to Braiins.Agents plus the Agent's Entity to Braiins.Entities
where
  $entityColsAA holds the column values for creation of the Agent's Entity
    EName
    Ident
    CtryId
    ETypeId

  $adminColsAA holds the column values for creation of the Administrator (Manager) of the Agent's Entity
    GName
    FName
    Title
    DName
    Email
    CtryId
    Level
    PW
    SexN    Can be omitted if unknown
    RoleN   "
    DeptN   "

  $bits are Agents.Bits bits e.g. AB_TaxAgent
  $descr is an optional description for the Agent/Entity stored with the Entity
*/

$entityColsAA = [
  'EName'   => 'Braiins Ltd',
  'Ident'   => '1234567890',
  'CtryId'  => CTRY_UK,
  'ETypeId' => ET_PrivateLtdCo];

$adminColsAA = [
  'GName'  => 'David',
  'FName'  => 'Hartley',
  'Title'  => 'Mr',
  'DName'  => 'David Hartley',
  'Email'  => 'david@braiins.com',
  'CtryId' => CTRY_UK,
  'Level'  => MLevel_Max,
  'PW'     => 'Bond007',
  'SexN'   => MaleN
];

if (($agentId = AddAgent($entityColsAA, $adminColsAA, AB_TaxAgent)) != BID)  die('Braiins Ltd AgentId not 1');
echo "Braiins Ltd Agent and Entity added with Id=$agentId<br>";
if (DJH != $DB->OneQuery("Select MngrId from Entities Where Id=$agentId")) die('David Hartley Id != DJH');
echo " David Hartley added with Id=1<br>";

# Charles
$colsAA = [
  'AgentId'=> BID,
  'GName'  => 'Charles',
  'FName'  => 'Woodgate',
  'Title'  => 'Mr',
  'DName'  => 'Charles Woodgate',
  'Email'  => 'charles@braiins.com',
  'CtryId' => CTRY_UK,
  'Level'  => MLevel_Max,
  'PW'     => 'Bond007',
  'Bits'   => AP_All,
  'SexN'   => MaleN
];
if (AddMember($colsAA) !== CWW) die('Charles Woodgate Id != CWW');
echo "Charles Woodgate added with Id=2<br>";

# Fred
$colsAA = [
  'AgentId'=> BID,
  'GName'  => 'Fred',
  'FName'  => 'Braiins',
  'Title'  => 'Mr',
  'DName'  => 'Fred Braiins',
  'Email'  => 'fred@braiins.com',
  'CtryId' => CTRY_UK,
  'Level'  => MLevel_Max,
  'PW'     => 'Bond007',
  'Bits'   => AP_All,
  'SexN'   => MaleN
  #RoleN
  #DeptN
];
if (AddMember($colsAA) !== 3) die('Fred Braiins Id != 3');
echo "Fred Braiins added with Id=3<br>";

# Add People used with AAAAA as Officers
$peoColsAA = [
  'AgentId'=> BID,
  'GName'  => 'C C',
  'FName'  => 'Smith',
  'Title'  => 'Mr',
  'DName'  => 'C C Smith',
  'CtryId' => CTRY_UK,
  'SexN'   => MaleN
];
AddPerson($peoColsAA); echo $peoColsAA['DName'],' added',BR;

$peoColsAA['GName'] = 'A A';
$peoColsAA['FName'] = 'Green';
$peoColsAA['DName'] = 'A A Green';
AddPerson($peoColsAA); echo $peoColsAA['DName'],' added',BR;

$peoColsAA['GName'] = 'B B';
$peoColsAA['FName'] = 'Black';
$peoColsAA['DName'] = 'B B Black';
AddPerson($peoColsAA); echo $peoColsAA['DName'],' added',BR;

$peoColsAA['GName'] = 'T T';
$peoColsAA['FName'] = 'Three';
$peoColsAA['DName'] = 'T T Three';
AddPerson($peoColsAA); echo $peoColsAA['DName'],' added',BR;

# An initial $DGsAllowed for all excpet ExSmall and Non U ones
$DGsAllowed = 0;
for ($dg=0,$bit=1; $dg<DG_Num; ++$dg, $bit *= 2) {
  $t = 0;
  switch ($dg) {
   #case DG_Group:      $t = 1; break; #  1 Group, 2 Consol, 27 Subsidiaries  Only Dimension Group with both E and U properties.
    case DG_Restated:   $t = 1; break; #  3 Restated
    case DG_Excepts:    $t = 1; break; #  5 ExceptAdjusts, 6 AmortAdjusts
  # case DG_BizSegs:    $t = 1; break; #  7 BizSegs
    case DG_Provisions: $t = 1; break; #  8 Provisions
    case DG_IFAs:       $t = 1; break; #  9 IFAClasses
    case DG_TFAs:       $t = 1; break; # 10 TFAClasses,  11 TFAOwnership
    case DG_FAIs:       $t = 1; break; # 12 FAIHoldings, 13 FAITypes
    case DG_Pensions:   $t = 1; break; # 15 PensionSchemes, 16 ShareBasedPaymentSchemes
    case DG_FinInstrs:  $t = 1; break; # 17 FinInstrValueType, 18 FinInstrCurrentNonCurrent, 19 FinInstrLevel, 20 FinInstrMvts
    case DG_Maturities: $t = 1; break; # 21 MPeriods
  # case DG_Acqs:       $t = 1; break; # 22 Acqs, 23 AcqAssetsLiabilities
  # case DG_Disposals:  $t = 1; break; # 24 Disposals
    case DG_JVs:        $t = 1; break; # 25 JVs
    case DG_Assocs:     $t = 1; break; # 26 Assocs
    case DG_OtherInts:  $t = 1; break; # 28 OtherInterestsOrInvests
    case DG_Countries:  $t = 1; break; # 39 Countries                         Off: Base = UK for Wales, England etc
   #case DG_Currencies: $t = 1; break; # 40 Currencies                        Off: Base = GBP
   #case DG_Languages:  $t = 1; break; # 42 Languages                         Off: Base => English for Wales or Welsh?
  }
  if ($t)
    $DGsAllowed |= $bit;
}

# temporary ......
$eTypeId = ET_PrivateLtdCo;
$eTypePLC = 9;

$bitsm = MB_OK | MB_MASTER | EB_Active;
$bitsd = MB_OK | EB_Demo   | EB_Active;

/* AddEntity($colsAA, $descr='', $crComment='', $credits=0)  Add an Entity to Braiins.Entities
   ---------
   Returns EntityId or false on AgentId + Ref duplicate
   Duplicate names are not prevented. Perhaps they should be.

Where
  $colsAA =
    AgentId
    Ref
    EName
    Ident
    CtryId
    ETypeId
    ESizeId    Can be omitted if the Entity doesn't have a size
    Level
    MngrId
    Bits       MB_OK is set by the function
    DGsAllowed

  $descr is an optional description of the entity
  $credits are the credits (<= 0) charged when setting up the entity, with details in $crComment EntityType Credits	EntitySize Credits{	Dim Group	DG credits...}
*/

$ident = 1234567890;


$colsAA = [
  'AgentId' => $agentId,
  'Ref'     => 'Braiins-UK-GAAP-DPL',
  'EName'   => 'Braiins UK-GAAP-DPL',
  'Ident'   => ++$ident,
  'CtryId'  => CTRY_UK,
  'ETypeId' => $eTypeId,
  'ESizeId' => ES_Small,
  'Level'   => ELevel_Max,
  'MngrId'  => CWW,
  'Bits'    => $bitsm,
  'DGsAllowed' =>  $DGsAllowed];
AddEntity($colsAA, 'Braains Master UK-GAAP Private Limited Company');

$colsAA['Ref']   = 'AAAAA';
$colsAA['EName'] = 'AAAAA Limited';
$colsAA['Ident'] = ++$ident;
AddEntity($colsAA, 'HMRC Demo');

$colsAA['Ref']     = 'BBBBB';
$colsAA['EName']   = 'BBBBB Limited';
$colsAA['Ident']   = ++$ident;
$colsAA['ESizeId'] = ES_Medium;
AddEntity($colsAA, 'HMRC Demo');

$colsAA['Ref']     = 'CCCCC';
$colsAA['EName']   = 'CCCCC Limited';
$colsAA['Ident']   = ++$ident;
$colsAA['ESizeId'] = ES_Large;
AddEntity($colsAA, 'HMRC Demo');

$colsAA['Ref']     = 'DDDDD';
$colsAA['EName']   = 'DDDDD PLC';
$colsAA['Ident']   = ++$ident;
$colsAA['ETypeId'] = $eTypePLC;
$colsAA['ESizeId'] = ES_Large;
AddEntity($colsAA, 'HMRC Demo');

echo "Braiins Entities Braiins-UK-GAAP-DPL, AAAAA, BBBBB, CCCCC, DDDDD added. AgentId=$agentId, MngrId=",CWW,"<br>\n";

for ($n=1; $n<=Num_Agents; ++$n) {
  $aName = "Test Agent $n";
  $entityColsAA = [
    'EName'   => $aName,
    'Ident'   => ++$ident,
    'CtryId'  => CTRY_UK,
    'ETypeId' => ET_PrivateLtdCo];
  $adminColsAA = [
    'GName'  => "Test$n",
    'FName'  =>  "Person$n",
    'Title'  => 'Ms',
    'DName'  => "Test$n Person$n",
    'Email'  => "test$n@braiins.com",
    'CtryId' => CTRY_UK,
    'Level'  => MLevel_Max,
    'PW'     => 'Bond007',
    'SexN'   => FemaleN
  ];
  $agentId = AddAgent($entityColsAA, $adminColsAA, AB_TaxAgent);
  $mngrId = $DB->OneQuery("Select Id From People Where AgentId=$agentId");
  echo " Agent 'Test Agent $n' AgentId=$agentId with Administrator 'Test$n Person$n' MngrId=$mngrId added<br>";
  # djh?? Until there are demo entiities for AddAgent() to copy to the new Agent, add a new empty entity

  $numEntities = $n==1 ? Num_EntitiesAgent1 : Num_EntitiesOtherAgents;
  for ($j=1; $j<=$numEntities; ++$j) {
    $colsAA = [
      'AgentId' => $agentId,
      'Ref'     => "TE$agentId-$j",
      'EName'   => "Test Entity $j for Agent |$aName|",
      'Ident'   => ++$ident,
      'CtryId'  => CTRY_UK,
      'ETypeId' => $eTypeId,
      'ESizeId' => ES_Small,
      'Level'   => ELevel_Min,
      'MngrId'  => $mngrId,
      'Bits'    => $bitsd,
      'DGsAllowed' =>  $DGsAllowed];
    AddEntity($colsAA);
    echo "$ident with Agent $aName, AgentId=$agentId<br>\n";
  }
}


$DB->commit();

echo "<br>\n";

Footer();
exit;

function ZapTable($table) {
  global $DB;
  echo " $table<br>\n";
  $DB->StQuery("Truncate Table $table");
  #$DB->StQuery("Delete from DBLog Where Mensa='$table'");
}

function Form() {
  echo <<< FORM
<div style=margin-left:10em>
<h2>Initialise the Braiins DB</h2>
<p>Running this will reset and initialise the Braiins DB for everything except Headings and Preferences.</p>
<form method=post>
Sure? (Enter Yes if you are.) <input name=Sure size=3 value=Yes> <button class=on>Go</button>
</div>
</form>
FORM;
  Footer(false);
}

