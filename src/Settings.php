<?php

namespace Netzstrategen\SalePercentage;

use WC_Admin_Settings;

/**
 * Plugin settings functionality.
 */
class Settings {

  /**
   * Adds plugin settings tab and fields.
   *
   * @param array $settings
   *   The WooCommerce settings.
   *
   * @return array
   *   The extended WooCommerce settings.
   */
  public static function addSettings(array $settings): array {
    add_action('woocommerce_settings_tabs_array', __CLASS__ . '::addSettingsTab', 30);
    add_action('woocommerce_settings_sale_percentage', __CLASS__ . '::addSettingsTabFields');
    add_action('woocommerce_settings_save_sale_percentage', __CLASS__ . '::saveSettings');

    return $settings;
  }

  /**
   * Adds plugin settings tab.
   *
   * @param array $tabs
   *   The WooCommerce settings tabs.
   *
   * @return array
   *   The extended WooCommerce settings tabs.
   *
   * @implements woocommerce_settings_tabs_array
   */
  public static function addSettingsTab(array $tabs): array {
    $tabs['sale_percentage'] = __('Sale Percentage', Plugin::L10N);

    return $tabs;
  }

  /**
   * Adds settings fields to the plugin WooCommerce settings tab.
   *
   * @implements woocommerce_settings_sale_percentage
   */
  public static function addSettingsTabFields() {
    $settings = static::getSettings();
    WC_Admin_Settings::output_fields($settings);
  }

  /**
   * Triggers WooCommerce tab setting save.
   *
   * @implements woocommerce_settings_save_sale_percentage
   */
  public static function saveSettings() {
    $settings = static::getSettings();
    WC_Admin_Settings::save_fields($settings);
  }

  /**
   * Creates plugin settings fields.
   *
   * @param array $settings
   *   The WooCommerce settings.
   *
   * @return array
   *   Extended WooCommerce settings.
   *
   * @implements woocommerce_get_settings_sale_percentage
   */
  public static function createSettingsFields(array $settings): array {
    $settings[] = [
      'type' => 'title',
    ];
    $settings[] = [
      'id' => '_minimum_sale_percentage_to_display_label',
      'type' => 'text',
      'name' => __('Minimum percentage:', Plugin::L10N),
      'desc_tip' => __('Minimum discount value (%) to display the sale flash bubble.', Plugin::L10N),
      'default' => SalePercentage::SALE_BUBBLE_MIN_AMOUNT,
    ];
    $settings[] = [
      'type' => 'title',
    ];
    $settings[] = [
      'id' => '_sale_percentage_displayed_value',
      'type' => 'select',
      'name' => __('Variable products default percentage:', Plugin::L10N),
      'desc_tip' => __('Select to display the lowest or highest discount value for variable products.', Plugin::L10N),
      'options' => [
        'highest' => __('Highest', Plugin::L10N),
        'lowest' => __('Lowest', Plugin::L10N),
      ],
      'default' => 'lowest',
    ];
    $settings[] = [
      'id' => Plugin::PREFIX,
      'type' => 'sectionend',
    ];

    return $settings;
  }

  /**
   * Defines the plugin configuration settings.
   *
   * @return array
   *   The plugin configuration settings.
   */
  public static function getSettings(): array {
    return apply_filters('woocommerce_get_settings_sale_percentage', []);
  }

}
