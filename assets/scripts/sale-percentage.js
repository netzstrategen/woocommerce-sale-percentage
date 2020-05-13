/* global jQuery, sale_percentage_settings */

(function pageLoad($) {
  // Select the sale label of the current displayed main product. We want to
  // avoid affecting the sale label of other products on the page. I.e. related
  // products, accessories, etc.
  const $currentProductId = $('form.cart [name="add-to-cart"]').val();
  const $singleProductSaleLabel = $('#sale-label-' + $currentProductId);

  /**
   * React to changes in the selected product variation.
   */
  $('.single_variation_wrap')
    .on('show_variation', (event, variation) => {
      // Updates the displayed sale percentage when a variation is selected.
      const percentage = calculateSalePercentage(
        variation.display_regular_price,
        variation.display_price,
      );
      updateSalePercentage(percentage, '-%d%%');
    })
    .on('hide_variation', () => {
      // Displays the default sale percentage when no variation is selected.
      updateSalePercentage(
        $singleProductSaleLabel.data('sale-percentage'),
        sale_percentage_settings.salePercentageFormat,
      );
    });

  /**
   * Calculates the sale percentage applied to a product.
   *
   * @param {Integer} regularPrice
   *   Product regular price.
   * @param {Integer} salePrice
   *   Product discounted price.
   *
   * @return {Integer}
   *   Product sale percentage.
   */
  function calculateSalePercentage(regularPrice, salePrice) {
    return Math.floor((regularPrice - salePrice) / regularPrice * 100);
  }

  /**
   * Updates sale percentage value.
   *
   * @param {Integer} percentage
   *   The product/variation sale percentage.
   * @param {String} format
   *   Format to display the sale percentage.
   */
  function updateSalePercentage(percentage, format) {
    if (percentage >= sale_percentage_settings.saleMinAmount) {
      $singleProductSaleLabel.text(format.replace('%d%%', `${percentage}%`));
      $singleProductSaleLabel.show();
    }
    else {
      $singleProductSaleLabel.hide();
    }
  }
}(jQuery));
