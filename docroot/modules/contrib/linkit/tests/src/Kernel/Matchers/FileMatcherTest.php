<?php

namespace Drupal\Tests\linkit\Kernel\Matchers;

use Drupal\file\Entity\File;
use Drupal\Tests\linkit\Kernel\LinkitKernelTestBase;

/**
 * Tests file matcher.
 *
 * @group linkit
 */
class FileMatcherTest extends LinkitKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file_test', 'file'];

  /**
   * The matcher manager.
   *
   * @var \Drupal\linkit\MatcherManager
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installSchema('system', ['key_value_expire']);
    $this->installSchema('file', array('file_usage'));

    $this->manager = $this->container->get('plugin.manager.linkit.matcher');

    // Linkit doesn't case about the actual resource, only the entity.
    foreach (['gif', 'jpg', 'png'] as $ext) {
      $file = File::create([
        'uid' => 1,
        'filename' => 'image-test.' . $ext,
        'uri' => 'public://image-test.' . $ext,
        'filemime' => 'text/plain',
        'status' => FILE_STATUS_PERMANENT,
      ]);
      $file->save();
    }
  }

  /**
   * Tests file matcher.
   */
  public function testFileMatcherWithDefaultConfiguration() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:file', []);
    $suggestions = $plugin->execute('image-test');
    $this->assertEquals(3, count($suggestions->getSuggestions()), 'Correct number of suggestions.');
  }

  /**
   * Tests file matcher with extension filer.
   */
  public function testFileMatcherWithExtensionFiler() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:file', [
      'settings' => [
        'file_extensions' => 'png',
      ],
    ]);

    $suggestions = $plugin->execute('image-test');
    $this->assertEquals(1, count($suggestions->getSuggestions()), 'Correct number of suggestions with single file extension filter.');

    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:file', [
      'settings' => [
        'file_extensions' => 'png jpg',
      ],
    ]);

    $suggestions = $plugin->execute('image-test');
    $this->assertEquals(2, count($suggestions->getSuggestions()), 'Correct number of suggestions with multiple file extension filter.');
  }

}
