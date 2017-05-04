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
pestle_import('Pulsestorm\Magento2\Cli\Magento2\Generate\Preference\generateDiConfiguration');

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

function generateRepositoryGetMethod()
{
    $docBlock = '';
    $methodBodyGet = 
'        $object = $this->factory->create();
        $object->setId($id);
        return $object;';    
    $methodGet = templateMethod('public', 'get', $docBlock);
    $methodGet  = str_replace('<$params$>', '$id', $methodGet);
    $methodGet  = str_replace('<$methodBody$>', $methodBodyGet, $methodGet);
    return $methodGet;
}

function generateRepositoryConstructMethod($model)
{
    $docBlock = '';   
    $methodBody = 
'        $this->factory = $factory;';    
    $method = templateMethod('public', '__construct', $docBlock);
    $method  = str_replace('<$params$>', '\\' . $model . 'Factory $factory', $method);
    $method  = str_replace('<$methodBody$>', $methodBody, $method);
    
    $props = '
    /**
    * @var ' . $model . 'Factory
    */        
    protected $factory;';
    return $props . "\n" . $method;
}

function generateRepositoryClassAndInterface($moduleInfo, $repositoryName, 
    $repositoryInterfaceName, $modelInterface, $modelName)
{
    $contents = createClassTemplateWithUse($repositoryName, false, '\\' . $repositoryInterfaceName);

    
    $methodGet = generateRepositoryGetMethod();
    $methodConstruct = generateRepositoryConstructMethod($modelName);
    
    $classBody = implode('', [$methodConstruct, $methodGet]);
    $contents = str_replace('<$body$>', $classBody, $contents);
    $contents = str_replace('<$use$>', '', $contents);
    createClassFile($repositoryName,$contents);

    $docBlock = trim('
    /**
     * @param int $id
     * @return \\'.$modelInterface.'
     */');      
    $contents = templateInterface($repositoryInterfaceName,['get']);
    $functionGet = 'function get';
    $contents = str_replace($functionGet, $docBlock . "\n" . '    '.$functionGet, $contents);
    $contents = str_replace($functionGet . '(', $functionGet . '($id', $contents);

    createClassFile($repositoryInterfaceName,$contents);        
}

function getMethodsFromProperties($properties)
{
    return array_map(function($item){
        return 'get' . ucwords($item);
    }, array_keys($properties));
}

function snakeToCamel($string)
{
    $string = str_replace('_', ' ', $string);
    $string = ucwords($string);
    $string = str_replace(' ', '', $string);
    return $string;
}

function generateClassAndInterface($modelToSign, $interfaceName, $properties)
{

    $methods = getMethodsFromProperties($properties);
    
    $contents = createClassTemplateWithUse($modelToSign, false, '\\' . $interfaceName);    
    $classBody      = '';
    $classBodyProps = '';
    $interfaceBody  = '';
    
    foreach($properties as $propName=>$type)
    {
        $camelPropName = snakeToCamel($propName);
        $classBodyProps .= '
    /**
     * @var '.$type.' $'.$propName.'
     */                
    protected $' . $propName . ';';
    
        $classBody .= '
    public function get'.$camelPropName.'()
    {
       return $this->'.$propName.';
    }        
    public function set'.$camelPropName.'($'.$propName.')
    {
       $this->'.$propName.' = $'.$propName.';
       return $this;
    }
';

        $interfaceBody .= '
    /**
     * @return '.$type.'
     */
     function get'.$camelPropName.'();
     
    /**
     * @param '.$type.' $'.$propName.'   
     * @return $this     
     */   
     public function set'.$camelPropName.'($'.$propName.');
';          
     
    }

    $classBody = $classBodyProps . "\n" . $classBody;
    
    $contents = str_replace('<$body$>', $classBody, $contents);
    $contents = str_replace('<$use$>', '', $contents);
    createClassFile($modelToSign, $contents);
    
    // $contents = templateInterface($interfaceName,$methods);
    $contents = templateInterface($interfaceName,[]);
    $contents = str_replace('{', "{\n" . $interfaceBody, $contents);
    createClassFile($interfaceName, $contents);
}

function generateDiConfigurations($moduleName, $repositoryInterfaceName, 
    $repositoryName, $interfaceName, $modelToSign)
{
    generateDiConfiguration([
        'module'=>$moduleName,
        'for'   =>$repositoryInterfaceName,
        'type'  =>$repositoryName]);                
          
    generateDiConfiguration([
        'module'=>$moduleName,
        'for'   =>$interfaceName,
        'type'  =>$modelToSign]);                        
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
    $repositoryName          = 'Pulsestorm\Apitest2\Model\ThingRepository';
    $repositoryInterfaceName = 'Pulsestorm\Apitest2\Api\ThingRepositoryInterface';    
    $apiEndpoint             = '/V1/pulsestorm_apitest2/things/:id';
    $resourceId              = 'anonymous';
    $properties              = [
        'id'=>'int'
    ];
    
    $moduleInfo              = getModuleInformation($moduleName);
    
        
    generateDiConfigurations($moduleName, $repositoryInterfaceName, 
        $repositoryName, $interfaceName, $modelToSign);        
    
    generateWebApiXml($moduleInfo, $apiEndpoint, $repositoryInterfaceName, $resourceId);    
    generateRepositoryClassAndInterface($moduleInfo, $repositoryName, $repositoryInterfaceName, $interfaceName, $modelToSign);
    generateClassAndInterface($modelToSign, $interfaceName, $properties);
    
    //output("@TODO: need di.xml");        
    //output("@TODO: Naming classes with one word per namespace leaves not very expressive base names when using PHP 5.3 namespaces and class imports.");
    //output("@TODO: The PHPDoc annotation {@inheritdoc} is only noise");    
    //output("@TODO: In the ThingInterface the setter argument type should be specified using a @param annotation.");    
    //output("@TODO: Personally I think returning void from a setter is more appropriate than returning $this, if the expectation is that the object state is changed.");
    //output("@TODO: I think it would be good if ThingRepositoryInterface::get() would take an $id parameter.");        
    //output("@TODO: The etc/di.xml file should also contain a <preference> mapping the ThingInterface to the Thing implementation.");    
    //output("@TODO: The class property \$id is declared dynamically. According to current \"best practice\" it should be declared as a class property using one of the visibility keywords, like");
    
    //output("@TODO: the repository should have a \Pulsestorm\Apitest2\Api\Data\ThingInterfaceFactory as constructor dependency");        
    output("@TODO: Make this work with actual arguments");
    output("@TODO: Decide what crud generation should do vs. this should do");
    output("@TODO: Attempt to extract interface name from generated model?");                    
    output("@TODO: What to do it repository already exists");
    output("@TODO: Attempt to extract fields from schema file? Or seperate command?");
    output("@TODO: Base repository inimplemention (and webapi.xml URLs to match?)");    

    
    // output("@TODO: Generate Repository");
    // output("@TODO: Generate Interface");        
    // output("@TODO: generate accessors on data interface");    
    //output("@TODO: the data model implementing the Api Data interface should not be the regular ORM model, but rather a separate data model as can be seen in the customer module,");
}
