<?php

namespace Drupal\Tests\linkit\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\editor\Entity\Editor;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\linkit\Tests\ProfileCreationTrait;
use Drupal\node\Entity\NodeType;

/**
 * Tests the linkit alterations on the drupallink plugin.
 *
 * @group linkit
 */
class LinkitDialogTest extends JavascriptTestBase {

  use ProfileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'ckeditor', 'filter', 'linkit'];

  /**
   * An instance of the "CKEditor" text editor plugin.
   *
   * @var \Drupal\ckeditor\Plugin\Editor\CKEditor;
   */
  protected $ckeditor;

  /**
   * A demo entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $demoEntity;

  /**
   * A linkit profile.
   *
   * @var \Drupal\linkit\ProfileInterface
   */
  protected $linkitProfile;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $matcherManager = $this->container->get('plugin.manager.linkit.matcher');
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $matcherManager->createInstance('entity:node', []);
    $this->linkitProfile = $this->createProfile();
    $this->linkitProfile->addMatcher($plugin->getConfiguration());
    $this->linkitProfile->save();

    // Create text format, associate CKEditor.
    $llama_format = FilterFormat::create([
      'format' => 'llama',
      'name' => 'Llama',
      'weight' => 0,
      'filters' => [],
    ]);
    $llama_format->save();
    $editor = Editor::create([
      'format' => 'llama',
      'editor' => 'ckeditor',
    ]);
    $editor->save();

    // Create "CKEditor" text editor plugin instance.
    $this->ckeditor = $this->container->get('plugin.manager.editor')->createInstance('ckeditor');

    // Create a node type for testing.
    NodeType::create(['type' => 'page', 'name' => 'page'])->save();

    // Create a body field instance for the 'page' node type.
    FieldConfig::create([
      'field_storage' => FieldStorageConfig::loadByName('node', 'body'),
      'bundle' => 'page',
      'label' => 'Body',
      'settings' => ['display_summary' => TRUE],
      'required' => TRUE,
    ])->save();

    // Assign widget settings for the 'default' form mode.
    EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('body', ['type' => 'text_textarea_with_summary'])->save();

    // Customize the configuration.
    $this->container->get('plugin.manager.editor')->clearCachedDefinitions();

    $this->ckeditor = $this->container->get('plugin.manager.editor')->createInstance('ckeditor');
    $this->container->get('plugin.manager.ckeditor.plugin')->clearCachedDefinitions();
    $settings = $editor->getSettings();
    $settings['plugins']['drupallink']['linkit_enabled'] = TRUE;
    $settings['plugins']['drupallink']['linkit_profile'] = $this->linkitProfile->id();
    $editor->setSettings($settings);
    $editor->save();

    $account = $this->drupalCreateUser([
      'administer nodes',
      'create page content',
      'edit own page content',
      'use text format llama',
    ]);

    $this->drupalLogin($account);
  }

  /**
   * Test the link dialog.
   */
  public function testLinkDialog() {
    $session = $this->getSession();
    $web_assert = $this->assertSession();
    $page = $session->getPage();

    // Create test nodes.
    $this->demoEntity = $this->createNode(['title' => 'Foo']);

    // Go to node creation page.
    $this->drupalGet('node/add/page');

    // Wait until the editor has been loaded.
    $ckeditor_loaded = $this->getSession()->wait(5000, "jQuery('.cke_contents').length > 0");
    $this->assertTrue($ckeditor_loaded, 'The editor has been loaded.');

    // Click on the drupallink plugin.
    $page->find('css', 'a.cke_button__drupallink')->click();

    // Wait for the form to load.
    $web_assert->assertWaitOnAjaxRequest();

    // Find the linkit field.
    $linkit_field = $page->findField('linkit');

    // Make sure the linkit field is an autocomplete field.
    $linkit_field->hasAttribute('data-autocomplete-path');
    $linkit_field->hasClass('form-linkit-autocomplete');
    $linkit_field->hasClass('ui-autocomplete-input');

    // Make sure all fields are empty.
    $this->assertEmpty($linkit_field->getValue(), 'Linkit field is empty.');
    $this->assertEmptyWithJs('attributes[data-entity-type]');
    $this->assertEmptyWithJs('attributes[data-entity-uuid]');
    $this->assertEmptyWithJs('attributes[data-entity-substitution]');
    $this->assertEmptyWithJs('attributes[href]');

    // Make sure the link information is empty.
    $this->assertEmpty($this->getLinkInfoText(), 'Link information is empty');

    // Make sure the autocomplete result container is hidden.
    $autocomplete_container = $page->find('css', 'ul.linkit-ui-autocomplete');
    $this->assertFalse($autocomplete_container->isVisible());

    // Trigger a keydown event to active a autocomplete search.
    $linkit_field->keyDown('f');

    // Wait for the results to load.
    $this->getSession()->wait(5000, "jQuery('.linkit-result.ui-menu-item').length > 0");

    // Make sure the autocomplete result container is visible.
    $this->assertTrue($autocomplete_container->isVisible());

    // Find all the autocomplete results.
    $results = $page->findAll('css', '.linkit-result.ui-menu-item');
    $this->assertEquals(1, count($results), 'Found autocomplete result');

    // Find the first result and click it.
    $page->find('xpath', '(//li[contains(@class, "linkit-result") and contains(@class, "ui-menu-item")])[1]')->click();

    // Make sure the linkit field field is populated with the node label.
    $this->assertEquals($this->demoEntity->label(), $linkit_field->getValue(), 'The linkit field is populated with the node label.');

    // Make sure the link information is populated.
    $this->assertEquals($this->demoEntity->label(), $this->getLinkInfoText(), 'Link information is populated');

    // Make sure all other fields are populated.
    $this->assertEqualsWithJs('attributes[data-entity-type]', $this->demoEntity->getEntityTypeId());
    $this->assertEqualsWithJs('attributes[data-entity-uuid]', $this->demoEntity->uuid());
    $this->assertEqualsWithJs('attributes[data-entity-substitution]', 'canonical');
    $this->assertEqualsWithJs('attributes[href]', '#');

    // Save the dialog input.
    $page->find('css', '.editor-link-dialog')->find('css', '.button.form-submit span')->click();

    // Wait for the dialog to close.
    $web_assert->assertWaitOnAjaxRequest();

    $fields = [
      'data-entity-type' => $this->demoEntity->getEntityTypeId(),
      'data-entity-uuid' => $this->demoEntity->uuid(),
      'data-entity-substitution' => 'canonical',
      'href' => '#',
    ];
    foreach ($fields as $attribute => $value) {
      $link_attribute = $this->getLinkAttributeFromEditor($attribute);
      $this->assertEquals($value, $link_attribute, 'The link contain an attribute by the name of "' . $attribute . '" with a value of "' . $value . '"');
    }

    // Select the link in the editor.
    $javascript = <<<JS
      (function(){
        var editor = window.CKEDITOR.instances['edit-body-0-value'];
        console.log(editor);
        var element = editor.document.findOne( 'a' );
        editor.getSelection().selectElement( element );
      })()
JS;
    $session->executeScript($javascript);

    // Click on the drupallink plugin.
    $page->find('css', 'a.cke_button__drupallink')->click();

    // Wait for the form to load.
    $web_assert->assertWaitOnAjaxRequest();

    // Find the linkit field.
    $linkit_field = $page->findField('linkit');
    $this->assertEquals($this->demoEntity->label(), $linkit_field->getValue(), 'Linkit field has the correct value.');

    // Make sure all other fields are populated when editing a link.
    $this->assertEqualsWithJs('attributes[data-entity-type]', $this->demoEntity->getEntityTypeId());
    $this->assertEqualsWithJs('attributes[data-entity-uuid]', $this->demoEntity->uuid());
    $this->assertEqualsWithJs('attributes[data-entity-substitution]', 'canonical');
    $this->assertEqualsWithJs('attributes[href]', '#');

    // Make sure the link information is populated with the old label.
    $this->assertEquals($this->demoEntity->label(), $this->getLinkInfoText(), 'Link information is populated');

    // Trigger a keydown event to active a autocomplete search.
    $linkit_field->setValue('http://example.co');
    $linkit_field->keyDown('m');

    // Wait for the autocomplete to be done.
    $this->getSession()->wait(5000, "jQuery('.linkit-result.ui-menu-item').length > 0");

    // Make sure the autocomplete result container is visible.
    $this->assertTrue($autocomplete_container->isVisible());

    // Find all the autocomplete results.
    $results = $page->findAll('css', '.linkit-result.ui-menu-item');
    $this->assertEquals(1, count($results), 'Found autocomplete result');

    // Find the first result and click it.
    $page->find('xpath', '(//li[contains(@class, "linkit-result") and contains(@class, "ui-menu-item")])[1]')->click();

    // Make sure the href field is populated with the external uri.
    $this->assertEquals('http://example.com', $linkit_field->getValue(), 'The linkit field is the same as the URI');

    // Make sure the link information is populated.
    $this->assertEquals('http://example.com', $this->getLinkInfoText(), 'Link information is populated');

    $this->assertEmptyWithJs('attributes[data-entity-type]');
    $this->assertEmptyWithJs('attributes[data-entity-uuid]');
    $this->assertEmptyWithJs('attributes[data-entity-substitution]');
    $this->assertEqualsWithJs('attributes[href]', 'http://example.com');

    // Save the dialog input.
    $page->find('css', '.editor-link-dialog')->find('css', '.button.form-submit span')->click();

    // Wait for the dialog to close.
    $web_assert->assertWaitOnAjaxRequest();

    $fields = [
      'data-entity-type',
      'data-entity-uuid',
      'data-entity-substitution',
    ];
    foreach ($fields as $attribute) {
      $link_attribute = $this->getLinkAttributeFromEditor($attribute);
      $this->assertNull($link_attribute, 'The link does not contain an attribute by the name of "' . $attribute . '"');
    }

    $href_attribute = $this->getLinkAttributeFromEditor('href');
    $this->assertEquals('http://example.com', $href_attribute, 'The link href is correct.');
  }

  /**
   * Asserts that a variable is empty.
   *
   * @param string $field_name
   *   The name of the field.
   */
  private function assertEmptyWithJs($field_name) {
    $javascript = "(function (){ return jQuery('input[name=\"" . $field_name . "\"]').val(); })()";
    $field_value = $this->getSession()->evaluateScript($javascript);
    $this->assertEmpty($field_value, 'The "' . $field_name . '" field is empty.');
  }

  /**
   * Asserts that two variables are equal.
   *
   * @param string $field_name
   *   The name of the field.
   * @param string $expected
   *   The expected value.
   */
  private function assertEqualsWithJs($field_name, $expected) {
    $javascript = "(function (){ return jQuery('input[name=\"" . $field_name . "\"]').val(); })()";
    $field_value = $this->getSession()->evaluateScript($javascript);
    $this->assertEquals($expected, $field_value, 'The "' . $field_name . '" field has a value of "' . $expected . '".');
  }

  /**
   * Gets an attribute of the first link in the ckeditor editor.
   *
   * @param string $attribute
   *   The attribute name.
   *
   * @return string|null
   *   The attribute, or null if the attribute is not found on the element.
   */
  private function getLinkAttributeFromEditor($attribute) {
    // We can't use $session->switchToIFrame() here, because the iframe does not
    // have a name.
    $javascript = <<<JS
        (function(){
          var iframes = document.getElementsByClassName('cke_wysiwyg_frame');
          if (iframes.length) {
            var doc = iframes[0].contentDocument || iframes[0].contentWindow.document;
            var link = doc.getElementsByTagName('a')[0];
            return link.getAttribute("$attribute");
          }
        })()
JS;
    return $this->getSession()->evaluateScript($javascript);
  }

  /**
   * Gets the text in the link information element.
   *
   * @return string
   *   The text in the link information element.
   */
  private function getLinkInfoText() {
    $javascript = "(function (){ return jQuery('.linkit-link-information > span').text(); })()";
    return $this->getSession()->evaluateScript($javascript);
  }

}
