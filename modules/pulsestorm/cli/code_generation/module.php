<?php
namespace Pulsestorm\Cli\Code_Generation;
use function Pulsestorm\Pestle\Importer\pestle_import;

function createControllerClass($class, $area)
{
    $extends = '\Magento\Framework\App\Action\Action';
    if($area === 'adminhtml')
    {
        $extends = '\Magento\Backend\App\Action';
    }
    $template = createControllerClassTemplate($class, $extends);
    
    $body = '
    protected $resultPageFactory;
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory)
    {
        $this->resultPageFactory = $resultPageFactory;        
        return parent::__construct($context);
    }
    
    public function execute()
    {
        return $this->resultPageFactory->create();  
    }' . "\n";
    
    
    return str_replace('<$body$>', $body, $template);
}

function createControllerClassTemplate($class, $extends=false, $implements=false)
{
    $class = trim($class, '\\');
    $parts = explode('\\',$class);
    $name  = array_pop($parts);
    
    $template = '<' . '?' . 'php' . "\n" .
    'namespace ' . implode('\\',$parts) . ";\n" . 
    "class $name";
    if($extends)
    {
        $template .= " extends $extends";
    }
    if($implements)
    {
        $template .= " implements $implements";
    }    
    $template .= "\n" . 
    '{' . '<$body$>' . '}' . "\n";

    return $template;
}

/**
* @command library
*/
function pestle_cli()
{
}