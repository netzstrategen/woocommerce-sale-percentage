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
    add_filter('graphql_woocommerce_products_add_sort_fields', [__CLASS__, 'graphql_woocommerce_products_add_sort_fields']);
  }

  /**
   * Adds sale percentage sort enum to GraphQL.
   *
   * @implements woographql_product_orderby_enum_values
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
  public static function graphql_woocommerce_products_add_sort_fields($fields):array {
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

}
