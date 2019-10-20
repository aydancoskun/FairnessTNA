UserGenericStatusWindowController = BaseViewController.extend( {

	// el: '.user-generic-data-status',
	el: '', // el is set to the DOM id which is set in UserGenericStatusWindowController.open()

	batch_id: '',
	user_id: '',

	callback: null,

	events: {
		'click .done-btn': 'onCloseClick'
	},

	onCloseClick: function() {
		// UserGenericStatusWindowController.instance = null;
		$( this.el ).remove();

		if ( this.callback ) {
			this.callback();
		}

	},

	init: function( options ) {
		this.options = options;
		this.content_div = $( this.el ).find( '.content' );
		this.batch_id = this.options.batch_id;
		this.user_id = this.options.user_id;

		if ( this.options.callback ) {
			this.callback = this.options.callback;
		}

		this.api = new (APIFactory.getAPIClass( 'APIUserGenericStatus' ))();
		this.render();
		this.initData();

	},

	//Don't initOptions if edit_only_mode. Do it in sub views
	initData: function() {
		var $this = this;
		ProgressBar.showOverlay();
		this.getAllColumns( function() {
			$this.initLayout();
		} );
	},

	initLayout: function() {
		var $this = this;
		$this.getDefaultDisplayColumns( function() {
			$this.setSelectLayout();
			$this.search();

		} );
	},

	render: function() {
		var title = $( this.el ).find( '.title' );
		title.text( $.i18n._( 'Status Report' ) );

	},

	getAllColumns: function( callBack ) {

		var $this = this;
		this.api.getOptions( 'columns', {
			onResult: function( columns_result ) {
				var columns_result_data = columns_result.getResult();
				$this.all_columns = Global.buildColumnArray( columns_result_data );

				if ( callBack ) {
					callBack();
				}

			}
		} );

	},

	search: function( set_default_menu ) {

		if ( !Global.isSet( set_default_menu ) ) {
			set_default_menu = true;
		}

		var $this = this;

		var filter = {};
		filter.filter_data = {};
		filter.filter_data.batch_id = this.batch_id;
		filter.filter_items_per_page = 0; // Default to 0 to load user preference defined

		this.api['getUserGenericStatus']( filter, true, {
			onResult: function( result ) {

				var result_data = result.getResult();
				result_data = Global.formatGridData( result_data, $this.api.key_name );

				$this.grid.setData( result_data );

				$this.setGridSize();

				ProgressBar.closeOverlay(); //Add this in initData

				if ( set_default_menu ) {
					$this.setDefaultMenu( true );
				}

			}
		} );

		this.api['getUserGenericStatusCountArray']( this.user_id, this.batch_id, {
			onResult: function( result ) {

				var result_data = result.getResult();

				var failed = $( $this.el ).find( '.failed' );
				var warning = $( $this.el ).find( '.warning' );
				var success = $( $this.el ).find( '.success' );

				if ( result_data != true && result_data.status ) {
					failed.text( result_data.status[10].total + '/' + result_data.total + '( ' + result_data.status[10].percent + '% )' );
					warning.text( result_data.status[20].total + '/' + result_data.total + '( ' + result_data.status[20].percent + '% )' );
					success.text( result_data.status[30].total + '/' + result_data.total + '( ' + result_data.status[30].percent + '% )' );
				}

			}
		} );

	},

	setGridSize: function() {

	},

	setSelectLayout: function( column_start_from ) {

		var $this = this;
		var column_info_array = [];

		this.select_layout = { id: '' };
		this.select_layout.data = { filter_data: {}, filter_sort: {} };
		this.select_layout.data.display_columns = this.default_display_columns;
		var layout_data = this.select_layout.data;
		var display_columns = this.buildDisplayColumns( layout_data.display_columns );

		//Set Data Grid on List view
		var len = display_columns.length;

		if ( layout_data.display_columns.length < 1 ) {
			layout_data.display_columns = this.default_display_columns;
		}

		var start_from = 0;

		if ( Global.isSet( column_start_from ) && column_start_from > 0 ) {
			start_from = column_start_from;
		}

		for ( var i = start_from; i < len; i++ ) {
			var view_column_data = display_columns[i];

			if ( view_column_data.value === 'description' ) {
				var column_info = {
					name: view_column_data.value,
					index: view_column_data.value,
					label: view_column_data.label,
					width: 400,
					sortable: false,
					title: false
				};

			} else if ( view_column_data.value === 'status' ) {
				column_info = {
					name: view_column_data.value, index: view_column_data.value, label: view_column_data.label,
					width: 100, sortable: false, title: false, formatter: function( cell_value, related_data, row ) {

						var span = $( '<span></span>' );

						if ( cell_value === 'Failed' ) {
							span.addClass( 'failed-label' );
						} else if ( cell_value === 'Warning' ) {
							span.addClass( 'warning-label' );
						} else if ( cell_value === 'Success' ) {
							span.addClass( 'success-label' );
						}

						span.text( cell_value );
						return span.get( 0 ).outerHTML;
					}
				};

			} else {
				column_info = {
					name: view_column_data.value,
					index: view_column_data.value,
					label: view_column_data.label,
					width: 100,
					sortable: false,
					title: false
				};
			}

			column_info_array.push( column_info );
		}

		if ( this.grid ) {
			this.grid.grid.jqGrid( 'GridUnload' );
			this.grid = null;
		}

		this.grid = new TTGrid( 'user_generic_data_status_grid-'+ this.batch_id, {
			altRows: true,
			onSelectRow: $.proxy( this.onGridSelectRow, this ),
			data: [],
			rowNum: 10000,
			sortable: false,
			datatype: 'local',
			width: 600,
			colNames: [],
			viewrecords: true
		}, column_info_array );

		var content_div = $( this.el ).find( '.content' );

		this.grid.grid.setGridWidth( content_div.width() - 2 );
		this.grid.grid.setGridHeight( content_div.height() - 25 );
		$( window ).resize( function() {
			$this.grid.grid.setGridWidth( content_div.width() - 2 );
			$this.grid.grid.setGridHeight( content_div.height() - 25 );
		} );

		this.filter_data = this.select_layout.data.filter_data;
	}

} );

// UserGenericStatusWindowController.instance = null;

UserGenericStatusWindowController.open = function( batch_id, user_id, callback ) {
	Global.loadViewSource( 'UserGenericStatus', 'UserGenericStatusWindow.css' );
	Global.loadViewSource( 'UserGenericStatus', 'UserGenericStatusWindow.html', function( result ) {
		var args = {
			batch_id: batch_id,
			failed: $.i18n._( 'Failed' ),
			warning: $.i18n._( 'Warning' ),
			success: $.i18n._( 'Success' )
		};

		var template = _.template( result );
		$( 'body' ).append( template( args ) );

		//Make it global variable
		// UserGenericStatusWindowController.instance = new UserGenericStatusWindowController( {
		// 	batch_id: batch_id,
		// 	user_id: user_id,
		// 	can_cache_controller: false
		// } );
		//UserGenericStatusWindowController.instance.callback = callback;

		new UserGenericStatusWindowController( {
			el: '#'+ batch_id,
			batch_id: batch_id,
			user_id: user_id,
			can_cache_controller: false,
			callback: callback,
		} );

	} );
};