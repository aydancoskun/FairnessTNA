AccrualViewController = BaseViewController.extend( {
	el: '#accrual_view_container',
	type_array: null,
	_required_files: ['APIAccrualBalance', 'APIAccrual', 'APIUserGroup', 'APIAccrualPolicyAccount', 'APIBranch', 'APIDepartment'],

	user_group_api: null,
	user_group_array: null,
	user_type_array: null,
	system_type_array: null,
	delete_type_array: null,
	date_api: null,

	edit_enabled: false,
	delete_enabled: false,

	is_trigger_add: false,

	sub_view_grid_data: null,

	hide_search_field: false,

//	  parent_filter: null,

	init: function( options ) {
		//this._super('initialize', options );
		this.edit_view_tpl = 'AccrualEditView.html';
		this.permission_id = 'accrual';
		this.viewId = 'Accrual';
		this.script_name = 'AccrualView';
		this.table_name_key = 'accrual';
		this.context_menu_name = $.i18n._( 'Accruals' );
		this.navigation_label = $.i18n._( 'Accrual' ) + ':';

		this.invisible_context_menu_dic[ContextMenuIconName.save_and_continue] = true; //Hide some context menus
		this.api = new (APIFactory.getAPIClass( 'APIAccrual' ))();
		this.date_api = new (APIFactory.getAPIClass( 'APIDate' ))();
		this.user_group_api = new (APIFactory.getAPIClass( 'APIUserGroup' ))();

		this.initPermission();
		this.render();

		if ( this.sub_view_mode ) {
			this.buildContextMenu( true );
		} else {
			this.buildContextMenu();
		}

		//call init data in parent view
		if ( !this.sub_view_mode ) {
			this.initData();
		}
		this.setSelectRibbonMenuIfNecessary( 'Accrual' );
		TTPromise.resolve( 'AccrualViewController', 'init' );
	},

	initPermission: function() {

		this._super( 'initPermission' );

		if ( PermissionManager.validate( this.permission_id, 'view' ) || PermissionManager.validate( this.permission_id, 'view_child' ) ) {
			this.hide_search_field = false;
		} else {
			this.hide_search_field = true;
		}

	},

	initOptions: function() {
		var $this = this;

		this.initDropDownOption( 'user_type', null, null, function( res ) {
			var result = res.getResult();
			$this.user_type_array = result;

		} );
		this.initDropDownOption( 'delete_type', null, null, function( res ) {
			var result = res.getResult();
			$this.delete_type_array = result;

		} );

		this.initDropDownOption( 'type', null, null, function( res ) {
			var result = res.getResult();
			if ( !$this.sub_view_mode && $this.basic_search_field_ui_dic['type_id'] ) {
				$this.basic_search_field_ui_dic['type_id'].setSourceData( Global.buildRecordArray( result ) );
				$this.system_type_array = result;
			}
		} );

		this.user_group_api.getUserGroup( '', false, false, {
			onResult: function( res ) {

				res = res.getResult();
				res = Global.buildTreeRecord( res );

				if ( !$this.sub_view_mode && $this.basic_search_field_ui_dic['group_id'] ) {
					$this.basic_search_field_ui_dic['group_id'].setSourceData( res );
				}

				$this.user_group_array = res;

			}
		} );

	},

	buildEditViewUI: function() {
		this._super( 'buildEditViewUI' );

		var $this = this;

		var tab_model = {
			'tab_accrual': { 'label': $.i18n._( 'Accrual' ) },
			'tab_audit': true,
		};
		this.setTabModel( tab_model );

		this.navigation.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIAccrual' )),
			id: this.script_name + '_navigation',
			allow_multiple_selection: false,
			layout_name: ALayoutIDs.ACCRUAL,
			navigation_mode: true,
			show_search_inputs: true
		} );

		this.setNavigation();

		//Tab 0 start

		var tab_accrual = this.edit_view_tab.find( '#tab_accrual' );

		var tab_accrual_column1 = tab_accrual.find( '.first-column' );

		this.edit_view_tabs[0] = [];

		this.edit_view_tabs[0].push( tab_accrual_column1 );

		var form_item_input;
		var widgetContainer;
		var label;

		// Employee

		if ( this.sub_view_mode ) {
			form_item_input = Global.loadWidgetByName( FormItemType.TEXT );
			form_item_input.TText( { field: 'full_name' } );
			this.addEditFieldToColumn( $.i18n._( 'Employee' ), form_item_input, tab_accrual_column1, '' );
		} else {
			form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );
			form_item_input.AComboBox( {
				api_class: (APIFactory.getAPIClass( 'APIUser' )),
				allow_multiple_selection: true,
				layout_name: ALayoutIDs.USER,
				show_search_inputs: true,
				set_empty: true,
				field: 'user_id'
			} );

			var default_args = {};
			default_args.permission_section = 'accrual';
			form_item_input.setDefaultArgs( default_args );
			this.addEditFieldToColumn( $.i18n._( 'Employee' ), form_item_input, tab_accrual_column1, '' );
		}

		// Accrual Policy Account

		if ( this.sub_view_mode ) {
			form_item_input = Global.loadWidgetByName( FormItemType.TEXT );
			form_item_input.TText( { field: 'accrual_policy_account' } );
			this.addEditFieldToColumn( $.i18n._( 'Accrual Account' ), form_item_input, tab_accrual_column1 );
		} else {
			form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );
			form_item_input.AComboBox( {
				api_class: (APIFactory.getAPIClass( 'APIAccrualPolicyAccount' )),
				allow_multiple_selection: false,
				layout_name: ALayoutIDs.ACCRUAL_POLICY_ACCOUNT,
				show_search_inputs: true,
				set_empty: true,
				field: 'accrual_policy_account_id'
			} );
			this.addEditFieldToColumn( $.i18n._( 'Accrual Account' ), form_item_input, tab_accrual_column1 );

		}

		//Type
		form_item_input = Global.loadWidgetByName( FormItemType.COMBO_BOX );

		form_item_input.TComboBox( { field: 'type_id' } );
		form_item_input.setSourceData( Global.addFirstItemToArray( $this.user_type_array ) );
		this.addEditFieldToColumn( $.i18n._( 'Type' ), form_item_input, tab_accrual_column1 );

		// Amount
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT_INPUT );
		form_item_input.TTextInput( { field: 'amount', need_parser_sec: true, mode: 'time_unit' } );
		this.addEditFieldToColumn( $.i18n._( 'Amount' ), form_item_input, tab_accrual_column1, '', null );

		// Date
		form_item_input = Global.loadWidgetByName( FormItemType.DATE_PICKER );

		form_item_input.TDatePicker( { field: 'time_stamp' } );
		this.addEditFieldToColumn( $.i18n._( 'Date' ), form_item_input, tab_accrual_column1, '', null );

		//Note
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT_AREA );
		form_item_input.TTextArea( {
			field: 'note'
		} );
		this.addEditFieldToColumn( $.i18n._( 'Note' ), form_item_input, tab_accrual_column1, '', null, null, true );

	},

	buildSearchFields: function() {
		this._super( 'buildSearchFields' );

		var default_args = {};
		default_args.permission_section = 'accrual';

		this.search_fields = [

			new SearchField( {
				label: $.i18n._( 'Employee' ),
				field: 'user_id',
				in_column: 1,
				default_args: default_args,
				layout_name: ALayoutIDs.USER,
				api_class: (APIFactory.getAPIClass( 'APIUser' )),
				multiple: true,
				basic_search: !this.hide_search_field,
				adv_search: false,
				form_item_type: FormItemType.AWESOME_BOX
			} ),

			new SearchField( {
				label: $.i18n._( 'Accrual Account' ),
				field: 'accrual_policy_account_id',
				in_column: 1,
				layout_name: ALayoutIDs.ACCRUAL_POLICY_ACCOUNT,
				api_class: (APIFactory.getAPIClass( 'APIAccrualPolicyAccount' )),
				multiple: true,
				basic_search: true,
				adv_search: false,
				form_item_type: FormItemType.AWESOME_BOX
			} ),

			new SearchField( {
				label: $.i18n._( 'Type' ),
				in_column: 1,
				field: 'type_id',
				multiple: true,
				basic_search: true,
				adv_search: false,
				layout_name: ALayoutIDs.OPTION_COLUMN,
				form_item_type: FormItemType.AWESOME_BOX
			} ),

			new SearchField( {
				label: $.i18n._( 'Group' ),
				in_column: 1,
				multiple: true,
				field: 'group_id',
				layout_name: ALayoutIDs.TREE_COLUMN,
				tree_mode: true,
				basic_search: !this.hide_search_field,
				adv_search: false,
				form_item_type: FormItemType.AWESOME_BOX
			} ),

			new SearchField( {
				label: $.i18n._( 'Default Branch' ),
				in_column: 2,
				field: 'default_branch_id',
				layout_name: ALayoutIDs.BRANCH,
				api_class: (APIFactory.getAPIClass( 'APIBranch' )),
				multiple: true,
				basic_search: !this.hide_search_field,
				adv_search: false,
				form_item_type: FormItemType.AWESOME_BOX
			} ),

			new SearchField( {
				label: $.i18n._( 'Default Department' ),
				in_column: 2,
				field: 'default_department_id',
				layout_name: ALayoutIDs.DEPARTMENT,
				api_class: (APIFactory.getAPIClass( 'APIDepartment' )),
				multiple: true,
				basic_search: !this.hide_search_field,
				adv_search: false,
				form_item_type: FormItemType.AWESOME_BOX
			} ),

			new SearchField( {
				label: $.i18n._( 'Created By' ),
				in_column: 2,
				field: 'created_by',
				layout_name: ALayoutIDs.USER,
				api_class: (APIFactory.getAPIClass( 'APIUser' )),
				multiple: true,
				basic_search: !this.hide_search_field,
				adv_search: false,
				form_item_type: FormItemType.AWESOME_BOX
			} ),

			new SearchField( {
				label: $.i18n._( 'Updated By' ),
				in_column: 2,
				field: 'updated_by',
				layout_name: ALayoutIDs.USER,
				api_class: (APIFactory.getAPIClass( 'APIUser' )),
				multiple: true,
				basic_search: !this.hide_search_field,
				adv_search: false,
				form_item_type: FormItemType.AWESOME_BOX
			} )
		];
	},

	setEditViewData: function() {
		//use the user_type_array in edit mode and new mode, use the system_type_array in view mode
		//this prevents users from choosing type_ids that are for system use only but can see the system type_ids when viewing
		if ( this.is_viewing ) {
			this.edit_view_ui_dic.type_id.setSourceData( this.system_type_array );
		} else {
			this.edit_view_ui_dic.type_id.setSourceData( this.user_type_array );
		}

		var $this = this;
		this._super( 'setEditViewData' ); //Set Navigation

		if ( !this.sub_view_mode ) {
			var widget = $this.edit_view_ui_dic['user_id'];
			if ( ( !this.current_edit_record || !this.current_edit_record.id ) && !this.is_mass_editing ) {

				widget.setAllowMultipleSelection( true );

			} else {
				widget.setAllowMultipleSelection( false );
			}
		}

	},

	uniformVariable: function( records ) {

		var record_array = [];
		if ( $.type( records.user_id ) === 'array' ) {

			if ( records.user_id.length === 0 ) {
				records.user_id = false;
				return records;
			}

			for ( var key in records.user_id ) {
				var new_record = Global.clone( records );
				new_record.user_id = records.user_id[key];
				record_array.push( new_record );
			}
		}

		if ( record_array.length > 0 ) {
			records = record_array;
		}

		return records;
	},

	setCurrentEditRecordData: function() {
		//Set current edit record data to all widgets
		for ( var key in this.current_edit_record ) {
			var widget = this.edit_view_ui_dic[key];
			if ( Global.isSet( widget ) ) {
				switch ( key ) {
					case 'full_name':
						if ( this.current_edit_record['first_name'] ) {
							widget.setValue( this.current_edit_record['first_name'] + ' ' + this.current_edit_record['last_name'] );
						}
						break;
					default:
						widget.setValue( this.current_edit_record[key] );
						break;
				}

			}
		}
		this.collectUIDataToCurrentEditRecord();
		this.setEditViewDataDone();

	},

	getFilterColumnsFromDisplayColumns: function() {
		var column_filter = {};
		column_filter.type_id = true;
		if ( this.sub_view_mode ) {
			column_filter.accrual_policy_account = true;
			column_filter.accrual_policy_account_id = true;
			column_filter.user_id = true;
		}
		return this._getFilterColumnsFromDisplayColumns( column_filter, true );
	},

	onGridSelectAll: function() {
		this.edit_enabled = this.editEnabled();
		this.delete_enabled = this.deleteEnabled();
		this.setDefaultMenu();
	},

	deleteEnabled: function() {
		var grid_selected_id_array = this.getGridSelectIdArray();
		if ( grid_selected_id_array.length > 0 ) {
			for ( var i = grid_selected_id_array.length - 1; i >= 0; i-- ) {
				var selected_item = this.getRecordFromGridById( grid_selected_id_array[i] );
				if ( Global.isSet( this.delete_type_array[selected_item.type_id] ) ) {
					return true;
				}
			}
		}
		return false;
	},

	editEnabled: function() {
		var grid_selected_id_array = this.getGridSelectIdArray();
		if ( grid_selected_id_array.length > 0 ) {
			for ( var i = grid_selected_id_array.length - 1; i >= 0; i-- ) {
				var selected_item = this.getRecordFromGridById( grid_selected_id_array[i] );
				if ( Global.isSet( this.user_type_array[selected_item.type_id] ) ) {
					return true;
				}
			}
		}
		return false;
	},

	onGridSelectRow: function() {

		var selected_item = null;
		var grid_selected_id_array = this.getGridSelectIdArray();
		var grid_selected_length = grid_selected_id_array.length;

		if ( grid_selected_length > 0 ) {
			selected_item = this.getRecordFromGridById( grid_selected_id_array[0] );

			this.edit_enabled = this.editEnabled();
			this.delete_enabled = this.deleteEnabled();
		}

		this.setDefaultMenu();
	},

	setDefaultMenuEditIcon: function( context_btn, grid_selected_length, pId ) {
		if ( !this.editPermissionValidate( pId ) || this.edit_only_mode ) {
			context_btn.addClass( 'invisible-image' );
		}

		if ( grid_selected_length === 1 && this.editOwnerOrChildPermissionValidate( pId ) ) {
			if ( this.edit_enabled ) {
				context_btn.removeClass( 'disable-image' );
			} else {
				context_btn.addClass( 'disable-image' );
			}
		} else {
			context_btn.addClass( 'disable-image' );
		}

	},

	setDefaultMenuMassEditIcon: function( context_btn, grid_selected_length, pId ) {
		if ( !this.editPermissionValidate( pId ) || this.edit_only_mode ) {
			context_btn.addClass( 'invisible-image' );
		}

		if ( grid_selected_length > 1 ) {
			if ( this.edit_enabled ) {
				context_btn.removeClass( 'disable-image' );
			} else {
				context_btn.addClass( 'disable-image' );
			}
		} else {
			context_btn.addClass( 'disable-image' );
		}
	},

	setDefaultMenuDeleteIcon: function( context_btn, grid_selected_length, pId ) {
		if ( !this.deletePermissionValidate( pId ) || this.edit_only_mode ) {
			context_btn.addClass( 'invisible-image' );
		}

		if ( grid_selected_length >= 1 && this.deleteOwnerOrChildPermissionValidate( pId ) ) {
			if ( this.delete_enabled ) {
				context_btn.removeClass( 'disable-image' );
			} else {
				context_btn.addClass( 'disable-image' );
			}
		} else {
			context_btn.addClass( 'disable-image' );
		}

	},

	setEditMenuEditIcon: function( context_btn, pId ) {
		if ( !this.editPermissionValidate( pId ) || this.edit_only_mode ) {

			context_btn.addClass( 'invisible-image' );
		}

		if ( this.edit_enabled && this.editOwnerOrChildPermissionValidate( pId ) ) {
			context_btn.removeClass( 'disable-image' );
			if ( !this.is_viewing ) {
				context_btn.addClass( 'disable-image' );
			}
		} else {
			context_btn.addClass( 'disable-image' );
		}
	},

	setEditMenuDeleteIcon: function( context_btn, pId ) {
		if ( !this.deletePermissionValidate( pId ) || this.edit_only_mode ) {
			context_btn.addClass( 'invisible-image' );
		}

		if ( this.delete_enabled && this.deleteOwnerOrChildPermissionValidate( pId ) ) {
			context_btn.removeClass( 'disable-image' );
		} else {
			context_btn.addClass( 'disable-image' );
		}
	},

	setEditMenuDeleteAndNextIcon: function( context_btn, pId ) {
		if ( !this.deletePermissionValidate( pId ) || this.edit_only_mode ) {
			context_btn.addClass( 'invisible-image' );
		}

		if ( this.delete_enabled && this.deleteOwnerOrChildPermissionValidate( pId ) ) {
			context_btn.removeClass( 'disable-image' );
		} else {
			context_btn.addClass( 'disable-image' );
		}
	},

	buildContextMenuModels: function() {
		var menu = this._super( 'buildContextMenuModels' )[0];


		//Context Menu
		var menu = new RibbonMenu( {
			label: this.context_menu_name,
			id: this.viewId + 'ContextMenu',
			sub_menu_groups: []
		} );

		//menu group
		var editor_group = new RibbonSubMenuGroup( {
			label: $.i18n._( 'Editor' ),
			id: this.viewId + 'Editor',
			ribbon_menu: menu,
			sub_menus: []
		} );

		//menu group
		var navigation_group = new RibbonSubMenuGroup( {
			label: $.i18n._( 'Navigation' ),
			id: this.viewId + 'navigation',
			ribbon_menu: menu,
			sub_menus: []
		} );

		var timesheet = new RibbonSubMenu( {
			label: $.i18n._( 'TimeSheet' ),
			id: ContextMenuIconName.timesheet,
			group: navigation_group,
			icon: Icons.timesheet,
			permission_result: true,
			permission: null
		} );

		var add = new RibbonSubMenu( {
			label: $.i18n._( 'New' ),
			id: ContextMenuIconName.add,
			group: editor_group,
			icon: Icons.new_add,
			permission_result: true,
			permission: null
		} );

		var view = new RibbonSubMenu( {
			label: $.i18n._( 'View' ),
			id: ContextMenuIconName.view,
			group: editor_group,
			icon: Icons.view,
			permission_result: true,
			permission: null
		} );

		var edit = new RibbonSubMenu( {
			label: $.i18n._( 'Edit' ),
			id: ContextMenuIconName.edit,
			group: editor_group,
			icon: Icons.edit,
			permission_result: true,
			permission: null
		} );

		var mass_edit = new RibbonSubMenu( {
			label: $.i18n._( 'Mass<br>Edit' ),
			id: ContextMenuIconName.mass_edit,
			group: editor_group,
			icon: Icons.mass_edit,
			permission_result: true,
			permission: null
		} );

		var del = new RibbonSubMenu( {
			label: $.i18n._( 'Delete' ),
			id: ContextMenuIconName.delete_icon,
			group: editor_group,
			icon: Icons.delete_icon,
			permission_result: true,
			permission: null
		} );

		var delAndNext = new RibbonSubMenu( {
			label: $.i18n._( 'Delete<br>& Next' ),
			id: ContextMenuIconName.delete_and_next,
			group: editor_group,
			icon: Icons.delete_and_next,
			permission_result: true,
			permission: null
		} );

		var copy = new RibbonSubMenu( {
			label: $.i18n._( 'Copy' ),
			id: ContextMenuIconName.copy,
			group: editor_group,
			icon: Icons.copy_as_new,
			permission_result: true,
			permission: null
		} );

		var copy_as_new = new RibbonSubMenu( {
			label: $.i18n._( 'Copy<br>as New' ),
			id: ContextMenuIconName.copy_as_new,
			group: editor_group,
			icon: Icons.copy,
			permission_result: true,
			permission: null
		} );

		var save = new RibbonSubMenu( {
			label: $.i18n._( 'Save' ),
			id: ContextMenuIconName.save,
			group: editor_group,
			icon: Icons.save,
			permission_result: true,
			permission: null
		} );

		var save_and_new = new RibbonSubMenu( {
			label: $.i18n._( 'Save<br>& New' ),
			id: ContextMenuIconName.save_and_new,
			group: editor_group,
			icon: Icons.save_and_new,
			permission_result: true,
			permission: null
		} );

		var save_and_copy = new RibbonSubMenu( {
			label: $.i18n._( 'Save<br>& Copy' ),
			id: ContextMenuIconName.save_and_copy,
			group: editor_group,
			icon: Icons.save_and_copy,
			permission_result: true,
			permission: null
		} );

		var save_and_next = new RibbonSubMenu( {
			label: $.i18n._( 'Save<br>& Next' ),
			id: ContextMenuIconName.save_and_next,
			group: editor_group,
			icon: Icons.save_and_next,
			permission_result: true,
			permission: null
		} );

		var cancel = new RibbonSubMenu( {
			label: $.i18n._( 'Cancel' ),
			id: ContextMenuIconName.cancel,
			group: editor_group,
			icon: Icons.cancel,
			permission_result: true,
			permission: null
		} );

		if ( !this.sub_view_mode ) {
			var other_group = new RibbonSubMenuGroup( {
				label: $.i18n._( 'Other' ),
				id: this.viewId + 'other',
				ribbon_menu: menu,
				sub_menus: []
			} );

			var ttimport = new RibbonSubMenu( {
				label: $.i18n._( 'Import' ),
				id: ContextMenuIconName.import_icon,
				group: other_group,
				icon: Icons.import_icon,
				permission_result: true,
				permission: null,
				sort_order: 8000
			} );
			var export_csv = new RibbonSubMenu( {
				label: $.i18n._( 'Export' ),
				id: ContextMenuIconName.export_excel,
				group: other_group,
				icon: Icons.export_excel,
				permission_result: true,
				permission: null,
				sort_order: 9000
			} );
		}

		return [menu];

	},

	getGridSetup: function(){
		var $this = this;

		var grid_setup = {
			container_selector: this.sub_view_mode ? '.edit-view-tab' : '.view',
			sub_grid_mode: this.sub_view_mode,
			onSelectRow: function() {
				$this.onGridSelectRow();
			},
			onCellSelect: function() {
				$this.onGridSelectRow();
			},
			onSelectAll: function() {
				$this.onGridSelectAll();
			},
			ondblClickRow: function( e ) {
				$this.onGridDblClickRow( e );
			},
			onRightClickRow: function( rowId ) {
				var id_array = $this.getGridSelectIdArray();
				if ( id_array.indexOf( rowId ) < 0 ) {
					$this.grid.grid.resetSelection();
					$this.grid.grid.setSelection( rowId );
					$this.onGridSelectRow();
				}
			},
		};

		return grid_setup;
	},

	onCustomContextClick: function( id ) {
		switch ( id ) {
			case ContextMenuIconName.timesheet:
				this.onNavigationClick();
				break;
			case ContextMenuIconName.import_icon:
				this.onImportClick();
				break;
		}
	},

	onImportClick: function() {
		var $this = this;
		IndexViewController.openWizard( 'ImportCSVWizard', 'accrual', function() {
			$this.search();
		} );
	},

	setDefaultMenu: function( doNotSetFocus ) {

		//Error: Uncaught TypeError: Cannot read property 'length' of undefined in /interface/html5/#!m=Employee&a=edit&id=42411&tab=Wage line 282
		if ( !this.context_menu_array ) {
			return;
		}

		if ( !Global.isSet( doNotSetFocus ) || !doNotSetFocus ) {
			this.selectContextMenu();
		}

		this.setTotalDisplaySpan();

		var len = this.context_menu_array.length;

		var grid_selected_id_array = this.getGridSelectIdArray();

		var grid_selected_length = grid_selected_id_array.length;

		for ( var i = 0; i < len; i++ ) {
			var context_btn = $( this.context_menu_array[i] );
			var id = $( context_btn.find( '.ribbon-sub-menu-icon' ) ).attr( 'id' );

			context_btn.removeClass( 'invisible-image' );
			context_btn.removeClass( 'disable-image' );

			switch ( id ) {
				case ContextMenuIconName.add:
					this.setDefaultMenuAddIcon( context_btn, grid_selected_length );
					break;
				case ContextMenuIconName.edit:
					this.setDefaultMenuEditIcon( context_btn, grid_selected_length );
					break;
				case ContextMenuIconName.view:
					this.setDefaultMenuViewIcon( context_btn, grid_selected_length );
					break;
				case ContextMenuIconName.mass_edit:
					this.setDefaultMenuMassEditIcon( context_btn, grid_selected_length );
					break;
				case ContextMenuIconName.copy:
					this.setDefaultMenuCopyIcon( context_btn, grid_selected_length );
					break;
				case ContextMenuIconName.delete_icon:
					this.setDefaultMenuDeleteIcon( context_btn, grid_selected_length );
					break;
				case ContextMenuIconName.delete_and_next:
					this.setDefaultMenuDeleteAndNextIcon( context_btn, grid_selected_length );
					break;
				case ContextMenuIconName.save:
					this.setDefaultMenuSaveIcon( context_btn, grid_selected_length );
					break;
				case ContextMenuIconName.save_and_next:
					this.setDefaultMenuSaveAndNextIcon( context_btn, grid_selected_length );
					break;
				case ContextMenuIconName.save_and_new:
					this.setDefaultMenuSaveAndAddIcon( context_btn, grid_selected_length );
					break;
				case ContextMenuIconName.save_and_copy:
					this.setDefaultMenuSaveAndCopyIcon( context_btn, grid_selected_length );
					break;
				case ContextMenuIconName.copy_as_new:
					this.setDefaultMenuCopyAsNewIcon( context_btn, grid_selected_length );
					break;
				case ContextMenuIconName.cancel:
					this.setDefaultMenuCancelIcon( context_btn, grid_selected_length );
					break;
				case ContextMenuIconName.timesheet:
					this.setDefaultMenuViewIcon( context_btn, grid_selected_length, 'punch' );
					break;
				case ContextMenuIconName.import_icon:
					this.setDefaultMenuImportIcon( context_btn, grid_selected_length );
					break;
				case ContextMenuIconName.export_excel:
					this.setDefaultMenuExportIcon( context_btn, grid_selected_length );
					break;

			}

		}

		this.setContextMenuGroupVisibility();

	},

	setEditMenu: function() {
		this.selectContextMenu();
		var len = this.context_menu_array.length;
		for ( var i = 0; i < len; i++ ) {
			var context_btn = $( this.context_menu_array[i] );
			var id = $( context_btn.find( '.ribbon-sub-menu-icon' ) ).attr( 'id' );
			context_btn.removeClass( 'disable-image' );

			if ( this.is_mass_editing ) {
				switch ( id ) {
					case ContextMenuIconName.save:
						this.setEditMenuSaveIcon( context_btn );
						break;
					case ContextMenuIconName.cancel:
						break;
					default:
						context_btn.addClass( 'disable-image' );
						break;
				}

				continue;
			}

			switch ( id ) {
				case ContextMenuIconName.add:
					this.setEditMenuAddIcon( context_btn );
					break;
				case ContextMenuIconName.edit:
					this.setEditMenuEditIcon( context_btn );
					break;
				case ContextMenuIconName.view:
					this.setEditMenuViewIcon( context_btn );
					break;
				case ContextMenuIconName.mass_edit:
					this.setEditMenuMassEditIcon( context_btn );
					break;
				case ContextMenuIconName.copy:
					this.setEditMenuCopyIcon( context_btn );
					break;
				case ContextMenuIconName.delete_icon:
					this.setEditMenuDeleteIcon( context_btn );
					break;
				case ContextMenuIconName.delete_and_next:
					this.setEditMenuDeleteAndNextIcon( context_btn );
					break;
				case ContextMenuIconName.save:
					this.setEditMenuSaveIcon( context_btn );
					break;
				case ContextMenuIconName.save_and_new:
					this.setEditMenuSaveAndAddIcon( context_btn );
					break;
				case ContextMenuIconName.save_and_next:
					this.setEditMenuSaveAndNextIcon( context_btn );
					break;
				case ContextMenuIconName.save_and_copy:
					this.setEditMenuSaveAndCopyIcon( context_btn );
					break;
				case ContextMenuIconName.copy_as_new:
					this.setEditMenuCopyAndAddIcon( context_btn );
					break;
				case ContextMenuIconName.cancel:
					break;
				case ContextMenuIconName.timesheet:
					// Prevent user clicking timesheet from new accrual page by disabling the icon
					this.setDefaultMenuViewIcon( context_btn, 'punch' );
					break;
				case ContextMenuIconName.export_excel:
					this.setDefaultMenuExportIcon( context_btn );
					break;
			}

		}

		this.setContextMenuGroupVisibility();

	},

	onNavigationClick: function() {
		var $this = this;
		var filter = { filter_data: {} };
		var label = this.sub_view_mode ? $.i18n._( 'Accrual Balances' ) : $.i18n._( 'Accruals' );

		if ( Global.isSet( this.current_edit_record ) ) {

			filter.user_id = this.current_edit_record.user_id;
			filter.base_date = this.current_edit_record.time_stamp;

			Global.addViewTab( this.viewId, label, window.location.href );
			IndexViewController.goToView( 'TimeSheet', filter );

		} else {
			var accrual_filter = {};
			var grid_selected_id_array = this.getGridSelectIdArray();
			var grid_selected_length = grid_selected_id_array.length;

			if ( grid_selected_length > 0 ) {
				var selectedId = grid_selected_id_array[0];

				accrual_filter.filter_data = {};
				accrual_filter.filter_data.id = [selectedId];

				this.api['get' + this.api.key_name]( accrual_filter, {
					onResult: function( result ) {

						var result_data = result.getResult();

						if ( !result_data ) {
							result_data = [];
						}

						result_data = result_data[0];

						filter.user_id = result_data.user_id;
						filter.base_date = result_data.time_stamp;

						Global.addViewTab( $this.viewId, label, window.location.href );
						IndexViewController.goToView( 'TimeSheet', filter );

					}
				} );
			}

		}

	},

	getSubViewFilter: function( filter ) {
		if ( this.parent_edit_record && this.parent_edit_record.user_id && this.parent_edit_record.accrual_policy_account_id ) {
			filter.user_id = this.parent_edit_record.user_id;
			filter.accrual_policy_account_id = this.parent_edit_record.accrual_policy_account_id;
		}
		return filter;
	},

	onAddResult: function( result ) {
		var $this = this;
		var result_data = result.getResult();

		if ( !result_data ) {
			result_data = [];
		}

		result_data.company = LocalCacheData.current_company.name;

		if ( $this.sub_view_mode ) {
			result_data['user_id'] = $this.parent_edit_record['user_id'];
			result_data['first_name'] = $this.parent_edit_record['first_name'];
			result_data['last_name'] = $this.parent_edit_record['last_name'];
			result_data['accrual_policy_account_id'] = $this.parent_edit_record['accrual_policy_account_id'];
			result_data['accrual_policy_account'] = $this.parent_edit_record['accrual_policy_account'];
		}

		$this.current_edit_record = result_data;

		$this.initEditView();
	},

	searchDone: function() {
		var $this = this;

		//When Attendance -> Accrual Balance, New icon is clicked, open the Balance view first, then trigger the New icon to create a new accrual entry from there.
		if ( Global.isSet( $this.is_trigger_add ) && $this.is_trigger_add ) {
			$this.onAddClick();
			$this.is_trigger_add = false;
		}

		if ( this.sub_view_mode ) {
			TTPromise.resolve( 'initSubAccrualView', 'init' );

			var result_data = this.grid.getGridParam( 'data' );
			if ( !Global.isArray( result_data ) || result_data.length < 1 ) {
				this.onCancelClick();
				if ( this.parent_view_controller ) {
					this.parent_view_controller.search();
				}
			}
		}

		this._super( 'searchDone' );
	},
} );

AccrualViewController.loadView = function() {

	Global.loadViewSource( 'Accrual', 'AccrualView.html', function( result ) {

		TTPromise.wait( 'BaseViewController', 'initialize', function() {

			var args = {};
			var template = _.template( result );

			Global.contentContainer().html( template( args ) );
		} );
	} );

};

AccrualViewController.loadSubView = function( container, beforeViewLoadedFun, afterViewLoadedFun ) {
	Global.loadViewSource( 'Accrual', 'SubAccrualView.html', function( result ) {

		var args = {};
		var template = _.template( result );

		if ( Global.isSet( beforeViewLoadedFun ) ) {
			beforeViewLoadedFun();
		}

		if ( Global.isSet( container ) ) {
			container.html( template( args ) );
			if ( Global.isSet( afterViewLoadedFun ) ) {
				TTPromise.add( 'AccrualViewController', 'init' );
				TTPromise.wait( 'AccrualViewController', 'init', function() {
					afterViewLoadedFun( sub_accrual_view_controller );
				} );
			}
		}
	} );
};