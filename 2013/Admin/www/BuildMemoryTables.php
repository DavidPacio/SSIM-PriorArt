<?php /* Copyright 2011-2013 Braiins Ltd

/Admin/www/BuildMemoryTables.php

To be run on starting MySQL to build or ebuild the Memory Tables Status and Locks

History:
26.02.13 Started

ToDo
====

*/

require 'BaseBraiins.inc';

Head('Build Memory Tables', true);

echo '<h1 class=c>Build Memory Tables</h1><br>';

$DB->StQuery("
CREATE TABLE IF NOT EXISTS Locks (
  TableN   int unsigned not null, # 0 for system, TableN for a Table
  RowId    int unsigned not null, # 0 for system or a Table, Row Id for a Table record
  LockN    int unsigned not null, # Lock enum = type of lock Lock_Login | Lock_Read | Lock_Write
  VisId    int unsigned not null, # Visits.Id Foreign Key and BDTsessions.Id
  StartT   timestamp not null default 0, # Start Time of a lock
  Key (TableN), # Not unique
  Key (RowId),  # Not unique
  Key (LockN),  # Not unique
  Key (VisId)   # Not unique
) Engine = Memory Default Charset=utf8;
");
$DB->StQuery('Truncate Table Locks'); # in case table already existed and we are doing a rebuild

# Add Member Locks
$res = $DB->ResQuery(sprintf('Select Id,MemId,EditT From Visits Where LoginN=%d', LGN_FULL));
while ($o = $res->fetch_object()) {
  $DB->VisId = (int)$o->Id;
  $DB->AddBDbLoginLock(T_B_People, (int)$o->MemId, $o->EditT);
}
echo sprintf('Added %d Member Login Locks<br>', $res->num_rows);
$res->free();

# Add Agent and Entity Locks for those in BDT
#res = $DB->ResQuery(sprintf('Select V.Id,V.MemId,AgentId,EntityId,(select UNIX_TIMESTAMP(T.AddT) From VisitTrans T Where T.VisId=V.Id Order by T.Id Desc Limit 1) as T From Visits V Join BDTsessions B on B.Id=V.Id Where V.LoginN=%d and (select T.PageN From VisitTrans T Where T.VisId=V.Id Order by T.Id Desc Limit 1)=%d', LGN_FULL, PG_D_BDT));
$res = $DB->ResQuery(sprintf('Select V.Id,B.AgentId,B.EntityId,(select T.AddT From VisitTrans T Where T.VisId=V.Id Order by T.Id Desc Limit 1) As T From Visits V Join BDTsessions B on B.Id=V.Id Where V.LoginN=%d and (select T.PageN From VisitTrans T Where T.VisId=V.Id Order by T.Id Desc Limit 1)=%d', LGN_FULL, PG_D_BDT));
while ($o = $res->fetch_object()) {
  $DB->VisId = (int)$o->Id;
  $DB->TnS   = "'$o->T'"; # with enclosing single quotes for use by AddBDbAgentEntityLoginLocks() which normally uses the visit time
  $DB->AddBDbAgentEntityLoginLocks([(int)$o->AgentId, (int)$o->EntityId]);
}
echo sprintf('Added %d sets of Agent/Entity Login Locks<br>', $res->num_rows);
$res->free();

Footer();

