<-- EDW HealthCheck Client Module -->

This client module needs to be installed on a Drupal website, and when enabled, it will export core and modules
information in a JSON format.

The module can be configured from the settings form, under the 'System' category.

To view the module's output, head to the path "[your_website]/edw_healthcheck" and see the information exported according
to the configuration you have set.(Enable core, modules or themes information)

Auxiliary instructions :
 - In order to use the module with the EDW HealthCheck server monitor, you have to add your own username and password
 into the local settings of your website, so you can supply them to the server monitor, so it can access the HealthCheck
 module externally and receive informations about the status of the modules

 Example : $config['edw_healthcheck.settings']['hc_user'] = 'your_username';
           $config['edw_healthcheck.settings']['hc_password'] = 'your_password';
           

