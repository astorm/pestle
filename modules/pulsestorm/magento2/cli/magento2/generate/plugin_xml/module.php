<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Plugin_Xml;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Generate\Plugin_Xml\exported_pestle_cli');
/**
* Generates plugin XML
* This command generates the necessary files and configuration 
* to "plugin" to a preexisting Magento 2 object manager object. 
*
*     pestle.phar magento2:generate:plugin_xml Pulsestorm_Helloworld 'Magento\Framework\Logger\Monolog' 'Pulsestorm\Helloworld\Plugin\Magento\Framework\Logger\Monolog'
* 
* @argument module_name Create in which module? [Pulsestorm_Helloworld]
* @argument class Which class are you plugging into? [Magento\Framework\Logger\Monolog]
* @argument class_plugin What's your plugin class name? [<$module_name$>\Plugin\<$class$>]
* @command magento2:generate:plugin_xml
*/
function pestle_cli($argv)
{
    return exported_pestle_cli($argv);
}
