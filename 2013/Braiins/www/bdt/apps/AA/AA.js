// AA Admin Account

AA = {

App:function() {
  Call('I',1);
  SetFormBtns( // Revert, Save
    $('#AAbRev').button().click(Revert),
    $('#AAbSav').button().click(function() {return Save();}) // Not just Save as Save() has optional argumnents
  );
},

BackI:function(d) { // d: Json array
  // Set Form: Inputs, Tips, Dat
  if (R) // R is 1 if unable to read data due to a lock failure
    Alert(d[0], '<span class=wng>Unable to Fetch Data for Editing</span>', 400, [{text:'OK'}], CloseApp, 'important.png');
  else
    SetForm($('#AAdI input:not([readonly])'), $('#AAdI :input:not(span>input),#AAdI span[title]'), $.parseJSON(d[0]));
},

BackS:function(d) { // OK AName|1 Alert Message
  if (R) // R is 1 if unable to save edits due to duplicate name, or there was a lock failure
    Alert(d[0], '<span class=wng>Unable to Save Edits</span>', 400, [{text:'OK'}],function(){$('#AAiN').focus();}, 'important.png');
  else{
    AName = d[0];
    $('#Ag span').html(AName+', '+DName);
    CloseApp();
  }
}

} // end of AA

