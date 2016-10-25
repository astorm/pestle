<?php
namespace Pulsestorm\Pestle\Tests;
require_once 'PestleBaseTest.php';

class CommandExistsTest extends PestleBaseTest
{
    protected function runHelpCommand($name)
    {
        return $this->runCommand('help', [$name]);
    }

    function testCommandsExistbaz_bar()
    {
        $results = $this->runHelpCommand('baz_bar');
        $this->assertContains('Help:', $results);    
        
    }
    
    function testCommandsExistbuild_command_list()
    {
        $results = $this->runHelpCommand('build_command_list');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistcheck_class_and_namespace()
    {
        $results = $this->runHelpCommand('check_class_and_namespace');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistcheck_htaccess()
    {
        $results = $this->runHelpCommand('check_htaccess');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistcheck_registration()
    {
        $results = $this->runHelpCommand('check_registration');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistcheck_templates()
    {
        $results = $this->runHelpCommand('check_templates');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistclass_from_path()
    {
        $results = $this->runHelpCommand('class_from_path');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistconvert_class()
    {
        $results = $this->runHelpCommand('convert_class');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistconvert_observers_xml()
    {
        $results = $this->runHelpCommand('convert_observers_xml');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistconvert_selenium_id_for_codecept()
    {
        $results = $this->runHelpCommand('convert_selenium_id_for_codecept');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistconvert_system_xml()
    {
        $results = $this->runHelpCommand('convert_system_xml');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistdev_import()
    {
        $results = $this->runHelpCommand('dev_import');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistdev_namespace()
    {
        $results = $this->runHelpCommand('dev_namespace');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistgenerate_observer()
    {
        $results = $this->runHelpCommand('generate_observer');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistextract_mage2_system_xml_paths()
    {
        $results = $this->runHelpCommand('extract_mage2_system_xml_paths');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistextract_session()
    {
        $results = $this->runHelpCommand('extract_session');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistfix_permissions_modphp()
    {
        $results = $this->runHelpCommand('fix_permissions_modphp');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistgenerate_command()
    {
        $results = $this->runHelpCommand('generate_command');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistgenerate_config_helper()
    {
        $results = $this->runHelpCommand('generate_config_helper');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistgenerate_di()
    {
        $results = $this->runHelpCommand('generate_di');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistgenerate_layout_xml()
    {
        $results = $this->runHelpCommand('generate_layout_xml');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistgenerate_mage2_command()
    {
        $results = $this->runHelpCommand('generate_pestle_command');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistgenerate_module()
    {
        $results = $this->runHelpCommand('generate_module');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistgenerate_plugin_xml()
    {
        $results = $this->runHelpCommand('generate_plugin_xml');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistgenerate_psr_log_level()
    {
        $results = $this->runHelpCommand('generate_psr_log_level');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistgenerate_registration()
    {
        $results = $this->runHelpCommand('generate_registration');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistgenerate_route()
    {
        $results = $this->runHelpCommand('generate_route');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExisthello_world()
    {
        $results = $this->runHelpCommand('hello_world');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExisthelp()
    {
        $results = $this->runHelpCommand('help');
        $this->assertContains('Help:', $results);    
        
    }

    function testCommandsExistlibrary()
    {
        $results = $this->runHelpCommand('library');
        $this->assertContains('Available commands:', $results);    
        
    }
    
    function testCommandsExistListAlias()
    {
        $results = $this->runHelpCommand('list');
        $this->assertContains('Help:', $results);    
        
    }
            
    function testCommandsExistlist()
    {
        $results = $this->runHelpCommand('list_commands');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistmd_to_say()
    {
        $results = $this->runHelpCommand('md_to_say');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistpath_from_class()
    {
        $results = $this->runHelpCommand('path_from_class');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExistsearch_controllers()
    {
        $results = $this->runHelpCommand('search_controllers');
        $this->assertContains('Help:', $results);    
        
    }
    function testCommandsExisttestbed()
    {
        $results = $this->runHelpCommand('testbed');
        $this->assertContains('Help:', $results);    
        
    }    
}