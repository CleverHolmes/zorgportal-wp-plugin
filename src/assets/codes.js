(function($)
{
  var inline_delete

  $('#zportal-items .zportal-inline-delete').click(function(e)
  {
    $('#zportal-items input[type=checkbox]').prop('checked', false)
    $('input[type=checkbox]', $(this).closest('tr')).prop('checked', 'checked')
    inline_delete = true
    $('#bulk-action-selector-bottom').val('delete')
    $(this).closest('form').submit()
  })

  $('#zportal-items').attr('action', $('#zportal-items').data('action')).submit(function(e)
  {
    if ( ! confirm(this.dataset.confirm) ) {
      inline_delete && (
        $('#zportal-items input[type=checkbox]').prop('checked', false),
        $('#bulk-action-selector-bottom').val('-1'),
        (inline_delete = false)
      )
      return e.preventDefault()
    }

    inline_delete = false
  })
})( window.jQuery )