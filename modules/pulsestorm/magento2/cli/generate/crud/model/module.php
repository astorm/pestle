<?php
namespace Pulsestorm\Magento2\Cli\Generate\Crud\Model;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\getModuleInformation');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Cli\Code_Generation\createClassTemplate');
pestle_import('Pulsestorm\Cli\Code_Generation\templateInterface');
pestle_import('Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass');
pestle_import('Pulsestorm\Cli\Code_Generation\generateInstallSchemaTable');

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

function templateInstallDataFunction()
{
    return "\n" . '    public function install(\Magento\Framework\Setup\ModuleDataSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        //install data here
    }' . "\n";
}

function templateInstallFunction()
{
    return "\n" . '    public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        //START: install stuff
        //END:   install stuff
        $installer->endSetup();
    }' . "\n";
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

function templateRepositoryInterfaceAbstractFunction($modelShortInterface)
{
    return "
    public function save({$modelShortInterface} \$page);

    public function getById(\$id);

    public function getList(SearchCriteriaInterface \$criteria);

    public function delete({$modelShortInterface} \$page);

    public function deleteById(\$id);    
";    
}

function templateRepositoryInterfaceUse($longModelInterfaceName)
{
    return "
use {$longModelInterfaceName};
use Magento\Framework\Api\SearchCriteriaInterface;
";
}

function templateComplexInterface($useContents, $methodContents, $interfaceContents)
{
    $interfaceContents = preg_replace(
        '%(namespace.+?;)%',
        '$1' . "\n" . $useContents,
        $interfaceContents);

    $interfaceContents = preg_replace(
        '%\{\s*\}%six',
        '{' . rtrim($methodContents) . "\n" . '}' . "\n",
        $interfaceContents
    );        
    
    return $interfaceContents;
}

function createRepositoryInterfaceContents($module_info, $model_name, $interface)
{
    $modelInterface             = getModelInterfaceShortName($model_name);
    $modelInterfaceLongName     = getModelInterfaceName($module_info, $model_name);
    
    $contents                   = templateInterface($interface,[]);   
    $contentsAbstractFunctions  = templateRepositoryInterfaceAbstractFunction($modelInterface);
    $contentsUse                = templateRepositoryInterfaceUse($modelInterfaceLongName);
    
    $contents = templateComplexInterface($contentsUse, $contentsAbstractFunctions, $contents);
    
    return $contents;
}

function getModelRepositoryName($model_name)
{
    return $model_name . 'Repository';    
}

function templateUseFunctions($repositoryInterface, $thingInterface, $classModel, $collectionModel)
{        
    $thingFactory   = $classModel . 'Factory';
    $resourceModel  = $collectionModel . 'Factory';
    // $resourceModel       = 'Pulsestorm\HelloGenerate\Model\ResourceModel\Thing\CollectionFactory';
    
    return "
use {$repositoryInterface};
use {$thingInterface};
use {$thingFactory};
use {$resourceModel};

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Api\SearchResultsInterfaceFactory;";
}

function templateRepositoryFunctions($modelName)
{
    $modelNameFactory = $modelName . 'Factory';
    $modelInterface   = getModelInterfaceShortName($modelName);
    return '
    protected $objectFactory;
    protected $collectionFactory;
    public function __construct(
        '.$modelNameFactory.' $objectFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory       
    )
    {
        $this->objectFactory        = $objectFactory;
        $this->collectionFactory    = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }
    
    public function save('.$modelInterface.' $object)
    {
        try
        {
            $object->save();
        }
        catch(\Exception $e)
        {
            throw new CouldNotSaveException(__($e->getMessage()));
        }
        return $object;
    }

    public function getById($id)
    {
        $object = $this->objectFactory->create();
        $object->load($id);
        if (!$object->getId()) {
            throw new NoSuchEntityException(__(\'Object with id "%1" does not exist.\', $id));
        }
        return $object;        
    }       

    public function delete('.$modelInterface.' $object)
    {
        try {
            $object->delete();
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;    
    }    

    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }    

    public function getList(SearchCriteriaInterface $criteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);  
        $collection = $this->collectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            $fields = [];
            $conditions = [];
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ? $filter->getConditionType() : \'eq\';
                $fields[] = $filter->getField();
                $conditions[] = [$condition => $filter->getValue()];
            }
            if ($fields) {
                $collection->addFieldToFilter($fields, $conditions);
            }
        }  
        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? \'ASC\' : \'DESC\'
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
        $objects = [];                                     
        foreach ($collection as $objectModel) {
            $objects[] = $objectModel;
        }
        $searchResults->setItems($objects);
        return $searchResults;        
    }';    
}

function createRepository($module_info, $model_name)
{
    $classCollection    = getCollectionClassNameFromModuleInfo($module_info, $model_name);
    $classModel         = getModelClassNameFromModuleInfo($module_info, $model_name);
    $modelInterface     = getModelInterfaceName($module_info, $model_name);
    $repositoryName     = getModelRepositoryName($model_name);
    $repositoryFullName = getModelClassNameFromModuleInfo($module_info, $repositoryName);
    $interface          = getModuleInterfaceName($module_info, $repositoryName, 'Api');    
    $template           = createClassTemplate($repositoryFullName, false, '\\' . $interface, true);
    
    $body               = templateRepositoryFunctions($model_name);
    $use                = templateUseFunctions($interface, $modelInterface, $classModel, $classCollection);
    $contents           = $template;
    $contents           = str_replace('<$body$>', $body, $contents);
    $contents           = str_replace('<$use$>' , $use,  $contents);
    
    $path               = getPathFromClass($repositoryFullName);        
    output("Creating: " . $path);
    
    writeStringToFile($path, $contents);    
}

function createRepositoryInterface($module_info, $model_name)
{    
    $repositoryName = getModelRepositoryName($model_name);
    $interface      = getModuleInterfaceName($module_info, $repositoryName, 'Api');
    $path           = getPathFromClass($interface);    
    $contents       = createRepositoryInterfaceContents($module_info, $model_name, $interface);
    output("Creating: " . $path);
    writeStringToFile($path, $contents);
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
    output("Creating: " . $path);
    writeStringToFile($path, $class_contents);
}

function createDbTableNameFromModuleInfoAndModelShortName($module_info, $model_name)
{
    return strToLower($module_info->name . '_' . $model_name);
}

function createDbIdFromModuleInfoAndModelShortName($module_info, $model_name)
{
    return createDbTableNameFromModuleInfoAndModelShortName(
        $module_info, $model_name) . '_id';
}

function createResourceModelClass($module_info, $model_name)
{
    $path = $module_info->folder . "/Model/ResourceModel/$model_name.php";
    // $db_table               = strToLower($module_info->name . '_' . $model_name);
    $db_table               = createDbTableNameFromModuleInfoAndModelShortName($module_info, $model_name);
    // $db_id                  = strToLower($db_table) . '_id';
    $db_id                  = createDbIdFromModuleInfoAndModelShortName($module_info, $model_name);    
    $class_resource         = getResourceModelClassNameFromModuleInfo($module_info, $model_name);
    $template               = createClassTemplate($class_resource, BASE_RESOURCE_CLASS);    
    $construct              = templateConstruct($db_table, $db_id);
    $class_contents         = str_replace('<$body$>', $construct, $template);    
    output("Creating: " . $path);
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
    $implements          = getModelInterfaceShortName($model_name) . ', \Magento\Framework\DataObject\IdentityInterface';
    $template            = createClassTemplate($class_model, BASE_MODEL_CLASS, $implements);    
    $construct           = templateConstruct($class_resource);

    $body                = 
        templateCacheTag($cache_tag)    .      
        $construct                      .
        templateGetIdentities();

    $class_contents      = str_replace('<$body$>', $body, $template);    
    output("Creating: " . $path);
    writeStringToFile($path, $class_contents);    
}

function getModuleInterfaceName($module_info, $model_name, $type)
{
    return $module_info->vendor . '\\' . $module_info->short_name . 
        '\\' . $type .'\\' . getModelInterfaceShortName($model_name);

}

function getModelInterfaceName($module_info, $model_name)
{
    return getModuleInterfaceName($module_info, $model_name, 'Model');
//     return $module_info->vendor . '\\' . $module_info->short_name . 
//         '\Model\\' . getModelInterfaceShortName($model_name);
}

function createModelInterface($module_info, $model_name)
{
    $interface = getModelInterfaceName($module_info, $model_name);
    $path      = getPathFromClass($interface);
    $contents  = templateInterface($interface,[]);    
    writeStringToFile($path, $contents);
    output("Creating: " . $path);
}

function createTableNameFromModuleInfoAndModelName($module_info, $model_name)
{
    return strToLower($module_info->name . '_' . $model_name);
}

function createSchemaClass($module_info, $model_name)
{
    $className  = str_replace('_', '\\', $module_info->name) . 
        '\Setup\InstallSchema';
    $path       = getPathFromClass($className);

    $template   = createClassTemplate($className, false, 
        '\Magento\Framework\Setup\InstallSchemaInterface');        
    $contents   = str_replace('<$body$>', templateInstallFunction(), $template);    
    if(!file_exists($path))
    {
        output("Creating: " . $path);        
        writeStringToFile($path, $contents);
    }
    else
    {
        output("File Already Exists: " . $path);
    }
    
    $table_name = createTableNameFromModuleInfoAndModelName(
        $module_info, $model_name);
    
    $install_code = generateInstallSchemaTable($table_name);
    $contents     = file_get_contents($path);
    $end_setup    = '$installer->endSetup();';
    $contents     = str_replace($end_setup, 
        "\n//START table setup\n" .
        $install_code .
        "\n//END   table setup\n" .
        $end_setup, $contents);
        
    writeStringToFile($path, $contents);
}

function createDataClass($module_info, $model_name)
{
    $className  = str_replace('_', '\\', $module_info->name) . 
        '\Setup\InstallData';
    $path       = getPathFromClass($className);
    $template   = createClassTemplate($className, false, 
        '\Magento\Framework\Setup\InstallDataInterface');        
    $contents   = str_replace('<$body$>', templateInstallDataFunction(), $template);        

    if(!file_exists($path))
    {
        output("Creating: " . $path);        
        writeStringToFile($path, $contents);
    }
    else
    {
        output("Data Installer Already Exists: " . $path);
    }        
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

    createRepositoryInterface($module_info, $model_name);    
    createRepository($module_info, $model_name);
    createModelInterface($module_info, $model_name);
    createCollectionClass($module_info, $model_name);
    createResourceModelClass($module_info, $model_name);
    createModelClass($module_info, $model_name);
    
    
    createSchemaClass($module_info, $model_name);
    createDataClass($module_info, $model_name);


}

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}