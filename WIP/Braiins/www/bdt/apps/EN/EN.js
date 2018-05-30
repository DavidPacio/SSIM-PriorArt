// EN New Entity

EN = {

App:function() {
  var AgCrs,ECrA,SCrA,DGsA,ET,CSz,Crs;
  Call('I',8);
  InPropsReplace('#ENdI');
  // Set Level number input attrs
  $('#ENnLev').attr({max:MLevel,'data-max':MLevel});
  // Buttons
  SetFormBtns(
    $('#ENbRes').button().click(function(){Revert();CoSize();return false;}),
    $('#ENbSav').button().click(function(){return Save(0,2,Crs);}) // Crs to be pushed onto dat
  );
  $('#ENbRef').click(function(e) { // Refresh
    e.stopPropagation();
    Call('R',1);
    return false;
  });

  this.OnFocus=function() {
    // App specific inputs events, all of which are off'ed on minimise
    // CoSize radio buttons
    $('#ENsCS [type=radio]').click(function() {CSz=+this.value;CoSize();});
    // RR checkboxes
    $('#ENdRR [type=checkbox]').click(Update);
    CoSize(); // to set the tips properly after minimise
  }

  this.Revert=function() { // Called from BDT.js.Revert() which is called via SetForm() as well as Revert Btn click
    CSz=2;
  }

  this.BackI=function(d) {
    var t,dat;
    // d: 0: CoSize RBs html | 1: Manager select options | 2: RR CBs html | 3: Agent Credits | 4: MemId | 5; EntityTypeCreditsA | 6: EntitySizeCreditsA | 7: Reduced DimGroupsA
    $('#ENsCS').html(d[0]);
    $('#ENsMN').html(d[1]);
    $('#ENdRR').html(d[2]);
    AgCrs=+d[3];
    ECrA=$.parseJSON(d[5]);
    SCrA=$.parseJSON(d[6]);
    DGsA=$.parseJSON(d[7]); // Credits, ExSmall
    ET=5;
    // Build dat array to populate form
    dat=['', '', 2, +d[4], MLevel];
    for (t=0; t<19; t++)
      dat.push(0);
    // Set Form: Inputs, Tips, Dat
    SetForm($('#ENdI input:not([readonly]),#ENdI select'), $('#ENdI :input:not(span>input),#ENdI span[title]'), dat);
    // Buttons
    // Select All
    $('#ENbAll').click(function(e) {
      e.stopPropagation();
      $('#ENdRR :enabled').each(function() {$(this).prop('checked',true);})
      Update();
      return false;
    });
    // Deselect All
    $('#ENbDeAll').click(function(e) {
      e.stopPropagation();
      $('#ENdRR :checked').each(function() {$(this).prop('checked',false);})
      Update();
      return false;
    });
    Show('.AgCrs',AgCrs);
  } // end of BackI

  this.BackS=function(d) {
    // OK|1|2 Agent Credits if R < 2 | Alert Message
    // R is 1 if unable to add due either to insuff credits or a duplicate ref; 2 if due to lock
    if (R<2) Show('.AgCrs,.AgACrs',AgCrs=+d[0]);
    if (R) {
      Update();
      Alert(d[1],
        '<span class=wng>Unable to Add New Entity</span>',
        400,
        [{text:'OK'}],
        function(){$('#ENiR').focus();},'important.png');
    }else
      Alert(d[1],
        'Done',
        500,
        [{text:'Change to this Entity',
          click:function(){
            AClose();
            Call('C',1,[$('#ENiR').val()]);
          }},
         {text:'Add another Entity',
          click:function(){
            AClose();
            Revert();
            $('#ENiN').focus();
          }},
         {text:'Close Window',
          click:function(){
            AClose();
            CloseApp();
          }},
        ],
        0,'ok.png');
  } // end of BackS

  this.BackR=function(d) {
    // OK Agent Credits
    AgCrs=+d[0];
    Show('.AgCrs',AgCrs);
    Update();
  }

  this.BackC=function(d) {
    $('#En .EName').html(EName=$('#ENiN').val());
    $('#En .ERef').html(ERef=$('#ENiR').val());
    ELevel=+d[0];
    CloseApp();
  }

  // App private functions
  function CoSize() {
    var d,$e;
    for (d=0; d<19; d++)
      if (DGsA[d][1]) { // Small
        $e=$('#ENc'+d);
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
    Crs=ECrA[ET]+SCrA[CSz];
    $('#ENdRR [type=checkbox]').each(function(k) {if ($(this).prop('checked')) Crs+=DGsA[k][0];});
    Show('.ENCrs',Crs);
    Show('.AgACrs',AgCrs-Crs);
    ShowHide(d=AgCrs<Crs,'.ENIC','.ENOK');
    if (d) ShowHide(AgCrs>=ECrA[ET],'#ENBaseOK');
    SetBtns();
  }
  function Show(q,h) {
    $('#ENdI '+q).html(h);
  }

} // end of App()
} // end of EN

