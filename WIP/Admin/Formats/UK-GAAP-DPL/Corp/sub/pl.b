© Braiins Ltd 2011

Format: Corp/pl.b utf-8
        Profit and Loss Page

Main format: LtdFullGAAP.bp
Sub-formats: None

History:
02.02.11 djh Started

====
[np]
[footer]
  [lines 5]
  [p c:c "The notes on pages "[xref page notesX] " to "[xref page notesEndX] " form an integral part of these financial statements."]
  [p c:c,m0 'Page '[page#]]
[end]
[xref target plX]
[h2 c:c "Profit and Loss"[nl]"for the Period Ended " [date f SchInputBRI.DatesPeriods.End]]
[line]
[zone PL]
[table cols:dn01 center]
                                                  [col2 "Note"] [col 3,4 c:b restatedHdg SchInputBRI.DatesPeriods.End]
                                                                [col 3,4 c:b "£"]
  [col "Turnover - continuing operations"][col [xref link N1X]] [col y0,, PLRev.TradingIncome.TurnoverGrossOpRevenue]
  [row b:PLExps ul]         row b:PLExps,ExpenseType.CoS ul
  [row b:PLExps.Materials]  row b:PLExps.ProfitTaxTotals.GrossProfit
  [row b:PLExps rc:asp1]    row b:PLExps,ExpenseType.Distrib rc:asp1
  [row b:PLExps ul]         row b:PLExps,ExpenseType.Admin ul
//[col "Operating profit - continuing operations"]       [col 3,4 PLExps.ProfitTaxTotals.OperatingProfit][col n [xref link N2X]]
//  [col c:pt10 "Interest receivable"]                   [col 3,4 b:PL.Interest.Receivable c:pt10]
//  [col PL.Interest.Payable]                            [col 3,4 ul PL.Interest.Payable][col n [xref link N4X]]
//  [col "Profit on ordinary activities before taxation"][col 3,4 ul PL.Totals.ProfitBeforeTax]
  [line]
//[row b:PL.Tax.OnOrdinaryActivities ul][col1 "Tax on profit on ordinary activities"][col [xref link N5X]][col 3,4]
//[row b:TaxOnOrdActivities ul][col1 "Tax on profit on ordinary activities"][col [xref link N5X]][col 3,4]
//[col "Profit on ordinary activities after taxation"] [col 3,4 dul PL]
//[col "Profit on ordinary activities after taxation"] [col 3,4 dul PL.Totals.Profit]
[end]

[h2 c:c "Profit and Loss Using Subtotals, Totals and Above Underlines"[nl]"for the Period Ended " [date f SchInputBRI.DatesPeriods.End]]
[line]
[table cols:dn01 center]
                                                  [col2 "Note"] [col 3,4 c:b restatedHdg SchInputBRI.DatesPeriods.End]
                                                                [col 3,4 c:b "£"]
  [col "Turnover - continuing operations"][col [xref link N1X]] [col y0,, PLRev.TradingIncome.TurnoverGrossOpRevenue]
//[row b:PLExps:ExpenseType.CoS]
//[row b:PLExps.ProfitTaxTotals.GrossProfit subtotal aul]
//[row b:PLExps:ExpenseType.Distrib rc:asp1]
//[row b:PLExps:ExpenseType.Admin]
//[row b:PLExps.ProfitTaxTotals.OperatingProfit subtotal aul][col "Operating profit - continuing operations"][col n [xref link N2X]]
//[col c:pt10 "Interest receivable"]              [col 3,4 b:PL.Interest.Receivable c:pt10]
//[row b:PL.Interest.Payable]                     [col n [xref link N4X]]
//[row b:PL.Totals.ProfitBeforeTax subtotal aul ul][col "Profit on ordinary activities before taxation"]
  [line]
//[row b:PL.Tax.OnOrdinaryActivities]     [col "Tax on profit on ordinary activities"]  [col n [xref link N5X]]
//[row b:TaxOnOrdActivities]     [col "Tax on profit on ordinary activities"]  [col n [xref link N5X]]
//[row b:PL.Totals.Profit total aul dul][col "Profit on ordinary activities after taxation"]
[end]
[if STRGL.StatementThatThereWereNoGainsInPeriodOtherThanThoseInPL][p c:c "There were no recognised gains or losses other than the profit for the financial year."]

PROFIT AND LOSS ACCOUNT
For the year ended 31 December 2009
                                      Note         2009           2008
                                                      £              £
Turnover - continuing operations          1  16,613,551     19,195,013   uk-gaap:TurnoverGrossOperatingRevenue
Cost of sales                               (13,819,379)   (16,524,490)  uk-gaap:CostSales
                                             ----------     ----------
Gross profit                                  2,794,172      2,670,523   uk-gaap:GrossProfitLoss
Distribution costs                             (221,780)      (260,471)  uk-gaap:DistributionCosts
Administrative expenses                        (862,719)    (1,057,994)  uk-gaap:AdministrativeExpenses
                                             ----------     ----------
Operating profit - continuing operations   2  1,709,673      1,352,058   uk-gaap:OperatingProfitLoss
Interest receivable                                 372          1,876   uk-gaap:OtherInterestReceivableSimilarIncome
Interest payable and similar charges       4    (81,149)      (117,176)  uk-gaap:InterestPayableSimilarCharges
                                             ----------     ----------
Profit on ordinary activities before taxation 1,628,896      1,236,758   uk-gaap:ProfitLossOnOrdinaryActivitiesBeforeTax
Tax on profit on ordinary activities       5   (212,422)      (337,173)  uk-gaap:TaxOnProfitOrLossOnOrdinaryActivities
                                             ----------     ----------
Profit on ordinary activities after taxation  1,416,474        899,585   uk-gaap:ProfitLossForPeriod
                                             ==========     ==========

There were no recognised gains or losses other than the profit for the financial year  uk-gaap:StatementThatThereWereNoGainsLossesInPeriodOtherThanThoseInProfitLossAccount



Alternative Text Example from Charles
                                              Note           £           £
TURNOVER                                         1  16,613,552  19,195,013

Cost of sales                                       14,651,380  16,666,490
                                                    ----------  ----------
GROSS PROFIT                                         1,962,172   2,528,523

Distribution costs                                     221,780     260,471
Administrative expenses                                887,119   1,057,994
                                                    ----------  ----------
OPERATING PROFIT                                 2     853,273   1,210,058

Attributable to:
Operating profit before exceptional items            1,709,673   1,210,058
  Exceptional items                              2    (856,400)          –
                                                    ----------  ----------
                                                       853,273   1,210,058

Loss on disposal of discontinued operations      5    (600,000)          –
Cost of restructuring the company                6           –  (1,100,000)
                                                    ----------  ----------
                                                       253,273     110,058

Interest receivable                                        372       1,876
Interest payable and similar charges             7     (81,149)   (117,176)

                                                    ----------   ----------
PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION   172,496       (5,242)

Tax on profit/(loss) on ordinary activities      8     212,422      337,173

                                                     ----------  ----------
LOSS ON ORDINARY ACTIVITIES AFTER TAXATION             (39,926)    (342,415)

Extraordinary items                              9           –     (500,000)
                                                     ---------    ----------
(LOSS)/PROFIT FOR THE FINANCIAL YEAR                   (39,926)      157,585
                                                     ---------    ----------
[np]
[h2 c:c "Alternative text Profit and Loss Test"[nl]"for the Period Ended " [date f SchInputBRI.DatesPeriods.End]]

[table cols:dn01 center]
                                                   [col3 restatedHdg [date y SchInputBRI.DatesPeriods.End]][col4 restatedHdg [date y y:1 SchInputBRI.DatesPeriods.End]]
                                                   [col3,4 c:b "£"]
[col1 'TURNOVER']                                  [col3 -16613552][col 4 -19195013]
[line]
[row dr][col1 'Cost of sales']                     [col3 14651380][col 4 16666490]
[row subtotal aul][col 'GROSS PROFIT/(LOSS)']
[line]
[row dr][col 'Distribution costs']                 [col3 221780][col 4 260471]
[row dr][col 'Administrative expenses']            [col3 887119][col 4 1057994]
[row dr][col 'Test 0 row - should not appear']                      [col 3 0][col 4 0]
[row dr keep][col 'Test 0 row with keep - should appear']           [col 3 0][col 4 0]
[row dr keepHide][col 'Test 0 row with keepHide - should be hidden'][col 3 0][col 4 0]
[row total aul][col 'OPERATING PROFIT/(LOSS)']
[line]
[col 'Attributable to:']
[col c:pl 'Operating profit before exceptional items'] [col3 -1709673][col 4 -1210058]
[col c:pl 'Exceptional items']                     [col 3 856400][col 4 0]
[row subtotal aul]
[line]
[col 'Loss on disposal of discontinued operations'][col 3 600000][col4 0]
[col 'Cost of restructuring the company']          [col 3 0][col 4 1100000]
[row subtotal aul]
[line]
//ol 'Interest receivable']                        [col 3 -372][col 4 -1876]
//[row b:PL.Interest.Receivable]
//[row b:PL.Interest.Payable]
[row subtotal aul n:PL.OrdinaryActivities][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION']
[line]
[row dr alt:PL.OrdinaryActivities][col 'Tax on profit/(loss) on ordinary activities'][col3 ul 212422][col ul 337173]
[row subtotal aul][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION']
[line]
[row dr][col 'Extraordinary items']                [col 3 0][col 4 -500000]
[row total aul dul rc:b][col '(LOSS)/PROFIT FOR THE FINANCIAL YEAR']
[end]

[np]
[h2 c:c "Alternative text Profit and Loss Test 4 Column Layout"[nl]"for the Period Ended " [date f SchInputBRI.DatesPeriods.End]]
[p c:c 'This test references row PL.OrdinaryActivities of the 2 column table for the alt: balances for the tax row.']
[table cols:dn0011 center]
[col y01,y02,y11,y12 c:b restatedHdg SchInputBRI.DatesPeriods.End]
[col y01,,,, c:b "£"]
[col  'TURNOVER']                                  [col y02 -16613552][col y12 -19195013]
[row dr][col  'Cost of sales']                     [col y02 14651380][col y12 16666490]
[row subtotal aul cols:y02,y12][col 'GROSS PROFIT/(LOSS)']
[line]
[row dr][col 'Distribution costs']                 [col y01 221780][col y11 260471]
[row dr][col 'Administrative expenses']            [col y01 887119][col y11 1057994]
[row subtotal aul cols:y01->2,y11->2]
[row total aul cols:y02,y12][col 'OPERATING PROFIT/(LOSS)']
[line]
[col 'Attributable to:']
[col c:pl 'Operating profit before exceptional items'] [col y01 -1709673][col y11 -1210058]
[col c:pl 'Exceptional items']                         [col y01   856400][col y11 0]
[row subtotal aul cols:y01->2,y11->2][col 'OPERATING PROFIT/(LOSS)']
[line]
[col 'Loss on disposal of discontinued operations'][col y01 600000][col y11 0]
[col 'Cost of restructuring the company']          [col y01 0][col y11 1100000]
[row subtotal aul cols:y01->2,y11->2]
[row subtotal aul cols:y02,y12]
[line]
//ol 'Interest receivable']                        [col y01 -372][col y11 -1876]
//[row b:PL.Interest.Receivable cols:y01,y11]
//[row b:PL.Interest.Payable cols:y01,y11]
[row subtotal aul cols:y01->2,y11->2]
[row subtotal aul cols:y02,y12][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION']
[line]
[row dr alt:PL.OrdinaryActivities][col 'Tax on profit/(loss) on ordinary activities'][col y02 ul 212422][col y12 337173]
[row subtotal aul cols:y02,y12][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION']
[line]
[row dr][col 'Extraordinary items']                [col y02 0][col y12 -500000]
[row total aul dul rc:b cols:y02,y12][col '(LOSS)/PROFIT FOR THE FINANCIAL YEAR']
[end]

[np]
[h2 c:c "Alternative text Profit and Loss Test 3 Column Layout"[nl]"for the Period Ended " [date f SchInputBRI.DatesPeriods.End]]
[p c:c 'This test references row PL.OrdinaryActivities of the 2 column table for the alt: balances for the tax row.']
[table cols:dn001 center]
[col y01,y02,y11 c:b restatedHdg SchInputBRI.DatesPeriods.End]
[col y01,,, c:b "£"]
[col  'TURNOVER']                                  [col y02 -16613552][col y1 -19195013]
[row dr][col  'Cost of sales']                     [col y02 14651380][col y1 16666490]
[row subtotal aul cols:y02,y1][col 'GROSS PROFIT/(LOSS)']
[line]
[row dr][col 'Distribution costs']                 [col y01 221780][col y11 260471]
[row dr][col 'Administrative expenses']            [col y01 887119][col y11 1057994]
[row subtotal aul cols:y01->2,y11]
[row total aul cols:y02,y1][col 'OPERATING PROFIT/(LOSS)']
[line]
[col 'Attributable to:']
[col c:pl 'Operating profit before exceptional items'] [col y01 -1709673][col y11 -1210058]
[col c:pl 'Exceptional items']                         [col y01   856400][col y11 0]
[row subtotal aul cols:y01->2,y11][col 'OPERATING PROFIT/(LOSS)']
//[row subtotal aul cols:y01->2,y11]
//[row subtotal aul cols:y02,y1]
[line]
[col 'Loss on disposal of discontinued operations'][col y01 600000][col y11 0]
[col 'Cost of restructuring the company']          [col y01 0][col y11 1100000]
[row subtotal aul cols:y01->2,y11]
[row subtotal aul cols:y02,y1]
[line]
//ol 'Interest receivable']                        [col y01 -372][col y11 -1876]
//[row b:PL.Interest.Receivable cols:y01,y11]
//[row b:PL.Interest.Payable cols:y01,y11]
[row subtotal aul cols:y01->2,y11]
[row subtotal aul cols:y02,y1][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION']
[line]
[row dr alt:PL.OrdinaryActivities][col 'Tax on profit/(loss) on ordinary activities'][col y02 ul 212422][col y1 337173]
[row subtotal aul cols:y02,y1][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION']
[line]
[row dr][col 'Extraordinary items']                [col y02 0][col y1 -500000]
[row total aul dul rc:b cols:y02,y1][col '(LOSS)/PROFIT FOR THE FINANCIAL YEAR']
[end]
====

[np]
[h2 c:c "Alternative text Profit and Loss Test 4 Column Layout with col 3 unused apart from headings to test auto column suppression"[nl]"for the Period Ended " [date f SchInputBRI.DatesPeriods.End]]
[p c:c 'This test references row PL.OrdinaryActivities of the 2 column table for the alt: balances for the tax row.']
[table cols:dn0011 center]
[col y01,y02,y11,y12 c:b restatedHdg SchInputBRI.DatesPeriods.End]
[col y01,,,, c:b "£"]
[col1 'TURNOVER']                                  [col y02 -16613552][col y12 -19195013]
[row dr][col1 'Cost of sales']                     [col y02 14651380][col y12 16666490]
[row subtotal aul cols:y02,y12][col 'GROSS PROFIT/(LOSS)']
[line]
[row dr][col 'Distribution costs']                 [col y01 221780][col y12 260471]
[row dr][col 'Administrative expenses']            [col y01 887119][col y12 1057994]
[row subtotal aul cols:y01->2,y12]
[row total aul cols:y02,y12][col 'OPERATING PROFIT/(LOSS)']
[line]
[col 'Attributable to:']
[col c:pl 'Operating profit before exceptional items'] [col y01 -1709673][col y12 -1210058]
[col c:pl 'Exceptional items']                         [col y01   856400][col y12 0]
[row subtotal aul cols:y01->2,y12][col 'OPERATING PROFIT/(LOSS)']
[line]
[col 'Loss on disposal of discontinued operations'][col y01 600000][col y12 0]
[col 'Cost of restructuring the company']          [col y01 0][col y12 1100000]
[row subtotal aul cols:y01->2,y12]
[row subtotal aul cols:y02,y12]
[line]
//ol 'Interest receivable']                        [col y01 -372][col y12 -1876]
//[row b:PL.Interest.Receivable cols:y01,y12]
//[row b:PL.Interest.Payable cols:y01,y12]
[row subtotal aul cols:y01->2,y12]
[row subtotal aul cols:y02,y12][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION']
[line]
[row dr alt:PL.OrdinaryActivities][col 'Tax on profit/(loss) on ordinary activities'][col y02 ul 212422][col y12 337173]
[row subtotal aul cols:y02,y12][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION']
[line]
[row dr][col 'Extraordinary items']                [col y02 0][col y12 -500000]
[row total aul dul rc:b cols:y02,y12][col '(LOSS)/PROFIT FOR THE FINANCIAL YEAR']
[end]

[np]
[h2 c:c "Alternative text Profit and Loss Test (Part)"]
[p c:c "4 Column Layout with col 3 unused apart from headings and with col 1 used only for single rows to test subtotal moving such balances to the 'to' column, which should result in the suppression of both columns 1 and 3"[nl] 'Plus cell reference tests including to the moved cell']
[table cols:d0011 center]                                                          row #
[col y01,y02,y11,y12 c:b restatedHdg SchInputBRI.DatesPeriods.End]                             0
[col y01,,,, c:b "£"]                                                               1
[row b:PLRev.TradingIncome.TurnoverGrossOpRevenue n:R.PLRev.TradingIncome.TurnoverGrossOpRevenue cols:y02,y12]                    2
[row n:R.WithMovedCell][col 'Cost of restructuring the company'][col y01 1][col y12 1100000]  3
[row subtotal aul cols:y01->2,y12]                                                  4
[row subtotal aul cols:y02,y12]                                                     5
[line]                                                                              6
//[row b:PL.Interest.Receivable.Other cols:y01,y12]                                   7
//[row b:PL.Interest.Payable cols:y01,y12]                                            8
[row subtotal aul cols:y01->2,y12]                                                  9
[row subtotal aul cols:y02,y12 n:PL.OrdinaryActivitiesT42][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION']      10
[line]                                                                                                                   11
[row dr alt:PL.OrdinaryActivitiesT42][col 'Tax on profit/(loss) on ordinary activities'][col y02 ul 212422][col y12 337173] 12
[row subtotal aul cols:y02,y12][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION']                               13
[line]                                                                             14
[row dr][col 'Extraordinary items']                [col y02 0][col y12 -500000]    15
[row total aul dul rc:b cols:y02,y12][col '(LOSS)/PROFIT FOR THE FINANCIAL YEAR']  16
[col PL.OrdinaryActivitiesT42:d][col y02 keep PL.OrdinaryActivitiesT42:y02]
[col R.PLRev.TradingIncome.TurnoverGrossOpRevenue:d ' times 2'][col y02 2*R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y02]
[col 'Reference to R.WithMovedCell:y01 which the subtotal moves to y:02'][col y02 R.WithMovedCell:y01]
[end]
[p R.PLRev.TradingIncome.TurnoverGrossOpRevenue:d " from R.PLRev.TradingIncome.TurnoverGrossOpRevenue:d in a p statement"]
[p ""R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y02 " from R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y02 in a p statement"]
[p ""R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y12 " from R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y12 in a p statement"]
[p R.PLRev.TradingIncome.TurnoverGrossOpRevenue:d" "R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y02" "R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y12 " from all three in a p statement"]

[table cols:dn010 center]                                                          row #
[col y01,y11 c:b restatedHdg SchInputBRI.DatesPeriods.End]                             0
[col y01,, c:b "£"]                                                               1
[row b:PLRev.TradingIncome.TurnoverGrossOpRevenue]                                                       2
[col 'Cost of restructuring the company']        [col y0 0][col y1 1100000]     3
[row subtotal aul]                                                                  4
[row subtotal aul]                                                                  5
[line]                                                                              6
//[row b:PL.Interest.Receivable.Other]                                                7
//[row b:PL.Interest.Payable]                                                         8
[row subtotal aul]                                                                  9
[row subtotal aul][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION']      10
[line]                                                                                                                   11
[row dr alt:PL.OrdinaryActivitiesT42][col 'Tax on profit/(loss) on ordinary activities'][col y0 ul 212422][col y1 337173] 12
[row subtotal aul][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION']                               13
[line]                                                                             14
[row dr][col 'Extraordinary items']                [col y01 0][col y11 -500000]    15
[row total aul dul rc:b][col '(LOSS)/PROFIT FOR THE FINANCIAL YEAR']  16
[line]                                                                             14
[col PL.OrdinaryActivitiesT42:d][col y0 keep PL.OrdinaryActivitiesT42:y02][col 5 '2008 - 2009']
[col R.PLRev.TradingIncome.TurnoverGrossOpRevenue:d " times 2"][col y01 2*R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y02][col y1 2*R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y12][col y02 this:y1:colSum-this:y01:colsum]
[row subtotal aul]
[col R.PLRev.TradingIncome.TurnoverGrossOpRevenue:d " times 2"][col y01 2*R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y02][col y1 2*R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y12][col y02 this:y1:colSum-this:y01:colsum]
[row total aul dul]
[line]                                                                             14
[col PL.OrdinaryActivitiesT42:d][col y0 keep PL.OrdinaryActivitiesT42:y02][col 5 '2009 - 2008']
[col R.PLRev.TradingIncome.TurnoverGrossOpRevenue:d " times 2"][col y01 2*R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y02][col y1 2*R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y12][col y02 this:y01:colSum-this:y1:colsum]
//[row subtotal aul cols:y01,y1]
[row subtotal cols:y01,y1][col y02 this:y01:colSum-this:y1:colsum]
[col R.PLRev.TradingIncome.TurnoverGrossOpRevenue:d " times 2"][col y01 2*R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y02][col y1 2*R.PLRev.TradingIncome.TurnoverGrossOpRevenue:y12][col y02 this:y01:colSum-this:y1:colsum]
[row total aul dul cols:y01,y1][col y02 this:y01:colSum-this:y1:colsum]
[end]

[np]
[h2 c:c "Alternative text Profit and Loss Test with Column Sums and Variance"[nl]"for the Period Ended " [date f SchInputBRI.DatesPeriods.End]]
[table cols:dn01111 center]
[col3 restatedHdg [date y SchInputBRI.DatesPeriods.End]][col restatedHdg [date y y:1 SchInputBRI.DatesPeriods.End]][col restatedHdg ""[date y SchInputBRI.DatesPeriods.End][nl]"Col Sum"][col restatedHdg ""[date y y:1 SchInputBRI.DatesPeriods.End][nl]"Col Sum"][col "2009-2008"[nl]"Variance"]
                                                   [col 3,4,5,6,7 c:b "£"]
[col1 'TURNOVER']                                  [col 3 -16613552][col 4 -19195013][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
[line]
[col 'Cost of sales']                              [col 3 dr 14651380][col 4 dr 16666490][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
[row subtotal][col 'GROSS PROFIT/(LOSS)']          [col 3 aul][col 4 aul][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
[line]
[row dr][col 'Distribution costs']                 [col3 221780][col 4 260471]        [col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
[row dr][col 'Administrative expenses']            [col3 887119][col 4 1057994]       [col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
[row dr][col 'Test 0 row - should not appear']                      [col 3 0][col 4 0][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
[row dr keep][col 'Test 0 row with keep - should appear']           [col 3 0][col 4 0][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
[row dr keepHide][col 'Test 0 row with keepHide - should be hidden'][col 3 0][col 4 0][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
[row total][col 'OPERATING PROFIT/(LOSS)']         [col 3 aul][col aul]               [col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
[line]
[col 'Attributable to:']
[col c:pl 'Operating profit before exceptional items'][col3 -1709673][col 4 -1210058][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
[col c:pl 'Exceptional items']                        [col 3 856400][col 4 0]        [col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
[row subtotal]                                        [col 3 aul][col aul]           [col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
[line]
[col 'Loss on disposal of discontinued operations'][col 3 600000][col4 0]  [col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
[col 'Cost of restructuring the company']          [col 3 0][col 4 1100000][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
[row subtotal]                                     [col 3 aul][col aul]    [col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
[line]
//[row b:PL.Interest.Receivable cols:3,4]                                   [col 5 this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
//[row b:PL.Interest.Payable cols:3,4]                                      [col 5 this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
[row subtotal][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES BEFORE TAXATION'][col 3 aul][col aul][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
[line]
[row dr alt:PL.OrdinaryActivities][col 'Tax on profit/(loss) on ordinary activities'][col 3 212422][col 4 337173][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
[row subtotal][col 'PROFIT/(LOSS) ON ORDINARY ACTIVITIES AFTER TAXATION'][col 3 aul][col aul][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
[line]
[row dr][col 'Extraordinary items']                [col 3 0][col 4 -500000][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
[row total rc:b][col '(LOSS)/PROFIT FOR THE FINANCIAL YEAR'][col 3 aul dul][col aul dul][col this:y0:colSum][col this:y1:colSum][col this:y0:colSum-this:y1:colSum]
[end]

