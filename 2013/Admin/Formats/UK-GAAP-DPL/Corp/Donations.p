Donations.p

Format to test Tuples use with Donations as per Charles' A-Dons Limited-2011-12-04-AccountsTRAP.html example
The DR analysis does not equal the PL expenses values

Date  2011.12.10

[h2 c:c "Donations Test for the Period Ended " [date f SchInputBRI.DatesPeriods.End]]

[h2 c:c "Directors' Report"]

[h4 "Political donations"]
[p "During the year the company made political donations of £" [span PLExps.OperationalAdministration.PoliticalDonations] ". Individual donations to EU political parties were:"]
[table cols:t01 center]
  [col 2,3 c:b restatedHdg SchInputBRI.DatesPeriods.End]
  [col 2,3 c:b "£"]
  [col SchInputDirRep.PoliticalCharitableDonations.Political.EU.SpecificEUPoliticalDonation.NameOrDescrRecipientOrg,T.all][col 2,3 SchInputDirRep.PoliticalCharitableDonations.Political.EU.SpecificEUPoliticalDonation.AmountToEUOrg,T.all]
[end]

//[p "The company has also contributed £" [span DR.Donations.Political.NonEU] " to non-EU political parties."]

[h4 "Charitable donations"]
[p "During the year the company made charitable donations of £" [span PLExps.OperationalAdministration.CharitableDonations] ". Individual donations were:"]
[table cols:t01 center]
  [col 2,3 c:b restatedHdg SchInputBRI.DatesPeriods.End]
  [col 2,3 c:b "£"]
  [col SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.DescrPurpose,T.all][col 2,3 SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount,T.all]
  [col 'Total via SchInputDirRep.PoliticalCharitableDonations.<br/>Charitable.SpecificCharitableDonation.Amount'][col SchInputDirRep.PoliticalCharitableDonations.Charitable.SpecificCharitableDonation.Amount]
[end]

[h2 c:c "And in the Detailed Profit and Loss Account"]

[zone PL]
[table cols:d01 center]
                                                          [col 2,3 c:b restatedHdg SchInputBRI.DatesPeriods.End]
                                                          [col 2,3 c:b "£"]
  [col c:b "Administrative expenses"]
  [row b:PLExps.OperationalAdministration.CharitableDonations]
  [row b:PLExps.OperationalAdministration.PoliticalDonations]
  [row subtotal aul dul]
[end]
