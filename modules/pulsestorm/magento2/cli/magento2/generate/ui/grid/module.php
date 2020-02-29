<?php
namespace Pulsestorm\Magento2\Cli\Magento2_Generate_Ui_Grid;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
pestle_import('Pulsestorm\Xml_Library\simpleXmlAddNodesXpath');    
pestle_import('Pulsestorm\Xml_Library\formatXmlString');
pestle_import('Pulsestorm\Xml_Library\getXmlNamespaceFromPrefix');
pestle_import('Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml');
pestle_import('Pulsestorm\Magento2\Cli\Library\getModuleInformation');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Magento2\Cli\Library\createClassFile');
pestle_import('Pulsestorm\Cli\Code_Generation\createClassTemplateWithUse');
pestle_import('Pulsestorm\Magento2\Cli\Library\addArgument');
pestle_import('Pulsestorm\Magento2\Cli\Library\addItem');
function generateArgumentNode($xml, $gridId, $dataSourceName, $columnsName, $collection)
{
    $shortName = getShortModelNameFromResourceModelCollection(
        $collection);    
    $fullIdentifier = $gridId . '.' . $dataSourceName;
    
    $argument   = addArgument($xml, 'data', 'array');
    $js_config  = addItem($argument,'js_config','array');
    $provider   = addItem($js_config, 'provider', 'string', $fullIdentifier);      
    $deps       = addItem($js_config, 'deps', 'string', $fullIdentifier);      
    $spinner    = addItem($argument, 'spinner', 'string', $columnsName);         
    
    $buttons    = addItem($argument, 'buttons', 'array');
    $add        = addItem($buttons, 'add', 'array');
    addItem($add, 'name', 'string', 'add');
    addItem($add, 'label', 'string', 'Add New');
    addItem($add, 'class', 'string', 'primary');
    addItem($add, 'url', 'string', '*/'.$shortName.'/new');

    return $argument;
}

function addArgumentsToDataProvider($dataProvider, $providerClass, 
            $dataSourceName, $databaseIdName, $requestIdName)
{
    addArgument($dataProvider, 'class','string',$providerClass);
    addArgument($dataProvider, 'name','string',$dataSourceName);
    addArgument($dataProvider, 'primaryFieldName','string',$databaseIdName);
    addArgument($dataProvider, 'requestFieldName','string',$requestIdName);
    $dataForProvider = addArgument($dataProvider, 'data','array');
    
    $config     = addItem($dataForProvider, 'config','array');
    $update_url = addItem($config,'update_url','url');
    $update_url->addAttribute('path', 'mui/index/render');

    $storageConfig = addItem($config, 'storageConfig', 'array');    
    $indexField    = addItem($storageConfig, 'indexField', 'string', $databaseIdName);
//     <item name="storageConfig" xsi:type="array">
//         <item name="indexField" xsi:type="string">pulsestorm_commercebug_log_id</item>
//     </item>                    
    
}

function generateDatasourceNode($xml, $dataSourceName, $providerClass, $databaseIdName, $requestIdName)
{
    $dataSource      = simpleXmlAddNodesXpath($xml, "dataSource[@name=$dataSourceName]");
    
    $dataProvider    = addArgument($dataSource, 'dataProvider','configurableObject');
    addArgumentsToDataProvider($dataProvider, $providerClass, $dataSourceName, 
                                $databaseIdName, $requestIdName);
    
    $data            = addArgument($dataSource, 'data','array');
    $js_config       = addItem($data, 'js_config', 'array');
    $component       = addItem($js_config, 'component', 'string', 'Magento_Ui/js/grid/provider');
    return $dataSource;
}

function addBaseColumnItemNodes($config, $width, $indexField, $sortOrder=10)
{
    addItem($config, 'resizeEnabled', 'boolean', 'false');
    addItem($config, 'resizeDefaultWidth', 'string', $width);
    addItem($config, 'indexField', 'string', $indexField);
    addItem($config, 'sortOrder', 'number', $sortOrder);
}

function addIdColumnToColumns($columns, $data, $idColumn)
{
    $columnId = $columns->addChild('column');
    $columnId->addAttribute('name', $idColumn);
    $data = addArgument($columnId, 'data', 'array');
    $config = addItem($data, 'config', 'array');
    
    addItem($config, 'filter',  'string', 'textRange');
    addItem($config, 'sorting', 'string', 'asc');
    
    $id = addItem($config, 'label',   'string', 'ID');
    $id->addAttribute('translate', 'true');

}

function addActionsColumnToColumns($columns, $pageActionsClassName, $idColumn)
{
    $actionsColumn = $columns->addChild('actionsColumn');
    $actionsColumn->addAttribute('name','actions');
    $actionsColumn->addAttribute('class',$pageActionsClassName);
    $data = addArgument($actionsColumn, 'data','array');
    $config = addItem($data, 'config', 'array');
    addBaseColumnItemNodes($config, '107', $idColumn, 200);        
    return $actionsColumn;
}

function generateColumnsNode($xml, $columnsName, $pulsestorm_commercebug_log_id, $pageActionsClassName)
{
    $columns         = simpleXmlAddNodesXpath($xml, "columns[@name=$columnsName]");    
    $sectionColumns  = $columns->addChild('selectionsColumn');
    $sectionColumns->addAttribute('name','ids');
    $data = addArgument($sectionColumns, 'data', 'array');
    $config = addItem($data, 'config', 'array');
        
    addBaseColumnItemNodes($config, '55', $pulsestorm_commercebug_log_id, 10);            
    addIdColumnToColumns($columns, $data, $pulsestorm_commercebug_log_id);
    addActionsColumnToColumns($columns, $pageActionsClassName, $pulsestorm_commercebug_log_id);
                
    return $columns;
}

function generateListingToolbar($xml)
{
    $listingToolbar = $xml->addChild('listingToolbar');
    $listingToolbar->addAttribute('name', 'listing_top');

    $settings = $listingToolbar->addChild('settings');
    $settings->addChild('sticky', 'true');

    $paging = $listingToolbar->addChild('paging');
    $paging->addAttribute('name', 'listing_paging');

    $filters = $listingToolbar->addChild('filters');
    $filters->addAttribute('name', 'listing_filters');
}

function generateDataSourceNameFromGridId($grid_id)
{
    $dataSourceName   = $grid_id . '_data_source';
    return $dataSourceName;
}

function generateColumnsNameFromGridId($grid_id)
{
    $columnsName      = $grid_id . '_columns';
    return $columnsName;
}

function generateProdiverClassFromGridIdAndModuleInfo($grid_id, $module_info)
{
    $providerClass = $module_info->vendor . '\\' . $module_info->short_name . '\\' .
        'Ui\\Component\\Listing\\DataProviders\\' .
        str_replace(' ','\\',ucwords(str_replace('_', ' ', $grid_id)));    
    return $providerClass;    
}

function generatePageActionClassNameFromPackageModuleAndGridId($package, $moduleName, $gridId)
{
    $pageActionsClassName = 'Pulsestorm\Commercebug\Ui\Component\Listing\Column\PageActions';
    $pageActionsClassName = $package . '\\' . $moduleName . '\\' . 
        'Ui\Component\Listing\Column\\' . 
        ucwords(preg_replace('%[^a-zA-Z0-9]%', '', $gridId)) . '\\' .
        'PageActions';
    return $pageActionsClassName;        
}

function generateRequestIdName()
{
    return 'id';
}

function generateUiComponentXmlFile($gridId, $databaseIdName, $module_info, $collection)
{
    $pageActionsClassName = generatePageActionClassNameFromPackageModuleAndGridId(
        $module_info->vendor, $module_info->short_name, $gridId);
    $requestIdName    = generateRequestIdName();
    $providerClass    = generateProdiverClassFromGridIdAndModuleInfo($gridId, $module_info);    
    $dataSourceName   = generateDataSourceNameFromGridId($gridId);    
    $columnsName      = generateColumnsNameFromGridId($gridId);

    $xml             = simplexml_load_string(getBlankXml('uigrid'));        
    $argument        = generateArgumentNode($xml, $gridId, $dataSourceName, $columnsName, $collection);        
    $dataSource      = generateDatasourceNode($xml, $dataSourceName, $providerClass, $databaseIdName, $requestIdName);
    generateListingToolbar($xml);
    $columns         = generateColumnsNode($xml, $columnsName, $databaseIdName, $pageActionsClassName);
     
    $path = $module_info->folder . 
        '/view/adminhtml/ui_component/' . $gridId . '.xml';        
    output("Creating New $path");
    writeStringToFile($path, formatXmlString($xml->asXml()));
    return $xml;
}

function addVirtualType($xml, $virtualTypeName, $virtualTypeType)
{
    $virtualType = $xml->addChild('virtualType');
    $virtualType->addAttribute('name', $virtualTypeName);
    $virtualType->addAttribute('type', $virtualTypeType);    
    return $virtualType;
}

function generateDiXml($module_info)
{
    $path_di = $module_info->folder . '/etc/adminhtml/di.xml';
    if(!file_exists($path_di))
    {
        $xml =  simplexml_load_string(getBlankXml('di'));           
        writeStringToFile($path_di, $xml->asXml());
        output("Created new $path_di");
    }
    $xml = simplexml_load_file($path_di);
    
    $item = simpleXmlAddNodesXpath($xml, 
        'type[@name=Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory]/' .
        'arguments/argument[@name=collections,@xsi:type=array]/' .
        'item[@name=pulsestorm_commercebug_log_data_source,@xsi:type=string]' 
        
    );            
    $item[0] = 'Pulsestorm\Commercebug\Model\ResourceModel\Log\Grid\Collection';

    $virtualType = addVirtualType(
        $xml, 'Pulsestorm\Commercebug\Model\ResourceModel\Log\Grid\Collection',
        'Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult');
    
    $arguments   = $virtualType->addChild('arguments');
    $argument   = addArgument($arguments, 'mainTable', 'string', 'pulsestorm_commercebug_log');
    $argument   = addArgument($arguments, 'resourceModel', 'string', 'Pulsestorm\Commercebug\Model\ResourceModel\Log');
    
    return $xml;
}

function generatePageActionClass($moduleInfo, $gridId, $frontname, $idColumn, $collection)
{
    $pageActionsClassName = generatePageActionClassNameFromPackageModuleAndGridId(
        $moduleInfo->vendor, $moduleInfo->short_name, $gridId);
        
    // $editUrl              = 'adminhtml/'.$gridId.'/viewlog';        
    
    
    // $editUrl              = $gridId . '/index/edit';        
    $shortName = getShortModelNameFromResourceModelCollection(
        $collection);    
    $editUrl = $frontname . '/' . strToLower($shortName) . '/edit';
    $prepareDataSource    = '
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource["data"]["items"])) {
            foreach ($dataSource["data"]["items"] as & $item) {
                $name = $this->getData("name");
                $id = "X";
                if(isset($item["'.$idColumn.'"]))
                {
                    $id = $item["'.$idColumn.'"];
                }
                $item[$name]["view"] = [
                    "href"=>$this->getContext()->getUrl(
                        "'.$editUrl.'",["'.$idColumn.'"=>$id]),
                    "label"=>__("Edit")
                ];
            }
        }

        return $dataSource;
    }    
    ' . "\n";            
    $contents = createClassTemplateWithUse($pageActionsClassName, '\Magento\Ui\Component\Listing\Columns\Column');            
    $contents = str_replace('<$use$>','',$contents);
    $contents = str_replace('<$body$>', $prepareDataSource, $contents);
    
    output("Creating: $pageActionsClassName");
    $return   = createClassFile($pageActionsClassName,$contents);             
    return $return;
}

function generateDataProviderClass($moduleInfo, $gridId, $collectionFactory)
{
    $providerClass      = generateProdiverClassFromGridIdAndModuleInfo($gridId, $moduleInfo);    
    $collectionFactory  = '\\' . trim($collectionFactory, '\\');
    $constructor = '    
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        '.$collectionFactory.' $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }' . "\n";        
    
    $contents           = createClassTemplateWithUse($providerClass, '\\Magento\Ui\DataProvider\AbstractDataProvider');
    $contents           = str_replace('<$use$>', '',  $contents);
    $contents           = str_replace('<$body$>', $constructor,  $contents);    
    
    output("Creating: $providerClass");
    $return             = createClassFile($providerClass,$contents);    
    return $contents;
}

function getShortModelNameFromResourceModelCollection($collection)
{
    $parts = explode('\\', $collection);
    if($parts[3] !== 'ResourceModel' || $parts[(count($parts)-1)] !== 'Collection')
    {
        exitWithErrorMessage("Collection model name does not conform to the arbitrary naming convention we chose.  We're bailing.");
    }
    $parts = array_slice($parts, 4);
    array_pop($parts);
    $shortName =  implode('_', $parts);
    return $shortName;
}
/**
* Generates a Magento 2.1 ui grid listing and support classes.
*
* @command magento2:generate:ui:grid
* @argument module Which Module? [Pulsestorm_Gridexample]
* @argument frontname Frontname/Route ID? [pulsestorm_helloworld]
* @argument grid_id Create a unique ID for your Listing/Grid! [pulsestorm_gridexample_log]
* @argument collection_resource What Resource Collection Model should your listing use? [Magento\Cms\Model\ResourceModel\Page\Collection]
* @argument db_id_column What's the ID field for you model? [pulsestorm_gridexample_log_id]
*/
function pestle_cli($argv)
{
    $module_info      = getModuleInformation($argv['module']);

    generateUiComponentXmlFile(
        $argv['grid_id'], $argv['db_id_column'], $module_info, $argv['collection_resource']);                                        
        
    generateDataProviderClass(
        $module_info, $argv['grid_id'], $argv['collection_resource'] . 'Factory');
        
    generatePageActionClass(
        $module_info, $argv['grid_id'], $argv['frontname'], $argv['db_id_column'], $argv['collection_resource']);
        
    output("Don't forget to add this to your layout XML with <uiComponent name=\"{$argv['grid_id']}\"/> ");        
}
