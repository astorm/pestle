<?php
namespace Pulsestorm\Magento2\Cli\Testbed;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\input');
pestle_import('Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir');
pestle_import('Pulsestorm\Xml_Library\formatXmlString');
pestle_import('Pulsestorm\Cli\Token_Parse\pestle_token_get_all');
pestle_import('Pulsestorm\Cli\Token_Parse\getFunctionNamesFromCode');
pestle_import('Pulsestorm\Cli\Token_Parse\getFunctionFromCode');
pestle_import('Pulsestorm\Cli\Token_Parse\getParsedFunctionInfoFromCode');

function getFrontendModelNodesFromMagento1SystemXml($xmls)
{
    $items = [];
    foreach($xmls as $xml_file)
    {        
        $xml = simplexml_load_file($xml_file);
        $items[$xml_file] = [];        
        foreach($xml->sections->children() as $section)
        {
            $strSection = $section->getName();
            foreach($section->groups->children() as $group)
            {
                $strGroup = $group->getName();
                foreach($group->fields->children() as $field)
                {
                    if($field->frontend_model)
                    {
                        $strField = $field->getName();
                        $items[$xml_file][] = implode('/', 
                        [$strSection, $strGroup, $strField]) . '::' . 
                        (string) $field->frontend_model;
                    }
                }
            }
        }        
    }
    
    return $items;
}

function getSectionXmlNodeFromSectionGroupAndField($xml,$section, $group, $field)
{
    $xpath = "/config/system/section[@id='$section']/group[@id='$group']/field[@id='$field']";
    $nodes = $xml->xpath($xpath);
    if(count($nodes) === 0)
    {
        throw new \Exception("Did no find node");
    }
    return array_shift($nodes);
}

function backupOldCode($arguments, $options)
{

    $xmls = [
    ];
    
    $frontend_models = getFrontendModelNodesFromMagento1SystemXml($xmls);

    foreach($frontend_models as $file=>$nodes)
    {
        $new_file = str_replace(
            ['/Users/alanstorm/Sites/magento-1-9-2-2.dev','/local'],
            '', $file);
        $new_file = getBaseMagentoDir() . 
            str_replace('/etc/', '/etc/adminhtml/', $new_file);            
        
        $xml = simplexml_load_file($new_file);
        
        foreach($nodes as $node)
        {
            list($path, $frontend_alias)   = explode('::', $node);
            list($section, $group, $field) = explode('/', $path);
            
            $node = getSectionXmlNodeFromSectionGroupAndField($xml, 
                $section, $group, $field);

            if($node->frontend_model)
            {
                output("The frontend_model node already exists: " . $path);
                continue;
            }

            $class = convertAliasToClass($frontend_alias);
            $node->frontend_model = $class;
        }
        
        file_put_contents($new_file, formatXmlString($xml->asXml()));
    }
    //search XML files
    // $base = getBaseMagentoDir();
    // $files = `find $base -name '*.xml'`;
    // $files = preg_split('%[\r\n]%', $files);  
    // $files = array_filter($files, function($file){
    //     return strpos($file, '/view/') !== false &&
    //     !is_dir($file);    
    // });
    // 
    // $report;
    // foreach($files as $file)
    // {
    //     $xml = simplexml_load_file($file);
    //     if(!$xml->head){ continue; }
    //     output($file);        
    //     foreach($xml->head->children() as $node)
    //     {
    //         output('    ' . $node->getName());
    //     }
    // }
  

}

define('PARSE_FOUND_NAMESPACE', 1);
define('PARSE_FOUND_LINE_END', 2);
define('PARSE_FOUND_USE', 3);
function parseNamespaceFromTokens($tokens)
{
    $state = 0;
    $all   = [];
    $namespace_tokens = [];
    foreach($tokens as $token)
    {
        if($token->token_name === 'T_NAMESPACE')
        {
            $state = PARSE_FOUND_NAMESPACE;
        }
        if($state === PARSE_FOUND_NAMESPACE && $token->token_value !== ';')
        {
            $namespace_tokens[] = $token;
        }
        if($state === PARSE_FOUND_NAMESPACE && $token->token_value === ';')
        {
            $state = PARSE_FOUND_LINE_END;
            $all[] = $namespace_tokens;
            $namespace_tokens = [];
        }        
    }
    return $all;
}

function parseUsesFromTokens($tokens)
{
    $state = 0;
    $all   = [];
    $namespace_tokens = [];
    foreach($tokens as $token)
    {
        if($token->token_name === 'T_USE')
        {
            $state = PARSE_FOUND_USE;
        }
        if($state === PARSE_FOUND_USE && $token->token_value !== ';')
        {
            $namespace_tokens[] = $token;
        }
        if($state === PARSE_FOUND_USE && $token->token_value === ';')
        {
            $state = PARSE_FOUND_LINE_END;
            $all[] = $namespace_tokens;
            $namespace_tokens = [];
        }        
    }
    return $all;
}

function parseClassCodeFromTokens($tokens)
{
    //type hints in functions
    
    //after `new` keyword
    
    //directly before ::
    
    //in all of above, don't forget namespace seperator
    print_r($tokens);
    exit;
}

function parseSetupDiCompileReport()
{
    $contents   = file_get_contents('/Users/alanstorm/Dropbox/Untitled/Notes_2016-01-19_14-49-06');
    $contents   = preg_match_all('%
    Incorrect[ ]dependency[ ]in[ ]class[ ]
    (.+?)
    [ ]in[ ].+?php[\r\n](.+?)\t
    %six', $contents, $matches, PREG_SET_ORDER);
    
    $report = [];
    foreach($matches as $match)
    {
        if(!array_key_exists($match[1], $report))
        {
            $report[$match[1]] = [];
        }
        
        $report[$match[1]] = array_merge($report[$match[1]], preg_split('%[\\r\n]%', $match[2]));
    }
    
    foreach($report as $key=>$errors)
    {
        $errors = array_map(function($error){
            $parts      = explode(' ', $error);
            return array_shift($parts);
        }, $errors);
        
        $errors = array_filter($errors, function($error){
            $error = trim($error);
            return !in_array($error, ['Total', 'Errors']);
        });        
        $report[$key]   = array_unique($errors);
    }
    
    foreach($report as $key=>$errors)
    {
        output($key);
        foreach($errors as $error)
        {
            output('    ' . $error);
        }
    }
}

function testbedParsing()
{

    // inProgressParsing();
    
    $urls = [
        "http://stackoverflow.com/questions/5412950/how-would-i-pull-the-content-of-a-cms-page-into-a-static-block/5413698",
        "http://stackoverflow.com/questions/5412950/how-would-i-pull-the-content-of-a-cms-page-into-a-static-block",
        "http://topwebseiten.de/mage-news.de",
        "http://www.venchina.com/noticia/venezuela/2014-01-13/141565.html",
        "http://www.venchina.com/noticia/ent/index_181.html",
        "http://www.venchina.com/noticia/china/2013-03-09/136348.html",
        "http://forum.azmagento.com/magento-users-guide/how-would-i-pull-the-content-of-a-cms-page-into-a-static-block-3267.html",
        "http://www.mage-news.de/startseite?page=128",
        "http://www.mage-news.de/englische-news?page=88",
        "http://answerlists.com/question/137218/how-would-i-pull-the-content-of-a-cms-page-into-a-static-block",
        "http://gootomain.com/question/137218/how-would-i-pull-the-content-of-a-cms-page-into-a-static-block",
    ];
    foreach($urls as $url)
    {
        $html = `curl $url`;
        $html = str_replace('>',">\n",$html);
        preg_match_all('%^.*alanstorm\.com.*$%m',$html, $matches);    
        foreach($matches[0] as $match)
        {
            echo $match,"\n";
        }
    }

}

function exampleOfACallback($arguments, $index)
{
    return 'Value of Argument';
}

function getOldToNewClassMap()
{
    $files = [
//         'app/code/Package/Module/Model/System//Config/Backend/Design/Color/Validatetransparent.php',
//         'app/code/Package/Module/Model/System//Config/Source/Category/Grid/Columncount.php',
//         'app/code/Package/Module/Model/System//Config/Source/Css/Background/Attachment.php',
//         'app/code/Package/Module/Model/System//Config/Source/Css/Background/Positionx.php',
//         'app/code/Package/Module/Model/System//Config/Source/Css/Background/Positiony.php',
//         'app/code/Package/Module/Model/System//Config/Source/Css/Background/Repeat.php',
//         'app/code/Package/Module/Model/System//Config/Source/Design/Font/Family/Google.php',
//         'app/code/Package/Module/Model/System//Config/Source/Design/Font/Family/Groupcustomgoogle.php',
//         'app/code/Package/Module/Model/System//Config/Source/Design/Font/Google/Subset.php',
//         'app/code/Package/Module/Model/System//Config/Source/Design/Font/Size/Basic.php',
//         'app/code/Package/Module/Model/System//Config/Source/Design/Icon/Color/Bw.php',
//         'app/code/Package/Module/Model/System//Config/Source/Design/Icon/Color/Bwhover.php',
//         'app/code/Package/Module/Model/System//Config/Source/Design/Section/Sidepadding.php',
//         'app/code/Package/Module/Model/System//Config/Source/Design/Section/Sidepaddingvalue.php',
//         'app/code/Package/Module/Model/System//Config/Source/Js/Jquery/Easing.php',
//         'app/code/Package/Module/Model/System//Config/Source/Layout/Element/Displayonhover.php',
//         'app/code/Package/Module/Model/System//Config/Source/Layout/Element/Replacewithblock.php',
//         'app/code/Package/Module/Model/System//Config/Source/Layout/Screen/Width/Widecustom.php'

        'app/code/Package/Module/Model/System/Config/Backend/Header/Centralcolunits.php',
        'app/code/Package/Module/Model/System/Config/Backend/Header/Leftcolunits.php',
        'app/code/Package/Module/Model/System/Config/Backend/Header/Rightcolunits.php',
        'app/code/Package/Module/Model/System/Config/Backend/Productpage/Imgcolunits.php',
        'app/code/Package/Module/Model/System/Config/Source/Category/Altimagecolumn.php',
        'app/code/Package/Module/Model/System/Config/Source/Category/Grid/Columncount.php',
        'app/code/Package/Module/Model/System/Config/Source/Category/Grid/Columncountmobile.php',
        'app/code/Package/Module/Model/System/Config/Source/Category/Grid/Columncountmobile.php',
        'app/code/Package/Module/Model/System/Config/Source/Category/Grid/Columncountmobile.php',
        'app/code/Package/Module/Model/System/Config/Source/Category/Grid/Hovereffect/Below.php',
        'app/code/Package/Module/Model/System/Config/Source/Category/Grid/Hovereffect/Below.php',
        'app/code/Package/Module/Model/System/Config/Source/Category/Grid/Size.php',
        'app/code/Package/Module/Model/System/Config/Source/Design/Tex/Names.php',
        'app/code/Package/Module/Model/System/Config/Source/Design/Tex/Names.php',
        'app/code/Package/Module/Model/System/Config/Source/Design/Tex/Names.php',
        'app/code/Package/Module/Model/System/Config/Source/Design/Tex/Names.php',
        'app/code/Package/Module/Model/System/Config/Source/Design/Tex/Names.php',
        'app/code/Package/Module/Model/System/Config/Source/Design/Tex/Names.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Position/Primary.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Position/Primarymenucontainer.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Position/Primarytop.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Position/Primarytop.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Position/Primarytop.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Position/Primarytopusermenu.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Position/Primarytopusermenuinsidemenu.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Position/Primarytopusermenuinsidemenu.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Position/Primarytopusermenuinsidemenu.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Usermenu/LineBreak/Position.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Usermenu/Position.php',
        'app/code/Package/Module/Model/System/Config/Source/Navshadow.php',
        'app/code/Package/Module/Model/System/Config/Source/Product/Position/All.php',
        'app/code/Package/Module/Model/System/Config/Source/Product/Position/All.php',
        'app/code/Package/Module/Model/System/Config/Source/Product/Position/All.php',
        'app/code/Package/Module/Model/System/Config/Source/Product/Related/Template.php',
        'app/code/Package/Module/Model/System/Config/Source/Product/Tabs/Mode.php',        


        ];

    $classes = array_map(function($item){
        $map = [
            'app/code/'=>'',
            '//'=>'/',
            '/'=>'\\',
            '.php'=>''
        ];
        $item = str_replace(array_keys($map), array_values($map), $item);
        return $item;
    }, $files);        
    
    $old_to_new = [];
    foreach($classes as $class)
    {
        $old_to_new[$class] = str_replace('Package\Module', 'Package\Module', $class);
    }
    return $old_to_new;
}

function classToPath($string)
{
    $map = [
        '\\'=>'/'
    ];
    return 'app/code/' . str_replace(
        array_keys($map), array_values($map), $string) . '.php'; 
}

function movingClasses()
{

    $oldToNew = getOldToNewClassMap();
    $pathSystemXml = 'app/code/Package/Module/etc/adminhtml/system.xml';
    $systemXmlContents    = file_get_contents($pathSystemXml);
    $newSystemXmlContents = $systemXmlContents;
    foreach($oldToNew as $old=>$new)
    {
        $newSystemXmlContents = str_replace($old, $new,$newSystemXmlContents);    
        $old_path = classToPath($old);
        $new_path = classToPath($new); 
        
        //creates directory
        $dir = dirname($new_path);
        if(!is_dir($dir))
        {
            output("Creating Dir: " . $dir);
            `mkdir -p $dir`;
        }  
        
        //moves file           
        if(file_exists($old_path))
        {
            output("Moving $old_path");
            `mv $old_path $new_path`;
        }
        
        //changes namespace
        if(file_exists($new_path))
        {
            $contents = file_get_contents($new_path);
            $contents_new = preg_replace(
                '%namespace Package\\\Module%',
                 'namespace Package\Module',
                 $contents);
            if($contents_new !== $contents)
            {
                output("Rewriting $new_path");
                file_put_contents($new_path, $contents_new);
            }                 
        }                
    }
    if($newSystemXmlContents !== $systemXmlContents)
    {
        output("Rewriting $pathSystemXml");
        file_put_contents($pathSystemXml, $newSystemXmlContents);
    }
    output("done");              
}

function eavQuery()
{
    $id = 37;
    $tables = [
        'catalog_product_entity_datetime',
        'catalog_product_entity_decimal',
        'catalog_product_entity_int',
        'catalog_product_entity_text',
        'catalog_product_entity_varchar'];
    
    $sql = '';
    foreach($tables as $table)
    {
        $sql .= "
    SELECT eav_attribute.attribute_code, main_table.value_id, main_table.attribute_id, main_table.store_id, main_table.entity_id, value 
    FROM $table main_table  
    LEFT JOIN eav_attribute ON eav_attribute.attribute_id = main_table.attribute_id
    WHERE entity_id IN ($id)        
    UNION
        ";
    }
    
    output("\n", $sql);
}

function getDatasourceClass($xml)
{
    $nodes = $xml->xpath('/listing/dataSource//argument[@name="class"]');
    $node = array_shift($nodes);
    return (string) $node;
}

function getFilesArray($folder)
{
    $files = `find $folder -name '*.xml'`;
    $files = preg_split('%[\r\n]%',$files);   
    $new   = [];
    foreach($files as $file)
    {
        $new[$file] = $file;
    } 

    return $new;
}

function loadXmlListingsFiles($files)
{
    $xmls   = array_map(function($file){
        $xml = @simplexml_load_file($file);
        return $xml;
    }, $files);
    $xmls = array_filter($xmls, function($xml){
        if(!$xml) { return false;}
        return $xml->getName() === 'listing';
    });
    return $xmls;
}

function getDataProviderClassesFromListing($xmls)
{
    $return = [];
    foreach($xmls as $file=>$xml)
    {
        $dataProviderClass = getDatasourceClass($xml);
        $return[$file] = $dataProviderClass;
    }
    $return = array_filter($return);
    return $return;
}

function getMaxClassLength($dataProviders)
{
    $max = 0;
    foreach($dataProviders as $file=>$class)
    {
        $length = strlen($class);
        if($length > $max)
        {
            $max = $length;
        }
    }
    return $max;
}

function getUniqueNameOfColumnsChildren($xmls, $columnsSubNode='columns')
{
    foreach($xmls as $file=>$xml)
    {
        $allColumns = $xml->xpath('//'.$columnsSubNode);
        
        foreach($allColumns as $columns)
        {
            foreach($columns->children() as $child)
            {
                $names[] = $child->getName();
            }
        }
    }
    $names = array_filter(array_unique($names), function($item){
        return $item !== 'argument';
    });;
    
    $known          = ['column','selectionsColumn','actionsColumn'];
    sort($known);
    sort($names);  
    if($names !== $known)
    {
        output("New column type I don't know about, bailing");
        exit;
    }
    return $names;       
}

function reportDataProviderToListingXmlFileMap($xmls)
{
    // find grid listing => data provider class name mappings            
    $dataProviders  = getDataProviderClassesFromListing($xmls);
    $max            = getMaxClassLength($dataProviders);
    foreach($dataProviders as $file=>$class)
    {
        $indent = str_pad(' ',($max + 5) - strlen($class));
        output($class . $indent . basename($file));
    }
}

function bailIfNonDataArgument($columns)
{
    foreach($columns as $column)
    {
        foreach($column->children() as $item)
        {
            if((string) $item['name'] !== 'data' || $item->getName() !== 'argument')
            {
                output("A <column/> sub-node that's not a data argument?! Bailing");
                exit;
            }                
        }
    }
}

function getConfigFieldNamesForColumnNodes($xmls, $columnsSubNode='column')
{
    foreach($xmls as $file=>$xml)
    {        
        $columns = $xml->xpath('//' . $columnsSubNode);
        bailIfNonDataArgument($columns);
        
        foreach($columns as $column)
        {            
            foreach($column->argument->children() as $node)
            {
                if(!in_array($node['name'], ['options','config']))
                {
                    var_dump($node->asXml());
                    var_dump(__FUNCTION__);
                    exit;
                }                
                if((string)$node['name'] !== 'config')
                {
                    continue;
                }
                $tmp = [];
                foreach($node->children() as $item)
                {
                    $tmp[] = (string) $item['name'];                    
                }
                sort($tmp);                
                $configs[] = $tmp;
            }            
        }        
    } 
    
    usort($configs, function($a, $b){
        if(count($a) > count($b))
        {
            return 1;
        }
        if(count($a) < count($b))
        {
            return -1;
        }
        return 0;
    });
    
    return $configs;    
}

function reportOnOptionsArgumentAndDataTypes($xmls)
{
    foreach($xmls as $file=>$xml)
    {                
        // output($file);    
        $columns = $xml->xpath('//column');
        bailIfNonDataArgument($columns);
        
        foreach($columns as $column)
        {
            // output($column->asXml());
            // output($column->getName());
            // output($column->getName());
            $doc = simplexml_load_string('<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' . $column->asXml() . '</root>');
            $hasOptionsItem = $doc->xpath('//item[@name="options"]');
            if(count($hasOptionsItem) === 0)
            {
                continue;
            }
            
            $dataTypes = $doc->xpath('//item[@name="dataType"]');
            if(count($dataTypes) !== 1)            
            {
                output("More than one datatype, bailing");
                var_dump($dataTypes);
                exit;
            }
            $dataType = array_shift($dataTypes);
            $dataType = (string) $dataType;
            
            if($dataType !== 'select')
            {
                output($file);
                output($dataType);
                output($column->asXml());
            }
            
        }
    }     
}

function reportValidateDateComponents($xmls)
{
    foreach($xmls as $file=>$xml)
    {                
        $columns = $xml->xpath('//column');
        // bailIfNonDataArgument($columns);
        
        foreach($columns as $column)
        {
            $doc = simplexml_load_string(
                '<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' .
                $column->asXml() . 
                '</root>');
                
            $dataTypes = $doc->xpath('//item[@name="dataType"]');
            $node      = array_shift($dataTypes);
            if(!$node){ continue;}
            if ( (string)$node !== 'date') { continue;}
            // output($column->asXml());
            
            if((string)$column['class'] !== 'Magento\Ui\Component\Listing\Columns\Date')
            {
                output("Date column with incorrect(?) class, bailing");
                exit;
            }
            $components = $doc->xpath('//item[@name="component"]');
            $component  = array_shift($components);
            if(!$component)
            {
                output("There's no component configured, bailing");
                exit;
            }
            if((string) $component !== 'Magento_Ui/js/grid/columns/date')
            {
                output("There's an incorrect(?) component configured, bailing");
                exit;
            }
            
        }
    } 
}

function reportOnNamedDataConfig($xmls, $name)
{
    foreach($xmls as $file=>$xml)
    {                
        $columns = $xml->xpath('//column/argument/item[@name="config"]/item[@name="'.$name.'"]');
        foreach($columns as $column)
        {
            output($column->asXml());
        }
    } 
}

function reportUniqueCombinations($xmls, $uniqueConfigCombinations, $columnsSubNode='column')
{
    foreach($uniqueConfigCombinations as $string)
    {
        output('$toCheck="'.$string.'";');
    }
    
    foreach($xmls as $file=>$xml)
    {        
        $columns = $xml->xpath('//'. $columnsSubNode);
        bailIfNonDataArgument($columns);
        
        foreach($columns as $column)
        {            
            foreach($column->argument->children() as $node)
            {
                if(!in_array($node['name'], ['options','config']))
                {
                    var_dump($node->asXml());
                    var_dump(__FUNCTION__);
                    exit;
                }                
                if((string)$node['name'] !== 'config')
                {
                    continue;
                }
                $names = [];
                foreach($node->children() as $item)
                {
                    $names[] = (string) $item['name'];                    
                }
                sort($names); 
                
                //START <columns>
                $toCheck = 'filter,label';                
                $toCheck = 'dataType,label';
                $toCheck = 'label,sortOrder';
                $toCheck = "filter,label,sorting";
                $toCheck="filter,label,visible";
                $toCheck="editor,filter,label";
                $toCheck="bodyTmpl,label,sortable";
                $toCheck="filter,label,sortOrder";
                $toCheck="label,sortOrder,visible";
                // $toCheck="label,sortOrder,sortable";
                // $toCheck="dataType,filter,label";
                // $toCheck="bodyTmpl,label,sortOrder";
                // $toCheck="bodyTmpl,filter,label,visible";
                // $toCheck="component,dataType,filter,label";
                // $toCheck="bodyTmpl,label,sortable,visible";
                // $toCheck="filter,label,sortOrder,sorting";
                $toCheck="add_field,filter,label,sortOrder";
                // $toCheck="editor,filter,label,visible";
                // $toCheck="add_field,label,sortOrder,visible";
                // $toCheck="editor,filter,label,sortOrder";
                // $toCheck="add_field,dataType,label,sortOrder";
                // $toCheck="dataType,filter,label,sortOrder";
                // $toCheck="dataType,label,sortOrder,visible";
                // $toCheck="component,dataType,filter,label,sortOrder";
                // $toCheck="component,dataType,filter,label,sorting";
                // $toCheck="component,dataType,filter,label,visible";
                // $toCheck="add_field,component,dataType,label,sortOrder";
                // $toCheck="editor,filter,label,sortOrder,visible";
                // $toCheck="component,dataType,editor,filter,label";
                // $toCheck="component,dataType,dateFormat,filter,label";
                $toCheck="escape,filter,label,nl2br,sortOrder,truncate";
                // $toCheck="component,dataType,filter,label,sortOrder,visible";
                // $toCheck="component,dataType,editor,filter,label,sortOrder";
                // $toCheck="add_field,component,dataType,filter,label,sortOrder";
                // $toCheck="component,dataType,editor,filter,label,visible";
                $toCheck="add_field,altField,component,has_preview,label,sortOrder,sortable";
                // $toCheck="add_field,component,dataType,filter,label,sortOrder,visible";
                // $toCheck="component,dataType,editor,filter,label,sortOrder,visible";
                $toCheck="add_field,align,altField,component,has_preview,label,sortOrder,sortable";
                $toCheck="component,dataType,dateFormat,editor,filter,label,timezone,visible";
                // $toCheck="component,dataType,dateFormat,filter,label,sortOrder,timezone,visible";
                // END   </columns>
                
                //START <actionsColumn>
                // $toCheck="indexField";
                // $toCheck="indexField,sortOrder";
                // $toCheck="editUrlPath,indexField";
                // $toCheck="indexField,urlEntityParamName,viewUrlPath";
                // $toCheck="indexField,resizeDefaultWidth,resizeEnabled";                
                //END   </actionsColumn>                 
                
                //START <selectionsColumn>                               
                $toCheck="indexField";
                $toCheck="indexField,sortOrder";
                $toCheck="indexField,preserveSelectionsOnFilter,sortOrder";
                $toCheck="indexField,resizeDefaultWidth,resizeEnabled";                
                //END   </selectionsColumn>
                if(implode(',', $names) === $toCheck)
                {
                    output($file);
                    output($column->asXml());
                    output('+--------------------------------------------------+');
                }
            }            
        }        
    } 
}

function getUniqueCombinationsFromConfigs($configs)
{
    $uniqueConfigCombinations = array_values(
        array_unique(array_map(function($item){
            return implode(',', $item);
        }, $configs))
    );
    
    return $uniqueConfigCombinations;
}

function getAllConfigItemsFromConfigs($configs)
{
    $allConfigItems = array_values(array_unique(
        array_reduce($configs, function($carry, $item){
            $carry = $carry ? $carry : [];
            return array_merge($carry, $item);
        })
    ));
    return $allConfigItems;
}

function getUniqueCombinationsFromXmls($xmls, $columnsSubNode)
{
    $configs        = getConfigFieldNamesForColumnNodes($xmls, $columnsSubNode);
    $allConfigItems = getAllConfigItemsFromConfigs($configs);    
    $uniqueConfigCombinations = getUniqueCombinationsFromConfigs($configs);
    return $uniqueConfigCombinations;
}

function whenDidIBuy()
{

    $files = glob('/Users/alanstorm/Desktop/when-did-I-buy/*');
    foreach($files as $file)
    {
        output($file);
        $handle = fopen($file, 'r');
        $del    = "\t";
        if(strpos($file, ".TXT") === false)
        {
            $del = ",";
        }
        while($row = fgetcsv($handle, 1024, $del))
        {
            output($row[2]);
//             if(count($row) === 0){ continue;}
//             if(!isset($row[2]))
//             {
//                 var_dump($row);
//                 exit;
//             }

//             if(count($row) < 5)
//             {
//                 var_dump($file);
//                 var_dump($row);
//                 exit;
//             }            
        }        
    }
    exit;
}

function randomUiComponentStuff()
{

    $folder         = $arguments['folder'];
    $files          = getFilesArray($folder);
    //* @argument foobar @callback exampleOfACallback    
    $xmls           = loadXmlListingsFiles($files);
    $names          = getUniqueNameOfColumnsChildren($xmls);    
    
    
             
    // reportValidateDateComponents($xmls);     
    // reportOnOptionsArgumentAndDataTypes($xmls);         
    // reportOnNamedDataConfig($xmls, 'component');
    // reportOnNamedDataConfig($xmls, 'filter');
    // reportUniqueCombinations($xmls);
    // var_dump($names);
    
    // $uniqueConfigCombinations = getUniqueCombinationsFromXmls($xmls, 'column');
    // reportUniqueCombinations($xmls, $uniqueConfigCombinations);

    //$uniqueConfigCombinations = getUniqueCombinationsFromXmls($xmls, 'actionsColumn');
    //reportUniqueCombinations($xmls, $uniqueConfigCombinations, 'actionsColumn');
    
    $uniqueConfigCombinations = getUniqueCombinationsFromXmls($xmls, 'selectionsColumn');
    reportUniqueCombinations($xmls, $uniqueConfigCombinations, 'selectionsColumn');    
    exit;
    // var_dump($names);
    
    foreach($xmls as $file=>$xml)
    {
        $nodes = $xml->xpath('//actionsColumn/argument/item[@name="config"]/item');
        $nodes = $xml->xpath('//selectionsColumn/argument/item[@name="config"]/item');
        foreach($nodes as $node)
        {
            output((string)$node['name']);            
        }
    } 
    //reportDataProviderToListingXmlFileMap($xmls);    
}

function tumblrBackupExtract()
{
    $files = glob('/Users/alanstorm/Documents/tumblr-backup/2017-02-03/*');
    foreach($files as $file)
    {
        $xml = simplexml_load_file($file);
        foreach($xml->posts->post as $post)
        {
            $title = (string)$post->{'regular-title'};
            $title = $title ? $title : (string)$post->{'link-text'};
            // output((string)$post['title'] . "\t" . (string)$post->url);
            output( 
                $title                  . "\t"  .
                (string)$post['url']    . "\t"  .
                (string) $post['unix-timestamp']
            );
        }
    }

}

function magentoSomeUiComponentSearch($argv, $options)
{
    $cmd    = 'find vendor/magento -wholename \'*ui_component/*.xml\'';
    $files  = explode("\n", `$cmd`);    
    $files  = array_filter($files);
    $files  = array_map(function($file){
        $xml = simplexml_load_file($file);
        return $xml;
    }, $files);
    
    $files = array_filter($files, function($xml){
        return $xml->getName() === 'listing';
        return true;
    });
    
    $allColumns = [];
    foreach($files as $xml)
    {
        $columns = $xml->xpath('//column');
        $allColumns = array_merge($allColumns, $columns);
    }    
    
    foreach($allColumns as $column)
    {        
        #output($column->children()->count());        
        output($column->asXml());
        
        
        foreach($column->argument->item as $item)
        {
            
        }
        exit;
    }
//     var_dump($allColumns);
//     exit;
}

/**
* Test Command
* @command testbed
* @Xargument folder Which Folder?
*/
function pestle_cli($arguments, $options)
{
    $code = file_get_contents('/Users/alanstorm/Documents/github/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php');    
    $functions = getParsedFunctionInfoFromCode($code);
    $functions = array_map(function($function) use ($code){
        $function->as_string = getFunctionFromCode($code, $function->function_name);
        return $function;
    }, $functions);
    var_dump($functions);
}