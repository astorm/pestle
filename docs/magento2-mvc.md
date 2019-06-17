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

The `magento2:generate:route` command generates all the configuration and code you'll need to make Magento respond to a URL.

**Interactive Invocation**

    $ pestle.phar magento2:generate:route
    Which Module? (Pulsestorm_HelloWorld)] Pulsestorm_Pestle
    Which Area (frontend, adminhtml)? (frontend)] frontend
    Frontname/Route ID? (pulsestorm_helloworld)] pulsestorm_pestle
    Controller name? (Index)] Index
    Action name? (Index)] Index
    /path/to/m2/app/code/Pulsestorm/Pestle/etc/frontend/routes.xml
    /path/to/m2/app/code/Pulsestorm/Pestle/Controller/Index/Index.php

**Argument Invocation**

    $ pestle.phar magento2:generate:route Pulsestorm_Pestle frontend pulsestorm_pestle Index Index

The `magento2:generate:route` command will ask you which module you want your controller and route configuration created in (`Pulsestorm_Pestle`), whether this is a cart (`frontend`) or admin-area (`adminhtml`) route, and the short class name versions of the controller and action names (both `Index` above).  With that information in hand, pestle will create a route configuration file.

    $ cat app/code/Pulsestorm/Pestle/etc/frontend/routes.xml
    <?xml version="1.0"?>
    <config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:App/etc/routes.xsd">
        <router id="standard">
            <route id="pulsestorm_pestle" frontName="pulsestorm_pestle">
                <module name="Pulsestorm_Pestle"/>
            </route>
        </router>
    </config>

and a controller class file

    $ cat app/code/Pulsestorm/Pestle/Controller/Index/Index.php
    <?php
    namespace Pulsestorm\Pestle\Controller\Index;
    class Index extends \Magento\Framework\App\Action\Action
    {

        protected $resultPageFactory;
        public function __construct(
            \Magento\Framework\App\Action\Context $context,
            \Magento\Framework\View\Result\PageFactory $resultPageFactory)
        {
            $this->resultPageFactory = $resultPageFactory;
            parent::__construct($context);
        }

        public function execute()
        {
            return $this->resultPageFactory->create();
        }
    }

In the above example, your page would be available at the URLs

    http://your-site.example.com/pulsestorm_pestle/index/index

    # or, with Magento's "index as default" behavior
    http://your-site.example.com/pulsestorm_pestle/

**Further Reading**

- [Introduction to Magento 2 -- No More MVC](https://alanstorm.com/magento_2_mvvm_mvc/)

- [Magento 2: Advanced Routing](https://alanstorm.com/magento_2_advanced_routing/)

- [Magento 2: Admin MVC/MVVM Endpoints](https://alanstorm.com/magento_2_admin_mvcmvvm_endpoints/)

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
