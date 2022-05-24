(function ($) {
  $(document).ready(function () {
    window.lti_platform_changed = false;
    window.onbeforeunload = function (e) {
      if (window.lti_platform_changed) {
        return '';
      } else {
        return;
      }
    };
    $(document).on('change', 'input[type="text"], input[type="checkbox"]', function () {
      window.lti_platform_changed = true;
    });
    $(document).on('submit', 'form', function () {
      window.lti_platform_changed = false;
    });

  });
})(jQuery);
