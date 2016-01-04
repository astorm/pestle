<?php
namespace Pulsestorm\Magento2\Cli\Generate\Config_Helper;
use function Pulsestorm\Pestle\Importer\pestle_import;
use Exception;
pestle_import('Pulsestorm\Pestle\Library\input');
pestle_import('Pulsestorm\Pestle\Library\output');

/**
* Generates a help class for reading Magento's configuration
*
* This command will generate the necessary files and configuration 
* needed for reading Magento 2's configuration values.
* 
* @command generate_config_helper
* @todo needs to be implemented
*/
function pestle_cli($argv)
{
    throw new Exception("Implement Me");
    $template = trim('
<?php
namespace Pulsestorm\Api\Helper;
use Pulsestorm\Api\Controller\V1\Get\Settings;

class Config
{
    const CONFIG_TOP = \'pulsestorm\';
    protected $scopeConfig;
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function get($path=null)
    {
        $config = $this->scopeConfig->getValue($this->getTopLevelName());
        if($config === null)
        {
            return null;
        }
        if(!$path)
        {
            return $config;
        }
        $parts = explode(\'/\',$path);
        
        foreach($parts as $part)
        {
            if(!array_key_exists($part, $config))
            {
                return null;
            }
            $config = $config[$part];
        }
        
        return $config;
    }
    
    protected function getTopLevelName()
    {
        return self::CONFIG_TOP;
    }
}    
');  

    output($template);  
}