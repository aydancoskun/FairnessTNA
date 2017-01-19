UserLicenseViewController = BaseViewController.extend( {
	el: '#user_license_view_container',

	document_object_type_id: null,
	qualification_group_api: null,
	qualification_api: null,
	qualification_group_array: null,

	qualification_array: null,
	initialize: function( options ) {
		this._super( 'initialize', options );
		this.edit_view_tpl = 'UserLicenseEditView.html';
		this.permission_id = 'user_license';
		this.viewId = 'UserLicense';
		this.script_name = 'UserLicenseView';
		this.table_name_key = 'user_license';
		this.context_menu_name = $.i18n._( 'Licenses' );
		this.navigation_label = $.i18n._( 'License' ) + ':';
		this.api = new (APIFactory.getAPIClass( 'APIUserLicense' ))();
		this.qualification_api = new (APIFactory.getAPIClass( 'APIQualification' ))();
		this.qualification_group_api = new (APIFactory.getAPIClass( 'APIQualificationGroup' ))();
		this.document_object_type_id = 128;
		this.render();

		//call init data in parent view
		if ( !this.sub_view_mode ) {
			this.buildContextMenu();
			this.initData();
			this.setSelectRibbonMenuIfNecessary();
		}

	},

	showNoResultCover: function( show_new_btn ) {

		show_new_btn = this.ifContextButtonExist( ContextMenuIconName.add );

		if ( this.sub_view_mode ) {
			show_new_btn = true;
			this.grid.setGridHeight( 150 );
		}

		this.removeNoResultCover();
		this.no_result_box = Global.loadWidgetByName( WidgetNamesDic.NO_RESULT_BOX );
		this.no_result_box.NoResultBox( {related_view_controller: this, is_new: show_new_btn} );
		this.no_result_box.attr( 'id', this.ui_id + '_no_result_box' );

		var grid_div = $( this.el ).find( '.grid-div' );

		grid_div.append( this.no_result_box );

		this.initRightClickMenu( RightClickMenuType.NORESULTBOX );
	},

	onGridSelectRow: function() {
		if ( this.sub_view_mode ) {
			this.buildContextMenu( true );
			this.cancelOtherSubViewSelectedStatus();
		} else {
			this.buildContextMenu();
		}
		this.setDefaultMenu();
	},

	onGridSelectAll: function() {
		if ( this.sub_view_mode ) {
			this.buildContextMenu( true );
			this.cancelOtherSubViewSelectedStatus();
		}
		this.setDefaultMenu();
	},

	cancelOtherSubViewSelectedStatus: function() {
		switch ( true ) {
			case typeof( this.parent_view_controller.sub_user_skill_view_controller ) !== 'undefined':
				this.parent_view_controller.sub_user_skill_view_controller.unSelectAll();
			case typeof( this.parent_view_controller.sub_user_membership_view_controller ) !== 'undefined':
				this.parent_view_controller.sub_user_membership_view_controller.unSelectAll();
			case typeof( this.parent_view_controller.sub_user_education_view_controller ) !== 'undefined':
				this.parent_view_controller.sub_user_education_view_controller.unSelectAll();
			case typeof( this.parent_view_controller.sub_user_language_view_controller ) !== 'undefined':
				this.parent_view_controller.sub_user_language_view_controller.unSelectAll();
				break;
		}
	},

	onAddClick: function() {

		if ( this.sub_view_mode ) {
			this.buildContextMenu( true );
		}

		this._super( 'onAddClick' );
	},

	resizeSubGridHeight: function( length ) {
		var height = ( length * 26 >= 200 ) ? 200 : length * 26;
		this.grid.setGridHeight( height );
	},

	onMassEditClick: function() {

		var $this = this;
		$this.is_add = false;
		$this.is_viewing = false;
		$this.is_mass_editing = true;
		LocalCacheData.current_doing_context_action = 'mass_edit';
		$this.openEditView();
		var filter = {};
		var grid_selected_id_array = this.getGridSelectIdArray();
		var grid_selected_length = grid_selected_id_array.length;
		this.mass_edit_record_ids = [];

		$.each( grid_selected_id_array, function( index, value ) {
			$this.mass_edit_record_ids.push( value )
		} );

		filter.filter_data = {};
		filter.filter_data.id = this.mass_edit_record_ids;

		this.api['getCommon' + this.api.key_name + 'Data']( filter, {
			onResult: function( result ) {
				var result_data = result.getResult();

				$this.unique_columns = {};

				$this.linked_columns = {};

				if ( !result_data ) {
					result_data = [];
				}

				if ( $this.sub_view_mode && $this.parent_key ) {
					result_data[$this.parent_key] = $this.parent_value;
				}

				$this.current_edit_record = result_data;
				$this.initEditView();

			}
		} );

	},

	initOptions: function() {
		var $this = this;

		this.qualification_group_api.getQualificationGroup( '', false, false, {
			onResult: function( res ) {
				res = res.getResult();

				res = Global.buildTreeRecord( res );
				$this.qualification_group_array = res;

				if ( !$this.sub_view_mode && $this.basic_search_field_ui_dic['group_id'] ) {
					$this.basic_search_field_ui_dic['group_id'].setSourceData( res );
				}

			}
		} );

		var args = {};
		var filter_data = {};
		filter_data.type_id = [30];
		args.filter_data = filter_data;
		this.qualification_api.getQualification( args, {
			onResult: function( res ) {
				res = res.getResult();

				$this.qualification_array = res;
				if ( !$this.sub_view_mode && $this.basic_search_field_ui_dic['qualification_id'] ) {
					$this.basic_search_field_ui_dic['qualification_id'].setSourceData( res );
				}
			}
		} );

	},

	setTabStatus: function() {
		//Handle most cases that one tab and on audit tab
		if ( this.is_mass_editing ) {

			$( this.edit_view_tab.find( 'ul li a[ref="tab_attachment"]' ) ).parent().hide();
			$( this.edit_view_tab.find( 'ul li a[ref="tab_audit"]' ) ).parent().hide();
			this.edit_view_tab.tabs( 'select', 0 );

		} else {
			if ( this.subDocumentValidate() ) {
				$( this.edit_view_tab.find( 'ul li a[ref="tab_attachment"]' ) ).parent().show();
			} else {
				$( this.edit_view_tab.find( 'ul li a[ref="tab_attachment"]' ) ).parent().hide();
				this.edit_view_tab.tabs( 'select', 0 );
			}
			if ( this.subAuditValidate() ) {
				$( this.edit_view_tab.find( 'ul li a[ref="tab_audit"]' ) ).parent().show();
			} else {
				$( this.edit_view_tab.find( 'ul li a[ref="tab_audit"]' ) ).parent().hide();
				this.edit_view_tab.tabs( 'select', 0 );
			}

		}

		this.editFieldResize( 0 );
	},

	buildEditViewUI: function() {

		this._super( 'buildEditViewUI' );

		var $this = this;

		this.setTabLabels( {
			'tab_license': $.i18n._( 'License' ),
			'tab_attachment': $.i18n._( 'Attachments' ),
			'tab_audit': $.i18n._( 'Audit' )
		} );

		this.navigation.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIUserLicense' )),
			id: this.script_name + '_navigation',
			allow_multiple_selection: false,
			layout_name: ALayoutIDs.USER_LICENSE,
			navigation_mode: true,
			show_search_inputs: true
		} );

		this.setNavigation();

		//Tab 0 start

		var tab_license = this.edit_view_tab.find( '#tab_license' );

		var tab_license_column1 = tab_license.find( '.first-column' );

		this.edit_view_tabs[0] = [];

		this.edit_view_tabs[0].push( tab_license_column1 );

		// Employee
		var form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );
		form_item_input.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIUser' )),
			allow_multiple_selection: false,
			layout_name: ALayoutIDs.USER,
			field: 'user_id',
			set_empty: true,
			show_search_inputs: true
		} );
		var default_args = {};
		default_args.permission_section = 'user_license';
		form_item_input.setDefaultArgs( default_args );
		this.addEditFieldToColumn( $.i18n._( 'Employee' ), form_item_input, tab_license_column1, '' );

		// Type
		var args = {};
		var filter_data = {};
		filter_data.type_id = [30];
		args.filter_data = filter_data;

		form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );
		form_item_input.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIQualification' )),
			allow_multiple_selection: false,
			layout_name: ALayoutIDs.QUALIFICATION,
			show_search_inputs: true,
			set_empty: true,
			field: 'qualification_id'
		} );

		form_item_input.setDefaultArgs( args );
		this.addEditFieldToColumn( $.i18n._( 'Type' ), form_item_input, tab_license_column1 );

		// Number
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT_INPUT );

		form_item_input.TTextInput( {field: 'license_number', width: 200} );

		this.addEditFieldToColumn( $.i18n._( 'Number' ), form_item_input, tab_license_column1 );

		// Issued Date
		form_item_input = Global.loadWidgetByName( FormItemType.DATE_PICKER );

		form_item_input.TDatePicker( {field: 'license_issued_date'} );
		this.addEditFieldToColumn( $.i18n._( 'Issued Date' ), form_item_input, tab_license_column1 );

		// Expiry Date
		form_item_input = Global.loadWidgetByName( FormItemType.DATE_PICKER );

		form_item_input.TDatePicker( {field: 'license_expiry_date'} );
		this.addEditFieldToColumn( $.i18n._( 'Expiry Date' ), form_item_input, tab_license_column1 );

		//Tags
		form_item_input = Global.loadWidgetByName( FormItemType.TAG_INPUT );

		form_item_input.TTagInput( {field: 'tag', object_type_id: 253} );
		this.addEditFieldToColumn( $.i18n._( 'Tags' ), form_item_input, tab_license_column1, '', null, null, true );

	},

	buildSearchFields: function() {

		this._super( 'buildSearchFields' );
		var default_args = {};
		default_args.permission_section = 'user_license';
		this.search_fields = [

			new SearchField( {
				label: $.i18n._( 'Employee' ),
				in_column: 1,
				field: 'user_id',
				default_args: default_args,
				layout_name: ALayoutIDs.USER,
				api_class: (APIFactory.getAPIClass( 'APIUser' )),
				multiple: true,
				basic_search: true,
				adv_search: false,
				form_item_type: FormItemType.AWESOME_BOX
			} ),

			new SearchField( {
				label: $.i18n._( 'License Type' ),
				in_column: 1,
				field: 'qualification_id',
				layout_name: ALayoutIDs.QUALIFICATION,
				api_class: (APIFactory.getAPIClass( 'APIQualification' )),
				multiple: true,
				basic_search: true,
				adv_search: false,
				form_item_type: FormItemType.AWESOME_BOX
			} ),

			new SearchField( {
				label: $.i18n._( 'Group' ),
				in_column: 1,
				multiple: true,
				field: 'group_id',
				layout_name: ALayoutIDs.TREE_COLUMN,
				tree_mode: true,
				basic_search: true,
				adv_search: false,
				form_item_type: FormItemType.AWESOME_BOX
			} ),

			new SearchField( {
				label: $.i18n._( 'Tags' ),
				field: 'tag',
				basic_search: true,
				adv_search: false,
				in_column: 1,
				object_type_id: 253,
				form_item_type: FormItemType.TAG_INPUT
			} ),

			new SearchField( {
				label: $.i18n._( 'License Number' ),
				in_column: 2,
				field: 'license_number',
				multiple: true,
				basic_search: true,
				adv_search: false,
				form_item_type: FormItemType.TEXT_INPUT
			} ),

			new SearchField( {
				label: $.i18n._( 'License Issued Date' ),
				in_column: 2,
				field: 'license_issued_date',
				tree_mode: true,
				basic_search: true,
				adv_search: false,
				form_item_type: FormItemType.DATE_PICKER
			} ),

			new SearchField( {
				label: $.i18n._( 'License Expiry Date' ),
				in_column: 2,
				field: 'license_expiry_date',
				tree_mode: true,
				basic_search: true,
				adv_search: false,
				form_item_type: FormItemType.DATE_PICKER
			} ),

			new SearchField( {
				label: $.i18n._( 'Created By' ),
				in_column: 2,
				field: 'created_by',
				layout_name: ALayoutIDs.USER,
				api_class: (APIFactory.getAPIClass( 'APIUser' )),
				multiple: true,
				basic_search: true,
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
				basic_search: true,
				adv_search: false,
				form_item_type: FormItemType.AWESOME_BOX
			} )
		];
	},

	onTabShow: function( e, ui ) {

		var key = this.edit_view_tab_selected_index;
		this.editFieldResize( key );
		if ( !this.current_edit_record ) {
			return;
		}

		if ( this.edit_view_tab_selected_index === 1 ) {
			if ( this.current_edit_record.id ) {
				this.edit_view_tab.find( '#tab_attachment' ).find( '.first-column-sub-view' ).css( 'display', 'block' );
				this.initSubDocumentView();
			} else {
				this.edit_view_tab.find( '#tab_attachment' ).find( '.first-column-sub-view' ).css( 'display', 'none' );
				this.edit_view.find( '.save-and-continue-div' ).css( 'display', 'block' );
			}

		} else if ( this.edit_view_tab_selected_index === 2 ) {

			if ( this.current_edit_record.id ) {
				this.edit_view_tab.find( '#tab_audit' ).find( '.first-column-sub-view' ).css( 'display', 'block' );
				this.initSubLogView( 'tab_audit' );
			} else {
				this.edit_view_tab.find( '#tab_audit' ).find( '.first-column-sub-view' ).css( 'display', 'none' );
				this.edit_view.find( '.save-and-continue-div' ).css( 'display', 'block' );
			}
		} else {
			this.buildContextMenu( true );
			this.setEditMenu();
		}
	},

	initTabData: function() {

		if ( this.edit_view_tab.tabs( 'option', 'selected' ) === 1 ) {
			if ( this.current_edit_record.id ) {
				this.edit_view_tab.find( '#tab_attachment' ).find( '.first-column-sub-view' ).css( 'display', 'block' );
				this.initSubDocumentView();
			} else {
				this.edit_view_tab.find( '#tab_attachment' ).find( '.first-column-sub-view' ).css( 'display', 'none' );
				this.edit_view.find( '.save-and-continue-div' ).css( 'display', 'block' );
			}
		} else if ( this.edit_view_tab.tabs( 'option', 'selected' ) === 2 ) {
			if ( this.current_edit_record.id ) {
				this.edit_view_tab.find( '#tab_audit' ).find( '.first-column-sub-view' ).css( 'display', 'block' );
				this.initSubLogView( 'tab_audit' );
			} else {
				this.edit_view_tab.find( '#tab_audit' ).find( '.first-column-sub-view' ).css( 'display', 'none' );
				this.edit_view.find( '.save-and-continue-div' ).css( 'display', 'block' );
			}
		}
	},

	removeEditView: function() {

		this._super( 'removeEditView' );
		this.sub_document_view_controller = null;

	},

	initSubDocumentView: function() {
		var $this = this;

		if ( this.sub_document_view_controller ) {
			this.sub_document_view_controller.buildContextMenu( true );
			this.sub_document_view_controller.setDefaultMenu();
			$this.sub_document_view_controller.parent_value = $this.current_edit_record.id;
			$this.sub_document_view_controller.parent_edit_record = $this.current_edit_record;
			$this.sub_document_view_controller.initData();
			return;
		}

		Global.loadScript( 'views/document/DocumentViewController.js', function() {
			var tab_attachment = $this.edit_view_tab.find( '#tab_attachment' );
			var firstColumn = tab_attachment.find( '.first-column-sub-view' );
			Global.trackView( 'Sub' + 'Document' + 'View' );
			DocumentViewController.loadSubView( firstColumn, beforeLoadView, afterLoadView );

		} );

		function beforeLoadView() {

		}

		function afterLoadView( subViewController ) {
			$this.sub_document_view_controller = subViewController;
			$this.sub_document_view_controller.parent_key = 'object_id';
			$this.sub_document_view_controller.parent_value = $this.current_edit_record.id;
			$this.sub_document_view_controller.document_object_type_id = $this.document_object_type_id;
			$this.sub_document_view_controller.parent_edit_record = $this.current_edit_record;
			$this.sub_document_view_controller.parent_view_controller = $this;
			$this.sub_document_view_controller.initData();
		}

	}

} );

UserLicenseViewController.loadSubView = function( container, beforeViewLoadedFun, afterViewLoadedFun ) {
	Global.loadViewSource( 'UserLicense', 'SubUserLicenseView.html', function( result ) {
		var args = {};
		var template = _.template( result );

		if ( Global.isSet( beforeViewLoadedFun ) ) {
			beforeViewLoadedFun();
		}
		if ( Global.isSet( container ) ) {
			container.html( template( args ) );
			if ( Global.isSet( afterViewLoadedFun ) ) {
				afterViewLoadedFun( sub_user_license_view_controller );
			}
		}
	} );
};