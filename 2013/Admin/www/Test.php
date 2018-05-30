<?php /* Copyright 2011-2013 Braiins Ltd

Test.php

*/

require 'BaseSIM.inc';

Head('Test');
echo '<br>';
#phpinfo();

# PHP_INT_SIZE, and maximum value using the constant PHP_INT_MAX 
echo "PHP_INT_SIZE=",PHP_INT_SIZE,BR;
echo "PHP_INT_MAX=",PHP_INT_MAX,BR;

exit;


$s = '1,7,2,3.12,3.20,3.2,4,3';
$testStartUtime= utime();
for ($k=0; $k<10000; ++$k) {
  $aA = explode(COM, $s);
  foreach ($aA as $i => $v)
    if (strpos($v, DOT) === false)
      $aA[$i] = (int)$v;
  $t = implode(COM, $aA);
}
sort($aA);
echo "time = ", MTime((utime() - $testStartUtime)/10000), '<br>';
Dump("aA from<br>$s<br>$t", $aA);

$s = '1,7,2,3:12,3:20,3:2,4,3';
$testStartUtime= utime();
for ($k=0; $k<10000; ++$k) {
  $aA = explode(COM, $s);
  foreach ($aA as $i => $v)
    if (is_numeric($v))
      $aA[$i] = (int)$v;
  $t = implode(COM, $aA);
}
sort($aA);
echo "time = ", MTime((utime() - $testStartUtime)/10000), '<br>';
Dump("aA from<br>$s<br>$t", $aA);

$s = '1,7,2,3:12,3:20,3:2,4,3';
$testStartUtime= utime();
for ($k=0; $k<10000; ++$k) {
  $aA = explode(COM, $s);
  foreach ($aA as $i => $v)
    if (is_numeric($v))
      $aA[$i] = (int)$v;
  $t = str_replace(':', DOT, implode(COM, $aA));
}
sort($aA);
echo "time = ", MTime((utime() - $testStartUtime)/10000), '<br>';
Dump("aA from<br>$s<br>$t", $aA);

# 15, 13, 15 usecs
# 16, 13, 15
# 15, 13, 15
# 16, 13, 15
# Use of : and is_numeric() is fastest for unpack and back in : form, marginally faster with back in . form
exit;

/*
$a = [1,7,2,'3:12', '3:20', '3:2', 4, 3];
sort($a);
Dump('$a', $a);

$a = [1,7,2,'3.12', '3.20', '3.2',4, 3];
sort($a);
Dump('$a', $a);

$s = '1,7,2,3.12,3.20,3.2,4,3';
$a = explode(COM, $s);
sort($a);
Dump('$a from $s', $a);

*/

/*
$txA = [1,2,3,4,5,6,7,8,9];

$testStartUtime= utime();
for ($i=0; $i<10000; ++$i) {
  $retA = [
    'InclBroSetId' => 1,
    'BoId'      => 2,
    'Name'      => 'Fred',
    'Level'     => 3,
    'DadId'     => 4,
    'Bits'      => 5,
    'DataTypeN' => 6,
    'AcctTypes' => 'BS',
    'SignN'     => 7,
    'SumUp'     => 8,
    'PeriodSEN' => 9,
    'SortOrder' => 10,
    'FolioHys'  => 'FolioHys',
    'ExclPropDims'  => 'ExclPropDims',
    'AllowPropDims' => 'AllowPropDims',
    'PMemDiMesA'    => 'PMemDiMesA',
    'UsablePropDims'=> 'UsablePropDims',
    'MasterId'  => 11,
    'SlaveYear' => 12,
    'CheckTest' => 'CheckTest',
    'Zones'     => 'Zones',
    'ShortName' => 'ShortName',
    'Ref'       => 'Ref',
    'Descr'     => 'Descr',
    'Comment'   => 'Comment'];

      $retA['TxId']    = 1234;
      $retA['TupId']   = 12345;
      $retA['ManDims'] = 'ManDims';
      $retA['xA']      = $txA;
}
echo "time = ", MTime(utime() - $testStartUtime), '<br>';
#Dump('retA', $retA);

$testStartUtime= utime();
for ($i=0; $i<10000; ++$i) {
  $retA = [
    'InclBroSetId' => 1,
    'BoId'      => 2,
    'Name'      => 'Fred',
    'Level'     => 3,
    'DadId'     => 4,
    'Bits'      => 5,
    'DataTypeN' => 6,
    'AcctTypes' => 'BS',
    'SignN'     => 7,
    'SumUp'     => 8,
    'PeriodSEN' => 9,
    'SortOrder' => 10,
    'FolioHys'  => 'FolioHys',
    'ExclPropDims'  => 'ExclPropDims',
    'AllowPropDims' => 'AllowPropDims',
    'PMemDiMesA'    => 'PMemDiMesA',
    'UsablePropDims'=> 'UsablePropDims',
    'MasterId'  => 11,
    'SlaveYear' => 12,
    'CheckTest' => 'CheckTest',
    'Zones'     => 'Zones',
    'ShortName' => 'ShortName',
    'Ref'       => 'Ref',
    'Descr'     => 'Descr',
    'Comment'   => 'Comment'];

  $retA = $retA + [
      'TxId'     => 1234,
      'TupId'    => 12345,
      'ManDims'  => 'ManDims',
      'xA'       => $txA];
}
echo "time = ", MTime(utime() - $testStartUtime), '<br>';
#Dump('retA', $retA);

$testStartUtime= utime();
for ($i=0; $i<10000; ++$i) {
  $retA = [
    'InclBroSetId' => 1,
    'BoId'      => 2,
    'Name'      => 'Fred',
    'Level'     => 3,
    'DadId'     => 4,
    'Bits'      => 5,
    'DataTypeN' => 6,
    'AcctTypes' => 'BS',
    'SignN'     => 7,
    'SumUp'     => 8,
    'PeriodSEN' => 9,
    'SortOrder' => 10,
    'FolioHys'  => 'FolioHys',
    'ExclPropDims'  => 'ExclPropDims',
    'AllowPropDims' => 'AllowPropDims',
    'PMemDiMesA'    => 'PMemDiMesA',
    'UsablePropDims'=> 'UsablePropDims',
    'MasterId'  => 11,
    'SlaveYear' => 12,
    'CheckTest' => 'CheckTest',
    'Zones'     => 'Zones',
    'ShortName' => 'ShortName',
    'Ref'       => 'Ref',
    'Descr'     => 'Descr',
    'Comment'   => 'Comment'];

      $retA['TxId']    = 1234;
      $retA['TupId']   = 12345;
      $retA['ManDims'] = 'ManDims';
      $retA['xA']      = $txA;
}
echo "time = ", MTime(utime() - $testStartUtime), '<br>';

$testStartUtime= utime();
for ($i=0; $i<10000; ++$i) {
  $retA = [
    'InclBroSetId' => 1,
    'BoId'      => 2,
    'Name'      => 'Fred',
    'Level'     => 3,
    'DadId'     => 4,
    'Bits'      => 5,
    'DataTypeN' => 6,
    'AcctTypes' => 'BS',
    'SignN'     => 7,
    'SumUp'     => 8,
    'PeriodSEN' => 9,
    'SortOrder' => 10,
    'FolioHys'  => 'FolioHys',
    'ExclPropDims'  => 'ExclPropDims',
    'AllowPropDims' => 'AllowPropDims',
    'PMemDiMesA'    => 'PMemDiMesA',
    'UsablePropDims'=> 'UsablePropDims',
    'MasterId'  => 11,
    'SlaveYear' => 12,
    'CheckTest' => 'CheckTest',
    'Zones'     => 'Zones',
    'ShortName' => 'ShortName',
    'Ref'       => 'Ref',
    'Descr'     => 'Descr',
    'Comment'   => 'Comment'];

  $retA = $retA + [
      'TxId'     => 1234,
      'TupId'    => 12345,
      'ManDims'  => 'ManDims',
      'xA'       => $txA];
}
echo "time = ", MTime(utime() - $testStartUtime), '<br>';

Gave
time = 42,222 usecs
time = 59,401 usecs
time = 40,934 usecs
time = 59,753 usecs

So it is faster to add the extra elements separately rather than via a + (union) array op.

exit;
*/

/*
      $IbrosA[$BroId] = $IbrosA[$BroId] + [
        'TxId'    => $TxId,
        'TupId'   => $TupId,
        'ManDims' => $ManDims];
*/



$A = ['abc', '123', 1234];
Dump('A before IntegeriseA', $A);
IntegeriseA($A);
Dump('A after IntegeriseA', $A);
$A = ['a'=>'abc', 'b'=>'123', 'c'=>1234];
Dump('A before IntegeriseA', $A);
IntegeriseA($A);
Dump('A after IntegeriseA', $A);
exit;


$A = [1,2,3];
$aR = &$A;
Dump('A', $A);
Dump('aR', $aR);
#unset($aR);
#Dump('A after unset of aR', $A); # The unset of $aR did not unset $A
unset($A, $B); # No error re unknown $B
Dump('aR after unset of A', $aR); # $aR and A remain after unset of A



function MTime($tsecs) {
  return number_format(round($tsecs*1000000)) . ' usecs';
}

function DebugMsg($msg) {
  echo $msg.'<br>';
}
