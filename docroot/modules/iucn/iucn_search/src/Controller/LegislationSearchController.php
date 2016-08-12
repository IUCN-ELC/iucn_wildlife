<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Controller\LegislationSearchController.
 */

namespace Drupal\iucn_search\Controller;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Cache\Cache;
use Drupal\iucn_search\edw\solr\SearchResult;
use Drupal\node\Entity\Node;

class LegislationSearchController extends DefaultSearchController {

  public function searchPage() {
    $current_page = !empty($_GET['page']) ? $_GET['page'] : 0;
    $results = array();
    $cacheTags = array();
    $found = 0;

    /** @var SearchResult $result */
    try {
      $result = self::getSearch('legislation', ['sort' => 'field_date_of_text', 'sortOrder' => 'asc'])
        ->search($current_page, $this->items_per_page);
      if (!empty($result)) {
        pager_default_initialize($result->getCountTotal(), $this->items_per_page);
        $found = $result->getCountTotal();
        $rows = $result->getResults();

        foreach ($rows as $nid => $data) {
          /** @var Node $node */
          $node = Node::load($nid);
          if (!empty($node)) {
            $cacheTags = array_merge($cacheTags, $node->getCacheTags());
            $results[$nid] = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, $this->items_viewmode);
          }
        }
      }

      Cache::invalidateTags($cacheTags);

      $numFound = $this->formatPlural($found, 'Found 1 legislation', 'Found @count legislations');

      $sorts = [
        'dateOfTextDesc' => [
          'field' => 'field_date_of_text',
          'order' => 'desc',
          'text' => 'most recent',
        ],
        'dateOfTextAsc' => [
          'field' => 'field_date_of_text',
          'order' => 'asc',
          'text' => 'least recent',
        ],
      ];
      $activeSort = !empty($_GET['sort']) ? $_GET['sort'] : 'field_date_of_text';
      $activeOrder = !empty($_GET['sortOrder']) ? $_GET['sortOrder'] : 'asc';
      $getCopy = $_GET;
      $sortMarkup = [];
      foreach ($sorts as $key => $sort) {
        if ($activeSort == $sort['field'] && $activeOrder == $sort['order']) {
          $markup = '<strong>Sorted by ' . $sort['text'] . '</strong>';
        }
        else {
          $getCopy['sort'] = $sort['field'];
          $getCopy['sortOrder'] = $sort['order'];
          $url = Url::fromRoute('iucn.search.legislation', [], ['query' => $getCopy]);
          $markup = Link::fromTextAndUrl('Sort by ' . $sort['text'], $url)->toString();
        }
        $sortMarkup[$key] = [
          '#type' => 'item',
          '#markup' => $markup,
        ];
      }

      if (empty($results)) {
        if ($found) {
          // The SOLR returned results but we couldn't find them in Drupal
          throw new \Exception("SOLR server is out of sync.");
        }
        $results = [
          '#attributes' => ['class' => ['well', 'blankslate']],
          '#type' => 'container',
          [
            '#attributes' => ['class' => ['ecolexicon', 'ecolexicon-court-decision']],
            '#tag' => 'span',
            '#type' => 'html_tag'
          ],
          [
            '#attributes' => ['class' => ['blankslate-title']],
            '#tag' => 'h3',
            '#type' => 'html_tag',
            '#value' => $this->t('No legislations found.')
          ],
          [
            '#attributes' => ['class' => []],
            '#tag' => 'p',
            '#type' => 'html_tag',
            '#value' => $this->t('Use the links above to find what you&rsquo;re looking for, or try a new search query. The Search filters are also super helpful for quickly finding court decisions most relevant to you.')
          ]
        ];
      }

      $content = [
        '#cache' => ['contexts' => ['url']],
        'meta' => [
          '#attributes' => ['class' => ['search-header']],
          '#type' => 'container',
          [
            '#attributes' => ['class' => ['pull-left']],
            '#tag' => 'div',
            '#type' => 'html_tag',
            '#value' => $numFound
          ],
          [
            '#attributes' => ['class' => ['list-inline', 'pull-right']],
            '#items' => $sortMarkup,
            '#list_type' => 'ul',
            '#theme' => 'item_list'
          ]
        ],
        'results' => $results,
        'pager' => ['#type' => 'pager'],
      ];

      if (!$found) {
        unset($content['meta']);
      }
    }
    catch(\Exception $e) {
      return $this->handleError($e);
    }

    return $content;
  }
}
