KPIGroupViewController = BaseTreeViewController.extend( {
	el: '#kpi_group_view_container',

	_required_files: ['APIKPIGroup'],

	tree_mode: null,
	grid_table_name: null,
	grid_select_id_array: null,

	init: function( options ) {
		//this._super('initialize', options );
		this.edit_view_tpl = 'KPIGroupEditView.html';
		this.permission_id = 'kpi';
		this.viewId = 'KPIGroup';
		this.script_name = 'KPIGroupView';
		this.table_name_key = 'kpi_group';
		this.context_menu_name = $.i18n._( 'KPI Groups' );
		this.grid_table_name = $.i18n._( 'KPI Groups' );
		this.navigation_label = $.i18n._( 'KPI Group' ) + ':';

		this.tree_mode = true;
		this.primary_tab_label = $.i18n._( 'KPI Group' );
		this.primary_tab_key = 'tab_kpi_group';

		this.api = new (APIFactory.getAPIClass( 'APIKPIGroup' ))();
		this.invisible_context_menu_dic[ContextMenuIconName.copy] = true; //Hide some context menus
		this.invisible_context_menu_dic[ContextMenuIconName.mass_edit] = true;
		this.invisible_context_menu_dic[ContextMenuIconName.delete_and_next] = true;
		this.invisible_context_menu_dic[ContextMenuIconName.save_and_continue] = true;
		this.invisible_context_menu_dic[ContextMenuIconName.save_and_next] = true;
		this.invisible_context_menu_dic[ContextMenuIconName.export_excel] = true;
		this.grid_select_id_array = [];

		this.render();
		this.buildContextMenu();
		this.initData();
		this.setSelectRibbonMenuIfNecessary();
	}
} );