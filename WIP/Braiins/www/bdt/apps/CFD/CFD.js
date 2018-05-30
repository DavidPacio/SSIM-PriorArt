// CFD Current entity Financial statements Download
//     ReInit app

CFD = {

App:function() {
  Init();

  var data = [],
  columns = [
  {id:'Ref',   field:'Ref',   name:'Name',        width:150},
  {id:'Descr', field:'Descr', name:'Description', width:408, cssClass:'norb', headerCssClass:'norb'}
  ],
  Ref,FId,
  grid = new Slick.Grid('#CFDsg', [], columns, {autoHeight: true});

  // Download
  $('#CFDsg').on('click','.slick-row',function(e) {
    var row=grid.getCellFromEvent(e).row;
    FId=data[row].fid;
    if (FId) { // FId is 0 when no formats are available
      Call('W',3,[$('#CFDd [type=radio]:checked').val(), FId]);
      Ref=data[row].Ref;
      Msg('Generating...');
    }
  });

this.BackI=function(d) {
  // Have in Dat: n x [FormatId  Name  Descr]
  var e,d=d[0].split(''),l=d.length;
  for (e=0;e<l;)
    data.push({fid:+d[e++], Ref:d[e++], Descr:d[e++]});
  grid.setData(data);
}

this.ReInit=function() {
  Destroy();
  Init();
}

this.OnClose=function() {
  Destroy();
}

this.BackW=function(d) {
  Msg(d[0]);
  $('#CFDf input').val(d[1]+''+d[2]+''+FId); // file name | EntityId | FormatId
  $('#CFDf').submit();
  Busy(1); // so that Complete()'s Busy(-1) call won't end the Busy immediately. Do it after a 5 sec wait
  setTimeout(function() {Busy(-1);}, 5000); // to try to prevent a second click before DL has completed
}

  // App private functions
  function Destroy() {
    if (grid) grid.destroy();
  }
  function Init() {
    $('#CFDmsg').hide();
    Call('I',1);
  }
  function Msg(m) {
    $('#CFDmsg').html('<b>'+Ref+' Messages:</b><br>'+m).show();
  }

} // end of App()
} // end of CFD
