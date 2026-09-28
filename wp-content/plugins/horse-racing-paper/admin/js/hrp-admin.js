(function ($) {
  'use strict';

  function bindMedia($row) {
    var frame;
    $row.on('click', '.hrp-media-pick', function (e) {
      e.preventDefault();
      var $wrap = $(this).closest('.hrp-field-row');
      if (frame) {
        frame.open();
        return;
      }
      frame = wp.media({
        title: $(this).data('title') || '選擇圖片',
        button: { text: '使用此圖' },
        multiple: false,
      });
      frame.on('select', function () {
        var att = frame.state().get('selection').first().toJSON();
        $wrap.find('.hrp-media-id').val(att.id);
        $wrap.find('.hrp-media-preview').attr('src', att.url).show();
      });
      frame.open();
    });

    $row.on('click', '.hrp-media-clear', function (e) {
      e.preventDefault();
      var $wrap = $(this).closest('.hrp-field-row');
      $wrap.find('.hrp-media-id').val('0');
      $wrap.find('.hrp-media-preview').attr('src', '').hide();
    });
  }

  $(function () {
    $('.hrp-admin .hrp-field-row').each(function () {
      if ($(this).find('.hrp-media-id').length) {
        bindMedia($(this));
      }
    });
  });
})(jQuery);
