(function($)
{
  $(document).on('click', '#zp-files .dashicons-trash', function(e)
  {
    e.preventDefault()
    $(this).closest('p').remove()
  })

  $(document).on('click', '#zp-files .upload-attachment', function(e)
  {
    e.preventDefault()

    var custom_uploader = wp.media.frames.file_frame = wp.media({
      title: ZP_SETTINGS.uploader_title,
      button: {
        text: ZP_SETTINGS.uploader_button
      },
      multiple: false
    }), button = $(this)

    custom_uploader.on('select', function()
    {
      var attachment = custom_uploader.state().get('selection').first().toJSON()

      if ( ! attachment )
        return alert(ZP_SETTINGS.invalid_file)

      var p = $('<p></p>')
      p.append($('<span></span>').text(attachment.filename))
      p.append($('<span class="dashicons dashicons-trash"></span>').css({
        color: '#f44336',
        cursor: 'pointer',
        marginLeft: 4,
      }).on('click', function(e)
      {
        p.remove()
      }))
      p.append($('<input type=hidden name="attachments[]" />').val(attachment.id))
      button.closest('#zp-files').prepend(p)
    })

    custom_uploader.open()
  })
})( window.jQuery )
