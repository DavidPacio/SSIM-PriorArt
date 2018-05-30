<?php /* Copyright 2011-2013 Braiins Ltd

/Com/inc/Session.inc
The Session functions

History:
10.02.11 Started based on the NMS, TARI, and originally LeLotto versions
02.03.11 $DB->ErrRet use removed
07.04.11 Server funs moved to FuncsServer.inc
08.01.12 Revised for client use of html5 local storage rather than cookies
19.10.12 Changed to use of gloabl $AppEnum
31.12.12 SessionOpenBDT() changed to set globals $AgentId, $EntityId, $TZO, $MLevel directly rather than via SessionA
08.03.13 VisId moved to BDB rather than global; AccumLog -> LogIt
11.03.13 AccumLog -> LogIt
13.03.13 Added RelBDbLocks() call to SessionClose()

ToDo djh??
----
- Make encoding of VisId more obscure? Wait till know about https set up to be used
- Tentative downgrade after xx wo access
- Ip, IpN, and UserAgent update in Visits if different on a subsequent visit, as per NMS.php
- Detect bots as per NMS.php
- Add detection of from page to set PageFromN
/*

Local Function
--------------
void   NewSession($actionN, $inst=0, $scrRes=0, $tzo=0, $ssRef=0, $bVer=0)

Public Functions
----------------
void Start()
void SessionStart($inst, $scrRes, $tzo, $ssRef, $bVer)
void SessionStartBDT($ssRef, $bVer)
void SessionOpen()
void SessionOpenBDT()
void SessionOpenLite()
void SessionClose()
void SessionStatusChange($actionN)
void InsertVisitTran($actionN, $ssRef=0, $bVer=0, $loginN= -1)
*/

###################
# Local Functions # Called only from this module
###################

# NewSession
# ----------
# Create a new Session = record in Visits table
#
# Called from:
# - SessionStart() as NewSession(V_SS, $inst, $scrRes, $tzo, $ssRef, $bVer);
# - SessionOpenLite() as NewSession(V_SSL); with $DB->MemId and $DB->LoginN both 0
#
# Uses $DB->VisId as PrevId, $DB->MemId, $DB->LoginN which must be set before this fn is called.
# Returns new VisId in $DB->VisId
#
function NewSession($actionN, $inst=0, $scrRes=0, $tzo=0, $ssRef=0, $bVer=0) {
  global $DB, $IpG, $IpN, $UserAgent;
  $DB->VisId = $DB->InsertQuery(sprintf('Insert Visits Set MemId=%u,LoginN=%d,PrevId=%d,Inst=%d,ScrRes=%u,TZO=%d,Ip=\'%s\',IpN=%d,UserAgent=\'%s\',AddT=%s',
  $DB->MemId, $DB->LoginN, $DB->VisId, $inst, $scrRes, $tzo, $IpG, $IpN, $UserAgent, $DB->TnS));
  InsertVisitTran($actionN, $DB->LoginN, $ssRef, $bVer, $inst);
  LogIt("NewSession: actionN=$actionN, VisId=$DB->VisId, LoginN=$DB->LoginN, Inst=$inst");
}

####################
# Public Functions #
####################

# Start()
# -------
# First call for a Braiins module invoked via Post.
# Decodes posted Dat expected to be in form:
#  OnnVidat
#  O   - single Op code character
#  nn  - number of data fields i.e.  separated field in dat. Can be 0.
#  V   - VisId coded as VisId * SESS_MN1 + SESS_MN2, 0 if none. Could encode more e.g. with each character then coded by -47+i where i is the char index
#  dat - nn  separated fields as required for the module. Can be nothing.
# Connects to DB when through to getting a VisId
# Sets globals of
# - $DB, $DB->VisId, $DatA, $Op
function Start() {
  global $DB, $DatA, $Op, $URI;
  if (!isset($_POST['Dat'])) Error(ERR_NoDatPosted);
  $dat = $_POST['Dat'];
  LogIt($URI . ", Dat=" . $dat);
  $Op  = ord(substr($dat,0,1));
  $dat = substr($dat, 1); # Dat after leading Op character
  if (!$dat) Error(ERR_NoDatPosted); # Only 1 character of dat
  $DatA = explode('', $dat);
  $fld0 = array_shift($DatA); # remove prepended field 0 expected to be nnVi
  if (($len = strlen($fld0)) < 3 ||         # expect at least 3 characters
      !ctype_digit($fld0)        ||         # with all of them digits
      count($DatA)!=(int)substr($fld0,0,2)) # nn check
    Error(ERR_SessionInfo);
  $len -= 2;
  if ($codedVisId = (int)substr($fld0, 2, $len)) {
    $log = "CodedVisId=$codedVisId -> VisId=";
    # for ($i=0; $i<$len; ++$i)
    #   $codedVisId[$i] = chr(ord($codedVisId[$i])+47-$i);
    $visId = ($codedVisId - SESS_MN2)/SESS_MN1;
    LogIt($log.$visId);
  }else{
    LogIt('No VisId from client');
    $visId = 0;
  }
  # Connect to DB
  $DB = new BDB($visId); # $DB->VisId = $visId
  # $Sta = $DB->OneQuery('Select Sta From Status');
  # // Perform the DB or site lock check.
  # if ($Sta & STA_LOCK_DBSML)
  #   AjaxReturn(NOP_LOCK);  // op unable to be performed due to DB or site lock. Exits
}

# SessionStart
# ------------
# 11.01.12 Revised for better info from client thanks to use of sessionStorage and localStorage. V_SRM info match restart dropped
# Expect inst to be defined and > 0 as determined by Site.js via localStorage.
# new is set if this is the first boot in a new window i.e. it is not a reload, determined by virtue of sessionStorage
# If VisId is not set it means this is the first visit to site, unless localStorage has been cleared. In this case Inst should be 1.
# If VisId is set it means that this is a repeat boot, either a reload or a new browser window as per new.
#
# Cases, all applying to the current browser:
# 1. VisId set new = 0 -> reboot or reload in same browser window         -> no change in VisId or Inst
# 2. VisId set new = 1 -> been to site before, boot in new browser window -> same VisId and Inst unless browser info different in which case -> new session
# 3. VisId = 0 new = 0 -> new boot to site                                -> new VisId and Inst of 1
#
# Returns with globals of:
# - $DB->MemId (0 for unknown site visit)
# - $DB->LoginN (LGN__NOT, LGN_LIST, LGN_TENT, LGN_FULL)
# - $DB->Bits being the Member's bit settings
# - $DB->SessionA for Email, DName
function SessionStart($inst, $new, $scrRes, $tzo, $ssRef, $bVer) { # data from client
  global $DB, $IpG, $UserAgent;
  $ssRef = CompactUrl($ssRef);
  if ($DB->VisId) {
    # Passed a VisId = cases 2 or 3
    # Fetch the Visits record, which should be possible unless the member has been deleted
    if ($vO = $DB->OptObjQuery("Select MemId,LoginN,Email,DName,Bits,Inst,ScrRes,TZO,Ip,UserAgent From Visits Where Id=$DB->VisId")) {
      # Got Visits record
      $DB->MemId  = (int)$vO->MemId;
      $DB->LoginN = (int)$vO->LoginN;
      $DB->Bits   = (int)$vO->Bits;
      $DB->SessionA = ['Email' => $vO->Email, 'DName' => $vO->DName];
      if (!$new) {
        # Case 1: reboot or reload in same browser window
        # Expect instance to match. Error if not. FF does keep sessionStorage (->instance) through reboots so an instance > 1 can persist in FF even if it is now the only instance.
        if ($inst != $vO->Inst)
          Error(ERR_Session); # Instance mismatch
        if ($UserAgent==$vO->UserAgent)
          $UserAgent = false; # so that InsertVisitTran() won't include it in the VisitTrans insert.
        else{
          # UserAgent different. Can happen for case 1 due to FF storing sessionStorage values between sessions. And for Chrome update wo unloading??
          $DB->StQuery("Update Visits Set UserAgent='$UserAgent',EditT=$DB->TnS Where Id=$DB->VisId");
          LogIt("SessionStart: UserAgent change for VisId=$DB->VisId from '$vO->UserAgent' to '$UserAgent' accepted for case 1 start");
        }
        InsertVisitTran(V_SR, -1, $ssRef, $bVer); # -1 = no change to LoginN status
        LogIt("SessionStart case 1: Reboot, resuming visit $DB->VisId, instance $inst for MemId=$DB->MemId");
        return;
      }
      # Case 2: been to site before, boot in new browser window
      $logMsg = "SessionStart case 2: New boot for previous visit $DB->VisId, instance $inst for MemId=$DB->MemId";
      if ($IpG==$vO->Ip && $tzo==$vO->TZO && $scrRes==$vO->ScrRes && $inst==$vO->Inst) {
        # Ip, TZO, ScrRes and Inst same = same location and hasn't changed ScrRes and no Inst error
        $okB = true;
        if ($UserAgent==$vO->UserAgent)
          $UserAgent = false; # so that InsertVisitTran() won't include it in the VisitTrans insert
        else{
          # UserAgent different
          if (($ld = levenshtein($vO->UserAgent, $UserAgent)) <= 8) {
            # Accept as same UA with change presumably due to a browser upgrade
            $DB->StQuery("Update Visits Set UserAgent='$UserAgent',EditT=$DB->TnS Where Id=$DB->VisId");
            LogIt("SessionStart: UserAgent change for VisId=$DB->VisId from '$vO->UserAgent' to '$UserAgent' accepted based on Levenshtein distance between UAs of $ld.");
          }else
            $okB = false;
        }
        if ($okB) {
          # Accept as same visitor
          if ($DB->LoginN == LGN_FULL) {
             $DB->LoginN = $loginN = LGN_TENT;
             $DB->StQuery("Update Visits Set LoginN=$loginN,EditT=$DB->TnS Where Id=$DB->VisId");
             $logMsg .= ' with downgrade from LGN_FULL to LGN_TENT';
          }else
             $loginN = -1; # -1 = no change to LoginN status
          InsertVisitTran(V_SR, $loginN, $ssRef, $bVer);
          LogIt($logMsg);
          return;
        }
      }
      $logMsg .= ' Not accepted as same visitor, because of';
      if ($IpG != $vO->Ip)
        $logMsg .= " Ip: $vO->Ip -> $IpG";
      else if ($inst != $vO->Inst)
        $logMsg .= " Inst: $vO->Inst -> $inst";
      else if ($tzo != $vO->TZO)
        $logMsg .= " TZO: $vO->TZO -> $tzo";
      else if ($UserAgent != $vO->UserAgent)
        $logMsg .= " UserAgent: '$vO->UserAgent' -> '$UserAgent'";
      else if ($scrRes != $vO->ScrRes)
        $logMsg .= " ScrRes: $vO->ScrRes -> $scrRes";
      else
        $logMsg .= 'the code is screwed up.';
      # else start new session
      LogIt($logMsg . ". Using Member $DB->MemId info from previous record to start a new visit.");
    }else{
      # LogIt and carry on with the defaults
      LogIt("SessionStart: Invalid Visit Id $DB->VisId received from client");
      $DB->VisId = 0;
    }
  } # else no VisId

  # Here for:
  # • returning visitor with MemId info from previous Visit, different ip, inst, TZO, UserAgent, ScrRes
  # • no VisId (or invalid VisId):
  #   - new arrival
  # Create a new visit
  # NewSession uses global $DB->MemId, $DB->LoginN and current $DB->VisId as PrevId, defines new $DB->VisId.
  if ($DB->LoginN == LGN_FULL)
    $DB->LoginN = LGN_TENT;
  NewSession(V_SS, $inst, $scrRes, $tzo, $ssRef, $bVer); # $DB->VisId 0 if no prev Visit found
} # end of SessionStart

# SessionStartBDT Session start for Braiins Desktop Boot.
# ---------------
# Start() should have been called before this.
# Expect to have a valid session on coming from Braiins. Return to Braiins if not.
#
# Returns with globals of:
#   - $DB->MemId  MemId
#   - $DB->LoginN LGN__FULL
#   - $DB->Bits   the Member's bit settings
#   - $DB->VisId  the Visit Id = 0 if not OK
#   - $DB->SessionA for TZO, MLevel, DName
#
function SessionStartBDT($ssRef, $bVer) { # data from client
  global $DB, $IpG, $UserAgent;
  $ssRef = CompactUrl($ssRef);
  if ($DB->VisId) {
    # Appear to have valid VisId
    # Try to fetch the Visits record, which should be possible unless the member has been deleted
    if ($vO = $DB->OptObjQuery("Select MemId,LoginN,V.DName,V.Bits,TZO,P.Level From Visits V Join People P On P.Id=V.MemId Where V.Id=$DB->VisId")) {
      # Got Visits record
      $DB->MemId  = (int)$vO->MemId;
      $DB->LoginN = (int)$vO->LoginN;
      $DB->Bits   = (int)$vO->Bits;
      $DB->SessionA = ['TZO' => (int)$vO->TZO, 'MLevel' => $vO->Level, 'DName' => $vO->DName];
      $UserAgent = false; # so that InsertVisitTran() won't include it in the VisitTrans insert
      InsertVisitTran(V_SR, -1, $ssRef, $bVer); # -1 = no change to LoginN status
      LogIt("SessionStartBDT: Reboot, resuming visit $DB->VisId for MemId=$DB->MemId");
      return;
    }else
      # no BDTSessions record so LogIt and return VisId of 0 = no go
      LogIt("SessionStartBDT: Invalid Visit Id $DB->VisId received from client");
    $DB->VisId = 0;
  } # else no VisId

  # Here for:
  # • returning visitor with MemId info from previous Visit, different ip or UserAgent
  # • no VisId (or invalid VisId):
  Error(ERR_Session); # Invalid VisId
} # end of SessionStartBDT

# SessionOpen
# -----------
# Start() should have been called before this.
# SessionOpen is used when a Visit is expected to exist - no browser info passed from client.
# Initiating or renewing a Visit is handled by SessionStart.
# If anything is not OK, SessionOpen errors.
# Returns with globals of:
# - $DB->MemId (0 for unknown site visit)
# - $DB->LoginN (LGN__NOT, LGN_LIST, LGN_TENT, LGN_FULL)
# - $DB->Bits being the Member's bit settings
# - $DB->VisId  the Visit Id
# - $DB->SessionA for Email, DName
function SessionOpen() {
  global $DB, $Op;
  if ($DB->VisId) {
    # Appear to have valid VisId
    # Try to fetch the Visits record, which should be possible
    if ($vO = $DB->OptObjQuery("Select MemId,LoginN,Email,DName,Bits From Visits Where Id=$DB->VisId")) {
     ($DB->MemId  = (int)$vO->MemId) or ($Op < 97 && Error("SessionOpen: Visit $DB->VisId does not include a MemId which the operation requires")); # Error aborts
      $DB->LoginN = (int)$vO->LoginN;
      $DB->Bits   = (int)$vO->Bits;
      $DB->SessionA = ['Email' => $vO->Email, 'DName' => $vO->DName];
      LogIt("SessionOpen: Resuming visit $DB->VisId for MemId=$DB->MemId");
      # skip inserting a session open visit tran if the op is a Log in ('l' Op_l) or log Out ('O' Op_O) one which results in another visit tran if succesful
      # 04.02.12 djh Changed to skip always as SessionOpen() is only called for Login, Logout, and Reg, all of which ressult in InsertVisitTran() calls
      #if ($Op!=Op_l && $Op!=Op_O)
      #  InsertVisitTran(V_SO);
      return;
    }else
      LogIt("SessionOpen: Invalid VisId $DB->VisId received from client");
  } # else no VisId
  # No VisId or invalid VisId
  Error(ERR_Session); # Invalid VisId in SessionOpen
} # end of SessionOpen

# SessionOpenBDT
# ---------------
# Start() should have been called before this.
# SessionOpenBDT is used for Braiins Desktop Ajax modules.
# No InsertVisitTran() call. The VisitTran of the SessionStartBDT() call applies, which can be found if needed based on MemId and time.
# Returns with globals of:
# - $DB->MemId
# - $DB->Bits being the Member's bit settings
# - $DB->VisId  the Visit Id
# - $AgentId
# - $EntityId
# - $TZO
# - $MLevel
function SessionOpenBDT() {
  global $DB, $AgentId, $EntityId, $TZO, $MLevel;
  if ($DB->VisId) {
    # Appear to have valid VisId
    # Try to fetch the BDTSessions record, which should be possible
    if ($sO = $DB->OptObjQuery("Select MemId,AgentId,EntityId,TZO,MLevel,MBits From BDTsessions Where Id=$DB->VisId")) {
      # Yes
      $DB->MemId = (int)$sO->MemId;
      $DB->Bits  = (int)$sO->MBits;
      $AgentId   = (int)$sO->AgentId;
      $EntityId  = (int)$sO->EntityId;
      $TZO       = (int)$sO->TZO;
      $MLevel    = (int)$sO->MLevel;
      LogIt("SessionOpenBDT: Resuming Visit $DB->VisId for MemId=$DB->MemId");
      return;
    }else
      # No BDT Session
      LogIt("SessionOpenBDT: Invalid VisId $DB->VisId received from client");
  }
  # No VisId
  Error(ERR_Session); # Invalid VisId in SessionOpenBDT
} # end of SessionOpenBDT

/* SessionOpenLite
# ---------------
# SessionOpenLite is used for a stand alone module e.g. an Optin module run from an email link,
#  to perform a simlified version of SessionStart and Session Open.
# Uses a previous session if found, o'wise starts a new one wo full browser info
# Returns with globals of:
# - $DB->MemId (0 for unknown site visit)
# - $DB->VisId  the Visit Id
# - $DB->LoginN (LGN__NOT, LGN_LIST, LGN_TENT)
# - $DB->Bits being the Member's bit settings
function SessionOpenLite() {
  global $DB;
  if ($DB->VisId) {
    # Appear to have valid VisId
    # Try to fetch the Visits record, which should be possible
    if ($vO = $DB->OptObjQuery("Select MemId,LoginN,Email,DName,Bits,TZO From Visits Where Id=$DB->VisId")) {
      $DB->MemId  = (int)$vO->MemId;
      $DB->LoginN = (int)$vO->LoginN;
      $DB->Bits   = (int)$vO->Bits;
      # #wc if !DEV exclude next line
      LogIt("SessionOpenLite: Resuming Visit $DB->VisId, MemId=$DB->MemId");
      InsertVisitTran(V_SOL);
      return;
    }else
      LogIt("SessionOpenLite: Invalid Visit Id $DB->VisId received from client");
  } # else no VisId

  # Create new visit
  # NewSession uses global $DB->MemId, $DB->LoginN (both 0 here) and current $DB->VisId as PrevId, defines new $DB->VisId.
  NewSession(V_SSL);
  # #wc if !DEV exclude next line
  LogIt("SessionOpenLite: New Session Id=$DB->VisId, MemId=0");
} # end of SessionOpenLite
*/

# SessionClose
# ------------
# SessionClose is called by AjaxReturn() when php modules are exiting.
function SessionClose() {
  global $DB;
  $DB->RelBDbLocks(); # Release BDB locks if set
}

# SessionStatusChange($actionN)
# -----------------------------
# Cases via parameter $actionN:
#  V_LO LogOut
#  V_LT Logout Totally
#
#  V_SR case is handled by SessionStart()
#  V_LI LogIn cases are handled in Login.php and Reg.php
#
# Called as:
#  SessionStatusChange(V_LO) Main site Logout.php and bdt BDT.php
#  SessionStatusChange(V_LT) Main site Boot.php
#  SessionStatusChange(V_LT) Main site Reg.php

# Argument:
#  $actionN as above
#
# Sets $DB->LoginN
#
function SessionStatusChange($actionN) {
  global $DB, $ActionsA;
  switch ($actionN) {
    case V_LO: # Log Out (Full -> Tent)
      $loginN = LGN_TENT; break;
    default:   # unknown actionN
      Error('SessionStatusChange: Unknown action code passed');
      # fall thru to log out totally
    case V_LT: # Log Out Totally
      $loginN = LGN_NOT;  break;
  }
  InsertVisitTran($actionN, $loginN);
  $DB->StQuery("Update Visits Set LoginN=$loginN,EditT=$DB->TnS Where Id=$DB->VisId");
  $DB->LoginN=$loginN;
  # #wc if !DEV exclude next line
  LogIt("SessionStatusChange: ActionN={$ActionsA[$actionN]}, VisId=$DB->VisId, LoginN=$loginN");
} # end of SessionStatusChange

# InsertVisitTran
# ---------------
# djh?? add URI -> PageFromN detection
# Session start or resume cases called as:                from
#  InsertVisitTran(V_SS,  LGN_TENT, $ssRef, $bVer, $inst) NewSession() via SessionStart()
#  InsertVisitTran(V_SS,  LGN_NOT,  $ssRef, $bVer, $inst) NewSession() via SessionStart()
#  InsertVisitTran(V_SSL, LGN_NOT,  0,      0,     0)     NewSession() via SessionOpenLite()
#  InsertVisitTran(V_SR,  -1,       $ssRef, $bVer)        SessionStart()
#  InsertVisitTran(V_SR,  LGN_TENT, $ssRef, $bVer)        SessionStart() for login downgrade case
#  InsertVisitTran(V_SR,  -1,       $ssRef, $bVer)        SessionStartBDT()
# Session open | change cases called from
#  InsertVisitTran(V_SO)             SessionOpen()
#  InsertVisitTran(V_SOL)            SessionOpenLite()
#  InsertVisitTran(V_LO,  LGN_TENT)  SessionStatusChange() from Logout.php
#  InsertVisitTran(V_LT,  LGN_NOT)   SessionStatusChange() from Boot.php & Reg.php
#  InsertVisitTran(V_LI,  LGN_FULL)  Login.php
#  InsertVisitTran(V_LIF)            "
#  InsertVisitTran(V_LI,  LGN_FULL)  Reg.php
#  InsertVisitTran(V_UP)             "
#
function InsertVisitTran($actionN, $loginN= -1, $ssRef=0, $bVer=0, $inst=0) {
  global $DB, $URI, $Op, $IpG, $IpN, $UserAgent, $ActionsA, $AppEnum;
  $qry = "Insert VisitTrans Set VisId=$DB->VisId,ActionN=$actionN,Op=$Op,AddT=$DB->TnS,AppN=$AppEnum";
  if ($DB->MemId > 0)
    $qry .= ",MemId=$DB->MemId";
  if ($actionN <= V_SR) {
    # Session start or resume cases called as:                from
    #  InsertVisitTran(V_SS,  LGN_TENT, $ssRef, $bVer, $inst) NewSession() via SessionStart()
    #  InsertVisitTran(V_SS,  LGN_NOT,  $ssRef, $bVer, $inst) NewSession() via SessionStart()
    #  InsertVisitTran(V_SSL, LGN_NOT,  0,      0,     0)     NewSession() via SessionOpenLite()
    #  InsertVisitTran(V_SR,  -1,       $ssRef, $bVer)        SessionStart()
    #  InsertVisitTran(V_SR,  LGN_TENT, $ssRef, $bVer)        SessionStart() for login downgrade case
    #  InsertVisitTran(V_SR,  -1,       $ssRef, $bVer)        SessionStartBDT()
    $qry .= ",URI='$URI',SSreferrer='$ssRef'";
    if ($actionN < V_SR) # V_SS or V_SSL
      $qry .= ",Inst=$inst,Ip='$IpG',IpN=$IpN";
    if ($UserAgent) # set to false in Start fns if same as previous Visits value
      $qry .= ",UserAgent='$UserAgent'";
    if ($loginN >= 0)
      $qry .= ",LoginN=$loginN";
    if ($bVer) {
      # 2.1.1 App Enum.Page #. Version # with PageN stored as AppN*100+page #
      $verA = explode('.', $bVer);
      if (($pageN=$verA[0]) != $AppEnum) {
        Error("Bver site # missmatch. Bver='$bVer', AppEnum=$AppEnum", 1); # returns
        $pageN=$AppEnum;
      }
      $pageN *= 100;
      if (isset($verA[1]) && is_numeric($verA[1]))
        $pageN += $verA[1];
      $ver = ((isset($verA[2]) && is_numeric($verA[2])) ? $verA[2] : 0);
      $qry .= ",PageN=$pageN,Ver=$ver";
    }
    if ($ref = GetReferrer())
      $qry .= ",Referrer='$ref'";
  }else
    # Session open | change cases called from
    #  InsertVisitTran(V_SO)             SessionOpen()
    #  InsertVisitTran(V_SOL)            SessionOpenLite()
    #  InsertVisitTran(V_LO,  LGN_TENT)  SessionStatusChange() from Logout.php
    #  InsertVisitTran(V_LT,  LGN_NOT)   SessionStatusChange() from Boot.php & Reg.php
    #  InsertVisitTran(V_LI,  LGN_FULL)  Login.php
    #  InsertVisitTran(V_LIF)            "
    #  InsertVisitTran(V_LI,  LGN_FULL)  Reg.php
    #  InsertVisitTran(V_UP)             "
    if ($loginN >= 0)
      $qry .= ",LoginN=$loginN";
  #LogIt($qry);
  $visTrId = $DB->InsertQuery($qry);
  # #wc if !DEV exclude next line
  LogIt("VisitTran: $visTrId {$ActionsA[$actionN]}");
}
/*
# VisitTrans.ActionN enum values
# ------------------  Op code
const V_SS  =  1; #   Session Start  = SessionStart and new Visits record created on an 'i' or 'I' initialisation op
const V_SSL =  2; #   Session Start via session Lite
const V_SR  =  3; #   Session Resume = SessionStart and previous Visits record used on an 'i' or 'I' initialisation op
const V_SO  =  4; #   Session Open == an operation on a page after an 'i' or 'I' initialisation op
const V_SOL =  5; #   Session Open via session Lite
const V_LI  =  6; # l Login OK
const V_LIF =  7; # l Login fail
const V_LO  =  8; # O LogOut       -> LGN_TENT
const V_LT  =  9; # T Logout Totally i.e. -> LGN_NOT with new LGN_NOT session.
const V_UP  = 10; #   Visit update for logged in member e.g. bits change
const V_UN  = 11; #   Page Unload - not a menu/btn caused page unload. Tran could be deleted by cron task if immediately followed by a V_SR tran
*/

# Temporary global
# #wc if !DEV exclude next line
$ActionsA = [
  V_SS  => 'V_SS Session Start',
  V_SSL => 'V_SSL Session Start Lite',
  V_SR  => 'V_SR Session Resume',
  V_SO  => 'V_SO Session Open',
  V_SOL => 'V_SOL Session Open Lite',
  V_LI  => 'V_LI Login',
  V_LIF => 'V_LIF Login fail',
  V_LO  => 'V_LO LogOut',
  V_LT  => 'V_LT Logout Totally',
  V_UP  => 'V_UP Visit UPdate',
  V_UN  => 'V_UN Unload'];
