<?php /* Copyright 2011-2013 Braiins Ltd

/bdt/srv/BDT.php
Server ops for the Ajax calls to boot and logout of the Braiins Desktop

History:
23.02.11 Written
08.01.12 Revised for client use of html5 local storage rather than cookies
17.02.12 Renamed from Boot.php and prev Logout.php merged into it.
11.06.12 Added passing of AppsA on Boot
02.03.13 Added the adding and releasing of MemberAgentEntity Login Locks

ToDo djh??
----

Change to rebuilding BDTSessions on each boot re possible changes

*/
$AppAid  = 'BDT';
$AppName = 'The Braiins Desktop';
require '../Base.inc';
$AppEnum = APP_BDT;
Start(); # Sets Visit info and connects to DB

# Op       Dat Rec                          Dat Returned
# B Boot   [Referrer | Bver | Coded VisId]  [DName  | MLevel | MBits |  AName |  Ref | EName | ELevel | AppsA] 8 fields
# O logOut None                             None
# u Unload None                             None

switch ($Op) {
  case Op_B: # Boot BDT
    $AppsA=[ # Defaults in BDT.js: AP AP_Read, w 600, h 400
      ['AppN'=>10,'Id'=>'CUD','AP'=>AP_Up,     'w'=>640,          'title'=>'Upload Data'],
      ['AppN'=>13,'Id'=>'CDT',                           'h'=>430,'title'=>'Data Trail'],
      ['AppN'=>15,'Id'=>'CFS',                                    'title'=>'Financial Statements','tbCap'=>'Financial State'],
      ['AppN'=>16,'Id'=>'CFD','AP'=>AP_Down,                      'title'=>'FS Download'],
      ['AppN'=>17,'Id'=>'CSF','AP'=>AP_Entity, 'w'=>640,          'title'=>'Set to Final'],
      ['AppN'=>20,'Id'=>'EC',                            'h'=>410,'title'=>'Change Entity'],
      ['AppN'=>21,'Id'=>'EL',                  'w'=>540,          'title'=>'List Entities'],
      ['AppN'=>22,'Id'=>'EN', 'AP'=>AP_Entity, 'w'=>660, 'h'=>606,'title'=>'New Entity'],
      ['AppN'=>23,'Id'=>'EE', 'AP'=>AP_Entity, 'w'=>660, 'h'=>610,'title'=>'Edit Current Entity'],
      ['AppN'=>24,'Id'=>'ER', 'AP'=>AP_Delete, 'w'=>700, 'h'=>475,'title'=>'Reset Entity'],
      ['AppN'=>25,'Id'=>'ED', 'AP'=>AP_Delete, 'w'=>700, 'h'=>510,'title'=>'Delete Entity'],
      ['AppN'=>40,'Id'=>'AS', 'AP'=>AP_Agent,  'w'=>700, 'h'=>350,'title'=>'Snapshot'],
      ['AppN'=>43,'Id'=>'AA', 'AP'=>AP_Agent,  'w'=>710, 'h'=>380,'title'=>'Account Details'],
      ['AppN'=>44,'Id'=>'AM', 'AP'=>AP_Members,'w'=>500, 'h'=>475,'title'=>'Members'],
    //['AppN'=>45,'Id'=>'AH', 'AP'=>AP_Agent,  'w'=>999, 'h'=>610,'title'=>'Headings','js'=>['pis/dt/BEdit.js']],
      ['AppN'=>45,'Id'=>'AH', 'AP'=>AP_Agent,  'w'=>1024,'h'=>485,'title'=>'Headings']];

    # should be passed client info
    #           0       1  after XCoded VisIdY removed
    # Dat = Referrer | Bver
    #               Referrer Bver
    SessionStartBDT($DatA[0], $DatA[1]); # no need to clean these as the Sessions fns do this. Inserts visit tran.
    extract($DB->SessionA); # -> TZO, MLevel, DName
    # Expect to be logged in
    if ($DB->LoginN < LGN_FULL) { # not LGN_FULL or LGN_GUEST
      # Should not be here if not logged in
      LogIt("BDT Boot failed - not logged in");
      Error(NOT_LOGGEDIN);
    }
    $MBits = $DB->Bits;
    $MemId = $DB->MemId;
    $VisId = $DB->VisId;
    if (($MBits & MB_STD_BITS) !== MB_OK) # MB_STD_BITS = MB_DELETE | MB_OK
      # Member in session but not OK due to deletion or permissions changed presumably so log previous member out totally
      Error("Member $MemId Bits not OK for BDT access.");

    # Get Agent to check BDT session or for use in creating one
    if (!$AgentId = $DB->OneQuery("Select AgentId From People Where Id=$MemId Order by Id Limit 1"))
      Error("BDT Boot Error: No AgentId set for Member $MemId");
    # See if a BDT session exists
    if ($sO = $DB->OptObjQuery("Select MemId,AgentId,EntityId,TZO,MLevel,MBits From BDTsessions Where Id=$VisId")) {
      # Yes
      while (true) {
        if ($MemId === (int)$sO->MemId) {
          # Same member
          if ($AgentId === (int)$sO->AgentId) {
            # Same Agent
            $EntityId = (int)$sO->EntityId; # Entity as per BDTSessions
            # Check that this Entity is still OK
            if ($eO = $DB->OptObjQuery("Select AgentId,Level,Bits From Entities Where Id=$EntityId")) {
              # Check whether login to this entity still OK
              if ((int)$eO->AgentId === $AgentId && ((int)$eO->Bits & (MB_STD_BITS | EB_Active)) === (MB_OK | EB_Active) && (int)$eO->Level <= $MLevel) {
                # Entity OK
                if ($TZO != $sO->TZO || $MLevel !== $sO->MLevel || $MBits != $sO->MBits)
                  # TZO, Member Level, or Bits changed so update the Session
                  $DB->StQuery("Update BDTsessions Set TZO=$TZO,MLevel=$MLevel,MBits=$MBits,EditT=$DB->TnS Where Id=$VisId");
                break; # Done
              } # else AgentId wrong or Entity not ok to login to
            } # else entity not found
          } # else Different Agent
        } # else Different Member
        # For all not ok cases get EntityId and update session
        $EntityId = GetEntity($AgentId);
        $DB->StQuery("Update BDTsessions Set MemId=$MemId,AgentId=$AgentId,EntityId=$EntityId,TZO=$TZO,MLevel=$MLevel,MBits=$MBits,EditT=$DB->TnS Where Id=$VisId");
        break;
      }
    }else{
      # No BDT Session so create one
      # Get EntityId
      $EntityId = GetEntity($AgentId);
      $DB->StQuery(sprintf('Insert BDTsessions Set Id=%u,MemId=%u,AgentId=%u,EntityId=%u,TZO=%d,MLevel=%d,MBits=%u,AddT=%s',
                           $VisId,$MemId,$AgentId,$EntityId,$TZO,$MLevel,$MBits,$DB->TnS));
    }
    # Get Agent Name from the Agent Entity, Entity Name, Entity Ref, Entity Level and check that Entity hasn't been set to Delete or not OK
    $aO = $DB->ObjQuery("Select A.EName AName,A.Bits ABits,E.EName,E.Ref,E.Bits EBits,E.Level From Entities A Join Entities E on A.Id=E.AgentId where E.Id=$EntityId");
    # Set Member Master bit if Agent is a master
    if ((int)$aO->ABits & MB_MASTER) $MBits |= MB_MASTER;

    # Add Member Login Lock if not there already i.e. after MySQL server restart and FF tab reload
    if (!$DB->IsBDbLockSet(T_B_People, $MemId, Lock_Login))
      $DB->AddBDbLoginLock(T_B_People, $MemId, $DB->StrOneQuery("Select EditT From Visits Where Id=$VisId"));

    # Add the Login Locks
    $DB->AddBDbAgentEntityLoginLocks([$AgentId, $EntityId]);

    # Returns 8 fields:
    #                DName |  MLevel  | MBits  |      AName |      ERef  |     EName |     ELevel  | AppsA
    AjaxReturn(OK, "$DName$MLevel$MBits$aO->AName$aO->Ref$aO->EName$aO->Level".json_encode($AppsA));

  case Op_O: # logOut
    SessionOpenBDT(); # -> $DB->MemId, $DB->Bits, AgentId, EntityId, TZO, MLevel, not $DB->LoginN
    # LGN_FULL -> LGN_TENT
    /* Every other state remains unchanged with a NOT_LOGGEDIN return
    # Expect to be logged in
    if ($DB->LoginN < LGN_FULL) { # not LGN_FULL or LGN_GUEST - don't have $DB->LoginN
      # Should not be here if not logged in
      LogIt("Logout: Was not logged in");
    }else */
    SessionStatusChange(V_LO);
    # Release Agent and Entity Login Locks
    $DB->RelBDbAgentEntityLoginLocks([$AgentId, $EntityId]);
    #AjaxReturn(OK);
    exit;

  case Op_u: # Unload page i.e. close tab or navigate away
    SessionOpenBDT(); # -> $DB->MemId, $DB->Bits, AgentId, EntityId, TZO, MLevel, not $DB->LoginN
    InsertVisitTran(V_UN);
    # Release Agent and Entity Login Locks
    $DB->RelBDbAgentEntityLoginLocks([$AgentId, $EntityId]);
    #AjaxReturn(OK);
    exit;
}
Error(ERR_Op);
#############

function GetEntity($agentId) {
  global $DB, $MLevel; #                                                               (Bits & (MB_STD_BITS | EB_Active) == (MB_OK | EB_Active) And Level<=$MLevel_Dormant
  if (!$entityId = $DB->OneQuery(sprintf('Select Id From Entities Where AgentId=%d And (Bits&%d)=%d And Level<=%d Order by Id Limit 1', $agentId, MB_STD_BITS | EB_Active, MB_OK | EB_Active, $MLevel)))
    Error("BDT Boot Error: No Entity available with Level <= $MLevel for Agent $agentId");
  return $entityId;
}

