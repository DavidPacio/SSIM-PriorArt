<?php /* Copyright 2011-2013 Braiins Ltd

FuncsBraiins.inc

Braiins specific functions, mostly for DB ops, used by both Braiins and Admin modules.
Default DB expected to be Braiins

int/false AddMember($colsAA, $role='', $descr='')                              Returns MemId on success, false on duplicate email

int/false AddPerson($colsAA, $role='', $descr='')                              Returns PeoId on success, false on duplicate email

int AddAgent($entityColsAA, $adminColsAA, $bits, $descr='')                    Returns AgentId

int AddEntity($colsAA, $descr='', $crComment='', $credits=0)                   Returns EntityId or false on AgentId + Ref duplicate

int AddAgentTran($agentId, $typeN, $logId=0, $comment=0, $credits=0, $moreA=0) Returns TranId
    UpdateAgentCredits($agentId, $credits)                                     Atomic op not logged to DBLog as Agents.Credits can be recreated from transactions.
int AddEntityTran($entityId, $entYear, $appN, $actioN1, $info1=0, $actioN2=0, $info2=0) Returns TranId

int UpdateEntity($entityId, $col, $val, $pA=0)                                 Returns 0 if no change or 1 if the Entities record was updated

History:
04.01.12 Created based on code in Braiins Reg.php and Admin InitDB.php
30.07.12 Added $bits arg to AddEntity()
02-06.05.13 Revised for BRL Agents, AgentData, Entities and People table changes, addition of an Agent Entity, plus args lists changed to use of $colsAA
*/

/*
############
## People ## People in any way connected with Braiins, including Members
############
# Type: Master
CREATE TABLE IF NOT EXISTS People (
  Id      mediumint unsigned not null auto_increment, # PeoId or MemId or PeopleId
  AgentId mediumint unsigned not null, # Agents.Id Foreign key; 0 if a newsletter subscriber but nothing else
  GName   varchar(20)        not null, #   Given name  = First name in US usage
  FName   varchar(30)        not null, #   Family name = Last name in US usage
  Title   varchar(20)        not null, #   Title
  DName   varchar(50)        not null, #   Display Name
  Email   varchar(40)            null, #   Email address which can be null for a person added as an Officer or Contact
  CtryId  smallint  unsigned not null, #   Foreign key BRL.Countries.Id Country
  Bits    mediumint unsigned not null default 2, # Person options as bit settings as below. MB_OK set by default.
  Ref     varchar(40)            null, #   Person reference mainly for use with BRL Properties e.g. an Officer or Contact ref.
  SexN    tinyint   unsigned     null, #   MaleN or FemaleN with NONE or null meaning undefined
  RoleN   tinyint   unsigned     null, # M Role enum ROL_SnrPartner, ROL_Partner, ROL_Manager, ROL_Senior, ROL_Staff, ROL_Owner, ROL_CEO, ROL_FinDirector, ROL_Director, ROL_FinControl, ROL_Contractor, ROL_Consultant, ROL_Other
  DeptN   tinyint   unsigned     null, # M Dept enum DEP_Management, DEP_Finance, DEP_Accounting, DEP_Audit, DEP_Tax, DEP_External, DEP_Other
  Level   tinyint   unsigned     null, # M Level re access control, from MLevel_Min (1) to MLevel_Max (9)
  PW      binary(64)             null, # M Password, 8 - 16 case sensitive chars encrypted via GenPw() in ClassBDB.inc as an SHA512 hash() with salt based on Id so same password if used more than once is different in the DB.
  Fails   tinyint   unsigned     null, # M Login failure count
  Data    text                   null, #   Additional jsonised data as below, retrieved via Person($o) as an Object; can be null
  # sys stuff
  EditT timestamp not null default 0,
  AddT  timestamp not null default 0,
  Primary Key (Id),    # unique
   Unique Key (Email), # unique
   Unique Key AgentIdRef (AgentId, Ref), # unique. Ref is null so nulls don't cause duplicate key errors
          Key (AgentId)                  # not unique
) Engine = InnoDB Default Charset=utf8;

Bits
----
Bits 0 and 1 are Master DB Table Standard Bits
                        Bit Byte
  MB_MASTER     =   4; #  2 0 Set for Person (a Member) on BDT boot if Agent or Entity is a Master.
  PB_Member     =   8; #  3 0 Set if a Member of an Agency. Unset with AgentId set if added by an Agent as a person for use as an Officer or Contact
  PB_FNameFirst =  16; #  4 0 Set if family name comes first as for Asian names
  PB_EmailOK    =  32; #  5 0 Set when email address has been confirmed
  PB_News       =  64; #  6 0 Set if to receive newsletter
                       #  7 0 Spare
  Access Permission bits for Person who is a Member, which work in conjunction with Levels
  AP_Read    =    256; #  8 1 Read              Read or View incl TB, Data Trail, Financial Statements, Change Entity. Turning this off effectively prevents a member from doing anything.
  AP_AccData =    512; #  9 1 Accounting Data   Add (Post) and Edit current entity accounting data, incl Data Check
  AP_Entity  =   1024; # 10 1 Entities          Set to Final, Start Next Year, Add, Edit Entities. Start Next Year and Add involve a credits charge. Edit can do also.
  AP_Up      =   2048; # 11 1 Upload            Upload Accounting Data
  AP_Down    =   4096; # 12 1 Download          Download Financial Statements
  AP_Agent   =   8192; # 13 1 Agent Data        View and Edit Agent Data - Snapshot, Admin Reports, Account Details, Headings, Layout Options, and Download Export Format
  AP_Members =  16384; # 14 1 Members           Add/Edit Members
  AP_Credits =  32768; # 15 1 Purchase Credits  Purchase Credits
  AP_Delete  =  65536; # 16 2 Delete            Reset Entities, Delete Entities, Delete Members
  AP_Admin   = 131072; # 17 2 Administrator     All of the above plus Set/Unset these Access Permissions for Self and Others. There must always be at least one Administrator Member with this permission set.
  AP_All     =0x3FF00; #      All the AP bits except for AP_Compile
  Braiins Member Access Permissions
  AP_Compile = 262144; # 18 2 Able to compile formats
  AP_First   = AP_Read;
  AP_Last    = AP_Admin;

Data =
  AddressesA  i [] of AddressIds which apply to the Person
  ContactsA   i [] of ContactIds which apply to the Person
  Role        string  Role or Position. Can be overridden for a Member via Member's RoleN. Required for member if RoleN = ROL_Other
  Descr       string  Description of the person


# AddMember($colsAA, $role='', $descr='') {
  ---------
Where
  $colsAA =
    AgentId
    Ref    Optional. Will be generated if not passed.
    GName
    FName
    Title
    DName
    Email
    CtryId
    Level
    PW
    Bits   MB_OK | PB_Member | PB_EmailOK are set by function and the default APs = Read, Acc Data, Up, Down are set if no APs are set
    SexN   Can be omitted if unknown
    RoleN  "
    DeptN  "
  $role    optional Role or Position. Can be overridden for a Member via Member's RoleN. Required for member if RoleN = ROL_Other
  $descr   optional description of the person

  Returns MemId on success, false on duplicate email
  There is no duplicate Ref error return as a Ref that is ok is generated
*/

function AddMember($colsAA, $role='', $descr='') {
  global $DB;
  # Get a People Table Write lock. With lock can do duplicate key check on Ref separately from the possible Email one checked for via the DupInsertMaster() call.
  $DB->GetBDbLock(T_B_People, 0, Lock_Write, function($why){Error("Sorry, Members cannot be added as $why.");});

  # turn OK, member and email confirmed bits on
  # and add default APs = Read, Acc Data, Up, Down if no APs are set
  $colsAA['Bits'] |= MB_OK | PB_Member | PB_EmailOK | (($colsAA['Bits'] & AP_All) ? 0 : AP_Read | AP_AccData |AP_Up | AP_Down);
  $colsAA['GName'] = ucwords($colsAA['GName']); # Not FName in case of 'de Lauren'
  $colsAA['Ref']   = GeneratePeopleRef($colsAA);
  if ($role || $descr) {
    # Data
    $dataA = [
      [], # AddressesA  i [] of AddressIds which apply to the Person
      [], # ContactsA   i [] of ContactIds which apply to the Person
      $role, # Role  string  Role or Position. Can be overridden for a Member via Member's RoleN. Required for member if RoleN = ROL_Other
      $descr # Descr string  Description of the person
    ];
    $colsAA['Data'] = json_encode($dataA, JSON_NUMERIC_CHECK);
  }
  $pw = $colsAA['PW']; # For PW to be added once Id is known
  unset($colsAA['PW']);
  $retA = $DB->DupInsertMaster(T_B_People, $colsAA); # Could fail (return false) on duplicate email tho should not fail on duplicate Ref due to lock and above check
  $DB->RelBDbLock(T_B_People, 0, Lock_Write); # Release just the People table lock
  if ($retA === false) # retuns false on duplicate email
    return false;
  list($memId, $logId) = $retA;
  $DB->StQuery("Update People Set PW='".$DB->real_escape_string(GenPw($pw, $memId))."' Where Id=$memId"); # Done afterwards to ensure have right Id
  # Agent Tran
  AddAgentTran($colsAA['AgentId'], ATT_AddMember, $logId);
  return $memId;
}

/* AddPerson($colsAA, $role='', $descr='') {
   ---------
Where
  $colsAA =
    AgentId
    Ref    Optional. Will be generated if not passed.
    GName
    FName
    Title
    DName
    Email  Can be omitted
    CtryId
    Bits   Optional, MB_OK is set by function, or the insert defaults to MB_OK
    SexN   Can be omitted if unknown
  $role    optional Role or Position
  $descr   optional description of the person

  Returns PeoId on success, false on duplicate email
  There is no duplicate Ref error return as a Ref that is ok is generated
*/

function AddPerson($colsAA, $role='', $descr='') {
  global $DB;
  # Get a People Table Write lock. With lock can do duplicate key check on Ref separately from the possible Email one checked for via the DupInsertMaster() call.
  $DB->GetBDbLock(T_B_People, 0, Lock_Write, function($why){Error("Sorry, People cannot be added as $why.");});

  # Turn OK bit on if $colsAA['Bits'] exists o'wise leave it as the Insert default is MB_OK
  if (isset($colsAA['Bits'])) $colsAA['Bits'] |= MB_OK;
  $colsAA['GName'] = ucwords($colsAA['GName']); # Not FName in case of 'de Lauren'
  $colsAA['Ref']   = GeneratePeopleRef($colsAA);
  if ($role || $descr) {
    # Data
    $dataA = [
      [], # AddressesA  i [] of AddressIds which apply to the Person
      [], # ContactsA   i [] of ContactIds which apply to the Person
      $role, # Role  string  Role or Position. Can be overridden for a Member via Member's RoleN. Required for member if RoleN = ROL_Other
      $descr # Descr string  Description of the person
    ];
    $colsAA['Data'] = json_encode($dataA, JSON_NUMERIC_CHECK);
  }
  $retA = $DB->DupInsertMaster(T_B_People, $colsAA); # Could fail (return false) on duplicate email tho should not fail on duplicate Ref due to lock and above check
  $DB->RelBDbLock(T_B_People, 0, Lock_Write); # Release just the People table lock
  if ($retA === false) # retuns false on duplicate email
    return false;
  list($peoId, $logId) = $retA;
  # Agent Tran
  AddAgentTran($colsAA['AgentId'], ATT_AddPerson, $logId);
  return $peoId;
}


/* AddAgent($entityColsAA, $adminColsAA, $bits, $descr='') # Add an Agent to Braiins.Agents plus the Agent's Entity to Braiins.Entities
   --------
   Returns AgentId
   djh?? This does not prevent the addition of Agents with the same name.

where
  $entityColsAA holds the column values for creation of the Agent's Entity
    EName
    Ident
    CtryId
    ETypeId
  # ESizeId     Left as 0 even if the ETypeId chosen does have sizes. Gets sorted out if the Entity is upgraded to EB_Active.
  # DGsAllowed  Left as 0. Gets sorted out if the Entity is upgraded to EB_Active.
  # Set by the function
  # AgentId
  # Ref     'Agent'
  # Level   ELevel_Max
  # MngrId  Set after adding the Admin member
  # Bits    MB_OK and EB_Dbo
  # Data

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
  # Set by the function:
  # AgentId
  # Ref     is generated by AddMember()
  # Bits    MB_OK | PB_Member | PB_EmailOK | AP_All which includes AP_Admin
  # Data

  $bits are Agents.Bits bits e.g. AB_TaxAgent
  $descr is an optional description for the Agent/Entity stored with the Entity

*/

function AddAgent($entityColsAA, $adminColsAA, $bits, $descr='') {
  global $DB;

  # Get an Entities Table Write lock to prevent another tasks grabbing the Entities table NextAutoIncrement which is used here as the AgentId
  $DB->GetBDbLock(T_B_Entities, 0, Lock_Write, function($why){Error("Sorry, Agent Entity cannot be added as $why.");});

  # AgentId = the Entities table NextAutoIncrement which will become the EntityId of the Agent's Entity which also is the AgentId
  $entityColsAA['AgentId'] = $adminColsAA['AgentId'] = $agentId = $DB->NextAutoIncrement(T_B_Entities);

  # Add the Admin Member
  $adminColsAA['AgentId'] = $agentId;
  $adminColsAA['Bits'] = AP_All;
  $adminId = AddMember($adminColsAA);

  # Add the Agent's Entity
  $entityColsAA = [
    'AgentId' => $agentId,
    'Ref'     => 'Agent',
    'Level'   => ELevel_Max,
    'MngrId'  => $adminId,
    'Bits'    => EB_Dbo] + $entityColsAA;
  if ($agentId !== ($entityId = AddEntity($entityColsAA, $descr))) #  Add an Entity to Braiins.Entities. Should not give a duplicate key error as this is the first entity for the agent
    Error("AddAgent error with EntityId $entityId from AddEntity() call for the Agent's Entity not == the predetermined AgentId $agentId. Locking problem?");
  # $DB->RelBDbLock(T_B_Entities, 0, Lock_Write); # Release the Entities lock. No needed here as this has already done by the AddEntity() call

  # Add Agent
  $colsAA = [
    'Id'      => $agentId,
    'Credits' => A_InitialGiftCredits,
   # NumPays
   # TotPaid
    'Bits'    => MB_OK | $bits];
  list(, $logId) = $DB->InsertMaster(T_B_Agents, $colsAA); # No keys
  AddAgentTran($agentId, ATT_Add, $logId);

  # djh?? Copy the demo entities if not Braiins
  return $agentId;
}


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
  # CurrYear and DGsInUse default to 0.
  # Data is created which incloudes $descr

  $descr is an optional description of the entity
  $credits are the credits (<= 0) charged when setting up the entity, with details in $crComment EntityType Credits	EntitySize Credits{	Dim Group	DG credits...}

*/
function AddEntity($colsAA, $descr='', $crComment='', $credits=0) {
  global $DB;
  #if ($statusN == EStatus_Demo || $statusN == EStatus_Test) $eRef = '~' . $eRef; # to ensure Test and Demo entities sort to the end by ref

  # Get an Entities Table Write lock to prevent AutoIncrement change while a possible AddAgent() call is in progress
  $DB->GetBDbLock(T_B_Entities, 0, Lock_Write, function($why){Error("Sorry, Entities cannot be added as $why.");});

  $dataA = [
    [], # SuperiorsA  i [] of EntityIds of Superiors.      Info on the relationship is held in the Superior. This array is maintained via the Superior.
    [], # AssocsA     i [] of EntityIds of Associates.     Info on FromDate, ToDate etc is held in Bros.
    [], # JVsA        i [] of EntityIds of Joint Ventures. Details are held in Bros.
    [], # OPIsA       i [] of EntityIds of Other Participating Interests. Details are held in Bros.
    [], # SubsidsA    i [] of EntityIds of Subsidiaries.   Info on BasisDominantInfluence, FromDate, ToDate, Percentages etc is held in Bros.
    [], # SPEsA       i [] of EntityIds of Special Purpose Entities. Details are held in Bros.
    [], # TPAsA       i [] of EntityIds of Third Party Agents.       Info on the type and classification is held in Bros.
    [], # OfficersA   i [] of PersonIds of Officers.       Info on FromDate, ToDate, FromReason, ToReason etc is held in Bros.
    [], # AddressesA  i [] of AddressIds of Addresses which apply to the Entity
    [], # ContactsA   i [] of ContactIds of Contacts  which apply to the Entity
    $descr, # Descr string  Description of the entity
    ''      # Comments  string  Comments about the entity D2 separated
  ];
  $colsAA['Bits'] |= MB_OK;
  $colsAA['Data']  = json_encode($dataA, JSON_NUMERIC_CHECK);

  $retA = $DB->DupInsertMaster(T_B_Entities, $colsAA); # retuns false on duplicate AgentId + Ref
  $DB->RelBDbLock(T_B_Entities, 0, Lock_Write); # release the Entities lock
  if ($retA === false) # retuns false on duplicate AgentId + Ref
    return false;
  list($entityId, $logId) = $retA;
 #AddAgentTran(          $agentId,        $typeN, $logId=0, $comment=0, $credits=0, $moreA=0)
  AddAgentTran($colsAA['AgentId'], ATT_AddEntity, $logId, $crComment, $credits);
  # Add the first entry to Refs so that the query to insert extra Refs doesn't fail on its select of Refid to be incremented
  #                                 Id,EntityId,RefId,PMemId,TableN,TableId,AddT
  $DB->StQuery(sprintf('Insert Refs Values(Null, %d, 1, %d, %d, %d, %s)', $entityId, PMemId_Officer_Ref, T_B_People, $colsAA['MngrId'], $DB->TnS));
  return $entityId;
}

/* AddEntityTran($entityId, $entYear, $appN, $actioN1, $info1=0, $actioN2=0, $info2=0)
   -------------
with arguments mirroring the table:
  Id       mediumint unsigned not null auto_increment,
  EntityId mediumint unsigned not null, # Foreign key Entities.Id
  MemId    mediumint unsigned not null, # Foreign key People.Id, 0 for a system op
  EntYear  tinyint     signed not null, # The entity year from -3 upwards = the entity's CurrYear to which the tran applies
  AppN     tinyint   unsigned not null, # Enum of Application/Module creating the tran: APP_Admin, APP_Braiins, APP_BDT, BDT_CFS, BDT_EL etc
  ActioN1  tinyint   unsigned not null, # Transaction action 1 enum from the ETA_* constants             /- 2 so can have 'Ran format xxx and updated AcctsState' type ops in one tran
  Info1    mediumint unsigned not null, # Info relating to action 1 according to AppN and TypeN as below |
  ActioN2  tinyint   unsigned not null, # Transaction action 2 enum from the ETA_* constants, 0 if none  |
  Info2    mediumint unsigned not null, # Info relating to action 2 according to AppN and TypeN as below |
  AddT     timestamp not null default 0,

Returns the Id of the EntityTrans tran

*/

/* AddAgentTran($agentId, $typeN, $logId=0, $comment=0, $credits=0, $moreA=0)
   ------------
where:
$typeN is one of the ATT_ constants ATT_Add etc

$comment is an optional comment, 0 if no comment but $moreA is to be passed

and $moreA is an optional array to pass other optional tran fields according to tran type:
  Amount   smallint    signed     null, # Money amount in dollars/pounds -32,768 to 32,767, if applicable to the tran
  PayTypeN tinyint   unsigned     null, # Payment type enum if applicable to the tran

Returns the Id of the AgentTrans tran

CREATE TABLE IF NOT EXISTS AgentTrans (
  Id       mediumint unsigned not null auto_increment,
  AgentId  mediumint unsigned not null, # Foreign key Agents.Id
  MemId    mediumint unsigned not null, # Foreign key People.Id, 0 for a system op
  AppN     tinyint   unsigned not null, # Enum of Application/Module creating the tran: APP_Admin, APP_Braiins, APP_BDT
  TypeN    tinyint   unsigned not null, # Transaction type ATT_Add etc
  LogId    mediumint unsigned     null, # Foreign key DBLog.Id for an Insert/Update of the Agent record, null o'wise
  Amount   smallint    signed     null, # Money amount in dollars/pounds -32,768 to 32,767, if applicable to the tran
  Credits  smallint    signed     null, # Credits if applicable to the tran
  PayTypeN tinyint   unsigned     null, # Payment type enum if applicable to the tran
  Comment  varchar(240)           null, # Comment
  AddT     timestamp not null default 0,
  Primary Key (Id),
          Key (AgentId)
) Engine = InnoDB Default Charset=utf8;


*/
function AddAgentTran($agentId, $typeN, $logId=0, $comment=0, $credits=0, $moreA=0) {
  global $DB, $AppEnum;
  $colsAA = [
    'AppN'    => $AppEnum,
    'AgentId' => $agentId,
    'MemId'   => $DB->MemId,
    'TypeN'   => $typeN];
  if ($logId)   $colsAA['LogId']   = $logId;
  if ($comment) $colsAA['Comment'] = rtrim($comment); # rtrim() to remove trailing tabs. Want to keep leading tabs.
  if ($moreA)   $colsAA = $colsAA + $moreA;
  if ($credits) {
    $colsAA['Credits'] = $credits;
    UpdateAgentCredits($agentId, $credits);
  }
  return $DB->InsertMaster(T_B_AgentTrans, $colsAA);
}

## UpdateAgentCredits
#  ------------------
# Atomic op not logged to DBLog as Agents.Credits can be recreated from transactions.
# $credits should be passed with the correct sign to add to or subtract from Agents.Credits
# There should be a tran created for every non-zero credits call to this fn.
function UpdateAgentCredits($agentId, $credits) {
  global $DB;
  if ($credits) {
    if ($credits>0) $credits="+$credits";
    $DB->StQuery("Update Agents Set Credits=Credits$credits Where Id=$agentId");
  }
}



function AddEntityTran($entityId, $entYear, $appN, $actioN1, $info1=0, $actioN2=0, $info2=0) {
  global $DB;
  $colsAA = array(
    'EntityId' => $entityId,
    'MemId'    => $DB->MemId,
    'EntYear'  => $entYear,
    'AppN'     => $appN,
    'ActioN1'  => $actioN1);
    if ($info1)   $colsAA['Info1']   = $info1;
    if ($actioN2) $colsAA['ActioN2'] = $actioN2;
    if ($info2)   $colsAA['Info2']   = $info2;
  return $DB->InsertMaster(T_B_EntityTrans, $colsAA);
}

/* UpdateEntity($entityId, $col, $val, $pA=0)
   ------------
Updates the Entities record

where:
$entityId is the Entity Id

$col is the name of the Entities column to be updated

$val is the updated value for the $col column

$pA is the assoc record of the Entities row with at least $col in it, if available.

Returns 0 if no change or 1 if the Entities record was updated
*/
function UpdateEntity($entityId, $col, $val, $pA=0) {
  global $DB;
  LogIt("D fix UpdateEntity()");
  die("D fix UpdateEntity()");
  if (!$pA) $pA = $DB->AaQuery("Select $col From Entities Where Id=$entityId");
  return $DB->UpdateMaster(T_B_Entities, array($col => $val), $pA, $entityId); # returns 0 or 1
}

####################
# Helper Functions #
####################

function GeneratePeopleRef($colsAA) {
  global $DB;
  $ref = isset($colsAA['Ref']) ? $colsAA['Ref'] : 0;
  if (!$ref) {
    # Generate Ref from name
    $ref = trim(str_replace([SP, SQ, DQ], '', $colsAA['FName'])); # with any spaces or quotes removed
    foreach (explode(SP, $colsAA['GName']) as $seg)
      if (strlen($seg = trim($seg)))
        $ref .= strtoupper($seg[0]);
  }
  $n = 0;
  $refbase = $ref;
  while (1) {
    if (!$DB->OneQuery("Select Id from People Where AgentId=$colsAA[AgentId] And Ref='$ref'"))
      break;
    $ref = $refBase .= ++$n;
  }
  return $ref;
}

