<?php /* Copyright 2011-2013 Braiins Ltd

Com/inc/ClassBDB.inc

Database class BDB and helper functions

History:
11.02.11 Started based on the NMS, TARI, and originally LeLotto versions
         Removed ListLogin(), and MO class object
12.02.11 Removed GetMemberInfo(); Login() moved to Login.php
24.02.11 Added real_escape_string() use to the string case of Clean()
02.03.11 $DB->ErrRet use removed
07.04.11 Old column removed from DBLog
20.04.11 Col column removed from DBLog
         New column of DBLog changed to Set to store the Set portion of the insert/update query as a single entry per insert/update,
         rather than multiple entries by column inserted/changed as before.
20.04.11 Removed use of real_escape_string() with Clean() & the reference return in $valDS, leaving real_escape_string() use to query build time
25.04.11 Adding updating of null on passing 'null' in $colsAA[]. lowercase 'null' only.
07.05.11 Updated how set is built for the DBLog inserts to avoid double escaping already escaped string data esp / which json_encode() slashes
09.08.11 InsertMasterNoLog() and UpdateMasterNoLog() added
02.01.12 Added GenPw()
03.01.12 Added IncrMaster()
03.02.12 Changed Clean() to exit via Error(ERR_Unclean) rather than incrementing global $Unclean which has now been removed.
07.05.12 DupUpdateMaster() missing real_escape_string() call corrected
20.05.12 Added detection of  in Clean(); Changed logging of col named PW to show just *
12.10.12 Changed to use of TableN enum for Master functions rather than table name re DBLog efficiency and more flexibity re DB as default or not.
         Added private and public declarations.
         Tidied code a bit and changed class and function prefix to bb.
         Added private member function DBLog() to go with the DBLog table change to include EntityId instead of AppN.
25.10.12 Gen functions split off to FuncsGen.inc
         Dropped bb prefix from Member function names
02.03.13 Added AddBDbLoginLock(), AddBDbAgentEntityLoginLocks(), RelBDbAgentEntityLoginLocks()
         Changed the OneQuery fns to use the php 5.4 ability to dereference the array result of a function call
14.03.13 Finished BDB locking additions

Class BDB Public Member functions
---------------------------------
DB res ResQuery($qry)          Execute query $qry with the resulting resource being returned
void   StQuery($qry)           Execute query $qry without a resource being returned (Straight Through Query)
bool   DupQuery($qry)          Execute query with possible Duplicate key error - 1/0 return
DB obj      ObjQuery($qry)     Execute query $qry to return a single DB object. Aborts on no result or error.
DB Obj/null OptObjQuery($qry)  Execute query $qry to return a single DB object which need not necessarily exist
AA          AaQuery($qry)      Execute query $qry to return a single Associative Array. Aborts on no result or error.
AA/null     OptAaQuery($qry)   Execute query $qry to return a single assoc array which need not necessarily exist
int    OneQuery($qry, $tableN=0) Execute query $qry to return an int single column value for one row. $tableN may optionally be passed, and if it is the $qry should include a %s term for the Table name to be inserted. Returns 0 on no or multiple rows.
string StrOneQuery($qry, $lockA=0) Execute query $qry to return a string single column value for a single row
int    InsertQuery($qry)           Execute query $qry to insert a DB record, typically for a Tran table, and returns the Id
[]          InsertMaster($tableN, $colsAA)                    Insert a Master record & log the insert to DBLog. Not to be used if there could be a duplicate key clash.
int         InsertMasterNoLog($tableN, $colsAA)               Insert a Master record. Not to be used if there could be a duplicate key clash.
[]/false    DupInsertMaster($tableN, $colsAA)                 Insert a Master record & log the insert to DBLog. To be used where there could be a duplicate key clash.
int/0       UpdateMaster($tableN, $colsAA, $aA=0, $id=0)      Update a Master record and log the changes to DBLog
int         UpdateMasterNoLog($tableN, $colsAA, $aA=0, $id=0) Update a Master record
int/0/false DupUpdateMaster($tableN, $colsAA, $aA=0, $id=0) Update a Master record where there is a possibility of a duplicate key clash and logs the changes
void   DeleteMaster($tableN, $id, $typeN=DBLOG_Delete)    Delete a master table row and records the delete in DBLog. If required the record can be recreated from the DBLog Insert and edit entries.
void   IncrMaster($tableN, $id, $col) Atomic increment of one column in a record
int    NextAutoIncrement($tableN)     Return the next auto increment value for $table
void   SetTime()                      Set the time of the BDB instance for use with long running tasks e.g. SendEmails.cli

Locking Functions
-----------------
#oid    LockTables($lock)                                    Applies an mysql table lock
#oid    UnlockTables()                                       Releases an mysql table lock
void    AddBDbLoginLock($tableN, $rowId, $startTS=0)         Adds a BDB persistent Member/Agent/Entity login lock which lasts until logout
void    RelBDbLoginLock($tableN, $rowId)                     Releases a BDB persistent login lock
void    AddBDbAgentEntityLoginLocks($rowIdsA)                Adds BDB persistent Agent, Entity login locks which last until logout
void    RelBDbAgentEntityLoginLocks($rowIdsA)                Releases BDB persistent Agent, Entity login locks
void/CB GetBDbLock($tableN, $rowId, $lockN, $failCallbackFn) Gets a BDB visit Login (Write), Read or Write lock which persists only for the rest of the current visit at most
void/CB GetBDbLocks($locksA, $failCallbackFn)                Gets BDB visit Login (Write), Read or Write locks which persist only for the rest of the current visit at most
void    RelBDbLocks()                                        Releases the BDB visit locks created by GetBDbLock() or GetBDbLocks() in the current visit.
void    RelBDbLock($tableN, $rowId, $lockN)                  Releases a single specific BDB lock
1/0     IsBDbLockSet($tableN, $rowId, $lockN)                Returns 1 if a BDb Lock for $tableN, $rowId, $lockN (this visit) is set, 0 if not.
#id     GetMysqlCoopLock()                                   Applies an mysql cooperative lock
#id     ReleaseMysqlCoopLock()                               Releases a lock applied by GetMysqlCoopLock()

Helper Functions
----------------
void   LError($locOrMsg, $qry=0)  BDB helper fn to assemble error message and pass it on to Error()

bool   Check($val, $type)         Returns true if clean, false if any unexpected or illegal characters are found

S/I/B  Clean($val, $type,         If $val is not clean exits via Error(ERR_Unclean) after logging it,
        $escB=false, &$escVal=0)   otherwise returns according to $type as described in fn notes.

string GenPw($pw, $id)            Generate SHA512 password via hash() with salt based on Id

String Handling
===============
Use utf-8 everywhere so that non-ascii characters e.g. £ € ¥ ® ™ © left and right single/double quotes etc can stay as they are.

Only need to deal with the xml/xhtml special characters & < > ' " with ' and " also relevant to SQL queries and SQL injection attacks.

Possibilities:
1. Escape for DB storage
   Escape for use in search type queries
   Convert for output - including format text and headings

2. Convert for DB storage in BDB fns with escaping removed. OK for echoing back to an input or text area field.
   Escape for use in search type queries without conversion
   Convert format text during compilation
   No conversion on output

3. Convert individual strings for DB storage with BDB fns doing escaping as for method 1. OK for echoing back to an input or text area field.
   Escape for use in search type queries without conversion
   Convert format text during compilation
   No conversion on output.

Up to 28.12.11 had used method 1 but means checking every 'dat' fetch for being a string and then calling htmlspecialchars() usually unnecessarily.
Also, headings were not being checked for the xml/xhtml special characters.

29.12.11 tried switching to method 2 but Bro BrosAll record was much longer at 3075 vs 1826 for the Import.txt case tested.
So went for method 3.

Must remember to call htmlspecialchars($str, ENT_QUOTES) on all strings to be stored incl headings.

This assumes no existing entities, with the fourth optional parameter of double_encode left set to the default of true.
(When double_encode is turned off PHP will not encode existing html entities, the default is to convert everything.)

The default character set is ISO-8859-1 but there is no need to pass 'UTF-8' as the third parameter since
"For the purposes of this function, the charsets ISO-8859-1, ISO-8859-15, UTF-8, cp866, cp1251, cp1252, and KOI8-R
 are effectively equivalent, provided the string itself is valid for the character set, as the characters affected
 by htmlspecialchars() occupy the same positions in all of these charsets.

The translations performed are:

    '&' (ampersand) becomes '&amp;'
    '"' (double quote) becomes '&quot;' when ENT_NOQUOTES is not set.
    "'" (single quote) becomes '&#039;' only when ENT_QUOTES is set.
    '<' (less than) becomes '&lt;'
    '>' (greater than) becomes '&gt;'



#############
# BDB class # used for most DB ops and all of those involving master and transaction tables, plus holds some session info
#############
Public Class vars:
MemId    Set by SessionStart() etc
LoginN   Session login state
Bits     Member's Bits
SessionA Assoc array for other stuff as needed: TZO, Email, DName, Level, AgentId, EntityIdBits    Members Bits set by SessionStart() and SessionOpen()
TnS      Time in string form with enclosing single quotes for setting/updating timestamp fields
         so that all queries in this connection incl DBLog ones will have the same UTC time.
*/

class BDB extends mysqli {
  private $TablesA, $LocksA = [];
  public $MemId, $LoginN, $Bits, $VisId, $TnS, $SessionsA;

  public function __construct($visId=0, $dbTables=DB_Braiins_Tables, $host=WS_HOST, $user=WS_USER, $pw=WS_PW, $db=DB_Default) {
    @parent::__construct($host, $user, $pw, $db);
    if (mysqli_connect_error()) Error(ERR_CONNECT_DB); # The default is for Error to abort.
    $this->TablesA = explode(',',$dbTables);
    $this->SessionA = []; # Assoc array for other stuff as needed: TZO, Email, DName, MLevel, AgentId, EntityId
    $this->MemId = $this->LoginN = $this->Bits = 0;
    $this->VisId = $visId;
    $this->SetTime();
  }

# DBLog()
# ~~~~~~~
# Private member function to insert the log records
private function DBLog($typeN, $tableN, $tableId, $set) {
  global $EntityId;
  if ($set) # can be null
   #$set = str_replace(["EntityId=$EntityId,", "MemId=$this->MemId,"], '', substr($set, 1)); # remove leading , and strip EntityId and MemId from $set as these are incl in the row
    $set = substr($set, 1); # remove leading ,
                                      # Id, TypeN  EntityId,       MemId, TableN  TableId Set,       AddT
  $this->StQuery("Insert DBLog Values(null,$typeN,$EntityId,$this->MemId,$tableN,$tableId,'$set',$this->TnS)");
  return $this->insert_id;
}

# ResQuery    DB Resource result Query
# ~~~~~~~~
# Executes a DB Query $qry with the resulting resource being returned,
# Typically expected to be used for selects.
# Does not return if an error happens.
function ResQuery($qry) {
  $res = $this->query($qry) or LError('ResQuery', $qry);
  return $res;
}

# StQuery  DB Straight Through Query - no return
# ~~~~~~~
# Executes a DB Straight Through Query $qry without a resource being returned
# Typically expected to be used for Insert/Update/Delete queries. It is of no use for Selects for which ResQuery() is applicable.
# Does not return if an error happens. Thus this fn must not be used for a query which might result in a valid error e.g. an insert
# or update which could give a duplicate key clash. For those cases use DupQuery() instead and check the return value.
function StQuery($qry) {
  $this->query($qry) or LError('Query', $qry);
}

# DupQuery    DB Query with possible Duplicate key error - 1/0 return
# ~~~~~~~~
# Executes a DB Query $qry without a resource being returned, returns true if OK, false if duplicate key clash occurred
# Expected to be used for Insert/Update queries where a duplicate key clash could occur. It is of no use for Selects for which ResQuery() is applicable.
# Does not return if an error other than a duplicate key clash happens.
# When this fn is used the calling module should always test the return value.
function DupQuery($qry) {
  if ($this->query($qry))
    return 1;
  if ($this->errno == mySQL_ERROR_DUPLICATE_ENTRY)
    return 0;
  LError('DupQuery', $qry);
}

# ObjQuery    DB Object Query
# ~~~~~~~~
# Executes a DB Query $qry to return a single DB object
# The query must be a Select which is not expected to fail. If the query could fail use OptObjQuery() instead.
# If the query fails or no object is found the fn errors and aborts
# Returns the first DB obj if more than one is found.
function ObjQuery($qry) {
  $res = $this->query($qry) and $o=$res->fetch_object() or LError('ObjQuery', $qry);
  $res->free();
  return $o;
}

# OptObjQuery DB Optional Object Query
# ~~~~~~~~~~~
# Executes a DB Query $qry to return a single DB object which need not necessarily exist
# The query must be a Select.
# Returns a DB obj (first one if more than one found) or null if no match was found.
function OptObjQuery($qry) {
  $res = $this->ResQuery($qry);
  $o   = $res->fetch_object(); # returns null if no record was found
  $res->free();
  return $o; # returns null if no record was found
}

# AaQuery    DB Associative Array Query
# ~~~~~~~
# Executes a DB Query $qry to return a single Associative Array
# The query must be a Select which is not expected to fail. If the query could fail use OptAaQuery() instead.
# If the query fails or no result is found the fn errors and aborts
# Returns the first row if more than one is found.
function AaQuery($qry) {
  $res = $this->query($qry) and $a=$res->fetch_assoc() or LError('AaQuery', $qry);
  $res->free();
  return $a;
}

# OptAaQuery DB Optional Associative Array Query
# ~~~~~~~~~~
# Executes a DB Query $qry to return a single DB associative array which need not necessarily exist
# The query must be a Select.
# Returns an associative array or null if no match was found.
function OptAaQuery($qry) {
  $res = $this->ResQuery($qry);
  $a   = $res->fetch_assoc(); # returns null if no record was found
  $res->free();
  return $a; # returns null if no record was found
}

# OneQuery
# ~~~~~~~~
# Executes a DB Query $qry to return a single int column value for a single row
# The query must be a Select and should be for one column only, tho if more are specified, the first one is used.
# $tableN may optionally be passed, and if it is the $qry should include a %s term for the Table name to be inserted here.
# Returns an int of the value or 0 if no row was found or if more than one row was found.
function OneQuery($qry, $tableN=0) {
  $res = $this->ResQuery($tableN ? sprintf($qry, $this->TablesA[$tableN]) : $qry);
  $ans = $res->num_rows === 1 ? (int)$res->fetch_row()[0] : 0;
  $res->free();
  return $ans;
}

# StrOneQuery
# ~~~~~~~~~~~
# Executes a DB Query $qry to return a single string column value for a single row.
# The query must be a Select and should be for one column only, tho if more are specified, the first one is used.
# $tableN may optionally be passed, and if it is the $qry should include a %s term for the Table name to be inserted here.
# Read lock info can optionally be passed via $tableN and $lockA as  [RowId, FailCallbackFn] e.g. [T_B_AgentData, $AgentId, function($why){AjaxReturn(1, "Sorry, your Account cannot be edited currently as $why.");}]
# Returns a string of the value or '' if no row was found or if multiple rows were found.
function StrOneQuery($qry, $tableN=0, $lockA=0) {
  if ($lockA)
    $this->GetBDbLock($tableN, $lockA[0], Lock_Read, $lockA[1]);
  $res = $this->ResQuery($tableN ? sprintf($qry, $this->TablesA[$tableN]) : $qry);
  if ($lockA)
    $this->RelBDbLocks();
  $ans = $res->num_rows === 1 ? $res->fetch_row()[0] : '';
  $res->free();
  return $ans;
}

# InsertQuery
# ~~~~~~~~~~~
# Executes a DB Query $qry to insert a DB record, typically for a Tran table, and returns the Id.
# Use InsertMaster or DupInsertMaster for Master tables.
# Argument: The query
# Returns:  Id of the inserted record
function InsertQuery($qry) {
  $this->StQuery($qry);
  return $this->insert_id; # Insert done OK so return Id
}

# InsertMaster
# ~~~~~~~~~~~~
# Inserts a DB record for Master tables and logs the insert
# Arguments:
# - $tableN The enum of the table
# - $colsAA An associative array of column name => values to be inserted
# Returns:
# - [Id of the inserted record, Id of the DBLog record]
function InsertMaster($tableN, $colsAA) {
  $set = $log = '';
  # Dump("InsertMaster($tableN, \$colsAA) \$colsAA=",$colsAA);
  foreach ($colsAA as $col => $dat) {
    $set .= ($t = ",$col=");
    $log .= $t;
    if (is_numeric($dat)) {
      $set .= $dat;
      $log .= $dat;
    }else{
      $t = $this->real_escape_string($dat);
      $set .=  SQ.$t.SQ;  # '$t'
      $log .= QSQ.$t.QSQ; # \'$t\'
    }
  }
  # Do the insert which is not expected to fail. (No mysql level unique key fields should come thru here unless the app has guarded against possible duplicate key clashes.)
  $set = substr($set, 1);
  $this->StQuery("Insert {$this->TablesA[$tableN]} Set $set,AddT=$this->TnS");
  # Insert OK so log it and return the Ids []
  if (!$id = $this->insert_id) {
    # Can be zero if table doesn't have an auto_increment Id e.g. Agents. In that case expect $colsAA to contain the Id
    if (!isset($colsAA['Id']))
      LError("InsertMaster on table $tableN did not give an auto_increment Id and there was no Id in the cols array");
    $id = (int)$colsAA['Id'];
  }
  return [$id, $this->DBLog(DBLOG_Insert, $tableN, $id, $log)];
}

# InsertMasterNoLog
# ~~~~~~~~~~~~~~~~~
# Inserts a DB record for Master tables
# Arguments:
# - $tableN The enum of the table
# - $colsAA An associative array of column name => values to be inserted
# Returns:
# - Id of the inserted record
#
function InsertMasterNoLog($tableN, $colsAA) {
  $set = '';
  foreach ($colsAA as $col => $dat) {
    $set .= ",$col=";
    if (is_numeric($dat))
      $set .= $dat;
    else
      $set .= SQ.$this->real_escape_string($dat).SQ; # 'dat'
  }
  # Do the insert which is not expected to fail. (No mysql level unique key fields should come thru here unless the app has guarded against possible duplicate key clashes.)
  $set = substr($set, 1);
  $this->StQuery("Insert {$this->TablesA[$tableN]} Set $set,AddT=".$this->TnS);
  # Insert done OK so return Id
  return $this->insert_id;
}

# DupInsertMaster
# ~~~~~~~~~~~~~~~
# Inserts a Master record & logs the insert to DBLog. To be used where there could be a duplicate key clash.
# Arguments:
# - $tableN The enum of the table
# - $colsAA An associative array of column name => values to be inserted
# Returns:
# - false if the insert couldn't be done due to a duplicate key clash
# - [Id of the inserted record, Id of the DBLog record]
function DupInsertMaster($tableN, $colsAA) {
  $set = $log = '';
  foreach ($colsAA as $col => $dat) {
    $set .= ($t = ",$col=");
    $log .= $t;
    if (is_numeric($dat)) {
      $set .= $dat;
      $log .= $dat;
    }else{
      $t = $this->real_escape_string($dat);
      $set .=  SQ.$t.SQ;  # '$t'
      $log .= QSQ.$t.QSQ; # \'$t\'
    }
  }
  $set = substr($set, 1);
  # Do the insert via DupQuery() which returns 0 if the insert can't be done due to a duplicate key clash
  if (!$this->DupQuery("Insert {$this->TablesA[$tableN]} Set $set,AddT=$this->TnS"))
    return false;
  # Insert OK so log it and return the Ids []
  return [$id = $this->insert_id, $this->DBLog(DBLOG_Insert, $tableN, $id, $log)];
}

# UpdateMaster
# ~~~~~~~~~~~~
# Updates a Master DB record and logs the changes to DBLog.
# Notes: • If there is a chance of a duplicate key clash use DupUpdateMaster() instead and test the return value.
# Arguments:
# - $tableN The enum of the table
# - $colsAA An associative array of column name => values to be updated
# - $aA=0   Optional original row AA or Object with values as they were before the change
# - $id=0   Optional Id of the master record to be updated
# One of aA or id, or both, are expected.
# If neither aA or id are passed an error is returned
# If aA is passed but id isn't, id is expected to be found in aA. If it isn't, an error is returned.
# If aA is not passed but id is, a select is done to fetch aA. If this fails, an error is returned.
# Returns:
# - 0 if no update is done, o'wise the DBLog record Id
function UpdateMaster($tableN, $colsAA, $aA=0, $id=0) {
  $table = $this->TablesA[$tableN];
  if ($aA) {
    if (is_object($aA))
      $aA = get_object_vars($aA); # convert object to assoc array
    # if aA is passed but id isn't, expect to find id in the array
    if (!$id) {
      if (!isset($aA['Id']))
        LError("UpdateMaster on $table was passed a prior values array, but no Id, and there was no Id in the array");
      $id = $aA['Id'];
    }
  }else{
    # no array so expect an id
    if (!$id)
      LError("UpdateMaster on $table was not passed either a prior values array or an Id but at least one of them is needed");
    # try to get it
    if (!$aA = $this->OptAaQuery("Select * From $table Where Id=$id"))
      LError("UpdateMaster on $table was passed an Id of $id, tho no prior values array, and a select for the prior values based on Id=$id failed");
  }
  $num = 0;
  $set = $log = '';
  foreach ($colsAA as $col => $dat) {
    if ($aA[$col] != $dat) {
      $set .= ($t = ",$col=");
      $log .= $t;
      if (is_numeric($dat) || $dat == 'null') {
        $set .= $dat;
        $log .= $dat;
      }else{
        $t = $this->real_escape_string($dat);
        $set .=  SQ.$t.SQ;  # '$t'
        $log .= QSQ.$t.QSQ; # \'$t\'
      }
      ++$num;
    }
  }
  if ($num) {
    # Do the update which is not expected to fail. (No mysql level unique key fields should come thru here unless the app has guarded against possible duplicate key clashs.
    $set = substr($set, 1);
    $this->StQuery("Update {$this->TablesA[$tableN]} Set $set,EditT=$this->TnS Where Id=$id");
    # Main update OK so now do the DBLog insert which is Not Expected To Fail - no unique keys
    return $this->DBLog(DBLOG_Update, $tableN, $id, $log);
  }
  return 0;
}

# UpdateMasterNoLog
# ~~~~~~~~~~~~~~~~~
# Updates a Master DB record
# Notes: • If there is a chance of a duplicate key clash use DupUpdateMaster() instead and test the return value.
# Arguments:
# - $tableN The enum of the table
# - $colsAA An associative array of column name => values to be updated
# - $aA=0   Optional original row AA or Object with values as they were before the change
# - $id=0   Optional Id of the master record to be updated
# One of aA or id, or both, are expected.
# If neither aA or id are passed an error is returned
# If aA is passed but id isn't, id is expected to be found in aA. If it isn't, an error is returned.
# If aA is not passed but id is, a select is done to fetch aA. If this fails, an error is returned.
# Returns:
# - count of columns changed
function UpdateMasterNoLog($tableN, $colsAA, $aA=0, $id=0) {
  $table = $this->TablesA[$tableN];
  if ($aA) {
    if (is_object($aA))
      $aA = get_object_vars($aA); # convert object to assoc array
    # if aA is passed but id isn't, expect to find id in the array
    if (!$id) {
      if (!isset($aA['Id']))
        LError("UpdateMasterNoLog on $table was passed a prior values array, but no Id, and there was no Id in the array");
      $id = $aA['Id'];
    }
  }else{
    # no array so expect an id
    if (!$id)
      LError("UpdateMaster on $table was not passed either a prior values array or an Id but at least one of them is needed");
    # try to get it
    if (!$aA = $this->OptAaQuery("Select * From $table Where Id=$id"))
      LError("UpdateMaster on $table was passed an Id of $id, tho no prior values array, and a select for the prior values based on Id=$id failed");
  }
  $num = 0;
  $set = '';
  foreach ($colsAA as $col => $dat) {
    if ($aA[$col] != $dat) {
      $set .= ",$col=";
      if (is_numeric($dat) || $dat == 'null')
        $set .= $dat;
      else
        $set .= SQ . $this->real_escape_string($dat) . SQ; # 'dat'
      ++$num;
    }
  }
  if ($num) {
    # Do the update which is not expected to fail. (No mysql level unique key fields should come thru here unless the app has guarded against possible duplicate key clashs.
    $set = substr($set, 1);
    $this->StQuery("Update $table Set $set,EditT=$this->TnS Where Id=$id");
  }
  return $num;
}

# DupUpdateMaster
# ~~~~~~~~~~~~~~~
# Updates a Master DB record where there is a possibility of a duplicate key clash,
#  and, if the update is ok, logs the changes to DBLog.
# Note: DupUpdateMaster() is very similar to UpdateMaster().
#       There are 2 fns because the 'Dup' fn is only rarely needed i.e. most modules will use only UpdateMaster().
# Arguments:
# - $tableN The enum of the table
# - $colsAA An associative array of column name => values to be updated
# - $aA=0   Optional original row AA or Object with values as they were before the change
# - $id=0   Optional Id of the master record to be updated
# One of aA or id, or both, are expected.
# If neither aA or id are passed an error is returned
# If aA is passed but id isn't, id is expected to be found in aA. If it isn't, an error is returned.
# If aA is not passed but id is, a select is done to fetch aA. If this fails, an error is returned.
# Returns:
# - false if the update couldn't be done due to a duplicate key clash
# - false if no update is done, o'wise the DBLog record Id
# - 0 if no update is done, o'wise the DBLog record Id
function DupUpdateMaster($tableN, $colsAA, $aA=0, $id=0) {
  $table = $this->TablesA[$tableN];
  if ($aA) {
    if (is_object($aA))
      $aA = get_object_vars($aA); # convert object to assoc array
    # if aA is passed but id isn't, expect to find id in the array
    if (!$id) {
      if (!isset($aA['Id']))
        LError("DupUpdateMaster on $table was passed a prior values array, but no Id, and there was no Id in the array");
      $id = $aA['Id'];
    }
  }else{
    # no array so expect an id
    if (!$id)
      LError("DupUpdateMaster on $table was not passed either a prior values array or an Id but at least one of them is needed");
    # try to get it
    if (!$aA = $this->OptAaQuery("Select * From $table Where Id=$id"))
      LError("DupUpdateMaster on $table was passed an Id of $id, tho no prior values array, and a select for the prior values based on Id=$id failed");
  }
  $num = 0;
  $set = $log = '';
  foreach ($colsAA as $col => $dat) {
    if ($aA[$col] != $dat) {
      $set .= ($t = ",$col=");
      $log .= $t;
      if (is_numeric($dat) || $dat == 'null') {
        $set .= $dat;
        $log .= $dat;
      }else{
        $t = $this->real_escape_string($dat);
        $set .=  SQ.$t.SQ;  # '$t'
        $log .= QSQ.$t.QSQ; # \'$t\'
      }
      ++$num;
    }
  }
  if ($num) {
    # Do the update via DupQuery() which returns 0 if the update can't be done due to a duplicate key clash
    $set = substr($set, 1);
    if (!$this->DupQuery("Update $table Set $set,EditT=$this->TnS Where Id=$id"))
      return false;
    # Main update OK so now do the DBLog insert which is Not Expected To Fail - no unique keys
    return $this->DBLog(DBLOG_Update, $tableN, $id, $log);
  }
  return 0;
}

# DeleteMaster
# ~~~~~~~~~~~~
# Deletes a master table row and records the delete in DBLog. If required the record can be recreated from the DBLog Insert and edit entries.
# Arguments:
# - $tableN The enum of the table
# - $id     The Id of the row to be deleted
# - $TypeN  An optional type enum for the DBLog record e.g. DBLOG_Delete_GarbageCollect, DBLOG_Delete_ReplaceImport
# Returns:
# - nothing. If there a problem an error is thrown.
function DeleteMaster($tableN, $id, $typeN=DBLOG_Delete) {
  if ($id) { # expect an Id always
    # Do the delete
    $this->StQuery("Delete From {$this->TablesA[$tableN]} Where Id=$id");
    # Now record the deletion in DBLog
    $this->DBLog($typeN, $tableN, $id, null);
  }else
    LError("DeleteMaster was passed an Id of 0");
}

# IncrMaster
# ~~~~~~~~~~
# Atomic increment of one column in a record
# Arguments:
# - $tableN The enum of the table
# - $id     The Id of the row
# - $Field  The name of the column
# Returns:
# - nothing. If there a problem an error is thrown.
function IncrMaster($tableN, $id, $col) {
  if ($id) { # expect an Id always
    # Do the update
    $this->StQuery("Update {$this->TablesA[$tableN]} Set $col=$col+1 Where Id=$id");
    # Now record the increment in DBLog
    $this->DBLog(DBLOG_Incr, $tableN, $id, ",$col"); # , before $col just becase DBLog expects a leading char to be thrown away
  }else
    LError('IncrMaster was passed an Id of 0');
}

# NextAutoIncrement
# ~~~~~~~~~~~~~~~~~
# Returns the next auto increment value for $table
function NextAutoIncrement($tableN) {
  $o = $this->ObjQuery("SHOW TABLE STATUS LIKE '{$this->TablesA[$tableN]}'");
  return (int)$o->Auto_increment;
}

# SetTime()
# ~~~~~~~~~
# Sets the time of the BDB instance for time stamp setting purposes so that each master update/tran involved has the same time stamp, even with with long running tasks.
#
# Issues to do with timestamp conversions and UNIX_TIMESTAMP() use discussed in /Doc/Dev/SnippetsMySQL.txtMySQL Timestamps
# were simplifed from 05.01.13 by using UTC for both the MySQL connection and the default timezone used by the PHP date functions.
# See /Doc/Doc.txt Server Config.
#
# Following this, the only time adjustment needed is for a user's TZO vs UTC, where TZO is the client time zone offset as reported by JavaScript
#
# Visitor's local time of a timestamp = gmstrftime(UNIX_TIMESTAMP(timestamp field) - TZO*60)

function SetTime() {
  $this->TnS = gmstrftime("'%Y-%m-%d %H:%M:%S'", time()); # WITH ENCLOSING SINGLE QUOTES
}

##################
# Lock Functions #
##################

# LockTables
# ~~~~~~~~~~
# Applies an mysql table lock
#function LockTables($lock) {
#  $this->StQuery('LOCK TABLES ' . $lock);
#}

# UnlockTables
# ~~~~~~~~~~~~
# Releases an mysql table lock
#function UnlockTables() {
#  $this->StQuery('UNLOCK TABLES');
#}

# AddBDbLoginLock
# ~~~~~~~~~~~~~~~
# Adds a BDB persistent Member/Agent/Entity login lock which lasts until logout. The call is not expected to fail, so there is no return to be checked, where:
#  tableN = <T_B_People | T_B_Agents | T_B_Entities>
#   rowId = <Member Id | Agent Id | Entity Id>
# startTS = optional string Timestamp of the login start, included for the Member case on a restart
# The lock is lost if MySQL is restarted as Locks is a memory table but the lock can be recreated by running BuildMemoryTables.php
function AddBDbLoginLock($tableN, $rowId, $startTS=0) {
  $qry = sprintf('Insert Locks Values(%d,%d,%d,%d,%s)', $tableN, $rowId, Lock_Login, $this->VisId, $startTS ? "'$startTS'" : $this->TnS);
  $this->query('Lock Tables Locks Write');
  $this->DupQuery($qry); # DupQuery in case Lock is already set = assume browser crash/IC failure
  $this->query('Unlock Tables');
}

# RelBDbLoginLock
# ~~~~~~~~~~~~~~~
# Releases a BDB persistent login lock, which is not expected to fail, so there is no return to be checked, where:
#  tableN = <T_B_People | T_B_Agents | T_B_Entities>
#   rowId = <Member Id | Agent Id | Entity Id>
function RelBDbLoginLock($tableN, $rowId) {
  $qry = sprintf('Delete From Locks Where TableN=%d And RowId=%d And LockN=%d And VisId=%d', $tableN, $rowId, Lock_Login, $this->VisId);
  $this->query('Lock Tables Locks Write');
  $this->query($qry);
  $this->query('Unlock Tables');
}

# AddBDbAgentEntityLoginLocks
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Adds BDB persistent Agent, Entity login locks which last until logout. The call is not expected to fail, so there is no return to be checked, where:
# rowIdsA = [Agent Id, Entity Id]
# The locks are lost if MySQL is restarted as Locks is a memory table but the locks can be recreated by running BuildMemoryTables.php
function AddBDbAgentEntityLoginLocks($rowIdsA) {
  $tablesA = [T_B_Agents, T_B_Entities];
  $this->query('Lock Tables Locks Write');
  for ($i=0; $i<2; ++$i)
    $this->DupQuery(sprintf('Insert Locks Values(%d,%d,%d,%d,%s)', $tablesA[$i], $rowIdsA[$i], Lock_Login, $this->VisId, $this->TnS)); # DupQuery in case Lock is already set = assume browser crash/IC failure
  $this->query('Unlock Tables');
}

# RelBDbAgentEntityLoginLocks
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~
# Releases BDB persistent Agent, Entity login locks, which is not expected to fail, so there is no return to be checked, where:
# rowIdsA = [Agent Id, Entity Id]
function RelBDbAgentEntityLoginLocks($rowIdsA) {
  $tablesA = [T_B_Agents, T_B_Entities];
  $this->query('Lock Tables Locks Write');
  for ($i=0; $i<2; ++$i)
    $this->query(sprintf('Delete From Locks Where TableN=%d And RowId=%d And LockN=%d And VisId=%d', $tablesA[$i], $rowIdsA[$i], Lock_Login, $this->VisId));
  $this->query('Unlock Tables');
}

# GetBDbLock
# ~~~~~~~~~~
# Gets a BDB visit Login (Write), Read or Write lock which persists only for the rest of the current visit at most, being released by SessionClose() or sooner by a RelBDbLocks() call.
# The call can fail. The timeout period is 10 seconds.
#  tableN = T_B_* enum of table
#   rowId = 0 for system or a Table, Row Id for a Table record
#   lockN = <Lock_Login | Lock_Read | Lock_Write> where Lock_Login mean a Write lock with Login locks being considered as well as Read locks.
# failCallbackFn = function to be called back on fail, with the reason as the single argument. Should not return i.e. should cause an Ajax return to client and exit
function GetBDbLock($tableN, $rowId, $lockN, $failCallbackFn) {
  #LogIt("$tableN, $rowId, $lockN");
  $qry = "Select count(*) From Locks Where VisId!=$this->VisId And TableN=$tableN" . ($rowId ? " And RowId=$rowId" : ''); # The VisId test allow multiple identical locks to be added for this visit e.g. the Entities table lock via AddAgent()
  switch ($lockN) {
    case Lock_Login: $lockN = Lock_Write; break;              # For a Login Write lock there must be no current locks of any kind; lock -> Write lock
    case Lock_Read:  $qry .= " And LockN=".Lock_Write; break; # For a Read lock there must be no current Write locks. Can have Login and Read locks.
    case Lock_Write: $qry .= " And LockN>=".Lock_Read; break; # For a Write lock there must be no current Read or Write locks. Can have Login lock.
  }
  $sleepUsecs = 1000; # 0.1 secs
  $startT = time();
  while (true) {
    $this->query('Lock Tables Locks Write');
    #Logit($this->OneQuery($qry));
    if (!$this->OneQuery($qry)) {
      # OK to apply lock
      $this->StQuery(sprintf('Insert Locks Values(%d,%d,%d,%d,%s)', $tableN, $rowId, $lockN, $this->VisId, $this->TnS));
      $this->query('Unlock Tables');
      $this->LocksA[] = [$tableN, $rowId, $lockN];
      return;
    }
    # Could not get lock, wait to try again unless time is up
    $this->query('Unlock Tables');
    if (time() - $startT >= 10) {
      # 10 second timeout
      $this->RelBDbLocks(); # Release any previous locks
      $o = $this->OptObjQuery(str_replace('count(*)', 'VisId,LockN', $qry) . ' Order by LockN Desc,StartT Desc Limit 1');
      # Lock_Login: Select VisId,LockN from Locks Where TableN=$tableN And RowId=$rowId Order by LockN Desc,StartT Desc Limit 1               For a Login Write lock to fail there must be a lock of any kind
      # Lock_Read:  Select VisId,LockN from Locks Where TableN=$tableN And RowId=$rowId And LockN=3 Order by LockN Desc,StartT Desc Limit 1   For a Read lock to fail there must be a current Write lock. Expect only 1 but make sure
      # Lock_Write: Select VisId,LockN from Locks Where TableN=$tableN And RowId=$rowId And LockN>=2 Order by LockN Desc,StartT Desc Limit 1  For a Write lock to fail there must be a Read or Write lock
      if ($o) { # expect query to give an obj but test in case not
        switch ((int)$o->LockN) {
          case Lock_Login:
            switch ($tableN) {
              case T_B_People:   $why = 's/he is logged in'; break;
              case T_B_Agents:   $why = '%s is logged in via the agent'; break;
              case T_B_Entities: $why = '%s is logged in to the entity'; break;
              default: $why = 'a lock is in place'; # shouldn't see this
            }
            break;
          case Lock_Read:  $why = '%s has the data locked for reading';  break;
          case Lock_Write: $why = '%s has the data locked for updating'; break;
        }
        if (InStr('%s', $why))
          $why = sprintf($why, $this->StrOneQuery("Select DName from Visits Where Id=$o->VisId"));
      }else
        $why = 'a lock is in place'; # shouldn't see this
      $failCallbackFn($why);
      # not expected to return
      Error('GetBDbLock fail callback fn returned unexpectedly');
    }
    usleep($sleepUsecs);
    $sleepUsecs += 333; # 333 microseconds more wait each time around
  }
}

# GetBDbLocks
# ~~~~~~~~~~~
# Gets BDB visit Login (Write), Read or Write locks which persist only for the rest of the current visit at most, being released by SessionClose() or sooner by a RelBDbLocks() call.
# The call can fail. The timeout period is 10 seconds. All locks must be obtained for the call to succeed.
#  $locksA = [i => [i => TableN, RowId, LockN]] where TableN, RowId, LockN are as per GetBDbLock()
# failCallbackFn = function to be called back on fail, with the reason as the single argument. Should not return i.e. should cause an Ajax return to client and exit
function GetBDbLocks($locksA, $failCallbackFn) {
  $numLocks = count($locksA);
  $sleepUsecs = 1000; # 0.1 secs
  $startT = time();
  while (true) {
    $firstFailQry = 0;
    $this->query('Lock Tables Locks Write');
    foreach ($locksA as $i => $lockA) {
      list($tableN, $rowId, $lockN) = $lockA;
      $qry = "Select count(*) From Locks Where VisId!=$this->VisId And TableN=$tableN" . ($rowId ? " And RowId=$rowId" : '');
      $rowWhere = $rowId ? " And RowId=$rowId" : '';
      switch ($lockN) {
        case Lock_Login: $lockN = Lock_Write; break;              # For a Login Write lock there must be no current locks of any kind; lock -> Write lock
        case Lock_Read:  $qry .= " And LockN=".Lock_Write; break; # For a Read lock there must be no current Write locks
        case Lock_Write: $qry .= " And LockN>=".Lock_Read; break; # For a Write lock there must be no current Read or Write locks
      }
      if ($this->OneQuery($qry)) {
        # Fail
        if (!$firstFailQry)
          $firstFailQry = $qry; # for use in timeout query
      }else{
        # OK to apply lock
        $this->query(sprintf('Insert Locks Values(%d,%d,%d,%d,%s)', $tableN, $rowId, $lockN, $this->VisId, $this->TnS));
        $this->LocksA[] = $lockA;
        if (--$numLocks) {
          unset($locksA[$i]);
          continue;
        }
        $this->query('Unlock Tables');
        return;
      }
    }
    # Could not get all locks, wait to try again unless time is up
    $this->query('Unlock Tables');
    if (time() - $startT >= 10) {
      # 10 second timeout
      $this->RelBDbLocks(); # Release any locks that were obtained
      $o = $this->OptObjQuery(str_replace('count(*)', 'VisId,LockN', $firstFailQry) . ' Order by LockN Desc,StartT Desc Limit 1');
      # Lock_Login: Select VisId,LockN from Locks Where TableN=$tableN And RowId=$rowId Order by LockN Desc,StartT Desc Limit 1               For a Login Write lock to fail there must be a lock of any kind
      # Lock_Read:  Select VisId,LockN from Locks Where TableN=$tableN And RowId=$rowId And LockN=3 Order by LockN Desc,StartT Desc Limit 1   For a Read lock to fail there must be a current Write lock. Expect only 1 but make sure
      # Lock_Write: Select VisId,LockN from Locks Where TableN=$tableN And RowId=$rowId And LockN>=2 Order by LockN Desc,StartT Desc Limit 1  For a Write lock to fail there must be a Read or Write lock
      if ($o) { # expect query to give an obj but test in case not
        switch ((int)$o->LockN) {
          case Lock_Login:
            switch ($tableN) {
              case T_B_People:   $why = 's/he is logged in'; break;
              case T_B_Agents:   $why = '%s is logged in via the agent'; break;
              case T_B_Entities: $why = '%s is logged in to the entity'; break;
              default: $why = 'a lock is in place'; # shouldn't see this
            }
            break;
          case Lock_Read:  $why = '%s has the data locked for reading';  break;
          case Lock_Write: $why = '%s has the data locked for updating'; break;
        }
        if (InStr('%s', $why))
          $why = sprintf($why, $this->StrOneQuery("Select DName   from Visits Where Id=$o->VisId"));
      }else
        $why = 'a lock is in place'; # shouldn't see this
      $failCallbackFn($why);
      # not expected to return
      Error('GetBDbLocks fail callback fn returned unexpectedly');
    }
    usleep($sleepUsecs);
    $sleepUsecs += 333; # 333 microseconds more wait each time around
  }
}

# RelBDbLocks
# ~~~~~~~~~~~
# Releases the BDB visit locks created by GetBDbLock() or GetBDbLocks() in the current visit.
# Is called by SessionClose() via AjaxReturn() so that all return routes incl error cases, will release locks without needing their own RelBDbLocks() calls.
# Can also be called explicity if it is desirable to release locks sooner.
function RelBDbLocks() {
  if (count($this->LocksA)) {
    foreach ($this->LocksA as $lockA) {
      $qry = sprintf('Delete From Locks Where TableN=%d And RowId=%d And LockN=%d And VisId=%d', $lockA[0], $lockA[1], $lockA[2], $this->VisId);
      $this->query('Lock Tables Locks Write');
      $this->query($qry);
      $this->query('Unlock Tables');
    }
    $this->LocksA = [];
  }
}

# RelBDbLock
# ~~~~~~~~~~
# Releases a specific BDB lock. Releases multiple identical locks for a given VisId e.g. the Entities table locks in the AddAgent() case.
function RelBDbLock($tableN, $rowId, $lockN) {
  if (count($this->LocksA)) {
    $qry = sprintf('Delete From Locks Where TableN=%d And RowId=%d And LockN=%d And VisId=%d', $tableN, $rowId, $lockN, $this->VisId);
    $this->query('Lock Tables Locks Write');
    $this->query($qry);
    $this->query('Unlock Tables');
    foreach ($this->LocksA as $i => $lockA)
      if ($lockA[0] === $tableN && $lockA[1] === $rowId && $lockA[2] === $lockN) {
        unset($this->LocksA[$i]);
        break;
      }
  }
}


# IsBDbLockSet
# ~~~~~~~~~~~~
# Returns 1 if a BDb Lock for $tableN, $rowId, $lockN (this visit) is set, 0 if not.
function IsBDbLockSet($tableN, $rowId, $lockN) {
  return $this->OneQuery(sprintf('Select count(*) From Locks Where TableN=%d%s And LockN=%d And VisId=%d', $tableN, $rowId ? " And RowId=$rowId" : '', $lockN, $this->VisId));
}

# GetMysqlCoopLock
# Applies an MySQL cooperative lock.
# Default timeout 5 seconds.
# Lock should be released by calling ReleaseMysqlCoopLock()
/*
function GetMysqlCoopLock($lock, $to=5) {
  if (!($r = $this->OneQuery("Select GET_LOCK('$lock',$to)")))
    # $r values 1 0 or null
    LogIt("GetMysqlCoopLock('$lock') failed on "
        . (($r == '0') ? "timeout after $to seconds"  # 0
                       : 'an error.'));               # null
}

function ReleaseMysqlCoopLock($lock) {
  if (!($r = $this->OneQuery("Select RELEASE_LOCK('$lock')")))
    # $r values 1 0 or null
    LogIt("ReleaseMysqlCoopLock('$lock') "
        . (($r == '0') ? 'failed as lock was set by another thread' # 0
                       : 'call made but lock was not set.'));       # null
}
*/
} # end of BDB


####################
# Helper Functions #
####################

# LError Local helper fn for BDB
# ~~~~~~
# Assemble error message and pass it on to Error()
# Arguments:
# - $loc1OrMsg A location (fn name) string or Error message.
# - $qry  Optionally the query which failed, expected if $locOrMsg is a location.
function LError($locOrMsg, $qry=0) {
  # LogIt("LError($locOrMsg, $qry");
  Error($locOrMsg . ($qry ? " failed on query |$qry|" : ''));
}

# bool Check($val, $type)
# ~~~~~~~~~~~~~~~~~~~~~~~
# Returns true if clean, false if any unexpected or illegal characters are found
# No check is performed on strings given that just about anything is legal via utf-8.
# Various attempts to sanitize strings have not worked. See SnippetsPhp.txt
# Escaping as above MUST be used to guard against SQL injection attacks..
function Check($val, $type) {
  switch ($type) {
    case FT_INT_EQ: if ($val == '=') return true; # fall thru if not '='
    case FT_INT:    return ValidateInt($val);
    case FT_EMAIL:  return filter_var($val, FILTER_VALIDATE_EMAIL);
    case FT_BOOL:   return (ValidateInt($val) && (!$val || $val==1));
  }
  return true;
}

function ValidateInt($val) {
  return filter_var($val, FILTER_VALIDATE_INT) || $val==='0'; # FILTER_VALIDATE_INT rejects 0 but 0 is an int!
}                                                             # Using options as below didn't work for 0 either.
#$int_options = array("options"=> array("min_range"=>0, "max_range"=>1));
#return (filter_var($val, FILTER_VALIDATE_INT, $int_options));

/* S/I/B  Clean($val, $type, , $escB=false, &$escVal=0)
   ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
If $val is not clean exits via Error(ERR_Unclean) after logging it, otherwise returns according to $type:
  FT_INT_EQ: -1 if passed '=', processed as int otherwise. Is for use with Ajax updates where field value has not changed
  FT_INT:   $val cast to int
  FT_BOOL:  $val cast to bool
  FT_PW:
  FT_EMAIL: $val with all ws removed
  FT_STR:      $val trimmed and internal excess ws reduced to a single space + real_escape_string() in $escVal if $escB is set
  FT_TEXTAREA: $val trimmed + real_escape_string() in $escVal if $escB is set
*/

function Clean($val, $type, $escB=false, &$escVal=0) {
  global $DB;
  $val = trim($val);
  if ($val === '' || $val==='') { #  = unchanged string incl email client side
    if ($escB)
      $escVal = '';
    return $val;
  }
  if (Check($val, $type)) {
    # Passed Check() so now sanitize a string and set type of others
    switch ($type) {
      case FT_INT_EQ:   if ($val == '=') return -1; # fall thru if not '='
      case FT_INT:      return (int)$val;
      case FT_BOOL:     return (bool)$val;
      case FT_PW:
      case FT_EMAIL:    $val = preg_replace('/\s*/m', '', $val); break; # remove all ws for FT_PW and FT_EMAIL
      case FT_STR:      $val = trim(preg_replace('/\s\s+/m', ' ', $val)); break; # trim and reduce internal excess ws to a single space
      case FT_TEXTAREA: $val = trim($val); break; # just trim re leaving content as entered
    }
    # Here for all string cases
    if ($escB)
      $escVal = $DB->real_escape_string($val);
    return $val;
  }
  # Failed Check
  LogIt("Posted data check failed for value '$val' and type=$type");
  Error(ERR_Unclean);
}

###########
## GenPw ## Generate SHA512 password via hash() with salt based on Id
###########
function GenPw($pw, $id) {
  return hash('sha512', ($id * 37237 - 23). $pw, true); # true = binary not hex
}
