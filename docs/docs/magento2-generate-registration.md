#magento2:generate:registration

TODO: WRITE THE DOCS!
    
    Usage: 
        $ pestle.phar magento2:generate:registration
    
    Arguments:
    
    Options:
    
    Help:
        Generates registration.php
        This command generates the PHP code for a
        Magento module registration.php file.
        
        $ pestle.phar magento2:generate:registration Foo_Bar
        <?php
        \Magento\Framework\Component\ComponentRegistrar::register(
        \Magento\Framework\Component\ComponentRegistrar::MODULE,
        'Foo_Bar',
        __DIR__
        );
        
        @command magento2:generate:registration
        @argument module_name Which Module? [Vendor_Module]
    
    
    