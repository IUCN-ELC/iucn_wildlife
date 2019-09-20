<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\process\UrlFile.
 */

namespace Drupal\elis_consumer\Plugin\migrate\process;

use Drupal\field\Entity\FieldConfig;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * This plugin downloads remote file and saves it as Drupal file.
 *
 * @MigrateProcessPlugin(
 *   id = "url_file"
 * )
 */
class UrlFile extends ProcessPluginBase {
  /**
   * {@inheritdoc}
   */
  public function transform($url,
                            MigrateExecutableInterface $migrate_executable,
                            Row $row,
                            $destination_property
  ) {
    $url = trim($url);
    if (empty($url)) {
      return null;
    }

    if (!empty($this->configuration['destination_property'])) {
      $destination_property = $this->configuration['destination_property'];
    }

    if (!empty($this->configuration['bundle'])) {
      $bundle = $this->configuration['bundle'];
    }
    elseif (!empty($row->getDestination()['type'])) {
      $bundle = $row->getDestination()['type'];
    }
    else {
      throw new \Exception('Invalid destination bundle');
    }

    $fi = FieldConfig::loadByName('node', $bundle, $destination_property);

    // Get the allowed file extensions.
    $ext = $fi->getSetting('file_extensions');
    $extensions = $ext ? explode(' ', $ext) : [];

    // Check if file extension is allowed.
    $pi = pathinfo($url, PATHINFO_EXTENSION);
    if (!in_array(strtolower($pi), $extensions)) {
      $ext = implode(',', $extensions);
      $migrate_executable->saveMessage(
        "Invalid extension: `$pi` (allowed:$ext) for " . $url . "\n",
        MigrationInterface::MESSAGE_WARNING
      );
      return null;
    }

    $filename = basename($url);
    $path = $fi->getSetting('file_directory') . '/' ?? '';
    // Keep the original tree structure for all imported files.
    $tree = explode('server2neu.php/', $url);
    if (isset($tree[1])) {
      $tree_path =  str_replace($filename, '', $tree[1]);
      $realpath = \Drupal::service('file_system')
          ->realpath("public://$path") . '/' . $tree_path;

      if (!file_prepare_directory(
        $realpath,
        FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS
      )) {
        $migrate_executable->saveMessage("$realpath is not writable", MigrationInterface::MESSAGE_WARNING);
        return null;
      }
      $path .= $tree_path;
    }

    // Create the file.
    if ($file = $this->createFile($url, $path, $filename)) {
      return $file;
    }

    $migrate_executable->saveMessage("Could not append to $destination_property, file at $url", MigrationInterface::MESSAGE_WARNING);
    return null;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple()
  {
    return false;
  }

  /**
   * @param string $url
   * @param string $destination_path
   * @param string $filename
   * @return integer|null
   *  File ID (fid)
   */
  protected function createFile(
    $url,
    $destination_path,
    $filename
  ) {
    if ($data = $this->download($url)) {
      // Ecolex specific - does not return 404, but 200 with 404 page.
      if (preg_match('/Server Error/', $data)
        || preg_match('/HTTP Status 404/', $data)
      ) {
        return null;
      }
      $target = 'public://' . $destination_path . rawurldecode($filename);
      if ($file = file_save_data($data, $target, FILE_EXISTS_REPLACE)) {
        return $file->id();
      }
    }
    return null;
  }

  /**
   * {@inheritdoc}
   */
  protected function download($url, $headers = [])
  {
    if (empty($url)) {
      return null;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_NOBODY, 0);
    $ret = curl_exec($ch);
    $info = curl_getinfo($ch);
    if ($info['http_code'] != 200) {
      // Retry
      $url = str_replace(' ', '%20', $url);
      curl_setopt($ch, CURLOPT_URL, $url);
      $ret = curl_exec($ch);
      $info = curl_getinfo($ch);
      if ($info['http_code'] != 200) {
        $ret = null;
      }
    }
    curl_close($ch);
    return $ret;
  }
}
