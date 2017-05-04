<?php
namespace Pulsestorm\Magento2\Generate\Servicecontract;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\input');
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
pestle_import('Pulsestorm\Magento2\Cli\Library\getModuleInformation');
pestle_import('Pulsestorm\Magento2\Cli\Xml_Template\getBlankXmlWebapi');
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
pestle_import('Pulsestorm\Xml_Library\formatXmlString');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Magento2\Cli\Library\createClassFile');
pestle_import('Pulsestorm\Cli\Code_Generation\createClassTemplateWithUse');
pestle_import('Pulsestorm\Cli\Code_Generation\templateInterface');
pestle_import('Pulsestorm\Cli\Code_Generation\templateMethod');
function generateWebApiXml($moduleInfo, $uri, $repositoryClass, $resourceId)
{
    $path   = $moduleInfo->folder . '/etc/webapi.xml';    
    $xml    = simplexml_load_string(getBlankXmlWebapi());
    if(file_exists($path))
    {
        $xml = simplexml_load_file($path);
    }
    
    $nodes = $xml->xpath('//route[@url="'.$uri.'"]');
    if(count($nodes) > 0)
    {
        exitWithErrorMessage("ERROR: webapi.xml already has $uri <route/> node");
    }

    $route  = $xml->addChild('route');
    $route->addAttribute('url',$uri);
    $route->addAttribute('method','GET');
    
    $service = $route->addChild('service');
    $service->addAttribute('class',$repositoryClass);
    $service->addAttribute('method','get');
    
    $resource = $route->addChild('resources')->addChild('resource');
    $resource->addAttribute('ref', $resourceId);
    
    
    writeStringToFile($path, formatXmlString($xml->asXml()));
}

function generateRepositoryClassAndInterface($moduleInfo, $repositoryName, $repositoryInterfaceName)
{
    $contents = createClassTemplateWithUse($repositoryName, false, $repositoryInterfaceName);
    $contents = str_replace('<$body$>', templateMethod('public', 'get'), $contents);
    $contents = str_replace('<$use$>', '', $contents);
    $contents = str_replace('<$params$>', '', $contents);
    $methodBody = 
'        $object = new \Pulsestorm\Apitest\Model\Thing;
        $object->setId(1);
        return $object;';    
    $contents = str_replace('<$methodBody$>', $methodBody, $contents);
    createClassFile($repositoryName,$contents);
    
    $contents = templateInterface($repositoryInterfaceName,['get']);
    createClassFile($repositoryInterfaceName,$contents);        
}

/**
* ALPHA: Service Contract Generator
*
* @command magento2:generate:service-contract
* @option skip-warning Allows user to skip experimental warning
*/
function pestle_cli($argv, $options)
{
    if(!$options['skip-warning'])
    {
        input("DANGER: Experimental Feature, might expose api endpoints.  \nPress enter to continue.");
    }
    
    $moduleName              = 'Pulsestorm_Apitest2';
    $modelToSign             = 'Pulsestorm\Apitest2\Model\Thing';
    $interfaceName           = 'Pulsestorm\Apitest2\Api\Data\ThingInterface';
    $repositoryName          = 'Pulsestorm\Apitest2\Model\Thing\Repository';
    $repositoryInterfaceName = 'Pulsestorm\Apitest2\Api\ThingRepositoryInterface';    
    $apiEndpoint             = 'V1/pulsestorm_apitest2/thing';
    $resourceId              = 'anonymous';
    $properties              = [
        'id'=>'int'
    ];
    
    $moduleInfo              = getModuleInformation($moduleName);
    
    #generateWebApiXml($moduleInfo, $apiEndpoint, $repositoryInterfaceName, $resourceId);    
    generateRepositoryClassAndInterface($moduleInfo, $repositoryName, $repositoryInterfaceName);
    
    output("@TODO: Generate Repository");
    output("@TODO: Generate Interface");        
    output("@TODO: generate accessors on data interface");
    
    output("@TODO: Make this work with actual arguments");            
    output("@TODO: Decide what crud generation should do vs. this should do");                
    output("@TODO: Attempt to extract interface name from generated model?");                    
    output("@TODO: What to do it repository already exists");
    output("@TODO: Attempt to extract fields from schema file? Or seperate command?");
    output("@TODO: Base repository inimplemention (and webapi.xml URLs to match?)");
}
