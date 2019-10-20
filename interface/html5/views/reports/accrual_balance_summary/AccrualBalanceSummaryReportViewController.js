AccrualBalanceSummaryReportViewController = ReportBaseViewController.extend( {

	_required_files: ['APIAccrualBalanceSummaryReport', 'APIAccrualPolicyAccount', 'APIAccrual', 'APIAccrualPolicy'],

	initReport: function( options ) {
		this.script_name = 'AccrualBalanceSummaryReport';
		this.viewId = 'AccrualBalanceSummaryReport';
		this.context_menu_name = $.i18n._( 'Accrual Balance Summary' );
		this.navigation_label = $.i18n._( 'Saved Report' ) + ':';
		this.view_file = 'AccrualBalanceSummaryReportView.html';
		this.api = new (APIFactory.getAPIClass( 'APIAccrualBalanceSummaryReport' ))();
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

		return [menu];

	},

	getFormValues: function() {

		var other = {};

		other.page_orientation = this.current_edit_record.page_orientation;
		other.font_size = this.current_edit_record.font_size;
		other.auto_refresh = this.current_edit_record.auto_refresh;
		other.disable_grand_total = this.current_edit_record.disable_grand_total;
		other.maximum_page_limit = this.current_edit_record.maximum_page_limit;
		other.show_duplicate_values = this.current_edit_record.show_duplicate_values;
		other.accrual_policy_account_id = this.current_edit_record.accrual_policy_account_id;

		if ( this.current_saved_report && Global.isSet( this.current_saved_report.name ) ) {
			other.report_name = _.escape( this.current_saved_report.name );
			other.report_description = this.current_saved_report.description;
		}

		return other;
	}

} );