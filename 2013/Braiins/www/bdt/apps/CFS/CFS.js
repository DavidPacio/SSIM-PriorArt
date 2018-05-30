// CFS Current entity Financial Statements
//     ReInit app

CFS = {

App:function() {
  Init();

  var data = [],
  columns = [
  {id:'Ref',   field:'Ref',   name:'Name',        width:150},
  {id:'Descr', field:'Descr', name:'Description', width:408, cssClass:'norb', headerCssClass:'norb'}
  ],
  Tn=Now, C=0, Ref,
  grid = new Slick.Grid('#CFSsg', [], columns, {autoHeight:true});

  // Tab Btn
  $('#CFSbTab').button().click(function(){TabFocus(C)});

  // FS
  $('#CFSsg').on('click','.slick-row',function(e) {
    var row=grid.getCellFromEvent(e).row, fId=data[row].fid;
    if (fId) { // fId is 0 when no formats are available
      C=Call('R',6,[fId],Tn,C);
      Ref=data[row].Ref;
      Msg('Generating...');
      $('#CFSbTab').hide();
    }
  });


this.BackI=function(d) {
  // Have in Dat: n x [FormatId  Name  Descr]
  var e,d=d[0].split(''),l=d.length;
  for (e=0;e<l;)
    data.push({fid:+d[e++], Ref:d[e++], Descr:d[e++]});
  grid.setData(data);
}

// No BackR as this is a Tab app. Instead have BackT:

this.BackT=function(d) {
  Msg(d[0]);
  $('#CFSbTab').show().blur();
}

this.ReInit=function() {
  Destroy();
  Init();
}

this.OnClose=function() {
  Destroy();
}

  // App private functions
  function Destroy() {
    if (grid) grid.destroy();
  }
  function Init() {
    $('#CFSbTab').hide();
    $('#CFSmsg').hide();
    Call('I',1);
  }
  function Msg(m) {
    $('#CFSmsg').html('<b>'+Ref+' Messages:</b><br>'+m).show();
  }

}, // end of App()

// Hide the Report button if Tab closes
TabClose:function() {$('#CFSbTab').hide();}

} // end of CFS

