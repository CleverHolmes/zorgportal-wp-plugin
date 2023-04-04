(function($)
{
  var inline_delete

  $('#euproj-items .euproj-inline-delete').click(function(e)
  {
    $('#euproj-items input[type=checkbox]').prop('checked', false)
    $('input[type=checkbox]', $(this).closest('tr')).prop('checked', 'checked')
    inline_delete = true
    $('#bulk-action-selector-bottom').val('delete')
    $(this).closest('form').submit()
  })

  $('#euproj-items').attr('action', $('#euproj-items').data('action')).submit(function(e)
  {
    if ( ! confirm(this.dataset.confirm) ) {
      inline_delete && (
        $('#euproj-items input[type=checkbox]').prop('checked', false),
        $('#bulk-action-selector-bottom').val('-1'),
        (inline_delete = false)
      )
      return e.preventDefault()
    }

    inline_delete = false
  })

  $('#euproj-items .euproj-view-data').click(function(e)
  {
    e.preventDefault()

    var table = $(this).closest('#euproj-items')
      , tr = $(this).closest('tr')
      , pre = $(this).closest('td').find('pre')
      , tr2 = $('<tr class="euproj-data-preview" style="background:#fdf6e1"><td colspan="'+tr[0].childElementCount+'"><pre style="color:#c69d00;white-space:pre-wrap"></pre></td></tr>')

    $('.euproj-data-preview', table).remove()

    if ( $(this).hasClass('euproj-hide') )
      return $('.euproj-hide', table).removeClass('euproj-hide')

    $('.euproj-hide', table).removeClass('euproj-hide')
    $(this).addClass('euproj-hide')

    tr2.insertAfter(tr)

    try {
      var data = JSON.parse(pre.text())
      $('pre', tr2).text( JSON.stringify(data, null, 2) || 'N/A' )
    } catch (err) {
      $('pre', tr2).text( pre.text() || 'N/A' )
    }
  })
})( window.jQuery )