<?php /* Copyright 2011-2013 Braiins Ltd

/bdt/apps/AS/AS.php
Server ops for AS Admin Snapshot

History:
18.05.12 Extracted from AA.php
19.10.12 Updated
31.12.12 'odd' added to odd rows following table show class changes

*/
$AppAid  = 'AS';
$AppName = 'Admin Snapshot';
require '../../Base.inc';
$AppEnum = BDT_AS;
Start();
SessionOpenBDT(); # BD->*, AgentId, EntityId, TZO, MLevel

# Op        Dat Rec Dat Returned
# I Init    []      OK  Table body
# R Refresh []      OK  Table body

# Check Member permission
($DB->Bits & AP_Agent) || Error(ERR_NOT_AUTH);

# I R
switch ($Op) {
  case Op_I: # Init
  case Op_R: # Refresh
    # Dat: Table body
    # Build Snapshot table body
    # Administrators
    $memsA = [];
    $res = $DB->ResQuery(sprintf('Select DName From People Where AgentId=%d And Bits&%d=%d Order by FName', $AgentId, MB_STD_BITS | AP_Admin, MB_OK | AP_Admin));
    $n=$res->num_rows;
    while ($o = $res->fetch_object())
      $memsA[] = $o->DName;
    $res->free();
    $h='<tr><td>'.PluralWord($n, 'Administrator').'</td><td>'.implode('<br>',$memsA).'</td><td>See Admin - Members for more info.</td></tr>';

    # Members loggedin
    $memsA = $whereA = [];
  /*# Using LoginVisitTrans and LoginN
    $res = $DB->ResQuery("Select P.Id PId,V.Id VId,P.DName,(Select PageN from VisitTrans Where VisId=V.Id Order by Id desc limit 1) PageN from People P Join Visits V on V.MemId=P.Id Where P.AgentId=$AgentId And V.LoginN=3 Order by P.FName");
    $n = $res->num_rows;
    while ($o = $res->fetch_object()) {
      if ($o->PId==$DB->MemId)
        $memsA[] = $o->VId==$DB->VisId ? 'You' : 'You in another browser or window';
      else
        $memsA[] = $o->DName;
      $whereA[] = $o->PageN==PG_D_BDT ? 'In Braiins Desktop' : 'In Braiins.com';
    }*/
    # Using Login Locks
    $res = $DB->ResQuery('Select V.Id,MemId,DName,count(*) Num From Locks L Join Visits V on V.Id=L.VisId Where LockN=1 Group by L.VisId'); # 1 = Lock_Login
    $n = $res->num_rows;
    while ($o = $res->fetch_object()) {
      if ($o->MemId == $DB->MemId)
        $memsA[] = $o->Id==$DB->VisId ? 'You' : 'You in another browser or window';
      else
        $memsA[] = $o->DName;
      $whereA[] = $o->Num == 3 ? 'In Braiins Desktop' : 'In Braiins.com'; # If in BDT have 3 login locks, for People, Agent, Entity. If logged in at Braiins.com have one login lock for People
    }
    $res->free();
    $h.='<tr class=odd><td>'.PluralWord($n, 'Member').' Logged in</td><td>'.implode('<br>',$memsA).'</td><td>'.implode('<br>',$whereA).'</td></tr>';

    # Number of active entities
    $n=$DB->OneQuery(sprintf('Select count(*) from Entities Where AgentId=%d And Bits&%d=%d', $AgentId, MB_STD_BITS | EB_Active, MB_OK | EB_Active));
    $h.="<tr><td>Number of Active Entities</td><td class=c>$n</td><td>See Entities - List for more info.</td></tr>";
    # Credits
    $Credits = $DB->OneQuery("Select Credits From Agents Where Id=$AgentId");
    $h.="<tr class=odd><td>Available Credits</td><td class=c>$Credits</td><td>See Admin - Reports and Admin - Credits for more info.</td></tr>";
    AjaxReturn(OK, $h);
}

Error(ERR_Op);
