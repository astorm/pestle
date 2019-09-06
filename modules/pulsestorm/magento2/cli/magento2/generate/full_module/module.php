<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Full_Module;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Magento2\Generate\Ui\Form\createShortPluralModelName');
pestle_import('Pulsestorm\Magento2\Cli\Library\getAppCodePath');

function pharString($commandName, $pharName)
{
    return $pharName . ' ' . $commandName . ' ';
}

function getShellScript($argv, $options)
{
    $packageName     = $argv['package_name'];//'Pulsestorm5';
    $moduleName      = $argv['module_name'];//'Pestleform5';
    $modelName       = $argv['model_name'];//'Thing5';
    $modelNamePlural = createShortPluralModelName(implode('\\',
        [$packageName, $moduleName, 'Model',$modelName]));

    $modelNamePluralLowerCase = strToLower($modelNamePlural);
    $packageNameLowerCase     = strToLower($packageName);
    $moduleNameLowerCase      = strToLower($moduleName);
    $modelNameLowerCase       = strToLower($modelName);
    $modelNamePluralLowerCase = strToLower($modelNamePlural);
    $fullModuleName           = $packageName . '_' . $moduleName;


    $pharName = 'pestle.phar';
    if(array_key_exists('with-phar-name', $options) && $options['with-phar-name'])
    {
        $pharName = 'pestle_dev';
    }

    $pathModule = getAppCodePath() . '/'.$packageName . '/' . $moduleName;
    $script = '
#!/bin/bash
' . pharString('magento2:generate:module',$pharName)              . $packageName . ' ' . $moduleName . ' 0.0.1
' . pharString('magento2:generate:crud-model',$pharName)                   . $fullModuleName . ' ' . $modelName . '
' . pharString('magento2:generate:acl',$pharName)                 . $fullModuleName . ' ' . $fullModuleName . '::' . $modelNamePluralLowerCase . '
' . pharString('magento2:generate:menu',$pharName)                . $fullModuleName . ' "" ' . $fullModuleName . '::' . $modelNamePluralLowerCase . ' ' . $fullModuleName . '::' . $modelNamePluralLowerCase . ' "' . $moduleName . ' ' . $modelNamePlural . '" ' . $packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . '/index/index 10
' . pharString('magento2:generate:menu',$pharName)                . $fullModuleName . ' ' . $fullModuleName . '::' . $modelNamePluralLowerCase . ' ' . $fullModuleName . '::' . $modelNamePluralLowerCase . '_list ' . $fullModuleName . '::' . $modelNamePluralLowerCase . ' "' . $modelName . ' Objects" ' . $packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . '/index/index 10
' . pharString('magento2:generate:route',$pharName)                 . $fullModuleName . ' adminhtml ' . $packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . ' Index Index
' . pharString('magento2:generate:view',$pharName)                  . $fullModuleName . ' adminhtml ' . $packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . '_index_index Main content.phtml 1column
' . pharString('magento2:generate:ui:grid',$pharName)             . $fullModuleName . ' ' . $packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . ' \'' . $packageName . '\\' . $moduleName . '\Model\ResourceModel\\' . $modelName . '\Collection\' ' . $modelNameLowerCase . '_id
' . pharString('magento2:generate:ui:add-column-text',$pharName)  . $pathModule . '/view/adminhtml/ui_component/' . $packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . '.xml title "Title"
' . pharString('magento2:generate:ui:form',$pharName)             . $fullModuleName . ' \'' . $packageName . '\\' . $moduleName . '\Model\\' . $modelName . '\' ' . $fullModuleName . '::' . $modelNamePluralLowerCase . '
' . pharString('magento2:generate:ui:add_to_layout',$pharName)    . $pathModule . '/view/adminhtml/layout/'.$packageNameLowerCase . '_' . $moduleNameLowerCase.'_'.$modelNamePluralLowerCase.'_index_index.xml content ' . $packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . '
' . pharString('magento2:generate:acl:change_title',$pharName)    . $pathModule . '/etc/acl.xml '.$packageName.'_'.$moduleName.'::'.$modelNamePluralLowerCase.' "Manage '.$modelNamePluralLowerCase.'"
' . pharString('magento2:generate:controller_edit_acl',$pharName) . $pathModule . '/Controller/Adminhtml/Index/Index.php ' . $packageName.'_'.$moduleName.'::'.$modelNamePluralLowerCase . '
' . pharString('magento2:generate:remove-named-node',$pharName)   . $pathModule . '/view/adminhtml/layout/'.$packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . '_index_index.xml block '.$packageNameLowerCase . '_' . $moduleNameLowerCase.'_block_main

php bin/magento module:enable '.$fullModuleName.'
';

    if(!is_null($options['with-setup-upgrade']))
    {
        $script .= '
php bin/magento setup:upgrade
';
    }
    return $script;

}

function replaceTemplateVars($template, $argv)
{
    return $template;
}

/**
* Creates shell script with all pestle commands needed for full module output
*
* @command magento2:generate:full-module
* @argument package_name Package Name? [Pulsestorm]
* @argument module_name Module Name? [Helloworld]
* @argument model_name One Word Model Name? [Thing]
* @option with-phar-name Change pestle.phar to something like pestle_dev
* @option with-setup-upgrade Add Setup Upgrade Call?
*/
function pestle_cli($argv, $options)
{
    $script = getShellScript($argv, $options);
    output($script);
}
