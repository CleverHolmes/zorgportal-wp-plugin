(function($)
{
  var fileXhr, xhr, opt = ZORGPORTAL_I18N, rows

  $(document).on('change', '#zp-import [name=date_criterea]', function(e)
  {
    $('#zp-import #zp-date-year').hide()
    $('#zp-import #zp-date-range').hide()
    $('#zp-import #zp-date-' + this.value).show().find('input,select').first().focus()
  })

  $(document).on('change', '#zp-import [name=file]', function(e)
  {
    var file = this.files[0]
      , form = $(this).closest('form')

    $('.zp-ajax-loader', form).hide()
    $('#zp-notices', form).html('')

    if ( ! file || ! file.size ) {
      fileXhr && fileXhr.abort && fileXhr.abort()
      rows = null
      $('#zp-fields-map select', form).html('')
      $('#zp-fields-map', form).hide()
      $('[type=submit]', form).prop('disabled', 'disabled')
      return
    }

    $('.zp-ajax-loader', form).show()

    var data = new FormData
    data.append('file', file)

    fileXhr = $.ajax({
      url: opt.ajaxUrl.replace(/%s/g, 'import/read-file'),
      type: 'POST',
      data: data,
      processData: false,
      contentType: false,
      success: function(res)
      {
        $('.zp-ajax-loader', form).hide()
        rows = null
    
        if ( res.rows && res.rows.length ) {
          rows = res.rows
          $('[type=submit]', form).prop('disabled', false)

          $('#zp-fields-map', form).show().find('select').each(function(i,select)
          {
            $(select).html('')

            const ranges = opt.pre_select_ranges[String(select.name).replace(/(fields_map)|\[|\]/g, '')]
            let selection_started

            for ( var key in res.rows[0] ) {
              var option = $('<option />')
              option.val(res.rows[0][key] || '__col::'.concat(key))
              option.text(res.rows[0][key] || opt.colPlaceholder.replace(/%s/g, key))
              $(select).append(option)

              var index = $(select).closest('tr').index()

              if ( ranges && ranges.length ) {
                if ( selection_started && -1 != ranges.indexOf(option.val()) ) {
                  selection_started = false
                  continue
                }

                selection_started && option.attr('selected', 'selected')

                if ( ! selection_started )
                  selection_started = -1 != ranges.indexOf(option.val())
              } else if ( opt.pre_select_headers && opt.pre_select_headers[index] ) {
                if ( opt.pre_select_headers[index] == option.val() )
                  option.attr('selected', 'selected')
              } else if ( $(option).index() == $(select).closest('tr').index() )
                option.attr('selected', 'selected')
            }
          })
        } else {
          alert(res.error || opt.error)
        }
      },
      error: function()
      {
        $('.zp-ajax-loader', form).hide()
    
        if ( 'abort' !== fileXhr.statusText )
          alert(opt.error)
      }
    })
  })

  $(document).on('submit', '#zp-import', function(e)
  {
    e.preventDefault()

    var form = $(this), notice = function(message, type)
    {
      var elem = $('<div class="notice"><p></p></div>').addClass(type||'error')
      elem.find('p').html(message)
      elem.appendTo($('#zp-notices', form))
    }, submit = $('[type=submit]', form)

    xhr && xhr.abort && xhr.abort()

    $('#zp-notices', form).html('')

    if ( ! rows || 0 == rows.length )
      return notice(opt.err_no_rows, 'error')

    var data = new FormData(form[0])
    data.delete('file')
    data.delete('_wpnonce')
    data.append('rows_json', JSON.stringify(rows))

    submit.val(submit.data('loading')).prop('disabled', 'disabled')

    xhr = $.ajax({
      url: opt.ajaxUrl.replace(/%s/g, 'import'),
      type: 'POST',
      data: data,
      processData: false,
      contentType: false,
      success: function(res)
      {
        submit.val(submit.data('value')).prop('disabled', false)

        if ( res && res.success ) {
          res.inserted && notice(opt.success_message_inserted.replace(/%d/g, res.inserted), 'updated')
          res.overwritten && notice(opt.success_message_overwritten.replace(/%d/g, res.overwritten), 'notice-warning')
          res.errored && notice(opt.success_message_errored.replace(/%d/g, res.errored), 'error')

          form[0].reset()
          rows = null
          $('#zp-fields-map select', form).html('')
          $('#zp-fields-map', form).hide()
        } else {
          [].concat(res.errors && res.errors.length ? res.errors : opt.error).map(function(message)
          {
            notice(message, 'error')
          })
        }
      },
      error: function()
      {
        submit.val(submit.data('value')).prop('disabled', false)
    
        if ( 'abort' !== xhr.statusText )
          alert(opt.error)
      }
    })
  })
})( window.jQuery )