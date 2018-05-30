<?php /* Copyright 2011-2013 Braiins Ltd

/bdt/apps/CFS/CFS.php
Server ops for CFS Current entity Financial Statements

History:
01.02.12 Started
19.10.12 Updated
14.03.13 Added locking

ToDo
====
djh??
Add date range check to query
Add data check
*/
$AppAid  = 'CFS';
$AppName = 'Financial Statements';
require '../../Base.inc';
$AppEnum = BDT_CFS;
Start();
SessionOpenBDT(); # BD->*, AgentId, EntityId, TZO, MLevel

# Op       Dat Rec       Dat Returned
# I Init   []            n x [FormatId  Name  Descr]
# R Report [Formats.Id]  6 fields: [Title  Heading  Ante  Main  Post  RT Messages]

# Check permissions
($DB->Bits & AP_Read) || Error(ERR_NOT_AUTH);

# I R
switch ($Op) {
  case Op_I: # Init. No Dat.
    # Dat: n x [FormatId  Name  Descr]
    # Get Entity Info
    # Get a Read lock for the Entity
    $DB->GetBDbLock(T_B_Entities, $EntityId, Lock_Read, function($why){Error("Sorry, entity '[EName]' is not currently available for Financial Statement generation as $why.");});
    $EA = $DB->AaQuery("Select StatusN,Level,DataState,Bits From Entities Where Id=$EntityId");
    foreach ($EA as $k => $v)
      $$k = (int)$v; # -> $StatusN, $Level, $DataState, $Bits
    # Check whether login to this entity still OK
    (($Bits & MB_STD_BITS) === MB_OK && $Level <= $MLevel && $StatusN < EStatus_Dormant) || Error(ERR_EntityLoginNok); # Entity Delete bit set, or OK bit not set, or Level changed, or StatusN not one of the active ones
    if ($DataState) {
      $res=$DB->ResQuery("Select F.Id,Name,Descr from Formats F Join Entities E on E.Id=$EntityId And E.ETypeId=F.ETypeId And E.Level>=F.ELevel Where (F.Status&1) And $MLevel>=MLevel And (Not F.AgentId Or F.AgentId=$AgentId) And (Not EntityId Or EntityId=$EntityId) Order By SortKey");
      if ($res->num_rows) {
        $dat = '';
        while ($o = $res->fetch_object())
          $dat .= "$o->Id$o->Name$o->Descr";
        $dat = substr($dat, 1);
      }else
        $dat = '0No Financial Statements are available.';
    }else
      $dat = '0No Accounting Data so no Financial Statements are available.';
    AjaxReturn(OK, $dat);

  case Op_R: # Run format
    $AppN     = BDT_CFS; # CFS Current entity Financial Statements
    $FormatId = Clean($DatA[0], FT_INT); # $FormatId is the Formats.Id of the selected format
    chdir(RG_Path);
    require 'RgRun.inc'; # Generates accounts in $Html with RT messages in $RunMsg. Requires $AgentId, $EntityId, $AppN, $FormatId to have been set
                         #  and a change to RG_PATH dir to have been made, with LOG_FILE defined using an absolute path.
                         # RgRun.inc also defines $FO as the Formats DB obj for format FormatId with cols FTypeN,FileName,Name, plus vars $FTypeN and $Type
                         # RgRun.inc includes the locking
    # title = ERef, Format Name
    # hdg   = 'Format Name' for EName as at dd MM YYYY MM:HH
    $hdg = "'$FO->Name' for [EName] as at ".gmstrftime(REPORT_DateTimeFormat, time()-$TZO*60);
    AjaxReturn(OK, "[ERef], $FO->Name$hdg$Type".$Html."".str_replace(NL, '<br>', trim($RunMsg)));
}

Error(ERR_Op);
