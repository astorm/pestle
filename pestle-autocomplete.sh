#!/bin/bash

# 'globals' within bash context
# used for caching purposes to avoid calling
# pestle unless we have to
pestle_magento2_base_directory=""
pestle_module_suggestions=""
pestle_have_suggestions_for=""
pestle_arg_types_suggestions=""
pestle_currently_suggesting=""

_commandList ()
{
    echo "magento2:base-dir"
    echo "codecept:convert-selenium-id-for-codecept"
    echo "magento2:check-templates"
    echo "magento2:class-from-path"
    echo "magento2:convert-class"
    echo "magento2:convert-observers-xml"
    echo "magento2:convert-system-xml"
    echo "magento2:extract-mage2-system-xml-paths"
    echo "magento2:fix-direct-om"
    echo "magento2:fix-permissions-modphp"
    echo "magento2:path-from-class"
    echo "magento2:read-rest-schema"
    echo "magento2:generate:acl"
    echo "magento2:generate:acl:change-title"
    echo "magento2:generate:command"
    echo "magento2:generate:config-helper"
    echo "magento2:generate:controller-edit-acl"
    echo "magento2:generate:crud-model"
    echo "magento2:generate:di"
    echo "magento2:generate:full-module"
    echo "magento2:generate:install"
    echo "magento2:generate:menu"
    echo "magento2:generate:module"
    echo "magento2:generate:observer"
    echo "magento2:generate:plugin-xml"
    echo "magento2:generate:preference"
    echo "magento2:generate:psr-log-level"
    echo "magento2:generate:registration"
    echo "magento2:generate:remove-named-node"
    echo "magento2:generate:route"
    echo "magento2:generate:schema-upgrade"
    echo "magento2:generate:theme"
    echo "magento2:generate:ui:add-column-text"
    echo "magento2:generate:ui:add-form-field"
    echo "magento2:generate:ui:add-schema-column"
    echo "magento2:generate:ui:add-to-layout"
    echo "magento2:generate:ui:form"
    echo "magento2:generate:ui:grid"
    echo "magento2:generate:view"
    echo "magento2:scan:acl-used"
    echo "magento2:scan:class-and-namespace"
    echo "magento2:scan:htaccess"
    echo "magento2:scan:registration"
    echo "magento2:search:search-controllers"
    echo "nexmo:send-text"
    echo "nexmo:store-credentials"
    echo "nexmo:verify-request"
    echo "nexmo:verify-sendcode"
    echo "parsing:citicard"
    echo "parsing:csv-to-iif"
    echo "parsing:wf"
    echo "pestle:baz-bar"
    echo "pestle:build-command-list"
    echo "pestle:clear-cache"
    echo "pestle:dev-import"
    echo "pestle:dev-namespace"
    echo "pestle:export-as-symfony"
    echo "pestle:export-module"
    echo "pestle:foo-bar"
    echo "pestle:generate-command"
    echo "pestle:hello-argument"
    echo "pestle:pestle-run-file"
    echo "php:extract-session"
    echo "php:format-php"
    echo "php:test-namespace-integrity"
    echo "pulsestorm:build-book"
    echo "pulsestorm:md-to-say"
    echo "pulsestorm:monty-hall-problem"
    echo "pulsestorm:orphan-content"
    echo "pulsestorm:pandoc-md"
    echo "pulsestorm:tax-estimate"
    echo "twitter:api:oauth"
    echo "hello-world"
    echo "help"
    echo "list-commands"
    echo "selfupdate"
    echo "test-output"
    echo "testbed"
    echo "version"
    echo "wordpress:export:xml"
    echo "wordpress:parse:urls"

    return 0;
}

_observer_list(){

    #all this should ideally be imported from another file somehow
    #so that same file can be used by php readline's autocomplete
    echo "cms_page_prepare_save"
    echo "adminhtml_cmspage_on_delete"
    echo "adminhtml_cmspage_on_delete"
    echo "cms_controller_router_match_before"
    echo "cms_wysiwyg_images_static_urls_allowed"
    echo "cms_page_render"
    echo "checkout_controller_onepage_saveOrder"
    echo "checkout_onepage_controller_success_action"
    echo "checkout_cart_add_product_complete"
    echo "checkout_cart_update_item_complete"
    echo "checkout_allow_guest"
    echo "shortcut_buttons_container"
    echo "custom_quote_process"
    echo "checkout_quote_init"
    echo "load_customer_quote_before"
    echo "checkout_quote_destroy"
    echo "restore_quote"
    echo "checkout_cart_product_add_after"
    echo "checkout_cart_update_items_before"
    echo "checkout_cart_update_items_after"
    echo "checkout_cart_save_before"
    echo "checkout_cart_save_after"
    echo "checkout_cart_product_update_after"
    echo "checkout_type_onepage_save_order_after"
    echo "checkout_submit_all_after"
    echo "on_view_report"
    echo "customer_register_success"
    echo "customer_account_edited"
    echo "adminhtml_customer_prepare_save"
    echo "adminhtml_customer_save_after"
    echo "adminhtml_block_html_before"
    echo "customer_customer_authenticated"
    echo "customer_data_object_login"
    echo "visitor_init"
    echo "visitor_activity_save"
    echo "customer_session_init"
    echo "customer_login"
    echo "customer_data_object_login"
    echo "customer_login"
    echo "customer_data_object_login"
    echo "customer_logout"
    echo "customer_customer_authenticated"
    echo "customer_address_format"
    echo "customer_save_after_data_object"
    echo "admin_permissions_role_prepare_save"
    echo "permissions_role_html_before"
    echo "admin_user_authenticate_before"
    echo "admin_user_authenticate_after"
    echo "admin_user_authenticate_after"
    echo "sendfriend_product"
    echo "adminhtml_block_eav_attribute_edit_form_init"
    echo "eav_collection_abstract_load_before"
    echo "paypal_express_place_order_success"
    echo "payment_method_assign_data_"
    echo "payment_method_assign_data"
    echo "payment_form_block_to_html_before"
    echo "payment_method_is_active"
    echo "payment_method_assign_data_"
    echo "payment_method_assign_data"
    echo "payment_method_assign_data_"
    echo "payment_method_assign_data"
    echo "payment_method_is_active"
    echo "payment_cart_collect_items_and_amounts"
    echo "clean_cache_after_reindex"
    echo "clean_cache_by_tags"
    echo "catalog_product_prepare_index_select"
    echo "checkout_directpost_placeOrder"
    echo "admin_system_config_changed_section_currency_before_reinit"
    echo "admin_system_config_changed_section_currency"
    echo "adminhtml_cache_flush_system"
    echo "swatch_gallery_upload_image_after"
    echo "admin_sales_order_address_update"
    echo "adminhtml_sales_order_creditmemo_register_before"
    echo "adminhtml_sales_order_create_process_data_before"
    echo "adminhtml_sales_order_create_process_data"
    echo "adminhtml_customer_orders_add_action_renderer"
    echo "sales_order_place_before"
    echo "sales_order_place_after"
    echo "order_cancel_after"
    echo "rss_order_new_collection_select"
    echo "sales_order_creditmemo_cancel"
    echo "sales_order_state_change_before"
    echo "sales_order_item_cancel"
    echo "sales_order_invoice_pay"
    echo "sales_order_invoice_cancel"
    echo "sales_order_invoice_register"
    echo "sales_order_payment_capture"
    echo "sales_order_payment_place_start"
    echo "sales_order_payment_place_end"
    echo "sales_order_payment_pay"
    echo "sales_order_payment_cancel_invoice"
    echo "sales_order_payment_void"
    echo "sales_order_payment_refund"
    echo "sales_order_payment_cancel_creditmemo"
    echo "sales_order_payment_cancel"
    echo "email_creditmemo_comment_set_template_vars_before"
    echo "email_shipment_comment_set_template_vars_before"
    echo "email_invoice_comment_set_template_vars_before"
    echo "email_invoice_set_template_vars_before"
    echo "email_shipment_set_template_vars_before"
    echo "email_order_comment_set_template_vars_before"
    echo "email_creditmemo_set_template_vars_before"
    echo "email_order_set_template_vars_before"
    echo "email_invoice_set_template_vars_before"
    echo "sales_order_invoice_register"
    echo "sales_order_status_unassign"
    echo "customer_address_format"
    echo "email_creditmemo_set_template_vars_before"
    echo "sales_order_creditmemo_refund"
    echo "email_shipment_set_template_vars_before"
    echo "sales_sale_collection_query_before"
    echo "sales_convert_order_to_quote"
    echo "sales_convert_order_item_to_quote_item"
    echo "checkout_submit_all_after"
    echo "catalog_product_validate_variations_before"
    echo "gift_options_prepare_items"
    echo "catalog_product_get_final_price"
    echo "catalog_product_option_price_configuration_after"
    echo "catalog_product_prepare_index_select"
    echo "prepare_catalog_product_collection_prices"
    echo "catalog_product_get_final_price"
    echo "catalog_product_get_final_price"
    echo "adminhtml_system_config_advanced_disableoutput_render_before"
    echo "admin_system_config_changed_section_"
    echo "category_prepare_ajax_response"
    echo "catalog_category_prepare_save"
    echo "catalog_controller_category_delete"
    echo "catalog_product_to_website_change"
    echo "controller_action_catalog_product_save_entity_after"
    echo "catalog_product_gallery_upload_image_after"
    echo "catalog_product_new_action"
    echo "catalog_product_edit_action"
    echo "catalog_controller_category_init_after"
    echo "catalog_product_compare_remove_product"
    echo "catalog_product_compare_add_product"
    echo "catalog_controller_product_init_before"
    echo "catalog_controller_product_init_after"
    echo "catalog_controller_product_view"
    echo "rss_catalog_category_xml_callback"
    echo "rss_catalog_new_xml_callback"
    echo "rss_catalog_special_xml_callback"
    echo "shortcut_buttons_container"
    echo "adminhtml_catalog_category_tree_is_moveable"
    echo "adminhtml_catalog_category_tree_can_add_root_category"
    echo "adminhtml_catalog_category_tree_can_add_sub_category"
    echo "adminhtml_catalog_product_form_prepare_excluded_field_list"
    echo "adminhtml_catalog_product_edit_tab_attributes_create_html_before"
    echo "adminhtml_catalog_product_edit_prepare_form"
    echo "adminhtml_catalog_product_edit_element_types"
    echo "catalog_product_gallery_prepare_layout"
    echo "adminhtml_catalog_product_grid_prepare_massaction"
    echo "adminhtml_catalog_product_attribute_set_main_html_before"
    echo "adminhtml_catalog_product_attribute_set_toolbar_main_html_before"
    echo "adminhtml_product_attribute_types"
    echo "product_attribute_form_build_main_tab"
    echo "product_attribute_form_build"
    echo "product_attribute_form_build_front_tab"
    echo "adminhtml_catalog_product_attribute_edit_frontend_prepare_form"
    echo "adminhtml_catalog_product_edit_prepare_form"
    echo "adminhtml_catalog_product_edit_element_types"
    echo "product_attribute_grid_build"
    echo "catalog_product_view_config"
    echo "catalog_product_upsell"
    echo "catalog_product_option_price_configuration_after"
    echo "catalog_block_product_list_collection"
    echo "catalog_block_product_status_display"
    echo "clean_cache_by_tags"
    echo "clean_cache_by_tags"
    echo "category_move"
    echo "clean_cache_by_tags"
    echo "rss_catalog_notify_stock_collection_select"
    echo "catalog_product_is_salable_before"
    echo "catalog_product_is_salable_after"
    echo "catalog_category_change_products"
    echo "catalog_category_delete_after_done"
    echo "catalog_product_delete_after_done"
    echo "catalog_category_tree_init_inactive_category_ids"
    echo "catalog_category_tree_init_inactive_category_ids"
    echo "catalog_category_flat_loadnodes_before"
    echo "prepare_catalog_product_index_select"
    echo "prepare_catalog_product_index_select"
    echo "prepare_catalog_product_index_select"
    echo "prepare_catalog_product_index_select"
    echo "prepare_catalog_product_index_select"
    echo "catalog_product_compare_item_collection_clear"
    echo "catalog_prepare_price_select"
    echo "catalog_product_collection_load_after"
    echo "catalog_product_collection_before_add_count_to_categories"
    echo "catalog_product_collection_apply_limitations_after"
    echo "catalog_product_attribute_update_before"
    echo "catalog_product_get_final_price"
    echo "adminhtml_product_attribute_types"
    echo "adminhtml_widget_grid_filter_collection"
    echo "sales_prepare_amount_expression"
    echo "wishlist_share"
    echo "wishlist_add_product"
    echo "wishlist_update_item"
    echo "wishlist_items_renewed"
    echo "product_option_renderer_init"
    echo "rss_wishlist_xml_callback"
    echo "wishlist_add_item"
    echo "wishlist_product_add_after"
    echo "wishlist_item_collection_products_after_load"
    echo "adminhtml_cache_flush_system"
    echo "adminhtml_cache_flush_all"
    echo "clean_media_cache_after"
    echo "clean_static_files_cache_after"
    echo "adminhtml_cache_flush_all"
    echo "adminhtml_cache_flush_system"
    echo "clean_catalog_images_cache_after"
    echo "store_delete"
    echo "store_group_save"
    echo "theme_save_after"
    echo "adminhtml_block_html_before"
    echo "backend_block_widget_grid_prepare_grid_before"
    echo "adminhtml_store_edit_form_prepare_form"
    echo "backend_auth_user_login_success"
    echo "backend_auth_user_login_failed"
    echo "backend_auth_user_login_failed"
    echo "persistent_session_expired"
    echo "persistent_session_expired"
    echo "adminhtml_cache_refresh_type"
    echo "tax_settings_change_after"
    echo "tax_settings_change_after"
    echo "tax_settings_change_after"
    echo "tax_settings_change_after"
    echo "tax_settings_change_after"
    echo "tax_rate_data_fetch"
    echo "catelogsearch_searchable_attributes_load_after"
    echo "catelogsearch_searchable_attributes_load_after"
    echo "catalogsearch_reset_search_result"
    echo "review_controller_product_init_before"
    echo "review_controller_product_init"
    echo "review_controller_product_init_after"
    echo "rss_catalog_review_collection_select"
    echo "rating_rating_collection_load_before"
    echo "review_review_collection_load_before"
    echo "catalog_product_import_bunch_delete_commit_before"
    echo "catalog_product_import_bunch_delete_after"
    echo "catalog_product_import_finish_before"
    echo "catalog_product_import_bunch_save_after"
    echo "checkout_controller_multishipping_shipping_post"
    echo "multishipping_checkout_controller_success_action"
    echo "checkout_type_multishipping_set_shipping_items"
    echo "checkout_type_multishipping_create_orders_single"
    echo "checkout_submit_all_after"
    echo "checkout_multishipping_refund_all"
    echo "payment_method_assign_data_vault"
    echo "payment_method_assign_data_vault_"
    echo "page_block_html_topmenu_gethtml_before"
    echo "page_block_html_topmenu_gethtml_after"
    echo "assign_theme_to_stores_after"
    echo "admin_system_config_changed_section_design"
    echo "admin_system_config_changed_section_design"
    echo "adminhtml_cache_refresh_type"
    echo "depersonalize_clear_session"
    echo "clean_cache_by_tags"
    echo "adminhtml_controller_salesrule_prepare_save"
    echo "adminhtml_block_promo_widget_chooser_prepare_collection"
    echo "adminhtml_block_salesrule_actions_prepareform"
    echo "adminhtml_promo_quote_edit_tab_coupons_form_prepare_form"
    echo "salesrule_validator_process"
    echo "sales_quote_address_discount_item"
    echo "sales_quote_address_discount_item"
    echo "salesrule_rule_condition_combine"
    echo "salesrule_rule_get_coupon_types"
    echo "store_address_format"
    echo "checkout_submit_before"
    echo "checkout_submit_all_after"
    echo "sales_model_service_quote_submit_before"
    echo "sales_model_service_quote_submit_success"
    echo "sales_model_service_quote_submit_failure"
    echo "prepare_catalog_product_collection_prices"
    echo "sales_quote_item_collection_products_after_load"
    echo "items_additional_data"
    echo "sales_quote_collect_totals_before"
    echo "sales_quote_collect_totals_after"
    echo "sales_quote_address_collect_totals_before"
    echo "sales_quote_address_collect_totals_after"
    echo "sales_quote_item_qty_set_after"
    echo "sales_quote_item_set_product"
    echo "sales_convert_quote_to_order"
    echo "sales_quote_remove_item"
    echo "sales_quote_add_item"
    echo "sales_quote_product_add_after"
    echo "controller_action_nocookies"
    echo "adminhtml_controller_catalogrule_prepare_save"
    echo "catalogrule_dirty_notice"
    echo "clean_cache_by_tags"

    return 0;
}

#echos the command arguement types in order
_pestleBootStrapCommandArgTypes () {
    local machine_readable output 
    machine_readable='--is-machine-readable'
    output=( $($1 list-commands $machine_readable $2) )
    echo ${output[*]} 
}

#given the pestle executable,
#a command,
#the position of the current word and 
#the position of the command
#will return the arguement type to suggest for
_getArgTypeToSuggestFor (){
    local command_input

    #pestle_have_suggestions_for and pestle_arg_types_suggestions are global
    #need this to decide whether we need to rebootstrap pestle or not
    #because running pestle is laggy and not ideal on every tab hit
    if [[ "'$pestle_have_suggestions_for'" != "'$2'" ]]; then 
        pestle_arg_types_suggestions=( $(_pestleBootStrapCommandArgTypes $1 $2) )
    fi
    pestle_have_suggestions_for="$2"

    let command_input=$3-$4
    let command_input=$command_input-1
    pestle_currently_suggesting=${pestle_arg_types_suggestions[$command_input]}
}

# returns 0 if we have a valid magento root directory
#TODO properly have pestle exit with correct exit values
#and check $? instead of checking string output
_haveMagentoRootDirectory () {
    if [[ "$pestle_magento2_base_directory" == "Could not find base Magento directory" ]]; then
        return 1
    fi

    if [[ "$pestle_magento2_base_directory" == "" ]]; then
        return 1
    fi

    return 0
}

#obtain the magento2_base_directory and cache it
# TODO: at some point we need to detect a change in magento
# root directories, and refresh the cache for commands that
# depend on it
_getMagentoRootDirectory (){
    if ! _haveMagentoRootDirectory; then
        pestle_magento2_base_directory=$($1 magento2:base-dir)
    fi
}

_generateModuleSuggestions (){
    #TODO automatically detect new modules under app/code and regen suggestions
    if [[ "$pestle_module_suggestions" == "" ]] && _haveMagentoRootDirectory; then
        pestle_module_suggestions=$(find "$pestle_magento2_base_directory/app/code" -maxdepth 2 -type d | sed 's/.*app\/code\///g' | grep '/' | sed 's/\//_/g' | tail -n+2)
    fi
}

_pestleAutocomplete ()
{
    local all cur prev words cword command command_input suggesting_a
    _get_comp_words_by_ref -n : cur prev words cword

    local counter=1
    while [ $counter -lt $cword ]; do
	    case "${words[$counter]}" in
            #skip switches
            -*)
                ;;
            #skip long-opts
            =)
                (( counter++ ))
                ;;
            *)
                command="${words[$counter]}"
                command_pos=$counter
                break
                ;;
        esac
        (( counter++ ))
    done

    command=$(echo "$command" | sed 's/\\//g')
    if [[ "$command" =~ magento2\:generate\:[a-zA-Z] ]] ; then 
        _getArgTypeToSuggestFor $1 $command $cword $command_pos
        #TODO: fix this type inconsistency in commands (example: generate:observer vs generate:command)
        #one takes one the other takes the other
        if [[ "$pestle_currently_suggesting" == "module" ]] || [[ "$pestle_currently_suggesting" == "module_name" ]]; then
            _getMagentoRootDirectory $1
            _generateModuleSuggestions $1
            all=$pestle_module_suggestions
        fi
        if [ "$command" == "magento2:generate:observer" ] ; then
            if [ "$pestle_currently_suggesting" == "event_name" ] ; then
                all=$(_observer_list)
            fi
        fi
    else
        all=$(_commandList)
    fi

    COMPREPLY=( $(compgen -W "$all" $cur) )
    __ltrim_colon_completions "$cur"
    return 0
}
complete -o default -F _pestleAutocomplete pestle.phar
complete -o default -F _pestleAutocomplete pestle_dev