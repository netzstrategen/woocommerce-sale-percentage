<?php

namespace Netzstrategen\SalePercentage;

/**
 * Helper methods.
 */
class Helpers {

  /**
   * Plugin base URL.
   *
   * @var string
   */
  private static $baseUrl;

  /**
   * The base URL path to this plugin's folder.
   *
   * Uses plugins_url() instead of plugin_dir_url() to avoid a trailing slash.
   */
  public static function getBaseUrl() {
    if (!isset(static::$baseUrl)) {
      static::$baseUrl = plugins_url('', static::getBasePath() . '/plugin.php');
    }

    return static::$baseUrl;
  }

  /**
   * The absolute filesystem base path of this plugin.
   *
   * @return string
   *   The plugin absolute filesystem base path.
   */
  public static function getBasePath() {
    return dirname(__DIR__);
  }

  /**
   * Generates a version out of the current commit hash.
   *
   * @return string
   *   The current git commit hash.
   */
  public static function getGitVersion() {
    $git_version = NULL;
    if (is_dir(ABSPATH . '.git')) {
      $ref = trim(file_get_contents(ABSPATH . '.git/HEAD'));
      if (strpos($ref, 'ref:') === 0) {
        $ref = substr($ref, 5);
        if (file_exists(ABSPATH . '.git/' . $ref)) {
          $ref = trim(file_get_contents(ABSPATH . '.git/' . $ref));
        }
        else {
          $ref = substr($ref, 11);
        }
      }
      $git_version = substr($ref, 0, 8);
    }

    return $git_version;
  }

  /**
   * Returns the full path to a given script file.
   *
   * @param string $scriptName
   *   Name of the JS file.
   *
   * @return string
   *   Full path to the script.
   */
  public static function getScriptPath($scriptName) {
    if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
      $scriptDir = '/assets';
      $scriptSuffix = '';
    }
    else {
      $scriptDir = '/dist';
      $scriptSuffix = '.min';
    }

    return static::getBaseUrl() . $scriptDir . '/scripts/' . $scriptName . $scriptSuffix . '.js';
  }

}
