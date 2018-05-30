<?php /* Copyright 2011-2013 Braiins Ltd

/bdt/apps/EL/EL.php
Ajax server ops for EL Entities List (List Entities)
No locking

History:
06.01.12 Started
04.05.12 Company Size added
19.10.12 Updated and revised to use SlickGrid
09.11.12 Revised for change to Bro class
13.01.13 Include Demo Entities option added; Level col made independent of selection criteria level

ToDo
====

ASssign own column widths?
http://www.datatables.net/usage/columns
Note sContentPadding

Get rid of the subquery for AcctsD

*/
$AppAid  = 'EL';
$AppName = 'List Entities';
require '../../Base.inc';
$AppEnum = BDT_EL;
require Com_Inc_Tx.'ConstantsRg.inc';
require Com_Inc.'FuncsBRL.inc';
require Com_Inc.'DateTime.inc';
Start();
SessionOpenBDT(); # BD->*, AgentId, EntityId, TZO, MLevel

# Op Dat Rec                                             Dat Returned
# R  [13 fields: 12 x <0|1> checkbox selections | level] [5 fields: Title  Heading  Ante  Main  Post] report content

# Check op and permission
($Op == Op_R) || Error(ERR_Op);
($DB->Bits & AP_Read) || Error(ERR_NOT_AUTH);

# Options                    Column       Data Source
const Col_Ref        =  0; # Entity Ref   Entities.Ref  Always included - not posted
const Col_Name       =  1; # Entity Name  Entities.EName
const Col_Type       =  2; # Type         Entities.ETypeId converted to string by EntityTypeStr()
const Col_CoSize     =  3; # Company Size Entities.ESizeId converted to string by EntitySizeStr()
const Col_YearEnd    =  4; # Year End     Bro BroId_Dates_YearEndDate for EntYear=CurrYear
const Col_DataYears  =  5; # Data Years   Via Bro BroId_Dates_YearEndDate for EntYear=CurrYear, EntYear+1=CurrYear, EntYear+2=CurrYear, EntYear+3=CurrYear
const Col_DataState  =  6; # Data State   Entities.DataState
const Col_AcctsState =  7; # Accts State  Entities.AcctsState
const Col_AcctsDate  =  8; # Accts Date   AddT of latest EntityTrans tran of ActioN1=ETA_RgStat in CurrYear for a Format of FTypeN = RGFT_Stat. Can't just check for ActioN2 = ETA_SetAcctsState as that is only set when the state changes.
const Col_Manager    =  9; # Manager      Entities.ManagerId -> Managers.PeoId -> People.DName
const Col_Level      = 10; # Level        Entities.Level
const Col_Status     = 11; # Status       Entities.StatusN converted to string by EntityStatusStr()
const Col_Comments   = 12; # Comments     Entities.Comments
const Incl_Demo_Ents = 13; # Entity Selection Criteria: Include Demo Entities
const Incl_Level     = 14; #                            Include Entities with Level <= this
const Num_Fields     = 14; # = 15 minus 1 for Col_Ref not passed

$ColInfoA = [
  [150, 'Reference'],   #  0 => width, 1 => name,
  [300, 'Entity Name'],
  [170, 'Entity Type'],
  [ 50, 'Size'],
  [ 90, 'Year End'],
  [ 95, 'Data Years'],
  [150, 'Data State'],
  [150, 'Accts State'],
  [100, 'Accts Date'],
  [150, 'Manager'],
  [ 55, 'Level'],
  [ 65, 'Status'],
  [300, 'Comments']
];

#DumpLog('EL.php DataA before Clean',$DatA);
count($DatA) == Num_Fields || Error(ERR_CLIENT_DATA);
# pop level off the end and check it vs MLevel
($level = Clean(array_pop($DatA), FT_INT)) <= $MLevel || Error(ERR_CLIENT_DATA); # expect $level to be <= $MLevel
# pop Incl_Demo_Ents
$inclDemoEnts = Clean(array_pop($DatA), FT_INT);
# Cols
$colsA  = [0]; # Col_Ref always included
$colLast =  Col_Ref;
foreach ($DatA as $i => $v)
  if (Clean($v, FT_INT))
    $colsA[$colLast=($i+1)] = 1;
$tzos  = $TZO*60; # member's browser TZO in secss
# Build query strings
$sele = $join = '';
$yearEndDateBroId = BroId_Dates_YearEndDate;
foreach ($colsA as $col => $t)
  switch ($col) {
    case Col_Name:         # Entity Name
      $sele .= ',EName';
      break;
    case Col_Type:         # Type
      $sele .= ',ETypeId';
      break;
    case Col_CoSize:       # CoSize
      $sele .= ',ESizeId';
      break;
    case Col_YearEnd:      # Year End
      $sele .= ',B0.BroStr Y0';
      $join .= " Left Join Bros B0 On B0.EntityId=E.Id And B0.EntYear=E.CurrYear And B0.BroId=$yearEndDateBroId";
      break;
    case Col_DataYears:    # Data Years
      if (!isset($colsA[Col_YearEnd])) {
        $sele .= ',B0.BroStr Y0';
        $join .= " Left Join Bros B0 On B0.EntityId=E.Id And B0.EntYear=E.CurrYear And B0.BroId=$yearEndDateBroId";
      }
      $sele .= ',B1.BroStr Y1,B2.BroStr Y2,B3.BroStr Y3';
      $join .= " Left Join Bros B1 On B1.EntityId=E.Id And B1.EntYear+1=E.CurrYear And B1.BroId=$yearEndDateBroId Left Join Bros B2 On B2.EntityId=E.Id And B2.EntYear+2=E.CurrYear And B2.BroId=$yearEndDateBroId Left Join Bros B3 On B3.EntityId=E.Id And B3.EntYear+3=E.CurrYear And B3.BroId=$yearEndDateBroId";
      break;
    case Col_DataState:    # Data State
      $sele .= ',DataState';
      break;
    case Col_AcctsState:   # Accts State
      $sele .= ',AcctsState';
      break;
    case Col_AcctsDate:    # Accts Date
      $sele .= ',(Select UNIX_TIMESTAMP(ET.AddT) from EntityTrans ET Left Join Formats F On F.Id=ET.Info1 And F.FTypeN='.RGFT_Stat.' Where ET.EntityId=E.Id And EntYear=E.CurrYear And ActioN1='.ETA_RgStat.' Order by ET.Id Desc Limit 1) AcctsD';
      break;
    case Col_Manager:      # Manager
      $sele .= ',DName';
      $join .= ' Join People P On P.Id=E.ManagerId';
      break;
    case Col_Level:        # Level
      $sele .= ',E.Level';
      break;
    case Col_Status:       # Status
      $sele .= ',StatusN';
      break;
    case Col_Comments:     # Comments
      $sele .= ',Comments';
  }

########
# Ante #
########
$ante  = $inclDemoEnts ? '' : 'excluding the Demo Entities';
$ante .= ($level < MLevel_Max ? (($inclDemoEnts ? '' : ', ')."for Entities with Level less than or equal to $level") : '');
$ante .= ($ante ? '<br>' : '').'Report generated by <span class=DName></span>, <span class=AName></span>.';

########
# Main #
########
$width = $wFactor = 0;
foreach ($colsA as $col => $t)
  $width += $ColInfoA[$col][0];
$adjWidth = max($width, 750); # Report at least 750 px wide
$wFactor  = $adjWidth/$width;
$main = "<div class='w sgContainer nobb' style=width:{$adjWidth}px>
<div class=sgHdr><span class=sgHdrBox>Filter: <input id=ELfil></span></div>
  <div id=ELsg></div>
</div>
<script>
var data = [";
/* Build data[]
Select Ref,EName,ETypeId,B0.BroStr Y0,B1.BroStr Y1,B2.BroStr Y2,B3.BroStr Y3,DataState,AcctsState,
  (Select UNIX_TIMESTAMP(ET.AddT) from EntityTrans ET Left Join Formats F On F.Id=ET.Info1 And F.FTypeN=1
    Where ET.EntityId=E.Id And EntYear=E.CurrYear And ActioN1=6 Order by ET.Id Desc Limit 1) AcctsD,
  DName,Text,StatusN,E.Level,Comments
  From Entities E Left Join Bros B0 On B0.EntityId=E.Id And B0.EntYear=E.CurrYear And B0.BroId=18
                  Left Join Bros B1 On B1.EntityId=E.Id And B1.EntYear+1=E.CurrYear And B1.BroId=18
                  Left Join Bros B2 On B2.EntityId=E.Id And B2.EntYear+2=E.CurrYear And B2.BroId=18
                  Left Join Bros B3 On B3.EntityId=E.Id And B3.EntYear+3=E.CurrYear And B3.BroId=18
                  Join People P On P.Id=E.ManagerId
  Where E.AgentId=1 And E.Level<=9 And not E.Bits&1

    data.push({Ref:d[e++], EName:d[e++]});


*/
#                LogIt("Select Ref$sele From Entities E$join Where E.AgentId=$AgentId And E.Level<=$level And not E.Bits&1");

$qry = "Select Ref$sele From Entities E$join Where E.AgentId=$AgentId And E.Level<=$level And not E.Bits&1".($inclDemoEnts ? '' : ' And E.StatusN!='.EStatus_Demo);
$res = $DB->ResQuery($qry);
if ($res->num_rows) {
  while ($o = $res->fetch_object()) {
    $row = '{';
    foreach ($colsA as $col => $t) {
      switch ($col) {
        case Col_Ref:        $dat = $o->Ref; break;
        case Col_Name:       $dat = $o->EName; break;
        case Col_Type:       $dat = EntityTypeStr($o->ETypeId); break;
        case Col_CoSize:     $dat = EntitySizeStr($o->ESizeId); break;
        case Col_YearEnd:    $dat = ($o->Y0 ? eeDtoStr(substr($o->Y0,2), REPORT_DateFormat) : 'Not set yet'); break; # substr($o->Y0,2) to strip off the leading SrceNChr DatType characters expected to be 13 for this Date Bro
        case Col_DataYears:  $dat = DataYears([$o->Y0,$o->Y1,$o->Y2,$o->Y3]); break;
        case Col_DataState:  $dat = EntityDataStateStr($o->DataState); break;
        case Col_AcctsState: $dat = EntityAcctsStateStr($o->AcctsState); break;
        case Col_AcctsDate:  $dat = ($o->AcctsD ? gmstrftime(REPORT_DateFormat, $o->AcctsD-$tzos) : 'Not run'); break;
        case Col_Manager:    $dat = $o->DName; break;
        case Col_Level:      $dat = $o->Level; break;
        case Col_Status:     $dat = EntityStatusStr($o->StatusN); break;
        case Col_Comments:   $dat = str_replace('', '<br>', $o->Comments); break;
      }
      $row .= "c$col:'$dat',";
    }
    $main .= substr($row, 0, -1)."},\n";
  }
}else
  $main .= "{id0:'No entities found'}\n";
$main .= "],
columns = [";
$cssClass=$hdrClass=''; # for all cols except last
$width=0;
foreach ($colsA as $col => $t) {
  $w = round($ColInfoA[$col][0] * $wFactor);
  $n = $ColInfoA[$col][1];
  $width += $w;
  if ($col==$colLast) {
    $cssClass=",cssClass:'norb'";
    $hdrClass=",headerCssClass:'norb'";
    $w += $adjWidth - $width; # re rounding differences
  }
  $id="c$col";
  $main .= sprintf("{id:'%s',field:'%s',name:'%s',width:%s,sortable:true%s%s,toolTip:'Click to Sort'},\n", $id, $id, $n, $w, $cssClass,$hdrClass);
}
$main .="],
sortcol, sortdir,
grid = new Slick.Grid('#ELsg', data, columns, {autoHeight:true});
$('#ELfil').keyup(function(e) {
  var i,l,fil=$(this).val().toLowerCase(), dat=[];
  if (e.which == 27) {
    this.value = '';
    dat=data;
  }else
    for (i=0,l=data.length; i<l; ++i)
      if (data[i].c0.toLowerCase().indexOf(fil)!=-1".(isset($colsA[Col_Name]) ? ' || data[i].c1.toLowerCase().indexOf(fil)!=-1' :'').")
        dat.push(data[i]);
  if (!dat.length)
    dat.push({c0:'None with current filter'});
  grid.setData(dat);
  grid.render();
});
grid.onSort.subscribe(function(e, args) {
  sortdir = args.sortAsc ? 1 : 0;
  sortcol = args.sortCol.field;
  grid.getData().sort(comparer);
  grid.invalidateAllRows();
  grid.render();
});
function comparer(a,b) {
  var x = a[sortcol], y = b[sortcol];
  return (x == y ? 0 : sortdir ? (x > y ? 1 : -1) : (x > y ? -1 : 1));
}
</script>
";
#LogIt($main);

########
# Post #
########
$post = '<br><h4>Notes:</h4>
<p>Enter characters into the search box at the top to reduce the report to rows containing the entered letters in the Reference or Entity Name columns, case insensitive.</p>
<p>Click on column headers to sort up/down by that column.</p>
<p>To change the column or level choices return to your Braiins Desktop, and repeat the report.</p>
';
$colNotes = '';
foreach ($colsA as $col => $t)
  switch ($col) {
    case Col_YearEnd:    $colNotes  = '<li>The <i>Year End</i> column shows the Entity\'s current year end, if defined. If the year end has changed see the Data Trail of the Entity for details.</li>'; break;
    case Col_DataYears:  $colNotes .= '<li>The <i>Data Years</i> column shows the years (by year end in YY format), for which data is held by Braiins.</li>'; break;
    case Col_DataState:  $colNotes .= "<li>The <i>Data State</i> column shows the state of the Entity's data which can be 'No data', 'OK', 'Errors', 'Warnings', or 'Errors, Warnings'.<br>'Errors' means that Braiins has detected critical errors in the data, and will not allow final accounts to be produced until the errors have been corrected. For details run 'Data Check' for the Entity.<br>'Warnings' means that Braiins has detected possible inconsistencies in the data which should be checked, but if there are no errors, will not prevent final accounts being generated. 'Data Check' provides the details</li>"; break;
    case Col_AcctsState: $colNotes .= "<li>The <i>Accts State</i> column shows the Accounts Production State for the current year. It can be 'Not run', 'Draft', 'Final Candidate', or 'Final', with ', Downloaded' appended if the Accounts have been doenloaded.</li>"; break;
    case Col_AcctsDate:  $colNotes .= '<li>The <i>Accts Date</i> column shows when the last <i>Accts State</i> change occurred for the current year.</li>'; break;
    case Col_Manager:    $colNotes .= '<li>The <i>Manager</i> column shows the <span class=AName></span> person (manager) currently responsible for the Entity Account. If the person has changed see the Data Trail of the Entity for details.</li>'; break;
    case Col_Level:      $colNotes .= '<li>The <i>Level</i> column shows the Entity Level which is part of the Braiins Entity management system for an Agent.</li>'; break;
    case Col_Status:     $colNotes .= "<li>The <i>Status</i> column can show 'Active', 'Dormant', 'To be deleted', 'Master', 'Demo', or 'Test'. The normal status is Active. Dormant is where you have stopped working on the Entity but have not deleted it, or set it for deletion. 'To be deleted' is where the entity has been flagged for deletion but not actually deleted yet. Master is a Braiins Master. Demo is en entity copied to each new Agent. You can 'play' with Demo entities and restore them to the starting state whenever you wish. Test entities are ones you create that are for testing or learning purposes, for which draft but not final accounts can be produced.</li>"; break;
    case Col_Comments:   $colNotes .= '<li>The <i>Comments</i> column shows the comments, if any, entered via Edit Entity. Comments are also shown in the Trial Balance and Data Trail reports. </li>'; break;
  }
if ($colNotes)
  $post .= '<div class=f90><h4>Column Notes:</h4><ul>'.$colNotes.'</ul></div>';
$hdg = "[AName] Entities as at ".gmstrftime(REPORT_DateTimeFormat, time()-$tzos);
AjaxReturn(OK, "Entities-[AName]$hdg".$ante.''.$main.''.$post); # 5 fields: Title  Heading  Ante  Main  Post

function DataYears($dA) {
  $ret='';
  foreach ($dA as $d)
    $ret .= ','.($d ? eeDtoStr(substr($d,2), '%y') : '-'); # substr($d,2) to strip off the leading SrceNChr DatType characters expected to be 13 for these Date Bros
  return trim($ret, ',-');
}

