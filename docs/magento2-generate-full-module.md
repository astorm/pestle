## generate:full-module

```plaintext
    Usage:
        $ pestle.phar magento2:generate:full-module

    Arguments:

    Options:

    Help:
        Creates shell script with all pestle commands needed for full module
        output

        @command magento2:generate:full-module
        @argument package_name Package Name? [Pulsestorm]
        @argument module_name Module Name? [Helloworld]
        @argument model_name One Word Model Name? [Thing]
        @option with-phar-name Change pestle.phar to something like pestle_dev
        @option with-setup-upgrade Add Setup Upgrade Call?
```

The `magento:generate:full-module` command works *slightly* differently than other generation commands.  Instead of directly creating PHP and XML module files, this command will generate a *unix shell script*, and that shell script will call *other pestle commands* to generate the module.

**Interactive Invocation**

```plaintext
$ pestle.phar magento2:generate:full-module
Package Name? (Pulsestorm)] Pulsestorm
Module Name? (Helloworld)] Pestle
One Word Model Name? (Thing)] Thing
```

**Argument Invocation**

```plaintext
$ pestle.phar magento2:generate:full-module Pulsestorm Pestle Thing > generate-thing-module.bash
```

The `magento:generate:full-module` command asks you for a Package and module short-name (in order to create a full module name), and a short-name for your model class.  The above invocations would output the following.

```plaintext
#!/bin/bash
pestle.phar magento2:generate:module Pulsestorm Pestle 0.0.1
pestle.phar magento2:generate:crud-model Pulsestorm_Pestle Thing
pestle.phar magento2:generate:acl Pulsestorm_Pestle Pulsestorm_Pestle::things
pestle.phar magento2:generate:menu Pulsestorm_Pestle "" Pulsestorm_Pestle::things Pulsestorm_Pestle::things "Pestle things" pulsestorm_pestle_things/index/index 10
pestle.phar magento2:generate:menu Pulsestorm_Pestle Pulsestorm_Pestle::things Pulsestorm_Pestle::things_list Pulsestorm_Pestle::things "Thing Objects" pulsestorm_pestle_things/index/index 10
pestle.phar magento2:generate:route Pulsestorm_Pestle adminhtml pulsestorm_pestle_things Index Index
pestle.phar magento2:generate:view Pulsestorm_Pestle adminhtml pulsestorm_pestle_things_index_index Main content.phtml 1column
pestle.phar magento2:generate:ui:grid Pulsestorm_Pestle pulsestorm_pestle_things 'Pulsestorm\Pestle\Model\ResourceModel\Thing\Collection' thing_id
pestle.phar magento2:generate:ui:add-column-text app/code/Pulsestorm/Pestle/view/adminhtml/ui_component/pulsestorm_pestle_things.xml title "Title"
pestle.phar magento2:generate:ui:form Pulsestorm_Pestle 'Pulsestorm\Pestle\Model\Thing' Pulsestorm_Pestle::things
pestle.phar magento2:generate:ui:add_to_layout app/code/Pulsestorm/Pestle/view/adminhtml/layout/pulsestorm_pestle_things_index_index.xml content pulsestorm_pestle_things
pestle.phar magento2:generate:acl:change_title app/code/Pulsestorm/Pestle/etc/acl.xml Pulsestorm_Pestle::things "Manage things"
pestle.phar magento2:generate:controller_edit_acl app/code/Pulsestorm/Pestle/Controller/Adminhtml/Index/Index.php Pulsestorm_Pestle::things
pestle.phar magento2:generate:remove-named-node app/code/Pulsestorm/Pestle/view/adminhtml/layout/pulsestorm_pestle_things_index_index.xml block pulsestorm_pestle_block_main

php bin/magento module:enable Pulsestorm_Pestle
```

This output can be saved/redirected to a file and run as a shell script, or copy/pasted line by line into your shell.

Running each and every of the above command will generate the bass files needed for a skeleton Magento 2 Module. This module will have

1. A Model file for saving information to/from the database
2. A Setup Resource Install file to generate the initial database table
3. Backend ACL and Menu configuration for a top level admin navigation item
4. Routing configuration and controller files for an index, edit, and save page
5. UI component configuration for rendering the interface for the above pages

While we recommend you try to understand what each of these commands is doing, the full module generator can give you a great head start when/if you're developing your own Magento 2 features.

**Further Reading:**

- [Pestle 1.1.1 Release](https://alanstorm.com/pestle-1-1-1-released/)
- [Using Pestle to Generate a Full Magento 2 Module](https://vimeo.com/205089771) (screencast)
