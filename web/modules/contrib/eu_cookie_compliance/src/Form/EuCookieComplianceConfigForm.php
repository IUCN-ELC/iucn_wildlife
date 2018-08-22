<?php

namespace Drupal\eu_cookie_compliance\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Routing\RequestContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\user\RoleStorageInterface;

/**
 * Provides settings for eu_cookie_compliance module.
 */
class EuCookieComplianceConfigForm extends ConfigFormBase {

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * The role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs an EuCookieComplianceConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PathValidatorInterface $path_validator, RequestContext $request_context, RoleStorageInterface $role_storage, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);

    $this->pathValidator = $path_validator;
    $this->requestContext = $request_context;
    $this->roleStorage = $role_storage;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.validator'),
      $container->get('router.request_context'),
      $container->get('entity.manager')->getStorage('user_role'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eu_cookie_compliance_config_form';
  }

  /**
   * Gets the roles to display in this form.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of role objects.
   */
  protected function getRoles() {
    return $this->roleStorage->loadMultiple();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'eu_cookie_compliance.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('eu_cookie_compliance.settings');

    $default_filter_format = filter_default_format();
    if ($default_filter_format == 'restricted_html') {
      $default_filter_format = 'full_html';
    }

    $form['popup_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable banner'),
      '#default_value' => $config->get('popup_enabled'),
    );

    // List of checkbox values.
    $role_names = [];
    // Permissions per role.
    $role_permissions = [];
    // Which checkboxes should be ticked.
    $role_values = [];

    $perm = 'display eu cookie compliance popup';

    foreach ($this->getRoles() as $role_name => $role) {
      // Exclude Admin roles.
      if (!$role->isAdmin()) {
        $role_names[$role_name] = $role->label();
        // Fetch permissions for the roles.
        $role_permissions[$role_name] = $role->getPermissions();
        // Indicate whether the checkbox should be ticked.
        if (in_array($perm, $role_permissions[$role_name])) {
          $role_values[] = $role_name;
        }
      }
    }

    $form['permissions'] = [
      '#type' => 'details',
      '#title' => $this->t('Permissions'),
      '#open' => TRUE,
    ];

    $form['permissions']['see_the_banner'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Display the banner for'),
      '#options' => $role_names,
      '#default_value' => $role_values,
    ];

    $form['popup_message'] = array(
      '#type' => 'details',
      '#title' => $this->t('Cookie information banner'),
      '#open' => TRUE,
    );

    $form['popup_message']['popup_clicking_confirmation'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Consent by clicking'),
      '#default_value' => $config->get('popup_clicking_confirmation'),
      '#description' => $this->t('By default by clicking any link or button on the website the visitor accepts the cookie policy. Uncheck this box if you don’t require this functionality. You may want to edit the banner message below accordingly.'),
    );

    $form['popup_message']['popup_info'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Cookie information banner message'),
      '#default_value' => $config->get('popup_info.value'),
      '#required' => TRUE,
      '#format' => !empty($config->get('popup_info.format')) ? $config->get('popup_info.format') : $default_filter_format,
    );

    $form['popup_message']['use_mobile_message'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use a different message for mobile phones'),
      '#default_value' => !empty($config->get('use_mobile_message')) ? $config->get('use_mobile_message') : FALSE,
    );

    $form['popup_message']['container'] = array(
      '#type' => 'container',
      '#states' => array('visible' => array('input[name="use_mobile_message"]' => array('checked' => TRUE))),
    );

    $form['popup_message']['container']['mobile_popup_info'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Cookie information banner message - mobile'),
      '#default_value' => $config->get('mobile_popup_info.value'),
      '#required' => FALSE,
      '#format' => !empty($config->get('mobile_popup_info.format')) ? $config->get('mobile_popup_info.format') : $default_filter_format,
    );

    $form['popup_message']['mobile_breakpoint'] = array(
      '#type' => 'number',
      '#title' => $this->t('Mobile breakpoint'),
      '#default_value' => !empty($config->get('mobile_breakpoint')) ? $config->get('mobile_breakpoint') : '768',
      '#field_suffix' => ' ' . $this->t('pixels'),
      '#size' => 4,
      '#maxlength' => 4,
      '#required' => FALSE,
      '#description' => $this->t('The mobile message will be used when the window width is below or equal to the given value.'),
      '#states' => array(
        'visible' => array(
          "input[name='use_mobile_message']" => array('checked' => TRUE),
        ),
      ),
    );

    $form['popup_message']['popup_agree_button_message'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Agree button label'),
      '#default_value' => $config->get('popup_agree_button_message'),
      '#size' => 30,
      '#required' => TRUE,
    );

    $form['popup_message']['disagree_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show “Cookie Policy” and “More info” buttons'),
      '#description' => $this->t('If this option is checked, the cookie policy button will be shown on the site. Disabling this option will hide both the “Cookie Policy” button on the information banner and the “More info” button on the “Thank you” banner.'),
      '#default_value' => $config->get('show_disagree_button'),
    ];

    $form['popup_message']['popup_disagree_button_message'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Cookie policy button label'),
      '#default_value' => $config->get('popup_disagree_button_message'),
      '#size' => 30,
      '#states' => [
        'visible' => [
          'input[name="disagree_button"]' => ['checked' => TRUE],
        ],
        'required' => [
          'input[name="disagree_button"]' => ['checked' => TRUE],
        ],
      ],
    );

    $form['thank_you'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Thank you banner'),
    );

    $form['thank_you']['popup_agreed_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable “Thank you” banner'),
      '#default_value' => $config->get('popup_agreed_enabled'),
    );

    $form['thank_you']['popup_hide_agreed'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Clicking hides “Thank you” banner'),
      '#default_value' => $config->get('popup_hide_agreed'),
      '#description' => $this->t('Clicking a link or button hides the “Thank you” message automatically.'),
    );

    $form['thank_you']['popup_agreed'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('“Thank you” banner message'),
      '#default_value' => !empty($config->get('popup_agreed')['value']) ? $config->get('popup_agreed')['value'] : '',
      '#required' => TRUE,
      '#format' => !empty($config->get('popup_agreed')['format']) ? $config->get('popup_agreed')['format'] : $default_filter_format,
    );

    $form['thank_you']['popup_find_more_button_message'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('More info button label'),
      '#default_value' => $config->get('popup_find_more_button_message'),
      '#size' => 30,
      '#states' => [
        'visible' => [
          'input[name="disagree_button"]' => ['checked' => TRUE],
        ],
        'required' => [
          'input[name="disagree_button"]' => ['checked' => TRUE],
        ],
      ],
    );

    $form['thank_you']['popup_hide_button_message'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Hide button label'),
      '#default_value' => $config->get('popup_hide_button_message'),
      '#size' => 30,
      '#required' => TRUE,
    );

    $form['privacy'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Privacy Policy'),
    );

    $form['privacy']['popup_link'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Privacy policy link'),
      '#default_value' => $config->get('popup_link'),
      '#maxlength' => 1024,
      '#required' => TRUE,
      '#description' => $this->t('Enter link to your privacy policy or other page that will explain cookies to your users, external links should start with http:// or https://.'),
      '#element_validate' => array(array($this, 'validatePopupLink')),
    );

    $form['privacy']['popup_link_new_window'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Open privacy policy link in a new window'),
      '#default_value' => $config->get('popup_link_new_window'),
    );

    $form['appearance'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Appearance'),
    );

    $form_color_picker_type = 'textfield';

    if ($this->moduleHandler->moduleExists('jquery_colorpicker')) {
      $form_color_picker_type = 'jquery_colorpicker';
    }

    $popup_position_options = [
      'bottom' => 'Bottom',
      'top' => 'Top',
    ];

    $popup_position_value = ($config->get('popup_position') === TRUE ? 'top' : ($config->get('popup_position') === FALSE ? 'bottom' : $config->get('popup_position')));

    $form['appearance']['popup_position'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Position'),
      '#default_value' => $popup_position_value,
      '#options' => $popup_position_options,
    );

    $form['appearance']['use_bare_css'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Include minimal CSS, I want to style the banner in the theme CSS.'),
      '#default_value' => !empty($config->get('use_bare_css')) ? $config->get('use_bare_css') : 0,
      '#description' => $this->t('This may be useful if you want the banner to share the button style of your theme. Note that you will have to configure values like the banner width, text color and background color in your CSS file.'),
    );

    $form['appearance']['popup_text_hex'] = array(
      '#type' => $form_color_picker_type,
      '#title' => $this->t('Text color'),
      '#default_value' => $config->get('popup_text_hex'),
      '#description' => $this->t('Change the text color of the banner. Provide HEX value without the #.'),
      '#element_validate' => array('eu_cookie_compliance_validate_hex'),
      '#states' => array(
        'visible' => array(
          "input[name='use_bare_css']" => array('checked' => FALSE),
        ),
      ),
    );

    $form['appearance']['popup_bg_hex'] = array(
      '#type' => $form_color_picker_type,
      '#title' => $this->t('Background color'),
      '#default_value' => $config->get('popup_bg_hex'),
      '#description' => $this->t('Change the background color of the banner. Provide HEX value without the #.'),
      '#element_validate' => array('eu_cookie_compliance_validate_hex'),
      '#states' => array(
        'visible' => array(
          "input[name='use_bare_css']" => array('checked' => FALSE),
        ),
      ),
    );

    $form['appearance']['popup_height'] = array(
      '#type' => 'number',
      '#title' => $this->t('Banner height in pixels'),
      '#default_value' => !empty($config->get('popup_height')) ? $config->get('popup_height') : '',
      '#field_suffix' => ' ' . $this->t('pixels'),
      '#size' => 5,
      '#maxlength' => 5,
      '#required' => FALSE,
      '#description' => $this->t('Enter an integer value for a desired height in pixels or leave empty for automatically adjusted height.'),
      '#states' => array(
        'visible' => array(
          "input[name='use_bare_css']" => array('checked' => FALSE),
        ),
      ),
    );

    $form['appearance']['popup_width'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Banner width in pixels or a percentage value'),
      '#default_value' => $config->get('popup_width'),
      '#field_suffix' => ' ' . $this->t('px or %'),
      '#size' => 5,
      '#maxlength' => 5,
      '#description' => $this->t('Set the width of the banner. This can be either an integer value or percentage of the screen width. For example: 200 or 50%.'),
      '#states' => array(
        'visible' => array("input[name='use_bare_css']" => array('checked' => FALSE)),
        'required' => array("input[name='use_bare_css']" => array('checked' => FALSE)),
      ),
    );

    $form['eu_only'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('EU countries'),
    );

    if ($this->moduleHandler->moduleExists('smart_ip') || extension_loaded('geoip')) {
      $form['eu_only']['eu_only'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Only display banner in EU countries'),
        '#default_value' => !empty($config->get('eu_only')) ? $config->get('eu_only') : 0,
        '#description' => $this->t('You can limit the number of countries for which the banner is displayed by checking this option. If you want to provide a list of countries other than current EU states, you may place an array in <code>$config[\'eu_cookie_compliance.settings\'][\'eu_countries\']</code> in your <code>settings.php</code> file. Using the <a href="http://drupal.org/project/smart_ip">smart_ip</a> module or the <a href="http://www.php.net/manual/en/function.geoip-country-code-by-name.php">geoip_country_code_by_name()</a> PHP function.'),
      );
      $form['eu_only']['eu_only_js'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('JavaScript-based (for Varnish): Only display banner in EU countries'),
        '#default_value' => !empty($config->get('eu_only_js')) ? $config->get('eu_only_js') : 0,
        '#description' => $this->t('This option also works for visitors that bypass Varnish. You can limit the number of countries for which the banner is displayed by checking this option. If you want to provide a list of countries other than current EU states, you may place an array in <code>$config[\'eu_cookie_compliance.settings\'][\'eu_countries\']</code> in your <code>settings.php</code> file. Using the <a href="http://drupal.org/project/smart_ip">smart_ip</a> module or the <a href="http://www.php.net/manual/en/function.geoip-country-code-by-name.php">geoip_country_code_by_name()</a> PHP function.'),
      );
    }
    else {
      $form['eu_only']['info'] = array(
        '#markup' => t('You can choose to show the banner only to visitors from EU countries. In order to achieve this, you need to install the <a href="http://drupal.org/project/smart_ip">smart_ip</a> module or enable the <a href="http://www.php.net/manual/en/function.geoip-country-code-by-name.php">geoip_country_code_by_name()</a> PHP function.'),
      );
    }

    $form['advanced'] = array(
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Advanced'),
    );

    $form['advanced']['fixed_top_position'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('If the banner is at the top, don’t scroll the banner with the page'),
      '#default_value' => $config->get('fixed_top_position'),
      '#description' => $this->t('Use position:fixed for the banner when displayed at the top.'),
    );

    $form['advanced']['popup_delay'] = array(
      '#type' => 'number',
      '#title' => $this->t('Banner sliding animation time in milliseconds'),
      '#default_value' => $config->get('popup_delay'),
      '#field_suffix' => ' ' . $this->t('milliseconds'),
      '#size' => 5,
      '#maxlength' => 5,
      '#required' => TRUE,
    );

    $form['advanced']['disagree_do_not_show_popup'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Don’t show cookie policy when the user clicks the “Cookie Policy” button.'),
      '#default_value' => !empty($config->get('disagree_do_not_show_popup')) ? $config->get('disagree_do_not_show_popup') : 0,
      '#description' => $this->t('Enabling this will make it possible to record the fact that the user disagrees without the user having to see the privacy policy.'),
    );

    $form['advanced']['reload_page'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Reload page after user clicks the “Agree” button.'),
      '#default_value' => !empty($config->get('reload_page')) ? $config->get('reload_page') : 0,
    );

    $form['advanced']['popup_scrolling_confirmation'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Consent by scrolling'),
      '#default_value' => $config->get('popup_scrolling_confirmation'),
      '#description' => $this->t('Scrolling makes the visitors to accept the cookie policy. In some countries, like Italy, it is permitted.'),
    );

    $form['advanced']['cookie_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Cookie name'),
      '#default_value' => !empty($config->get('cookie_name')) ? $config->get('cookie_name') : '',
      '#description' => $this->t('Sets the cookie name that is used to check whether the user has agreed or not.  This option is useful when policies change and the user needs to agree again.'),
    );

    // Adding option to add/remove banner on specified domains.
    $exclude_domains_option_active = array(
      0 => $this->t('Add'),
      1 => $this->t('Remove'),
    );

    $form['advanced']['domains_option'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Add/remove banner on specified domains'),
      '#default_value' => $config->get('domains_option'),
      '#options' => $exclude_domains_option_active,
      '#description' => $this->t('Specify if you want to add or remove banner on the listed below domains.'),
    );

    $form['advanced']['domains_list'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Domains list'),
      '#default_value' => $config->get('domains_list'),
      '#description' => $this->t('Specify domains with protocol (e.g., http or https). Enter one domain per line.'),
    );

    $form['advanced']['exclude_paths'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Exclude paths'),
      '#default_value' => !empty($config->get('exclude_paths')) ? $config->get('exclude_paths') : '',
      '#description' => $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array(
        '%blog' => '/blog',
        '%blog-wildcard' => '/blog/*',
        '%front' => '<front>',
      )),
    );

    $form['advanced']['exclude_admin_theme'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude admin pages'),
      '#default_value' => $config->get('exclude_admin_theme'),
    );

    $form['advanced']['exclude_uid_1'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Don’t show the banner for UID 1.'),
      '#default_value' => !empty($config->get('exclude_uid_1')) ? $config->get('exclude_uid_1') : 0,
    );

    $form['advanced']['better_support_for_screen_readers'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Let screen readers see the banner before other links on the page.'),
      '#default_value' => !empty($config->get('better_support_for_screen_readers')) ? $config->get('better_support_for_screen_readers') : 0,
      '#description' => $this->t('Enable this if you want to place the banner as the first HTML element on the page. This will make it possible for screen readers to close the banner without tabbing through all links on the page.'),
    );

    $form['advanced']['domain'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Domain'),
      '#default_value' => $config->get('domain'),
      '#description' => $this->t('Sets the domain of the cookie to a specific url. Used when you need consistency across domains. This is language independent. Note: Make sure you actually enter a domain that the browser can make use of. For example if your site is accessible at both www.domain.com and domain.com, you will not be able to hide the banner at domain.com if your value for this field is www.domain.com.'),
    );

    $form['advanced']['cookie_lifetime'] = array(
      '#type' => 'number',
      '#title' => $this->t('Cookie lifetime'),
      '#description' => $this->t("How long does the system remember the user's choice, in days."),
      '#default_value' => $config->get('cookie_lifetime'),
      '#field_suffix' => ' ' . $this->t('days'),
      '#size' => 5,
      '#maxlength' => 5,
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Clear values if we are using minimal css.
    if ($form_state->getValue('use_bare_css')) {
      $form_state->setValue('popup_bg_hex', '');
      $form_state->setValue('popup_text_hex', '');
      $form_state->setValue('popup_height', '');
      $form_state->setValue('popup_width', '');
    }

    // If there's no mobile message entered, disable the feature.
    if (trim($form_state->getValue('mobile_popup_info')['value']) == '') {
      $form_state->setValue('use_mobile_message', FALSE);
    }

    if ($form_state->getValue('popup_link') == '<front>' && $form_state->getValue('disagree_button')) {
      drupal_set_message($this->t('Your privacy policy link is pointing at the front page. This is the default value after installation, and unless your privacy policy is actually posted at the front page, you will need to create a separate page for the privacy policy and link to that page.'), 'error');
    }

    // Save permissions.
    $permission_name = 'display eu cookie compliance popup';

    foreach ($this->getRoles() as $role_name => $role) {
      if (!$role->isAdmin()) {
        if (array_key_exists($role_name, $form_state->getValue('see_the_banner')) && $form_state->getValue('see_the_banner')[$role_name]) {
          user_role_grant_permissions($role_name, [$permission_name]);
        }
        else {
          user_role_revoke_permissions($role_name, [$permission_name]);
        }
      }
    }

    // Handle legacy settings for popup_position:
    if ($form_state->getValue('popup_position') == 'top') {
      $form_state->setValue('popup_position', TRUE);
    }
    elseif ($form_state->getValue('popup_position') == 'bottom') {
      $form_state->setValue('popup_position', FALSE);
    }

    // Save settings.
    $this->config('eu_cookie_compliance.settings')
      ->set('domain', $form_state->getValue('domain'))
      ->set('popup_enabled', $form_state->getValue('popup_enabled'))
      ->set('popup_clicking_confirmation', $form_state->getValue('popup_clicking_confirmation'))
      ->set('popup_scrolling_confirmation', $form_state->getValue('popup_scrolling_confirmation'))
      ->set('popup_position', $form_state->getValue('popup_position'))
      ->set('popup_agree_button_message', $form_state->getValue('popup_agree_button_message'))
      ->set('show_disagree_button', $form_state->getValue('disagree_button'))
      ->set('popup_disagree_button_message', $form_state->getValue('popup_disagree_button_message'))
      ->set('popup_info', $form_state->getValue('popup_info'))
      ->set('use_mobile_message', $form_state->getValue('use_mobile_message'))
      ->set('mobile_popup_info', $form_state->getValue('use_mobile_message') ? $form_state->getValue('mobile_popup_info') : array('value' => '', 'format' => filter_default_format()))
      ->set('mobile_breakpoint', $form_state->getValue('mobile_breakpoint'))
      ->set('popup_agreed_enabled', $form_state->getValue('popup_agreed_enabled'))
      ->set('popup_hide_agreed', $form_state->getValue('popup_hide_agreed'))
      ->set('popup_find_more_button_message', $form_state->getValue('popup_find_more_button_message'))
      ->set('popup_hide_button_message', $form_state->getValue('popup_hide_button_message'))
      ->set('popup_agreed', $form_state->getValue('popup_agreed'))
      ->set('popup_link', $form_state->getValue('popup_link'))
      ->set('popup_link_new_window', $form_state->getValue('popup_link_new_window'))
      ->set('popup_height', $form_state->getValue('popup_height'))
      ->set('popup_width', $form_state->getValue('popup_width'))
      ->set('popup_delay', $form_state->getValue('popup_delay'))
      ->set('popup_bg_hex', $form_state->getValue('popup_bg_hex'))
      ->set('popup_text_hex', $form_state->getValue('popup_text_hex'))
      ->set('domains_option', $form_state->getValue('domains_option'))
      ->set('domains_list', $form_state->getValue('domains_list'))
      ->set('exclude_paths', $form_state->getValue('exclude_paths'))
      ->set('exclude_admin_theme', $form_state->getValue('exclude_admin_theme'))
      ->set('cookie_lifetime', $form_state->getValue('cookie_lifetime'))
      ->set('eu_only', $form_state->getValue('eu_only'))
      ->set('eu_only_js', $form_state->getValue('eu_only_js'))
      ->set('use_bare_css', $form_state->getValue('use_bare_css'))
      ->set('disagree_do_not_show_popup', $form_state->getValue('disagree_do_not_show_popup'))
      ->set('reload_page', $form_state->getValue('reload_page'))
      ->set('cookie_name', $form_state->getValue('cookie_name'))
      ->set('exclude_uid_1', $form_state->getValue('exclude_uid_1'))
      ->set('better_support_for_screen_readers', $form_state->getValue('better_support_for_screen_readers'))
      ->set('fixed_top_position', $form_state->getValue('fixed_top_position'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Validates the banner link field.
   *
   * @param array $element
   *   Element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function validatePopupLink($element, FormStateInterface &$form_state) {
    if (empty($element['#value'])) {
      return;
    }

    $input = $element['#value'];
    if (UrlHelper::isExternal($input)) {
      $allowed_protocols = ['http', 'https'];
      if (!in_array(parse_url($input, PHP_URL_SCHEME), $allowed_protocols)) {
        $form_state->setError($element, $this->t('Invalid protocol specified for the %name (valid protocols: %protocols).', array(
          '%name' => $element['#title'],
          '%protocols' => implode(', ', $allowed_protocols),
        )));
      }
      else {
        try {
          Url::fromUri($input);
        }
        catch (\Exception $exc) {
          $form_state->setError($element, $this->t('Invalid %name (:message).', array('%name' => $element['#title'], ':message' => $exc->getMessage())));
        }
      }
    }
    else {
      // Special case for '<front>'.
      if ($input === '<front>') {
        $input = '/';
      }
      try {
        Url::fromUserInput($input);
      }
      catch (\Exception $exc) {
        $form_state->setError($element, $this->t('Invalid URL in %name field (:message).', array('%name' => $element['#title'], ':message' => $exc->getMessage())));
      }
    }
  }

}
