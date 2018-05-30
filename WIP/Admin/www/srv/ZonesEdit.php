<?php /* Copyright 2011-2013 Braiins Ltd

/Admin/utils/srv/ZonesEdit.php
Server ops for the Ajax calls to edit Zones

History:
07.04.11 Written
18.07.11 AllowDims added

*/
require '../BaseTx.inc';
require Com_Inc_Tx.'ConstantsRg.inc';

// Op     Dat Rec                           Dat Returned
// I Init []                                [OK, Zones table body]
// N New  [Ref | SignN | AllowDims | Descr] [OK, Zones table body] or [1, error msg]
// S Save [table id | changed value]        [OK, value] or [2, error msg]

// I S N
switch ($Op) {
  case Op_N: // New. Dat = [Ref | SignN | AllowDims | Descr]
    $ref   = Clean($DatA[0], FT_STR);
    $signN = Clean($DatA[1], FT_INT);
    $aDims = Clean($DatA[2], FT_STR);
    $descr = Clean($DatA[3], FT_STR);
    $colsAA = array('Ref' => $ref);
    if ($signN) $colsAA['SignN'] = $signN;
    if ($aDims) {
      if ($aDims = trim($aDims, ',')) {
        $listA = CsListToIntA($aDims);
        foreach ($listA as $i) {
          if ($i < 1 || $i > DimId_Max)
            AjaxReturn(1, "The Allowable Dimension Id $i is out of the allowable range of 1 to " . DimId_Max);
        }
        if (strlen($aDims = PackArrayToChrList($listA)) > 12) # eliminates duplicates and sorts the list
          AjaxReturn(1, "The Allowable Dimension list length > max allowed of 12");
      }
      if ($aDims) $colsAA['AllowDims'] = $aDims;
    }
    if ($descr) $colsAA['Descr'] = $descr;
    if ($DB->eeDupInsertMaster('Zones', $colsAA) === false)
      AjaxReturn(1, 'That Ref has already been used so please edit it');
    // fall thru to do an Init return
  case Op_I: // Init. No Dat.
    $dat = '';
    $res = $DB->ResQuery('Select * From Zones Order by Ref');
    while ($o = $res->fetch_object()) {
      $id    = (int)$o->Id;
      $signN = (int)$o->SignN;
      $allowDims = ChrListToCsList($o->AllowDims);
      $dat .= "<tr><td>$id</td><td><input id=r$id size=15 maxlength=15 value='$o->Ref'></td><td><select id=s$id><option value=0>Not Defined</option><option value=1" .
        ($signN==1 ? " selected=selected" : '') . ">Debit</option><option value=2" .
        ($signN==2 ? " selected=selected" : '') .
        ">Credit</option></select></td><td><input id=a$id size=24 maxlength=36 value='$allowDims'></td><td><input id=d$id size=80 maxlength=80 value='$o->Descr'></td></tr>";
    }
    $res->free();
    $dat .= "<tr><td><button id='NewB' class='on m05'>Go</button></td><td><input id=nr size=15 maxlength=15></td><td><select id=ns><option value=0>Not Defined</option><option value=1>Debit</option><option value=2>Credit</option></select></td><td><input id=na size=24 maxlength=36</td><td><input id=nd size=80 maxlength=80></td></tr>";
    AjaxReturn(OK, $dat);

  case Op_S: // Save Dat = tid | value
    $tid = Clean($DatA[0], FT_STR);
    $val = Clean($DatA[1], FT_STR);
    // Expect id to be
    // - r# for Ref   changed
    // - s# for SignN changed
    // - a# for AllowDims changed
    // - d# for Descr changed
    $c = $tid[0];
    $id = substr($tid,1);
    if (!is_numeric($id))
      Error("ZonesEdit Save table id of $tid not valid");
    $ret = $val;
    switch ($c) {
      case 'r': $col = 'Ref';   break;
      case 's': $col = 'SignN'; break;
      case 'a': $col = 'AllowDims';
        if ($val = trim($val, ',')) {
          $listA = CsListToIntA($val);
          foreach ($listA as $i) {
            if ($i < 1 || $i > DimId_Max)
              AjaxReturn(2, "The Allowable Dimension Id $i is out of the allowable range of 1 to " . DimId_Max);
          }
          if (strlen($val = PackArrayToChrList($listA)) > 12) # eliminates duplicates and sorts the list
            AjaxReturn(2, "The Allowable Dimension list length > max allowed of 12");
        }
        if ($val)
          $ret = ChrListToCsList($val);
        else
          $val = 'null';
        break;
      case 'd': $col = 'Descr'; break;
      default: Error("ZonesEdit Save table id of $tid not valid");
    }
    $DB->eeUpdateMaster('Zones', array($col => $val), 0, $id);
    AjaxReturn(OK,$ret);

}

Error(ERR_Op);

