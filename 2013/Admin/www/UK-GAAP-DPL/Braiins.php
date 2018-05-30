<?php /* Copyright 2011-2013 Braiins Ltd

Braiins.php

=> Braiins.com

History:
30.12.11 djh Written

*/

if ($_SERVER['DOCUMENT_ROOT'][0] == '/') # /hosted
  header('Location: http://www.Braiins.com/');
else
  header('Location: ../../../Braiins/www/');

#####
