## magento2:generate:ui:add-column-text

TODO: WRITE THE DOCS!

    Usage:
        $ pestle.phar magento2:generate:ui:add-column-text

    Arguments:

    Options:

    Help:
        Adds a simple text column to a UI Component Grid

        @command magento2:generate:ui:add-column-text
        @argument listing_file Which Listing XML File?
        @argument column_name New Column Field? [title]
        @argument column_label New Column Label? [Title]


## magento2:generate:ui:add-form-field

TODO: WRITE THE DOCS!

    Usage:
        $ pestle.phar magento2:generate:ui:add-form-field

    Arguments:

    Options:

    Help:
        Adds a Form Field

        @command magento2:generate:ui:add-form-field
        @argument path_xml Path to Form XML File?
        @argument field Field Name? [title]
        @argument label Label? [Title]
        @argument fieldset Fieldset Name? [general]
        @option is-required Is field required?


## magento2:generate:ui:add-form-fieldset

TODO: WRITE THE DOCS!

    Usage:
        $ pestle.phar magento2:generate:ui:add-form-fieldset

    Arguments:

    Options:

    Help:
        Add a Fieldset to a Form

        @command magento2:generate:ui:add-form-fieldset
        @argument path_xml Path to Form XML File?
        @argument fieldset Fieldset Name? [newfieldset]
        @argument label Label? [NewFieldset]


## magento2:generate:ui:add-schema-column

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


## magento2:generate:ui:add-to-layout

TODO: WRITE THE DOCS!

    Usage:
        $ pestle.phar magento2:generate:ui:add-to-layout

    Arguments:

    Options:

    Help:
        Adds a <uiComponent/> node to a named node in a layout update XML file

        @command magento2:generate:ui:add-to-layout
        @argument path_layout Layout XML File?
        @argument block_name Block or Reference Name?
        @argument ui_component_name UI Component Name?


## magento2:generate:ui:form

TODO: WRITE THE DOCS!

    Usage:
        $ pestle.phar magento2:generate:ui:form

    Arguments:

    Options:

    Help:
        Generates a Magento 2 UI Component form configuration and PHP
        boilerplate

        @command magento2:generate:ui:form
        @argument module Which Module? [Pulsestorm_Formexample]
        @argument model Model Class? [Pulsestorm\Formexample\Model\Thing]
        @argument aclRule ACL Rule for Controllers?
        [Pulsestorm_Formexample::ruleName]


## magento2:generate:ui:grid

TODO: WRITE THE DOCS!

    Usage:
        $ pestle.phar magento2:generate:ui:grid

    Arguments:

    Options:

    Help:
        Generates a Magento 2.1 ui grid listing and support classes.

        @command magento2:generate:ui:grid
        @argument module Which Module? [Pulsestorm_Gridexample]
        @argument grid_id Create a unique ID for your Listing/Grid!
        [pulsestorm_gridexample_log]
        @argument collection_resource What Resource Collection Model should
        your listing use? [Magento\Cms\Model\ResourceModel\Page\Collection]
        @argument db_id_column What's the ID field for you model?
        [pulsestorm_gridexample_log_id]


