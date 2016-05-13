<?php
namespace Pulsestorm\Magento2\Cli\Testbed;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\input');
pestle_import('Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir');
pestle_import('Pulsestorm\Xml_Library\formatXmlString');
pestle_import('Pulsestorm\Cli\Token_Parse\pestle_token_get_all');

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

/**
* Test Command
* @command testbed
* @argument foobar @callback exampleOfACallback
*/
function pestle_cli($arguments, $options)
{
    output("Hello World");
}
