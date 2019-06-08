## generate:route



    Usage:
        $ pestle.phar magento2:generate:route

    Arguments:

    Options:

    Help:
        Creates a Route XML
        generate_route module area id
        @command magento2:generate:route
        @argument module_name Which Module? [Pulsestorm_HelloWorld]
        @argument area Which Area (frontend, adminhtml)? [frontend]
        @argument frontname Frontname/Route ID? [pulsestorm_helloworld]
        @argument controller Controller name? [Index]
        @argument action Action name? [Index]

https://alanstorm.com/magento2_pestle_code_generation/

TODO: WRITE THE DOCS!

## generate:crud-model

    Usage:
        $ pestle.phar magento2:generate:crud-model

    Arguments:

    Options:

    Help:
        Generates a Magento 2 CRUD/AbstractModel class and support files

        @command magento2:generate:crud-model
        @argument module_name Which module? [Pulsestorm_HelloGenerate]
        @argument model_name  What model name? [Thing]
        @option use-upgrade-schema Create UpgradeSchema and UpgradeData
        classes instead of InstallSchema
        @option use-upgrade-schema-with-scripts Same as use-upgrade-schema,
        but uses schema script helpers
        @option use-install-schema-for-new-model Allows you to add another
        model definition to InstallSchema

https://alanstorm.com/magento_2_crud_models_for_database_access/

TODO: WRITE THE DOCS!

## generate:view

    Usage:
        $ pestle.phar magento2:generate:view

    Arguments:

    Options:

    Help:
        Generates view files (layout handle, phtml, Block, etc.)

        @command magento2:generate:view
        @argument module_name Which Module? [Pulsestorm_HelloGenerate]
        @argument area Which Area? [frontend]
        @argument handle Which Handle? [<$module_name$>_index_index]
        @argument block_name Block Name? [Main]
        @argument template Template File? [content.phtml]
        @argument layout Layout (ignored for adminhtml) ? [1column]

https://alanstorm.com/magento_2_mvvm_mvc/

TODO: WRITE THE DOCS!

## generate:theme

    Usage:
        $ pestle.phar magento2:generate:theme

    Arguments:

    Options:

    Help:
        Generates Theme Configuration

        @command magento2:generate:theme
        @argument package Theme Package Name? [Pulsestorm]
        @argument theme Theme Name? [blank]
        @argument area Area? (frontend, adminhtml) [frontend]
        @argument parent Parent theme (enter 'null' for none) [Magento/blank]

TODO: WRITE THE DOCS!

## generate:schema-upgrade

    Usage:
        $ pestle.phar magento2:generate:schema-upgrade

    Arguments:

    Options:

    Help:
        BETA: Generates a migration-based UpgradeSchema and UpgradeData
        classes

        @command magento2:generate:schema-upgrade
        @argument module_name Module Name? [Pulsestorm_Helloworld]
        @argument upgrade_version New Module Version? [0.0.2]
        @option use-simple-upgrade Option to skip creating script helpers

https://alanstorm.com/magento-2-setup-migration-scripts/

TODO: WRITE THE DOCS!
