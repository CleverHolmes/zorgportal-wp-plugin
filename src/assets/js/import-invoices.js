(function($)
{
  var fileXhr, xhr, opt = ZORGPORTAL_I18N, rows, download_url, last_submitted_data

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
      , submit = $('[type=submit]', form)

    $('.zp-ajax-loader', form).hide()
    $('#zp-notices', form).html('')
    $('#submit-button-early', form).hide()

    download_url = null
    submit.val(submit.data('value')).prop('disabled', 'disabled')

    if ( ! file || ! file.size ) {
      fileXhr && fileXhr.abort && fileXhr.abort()
      rows = null
      $('#zp-fields-map select', form).html('')
      $('#zp-fields-map', form).hide()
      submit.prop('disabled', 'disabled')
      return
    }

    $('.zp-ajax-loader', form).show()

    var data = new FormData
    data.append('file', file)

    fileXhr = $.ajax({
      url: opt.ajaxUrl.replace(/%s/g, 'invoices/read-file'),
      type: 'POST',
      data: data,
      processData: false,
      contentType: false,
      success: function(res)
      {
        $('.zp-ajax-loader', form).hide()
        rows = null
        if (res.errors === undefined && res.rows && res.rows.length ) {
          rows = res.rows
          submit.prop('disabled', false)

          $('#zp-fields-map', form).show().find('select').each(function(i,select)
          {
            $(select).html('')

            var option = $('<option />')
            option.val('')
            option.text('')
            $(select).append(option)

            for ( var key in res.rows[0] ) {
              var option = $('<option />')
              option.val(res.rows[0][key] || '__col::'.concat(key))
              option.text(res.rows[0][key] || opt.colPlaceholder.replace(/%s/g, key))
              $(select).append(option)

              var index = $(select).closest('tr').index()
              
              if ( opt.pre_select_headers && opt.pre_select_headers[index] ) {
                if ( opt.pre_select_headers[index] == option.val() )
                  option.attr('selected', 'selected')
              } else if ( $(option).index() == $(select).closest('tr').index() )
                option.attr('selected', 'selected')
            }
          })
        } else {
          if(res.errors) {
            alert(opt[res.errors]);
          } else if(res.non_exist) {
            alert(res.non_exist);
          } else alert(res.error || opt.error)
        }
      },
      error: function()
      {       
        //  console.log(opt);

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
      elem.find('p').text(message)
      elem.appendTo($('#zp-notices', form))
    }, submit = $('[type=submit]', form)

    if ( download_url ) {
      last_submitted_data.append('refresh_download', '1')
      submit.val(submit.data('loading')).prop('disabled', 'disabled')
      $.ajax({
        url: opt.ajaxUrl.replace(/%s/g, 'invoices/import'),
        type: 'POST',
        data: last_submitted_data,
        processData: false,
        contentType: false,
        success: function(res)
        {

          last_submitted_data.delete('refresh_download')

          if ( res && res.download_url ) {
            download_url = null
            submit.val(submit.data('value')).prop('disabled', 'disabled')
            location.assign(res.download_url)
          } else {
            submit.val(submit.data('download')).prop('disabled', false)
            alert(opt.error)
          }
        },
        error: function()
        {

          last_submitted_data.delete('refresh_download')
          submit.val(submit.data('download')).prop('disabled', false)
        }
      })

      // location.assign(download_url)
      return
    }

    xhr && xhr.abort && xhr.abort()

    $('#zp-notices', form).html('')

    if ( ! rows || 0 == rows.length )
      return notice(opt.err_no_rows, 'error')

    var data = new FormData(form[0])
    data.delete('file')
    data.delete('_wpnonce')
    data.append('rows_json', JSON.stringify(rows))

    submit.val(submit.data('loading')).prop('disabled', 'disabled')

    last_submitted_data = data

    xhr = $.ajax({
      url: opt.ajaxUrl.replace(/%s/g, 'invoices/import'),
      type: 'POST',
      data: data,
      processData: false,
      contentType: false,
      success: function(res)
      {
        submit.val(submit.data('download')).prop('disabled', false)

        if ( res && res.success && res.download_url ) {
          form[0].reset()
          rows = null
          submit.prop('disabled', false)
          $('#zp-fields-map select', form).html('')
          $('#zp-fields-map', form).hide()
          download_url = res.download_url
        } else {
          notice([].concat(res.errors && res.errors.length ? res.errors : opt.error).join('<br/>'), 'error')
        }

        if ( res && res.total_ok )
          $('#zp-notices', form).append(
            $('<div class="notice updated"><p>' + opt.status_ok_header.replace(/%d/g, String(res.total_ok)) + '</p></div>'))

        if ( res && res.codes_404 && res.codes_404.length > 0 )
          displayNotices(res.codes_404, form, $('<p style="cursor:pointer;display:flex;align-items:center;justify-content:space-between"><strong>' + opt.codes_404_pre.replace(/%d/g, String(res.codes_404.length)) + '</strong><span class="dashicons dashicons-arrow-down-alt2"></span></p>'))

        if ( res && res.policy_404 && res.policy_404.length > 0 )
          displayNotices(res.policy_404, form, $('<p style="cursor:pointer;display:flex;align-items:center;justify-content:space-between"><strong>' + opt.policy_404_pre.replace(/%d/g, String(res.policy_404.length)) + '</strong><span class="dashicons dashicons-arrow-down-alt2"></span></p>'), 'policy_404')

        $('#submit-button-early', form).show()
      },
      error: function(err)
      {

        $('#submit-button-early', form).hide()
        submit.val(submit.data('value')).prop('disabled', false)
    
        if ( 'abort' !== xhr.statusText )
          alert( xhr.statusText)
      }
    })
  })

  var displayNotices = function(src, form, pre, id)
  {
    var table = $('<table class="widefat striped" style="display:none"><thead></thead><tbody></tbody></table>')

    src.forEach(function(row,i)
    {
      if ( ! i ) {
        var tr = $('<tr/>')

        for ( var prop in row ) {
          if ( 'policy_404' == id && 'code_id' == prop )
            continue

          tr.append('<th>' + prop + '</th>')
        }

        if ( 'policy_404' == id )
          tr.append('<th></th>')

        $('thead', table).append(tr)
      }

      var tr2 = $('<tr/>')

      for ( var prop in row ) {
        var td = $('<td>' + row[prop] + '</td>')

        if ( 'policy_404' == id && 'code_id' == prop )
          continue

        if ( 'policy_404' == id && 'reimburse amount' == String(prop).toLowerCase() ) {
          td = $('<td><input type="text" /></td>')
          td.addClass('reimburse-amount')
          $('input', td).val(row[prop]||'')
        }

        if ( 'policy_404' == id && 'applied date' == String(prop).toLowerCase() ) {
          td = $('<td></td>')
          var rowId = [1,2,3].map(Math.random).join('').replace(/\./g, '')
          td.html(opt.applied_date_html.replace(new RegExp('__row_id__', 'g'), rowId))
          td.addClass('applied-date')
          $('input', td).val(row[prop]||'')
          $('[name="applied_date[' + rowId + '][year]"]', td).val(String(row[opt.invoices_date_column_name]).trim().split('/').pop())
        }

        td.attr('data-col', prop)
        tr2.append(td)

        $('.zp-criteria-year', td).val('year')
        $('.zp-criteria-range', td).val('range')
      }

      if ( 'policy_404' == id ) {
        tr2.attr('data-code_id', row.code_id)

        var td = $('<td><button type="button" class="button">' + opt.policy_save + '</button></td>')
        tr2.append(td)
        
        $('button', td).on('click', function(e)
        {
          e.preventDefault()

          var cont = $(this).closest('tr')
            , input = cont.find('.reimburse-amount input')
            , amount = Number(input.val())

          if ( '' == $.trim(input.val()) || isNaN(amount) )
            return input.focus()

          var payload = {
            amount: amount,
            insurer: $('td', cont).filter(function(){
              return String($(this).attr('data-col')).toLowerCase()
                == String(opt.invoices_insurance_company_column_name).toLowerCase()
            }).text().trim(),
            policy: $('td', cont).filter(function(){
              return String($(this).attr('data-col')).toLowerCase()
                == String(opt.invoices_insurance_policy_column_name).toLowerCase()
            }).text().trim(),
            total_amount: $('td', cont).filter(function(){
              return String($(this).attr('data-col')).toLowerCase()
                == String(opt.invoices_total_amount_column_name).toLowerCase()
            }).text().trim(),
            description: $('td', cont).filter(function(){
              return String($(this).attr('data-col')).toLowerCase()
                == String(opt.invoices_omschrijving_column_name).toLowerCase()
            }).text().trim(),
          }

          if ( ! row.code_id ) {
            var rowId = $('[data-row-id]', cont).data('rowId')
            payload.date_criterea = $('[name="applied_date[' + rowId + '][criteria]"]', cont).filter(':checked').val()
            payload.date_year = $('[name="applied_date[' + rowId + '][year]"]', cont).val()
            payload.date_from = $('[name="applied_date[' + rowId + '][from]"]', cont).val()
            payload.date_to = $('[name="applied_date[' + rowId + '][to]"]', cont).val()
            payload.dbc_code = $('td', cont).filter(function(){
              return String($(this).attr('data-col')).toLowerCase() == String(opt.invoices_code_column_name).toLowerCase()
            }).text().trim()

            if ( -1 == ['year','range'].indexOf(payload.date_criterea) )
              return alert(opt.error_date_invalid)

            if ( 'year' == payload.date_criterea && ! payload.date_year )
              return alert(opt.error_date_invalid)

            if ( 'range' == payload.date_criterea && ( ! payload.date_from || ! payload.date_to ) )
              return alert(opt.error_date_invalid)
          }

          $.ajax({
            url: opt.ajaxUrl.replace(/%s/g, row.code_id ? 'codes'.concat('/', row.code_id) : 'codes'),
            type: row.code_id ? 'PATCH' : 'PUT',
            data: JSON.stringify(payload),
            contentType: 'application/json; charset=utf-8',
            success: function(recId)
            {
              var new_code

              if ( recId && ! row.code_id ) { // new code saved
                cont[0].dataset.code_id = recId
                row.code_id = recId
                new_code = true
              }

              $('tbody tr', table).each(function(i,tr)
              {
                var insurer = $('td', tr).filter(function(){
                  return String($(this).attr('data-col')).toLowerCase()
                    == String(opt.invoices_insurance_company_column_name).toLowerCase()
                }).text().trim()
                  , policy = $('td', tr).filter(function(){
                  return String($(this).attr('data-col')).toLowerCase()
                    == String(opt.invoices_insurance_policy_column_name).toLowerCase()
                }).text().trim()
                  , code = $('td', tr).filter(function(){
                  return String($(this).attr('data-col')).toLowerCase()
                    == String(opt.invoices_code_column_name).toLowerCase()
                }).text().trim()

                var code_match = new_code
                  ? code == payload.dbc_code
                  : tr.dataset.code_id == row.code_id

                if ( code_match && insurer == payload.insurer && policy == payload.policy ) {
                  $('td', tr).first().find('img').remove()
                  $('td', tr).first().append('<img src="' + opt.baseUrl + '/src/assets/check.png" alt="check" style="width:25px;margin-top:5px" />')
                }
              })
            },
            error: function()
            {
              input.focus()
            }
          })
        })
      }

      $('tbody', table).append(tr2)
    })

    var notices = $('<div class="notice notice-warning"></div>')
    pre && notices.append(pre)
    $('#zp-notices', form).append(notices.append(table))

    $(pre).on('click', function(e)
    {
      e.preventDefault()

      var icon = $('.dashicons', this)

      if ( icon.hasClass('dashicons-arrow-down-alt2') ) {
        icon.removeClass('dashicons-arrow-down-alt2')
        icon.addClass('dashicons-arrow-up-alt2')
        table.show()
      } else {
        icon.removeClass('dashicons-arrow-up-alt2')
        icon.addClass('dashicons-arrow-down-alt2')
        table.hide()
      }
    })
  }

})( window.jQuery )