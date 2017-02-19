<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Install;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Generate\Install\exported_pestle_cli');

/**
* BETA: Generates commands to install Magento via composer
*
* @command magento2:generate:install
* @argument id_key Identity Key? [magento_2_new]
* @argument umask Default Umask? [000]
* @argument repo Composer Repo [https://repo.magento.com/]
* @argument composer_package Starting Package? [magento/project-community-edition]
* @argument folder Folder? [magento-2-source]
* @argument admin_first_name Admin First Name? [Alan]
* @argument admin_last_name Admin Last Name? [Storm]
* @argument admin_password Admin Password? [password12345]
* @argument admin_email Admin Email? [astorm@alanstorm.com]
* @argument admin_user Admin Username? [astorm@alanstorm.com]
* @argument db_host Database Host? [127.0.0.1]
* @argument db_user Database User? [root]
* @argument db_pass Database Password? [password12345]
* @argument email Admin Email? [astorm@alanstorm.com]
*/
function pestle_cli($argv)
{
    return exported_pestle_cli($argv);
}
