function loadProductData(id,url)
{
  $.ajax({
    url: url,
    data: '&getproduct=' + id,
    type: "POST",
    dataType: "json",
    success: function(data) {
      //alert(data.toSource());
      $('#allegroPrice').val(data.price);
      
      if (data.old_price) $('td.old_price').html(data.old_price);
      
      //tinyMCE.activeEditor.setContent(data.description);
      var ed = tinyMCE.get('allegroDesc');

      // Do you ajax call here, window.setTimeout fakes ajax call
      ed.setProgressState(1); // Show progress
      window.setTimeout(function() {
              ed.setProgressState(0); // Hide progress
              ed.setContent(data.desc);
      }, 3000);

    }
  });
}

function loadProductStateSelect(id)
{
    $.ajax({
    url: '/modules/jballegro/ajax.php',
    data: 'getstateselect=' + id,
    type: "POST",
    success: function(data) 
    {
      if (data != '')
      {
        $('#state-row td').html(data);
        $('#state-row').show();
      }
      else {
          $('#state-row td').html('');
           $('#state-row').hide();
      }
    }
  });
}
