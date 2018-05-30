<?php /* Copyright 2011-2013 Braiins Ltd

LocalStorage.php

=> admin or www version

History:
12.01.11 djh Written

*/

if ($_SERVER['DOCUMENT_ROOT'][0] == '/') # /hosted
  header('Location: http://www.Braiins.com/utils/LocalStorage.htm');
else
  #header('Location: LocalStorage.htm');
  require 'LocalStorage.htm';
#####
