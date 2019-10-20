LoginUserPreferenceViewController = BaseViewController.extend( {


	_required_files: ['APIUserPreference', 'APIDate', 'APICurrentUser', 'APIUserPreference'],

	language_array: null,
	date_format_array: null,

	time_format_array: null,

	time_unit_format_array: null,

	distance_format_array: null,

	time_zone_array: null,
	start_week_day_array: null,

	schedule_icalendar_type_array: null,

	date_api: null,
	currentUser_api: null,

	user_preference_api: null,

	init: function( options ) {

		//this._super('initialize', options );

		this.permission_id = 'user_preference';
		this.viewId = 'LoginUserPreference';
		this.script_name = 'LoginUserPreferenceView';
		this.table_name_key = 'user_preference';
		this.context_menu_name = $.i18n._( 'Preferences' );
		this.api = new (APIFactory.getAPIClass( 'APIUserPreference' ))();
		this.date_api = new (APIFactory.getAPIClass( 'APIDate' ))();
		this.currentUser_api = new (APIFactory.getAPIClass( 'APICurrentUser' ))();
		this.user_preference_api = new (APIFactory.getAPIClass( 'APIUserPreference' ))();

		this.invisible_context_menu_dic[ContextMenuIconName.add] = true; //Hide some context menus
		this.invisible_context_menu_dic[ContextMenuIconName.view] = true;
		this.invisible_context_menu_dic[ContextMenuIconName.edit] = true;
		this.invisible_context_menu_dic[ContextMenuIconName.delete_icon] = true;
		this.invisible_context_menu_dic[ContextMenuIconName.delete_and_next] = true;
		this.invisible_context_menu_dic[ContextMenuIconName.save_and_next] = true;
		this.invisible_context_menu_dic[ContextMenuIconName.save_and_continue] = true;
		this.invisible_context_menu_dic[ContextMenuIconName.save_and_new] = true;
		this.invisible_context_menu_dic[ContextMenuIconName.save_and_copy] = true;
		this.invisible_context_menu_dic[ContextMenuIconName.copy_as_new] = true;
		this.invisible_context_menu_dic[ContextMenuIconName.copy] = true;
		this.invisible_context_menu_dic[ContextMenuIconName.mass_edit] = true;

		this.render();

		this.initData();

	},

	render: function() {
		this._super( 'render' );
	},

	initOptions: function( callBack ) {

		var options = [
			{ option_name: 'language', field_name: null, api: this.api },
			{ option_name: 'date_format', field_name: null, api: this.api },
			{ option_name: 'time_format', field_name: null, api: this.api },
			{ option_name: 'time_unit_format', field_name: null, api: this.api },
			{ option_name: 'distance_format', field_name: null, api: this.api },
			{ option_name: 'time_zone', field_name: null, api: this.api },
			{ option_name: 'start_week_day', field_name: null, api: this.api },
			{ option_name: 'schedule_icalendar_type', field_name: null, api: this.api },
			{ option_name: 'default_login_screen', field_name: null, api: this.api }

		];

		this.initDropDownOptions( options, function( result ) {
			if ( callBack ) {
				callBack( result ); // First to initialize drop down options, and then to initialize edit view UI.
			}
		} );

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

		var save = new RibbonSubMenu( {
			label: $.i18n._( 'Save' ),
			id: ContextMenuIconName.save,
			group: editor_group,
			icon: Icons.save,
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

	getUserPreferenceData: function( callBack ) {
		var $this = this;
		var filter = {};
		filter.filter_data = {};
		filter.filter_data.user_id = LocalCacheData.loginUser.id;

		$this.api['get' + $this.api.key_name]( filter, {
			onResult: function( result ) {

				var result_data = result.getResult();

				if ( Global.isArray( result_data ) && Global.isSet( result_data[0] ) ) {
					callBack( result_data[0] );
				} else {
					$this.api['get' + $this.api.key_name + 'DefaultData']( {
						onResult: function( newResult ) {
							var result_data = newResult.getResult();
							callBack( result_data );

						}
					} );
				}

			}
		} );
	},

	setCurrentEditRecordData: function() {


		//Set current edit record data to all widgets

		for ( var key in this.current_edit_record ) {
			var widget = this.edit_view_ui_dic[key];
			if ( Global.isSet( widget ) ) {
				switch ( key ) {
					case 'full_name':
						widget.setValue( this.current_edit_record['first_name'] + ' ' + this.current_edit_record['last_name'] );
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

	setEditViewDataDone: function() {
		this._super( 'setEditViewDataDone' );
		this.onStatusChange();
	},

	openEditView: function() {
		var $this = this;

		if ( $this.edit_only_mode ) {

			$this.initOptions( function( result ) {

				$this.getUserPreferenceData( function( result ) {

					$this.buildContextMenu();

					if ( !$this.edit_view ) {
						$this.initEditViewUI( 'LoginUserPreference', 'LoginUserPreferenceEditView.html' );
					}

					if ( !result.id ) {
						result.first_name = LocalCacheData.loginUser.first_name;
						result.last_name = LocalCacheData.loginUser.last_name;
						result.user_id = LocalCacheData.loginUser.id;
					}

					// Waiting for the API returns data to set the current edit record.
					$this.current_edit_record = result;

					$this.initEditView();

				} );

			} );

		}

	},

	onFormItemChange: function( target, doNotValidate ) {
		this.setIsChanged( target );
		this.setMassEditingFieldsWhenFormChange( target );

		var key = target.getField();
		var c_value = target.getValue();
		this.current_edit_record[key] = c_value;

		if ( key === 'schedule_icalendar_type_id' ) {
			this.onStatusChange();
		}

		if ( !doNotValidate ) {
			this.validate();
		}
	},

	onStatusChange: function() {

		if ( this.current_edit_record.schedule_icalendar_type_id == 0 ) {
			this.detachElement( 'calendar_url' );
			this.detachElement( 'schedule_icalendar_alarm1_working' );
			this.detachElement( 'schedule_icalendar_alarm2_working' );
			this.detachElement( 'schedule_icalendar_alarm1_absence' );
			this.detachElement( 'schedule_icalendar_alarm2_absence' );
			this.detachElement( 'schedule_icalendar_alarm1_modified' );
			this.detachElement( 'schedule_icalendar_alarm2_modified' );
			this.detachElement( 'shifts_scheduled_to_work' );
			this.detachElement( 'shifts_scheduled_absent' );
			this.detachElement( 'modified_shifts' );

		} else {
			this.setCalendarURL();
			this.attachElement( 'calendar_url' );
			this.attachElement( 'schedule_icalendar_alarm1_working' );
			this.attachElement( 'schedule_icalendar_alarm2_working' );
			this.attachElement( 'schedule_icalendar_alarm1_absence' );
			this.attachElement( 'schedule_icalendar_alarm2_absence' );
			this.attachElement( 'schedule_icalendar_alarm1_modified' );
			this.attachElement( 'schedule_icalendar_alarm2_modified' );
			this.attachElement( 'shifts_scheduled_to_work' );
			this.attachElement( 'shifts_scheduled_absent' );
			this.attachElement( 'modified_shifts' );
		}

		this.editFieldResize();
	},

	setCalendarURL: function( widget ) {

		if ( !Global.isSet( widget ) ) {
			widget = this.edit_view_ui_dic['calendar_url'];
		}
		this.api['getScheduleIcalendarURL']( this.current_edit_record.user_name, this.current_edit_record.schedule_icalendar_type_id, {
			onResult: function( result ) {
				var result_data = result.getResult();
				widget.setValue( ServiceCaller.rootURL + result_data );

				widget.unbind( 'click' ); // First unbind all click events, otherwise, when we change the schedule icalendar type this will trigger several times click events.

				widget.click( function() {
					window.open( widget.text() );
				} );

			}
		} );

	},

	initSubScheduleSynchronizationView: function() {
		if ( Global.getProductEdition() >= 15 ) {
			this.edit_view_tab.find( '#tab_schedule_synchronization' ).find( '.first-column' ).css( 'display', 'block' );
			this.edit_view.find( '.permission-defined-div' ).css( 'display', 'none' );
			this.buildContextMenu( true );
			this.setEditMenu();
		} else {
			this.edit_view_tab.find( '#tab_schedule_synchronization' ).find( '.first-column' ).css( 'display', 'none' );
			this.edit_view.find( '.permission-defined-div' ).css( 'display', 'block' );
			this.edit_view.find( '.permission-message' ).html( Global.getUpgradeMessage() );
		}
	},

	onSaveClick: function( ignoreWarning ) {
		var $this = this;
		var record = this.current_edit_record;
		if ( !Global.isSet( ignoreWarning ) ) {
			ignoreWarning = false;
		}
		LocalCacheData.current_doing_context_action = 'save';
		this.api['set' + this.api.key_name]( record, false, ignoreWarning, {
			onResult: function( result ) {
				if ( result.isValid() ) {
					var result_data = result.getResult();
					if ( result_data === true ) {
						$this.refresh_id = $this.current_edit_record.id;
					} else if ( TTUUID.isUUID( result_data ) && result_data != TTUUID.zero_id && result_data != TTUUID.not_exist_id ) {
						$this.refresh_id = result_data;
					}

					Global.setLanguageCookie( $this.current_edit_record.language );
					LocalCacheData.setI18nDic( null );

					Global.updateUserPreference( function() {
						window.location.reload( true );
					}, $.i18n._( 'Updating preferences, reloading' ) + '...' );


					$this.removeEditView();

					IndexViewController.setNotificationBar( 'preference' );

				} else {
					$this.setErrorTips( result );
					$this.setErrorMenu();
				}

			}
		} );
	},

	setEditMenuSaveIcon: function( context_btn, pId ) {

	},

	setErrorMenu: function() {

		var len = this.context_menu_array.length;

		for ( var i = 0; i < len; i++ ) {
			var context_btn = $( this.context_menu_array[i] );
			var id = $( context_btn.find( '.ribbon-sub-menu-icon' ) ).attr( 'id' );
			context_btn.removeClass( 'disable-image' );

			switch ( id ) {
				case ContextMenuIconName.cancel:
					break;
				default:
					context_btn.addClass( 'disable-image' );
					break;
			}

		}
	},

	buildEditViewUI: function() {
		var $this = this;
		this._super( 'buildEditViewUI' );

		var tab_model = {
			'tab_preferences': { 'label': $.i18n._( 'Preferences' ) },
			'tab_schedule_synchronization': { 'label': $.i18n._( 'Schedule Synchronization' ), 'init_callback': 'initSubScheduleSynchronizationView' },
		};
		this.setTabModel( tab_model );

		//Tab 0 start

		var tab_preferences = this.edit_view_tab.find( '#tab_preferences' );

		var tab_preferences_column1 = tab_preferences.find( '.first-column' );
		var tab_preference_column2 = tab_preferences.find( '.second-column' );

		this.edit_view_tabs[0] = [];

		this.edit_view_tabs[0].push( tab_preferences_column1 );
		this.edit_view_tabs[0].push( tab_preference_column2 );

		// Employee
		var form_item_input = Global.loadWidgetByName( FormItemType.TEXT );
		form_item_input.TText( { field: 'full_name' } );
		this.addEditFieldToColumn( $.i18n._( 'Employee' ), form_item_input, tab_preferences_column1, '' );

		// Language
		form_item_input = Global.loadWidgetByName( FormItemType.COMBO_BOX );
		form_item_input.TComboBox( { field: 'language' } );
		form_item_input.setSourceData( Global.addFirstItemToArray( $this.language_array ) );
		this.addEditFieldToColumn( $.i18n._( 'Language' ), form_item_input, tab_preferences_column1 );

		// Date Format
		form_item_input = Global.loadWidgetByName( FormItemType.COMBO_BOX );
		form_item_input.TComboBox( { field: 'date_format' } );
		form_item_input.setSourceData( Global.addFirstItemToArray( $this.date_format_array ) );
		this.addEditFieldToColumn( $.i18n._( 'Date Format' ), form_item_input, tab_preferences_column1 );

		// Time Format
		form_item_input = Global.loadWidgetByName( FormItemType.COMBO_BOX );
		form_item_input.TComboBox( { field: 'time_format' } );
		form_item_input.setSourceData( Global.addFirstItemToArray( $this.time_format_array ) );
		this.addEditFieldToColumn( $.i18n._( 'Time Format' ), form_item_input, tab_preferences_column1 );

		// Time Units
		form_item_input = Global.loadWidgetByName( FormItemType.COMBO_BOX );
		form_item_input.TComboBox( { field: 'time_unit_format' } );
		form_item_input.setSourceData( Global.addFirstItemToArray( $this.time_unit_format_array ) );
		this.addEditFieldToColumn( $.i18n._( 'Time Units' ), form_item_input, tab_preferences_column1 );

		// Distance Units
		form_item_input = Global.loadWidgetByName( FormItemType.COMBO_BOX );
		form_item_input.TComboBox( { field: 'distance_format' } );
		form_item_input.setSourceData( Global.addFirstItemToArray( $this.distance_format_array ) );
		this.addEditFieldToColumn( $.i18n._( 'Distance Units' ), form_item_input, tab_preferences_column1 );

		// Time Zone
		form_item_input = Global.loadWidgetByName( FormItemType.COMBO_BOX );
		form_item_input.TComboBox( { field: 'time_zone' } );
		form_item_input.setSourceData( Global.addFirstItemToArray( $this.time_zone_array ) );
		this.addEditFieldToColumn( $.i18n._( 'Time Zone' ), form_item_input, tab_preferences_column1 );

		// Start Weeks on
		form_item_input = Global.loadWidgetByName( FormItemType.COMBO_BOX );
		form_item_input.TComboBox( { field: 'start_week_day' } );
		form_item_input.setSourceData( Global.addFirstItemToArray( $this.start_week_day_array ) );
		this.addEditFieldToColumn( $.i18n._( 'Calendar Starts On' ), form_item_input, tab_preferences_column1 );

		// Rows per page
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT_INPUT );
		form_item_input.TTextInput( { field: 'items_per_page', width: 50 } );
		this.addEditFieldToColumn( $.i18n._( 'Rows per page' ), form_item_input, tab_preferences_column1 );

		// Default Login Screen
		form_item_input = Global.loadWidgetByName( FormItemType.COMBO_BOX );
		form_item_input.TComboBox( { field: 'default_login_screen' } );
		form_item_input.setSourceData( Global.addFirstItemToArray( $this.default_login_screen_array ) );
		this.addEditFieldToColumn( $.i18n._( 'Default Screen' ), form_item_input, tab_preferences_column1 );

		// Save TimeSheet State
		form_item_input = Global.loadWidgetByName( FormItemType.CHECKBOX );
		form_item_input.TCheckbox( { field: 'enable_save_timesheet_state' } );
		this.addEditFieldToColumn( $.i18n._( 'Save TimeSheet State' ), form_item_input, tab_preferences_column1 );

		// Automatically Show Context Menu
		form_item_input = Global.loadWidgetByName( FormItemType.CHECKBOX );
		form_item_input.TCheckbox( { field: 'enable_auto_context_menu' } );
		this.addEditFieldToColumn( $.i18n._( 'Automatically Show Context Menu' ), form_item_input, tab_preferences_column1 );

		// TODO
		// Zoom

		// Email Notifications

		form_item_input = Global.loadWidgetByName( FormItemType.SEPARATED_BOX );
		form_item_input.SeparatedBox( { label: $.i18n._( 'Email Notifications' ) } );
		this.addEditFieldToColumn( null, form_item_input, tab_preference_column2 );

		// Exceptions

		form_item_input = Global.loadWidgetByName( FormItemType.CHECKBOX );
		form_item_input.TCheckbox( { field: 'enable_email_notification_exception' } );
		this.addEditFieldToColumn( $.i18n._( 'Exceptions' ), form_item_input, tab_preference_column2 );

		// Messages

		form_item_input = Global.loadWidgetByName( FormItemType.CHECKBOX );
		form_item_input.TCheckbox( { field: 'enable_email_notification_message' } );
		this.addEditFieldToColumn( $.i18n._( 'Messages' ), form_item_input, tab_preference_column2 );

		// Pay Stubs
		form_item_input = Global.loadWidgetByName( FormItemType.CHECKBOX );
		form_item_input.TCheckbox( { field: 'enable_email_notification_pay_stub' } );
		this.addEditFieldToColumn( $.i18n._( 'Pay Stubs' ), form_item_input, tab_preference_column2 );

		// Send Notifications to Home Email

		form_item_input = Global.loadWidgetByName( FormItemType.CHECKBOX );
		form_item_input.TCheckbox( { field: 'enable_email_notification_home' } );
		this.addEditFieldToColumn( $.i18n._( 'Send Notifications to Home Email' ), form_item_input, tab_preference_column2, '' );

		//Tab 1 start

		var tab_schedule_synchronization = this.edit_view_tab.find( '#tab_schedule_synchronization' );

		var tab_schedule_synchronization_column1 = tab_schedule_synchronization.find( '.first-column' );

		this.edit_view_tabs[1] = [];

		this.edit_view_tabs[1].push( tab_schedule_synchronization_column1 );

		// Status
		form_item_input = Global.loadWidgetByName( FormItemType.COMBO_BOX );
		form_item_input.TComboBox( { field: 'schedule_icalendar_type_id' } );
		form_item_input.setSourceData( Global.addFirstItemToArray( $this.schedule_icalendar_type_array ) );
		this.addEditFieldToColumn( $.i18n._( 'Status' ), form_item_input, tab_schedule_synchronization_column1, '' );

		// Calendar URL
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT );
		form_item_input.TText( { field: 'calendar_url' } );
		form_item_input.addClass( 'link' );
		this.addEditFieldToColumn( $.i18n._( 'Calendar URL' ), form_item_input, tab_schedule_synchronization_column1, '', null, true );

		// Shifts Scheduled to Work
		form_item_input = Global.loadWidgetByName( FormItemType.SEPARATED_BOX );
		form_item_input.SeparatedBox( { label: $.i18n._( 'Shifts Scheduled to Work' ) } );
		this.addEditFieldToColumn( null, form_item_input, tab_schedule_synchronization_column1, '', null, true, false, 'shifts_scheduled_to_work' );

		// Alarm 1
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT_INPUT );
		form_item_input.TTextInput( { field: 'schedule_icalendar_alarm1_working', width: 90, need_parser_sec: true } );

		var widgetContainer = $( '<div class=\'widget-h-box\'></div>' );
		var label = $( '<span class=\'widget-right-label\'>( ' + $.i18n._( 'before schedule start time' ) + ' )</span>' );

		widgetContainer.append( form_item_input );
		widgetContainer.append( label );

		this.addEditFieldToColumn( $.i18n._( 'Alarm 1' ), form_item_input, tab_schedule_synchronization_column1, '', widgetContainer, true );

		// Alarm 2

		form_item_input = Global.loadWidgetByName( FormItemType.TEXT_INPUT );
		form_item_input.TTextInput( { field: 'schedule_icalendar_alarm2_working', width: 90, need_parser_sec: true } );

		widgetContainer = $( '<div class=\'widget-h-box\'></div>' );
		label = $( '<span class=\'widget-right-label\'>( ' + $.i18n._( 'before schedule start time' ) + ' )</span>' );

		widgetContainer.append( form_item_input );
		widgetContainer.append( label );

		this.addEditFieldToColumn( $.i18n._( 'Alarm 2' ), form_item_input, tab_schedule_synchronization_column1, '', widgetContainer, true );

		// Shifts Scheduled Absent

		form_item_input = Global.loadWidgetByName( FormItemType.SEPARATED_BOX );
		form_item_input.SeparatedBox( { label: $.i18n._( 'Shifts Scheduled Absent' ) } );
		this.addEditFieldToColumn( null, form_item_input, tab_schedule_synchronization_column1, '', null, true, false, 'shifts_scheduled_absent' );

		// Alarm 1
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT_INPUT );
		form_item_input.TTextInput( { field: 'schedule_icalendar_alarm1_absence', width: 90, need_parser_sec: true } );

		widgetContainer = $( '<div class=\'widget-h-box\'></div>' );
		label = $( '<span class=\'widget-right-label\'>( ' + $.i18n._( 'before schedule start time' ) + ' )</span>' );

		widgetContainer.append( form_item_input );
		widgetContainer.append( label );

		this.addEditFieldToColumn( $.i18n._( 'Alarm 1' ), form_item_input, tab_schedule_synchronization_column1, '', widgetContainer, true );

		// Alarm 2

		form_item_input = Global.loadWidgetByName( FormItemType.TEXT_INPUT );
		form_item_input.TTextInput( { field: 'schedule_icalendar_alarm2_absence', width: 90, need_parser_sec: true } );

		widgetContainer = $( '<div class=\'widget-h-box\'></div>' );
		label = $( '<span class=\'widget-right-label\'>( ' + $.i18n._( 'before schedule start time' ) + ' )</span>' );

		widgetContainer.append( form_item_input );
		widgetContainer.append( label );

		this.addEditFieldToColumn( $.i18n._( 'Alarm 2' ), form_item_input, tab_schedule_synchronization_column1, '', widgetContainer, true );

		// Modified Shifts

		form_item_input = Global.loadWidgetByName( FormItemType.SEPARATED_BOX );
		form_item_input.SeparatedBox( { label: $.i18n._( 'Modified Shifts' ) } );
		this.addEditFieldToColumn( null, form_item_input, tab_schedule_synchronization_column1, '', null, true, false, 'modified_shifts' );

		// Alarm 1
		form_item_input = Global.loadWidgetByName( FormItemType.TEXT_INPUT );
		form_item_input.TTextInput( { field: 'schedule_icalendar_alarm1_modified', width: 90, need_parser_sec: true } );

		widgetContainer = $( '<div class=\'widget-h-box\'></div>' );
		label = $( '<span class=\'widget-right-label\'>( ' + $.i18n._( 'before schedule start time' ) + ' )</span>' );

		widgetContainer.append( form_item_input );
		widgetContainer.append( label );

		this.addEditFieldToColumn( $.i18n._( 'Alarm 1' ), form_item_input, tab_schedule_synchronization_column1, '', widgetContainer, true );

		// Alarm 2

		form_item_input = Global.loadWidgetByName( FormItemType.TEXT_INPUT );
		form_item_input.TTextInput( { field: 'schedule_icalendar_alarm2_modified', width: 90, need_parser_sec: true } );

		widgetContainer = $( '<div class=\'widget-h-box\'></div>' );
		label = $( '<span class=\'widget-right-label\'>( ' + $.i18n._( 'before schedule start time' ) + ' )</span>' );

		widgetContainer.append( form_item_input );
		widgetContainer.append( label );

		this.addEditFieldToColumn( $.i18n._( 'Alarm 2' ), form_item_input, tab_schedule_synchronization_column1, '', widgetContainer, true );
	}

} );
