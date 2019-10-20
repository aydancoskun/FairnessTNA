var LocalCacheData = function() {

};

LocalCacheData.view_layout_cache = {};

LocalCacheData.i18nDic = null;

LocalCacheData.notification_bar = null;

LocalCacheData.ui_click_stack = [];

LocalCacheData.api_stack = [];

LocalCacheData.last_timesheet_selected_date = null;

LocalCacheData.last_timesheet_selected_user = null;

LocalCacheData.last_schedule_selected_date = null;

LocalCacheData.current_open_wizard_controller = null; // cache opened wizard conroller, only one wizard open at a time

LocalCacheData.default_filter_for_next_open_view = null;

LocalCacheData.extra_filter_for_next_open_view = null;

LocalCacheData.default_edit_id_for_next_open_edit_view = null; //First use in save report jump to report

LocalCacheData.current_open_view_id = ''; // Save current open view's id. set in BaseViewController.loadView

LocalCacheData.login_error_string = ''; //Error message show on Login Screen

LocalCacheData.all_url_args = null; //All args from URL

LocalCacheData.current_open_primary_controller = null; // Save current open view's id. set in BaseViewController.loadView

LocalCacheData.current_open_sub_controller = null; // Save current open view's id. set in BaseViewController.loadView

LocalCacheData.current_open_edit_only_controller = null; // Save current open view's id. set in BaseViewController.loadView

LocalCacheData.current_open_report_controller = null; //save open report view controller

LocalCacheData.current_doing_context_action = ''; //Save what context action is doing right now

LocalCacheData.current_select_date = ''; // Save

LocalCacheData.edit_id_for_next_open_view = '';

LocalCacheData.url_args = null;

LocalCacheData.result_cache = {};

LocalCacheData.paging_type = 10;  //0 is CLick to show more, 10 is normal paging

LocalCacheData.currentShownContextMenuName = '';

LocalCacheData.isSupportHTML5LocalCache = false;

LocalCacheData.loginData = null;

LocalCacheData.currentLanguage = 'en_us';

LocalCacheData.currentLanguageDic = {};

LocalCacheData.appType = null;

LocalCacheData.productEditionId = null;

LocalCacheData.debuger = null;

LocalCacheData.applicationName = null;

LocalCacheData.loginUser = null;

LocalCacheData.loginUserPreference = null;

LocalCacheData.openAwesomeBox = null; //To help make sure only one Awesomebox is shown at one time. Do mouse click outside job

LocalCacheData.openAwesomeBoxColumnEditor = null; //To Make sure only one column editor of Awesomebox is shown at one time Do mouse click outside job

LocalCacheData.openRibbonNaviMenu = null;

LocalCacheData.loadedWidgetCache = {};

LocalCacheData.loadedScriptNames = {}; //Save load javascript, prevent multiple load

LocalCacheData.permissionData = null;

LocalCacheData.uniqueCountryArray = null;

LocalCacheData.currentSelectMenuId = null;

LocalCacheData.currentSelectSubMenuId = null;

LocalCacheData.timesheet_sub_grid_expended_dic = {};

LocalCacheData.view_min_map = {};

LocalCacheData.view_min_tab_bar = null;

LocalCacheData.cookie_path = APIGlobal.pre_login_data.cookie_base_url;

LocalCacheData.domain_name = '';

LocalCacheData.fullUrlParameterStr = '';

LocalCacheData.PayrollRemittanceAgencyEventWizard = null;

LocalCacheData.resizeable_grids = [];

LocalCacheData.isStorageAvailable = function() {
	//Turn off sessionStorage as its not required and just slows things down anyways. We can store things in memory instead.
	// It also has space limitations which can be hit like: QuotaExceededError: DOM Exception 22: An attempt was made to add something to storage that exceeded the quota
	LocalCacheData.isSupportHTML5LocalCache = false;

	// if ( window.sessionStorage ) {
	// 	try {
	// 		//Test to make sure we can actually store some data. This should help avoid JS exceptions such as: QuotaExceededError: DOM Exception 22: An attempt was made to add something to storage that exceeded the quota
	// 		var storage = window['sessionStorage'];
	// 		var x = '__storage_test__';
	// 		storage.setItem(x, x);
	// 		storage.removeItem(x);
	//
	// 		LocalCacheData.isSupportHTML5LocalCache = true;
	// 	} catch(e) {
	// 		LocalCacheData.isSupportHTML5LocalCache = false;
	// 	}
	// } else {
	// 	LocalCacheData.isSupportHTML5LocalCache = false;
	// }
	//Debug.Text( 'Is sessionStorage available: '+ LocalCacheData.isSupportHTML5LocalCache, 'LocalCacheData.js', 'LocalCacheData', 'isStorageAvailable', 10 );

	return LocalCacheData.isSupportHTML5LocalCache;
};

LocalCacheData.isLocalCacheExists = function( key ) {
	if ( LocalCacheData.getLocalCache( key ) !== null ) {
		return true;
	}

	return false;
};

LocalCacheData.getLocalCache = function( key, format ) {
	//BUG#2066 - For testing bad cache. See getRequiredLocalCache()
	//if ( key == 'current_company' ){ return null; }
	if ( LocalCacheData[key] ) {
		return LocalCacheData[key];
	} else if ( LocalCacheData.isSupportHTML5LocalCache == true && sessionStorage[key] ) { //Fall back to sessionStorage if available and data exists.
		var result = sessionStorage.getItem( key );

		if ( result !== 'undefined' && format === 'JSON' ) {
			result = JSON.parse( result );
		}

		if ( result === 'true' ) {
			result = true;
		} else if ( result === 'false' ) {
			result = false;
		}

		LocalCacheData[key] = result;

		return LocalCacheData[key];
	}

	return null;
};

LocalCacheData.setLocalCache = function( key, val, format ) {
	if ( LocalCacheData.isSupportHTML5LocalCache ) {
		if ( format === 'JSON' ) {
			sessionStorage.setItem( key, JSON.stringify( val ) );
		} else {
			sessionStorage.setItem( key, val );
		}
	}

	LocalCacheData[key] = val; //Always set in memory as well.

	return true;
};

/**
 * BUG#2066
 * JavaScript was reporting: TypeError: Cannot read property 'product_edition_id' of null
 *
 * This appears to be caused by a person closing the browser and reopening it with a "return to where I was" option active.
 * The browser is trying to load local cache data and it may be incomplete in this scenario, which generates the error. We could not reproduce this reliably.
 * To fix it, we created LocalCacheData.getRequiredLocalCache(), and called it for mission critical cache chunks instead of LocalCacheData.getLocalCache()
 */
LocalCacheData.getRequiredLocalCache = function( key, format ) {
	var result = LocalCacheData.getLocalCache( key, format );
	if ( result == null ) {
		//There are 2 cases where result can be null.
		//  First is the cache going dead.
		//  Second is that a required local cache item is not yet loaded because most of the required data isn't set yet.
		//  In the second case we need to fail gracefully to show the error and stack trace on the console.
		try {
			Global.sendErrorReport( 'ERROR: Unable to get required local cache data: ' + key ); //Send error as soon as possible, before any data gets cleared.

			Global.Logout();
			window.location.reload();
		} catch ( e ) {
			// Early page loads won't have Global or TAlertManager
			console.debug( 'ERROR: Unable to get required local cache data: ' + key );
			console.debug( 'ERROR: Unable to report error to server: ' + key );
			console.debug( e.stack );
			if ( confirm( 'Local cache has expired. Click OK to reload.' ) ) {
				window.location.reload();
			}
		}

		return;
	}

	return result;
};

LocalCacheData.getI18nDic = function() {
	return LocalCacheData.getLocalCache( 'i18nDic', 'JSON' );
};

LocalCacheData.setI18nDic = function( val ) {

	LocalCacheData.setLocalCache( 'i18nDic', val, 'JSON' );
};

LocalCacheData.getViewMinMap = function() {
	return LocalCacheData.getLocalCache( 'viewMinMap', 'JSON' );
};

LocalCacheData.setViewMinMap = function( val ) {

	LocalCacheData.setLocalCache( 'viewMinMap', val, 'JSON' );
};

LocalCacheData.getCopyRightInfo = function() {
	return LocalCacheData.getLocalCache( 'copyRightInfo' );
};

LocalCacheData.setCopyRightInfo = function( val ) {
	LocalCacheData.setLocalCache( 'copyRightInfo', val );
};

LocalCacheData.getApplicationName = function() {
	//return LocalCacheData.getRequiredLocalCache( 'applicationName' );
	return LocalCacheData.getLoginData().application_name;
};

// LocalCacheData.setApplicationName = function( val ) {
// 	LocalCacheData.setLocalCache( 'applicationName', val );
// };

LocalCacheData.getCurrentCompany = function() {
	return LocalCacheData.getRequiredLocalCache( 'current_company', 'JSON' );
};

LocalCacheData.setCurrentCompany = function( val ) {
	LocalCacheData.setLocalCache( 'current_company', val, 'JSON' );
};

LocalCacheData.getLoginUser = function() {
	//Can't be set to required as the data is chekced for null to trigger cache load.
	//See loginViewController.onLoginSuccess()
	return LocalCacheData.getLocalCache( 'loginUser', 'JSON' );
};

LocalCacheData.getPortalLoginUser = function() {
	//Can't be set to required as the data is chekced for null to trigger cache load.
	//See loginViewController.onLoginSuccess()
	return LocalCacheData.getLocalCache( 'portalLoginUser', 'JSON' );
};

LocalCacheData.setLoginUser = function( val ) {
	LocalCacheData.setLocalCache( 'loginUser', val, 'JSON' );
};
LocalCacheData.setPunchLoginUser = function( val ) {
	LocalCacheData.setLocalCache( 'punchLoginUser', val, 'JSON' );
};

LocalCacheData.getPunchLoginUser = function() {
	return LocalCacheData.getLocalCache( 'punchLoginUser', 'JSON' );
};

LocalCacheData.setPortalLoginUser = function( val ) {
	LocalCacheData.setLocalCache( 'portalLoginUser', val, 'JSON' );
};

LocalCacheData.setPortalLoginUser = function( val ) {
	LocalCacheData.setLocalCache( 'portalLoginUser', val, 'JSON' );
};

LocalCacheData.getCurrentCurrencySymbol = function() {
	return LocalCacheData.getLocalCache( 'currentCurrencySymbol' );
};

LocalCacheData.setCurrentCurrencySymbol = function( val ) {
	LocalCacheData.setLocalCache( 'currentCurrencySymbol', val );
};

LocalCacheData.getLoginUserPreference = function() {
	return LocalCacheData.getRequiredLocalCache( 'loginUserPreference', 'JSON' );
};

LocalCacheData.setLoginUserPreference = function( val ) {
	LocalCacheData.setLocalCache( 'loginUserPreference', val, 'JSON' );
};

LocalCacheData.getPermissionData = function() {
	return LocalCacheData.getRequiredLocalCache( 'permissionData', 'JSON' );
};

LocalCacheData.setPermissionData = function( val ) {
	LocalCacheData.setLocalCache( 'permissionData', val, 'JSON' );
};

LocalCacheData.getUniqueCountryArray = function() {
	return LocalCacheData.getRequiredLocalCache( 'uniqueCountryArray', 'JSON' );
};

LocalCacheData.setUniqueCountryArray = function( val ) {
	LocalCacheData.setLocalCache( 'uniqueCountryArray', val, 'JSON' );
};

LocalCacheData.getSessionID = function() {

	var result = LocalCacheData.getLocalCache( Global.getSessionIDKey() );
	if ( !result ) {
		result = '';
	}

	return result;
};

LocalCacheData.setSessionID = function( val ) {

	LocalCacheData.setLocalCache( Global.getSessionIDKey(), val );
};

LocalCacheData.getLoginData = function() {
	return LocalCacheData.getRequiredLocalCache( 'loginData', 'JSON' );
};

LocalCacheData.setLoginData = function( val ) {

	LocalCacheData.setLocalCache( 'loginData', val, 'JSON' );
};

LocalCacheData.getCurrentSelectMenuId = function() {
	return LocalCacheData.getLocalCache( 'currentSelectMenuId' );
};

LocalCacheData.setCurrentSelectMenuId = function( val ) {

	LocalCacheData.setLocalCache( 'currentSelectMenuId', val );
};

LocalCacheData.getCurrentSelectSubMenuId = function() {
	return LocalCacheData.getLocalCache( 'currentSelectSubMenuId' );
};

LocalCacheData.setCurrentSelectSubMenuId = function( val ) {

	LocalCacheData.setLocalCache( 'currentSelectSubMenuId', val );
};

LocalCacheData.cleanNecessaryCache = function() {
	Debug.Text( 'Clearing Cache', 'LocalCacheData.js', 'LocalCacheData', 'cleanNecessaryCache', 10 );
	LocalCacheData.last_timesheet_selected_user = null;
	LocalCacheData.last_timesheet_selected_date = null;
	//JS load Optimize
	if ( LocalCacheData.loadViewRequiredJSReady ) {
		if ( typeof ALayoutCache !== 'undefined' ) {
			ALayoutCache.layout_dic = {};
		}
	}
	LocalCacheData.view_layout_cache = {};
	LocalCacheData.result_cache = {};
	if ( LocalCacheData.current_open_wizard_controller ) {
		LocalCacheData.current_open_wizard_controller.onCloseClick();
		LocalCacheData.current_open_wizard_controller = null;
	}
	Global.cleanViewTab();
};

//Check to see if local storage is actually available.
LocalCacheData.isStorageAvailable();