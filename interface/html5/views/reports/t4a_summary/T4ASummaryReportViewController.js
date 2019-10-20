T4ASummaryReportViewController = ReportBaseViewController.extend( {


	_required_files: ['APIT4ASummaryReport', 'APIPayStubEntryAccount'],

	type_array: null,

	initReport: function( options ) {
		this.script_name = 'T4ASummaryReport';
		this.viewId = 'T4ASummaryReport';
		this.context_menu_name = $.i18n._( 'T4A Summary' );
		this.navigation_label = $.i18n._( 'Saved Report' ) + ':';
		this.view_file = 'T4ASummaryReportView.html';
		this.api = new (APIFactory.getAPIClass( 'APIT4ASummaryReport' ))();
		this.include_form_setup = true;
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
			id: this.viewId + 'Editor',
			ribbon_menu: menu,
			sub_menus: []
		} );

		//menu group
		var saved_report_group = new RibbonSubMenuGroup( {
			label: $.i18n._( 'Saved Report' ),
			id: this.viewId + 'SavedReport',
			ribbon_menu: menu,
			sub_menus: []
		} );

		//menu group
		var form_setup_group = new RibbonSubMenuGroup( {
			label: $.i18n._( 'Form' ),
			id: this.viewId + 'Form',
			ribbon_menu: menu,
			sub_menus: []
		} );

		var view_html = new RibbonSubMenu( {
			label: $.i18n._( 'View' ),
			id: ContextMenuIconName.view_html,
			group: editor_group,
			icon: Icons.view,
			permission_result: true,
			permission: null
		} );

		var view_pdf = new RibbonSubMenu( {
			label: $.i18n._( 'PDF' ),
			id: ContextMenuIconName.view,
			group: editor_group,
			icon: Icons.print,
			permission_result: true,
			permission: null
		} );

		var excel = new RibbonSubMenu( {
			label: $.i18n._( 'Excel' ),
			id: ContextMenuIconName.export_excel,
			group: editor_group,
			icon: Icons.export_excel,
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

		var save_existed_report = new RibbonSubMenu( {
			label: $.i18n._( 'Save' ),
			id: ContextMenuIconName.save_existed_report,
			group: saved_report_group,
			icon: Icons.save,
			permission_result: true,
			permission: null
		} );

		var save_new_report = new RibbonSubMenu( {
			label: $.i18n._( 'Save as New' ),
			id: ContextMenuIconName.save_new_report,
			group: saved_report_group,
			icon: Icons.save_and_new,
			permission_result: true,
			permission: null
		} );

		var view_print = new RibbonSubMenu( {
			label: $.i18n._( 'View' ),
			id: ContextMenuIconName.view_print,
			group: form_setup_group,
			icon: 'view-35x35.png',
			type: RibbonSubMenuType.NAVIGATION,
			items: [],
			permission_result: true,
			permission: true
		} );

		var pdf_form_government = new RibbonSubMenuNavItem( {
			label: $.i18n._( 'Government (Multiple Employees/Page)' ),
			id: 'pdf_form_government',
			nav: view_print
		} );

		var pdf_form = new RibbonSubMenuNavItem( {
			label: $.i18n._( 'Employee (One Employee/Page)' ),
			id: 'pdf_form',
			nav: view_print
		} );

		if ( ( Global.getProductEdition() >= 15 ) ) {
			var pdf_form_publish_employee = new RibbonSubMenuNavItem( {
				label: $.i18n._( 'Publish Employee Forms' ),
				id: 'pdf_form_publish_employee',
				nav: view_print
			} );
		}

		// var print_print = new RibbonSubMenu( {label: $.i18n._( 'Print' ),
		// 	id: ContextMenuIconName.print,
		// 	group: form_setup_group,
		// 	icon: 'print-35x35.png',
		// 	type: RibbonSubMenuType.NAVIGATION,
		// 	items: [],
		// 	permission_result: true,
		// 	permission: true} );
		//
		// var pdf_form_print_government = new RibbonSubMenuNavItem( {label: $.i18n._( 'Government (Multiple Employees/Page)' ),
		// 	id: 'pdf_form_print_government',
		// 	nav: print_print
		// } );
		//
		// var pdf_form_print = new RibbonSubMenuNavItem( {label: $.i18n._( 'Employee (One Employee/Page)' ),
		// 	id: 'pdf_form_print',
		// 	nav: print_print
		// } );

		var eFile = new RibbonSubMenu( {
			label: $.i18n._( 'eFile' ),
			id: ContextMenuIconName.e_file_xml,
			group: form_setup_group,
			icon: Icons.e_file,
			permission_result: true,
			permission: null
		} );

		var save_setup = new RibbonSubMenu( {
			label: $.i18n._( 'Save Setup' ),
			id: ContextMenuIconName.save_setup,
			group: form_setup_group,
			icon: Icons.save_setup,
			permission_result: true,
			permission: null
		} );

		return [menu];

	},

	initOptions: function( callBack ) {
		var $this = this;
		var options = [
			{ option_name: 'page_orientation' },
			{ option_name: 'font_size' },
			{ option_name: 'chart_display_mode' },
			{ option_name: 'chart_type' },
			{ option_name: 'templates' },
			{ option_name: 'setup_fields' },
			{ option_name: 'type' },
			{ option_name: 'auto_refresh' }
		];

		this.initDropDownOptions( options, function( result ) {

			new (APIFactory.getAPIClass( 'APICompany' ))().getOptions( 'province', 'CA', {
				onResult: function( provinceResult ) {

					$this.province_array = Global.buildRecordArray( provinceResult.getResult() );

					callBack( result ); // First to initialize drop down options, and then to initialize edit view UI.
				}
			} );

		} );

	},

	onCustomContextClick: function( id ) {
		switch ( id ) {
			case ContextMenuIconName.e_file_xml: //All report view
				this.onViewClick( 'efile_xml' );
				break;
			default:
				return false; //FALSE tells onContextMenuClick() to keep processing.
		}

		return true;
	},

	onReportMenuClick: function( id ) {
		this.onViewClick( id );
	},

	buildFormSetupUI: function() {

		var $this = this;

		var tab3 = this.edit_view_tab.find( '#tab_form_setup' );

		var tab3_column1 = tab3.find( '.first-column' );

		this.edit_view_tabs[3] = [];

		this.edit_view_tabs[3].push( tab3_column1 );

		//Status

		var form_item_input = Global.loadWidgetByName( FormItemType.COMBO_BOX );
		form_item_input.TComboBox( { field: 'status_id', set_empty: false } );
		form_item_input.setSourceData( Global.addFirstItemToArray( $this.type_array ) );
		this.addEditFieldToColumn( $.i18n._( 'Status' ), form_item_input, tab3_column1 );

		//Pension Or Superannuation (Box: 16)
		var v_box = $( '<div class=\'v-box\'></div>' );

		//Include
		form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );
		form_item_input.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'pension_include_pay_stub_entry_account'
		} );

		var form_item = this.putInputToInsideFormItem( form_item_input, $.i18n._( 'Include' ) );

		v_box.append( form_item );
		v_box.append( '<div class=\'clear-both-div\'></div>' );

		//Exclude
		var form_item_input_1 = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

		form_item_input_1.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'pension_exclude_pay_stub_entry_account'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input_1, $.i18n._( 'Exclude' ) );

		v_box.append( form_item );

		this.addEditFieldToColumn( $.i18n._( 'Pension Or Superannuation (Box: 16)' ), [form_item_input, form_item_input_1], tab3_column1, '', v_box, false, true );

		//Lump-sum Payments (Box: 18)
		v_box = $( '<div class=\'v-box\'></div>' );

		//Include
		form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );
		form_item_input.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'lump_sum_payment_include_pay_stub_entry_account'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input, $.i18n._( 'Include' ) );

		v_box.append( form_item );
		v_box.append( '<div class=\'clear-both-div\'></div>' );

		//Exclude
		form_item_input_1 = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

		form_item_input_1.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'lump_sum_payment_exclude_pay_stub_entry_account'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input_1, $.i18n._( 'Exclude' ) );

		v_box.append( form_item );

		this.addEditFieldToColumn( $.i18n._( 'Lump-sum Payments (Box: 18)' ), [form_item_input, form_item_input_1], tab3_column1, '', v_box, false, true );

		//Self-Employed Commisions  (Box: 20)
		v_box = $( '<div class=\'v-box\'></div>' );

		//Include
		form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );
		form_item_input.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'self_employed_commission_include_pay_stub_entry_account'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input, $.i18n._( 'Include' ) );

		v_box.append( form_item );
		v_box.append( '<div class=\'clear-both-div\'></div>' );

		//Exclude
		form_item_input_1 = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

		form_item_input_1.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'self_employed_commission_exclude_pay_stub_entry_account'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input_1, $.i18n._( 'Exclude' ) );

		v_box.append( form_item );

		this.addEditFieldToColumn( $.i18n._( 'Self-Employed Commisions  (Box: 20)' ), [form_item_input, form_item_input_1], tab3_column1, '', v_box, false, true );

		//Income Tax Deducted (Box: 22)
		v_box = $( '<div class=\'v-box\'></div>' );

		//Include
		form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );
		form_item_input.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'income_tax_include_pay_stub_entry_account'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input, $.i18n._( 'Include' ) );

		v_box.append( form_item );
		v_box.append( '<div class=\'clear-both-div\'></div>' );

		//Exclude
		form_item_input_1 = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

		form_item_input_1.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'income_tax_exclude_pay_stub_entry_account'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input_1, $.i18n._( 'Exclude' ) );

		v_box.append( form_item );

		this.addEditFieldToColumn( $.i18n._( 'Income Tax Deducted (Box 22)' ), [form_item_input, form_item_input_1], tab3_column1, '', v_box, false, true );

		//Annuities (Box: 27)
		v_box = $( '<div class=\'v-box\'></div>' );

		//Include
		form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );
		form_item_input.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'annuities_include_pay_stub_entry_account'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input, $.i18n._( 'Include' ) );

		v_box.append( form_item );
		v_box.append( '<div class=\'clear-both-div\'></div>' );

		//Exclude
		form_item_input_1 = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

		form_item_input_1.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'annuities_exclude_pay_stub_entry_account'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input_1, $.i18n._( 'Exclude' ) );

		v_box.append( form_item );

		this.addEditFieldToColumn( $.i18n._( 'Annuities (Box 24)' ), [form_item_input, form_item_input_1], tab3_column1, '', v_box, false, true );

		//Fees for Services (Box: 48)
		v_box = $( '<div class=\'v-box\'></div>' );

		//Include
		form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );
		form_item_input.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'service_fees_include_pay_stub_entry_account'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input, $.i18n._( 'Include' ) );

		v_box.append( form_item );
		v_box.append( '<div class=\'clear-both-div\'></div>' );

		//Exclude
		form_item_input_1 = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

		form_item_input_1.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'service_fees_exclude_pay_stub_entry_account'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input_1, $.i18n._( 'Exclude' ) );

		v_box.append( form_item );

		this.addEditFieldToColumn( $.i18n._( 'Fees for Services (Box: 48)' ), [form_item_input, form_item_input_1], tab3_column1, '', v_box, false, true );

		//Box [0]
		v_box = $( '<div class=\'v-box\'></div>' );

		//Include
		form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

		form_item_input.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'box_0_include_pay_stub_entry_account'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input, $.i18n._( 'Include' ) );

		v_box.append( form_item );
		v_box.append( '<div class=\'clear-both-div\'></div>' );

		//Exclude
		form_item_input_1 = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

		form_item_input_1.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'box_0_exclude_pay_stub_entry_account'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input_1, $.i18n._( 'Exclude' ) );

		v_box.append( form_item );

		var custom_label_widget = $( '<div class=\'h-box\'></div>' );
		var label = $( '<span class="edit-view-form-item-label"></span>' );
		var box = Global.loadWidgetByName( FormItemType.TEXT_INPUT );
		box.TTextInput( { field: 'box_0_box', width: 50 } );
		box.css( 'float', 'right' );
		box.bind( 'formItemChange', function( e, target ) {
			$this.onFormItemChange( target );
		} );

		label.text( $.i18n._( 'Box' ) );

		this.edit_view_ui_dic[box.getField()] = box;

		custom_label_widget.append( box );
		custom_label_widget.append( label );

		this.addEditFieldToColumn( $.i18n._( 'Box' ), [form_item_input, form_item_input_1], tab3_column1, '', v_box, false, true, false, false, custom_label_widget );

		//Box [1]
		v_box = $( '<div class=\'v-box\'></div>' );

		//Include
		form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

		form_item_input.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'box_1_include_pay_stub_entry_account'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input, $.i18n._( 'Include' ) );

		v_box.append( form_item );
		v_box.append( '<div class=\'clear-both-div\'></div>' );

		//Exclude
		form_item_input_1 = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

		form_item_input_1.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'box_1_exclude_pay_stub_entry_account'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input_1, $.i18n._( 'Exclude' ) );

		v_box.append( form_item );

		custom_label_widget = $( '<div class=\'h-box\'></div>' );
		label = $( '<span class="edit-view-form-item-label"></span>' );
		box = Global.loadWidgetByName( FormItemType.TEXT_INPUT );
		box.TTextInput( { field: 'box_1_box', width: 50 } );
		box.css( 'float', 'right' );
		box.bind( 'formItemChange', function( e, target ) {
			$this.onFormItemChange( target );
		} );

		label.text( $.i18n._( 'Box' ) );

		this.edit_view_ui_dic[box.getField()] = box;

		custom_label_widget.append( box );
		custom_label_widget.append( label );

		this.addEditFieldToColumn( $.i18n._( 'Box' ), [form_item_input, form_item_input_1], tab3_column1, '', v_box, false, true, false, false, custom_label_widget );

		//Box [2]
		v_box = $( '<div class=\'v-box\'></div>' );

		//Include
		form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

		form_item_input.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'box_2_include_pay_stub_entry_account'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input, $.i18n._( 'Include' ) );

		v_box.append( form_item );
		v_box.append( '<div class=\'clear-both-div\'></div>' );

		//Exclude
		form_item_input_1 = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

		form_item_input_1.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'box_2_exclude_pay_stub_entry_account'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input_1, $.i18n._( 'Exclude' ) );

		v_box.append( form_item );

		custom_label_widget = $( '<div class=\'h-box\'></div>' );
		label = $( '<span class="edit-view-form-item-label"></span>' );
		box = Global.loadWidgetByName( FormItemType.TEXT_INPUT );
		box.TTextInput( { field: 'box_2_box', width: 50 } );
		box.css( 'float', 'right' );
		box.bind( 'formItemChange', function( e, target ) {
			$this.onFormItemChange( target );
		} );

		label.text( $.i18n._( 'Box' ) );

		this.edit_view_ui_dic[box.getField()] = box;

		custom_label_widget.append( box );
		custom_label_widget.append( label );

		this.addEditFieldToColumn( $.i18n._( 'Box' ), [form_item_input, form_item_input_1], tab3_column1, '', v_box, false, true, false, false, custom_label_widget );

		//Box [3]
		v_box = $( '<div class=\'v-box\'></div>' );

		//Include
		form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

		form_item_input.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'box_3_include_pay_stub_entry_account'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input, $.i18n._( 'Include' ) );

		v_box.append( form_item );
		v_box.append( '<div class=\'clear-both-div\'></div>' );

		//Exclude
		form_item_input_1 = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

		form_item_input_1.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'box_3_exclude_pay_stub_entry_account'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input_1, $.i18n._( 'Exclude' ) );

		v_box.append( form_item );

		custom_label_widget = $( '<div class=\'h-box\'></div>' );
		label = $( '<span class="edit-view-form-item-label"></span>' );
		box = Global.loadWidgetByName( FormItemType.TEXT_INPUT );
		box.TTextInput( { field: 'box_3_box', width: 50 } );
		box.css( 'float', 'right' );
		box.bind( 'formItemChange', function( e, target ) {
			$this.onFormItemChange( target );
		} );

		label.text( $.i18n._( 'Box' ) );

		this.edit_view_ui_dic[box.getField()] = box;

		custom_label_widget.append( box );
		custom_label_widget.append( label );

		this.addEditFieldToColumn( $.i18n._( 'Box' ), [form_item_input, form_item_input_1], tab3_column1, '', v_box, false, true, false, false, custom_label_widget );

		//Box [4]
		v_box = $( '<div class=\'v-box\'></div>' );

		//Include
		form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

		form_item_input.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'box_4_include_pay_stub_entry_account'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input, $.i18n._( 'Include' ) );

		v_box.append( form_item );
		v_box.append( '<div class=\'clear-both-div\'></div>' );

		//Exclude
		form_item_input_1 = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

		form_item_input_1.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIPayStubEntryAccount' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.PAY_STUB_ACCOUNT,
			show_search_inputs: true,
			set_empty: true,
			field: 'box_4_exclude_pay_stub_entry_account'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input_1, $.i18n._( 'Exclude' ) );

		v_box.append( form_item );

		custom_label_widget = $( '<div class=\'h-box\'></div>' );
		label = $( '<span class="edit-view-form-item-label"></span>' );
		box = Global.loadWidgetByName( FormItemType.TEXT_INPUT );
		box.TTextInput( { field: 'box_4_box', width: 50 } );
		box.css( 'float', 'right' );
		box.bind( 'formItemChange', function( e, target ) {
			$this.onFormItemChange( target );
		} );

		label.text( $.i18n._( 'Box' ) );

		this.edit_view_ui_dic[box.getField()] = box;

		custom_label_widget.append( box );
		custom_label_widget.append( label );

		this.addEditFieldToColumn( $.i18n._( 'Box' ), [form_item_input, form_item_input_1], tab3_column1, '', v_box, false, true, false, false, custom_label_widget );

		//Remittances Paid in Year
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT_INPUT );

		form_item_input.TTextInput( { field: 'remittances_paid', width: 120 } );
		this.addEditFieldToColumn( $.i18n._( 'Remittances Paid in Year' ), form_item_input, tab3_column1 );
	},

	getFormSetupData: function() {
		var other = {};
		other.pension = {
			include_pay_stub_entry_account: this.current_edit_record.pension_include_pay_stub_entry_account,
			exclude_pay_stub_entry_account: this.current_edit_record.pension_exclude_pay_stub_entry_account
		};

		other.lump_sum_payment = {
			include_pay_stub_entry_account: this.current_edit_record.lump_sum_payment_include_pay_stub_entry_account,
			exclude_pay_stub_entry_account: this.current_edit_record.lump_sum_payment_exclude_pay_stub_entry_account
		};

		other.self_employed_commission = {
			include_pay_stub_entry_account: this.current_edit_record.self_employed_commission_include_pay_stub_entry_account,
			exclude_pay_stub_entry_account: this.current_edit_record.self_employed_commission_exclude_pay_stub_entry_account
		};

		other.income_tax = {
			include_pay_stub_entry_account: this.current_edit_record.income_tax_include_pay_stub_entry_account,
			exclude_pay_stub_entry_account: this.current_edit_record.income_tax_exclude_pay_stub_entry_account
		};

		other.annuities = {
			include_pay_stub_entry_account: this.current_edit_record.annuities_include_pay_stub_entry_account,
			exclude_pay_stub_entry_account: this.current_edit_record.annuities_exclude_pay_stub_entry_account
		};

		other.service_fees = {
			include_pay_stub_entry_account: this.current_edit_record.service_fees_include_pay_stub_entry_account,
			exclude_pay_stub_entry_account: this.current_edit_record.service_fees_exclude_pay_stub_entry_account
		};

		other.other_box = [];

		other.other_box.push( {
			box: this.current_edit_record.box_0_box,
			include_pay_stub_entry_account: this.current_edit_record.box_0_include_pay_stub_entry_account,
			exclude_pay_stub_entry_account: this.current_edit_record.box_0_exclude_pay_stub_entry_account
		} );

		other.other_box.push( {
			box: this.current_edit_record.box_1_box,
			include_pay_stub_entry_account: this.current_edit_record.box_1_include_pay_stub_entry_account,
			exclude_pay_stub_entry_account: this.current_edit_record.box_1_exclude_pay_stub_entry_account
		} );

		other.other_box.push( {
			box: this.current_edit_record.box_2_box,
			include_pay_stub_entry_account: this.current_edit_record.box_2_include_pay_stub_entry_account,
			exclude_pay_stub_entry_account: this.current_edit_record.box_2_exclude_pay_stub_entry_account
		} );

		other.other_box.push( {
			box: this.current_edit_record.box_3_box,
			include_pay_stub_entry_account: this.current_edit_record.box_3_include_pay_stub_entry_account,
			exclude_pay_stub_entry_account: this.current_edit_record.box_3_exclude_pay_stub_entry_account
		} );

		other.other_box.push( {
			box: this.current_edit_record.box_4_box,
			include_pay_stub_entry_account: this.current_edit_record.box_4_include_pay_stub_entry_account,
			exclude_pay_stub_entry_account: this.current_edit_record.box_4_exclude_pay_stub_entry_account
		} );

		other.status_id = this.current_edit_record.status_id;

		other.remittances_paid = this.current_edit_record.remittances_paid;

		return other;
	},
	/* jshint ignore:start */
	setFormSetupData: function( res_Data ) {

		if ( !res_Data ) {
			this.show_empty_message = true;
		}

		if ( res_Data ) {
			if ( res_Data.pension ) {
				this.edit_view_ui_dic.pension_exclude_pay_stub_entry_account.setValue( res_Data.pension.exclude_pay_stub_entry_account );
				this.edit_view_ui_dic.pension_include_pay_stub_entry_account.setValue( res_Data.pension.include_pay_stub_entry_account );

				this.current_edit_record.pension_include_pay_stub_entry_account = res_Data.pension.include_pay_stub_entry_account;
				this.current_edit_record.pension_exclude_pay_stub_entry_account = res_Data.pension.exclude_pay_stub_entry_account;

			}

			if ( res_Data.lump_sum_payment ) {
				this.edit_view_ui_dic.lump_sum_payment_exclude_pay_stub_entry_account.setValue( res_Data.lump_sum_payment.exclude_pay_stub_entry_account );
				this.edit_view_ui_dic.lump_sum_payment_include_pay_stub_entry_account.setValue( res_Data.lump_sum_payment.include_pay_stub_entry_account );

				this.current_edit_record.lump_sum_payment_include_pay_stub_entry_account = res_Data.lump_sum_payment.include_pay_stub_entry_account;
				this.current_edit_record.lump_sum_payment_exclude_pay_stub_entry_account = res_Data.lump_sum_payment.exclude_pay_stub_entry_account;
			}

			if ( res_Data.self_employed_commission ) {
				this.edit_view_ui_dic.self_employed_commission_exclude_pay_stub_entry_account.setValue( res_Data.self_employed_commission.exclude_pay_stub_entry_account );
				this.edit_view_ui_dic.self_employed_commission_include_pay_stub_entry_account.setValue( res_Data.self_employed_commission.include_pay_stub_entry_account );

				this.current_edit_record.self_employed_commission_include_pay_stub_entry_account = res_Data.self_employed_commission.include_pay_stub_entry_account;
				this.current_edit_record.self_employed_commission_exclude_pay_stub_entry_account = res_Data.self_employed_commission.exclude_pay_stub_entry_account;
			}

			//
			if ( res_Data.income_tax ) {
				this.edit_view_ui_dic.income_tax_exclude_pay_stub_entry_account.setValue( res_Data.income_tax.exclude_pay_stub_entry_account );
				this.edit_view_ui_dic.income_tax_include_pay_stub_entry_account.setValue( res_Data.income_tax.include_pay_stub_entry_account );

				this.current_edit_record.income_tax_include_pay_stub_entry_account = res_Data.income_tax.include_pay_stub_entry_account;
				this.current_edit_record.income_tax_exclude_pay_stub_entry_account = res_Data.income_tax.exclude_pay_stub_entry_account;
			}

			if ( res_Data.annuities ) {
				this.edit_view_ui_dic.annuities_exclude_pay_stub_entry_account.setValue( res_Data.annuities.exclude_pay_stub_entry_account );
				this.edit_view_ui_dic.annuities_include_pay_stub_entry_account.setValue( res_Data.annuities.include_pay_stub_entry_account );

				this.current_edit_record.annuities_include_pay_stub_entry_account = res_Data.annuities.include_pay_stub_entry_account;
				this.current_edit_record.annuities_exclude_pay_stub_entry_account = res_Data.annuities.exclude_pay_stub_entry_account;
			}

			if ( res_Data.service_fees ) {
				this.edit_view_ui_dic.service_fees_exclude_pay_stub_entry_account.setValue( res_Data.service_fees.exclude_pay_stub_entry_account );
				this.edit_view_ui_dic.service_fees_include_pay_stub_entry_account.setValue( res_Data.service_fees.include_pay_stub_entry_account );

				this.current_edit_record.service_fees_include_pay_stub_entry_account = res_Data.service_fees.include_pay_stub_entry_account;
				this.current_edit_record.service_fees_exclude_pay_stub_entry_account = res_Data.service_fees.exclude_pay_stub_entry_account;
			}

			if ( res_Data.status_id ) {
				this.edit_view_ui_dic.status_id.setValue( res_Data.status_id );

				this.current_edit_record.status_id = res_Data.status_id;
			}

			if ( res_Data.remittances_paid ) {
				this.edit_view_ui_dic.remittances_paid.setValue( res_Data.remittances_paid );

				this.current_edit_record.remittances_paid = res_Data.remittances_paid;
			}

			if ( res_Data.other_box ) {

				if ( res_Data.other_box[0] ) {
					this.edit_view_ui_dic.box_0_box.setValue( res_Data.other_box[0].box );
					this.edit_view_ui_dic.box_0_exclude_pay_stub_entry_account.setValue( res_Data.other_box[0].exclude_pay_stub_entry_account );
					this.edit_view_ui_dic.box_0_include_pay_stub_entry_account.setValue( res_Data.other_box[0].include_pay_stub_entry_account );

					this.current_edit_record.box_0_box = res_Data.other_box[0].box;
					this.current_edit_record.box_0_include_pay_stub_entry_account = res_Data.other_box[0].include_pay_stub_entry_account;
					this.current_edit_record.box_0_exclude_pay_stub_entry_account = res_Data.other_box[0].exclude_pay_stub_entry_account;

				}

				if ( res_Data.other_box[1] ) {
					this.edit_view_ui_dic.box_1_box.setValue( res_Data.other_box[1].box );
					this.edit_view_ui_dic.box_1_exclude_pay_stub_entry_account.setValue( res_Data.other_box[1].exclude_pay_stub_entry_account );
					this.edit_view_ui_dic.box_1_include_pay_stub_entry_account.setValue( res_Data.other_box[1].include_pay_stub_entry_account );

					this.current_edit_record.box_1_box = res_Data.other_box[1].box;
					this.current_edit_record.box_1_include_pay_stub_entry_account = res_Data.other_box[1].include_pay_stub_entry_account;
					this.current_edit_record.box_1_exclude_pay_stub_entry_account = res_Data.other_box[1].exclude_pay_stub_entry_account;

				}

				if ( res_Data.other_box[2] ) {
					this.edit_view_ui_dic.box_2_box.setValue( res_Data.other_box[2].box );
					this.edit_view_ui_dic.box_2_exclude_pay_stub_entry_account.setValue( res_Data.other_box[2].exclude_pay_stub_entry_account );
					this.edit_view_ui_dic.box_2_include_pay_stub_entry_account.setValue( res_Data.other_box[2].include_pay_stub_entry_account );

					this.current_edit_record.box_2_box = res_Data.other_box[2].box;
					this.current_edit_record.box_2_include_pay_stub_entry_account = res_Data.other_box[2].include_pay_stub_entry_account;
					this.current_edit_record.box_2_exclude_pay_stub_entry_account = res_Data.other_box[2].exclude_pay_stub_entry_account;

				}

				if ( res_Data.other_box[3] ) {
					this.edit_view_ui_dic.box_3_box.setValue( res_Data.other_box[3].box );
					this.edit_view_ui_dic.box_3_exclude_pay_stub_entry_account.setValue( res_Data.other_box[3].exclude_pay_stub_entry_account );
					this.edit_view_ui_dic.box_3_include_pay_stub_entry_account.setValue( res_Data.other_box[3].include_pay_stub_entry_account );

					this.current_edit_record.box_3_box = res_Data.other_box[3].box;
					this.current_edit_record.box_3_include_pay_stub_entry_account = res_Data.other_box[3].include_pay_stub_entry_account;
					this.current_edit_record.box_3_exclude_pay_stub_entry_account = res_Data.other_box[3].exclude_pay_stub_entry_account;

				}

				if ( res_Data.other_box[4] ) {
					this.edit_view_ui_dic.box_4_box.setValue( res_Data.other_box[4].box );
					this.edit_view_ui_dic.box_4_exclude_pay_stub_entry_account.setValue( res_Data.other_box[4].exclude_pay_stub_entry_account );
					this.edit_view_ui_dic.box_4_include_pay_stub_entry_account.setValue( res_Data.other_box[4].include_pay_stub_entry_account );

					this.current_edit_record.box_4_box = res_Data.other_box[4].box;
					this.current_edit_record.box_4_include_pay_stub_entry_account = res_Data.other_box[4].include_pay_stub_entry_account;
					this.current_edit_record.box_4_exclude_pay_stub_entry_account = res_Data.other_box[4].exclude_pay_stub_entry_account;

				}

				if ( res_Data.other_box[5] ) {
					this.edit_view_ui_dic.box_5_box.setValue( res_Data.other_box[5].box );
					this.edit_view_ui_dic.box_5_exclude_pay_stub_entry_account.setValue( res_Data.other_box[5].exclude_pay_stub_entry_account );
					this.edit_view_ui_dic.box_5_include_pay_stub_entry_account.setValue( res_Data.other_box[5].include_pay_stub_entry_account );

					this.current_edit_record.box_5_box = res_Data.other_box[5].box;
					this.current_edit_record.box_5_include_pay_stub_entry_account = res_Data.other_box[5].include_pay_stub_entry_account;
					this.current_edit_record.box_5_exclude_pay_stub_entry_account = res_Data.other_box[5].exclude_pay_stub_entry_account;

				}

			}

		}
	}
	/* jshint ignore:end */
} );
