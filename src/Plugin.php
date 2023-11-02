<?php

namespace Netzstrategen\SalePercentage;

use ElasticPress\Indexable\Post\SyncManager;
use Netzstrategen\SalePercentage\GraphQL;

/**
 * Main front-end functionality.
 */
class Plugin {

  /**
   * Prefix for naming.
   *
   * @var string
   */
  const PREFIX = 'sale-percentage';

  /**
   * Gettext localization domain.
   *
   * @var string
   */
  const L10N = self::PREFIX;

  /**
   * Plugin initialization method.
   *
   * @implements init
   */
  public static function init() {
    // Creates plugin settings fields.
    add_filter('woocommerce_get_settings_sale_percentage', __NAMESPACE__ . '\Settings::createSettingsFields');

    // Updates sale percentage when regular price or sale price are updated.
    add_action('added_post_meta', __NAMESPACE__ . '\SalePercentage::updateSalePercentage', 10, 4);
    add_action('updated_post_meta', __NAMESPACE__ . '\SalePercentage::updateSalePercentage', 10, 4);
    add_action('deleted_post_meta', __NAMESPACE__ . '\SalePercentage::deleteSalePercentage', 10, 4);

    // Ensures ES index is always triggered to avoid any sync inconsistency, as
    // updated_post_meta will only be triggered if the value has changed.
    // @see web/app/plugins/elasticpress/includes/classes/Indexable/Post/SyncManager.php:45
    if (class_exists('ElasticPress\Indexable\Post\SyncManager')) {
      $sync_manager = new SyncManager('post');
      add_action('update_post_meta', [$sync_manager, 'action_queue_meta_sync'], 10, 4);
    }

    // Adds sale percentage to GraphQL.
    GraphQL::init();

    // Displays sale percentage as a product flash bubble.
    add_filter('woocommerce_sale_flash', __NAMESPACE__ . '\SalePercentage::displaySalePercentage', 10, 3);

    if (is_admin()) {
      return;
    }

    // Enqueues plugin scripts and styles.
    add_action('wp_enqueue_scripts', __CLASS__ . '::enqueueAssets');
    add_action('wp_head', __CLASS__ . '::addInlineStyle');

    // Adds custom order by option.
    add_filter('woocommerce_default_catalog_orderby_options', __NAMESPACE__ . '\SalePercentage::addOrderBySalePercentageOption');
    add_filter('woocommerce_catalog_orderby', __NAMESPACE__ . '\SalePercentage::addOrderBySalePercentageOption');
    add_filter('woocommerce_get_catalog_ordering_args', __NAMESPACE__ . '\SalePercentage::orderProductsBySalePercentage');
  }

  /**
   * Displays a notice if WooCommerce is not installed and active.
   *
   * @implements admin_notices
   */
  public static function admin_notices() {
    if (!class_exists('WooCommerce')) {
      ?>
        <div class="error below-h3">
          <p>
            <strong><?= __('Sale Percentage plugin requires WooCommerce to be installed and active.', Plugin::L10N); ?></strong>
          </p>
        </div>
      <?php
    }
  }

  /**
   * Enqueues plugin scripts and styles.
   *
   * @implements wp_enqueue_scripts
   */
  public static function enqueueAssets() {
    $git_version = Helpers::getGitVersion();

    wp_enqueue_script(Plugin::PREFIX, Helpers::getScriptPath(Plugin::PREFIX), ['jquery'], $git_version, TRUE);

    wp_localize_script(Plugin::PREFIX, 'sale_percentage_settings', [
      'saleMinAmount' => get_option('_minimum_sale_percentage_to_display_label', SalePercentage::SALE_BUBBLE_MIN_AMOUNT),
      'salePercentageFormat' => SalePercentage::getSalePercentageFormat(),
    ]);
  }

  /**
   * Adds plugin inline styles without a styles file enqueued.
   *
   * @uses wp_head
   */
  public static function addInlineStyle() {
    $badge_background_color = SalePercentage::getBadgeBackgroundColor();
    if (!empty($badge_background_color)) {
      echo "
      <style id='" . Plugin::PREFIX . "-inline-css' type='text/css'>
        .products-list.products,
        .single-product-summary {
          --on-sale-background: {$badge_background_color};
        }
      </style>";
    }
  }

  /**
   * Loads the plugin textdomain.
   */
  public static function loadTextdomain() {
    load_plugin_textdomain(static::L10N, FALSE, static::L10N . '/languages/');
  }

}
