<?php
namespace Pulsestorm\Magento2\Cli\Generate\Crud\Model;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\getModuleInformation');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Cli\Code_Generation\createClassTemplate');
pestle_import('Pulsestorm\Cli\Code_Generation\templateInterface');
pestle_import('Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass');

define('BASE_COLLECTION_CLASS'  , '\Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection');
define('BASE_RESOURCE_CLASS'    , '\Magento\Framework\Model\ResourceModel\Db\AbstractDb');
define('BASE_MODEL_CLASS'       , '\Magento\Framework\Model\AbstractModel');

function getCollectionClassNameFromModuleInfo($module_info, $model_name)
{
    return $module_info->vendor . '\\' . $module_info->short_name . 
        '\Model\ResourceModel\\' . $model_name . '\Collection';
}

function getResourceModelClassNameFromModuleInfo($module_info, $model_name)
{
    return $module_info->vendor . '\\' . $module_info->short_name . 
        '\Model\ResourceModel\\' . $model_name;
}

function getModelClassNameFromModuleInfo($module_info, $model_name)
{
    return $module_info->vendor . '\\' . $module_info->short_name . 
        '\Model\\' . $model_name;
}


function templateConstruct($init1=false, $init2=false)
{
    $params = array_filter([$init1, $init2]);
    $params = "'" . implode("','",$params) . "'";
    
    return "\n" . '    protected function _construct()' . "\n" .
    '    {' . "\n" .
    '        $this->_init('.$params.');' . "\n" .
    '    }' . "\n";
}

function createCollectionClass($module_info, $model_name)
{
    $path                   = $module_info->folder . "/Model/ResourceModel/$model_name/Collection.php";
    $class_collection       = getCollectionClassNameFromModuleInfo($module_info, $model_name);
    $class_model            = getModelClassNameFromModuleInfo($module_info, $model_name);
    $class_resource         = getResourceModelClassNameFromModuleInfo($module_info, $model_name);
            
    $template               = createClassTemplate($class_collection, BASE_COLLECTION_CLASS);
    $construct              = templateConstruct($class_model, $class_resource);

    $class_contents         = str_replace('<$body$>', $construct, $template);
    output("Creating:" . $path);
    writeStringToFile($path, $class_contents);
}

function createResourceModelClass($module_info, $model_name)
{
    $path = $module_info->folder . "/Model/ResourceModel/$model_name.php";
    $db_table               = strToLower($module_info->name . '_' . $model_name);
    $db_id                  = strToLower($model_name) . '_id';
    $class_resource         = getResourceModelClassNameFromModuleInfo($module_info, $model_name);
    $template               = createClassTemplate($class_resource, BASE_RESOURCE_CLASS);    
    $construct              = templateConstruct($db_table, $db_id);
    $class_contents         = str_replace('<$body$>', $construct, $template);    
    output("Creating:" . $path);    
    writeStringToFile($path, $class_contents);    
}

function templateGetIdentities()
{
    return "\n" . '    public function getIdentities()
    {
        return [self::CACHE_TAG . \'_\' . $this->getId()];
    }' . "\n";
}

function templateCacheTag($tag_name)
{
    return "\n    const CACHE_TAG = '$tag_name';\n";
}

function getModelInterfaceShortName($model_name)
{
    return $model_name . 'Interface';
}

function createModelClass($module_info, $model_name)
{
    $path = $module_info->folder . "/Model/$model_name.php";
    $cache_tag           = strToLower($module_info->name . '_' . $model_name);
    $class_model         = getModelClassNameFromModuleInfo($module_info, $model_name);
    $class_resource      = getResourceModelClassNameFromModuleInfo($module_info, $model_name);
    $implements          = getModelInterfaceShortName($model_name) . ', IdentityInterface';
    $template            = createClassTemplate($class_model, BASE_MODEL_CLASS, $implements);    
    $construct           = templateConstruct($class_resource);
    
    $body                = 
        templateCacheTag($cache_tag)    .      
        $construct                      .
        templateGetIdentities();

    $class_contents      = str_replace('<$body$>', $body, $template);    
    output("Creating:" . $path);    
    writeStringToFile($path, $class_contents);    
}

function getModelInterfaceName($module_info, $model_name)
{
    return $module_info->vendor . '\\' . $module_info->short_name . 
        '\Model\\' . getModelInterfaceShortName($model_name);
}

function createModelInterface($module_info, $model_name)
{
    $interface = getModelInterfaceName($module_info, $model_name);
    $path      = getPathFromClass($interface);
    $contents  = templateInterface($interface,[]);    
    writeStringToFile($path, $contents);
    output("Creating: $path");
}

function createSchemaClass($module_info, $model_name)
{
    $path = $module_info->folder . "/Setup/InstallData.php";
    output("Creating or Editing:" . $path);    
}

function createDataClass($module_info, $model_name)
{
    $path = $module_info->folder . "/Setup/InstallSchema.php";
    output("Creating or Editing:" . $path);    
}

/**
* One Line Description
*
* @command generate_crud_model
* @argument module_name Which module? [Pulsestorm_HelloGenerate]
* @argument model_name  What model name? [Thing]
*/
function pestle_cli($argv)
{
    $module_name = $argv['module_name'];
    $module_info = getModuleInformation($argv['module_name']);    
    $model_name  = $argv['model_name'];
    
    createModelInterface($module_info, $model_name);
    createCollectionClass($module_info, $model_name);
    createResourceModelClass($module_info, $model_name);
    createModelClass($module_info, $model_name);
    
    
    createSchemaClass($module_info, $model_name);
    createDataClass($module_info, $model_name);


}
