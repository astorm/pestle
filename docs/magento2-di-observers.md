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

TODO: WRITE THE DOCS!

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

TODO: WRITE THE DOCS!

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


