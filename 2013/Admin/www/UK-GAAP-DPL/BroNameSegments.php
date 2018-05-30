<?php /* Copyright 2011-2013 Braiins Ltd

BroNameSegments.php

Produce a table of Bro Name segments and usage count

History:
27.06.12 djh Started

*/

require 'BaseTx.inc';
require Com_Str_Tx.'BroNamesA.inc';    # $BroNamesA

Head("Bro Name Segments: $TxName", true);

echo "<h2 class=c>$TxName Bro Name Segments</h2>
<p class=c>(In order of descending number of times used, alphanumeric ascending order for equal counts.)</p>
<table class=mc>
<tr class='b bg0'><td>Name Segment</td><td>Times Used</td></tr>
";

$SegsA = [];

foreach ($BroNamesA as $name) {
  $segsA=explode('.',$name);
  foreach ($segsA as $seg)
    if (isset($SegsA[$seg]))
      ++$SegsA[$seg];
    else
      $SegsA[$seg]=1;
}
$SegsCopyA=$SegsA; # copy as o'wise get warning about array being modified during sort
uksort($SegsA, 'cmp');
foreach ($SegsA as $seg => $n)
  echo "<tr><td>$seg</td><td class=r>$n</td></tr>";
echo sprintf('</table>
<p class=c>Number of name segments=%s</p>',number_format(count($SegsA)));

Footer();
######

# Desc order by count, ascending order by seg name for equal count
function cmp($a, $b) {
  global $SegsCopyA;
  $na = $SegsCopyA[$a];
  $nb = $SegsCopyA[$b];
  if ($na == $nb)
    return ($a > $b) ? 1 : -1; # count same, asc  order by seg   (key) $a never == $b in this case
  return ($na > $nb) ? -1 : 1; # count diff, desc order by count (val)
}

