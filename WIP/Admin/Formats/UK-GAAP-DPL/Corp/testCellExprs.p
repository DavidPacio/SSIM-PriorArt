© Braiins Ltd 2011

Format: Corp/testCellExprs.b
        Test Format

Main format: None
Sub-formats: None

[zone PL]

[h2 c:c "Alternative text Profit and Loss Test (Part)"]
[p c:c "4 Column Layout with col 3 unused apart from headings and with col 1 used only for single rows to test subtotal moving such balances to the 'to' column, which should result in the suppression of both columns 1 and 3"[nl] 'Plus cell reference tests including to the moved cell']
[table cols:t0011 center]                                                          row #
[col y01,y02,y11,y12 c:b restatedHdg SchInputBRI.DatesPeriods.End]                             0
[col y01,,,, c:b "£"]                                                               1
[row b:RevOp.Turnover n:R.RevOp.Turnover cols:y02,y12]                    2
[row n:R.WithMovedCell][col 'Cost of restructuring the company'][col y01 1][col y12 1100000]  3
[row subtotal aul cols:y01->2,y12]                                                  4
[row subtotal aul cols:y02,y12]                                                     5
[line]                                                                              6
[row b:Exp.Financial.InterestPayableSimilarCharges.FinanceIncome.Ne.Other cols:y01,y12]                                   7
[row b:Exp.Financial.InterestPayableSimilarCharges.FinanceIncome.Ne cols:y01,y12]                                            8
[row subtotal aul cols:y01->2,y12]                                                  9
[row subtotal aul cols:y02,y12 n:PL.OrdinaryActivitiesT42][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION']      10
[line]                                                                                                                   11
[row dr alt:PL.OrdinaryActivitiesT42][col 'Tax on profit/(loss) on ordinary activities'][col y02 ul 212422][col y12 337173] 12
[row subtotal aul cols:y02,y12][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION']                               13
[line]                                                                             14
[row dr][col 'Extraordinary items']                [col y02 0][col y12 -500000]    15
[row total aul dul rc:b cols:y02,y12][col '(LOSS)/PROFIT FOR THE FINANCIAL YEAR']  16
[col PL.OrdinaryActivitiesT42:t][col y02 keep PL.OrdinaryActivitiesT42:y02]
[col R.RevOp.Turnover:t " times 2"][col y02 2*R.RevOp.Turnover:y02]
[col 'Reference to R.WithMovedCell:y01 which the subtotal moves to y:02'][col y02 R.WithMovedCell:y01]
[end]
[p R.RevOp.Turnover:t " from R.RevOp.Turnover:t in a p statement"]
[p ""R.RevOp.Turnover:y02 " from R.RevOp.Turnover:y02 in a p statement"]
[p ""R.RevOp.Turnover:y12 " from R.RevOp.Turnover:y12 in a p statement"]
[p R.RevOp.Turnover:t" "R.RevOp.Turnover:y02" "R.RevOp.Turnover:y12 " from all three in a p statement"]

[table cols:tn010 center]                                                          row #
[col y01,y11 c:b restatedHdg SchInputBRI.DatesPeriods.End]                             0
[col y01,, c:b "£"]                                                               1
[row b:RevOp.Turnover]                                                       2
[col 'Cost of restructuring the company']        [col y0 0][col y1 1100000]     3
[row subtotal aul]                                                                  4
[row subtotal aul]                                                                  5
[line]                                                                              6
[row b:Exp.Financial.InterestPayableSimilarCharges.FinanceIncome.Ne.Other]                                                7
[row b:Exp.Financial.InterestPayableSimilarCharges.FinanceIncome.Ne]                                                         8
[row subtotal aul]                                                                  9
[row subtotal aul][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION']      10
[line]                                                                                                                   11
[row dr alt:PL.OrdinaryActivitiesT42][col 'Tax on profit/(loss) on ordinary activities'][col y0 ul 212422][col y1 337173] 12
[row subtotal aul][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION']                               13
[line]                                                                             14
[row dr][col 'Extraordinary items']                [col y01 0][col y11 -500000]    15
[row total aul dul rc:b][col '(LOSS)/PROFIT FOR THE FINANCIAL YEAR']  16
[line]                                                                             14
[col PL.OrdinaryActivitiesT42:t][col y0 keep PL.OrdinaryActivitiesT42:y02][col 5 '2008 - 2009']
[col R.RevOp.Turnover:t " times 2"][col y01 2*R.RevOp.Turnover:y02][col y1 2*R.RevOp.Turnover:y12][col y02 this:y1:colSum-this:y01:colsum]
[row subtotal aul]
[col R.RevOp.Turnover:t " times 2"][col y01 2*R.RevOp.Turnover:y02][col y1 2*R.RevOp.Turnover:y12][col y02 this:y1:colSum-this:y01:colsum]
[row total aul dul]
[line]                                                                             14
[col PL.OrdinaryActivitiesT42:t][col y0 keep PL.OrdinaryActivitiesT42:y02][col 5 '2009 - 2008']
[col R.RevOp.Turnover:t " times 2"][col y01 2*R.RevOp.Turnover:y02][col y1 2*R.RevOp.Turnover:y12][col y02 this:y01:colSum-this:y1:colsum]
//[row subtotal aul cols:y01,y1]
[row subtotal cols:y01,y1][col y02 this:y01:colSum-this:y1:colsum]
[col R.RevOp.Turnover:t " times 2"][col y01 2*R.RevOp.Turnover:y02][col y1 2*R.RevOp.Turnover:y12][col y02 this:y01:colSum-this:y1:colsum]
[row total aul dul cols:y01,y1][col y02 this:y01:colSum-this:y1:colsum]
[end]
