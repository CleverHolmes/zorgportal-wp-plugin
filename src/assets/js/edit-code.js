(function($)
{
  $(document).on('click', '#zp-add-package', function(e)
  {
    e.preventDefault()

    var cont = $('#zp-packages')
      , wrap = cont.children().last().clone().appendTo(cont)

    $('input', wrap).val('').attr('name', function()
    {
      return this.name.replace(/^packages\[\d+\]/, 'packages['.concat(+new Date, ']'))
    })
  })

  $(document).on('click', '#zp-packages .zp-delete-inline', function(e)
  {
    e.preventDefault()

    var cont = $(this).closest('#zp-packages')

    if ( cont.children().length > 1 )
      return $(this).parent().remove()

    $('input', $(this).parent()).val('')
  })
})( window.jQuery )