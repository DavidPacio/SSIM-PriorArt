<?php
# Start of Format Test.p with index 0 and level 0
# [h2 c:c "Test of Use of Stock Braiins Dimension Function without TxId"] Test.p line 13
H(2,"Test of Use of Stock Braiins Dimension Function without TxId",'c');
# [table cols:d01 center] Test.p line 15
TableStart('d01',1);
  # [col 2 c:b 'Year 0'][col 3 c:b 'Year 1'] Test.p line 16
  Row(0,array(2,0,'Year 0',0,'b'),array(3,0,'Year 1',0,'b'));
  # [col 2,3 c:b "£"] Test.p line 17
  Row(0,array(2,0,"£",0,'b'),array(3,0,"£",0,'b'));
  # [row b:ExpOp.Stock:Function.Admin] Test.p line 18
  Row(array('5100,1287'),array(1,20),array(2,4),array(3,4));
  # [row b:ExpOp.Stock:Function.CoS] Test.p line 19
  Row(array('5100,1288'),array(1,20),array(2,4),array(3,4));
  # [row b:ExpOp.Stock:Function.Distrib] Test.p line 20
  Row(array('5100,1289'),array(1,20),array(2,4),array(3,4));
  # [row b:ExpOp.Stock] Test.p line 21
  Row(array(5100),array(1,20),array(2,4),array(3,4));
  # [row b:RevOp] Test.p line 22
  Row(array(4000),array(1,20),array(2,4),array(3,4));
# [end] Test.p line 23
TableEnd();
# End of Format Test.p
