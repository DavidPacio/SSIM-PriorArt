// CUD Current entity Upload Data
//     ReInit app

CUD = {

App:function() {
  InitApp(); // No 'I' call so need to call InitApp() directly
  $('#CUDbFile').button().click(function() {$('#CUDiFile').click();});
  $('#CUDiFile').change(Upload);

  function Upload() {
    var fileO = this.files[0], reader;
    // size, type, name
    if (fileO.type && fileO.type!='text/plain')
      return NoGo(fileO.name, 'is a not a plain text file which an export file to be uploaded should be');
    reader = new FileReader();
    reader.onload = function(event) {
      var dat = event.target.result;
      if (!dat.contains(ERef) && !dat.contains(EName))
        return NoGo(fileO.name, "does not contain either the Entity Reference '"+ERef+"' or the Entity Name '"+EName+"' so is not a valid export/upload file for this entity");
      $('#CUDmsg').html('<span class=b>'+fileO.name+' is being uploaded and imported.</span>');
      //alert(dat);
      console.log(dat);

      // Ajax upload.....

    };
    reader.onerror = function(event) {
        alert("File could not be read! Code " + event.target.error.code);
    };
    reader.readAsText(fileO);

  }

  function NoGo(name, msg) {
    $('#CUDmsg').html('The file you have chosen ('+name+') '+msg+'. <span class=wng>Please choose again.</span>');
    return false;
  }

}, // end of App()

ReInit:function() {
  InitApp();
  $('#CUDmsg').empty();
}

} // end of CUD
