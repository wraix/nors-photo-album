$(document).ready(function() {
  $("#phone_no").focus();
  $("#phone_no").keyup(function(e) {
    try {
      document.createEvent("TouchEvent");
      if ($(this).val().length == $(this).attr('maxlength')) { 
        $("#password").focus();
      }
    } catch(e) {
      // squelch
    }
  });
});