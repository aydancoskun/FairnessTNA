StationViewController = BaseViewController.extend( {
	el: '#station_view_container',

	_required_files: {
		10: ['APIStation', 'APIUserGroup', 'APIUserPreference', 'APIBranch', 'APIDepartment'],
		20: ['APIJob', 'APIJobItem']
	},

	user_group_api: null,
	status_array: null,
	type_array: null,

	time_zone_array: null,
	time_clock_command_array: null,
	mode_flag_array: null,
	default_mode_flag_array: null,
	poll_frequency_array: null,
	push_frequency_array: null,
	partial_push_frequency_array: null,
	group_selection_type_array: null,
	branch_selection_type_array: null,
	department_selection_type_array: null,
	user_group_array: null,

	user_preference_api: null,

	init: function( options ) {
		//this._super('initialize', options );
		this.edit_view_tpl = 'StationEditView.html';
		this.permission_id = 'station';
		this.viewId = 'Station';
		this.script_name = 'StationView';
		this.table_name_key = 'station';
		this.context_menu_name = $.i18n._( 'Station' );
		this.navigation_label = $.i18n._( 'Station' ) + ':';
		this.api = new (APIFactory.getAPIClass( 'APIStation' ))();
		this.user_group_api = new (APIFactory.getAPIClass( 'APIUserGroup' ))();
		this.user_preference_api = new (APIFactory.getAPIClass( 'APIUserPreference' ))();

		if ( ( Global.getProductEdition() >= 20 ) ) {

			this.job_api = new (APIFactory.getAPIClass( 'APIJob' ))();
			this.job_item_api = new (APIFactory.getAPIClass( 'APIJobItem' ))();

		}

		this.invisible_context_menu_dic[ContextMenuIconName.copy] = true; //Hide some context menus

		this.render();
		this.buildContextMenu();

		this.initData();
		this.setSelectRibbonMenuIfNecessary();

	},
	initOptions: function( callBack ) {

		var $this = this;

		var options = [
			{ option_name: 'status', field_name: null, api: null },
			{ option_name: 'type', field_name: null, api: null },
			{ option_name: 'time_zone', field_name: 'time_zone', api: $this.user_preference_api },
			{ option_name: 'time_clock_command', field_name: null, api: null },
			{ option_name: 'poll_frequency', field_name: null, api: null },
			{ option_name: 'push_frequency', field_name: null, api: null },
			{ option_name: 'partial_push_frequency', field_name: null, api: null },
			{ option_name: 'group_selection_type', field_name: null, api: null },
			{ option_name: 'branch_selection_type', field_name: null, api: null },
			{ option_name: 'department_selection_type', field_name: null, api: null }

		];

		this.initDropDownOptions( options, function( result ) {

			$this.user_group_api.getUserGroup( '', false, false, {
				onResult: function( res ) {

					res = res.getResult();
					res = Global.buildTreeRecord( res );
					$this.user_group_array = res;

					if ( callBack ) {
						callBack( result ); // First to initialize drop down options, and then to initialize edit view UI.
					}

				}
			} );

		} );

	},

	setCurrentEditRecordData: function() {

		// When mass editing, these fields may not be the common data, so their value will be undefined, so this will cause their change event cannot work properly.
		this.setDefaultData( {
			'type_id': 10,
			'user_group_selection_type_id': 10,
			'branch_selection_type_id': 10,
			'department_selection_type_id': 10
		} );

		for ( var key in this.current_edit_record ) {

			if ( !this.current_edit_record.hasOwnProperty( key ) ) {
				continue;
			}

			var widget = this.edit_view_ui_dic[key];
			if ( Global.isSet( widget ) ) {
				switch ( key ) {
					case 'job_id':
						if ( ( Global.getProductEdition() >= 20 ) ) {
							widget.setValue( this.current_edit_record[key] );
						}
						break;
					case 'job_item_id':
						if ( ( Global.getProductEdition() >= 20 ) ) {
							var args = {};
							args.filter_data = { job_id: this.current_edit_record.job_id };
							widget.setDefaultArgs( args );
							widget.setValue( this.current_edit_record[key] );
						}
						break;
					case 'job_quick_search':
//						widget.setValue( this.current_edit_record['job_id'] ? this.current_edit_record['job_id'] : 0 );
						break;
					case 'job_item_quick_search':
//						widget.setValue( this.current_edit_record['job_item_id'] ? this.current_edit_record['job_item_id'] : 0 );
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

	/* jshint ignore:start */
	onFormItemChange: function( target, doNotValidate ) {
		this.setIsChanged( target );
		this.setMassEditingFieldsWhenFormChange( target );
		var key = target.getField();
		var c_value = target.getValue();

		this.current_edit_record[key] = c_value;

		switch ( key ) {
			case 'type_id':
				this.onTypeChange();
				break;
			case 'job_id':
				if ( ( Global.getProductEdition() >= 20 ) ) {
					this.edit_view_ui_dic['job_quick_search'].setValue( target.getValue( true ) ? ( target.getValue( true ).manual_id ? target.getValue( true ).manual_id : '' ) : '' );
					this.setJobItemValueWhenJobChanged( target.getValue( true ), 'job_item_id', { job_id: this.current_edit_record.job_id } );
					this.edit_view_ui_dic['job_quick_search'].setCheckBox( true );
				}
				break;
			case 'job_item_id':
				if ( ( Global.getProductEdition() >= 20 ) ) {
					this.edit_view_ui_dic['job_item_quick_search'].setValue( target.getValue( true ) ? ( target.getValue( true ).manual_id ? target.getValue( true ).manual_id : '' ) : '' );
					this.edit_view_ui_dic['job_item_quick_search'].setCheckBox( true );
				}
				break;
			case 'job_quick_search':
			case 'job_item_quick_search':
				if ( ( Global.getProductEdition() >= 20 ) ) {
					this.onJobQuickSearch( key, c_value );
				}
				break;
			case 'user_group_selection_type_id':
				this.onEmployeeGroupSelectionTypeChange();
				break;
			case 'branch_selection_type_id':
				this.onBranchSelectionTypeChange();
				break;
			case 'department_selection_type_id':
				this.onDepartmentSelectionTypeChange();
				break;

		}
		this.isDisableIncludeEmployees();
		if ( !doNotValidate ) {
			this.validate();
		}

	},

	/* jshint ignore:end */
	isDisableIncludeEmployees: function() {
		if ( this.edit_view_ui_dic['group'].getEnabled() || this.edit_view_ui_dic['branch'].getEnabled() || this.edit_view_ui_dic['department'].getEnabled() ) {
			this.edit_view_ui_dic['include_user'].setEnabled( true );
		} else {
			this.edit_view_ui_dic['include_user'].setEnabled( false );
		}
	},

	onEmployeeGroupSelectionTypeChange: function() {

		if ( parseInt( this.current_edit_record['user_group_selection_type_id'] ) == 10 ) {
			this.edit_view_ui_dic['group'].setEnabled( false );
		} else {
			this.edit_view_ui_dic['user_group_selection_type_id'].setValue( this.current_edit_record['user_group_selection_type_id'] );
			this.edit_view_ui_dic['group'].setEnabled( true );
		}
	},
	onBranchSelectionTypeChange: function() {
		if ( parseInt( this.current_edit_record['branch_selection_type_id'] ) == 10 ) {

			this.edit_view_ui_dic['branch'].setEnabled( false );
		} else {
			this.edit_view_ui_dic['branch_selection_type_id'].setValue( this.current_edit_record['branch_selection_type_id'] );
			this.edit_view_ui_dic['branch'].setEnabled( true );
		}
	},
	onDepartmentSelectionTypeChange: function() {
		if ( parseInt( this.current_edit_record['department_selection_type_id'] ) == 10 ) {
			this.edit_view_ui_dic['department'].setEnabled( false );
		} else {
			this.edit_view_ui_dic['department_selection_type_id'].setValue( this.current_edit_record['department_selection_type_id'] );
			this.edit_view_ui_dic['department'].setEnabled( true );
		}
	},

	onTypeChange: function() {
		if ( parseInt( this.current_edit_record['type_id'] ) == 100 ||
				parseInt( this.current_edit_record['type_id'] ) == 150 ||
				parseInt( this.current_edit_record['type_id'] ) == 28 ||
				parseInt( this.current_edit_record['type_id'] ) == 65 ) {

			$( this.edit_view_tab.find( 'ul li' )[2] ).show();
			var tab_2_label = this.edit_view.find( 'a[ref=tab_time_clock]' );

			if ( parseInt( this.current_edit_record['type_id'] ) == 100 ||
					parseInt( this.current_edit_record['type_id'] ) == 150 ) {
				tab_2_label.text( $.i18n._( 'TimeClock' ) );

				if ( parseInt( this.current_edit_record['type_id'] ) != 150 ) {
					this.attachElement( 'manual_command' );
					this.attachElement( 'push_frequency' );
					this.attachElement( 'partial_push_frequency' );
				} else {
					this.detachElement( 'manual_command' );
					this.detachElement( 'push_frequency' );
					this.detachElement( 'partial_push_frequency' );
				}

				this.attachElement( 'password' );
				this.attachElement( 'port' );

			} else {
				tab_2_label.text( $.i18n._( 'Mobile App' ) );
				this.detachElement( 'password' );
				this.detachElement( 'port' );
				this.detachElement( 'manual_command' );
				this.detachElement( 'push_frequency' );
				this.detachElement( 'partial_push_frequency' );
			}


			this.initModeFlag();

			//#2590 - ensure field is only visible in valid types.
			if ( parseInt( this.current_edit_record['type_id'] ) == 65 ) {
				this.initDefaultModeFlag();
			} else {
				this.edit_view_ui_dic['default_mode_flag'].parents( '.edit-view-form-item-div' ).hide();
			}

		} else {
			$( this.edit_view_tab.find( 'ul li' )[2] ).hide();
			this.edit_view_tab.tabs( 'option', 'active', 0 );

		}

		this.editFieldResize();

	},

	initModeFlag: function() {
		var $this = this;
		this.api.getOptions( 'mode_flag', this.current_edit_record.type_id, true, {
			onResult: function( result ) {
				var result_data = Global.buildRecordArray( result.getResult() );

				$this.edit_view_ui_dic['mode_flag'].setSourceData( result_data );
				$this.edit_view_ui_dic['mode_flag'].setValue( $this.current_edit_record.mode_flag );

			}
		} );
	},

	initDefaultModeFlag: function() {
		var $this = this;
		this.api.getOptions( 'default_mode_flag', this.current_edit_record.type_id, true, {
			onResult: function( result ) {
				var result_data = Global.buildRecordArray( result.getResult() );

				$this.edit_view_ui_dic['default_mode_flag'].setSourceData( result_data );
				var value = ( $this.current_edit_record.default_mode_flag != 0 ) ? $this.current_edit_record.default_mode_flag : TTUUID.zero_id;
				$this.edit_view_ui_dic['default_mode_flag'].setValue( value );
				$this.edit_view_ui_dic['default_mode_flag'].parents( '.edit-view-form-item-div' ).show();

			}
		} );
	},

	setEditViewDataDone: function() {
		var $this = this;
		this._super( 'setEditViewDataDone' );

		this.onTypeChange();
		this.onEmployeeGroupSelectionTypeChange();
		this.onBranchSelectionTypeChange();
		this.onDepartmentSelectionTypeChange();
		this.isDisableIncludeEmployees();

		var runButton = this.edit_view_form_item_dic['manual_command'].find( 'button[type=\'button\']' );
		if ( $this.is_mass_editing || $this.is_viewing ) {
			this.edit_view_ui_dic['manual_command'].setEnabled( false );
			runButton.attr( 'disabled', true );
		} else {
			runButton.off( 'click' ).on( 'click', function() {
				$this.onSaveAndContinue( true );
			} );
		}

	},

	onSaveAndContinue: function( isRun ) {
		this.is_add = false;
		LocalCacheData.current_doing_context_action = 'save_and_continue';
		var $this = this;
		this.is_changed = false;
		var commandData = this.edit_view_ui_dic['manual_command'].getValue();
		var commandId = this.current_edit_record.id;
		this.api['set' + this.api.key_name]( this.current_edit_record, {
			onResult: function( result ) {
				if ( isRun ) {
					$this.api['runManualCommand']( commandData, commandId, {
						onResult: function( result_1 ) {
							if ( result_1.isValid() ) {
								var result_data = result_1.getResult();
								TAlertManager.showAlert( result_data, $.i18n._( 'Manual Command Result' ) );
								$this.onSaveAndContinueResult( result );

							} else {
								TAlertManager.showErrorAlert( result );
							}

						}
					} );
				} else {
					$this.onSaveAndContinueResult( result );
				}

			}
		} );
	},

	onSaveDone: function( result ) {
		if ( this.edit_only_mode && this.parent_view_controller ) {
			this.parent_view_controller.onEditStationDone( result );
		}
	},

	onBuildBasicUIFinished: function() {
		var station_input = this.basic_search_field_ui_dic['station_id'];

		var icon = $( '<img class="station-location" src="' + Global.getRealImagePath( 'images/location.png' ) + '">' );

		icon.insertAfter( station_input );
		icon.unbind( 'click' ).bind( 'click', function() {
			var station_id = Global.getStationID();
			if ( station_id ) {
				station_input.setValue( station_id );
			} else {
				TAlertManager.showAlert( $.i18n._( 'Current Station is not currently set.' ) );
			}
		} );

	},

	setEditMenuEditIcon: function( context_btn, pId ) {

		if ( !this.editPermissionValidate( pId ) ) {
			context_btn.addClass( 'invisible-image' );
		}

		if ( !this.is_viewing || !this.editOwnerOrChildPermissionValidate( pId ) ) {
			context_btn.addClass( 'disable-image' );
		}
	},

	onEditClick: function( editId, noRefreshUI ) {
		var $this = this;
		var grid_selected_id_array = this.getGridSelectIdArray();
		var grid_selected_length = grid_selected_id_array.length;
		if ( Global.isSet( editId ) ) {
			var selectedId = editId;
		} else {
			if ( this.is_viewing ) {
				selectedId = this.current_edit_record.id;
			} else if ( grid_selected_length > 0 ) {
				selectedId = grid_selected_id_array[0];
			} else {
				return;
			}
		}
		this.is_viewing = false;
		this.is_edit = true;
		this.is_add = false;
		LocalCacheData.current_doing_context_action = 'edit';
		if ( !this.edit_only_mode ) {
			$this.openEditView();
		} else {

		}
		var filter = {};
		filter.filter_data = {};
		filter.filter_data.id = [selectedId];
		this.api['get' + this.api.key_name]( filter, {
			onResult: function( result ) {
				var result_data = result.getResult();
				if ( !result_data ) {
					result_data = [];
				}
				result_data = result_data[0];
				if ( !result_data ) {
					TAlertManager.showAlert( $.i18n._( 'Record does not exist' ) );
					$this.onCancelClick();
					return;
				}
				if ( $this.sub_view_mode && $this.parent_key ) {
					result_data[$this.parent_key] = $this.parent_value;
				}
				$this.current_edit_record = result_data;
				$this.initEditView();

			}
		} );

	},

	openEditView: function( id ) {
		var $this = this;

		if ( $this.edit_only_mode ) {

			$this.initOptions( function( result ) {
				if ( !$this.edit_view ) {
					$this.is_viewing = true;
					$this.initEditViewUI( $this.viewId, $this.edit_view_tpl );
				}
				$this.getStationData( id, function( result ) {
					// Waiting for the (APIFactory.getAPIClass( 'API' )) returns data to set the current edit record.
					$this.current_edit_record = result;
					//if ( !$this.editPermissionValidate() || !$this.editOwnerOrChildPermissionValidate()) {
					//	$this.is_viewing = true;
					//}

					$this.initEditView();

				} );

			} );

		} else {
			if ( !this.edit_view ) {
				this.initEditViewUI( $this.viewId, $this.edit_view_tpl );
			}


		}

	},

	getStationData: function( id, callBack ) {
		var filter = {};
		filter.filter_data = {};
		filter.filter_data.id = [id];

		this.api['get' + this.api.key_name]( filter, {
			onResult: function( result ) {
				var result_data = result.getResult();

				if ( !result_data ) {
					result_data = [];
				}
				result_data = result_data[0];

				callBack( result_data );

			}
		} );

	},

	buildEditViewUI: function() {
		this._super( 'buildEditViewUI' );

		var $this = this;

		var tab_model = {
			'tab_station': { 'label': $.i18n._( 'Station' ) },
			'tab_employee_criteria': { 'label': $.i18n._( 'Employee Criteria' ) },
			'tab_time_clock': { 'label': $.i18n._( 'TimeClock' ) },
			'tab_audit': true,
		};
		this.setTabModel( tab_model );

		if ( !this.edit_only_mode ) {
			this.navigation.AComboBox( {
				api_class: (APIFactory.getAPIClass( 'APIStation' )),
				id: this.script_name + '_navigation',
				allow_multiple_selection: false,
				layout_name: ALayoutIDs.STATION,
				navigation_mode: true,
				show_search_inputs: true
			} );

			this.setNavigation();
		}

		//Tab 0 start

		var tab_station = this.edit_view_tab.find( '#tab_station' );

		var tab_station_column1 = tab_station.find( '.first-column' );

		this.edit_view_tabs[0] = [];

		this.edit_view_tabs[0].push( tab_station_column1 );

		//Status

		var form_item_input = Global.loadWidgetByName( FormItemType.COMBO_BOX );
		form_item_input.TComboBox( { field: 'status_id' } );
		form_item_input.setSourceData( Global.addFirstItemToArray( $this.status_array ) );
		this.addEditFieldToColumn( $.i18n._( 'Status' ), form_item_input, tab_station_column1, '' );

		//Type
		form_item_input = Global.loadWidgetByName( FormItemType.COMBO_BOX );
		form_item_input.TComboBox( { field: 'type_id' } );
		form_item_input.setSourceData( Global.addFirstItemToArray( $this.type_array ) );
		this.addEditFieldToColumn( $.i18n._( 'Type' ), form_item_input, tab_station_column1 );

		//Station
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT_INPUT );

		form_item_input.TTextInput( { field: 'station_id', width: 254 } );
		this.addEditFieldToColumn( $.i18n._( 'Station' ), form_item_input, tab_station_column1 );

		//Source
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT_INPUT );

		form_item_input.TTextInput( { field: 'source', width: 289 } );
		this.addEditFieldToColumn( $.i18n._( 'Source' ), form_item_input, tab_station_column1 );

		//Description
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT_INPUT );

		form_item_input.TTextInput( { field: 'description', width: '100%' } );
		this.addEditFieldToColumn( $.i18n._( 'Description' ), form_item_input, tab_station_column1 );

		form_item_input.parent().width( '45%' );

		//Default Branch

		form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

		form_item_input.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIBranch' )),
			allow_multiple_selection: false,
			layout_name: ALayoutIDs.BRANCH,
			show_search_inputs: true,
			set_empty: true,
			field: 'branch_id'
		} );
		this.addEditFieldToColumn( $.i18n._( 'Default Branch' ), form_item_input, tab_station_column1 );

		//Default Department
		form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

		form_item_input.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIDepartment' )),
			allow_multiple_selection: false,
			layout_name: ALayoutIDs.DEPARTMENT,
			show_search_inputs: true,
			set_empty: true,
			field: 'department_id'
		} );
		this.addEditFieldToColumn( $.i18n._( 'Default Department' ), form_item_input, tab_station_column1, '' );

		if ( ( Global.getProductEdition() >= 20 ) ) {
			//Job
			form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

			form_item_input.AComboBox( {
				api_class: (APIFactory.getAPIClass( 'APIJob' )),
				allow_multiple_selection: false,
				layout_name: ALayoutIDs.JOB,
				show_search_inputs: true,
				set_empty: true,
				setRealValueCallBack: (function( val ) {

					if ( val ) {
						job_coder.setValue( val.manual_id );
					}
				}),
				field: 'job_id'
			} );

			widgetContainer = $( '<div class=\'widget-h-box\'></div>' );

			var job_coder = Global.loadWidgetByName( FormItemType.TEXT_INPUT );
			job_coder.TTextInput( { field: 'job_quick_search', disable_keyup_event: true } );
			job_coder.addClass( 'job-coder' );

			widgetContainer.append( job_coder );
			widgetContainer.append( form_item_input );
			this.addEditFieldToColumn( $.i18n._( 'Default Job' ), [form_item_input, job_coder], tab_station_column1, '', widgetContainer );

			// Task
			form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

			form_item_input.AComboBox( {
				api_class: (APIFactory.getAPIClass( 'APIJobItem' )),
				allow_multiple_selection: false,
				layout_name: ALayoutIDs.JOB_ITEM,
				show_search_inputs: true,
				set_empty: true,
				setRealValueCallBack: (function( val ) {

					if ( val ) {
						job_item_coder.setValue( val.manual_id );
					}
				}),
				field: 'job_item_id'
			} );

			widgetContainer = $( '<div class=\'widget-h-box\'></div>' );

			var job_item_coder = Global.loadWidgetByName( FormItemType.TEXT_INPUT );
			job_item_coder.TTextInput( { field: 'job_item_quick_search', disable_keyup_event: true } );
			job_item_coder.addClass( 'job-coder' );

			widgetContainer.append( job_item_coder );
			widgetContainer.append( form_item_input );
			this.addEditFieldToColumn( $.i18n._( 'Default Task' ), [form_item_input, job_item_coder], tab_station_column1, 'last', widgetContainer );
		}

		//Tab 1 start

		var tab_employee_criteria = this.edit_view_tab.find( '#tab_employee_criteria' );

		var tab_employee_criteria_column1 = tab_employee_criteria.find( '.first-column' );

		this.edit_view_tabs[1] = [];

		this.edit_view_tabs[1].push( tab_employee_criteria_column1 );

		//Employee group
		var v_box = $( '<div class=\'v-box\'></div>' );

		//Selection Type
		form_item_input = Global.loadWidgetByName( FormItemType.COMBO_BOX );
		form_item_input.TComboBox( { field: 'user_group_selection_type_id' } );
		form_item_input.setSourceData( Global.addFirstItemToArray( $this.group_selection_type_array ) );

		var form_item = this.putInputToInsideFormItem( form_item_input, $.i18n._( 'Selection Type' ) );

		v_box.append( form_item );
		v_box.append( '<div class=\'clear-both-div\'></div>' );

		//Selection
		form_item_input_1 = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

		form_item_input_1.AComboBox( {
			tree_mode: true,
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.TREE_COLUMN,
			set_empty: true,
			field: 'group'
		} );
		form_item_input_1.setSourceData( Global.addFirstItemToArray( $this.user_group_array ) );

		form_item = this.putInputToInsideFormItem( form_item_input_1, $.i18n._( 'Selection' ) );

		v_box.append( form_item );

		this.addEditFieldToColumn( $.i18n._( 'Employee Groups' ), [form_item_input, form_item_input_1], tab_employee_criteria_column1, 'first', v_box, false, true );

		//Branches
		v_box = $( '<div class=\'v-box\'></div>' );
		//Selection Type
		form_item_input = Global.loadWidgetByName( FormItemType.COMBO_BOX );
		form_item_input.TComboBox( { field: 'branch_selection_type_id' } );
		form_item_input.setSourceData( Global.addFirstItemToArray( $this.branch_selection_type_array ) );

		form_item = this.putInputToInsideFormItem( form_item_input, $.i18n._( 'Selection Type' ) );

		v_box.append( form_item );
		v_box.append( '<div class=\'clear-both-div\'></div>' );

		//Selection
		var form_item_input_1 = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

		form_item_input_1.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIBranch' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.BRANCH,
			show_search_inputs: true,
			set_empty: true,
			field: 'branch'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input_1, $.i18n._( 'Selection' ) );

		v_box.append( form_item );

		this.addEditFieldToColumn( $.i18n._( 'Branches' ), [form_item_input, form_item_input_1], tab_employee_criteria_column1, '', v_box, false, true );

		// Departments
		v_box = $( '<div class=\'v-box\'></div>' );
		// Selection Type
		form_item_input = Global.loadWidgetByName( FormItemType.COMBO_BOX );
		form_item_input.TComboBox( { field: 'department_selection_type_id' } );
		form_item_input.setSourceData( Global.addFirstItemToArray( $this.department_selection_type_array ) );

		form_item = this.putInputToInsideFormItem( form_item_input, $.i18n._( 'Selection Type' ) );
		v_box.append( form_item );
		v_box.append( '<div class=\'clear-both-div\'></div>' );

		//Selection
		form_item_input_1 = Global.loadWidgetByName( FormItemType.AWESOME_BOX );

		form_item_input_1.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIDepartment' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.DEPARTMENT,
			show_search_inputs: true,
			set_empty: true,
			field: 'department'
		} );

		form_item = this.putInputToInsideFormItem( form_item_input_1, $.i18n._( 'Selection' ) );

		v_box.append( form_item );

		this.addEditFieldToColumn( $.i18n._( 'Departments' ), [form_item_input, form_item_input_1], tab_employee_criteria_column1, '', v_box, false, true );

		// Include Employees
		form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );
		form_item_input.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIUser' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.USER,
			show_search_inputs: true,
			set_empty: true,
			field: 'include_user'
		} );
		this.addEditFieldToColumn( $.i18n._( 'Include Employees' ), form_item_input, tab_employee_criteria_column1 );

		// Exclude Employees
		form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );
		form_item_input.AComboBox( {
			api_class: (APIFactory.getAPIClass( 'APIUser' )),
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.USER,
			show_search_inputs: true,
			set_empty: true,
			field: 'exclude_user'
		} );
		this.addEditFieldToColumn( $.i18n._( 'Exclude Employees' ), form_item_input, tab_employee_criteria_column1, '' );

		// Tab2 start

		var tab_time_clock = this.edit_view_tab.find( '#tab_time_clock' );
		var tab_time_clock_column1 = tab_time_clock.find( '.first-column' );
		var tab_time_clock_column2 = tab_time_clock.find( '.second-column' );

		this.edit_view_tabs[2] = [];

		this.edit_view_tabs[2].push( tab_time_clock_column1 );
		this.edit_view_tabs[2].push( tab_time_clock_column2 );

		// Password/COMM Key
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT_INPUT );

		form_item_input.TTextInput( { field: 'password', width: 254 } );
		this.addEditFieldToColumn( $.i18n._( 'Password/COMM Key' ), form_item_input, tab_time_clock_column1, '', null, true );

		// Port
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT_INPUT );

		form_item_input.TTextInput( { field: 'port', width: 254 } );
		this.addEditFieldToColumn( $.i18n._( 'Port' ), form_item_input, tab_time_clock_column1, '', null, true );

		// Force Time Zone
		form_item_input = Global.loadWidgetByName( FormItemType.COMBO_BOX );
		form_item_input.TComboBox( { field: 'time_zone', set_empty: true } );
		form_item_input.setSourceData( Global.addFirstItemToArray( $this.time_zone_array ) );
		this.addEditFieldToColumn( $.i18n._( 'Force Time Zone' ), form_item_input, tab_time_clock_column1 );

		// Enable Automatic Punch Status
		form_item_input = Global.loadWidgetByName( FormItemType.CHECKBOX );
		form_item_input.TCheckbox( { field: 'enable_auto_punch_status' } );
		this.addEditFieldToColumn( $.i18n._( 'Enable Automatic Punch Status' ), form_item_input, tab_time_clock_column1, '' );

		// Manual Command
		form_item_input = Global.loadWidgetByName( FormItemType.COMBO_BOX );
		form_item_input.TComboBox( { field: 'manual_command' } );
		form_item_input.setSourceData( Global.addFirstItemToArray( $this.time_clock_command_array ) );

		var widgetContainer = $( '<div class=\'widget-h-box\'></div>' );
		var label = $( '<button type=\'button\' class=\' t-button widget-right-label\'>' + $.i18n._( 'Run' ) + '</button>' );

		widgetContainer.append( form_item_input );
		widgetContainer.append( label );

		this.addEditFieldToColumn( $.i18n._( 'Manual Command' ), form_item_input, tab_time_clock_column2, '', widgetContainer, true );

		// Download Frequency
		form_item_input = Global.loadWidgetByName( FormItemType.COMBO_BOX );
		form_item_input.TComboBox( { field: 'poll_frequency' } );
		form_item_input.setSourceData( Global.addFirstItemToArray( $this.poll_frequency_array ) );

		widgetContainer = $( '<div class=\'widget-h-box\'></div>' );
		label = $( '<span class=\'widget-right-label\'> ' + $.i18n._( 'Last Download' ) + ': ' + ' </span>' );

		var widget_text = Global.loadWidgetByName( FormItemType.TEXT );
		widget_text.TText( { field: 'last_push_date' } );

		widgetContainer.append( form_item_input );
		widgetContainer.append( label );
		widgetContainer.append( widget_text );

		this.addEditFieldToColumn( $.i18n._( 'Download Frequency' ), [form_item_input, widget_text], tab_time_clock_column2, '', widgetContainer );

		// Full Upload Frequency
		form_item_input = Global.loadWidgetByName( FormItemType.COMBO_BOX );
		form_item_input.TComboBox( { field: 'push_frequency' } );
		form_item_input.setSourceData( Global.addFirstItemToArray( $this.push_frequency_array ) );

		widgetContainer = $( '<div class=\'widget-h-box\'></div>' );
		label = $( '<span class=\'widget-right-label\'> ' + $.i18n._( 'Last Upload' ) + ': ' + ' </span>' );

		widget_text = Global.loadWidgetByName( FormItemType.TEXT );
		widget_text.TText( { field: 'last_poll_date' } );

		widgetContainer.append( form_item_input );
		widgetContainer.append( label );
		widgetContainer.append( widget_text );
		this.addEditFieldToColumn( $.i18n._( 'Full Upload Frequency' ), [form_item_input, widget_text], tab_time_clock_column2, '', widgetContainer, true );

		// Partial Upload Frequency
		form_item_input = Global.loadWidgetByName( FormItemType.COMBO_BOX );
		form_item_input.TComboBox( { field: 'partial_push_frequency' } );
		form_item_input.setSourceData( Global.addFirstItemToArray( $this.push_frequency_array ) );

		widgetContainer = $( '<div class=\'widget-h-box\'></div>' );
		label = $( '<span class=\'widget-right-label\'> ' + $.i18n._( 'Last Upload' ) + ': </span>' );
		widget_text = Global.loadWidgetByName( FormItemType.TEXT );
		widget_text.TText( { field: 'last_partial_push_date' } );

		widgetContainer.append( form_item_input );
		widgetContainer.append( label );
		widgetContainer.append( widget_text );
		this.addEditFieldToColumn( $.i18n._( 'Partial Upload Frequency' ), [form_item_input, widget_text], tab_time_clock_column2, '', widgetContainer, true );

		// Last Downloaded Punch
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT );
		form_item_input.TText( { field: 'last_punch_time_stamp' } );
		this.addEditFieldToColumn( $.i18n._( 'Last Downloaded Punch' ), form_item_input, tab_time_clock_column2 );

		// Configuration Modes
		form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );
		form_item_input.AComboBox( {
			allow_multiple_selection: true,
			layout_name: ALayoutIDs.OPTION_COLUMN,
			show_search_inputs: true,
			set_empty: true,
			field: 'mode_flag'
		} );
		this.addEditFieldToColumn( $.i18n._( 'Configuration Modes' ), form_item_input, tab_time_clock_column2, '', null, null, true );

		// Default Punch Mode
		form_item_input = Global.loadWidgetByName( FormItemType.AWESOME_BOX );
		form_item_input.AComboBox( {
			allow_multiple_selection: false,
			layout_name: ALayoutIDs.OPTION_COLUMN,
			show_search_inputs: true,
			set_empty: true,
			field: 'default_mode_flag'
		} );
		this.addEditFieldToColumn( $.i18n._( 'Default Punch Mode' ), form_item_input, tab_time_clock_column2 ); //, '', null, null, true );
	},

	buildSearchFields: function() {

		this._super( 'buildSearchFields' );
		this.search_fields = [

			new SearchField( {
				label: $.i18n._( 'Status' ),
				in_column: 1,
				field: 'status_id',
				multiple: true,
				basic_search: true,
				adv_search: false,
				layout_name: ALayoutIDs.OPTION_COLUMN,
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
				label: $.i18n._( 'Station' ),
				in_column: 1,
				field: 'station_id',
				multiple: true,
				basic_search: true,
				adv_search: false,
				form_item_type: FormItemType.TEXT_INPUT
			} ),
			new SearchField( {
				label: $.i18n._( 'Source' ),
				in_column: 1,
				field: 'source',
				multiple: true,
				basic_search: true,
				adv_search: false,
				form_item_type: FormItemType.TEXT_INPUT
			} ),
			new SearchField( {
				label: $.i18n._( 'Description' ),
				in_column: 2,
				field: 'description',
				multiple: true,
				basic_search: true,
				adv_search: false,
				form_item_type: FormItemType.TEXT_INPUT
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
	}

} );
