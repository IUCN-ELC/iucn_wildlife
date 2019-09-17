<?php
namespace Drupal\iucn_search\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Console\Bootstrap\Drupal;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\iucn_search\edw\solr\SearchResult;
use Drupal\iucn_search\edw\solr\SolrSearch;
use Drupal\iucn_search\edw\solr\SolrSearchServer;
use Drupal\node\Entity\Node;
use Drupal\wildlex_map\CountriesMapService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Reset filters block' block.
 *
 * @Block(
 *   id = "reset_filters_block",
 *   admin_label = @Translation("Reset filters block"),
 * )
 */

class ResetFiltersBlock extends BlockBase{

  public function build() {
    $form['actions']['reset'] = [
      '#attributes' => [
        'class' => ['btn', 'btn-default', 'btn-sm', 'btn-block', 'search-reset'],
        'href' => Url::fromRoute(\Drupal::request()->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_NAME))->toString(),
      ],
      '#tag' => 'a',
      '#type' => 'html_tag',
      '#value' => $this->t('Reset all filters')
    ];
    return $form;
  }


}