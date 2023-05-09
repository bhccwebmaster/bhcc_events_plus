(function($) {

  /**
   * Auto submit form when facet changed
   * @param  {jQuery} element
   *   Checkbox element.
   */
  function facet_auto_submit(element) {
    var form = element.closest('form');
    element.change(function() {
      form.submit();
    });
  }

  Drupal.behaviors.bhcc_events_plus_facet_auto_submit = {
    attach: function(context, settings) {
      $(once('bhccEventsPlusFacetAutoSubmit', '.facet-filter input[type="checkbox"]', context)).each(function () {
        facet_auto_submit($(this));
      });
    }
  }

}) (jQuery);
