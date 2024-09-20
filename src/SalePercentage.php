<?php

namespace Netzstrategen\SalePercentage;

/**
 * Main plugin functionality.
 */
class SalePercentage {

  /**
   * Default minimum discount percentage to display product sale label.
   *
   * @var int
   */
  const SALE_BUBBLE_MIN_AMOUNT = 10;

  /**
   * Updates sale percentage when regular price or sale price are updated.
   *
   * The plugin woocommerce-advanced-bulk-edit does not invoke the hook
   * 'save_post' when updating the regular price or sale price, so we need to
   * manually calculate the sale percentage whenever post meta fields were
   * updated.
   *
   * @param int $meta_id
   *   ID of updated metadata entry.
   * @param int $object_id
   *   ID of the object metadata is for.
   * @param string $meta_key
   *   Metadata key.
   * @param mixed $meta_value
   *   Metadata value. Serialized if non-scalar.
   *
   * @implements added_post_meta
   * @implements updated_post_meta
   */
  public static function updateSalePercentage($meta_id, $object_id, $meta_key, $meta_value) {
    if (!static::priceFieldsUpdated($meta_key) || !$product = wc_get_product($object_id)) {
      return;
    }

    if ($product->get_type() === 'variation') {
      $parent_product_id = $product->get_parent_id();
      static::saveSalePercentage($parent_product_id, get_post($parent_product_id));
    }
    elseif (in_array($product->get_type(), ['simple', 'variable', 'bundle'])) {
      static::saveSalePercentage($object_id, get_post($object_id));
    }
  }

  /**
   * Updates sale percentage when regular price or sale price are removed.
   *
   * @param int $meta_id
   *   ID of updated metadata entry.
   * @param int $object_id
   *   ID of the object metadata is for.
   * @param string $meta_key
   *   Metadata key.
   * @param mixed $meta_value
   *   Metadata value. Serialized if non-scalar.
   *
   * @implements deleted_post_meta
   */
  public static function deleteSalePercentage($meta_id, $object_id, $meta_key, $meta_value) {
    if (!static::priceFieldsUpdated($meta_key) || !$product = wc_get_product($object_id)) {
      return;
    }

    if ($product->get_type() === 'variation') {
      $parent_product_id = $product->get_parent_id();
      static::saveSalePercentage($parent_product_id, get_post($parent_product_id));
    }
    else {
      update_post_meta($object_id, '_sale_percentage', 0);
      update_post_meta($object_id, '_sale_percentage_highest', 0);
    }
  }

  /**
   * Calculates and updates product sale percentage on post save.
   *
   * @param int $post_id
   *   Product post unique identifier.
   * @param WP_Post|array|null $post
   *   Product post.
   */
  public static function saveSalePercentage($post_id, $post) {
    global $wpdb; // phpcs:ignore

    if ($post->post_type === 'product') {
      $product_has_variation = $wpdb->get_var("SELECT ID from wp_posts WHERE post_type = 'product_variation' AND post_parent = $post_id LIMIT 0,1");
      if ($product_has_variation) {
        $where = "WHERE p.post_type = 'product_variation' AND p.post_parent = $post_id AND p.post_status = 'publish'";
      }
      else {
        $where = "WHERE p.post_type = 'product' AND p.ID = $post_id";
      }

      $sale_percentage = $wpdb->get_col("SELECT FLOOR((regular_price.meta_value - sale_price.meta_value) / regular_price.meta_value * 100) AS sale_percentage
        FROM wp_posts p
        LEFT JOIN wp_postmeta regular_price ON regular_price.post_id = p.ID AND regular_price.meta_key = '_regular_price'
        LEFT JOIN wp_postmeta sale_price ON sale_price.post_id = p.ID AND sale_price.meta_key = '_sale_price'
        $where
        AND sale_price.meta_value IS NOT NULL
        AND sale_price.meta_value <> ''
        ORDER BY sale_percentage ASC
      ");

      if ($sale_percentage) {
        update_post_meta($post_id, '_sale_percentage', (int) $sale_percentage[0]);
        update_post_meta($post_id, '_sale_percentage_highest', (int) end($sale_percentage));
      }
      else {
        update_post_meta($post_id, '_sale_percentage', 0);
        update_post_meta($post_id, '_sale_percentage_highest', 0);
      }
    }
  }

  /**
   * Displays sale percentage as a product flash bubble.
   *
   * @param mixed $output
   *   The WooCommerce sale flash HTML output.
   * @param \WP_Post|null $post
   *   The WordPress Post.
   * @param \WC_Product|null $product
   *   The product object.
   *
   * @implements woocommerce_sale_flash
   */
  public static function displaySalePercentage($output, ?\WP_Post $post, ?\WC_Product $product) {
    if (!$product) {
      return $output;
    }
    $salePercentage = static::getProductSalePercentage($product);
    if (static::checkDisplaySalePercentage($salePercentage, $product) && $salePercentage >= static::getMinimumSalePercentage()) {
      $classes = 'onsale';
      if ($product->get_type() === 'variable') {
        $salePercentageFormat = static::getSalePercentageFormat();
        $classes .= get_option('_sale_percentage_displayed_value') === 'highest' ? ' upto' : '';
      }
      else {
        $salePercentageFormat = '-%d%%';
      }

      $output = sprintf(
        '<span id="sale-label-%d" class="%s" data-sale-percentage="%d">%s</span>',
        $product->get_id(),
        $classes,
        abs($salePercentage),
        sprintf($salePercentageFormat, $salePercentage)
      );
    }
    else {
      $output = '';
    }

    return apply_filters('sale_percentage_output', $output, $salePercentage, $product);
  }

  /**
   * Adds custom order by option.
   *
   * @param array $orderBy
   *   The WooCommerce order by options.
   *
   * @return array
   *   The modified WooCommerce order by options.
   *
   * @implements woocommerce_catalog_orderby
   * @implements woocommerce_default_catalog_orderby_options
   */
  public static function addOrderBySalePercentageOption($orderBy) {
    $orderBy['sale_percentage'] = __('Sort by discount', Plugin::L10N);

    return $orderBy;
  }

  /**
   * Orders products by sale percentage.
   *
   * @param array $args
   *   The order by arguments.
   *
   * @return array
   *   The modified order by arguments.
   *
   * @implements woocommerce_get_catalog_ordering_args
   */
  public static function orderProductsBySalePercentage($args) {
    $orderby_value = isset($_GET['orderby']) ? wc_clean($_GET['orderby']) : apply_filters('woocommerce_default_catalog_orderby', get_option('woocommerce_default_catalog_orderby'));
    if ('sale_percentage' === $orderby_value) {
      $args['orderby'] = 'meta_value_num';
      $args['order'] = 'DESC';
      $args['meta_key'] = get_option('_sale_percentage_displayed_value') === 'highest' ? '_sale_percentage_highest' : '_sale_percentage';
    }
    return $args;
  }

  /**
   * Checks if a product price related meta field is updated.
   *
   * @param string $meta_key
   *   Meta key of the update field.
   *
   * @return bool
   *   TRUE if updated field is one of the product prices.
   */
  public static function priceFieldsUpdated($meta_key) {
    return in_array($meta_key, ['price', '_regular_price', '_sale_price']);
  }

  /**
   * Retrieves the sale percentage of a given product.
   *
   * @param WP_Product $product
   *   The product object.
   *
   * @return int
   *   The product sale percentage.
   */
  public static function getProductSalePercentage($product) {
    if ($product->get_type() === 'variation') {
      $product_id = $product->get_parent_id();
    }
    else {
      $product_id = $product->get_id();
    }

    if (get_option('_sale_percentage_displayed_value') === 'highest') {
      $meta_key = '_sale_percentage_highest';
    }
    else {
      $meta_key = '_sale_percentage';
    }

    return (int) get_post_meta($product_id, $meta_key, TRUE);
  }

  /**
   * Checks if the sale percentage flash bubble should be displayed.
   *
   * @param int $salePercentage
   *   The product sale percentage.
   * @param \WC_Product $product
   *   The product object.
   *
   * @return bool
   *   TRUE if the sale percentage flash bubble should be displayed.
   */
  public static function checkDisplaySalePercentage($salePercentage, \WC_Product $product) {
    $product_category_ids = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'ids']);
    $eligible_category_ids = get_option('_sale_percentage_eligible_product_categories') ?: [];
    $eligible_categories = array_intersect($eligible_category_ids, $product_category_ids);
    $current_category_slug = get_query_var('product_cat') ?: get_query_var('pa_kategorie') ?: get_query_var('pa_warensortiment') ?: get_query_var('pa_produktart-stuhl');
    $current_category = !empty($current_category_slug) ? get_term_by('slug', $current_category_slug, 'product_cat') : '';
    $current_category_id = !empty($current_category) ? $current_category->term_id : '';

    return (is_single() && !empty($eligible_categories)) || (!is_single() && !empty($current_category_id) && in_array($current_category_id, $eligible_categories));
  }

  /**
   * Retrieves the minimum product sale percentage to display the sale bubble.
   *
   * @return int
   *   Minimum product sale percentage.
   */
  public static function getMinimumSalePercentage() {
    return (int) get_option('_minimum_sale_percentage_to_display_label', static::SALE_BUBBLE_MIN_AMOUNT);
  }

  /**
   * Retrieves the format to display the sale percentage value.
   *
   * @return string
   *   The sale percentage format.
   */
  public static function getSalePercentageFormat() {
    return get_option('_sale_percentage_displayed_value') === 'highest' ? __('up to -%d%%', Plugin::L10N) : '-%d%%';
  }

}
