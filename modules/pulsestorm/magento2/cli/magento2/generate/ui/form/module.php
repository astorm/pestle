<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Ui\Form;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\getModuleInformation');
pestle_import('Pulsestorm\Cli\Code_Generation\createClassTemplateWithUse');
pestle_import('Pulsestorm\Magento2\Cli\Library\createClassFile');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
function getModelShortName($module_fullname, $model_class)
{
    $regex = '%^' . $module_fullname . '_Model_%six';
    return preg_replace($regex, '', $model_class);
}

function createControllerFiles($module_info)
{
    output('@TODO: generate controller contents');
    // $moduleBasePath = getModuleBasePath();
    $prefix = $module_info->vendor . '\\' . $module_info->short_name;
    $classes = [
        'controllerEditClassname' => $prefix . '\Controller\Adminhtml\Index\Edit',
        'controllerNewClassName'  => $prefix . '\Controller\Adminhtml\Index\NewAction',
        'controllerSaveClassName' => $prefix . '\Controller\Adminhtml\Index\Save'
    ];
    foreach($classes as $desc=>$className)
    {
        $contents           = createClassTemplateWithUse($className, '\Magento\Backend\App\Action');
        output("Creating: $className");
        $return             = createClassFile($className,$contents);        
    }
}

function createDataProvider($module_info, $modelClass)
{
    output('@TODO: generate data provider class contents');
    // $moduleBasePath = getModuleBasePath();
    var_dump($modelClass);
    $dataProviderClassName = $modelClass . '\DataProvider';
    $contents           = createClassTemplateWithUse($dataProviderClassName, '\Magento\Ui\DataProvider\AbstractDataProvider');
    output("Creating: $dataProviderClassName");
    $return             = createClassFile($dataProviderClassName,$contents);        
    
}

function createShortPluralModelName($modelClass)
{
    $parts = [];
    $flag  = false;
    foreach(explode('\\', $modelClass) as $part)
    { 
        if($part === 'Model')
        {
            $flag = true;
            continue;
        }
        if(!$flag) { continue;}
        $parts[] = $part;
    }          
          
    $parts = array_map('strToLower', $parts);
    $name  = implode('_', $parts);
    
    if(preg_match('%ly$%',$name))
    {
        $name = preg_replace('%ly$%', 'lies',$name);
    }
    else
    {
        $name = $name . 's';
    }
    return $name;
}

function createEmptyXmlTree()
{
    $xml = simplexml_load_string(
        '<page  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd"></page>');
    return $xml;
}

function createLayoutXmlFiles($module_info, $modelClass)
{
    $moduleBasePath = $module_info->folder;
    $layoutBasePath = $moduleBasePath . '/view/adminhtml/layout'; 
     
    $xml = createEmptyXmlTree();
    
    $prefixFilename = implode('_', [
        strToLower($module_info->name),
        createShortPluralModelName($modelClass),
        'index'
    ]);;
    
    $names = ['edit', 'new', 'save' ];
    
    foreach($names as $name)
    {
        $fileName = $layoutBasePath . '/' . $prefixFilename . '_' . $name . '.xml';
        output("Creating $fileName");
        writeStringToFile($fileName, $xml->asXml());
    }
    
    output('@TODO: Do something about /index/ in URLs, handles');
    output('@TODO: Contents of layout xml files. ');
}

function createUiComponentXmlFile($module_info, $modelClass)
{
    $moduleBasePath      = $module_info->folder;
    $uiComponentBasePath = $moduleBasePath . '/view/adminhtml/ui_component'; 
    $uiComponentFilePath = $uiComponentBasePath . '/' . implode('_', [
        strToLower($module_info->name),
        createShortPluralModelName($modelClass),
        'form'
    ]) . '.xml';
    
    $xml = createEmptyXmlTree();
    
    writeStringToFile($uiComponentFilePath, $xml->asXml());
 
    output("@TODO: Contents of UI Component File");
}
/**
* One Line Description
*
* @command magento2:generate:ui:form
* @argument module Which Module? [Pulsestorm_Formexample]
* @argument model Model Class? [Pulsestorm\Formexample\Model\Thing]
*/
function pestle_cli($argv)
{
    $module_info      = getModuleInformation($argv['module']);

    output("In Progress");

    // $package_name           = 'Pulsestorm';
    // $module_name            = 'Pestleform';
    // $module_fullname        = $package_name . '_' . $module_name;
    // $module_fullname_lower  = strToLower($module_fullname);    
    // $model_class            = $module_fullname . '_Model_Reply';
    // $model_short_name       = getModelShortName($module_fullname, $model_class);

    createControllerFiles($module_info);
    createDataProvider($module_info, $argv['model']);
    createLayoutXmlFiles($module_info, $argv['model']);
    createUiComponentXmlFile($module_info, $argv['model']);    
    
}
