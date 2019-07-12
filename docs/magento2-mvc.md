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

The `magento2:generate:view` command allows you to generate a "Magento View".  View here is a loose concept -- what this command actually does is allow you to generate-or-edit a Magento layout handle XML file, auto generate layout handle XML code that will add a block to the `content` container block, add a new class and `phtml` template for the block.

**Interactive Invocation**

    $ pestle.phar magento2:generate:view
    Which Module? (Pulsestorm_HelloGenerate)] Pulsestorm_Pestle
    Which Area? (frontend)] frontend
    Which Handle? (pulsestorm_pestle_index_index)] pulsestorm_pestle_index_index
    Block Name? (Main)] Main
    Template File? (content.phtml)] content.phtml
    Layout (ignored for adminhtml) ? (1column)] 1column
    Creating  /path/to/m2/app/code/Pulsestorm/Pestle/view/frontend/templates/content.phtml
    Creating: Pulsestorm\Pestle\Block\Main
    Creating: /path/to/m2/app/code/Pulsestorm/Pestle/view/frontend/layout/pulsestorm_pestle_index_index.xml

**Argument Invocation**

    $ pestle.phar magento2:generate:view Pulsestorm_Pestle frontend pulsestorm_pestle_index_index Main content.phtml 1column

This command will ask you for

1. The module you want to create your view inside of (`Pulsestorm_Pestle`)
2. The area this view applies to (`frontend`, `adminhtml`)
3. The full action name layout handle this view applies to. (`pulsestorm_pestle_index_index`)
4. The short class name for your block (`Main`)
5. The template file for your block (`content.phtml`)
6. The magento layout key for your handle file (`1column`)

Magento's blocks and layout system is complicated.  If you're not sure what any of the above means, be sure to checkout the further reading section below.

**Further Reading**

- [No Frills Magento 2 Layout](https://store.pulsestorm.net/products/no-frills-magento-2-layout)

- [Introduction to Magento 2 — No More MVC](https://alanstorm.com/magento_2_mvvm_mvc/)

- [Magneto 1 Layouts, Blocks and Templates](https://alanstorm.com/layouts_blocks_and_templates/)

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

A Magento 2 theme is a collection of layout handles, templates, and frontend files that will change the look, feel, and (sometimes) behavior of a Magento system.  Themes are distributed as packages separate from modules, and the `magento2:generate:theme` command will create the basic skeleton of a theme.

**Interactive Invocation**

    $ pestle.phar magento2:generate:theme
    Theme Package Name? (Pulsestorm)] Pulsestorm
    Theme Name? (blank)] blank
    Area? (frontend, adminhtml) (frontend)] frontend
    Parent theme (enter 'null' for none) (Magento/blank)]
    Creating: /path/to/m2/app/design/frontend/Pulsestorm/blank/theme.xml
    Creating: /path/to/m2/app/design/frontend/Pulsestorm/blank/registration.php
    Creating: /path/to/m2/app/design/frontend/Pulsestorm/blank/etc/view.xml
    Creating: /path/to/m2/app/design/frontend/Pulsestorm/blank/web/css/source
    Creating: /path/to/m2/app/design/frontend/Pulsestorm/blank/fonts
    Creating: /path/to/m2/app/design/frontend/Pulsestorm/blank/images
    Creating: /path/to/m2/app/design/frontend/Pulsestorm/blank/js
    /path/to/m2/app/design/frontend/Pulsestorm/blank

**Argument Invocation**

    $ pestle.phar magento2:generate:theme Pulsestorm blank frontend ''

    Creating: /path/to/m2/app/design/frontend/Pulsestorm/blank/theme.xml
    Creating: /path/to/m2/app/design/frontend/Pulsestorm/blank/registration.php
    Creating: /path/to/m2/app/design/frontend/Pulsestorm/blank/etc/view.xml
    Creating: /path/to/m2/app/design/frontend/Pulsestorm/blank/web/css/source
    Creating: /path/to/m2/app/design/frontend/Pulsestorm/blank/fonts
    Creating: /path/to/m2/app/design/frontend/Pulsestorm/blank/images
    Creating: /path/to/m2/app/design/frontend/Pulsestorm/blank/js
    /path/to/m2/app/design/frontend/Pulsestorm/blank

**Further Reading**

- [What is a Magento Theme, the 10,000 Foot View](https://alanstorm.com/what-is-a-magento-theme-the-10000-foot-view/)

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

The `magento2:generate:crud-model` command allows you create a model, resource model, and collection for a Magento active-record-style C.R.U.D. model, as well as a skeleton install schema for creating the model's base table.

In addition to these standard ORM files, this command will also create a base item repository and interface, as wells as a model API interface.

**Interactive Invocation**

    $ pestle.phar magento2:generate:crud-model
    Which module? (Pulsestorm_HelloGenerate)] Pulsestorm_Pestle
    What model name? (Thing)] Item
    Creating: /path/to/m2/app/code/Pulsestorm/Pestle/Api/ItemRepositoryInterface.php
    Creating: /path/to/m2/app/code/Pulsestorm/Pestle/Model/ItemRepository.php
    Creating: /path/to/m2/app/code/Pulsestorm/Pestle/Api/Data/ItemInterface.php
    Creating: /path/to/m2/app/code/Pulsestorm/Pestle/Model/ResourceModel/Item/Collection.php
    Creating: /path/to/m2/app/code/Pulsestorm/Pestle/Model/ResourceModel/Item.php
    Creating: /path/to/m2/app/code/Pulsestorm/Pestle/Model/Item.php
    Creating: /path/to/m2/app/code/Pulsestorm/Pestle/Setup/InstallSchema.php
    Creating: /path/to/m2/app/code/Pulsestorm/Pestle/Setup/InstallData.php

**Argument Invocation**

    $ pestle.phar magento2:generate:crud-model Pulsestorm_Pestle Item
    Creating: /path/to/m2/app/code/Pulsestorm/Pestle/Api/ItemRepositoryInterface.php
    Creating: /path/to/m2/app/code/Pulsestorm/Pestle/Model/ItemRepository.php
    Creating: /path/to/m2/app/code/Pulsestorm/Pestle/Api/Data/ItemInterface.php
    Creating: /path/to/m2/app/code/Pulsestorm/Pestle/Model/ResourceModel/Item/Collection.php
    Creating: /path/to/m2/app/code/Pulsestorm/Pestle/Model/ResourceModel/Item.php
    Creating: /path/to/m2/app/code/Pulsestorm/Pestle/Model/Item.php
    Creating: /path/to/m2/app/code/Pulsestorm/Pestle/Setup/InstallSchema.php
    Creating: /path/to/m2/app/code/Pulsestorm/Pestle/Setup/InstallData.php

After running this command you'll want to bump your module version (in `module.xml`) and run the

    $ php bin/magento setup:upgrade

script to pickup the changes in the new install schema.

**Further Reading**

- [Magento 2: CRUD Models for Database Access](https://alanstorm.com/magento_2_crud_models_for_database_access/)
- [Magento 1: Models and ORM Basics](https://alanstorm.com/magento_models_orm/)

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

The `magento2:generate:schema-upgrade` command allows you to create **upgrade** classes and scripts for your crud models.  If you're looking to create **installer** classes, see the [`magento2:generate:crud-model`](https://pestle.readthedocs.io/en/latest/magento2-mvc/#generatecrud-model) command.

**Interactive Invocation**

    $ pestle.phar magento2:generate:schema-upgrade
    Module Name? (Pulsestorm_Helloworld)] Pulsestorm_Pestle
    New Module Version? (0.0.2)] 0.0.2
    Creating Pulsestorm\Pestle\Setup\UpgradeSchema
    Creating Pulsestorm\Pestle\Setup\UpgradeData
    Incrementing module.xml to 0.0.2
    Creating 0.0.2 Upgrade Scripts in /path/to/m2/app/code/Pulsestorm/Pestle/upgrade_scripts/schema
    Creating 0.0.2 Upgrade Scripts in /path/to/m2/app/code/Pulsestorm/Pestle/upgrade_scripts/data

**Argument Invocation**

    $ pestle.phar magento2:generate:schema-upgrade Pulsestorm_Pestle 0.0.2

This command will ask you for the module where the upgrade classes and scripts need to go (`Pulsestorm_Pestle`), and what the new module version should be (`0.0.2`).  The command will then

1. Create an `UpgradeSchema` and `UpgradeData` class.
2. Bump the module version in `etc/module.xml`. (needed to trigger an upgrade when users run `php bin/magento setup:upgrade`)
3. Create a `Package/Module/Setup/Script` helper class.
4. Create versioned upgrade scripts in the `upgrade_scripts/data` and `upgrade_scripts/schema` folders.

Numbers three and four may be unfamiliar to you, even if you're familiar with Magento's setup resource migration system.  The `magento2:generate:schema-upgrade` command creates an opinionated system for handling module migration scripts. This system hews more closely to Magento 1's system.  Rather than use a large `if/then` block in Magento's single `...Upgrade` classes, the upgrade classes invoke methods on the `Package/Module/Setup/Script` object, and this object runs a versioned script from the non-standard `upgrade_scripts` folder.

You can skip creating these upgrade scripts by using the `use-simple-upgrade` option.

    $ pestle.phar magento2:generate:schema-upgrade --use-simple-upgrade Pulsestorm_Pestle 0.0.4

To learn more about this system, read the [Magento 2 Setup Migration Scripts](https://alanstorm.com/magento-2-setup-migration-scripts/) article.

**Further Reading**

- [Magento 2 Setup Migration Scripts](https://alanstorm.com/magento-2-setup-migration-scripts/)
- [Magento 1 Setup Resources](https://alanstorm.com/magento_setup_resources/)
- [Magento 1 Setup Resources, licensed-to and forked by Magento Inc.](https://devdocs.magento.com/guides/m1x/magefordev/mage-for-dev-6.html)

## generate:schema-add-column

    Usage:
        $ pestle.phar magento2:generate:schema-add-column

    Arguments:

    Options:

    Help:
        Genreated a Magento 2 addColumn DDL definition and inserts into file

        Command scans creates column definition code and, if provided
        attempts to insert it into provided php_file.  Inserting means
        looking for this pattern.
        newTable($installer->getTable('table_name'))->addColumn
        and if found, scanning to the ; and inserting the addColumn

        @command magento2:generate:schema-add-column
        @argument php_file PHP file with newTable call? [skip]
        @argument table Database Table? (packagename_modulename_modelnames)
        @argument column Columns Name? (new_column)
        @argument column_type @callback selectColumnType

The `magento2:generate:schema-add-column` command will generate the PHP code that's needed to add a database column in a schema upgrade class or script.

**Interactive Invocation**

    $ pestle.phar magento2:generate:schema-add-column
    PHP file with newTable call? (skip)]
    Database Table? (packagename_modulename_modelnames) ()] pulsestorm_pestle_thing
    Columns Name? (new_column) ()] sub_title
    Column Type?
    [1] bigint
    [2] boolean
    [3] date
    [4] datetime
    [5] decimal
    [6] float
    [7] integer
    [8] smallint
    [9] varchar
    [10] varbinary
    [11] text
    [12] blob
    [13] mediumtext
    [14] mediumblob
    [15] longblob

    ->addColumn('sub_title',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                "sub_title field"
            )

**Argument Invocation**

    $ pestle.phar magento2:generate:schema-add-column skip pulsestorm_pestle_thing sub_title varchar
    ->addColumn('sub_title',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                "sub_title field"
            )

After running the above command, you can copy and paste the `addColumn` method call into your schema upgrade file.

When invoke the command with the `php_file` argument set to `skip`, the `magento2:generate:schema-add-column` command will output the new column definition.  **However**, if this argument points to PHP file with a schema upgrade class-or-script, pestle will attempt to insert the `addColumn` command automatically into the first `newTable` call that includes your `table_name` argument.

    $ pestle.phar magento2:generate:schema-add-column \
        app/code/Pulsestorm/Pestle/Setup/UpgradeSchema.php \
        pulsestorm_pestle_thing sub_title varchar

    $ pestle.phar magento2:generate:schema-add-column \
        app/code/Pulsestorm/Pestle/upgrade_scripts/schema/0.0.5.php \
        pulsestorm_pestle_thing sub_title varchar

**Further Reading**

- [Magento 2 Setup Migration Scripts](https://alanstorm.com/magento-2-setup-migration-scripts/)
- [Pestle: Generate Schema Upgrade](https://pestle.readthedocs.io/en/latest/magento2-mvc/#generateschema-upgrade)
