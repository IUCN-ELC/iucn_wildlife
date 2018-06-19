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
  public function preprocessVariables(Variables $variables) {
    $file = ($variables['file'] instanceof File) ? $variables['file'] : File::load($variables['file']->fid);

    $url = file_create_url($file->getFileUri());
    $variables['url'] = $url;
    $variables['#cache']['contexts'][] = 'url.site';

    $file_size = $file->getSize();
    $variables['file_size'] = format_size($file_size);

    $mime_type = $file->getMimeType();
    $variables['type'] = $mime_type . '; length=' . $file_size;

    if (empty($variables['description'])) {
      $file_name = $file->getFilename();
    } else {
      $file_name = $variables['description'];
      $variables['attributes']['title'] = $file->getFilename();
    }

    $variables['file_name'] = $file_name;

    $variables->addClass(array(
      'file',
      'file--mime-' . strtr($mime_type, array('/' => '-', '.' => '-')),
      'file--' . file_icon_class($mime_type)
    ));

    $this->preprocessAttributes();
  }

}
