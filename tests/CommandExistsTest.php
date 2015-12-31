<?php
class CommandExistsTest extends PHPUnit_Framework_TestCase
{
    function testCommandsExistbaz_bar()
    {
        $results = $this->runCommand('baz_bar');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistbuild_command_list()
    {
        $results = $this->runCommand('build_command_list');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistcheck_class_and_namespace()
    {
        $results = $this->runCommand('check_class_and_namespace');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistcheck_htaccess()
    {
        $results = $this->runCommand('check_htaccess');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistcheck_registration()
    {
        $results = $this->runCommand('check_registration');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistcheck_templates()
    {
        $results = $this->runCommand('check_templates');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistclass_from_path()
    {
        $results = $this->runCommand('class_from_path');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistconvert_class()
    {
        $results = $this->runCommand('convert_class');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistconvert_observers_xml()
    {
        $results = $this->runCommand('convert_observers_xml');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistconvert_selenium_id_for_codecept()
    {
        $results = $this->runCommand('convert_selenium_id_for_codecept');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistconvert_system_xml()
    {
        $results = $this->runCommand('convert_system_xml');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistdev_import()
    {
        $results = $this->runCommand('dev_import');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistdev_namespace()
    {
        $results = $this->runCommand('dev_namespace');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistevent_add()
    {
        $results = $this->runCommand('event_add');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistextract_mage2_system_xml_paths()
    {
        $results = $this->runCommand('extract_mage2_system_xml_paths');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistextract_session()
    {
        $results = $this->runCommand('extract_session');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistfix_permissions_modphp()
    {
        $results = $this->runCommand('fix_permissions_modphp');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistgenerate_command()
    {
        $results = $this->runCommand('generate_command');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistgenerate_config_helper()
    {
        $results = $this->runCommand('generate_config_helper');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistgenerate_di()
    {
        $results = $this->runCommand('generate_di');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistgenerate_layout_xml()
    {
        $results = $this->runCommand('generate_layout_xml');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistgenerate_mage2_command()
    {
        $results = $this->runCommand('generate_mage2_command');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistgenerate_module()
    {
        $results = $this->runCommand('generate_module');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistgenerate_plugin_xml()
    {
        $results = $this->runCommand('generate_plugin_xml');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistgenerate_psr_log_level()
    {
        $results = $this->runCommand('generate_psr_log_level');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistgenerate_registration()
    {
        $results = $this->runCommand('generate_registration');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistgenerate_route()
    {
        $results = $this->runCommand('generate_route');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExisthello_world()
    {
        $results = $this->runCommand('hello_world');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExisthelp()
    {
        $results = $this->runCommand('help');
        $this->assertContains('Description', $results);    
        
    }
//     function testCommandsExistlibaray()
//     {
//         $results = $this->runCommand('libaray');
//         $this->assertContains('Description', $results);    
//         
//     }
    function testCommandsExistlibrary()
    {
        $results = $this->runCommand('library');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistlist()
    {
        $results = $this->runCommand('list_commands');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistmd_to_say()
    {
        $results = $this->runCommand('md_to_say');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistpath_from_class()
    {
        $results = $this->runCommand('path_from_class');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExistsearch_controllers()
    {
        $results = $this->runCommand('search_controllers');
        $this->assertContains('Description', $results);    
        
    }
    function testCommandsExisttestbed()
    {
        $results = $this->runCommand('testbed');
        $this->assertContains('Description', $results);    
        
    }
    
    protected function runCommand($command)
    {
        global $argv;
        $real_argv = $argv;
        $argv = [];
        ob_start();
        require_once('runner.php');
        ob_end_clean();
        $argv = $real_argv;
    
        // Hello Sailor
        ob_start();
        \Pulsestorm\Pestle\Runner\main(['fake-script.php','help',$command]);        
        $results = ob_get_clean();
        
        return $results;
    }
}