// EC Entity Change

EC = { // no var so it can be deleted

App:function() {
  Call('I',1);
  var data,
  columns = [
  {id:'Ref',   field:'Ref',   name:'Reference',   width:150, sortable:true, toolTip:'Click to Sort'},
  {id:'EName', field:'EName', name:'Entity Name', width:408, cssClass:'norb', sortable:true, headerCssClass:'norb', toolTip:'Click to Sort'}
  ],
  sortcol, sortdir, ref,
  grid = new Slick.Grid('#ECsg', [], columns);

  // Filter
  $('#ECfil').keyup(function(e) {
    var i,l,fil=$(this).val().toLowerCase(), dat=[];
    if (e.which == 27) {
      this.value = '';
      dat=data;
    }else
      for (i=0,l=data.length; i<l; ++i)
        if (data[i].Ref.toLowerCase().contains(fil) || data[i].EName.toLowerCase().contains(fil))
          dat.push(data[i]);
    SetData(dat);
  });

  // Sort
  grid.onSort.subscribe(function(e, args) {
  //args = {grid, multiColumnSort, sortAsc, sortCol}
    sortdir = args.sortAsc ? 1 : 0;
    sortcol = args.sortCol.field;
    grid.getData().sort(comparer);
    grid.invalidateAllRows();
    grid.render();
  });

  // Change
  $('#ECsg').on('click','.slick-row',function() {
    if (ref=$(this).find('div:first')[0].innerHTML) {
      Call('C',3,[ref]);
      $(this).css({fontWeight:'bold',color:'#007AA3',fontSize:'125%'});
    }
  });

// Init return, 1 field
this.BackI = function(d) {
  ShowList(d);
  $('.slick-sort-indicator')[0].click(); // to sort and show sorted ^
}

// Change return, 3 fields
this.BackC = function(d) {
  // OK, [ERef | EName | ELevel]
  // 1,  [n x [Ref  Entity Name] | Error message |]
  if (R) {
    Alert(d[1],
      '<span class=wng>Unable to Change Entity</span>',
      300,
      [{text:'OK'}],
      function(){ShowList(d);},'important.png');
    return;
  }
  $('#En .ERef').html(ERef=d[0]); // Just for top bar. App windows that need these will reinit onfocus
  $('#En .EName').html(EName=d[1]);
  ELevel=+d[2];
  CloseApp();
}

this.OnClose=function() {
  if (grid) grid.destroy();
}

  function ShowList(d) {
    // Have in Dat[0]: n x [Ref  Entity Name] excluding current entity
    var e,id,d=d[0].split(''), l=d.length;
    data = [];
    for (e=0;e<l;)
      data.push({Ref:d[e++], EName:d[e++]});
    SetData(data);
  }

  function comparer(a,b) {
    var x = a[sortcol], y = b[sortcol];
    return (x == y ? 0 : sortdir ? (x > y ? 1 : -1) : (x > y ? -1 : 1));
  }

  function SetData(dat) {
    var l=dat.length;
    columns[1].width=l<9?408:390; // col full width when no scroll bar, 18px for scrollbar
    grid.setColumns(columns);
    grid.setData(dat);
    grid.render();
  }

}, // end of App()


} // end of EC

