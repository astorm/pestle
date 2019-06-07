#magento2:generate:plugin-xml

TODO: WRITE THE DOCS!
    
    Usage: 
        $ pestle.phar magento2:generate:plugin-xml
    
    Arguments:
    
    Options:
    
    Help:
        Generates plugin XML
        This command generates the necessary files and configuration
        to "plugin" to a preexisting Magento 2 object manager object.
        
        pestle.phar magento2:generate:plugin_xml Pulsestorm_Helloworld
        'Magento\Framework\Logger\Monolog'
        'Pulsestorm\Helloworld\Plugin\Magento\Framework\Logger\Monolog'
        
        @argument module_name Create in which module? [Pulsestorm_Helloworld]
        @argument class Which class are you plugging into?
        [Magento\Framework\Logger\Monolog]
        @argument class_plugin What's your plugin class name?
        [<$module_name$>\Plugin\<$class$>]
        @option use-type-hint Add type hint to subject?
        @command magento2:generate:plugin-xml
    
    
    