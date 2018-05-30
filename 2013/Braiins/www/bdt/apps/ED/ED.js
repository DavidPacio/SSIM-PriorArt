// ED Entity Delete

ED = {

App:function() {
  Call('I',1);
  var data = [],
  columns = [
  {id:'Ref',   field:'Ref',   name:'Reference',   width:150, sortable:true, toolTip:'Click to Sort'},
  {id:'EName', field:'EName', name:'Entity Name', width:408, sortable:true, toolTip:'Click to Sort'},
  {id:'DYrs',  field:'DYrs',  name:'Data Years',  width:100, cssClass:'norb', headerCssClass:'norb'}
  ],
  sortcol, sortdir, Ref, Fil,
  grid = new Slick.Grid('#EDsg', [], columns);

  // Filter
  $('#EDfil').keyup(function(e) {
    if (e.which == 27) {
      this.value = Fil = '';
      SetData(data);
    }else
      Filter();
  });

  // Sort
  grid.onSort.subscribe(function(e, args) {
    sortdir = args.sortAsc ? 1 : 0;
    sortcol = args.sortCol.field;
    grid.getData().sort(comparer);
    grid.invalidateAllRows();
    grid.render();
  });

  // Delete
  $('#EDsg').on('click','.slick-row',function() {
    if ((Ref=$(this).find('div:first')[0].innerHTML)) {
      var t='Delete '+Ref;
      if (Ref===ERef) {
        Alert(Ref+" can't be deleted as it is your current entity. To delete "+Ref+' change to another entity.',
          t,
          400,
          [{text:'OK'}],
          0,'important.png');
        return;
      }
      Alert("<span class=wng>WARNING:</span> If you click '"+t+"', the Entity will be deleted and cannot be recovered.",
        t,
        400,
        [{text:t,
          click:function(){
            AClose();
            Call('D',1,[Ref]);
          },
         },
         {text:'Cancel'}],
        0,'important.png');
    }
  });

this.BackI = function(d) {
  // Have in Dat: n x [Ref  Entity Name DataYears] excluding current entity
  var e,id,d=d[0].split(''), l=d.length;
  for (e=0;e<l;)
    data.push({Ref:d[e++], EName:d[e++], DYrs:d[e++]});
  SetData(data);
  $('.slick-sort-indicator')[0].click(); // to sort and show sorted ^
 }

this.BackD=function(d) {
  // Dat: <OK | 1> <0 | Alert message>
  if (R) {
    Alert(d[0],
      '<span class=wng>Unable to Delete Entity</span>',
      400,
      [{text:'OK'}],
      0,'important.png');
  }else
    // Can't use row # as data may have been filtered, so search for it
    for (var i=0,l=data.length; i<l; ++i)
      if (data[i].Ref==Ref) {
        data.splice(i, 1);
        break;
      }
  Fil ? Filter() : SetData(data);
}

this.OnClose=function() {
  if (grid) grid.destroy();
}

  function comparer(a,b) {
    var x = a[sortcol], y = b[sortcol];
    return (x == y ? 0 : sortdir ? (x > y ? 1 : -1) : (x > y ? -1 : 1));
  }

  function Filter() {
    var i,l,dat=[];
    Fil=$('#EDfil').val().toLowerCase();
    for (i=0,l=data.length; i<l; ++i)
      if (data[i].Ref.toLowerCase().contains(Fil) || data[i].EName.toLowerCase().contains(Fil))
        dat.push(data[i]);
    if (!dat.length)
      dat.push({Ref:'', EName:'No entities with current filter', DYrs:''});
    SetData(dat);
  }

  function SetData(dat) {
    var l=dat.length;
    columns[2].width=l<9?100:82; // col full width when no scroll bar, 18px for scrollbar
    grid.setColumns(columns);
    grid.setData(dat);
    grid.render();
  }

} // end of App()
} // end of ED
