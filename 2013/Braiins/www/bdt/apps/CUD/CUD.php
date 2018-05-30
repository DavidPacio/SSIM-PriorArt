<?php /* Copyright 2011-2012 Braiins Ltd

/bdt/apps/CUD/CUD.php
Server ops for CUD Current entity Upload Data

History:
xx.11.12 Started

ToDo
====

*/
$AppAid  = 'CUD';
$AppName = 'Upload Data';
require '../../Base.inc';
$AppEnum = BDT_CUD;
require Com_Inc.'FuncsBraiins.inc';
Start();
SessionOpenBDT(); # BD->*, AgentId, EntityId, TZO, MLevel

Error(ERR_Op);

