(function($) {
  $(document).ready(function() {
    const popup = $('#pfWelcomePopup');
    if (!popup.length) return;

    $('#pfPopupClose, .pf-popup-overlay').on('click', function(e) {
      if ($(e.target).is('.pf-popup-overlay') || $(e.target).is('#pfPopupClose')) {
        popup.fadeOut(300);
        if (typeof pfPopupData !== 'undefined') {
          $.post(pfPopupData.ajaxUrl, {
            action: 'pf_dismiss_popup',
            nonce: pfPopupData.nonce,
          });
        }
      }
    });

    $('.pf-popup-box').on('click', function(e) { e.stopPropagation(); });
  });
})(jQuery);
