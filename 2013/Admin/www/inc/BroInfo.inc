<?php /* Copyright 2011-2013 Braiins Ltd

Admin/www/inc/BroInfo.inc

Provides BroInfo() function to get Bro info.

History:
30.03.13 Written based on the previous UK-GAAP-DPL one
07.08.13 AcctTypes removed

ToDo
----

Bro($o)
-------
Must be passed a Bro DB or Map Object
Expected to be called for Bros with the same BroSetTaxnId. The BroSetId, which isn't used here, could change as a result of recursive processing of includes.
Returns an assoc array of the Bro data
 with the int strings from DB cast to ints, all indices as per the table column names i.e. names start with u/c letter,
 plus Tx data in $xA if the BroSet is a Tx one and the Bro has a TxId.

Scratch and RowComments which are used only by bros Export are only returned if $allB is set.

*/
function BroInfo($o, $allB=false) {
  global $DB, $BroSetTaxnId;
  static $txOSA;

  $retA = [
    'InclBroSetId' => (int)$o->InclBroSetId,
    'BroId'     => (int)$o->BroId,
    'Name'      => $o->Name,
    'Level'     => (int)$o->Level,
    'DadId'     => (int)$o->DadId,
    'Bits'      => (int)$o->Bits,
    'DataTypeN' => (int)$o->DataTypeN,
    'DboFieldN' => (int)$o->DboFieldN,
    'SignN'     => (int)$o->SignN,
    'SumUp'     => (int)$o->SumUp,
    'PeriodSEN' => (int)$o->PeriodSEN ? : BPT_Duration,
    'SortOrder' => (int)$o->SortOrder,
    'FolioHys'  => $o->FolioHys,
    'ExclPropDims'  => $o->ExclPropDims,
    'AllowPropDims' => $o->AllowPropDims,
    'PMemDiMesA'    => $o->PMemDiMesA, #  ? json_decode($o->PMemDiMesA) : 0, No to the decode as the json string is used directly in building the Bro structs
    'UsablePropDims'=> $o->UsablePropDims,
    'Related'   => $o->Related,
    'SeSumList' => $o->SeSumList,
    'MasterId'  => (int)$o->MasterId,
    'SlaveYear' => (int)$o->SlaveYear,
    'CheckTest' => $o->CheckTest,
    'Zones'     => $o->Zones,
    'ShortName' => $o->ShortName,
    'Ref'       => $o->Ref,
    'Descr'     => $o->Descr,
    'Data'      => $o->Data,
    'Comment'   => $o->Comment];

  if ($BroSetTaxnId) {
    # Out BroSet
    # Tx info: $xA
    if ($TxId = (int)$o->TxId) {
      if (!isset($txOSA[$TxId]))
        $txOSA[$TxId] = $DB->AaQuery(sprintf('Select NsId,abstract,SubstGroupN,Hypercubes,PeriodN,SignN,TypeN,name,T.Text From %s.Elements E Join %s.Text T on T.Id=E.StdLabelTxtId Where E.Id=%d', DB_Tx, DB_Tx, $TxId));
      $retA['TxId']    = $TxId;
      $retA['TupId']   = (int)$o->TupId;
      $retA['ManDims'] = $o->ManDims;
      $retA['xA']      = $txOSA[$TxId];
    }else
      $retA['TxId'] = 0;
  }

  if ($allB) {
    $retA['Scratch']     = $o->Scratch;
    $retA['RowComments'] = $o->RowComments;
  }

  return $retA;
}

