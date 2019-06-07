#magento2:generate:di

TODO: WRITE THE DOCS!
    
    Usage: 
        $ pestle.phar magento2:generate:di
    
    Arguments:
    
    Options:
    
    Help:
        Injects a dependency into a class constructor
        This command modifies a preexisting class, adding the provided
        dependency to that class's property list, `__construct` parameters
        list, and assignment list.
        
        pestle.phar magento2:generate:di
        app/code/Pulsestorm/Generate/Command/Example.php
        'Magento\Catalog\Model\ProductFactory'
        
        @command magento2:generate:di
        @argument file Which PHP class file are we injecting into?
        @argument class Which class to inject?
        [Magento\Catalog\Model\ProductFactory]
    
    
    