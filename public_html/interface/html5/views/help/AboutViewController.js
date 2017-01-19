AboutViewController = BaseViewController.extend( {

	date_api: null,

	employeeActive: [],

	initialize: function( options ) {

		this._super( 'initialize', options );
		this.viewId = 'About';
		this.script_name = 'AboutView';
		this.context_menu_name = $.i18n._( 'About' );
		this.api = new (APIFactory.getAPIClass( 'APIAbout' ))();
		this.date_api = new (APIFactory.getAPIClass( 'APIDate' ))();

		this.render();

		this.initData();

	},

	onTabShow: function( e, ui ) {
		var key = this.edit_view_tab_selected_index;
		this.editFieldResize( key );

		if ( !this.current_edit_record ) {
			return;
		}

		this.buildContextMenu( true );
		this.setEditMenu();

	},

	buildContextMenuModels: function() {

		//Context Menu
		var menu = new RibbonMenu( {
			label: this.context_menu_name,
			id: this.viewId + 'ContextMenu',
			sub_menu_groups: []
		} );

		//menu group
		var editor_group = new RibbonSubMenuGroup( {
			label: $.i18n._( 'Editor' ),
			id: this.script_name + 'Editor',
			ribbon_menu: menu,
			sub_menus: []
		} );

		var check = new RibbonSubMenu( {
			label: $.i18n._( 'Check For<br>Updates' ),
			id: ContextMenuIconName.check_updates,
			group: editor_group,
			icon: Icons.check_updates,
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

		return [menu];

	},

	onContextMenuClick: function( context_btn, menu_name ) {
		var id;

		var $this = this;
		if ( Global.isSet( menu_name ) ) {
			id = menu_name;
		} else {
			context_btn = $( context_btn );

			id = $( context_btn.find( '.ribbon-sub-menu-icon' ) ).attr( 'id' );

			if ( context_btn.hasClass( 'disable-image' ) ) {
				return;
			}
		}

		switch ( id ) {

			case ContextMenuIconName.check_updates:
				ProgressBar.showOverlay();
				this.onCheckClick();
				break;
			case ContextMenuIconName.cancel:
				this.onCancelClick();
				break;

		}

	},

	getAboutData: function( callBack ) {
		var $this = this;
		$this.api['get' + $this.api.key_name]( {onResult: function( result ) {
			var result_data = result.getResult();
			if ( Global.isSet( result_data ) ) {
				callBack( result_data );
			}

		}} );
	},

	openEditView: function() {
		var $this = this;

		if ( $this.edit_only_mode ) {

			this.buildContextMenu();
			if ( !$this.edit_view ) {
				$this.initEditViewUI( 'About', 'AboutEditView.html' );
			}

			$this.getAboutData( function( result ) {
				// Waiting for the (APIFactory.getAPIClass( 'API' )) returns data to set the current edit record.
				$this.current_edit_record = result;

				$this.initEditView();

			} );

		}

	},

	setUIWidgetFieldsToCurrentEditRecord: function() {

	},

	setCurrentEditRecordData: function() {
		//Set current edit record data to all widgets
		for ( var i in this.edit_view_form_item_dic ) {
			this.detachElement( i );
		}

		for ( var key in this.current_edit_record ) {
			if ( !this.current_edit_record.hasOwnProperty( key ) ) {
				continue;
			}

			var widget = this.edit_view_ui_dic[key];

			switch ( key ) {
				case 'cron': //popular case
					if ( Global.isSet( widget ) ) {
						if ( this.current_edit_record[key]['last_run_date'] !== '' ) {
							widget.setValue( this.current_edit_record[key]['last_run_date'] );
						} else {
							widget.setValue( $.i18n._( 'Never' ) );
						}
					}
					break;
				case 'user_counts':
					if ( this.current_edit_record[key].length > 0 ) {
						this.attachElement( 'user_active_inactive' );
					}
					break;
				case 'schema_version_group_A':
				case 'schema_version_group_B':
				case 'schema_version_group_C':
				case 'schema_version_group_D':
					if ( Global.isSet( widget ) && this.current_edit_record[key] ) {
						widget.setValue( this.current_edit_record[key] );
					}
					break;
				default:
					if ( Global.isSet( widget ) ) {
						widget.setValue( this.current_edit_record[key] );
					}
					break;
			}

		}

		this.collectUIDataToCurrentEditRecord();
		this.setEditViewDataDone();

	},

	setEditViewDataDone: function() {
		this._super( 'setEditViewDataDone' );
		this.setActiveEmployees();
	},

	setActiveEmployees: function() {

		if ( this.employeeActive.length > 0 ) {
			for ( var i in this.employeeActive ) {
				var field = this.employeeActive[i].getField();
				if ( Global.isSet( this.edit_view_form_item_dic[field] ) ) {
					this.edit_view_form_item_dic[field].remove();
				}
			}

			this.employeeActive = [];

		}

		if ( Global.isSet( this.current_edit_record['user_counts'] ) && this.current_edit_record['user_counts'].length > 0 ) {
			var tab_about = this.edit_view_tab.find( '#tab_about' );
			var tab_about_column1 = tab_about.find( '.first-column' );

			for ( var key in this.current_edit_record['user_counts'] ) {

				var item = this.current_edit_record['user_counts'][key];

				var form_item_input = Global.loadWidgetByName( FormItemType.TEXT );
				form_item_input.TText( {field: 'active_' + key  } );
				form_item_input.setValue( item['max_active_users'] + ' / ' + item['max_inactive_users'] );

				this.addEditFieldToColumn( $.i18n._( item['label'] ), form_item_input, tab_about_column1, '', null, true );

				this.employeeActive.push( form_item_input );

				this.edit_view_ui_dic['active_' + key ].css( 'opacity', 1 );
			}

			this.editFieldResize( 0 );
		}
	},

	buildEditViewUI: function() {
		var $this = this;
		this._super( 'buildEditViewUI' );

		this.setTabLabels( {
			'tab_about': $.i18n._( 'About' )
		} );

		//Tab 0 start

		var tab_about = this.edit_view_tab.find( '#tab_about' );

		var tab_about_column1 = tab_about.find( '.first-column' );

		this.edit_view_tabs[0] = [];

		this.edit_view_tabs[0].push( tab_about_column1 );

		var form_item_input = $( "<div class='tblDataWarning'></div>" );
		this.addEditFieldToColumn( null, form_item_input, tab_about_column1, '', null, true, false, 'notice' );

		// separate box
		form_item_input = Global.loadWidgetByName( FormItemType.SEPARATED_BOX );
		form_item_input.SeparatedBox( {label: $.i18n._( 'System Information' )} );
		this.addEditFieldToColumn( null, form_item_input, tab_about_column1 );

		// Version
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT );
		form_item_input.TText( {field: 'system_version' } );
		this.addEditFieldToColumn( $.i18n._( 'Version' ), form_item_input, tab_about_column1 );

		// Tax Engine Version
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT );
		form_item_input.TText( {field: 'tax_engine_version' } );
		this.addEditFieldToColumn( $.i18n._( 'Tax Engine Version' ), form_item_input, tab_about_column1 );

		// Tax Data Version
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT );
		form_item_input.TText( {field: 'tax_data_version' } );
		this.addEditFieldToColumn( $.i18n._( 'Tax Data Version' ), form_item_input, tab_about_column1 );

		// Maintenance Jobs Last Ran
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT );
		form_item_input.TText( {field: 'cron' } );
		this.addEditFieldToColumn( $.i18n._( 'Maintenance Jobs Last Ran' ), form_item_input, tab_about_column1 );

		// separate box
		form_item_input = Global.loadWidgetByName( FormItemType.SEPARATED_BOX );
		form_item_input.SeparatedBox( {label: $.i18n._( 'License Information' )} );
		this.addEditFieldToColumn( null, form_item_input, tab_about_column1, '', null, true, false, 'license_info' );

		// Upload License
		form_item_input = Global.loadWidgetByName( FormItemType.FILE_BROWSER );

		this.file_browser = form_item_input.TImageBrowser( {
			field: 'license_browser',
			name: 'filedata',
			accept_filter: '*',
			changeHandler: function( a ) {
				$this.uploadLicense( this );
			}
		} );

		this.addEditFieldToColumn( $.i18n._( 'Upload License' ), form_item_input, tab_about_column1, '', null, true );

		// Product
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT );
		form_item_input.TText( {field: 'product_name' } );
		this.addEditFieldToColumn( $.i18n._( 'Product' ), form_item_input, tab_about_column1, '', null, true );

		// Company
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT );
		form_item_input.TText( {field: 'organization_name' } );
		this.addEditFieldToColumn( $.i18n._( 'Company' ), form_item_input, tab_about_column1, '', null, true );

		// Version
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT );
		form_item_input.TText( {field: '_version' } );
		this.addEditFieldToColumn( $.i18n._( 'Version' ), form_item_input, tab_about_column1, '', null, true );

		// Active Employee Licenses
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT );
		form_item_input.TText( {field: 'active_employee_licenses' } );
		this.addEditFieldToColumn( $.i18n._( 'Active Employee Licenses' ), form_item_input, tab_about_column1, '', null, true );

		// Issue Date
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT );
		form_item_input.TText( {field: 'issue_date' } );
		this.addEditFieldToColumn( $.i18n._( 'Issue Date' ), form_item_input, tab_about_column1, '', null, true );

		// Expire Date
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT );
		form_item_input.TText( {field: 'expire_date_display' } );
		this.addEditFieldToColumn( $.i18n._( 'Expire Date' ), form_item_input, tab_about_column1, '', null, true );

		// Schema Version
		form_item_input = Global.loadWidgetByName( FormItemType.SEPARATED_BOX );
		form_item_input.SeparatedBox( {label: $.i18n._( 'Schema Version' )} );
		this.addEditFieldToColumn( null, form_item_input, tab_about_column1 );

		// Group A
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT );
		form_item_input.TText( {field: 'schema_version_group_A' } );
		this.addEditFieldToColumn( $.i18n._( 'Group A' ), form_item_input, tab_about_column1 );

		// Group B
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT );
		form_item_input.TText( {field: 'schema_version_group_B' } );
		this.addEditFieldToColumn( $.i18n._( 'Group B' ), form_item_input, tab_about_column1 );

		// Group C
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT );
		form_item_input.TText( {field: 'schema_version_group_C' } );
		this.addEditFieldToColumn( $.i18n._( 'Group C' ), form_item_input, tab_about_column1 );


		// Group D
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT );
		form_item_input.TText( {field: 'schema_version_group_D' } );
		this.addEditFieldToColumn( $.i18n._( 'Group D' ), form_item_input, tab_about_column1 );


		// Separated Box
		form_item_input = Global.loadWidgetByName( FormItemType.SEPARATED_BOX );
		form_item_input.SeparatedBox( {label: $.i18n._( 'Employees (Active / InActive)' )} );
		this.addEditFieldToColumn( null, form_item_input, tab_about_column1, '', null, true, false, 'user_active_inactive' );

	},

	uploadLicense: function( obj ) {
		var $this = this;
		var file = this.edit_view_ui_dic['license_browser'].getValue();
		$this.api.uploadFile( file, 'object_type=license&object_id=', {onResult: function( res ) {
			//file upload returns a "TRUE" string on success
			if ( res == "TRUE" ) {
				//$this.openEditView();

				ProgressBar.showProgressBar();
				IndexViewController.setNotificationBar( 'login' );
				window.setTimeout(function(){
					window.location.reload();
				},3000);
			} else {
				//TAlertManager.showAlert( $.i18n._( 'Invalid license file' ) )
				TAlertManager.showAlert( res );
				$this.edit_view_ui_dic.license_browser.clearForm();
			}

		}} );
	}


} );
