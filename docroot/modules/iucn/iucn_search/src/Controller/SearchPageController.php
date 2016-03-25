<?php
/**
 * @file
 * Contains \Drupal\iucn_search\Controller\SearchPageController.
 */
namespace Drupal\iucn_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\iucn_search\edw\solr\SearchResult;
use Drupal\iucn_search\edw\solr\SolrSearch;
use Drupal\iucn_search\edw\solr\SolrSearchServer;

class SearchPageController extends ControllerBase {

  protected $items_per_page = 10;
  protected $items_viewmode = 'search_result';
  protected $resultCount = 0;
  protected $search = NULL;

  public function __construct() {
    try {
      $server_config = new SolrSearchServer('default_node_index');
      $this->search = new SolrSearch($_GET, $server_config);
    } catch (\Exception $e) {
      watchdog_exception('iucn_search', $e);
      drupal_set_message($this->t('An error occurred.'), 'error');
    }
  }

  public function searchPage() {
    $current_page = !empty($_GET['page']) ? $_GET['page'] : 0;
    $results = array();

    /** @var SearchResult $result */
    try {
      if ($result = $this->search->search($current_page, $this->items_per_page)) {
        pager_default_initialize($result->getCountTotal(), $this->items_per_page);
        $rows = $result->getResults();

        foreach ($rows as $nid => $data) {
          $node = \Drupal\node\Entity\Node::load($nid);

          if (!empty($node)) {
            $results[$nid] = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, $this->items_viewmode);
          }
        }
      }
    } catch(\Exception $e) {
      return $this->handleError($e);
    }

    return $results;
  }

}
