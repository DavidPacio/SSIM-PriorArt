© Braiins Ltd 2011

Format: Corp/Test.p
        Test Format

Main format: None
Sub-formats: None

//[table]
//  [col SchInputEntity.Officers.Name:.CoSec]
//[end]

[h2 c:c "Test of Use of Stock Braiins Dimension Function without TxId"]

[table cols:d01 center]
  [col 2 c:b 'Year 0'][col 3 c:b 'Year 1']
  [col 2,3 c:b "£"]
  [row b:ExpOp.Stock:ExpenseType.Admin]
  [row b:ExpOp.Stock:ExpenseType.CoS]
  [row b:ExpOp.Stock:ExpenseType.Distrib]
  [row b:ExpOp.Stock]
  [row b:RevOp]
[end]
