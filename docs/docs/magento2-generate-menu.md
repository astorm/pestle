#magento2:generate:menu

TODO: WRITE THE DOCS!
    
    Usage: 
        $ pestle.phar magento2:generate:menu
    
    Arguments:
    
    Options:
    
    Help:
        Generates configuration for Magento Adminhtml menu.xml files
        
        @command magento2:generate:menu
        @argument module_name Module Name? [Pulsestorm_HelloGenerate]
        @argument parent @callback selectParentMenu
        @argument id Menu Link ID [<$module_name$>::unique_identifier]
        @argument resource ACL Resource [<$id$>]
        @argument title Link Title [My Link Title]
        @argument action Three Segment Action [frontname/index/index]
        @argument sortOrder Sort Order? [10]
    
    
    