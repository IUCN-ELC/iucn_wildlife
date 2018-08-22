<?php

namespace Drupal\eu_cookie_compliance\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for JS call that checks if the visitor is in the EU.
 */
class CheckIfEuCountryJsController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content() {
    $data = eu_cookie_compliance_user_in_eu();
    return new JsonResponse($data, 200, ['Cache-Control' => 'private']);
  }

}
