#magento2:generate:command

TODO: WRITE THE DOCS!
    
    Usage: 
        $ pestle.phar magento2:generate:command
    
    Arguments:
    
    Options:
    
    Help:
        Generates bin/magento command files
        This command generates the necessary files and configuration
        for a new command for Magento 2's bin/magento command line program.
        
        pestle.phar magento2:generate:command Pulsestorm_Generate Example
        
        Creates
        app/code/Pulsestorm/Generate/Command/Example.php
        app/code/Pulsestorm/Generate/etc/di.xml
        
        @command magento2:generate:command
        @argument module_name In which module? [Pulsestorm_Helloworld]
        @argument command_name Command Name? [Testbed]
    
    
    