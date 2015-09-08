(function($){
  'use strict';

  function completeInstall(res, cb) {
    alert(res.message);
    setTimeout(cb, 1000);
  }

  $("#install").on("click", function(){
    $.post(InstallerData.location, {mode: "install"}, function(res){
      if ( ! res.error) {
        $.post(InstallerData.location, {mode: "delete"});
        completeInstall(res, function(){
          //location.href = res.redirect
        });
      } else {
        alert(res.message);
      }
    });
  });
})(jQuery);
