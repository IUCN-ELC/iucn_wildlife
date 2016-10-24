<?php

namespace Drupal\Tests\linkit\Functional;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\linkit\Tests\ProfileCreationTrait;

/**
 * Tests the IMCE module integration.
 *
 * @group linkit
 */
class ImceIntegrationTest extends LinkitBrowserTestBase {

  use ProfileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['editor', 'ckeditor', 'imce'];

  /**
   * The linkit profile.
   *
   * @var \Drupal\linkit\ProfileInterface
   */
  protected $linkitProfile;

  /**
   * The text format to use when opening the link dialog.
   *
   * @var \Drupal\filter\FilterFormatInterface
   */
  protected $filterFormat;

  /**
   * The editor to bind the text format to and enable linkit on.
   *
   * @var \Drupal\editor\EditorInterface
   */
  protected $editor;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->linkitProfile = $this->createProfile();
    $this->drupalLogin($this->adminUser);

    $this->filterFormat = FilterFormat::create([
      'format' => 'linkit_test_format',
      'name' => 'Linkit test format',
      'weight' => 1,
      'filters' => [],
    ]);
    $this->filterFormat->save();

    // Set up text editor.
    $this->editor = Editor::create([
      'format' => $this->filterFormat->id(),
      'editor' => 'ckeditor',
    ]);
    $this->editor->setSettings([
      'plugins' => [
        'drupallink' => [
          'linkit_enabled' => TRUE,
          'linkit_profile' => $this->linkitProfile->id(),
        ],
      ],
    ]);
    $this->editor->save();

    // Create a regular user with access to the format.
    $this->webUser = $this->drupalCreateUser([
      $this->filterFormat->getPermissionName(),
    ]);
  }

  /**
   * Test that the IMCE link does not shows up.
   */
  public function testImceIntegationDisabled() {
    $this->drupalLogin($this->webUser);
    $this->drupalGet('editor/dialog/link/' . $this->filterFormat->id());
    $this->assertSession()->linkNotExists('Open IMCE file browser');
  }

  /**
   * Test that the IMCE link shows up.
   */
  public function testImceIntegationEnabled() {
    $this->drupalGet('/admin/config/content/linkit/manage/' . $this->linkitProfile->id());
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->pageTextContains('IMCE integration');
    $this->assertSession()->fieldExists('imce_use');

    $edit = [];
    $edit['imce_use'] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Update profile'));

    $this->drupalGet('/admin/config/content/linkit/manage/' . $this->linkitProfile->id());

    $this->assertSession()->fieldValueEquals('edit-imce-use', '1');

    $this->drupalLogin($this->webUser);

    $this->drupalGet('editor/dialog/link/' . $this->filterFormat->id());
    $this->assertSession()->linkExists('Open IMCE file browser');
  }

}
