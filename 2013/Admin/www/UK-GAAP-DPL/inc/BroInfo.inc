<?php /* Copyright 2011-2013 Braiins Ltd

Admin UK-GAAP-DPL/inc/BroInfo.inc

Provides BroInfo() function to get Bro info.

History:
17.10.11 Renamed from BroRaw followed removal of much of that with the demise of inheritance
21.11.11 Revised for BDs
30.11.11 Simplified for inherited Main values being stored for BD Maps by Bros Import.
03.12.11 Further simplified
17.12.11 SeTypeN and SeBroIds added
25.12.11 SeTypeN -> InTypeN
27.12.11 InTypeN -> InBits
01.03.12 ShortName and Ref added
02.03.12 ShortName removed
28.05.12 Revised for addition of Slave Bros etc
01.06.12 Added Scratch and RowComments
02.06.12 Added TupId
24.06.12 Added ShortName again
20.08.12 Added derivation of allowDims for slaves with subset Hys
08.09.12 CheckId -> CheckTest
12.09.12 Removed $manDim, $propDim
13.09.12 DiMeId -> BroDiMesA
17.09.12 AllowDims derivation removed with addition of AllowDims to the DB table and storage there by BrosImport
19.09.12 Added SlaveYear
26.09.12 Removed InBits - bits now in Bits
25.10.12 Renamed BroInfo() from Bro()
18.01.13 Change to AllowDims and UsableDims
08.02.13 Cached $xO and removed Id from TxId Select
15.02.13 BD Maps removed
17.02.13 ContextN -> PeriodSEN, SeBroIds removed

ToDo
----

Bro($o)
-------
Must be passed a Bro DB or Map Object
Returns an assoc array of the Bro data
 with the int strings from DB cast to ints, all indices as per the table column names i.e. names start with u/c letter,
 plus derived data of $xO = 0 with names starting with l/c letter

*/
function BroInfo($o) {
  global $DB;
  static $txOSA;

  # Derived value: $xO
  if ($TxId = (int)$o->TxId) {
    if (!isset($txOSA[$TxId]))
      $txOSA[$TxId] = $DB->ObjQuery("Select NsId,abstract,SubstGroupN,Hypercubes,PeriodN,SignN,TypeN,name,T.Text from Elements E Join Text T on T.Id=E.StdLabelTxtId Where E.Id=$TxId");
    $xO = $txOSA[$TxId];
  }else
    $xO = 0;

  return array(
    'Id'        => (int)$o->Id,
    'Name'      => $o->Name,
    'Level'     => (int)$o->Level,
    'DadId'     => (int)$o->DadId,
    'Bits'      => (int)$o->Bits,
    'DataTypeN' => (int)$o->DataTypeN,
    'AcctTypes' => $o->AcctTypes,
    'SumUp'     => (int)$o->SumUp,
    'TxId'      => $TxId,
    'Hys'       => $o->Hys, # Chr list. Can be more than 1 in case of a Set (or slave of a Set?)
    'TupId'     => (int)$o->TupId,
    'SignN'     => (int)$o->SignN,
    'ShortName' => $o->ShortName,
    'Ref'       => $o->Ref,
    'PeriodSEN' => (int)$o->PeriodSEN,
    'ExclDims'  => $o->ExclDims,
    'AllowDims' => $o->AllowDims,
    'BroDiMesA' => $o->BroDiMesA, # ? json_decode($o->BroDiMesA) : 0,
    'MasterId'  => (int)$o->MasterId,
    'CheckTest' => $o->CheckTest,
    'SortOrder' => (int)$o->SortOrder,
    'Zones'     => $o->Zones,
    'Descr'     => $o->Descr,
    'Comment'   => $o->Comment,
    'SlaveIds'  => $o->SlaveIds,
    'SlaveYear' => (int)$o->SlaveYear,
    'UsableDims'=> $o->UsableDims,
    'Scratch'   => $o->Scratch,     # /- Only used by Bros Export
    'RowComments'=>$o->RowComments, # |
    'xO'        => $xO);            # - Derived data
}

