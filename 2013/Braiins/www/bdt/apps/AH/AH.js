// AH = Headings

AH = {

App:function() {
  Call('I',1);
  var data = [],
  columns = [
  {id:'Ref',  field:'Ref',  name:'Ref', width:200, cssClass:'b', sortable:true,focusable:false,selectable:false}, //  ui-corner-tl didn't do anything
  {id:'MHdg', field:'MHdg', name:'Braiins Master Headings', width:391, focusable:false,selectable:false},
  {id:'AHdg', field:'AHdg', name:AName+' Headings', width:391, cssClass:'inpbg norb',editor:Slick.Editors.Text, maxLength:100,headerCssClass:'norb'},
  ],
  options = {
    editable: true,
    autoHeight: true
  },
  sortcol = 'Ref', sortdir = 1,
  grid = new Slick.Grid('#AHsg', [], columns, options);

  grid.onCellChange.subscribe(function(e, args) {
    // Save the change
    // console.log('onCellChange args=',args);
    var aHdg=args.item.AHdg;
    if (aHdg) {
      // args {cell:2, grid, item {Ref, MHdg, AHdg}, row}
      if (MBits & 4) { // Master Agent so update Master too
        args.item.MHdg = aHdg
        grid.invalidateRow(args.row);
        grid.render();
      }
    }else{ // AHdg edited to empty, set to MHdger
      args.item.AHdg=aHdg=args.item.MHdg;
      grid.invalidateRow(args.row);
      grid.render();
    }
    BusyO.NoBusy=1; // To prevent Busy() flash
    Call('S',1,[args.item.Ref,aHdg]);
  });

  // Sort
  grid.onSort.subscribe(function(e, args) {
  //args = {grid, multiColumnSort, sortAsc, sortCol}
    sortdir = args.sortAsc ? 1 : 0;
  //sortcol = args.sortCol.field;
    data.sort(comparer);
    grid.invalidateAllRows();
    grid.render();
  });

this.BackI = function(d) {
  // Set the data after it has been received from the server
  // Have in Dat: <n x [ Ref  Master Heading  Agent Heading, '' if same] | Alert text>
  if (R) { // R is 1 if unable to read data due to a lock failure
    Alert(d[0], '<span class=wng>Unable to Fetch Data for Editing</span>', 400, [{text:'OK'}], CloseApp, 'important.png');
    return;
  }
  var aHdg, mHdg, ref, e, d=d[0].split(''), l=d.length;
  for (e=0;e<l;) {
    ref  = d[e++];
    mHdg = d[e++];
    if (!(aHdg = d[e++]))
      aHdg = mHdg;
    data.push({Ref:ref, MHdg:mHdg, AHdg:aHdg});
  }
  grid.setData(data);
  $('.slick-sort-indicator').click(); // to sort and show sorted ^. Only Ref col is sortable
 }

this.BackS = function(d) {
  if (R) // R is 1 if there was a lock failure
    Alert(d[0], '<span class=wng>Unable to Save Edit</span>', 400, [{text:'OK'}], CloseApp, 'important.png');
}

this.OnClose=function() {
  if (grid) grid.destroy();
}

  function comparer(a,b) {
    var x = a[sortcol], y = b[sortcol];
    return (x == y ? 0 : sortdir ? (x > y ? 1 : -1) : (x > y ? -1 : 1));
  }

} // end of App()
} // end of AH

