<?php

namespace Drupal\iucn_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Reset filters block' block.
 *
 * @Block(
 *   id = "reset_filters_block",
 *   admin_label = @Translation("Reset filters block"),
 * )
 */
class ResetFiltersBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /** @var \Drupal\Core\Routing\RouteMatchInterface */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $routeMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  public function build() {
    $form['actions']['reset'] = [
      '#attributes' => [
        'class' => [
          'btn',
          'btn-default',
          'btn-sm',
          'btn-block',
          'search-reset',
        ],
        'href' => Url::fromRoute($this->routeMatch->getRouteName())
          ->toString(),
      ],
      '#tag' => 'a',
      '#type' => 'html_tag',
      '#value' => $this->t('Reset all filters'),
    ];
    return $form;
  }

  public function getCacheContexts() {
    //Every new route this block will rebuild
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
