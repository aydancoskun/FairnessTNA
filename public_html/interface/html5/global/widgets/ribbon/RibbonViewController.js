RibbonViewController = Backbone.View.extend({

    el: '#ribbon_view_container', //Must set el here and can only set string, so events can work
    user_api: null,

    subMenuNavMap: null,

    initialize: function (options) {

        this.render();
        TopMenuManager.ribbon_view_controller = this;

    },

    onMenuSelect: function (e, ui) {
        if (TopMenuManager.selected_menu_id && TopMenuManager.selected_menu_id.indexOf('ContextMenu') >= 0) {
            $('.context-menu-active').removeClass('context-menu-active');
        }
        TopMenuManager.selected_menu_id = $(ui.tab).attr('ref');
        if (TopMenuManager.selected_menu_id && TopMenuManager.selected_menu_id.indexOf('ContextMenu') >= 0) {
            $(ui.tab).parent().addClass('context-menu-active');
        }
    },

    onSubMenuNavClick: function (target, id) {
        var $this = this;
        var sub_menu = this.subMenuNavMap[id];
        if (LocalCacheData.openRibbonNaviMenu) {

            if (LocalCacheData.openRibbonNaviMenu.attr('id') === 'sub_nav' + id) {
                LocalCacheData.openRibbonNaviMenu.close();
                return;
            } else {
                LocalCacheData.openRibbonNaviMenu.close();
            }
        }
        showNavItems();
        function showNavItems() {
            var items = sub_menu.get('items');
            var box = $("<ul id='sub_nav" + id + "' class='ribbon-sub-menu-nav'> </ul>");
            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                var item_node = $("<li class='ribbon-sub-menu-nav-item' id='" + item.get('id') + "'><span class='label'>" + item.get('label') + "</span></li>")
                box.append(item_node);

                item_node.unbind('click').click(function () {

                    var id = $(this).attr('id');
                    $this.onReportMenuClick(id)
                });
            }
            box = box.RibbonSubMenuNavWidget();
            LocalCacheData.openRibbonNaviMenu = box;
            $(target).append(box)
        }

    },
    onReportMenuClick: function (id) {
        IndexViewController.openReport(LocalCacheData.current_open_primary_controller, id);
    },

    //FIXME: Stops punch inout from being able to exit via the menu system except on items with dropdowns
    //Does not trigger on Report menu items with dropdowns (see the right event)
    onSubMenuClick: function (id) {
        var $this = this;
        if ((LocalCacheData.current_open_primary_controller &&
            LocalCacheData.current_open_primary_controller.edit_view &&
            LocalCacheData.current_open_primary_controller.is_changed) ||
            (LocalCacheData.current_open_report_controller &&
            LocalCacheData.current_open_report_controller.is_changed) ||
            (LocalCacheData.current_open_edit_only_controller &&
            LocalCacheData.current_open_edit_only_controller.is_changed) ||
            (LocalCacheData.current_open_sub_controller &&
            LocalCacheData.current_open_sub_controller.edit_view &&
            LocalCacheData.current_open_sub_controller.is_changed)) {
            TAlertManager.showConfirmAlert(Global.modify_alert_message, null, function (flag) {
                if (flag === true) {
                    doNext();
                }

            });
            return;
        } else if (LocalCacheData.current_open_primary_controller &&
            LocalCacheData.current_open_primary_controller.viewId === 'TimeSheet' &&
            LocalCacheData.current_open_primary_controller.getPunchMode() === 'manual') {
            LocalCacheData.current_open_primary_controller.doNextIfNoValueChangeInManualGrid(doNext)
        } else {
            doNext();
        }

        function doNext() {
            $this.setSelectSubMenu(id);
            $this.openSelectView(id);
        }
    },

    buildRibbonMenus: function () {

        var $this = this;
        this.subMenuNavMap = {};
        var ribbon_menu_array = TopMenuManager.ribbon_menus;
        var ribbon_menu_label_node = $('.ribbonTabLabel');
        var ribbon_menu_root_node = $('.ribbon');

        var len = ribbon_menu_array.length;

        for (var i = 0; i < len; i++) {

            var ribbon_menu = ribbon_menu_array[i];

            if (ribbon_menu.get('permission_result') === false) {
                continue;
            }

            var ribbon_menu_group_array = ribbon_menu.get('sub_menu_groups');
            var ribbon_menu_ui = $('<div id="' + ribbon_menu.get('id') + '" class="ribbon-tab-out-side"><div class="ribbon-tab"><div class="ribbon-sub-menu"></div></div></div>');

            var len1 = ribbon_menu_group_array.length;
            for (var x = 0; x < len1; x++) {
                var ribbon_menu_group = ribbon_menu_group_array[x];
                var ribbon_sub_menu_array = ribbon_menu_group.get('sub_menus');
                var sub_menu_ui_nodes = $("<ul></ul>");
                var ribbon_menu_group_ui = $('<div class="menu top-ribbon-menu" ondragstart="return false;" />');

                var len2 = ribbon_sub_menu_array.length;
                for (var y = 0; y < len2; y++) {

                    var ribbon_sub_menu = ribbon_sub_menu_array[y];

                    var sub_menu_ui_node = $('<li><div class="ribbon-sub-menu-icon" id="' + ribbon_sub_menu.get('id') + '"><img src="' + ribbon_sub_menu.get('icon') + '" ><span class="ribbon-label">' + ribbon_sub_menu.get('label') + '</sapn></div></li>');

                    if (ribbon_sub_menu.get('type') === RibbonSubMenuType.NAVIGATION) {

                        if (ribbon_sub_menu.get('items').length > 0) {
                            sub_menu_ui_nodes.append(sub_menu_ui_node);
                            sub_menu_ui_node.children().eq(0).addClass('ribbon-sub-menu-nav-icon');
                            $this.subMenuNavMap[ribbon_sub_menu.get('id')] = ribbon_sub_menu;

                            sub_menu_ui_node.click(function (e) {
                                var id = $($(this).find('.ribbon-sub-menu-icon')).attr('id');
                                $this.onSubMenuNavClick(this, id);
                            });
                        }

                    } else {

                        sub_menu_ui_nodes.append(sub_menu_ui_node);

                        sub_menu_ui_node.click(function (e) {
                            var id = $($(this).find('.ribbon-sub-menu-icon')).attr('id');
                            $this.onSubMenuClick(id);
                        });
                    }

//					  sub_menu_ui_node.click( function( e ) {
//						  var id = $( $( this ).find( '.ribbon-sub-menu-icon' ) ).attr( 'id' );
//						  $this.onSubMenuClick( id );
//					  } );
                }

                //If there is any menu
                if (sub_menu_ui_nodes.children().length > 0) {
                    ribbon_menu_group_ui.append(sub_menu_ui_nodes);
                    ribbon_menu_group_ui.append($('<div class="menu-bottom"><span class="menu-bottom-span">' + ribbon_menu_group.get('label') + '</span></div>'));
                    ribbon_menu_ui.find('.ribbon-sub-menu').append(ribbon_menu_group_ui);
                }

            }

            if (ribbon_menu_ui.find('.ribbon-sub-menu').children().length > 0) {
                ribbon_menu_label_node.append($('<li><a ref="' + ribbon_menu.get('id') + '" href="#' + ribbon_menu.get('id') + '">' + ribbon_menu.get('label') + '</a></li>'));
                ribbon_menu_root_node.append(ribbon_menu_ui);
            }

        }

        this.setRibbonMenuVisibility()

    },

    setRibbonMenuVisibility: function () {
        // Set Employee tab visibility

        var tab_array = ['companyMenu', 'employeeMenu', 'payrollMenu'];

        var len = tab_array.length;

        for (var i = 0; i < len; i++) {
            var menu_id = tab_array[i];

            var tab_content = Global.topContainer().find('#' + menu_id).find('li');
            if (tab_content.length < 1) {
                var tab = Global.topContainer().find("a[ref='" + menu_id + "']");
                tab.parent().hide();
            }
        }

//		  // Set COmpany tab visibility
//		  var employee_tab_content = Global.topContainer().find('#employeeMenu ' ).find('li');
//		  if(employee_tab_content.length < 1){
//			  var employee_tab = Global.topContainer().find("a[ref='employeeMenu']" );
//			  employee_tab.parent().hide();
//		  }

    },

    render: function () {
        // Error: TypeError: $(...).tabs is not a function in /interface/html5/framework/jquery.min.js?v=8.0.6-20150417-104146 line 2 > eval line 205
        if (!this.el) {
            return;
        }

        this.buildRibbonMenus();

        $(this.el).tabs();

        $(this.el).bind('tabsselect', this.onMenuSelect);

        this.setSelectMenu(TopMenuManager.selected_menu_id);

        this.setSelectSubMenu(TopMenuManager.selected_sub_menu_id);

        if (LocalCacheData.loginData.is_application_branded) {
            $('#leftLogo').attr('src', Global.getRealImagePath('css/global/widgets/ribbon/images/logo1.png'));
        } else {
            $('#leftLogo').attr('src', Global.getRealImagePath('css/global/widgets/ribbon/images/logo.png'));
        }
        $('#rightLogo').attr('src', ServiceCaller.companyLogo + '&t=' + new Date().getTime());
        $('#leftLogo').unbind('click').bind('click', function () {
            if (LocalCacheData.current_open_primary_controller.viewId !== 'Home') {
                TopMenuManager.goToView('Home');
            } else {
                LocalCacheData.current_open_primary_controller.setDefaultMenu();
                if (LocalCacheData.current_open_edit_only_controller) {
                    LocalCacheData.current_open_edit_only_controller.onCancelClick();
                }
                if (LocalCacheData.current_open_report_controller) {
                    LocalCacheData.current_open_report_controller.removeEditView();
                }
            }
        });
    },

    setSelectMenu: function (name) {
        $(this.el).tabs({selected: name});
        TopMenuManager.selected_menu_id = name;
    },

    openSelectView: function (name) {
        switch (name) {
            case 'ImportCSV':
                IndexViewController.openWizard('ImportCSVWizard', null, function () {
                    //Error: TypeError: LocalCacheData.current_open_primary_controller.search is not a function in interface/html5/framework/jquery.min.js?v=9.0.0-20151016-110437 line 2 > eval line 248
                    if (LocalCacheData.current_open_primary_controller && typeof LocalCacheData.current_open_primary_controller.search === 'function') {
                        LocalCacheData.current_open_primary_controller.search();
                    }
                });
                break;
            case 'QuickStartWizard':
                if (!LocalCacheData.getCurrentCompany().is_setup_complete && PermissionManager.validate('user_preference', 'edit') && PermissionManager.validate('pay_period_schedule', 'add') && PermissionManager.validate('policy_group', 'edit')) {
                    IndexViewController.openWizard('QuickStartWizard');
                }
                break;
            case 'InOut':
            case 'UserDefault':
            case 'Company':
            case 'CompanyBankAccount':
            case 'LoginUserContact':
            case 'LoginUserBankAccount':
            case 'LoginUserPreference':
            case 'ChangePassword':
            case 'InvoiceConfig':
            case 'About':
                IndexViewController.openEditView(LocalCacheData.current_open_primary_controller, name);
                break;
            case 'Logout':
                this.doLogout();
                break;
            case 'PortalLogout':
                this.doPortalLogout();
                break;
            case 'AdminGuide':
                var url = 'https://github.com/aydancoskun/fairness&v=' + LocalCacheData.getLoginData().application_version
                window.open(url, '_blank');
                break;
            case 'FAQS':
                url = 'https://github.com/aydancoskun/fairness?id=faq&v=' + LocalCacheData.getLoginData().application_version
                window.open(url, '_blank');
                break;
            case 'WhatsNew':
                url = 'https://github.com/aydancoskun/fairness?id=changelog&v=' + LocalCacheData.getLoginData().application_version
                window.open(url, '_blank');
                break;
            case 'ProcessPayrollWizard':
                IndexViewController.openWizard('ProcessPayrollWizard', null, function () {
                    //Error: TypeError: LocalCacheData.current_open_primary_controller.search is not a function in interface/html5/framework/jquery.min.js?v=9.0.0-20151016-110437 line 2 > eval line 248
                    if (LocalCacheData.current_open_primary_controller && typeof LocalCacheData.current_open_primary_controller.search === 'function') {
                        LocalCacheData.current_open_primary_controller.search();
                    }
                });
                break;
            default:
                TopMenuManager.goToView(TopMenuManager.selected_sub_menu_id);
        }
    },

    setSelectSubMenu: function (name) {
        switch (name) {
            case 'InOut':
            case 'UserDefault':
            case 'Company':
            case 'CompanyBankAccount':
            case 'LoginUserContact':
            case 'LoginUserBankAccount':
            case 'ImportCSV':
            case 'QuickStartWizard':
            case 'InvoiceConfig':
            case 'LoginUserPreference':
                break;
            case 'Logout':
                break;
            case 'AdminGuide':
                break;
            case 'FAQS':
                break;
            case 'WhatsNew':
                break;
            case 'EmailHelp':
                break;
            case 'ProcessPayrollWizard':
                break;
            default:
                if (TopMenuManager.selected_sub_menu_id) {

                    try {
                        $('#' + TopMenuManager.selected_sub_menu_id).removeClass('selected-menu');
                    } catch (e) {
                        TopMenuManager.selected_sub_menu_id = '';
                        TopMenuManager.selected_menu_id = '';
                        TAlertManager.showAlert($.i18n._('Invalid view name'));
                        return;
                    }

                }

                $('#' + name).addClass('selected-menu');
                TopMenuManager.selected_sub_menu_id = name;

        }

    },

    doPortalLogout: function () {
        var current_user_api = new (APIFactory.getAPIClass('APICurrentUser'))();

        current_user_api.Logout({
            onResult: function (result) {

                $.cookie('SessionID', null, {expires: 30, path: LocalCacheData.cookie_path});
                LocalCacheData.current_open_view_id = ''; //#1528  -  Logout icon not working.
                TopMenuManager.goToView('PortalLogin');

            }
        })
    },

    doLogout: function () {
        //Don't wait for result of logout in case of slow or disconnected internet. Just clear local cookies and move on.
        var current_user_api = new (APIFactory.getAPIClass('APICurrentUser'))();
        current_user_api.Logout({
            onResult: function () {
            }
        })

        Global.setAnalyticDimensions();
        if (typeof(ga) != "undefined" && APIGlobal.pre_login_data.analytics_enabled === true) {
            ga('send', 'pageview', {'sessionControl': 'end'});
        }

        //A bare "if" wrapped around lh_inst doesn't work here for some reason.
        if (typeof(lh_inst) != "undefined") {
            //stop the update loop for live chat with support
            clearTimeout(lh_inst.timeoutStatuscheck);
        }

        Global.clearSessionCookie();
        LocalCacheData.current_open_view_id = ''; //#1528  -  Logout icon not working.
        LocalCacheData.setLoginUser(null);
        LocalCacheData.setCurrentCompany(null);
        sessionStorage.clear();
        TopMenuManager.goToView('Login');
    }
});

RibbonViewController.loadView = function () {
    Global.topContainer().css('display', 'block');
    var result = Global.loadPageSync('global/widgets/ribbon/RibbonView.html');
    var template = _.template(result);
    Global.topContainer().html(template);

}
