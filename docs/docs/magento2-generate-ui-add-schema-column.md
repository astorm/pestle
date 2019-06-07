#magento2:generate:ui:add-schema-column

TODO: WRITE THE DOCS!
    
    Usage: 
        $ pestle.phar magento2:generate:ui:add-schema-column
    
    Arguments:
    
    Options:
    
    Help:
        Genreated a Magento 2 addColumn DDL definition and inserts into file
        
        Command scans creates column definition code and, if provided
        attempts to insert it into provided php_file.  Inserting means
        looking for this pattern.
        newTable($installer->getTable('table_name'))->addColumn
        and if found, scanning to the ; and inserting the addColumn
        
        @command magento2:generate:ui:add-schema-column
        @argument php_file PHP file with newTable call? [skip]
        @argument table Database Table? (packagename_modulename_modelnames)
        @argument column Columns Name? (new_column)
        @argument column_type @callback selectColumnType
    
    
    