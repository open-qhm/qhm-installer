(function($){
  'use strict';

  function completeInstall(res, cb) {
    alert(res.message);
    setTimeout(cb, 1000);
  }

  function setProgress(value) {
    value = value || 0;
    if (value > 100) value = 100;
    $("#progress").css({visibility: "visible"})
      .find(".progress-bar")
      .attr({ariaValuenow: value})
      .css({width: value + '%'})
  }

  $("#install").on("click", function(){
    var progress = 0;
    var interval = setInterval(function(){
      progress += Math.ceil(Math.random() * 10);
      setProgress(progress);
    }, 600);

    $(this).prop("disabled", true).off("click");

    $.post(InstallerData.location, {mode: "install"}, function(res){
      clearInterval(interval);
      if ( ! res.error) {
        setProgress(100);
        $.post(InstallerData.location, {mode: "delete"});
        completeInstall(res, function(){
          location.href = res.redirect
        });
      } else {
        setProgress(0);
        alert(res.message);
      }
    });
  });
})(jQuery);
