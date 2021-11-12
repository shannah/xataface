;<?php exit;
;;------------------------------------------------------------------------------
;; Table tabs
;; -----------
;;
;; The table tabs are the little tabs ('details', 'list', 'find', ...) at the top
;; of the screen.

;; Show the details of the current record
[browse]
	label = Details
	category = table_tabs
	url = "{$this->url('-action=view')}"
	accessKey = "b"
	mode = browse
	permission = view
	order=0
	materialIcon="details"

;; Show a list of the records in the current found set
[list]
	label = List
	category = table_tabs
	url = "{$this->url('-action=list')}"
	accessKey = "l"
	mode = list
	template = Dataface_List_View.html
	permission = list
	order=0.5
	materialIcon=view_list

;; Show a "Find Record Form"
[find]
	label = Find
	category = table_tabs
	url = "{$this->url('-action=find')}"
	accessKey = "f"
	mode = find
	permission = find
	template = Dataface_Find_View.html
	order=0.75
	materialIcon=search
	
[calendar]
	label = Calendar
	category = table_tabs
	mode=calendar
	permission=calendar
	order=1
	url="{$this->url('-action=calendar')}"
	condition="false"
	
[search_index]
	label="This Site"
	category=find_actions
	condition="isset($this->_conf['_index'])"
	action=search_index
	permission=find_multi_table

;;------------------------------------------------------------------------------
;; Table Actions
;; --------------
;; The table actions are the actions that appear in the "actions to be performed"
;; menu in the top right of the screen. (e.g. new, delete, etc..)

;; Create a new record
[new]
	label = "New Record"
	breadcrumb_label="New"
	description = Create a new record
	url = "{$this->url('-action=new', false)}"
	materialIcon="add"
	category = table_actions_menu
	accessKey = n
	mode = browse
	permission = new
	order=1
	class="featured-action"
    rel=child
	
;; Post a record update using HTTP POST
[post]
	permission = post

;; Show all records in the current table	
[show_all]	
	label = Show All	
	description = Show all records in table	
	url = "{$site_href}?-action=list&-table={$table}"	
	icon = "{$dataface_url}/images/zoom-out.gif"	
	accessKey = a	
    ; Removing category as this action is no longer needed for the UI
	;category = table_actions	
	mode = list	
	permission = show all	
	order=4	
	tags="#large#"

[copy_replace] 
    label="Copy Set"
	description="Copy the records in this found set."
	url="{$this->url('-action=copy_replace')}&--copy=1"
	accessKey = c
	mode = copy_replace
	order=5
	icon="{$dataface_url}/images/view.gif"
    rel=child
	
[copy_replace_ui]
	label="Copy Set"
	description="Copy the records in this found set."
	url="{$this->url('-action=copy_replace')}&--copy=1"
	accessKey = c
	mode = copy_replace
	order=5
	icon="{$dataface_url}/images/view.gif"
	category=table_actions
	permission=copy
    rel=child

[update_set]
	label="Update Set"
	description="Update the records in this found set as a group."
	url="{$this->url('-action=copy_replace')}&--copy=0"
	accessKey=u
	mode = copy_replace
	permission = update_set
	order=6
	icon="{$dataface_url}/images/edit.gif"
	category=table_actions
    rel=child




[export_csv]
	label = Export CSV
	description = "Export the current result set in comma separated value (CSV) format.  CSV is compatible with most spread sheet applications like MS Excel"
	url = "{$this->url('-action=export_csv&-skip=0&-limit=999999')}"
	icon = "{$dataface_url}/images/table.gif"
	mode=list
	permission=export_csv
	category=table_actions
	
[export_xml]
	label = Export XML
	description = "Export the current result set as XML."
	url = "{$this->url('-action=export_xml')}"
	permission=export_xml
	mode=list
	category=table_actions
    materialIcon=code
	
[export_json]
	label = Export JSON
	description = "Export the current result set as JSON."
	url = "{$this->url('-action=export_json')}"
	permission=export_json
	
[record_actions]
	label=""
	materialIcon="more_vert"
	subcategory=record_actions
	category="record_actions_menu"
	
[edit_record_actions]
	label=""
	materialIcon="more_vert"
	subcategory=edit_record_actions
	category="edit_record_actions_menu"
    
    
[save_record]
    label="Save"
    category=edit_record_actions_menu
    class="featured-action"
    materialIcon="save"
    url="javascript:jQuery('input[type="submit"]').each(function(e){ if (jQuery(this).attr('name') == '--session:save') {this.click();}});"
    
[cancel_edit_record]
    label="Cancel"
    category=edit_record_actions_menu
    materialIcon="cancel"
    url="{$app->url('-action=browse')}"

    
[new_record_actions]
	label=""
	materialIcon="more_vert"
	subcategory=new_record_actions
	category="new_record_actions_menu"
    
[new_related_record_actions]
	label=""
	materialIcon="more_vert"
	subcategory=new_related_record_actions
	category="new_related_record_actions_menu"
    
[existing_related_record_actions]
	label=""
	materialIcon="more_vert"
	subcategory=existing_related_record_actions
	category="existing_related_record_actions_menu"


[save_new_record > save_record]
    category=new_record_actions_menu
    
[cancel_new_record > cancel_edit_record]
    url="{$app->url('-action=list')}"
    category=new_record_actions_menu
    condition="empty($query['-add-related-context'])"
    
[cancel_new_record_related_context > cancel_new_record]
    condition="!empty($query['-add-related-context'])"
    url="javascript:xataface.goBackToParentContext()"
    
[save_new_related_record > save_record]
    category=new_related_record_actions_menu
    url="javascript:jQuery('input[type="submit"]').each(function(e){ if (jQuery(this).attr('name') == '-Save') {this.click();}});"
    
[save_existing_related_record > save_record]
    category=existing_related_record_actions_menu
    url="javascript:jQuery('input[type="submit"]').each(function(e){ if (jQuery(this).attr('name') == '-Save') {this.click();}});"
    
[cancel_save_existing_record]
    category=existing_related_record_actions_menu
    url="{$app->url('-action=related_records_list')}"
    label="Cancel"
    materialIcon=cancel
    
[cancel_new_related_record]
    category=new_related_record_actions_menu
    url="{$app->url('-action=related_records_list')}"
    label="Cancel"
    materialIcon=cancel
    



[view_xml]
	label = Export XML
	description = "Export an XML representation of this record"
	url = "{$record->getURL('-action=export_xml')}&--single-record-only=1"
	url_condition="$record"
	permission=view xml
	mode=browse
	category=record_actions
	materialIcon=code
	condition="$record"
	

[rss]
	label=RSS
	description=RSS Feed of this found set.
	url="{$this->url('-action=feed&-mode=list')}&--format=RSS2.0"
	materialIcon="rss_feed"
	permission=rss
	category=table_actions
	
[record_rss > rss]
	category=record_actions
	url="{$record->getURL('-action=single_record_search')}&--format=RSS2.0"
	condition="$record"
	url_condition="$record"
	description="Subscribe to receive RSS updates when this record is updated"

[related_rss]
	label="RSS"
    label_prefix="{$app->getRelationship()->getLabel()} "
    label_prefix_condition="$app->getRelationship()"
	description="Subscribe to RSS feed of this relationship"
	url="{$this->url('-action=feed&-mode=list')}&--format=RSS2.0"
	materialIcon=rss_feed
	permission=rss
	category=record_actions
    condition="$query['-relationship'] and $query['-action'] == 'related_records_list'"
	
[related_xml > export_xml]
	category=related_export_actions
    
[related_export_menu]
    category=record_actions
    label="Export"
    label_suffix=" {$app->getRelationship()->getLabel()}"
    label_suffix_condition="$app->getRelationship()"
    condition="$query['-action'] == 'related_records_list' and $query['-relationship']"
    subcategory=related_export_actions
    order=99
    materialIcon=file_download
    
    

[feed]
	mode=list
	permission=rss

[export_csv_related]
	label = Export CSV
	description = "Export the current result set in comma separated value (CSV) format.  CSV is compatible with most spread sheet applications like MS Excel"
	url = "{$this->url('-action=export_csv')}&--related=1"
	;icon = "{$dataface_url}/images/table.gif"
    materialIcon=donut_small
	mode=list
	permission=export_csv
	category=related_export_actions
    condition="$query['-relationship'] and $query['-action'] == 'related_records_list'"

[table_actions]
	label=""
	materialIcon="more_vert"
	subcategory=table_actions
	category=table_actions_menu
    order=99

;; Delete the current record
[delete]
	label = Delete
	description = Delete current record
	url = "{$this->url('-action=delete&-delete-one=1')}"
	materialIcon="delete"
	category = record_actions
	accessKey = d
	mode = browse
	condition = "$query['-mode']=='browse'"
	permission = delete
	order=5
    rel=child

;; Delete all records in the current found set
[delete_found]
	label = Delete Set
	description = Delete found records
	url = "{$this->url('-action=delete&-delete-found=1')}"
	icon = "{$dataface_url}/images/recycle.gif"
	category = table_actions
	mode = list
	condition = "$query['-mode']=='list'"
	permission = delete found
	order=5
    rel=child
	
[delete_selected]
	label="Delete"
	description="Delete selected records"
	permission = delete selected
	category=selected_result_actions
	confirm="Are you sure you want to delete the selected records?"
	icon="{$dataface_url}/images/delete.gif"
    rel=child
	

;; Invalidates the current translations and marks a new version
[invalidate_translations]
	label = "Invalidate Translations"
	description = "Flag all translations of this record (or the currently found records) so that they will be re-translated."
	url = "javascript:invalidateTranslations('{$this->url('-action=invalidate_translations')}&--redirect='+escape('{$this->url()}'));"
	icon = "{$dataface_url}/images/broken.gif"
	category = record_actions
	permission = edit
	condition = "$query['-mode'] == 'browse' and $this->_conf['multilingual_content']"


;; Used in the summary list to edit the current record
[summary_edit]
	label="Edit"
	description="Edit this record"
	url="{$record->getURL('-action=edit')}"
	url_condition="is_a($record,'Dataface_Record')"
	icon="{$dataface_url}/images/edit.gif"
	permission=edit
	category=summary_actions
	condition="$record"

[set_translation_status]
	label = "Set Translation Status"
	description = "Set the translation status of the found set."
	url="{$this->url('-action=set_translation_status')}"
	category=table_actions
	condition="$this->_conf['multilingual_content']"
	permission=edit
	icon="{$dataface_url}/images/workflow.gif"
	order=10

[submit_translation]
	label = "Submit a translation"
	description = "Submit your own translation for this section"
	url = "javascript:window.location='{$this->url('-action=submit_translation')}&--url='+escape(window.location.href)+'&--recordid='+escape('{$context['record_id']}')"
	category=translation_warning_actions

[view_original]
	label = "View original"
	description = "View the original version of this page in its original language"
	url = "{$this->url(array('-lang'=>$this->_conf['default_language']))}"
	category = translation_warning_actions
	

;;------------------------------------------------------------------------------
;; Record Tabs
;; -----------
;; The record tabs are the tabs that always appear at the top of the record
;; detail view.  In 0.5.3 this would consist of a "main" tab and tabs for 
;; all of the relationships of that table.
;; As of 0.6, there is a 'View' tab (read only) AND and 'Edit' tab for editing,
;; in addition to the relationships.

;; View the details of the current record.
[view]
	label = View
	url = "{$this->url('-action=view&-relationship=')}"
	template = Dataface_View_Record.html
	permission = view
	mode = browse
	category = record_tabs
	selected_condition = "$query['-action'] == 'view'"
	order=-2
    page_menu_category=record_actions_menu
    rel=sibling

;; Edit the details of the current record.
[edit]
	label = Edit
	url = "{$this->url('-action=edit&-relationship=')}"
	template = Dataface_Edit_Record.html
	mode = browse
	category = record_actions_menu
	selected_condition = "$query['-action'] == 'edit'"
	permission = edit
	order=-1
	materialIcon=create
    class="featured-action"
    rel=child
	

;; Translate a record
[translate]
	label = Translate
	url = "{$this->url('-action=translate&-relationship=')}"
	template = Dataface_Translate_Record.html
	mode = browse
	category = record_actions
	selected_condition = "$query['-action'] == 'translate'"
	condition = "($tableobj =& Dataface_Table::loadTable($table)) and count($tableobj->getTranslations()) > 0"
	permission = translate
	order=3
	materialIcon=translate
    rel=child
	

;; History for a record
[history]
	url = "{$this->url('-action=history')}"
	template = Dataface_Record_History.html
	mode = browse
	category = record_actions
	selected_condition = "$query['-action'] == 'history'"
	condition = "is_array($this->_conf['history'])"
	permission = history
	order=4
	materialIcon=history
    page_menu_category=record_actions_menu
    rel=child
	

[view_history_record_details]
	mode = browse
	permission = history
    rel=child
	
[single_record_search]
	permission=view
    rel=child
	

;;------------------------------------------------------------------------------
;;  Other actions that don't appear as a button in any particular place on the
;; screen but, nonetheless, need to be defined

;; Show of a list of the records in a specified relationship
[related_records_list]
	mode = browse
	template = Dataface_Related_Records_List.html
	label = "{$query['-relationship']}"
	permission = view
	related=1
	allow_override="relationships.ini"
    page_menu_category=record_actions_menu
    rel=sibling

;;------------------------------------------------------------------------------
;; Relationship Actions
;; --------------------
;; Actions that appear above related record lists.  E.g., "Add New Related Record"

;; Show the "Add Related Record" form to add a record to a relationship
[new_related_record]
	mode = browse
	template = Dataface_Add_New_Related_Record.html
	permission = add new related record
	category = relationship_actions
	label = "Add new {$query['-relationship']} record"
	related=1
    rel=child
    
[new_related_record_menuitem > new_related_record]
    condition="$query['-action'] == 'related_records_list' and $query['-relationship'] and $app->getRelationship() and $app->getRelationship()->supportsAddNew() and !$app->getRelationship()->supportsAddExisting()"
    label = "Add {$app->getRelationship()->getSingularLabel()}"
    materialIcon=add
    category=record_actions_menu
    url="{$app->url('-action=new_related_record')}"
    
[add_first_related_record > new_related_record]
    condition="$query['-action'] == 'related_records_list' and $query['-relationship'] and $app->getRelationship() and $app->getRelationship()->supportsAddNew() and !$app->getRelationship()->supportsAddExisting()"
    label = "Add {$app->getRelationship()->getSingularLabel()}"
    materialIcon=add
    category=empty_relationship_actions
    url="{$app->url('-action=new_related_record')}"
    class=featured-action


;; Show the "Add Existing Related Record" form to add an existing record to a 
;; relationship.
[existing_related_record]
	mode = browse
	template = Dataface_Add_Existing_Related_Record.html
	permission = add existing related record
	category = relationship_actions
	related=1
    rel=child
    
[existing_related_record_menuitem > existing_related_record]
    condition="$query['-action'] == 'related_records_list' and $query['-relationship'] and $app->getRelationship() and $app->getRelationship()->supportsAddExisting()"
    label = "Add {$app->getRelationship()->getSingularLabel()}"
    materialIcon=add
    category=record_actions_menu
    url="{$app->url('-action=existing_related_record')}"

;; Remove record from a relationship
[remove_related_record]
	mode = browse
	template = Dataface_Remove_Related_Record.html
	permission = remove related record
	category=selected_records_actions
	label = remove
	related=1
    rel=child
	
	

[reorder_related_records]
	permission = reorder_related_records
	mode = browse
	related=1

;;------------------------------------------------------------------------------

[login]

[logout]

;; Action to register new users to the application
;; This action is only enabled if allow_register=1 in the [_auth] section of
;; the conf.ini file.
[register]
	;; This should appear in beneath the login form
	category = login_actions
	mode = browse
	label = Register for an account
	url = "{$this->url('-action=register')}"
	;; Only show this action is registration is allowed in the conf.ini
	;; file.
	condition = "$this->_conf['_auth']['allow_register']"
	;; By default we use email validation. i.e accounts are not created until
	;; they have been verified by email.  @see activate
	email_validation=1
    rel=child
	
[forgot_password]
	;; This should appear in beneath the login form
	category = login_actions
	mode = browse
	label = Forgot password
	url="{$this->url('-action=forgot_password')}"
    condition="class_exists('Dataface_AuthenticationTool') and Dataface_AuthenticationTool::getInstance()->isPasswordLoginAllowed()"
    rel=child


;; An action to activate an account after it has been verified by email.
;; This is part 2 of the registration process if email_validation is
;; enabled in the register action.
[activate]
	mode=browse
	;; The number of seconds the user has between filling in the registration
	;; form and activating the account.  Default 3600 seconds = 1 hour.
	time_limit=3600

[import]
	label = Import Records
	mode = import
	description = "Import records into table"
	url = "{$this->url('-action=import')}"
	category=table_actions
	icon="{$dataface_url}/images/worklist.gif"
	permission=import
	order = 20
	condition="$tableObj and $tableObj->hasImportFilters()"

;;------------------------------------------------------------------------------
;; AJAX Actions
[ajax_save]
	category = ajax_actions
	;;permission = ajax_save
	mode=browse
	
[ajax_related_find_form]
	permission=find

[ajax_load]
	category = ajax_actions
	permission = ajax_load
	mode=browse
	
[ajax_form]
	category = ajax_actions
	;;permission = ajax_form ;; We let the action handle its own permissions

;;------------------------------------------------------------------------------
;; Find actions
[find_list]
	permission=find_list
	label = "{$query['-table']}"
	description = "Find records in {$query['-table']} category only."
	order = 10
	category=find_actions
	action="{$app->getSearchTarget()}"





;;----------------------------------------------------------------------------
;; Actions available to a history record - displayed in list view.
[history_restore_record]
	category=history_record_actions
	label = "Restore"
	url = "javascript: historyToolClient.restoreRecord('{$context['history__id']}')"
	onmouseover = "window.status = 'hello';"
	description = "Restore the current record to the contents of this history snapshot"
	permission = edit_history
	icon="{$dataface_url}/images/undo_icon.gif"
	

[ajax_nav_tree_node]
	permission = view

[ajax_view_record_details]
	permission view


;;------------------------------------------------------------------------------
;; Grid actions
[load_grid]
	permission = view

[update_grid]
	permission = edit
	
;;------------------------------------------------------------------------------
;; Personal Tools
[my_profile]
	condition="(df_is_logged_in())"
	url="{$app->url('-action=my_profile')}"
	label="My Profile"
	category=personal_tools
	materialIcon="account_circle"
	
[change_password]
	condition="(df_is_logged_in()) and Dataface_AuthenticationTool::getInstance()->isPasswordLoginAllowed() and Dataface_AuthenticationTool::getInstance()->userHasPassword()"
	url="{$app->url('-action=change_password')}"
	label="Change Password"
	category=personal_tools
	materialIcon="security"
    
[create_password > change_password]
    condition="(df_is_logged_in()) and Dataface_AuthenticationTool::getInstance()->isPasswordLoginAllowed() and !Dataface_AuthenticationTool::getInstance()->userHasPassword()"
    label="Create Password"

[personal_tools_logout]
	condition="(df_is_logged_in())"
	url="{$app->url('-action=logout')}"
	label="Log out"
	category=personal_tools
	materialIcon="exit_to_app"
	order=999

;;------------------------------------------------------------------------------
;; Management actions
[install]
	permission=install

[manage]
	permission=manage
	category=personal_tools
	label="Control Panel"
	url="{$app->url('-action=manage')}"
	template=manage.html
	materialIcon="settings"
    

[manage_migrate]
	permission=manage_migrate
	category=management_actions
	url="{$app->url('-action=manage_migrate')}"
	label="Migrations"
	description="A tool to help migrate to newer versions of Dataface."
    rel=child

[sync_bindings]
	permission=manage_sync_bindings
	category=management_actions
	url="{$app->url('-action=sync_bindings')}"
	label="Synchronize Field Bindings"
	description="Updates the database triggers for the current field bindings, as declared in the fields.ini files."
    rel=child
	
	
[clear_views]
	permission=manage
	url="{$app->url('-action=clear_views')}"
	label="Clear __sql__ Views"
	description="Clears all of the cached views of the form dataface_view__xxx in the database.  This is necessary if you have added or removed columns from tables that also specify a custom __sql__ directive in the fields.ini file."
	
[clear_cache]
    permission=manage
    category=management_actions
    url="{$app->url('-action=clear_cache')}"
    label="Clear Cache"
    description="Clear all caches, such as opcache, templates, output cache, etc.."
	
[clear_templates_c > clear_cache]
    category=
	
	
[manage_output_cache]
	permission=manage
	category=management_actions
	url="{$app->url('-action=manage_output_cache')}"
	label="Output cache"
	description="Management options for the Dataface output cache."
        condition="$app->_conf['_output_cache'] and $app->_conf['_output_cache']['enabled']"
    rel=child
	
[manage_build_index]
	permission=manage_build_index
	category=management_actions
	url="{$app->url('-action=manage_build_index')}"
	label="Build Search Index"
	description="Build and maintain a search index to perform full site searches."
    rel=child


[copy_selected]
	url="javascript:copySelected('result_list')"
	label="Copy"
	description="Copy selected records"
	category=selected_result_actions
	permission=copy
	icon="{$dataface_url}/images/view.gif"
    rel=child

[update_selected]
	url="javascript:updateSelected('result_list')"
	label="Update"
	description="Update selected records"
	category=selected_result_actions
	permission=update_selected
	icon="{$dataface_url}/images/edit.gif"
    rel=child


[update_selected_related]
	url="javascript:updateSelected('relatedList')"
	label="Update"
	description="Update selected records"
	category=selected_related_result_actions
	permission=update related records
	condition="$record and $record->checkPermission('edit', array('relationship'=>$query['-relationship']))"
    rel=child
	

[remove_selected_related]
	url="javascript:removeSelectedRelated('relatedList')"
	label="Remove"
	description="Remove selected records from this relationship"
	category=selected_related_result_actions
	permission=remove related record
	condition="$record and $record->checkPermission('remove related record', array('relationship'=>$query['-relationship']))"
    rel=child

[xml_list]
	permission = xml_view

[login_prompt]
	template=Dataface_Login_Prompt.html
	

[view_event_details]
	category=event_actions
	condition="$record"
	label="View Event Details"
	permission=view
	url="{$record->getURL('-action=view')}"
	icon="{$dataface_url}/images/view.gif"
    rel=child
	
[edit_event_details]
	category=event_actions
	condition="$record"
	label="Edit Event Details"
	permission=edit
	url="{$record->getURL('-action=edit')}"
	icon="{$dataface_url}/images/edit.gif"
    rel=child

[RecordBrowser_data]
	permission=view
	
[entry_page]

[open_record_in_table]
	category=view_related_record_actions
	description="Open this record in the {$record->_table->getLabel()} table"
	materialIcon="open_in_new"
	label=Open
	permission=view
	condition="$record"
	url="{$record->getURL('-action=view')}"
	url_condition="$record"
    rel=child
	
[edit_related_record]
	category=view_related_record_actions
	materialIcon=edit
	label="Edit"
	label_condition="$record"
	url="#"
	class="edit-btn"
	condition="$record"
	permission="edit"
    rel=child
	
[cancel_edit_related_record]
	category=edit_related_record_actions
	label="Cancel"
	materialIcon=cancel
	url="#"
	class="cancel-btn"
	permission=edit
	
	
[show_hide_list_columns]
	class="show-hide-columns-action"
	category="result_list_actions"
	url="{$this->url('-action=show_hide_columns')}&--visibility-types=list"
	label="Show/Hide Columns"
	permission="show hide columns"
	condition="@$this->_conf['user_config_enabled']"
	icon="{$dataface_url}/images/insert_columns.png"
	description="Show or Hide columns from this list"
	
[show_hide_related_list_columns]
	class="show-hide-related-list-columns-action"
	category=related_list_actions
	url="{$this->url('-action=show_hide_columns')}&--hide-local-fields=1&--relationships={$relationship->getName()}&--visibility-types=list"
	url_condition="$relationship"
	label="Show/Hide Columns"
	permission="show hide columns"
	condition="@$this->_conf['user_config_enabled']"
	icon="{$dataface_url}/images/insert_columns.png"
	description="Show or Hide columns from this list"
	
[personal_tools]
	category=status_bar_right
	subcategory=personal_tools
	label="{$authTool->getLoggedInUsername()}"
	materialIcon="person"
	condition="(df_is_logged_in())"
	url_condition="(df_is_logged_in())"

[login_menu_item]
	category=status_bar_right
	label="Login"
	condition="($app->_conf['_auth'] and !df_is_logged_in())"
	materialIcon="security"
	url="?-action=login"
	
    
[list_filter]
    category=list_settings
    materialIcon=filter_list
    label=Filter
    description=Filter results
    permission=list
    url="javascript:void(0)"
    onclick="window.xataface.list.openFilterDialog()"
    
[list_sort]
    category=list_settings
    materialIcon=sort
    label=Sort
    description=Sort results
    url="javascript:void(0)"
    onclick="window.xataface.list.openSortDialog()"
    permission=list
    
[related_list_filter]
    category=related_list_settings
    materialIcon=filter_list
    label=Filter
    description=Filter results
    permission=list
    url="javascript:void(0)"
    onclick="window.xataface.relatedList.openFilterDialog()"
    permission=view
    related=1
    allow_override="relationships.ini"
    
    
[related_list_sort]
    category=related_list_settings
    materialIcon=sort
    label=Sort
    description=Sort results
    url="javascript:void(0)"
    onclick="window.xataface.relatedList.openSortDialog()"
    permission=view
    related=1
    allow_override="relationships.ini"

[related_list_delete]
    category=related_list_settings
    materialIcon=delete
    label=delete
    description=Select and Delete rows
    url="javascript:void(0)"
    onclick="window.xataface.relatedList.selectAndDeleteRowsInRelatedList()"
    permission=delete related record
    related=1
    allow_override="relationships.ini"
    order=99
    
    
[related_sort_dialog]
	permission = view
	related=1
	allow_override="relationships.ini"
    
[mobile_sort_dialog]
    permission=list
    
[mobile_filter_dialog]
    permission=list
    
[xf_infinite_scroll]
    permission=list
    
[mobile_app_back]
    ;condition="in_array($query['-action'], ['view'])"
    materialIcon=arrow_back_ios
    url="javascript:void(0)"
    label="Back"

    
[mobile_edit > edit]
    category=mobile_edit
    featured=1
    rel=child
    
[password_changed_home]
    category=password_changed_actions
    label=Home
    url="{$site_href}"
    materialIcon=home