<?php

namespace Drupal\edw_healthcheck\Helper;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\update\UpdateManagerInterface;
use Drupal\update\UpdateFetcherInterface;

/**
 * Helper class that contains reusable functions of the EDWHealthCheck module.
 */
class EDWHealthCheckHelper {
  use StringTranslationTrait;

  /**
   * For backwards compatibility, this module uses defines to set levels.
   *
   * This function is called globally in edw_healthcheck.module.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Config to read the values from.
   */
  public static function setEDWHealthcheckStatusConstants(\Drupal\Core\Config\ImmutableConfig $config = NULL) {
    // Defines constants to be used by this modules and others that
    // use its hook_edw_healthcheck().
    if (!$config) {
      $config = \Drupal::config('edw_healthcheck.settings');
    }
    if ($config->get('edw_healthcheck.status.ok') === NULL) {
      // Should only happen in tests, as the config might not be loaded yet.
      return;
    }
    define('HEALTHCHECK_STATUS_OK', $config->get('edw_healthcheck.status.ok') /* Default: 0 */);
    define('HEALTHCHECK_STATUS_WARNING', $config->get('edw_healthcheck.status.warning') /* Default: 1 */);
    define('HEALTHCHECK_STATUS_CRITICAL', $config->get('edw_healthcheck.status.critical') /* Default: 2 */);
    define('HEALTHCHECK_STATUS_UNKNOWN', $config->get('edw_healthcheck.status.unknown') /* Default: 3 */);
  }

  /**
   * Function that calculates the outdated modules.
   *
   * @param string $tmp_state
   *    Tmp_state parameter.
   */
  public function calculateOutdatesModuleAndThemeNames(&$tmp_state) {
    $tmp_projects = update_calculate_project_data(\Drupal::service('update.manager')
          ->getProjects());
    $outdated_count = 0;
    $tmp_modules = '';
    foreach ($tmp_projects as $projkey => $projval) {
      if ($projval['status'] < UpdateManagerInterface::CURRENT && $projval['status'] >= UpdateManagerInterface::NOT_SECURE) {
        switch ($projval['status']) {
          case UpdateManagerInterface::NOT_SECURE:
            $tmp_projstatus = $this->t('NOT SECURE');
            break;

          case UpdateManagerInterface::REVOKED:
            $tmp_projstatus = $this->t('REVOKED');
            break;

          case UpdateManagerInterface::NOT_SUPPORTED:
            $tmp_projstatus = $this->t('NOT SUPPORTED');
            break;

          case UpdateManagerInterface::NOT_CURRENT:
            $tmp_projstatus = $this->t('NOT CURRENT');
            break;

          default:
            $tmp_projstatus = $projval['status'];
        }
        $tmp_modules .= ' ' . $projkey . ':' . $tmp_projstatus;
        $outdated_count++;
      }
    }
    if ($outdated_count > 0) {
      $tmp_modules = trim($tmp_modules);
      $tmp_state .= " ($tmp_modules)";
    }
  }

  /**
   * Gets status text.
   *
   * @param int $status
   *   One of UpdateManagerInterface::* constants.
   *
   * @return string
   *   Status text.
   */
  public static function getStatusText($status) {
    switch ($status) {
      case UpdateManagerInterface::NOT_SECURE:
        return 'NOT SECURE';

      case UpdateManagerInterface::CURRENT:
        return 'current';

      case UpdateManagerInterface::REVOKED:
        return 'version revoked';

      case UpdateManagerInterface::NOT_SUPPORTED:
        return 'not supported';

      case UpdateManagerInterface::NOT_CURRENT:
        return 'update available';

      case UpdateFetcherInterface::UNKNOWN:
      case UpdateFetcherInterface::NOT_CHECKED:
      case UpdateFetcherInterface::NOT_FETCHED:
      case UpdateFetcherInterface::FETCH_PENDING:
        return 'unknown';
    }
    return 'unknown';
  }
}
