<?php
namespace Pulsestorm\Magento2\Cli\Generate\Install;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

/**
* Generates Magento 2 install
*
* Wrapped by magento:doo:baz version of command
*
* @command generate_install
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
    //$composer_package .= '=2.1.0-rc1';
    extract($argv);
    
    $db_name = preg_replace('%[^a-zA-Z0-9]%','_', $id_key);
    $url     = preg_replace('%[^a-zA-Z0-9]%','-', $id_key) . '.dev';
    $cmds = [];
    $cmds[] = "composer create-project --repository-url=$repo $composer_package $folder";
    $cmds[] = "cd $folder";
    $cmds[] = "echo '$umask' >> magento_umask";
    $cmds[] = "echo \"We're about to ask for your MySQL password so we can create the database\"";
    $cmds[] = "echo 'CREATE DATABASE $db_name' | mysql -uroot -p";

    $cmds[] = "php bin/magento setup:install --admin-email $admin_email --admin-firstname $admin_first_name --admin-lastname $admin_last_name --admin-password $admin_password --admin-user $admin_user --backend-frontname admin --base-url http://$url --db-host 127.0.0.1 --db-name $db_name --db-password $db_pass --db-user $db_user --session-save files --use-rewrites 1 --use-secure 0 -vvv";    
    $cmds[] = 'php bin/magento sampledata:deploy';    
    $cmds[] = 'php bin/magento cache:enable';
    
    array_map(function($cmd){
        output($cmd);
    }, $cmds);
}

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}