// CDT Current entity Data Trail
//     ReInit app

CDT = {

App:function() {
  var Tn=Now, C=0;

  Call('I',1);

  // Revert, Save (Go)
  SetFormBtns(null, $('#CDTbGo').button().click(function(){C=Call('R',5,Data(),Tn,C);}));

  // Select All
  $('#CDTbAll').click(function(e) {
    e.stopPropagation(); // especially this one that is clicked programmaticallyduring App()
    if (Tao.$Inputs) {
      Tao.$Inputs.each(function() {$(this).prop('checked',true);});
      SetBtns();
    }
  });
  // Deselect All
  $('#CDTbUnAll').click(function(e) {
    e.stopPropagation();
    if (Tao.$Inputs) {
      Tao.$Inputs.each(function() {$(this).prop('checked',false);});
      SetBtns();
    }
  });

  // Button to go to report tab
  $('#CDTbTab').button().hide().click(function(){TabFocus(C);});

this.BackI=function(d) {
  // Have in Dat: # table body rows html
  $('#CDTtbody').html(d[0]);
  if (R) {
    // Not OK return
    Tao.$Inputs = null;
    SetBtn(Tao.$SavBtn,false);
  }else{
    // Set Form: Inputs, Tips{, Dat}
    SetForm($('#CDTtbody input'), $('#CDT button,#CDT td[title]'));
    SetData(Data()); // Set with initial empty state
    $('#CDTbAll').click(); // Select All
  }
}


}, // end of App()

ReInit:function() {
  Call('I',1);
  $('#CDTmsg').empty();
  CDT.TabClose();
},

// Show the Report Tab button when back from the 'R' call
BackT:function() {$('#CDTbTab').show()},

// Hide the Report Tab button if Tab closes
TabClose:function() {$('#CDTbTab').hide()}

} // end of CDT
