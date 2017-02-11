<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Ui\Form;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\getModuleInformation');
pestle_import('Pulsestorm\Cli\Code_Generation\createClassTemplateWithUse');
pestle_import('Pulsestorm\Magento2\Cli\Library\createClassFile');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Xml_Library\formatXmlString');

function getModelShortName($module_fullname, $model_class)
{
    $regex = '%^' . $module_fullname . '_Model_%six';
    return preg_replace($regex, '', $model_class);
}

function createControllerClassBodyForSave($module_info)
{
    output('@TODO: Make this dynamic/real');
    return '
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = \'Pulsestorm_Pestleform::replies\';

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
                $data[\'is_active\'] = \Pulsestorm\Pestleform\Model\Reply::STATUS_ENABLED;
            }
            if (empty($data[\'pulsestorm_pestleform_reply_id\'])) {
                $data[\'pulsestorm_pestleform_reply_id\'] = null;
            }

            /** @var \Pulsestorm\Pestleform\Model\Reply $model */
            $model = $this->_objectManager->create(\'Pulsestorm\Pestleform\Model\Reply\');

            $id = $this->getRequest()->getParam(\'pulsestorm_pestleform_reply_id\');
            if ($id) {
                $model->load($id);
            }

            $model->setData($data);

            try {
                $model->save();
                $this->messageManager->addSuccess(__(\'You saved the thing.\'));
                $this->dataPersistor->clear(\'pulsestorm_pestleform_reply\');
                if ($this->getRequest()->getParam(\'back\')) {
                    return $resultRedirect->setPath(\'*/*/edit\', [\'pulsestorm_pestleform_reply_id\' => $model->getId(), \'_current\' => true]);
                }
                return $resultRedirect->setPath(\'*/*/\');
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __(\'Something went wrong while saving the data.\'));
            }

            $this->dataPersistor->set(\'pulsestorm_pestleform_reply\', $data);
            return $resultRedirect->setPath(\'*/*/edit\', [\'pulsestorm_pestleform_reply_id\' => $this->getRequest()->getParam(\'pulsestorm_pestleform_reply_id\')]);
        }
        return $resultRedirect->setPath(\'*/*/\');
    }    
';
}

function createControllerClassBody($module_info)
{
    output('@TODO: Make this dynamic/real');
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
        return $this->_authorization->isAllowed(\'Pulsestorm_Pestleform::replies\');
    }         
';
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
        $contents = createClassWithUse($className, '\Magento\Backend\App\Action', '', 
            createControllerClassBody($module_info));
        if($desc === 'controllerSaveClassName')
        {
            $contents = createControllerClassBodyForSave($module_info);
        }       
        output("Creating: $className");
        $return             = createClassFile($className,$contents);        
    }
}

function createDataProviderUseString($module_info, $modelClass)
{
    output('@TODO Be dynamic here, not static');
    $collectionClassName = 'Pulsestorm\Pestleform\Model\ResourceModel\Reply\CollectionFactory';
    
    return 'use '.$collectionClassName.';
use Magento\Framework\App\Request\DataPersistorInterface;';
}

function createDataProviderClassBodyString($module_info, $modelClass)
{
    output('@TODO Be dynamic here, not static, change/remove CMS stuff');
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

        $data = $this->dataPersistor->get(\'pulsestorm_pestleform_reply\');
        if (!empty($data)) {
            $item = $this->collection->getNewEmptyItem();
            $item->setData($data);
            $this->loadedData[$item->getId()] = $item->getData();
            $this->dataPersistor->clear(\'pulsestorm_pestleform_reply\');
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


function createDataProvider($module_info, $modelClass)
{
    output('@TODO: generate data provider class contents');
    // $moduleBasePath = getModuleBasePath();
    $dataProviderClassName = $modelClass . '\DataProvider';
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
    
    output('@TODO: Do something about /index/ in URLs, handles');
    output('@TODO: Are the layout files the right schema?.');
}

function createUiComponentNameFromModuleInfoAndModelClass($module_info, $modelClass)
{
    return implode('_', [
        strToLower($module_info->name),
        createShortPluralModelName($modelClass),
        'form'
    ]);

}

function createUiComponentXmlFile($module_info, $modelClass)
{
    $moduleBasePath      = $module_info->folder;
    $uiComponentBasePath = $moduleBasePath . '/view/adminhtml/ui_component'; 
    
    $uiComponentName     = createUiComponentNameFromModuleInfoAndModelClass($module_info, $modelClass);
    $uiComponentFilePath = $uiComponentBasePath . '/' . $uiComponentName . '.xml';    
    output('@TODO: All those buttons -- what to do?');
    output('@TODO: Source -- is that needed?');
    $xml = simplexml_load_string(
'<?xml version="1.0" encoding="UTF-8"?>
<form xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd">
    <argument name="data" xsi:type="array">
        <item name="js_config" xsi:type="array">
            <item name="provider" xsi:type="string">pulsestorm_pestleform_replies_form.pulsestorm_pestleform_replies_form_data_source</item>
            <item name="deps" xsi:type="string">pulsestorm_pestleform_replies_form.pulsestorm_pestleform_replies_form_data_source</item>
        </item>
        <item name="label" xsi:type="string" translate="true">Object Information</item>
        <item name="config" xsi:type="array">
            <item name="dataScope" xsi:type="string">data</item>
            <item name="namespace" xsi:type="string">pulsestorm_pestleform_replies_form</item>
        </item>
        <item name="template" xsi:type="string">templates/form/collapsible</item>
        <item name="buttons" xsi:type="array">
            <item name="back" xsi:type="string">Magento\Cms\Block\Adminhtml\Page\Edit\BackButton</item>
            <item name="delete" xsi:type="string">Magento\Cms\Block\Adminhtml\Page\Edit\DeleteButton</item>
            <item name="reset" xsi:type="string">Magento\Cms\Block\Adminhtml\Page\Edit\ResetButton</item>
            <item name="save" xsi:type="string">Magento\Cms\Block\Adminhtml\Page\Edit\SaveButton</item>
            <item name="save_and_continue" xsi:type="string">Magento\Cms\Block\Adminhtml\Page\Edit\SaveAndContinueButton</item>
        </item>
    </argument>
    <dataSource name="pulsestorm_pestleform_replies_form_data_source">
        <argument name="dataProvider" xsi:type="configurableObject">
            <argument name="class" xsi:type="string">Pulsestorm\Pestleform\Model\Reply\DataProvider</argument>
            <argument name="name" xsi:type="string">pulsestorm_pestleform_replies_form_data_source</argument>
            <argument name="primaryFieldName" xsi:type="string">pulsestorm_pestleform_reply_id</argument>
            <argument name="requestFieldName" xsi:type="string">pulsestorm_pestleform_reply_id</argument>
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
        <field name="pulsestorm_pestleform_reply_id">
            <argument name="data" xsi:type="array">
                <item name="config" xsi:type="array">
                    <item name="visible" xsi:type="boolean">false</item>
                    <item name="dataType" xsi:type="string">text</item>
                    <item name="formElement" xsi:type="string">input</item>
                    <item name="source" xsi:type="string">page</item>
                    <item name="dataScope" xsi:type="string">pulsestorm_pestleform_reply_id</item>
                </item>
            </argument>
        </field>
        <field name="title">
            <argument name="data" xsi:type="array">
                <item name="config" xsi:type="array">
                    <item name="dataType" xsi:type="string">text</item>
                    <item name="label" xsi:type="string" translate="true">Title</item>
                    <item name="formElement" xsi:type="string">input</item>
                    <item name="source" xsi:type="string">page</item>
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
    
    output('@TODO: Make this dynamic/real');
    writeStringToFile($uiComponentFilePath, formatXmlString($xml->asXml()));
 
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

    output("In Progress, see @todo");

    createControllerFiles($module_info);
    createDataProvider($module_info, $argv['model']);
    createLayoutXmlFiles($module_info, $argv['model']);
    createUiComponentXmlFile($module_info, $argv['model']);    
    
}
