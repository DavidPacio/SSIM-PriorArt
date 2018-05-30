// EE Entity Edit
//     ReInit app

EE = {

App:function() {
  var AgCrs,ECrA,SCrA,DGsA,ET,CSz,Crs,ECrs,OCrs;
  Init();
  InPropsReplace('#EEdI');
  // Set Level number input attrs
  $('#EEnLev').attr({max:MLevel,'data-max':MLevel});
  // Buttons
  SetFormBtns(
    $('#EEbRev').button().click(function(){Revert();CoSize();return false;}),
    $('#EEbSav').button().click(function(){return Save(0,2,Crs);}) // Crs to be pushed onto dat
  );

  $('#EEbRef').click(function(e) { // Refresh
    e.stopPropagation();
    Call('R',1);
    return false;
  });

  this.OnFocus=function() {
    // App specific inputs events, all of which are off'ed on minimise
    // CoSize radio buttons
    $('#EEsCS [type=radio]').click(function() {CSz=+this.value;CoSize();});
    // RR checkboxes
    $('#EEdRR [type=checkbox]').click(Update);
    CoSize(); // to set the tips properly after minimise
  }

  this.ReInit=function() {
    Init();
    $('#EEsCS').empty();
    $('#EEsMN').empty();
    $('#EEdRR').empty();
  }

  this.Revert=function() { // Called from BDT.js.Revert() which is called via SetForm() as well as Revert Btn click
    CSz=Tao.ODatA[2];
    Show('.EEOCrs',OCrs=Credits());
  }

  this.BackI=function(d) {
    var t,dat;
    // d: 0: CoSize RBs html | 1: Manager select options | 2; RR CBs html | 3: Agent Credits | 4: EName  | 5: ERef | 6: CoSize | 7: ManagerId | 8: Level | 9: EntityTypeCreditsA | 10: EntitySizeCreditsA | 11; Reduced DimGroupsA
    $('#EEsCS').html(d[0]);
    $('#EEsMN').html(d[1]);
    $('#EEdRR').html(d[2]);
    AgCrs=+d[3];
    ECrA=$.parseJSON(d[9]);
    SCrA=$.parseJSON(d[10]);
    DGsA=$.parseJSON(d[11]); // [Credits, ExSmall, Allowed]
    ET=5;
    // Build dat array to populate form
    dat=[d[4], d[5], +d[6], +d[7], +d[8]];
    for (t=0; t<19; t++)
      dat.push(DGsA[t][2]);
    // Set Form: Inputs, Tips, Dat
    SetForm($('#EEdI input:not([readonly]),#EEdI select'), $('#EEdI :input:not(span>input),#EEdI span[title]'), dat);
    // Buttons in dynamic code
    // Select All
    $('#EEbAll').click(function(e) {
      e.stopPropagation();
      $('#EEdRR :enabled').each(function() {$(this).prop('checked',true);})
      Update();
      return false;
    });
    // Deselect All
    $('#EEbDeAll').click(function(e) {
      e.stopPropagation();
      $('#EEdRR :enabled:checked').each(function() {$(this).prop('checked',false);})
      Update();
      return false;
    });
    Show('.AgCrs,.AgACrs',AgCrs);
  } // end BackI()

  this.BackS=function(d) {
    // OK|1|2 Agent Credits if R < 2 | ERef or Alert Message
    if (R<2) Show('.AgCrs,.AgACrs',AgCrs=+d[0]);
    // R is 1 if unable to save edits due either to insuff credits or a duplicate ref; 2 if due to lock
    if (R) {
      Update();
      Alert(d[1],
        '<span class=wng>Unable to Save Edits</span>',
        400,
        [{text:'OK'}],
        function(){$('#EEiR').focus();},'important.png');
    }else{
      $('#En .ERef').html(ERef=d[1]);
      $('#En .EName').html(EName=$('#EEiN').val());
      ELevel=+$('#EEnLev').val();
      CloseApp();
    }
  } // end of BackS()

  this.BackR=function(d) {
    // OK Agent Credits
    AgCrs=+d[0];
    Show('.AgCrs',AgCrs);
    Update();
  }

  // Apps local functions
  function Init() {
    Call('I',12);
  }

  function CoSize() {
    var d,$e;
    for (d=0; d<19; d++)
      if (DGsA[d][1]) { // ExSmall
        $e=$('#EEc'+d);
        if (CSz<=2) { // small
          $e.prop('checked',false);
          $e.prop('disabled',true);
          BTip.Append($e.parent(), ' (Disabled for Small Company.)');
        }else{
          $e.prop('disabled',false);
          BTip.Revert($e.parent());
        }
      }
    Update();
  }
  function Update() {
    ECrs=Credits();
    Crs=ECrs-OCrs;
    Show('.EEECrs',ECrs);
    Show('.EECrs',Crs>=0 ?(!Crs?'Zero':Crs):-Crs);
    Show('.EEWord',Crs>=0 ? 'charge':'refund');
    Show('.EEWordv',Crs>=0 ? 'charged':'refunded');
    Show('.AgACrs',AgCrs-Crs);
    ShowHide(SetSaveNOK(Crs>AgCrs),'.EEIC','.EEOK');
    SetBtns();
  }
  function Credits() {
    var crs=ECrA[ET]+SCrA[CSz];
    $('#EEdRR [type=checkbox]').each(function(k) {if ($(this).prop('checked')) crs+=DGsA[k][0];});
    return crs;
  }
  function Show(q,h) {
    $('#EEdI '+q).html(h);
  }

} // end of App()
} // end of EE

