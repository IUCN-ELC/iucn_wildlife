<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\process\Get.
 */

namespace Drupal\elis_consumer\Plugin\migrate\process;

use Drupal\field\Entity\FieldConfig;
use Drupal\file\FileInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Annotation\MigrateProcessPlugin;


/**
 * This plugin downloads remote file and saves it as Drupal file.
 *
 * @MigrateProcessPlugin(
 *   id = "url_file"
 * )
 */
class UrlFile extends ProcessPluginBase {

  /**
   * Flag indicating whether there are multiple values.
   *
   * @var bool
   */
  protected $multiple = FALSE;

  /**
   * {@inheritdoc}
   */
  public function transform($url, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $url = trim($url);
    if (empty($url)) {
      return NULL;
    }
    $bundle = $row->getDestination()['type'];
    $fi = FieldConfig::loadByName('node', $bundle, $destination_property);
    $extensions = $fi->getSetting('file_extensions');
    if (!empty($extensions)) {
      $extensions = explode(' ', $extensions);
    }
    else {
      $extensions = array();
    }
    // Not allowed
    $pi = pathinfo($url, PATHINFO_EXTENSION);
    if (!in_array($pi, $extensions)) {
      $ext = implode(',', $extensions);
      $migrate_executable->saveMessage("Invalid extension: `$pi` (allowed:$ext) for " . $url . "\n", MigrationInterface::MESSAGE_WARNING);
      return NULL;
    }

    $filename = basename($url);
    $path = $fi->getSetting('file_directory');
    $path = !empty($path) ? $path . '/' : '';
    $destination = 'public://' . $path . $filename;
    $file = $this->createFile($url, $destination);
    if (!$file) {
      $migrate_executable->saveMessage("Could not append to $destination_property, file at $url", MigrationInterface::MESSAGE_WARNING);
    }
    return $file;
  }


  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return FALSE;
  }


  /**
   * @param string $url
   * @return integer
   *  File ID (fid)
   */
  protected function createFile($url, $destination) {
    if ($data = $this->download($url)) {
      // Ecolex specific - does not return 404, but 200 with 404 page :rofl:
      if (preg_match('/Server Error/', $data)) {
        return NULL;
      }
      /** @var FileInterface $file */
      if ($file = file_save_data($data, $destination, FILE_EXISTS_REPLACE)) {
        return $file->id();
      }
    }
    return NULL;
  }


  protected function download($url, $headers = array()) {
    if (empty($url)) {
      return NULL;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_NOBODY, 0);
    $ret = curl_exec($ch);
    $info = curl_getinfo($ch);
    if ($info['http_code'] != 200) {
      $ret = NULL;
    }
    curl_close($ch);
    return $ret;
  }
}
