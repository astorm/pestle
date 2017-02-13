<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Ui\Form;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\getModuleInformation');
pestle_import('Pulsestorm\Cli\Code_Generation\createClassTemplateWithUse');
pestle_import('Pulsestorm\Magento2\Cli\Library\createClassFile');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Xml_Library\formatXmlString');
pestle_import('Pulsestorm\Magento2\Cli\Generate\Crud\Model\createDbIdFromModuleInfoAndModelShortName');
function getModelShortName($modelClass)
{
    $parts = explode('\\', $modelClass);
    $parts = array_slice($parts, 3);
    return implode('_', $parts);
}

function getModuleNameFromClassName($modelClass)
{
    $parts = explode('\\', $modelClass);
    $parts = array_slice($parts, 0,2);
    return implode('_', $parts);
}

function getPersistKeyFromModelClassName($modelClass)
{
    $key = strToLower(getModuleNameFromClassName($modelClass) 
        . '_' 
        . getModelShortName($modelClass));
    
    return $key;        
}

function createControllerClassBodyForSave($module_info, $modelClass, $aclRule)
{
    $dbID       = createDbIdFromModuleInfoAndModelShortName($module_info, getModelShortName($modelClass));
    $persistKey = getPersistKeyFromModelClassName($modelClass);
    return '
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = \''.$aclRule.'\';

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @param Action\Context $context
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Action\Context $context,
        DataPersistorInterface $dataPersistor
    ) {
        $this->dataPersistor = $dataPersistor;
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            if (isset($data[\'is_active\']) && $data[\'is_active\'] === \'true\') {
                $data[\'is_active\'] = '.$modelClass.'::STATUS_ENABLED;
            }
            if (empty($data[\''.$dbID.'\'])) {
                $data[\''.$dbID.'\'] = null;
            }

            /** @var '.$modelClass.' $model */
            $model = $this->_objectManager->create(\''.$modelClass.'\');

            $id = $this->getRequest()->getParam(\''.$dbID.'\');
            if ($id) {
                $model->load($id);
            }

            $model->setData($data);

            try {
                $model->save();
                $this->messageManager->addSuccess(__(\'You saved the thing.\'));
                $this->dataPersistor->clear(\''.$persistKey.'\');
                if ($this->getRequest()->getParam(\'back\')) {
                    return $resultRedirect->setPath(\'*/*/edit\', [\''.$dbID.'\' => $model->getId(), \'_current\' => true]);
                }
                return $resultRedirect->setPath(\'*/*/\');
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __(\'Something went wrong while saving the data.\'));
            }

            $this->dataPersistor->set(\''.$persistKey.'\', $data);
            return $resultRedirect->setPath(\'*/*/edit\', [\''.$dbID.'\' => $this->getRequest()->getParam(\''.$dbID.'\')]);
        }
        return $resultRedirect->setPath(\'*/*/\');
    }    
';
}

function createControllerClassBody($module_info, $aclRule)
{
    return '
    protected $resultPageFactory;
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory)
    {
        $this->resultPageFactory = $resultPageFactory;        
        return parent::__construct($context);
    }
    
    public function execute()
    {
        return $this->resultPageFactory->create();  
    }    
    
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(\''.$aclRule.'\');
    }         
';
}

function createControllerFiles($module_info, $modelClass, $aclRule)
{
    // $moduleBasePath = getModuleBasePath();
    $prefix = $module_info->vendor . '\\' . $module_info->short_name;
    $classes = [
        'controllerEditClassname' => $prefix . '\Controller\Adminhtml\Index\Edit',
        'controllerNewClassName'  => $prefix . '\Controller\Adminhtml\Index\NewAction',
        'controllerSaveClassName' => $prefix . '\Controller\Adminhtml\Index\Save'
    ];
    foreach($classes as $desc=>$className)
    {        
        $contents = createClassWithUse($className, '\Magento\Backend\App\Action', '', 
            createControllerClassBody($module_info, $aclRule));
        if($desc === 'controllerSaveClassName')
        {
            $contents = createControllerClassBodyForSave($module_info, $modelClass, $aclRule);
        }       
        output("Creating: $className");
        $return             = createClassFile($className,$contents);        
    }
}

function createCollectionClassNameFromModelName($modelClass)
{
    $parts = explode('\\', $modelClass);
    if($parts[2] !== 'Model')
    {
        throw new \Exception("Model name that, while valid, doesn't conform to what we expect");
    }
    $first      = array_slice($parts, 0, 3);
    $first[]    = 'ResourceModel';
    
    $second     = array_slice($parts, 3);
    $second[]   = 'CollectionFactory';
    $new        = array_merge($first, $second);
    return implode('\\', $new);
}

function createDataProviderUseString($module_info, $modelClass)
{
    $collectionClassName = createCollectionClassNameFromModelName($modelClass);
        
    return 'use '.$collectionClassName.';
use Magento\Framework\App\Request\DataPersistorInterface;';
}

function createDataProviderClassBodyString($module_info, $modelClass)
{
    $persistKey = getPersistKeyFromModelClassName($modelClass);
    return '

    protected $collection;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->meta = $this->prepareMeta($this->meta);
    }

    /**
     * Prepares Meta
     *
     * @param array $meta
     * @return array
     */
    public function prepareMeta(array $meta)
    {
        return $meta;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();

        foreach ($items as $item) {
            $this->loadedData[$item->getId()] = $item->getData();
        }

        $data = $this->dataPersistor->get(\''.$persistKey.'\');
        if (!empty($data)) {
            $item = $this->collection->getNewEmptyItem();
            $item->setData($data);
            $this->loadedData[$item->getId()] = $item->getData();
            $this->dataPersistor->clear(\''.$persistKey.'\');
        }

        return $this->loadedData;
';        
}

function createClassWithUse($className, $parentClass, $useString, $bodyString)
{
    $contents           = createClassTemplateWithUse($className, $parentClass);
    $contents           = str_replace('<$use$>', $useString, $contents);
    $contents           = str_replace('<$body$>', $bodyString, $contents);
    return $contents;
}

function createDataProviderClassNameFromModelClassName($modelClass)
{
    return $modelClass . '\DataProvider';
}

function createDataProvider($module_info, $modelClass)
{
    // $moduleBasePath = getModuleBasePath();
    $dataProviderClassName = createDataProviderClassNameFromModelClassName($modelClass);
    $contents           = createClassWithUse(
        $dataProviderClassName, 
        '\Magento\Ui\DataProvider\AbstractDataProvider',
        createDataProviderUseString($module_info, $modelClass),
        createDataProviderClassBodyString($module_info, $modelClass)        
    );        
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

function addUiComponentToXml($xml, $uiComponentName)
{
    $nodes = $xml->xpath("//uiComponent[@name='$uiComponentName']");
    if(count($nodes) === 0)
    {
        $referenceBlock = $xml->addChild('referenceBlock');
        $referenceBlock->addAttribute('name', 'content');
        $uiComponent = $referenceBlock->addChild('uiComponent');
        $uiComponent->addAttribute('name', $uiComponentName);
    }
    return $xml;
}

function createLayoutXmlFiles($module_info, $modelClass)
{
    $moduleBasePath     = $module_info->folder;
    $layoutBasePath     = $moduleBasePath . '/view/adminhtml/layout'; 
    
    $uiComponentName    = createUiComponentNameFromModuleInfoAndModelClass(
        $module_info, $modelClass);
    
    $prefixFilename = implode('_', [
        strToLower($module_info->name),
        createShortPluralModelName($modelClass),
        'index'
    ]);;
    
    $names = ['edit', 'new', 'save' ];
    
    foreach($names as $name)
    {
        $fileName = $layoutBasePath . '/' . $prefixFilename . '_' . $name . '.xml';
        
        $xml = createEmptyXmlTree();
        if(file_exists($fileName))
        {
            $xml = simplexml_load_file($fileName);
        }
        $xml = addUiComponentToXml($xml, $uiComponentName);
        
        output("Creating $fileName");
        writeStringToFile($fileName, formatXmlString($xml->asXml()));
    }        
}

function createUiComponentNameFromModuleInfoAndModelClass($module_info, $modelClass)
{
    return implode('_', [
        strToLower($module_info->name),
        createShortPluralModelName($modelClass),
        'form'
    ]);

}

function generateButtonClassAndReturnName($modelClass, $buttonName)
{
    $prefix = str_replace('_','\\',getModuleNameFromClassName($modelClass)) . '\\Block\\Adminhtml\\' .
        getModelShortName($modelClass) . '\\Edit';
    $className = $prefix .= str_replace(' ', '_', 
        ucWords(str_replace('_', ' ', $buttonName))) . '\\Button';        
    
    $contents = createClassTemplateWithUse($modelClass, 'GenericButton', 
        'ButtonProviderInterface');
    $contents   = str_replace('<$use$>','Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface',$contents);
    $contents   = str_replace('<$body$>','//implement me',$contents);
    
    output('@TODO: generate generic button class where?');        
    output('@TODO: finish generating class bodies');        
    exit($contents);        
    return $className;
}

function createButtonXml($modelClass)
{
    $buttons = [        
        'back'              => generateButtonClassAndReturnName($modelClass,'back'),
        'delete'            => generateButtonClassAndReturnName($modelClass,'delete'),
        'reset'             => generateButtonClassAndReturnName($modelClass,'reset'),
        'save'              => generateButtonClassAndReturnName($modelClass,'save'),
        'save_and_continue' => generateButtonClassAndReturnName($modelClass,'save_and_continue')                                        
//         'back'              => $prefix . '\BackButton',
//         'delete'            => $prefix . '\DeleteButton',
//         'reset'             => $prefix . '\ResetButton',
//         'save'              => $prefix . '\SaveButton',
//         'save_and_continue' => $prefix . '\SaveAndContinueButton',                                
    ];
    $buttonXml = "\n";
    foreach($buttons as $name=>$class)
    {
        $buttonXml .= '<item name="'.$name.'" xsi:type="string">'.$class.'</item>' . "\n";
    }
    
    return $buttonXml;
}

function createUiComponentXmlFile($module_info, $modelClass)
{
    output('@TODO: All those buttons -- we need our own?');
    
    $moduleBasePath      = $module_info->folder;
    $uiComponentBasePath = $moduleBasePath . '/view/adminhtml/ui_component';     
    $uiComponentName     = createUiComponentNameFromModuleInfoAndModelClass($module_info, $modelClass);
    $uiComponentFilePath = $uiComponentBasePath . '/' . $uiComponentName . '.xml';        
    $dbID       = createDbIdFromModuleInfoAndModelShortName($module_info, getModelShortName($modelClass));
    $dataProviderClassName = createDataProviderClassNameFromModelClassName($modelClass);          
    
    $buttonXml = createButtonXml($modelClass);
         
    $xml = simplexml_load_string(
'<?xml version="1.0" encoding="UTF-8"?>
<form xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd">
    <argument name="data" xsi:type="array">
        <item name="js_config" xsi:type="array">
            <item name="provider" xsi:type="string">'.$uiComponentName.'.'.$uiComponentName.'_data_source</item>
            <item name="deps" xsi:type="string">'.$uiComponentName.'.'.$uiComponentName.'_data_source</item>
        </item>
        <item name="label" xsi:type="string" translate="true">Object Information</item>
        <item name="config" xsi:type="array">
            <item name="dataScope" xsi:type="string">data</item>
            <item name="namespace" xsi:type="string">'.$uiComponentName.'</item>
        </item>
        <item name="template" xsi:type="string">templates/form/collapsible</item>
        <item name="buttons" xsi:type="array">
            '.$buttonXml.'
        </item>
    </argument>
    <dataSource name="'.$uiComponentName.'_data_source">
        <argument name="dataProvider" xsi:type="configurableObject">
            <argument name="class" xsi:type="string">'.$dataProviderClassName.'</argument>
            <argument name="name" xsi:type="string">'.$uiComponentName.'_data_source</argument>
            <argument name="primaryFieldName" xsi:type="string">'.$dbID.'</argument>
            <argument name="requestFieldName" xsi:type="string">'.$dbID.'</argument>
            <argument name="data" xsi:type="array">
                <item name="config" xsi:type="array">
                    <item name="submit_url" xsi:type="url" path="*/*/save"/>
                </item>
            </argument>
        </argument>
        <argument name="data" xsi:type="array">
            <item name="js_config" xsi:type="array">
                <item name="component" xsi:type="string">Magento_Ui/js/form/provider</item>
            </item>
        </argument>
    </dataSource>
    <fieldset name="general">
        <argument name="data" xsi:type="array">
            <item name="config" xsi:type="array">
                <item name="label" xsi:type="string">Form Data</item>
                <item name="collapsible" xsi:type="boolean">true</item>                
            </item>
        </argument>
        <field name="'.$dbID.'">
            <argument name="data" xsi:type="array">
                <item name="config" xsi:type="array">
                    <item name="visible" xsi:type="boolean">false</item>
                    <item name="dataType" xsi:type="string">text</item>
                    <item name="formElement" xsi:type="string">input</item>                    
                    <item name="dataScope" xsi:type="string">'.$dbID.'</item>
                </item>
            </argument>
        </field>
        <field name="title">
            <argument name="data" xsi:type="array">
                <item name="config" xsi:type="array">
                    <item name="dataType" xsi:type="string">text</item>
                    <item name="label" xsi:type="string" translate="true">Title</item>
                    <item name="formElement" xsi:type="string">input</item>
                    <item name="sortOrder" xsi:type="number">20</item>
                    <item name="dataScope" xsi:type="string">title</item>
                    <item name="validation" xsi:type="array">
                        <item name="required-entry" xsi:type="boolean">true</item>
                    </item>
                </item>
            </argument>
        </field>
    </fieldset>
</form>'    
    );        
    writeStringToFile($uiComponentFilePath, formatXmlString($xml->asXml()));
}
/**
* One Line Description
*
* @command magento2:generate:ui:form
* @argument module Which Module? [Pulsestorm_Formexample]
* @argument model Model Class? [Pulsestorm\Formexample\Model\Thing]
* @argument aclRule ACL Rule for Controllers? [Pulsestorm_Formexample::ruleName]
*/
function pestle_cli($argv)
{
    $module_info      = getModuleInformation($argv['module']);
    output("In Progress, see @todo");
    output('@TODO: Do something about /index/ in URLs, handles');
    output('@TODO: <item name="source" xsi:type="string">page</item>? Needed?');
    
    createControllerFiles($module_info, $argv['model'], $argv['aclRule']);
    createDataProvider($module_info, $argv['model']);
    createLayoutXmlFiles($module_info, $argv['model']);
    createUiComponentXmlFile($module_info, $argv['model']);        
}
