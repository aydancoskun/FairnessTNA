RequestViewCommonController = BaseViewController.extend( {

	authorization_history:null,
	selected_absence_policy_record: null,

	setGridCellBackGround: function() {
		var data = this.grid.getGridParam( 'data' );

		//Error: TypeError: data is undefined in /interface/html5/framework/jquery.min.js?v=7.4.6-20141027-074127 line 2 > eval line 70
		if ( !data ) {
			return;
		}

		var len = data.length;

		for ( var i = 0; i < len; i++ ) {
			var item = data[i];

			if ( item.status_id === 30 ) {
				$( "tr#" + item.id ).addClass( 'bolder-request' );
			}
		}
	},

	onCancelClick: function( force, cancel_all ) {
		var $this = this;
		if ( this.current_edit_record.id ) {
			var $record_id = this.current_edit_record.id;
		}

		LocalCacheData.current_doing_context_action = 'cancel';
		if ( this.is_changed && !force ) {
			TAlertManager.showConfirmAlert( Global.modify_alert_message, null, function( flag ) {

				if ( flag === true ) {
					doNext();
				}

			} );
		} else {
			doNext();
		}

		function doNext() {
			if ( !$this.edit_view && $this.parent_view_controller && $this.sub_view_mode ) {
				$this.parent_view_controller.is_changed = false;
				$this.parent_view_controller.buildContextMenu( true );
				$this.parent_view_controller.onCancelClick();

			} else {
				if ( $this.is_edit && $record_id ) {
					$this.setCurrentEditViewState('view')
					$this.onViewClick( $record_id, true );
				} else {
					$this.removeEditView();
				}

			}

		}

	},

	openEditView: function() {
		if ( !this.edit_view ) {
			this.initEditViewUI(this.viewId, this.edit_view_tpl);
		}
	},

	buildDataForAPI: function( data ) {
		if ( this.viewId == 'RequestAuthorization' && (!data.request_schedule_id || data.request_schedule_id <= 0) ) {
			return data;
		}

		var user_id = LocalCacheData.loginUser.id;
		if ( Global.isSet(this.current_edit_record.user_id) ) {
			user_id = this.current_edit_record.user_id;
		}
		var data_for_api = { 'user_id': user_id };
		var request_schedule = {};

		var request_schedule_keys = '';

		var afn = this.getAdvancedFieldNames();

		for ( var key in this.current_edit_record ) {
			if ( key == 'start_date' && this.edit_view_ui_dic[key] ) {
				data_for_api.date_stamp = this.edit_view_ui_dic[key].getValue();
			}

			if ( afn.indexOf(key) > -1 ) {
				if ( key == 'request_schedule_id' ) {
					request_schedule['id'] = this.current_edit_record.request_schedule_id;
				} else if ( key == 'request_schedule_status_id' ) {
					//this case is for when asking for default data
					request_schedule['status_id'] = this.edit_view_ui_dic.request_schedule_status_id.getValue();
				} else if ( this.edit_view_ui_dic[key] ) {
					request_schedule[key] = this.edit_view_ui_dic[key].getValue();
				}
			} else if (key == 'available_balance' || key == 'job_item_quick_search' || key == 'job_quick_search') {
				//ignore. these fields do not need to be saved and break the insert sql.
			} else {
				data_for_api[key] = this.current_edit_record[key];
			}
		}
		data_for_api['status_id'] = 30; //manually set pending status
		return data_for_api;
	},

	buildDataFromAPI: function (data) {
		if ( Global.isSet(data) && Global.isSet(data.request_schedule) ) {
			for ( var key in data.request_schedule ) {
				if ( key == 'id' ) {
					data['request_schedule_id'] = data.request_schedule.id;
				} else if ( key == 'status_id' ) {
					data['request_schedule_status_id'] = data.request_schedule.status_id;
				} else if ( typeof(data[key]) == 'undefined' ) {
					data[key] = data.request_schedule[key];
				} else {
					//Debug.Text('Not overwriting: '+key+' request_schedule: '+data.request_schedule[key]+' request: '+data[key], 'RequestViewCommonController.js', 'RequestViewCommonController','buildDataFromAPI' ,10)
				}

			}
			delete data.request_schedule;
			this.pre_request_schedule = false;
		} else {
			this.pre_request_schedule = true;
		}

		var retval = $.extend(this.current_edit_record, data);
		return retval;
	},

	showAdvancedFields: function() {
		this.edit_view_ui_dic.date_stamp.parents('.edit-view-form-item-div').show();
		this.hideAdvancedFields();
	},

	hideAdvancedFields: function() {
		var advanced_field_names = this.getAdvancedFieldNames();
		if ( this.edit_view_ui_dic ) {
			for ( var i=0; i < advanced_field_names.length; i++ ) {
				if ( this.edit_view_ui_dic[advanced_field_names[i]] ) {
					this.edit_view_ui_dic[advanced_field_names[i]].parents('.edit-view-form-item-div').hide();
				}
			}
			if ( this.edit_view_ui_dic.date ) {
				this.edit_view_ui_dic.date.parents('.edit-view-form-item-div').show();
			}
		}
	},

	getAdvancedFieldNames: function(){
		return [
			'request_id',
			'request_schedule_status_id',
			'request_schedule_id',
			'start_date',
			'end_date',

			'sun',
			'mon',
			'tue',
			'wed',
			'thu',
			'fri',
			'sat',

			'start_time',
			'end_time',
			'total_time',

			'schedule_policy_id',
			'absence_policy_id',
			'branch_id',
			'department_id',
			'job_id',
			'job_item_id',

			'schedule_policy',
			'absence_policy',
			'branch',
			'department',
			'job',
			'job_item',
			'available_balance'
		];
	},

	getScheduleTotalTime: function() {
		if ( this.edit_view_ui_dic.total_time ) {
			this.edit_view_ui_dic.total_time.parents('.edit-view-form-item-div').hide();
		}
		this.onAvailableBalanceChange();
	},

	onWorkingStatusChanged: function() {
	},

	showAbsencePolicyField: function(type_id, request_schedule_status_id, ui_field){
		if (request_schedule_status_id == 20 && ( type_id == 30 || type_id == 40)) {
			ui_field.parents('.edit-view-form-item-div').show();
			if ((this.viewId == 'Request' && this.is_viewing) == false) {
				this.onAvailableBalanceChange();
			}
		} else{
			ui_field.parents('.edit-view-form-item-div').hide();
			this.edit_view_ui_dic.available_balance.parents('.edit-view-form-item-div').hide();
		}
	},

	onDateStampChanged: function(){
	},

	onStartDateChanged: function(){
		this.edit_view_ui_dic.date_stamp.setValue(this.current_edit_record.start_date);
		this.current_edit_record.date_stamp = this.current_edit_record.start_date;
	},

	getAvailableBalance: function() {
		if ( this.is_viewing && this.viewId != 'RequestAuthorization' ) {
			return;
		}

		if ( ((this.viewId == 'Request' && !this.is_viewing) || this.viewId == 'RequestAuthorization' ) &&
			this.current_edit_record.request_schedule_id &&
			this.current_edit_record.request_schedule_id == 20 &&
			this.current_edit_record.absence_policy_id &&
			this.current_edit_record.absence_policy_id > 0 &&
			LocalCacheData.loginUser.id &&
			this.current_edit_record.total_time &&
			this.current_edit_record.total_time != '00:00' &&
			this.current_edit_record.start_date ) {

			var days = 1;
			if ( this.current_edit_record.start_date != this.current_edit_record.end_date ) {
				var days = Global.getDaysInSpan(this.current_edit_record.start_date , this.current_edit_record.end_date, this.current_edit_record.sun, this.current_edit_record.mon, this.current_edit_record.tue, this.current_edit_record.wed, this.current_edit_record.thu, this.current_edit_record.fri, this.current_edit_record.sat  );
			}

			var $this = this;
			var user_id = this.current_edit_record.user_id;
			var total_time = this.current_edit_record.total_time * days;
			var date_stamp = this.current_edit_record.date_stamp;

			this.api_absence_policy.getProjectedAbsencePolicyBalance(
				this.current_edit_record.absence_policy_id,
				user_id,
				date_stamp,
				total_time,
				this.pre_total_time, {
					onResult: function (result) {
						$this.getBalanceHandler(result, date_stamp);
						if ( result && $this.selected_absence_policy_record ) {
							$this.edit_view_ui_dic.available_balance.parents('.edit-view-form-item-div').show();
						} else {
							$this.edit_view_ui_dic.available_balance.parents('.edit-view-form-item-div').hide();
						}
					}
				}
			);
		} else if ( this.current_edit_record.absence_policy_id == 0 ) {
			if (this.edit_view_ui_dic.available_balance) {
				this.edit_view_ui_dic.available_balance.parents('.edit-view-form-item-div').hide();
			}
		}
	},


	getFilterColumnsFromDisplayColumns: function( authorization_history ) {
		// Error: Unable to get property 'getGridParam' of undefined or null reference
		var display_columns = [];
		if ( authorization_history ) {
			if ( this.authorization_history.authorization_history_grid ) {
				display_columns = AuthorizationHistory.getAuthorizationHistoryDefaultDisplayColumns();
			}
		} else {
			if ( this.grid ) {
				display_columns = this.grid.getGridParam( 'colModel' );
			}
		}
		var column_filter = {};
		column_filter.is_owner = true;
		column_filter.id = true;
		column_filter.is_child = true;
		column_filter.in_use = true;
		column_filter.first_name = true;
		column_filter.last_name = true;
		column_filter.user_id = true;
		column_filter.status_id = true;

		if ( display_columns ) {
			var len = display_columns.length;

			for ( var i = 0; i < len; i++ ) {
				var column_info = display_columns[i];
				column_filter[column_info.name] = true;
			}
		}

		return column_filter;
	},

	jobUIValidate: function() {
		//use punch permission section rather than schedule permission section as that's what they can see when they're creating punches
		if ( PermissionManager.validate( 'job', 'enabled' ) &&
			PermissionManager.validate( 'punch', 'edit_job' ) ) {
			return true;
		}
		return false;
	},

	jobItemUIValidate: function() {
		//use punch permission section rather than schedule permission section as that's what they can see when they're creating punches
		if ( PermissionManager.validate( 'punch', 'edit_job_item' ) ) {
			return true;
		}
		return false;
	},

	branchUIValidate: function() {
		//use punch permission section rather than schedule permission section as that's what they can see when they're creating punches
		if ( PermissionManager.validate( "punch", 'edit_branch' ) ) {
			return true;
		}
		return false;
	},

	departmentUIValidate: function() {
		//use punch permission section rather than schedule permission section as that's what they can see when they're creating punches
		if ( PermissionManager.validate( "punch", 'edit_department' ) ) {
			return true;
		}
		return false;
	},

	onViewClick: function( editId, clear_edit_view ) {
		var $this = this;
		this.setCurrentEditViewState( 'view' );

		var filter = {};
		var grid_selected_id_array = this.getGridSelectIdArray();
		var grid_selected_length = grid_selected_id_array.length;

		if ( Global.isSet( editId ) ) {
			var selectedId = editId
		} else {
			if ( grid_selected_length > 0 ) {
				selectedId = grid_selected_id_array[0];
			} else {
				return;
			}
		}

		filter.filter_data = {};
		filter.filter_data.id = [selectedId];
		this.api['get' + this.api.key_name]( filter, {
			onResult: function( result ) {
				if (clear_edit_view) {
					//Clear the edit view without removing it.
					$this.clearEditView();
				}
				var result_data = result.getResult();
				if ( !result_data ) {
					result_data = [];
				}

				result_data = result_data[0];
				$this.current_edit_record = $this.buildDataFromAPI(result_data);
				$this.current_edit_record.total_time = Global.secondToHHMMSS( $this.current_edit_record.total_time );

				$this.openEditView();
				if ( !result_data ) {
					TAlertManager.showAlert( $.i18n._( 'Record does not exist' ) );
					$this.onCancelClick();
					return;
				}

				if ( Global.isSet($this.current_edit_record.start_date) ) {
					$this.edit_view_tab.find( '#tab_request' ).find( '.third-column' ).show();
				}

				$this.initEditView();
				$this.initViewingView();
				$this.navigation.setValue(result_data);

				//This line is required to avoid problems with the absence policy box not showing properly on initial load.
				$this.onWorkingStatusChanged();

				EmbeddedMessage.init( $this.current_edit_record.id, 50, $this, $this.edit_view, $this.edit_view_tab, $this.edit_view_ui_dic, function(){
					$this.authorization_history = AuthorizationHistory.init($this);
				} );
			}
		} );


	},

	/**
	 * This function exists because the edit form is not actually an edit mode form, so we need to do some
	 * stuff differently in view mode than in edit mode.
	 */
	initViewingView: function(){
		this.showAdvancedFields();
	},

	initEditViewUI: function( view_id, edit_view_file_name ) {
		var $this = this;

		if ( this.edit_view ) {
			this.edit_view.remove();
		}

		this.edit_view = $( Global.loadViewSource( view_id, edit_view_file_name, null, true ) );
		this.edit_view_tab = $( this.edit_view.find( '.edit-view-tab-bar' ) );

		//Give edt view tab a id, so we can load it when put right click menu on it
		this.edit_view_tab.attr( 'id', this.ui_id + '_edit_view_tab' );

		this.setTabOVisibility( false );

		this.edit_view_tab = this.edit_view_tab.tabs( {show: function( e, ui ) {
			$this.onTabShow( e, ui );
		}} );

		this.edit_view_tab.bind( 'tabsselect', function( e, ui ) {
			$this.onTabIndexChange( e, ui );
		} );

		Global.contentContainer().append( this.edit_view );
		this.initRightClickMenu( RightClickMenuType.EDITVIEW );

		if ( this.is_viewing ) {
			LocalCacheData.current_doing_context_action = 'view';
			this.buildViewUI();
		} else if ( this.is_edit ) {
			LocalCacheData.current_doing_context_action = 'edit';
			this.buildEditViewUI();
		}

		$this.setEditViewTabHeight();
	},

	onEditClick: function( editId, noRefreshUI ) {
		this.is_viewing = false;
		this.is_edit = true;
		this.is_add = false;
		this.initEditViewUI(this.viewId, this.edit_view_tpl);
		LocalCacheData.current_doing_context_action = 'edit';
		this.initEditView();
		//Clear last sent message body value.
		this.edit_view_ui_dic.body.setValue('');
	},

	buildViewUI: function() {
		this._super('buildEditViewUI');

		var $this = this;

		this.setTabLabels({
			'tab_request': $.i18n._('Request'),
			'tab_audit': $.i18n._('Audit')
		});

		this.navigation.AComboBox({
			api_class: (APIFactory.getAPIClass('APIRequest')),
			id: this.script_name + '_navigation',
			allow_multiple_selection: false,
			layout_name: ALayoutIDs.REQUESRT,
			navigation_mode: true,
			show_search_inputs: true
		});

		this.setNavigation();

		//Tab 0 first column start
		var tab_request = this.edit_view_tab.find('#tab_request');
		var tab_request_column1 = tab_request.find('.first-column');
		this.edit_view_tabs[0] = [];
		this.edit_view_tabs[0].push(tab_request_column1);

		// Employee
		var form_item_input = Global.loadWidgetByName(FormItemType.TEXT);
		form_item_input.TText({field: 'full_name'});
		this.addEditFieldToColumn($.i18n._('Employee'), form_item_input, tab_request_column1);

		// Type
		var form_item_input = Global.loadWidgetByName(FormItemType.TEXT);
		form_item_input.TText({field: 'type', set_empty: false});
		this.addEditFieldToColumn($.i18n._('Type'), form_item_input, tab_request_column1);

		// Date
		var form_item_input = Global.loadWidgetByName(FormItemType.TEXT);
		form_item_input.TText({field: 'date_stamp'});
		this.addEditFieldToColumn($.i18n._('Date'), form_item_input, tab_request_column1);

		EmbeddedMessage.initUI(this, tab_request);
	},


	setAbsencePolicyFilter: function( filter ) {
		if ( !filter.filter_data ) {
			filter.filter_data = {};
		}
		filter.filter_data.user_id = this.current_edit_record.user_id;

		if ( filter.filter_columns ) {
			filter.filter_columns.absence_policy = true;
		}
		return filter;
	},

	needShowNavigation: function() {
		if ( this.is_viewing && this.current_edit_record && Global.isSet( this.current_edit_record.id ) && this.current_edit_record.id ) {
			return true;
		} else {
			return false;
		}
	},

	// needShowNavigation: function() {
	// 	if ( this.is_viewing ) {
	// 		return this._super( 'needShowNavigation', [] );
	// 	} else {
	// 		return false;
	// 	}
	// },

	onNavigationClick: function( iconName ) {

		var $this = this;
		var filter;
		var temp_filter;
		var grid_selected_id_array;
		var grid_selected_length;

		var selectedId;
		/* jshint ignore:start */
		switch ( iconName ) {
			case ContextMenuIconName.timesheet:
				filter = {filter_data: {}};
				if ( Global.isSet( this.current_edit_record ) ) {

					filter.user_id = this.current_edit_record.user_id ? this.current_edit_record.user_id : LocalCacheData.loginUser.id;
					filter.base_date = this.current_edit_record.date_stamp;

					Global.addViewTab( $this.viewId, 'Authorization - Request', window.location.href );
					IndexViewController.goToView( 'TimeSheet', filter );

				} else {
					temp_filter = {};
					grid_selected_id_array = this.getGridSelectIdArray();
					grid_selected_length = grid_selected_id_array.length;

					if ( grid_selected_length > 0 ) {
						selectedId = grid_selected_id_array[0];

						temp_filter.filter_data = {};
						temp_filter.filter_columns = {user_id: true, date_stamp: true};
						temp_filter.filter_data.id = [selectedId];

						this.api['get' + this.api.key_name]( temp_filter, {onResult: function( result ) {
							var result_data = result.getResult();

							if ( !result_data ) {
								result_data = [];
							}

							result_data = result_data[0];

							filter.user_id = result_data.user_id;
							filter.base_date = result_data.date_stamp;
							Global.addViewTab( $this.viewId, 'Authorization - Request', window.location.href );
							IndexViewController.goToView( 'TimeSheet', filter );

						}} );
					}

				}

				break;

			case ContextMenuIconName.edit_employee:
				filter = {filter_data: {}};
				if ( Global.isSet( this.current_edit_record ) ) {
					IndexViewController.openEditView( this, 'Employee', this.current_edit_record.user_id ? this.current_edit_record.user_id : LocalCacheData.loginUser.id );
				} else {
					temp_filter = {};
					grid_selected_id_array = this.getGridSelectIdArray();
					grid_selected_length = grid_selected_id_array.length;

					if ( grid_selected_length > 0 ) {
						selectedId = grid_selected_id_array[0];

						temp_filter.filter_data = {};
						temp_filter.filter_columns = {user_id: true};
						temp_filter.filter_data.id = [selectedId];

						this.api['get' + this.api.key_name]( temp_filter, {onResult: function( result ) {
							var result_data = result.getResult();

							if ( !result_data ) {
								result_data = [];
							}

							result_data = result_data[0];

							IndexViewController.openEditView( $this, 'Employee', result_data.user_id );

						}} );
					}

				}
				break;
			case ContextMenuIconName.schedule:

				filter = {filter_data: {}};

				var include_users = null;

				if ( Global.isSet( this.current_edit_record ) ) {

					include_users = [this.current_edit_record.user_id ? this.current_edit_record.user_id : LocalCacheData.loginUser.id];
					filter.filter_data.include_user_ids = {value: include_users };
					filter.select_date = this.current_edit_record.date_stamp;

					Global.addViewTab( $this.viewId, 'Authorization - Request', window.location.href );
					IndexViewController.goToView( 'Schedule', filter );

				} else {
					temp_filter = {};
					grid_selected_id_array = this.getGridSelectIdArray();
					grid_selected_length = grid_selected_id_array.length;

					if ( grid_selected_length > 0 ) {
						selectedId = grid_selected_id_array[0];

						temp_filter.filter_data = {};
						temp_filter.filter_columns = {user_id: true, date_stamp: true};
						temp_filter.filter_data.id = [selectedId];

						this.api['get' + this.api.key_name]( temp_filter, {onResult: function( result ) {
							var result_data = result.getResult();

							if ( !result_data ) {
								result_data = [];
							}

							result_data = result_data[0];

							include_users = [result_data.user_id];

							filter.filter_data.include_user_ids = include_users;
							filter.select_date = result_data.date_stamp;

							Global.addViewTab( $this.viewId, 'Authorization - Request', window.location.href );
							IndexViewController.goToView( 'Schedule', filter );

						}} );
					}

				}
				break;
		}

		/* jshint ignore:end */
	},

	initPermission: function(){
		// this._super( 'initPermission' );
		if ( PermissionManager.validate( this.permission_id, 'view' ) || PermissionManager.validate( this.permission_id, 'view_child' ) ) {
			this.show_search_tab = true;
		} else {
			this.show_search_tab = false;
		}

		if ( this.jobUIValidate() ) {
			this.show_job_ui = true;
		} else {
			this.show_job_ui = false;
		}

		if ( this.jobItemUIValidate() ) {
			this.show_job_item_ui = true;
		} else {
			this.show_job_item_ui = false;
		}

		if ( this.branchUIValidate() ) {
			this.show_branch_ui = true;
		} else {
			this.show_branch_ui = false;
		}

		if ( this.departmentUIValidate() ) {
			this.show_department_ui = true;
		} else {
			this.show_department_ui = false;
		}

		// Error: Uncaught TypeError: (intermediate value).isBranchAndDepartmentAndJobAndJobItemEnabled is not a function on line 207
		var company_api = new (APIFactory.getAPIClass( 'APICompany' ))();
		if ( company_api && _.isFunction( company_api.isBranchAndDepartmentAndJobAndJobItemEnabled ) ) {
			result = company_api.isBranchAndDepartmentAndJobAndJobItemEnabled( {async: false} ).getResult();
		}

		if ( !result ) {
			this.show_branch_ui = false;
			this.show_department_ui = false;
			this.show_job_ui = false;
			this.show_job_item_ui = false;
		} else {
			if ( !result.branch ) {
				this.show_branch_ui = false;
			}

			if ( !result.department ) {
				this.show_department_ui = false;
			}

			if ( !result.job ) {
				this.show_job_ui = false;
			}

			if ( !result.job_item ) {
				this.show_job_item_ui = false;
			}
		}

	},

	setEditMenuEditIcon: function( context_btn, pId ) {
		if ( !this.editPermissionValidate( pId )  ) {
			context_btn.addClass( 'invisible-image' );
		}

		if ( !this.editOwnerOrChildPermissionValidate( pId ) || this.is_add || this.is_edit ) {
			context_btn.addClass( 'disable-image' );
		}
	},

	// Creates the record shipped to the API at setMesssage
	uniformMessageVariable: function(records){
		var msg = {};

		msg.subject = this.edit_view_ui_dic['subject'].getValue();
		msg.body = this.edit_view_ui_dic['body'].getValue();
		msg.object_id = this.current_edit_record['id'];
		msg.object_type_id = 50;

		return msg;
	}

} );
