// CSF Current entity Set to Final
//     ReInit app

CSF = {

App:function() {
  Call('I',2);
  $('#CSFbOk').button().click(function(){Call('S')});
  $('#CSFbRF').button().click(function(){Call('U')});
}, // end of App()

ReInit:function() {
  Call('I',2);
},

BackI:function(d) {
  /* Have in Dat: case  reason for case 4
  Cases:                                            Show           Hide
  1: Set to Final, can't be reversed by this member CSFdIF         CSFdOk,CSFdNo
  2: Set to Final, can be reversed by this member   CSFdIF, CSFpRF CSFdOk,CSFdNo
  3: Not Final and OK to Set To Final               CSFdOk         CSFdIF,CSFdNo
  4: Not Final and Not OK to Set To Final           CSFdNo         CSFdIF,CSFdOk */
  switch (+d[0]) {
    case 1: $('#CSFdOk,#CSFdNo').hide();$('#CSFdIF').show();break;
    case 2: $('#CSFdOk,#CSFdNo').hide();$('#CSFdIF,#CSFpRF').show();break;
    case 3: $('#CSFdIF,#CSFdNo').hide();$('#CSFdOk').show();break;
    case 4: $('#CSFdIF,#CSFdOk').hide();$('#CSFdNo').show();$('#CSFsNo').html(d[1]);break;
  }
},

BackS:function() {
  Alert(ERef+' has been Set to Final.','Done',250,[{text:'OK'}],CSF.ao.$a.CloseWin,'ok.png');
},

BackU:function() {
  Alert('The Final Setting has been Removed from '+ERef,'Done',250,[{text:'OK'}],CSF.ao.$a.CloseWin,'ok.png');
}

} // end of CSF
