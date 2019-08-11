<?php
namespace Pulsestorm\Magento2\Cli\Generate\Crud\Model;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
pestle_import('Pulsestorm\Magento2\Cli\Library\getModuleInformation');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Cli\Code_Generation\createClassTemplate');
pestle_import('Pulsestorm\Cli\Code_Generation\templateInterface');
pestle_import('Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass');
pestle_import('Pulsestorm\Cli\Code_Generation\generateInstallSchemaTable');

pestle_import('Pulsestorm\Magento2\Generate\SchemaUpgrade\classFileIsOurSchemaUpgrade');
pestle_import('Pulsestorm\Magento2\Generate\SchemaUpgrade\classFileIsOurDataUpgrade');
pestle_import('Pulsestorm\Magento2\Generate\SchemaUpgrade\getModuleXmlPathFromModuleInfo');
pestle_import('Pulsestorm\Magento2\Generate\SchemaUpgrade\exportedSchemaUpgrade');

pestle_import('Pulsestorm\Magento2\Generate\SchemaUpgrade\getSetupScriptPathFromModuleInfo');

define('BASE_COLLECTION_CLASS'  , '\Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection');
define('BASE_RESOURCE_CLASS'    , '\Magento\Framework\Model\ResourceModel\Db\AbstractDb');
define('BASE_MODEL_CLASS'       , '\Magento\Framework\Model\AbstractModel');

function getCollectionClassNameFromModuleInfo($moduleInfo, $modelName)
{
    return $moduleInfo->vendor . '\\' . $moduleInfo->short_name . 
        '\Model\ResourceModel\\' . $modelName . '\Collection';
}

function getResourceModelClassNameFromModuleInfo($moduleInfo, $modelName)
{
    return $moduleInfo->vendor . '\\' . $moduleInfo->short_name . 
        '\Model\ResourceModel\\' . $modelName;
}

function getModelClassNameFromModuleInfo($moduleInfo, $modelName)
{
    return $moduleInfo->vendor . '\\' . $moduleInfo->short_name . 
        '\Model\\' . $modelName;
}

function templateUpgradeDataFunction()
{
    $phpDoc =
    "    /**" . "\n" .
    "     * @inheritDoc" . "\n" .
    "     */";
    return "\n" . $phpDoc . "\n" . '    public function upgrade(' . "\n" .
    '        \Magento\Framework\Setup\ModuleDataSetupInterface $setup,' . "\n" .
    '        \Magento\Framework\Setup\ModuleContextInterface $context' ."\n" .
    '    ) {
        //install data here
    }' . "\n";

}

function templateInstallDataFunction()
{
    $phpDoc =
    "    // phpcs:disable" . "\n" .
    "    /**" . "\n" .
    "     * @inheritDoc" . "\n" .
    "     */";
    return "\n" . $phpDoc . "\n" . '    public function install(' . "\n" .
    '        \Magento\Framework\Setup\ModuleDataSetupInterface $setup,' . "\n" .
    '        \Magento\Framework\Setup\ModuleContextInterface $context' . "\n" .
    '    ) {
        //install data here
    }' . "\n" .
    '    // phpcs:enable' . "\n";
}

function templateUpgradeFunction()
{
    return "\n" . '    public function upgrade(' . "\n" .
    '        \Magento\Framework\Setup\SchemaSetupInterface $setup,' . "\n" .
    '        \Magento\Framework\Setup\ModuleContextInterface $context' . "\n" .
    '    ) {
        $installer = $setup;
        $installer->startSetup();
        //START: install stuff
        //END:   install stuff
        $installer->endSetup();
    }' . "\n";

}

function templateInstallFunction()
{
    $phpDoc =
    '    /**' . "\n" .
    '     * @inheritDoc' . "\n" .
    '     * @throws \Zend_Db_Exception' . "\n" .
    '     */';
    return "\n" . $phpDoc . "\n" . '    public function install(' . "\n" .
    '        \Magento\Framework\Setup\SchemaSetupInterface $setup,' . "\n" .
    '        \Magento\Framework\Setup\ModuleContextInterface $context' . "\n" .
    '    ) {
        $installer = $setup;
        $installer->startSetup();
        //START: install stuff
        //END:   install stuff
        $installer->endSetup();
    }' . "\n";
}

function templateConstruct($init1=false, $init2=false)
{
    $params = convertToClassNotation($init1, $init2);
    $phpDoc =
    '    /**'. "\n" .
    '     * Init' . "\n" .
    '     */';
    $phpCsIgnore = ' // phpcs:ignore PSR2.Methods.MethodDeclaration';
    return "\n" . $phpDoc . "\n" .
    '    protected function _construct()' . $phpCsIgnore . "\n" .
    '    {' . "\n" .
    '        $this->_init('.$params.');' . "\n" .
    '    }' . "\n";
}

function convertToClassNotation($init1, $init2=false)
{
    // add ::class if necessary
    if (strpos($init1, "\\") !== false) {
        $init1 = "\\" . $init1 . '::class';
        if ($init2) {
            $init2 = "\\" . $init2 . '::class';
        }
        $params = array_filter([$init1, $init2]);
        $params = implode(", ",$params);
    } else {
        $params = array_filter([$init1, $init2]);
        $params = implode("', '",$params);
        $params = "'" . $params . "'";
    }
    // make output multiline if long string
    if (strlen($params) > 90) {
        if ($init2) {
            $params = explode(', ', $params);
            $params = "\n" .
            '            ' . $params[0] . ',' . "\n" .
            '            ' . $params[1] . "\n" .
            '        ';
        }
        else {
            $params = "\n" .
            '            ' . $params . "\n" .
            '        ';
        }
    }
    return $params;
}

function templateRepositoryInterfaceAbstractFunction($modelShortInterface)
{
    $modelName = str_replace('Interface', '', $modelShortInterface);

    return "
    /**
     * Create or update a $modelName.
     *
     * @param " . $modelName . "Interface \$page
     * @return " . $modelName . "Interface
     */
    public function save({$modelShortInterface} \$page);

    /**
     * Get a $modelName by Id
     *
     * @param int \$id
     * @return " . $modelName . "Interface
     * @throws \Magento\Framework\Exception\NoSuchEntityException If " . $modelName . " with the specified ID does not exist.
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById(\$id);

    /**
     * Retrieve $modelName"."s which match a specified criteria.
     *
     * @param SearchCriteriaInterface \$criteria
     */
    public function getList(SearchCriteriaInterface \$criteria);

    /**
     * Delete a $modelName
     *
     * @param " . $modelName . "Interface \$page
     * @return " . $modelName . "Interface
     * @throws \Magento\Framework\Exception\NoSuchEntityException If " . $modelName . " with the specified ID does not exist.
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete({$modelShortInterface} \$page);

    /**
     * Delete a $modelName by Id
     *
     * @param int \$id
     * @return " . $modelName . "Interface
     * @throws \Magento\Framework\Exception\NoSuchEntityException If customer with the specified ID does not exist.
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById(\$id);    
";    
}

function templateRepositoryInterfaceUse($longModelInterfaceName)
{
    return "use {$longModelInterfaceName};
use Magento\Framework\Api\SearchCriteriaInterface;
";
}

function templateComplexInterface($useContents, $methodContents, $interfaceContents)
{
    $interfaceContents = preg_replace(
        '%(\/\*\*\n \* Interface.*\n.*\n.*\n.*\*\/)%',
        $useContents . "\n" . '$1',
        $interfaceContents);

    $interfaceContents = preg_replace(
        '%\{\s*\}%six',
        '{' . rtrim($methodContents) . "\n" . '}' . "\n",
        $interfaceContents
    );        
    
    return $interfaceContents;
}

function createRepositoryInterfaceContents($moduleInfo, $modelName, $interface)
{
    $modelInterface             = getModelInterfaceShortName($modelName);
    $modelInterfaceLongName     = getModelInterfaceName($moduleInfo, $modelName);
    
    $contents                   = templateInterface($interface,[]);   
    $contentsAbstractFunctions  = templateRepositoryInterfaceAbstractFunction($modelInterface);
    $contentsUse                = templateRepositoryInterfaceUse($modelInterfaceLongName);
    
    $contents = templateComplexInterface($contentsUse, $contentsAbstractFunctions, $contents);
    
    return $contents;
}

function getModelRepositoryName($modelName)
{
    return $modelName . 'Repository';    
}

function templateUseFunctions($repositoryInterface, $thingInterface, $classModel, $collectionModel, $classResourceModel)
{        
    $thingFactory   = $classModel . 'Factory';
    $resourceModel  = $collectionModel . 'Factory';
    $nameSpace      = explode('\\', $classModel)[0];
    $coreImport     = "use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;";

    $customImport = "use {$repositoryInterface};
use {$thingInterface};
use {$thingFactory};
use {$classResourceModel} as ObjectResourceModel;
use {$resourceModel};";

    if (strcmp('Magento', $nameSpace) < 0) {
        return $coreImport . "\n" . "\n" . $customImport;
    }
    else {
        return $customImport . "\n" . "\n" . $coreImport;
    }
}

function templateRepositoryFunctions($modelName)
{
    $modelNameFactory = $modelName . 'Factory';
    $modelInterface   = getModelInterfaceShortName($modelName);
    return '
    protected $objectFactory;
    protected $objectResourceModel;
    protected $collectionFactory;
    protected $searchResultsFactory;

    /**
     * ' . $modelName . 'Repository constructor.
     *
     * @param '.$modelNameFactory.' $objectFactory
     * @param ObjectResourceModel $objectResourceModel
     * @param CollectionFactory $collectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        '.$modelNameFactory.' $objectFactory,
        ObjectResourceModel $objectResourceModel,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->objectFactory        = $objectFactory;
        $this->objectResourceModel  = $objectResourceModel;
        $this->collectionFactory    = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    /**
     * @inheritDoc
     *
     * @throws CouldNotSaveException
     */
    public function save('.$modelInterface.' $object)
    {
        try {
            $this->objectResourceModel->save($object);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }
        return $object;
    }

    /**
     * @inheritDoc
     */
    public function getById($id)
    {
        $object = $this->objectFactory->create();
        $this->objectResourceModel->load($object, $id);
        if (!$object->getId()) {
            throw new NoSuchEntityException(__(\'Object with id "%1" does not exist.\', $id));
        }
        return $object;
    }

    /**
     * @inheritDoc
     */
    public function delete('.$modelInterface.' $object)
    {
        try {
            $this->objectResourceModel->delete($object);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }

    /**
     * @inheritDoc
     */
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
    }' . "\n";
}

function createRepository($moduleInfo, $modelName)
{
    $classCollection    = getCollectionClassNameFromModuleInfo($moduleInfo, $modelName);
    $classResourceModel = getResourceModelClassNameFromModuleInfo($moduleInfo, $modelName);
    $classModel         = getModelClassNameFromModuleInfo($moduleInfo, $modelName);
    $modelInterface     = getModelInterfaceName($moduleInfo, $modelName);
    $repositoryName     = getModelRepositoryName($modelName);
    $repositoryFullName = getModelClassNameFromModuleInfo($moduleInfo, $repositoryName);
    $interface          = getModuleInterfaceName($moduleInfo, $repositoryName, 'Api');
    $interfaceShortName = getModelInterfaceShortName($repositoryName);
    $template           = createClassTemplate($repositoryFullName, false, $interfaceShortName, true);
    
    $body               = templateRepositoryFunctions($modelName);
    $use                = templateUseFunctions($interface, $modelInterface, $classModel, $classCollection, $classResourceModel);
    $contents           = $template;
    $contents           = str_replace('<$body$>', $body, $contents);
    $contents           = str_replace('<$use$>' , $use,  $contents);
    
    $path               = getPathFromClass($repositoryFullName);        
    output("Creating: " . $path);
    
    writeStringToFile($path, $contents);    
}

function createRepositoryInterface($moduleInfo, $modelName)
{    
    $repositoryName = getModelRepositoryName($modelName);
    $interface      = getModuleInterfaceName($moduleInfo, $repositoryName, 'Api');
    $path           = getPathFromClass($interface);    
    $contents       = createRepositoryInterfaceContents($moduleInfo, $modelName, $interface);
    output("Creating: " . $path);
    writeStringToFile($path, $contents);
}

function createCollectionClass($moduleInfo, $modelName)
{
    $path                   = $moduleInfo->folder . "/Model/ResourceModel/$modelName/Collection.php";
    $class_collection       = getCollectionClassNameFromModuleInfo($moduleInfo, $modelName);
    $class_model            = getModelClassNameFromModuleInfo($moduleInfo, $modelName);
    $class_resource         = getResourceModelClassNameFromModuleInfo($moduleInfo, $modelName);
            
    $template               = createClassTemplate($class_collection, BASE_COLLECTION_CLASS);
    $construct              = templateConstruct($class_model, $class_resource);

    $class_contents         = str_replace('<$body$>', $construct, $template);
    output("Creating: " . $path);
    writeStringToFile($path, $class_contents);
}

function createDbTableNameFromModuleInfoAndModelShortName($moduleInfo, $modelName)
{
    return strToLower($moduleInfo->name . '_' . $modelName);
}

function createDbIdFromModuleInfoAndModelShortName($moduleInfo, $modelName)
{
    return strtolower($modelName) . '_id';
}

function createResourceModelClass($moduleInfo, $modelName)
{
    $path = $moduleInfo->folder . "/Model/ResourceModel/$modelName.php";
    // $db_table               = strToLower($moduleInfo->name . '_' . $modelName);
    $db_table               = createDbTableNameFromModuleInfoAndModelShortName($moduleInfo, $modelName);
    // $db_id                  = strToLower($db_table) . '_id';
    $db_id                  = createDbIdFromModuleInfoAndModelShortName($moduleInfo, $modelName);    
    $class_resource         = getResourceModelClassNameFromModuleInfo($moduleInfo, $modelName);
    $template               = createClassTemplate($class_resource, BASE_RESOURCE_CLASS);    
    $construct              = templateConstruct($db_table, $db_id);
    $class_contents         = str_replace('<$body$>', $construct, $template);    
    output("Creating: " . $path);
    writeStringToFile($path, $class_contents);    
}

function templateGetIdentities()
{
    $phpDoc =
    "    /**" . "\n" .
    "     * @inheritDoc" . "\n" .
    "     */";
    return "\n" . $phpDoc . "\n" . '    public function getIdentities()
    {
        return [self::CACHE_TAG . \'_\' . $this->getId()];
    }' . "\n";
}

function templateCacheTag($tag_name)
{
    return "\n    const CACHE_TAG = '$tag_name';\n";
}

function getModelInterfaceShortName($modelName)
{
    return $modelName . 'Interface';
}

function createModelClass($moduleInfo, $modelName)
{
    $path = $moduleInfo->folder . "/Model/$modelName.php";
    $cache_tag           = strToLower($moduleInfo->name . '_' . $modelName);
    $class_model         = getModelClassNameFromModuleInfo($moduleInfo, $modelName);
    $class_resource      = getResourceModelClassNameFromModuleInfo($moduleInfo, $modelName);
    $implements          = getImplementsModelInterfaceName($moduleInfo, $modelName) . ',\Magento\Framework\DataObject\IdentityInterface';
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

function getModuleInterfaceName($moduleInfo, $modelName, $type)
{
    return $moduleInfo->vendor . '\\' . $moduleInfo->short_name . 
        '\\' . $type .'\\' . getModelInterfaceShortName($modelName);

}

function getModelInterfaceName($moduleInfo, $modelName)
{
    return getModuleInterfaceName($moduleInfo, $modelName, 'Api\\Data');
//     return $moduleInfo->vendor . '\\' . $moduleInfo->short_name . 
//         '\Model\\' . getModelInterfaceShortName($modelName);
}

function getImplementsModelInterfaceName($moduleInfo, $modelName)
{
    return '\\' . getModelInterfaceName($moduleInfo, $modelName);
}


function createModelInterface($moduleInfo, $modelName)
{
    $interface = getModelInterfaceName($moduleInfo, $modelName);
    $path      = getPathFromClass($interface);
    $contents  = templateInterface($interface,[]);
    $contents .= "\n";
    writeStringToFile($path, $contents);
    output("Creating: " . $path);
}

function createTableNameFromModuleInfoAndModelName($moduleInfo, $modelName)
{
    return strToLower($moduleInfo->name . '_' . $modelName);
}

function generateClassNameAndInterfaceNameForSchemaFromModuleInfoAndOptions(
    $moduleInfo, $options)
{
    //InstallSchema
    $className      = generateInstallSchemaClassName($moduleInfo);
    $interfaceName  = '\Magento\Framework\Setup\InstallSchemaInterface';

    //UpgradeSchema
    if(isUseUpgradeSchema($options))
    {
        $className      = generateUpgradeSchemaClassName($moduleInfo);        
        $interfaceName  = '\Magento\Framework\Setup\UpgradeSchemaInterface';    
    }
    
    
    return [
        $className, $interfaceName];
}    

function isUseUpgradeSchema($options)
{
    return array_key_exists('use-upgrade-schema', $options);
}

function generateSchemaBodyFromModuleInfoAndOptions($moduleInfo, $options)
{
    $body = templateInstallFunction();
    if(isUseUpgradeSchema($options))
    {
        $body = templateUpgradeFunction();
    }
    return $body;    
}

function conditionalWriteStringToFile($path, $contents)
{
    if(!file_exists($path))
    {
        output("Creating: " . $path);        
        writeStringToFile($path, $contents);
    }
    else
    {
        output("File Already Exists: " . $path);
    }
}

function prependInstallerCodeBeforeEndSetup($moduleInfo, $modelName, $path)
{
    $table_name = createTableNameFromModuleInfoAndModelName(
        $moduleInfo, $modelName);
    $install_code = generateInstallSchemaTable($table_name, $modelName);
    $contents     = file_get_contents($path);
    $end_setup    = '        $installer->endSetup();';
    $contents     = str_replace($end_setup, 
        "\n        //START table setup\n" .
        $install_code .
        "\n        //END   table setup\n" .
        $end_setup, $contents);
    return $contents;
}

function appendInstallSchemaClass($moduleInfo, $modelName, $options)
{
    list($className, $interfaceName) = 
        generateClassNameAndInterfaceNameForSchemaFromModuleInfoAndOptions(
            $moduleInfo, $options);
    $path       = getPathFromClass($className);         
    $contents = prependInstallerCodeBeforeEndSetup(
        $moduleInfo, $modelName, $path); 

    output("Adding model to InstallSchema");        
    writeStringToFile($path, $contents);                              
}

function createSchemaClass($moduleInfo, $modelName, $options)
{
    list($className, $interfaceName) = 
        generateClassNameAndInterfaceNameForSchemaFromModuleInfoAndOptions(
            $moduleInfo, $options);
            
    $path       = getPathFromClass($className);
    $template   = createClassTemplate($className, false, $interfaceName);        
        
    $classBody = generateSchemaBodyFromModuleInfoAndOptions($moduleInfo, $options);        
    $contents   = str_replace('<$body$>', $classBody, $template); 
    conditionalWriteStringToFile($path, $contents);   
    
    $contents = prependInstallerCodeBeforeEndSetup(
        $moduleInfo, $modelName, $path);
            
    writeStringToFile($path, $contents);
}

function generateUpgradeDataClassName($moduleInfo)
{
    $className  = str_replace('_', '\\', $moduleInfo->name) . 
        '\Setup\UpgradeData';
    return $className;
}

function generateClassNameAndInterfaceNameForDataFromModuleInfoAndOptions($moduleInfo, $options)
{
    $className  = str_replace('_', '\\', $moduleInfo->name) . 
        '\Setup\InstallData';
    $interfaceName = '\Magento\Framework\Setup\InstallDataInterface';    
    
    if(isUseUpgradeSchema($options))
    {
        $className = generateUpgradeDataClassName($moduleInfo);

        $interfaceName = '\Magento\Framework\Setup\UpgradeDataInterface';        
    }
        
    return [$className, $interfaceName];
}

function generateDataBodyFromOptions($options)
{
    $body = templateInstallDataFunction();
    if(isUseUpgradeSchema($options))
    {
        $body = templateUpgradeDataFunction();        
    }
    
    return $body;
}

function createDataClass($moduleInfo, $modelName, $options)
{
    list($className, $interfaceName) = 
        generateClassNameAndInterfaceNameForDataFromModuleInfoAndOptions(
            $moduleInfo, $options);

    $path       = getPathFromClass($className);
    $template   = createClassTemplate($className, false, $interfaceName);        
    $classBody  = generateDataBodyFromOptions($options);
    $contents   = str_replace('<$body$>', $classBody, $template);        

    conditionalWriteStringToFile($path, $contents);       
}

function generateUpgradeSchemaClassName($moduleInfo)
{
    $className  = str_replace('_', '\\', $moduleInfo->name) . 
        '\Setup\UpgradeSchema';
    return $className;
}

function generateInstallSchemaClassName($moduleInfo)
{
    $className  = str_replace('_', '\\', $moduleInfo->name) . 
        '\Setup\InstallSchema';
    return $className;
}

function checkForUpgradeSchemaClass($moduleInfo, $modelName)
{
    $className = generateUpgradeSchemaClassName($moduleInfo);
    $path      = getPathFromClass($className);
    
    if(file_exists($path))
    {    
        $message = 
"\nERROR: The module {$moduleInfo->name} already has a 
defined {$className}.  

We can't proceed. If you're using upgrade scripts, try
the --use-upgrade-schema-with-scripts option.
";        
        exitWithErrorMessage($message);
    }

}

function checkThatInstallSchemaClassExists($moduleInfo, $modelName)
{
    $className = generateInstallSchemaClassName($moduleInfo);
    $path      = getPathFromClass($className);
    
    if(!file_exists($path))
    {  
        $message = 
"\nIt looks like this module does not has an InstallSchema class.  This means 
ee can't proceed.  The --use-install-schema-for-new-model options requires this
class.  Try running the command with no -- options for initial generation.
";                   

        exitWithErrorMessage($message);    
    }  
}

function checkForInstallSchemaClass($moduleInfo, $modelName)
{
    $className = generateInstallSchemaClassName($moduleInfo);
    $path      = getPathFromClass($className);
    
    if(file_exists($path))
    {  
        $message = 
"\nIt looks like this module already has an InstallSchema class.  This means 
we can't proceed.  If you're trying to add a second model to a module, 
try using --use-upgrade-schema, --use-upgrade-schema-with-scripts or
--use-install-schema-for-new-model.
";                   

        exitWithErrorMessage($message);    
    }  

}

function checkForNoInstallSchemaAndOurUpgrade($moduleInfo, $modelName)
{
    $className = generateInstallSchemaClassName($moduleInfo);
    $path      = getPathFromClass($className);
    
    if(!file_exists($path))
    {    
        $message = 
"The --use-upgrade-schema-with-scripts options requires an InstallSchema
class to already be present.  

Try creating your first crud model without any options, and use the 
--use-upgrade-schema-with-scripts options for your 2nd, 3rd, ..., nth 
crud models. ";        
        exitWithErrorMessage($message);
    }

    $path = getPathFromClass(
        generateUpgradeSchemaClassName($moduleInfo));

    if(file_exists($path) && !classFileIsOurSchemaUpgrade($path))
    {
        $message =
"The module contains an UpgradeSchema class that is not compatible with
UpgradeSchema classes created via magento2:generate:schema-upgrade.

The --use-upgrade-schema-with-scripts relies on an UpgradeSchema class
that is compatible with magento2:generate:schema-upgrade.
";        
        exitWithErrorMessage($message);
    }

    $path = getPathFromClass(
        generateUpgradeDataClassName($moduleInfo));        
    if(file_exists($path) && !classFileIsOurDataUpgrade($path))
    {
        $message =
"The module contains an UpgradeData class that is not compatible with
UpgradeSchema classes created via magento2:generate:schema-upgrade.

The --use-upgrade-schema-with-scripts relies on an UpgradeSchema class
that is compatible with magento2:generate:schema-upgrade.
";      
        exitWithErrorMessage($message);
    }
    
}

function checkForSchemaOptions($keys)
{
    if( in_array('use-upgrade-schema',$keys) && 
        in_array('use-upgrade-schema-with-scripts',$keys))
    {
        $message = 'Can\'t use --use-upgrade-schema and --use-upgrade-schema-with-scripts together.';
        exitWithErrorMessage($message);
    }
}

function checkForSchemaClasses($moduleInfo, $modelName, $options)
{
    if(array_key_exists('use-upgrade-schema', $options))
    {
        checkForUpgradeSchemaClass($moduleInfo, $modelName);
    }
    else if(array_key_exists('use-upgrade-schema-with-scripts', $options))    
    {
        checkForNoInstallSchemaAndOurUpgrade($moduleInfo, $modelName);
    }    
    else if(array_key_exists('use-install-schema-for-new-model', $options))
    {
        checkThatInstallSchemaClassExists($moduleInfo, $modelName);
    }    
    else if(!array_key_exists('use-upgrade-schema-with-scripts', $options))
    {
        checkForInstallSchemaClass($moduleInfo, $modelName);
    }
}

function isUseUpgradeSchemaWithScripts($options)
{
    return array_key_exists('use-upgrade-schema-with-scripts', $options);
}

function bumpDottedVersionNumber($version)
{
    $parts = explode('.', $version);
    $last = array_pop($parts);
    if(!is_numeric($last))
    {
        exitWithErrorMessage("I don't know what to do with a version number that looks like $version");
    }
    $last++;
    $parts[] = $last;
    return implode('.', $parts);
}

function invokeGenerateSchemaUpgrade($moduleInfo, $modelName, $options)
{
    $xml = simplexml_load_file(getModuleXmlPathFromModuleInfo($moduleInfo));
    $oldVersion = $xml->module['setup_version'];
    $version = bumpDottedVersionNumber($oldVersion);

    exportedSchemaUpgrade([
        'module_name'       => $moduleInfo->name,
        'upgrade_version'   => $version
    ],[]);
    
    $setupPath = getSetupScriptPathFromModuleInfo($moduleInfo, 'schema') . 
                    "/{$version}.php";
    
    $table_name = createTableNameFromModuleInfoAndModelName(
        $moduleInfo, $modelName);
    
    $contents = file_get_contents($setupPath);
    $contents .= "\n" . '$installer = $setup;' . "\n" . generateInstallSchemaTable($table_name, $modelName);
    
    writeStringToFile($setupPath, $contents);                
}

function createSchemaAndDataClasses($moduleInfo, $modelName, $options)
{
    if(isUseUpgradeSchemaWithScripts($options))
    {                   
        invokeGenerateSchemaUpgrade($moduleInfo, $modelName, $options);
        return;
    }
    
    if(array_key_exists('use-install-schema-for-new-model', $options))
    {
        appendInstallSchemaClass($moduleInfo, $modelName, $options);
        return;
    }
    createSchemaClass($moduleInfo, $modelName, $options);
    createDataClass($moduleInfo, $modelName, $options);
}

/**
 * Temp fix until: https://github.com/astorm/pestle/issues/212
 */
function validateModelName($modelName)
{
    $newModelName = preg_replace('%[^A-Za-z0-9]%','',$modelName);
    if($newModelName !== $modelName)
    {
        exitWithErrorMessage("Invalid (to us) model name -- try again with {$newModelName}?" . "\n" .
        "If this annoys you -- pull requests welcome at: https://github.com/astorm/pestle/issues/212");
    }
    return $modelName;
}

/**
* Generates Magento 2 CRUD model
*
* Wrapped by magento:foo:baz ... version of command
*
* 
* @command generate-crud-model
* @argument module_name Which module? [Pulsestorm_HelloGenerate]
* @argument model_name  What model name? [Thing]
* @option use-upgrade-schema Create UpgradeSchema and UpgradeData classes instead of InstallSchema
* @option use-upgrade-schema-with-scripts Same as use-upgrade-schema, but uses schema script helpers
* @option use-install-schema-for-new-model Allows you to add another model definition to InstallSchema
*/
function pestle_cli($argv, $options)
{
    $options = array_filter($options, function($item){
        return !is_null($item);
    });

    $module_name = $argv['module_name'];
    $moduleInfo = getModuleInformation($argv['module_name']);    
    $modelName  = validateModelName($argv['model_name']);
    
    checkForSchemaOptions(array_keys($options));
    checkForSchemaClasses($moduleInfo, $modelName, $options);
    
    createRepositoryInterface($moduleInfo, $modelName);    
    createRepository($moduleInfo, $modelName);
    createModelInterface($moduleInfo, $modelName);
    createCollectionClass($moduleInfo, $modelName);
    createResourceModelClass($moduleInfo, $modelName);
    createModelClass($moduleInfo, $modelName);                    
    createSchemaAndDataClasses($moduleInfo, $modelName, $options);
}

function exported_pestle_cli($argv, $options)
{
    return pestle_cli($argv, $options);
}
