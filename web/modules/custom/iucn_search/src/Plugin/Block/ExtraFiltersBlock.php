<?php

namespace Drupal\iucn_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a 'Extra filters block' block.
 *
 * @Block(
 *   id = "extra_filters_block",
 *   admin_label = @Translation("Extra filters block"),
 * )
 */
class ExtraFiltersBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /** @var \Symfony\Component\HttpFoundation\Request */
  protected $request;

  /** @var \Drupal\Core\Routing\RouteMatchInterface */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $request, RouteMatchInterface $routeMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->request = $request;
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
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('current_route_match')
    );
  }

  public function build() {
    $filters = \Drupal::request()->query->get('f');

    $allowedExtraFilterTypes = [
      'court_jurisdictions',
      'court_levels',
    ];

    if (empty($filters)) {
      return [];
    }

    $extraFilters = [];
    foreach ($filters as $index => $filter) {
      list($type, $tid) = explode(':', $filter);
      if (!in_array($type, $allowedExtraFilterTypes)) {
        continue;
      }

      $extraFilters[] = $tid;
      unset($filters[$index]);
    }

    if (empty($extraFilters)) {
      return [];
    }

    $terms = Term::loadMultiple($extraFilters);

    if (empty($terms)) {
      return [];
    }

    $links = [];
    foreach ($terms as $term) {
      $closeLink = [
        '#type' => 'link',
        '#url' => Url::fromRoute(
          $this->routeMatch->getRouteName(),
          [],
          [
            'query' => ['f' => $filters],
          ]),
        '#title' => '×',
        '#attributes' => [
          'class' => ['close'],
        ],
      ];

      $link = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => "{$term->label()} ",
        'close' => $closeLink,
        '#attributes' => [
          'class' => ['label', 'label-primary'],
        ],
      ];

      $links[] = $link;
    }

    $build = [
      '#type' => 'container',
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['filter-label'],
        ],
        '#value' => $this->t('Court decisions filtered by:'),
      ],
      'filters' => $links,
      'delimiter' => [
        '#type' => 'html_tag',
        '#tag' => 'hr',
      ],
    ];

    return $build;
  }

}
