© Braiins Ltd 2011

Format: Corp/notes.b utf-8
        Notes

Main format: LtdFullGAAP.bp
Sub-formats: None

History:
11.05.11 djh Started

====
Header unchanged

[np]
[footer]
  [p c:c,m0 'Page '[page#]]
[end]
[xref target notesX]
[h2 c:c "Notes to the "AccountsShortH[nl]"for the Period Ended " [date f SchInputBRI.DatesPeriods.End]]
[line]
[xref target N1X]
[h2 "Note <span>1</span> Turnover"]
[p "The analysis of turnover by geographical market by destination is as follows"]
[table cols:d01]
                         [col2,3 c:b restatedHdg SchInputBRI.DatesPeriods.End]
                         [col2,3 c:b "£"]
  [col1 "United Kingdom"][col2,3 SchInputPL.SegAnalysisRevCostsProfits.Geography.RevenueByDestination.Geo,Countries.UK]
  [col1 "Europe"]        [col2,3 SchInputPL.SegAnalysisRevCostsProfits.Geography.RevenueByDestination.Geo,Countries.Europe]
  [col1 "North America"] [col2,3 SchInputPL.SegAnalysisRevCostsProfits.Geography.RevenueByDestination.Geo,Countries.NorthAmerica]
  [col1 "Rest of World"] [col2,3 ul SchInputPL.SegAnalysisRevCostsProfits.Geography.RevenueByDestination.Geo,Countries.OtherRegions]
                         [col2,3 dul PLRev.TradingIncome.TurnoverGrossOpRevenue]
[end]

[if y:1 SchInputPL.SegAnalysisRevCostsProfits.Geography.RevenueByDestination.Geo,Countries.UK,Restated || PLRev.TradingIncome.TurnoverGrossOpRevenue,Restated]
  [table cols:d1]

    [if y:1 SchInputPL.SegAnalysisRevCostsProfits.Geography.RevenueByDestination.Geo,Countries.UK,Restated]
                                                    [col2 c:b restatedhdg SchInputBRI.DatesPeriods.End]
      [col1 c:b 'Restated UK Turnover']             [col2 c:b "£"]
      [col1 'UK Turnover:Restated [OriginalAmount]']    [col SchInputPL.SegAnalysisRevCostsProfits.Geography.RevenueByDestination.Geo,Countries.UK,Restated]
      [col1 'UK Turnover:Restated.AccountingPolicyIncr'][col SchInputPL.SegAnalysisRevCostsProfits.Geography.RevenueByDestination.Geo,Countries.UK,Restated.AcctPolicyIncr]
      [col1 'UK Turnover:Restated.PriorPeriodIncr']     [col SchInputPL.SegAnalysisRevCostsProfits.Geography.RevenueByDestination.Geo,Countries.UK,Restated.PriorPeriodIncr]
      [col1 'UK Turnover:Restated.Amount']          [col aul SchInputPL.SegAnalysisRevCostsProfits.Geography.RevenueByDestination.Geo,Countries.UK,Restated.Amount]
      [line]
    [end]
    [if y:1 PLRev.TradingIncome.TurnoverGrossOpRevenue,Restated]
      [col1 c:b 'Restated Turnover']
      [col1 'Turnover:Restated [OriginalAmount]']    [col PLRev.TradingIncome.TurnoverGrossOpRevenue,Restated]
      [col1 'Turnover:Restated.AccountingPolicyIncr'][col PLRev.TradingIncome.TurnoverGrossOpRevenue,Restated.AcctPolicyIncr]
      [col1 'Turnover:Restated.PriorPeriodIncr']     [col PLRev.TradingIncome.TurnoverGrossOpRevenue,Restated.PriorPeriodIncr]
      [col1 'Turnover:Restated.Amount']          [col aul PLRev.TradingIncome.TurnoverGrossOpRevenue,Restated.Amount]
    [end]
  [end]
[end]


[xref target N2X]
[h2 "Note <span>2</span>"]
[xref target N3X]
[h2 "Note <span>3</span>"]
[xref target N4X]
[h2 "Note <span>4</span>"]
[xref target N5X]
[h2 "Note <span>5</span>"]
[np]
[p "Second page of Notes"]
[np]
[p "Third page of Notes"]
[xref target notesEndX]
====
