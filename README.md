![GitHub](https://img.shields.io/github/license/netzstrategen/woocommerce-sale-percentage?color=6AAAE9&style=for-the-badge)

# Sale Percentage

This plugin improves the way WooCommerce manages and displays product sale labels as follows:

- Shows the calculated discount percentage taking in account the product regular and sale prices.
- Displays the lowest/highest discount percentage for all the variations of a product.
- Ensures the sale label is displayed only if the sale percentage reaches a minimum value, defined as a WooCommerce custom configuration setting (10% by default).
- Ensures performance by recalculating and saving the products sale percentage only when its regular price or sale price is modified.
- Provides a wp-cli command to force the recalculation of the sale percentage for a single product, several or all products in the shop.
- Is compatible with WooCommerce Advanced Bulk Edit plugin.

## :gear: How to Install

:warning: The plugin requires WooCommerce to be installed.

1. Open your WordPress installation root path in the terminal.
2. `git submodule add --name sale-percentage -- git@github.com:netzstrategen/woocommerce-sale-percentage.git wp-content/plugins/sale-percentage`
3. Activate the plugin: `wp plugin activate sale-percentage`

## :zap: WP CLI

This plugin provides a wp-cli command to force the recalculation of the sale percentage for a single product, several or all products in the shop.

Usage: `wp sale-percentage refresh [<product-ids-list> | --all]`

Examples:

- `wp sale-percentage refresh 2165, 2166, 2167`
- `wp sale-percentage refresh --all`

## :building_construction: How to Contribute

Any kind of contribution is very welcome!

Please, be sure to read our [Code of Conduct](https://github.com/netzstrategen/woocommerce-sale-percentage/blob/master/CODE_OF_CONDUCT) before start contributing.

If you notice something wrong please [open an issue](https://github.com/netzstrategen/woocommerce-sale-percentage/issues) or create a [Pull Request](https://github.com/netzstrategen/woocommerce-sale-percentage/pulls) or just send an email to [tech@netztsrategen.com](mailto:tech@netztsrategen.com).
If you want to warn me about an important security vulnerability, please use [the GitHub Security page](https://github.com/netzstrategen/woocommerce-sale-percentage/network/alerts).

## :hammer_and_wrench: Dev Setup

Requirements:

- [PHP](https://www.php.net/) >= 7.x
- [Node.js](https://nodejs.org/en/) >= 12.x
- [Gulp](https://gulpjs.com/) >= 4.x

Setup steps:

1. `git clone git@github.com:netzstrategen/woocommerce-sale-percentage.git`
2. `cd woocommerce-sale-percentage`
3. `npm install`
4. `npm run build`

### Available Scripts

- `npm run serve` ·Builds all assets and watches for changes. Alias for `gulp watch`.
- `npm run build` · Builds all the assets. Alias for `gulp build`.

## :page_facing_up: License

[GPL-2.0 License](https://github.com/netzstrategen/woocommerce-sale-percentage/blob/master/LICENSE) © [netzstrategen](https://netzstrategen.com)
