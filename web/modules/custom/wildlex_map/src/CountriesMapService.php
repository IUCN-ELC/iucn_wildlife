<?php

namespace Drupal\wildlex_map;

use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\taxonomy\Entity\Term;

/**
 * Class CountriesCourtDecisionsService.
 */
class CountriesMapService {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\BrowserKit\Request
   */
  protected $request;

  /**
   * CountriesMapService constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *
   */
  public function __construct(RequestStack $requestStack) {
    $this->request = $requestStack->getCurrentRequest();
  }

  public function getFilters() {
    if (empty($this->request->query->get('f'))) {
      return [];
    }

    $filters = [];
    foreach ($this->request->query->get('f') as $item) {
      list($type, $value) = explode(':', $item);
      if ($type == 'year_period') {
        continue;
      }

      $filters[$type][] = $value;
    }

    return $filters;
  }

  protected function getYearFilters() {
    if (empty($this->request->query->get('f'))) {
      return [NULL, NULL];
    }

    foreach ($this->request->query->get('f') as $item) {
      list($type,) = explode(':', $item);
      if ($type != 'year_period') {
        continue;
      }

      preg_match('/.*((\d){4}).*((\d){4}).*/', $item, $matches);
      return [$matches[1], $matches[3]];
    }

    return [NULL, NULL];
  }

  protected function getTermNames($tids) {
    if (is_numeric($tids)) {
      $tids = (array) $tids;
    }

    $terms = [];
    foreach ($tids as $tid) {
      $term = Term::load($tid);
      $terms[] = $term->label();
    }
    return $terms;
  }

  public function getModalBuild($content_type, $items_count = 0) {
    $fields = [];
    foreach ($this->getFilters() as $filterName => $filterValue) {
      switch ($filterName) {
        case 'countries':
          $label = t('Country');
          break;
        case 'territorial_subdivisions':
          $label = t('Territorial subdivision');
          break;
        case 'document_types':
          $label = t('Type of court');
          break;
        case 'species':
          $label = t('Species');
          break;
        case 'language':
          $label = t('Language');
          break;
        default:
          continue 2;
      }

      $fields[] = sprintf('<strong>%s</strong> (%s)',
        $label,
        implode(', ', $this->getTermNames($filterValue)));
    }

    list($minYear, $maxYear) = $this->getYearFilters();
    if (!empty($minYear) || !empty($maxYear)) {
      $fields[] = sprintf('<strong>%s</strong> (%s)',
        t('Year/period'),
        (isset($minYear) ? t('from ') . $minYear : '') . (isset($maxYear) ? t(' to ') . $maxYear : '')
      );
    }

    $modal_title = t("Showing <strong>@items_count</strong> <em>@content_type_name</em>",
      [
        '@content_type_name' => $content_type['singular'],
        '@items_count' => $items_count,
      ]);

    if ($fields) {
      $modal_title .= t(' filtered by ') . implode(', ', $fields);
    }

    return [
      '#theme' => 'wildlex_map_block',
      '#title' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $modal_title,
      ],
    ];
  }

}
