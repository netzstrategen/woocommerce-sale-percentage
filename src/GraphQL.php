<?php

namespace Netzstrategen\SalePercentage;

/**
 * GraphQL related functionalities.
 */
class GraphQL {

  /**
   * Initializes GraphQL related functionalities.
   */
  public static function init() {
    add_filter('woographql_product_orderby_enum_values', [__CLASS__, 'woographql_product_orderby_enum_values']);
    add_filter('woographql_product_connection_orderby_numeric_meta_keys', [__CLASS__, 'woographql_product_connection_orderby_numeric_meta_keys']);
    add_action('graphql_register_types', [__CLASS__, 'graphql_register_types']);
  }

  /**
   * Adds sale percentage sort enum to GraphQL.
   *
   * @uses woographql_product_orderby_enum_values
   */
  public static function woographql_product_orderby_enum_values($values):array {
    $values['SALE_PERCENTAGE'] = [
      'value' => self::getSalePercentageKey(),
    ];
    return $values;
  }

  /**
   * Adds sale percentage sort field to GraphQL.
   *
   * @uses graphql_woocommerce_products_add_sort_fields
   */
  public static function woographql_product_connection_orderby_numeric_meta_keys($fields):array {
    $fields[] = self::getSalePercentageKey();
    return $fields;
  }

  /**
   * Returns the sale percentage key to be used in GraphQL.
   *
   * @return string The sale percentage key.
   */
  public static function getSalePercentageKey():string {
    return get_option('_sale_percentage_displayed_value') === 'highest' ? '_sale_percentage_highest' : '_sale_percentage';
  }

  /**
   * Registers the GraphQL field for the 'salePercentage' property of the 'Product' type.
   *
   * @return void
   */
  public static function graphql_register_types() {
    register_graphql_field('Product', 'salePercentage', [
      'type' => 'string',
      'resolve' => function ($source) {
        $product = $source?->as_WC_Data();
        if(!$product || !$product?->is_on_sale()) {
          return null;
        }
        $sale_percentage = SalePercentage::displaySalePercentage('', null, $source->as_WC_Data());
        if (!$sale_percentage) {
          return null;
        }
        return strip_tags((string) $sale_percentage);
      },
    ]);
  }

}
