<?php
/**
 * @file
 * Contains \Drupal\iucn_frontend\Plugin\Preprocess\FileLink.
 */

namespace Drupal\iucn_frontend\Plugin\Preprocess;

use Drupal\bootstrap\Annotation\BootstrapPreprocess;
use Drupal\bootstrap\Bootstrap;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * Pre-processes variables for the "file_link" theme hook.
 *
 * @ingroup theme_preprocess
 *
 * @BootstrapPreprocess("file_link",
 *   replace = "template_preprocess_file_link"
 * )
 */
class FileLink extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  public function preprocessVariables(Variables $variables, $hook, array $info) {
    $file = ($variables['file'] instanceof File) ? $variables['file'] : File::load($variables['file']->fid);

    $url = file_create_url($file->getFileUri());
    $variables['attributes']['href'] = $url;
    $variables['#cache']['contexts'][] = 'url.site';

    $file_size = $file->getSize();
    $mime_type = $file->getMimeType();
    $variables['attributes']['type'] = $mime_type . '; length=' . $file_size;

    if (empty($variables['description'])) {
      $link_text = $file->getFilename();
    } else {
      $link_text = $variables['description'];
      $variables['attributes']['title'] = $file->getFilename();
    }

    $variables->addClass(array(
      'file',
      'file--mime-' . strtr($mime_type, array('/' => '-', '.' => '-')),
      'file--' . file_icon_class($mime_type)
    ));

    $icon = Bootstrap::glyphicon('file');

    $variables['icon'] = Element::create($icon)
      ->addClass('text-primary')
      ->getArray();

    $variables['file_name'] = $link_text;
    $variables['url'] = $url;

    $variables['file_size'] = format_size($file_size);

    $this->preprocessAttributes($variables, $hook, $info);
  }

}
