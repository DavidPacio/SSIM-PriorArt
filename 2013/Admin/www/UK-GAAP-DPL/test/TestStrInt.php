<?php

   //include("hexarray.php");
   include("binarray.php");
   include("bytearray.php");

   $m0 = memory_get_usage();
   $a = new WordArray(32768);
   $m1 = memory_get_usage();

   $i = 0;
   while (++$i < 32768) {
       $a[$i] = $i;
   }

   $m2 = memory_get_usage();
   $b = range(1,32768);
   $m3 = memory_get_usage();

   echo "BinArray: ",  number_format($m1-$m0), "<br/>",
        "php array: ", number_format($m3-$m2), "<br/>";

   echo $a[22], '<br/>', $a[32767], '<br/>';
   for ($i=0; $i<23; $i++)
     echo $i, ' ', $a[$i], '<br/>';
#   print_r($a)
