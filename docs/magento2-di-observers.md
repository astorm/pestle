## generate:di

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

The `magento2:generate:di` command will add a new Magento object manager dependency to the provided class file.

**Interactive Invocation**

    $ pestle_dev magento2:generate:di
    Which PHP class file are we injecting into? ()] /path/to/class/File.php
    Which class to inject? (Magento\Catalog\Model\ProductFactory)] Php\Class\To\Inject\Into
    Injecting Php\Class\To\Inject into /path/to/class/File.php

**Argument Invocation**

    $ pestle_dev magento2:generate:di /path/to/class/File.php 'Php\Class\To\Inject\Into'
    Injecting Php\Class\To\Inject into /path/to/Php/Class/To/Inject/Into.php

The `magento2:generate:di` command **will not** add any configuration to a Magento system.  This command will

1. Add a class argument arguments to the `__construct` method of another class
2. Add a property assignment for that argument
3. Add a definition for the aforementioned property

**Further Reading**

- [The Magento 2 Object System](https://alanstorm.com/category/magento-2/#magento-2-object-system)
- [Magento 2's Automatic Dependency Injection](https://alanstorm.com/magento2_dependency_injection_2015/)

In other words, the `magento2:generate:di` adds the PHP code to your class.  If your particular dependency requires additional configuration, you'll need to edit your `di.xml` file.

## generate:plugin-xml

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

The `generate:plugin-xml` command will generate the needed XML configuration to add a Magento 2 Plugin to a module, and, (if necessary), generate the configured PHP class.

**Interactive Invocation**

    $ pestle.phar magento2:generate:plugin-xml
    Create in which module? (Pulsestorm_Helloworld)] Pulsestorm_Pestle
    Which class are you plugging into? (Magento\Framework\Logger\Monolog)] Magento\Catalog\Model\Product
    What's your plugin class name? (Pulsestorm\Pestle\Plugin\Magento\Catalog\Model\Product)] Pulsestorm\Pestle\Plugin\Magento\Product
    Added nodes to /path/to/m2/app/code/Pulsestorm/Pestle/etc/di.xml
    Created file /path/to/m2/app/code/Pulsestorm/Pestle/Plugin/Magento/Product.php

**Argument Invocation**

    $ pestle.phar magento2:generate:plugin-xml Pulsestorm_Pestle \
        'Magento\Catalog\Model\Product' \
        'Pulsestorm\Pestle\Plugin\Magento\Product'
    Added nodes to /path/to/m2/app/code/Pulsestorm/Pestle/etc/di.xml
    Created file /path/to/m2/app/code/Pulsestorm/Pestle/Plugin/Magento/Product.php

The `magento2:generate:plugin-xml` command needs to know which module you want to create your plugin in, the name of the class you're writing a plugin for, and the name of your plugin class.  The above configuration would add code like the following to your `di.xml` file

    <config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <!-- ... -->
        <type name="Magento\Catalog\Model\Product">
            <plugin name="pulsestorm_pestle_magento_catalog_model_product" type="Pulsestorm\Pestle\Plugin\Magento\Product"/>
        </type>
    <!-- ... -->
    </config>

and a skeleton plugin class that looks like this.

    $ cat app/code/Pulsestorm/Pestle/Plugin/Magento/Product.php
    <?php
    namespace Pulsestorm\Pestle\Plugin\Magento;
    class Product
    {
        //function beforeMETHOD($subject, $arg1, $arg2){}
        //function aroundMETHOD($subject, $proceed, $arg1, $arg2){return $proceed($arg1, $arg2);}
        //function afterMETHOD($subject, $result){return $result;}
    }

The `use-type-hint` option will generate `$subjects` with a PHP type hint.

    $ pestle.phar magento2:generate:plugin-xml --use-type-hint Pulsestorm_Pestle \
        'Magento\Catalog\Model\Product' \
        'Pulsestorm\Pestle\Plugin\Magento\Product'

    $ cat app/code/Pulsestorm/Pestle/Plugin/Magento/Product.php
    <?php
    namespace Pulsestorm\Pestle\Plugin\Magento;
    class Product
    {
        //function beforeMETHOD(\Magento\Catalog\Model\Product $subject, $arg1, $arg2){}
        //function aroundMETHOD(\Magento\Catalog\Model\Product $subject, $proceed, $arg1, $arg2){return $proceed($arg1, $arg2);}
        //function afterMETHOD(\Magento\Catalog\Model\Product $subject, $result){return $result;}
    }

**Further Reading**

- [The Magento 2 Object System](https://alanstorm.com/series/magento-2-object-system/)
- [Magento 2 Object Manager Plugin System](https://alanstorm.com/magento_2_object_manager_plugin_system/)
## generate:preference

TODO: WRITE THE DOCS!

    Usage:
        $ pestle.phar magento2:generate:preference

    Arguments:

    Options:

    Help:
        Generates a Magento 2.1 ui grid listing and support classes.

        @command magento2:generate:preference
        @argument module Which Module? [Pulsestorm_Helloworld]
        @argument for For which Class/Interface/Type?
        [Pulsestorm\Helloworld\Model\FooInterface]
        @argument type New Concrete Class?
        [Pulsestorm\Helloworld\Model\NewModel]


## generate:observer

    Usage:
        $ pestle.phar magento2:generate:observer

    Arguments:

    Options:

    Help:
        Generates Magento 2 Observer
        This command generates the necessary files and configuration to add
        an event observer to a Magento 2 system.

        pestle.phar magento2:generate:observer Pulsestorm_Generate
        controller_action_predispatch pulsestorm_generate_listener3
        'Pulsestorm\Generate\Model\Observer3'

        @command magento2:generate:observer
        @argument module Full Module Name? [Pulsestorm_Generate]
        @argument event_name Event Name? [controller_action_predispatch]
        @argument observer_name Observer Name? [<$module$>_listener]
        @argument model_name @callback getModelName

The `magento2:generate:observer` command generates the configuration and class files needed to observe an event dispatched by Magento 2's event/observer system.

**Interactive Invocation**

    $ pestle.phar magento2:generate:observer
    Full Module Name? (Pulsestorm_Generate)] Pulsestorm_Pestle
    Event Name? (controller_action_predispatch)] controller_action_predispatch
    Observer Name? (pulsestorm_pestle_listener)] pulsestorm_pestle_listener_before_execute
    Class Name? (Pulsestorm\Pestle\Observer\Listener\Before\Execute)] Pulsestorm\Pestle\Observer\Listener\Before\Execute
    Creating: /path/to/m2/app/code/Pulsestorm/Pestle/etc/events.xml
    Creating: Pulsestorm\Pestle\Observer\Listener\Before\Execute

**Argument Invocation**

    $ pestle.phar magento2:generate:observer Pulsestorm_Pestle \
        controller_action_predispatch \
        pulsestorm_pestle_listener_before_execute \
        'Pulsestorm\Pestle\Observer\Listener\Before\Execute' \
        /path/to/m2/app/code/Pulsestorm/Pestle/etc/events.xml \
        'Pulsestorm\Pestle\Observer\Listener\Before\Execute'

The `magento2:generate:observer` command needs to know the module you want to generate your event observer in (`Pulsestorm_Pestle`), the name of the the event you're listening for (`controller_action_predispatch`), a unique name for your observer (`pulsestorm_pestle_listener_before_execute`), and the name of your new observer class (`Pulsestorm\Pestle\Observer\Listener\Before\Execute`).

The above invocations would generate an `events.xml` configuration that looked like the following

    <?xml version="1.0"?>
    <config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Event/etc/events.xsd">
        <event name="controller_action_predispatch">
            <observer name="pulsestorm_pestle_listener_before_execute"
                      instance="Pulsestorm\Pestle\Observer\Listener\Before\Execute"/>
        </event>
    </config>

and an observer class file that looks like this

    <?php
    namespace Pulsestorm\Pestle\Observer\Listener\Before;
    class Execute implements \Magento\Framework\Event\ObserverInterface
    {
        public function execute(\Magento\Framework\Event\Observer $observer){
            exit(__FILE__);
        }
    }

**Further Reading**
