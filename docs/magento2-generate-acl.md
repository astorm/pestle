## generate:acl

```plaintext
Usage:
    $ pestle.phar magento2:generate:acl

Arguments:

Options:

Help:
    Generates a Magento 2 acl.xml file.

    @command magento2:generate:acl
    @argument module_name Which Module? [Pulsestorm_HelloWorld]
    @argument rule_ids Rule IDs?
    [<$module_name$>::top,<$module_name$>::config,]
```

The `magento2:generate:acl` command will generate a new *Access Control Rule* for your module.

**Interactive Invocation**

```plaintext
$ pestle.phar magento2:generate:acl
Which Module? (Pulsestorm_HelloWorld)] Pulsestorm_Pestle
Rule IDs? (Pulsestorm_Pestle::top,Pulsestorm_Pestle::config,)]
Created /path/to/m2/app/code/Pulsestorm/Pestle/etc/acl.xml
```

**Argument Invocation**

```plaintext
$ pestle.phar magento2:generate:acl Pulsestorm_Pestle "Pulsestorm_Pestle::top,Pulsestorm_Pestle::config"
Created /path/to/m2/app/code/Pulsestorm/Pestle/etc/acl.xml
```

The first argument is is module you want to create the access control rule for.  The second argument is a list of rules, each representing one of level of the access control tree.  Pestle will create any nodes needed to reach the bottom of the tree, starting from the `<resource id="Magento_Backend::admin">` node.

For example, when you run the following command

```plaintext
pestle.phar magento2:generate:acl Pulsestorm_Pestle "Pulsestorm_Pestle::top,Pulsestorm_Pestle::config"
```

pestle will create a tree that looks like this.

```plaintext
/path/to/m2/app/code/Pulsestorm/Pestle/etc/acl.xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Acl/etc/acl.xsd">
    <acl>
        <resources>
            <resource id="Magento_Backend::admin">
                <resource id="Pulsestorm_Pestle::top" title="TITLE HERE FOR">
                    <resource id="Pulsestorm_Pestle::config" title="TITLE HERE FOR"/>
                </resource>
            </resource>
        </resources>
    </acl>
</config>
```

**Further Reading**

- [Magento 2: Understanding Access Control List Rules](https://alanstorm.com/magento_2_understanding_access_control_list_rules/)

## generate:acl:change-title

```plaintext
Usage:
    $ pestle.phar magento2:generate:acl:change-title

Arguments:

Options:

Help:
    Changes the title of a specific ACL rule in a Magento 2 acl.xml file

    @command magento2:generate:acl:change-title
    @argument path_acl Path to ACL file?
    @argument acl_rule_id ACL Rule ID?
    @argument title New Title?
```

The `magento2:generate:acl:change-title` will edit the value in an ACL rule's title attribute.

**Interactive Invocation**

```plaintext
$ pestle.phar magento2:generate:acl:change-title
Path to ACL file? ()] app/code/Pulsestorm/Pestle/etc/acl.xml
ACL Rule ID? ()] Pulsestorm_Pestle::config
New Title? ()] Configuration Settings
Changed Title
```

**Argument Invocation**

```plaintext
$ pestle.phar magento2:generate:acl:change-title app/code/Pulsestorm/Pestle/etc/acl.xml Pulsestorm_Pestle::config "Configuration Settings"
Changed Title
```

In the above scenarios, running pestle would change the following access control node from

```plaintext
<resource id="Pulsestorm_Pestle::config" title="TITLE HERE FOR"/>
```

to

```plaintext
<resource id="Pulsestorm_Pestle::config" title="Configuration Settings"/>
```

This command is most useful for changing the default titles after using the [`magento2-generate-acl`](https://pestle.readthedocs.io/en/latest/magento2-generate-acl/#generateacl) command.

**Further Reading**

- [Magento 2: Understanding Access Control List Rules](https://alanstorm.com/magento_2_understanding_access_control_list_rules/)

## generate:controller-edit-acl

```plaintext
Usage:
    $ pestle.phar magento2:generate:controller-edit-acl

Arguments:

Options:

Help:
    Edits the const ADMIN_RESOURCE value of an admin controller

    @command magento2:generate:controller-edit-acl
    @argument path_controller Path to Admin Controller
    @argument acl_rule Path to Admin Controller
```

Many Magento 2 admin controller class files contain an `ADMIN_RESOURCE` constant.  This constant controls which logged in users can access the page provided by the controller.  The `magento2:generate:controller-edit-acl` command allows you to *edit* the string value for this constant **in the controller file**.

**Interactive Invocation**

```plaintext
$ pestle.phar magento2:generate:controller-edit-acl
Path to Admin Controller ()] app/code/Pulsestorm/Pestle/Controller/Index/Index.php
ACL Rule ()] Pulsestorm_Pestle::config
ADMIN_RESOURCE constant value changed
```

**Argument Invocation**

```plaintext
$ pestle.phar magento2:generate:controller-edit-acl app/code/Pulsestorm/Pestle/Controller/Index/Index.php Pulsestorm_Pestle::config
ADMIN_RESOURCE constant value changed
```

If the class in question does not contain an `ADMIN_RESOURCE` constant, `magento2:generate:controller-edit-acl` will tell you.

```plaintext
$ pestle.phar magento2:generate:controller-edit-acl app/code/Pulsestorm/Pestle/Controller/Index/Index.php Pulsestorm_Pestle::config
No ADMIN_RESOURCE constant in class file
```

**Further Reading**

- [Magento 2: Understanding Access Control List Rules](https://alanstorm.com/magento_2_understanding_access_control_list_rules/)
- [Magento 2: Admin MVC/MVVM Endpoints](https://alanstorm.com/magento_2_admin_mvcmvvm_endpoints/)

## generate:menu

```plaintext
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
```

The `magento2:generate:menu` command adds the configuration needed to create a Magento admin menu item.  An admin menu item is the link in the left side navigation that points to a specific admin page.  It is necessary to generate a menu item as all Magento backend URLs are protected with a unique "nonce token", and this token is generated by the menu system.

**Interactive Invocation**

```plaintext
$ pestle.phar magento2:generate:menu
Module Name? (Pulsestorm_HelloGenerate)] Pulsestorm_Pestle
Is this a new top level menu? (Y/N) (N)] Y
Menu Link ID (Pulsestorm_Pestle::unique_identifier)] Pulsestorm_Pestle::menu_id_created_now
ACL Resource (Pulsestorm_Pestle::menu_id_created_now)] Pulsestorm_Pestle::acl_rule_for_menu
Link Title (My Link Title)] Menu Title
Three Segment Action (frontname/index/index)] pulsestorm_pestle/index/index
Sort Order? (10)] 10
Writing: /path/to/m2/app/code/Pulsestorm/Pestle/etc/adminhtml/menu.xml
```

**Argument Invocation**

```plaintext
$ pestle.phar magento2:generate:menu Pulsestorm_Pestle Y \
  Pulsestorm_Pestle::menu_id_created_now Pulsestorm_Pestle::acl_rule_for_menu \
  "Menu Title" pulsestorm_pestle/index/index 10
```

The `magento2:generate:menu` command needs to know what module you want to create your menu item in, which (previously generated) ACL rule should control the visibility of the menu, text for the menu title, the `module/controller/action` URL segments that identify the controller/URL, and a numerical value to control the order the menu item will appear in with regards to other menu items at the same level.

The above invocations will create a new top level item.  If you want to create a sub-menu item, you'll need to use the command in interactive mode and answer `N` to the "is this a new top level menu" question.

```plaintext
Module Name? (Pulsestorm_HelloGenerate)] Pulsestorm_Pestle
Is this a new top level menu? (Y/N) (N)]
Select Parent Menu:
[1] System	(Magento_Backend::system)
[2] Customers	(Magento_Customer::customer)
[3] Reports	(Magento_Reports::report)
[4] Find Partners & Extensions	(Magento_Marketplace::partners)
[5] Sales	(Magento_Sales::sales)
[6] Dashboard	(Magento_Backend::dashboard)
[7] (Magento_Backend::system)
[8] Marketing	(Magento_Backend::marketing)
[9] Content	(Magento_Backend::content)
[10] Stores	(Magento_Backend::stores)
[11] Catalog	(Magento_Catalog::catalog)
()]
```

Pestle will search through any existing menu.xml files for possible parent menus, and ask you to select one.

**Further Reading**

- [Magento 2: Admin Menu Items](https://alanstorm.com/magento_2_admin_menu_items/)
