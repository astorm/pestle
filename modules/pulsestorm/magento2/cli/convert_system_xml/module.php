<?php
namespace Pulsestorm\Magento2\Cli\Convert_System_Xml;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\input');
pestle_import('Pulsestorm\Pestle\Library\output');

/**
* Short Description
* Long
* Description
* @command convert_system_xml
*/
function pestle_cli($argv)
{
    $xml = input('Which system.xml?', 'app/code/core/Mage/Core/etc/system.xml');
    $xml = simplexml_load_file($xml);
    $xml_new = simplexml_load_string('<config><system></system></config>');
    $sort = 1000;
    foreach($xml->tabs->children() as $tab)
    {
        $new_tab = $xml_new->system->addChild('tab');
        $new_tab->addAttribute('id',$tab->getName());
        $new_tab->addAttribute('translate',(string)$tab['translate']);
        $new_tab->addAttribute('sortOrder',$sort);
        $sort+=10;
        
        $new_tab->addChild('label', (string) $tab->label);
        // output($tab->getName());
    }
    
    foreach($xml->sections->children() as $section)
    {
        $new_section = $xml_new->system->addChild('section');
        $new_section->addAttribute('id',$section->getName());
        $new_section->addAttribute('translate',(string)$section['translate']);
        $new_section->addAttribute('type',(string)$section->frontend_type);
        $new_section->addAttribute('sortOrder',$sort);
        $sort += 10;
        $new_section->addAttribute('showInDefault',(string)$section->show_in_default);
        $new_section->addAttribute('showInWebsite',(string)$section->show_in_website);
        $new_section->addAttribute('showInStore',(string)$section->show_in_store);
        
        $new_section->addChild('label', (string)$section->label);
        $new_section->addChild('tab', (string)$section->tab);
        $new_section->addChild('resource', 'XXXX');
        
        //id="advanced" translate="label" type="text" sortOrder="910" showInDefault="1" showInWebsite="1" showInStore="1"
        
        // output($section->getName());
        foreach($section->groups->children() as $group)
        {
            $new_group = $new_section->addChild('group');
            $new_group->addAttribute('id',$group->getName());
            $new_group->addAttribute('translate',(string)$group['translate']);
            $new_group->addAttribute('type',(string)$group->frontend_type);
            $new_group->addAttribute('sortOrder',$sort);
            $sort += 10;
            $new_group->addAttribute('showInDefault',(string)$group->show_in_default);
            $new_group->addAttribute('showInWebsite',(string)$group->show_in_website);
            $new_group->addAttribute('showInStore',(string)$group->show_in_store);

            $new_group->addChild('label', (string)$group->label);
            $new_group->addChild('frontend_model', 'XXXX');
                    
            // output($group->getName());
            foreach($group->fields->children() as $field)
            {
                //id="email" translate="label" type="text" sortOrder="2" showInDefault="1" showInWebsite="1" showInStore="1"            
                $new_field = $new_group->addChild('field');
                $new_field->addAttribute('id',$field->getName());
                $new_field->addAttribute('translate',(string)$field['translate']);
                $new_field->addAttribute('type',(string)$field->frontend_type);
                $new_field->addAttribute('sortOrder',$sort);
                $sort += 10;
                $new_field->addAttribute('showInDefault',(string)$field->show_in_default);
                $new_field->addAttribute('showInWebsite',(string)$field->show_in_website);
                $new_field->addAttribute('showInStore',(string)$field->show_in_store);
                foreach($field->children() as $field_child)
                {
                    if(in_array($field_child->getName(), ['id','translate','type','sort_order','show_in_default','show_in_website','show_in_store','frontend_type'])) 
                    { 
                        continue; 
                    }
                    $new_field->addChild($field_child->getName(), (string) $field_child);
                }                            
                // output($field->getName());
            }
        }
    }
    
    echo $xml_new->asXml(),"\n";
    output("Done");
}
