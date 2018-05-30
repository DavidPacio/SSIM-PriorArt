<?php /* Copyright 2011-2013 Braiins Ltd

Test.php

*/

require 'BaseTx.inc';
#equire 'BaseBraiins.inc';
require Com_Inc_Tx.'ConstantsRg.inc';
require Com_Inc_Tx.'ConstantsTx.inc';
require Com_Str_Tx.'TupNamesA.inc';         # $TupNamesA
require Com_Str_Tx.'TuMesA.inc';            # $TuMesA
require Com_Inc.'ClassBro.inc';

Head('Test');
echo '<br>';
#phpinfo();

$a = 1 << 12;
$b = 1 << 13;
echo $a, ' ', $b,'<br>';

echo "gmstrftime = ". gmstrftime("'%Y-%m-%d %H:%M:%S'", time()).'<br>'; # WITH ENCLOSING SINGLE QUOTES
echo "  strftime = ".   strftime("'%Y-%m-%d %H:%M:%S'", time()).'<br>'; # WITH ENCLOSING SINGLE QUOTES

exit;

/*
$bdMapDiMeId=$manDiMeId=$pyaDiMeId=$bdMapDiMeId=$manDiMeId=0;
#            0 1 2 3
$diMeIdsA = [75];
$pyaDiMeId   = 75;
$pyaDiMei    = 0;
$testStartUtime= utime();
    # all OK so sort DiMes
    $diMeIds2A = [];                 # /- to get $bdMapDiMeId, $manDiMeId{,$proDiMeId} first in the array, with $pyaDiMeId last,
    if ($bdMapDiMeId) {              # |  and any others sorted in between so that refs will be consistent
      unset($diMeIdsA[$bdMapDiMei]); # |  (Doing this in just $diMeIdsA gave problems with non contig indices.)
      $diMeIds2A[] = $bdMapDiMeId;   # |  djh?? See if ok in $diMeIdsA with php 5.4
    }                                # |
    if ($manDiMeId) {                # v
      unset($diMeIdsA[$manDiMei]);
      $diMeIds2A[] = $manDiMeId;
      if ($proDiMeId) {
        unset($diMeIdsA[$proDiMei]);
        $diMeIds2A[] = $proDiMeId;
      }
    }
    if ($pyaDiMeId)
      unset($diMeIdsA[$pyaDiMei]);
    sort($diMeIdsA);
    $diMeIds2A = array_merge($diMeIds2A, $diMeIdsA);
    if ($pyaDiMeId) $diMeIds2A[] = $pyaDiMeId;
echo "time = ", MTime(utime() - $testStartUtime), '<br>';

$diMeRef = implode(',', $diMeIds2A);
echo "diMeRef=$diMeRef<br>"; # diMeRef=5,1,2,9,10,11,7
Dump('$diMeIds2A',$diMeIds2A);

$bdMapDiMeId=$manDiMeId=$pyaDiMeId=$bdMapDiMeId=$manDiMeId=0;
#            0 1 2 3
$diMeIdsA = [75];
$pyaDiMeId   = 75;
$pyaDiMei    = 0;
$testStartUtime= utime();
    # all OK so sort DiMes
    # to get $bdMapDiMeId, $manDiMeId{,$proDiMeId} first in the array, with $pyaDiMeId last, and any others sorted in between so that refs will be consistent
    if ($bdMapDiMeId)
      unset($diMeIdsA[$bdMapDiMei]);
    if ($manDiMeId) {
      unset($diMeIdsA[$manDiMei]);
      if ($proDiMeId)
        unset($diMeIdsA[$proDiMei]);
    }
    if ($pyaDiMeId)
      unset($diMeIdsA[$pyaDiMei]);
    count($diMeIdsA) ? sort($diMeIdsA) : $diMeIdsA=[];
    if ($manDiMeId) {
      if ($proDiMeId)
        array_unshift($diMeIdsA, $proDiMeId);
      array_unshift($diMeIdsA, $manDiMeId);
    }
    if ($bdMapDiMeId)
      array_unshift($diMeIdsA, $bdMapDiMeId);
    if ($pyaDiMeId) $diMeIdsA[] = $pyaDiMeId;
echo "time = ", MTime(utime() - $testStartUtime), '<br>';
$diMeRef = implode(',', $diMeIdsA);
echo "diMeRef=$diMeRef<br>"; # diMeRef=5,1,2,9,10,11,7
Dump('$diMeIdsA',$diMeIdsA);
exit;
*/


function TError($msg) {
 echo "<b>TError() $msg</b><br>";
}

brO::SetErrorCallbackFn('TError');

echo "brO::Changes()=",brO::Changes(),'<br>';

$broId = 2030;
$brO = new brO($broId);
$brO(BroDatT_End, 100, 1);
echo 'EndBase Dat= ',$brO->EndBase(),'<br>';
foreach ($brO->AllBroDatOs() as $broDatKey => $datO)
  echo 'Key=',$broDatKey, ' Dat=',$datO->Dat, ' Dat2=',$datO->Dat2,', Srce=',$datO->Source(), ' Ref=',$datO->BroRefFull(),'<br>';
$s = $brO->Stringify();
echo 'Stringify ',$s,'<br>';
$brO2=NewBroFromString($broId, $s);
echo '$brO==$brO2 after NewBroFromString ',($s==($s2=$brO2->Stringify()) ? '==' : "!=<br>$s<br>$s2"),'<br>';
echo "brO::Changes()=",brO::Changes(),'<br>';


echo '<br>2<br>';
$broId = 2031;
$brO = new brO($broId);
$brO(BroDatT_End, 'Fred',BroDatSrce_P);
echo $brO,'<br>';
echo 'EndBase Dat= ',$brO->EndBase(),'<br>';
#Dump('$brO', $brO);
$s = $brO->Stringify();
echo 'Stringify ',$s,'<br>';
$brO2=NewBroFromString($broId, $s);
#Dump('$brO2', $brO2);
echo '$brO==$brO2 after NewBroFromString ',($s==$brO2->Stringify() ? '==' : '!='),'<br>';
echo "brO::Changes()=",brO::Changes(),'<br>';

echo '<br>3 Summing, Base added after DiMes<br>';
brO::ResetChanges();
$broId = 2030;
$brO = new brO($broId);
$brO(BroDatT_End, 101, BroDatSrce_P, 0, [160,185]);
$brO(BroDatT_End, 102, BroDatSrce_P, 0, [160,186]);
$brO(BroDatT_End, 1000, BroDatSrce_P);
echo "brO::Changes()=",brO::Changes(),'<br>';
echo $brO,'<br>';
echo 'EndBase Dat= ',$brO->EndBase(),'<br>';
foreach ($brO->AllBroDatOs() as $broDatKey => $datO)
  echo 'Key=',$broDatKey, ' Dat=',$datO->Dat, ' Dat2=',$datO->Dat2,', Srce=',$datO->Source(), ' Ref=',$datO->BroRefFull(),'<br>';
$s = $brO->Stringify();
echo 'Stringify ',$s,'<br>'; #  14160,18510114160,1861021499991000
$brO2=NewBroFromString($broId, $s);
echo 'After NewBroFromString with DiMeSumming: ',$brO2,'<br>';
echo 'EndBase Dat= ',$brO2->EndBase(),'<br>';
foreach ($brO2->AllBroDatOs() as $broDatKey => $datO)
  echo 'Key=',$broDatKey, ' Dat=',$datO->Dat, ' Dat2=',$datO->Dat2,', Srce=',$datO->Source(), ' Ref=',$datO->BroRefFull(),'<br>';
echo '$brO==$brO2 after NewBroFromString ',($s==$brO2->Stringify() ? '==' : '!='),'<br>';
echo "brO::Changes()=",brO::Changes(),'<br>';
/*

2030            b 1203
2030,160,185    P  101
2030,160,186    P  102
2030,9999       P 1000



Intermediate DiMes
2030,160 Bal=101
2030,185 Bal=101
2030,160 Bal=102
2030,186 Bal=102

DiMe Summing
2030,4,159 Bal=203
2030,4,159,185 Bal=101
2030,4,160,184 Bal=203
2030,4,159,186 Bal=102

2030            b 1,203
2030,159        d   203
2030,159,185    d   101
2030,159,186    d   102
2030,160        i   203
2030,160,184    d   203
2030,160,185    P   101
2030,160,186    P   102
2030,185        i   101
2030,186        i   102
2030,9999       P 1,000


Key=2 Dat=1,203 Srce=b Ref=2030 Assets.Fixed.Tangible.CostOrValuation
Key=2,159 Dat=203 Srce=d Ref=2030,159 Assets.Fixed.Tangible.CostOrValuation,TFAClasses.All
Key=2,159,185 Dat=101 Srce=d Ref=2030,159,185 Assets.Fixed.Tangible.CostOrValuation,TFAClasses.All,TFAOwnership.OwnedOrFreehold
Key=2,159,186 Dat=102 Srce=d Ref=2030,159,186 Assets.Fixed.Tangible.CostOrValuation,TFAClasses.All,TFAOwnership.Leased
Key=2,160 Dat=203 Srce=i Ref=2030,160 Assets.Fixed.Tangible.CostOrValuation,TFAClasses.LandBuildings
Key=2,160,184 Dat=203 Srce=d Ref=2030,160,184 Assets.Fixed.Tangible.CostOrValuation,TFAClasses.LandBuildings,TFAOwnership.Total
Key=2,160,185 Dat=101 Srce=P Ref=2030,160,185 Assets.Fixed.Tangible.CostOrValuation,TFAClasses.LandBuildings,TFAOwnership.OwnedOrFreehold
Key=2,160,186 Dat=102 Srce=P Ref=2030,160,186 Assets.Fixed.Tangible.CostOrValuation,TFAClasses.LandBuildings,TFAOwnership.Leased
Key=2,185 Dat=101 Srce=i Ref=2030,185 Assets.Fixed.Tangible.CostOrValuation,TFAOwnership.OwnedOrFreehold
Key=2,186 Dat=102 Srce=i Ref=2030,186 Assets.Fixed.Tangible.CostOrValuation,TFAOwnership.Leased
Key=2,9999 Dat=1,000 Srce=P Ref=2030,9999 Assets.Fixed.Tangible.CostOrValuation,Unallocated

*/

echo '<br>4 String Tuple DiMes, duplicate add<br>';
brO::ResetChanges();
$broId = 6370;
$brO = new brO($broId);
$brO(BroDatT_End, 'Fred', BroDatSrce_P, 11);
echo "brO::Changes()=",brO::Changes(),'<br>';
$brO(BroDatT_End, 'Fred2', BroDatSrce_P, 11, [160,184]);
echo "brO::Changes()=",brO::Changes(),'<br>';
$brO('2,160,184', 'Fred3', BroDatSrce_P, 11); # should error
echo "brO::Changes()=",brO::Changes(),'<br>';
echo '$brO->ErrorMsg() ',$brO->ErrorMsg(),'<br>';
$brO('2,160,185', 'Fred3', BroDatSrce_P,  11);
echo "brO::Changes()=",brO::Changes(),'<br>';
echo $brO,'<br>';
echo 'EndBase Dat= ',$brO->EndBase(),'<br>';
$entsA = $brO->AllBroDatOs();
#Dump('$entsA',$entsA);
foreach ($entsA as $broDatKey => $datO)
  echo 'Key=',$broDatKey, ' Dat=',$datO->Dat, ' Dat2=',$datO->Dat2,', Srce=',$datO->Source(), ' Ref=',$datO->BroRefFull(),'<br>';
#Dump('$brO', $brO);
$s = $brO->Stringify();
echo 'Stringify ',$s,'<br>'; # 1211Fred1211160,184Fred21211160,185Fred3
$brO2=NewBroFromString($broId, $s);
#Dump('$brO2', $brO2);
echo '$brO==$brO2 after NewBroFromString ',($s==$brO2->Stringify() ? '==' : '!='),'<br>';
echo "brO::Changes()=",brO::Changes(),'<br>';


echo '<br>5 String Tuple Base<br>';
brO::ResetChanges();
$broId = 6370;
$brO = new brO($broId);
$brO(BroDatT_End, 'Inst 1',BroDatSrce_P, 1);
$brO(BroDatT_End, 'Inst 2',BroDatSrce_P, 2);
echo $brO,'<br>';
echo 'EndBase Dat= ',$brO->EndBase(),'<br>';
foreach ($brO->AllBroDatOs() as $broDatKey => $datO)
  echo 'Key=',$broDatKey, ' Dat=',$datO->Dat, ' Dat2=',$datO->Dat2,', Srce=',$datO->Source(), ' Ref=',$datO->BroRefFull(),'<br>';
$s = $brO->Stringify();
echo 'Stringify ',$s,'<br>'; # Stringify 131Inst 1132Inst 2
$brO2=NewBroFromString($broId, $s);
echo '$brO==$brO2 after NewBroFromString ',($s==($s2=$brO2->Stringify()) ? '==' : "!=<br>$s<br>$s2"),'<br>';
echo "brO::Changes()=",brO::Changes(),'<br>';

echo '<br>6 String Tuple DiMe<br>';
brO::ResetChanges();
$broId = 6370;
$brO = new brO($broId);
$brO(BroDatT_End, 'Inst 1',BroDatSrce_P, 1, [160,184]);
$brO(BroDatT_End, 'Inst 2',BroDatSrce_P, 2, [160,184]);
echo $brO,'<br>';
echo 'EndBase Dat= ',$brO->EndBase(),'<br>';
foreach ($brO->AllBroDatOs() as $broDatKey => $datO)
  echo 'Key=',$broDatKey, ' Dat=',$datO->Dat, ' Dat2=',$datO->Dat2,', Srce=',$datO->Source(), ' Ref=',$datO->BroRefFull(),'<br>';
$s = $brO->Stringify(); # 141160,184,1,100Inst 1142160,184,1,100Inst 2
echo 'Stringify ',$s,'<br>';
$brO2=NewBroFromString($broId, $s);
echo '$brO==$brO2 after NewBroFromString ',($s==($s2=$brO2->Stringify()) ? '==' : "!=<br>$s<br>$s2"),'<br>';
echo "brO::Changes()=",brO::Changes(),'<br>';


echo '<br>7 String Tuple Base and DiMe<br>';
brO::ResetChanges();
$broId = 6370;
$brO = new brO($broId);
$brO(BroDatT_End, 'Base Inst 1',BroDatSrce_P, 1);
$brO(BroDatT_End, 'Base Inst 2',BroDatSrce_P, 2);
$brO(BroDatT_End, 'DiMe Inst 1',BroDatSrce_P, 1, [160,184]);
$brO(BroDatT_End, 'DiMe Inst 2',BroDatSrce_P, 2, [160,184]);
echo $brO,'<br>';
echo 'EndBase Dat= ',$brO->EndBase(),'<br>';
foreach ($brO->AllBroDatOs() as $broDatKey => $datO)
  echo 'Key=',$broDatKey, ' Dat=',$datO->Dat, ' Dat2=',$datO->Dat2,', Srce=',$datO->Source(), ' Ref=',$datO->BroRefFull(),'<br>';
$s = $brO->Stringify();
echo 'Stringify ',$s,'<br>';
$brO2=NewBroFromString($broId, $s);
echo '$brO==$brO2 after NewBroFromString ',($s==$brO2->Stringify() ? '==' : '!='),'<br>';
echo "brO::Changes()=",brO::Changes(),'<br>';

echo '<br>8 Summing Tuple Base<br>';
brO::ResetChanges();
$broId = 6371;
$brO = new brO($broId);
$brO(BroDatT_End, 1, BroDatSrce_P, 1);
$brO(BroDatT_End, 2, BroDatSrce_P, 2);
echo $brO,'<br>';
echo 'EndBase Dat= ',$brO->EndBase(),'<br>';
foreach ($brO->AllBroDatOs() as $broDatKey => $datO)
  echo 'Key=',$broDatKey, ' Dat=',$datO->Dat, ' Dat2=',$datO->Dat2,', Srce=',$datO->Source(), ' Ref=',$datO->BroRefFull(),'<br>';
$s = $brO->Stringify();
echo 'Stringify ',$s,'<br>';
$brO2=NewBroFromString($broId, $s);
echo '$brO==$brO2 after NewBroFromString ',($s==$brO2->Stringify() ? '==' : '!='),'<br>';
echo "brO::Changes()=",brO::Changes(),'<br>';

echo '<br>9 Summing Tuple 1 Base -> DiMes<br>';
brO::ResetChanges();
$broId = 6371;
$brO = new brO($broId);
$brO(BroDatT_End, 1, BroDatSrce_P, 1);
$brO(BroDatT_End, 2, BroDatSrce_P, 2, [160,185]);
$brO(BroDatT_End, 3, BroDatSrce_P, 3, [160,186]);
echo 'Before DiMe Summing ',$brO,'<br>';
echo 'EndBase Dat= ',$brO->EndBase(),'<br>';
foreach ($brO->AllBroDatOs() as $broDatKey => $datO)
  echo 'Key=',$broDatKey, ' Dat=',$datO->Dat, ' Dat2=',$datO->Dat2,', Srce=',$datO->Source(), ' Ref=',$datO->BroRefFull(),'<br>';
$brO->DiMeSumming();
echo 'After DiMe Summing ',$brO,'<br>';
echo 'EndBase Dat= ',$brO->EndBase(),'<br>';
foreach ($brO->AllBroDatOs() as $broDatKey => $datO)
  echo 'Key=',$broDatKey, ' Dat=',$datO->Dat, ' Dat2=',$datO->Dat2,', Srce=',$datO->Source(), ' Ref=',$datO->BroRefFull(),'<br>';
/*


Before DiMe Summing Bro 6371 PostType Sch, Element, Summing, Tuple, has Base, has 2 DiMes, includes Primary data
EndBase Dat= 6
Key=3 Dat=6 Dat2=, Srce=b Ref=6371 SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount
Key=1001,3 Dat=1 Dat2=, Srce=P Ref=6371,T.1 SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount,T.1
Key=1002,3 Dat=2 Dat2=, Srce=P Ref=6371,T.2 SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount,T.2
Key=1002,4,160,185 Dat=2 Dat2=2, Srce=P Ref=6371,160,185,T.2 SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount,TFAClasses.LandBuildings,TFAOwnership.OwnedOrFreehold,T.2
Key=1003,3 Dat=3 Dat2=, Srce=P Ref=6371,T.3 SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount,T.3
Key=1003,4,160,186 Dat=3 Dat2=3, Srce=P Ref=6371,160,186,T.3 SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount,TFAClasses.LandBuildings,TFAOwnership.Leased,T.3

EndBase Dat= 6
6371              b 6
6371,T.1          P 1
6371,T.2          P 2
6371,160,185,T.2  P 2
6371,T.3          P 3
6371,160,186,T.3  P 3

After DiMe Summing Bro 6371 PostType Sch, Element, Summing, Tuple, has Base, has 9 DiMes, includes Primary data
EndBase Dat= 6
Key=3 Dat=6 Dat2=, Srce=b Ref=6371 SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount
Key=4,159 Dat=5 Dat2=, Srce=d Ref=6371,159 SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount,TFAClasses.All
Key=4,159,185 Dat=2 Dat2=, Srce=d Ref=6371,159,185 SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount,TFAClasses.All,TFAOwnership.OwnedOrFreehold
Key=4,159,186 Dat=3 Dat2=, Srce=d Ref=6371,159,186 SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount,TFAClasses.All,TFAOwnership.Leased
Key=4,160 Dat=5 Dat2=, Srce=i Ref=6371,160 SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount,TFAClasses.LandBuildings
Key=4,160,184 Dat=5 Dat2=, Srce=d Ref=6371,160,184 SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount,TFAClasses.LandBuildings,TFAOwnership.Total
Key=4,185 Dat=2 Dat2=, Srce=i Ref=6371,185 SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount,TFAOwnership.OwnedOrFreehold
Key=4,186 Dat=3 Dat2=, Srce=i Ref=6371,186 SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount,TFAOwnership.Leased
Key=1001,3 Dat=1 Dat2=1, Srce=P Ref=6371,T.1 SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount,T.1
Key=1002,3 Dat=2 Dat2=, Srce=t Ref=6371,T.2 SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount,T.2
Key=1002,4,160,185 Dat=2 Dat2=2, Srce=P Ref=6371,160,185,T.2 SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount,TFAClasses.LandBuildings,TFAOwnership.OwnedOrFreehold,T.2
Key=1003,3 Dat=3 Dat2=, Srce=t Ref=6371,T.3 SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount,T.3
Key=1003,4,160,186 Dat=3 Dat2=3, Srce=P Ref=6371,160,186,T.3 SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount,TFAClasses.LandBuildings,TFAOwnership.Leased,T.3
Stringify 1311142160,1852143160,1863

EndBase Dat= 6
6371              b 6
6371,159          d 5
6371,159,185      d 2
6371,159,186      d 3
6371,160          i 5
6371,160,184      d 5
6371,185          i 2
6371,186          i 3
6371,T.1          P 1
6371,T.2          t 2
6371,160,185,T.2  P 2
6371,T.3          t 3
6371,160,186,T.3  P 3
*/


$s = $brO->Stringify();
echo 'Stringify ',$s,'<br>'; # 1311142160,1842141184,1601
$brO2=NewBroFromString($broId, $s);
echo '$brO==$brO2 after NewBroFromString ',($s==$brO2->Stringify() ? '==' : '!='),'<br>';
echo "brO::Changes()=",brO::Changes(),'<br>';
echo '6371 after NewBroFromString ', $brO2,'<br>';
echo 'EndBase Dat= ',$brO2->EndBase(),'<br>';
foreach ($brO2->AllBroDatOs() as $broDatKey => $datO)
  echo 'Key=',$broDatKey, ' Dat=',$datO->Dat, ' Dat2=',$datO->Dat2,', Srce=',$datO->Source(), ' Ref=',$datO->BroRefFull(),'<br>';
/*
6371              b 4
6371,T.1          P 1
6371,159          d 2
6371,159,184      d 2
6371,160          i 1
6371,160,184,T.2  P 2
6371,184          i 1
6371,184,159      d 1
6371,184,160,T.1  P 1
*/

echo '<br>10 Summing Tuple 3 Base -> DiMes<br>';
brO::ResetChanges();
$broId = 6371;
$brO = new brO($broId);
$brO(BroDatT_End, 1, BroDatSrce_P, 1);
$brO(BroDatT_End, 2, BroDatSrce_P, 2);
$brO(BroDatT_End, 3, BroDatSrce_P, 3);
$brO(BroDatT_End, 1, BroDatSrce_P, 1, [184,160]);
$brO(BroDatT_End, 2, BroDatSrce_P, 2, [160,184]);
echo $brO,'<br>';
echo 'EndBase Dat= ',$brO->EndBase(),'<br>';
foreach ($brO->AllBroDatOs() as $broDatKey => $datO)
  echo 'Key=',$broDatKey, ' Dat=',$datO->Dat, ' Dat2=',$datO->Dat2,', Srce=',$datO->Source(), ' Ref=',$datO->BroRefFull(),'<br>';
$s = $brO->Stringify();
echo 'Stringify ',$s,'<br>';
$brO2=NewBroFromString($broId, $s);
echo '$brO==$brO2 after NewBroFromString ',($s==($s2=$brO2->Stringify()) ? '==' : "!=<br>$s<br>$s2"),'<br>';
echo "brO::Changes()=",brO::Changes(),'<br>';

echo '<br>11 test 6574 yr 1<br>';
#broString='44557-1801202644568-29130644570-1308444572-878597449999-38406910';
$broString='42557-1801202642568-29130642570-1308442572-878597429999-211162027'; # 20.12.12
$brO=NewBroFromString(6574, $broString);
echo $brO,'<br>';
echo 'EndBase Dat= ',$brO->EndBase(),'<br>';
foreach ($brO->AllBroDatOs() as $broDatKey => $datO)
  echo 'Key=',$broDatKey, ' Dat=',$datO->Dat, ' Dat2=',$datO->Dat2,', Srce=',$datO->Source(), ' Ref=',$datO->BroRefFull(),'<br>';

echo '<br>12 test 6574 PYA (yr 4)<br>';
#broString='4475-26477-5760192364556,77-1919501344557-1801202644557,75164557,77-1801202644568-29130644570-1308444572-878597449999-38406910'; # 10.12.12
$broString='4275-116277-23035704062556,77-1919501342557-1801202642557,75162557,77-1801202642568-29130642570-1308442572-878597429999-211162027'; # 20.12.12
$brO=NewBroFromString(6574, $broString);
echo $brO,'<br>';
echo 'EndBase Dat= ',$brO->EndBase(),'<br>';
foreach ($brO->AllBroDatOs() as $broDatKey => $datO)
  echo 'Key=',$broDatKey, ' Dat=',$datO->Dat, ' Dat2=',$datO->Dat2,', Srce=',$datO->Source(), ' Ref=',$datO->BroRefFull(),'<br>';

echo '6574 ';
#print_r($brO);
$s = $brO->Stringify();
echo 'Stringify ',$s,'<br>'; # 4475-26477-5760192364556,77-1919501344557-1801202644557,75164557,77-1801202644568-29130644570-1308444572-878597449999-38406910

#$copyBrO = clone $brO;
$copyBrO = $brO->Copy();

echo '<br>6574 copy<br>';
#print_r($copyBrO);
$s = $copyBrO->Stringify();
echo 'Stringify copyBrO ',$s,'<br>'; # 4475-26477-5760192364556,77-1919501344557-1801202644557,75164557,77-1801202644568-29130644570-1308444572-878597449999-38406910
echo $copyBrO,'<br>';
echo 'EndBase Dat= ',$copyBrO->EndBase(),'<br>';
foreach ($copyBrO->AllBroDatOs() as $broDatKey => $datO)
  echo 'Key=',$broDatKey, ' Dat=',$datO->Dat, ' Dat2=',$datO->Dat2,', Srce=',$datO->Source(), ' Ref=',$datO->BroRefFull(),'<br>';
/*

4475-26477-5760192364556,77-1919501344557-1801202644557,75164557,77-1801202644568-29130644570-1308444572-878597449999-38406910'; # 10.12.12
4475-26477-5760192364556,77-1919501344557-1801202644557,75164557,77-1801202644568-29130644570-1308444572-878597449999-38406910
4475-26477-5760192364556,77-1919501344557-1801202644557,75164557,77-1801202644568-29130644570-1308444572-878597449999-38406910

6574         b   -57,601,923  -57,601,924
6574,73      d                -57,601,924
6574,74      d                -1
6574,75      Si               -1          -2 initially   -> -1 with i
6574,77      r                -57,601,923 *
6574,556     d   -19,195,013  -19,195,012 *
6574,556,73  d                -19,195,012
6574,556,74  d                1
6574,556,75  d                1           *
6574,556,77  r                -19,195,013 *
6574,557     S,Si -18,012,026 -18,012,025   26 intitially -> 25 with i
6574,557,73  d                -18,012,025
6574,557,74  d                1
6574,557,75  S                1           *
6574,557,77  r                -18,012,026 *
6574,568     S    -291,306    -291,306    *
6574,570     S    -13,084     -13,084     *
6574,572     S    -878,597    -878,597    *
6574,9999    S    -38,406,910 -38,406,910 *

Set Summing year 4 6574,4,75 Bal -1 created from Bro 9002,4,75 bal -1
Set Summing year 4 6574,4,75 Bal -2 after adding Bro 9021,4,75 bal -1
Set Summing year 4 6574,4,557,75 Bal 1 created from Bro 9059,4,557,75 bal 1

From the 6574,557,75 post
Intermediate DiMe for 6574,557 Bal=1 *
Intermediate DiMe for 6574,75 Bal=1  *

DiMe Summing for 6574,4,556 Bal=-19195012
DiMe Summing for 6574,4,556,75 Bal=1

Pya DiMe Summing for 6574,4,74 Bal=-2 Inst=0
Pya DiMe Summing for 6574,4,73 Bal=-2 Inst=0
Pya DiMe Summing for 6574,4,73 Bal=-57601923 Inst=0
Pya DiMe Summing for 6574,4,556,74 Bal=1 Inst=0
Pya DiMe Summing for 6574,4,556,73 Bal=1 Inst=0
Pya DiMe Summing for 6574,4,556,73 Bal=-19195013 Inst=0
Pya DiMe Summing for 6574,4,557,74 Bal=1 Inst=0
Pya DiMe Summing for 6574,4,557,73 Bal=1 Inst=0
Pya DiMe Summing for 6574,4,557,73 Bal=-18012026 Inst=0
Pya DiMe Summing for 6574,4,74 Bal=-2, Inst=0
Bro::add data to Bro 6574, broDatTypeOrBroDatKey=4,74, dat=-2, srceN=9, inst=0, diMeIdOrDiMeIdsA=0, Op=2; Changes before=45
BroDat::constructor Bro 6574 broDatType=4, dat=-2, srceN=9, diMeIdsA=[74]; Changes before=45
Pya DiMe Summing for 6574,4,73 Bal=-57601925, Inst=0
Bro::add data to Bro 6574, broDatTypeOrBroDatKey=4,73, dat=-57601925, srceN=9, inst=0, diMeIdOrDiMeIdsA=0, Op=2; Changes before=46
BroDat::constructor Bro 6574 broDatType=4, dat=-57601925, srceN=9, diMeIdsA=[73]; Changes before=46
Pya DiMe Summing for 6574,4,556,74 Bal=1, Inst=0
Bro::add data to Bro 6574, broDatTypeOrBroDatKey=4,556,74, dat=1, srceN=9, inst=0, diMeIdOrDiMeIdsA=0, Op=2; Changes before=47
BroDat::constructor Bro 6574 broDatType=4, dat=1, srceN=9, diMeIdsA=[556,74]; Changes before=47
Pya DiMe Summing for 6574,4,556,73 Bal=-19195012, Inst=0
Bro::add data to Bro 6574, broDatTypeOrBroDatKey=4,556,73, dat=-19195012, srceN=9, inst=0, diMeIdOrDiMeIdsA=0, Op=2; Changes before=48
BroDat::constructor Bro 6574 broDatType=4, dat=-19195012, srceN=9, diMeIdsA=[556,73]; Changes before=48
Pya DiMe Summing for 6574,4,557,74 Bal=1, Inst=0
Bro::add data to Bro 6574, broDatTypeOrBroDatKey=4,557,74, dat=1, srceN=9, inst=0, diMeIdOrDiMeIdsA=0, Op=2; Changes before=49
BroDat::constructor Bro 6574 broDatType=4, dat=1, srceN=9, diMeIdsA=[557,74]; Changes before=49
Pya DiMe Summing for 6574,4,557,73 Bal=-18012025, Inst=0
Bro::add data to Bro 6574, broDatTypeOrBroDatKey=4,557,73, dat=-18012025, srceN=9, inst=0, diMeIdOrDiMeIdsA=0, Op=2; Changes before=50
BroDat::constructor Bro 6574 broDatType=4, dat=-18012025, srceN=9, diMeIdsA=[557,73]; Changes before=50
Bro 6574 PostType Sch, Set, Summing, has Base, has 18 DiMes, includes Primary data



6574         b   -57,601,923  -57,601,924
6574,73      d                -57,601,925 ** 24
6574,74      d                -2          ** -1
6574,75      Si               -1
6574,77      r                -57,601,923
6574,556     d   -19,195,013  -19,195,012
6574,556,73  d                -19,195,012
6574,556,74  d                1
6574,556,75  d                1
6574,556,77  r                -19,195,013
6574,557     S,Si -18,012,026 -18,012,025
6574,557,73  d                -18,012,025
6574,557,74  d                1
6574,557,75  S                1
6574,557,77  r                -18,012,026
6574,568     S    -291,306    -291,306
6574,570     S    -13,084     -13,084
6574,572     S    -878,597    -878,597
6574,9999    S    -38,406,910 -38,406,910

*/

/*
echo '<br>13 test 6376 yr 1) Donations Tuples<br>';
$broString='131201132202133203'; # 15.12.12
echo "6376 from string ",$broString,'<br>';
$brO=NewBroFromString(6376, $broString);
echo $brO,'<br>';
echo 'EndBase Dat= ',$brO->EndBase(),'<br>';
foreach ($brO->AllBroDatOs() as $broDatKey => $datO)
  echo 'Key=',$broDatKey, ' Dat=',$datO->Dat, ' Dat2=',$datO->Dat2,', Srce=',$datO->Source(), ' Ref=',$datO->BroRefFull(),'<br>';
$s = $brO->Stringify();
echo '6376 Stringify ',$s,'<br>';
$copyBrO = $brO->Copy();
echo 'Copy 6376 ',$copyBrO,'<br>';
echo 'EndBase Dat= ',$copyBrO->EndBase(),'<br>';
foreach ($copyBrO->AllBroDatOs() as $broDatKey => $datO)
  echo 'Key=',$broDatKey, ' Dat=',$datO->Dat, ' Dat2=',$datO->Dat2,', Srce=',$datO->Source(), ' Ref=',$datO->BroRefFull(),'<br>';
$s = $copyBrO->Stringify();
echo '6376 Copy Stringify ',$s,'<br>';


echo '<br>14 test 6376 PYA (yr 4) Donations Tuples<br>';
$broString='13120114175164177201132202133203'; # 15.12.12
$brO=NewBroFromString(6376, $broString);
echo $brO,'<br>';
echo 'EndBase Dat= ',$brO->EndBase(),'<br>';
foreach ($brO->AllBroDatOs() as $broDatKey => $datO)
  echo 'Key=',$broDatKey, ' Dat=',$datO->Dat, ' Dat2=',$datO->Dat2,', Srce=',$datO->Source(), ' Ref=',$datO->BroRefFull(),'<br>';
echo '6574 ';
#print_r($brO);
$s = $brO->Stringify();
echo 'Stringify ',$s,'<br>';

echo '<br>15 test 6374 yr 1) Donations Tuples<br>';
$broString='43606'; # 15.12.12
echo "6374 from string ",$broString,'<br>';
$brO=NewBroFromString(6374, $broString);
echo $brO,'<br>';
echo 'EndBase Dat= ',$brO->EndBase(),'<br>';
foreach ($brO->AllBroDatOs() as $broDatKey => $datO)
  echo 'Key=',$broDatKey, ' Dat=',$datO->Dat, ' Dat2=',$datO->Dat2,', Srce=',$datO->Source(), ' Ref=',$datO->BroRefFull(),'<br>';
$s = $brO->Stringify();
echo '6374 Stringify ',$s,'<br>';
$copyBrO = $brO->Copy();
echo 'Copy 6374 ',$copyBrO,'<br>';
echo 'EndBase Dat= ',$copyBrO->EndBase(),'<br>';
foreach ($copyBrO->AllBroDatOs() as $broDatKey => $datO)
  echo 'Key=',$broDatKey, ' Dat=',$datO->Dat, ' Dat2=',$datO->Dat2,', Srce=',$datO->Source(), ' Ref=',$datO->BroRefFull(),'<br>';
$s = $copyBrO->Stringify();
echo '6374 Copy Stringify ',$s,'<br>';
*/


echo '<br>15 test 6784 PYA (yr 4) PensionsTuples<br>';
$broString='62077135006620209,77135006620210,7713500612121145001121211,7510621211,774500112221145002122211,7520622211,774500212321145003'; # 23.12.12
$brO=NewBroFromString(6784, $broString);
echo $brO,'<br>';
echo 'EndBase Dat= ',$brO->EndBase(),'<br>';
foreach ($brO->AllBroDatOs() as $broDatKey => $datO)
  echo 'Key=',$broDatKey, ' Dat=',$datO->Dat, ' Dat2=',$datO->Dat2,', Srce=',$datO->Source(), ' Ref=',$datO->BroRefFull(),'<br>';
echo '6784 ';
$s = $brO->Stringify();
echo 'Stringify ',$s,'<br>';

exit;

for ($n=0;$n<=15;$n++)
  echo "$n ", !($n%3), '<br>';

exit;

/*
$bdMapDiMeId=$manDiMeId=$pyaDiMeId=0;
#            0 1 2 3
$diMeIdsA = [7,5,1,2,11,10,9];
$bdMapDiMeId = 5;
$bdMapDiMei  = 1;
$manDiMeId   = 1;
$manDiMei    = 2;
$proDiMeId   = 2;
$proDiMei    = 3;
$pyaDiMeId   = 7;
$pyaDiMei    = 0;
$testStartUtime= utime();
    # all OK so sort DiMes
    $diMeIds2A = [];                 # /- to get $bdMapDiMeId, $manDiMeId{,$proDiMeId} first in the array, with $pyaDiMeId last,
    if ($bdMapDiMeId) {              # |  and any others sorted in between so that refs will be consistent
      unset($diMeIdsA[$bdMapDiMei]); # |  (Doing this in just $diMeIdsA gave problems with non contig indices.)
      $diMeIds2A[] = $bdMapDiMeId;   # |  djh?? See if ok in $diMeIdsA with php 5.4
    }                                # |
    if ($manDiMeId) {                # v
      unset($diMeIdsA[$manDiMei]);
      $diMeIds2A[] = $manDiMeId;
      if ($proDiMeId) {
        unset($diMeIdsA[$proDiMei]);
        $diMeIds2A[] = $proDiMeId;
      }
    }
    if ($pyaDiMeId)
      unset($diMeIdsA[$pyaDiMei]);
    sort($diMeIdsA);
    $diMeIds2A = array_merge($diMeIds2A, $diMeIdsA);
    if ($pyaDiMeId) $diMeIds2A[] = $pyaDiMeId;
echo "time = ", MTime(utime() - $testStartUtime), '<br>';

$diMeRef = implode(',', $diMeIds2A);
echo "diMeRef=$diMeRef<br>"; # diMeRef=5,1,2,9,10,11,7
Dump('$diMeIds2A',$diMeIds2A);

$bdMapDiMeId=$manDiMeId=$pyaDiMeId=0;

#            0 1 2 3
$diMeIdsA = [7,5,1,2,11,10,9];
$bdMapDiMeId = 5;
$bdMapDiMei  = 1;
$manDiMeId   = 1;
$manDiMei    = 2;
$proDiMeId   = 2;
$proDiMei    = 3;
$pyaDiMeId   = 7;
$pyaDiMei    = 0;
$testStartUtime= utime();
    # all OK so sort DiMes
    # to get $bdMapDiMeId, $manDiMeId{,$proDiMeId} first in the array, with $pyaDiMeId last, and any others sorted in between so that refs will be consistent
    if ($bdMapDiMeId)
      unset($diMeIdsA[$bdMapDiMei]);
    if ($manDiMeId) {
      unset($diMeIdsA[$manDiMei]);
      if ($proDiMeId)
        unset($diMeIdsA[$proDiMei]);
    }
    if ($pyaDiMeId)
      unset($diMeIdsA[$pyaDiMei]);
    count($diMeIdsA) ? sort($diMeIdsA) : $diMeIdsA=[];
    if ($manDiMeId) {
      if ($proDiMeId)
        array_unshift($diMeIdsA, $proDiMeId);
      array_unshift($diMeIdsA, $manDiMeId);
    }
    if ($bdMapDiMeId)
      array_unshift($diMeIdsA, $bdMapDiMeId);
    if ($pyaDiMeId) $diMeIdsA[] = $pyaDiMeId;
echo "time = ", MTime(utime() - $testStartUtime), '<br>';
$diMeRef = implode(',', $diMeIdsA);
echo "diMeRef=$diMeRef<br>"; # diMeRef=5,1,2,9,10,11,7
Dump('$diMeIdsA',$diMeIdsA);
exit;
*/


/*
$broDatTypeOrBroDatKey = 37777;
        # Key includes an inst
        $instk = $broDatTypeOrBroDatKey % 10000;
        $broDatType = ($broDatTypeOrBroDatKey - $instk)/10000;
echo "broDatTypeOrBroDatKey=$broDatTypeOrBroDatKey, broDatType=$broDatType, inst=$instk<br>";

$broDatType = (int)($broDatTypeOrBroDatKey/10000);
echo "broDatTypeOrBroDatKey=$broDatTypeOrBroDatKey, broDatType=$broDatType<br>";

exit;
*/




# Speed test of == vs === for strings

$testStartUtime= utime();
$sum=0;
$t='7';
for ($i=0; $i < 100000; ++$i)
  $sum += ((string)$i==$t);
echo "== sum=$sum, time = ", MTime(utime() - $testStartUtime), '<br>';

$testStartUtime= utime();
$sum=0;
for ($i=0; $i < 100000; ++$i)
  $sum += ((string)$i===$t);
echo "=== sum=$sum, time = ", MTime(utime() - $testStartUtime), '<br>';

# Local
# == sum=1, time = 85,458 usecs
# === sum=1, time = 39,988 usecs

# == sum=1, time = 63,869 usecs
# === sum=1, time = 38,426 usecs

# == sum=1, time = 53,171 usecs
# === sum=1, time = 40,510 usecs

# Faster to use === with strings

exit;

/*
# http://au1.php.net/manual/en/language.oop5.overloading.php
class PropertyTest{
    ###  Location for overloaded data.
    private $data = array();

    ###  Overloading not used on declared properties.
    public $declared = 1;

    ###  Overloading only used on this when accessed outside the class.
    private $hidden = 2;

    public function __set($name, $value)   {
        echo "Setting '$name' to '$value'\n";
        $this->data[$name] = $value;
    }

    public function __get($name) {
        echo "Getting '$name'\n";
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }

    ###  As of PHP 5.1.0
    public function __isset($name)     {
        echo "Is '$name' set?\n";
        return isset($this->data[$name]);
    }

    public function __unset($name)     {
        echo "Unsetting '$name'\n";
        unset($this->data[$name]);
    }

    ###  Not a magic method, just here for example.
    public function getHidden()     {
        return $this->hidden;
    }
}

echo "<pre>\n";

$obj = new PropertyTest;

$obj->a = 1;
echo $obj->a . "\n\n";

var_dump(isset($obj->a));
unset($obj->a);
var_dump(isset($obj->a));
echo "\n";

echo $obj->declared . "\n\n";

echo "Let's experiment with the private property named 'hidden':\n";
echo "Privates are visible inside the class, so __get() not used...\n";
echo $obj->getHidden() . "\n";
echo "Privates not visible outside of class, so __get() is used...\n";
echo $obj->hidden . "\n";

*/
/*
class Foo {
    public static $my_static = 'foo';
    public function staticValue() { return self::$my_static;}
}

class Bar extends Foo {
    public function fooStatic() { return parent::$my_static;}
}


print Foo::$my_static . "<br>";

$foo = new Foo();
print $foo->staticValue() . "<br>";
#print $foo->my_static . "<br>";      // Undefined "Property" my_static

print $foo::$my_static . "<br>";
$classname = 'Foo';
print $classname::$my_static . "<br>"; // As of PHP 5.3.0

print Bar::$my_static . "<br>";
$bar = new Bar();
print $bar->fooStatic() . "<br>";

$foo = new $classname();
print 'foo via var classname='.$foo->staticValue(). "<br>";

*/

/*
# Speed test of == vs === for ints

$testStartUtime= utime();
$sum=0;
$t=7;
for ($i=0; $i < 100000; ++$i)
  $sum += ($i==$t);
echo "== sum=$sum, time = ", MTime(utime() - $testStartUtime), '<br>';

$testStartUtime= utime();
$sum=0;
for ($i=0; $i < 100000; ++$i)
  $sum += ($i===$t);
echo "=== sum=$sum, time = ", MTime(utime() - $testStartUtime), '<br>';

# Local
# == sum=1, time = 40,844 usecs
# === sum=1, time = 33,833 usecs

# == sum=1, time = 26,943 usecs
# === sum=1, time = 27,685 usecs

# == sum=1, time = 27,055 usecs
# === sum=1, time = 27,062 usecs

# == sum=1, time = 36,654 usecs
# === sum=1, time = 26,631 usecs

# AA
#== sum=1, time = 11,730 usecs
#=== sum=1, time = 10,926 usecs

#== sum=1, time = 11,408 usecs
#=== sum=1, time = 15,093 usecs

#== sum=1, time = 11,463 usecs
#=== sum=1, time = 11,113 usecs

#== sum=1, time = 11,574 usecs
#=== sum=1, time = 10,761 usecs

# Marginal. Perhaps slightly ===. Difference not enough to bother about unless === is really required or makes the intent clearer.

exit;
*/

/*
# Speed test of ways of calling a variable function

function Test($i) {
  global $sum;
  $sum+=$i;
}

$callbackFn = 'Test';

$testStartUtime= utime();
$sum=0;
for ($i=0; $i < 10000; ++$i)
  $callbackFn($i);
echo "\$callbackFn() sum=$sum, time = ", MTime(utime() - $testStartUtime), '<br>';

$testStartUtime= utime();
$sum=0;
for ($i=0; $i < 10000; ++$i)
  call_user_func($callbackFn, $i);
echo "call_user_func(\$callbackFn) sum=$sum, time = ", MTime(utime() - $testStartUtime), '<br>';

$function = new ReflectionFunction('Test');
$testStartUtime= utime();
$sum=0;
for ($i=0; $i < 10000; ++$i)
  $function->invoke($i);
echo "\$function->invoke($i) sum=$sum, time = ", MTime(utime() - $testStartUtime), '<br>';

# $callbackFn() time = 11,176 usecs               /- wo the sum
# call_user_func($callbackFn) time = 20,475 usecs |

# $callbackFn() sum=49995000, time = 15,796 usecs
# call_user_func($callbackFn) sum=49995000, time = 26,833 usecs

# Local:
# $callbackFn() sum=49995000, time = 16,054 usecs
# call_user_func($callbackFn) sum=49995000, time = 27,069 usecs
# $function->invoke(10000) sum=49995000, time = 27,414 usecs

# $callbackFn() sum=49995000, time = 15,701 usecs
# call_user_func($callbackFn) sum=49995000, time = 27,401 usecs
# $function->invoke(10000) sum=49995000, time = 26,557 usecs

# AA:
# $callbackFn() sum=49995000, time = 3,257 usecs
# call_user_func($callbackFn) sum=49995000, time = 7,908 usecs
# $function->invoke(10000) sum=49995000, time = 5,335 usecs

# $callbackFn() sum=49995000, time = 3,212 usecs
# call_user_func($callbackFn) sum=49995000, time = 8,522 usecs
# $function->invoke(10000) sum=49995000, time = 5,615 usecs

# => fastest is $callbackFn() form, tho not by as big a factor at AA

exit;
*/

function MTime($tsecs) {
  return number_format(round($tsecs*1000000)) . ' usecs';
}

function DebugMsg($msg) {
  echo $msg.'<br>';
}
