// EL Entities List

EL = {

App:function() {
  var Tn=Now,C=0; // tab name, Calls object key
  // Ref and Level are disabled initially. Don't want Ref but do want Level, so can't select enabled. Skip first=Ref instead
  // Revert, Save (Go)
  SetFormBtns(null, $('#ELbGo').button().click(function(){C=Call('R',5,Data(),Tn,C);}));
  // Set Form: Inputs, Tips{, Dat}
  SetForm($('#ELtOpt input:not(:first)'), $('#EL button'));
  // Set Level number input attrs
  $('#ELnLev').attr({max:MLevel,'data-max':MLevel,value:MLevel});
  SetData(Data()); // Set with initial empty state plus MLevel
  InitApp(); // No 'I' call so need to call InitApp() directly

  // Select All
  $('#ELbAll').click(function(e) {
    e.stopPropagation(); // especially this one that is clicked programmaticallyduring App()
    Tao.$Inputs.each(function() {$(this).prop('checked',true);});
    $('#ELnLev').prop('disabled', false);
    SetBtns();
  });

  // Deselect All
  $('#ELbUnAll').click(function(e) {
    e.stopPropagation();
    Tao.$Inputs.each(function() {$(this).prop('checked',false);});
    $('#ELnLev').prop('disabled', true).attr({value:MLevel});
    SetBtns();
  });

  // Disable/Enable Level number input with checkbox change
  $('#ELcLev').click(function() {$('#ELnLev').prop('disabled', !this.checked);});

  // Button to go to report tab
  $('#ELbTab').button().hide().click(function(){TabFocus(C);});

}, // end of App()

// Show the Report Tab button when back from the 'R' call
BackT:function() {$('#ELbTab').show()},

// Hide the Report Tab button if Tab closes
TabClose:function() {$('#ELbTab').hide()}

} // end of EL

