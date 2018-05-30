© Braiins Ltd 2011

Format: Corp/test.b
        Test Format

Main format: None
Sub-formats: None

History:
31.08.11 djh Started

[zone PL]

[table cols:tn012 center]
[row b:RevOp.Turnover]
[col y0 0+PL.CoS][col y1 1][col y2 2]
[col y0 PL.CoS][col y1 1][col y2 2]
[row subtotal aul]
[row subtotal aul]
[row subtotal aul][col 'Subtotal with title']
[row b:PL.CoS][col y0][col y1 1][col y2 0]
[row subtotal aul][col 'Subtotal with title']
[row subtotal aul]
[row b:PL.CoS subtotal aul]
[row dr b:PL.CoS][col y0 b:RevOp.Turnover 0][col y1][col y2 22]
[row total aul dul][col '(LOSS)/PROFIT FOR THE FINANCIAL YEAR']
[row total aul dul][col 'Test of auto keep on total']
[end]
