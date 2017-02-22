<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Full_Module;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Magento2\Generate\Ui\Form\createShortPluralModelName');

function getShellScript($argv)
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

    $pathModule = 'app/code/'.$packageName . '/' . $moduleName;        
    return '
#!/bin/bash
pestle.phar magento2:generate:module ' . $packageName . ' ' . $moduleName . ' 0.0.1
pestle.phar generate_crud_model ' . $packageName . '_' . $moduleName . ' ' . $modelName . '
pestle.phar magento2:generate:acl ' . $packageName . '_' . $moduleName . ' ' . $packageName . '_' . $moduleName . '::' . $modelNamePluralLowerCase . '
pestle.phar magento2:generate:menu ' . $packageName . '_' . $moduleName . ' "" ' . $packageName . '_' . $moduleName . '::' . $modelNamePluralLowerCase . ' ' . $packageName . '_' . $moduleName . '::' . $modelNamePluralLowerCase . ' "' . $moduleName . ' ' . $modelNamePlural . '" ' . $packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . '/index/index 10
pestle.phar magento2:generate:menu ' . $packageName . '_' . $moduleName . ' ' . $packageName . '_' . $moduleName . '::' . $modelNamePluralLowerCase . ' ' . $packageName . '_' . $moduleName . '::' . $modelNamePluralLowerCase . '_list ' . $packageName . '_' . $moduleName . '::' . $modelNamePluralLowerCase . ' "' . $modelName . ' Objects" ' . $packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . '/index/index 10
pestle.phar generate_route ' . $packageName . '_' . $moduleName . ' adminhtml ' . $packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . '    
pestle.phar generate_view ' . $packageName . '_' . $moduleName . ' adminhtml ' . $packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . '_index_index Main content.phtml 1column
pestle.phar magento2:generate:ui:grid ' . $packageName . '_' . $moduleName . ' ' . $packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . ' \'' . $packageName . '\\' . $moduleName . '\Model\ResourceModel\\' . $modelName . '\Collection\' ' . $packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNameLowerCase . '_id
pestle.phar magento2:generate:ui:form ' . $packageName . '_' . $moduleName . ' \'' . $packageName . '\\' . $moduleName . '\Model\\' . $modelName . '\' ' . $packageName . '_' . $moduleName . '::' . $modelNamePluralLowerCase . '

pestle.phar magento2:generate:ui:add_to_layout '    . $pathModule.'/view/adminhtml/layout/'.$packageNameLowerCase . '_' . $moduleNameLowerCase.'_'.$modelNamePluralLowerCase.'_index_index.xml content ' . $packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . '
pestle.phar magento2:generate:acl:change_title '    . $pathModule.'/etc/acl.xml '.$packageName.'_'.$moduleName.'::'.$modelNamePluralLowerCase.' "Manage '.$modelNamePluralLowerCase.'"
pestle.phar magento2:generate:controller_edit_acl ' . $pathModule.'/Controller/Adminhtml/Index/Index.php ' . $packageName.'_'.$moduleName.'::'.$modelNamePluralLowerCase . '

pestle.phar magento2:generate:remove-named-node '   . $pathModule . '/view/adminhtml/layout/'.$packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . '_index_index.xml block '.$packageNameLowerCase . '_' . $moduleNameLowerCase.'_block_main

php bin/magento module:enable '.$packageName . '_' . $moduleName.'
php bin/magento setup:upgrade

';    

}

function replaceTemplateVars($template, $argv)
{
    return $template;
}

/**
* Creates shell script with all pestle commands needed for full module output
*
* @command magento2:generate:full_module
* @argument package_name Package Name? [Pulsestorm]
* @argument module_name Module Name? [Helloworld]
* @argument model_name One Word Model Name? [Thing]
*/
function pestle_cli($argv)
{
    $script = getShellScript($argv);
    output($script);
}
