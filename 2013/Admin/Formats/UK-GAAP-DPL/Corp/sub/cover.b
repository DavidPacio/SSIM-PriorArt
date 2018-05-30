© Braiins Ltd 2011

Format: Corp/cover.b
        Corporate cover page

Main format: LtdFullGAAP.bp
Sub-formats: None

History:
31.01.11 djh Started

====
[footer]
  [line]
  [p c:c,m0 "Cover page footer"]
  [p c:c,m0,b SchInputEntity.ThirdPartyAgents.Name,TPAType.Accountants]
  [p c:c,m0 SchInputEntity.ThirdPartyAgents.MeansContact.Address,TPAType.Accountants@Address]
[end]

No New Page as this is the first format
[lines 10]
[h1 c:c SchInputEntity.Names.CurrentLegalOrRegistered]
[lines 2]
[h2 c:c CompanyRegistrationNumberH ':' [nl] SchInputEntity.IdentifyingCodes.UKCompaniesHouseRegisteredNumber " (" IncorporationCountryS ")"]
[lines 2]
[h2 c:c DraftS FullAccountsS]
[lines 2]
[h2 c:c AccountsPeriodH]
[line]
[h2 c:c "Start date: " [date c:nb SchInputBRI.DatesPeriods.Start]]
[h2 c:c "End date: " [date c:nb SchInputBRI.DatesPeriods.End]]
===
