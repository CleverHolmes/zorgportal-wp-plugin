(function($)
{
  var fileXhr, xhr, opt = window.ZORGPORTAL_I18N, rows

  $(document).on('change', '#zp-import [name=date_criterea]', function(e)
  {
    $('#zp-import #zp-date-quarter').hide()
    $('#zp-import #zp-date-range').hide()
    $('#zp-import #zp-date-' + this.value).css('display','flex').find('input,select').first().focus()
  })
})( window.jQuery )