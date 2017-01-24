RequestViewController = RequestViewCommonController.extend({
    el: '#request_view_container',
    type_array: null,
    status_array: null,

    api_request_schedule: null,
    authorization_api: null,
    navigation: null,
    hierarchy_type_id: false,
    messages: null,


    initialize: function (options) {
        this._super('initialize', options);
        this.edit_view_tpl = 'RequestEditView.html';
        this.permission_id = 'request';
        this.viewId = 'Request';
        this.script_name = 'RequestView';
        this.table_name_key = 'request';
        this.context_menu_name = $.i18n._('Requests');
        this.navigation_label = $.i18n._('Request') + ':';
        this.api = new (APIFactory.getAPIClass('APIRequest'))();
        this.api_absence_policy = new (APIFactory.getAPIClass('APIAbsencePolicy'))();
        this.api_schedule = new (APIFactory.getAPIClass('APISchedule'))();
        this.message_control_api = new (APIFactory.getAPIClass('APIMessageControl'))();

        this.authorization_api = new (APIFactory.getAPIClass('APIAuthorization'))();

        this.invisible_context_menu_dic[ContextMenuIconName.mass_edit] = true;
        this.invisible_context_menu_dic[ContextMenuIconName.copy] = true;
        this.invisible_context_menu_dic[ContextMenuIconName.copy_as_new] = true;
        this.invisible_context_menu_dic[ContextMenuIconName.save] = true;
        this.invisible_context_menu_dic[ContextMenuIconName.save_and_continue] = true;
        this.invisible_context_menu_dic[ContextMenuIconName.save_and_next] = true;
        this.invisible_context_menu_dic[ContextMenuIconName.save_and_copy] = true;
        this.invisible_context_menu_dic[ContextMenuIconName.save_and_new] = true;


        this.initPermission();
        this.render();
        this.buildContextMenu();

        this.initData();
        this.setSelectRibbonMenuIfNecessary();
    },

    // override allows a callback after initOptions when run as sub view (from EmployeeViewController)
    initOptions: function (callBack) {

        var options = [
            {option_name: 'status'},
            {option_name: 'type'},
        ];

        this.initDropDownOptions(options, function (result) {

            if (callBack) {
                callBack(result); // First to initialize drop down options, and then to initialize edit view UI.
            }

        });

    },

    buildNavigation: function () {
        // var pager_data = this.navigation && this.navigation.getPagerData && this.navigation.getPagerData();
        // var source_data = this.navigation && this.navigation.getSourceData && this.navigation.getSourceData();
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
    },

    buildEditViewUI: function () {

        this._super('buildEditViewUI');

        this.setTabLabels({
            'tab_request': $.i18n._('Message'),
            'tab_audit': $.i18n._('Audit')
        });

        var tab_audit_label = this.edit_view.find('a[ref=tab_audit]');

        tab_audit_label.css('display', 'none');

        //Tab 0 start

        var tab_request = this.edit_view_tab.find('#tab_request');

        var tab_request_column1 = tab_request.find('.first-column');

        var tab_request_column2 = tab_request.find('.second-column');

        this.edit_view_tabs[0] = [];

        this.edit_view_tabs[0].push(tab_request_column1);

        // Subject
        var form_item_input = Global.loadWidgetByName(FormItemType.TEXT_INPUT);

        form_item_input.TTextInput({field: 'subject', width: 359});
        this.addEditFieldToColumn($.i18n._('Subject'), form_item_input, tab_request_column1, '');

        // Body
        form_item_input = Global.loadWidgetByName(FormItemType.TEXT_AREA);

        form_item_input.TTextArea({field: 'body', width: 600, height: 400});

        this.addEditFieldToColumn($.i18n._('Body'), form_item_input, tab_request_column1, '', null, null, true);

        tab_request_column2.css('display', 'none');
    },

    buildAddViewUI: function () {
        this._super('buildEditViewUI');
        var $this = this;

        this.setTabLabels({
            'tab_request': $.i18n._('Request'),
            'tab_audit': $.i18n._('Audit')
        });

        //Tab 0 start

        var tab_request = this.edit_view_tab.find('#tab_request');
        var tab_request_column1 = tab_request.find('.first-column');
        var tab_request_column2 = tab_request.find('.second-column');

        this.edit_view_tabs[0] = [];
        this.edit_view_tabs[0].push(tab_request_column1);
        this.edit_view_tabs[0].push(tab_request_column2);

        // Employee
        var form_item_input = Global.loadWidgetByName(FormItemType.TEXT);
        form_item_input.TText({field: 'full_name'});
        this.addEditFieldToColumn($.i18n._('Employee'), form_item_input, tab_request_column1, '');

        // Type
        form_item_input = Global.loadWidgetByName(FormItemType.COMBO_BOX);
        form_item_input.TComboBox({field: 'type_id', set_empty: false});
        form_item_input.setSourceData(Global.addFirstItemToArray($this.type_array));
        this.addEditFieldToColumn($.i18n._('Type'), form_item_input, tab_request_column1);

        // Date
        form_item_input = Global.loadWidgetByName(FormItemType.DATE_PICKER);
        form_item_input.TDatePicker({field: 'date_stamp'});
        var widgetContainer = $("<div class='widget-h-box'></div>");
        var label = $("<span class='widget-right-label'> " + $.i18n._('(Use the first or only date affected by this request)') + "</span>");
        widgetContainer.append(form_item_input);
        widgetContainer.append(label);
        this.addEditFieldToColumn($.i18n._('Date'), form_item_input, tab_request_column1, '', widgetContainer);


        // Message
        form_item_input = Global.loadWidgetByName(FormItemType.TEXT_AREA);
        form_item_input.TTextArea({field: 'message', width: 400, height: 300});
        this.addEditFieldToColumn($.i18n._('Reason / Message'), form_item_input, tab_request_column1, '', null, null, true);

        //hide initially hidden fields.
        //tab_request_column2.css( 'display', 'none' );

        this.onTypeChanged();
        this.onWorkingStatusChanged();
    },

    buildSearchFields: function () {
        this._super('buildSearchFields');
        this.search_fields = [

            new SearchField({
                label: $.i18n._('Employee'),
                in_column: 1,
                field: 'user_id',
                layout_name: ALayoutIDs.USER,
                api_class: (APIFactory.getAPIClass('APIUser')),
                multiple: true,
                basic_search: true,
                adv_search: false,
                form_item_type: FormItemType.AWESOME_BOX
            }),

            new SearchField({
                label: $.i18n._('Type'),
                in_column: 1,
                field: 'type_id',
                multiple: true,
                basic_search: true,
                adv_search: false,
                layout_name: ALayoutIDs.OPTION_COLUMN,
                form_item_type: FormItemType.AWESOME_BOX
            }),

            new SearchField({
                label: $.i18n._('Start Date'),
                in_column: 1,
                field: 'start_date',
                basic_search: true,
                adv_search: false,
                form_item_type: FormItemType.DATE_PICKER
            }),

            new SearchField({
                label: $.i18n._('End Date'),
                in_column: 1,
                field: 'end_date',
                basic_search: true,
                adv_search: false,
                form_item_type: FormItemType.DATE_PICKER
            }),

            new SearchField({
                label: $.i18n._('Status'),
                in_column: 2,
                field: 'request_schedule_status_id',
                multiple: true,
                basic_search: true,
                adv_search: false,
                layout_name: ALayoutIDs.OPTION_COLUMN,
                form_item_type: FormItemType.AWESOME_BOX
            }),

            new SearchField({
                label: $.i18n._('Created By'),
                in_column: 2,
                field: 'created_by',
                layout_name: ALayoutIDs.USER,
                api_class: (APIFactory.getAPIClass('APIUser')),
                multiple: true,
                basic_search: true,
                adv_search: false,
                form_item_type: FormItemType.AWESOME_BOX
            }),

            new SearchField({
                label: $.i18n._('Updated By'),
                in_column: 2,
                field: 'updated_by',
                layout_name: ALayoutIDs.USER,
                api_class: (APIFactory.getAPIClass('APIUser')),
                multiple: true,
                basic_search: true,
                adv_search: false,
                form_item_type: FormItemType.AWESOME_BOX
            })
        ];
    },

    buildContextMenuModels: function () {
        //Context Menu
        var menu = new RibbonMenu({
            label: this.context_menu_name,
            id: this.viewId + 'ContextMenu',
            sub_menu_groups: []
        });

        //menu group
        var editor_group = new RibbonSubMenuGroup({
            label: $.i18n._('Editor'),
            id: this.viewId + 'Editor',
            ribbon_menu: menu,
            sub_menus: []
        });

        //menu group
        var navigation_group = new RibbonSubMenuGroup({
            label: $.i18n._('Navigation'),
            id: this.viewId + 'navigation',
            ribbon_menu: menu,
            sub_menus: []
        });

        //menu group
        var other_group = new RibbonSubMenuGroup({
            label: $.i18n._('Other'),
            id: this.viewId + 'other',
            ribbon_menu: menu,
            sub_menus: []
        });

        var add = new RibbonSubMenu({
            label: $.i18n._('New'),
            id: ContextMenuIconName.add,
            group: editor_group,
            icon: Icons.new_add,
            permission_result: true,
            permission: null
        });

        var view = new RibbonSubMenu({
            label: $.i18n._('View'),
            id: ContextMenuIconName.view,
            group: editor_group,
            icon: Icons.view,
            permission_result: true,
            permission: null
        });

        var reply = new RibbonSubMenu({
            label: $.i18n._('Reply'),
            id: ContextMenuIconName.edit,
            group: editor_group,
            icon: Icons.edit,
            permission_result: true,
            permission: null
        });

        var del = new RibbonSubMenu({
            label: $.i18n._('Delete'),
            id: ContextMenuIconName.delete_icon,
            group: editor_group,
            icon: Icons.delete_icon,
            permission_result: true,
            permission: null
        });

        var delAndNext = new RibbonSubMenu({
            label: $.i18n._('Delete<br>& Next'),
            id: ContextMenuIconName.delete_and_next,
            group: editor_group,
            icon: Icons.delete_and_next,
            permission_result: true,
            permission: null
        });

        var send = new RibbonSubMenu({
            label: $.i18n._('Send'),
            id: ContextMenuIconName.send,
            group: editor_group,
            icon: Icons.send,
            permission_result: true,
            permission: null
        });

        var cancel = new RibbonSubMenu({
            label: $.i18n._('Cancel'),
            id: ContextMenuIconName.cancel,
            group: editor_group,
            icon: Icons.cancel,
            permission_result: true,
            permission: null
        });

        var timesheet = new RibbonSubMenu({
            label: $.i18n._('TimeSheet'),
            id: ContextMenuIconName.timesheet,
            group: navigation_group,
            icon: Icons.timesheet,
            permission_result: true,
            permission: null
        });

        var schedule_view = new RibbonSubMenu({
            label: $.i18n._('Schedule'),
            id: ContextMenuIconName.schedule,
            group: navigation_group,
            icon: Icons.schedule,
            permission_result: true,
            permission: null
        });

        var employee = new RibbonSubMenu({
            label: $.i18n._('Edit<br>Employee'),
            id: ContextMenuIconName.edit_employee,
            group: navigation_group,
            icon: Icons.employee,
            permission_result: true,
            permission: null
        });

        var export_csv = new RibbonSubMenu({
            label: $.i18n._('Export'),
            id: ContextMenuIconName.export_excel,
            group: other_group,
            icon: Icons.export_excel,
            permission_result: true,
            permission: null,
            sort_order: 9000
        });

        return [menu];

    },

    setCurrentEditRecordData: function (current_edit_record) {
        if (current_edit_record) {
            this.current_edit_record = current_edit_record;
        }

        if (!this.current_edit_record) {
            this.current_edit_record = {};
        }
        //Set current edit record data to all widgets
        for (var key in this.current_edit_record) {
            if (!this.current_edit_record.hasOwnProperty(key)) {
                continue;
            }
            var widget = this.edit_view_ui_dic[key];
            if (Global.isSet(widget)) {
                switch (key) {
                    case 'full_name':
                        if (this.is_add) {
                            widget.setValue(LocalCacheData.loginUser.first_name + ' ' + LocalCacheData.loginUser.last_name);
                        } else if (this.is_viewing) {
                            widget.setValue(this.current_edit_record['first_name'] + ' ' + this.current_edit_record['last_name']);
                        }
                        break;
                    case 'subject':
                        // Error: Uncaught TypeError: Cannot read property '0' of null in /interface/html5/#!m=Request&a=view&id=13185&tab=Request line 505
                        if (this.is_edit && this.messages) {
                            widget.setValue('Re: ' + this.messages[0].subject);
                        } else if (this.is_viewing) {
                            widget.setValue(this.current_edit_record[key]);
                        }
                        break;
                    default:
                        widget.setValue(this.current_edit_record[key]);
                        break;
                }

            }
        }

        this.collectUIDataToCurrentEditRecord();
        this.setEditViewDataDone();
    },

    setEditViewDataDone: function () {
        var $this = this;
        this._super('setEditViewDataDone');
        if (this.is_viewing) {


        } else {
            if (Global.isSet($this.messages)) {
                $this.messages = null;
            }
        }

    },


    initViewingView: function () {
        this._super('initViewingView');
        if (this.edit_view_ui_dic.message) {
            this.edit_view_ui_dic.message.parents('.edit-view-form-item-div').hide();
        }
    },

    setURL: function () {

        if (LocalCacheData.current_doing_context_action === 'edit') {
            LocalCacheData.current_doing_context_action = '';
            return;
        }

        this._super('setURL');
    },

    setDefaultMenu: function (doNotSetFocus) {

        //Error: Uncaught TypeError: Cannot read property 'length' of undefined in /interface/html5/#!m=Employee&a=edit&id=42411&tab=Wage line 282
        if (!this.context_menu_array) {
            return;
        }

        if (!Global.isSet(doNotSetFocus) || !doNotSetFocus) {
            this.selectContextMenu();
        }

        this.setTotalDisplaySpan();

        var len = this.context_menu_array.length;

        var grid_selected_id_array = this.getGridSelectIdArray();

        var grid_selected_length = grid_selected_id_array.length;

        for (var i = 0; i < len; i++) {
            var context_btn = this.context_menu_array[i];
            var id = $(context_btn.find('.ribbon-sub-menu-icon')).attr('id');

            context_btn.removeClass('disable-image');
            context_btn.removeClass('invisible-image');

            switch (id) {
                case ContextMenuIconName.add:
                    this.setDefaultMenuAddIcon(context_btn, grid_selected_length);
                    break;
                case ContextMenuIconName.view:
                    this.setDefaultMenuViewIcon(context_btn, grid_selected_length);
                    break;
                case ContextMenuIconName.edit:
                    this.setDefaultMenuEditIcon(context_btn, grid_selected_length, 'request');
                    break;
                case ContextMenuIconName.delete_icon:
                    this.setDefaultMenuDeleteIcon(context_btn, grid_selected_length);
                    break;
                case ContextMenuIconName.delete_and_next:
                    this.setDefaultMenuDeleteAndNextIcon(context_btn, grid_selected_length);
                    break;
                case ContextMenuIconName.send:
                    this.setDefaultMenuSendIcon(context_btn, grid_selected_length);
                    break;
                case ContextMenuIconName.cancel:
                    this.setDefaultMenuCancelIcon(context_btn, grid_selected_length);
                    break;
                case ContextMenuIconName.timesheet:
                    this.setDefaultMenuViewIcon(context_btn, grid_selected_length, 'punch');
                    break;
                case ContextMenuIconName.schedule:
                    this.setDefaultMenuViewIcon(context_btn, grid_selected_length, 'schedule');
                    break;
                case ContextMenuIconName.edit_employee:
                    this.setDefaultMenuEditEmployeeIcon(context_btn, grid_selected_length, 'user');
                    break;
                case ContextMenuIconName.export_excel:
                    this.setDefaultMenuExportIcon(context_btn, grid_selected_length);
                    break;

            }

        }

        this.setContextMenuGroupVisibility();

    },

    setEditMenu: function () {
        this.selectContextMenu();
        var len = this.context_menu_array.length;
        for (var i = 0; i < len; i++) {
            var context_btn = this.context_menu_array[i];
            var id = $(context_btn.find('.ribbon-sub-menu-icon')).attr('id');
            context_btn.removeClass('disable-image');

            switch (id) {
                case ContextMenuIconName.add:
                    this.setEditMenuAddIcon(context_btn);
                    break;
                case ContextMenuIconName.view:
                    this.setEditMenuViewIcon(context_btn);
                    break;
                case ContextMenuIconName.edit:
                    this.setEditMenuEditIcon(context_btn, 'request');
                    break;
                case ContextMenuIconName.delete_icon:
                    this.setEditMenuDeleteIcon(context_btn);
                    break;
                case ContextMenuIconName.delete_and_next:
                    this.setEditMenuDeleteAndNextIcon(context_btn);
                    break;
                case ContextMenuIconName.send:
                    this.setEditMenuSendIcon(context_btn);
                    break;
                case ContextMenuIconName.cancel:
                    break;
                case ContextMenuIconName.timesheet:
                    this.setEditMenuNavViewIcon(context_btn, 'punch');
                    break;
                case ContextMenuIconName.schedule:
                    this.setEditMenuNavViewIcon(context_btn, 'schedule');
                    break;
                case ContextMenuIconName.edit_employee:
                    this.setEditMenuNavEditIcon(context_btn, 'user');
                    break;
                case ContextMenuIconName.export_excel:
                    this.setDefaultMenuExportIcon(context_btn);
                    break;
            }

        }

        this.setContextMenuGroupVisibility();

    },

    setDefaultMenuCancelIcon: function (context_btn, grid_selected_length, pId) {
        if (!this.sub_view_mode && !this.is_viewing) {
            context_btn.addClass('disable-image');
        }
    },

    setDefaultMenuExportIcon: function (context_btn, grid_selected_length, pId) {
        if (this.edit_only_mode || this.grid == undefined) {
            context_btn.addClass('invisible-image');
        } else {
            if (this.is_viewing || this.is_edit || this.is_add) {
                context_btn.addClass('disable-image');
            }
        }
    },

    setEditMenuAddIcon: function (context_btn, pId) {
        this._super('setEditMenuAddIcon', context_btn, pId);

        if (this.is_edit || this.is_add) {
            context_btn.addClass('disable-image');
        }

    },

    setEditMenuDeleteAndNextIcon: function (context_btn, pId) {
        if (this.is_edit || this.is_add) {
            context_btn.addClass('disable-image');
        }
    },

    setEditMenuNavViewIcon: function (context_btn, pId) {
        if (this.is_edit || this.is_add) {
            context_btn.addClass('disable-image');
        }
    },

    setEditMenuViewIcon: function (context_btn, pId) {
        context_btn.addClass('disable-image');
    },

    setEditMenuDeleteIcon: function (context_btn, pId) {
        if (this.is_edit || this.is_add) {
            context_btn.addClass('disable-image');
        }
    },

    setEditMenuSendIcon: function (context_btn, pId) {
        if (((pId && !this.addPermissionValidate(pId)) || this.edit_only_mode) && !this.is_add) {
            context_btn.addClass('invisible-image');
        }

        if (!this.is_edit) {
            context_btn.addClass('disable-image');
        }

        if (this.is_add) {
            context_btn.removeClass('disable-image');
        }

    },

    onTypeChanged: function (arg) {
        this.hideAdvancedFields();
        if (this.edit_view_ui_dic.date_stamp) {
            this.edit_view_ui_dic.date_stamp.parents('.edit-view-form-item-div').show();
        }
        this.onWorkingStatusChanged();
    },

    setRequestFormDefaultData: function (data, callback_function) {
    },

    onAvailableBalanceChange: function () {
    },

    //post hook for onSaveResult
    onSaveDone: function (result) {
        if (this.is_edit) {
            this.onViewClick(this.current_edit_record.id);
            return false
        } else {
            return true;
        }
    },

    uniformVariable: function (records) {
        if (typeof records === 'object') {
            records.user_id = LocalCacheData.loginUser.id;
            records.first_name = LocalCacheData.loginUser.first_name;
            records.last_name = LocalCacheData.loginUser.last_name;
        }

        if (this.is_add) {
            records = this.buildDataForAPI(records);
        } else if (this.is_edit) {
            var msg = this.uniformMessageVariable(records);

            if (records && records.request_schedule) {
                msg.request_schedule = records.request_schedule;
            }

            return msg;
        }

        return records;
    },

    onSaveClick: function (ignoreWarning) {
        var $this = this;
        LocalCacheData.current_doing_context_action = 'save';
        if (!Global.isSet(ignoreWarning)) {
            ignoreWarning = false;
        }

        for (key in $this.current_edit_record) {
            if ($this.edit_view_ui_dic[key] != undefined) {
                $this.current_edit_record[key] = $this.edit_view_ui_dic[key].getValue();
            }
        }

        if (this.is_add) {
            // //format data as expected by API
            record = this.uniformVariable($this.current_edit_record);

            this.api['set' + this.api.key_name](record, false, ignoreWarning, {
                onResult: function (result) {
                    $this.onSaveResult(result);
                }
            });
        } else if (this.is_edit) {
            var record = {};
            this.is_add = false;
            this.setCurrentEditRecordData();
            record = this.uniformVariable(this.current_edit_record);
            EmbeddedMessage.reply([record], ignoreWarning, function (result) {
                    if (result.isValid()) {
                        var id = $this.current_edit_record.id;
                        //$this.removeEditView();
                        $this.onViewClick(id, true);
                    } else {
                        $this.setErrorTips(result);
                        $this.setErrorMenu();
                    }
                }
            );
        }
    },

    search: function (set_default_menu, page_action, page_number, callBack) {
        this.refresh_id = 0;
        this._super('search', set_default_menu, page_action, page_number, callBack)
    },

    setDefaultMenuEditIcon: function (context_btn, grid_selected_length, pId) {
        if (!this.editPermissionValidate(pId) || this.edit_only_mode) {
            context_btn.addClass('invisible-image');
        }
        context_btn.addClass('disable-image');
    },

    setDefaultMenuScheduleIcon: function (context_btn, grid_selected_length, pId) {
        if (!PermissionManager.checkTopLevelPermission('Schedule') || this.edit_only_mode) {
            context_btn.addClass('invisible-image');
        }

        if (grid_selected_length === 1) {
            context_btn.removeClass('disable-image');
        } else {
            context_btn.addClass('disable-image');
        }
    },

    setDefaultMenuSendIcon: function (context_btn, grid_selected_length, pId) {
        if (!this.addPermissionValidate(pId) || this.edit_only_mode) {
            context_btn.addClass('invisible-image');
        }

        context_btn.addClass('disable-image');
    },

    setDefaultMenuEditEmployeeIcon: function (context_btn, grid_selected_length) {
        if (!this.editChildPermissionValidate('user')) {
            context_btn.addClass('invisible-image');
        }

        if (grid_selected_length === 1) {
            context_btn.removeClass('disable-image');
        } else {
            context_btn.addClass('disable-image');
        }
    },

    onContextMenuClick: function (context_btn, menu_name) {
        if (Global.isSet(menu_name)) {
            var id = menu_name;
        } else {
            context_btn = $(context_btn);

            id = $(context_btn.find('.ribbon-sub-menu-icon')).attr('id');

            if (context_btn.hasClass('disable-image')) {
                return;
            }
        }

        switch (id) {
            case ContextMenuIconName.add:
                ProgressBar.showOverlay();
                this.onAddClick();
                break;
            case ContextMenuIconName.view:
                ProgressBar.showOverlay();
                this.onViewClick();
                break;
            case ContextMenuIconName.edit:
                ProgressBar.showOverlay();
                this.onEditClick();
                break;
            case ContextMenuIconName.delete_icon:
                ProgressBar.showOverlay();
                this.onDeleteClick();
                if (this.edit_view) {
                    this.buildNavigation();
                }
                break;
            case ContextMenuIconName.delete_and_next:
                ProgressBar.showOverlay();
                this.onDeleteAndNextClick();
                break;
            case ContextMenuIconName.send:
                ProgressBar.showOverlay();
                this.onSaveClick();
                break;
            case ContextMenuIconName.cancel:
                this.onCancelClick();
                break;
            case ContextMenuIconName.timesheet:
            case ContextMenuIconName.schedule:
            case ContextMenuIconName.edit_employee:
            case ContextMenuIconName.export_excel:
                this.onNavigationClick(id);
                break;

        }
    },

    setEditViewWidgetsMode: function () {
        var did_clean = false;
        for (var key in this.edit_view_ui_dic) {
            var widget = this.edit_view_ui_dic[key];
            widget.css('opacity', 1);
            var column = widget.parent().parent().parent();
            if (!column.hasClass('v-box')) {
                if (!did_clean) {
                    did_clean = true;
                }
            }
            if (this.is_viewing) {
                if (Global.isSet(widget.setEnabled)) {
                    widget.setEnabled(false);
                }
            } else {
                if (Global.isSet(widget.setEnabled)) {
                    widget.setEnabled(true);
                }
            }
        }
    },

    onFormItemChange: function (target, doNotValidate) {
        var $this = this;
        this.collectUIDataToCurrentEditRecord();
        this.setIsChanged(target);
        this.setMassEditingFieldsWhenFormChange(target);
        var key = target.getField();
        var c_value = target.getValue();
        this.current_edit_record[key] = c_value;
        var needs_callback = false;

        switch (key) {
            case 'job_id':
                break;
            case 'job_item_id':
                break;
            case 'job_quick_search':
            case 'job_item_quick_search':
                break;
            case 'type_id':
                doNotValidate = true;
                this.onTypeChanged();
                break;
            case 'date_stamp':
                this.onDateStampChanged();
                break;
            case 'request_schedule_status_id':
                this.onWorkingStatusChanged();
                break;
            case 'start_date':
                this.onStartDateChanged();
                this.onAvailableBalanceChange();
                this.setRequestFormDefaultData(null, function () {
                    finishFormItemChange()
                });
                needs_callback = true;
                this.current_edit_record.start_date = this.edit_view_ui_dic.start_date.getValue();
                this.current_edit_record.date_stamp = this.edit_view_ui_dic.start_date.getValue();
                break;
            case 'end_date':
                this.onAvailableBalanceChange();
                this.setRequestFormDefaultData(null, function () {
                    finishFormItemChange()
                });
                needs_callback = true;
                this.current_edit_record.end_date = this.edit_view_ui_dic.end_date.getValue();
                break;
            case 'start_time':
            case 'end_time':
            case 'sun':
            case 'tue':
            case 'wed':
            case 'thu':
            case 'fri':
            case 'sat':
                this.getScheduleTotalTime();
                break;
            case'absence_policy_id':
                this.selected_absence_policy_record = this.edit_view_ui_dic.absence_policy_id.getValue();
                this.onAvailableBalanceChange();
                break;
        }

        if (!needs_callback) {
            finishFormItemChange();
        }

        function finishFormItemChange() {
            if (key === 'date_stamp' ||
                key === 'start_date_stamps' ||
                key === 'start_date' ||
                key === 'end_date' ||
                key === 'start_date_stamp' ||
                key === 'start_time' ||
                key === 'end_time' ||
                key === 'schedule_policy_id' ||
                key === 'absence_policy_id') {

                if ($this.current_edit_record['date_stamp'] !== '' &&
                    $this.current_edit_record['start_time'] !== '' &&
                    $this.current_edit_record['end_time'] !== '') {

                    $this.getScheduleTotalTime();
                } else {
                    $this.onAvailableBalanceChange();
                }

            }

            if (!doNotValidate) {
                $this.validate();
            }
            $this.setEditMenu();
        }
    },

    validate: function () {
        var $this = this;
        var record = this.current_edit_record;
        record = this.uniformVariable(record);
        var api = this.message_control_api;
        if (this.is_add) {
            record = this.buildDataForAPI(record);
            api = this.api;
        }

        api['validate' + api.key_name](record, {
            onResult: function (result) {
                $this.validateResult(result);
            }
        });

    },

    onAddClick: function (data) {
        var $this = this;
        if (this.edit_view) {
            this.removeEditView();
        }
        this.setCurrentEditViewState('new');
        this.openEditView();
        this.buildAddViewUI();
        //Error: Uncaught TypeError: undefined is not a function in /interface/html5/views/BaseViewController.js?v=8.0.0-20141117-111140 line 897
        if ($this.api && typeof $this.api['get' + $this.api.key_name + 'DefaultData'] === 'function') {
            $this.api['get' + $this.api.key_name + 'DefaultData']({
                onResult: function (result) {
                    if (data) {
                        //data passed should overwrite the default data from the API.
                        result = $.extend({}, result.getResult(), data);
                    }
                    $this.onAddResult(result);
                    $this.onDateStampChanged();
                    if (result.type_id) {
                        $this.onTypeChanged();
                    }
                    $this.getScheduleTotalTime();
                }
            });
        }
    },

    //To be called only by external scripts creating requests (timesheet and schedule at this time)
    openAddView: function (data_array) {
        this.sub_view_mode = true;
        this.edit_only_mode = true;
        var $this = this;
        this.initOptions(function () {
            $this.onAddClick(data_array);
        });
    },

});