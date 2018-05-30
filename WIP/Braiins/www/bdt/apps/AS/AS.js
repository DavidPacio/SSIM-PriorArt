// AS Admin Snapshot

AS = {

App:function() {
  Call('I',1);
  // Tips and Refresh button
  (Tao.$Tips=$('#ASbRef')).button().click(function(e) {
    e.stopPropagation();
    Call('R',1);
    return false;
  });
}, // end of App()

BackI:function(d) {
  $('#AStOI').html(d[0]);
},

BackR:function(d) {
  $('#AStOI').html(d[0]);
}

} // end of AS

