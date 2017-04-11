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

function createControllerClassBodyForIndexRedirect($module_info, $modelClass, $aclRule)
{
    return '
    const ADMIN_RESOURCE = \''.$aclRule.'\';  
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath(\'*/index/index\');
        return $resultRedirect;
    }     
';
}

function createControllerClassBodyForDelete($module_info, $modelClass, $aclRule)
{    
    $dbID       = createDbIdFromModuleInfoAndModelShortName($module_info, getModelShortName($modelClass));    
    return '  
    const ADMIN_RESOURCE = \''.$aclRule.'\';   
          
    public function execute()
    {
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam(\'object_id\');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            $title = "";
            try {
                // init model and delete
                $model = $this->_objectManager->create(\''.$modelClass.'\');
                $model->load($id);
                $model->delete();
                // display success message
                $this->messageManager->addSuccess(__(\'You have deleted the object.\'));
                // go to grid
                return $resultRedirect->setPath(\'*/*/\');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath(\'*/*/edit\', [\''.$dbID.'\' => $id]);
            }
        }
        // display error message
        $this->messageManager->addError(__(\'We can not find an object to delete.\'));
        // go to grid
        return $resultRedirect->setPath(\'*/*/\');
        
    }    
    
';    
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
    const ADMIN_RESOURCE = \''.$aclRule.'\';       
    protected $resultPageFactory;
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory)
    {
        $this->resultPageFactory = $resultPageFactory;        
        parent::__construct($context);
    }
    
    public function execute()
    {
        return $this->resultPageFactory->create();  
    }    
';
}

function createControllerFiles($module_info, $modelClass, $aclRule)
{
    $shortName = getModelShortName($modelClass);
    // $moduleBasePath = getModuleBasePath();
    $prefix = $module_info->vendor . '\\' . $module_info->short_name;
    $classes = [
        'controllerEditClassname' => $prefix . '\Controller\Adminhtml\\'.$shortName.'\Edit',
        'controllerNewClassName'  => $prefix . '\Controller\Adminhtml\\'.$shortName.'\NewAction',
        'controllerSaveClassName' => $prefix . '\Controller\Adminhtml\\'.$shortName.'\Save'
    ];
    foreach($classes as $desc=>$className)
    {        
        $contents = createClassWithUse($className, '\Magento\Backend\App\Action', '', 
            createControllerClassBody($module_info, $aclRule));
        if($desc === 'controllerSaveClassName')
        {
            $useString = '
use Magento\Backend\App\Action;
use '.$prefix.'\Model\Page;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
            ';
            $contents = createClassWithUse($className, '\Magento\Backend\App\Action', $useString,
                createControllerClassBodyForSave($module_info, $modelClass, $aclRule));   
        }       
        output("Creating: $className");
        createClassFile($className,$contents);        
    }
    
    $deleteClassName = $prefix . '\Controller\Adminhtml\\'.$shortName.'\Delete';
    $useString       = '';
    $contents = createClassWithUse(
        $deleteClassName, '\Magento\Backend\App\Action', $useString,
        createControllerClassBodyForDelete($module_info, $modelClass, $aclRule));
    output("Creating: $deleteClassName");
    createClassFile($deleteClassName,$contents); 
        
    $indexRedirectClassName = $prefix . '\Controller\Adminhtml\\'.$shortName.'\Index';
    $useString       = '';
    $contents = createClassWithUse(
        $indexRedirectClassName, '\Magento\Backend\App\Action', $useString,
        createControllerClassBodyForIndexRedirect($module_info, $modelClass, $aclRule));
    output("Creating: $deleteClassName");
    createClassFile($indexRedirectClassName,$contents);             


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
    }
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
        strToLower(getModelShortName($modelClass))
        // 'index'
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

function generateGenericButtonClassAndReturnName($prefix, $dbID, $aclRule)
{

    $genericButtonClassName     = $prefix . '\\GenericButton';
    $genericButtonClassContents = createClassTemplateWithUse($genericButtonClassName);
    $genericButtonClassContents = str_replace('<$use$>' ,'', $genericButtonClassContents);
    
    $genericContents = '
    //putting all the button methods in here.  No "right", but the whole
    //button/GenericButton thing seems -- not that great -- to begin with
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context
    ) {
        $this->context = $context;    
    }
    
    public function getBackUrl()
    {
        return $this->getUrl(\'*/*/\');
    }    
    
    public function getDeleteUrl()
    {
        return $this->getUrl(\'*/*/delete\', [\'object_id\' => $this->getObjectId()]);
    }   
    
    public function getUrl($route = \'\', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }    
    
    public function getObjectId()
    {
        return $this->context->getRequest()->getParam(\''.$dbID.'\');
    }     
';    
    $genericButtonClassContents = str_replace('<$body$>',$genericContents, $genericButtonClassContents);    
    createClassFile($genericButtonClassName,$genericButtonClassContents);                   
    return $genericButtonClassName;
}

function generateButtonClassPrefix($modelClass)
{
    $prefix = str_replace('_','\\',getModuleNameFromClassName($modelClass)) . '\\Block\\Adminhtml\\' .
        getModelShortName($modelClass) . '\\Edit';
    return $prefix;
}

function getAllButtonDataStrings()
{        
    $singleQuoteForJs = "\\''";
    return [
        'back'=> '[
            \'label\' => __(\'Back\'),
            \'on_click\' => sprintf("location.href = \'%s\';", $this->getBackUrl()),
            \'class\' => \'back\',
            \'sort_order\' => 10    
        ]',
        'delete'=> '[
                \'label\' => __(\'Delete Object\'),
                \'class\' => \'delete\',
                \'on_click\' => \'deleteConfirm( '.$singleQuoteForJs.' . __(
                    \'Are you sure you want to do this?\'
                ) . \''.'\\'.'\', ' . $singleQuoteForJs . ' . $this->getDeleteUrl() . \''.'\\'.'\')\',
                \'sort_order\' => 20,
            ]',
        'reset'=> '[
            \'label\' => __(\'Reset\'),
            \'class\' => \'reset\',
            \'on_click\' => \'location.reload();\',
            \'sort_order\' => 30
        ]',        
        'save'=> '[
            \'label\' => __(\'Save Object\'),
            \'class\' => \'save primary\',
            \'data_attribute\' => [
                \'mage-init\' => [\'button\' => [\'event\' => \'save\']],
                \'form-role\' => \'save\',
            ],
            \'sort_order\' => 90,
        ]',                
        'save_and_continue'=> '[
            \'label\' => __(\'Save and Continue Edit\'),
            \'class\' => \'save\',
            \'data_attribute\' => [
                \'mage-init\' => [
                    \'button\' => [\'event\' => \'saveAndContinueEdit\'],
                ],
            ],
            \'sort_order\' => 80,
        ]',                        
    ];    
}

function getButtonDataStringForButton($buttonName)
{
    $buttons = getAllButtonDataStrings();
    if(!isset($buttons[$buttonName]))
    {
        output("Bailing -- I don't know how to create a [$buttonName] button");
        exit;
    }
    return $buttons[$buttonName];
}

function createButtonClassContents($buttonName)
{
    $buttonData = getButtonDataStringForButton($buttonName);
    $extra = '';
    if($buttonName === 'delete')
    {
        $extra = 'if(!$this->getObjectId()) { return []; }';
    }
    $contents = '     
    public function getButtonData()
    {
        '.$extra.'
        return '. $buttonData .';
    }' . "\n";
    return $contents;
    // return '//implement me for ' . $buttonName;
}
function generateButtonClassAndReturnName($modelClass, $buttonName)
{            
    $prefix = generateButtonClassPrefix($modelClass);
    $buttonClassName = $prefix .= '\\' . str_replace(' ', '', 
        ucWords(str_replace('_', ' ', $buttonName))) . 'Button';        
    
    $contents = createClassTemplateWithUse($buttonClassName, 'GenericButton', 
        'ButtonProviderInterface');
    $contents   = str_replace('<$use$>','use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;',$contents);
    $contents   = str_replace('<$body$>',createButtonClassContents($buttonName),$contents);
    createClassFile($buttonClassName,$contents);            
    
    return $buttonClassName;
}

function createButtonXml($module_info, $modelClass, $aclRule)
{
    //handle generic button
    $prefix = generateButtonClassPrefix($modelClass);
    $dbID   = createDbIdFromModuleInfoAndModelShortName($module_info, getModelShortName($modelClass));    
    generateGenericButtonClassAndReturnName($prefix, $dbID, $aclRule);    
    
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

function createUiComponentXmlFile($module_info, $modelClass, $aclRule)
{    
    $moduleBasePath      = $module_info->folder;
    $uiComponentBasePath = $moduleBasePath . '/view/adminhtml/ui_component';     
    $uiComponentName     = createUiComponentNameFromModuleInfoAndModelClass($module_info, $modelClass);
    $uiComponentFilePath = $uiComponentBasePath . '/' . $uiComponentName . '.xml';        
    $dbID       = createDbIdFromModuleInfoAndModelShortName($module_info, getModelShortName($modelClass));
    $dataProviderClassName = createDataProviderClassNameFromModelClassName($modelClass);          
    
    $buttonXml = createButtonXml($module_info, $modelClass, $aclRule);
         
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
* Generates a Magento 2 UI Component form configuration and PHP boilerplate
*
* @command magento2:generate:ui:form
* @argument module Which Module? [Pulsestorm_Formexample]
* @argument model Model Class? [Pulsestorm\Formexample\Model\Thing]
* @argument aclRule ACL Rule for Controllers? [Pulsestorm_Formexample::ruleName]
*/
function pestle_cli($argv)
{
    $module_info      = getModuleInformation($argv['module']);
    createControllerFiles($module_info, $argv['model'], $argv['aclRule']);
    createDataProvider($module_info, $argv['model']);
    createLayoutXmlFiles($module_info, $argv['model']);
    createUiComponentXmlFile($module_info, $argv['model'], $argv['aclRule']);
}
