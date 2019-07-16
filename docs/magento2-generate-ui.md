## generate:ui:add-to-layout

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

The `magento2:generate:ui:add-to-layout` command will add a `<uiComponent/>` node to a specific block in a layout handle XML file.

**Interactive Invocation**

    $ pestle.phar magento2:generate:ui:add-to-layout
    Layout XML File? ()] app/code/Pulsestorm/Pestle/view/adminhtml/layout/pulsestorm_pestle_things_index_index.xml
    Block or Reference Name? ()] content
    UI Component Name? ()] my-grid
    Added Component

**Argument Invocation**

    $ pestle.phar magento2:generate:ui:add-to-layout \
        app/code/Pulsestorm/Pestle/view/adminhtml/layout/pulsestorm_pestle_things_index_index.xml \
        content
        my-grid

Invoking the above commands will add the following node to the  `pulsestorm_pestle_things_index_index.xml` file (presuming `pulsestorm_pestle_things_index_index.xml` has a  block or reference named `content`)

    <page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
        <referenceContainer name="content">
            <uiComponent name="my-grid"/>
        </referenceContainer>
    </page>

**Further Reading**

- [Pestle: Generate Full Modules](https://pestle.readthedocs.io/en/latest/magento2-generate-full-module/)

- [Magento 2: UI Components](https://alanstorm.com/series/magento-2-ui/)

## generate:ui:grid

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

The `magento2:generate:ui:grid` command will automatically generate the UI Component XML needed to add a grid interface element for a standard Magento CRUD collection.

**Interactive Invocation**

    $ pestle.phar magento2:generate:ui:grid
    Which Module? (Pulsestorm_Gridexample)] Pulsestorm_Pestle
    Create a unique ID for your Listing/Grid! (pulsestorm_gridexample_log)] pulsestorm_pestle_grid
    What Resource Collection Model should your listing use? (Magento\Cms\Model\ResourceModel\Page\Collection)] Pulsestorm\Pestle\Model\ResourceModel\Thing\Collection
    What's the ID field for you model? (pulsestorm_gridexample_log_id)] pulsestorm_pestle_thing_id
    Creating New /path/to/m2/app/code/Pulsestorm/Pestle/view/adminhtml/ui_component/pulsestorm_pestle_grid.xml
    Creating: Pulsestorm\Pestle\Ui\Component\Listing\DataProviders\Pulsestorm\Pestle\Grid
    Creating: Pulsestorm\Pestle\Ui\Component\Listing\Column\Pulsestormpestlegrid\PageActions
    Don't forget to add this to your layout XML with <uiComponent name="pulsestorm_pestle_grid"/>

**Argument Invocation**

    $ pestle.phar magento2:generate:ui:grid Pulsestorm_Pestle \
        pulsestorm_pestle_grid \
        Pulsestorm\Pestle\Model\ResourceModel\Thing\Collection \
        pulsestorm_pestle_thing_id

    Creating New /path/to/m2/app/code/Pulsestorm/Pestle/view/adminhtml/ui_component/pulsestorm_pestle_grid.xml
    Creating: Pulsestorm\Pestle\Ui\Component\Listing\DataProviders\Pulsestorm\Pestle\Grid
    Creating: Pulsestorm\Pestle\Ui\Component\Listing\Column\Pulsestormpestlegrid\PageActions
    Don't forget to add this to your layout XML with <uiComponent name="pulsestorm_pestle_grid"/>

This command needs to know the name of the Magento module where you  new UI component XML file will live (`Pulsestorm_Pestle`), a unique identifier for the grid (`pulsestorm_pestle_grid`), the PHP class name of your collection (`Pulsestorm\Pestle\Model\ResourceModel\Thing\Collection`), and the primary key of the model's database table (`pulsestorm_pestle_thing_id`.

After invoking the above commands, you'll end up with a UI component XML file that looks something like this

    $ cat app/code/Pulsestorm/Pestle/view/adminhtml/ui_component/pulsestorm_pestle_things.xml
    <?xml version="1.0"?>
    <listing xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd">
        <argument name="data" xsi:type="array">
            <item name="js_config" xsi:type="array">
                <item name="provider" xsi:type="string">pulsestorm_pestle_things.pulsestorm_pestle_things_data_source</item>
                <item name="deps" xsi:type="string">pulsestorm_pestle_things.pulsestorm_pestle_things_data_source</item>
            </item>
            <item name="spinner" xsi:type="string">pulsestorm_pestle_things_columns</item>
            <item name="buttons" xsi:type="array">
                <item name="add" xsi:type="array">
                    <item name="name" xsi:type="string">add</item>
                    <item name="label" xsi:type="string">Add New</item>
                    <item name="class" xsi:type="string">primary</item>
                    <item name="url" xsi:type="string">*/Thing/new</item>
                </item>
            </item>
        </argument>
        <dataSource name="pulsestorm_pestle_things_data_source">
            <argument name="dataProvider" xsi:type="configurableObject">
                <argument name="class" xsi:type="string">Pulsestorm\Pestle\Ui\Component\Listing\DataProviders\Pulsestorm\Pestle\Things</argument>
                <argument name="name" xsi:type="string">pulsestorm_pestle_things_data_source</argument>
                <argument name="primaryFieldName" xsi:type="string">thing_id</argument>
                <argument name="requestFieldName" xsi:type="string">id</argument>
                <argument name="data" xsi:type="array">
                    <item name="config" xsi:type="array">
                        <item name="update_url" xsi:type="url" path="mui/index/render"/>
                        <item name="storageConfig" xsi:type="array">
                            <item name="indexField" xsi:type="string">thing_id</item>
                        </item>
                    </item>
                </argument>
            </argument>
            <argument name="data" xsi:type="array">
                <item name="js_config" xsi:type="array">
                    <item name="component" xsi:type="string">Magento_Ui/js/grid/provider</item>
                </item>
            </argument>
        </dataSource>
        <listingToolbar name="listing_top">
            <settings>
                <sticky>true</sticky>
            </settings>
            <paging name="listing_paging"/>
            <filters name="listing_filters"/>
        </listingToolbar>
        <columns name="pulsestorm_pestle_things_columns">
            <selectionsColumn name="ids">
                <argument name="data" xsi:type="array">
                    <item name="config" xsi:type="array">
                        <item name="resizeEnabled" xsi:type="boolean">false</item>
                        <item name="resizeDefaultWidth" xsi:type="string">55</item>
                        <item name="indexField" xsi:type="string">thing_id</item>
                        <item name="sortOrder" xsi:type="number">10</item>
                    </item>
                </argument>
            </selectionsColumn>
            <column name="thing_id">
                <argument name="data" xsi:type="array">
                    <item name="config" xsi:type="array">
                        <item name="filter" xsi:type="string">textRange</item>
                        <item name="sorting" xsi:type="string">asc</item>
                        <item name="label" xsi:type="string" translate="true">ID</item>
                    </item>
                </argument>
            </column>
            <actionsColumn name="actions" class="Pulsestorm\Pestle\Ui\Component\Listing\Column\Pulsestormpestlethings\PageActions">
                <argument name="data" xsi:type="array">
                    <item name="config" xsi:type="array">
                        <item name="resizeEnabled" xsi:type="boolean">false</item>
                        <item name="resizeDefaultWidth" xsi:type="string">107</item>
                        <item name="indexField" xsi:type="string">thing_id</item>
                        <item name="sortOrder" xsi:type="number">200</item>
                    </item>
                </argument>
            </actionsColumn>
            <column name="title">
                <argument name="data" xsi:type="array">
                    <item name="config" xsi:type="array">
                        <item name="label" xsi:type="string">Title</item>
                        <item name="sortOrder" xsi:type="number">105</item>
                    </item>
                </argument>
            </column>
        </columns>
    </listing>

This grid will have the standard add and edit buttons, as well as columns for an ID (using the ID field you provided) and a column for "title".  If your collection collects models that don't have a title, you'll want to remove or edit the title column

    <column name="title">
        <argument name="data" xsi:type="array">
            <item name="config" xsi:type="array">
                <item name="label" xsi:type="string">Title</item>
                <item name="sortOrder" xsi:type="number">105</item>
            </item>
        </argument>
    </column>

**Important**: This command will not automatically insert the `<uiComponent name="pulsestorm_pestle_grid"/>` node into a layout XML file for you.  You'll need to do that yourself.

**Further Reading**

- [Magento 2: Introducing UI Components](https://alanstorm.com/magento_2_introducing_ui_components/)
- [Pestle: `magento2:generate:ui:add-to-layout`](https://pestle.readthedocs.io/en/latest/magento2-generate-ui/#generateuiadd-to-layout)

## generate:ui:add-column-text

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

The `magento2:generate:ui:add-column-text` command will add the configuration for a new column to an already-created UI Component XML file.

**Interactive Invocation**

    $ pestle.phar magento2:generate:ui:add-column-text
    Which Listing XML File? ()] app/code/Pulsestorm/Pestle/view/adminhtml/ui_component/pulsestorm_pestle_grid.xml
    New Column Field? (title)] new_field
    New Column Label? (Title)] New Field Label
    Adding to app/code/Pulsestorm/Pestle/view/adminhtml/ui_component/pulsestorm_pestle_grid.xml

**Argument Invocation**

    pestle.phar magento2:generate:ui:add-column-text \
        app/code/Pulsestorm/Pestle/view/adminhtml/ui_component/pulsestorm_pestle_grid.xml
        new_field \
        'New Field Label'

After invoking the above command, the following configuration would be added to the `pulsestorm_pestle_grid.xml` file.

    <listing xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd">
        <!-- ... -->
        <columns name="pulsestorm_pestle_grid_columns">
            <!-- ... -->
            <column name="new_field">
                <argument name="data" xsi:type="array">
                    <item name="config" xsi:type="array">
                        <item name="label" xsi:type="string">New Field Label</item>
                        <item name="sortOrder" xsi:type="number">105</item>
                    </item>
                </argument>
            </column>
            <!-- ... -->
        </columns>
        <!-- ... -->
    </listing>

**Further Reading**

- [Magento 2: Introducing UI Components](https://alanstorm.com/magento_2_introducing_ui_components/)

## generate:ui:form

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

The `magento2:generate:ui:form` command allows you to generate the XML configuration _and_ PHP controllers needed to add a CRUD model editing form to Magento's backend.

**Interactive Invocation**

    $ pestle.phar magento2:generate:ui:form
    Which Module? (Pulsestorm_Formexample)] Pulsestorm_Pestle
    Model Class? (Pulsestorm\Formexample\Model\Thing)] Pulsestorm\Pestle\Model\Thing
    ACL Rule for Controllers? (Pulsestorm_Formexample::ruleName)] Pulsestorm_Pestle::thing_edit

    Creating: Pulsestorm\Pestle\Controller\Adminhtml\Thing\Edit
    Creating: Pulsestorm\Pestle\Controller\Adminhtml\Thing\NewAction
    Creating: Pulsestorm\Pestle\Controller\Adminhtml\Thing\Save
    Creating: Pulsestorm\Pestle\Controller\Adminhtml\Thing\Delete
    Creating: Pulsestorm\Pestle\Controller\Adminhtml\Thing\Delete
    Creating: Pulsestorm\Pestle\Model\Thing\DataProvider
    Creating /path/to/app/code/Pulsestorm/Pestle/view/adminhtml/layout/pulsestorm_pestle_things_thing_edit.xml
    Creating /path/to/app/code/Pulsestorm/Pestle/view/adminhtml/layout/pulsestorm_pestle_things_thing_new.xml
    Creating /path/to/app/code/Pulsestorm/Pestle/view/adminhtml/layout/pulsestorm_pestle_things_thing_save.xml

**Argument Invocation**

    $ pestle.phar magento2:generate:ui:form Pulsestorm_Pestle \
        'Pulsestorm\Pestle\Model\Thing' \
        Pulsestorm_Pestle::thing_edit

    //...

In addition to the needed controller files, layout files, data provider, and UI Component Configuration, the above invocation would create a UI Component configuration file that looks like this

    $ cat app/code/Pulsestorm/Pestle/view/adminhtml/ui_component/pulsestorm_pestle_things_form.xml
    <?xml version="1.0" encoding="UTF-8"?>
    <form xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd">
        <argument name="data" xsi:type="array">
            <item name="js_config" xsi:type="array">
                <item name="provider" xsi:type="string">pulsestorm_pestle_things_form.pulsestorm_pestle_things_form_data_source</item>
                <item name="deps" xsi:type="string">pulsestorm_pestle_things_form.pulsestorm_pestle_things_form_data_source</item>
            </item>
            <item name="label" xsi:type="string" translate="true">Object Information</item>
            <item name="config" xsi:type="array">
                <item name="dataScope" xsi:type="string">data</item>
                <item name="namespace" xsi:type="string">pulsestorm_pestle_things_form</item>
            </item>
            <item name="template" xsi:type="string">templates/form/collapsible</item>
            <item name="buttons" xsi:type="array">
                <item name="back" xsi:type="string">Pulsestorm\Pestle\Block\Adminhtml\Thing\Edit\BackButton</item>
                <item name="delete" xsi:type="string">Pulsestorm\Pestle\Block\Adminhtml\Thing\Edit\DeleteButton</item>
                <item name="reset" xsi:type="string">Pulsestorm\Pestle\Block\Adminhtml\Thing\Edit\ResetButton</item>
                <item name="save" xsi:type="string">Pulsestorm\Pestle\Block\Adminhtml\Thing\Edit\SaveButton</item>
                <item name="save_and_continue" xsi:type="string">Pulsestorm\Pestle\Block\Adminhtml\Thing\Edit\SaveAndContinueButton</item>
            </item>
        </argument>
        <dataSource name="pulsestorm_pestle_things_form_data_source">
            <argument name="dataProvider" xsi:type="configurableObject">
                <argument name="class" xsi:type="string">Pulsestorm\Pestle\Model\Thing\DataProvider</argument>
                <argument name="name" xsi:type="string">pulsestorm_pestle_things_form_data_source</argument>
                <argument name="primaryFieldName" xsi:type="string">thing_id</argument>
                <argument name="requestFieldName" xsi:type="string">thing_id</argument>
                <argument name="data" xsi:type="array">
                    <item name="config" xsi:type="array">
                        <item name="submit_url" xsi:type="url" path="*/*/save"/>
                    </item>
                </argument>
            </argument>
            <argument name="data" xsi:type="array">
                <item name="js_config" xsi:type="array">
                    <item name="component" xsi:type="string">Magento_Ui/js/form/provider</item>
                </item>
            </argument>
        </dataSource>
        <fieldset name="general">
            <argument name="data" xsi:type="array">
                <item name="config" xsi:type="array">
                    <item name="label" xsi:type="string">Form Data</item>
                    <item name="collapsible" xsi:type="boolean">true</item>
                    <item name="opened" xsi:type="boolean">true</item>
                </item>
            </argument>
            <field name="thing_id">
                <argument name="data" xsi:type="array">
                    <item name="config" xsi:type="array">
                        <item name="visible" xsi:type="boolean">false</item>
                        <item name="dataType" xsi:type="string">text</item>
                        <item name="formElement" xsi:type="string">input</item>
                        <item name="dataScope" xsi:type="string">thing_id</item>
                    </item>
                </argument>
            </field>
            <field name="title">
                <argument name="data" xsi:type="array">
                    <item name="config" xsi:type="array">
                        <item name="dataType" xsi:type="string">text</item>
                        <item name="label" xsi:type="string" translate="true">Title</item>
                        <item name="formElement" xsi:type="string">input</item>
                        <item name="sortOrder" xsi:type="number">20</item>
                        <item name="dataScope" xsi:type="string">title</item>
                        <item name="validation" xsi:type="array">
                            <item name="required-entry" xsi:type="boolean">true</item>
                        </item>
                    </item>
                </argument>
            </field>
        </fieldset>
    </form>

If you're looking to create a _full_ module, be sure to checkout the [`magento2:generate:full-module
`](https://pestle.readthedocs.io/en/latest/magento2-generate-full-module/) command

**Further Reading**

- [Magento 2: Introducing UI Components](https://alanstorm.com/magento_2_introducing_ui_components/)

- [Pestle: Generate Full Module](https://pestle.readthedocs.io/en/latest/magento2-generate-full-module/)

## generate:ui:add-form-field

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

The `generate:ui:add-form-field` command will add a new field to a UI Component `<form/>` XML file.

**Interactive Invocation**

    $ pestle.phar magento2:generate:ui:add-form-field
    Path to Form XML File? ()] app/code/Pulsestorm/Pestle/view/adminhtml/ui_component/pulsestorm_pestle_things_form.xml
    Field Name? (title)] new_field
    Label? (Title)] My New Field
    Fieldset Name? (general)] general

**Argument Invocation**

    $ pestle.phar magento2:generate:ui:add-form-field \
        app/code/Pulsestorm/Pestle/view/adminhtml/ui_component/pulsestorm_pestle_things_form.xml \
        new_field \
        "My New Field" \
        general

The `magento2:generate:ui:add-form-field` command needs to know the full path to the `<form/>` XML file where you want the new field (`pulsestorm_pestle_things_form.xml`), your field's `name` property (`new_field`, for computers), your field's Label (`My New Field`, for humans), and the name of the fieldset the field should be inserted into (`general`).

After invoking the above command, the following nodes will be added to the `pulsestorm_pestle_things_form.xml` file.

    <?xml version="1.0" encoding="UTF-8"?>
    <form xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd">
        <!-- ... -->
        <fieldset name="general">
            <!-- ... -->
            <field name="new_field">
                <argument name="data" xsi:type="array">
                    <item name="config" xsi:type="array">
                        <item name="dataType" xsi:type="string">text</item>
                        <item name="label" xsi:type="string">My New Field</item>
                        <item name="formElement" xsi:type="string">input</item>
                        <item name="sortOrder" xsi:type="string">30</item>
                        <item name="dataScope" xsi:type="string">new_field</item>
                        <item name="validation" xsi:type="array">
                            <item name="required-entry" xsi:type="boolean">false</item>
                        </item>
                    </item>
                </argument>
            </field>
        </fieldset>
    </form>

The `magento2:generate:ui:form` command also supports an `is-required` option. The `is-required` option will generate configuration with the `<item name="required-entry" xsi:type="boolean"/>` node to `true`.

    $ pestle.phar magento2:generate:ui:add-form-field \
        --is-required \
        app/code/Pulsestorm/Pestle/view/adminhtml/ui_component/pulsestorm_pestle_things_form.xml \
        new_field \
        "My New Field" \
        general

**Further Reading**

- [Magento 2: Introducing UI Components](https://alanstorm.com/magento_2_introducing_ui_components/)
- [Pestle: `magento2:generate:ui:form`](https://pestle.readthedocs.io/en/latest/magento2-generate-ui/#generateuiform)

## generate:ui:add-form-fieldset

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

Once written add fieldset warning to field entry
