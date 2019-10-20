LoginViewController = BaseViewController.extend( {

	el: '#loginViewContainer', //Must set el here and can only set string, so events can work

	_required_files: ['APICurrentUser', 'APICurrency', 'APIUserPreference', 'APIPermission', 'APIDate', 'APIAuthentication'],

	authentication_api: null,
	currentUser_api: null,
	currency_api: null,
	user_preference_api: null,
	user_locale: 'en_US',
	is_login: true,
	date_api: null,
	permission_api: null,

	doing_login: false,

	lan_selector: null,

	init: function( options ) {
		//this._super('initialize', options );
		var $this = this;
		Global.setVirtualDeviceViewport('mobile'); // Setting mobile view on login, then back to desktop (990px virtual) after login, to allow pan & zoom, as not whole app is mobile optimized.
		this.authentication_api = new (APIFactory.getAPIClass( 'APIAuthentication' ))();
		this.currentUser_api = new (APIFactory.getAPIClass( 'APICurrentUser' ))();
		this.currency_api = new (APIFactory.getAPIClass( 'APICurrency' ))();
		this.user_preference_api = new (APIFactory.getAPIClass( 'APIUserPreference' ))();
		this.date_api = new (APIFactory.getAPIClass( 'APIDate' ))();
		this.permission_api = new (APIFactory.getAPIClass( 'APIPermission' ))();
		this.viewId = 'LoginView';
		Global.topContainer().css( 'display', 'none' );
		Global.bottomContainer().css( 'display', 'none' );
		Global.contentContainer().removeClass( 'content-container-after-login' );
		Global.topContainer().removeClass( 'top-container-after-login' );

		var login_data = LocalCacheData.getLoginData();

		//Clean cache that saved in some views
		LocalCacheData.cleanNecessaryCache();
		var session_cookie = getCookie( Global.getSessionIDKey() );
		if ( session_cookie && session_cookie.length >= 40 && LocalCacheData.getLoginData().is_logged_in ) {
			var timeout_count = 0;
			$( this.el ).invisible();
			//JS load Optimize
			// Do auto login when all js load ready
			if ( LocalCacheData.loadViewRequiredJSReady ) {
				Debug.Text( 'Login Success (first try)', null, null, 'initialize', 10 );
				$this.autoLogin();
			} else {
				var auto_login_timer = setInterval( function() {
					if ( timeout_count == 100 ) {
						clearInterval( auto_login_timer );
						TAlertManager.showAlert( $.i18n._( 'The network connection was lost. Please check your network connection then try again.' ) );
						Debug.Text( 'Login Failure', null, null, 'initialize', 10 );
						return;
					}
					timeout_count = timeout_count + 1;
					if ( LocalCacheData.loadViewRequiredJSReady ) {
						Debug.Text( 'Login Success after retry: ' + timeout_count, null, null, 'initialize', 10 );
						$this.autoLogin();
						clearInterval( auto_login_timer );
					}
				}, 600 );
			}
		} else {
			$( this.el ).visible();
			this.render();
		}

		if ( LocalCacheData.notification_bar ) {
			LocalCacheData.notification_bar.remove();
		}
	},

	default: {
		is_login: false
	},

	events: {
		'click #quick_punch': 'onQuickPunchClick',
		'click #forgot_password': 'forgotPasswordClick',
		'click #appTypeLogo': 'onAppTypeClick',
		'click #companyLogo': 'onAppTypeClick',
		'click #powered_by': 'onAppTypeClick'
	},

	onAppTypeClick: function() {
		window.open( 'https://' + LocalCacheData.loginData.organization_url );
	},

	onQuickPunchClick: function() {
		window.open( ServiceCaller.rootURL + LocalCacheData.loginData.base_url + 'html5/quick_punch' );
	},

	onLoginBtnClick: function( e ) {
		e.preventDefault(); //Prevent login form from being submitted, as Chrome will append a "?" to the end of a URL and cancel the XHR login request. This is also affecting the enter key press binding in render()

		var user_name = $( '#user_name' ).val();
		var password = $( '#password' ).val();
		var $this = this;

		if ( !this.doing_login ) {
			this.doing_login = true;
		} else {
			return;
		}

		//Catch blank username/passwords as early as possible. This may catch some bots from attempting to login as well.
		if ( user_name == '' || password == '' ) {
			TAlertManager.showAlert( $.i18n._( 'Please enter a user name and password.' ) );
			this.doing_login = false;
			return;
		}

		if ( this.authentication_api ) {
			this.authentication_api.login( user_name, password, {
				onResult: function ( e ) {
					if ( LocalCacheData.loadViewRequiredJSReady ) {
						Debug.Text( 'Login Success (first try)', null, null, 'onLoginBtnClick', 10 );
						$this.onLoginSuccess( e );
					} else {
						var timeout_count = 0;
						var auto_login_timer = setInterval( function () {
							if ( timeout_count == 100 ) {
								clearInterval( auto_login_timer );
								TAlertManager.showAlert( $.i18n._( 'The network connection was lost. Please check your network connection then try again.' ) );
								Debug.Text( 'Login Failure', null, null, 'onLoginBtnClick', 10 );
								return;
							}
							timeout_count = timeout_count + 1;
							if ( LocalCacheData.loadViewRequiredJSReady ) {
								$this.onLoginSuccess( e );
								Debug.Text( 'Login Success after retry: ' + timeout_count, null, null, 'onLoginBtnClick', 10 );
								clearInterval( auto_login_timer );
							}
						}, 600 );
					}
				},
				onError: function ( e ) {
					Debug.Text( 'Login Error...', null, null, 'onLoginBtnClick', 10 );
					$this.doing_login = false;
				},
				delegate: this
			} );
		}

	},

	onLoginSuccess: function( e, session_id ) {
		var result;
		var $this = this;

		if ( !session_id ) {
			result = e.getResult();
		} else {
			result = session_id;
		}

		TAlertManager.closeBrowserBanner();

		var user_name = $( '#user_name' );
		var password = $( '#password' );

		if ( e && !e.isValid() ) {
			LocalCacheData.setSessionID( '' );

			Global.clearSessionCookie();

			if ( e.getDetails()[0].hasOwnProperty( 'password' ) ) {
				IndexViewController.openWizard( 'ResetPasswordWizard', {
					user_name: user_name.val(),
					message: e.getDetailsAsString()
				}, function() {
					TAlertManager.showAlert( $.i18n._( 'Password has been changed successfully, you may now login.' ), '', function() {
						password.focus();
					} );
				} );
			} else {
				TAlertManager.showAlert( e.getDetailsAsString(), 'Error', function() {
					password.focus();
				} );
			}

			$this.doing_login = false;
		} else {

			ServiceCaller.cancelAllError = false;
			LocalCacheData.setSessionID( result );
			setCookie( Global.getSessionIDKey(), result );

			if ( typeof is_login === 'undefined' ) {
				this.is_login = true;
			}

			//Error: TypeError: this.currentUser_api.getCurrentUserPreference is not a function in /interface/html5/framework/jquery.min.js?v=8.0.4-20150320-094021 line 2 > eval line 205
			if ( !this.currentUser_api || typeof this.currentUser_api['getCurrentUserPreference'] !== 'function' ) {
				return;
			}

			$this.initializeLoginData();
		}
	},

	initializeLoginData: function() {
		var $this = this;

		TTPromise.add('login', 'init');

		TTPromise.add('login', 'getCurrentUser');
		$this.currentUser_api.getCurrentUser( { onResult: $this.onGetCurrentUser, delegate: $this } ); //Get more in result handler


		TTPromise.add('login', 'getCurrentUserPreference');
		$this.currentUser_api.getCurrentUserPreference( { onResult: $this.onGetCurrentUserPreference, delegate: $this } );


		//Ensure that the language chosen at the login screen is passed in so that the user's country can be appended to create a proper locale.
		TTPromise.add('login', 'getCurrentUserLocale');
		$this.authentication_api.getLocale( $( '.language-selector' ).val(), { onResult: $this.onGetCurrentUserLocale, delegate: $this } );


		TTPromise.add('login', 'getPermission');
		$this.permission_api.getPermission( {
			onResult: function( permissionRes ) {
				permission_result = permissionRes.getResult();

				if ( permission_result != false ) {
					LocalCacheData.setPermissionData( permission_result );
					TTPromise.resolve('login', 'getPermission');
				} else {
					//User does not have any permissions.
					Debug.Text( 'User does not have any permissions!', 'LoginViewController.js', 'LoginViewController', 'initializeLoginData:next', 10 );
					TAlertManager.showAlert( $.i18n._( 'Unable to login due to permissions, please try again and if the problem persists contact customer support.' ), '', function() {
						Global.Logout();
						window.location.reload();
					} );
				}
			}
		} );


		TTPromise.add('login', 'getCurrentCompany');
		$this.authentication_api.getCurrentCompany( {
			onResult: function ( current_company_result ) {
				var com_result = current_company_result.getResult();

				if ( com_result != false ) {
					if ( com_result.is_setup_complete === '1' || com_result.is_setup_complete === 1 ) {
						com_result.is_setup_complete = true;
					} else {
						com_result.is_setup_complete = false;
					}

					LocalCacheData.setCurrentCompany( com_result );
					Debug.Text( 'Version: Client: ' + APIGlobal.pre_login_data.application_build + ' Server: ' + com_result.application_build, 'LoginViewController.js', 'LoginViewController', 'onUserPreference:next', 10 );

					//Avoid reloading in unit test mode.
					if ( APIGlobal.pre_login_data.application_build != com_result.application_build && !Global.UNIT_TEST_MODE ) {
						Debug.Text( 'Version mismatch on login: Reloading...', 'LoginViewController.js', 'LoginViewController', 'initializeLoginData:next', 10 );
						window.location.reload( true );
					}

					TTPromise.resolve( 'login', 'getCurrentCompany' );
				} else {
					//User does not have any permissions.
					Debug.Text( 'Unable to get company information!', 'LoginViewController.js', 'LoginViewController', 'onUserPreference:next', 10 );
					TAlertManager.showAlert( $.i18n._( 'Unable to download required information, please check your network connection and try again. If the problem persists contact customer support.' ), '', function() {
						Global.Logout();
						window.location.reload();
					} );
				}
			}
		} );


		TTPromise.add('login', 'getUniqueCountry');
		$this.permission_api.getUniqueCountry( {
			onResult: function ( country_result ) {
				LocalCacheData.setUniqueCountryArray( country_result.getResult() );
				TTPromise.resolve('login', 'getUniqueCountry');
			}
		} );


		//Once all login promises are complete, switch to home view.
		TTPromise.wait( 'login', null, function() {
			$this.goToView()
		});

		TTPromise.resolve('login', 'init');
	},

	onGetCurrentUser: function( e ) {
		LocalCacheData.setLoginUser( e.getResult() );

		var filter = {};
		filter.filter_data = {};
		filter.filter_data.user_id = LocalCacheData.loginUser.id;
		filter.filter_columns = {};
		filter.filter_columns.symbol = true;

		e.get( 'delegate' ).currency_api.getCurrency( filter, {
			onResult: function( raw_result ) {
				var result = raw_result.getResult();

				if ( Global.isArrayAndHasItems( result ) && result[0].symbol ) {
					LocalCacheData.setCurrentCurrencySymbol( result[0].symbol );
				} else {
					LocalCacheData.setCurrentCurrencySymbol( '$' );
				}
			}
		} );

		TTPromise.resolve('login', 'getCurrentUser');
	},

	onGetCurrentUserPreference: function( e ) {
		var result = e.getResult();
		var login_view_this = e.get( 'delegate' );

		if ( result.date_format ) {
			handleDateTimeFormats( result );
		} else {
			login_view_this.user_preference_api.getUserPreferenceDefaultData( {
				onResult: function( userPD ) {
					handleDateTimeFormats( userPD.getResult() );
				}
			} );
		}

		function handleDateTimeFormats( nextResult ) {
			LocalCacheData.loginUserPreference = nextResult;

			TTPromise.add('getCurrentUserPreference', 'getDateFormat');
			login_view_this.user_preference_api.getOptions( 'moment_date_format', {
				onResult: function( jsDateFormatRes ) {
					var jsDateFormatResultData = jsDateFormatRes.getResult();

					//For moment date parser
					LocalCacheData.loginUserPreference.js_date_format = jsDateFormatResultData;

					var date_format = LocalCacheData.loginUserPreference.date_format;
					if ( !date_format ) {
						date_format = 'DD-MMM-YY';
					}

					LocalCacheData.loginUserPreference.date_format = LocalCacheData.loginUserPreference.js_date_format[date_format];

					LocalCacheData.loginUserPreference.date_format_1 = Global.convertTojQueryFormat( date_format ); //TDatePicker, TRangePicker
					LocalCacheData.loginUserPreference.time_format_1 = Global.convertTojQueryFormat( LocalCacheData.loginUserPreference.time_format ); //TTimePicker

					TTPromise.resolve('getCurrentUserPreference', 'getDateFormat');
				}
			} );

			TTPromise.add('getCurrentUserPreference', 'getTimeFormat');
			login_view_this.user_preference_api.getOptions( 'moment_time_format', {
				onResult: function( jsTimeFormatRes ) {
					var jsTimeFormatResultData = jsTimeFormatRes.getResult();

					LocalCacheData.loginUserPreference.js_time_format = jsTimeFormatResultData;
					TTPromise.resolve('getCurrentUserPreference', 'getTimeFormat');
				}
			} );

			TTPromise.wait( 'getCurrentUserPreference', null, function() {
				LocalCacheData.setLoginUserPreference( LocalCacheData.loginUserPreference );
				TTPromise.resolve('login', 'getCurrentUserPreference');
			});
		}
	},

	onGetCurrentUserLocale: function( e ) {
		var login_view_this = e.get( 'delegate' );
		result = e.getResult();
		if ( result ) {
			login_view_this.user_locale = result;
		}

		TTPromise.resolve('login', 'getCurrentUserLocale');
	},

	goToView: function() {
		this.doing_login = false;
		TopMenuManager.ribbon_view_controller = null;
		TopMenuManager.ribbon_menus = null;
		Global.topContainer().empty();
		LocalCacheData.currentShownContextMenuName = null;

		var message_id = TTUUID.generateUUID();
		if ( LocalCacheData.getLoginData().locale != null && this.user_locale !== LocalCacheData.getLoginData().locale ) {
			ProgressBar.showProgressBar( message_id );
			ProgressBar.changeProgressBarMessage( $.i18n._( 'Language changed, reloading' ) + '...' );

			Global.setLanguageCookie( this.user_locale );
			LocalCacheData.setI18nDic( null );
			setTimeout( function() {
				window.location.reload( true );
			}, 5000 );
		}
		IndexViewController.instance.router.removeCurrentView();
		var target_view = getCookie( 'PreviousSessionType' );
		if ( target_view && getCookie( 'PreviousSessionID' ) ) {
			TopMenuManager.goToView( target_view );
			deleteCookie( 'PreviousSessionType', LocalCacheData.cookie_path, Global.getHost() );
		} else {
			if ( Global.getDeepLink() != false ) {

				//Catch users coming back from a masquerade, and prevent deeplink override after returning them to the view that they started masquerading from.
				var previous_session_cookie = decodeURIComponent( getCookie( 'AlternateSessionData' ) );
				if ( previous_session_cookie ) {
					//The user has been masquerading.
					previous_session_cookie = JSON.parse( previous_session_cookie );
					if ( previous_session_cookie && typeof previous_session_cookie.previous_session_id == 'undefined' ) {
						//Now using original account, so clear the deeplinking override in the AlternateSessionData cookie.
						setCookie( 'AlternateSessionData', '{}', -1, APIGlobal.pre_login_data.cookie_base_url, Global.getHost() );
					}
				}

				TopMenuManager.goToView( Global.getDeepLink() );
			} else if ( LocalCacheData.getLoginUserPreference().default_login_screen ) {
				TopMenuManager.goToView( LocalCacheData.getLoginUserPreference().default_login_screen );
			} else {
				TopMenuManager.goToView( 'Home' );
			}
		}

		if ( !LocalCacheData.getCurrentCompany().is_setup_complete && PermissionManager.checkTopLevelPermission( 'QuickStartWizard' ) ) {
			IndexViewController.openWizard( 'QuickStartWizard' );
		}

		var current_company = LocalCacheData.getCurrentCompany();
	},

	forgotPasswordClick: function() {
		//window.open( ServiceCaller.rootURL + LocalCacheData.loginData.base_url + 'ForgotPassword.php' );
		IndexViewController.openWizard( 'ForgotPasswordWizard', null, function() {
			TAlertManager.showAlert( $.i18n._( 'An email has been sent to you with instructions on how to change your password.' ) );
		} );
	},

	autoLogin: function() {
		// Error: TypeError: e is null in interface/html5/framework/jquery.min.js?v=9.0.5-20151222-094938 line 2 > eval line 154
		var session_cookie = getCookie( Global.getSessionIDKey() );
		if ( session_cookie && session_cookie.length >= 40 ) {
			this.doing_login = true;
			this.onLoginSuccess( null, session_cookie );
		} else {
			$( this.el ).visible();
			this.render();
		}
	},

	render: function() {

		var $this = this;
		var message_id = TTUUID.generateUUID();
		LocalCacheData.setSessionID( '' );

		if(!$('body').hasClass('mobile-device-mode')) {
			// If not on a mobile view (Where desktop UI is forced, render the random animal on background. If mobile, no animals.
			this.renderAnimalsForBackground();
		}

		var passwordInput = $( '#password' ).TTextInput( { width: 151 } );
		var username_input = $( '#user_name' ).TTextInput( { width: 151 } );
		var error_string_td = $( '.error-info' );

		if ( LocalCacheData.login_error_string ) {
//			error_string_td.html( LocalCacheData.login_error_string );
			error_string_td.text( LocalCacheData.login_error_string );
			LocalCacheData.login_error_string = '';
		} else {
			error_string_td.text( '' );
		}

		username_input.focus();

		// Listen to the login form submit event, but replace default behaviour with our own.
		// We need to trigger login via the standard form submission for browsers to properly detect a login form for autocomplete and credential storage, especially picky browsers like IE11. See git log or issue #2680 for further context.
		// This event listener will also be triggered by keyboard ENTER, thus we do not need a separate listener for that key anymore.
		$('#login-form').on('submit', function(e) {
			// Prevent the default once submit event triggered.
			e.preventDefault();

			// Instead of submitting the form to itself, trigger the login check.
			$this.onLoginBtnClick(e);
		});

		$( '#versionNumber' ).html( 'v' + APIGlobal.pre_login_data.application_build );

		$( '#appTypeLogo' ).css( 'opacity', 0 );

		//community edition
		var is_seo = false;

		var url = 'theme/' + Global.theme;

		if ( Global.url_offset ) {
			url = Global.url_offset + url;
		}

		if ( LocalCacheData.productEditionId > 10 && LocalCacheData.appType === true ) {
			$( '#appTypeLogo' ).attr( 'src', url + '/css/views/login/images/od.png' + '?v=' + APIGlobal.pre_login_data.application_build );
		} else if ( LocalCacheData.productEditionId === 15 ) {
			$( '#appTypeLogo' ).attr( 'src', url + '/css/views/login/images/beo.png' + '?v=' + APIGlobal.pre_login_data.application_build );
		} else if ( LocalCacheData.productEditionId === 20 ) {
			$( '#appTypeLogo' ).attr( 'src', url + '/css/views/login/images/peo.png' + '?v=' + APIGlobal.pre_login_data.application_build );
		} else if ( LocalCacheData.productEditionId === 25 ) {
			$( '#appTypeLogo' ).attr( 'src', url + '/css/views/login/images/eeo.png' + '?v=' + APIGlobal.pre_login_data.application_build );
		} else {
			$( '#appTypeLogo' ).attr( 'src', url + '/css/views/login/images/seo.png' + '?v=' + APIGlobal.pre_login_data.application_build );
			is_seo = true;
		}

		var quick_punch_link = $( this.el ).find( '#quick_punch' );
		quick_punch_link.hide();

		$( '#appTypeLogo' ).on( 'load', function() {
			$( this ).animate( {
				opacity: 1
			}, 100 );
		} );

		$( '#companyLogo' ).hide();
		$( '#companyLogo' ).css( 'opacity', 0 );
		$( '#companyLogo' ).attr( 'src', ServiceCaller.mainCompanyLogo );

		$( '#companyLogo' ).on( 'load', function() {

			var ratio = 78 / $( this ).height();

			if ( $( this ).height() > 78 ) {
				$( this ).css( 'height', 78 );

				if ( $( this ).width > 286 ) {
					$( this ).css( 'width', 286 );
				}
			}

			if ( $( this ).width > 286 ) {
				$( this ).css( 'width', 286 );
			}
			$( '#companyLogo' ).show();

			$( this ).animate( {
				opacity: 1
			}, 100 );
		} );

		var powered_by_img = $( '#powered_by' );
		powered_by_img.show();
		powered_by_img.attr( 'src', ServiceCaller.login_page_powered_by_logo );
		powered_by_img.attr( 'alt', LocalCacheData.loginData.application_name + ' Workforce Management Software' );
		var powered_by_link = $( '<a target="_blank" href="https://' + LocalCacheData.getLoginData().organization_url + '"></a>' );
		powered_by_link.addClass( 'powered-by-img-seo' );
		powered_by_img.wrap(powered_by_link);
		
		var footer_right_html = LocalCacheData.getLoginData().footer_right_html;
		var footer_left_html = LocalCacheData.getLoginData().footer_left_html;

		if ( footer_right_html && $.type( footer_right_html ) === 'string' ) {
			footer_right_html = $( footer_right_html );
			footer_right_html.addClass( 'foot-right-html' );

			footer_right_html.insertAfter( $( $this.el ) );
		}

		if ( footer_left_html && $.type( footer_left_html ) === 'string' ) {
			footer_left_html = $( footer_left_html );
			footer_left_html.addClass( 'foot-left-html' );

			footer_left_html.insertAfter( $( $this.el ) );
		}

		this.lan_selector = $( '.language-selector' );
		this.lan_selector.TComboBox();
		var array = Global.buildRecordArray( (LocalCacheData.getLoginData().language_options) );

		this.lan_selector.setSourceData( array );


		this.lan_selector.setValue( LocalCacheData.getLoginData().language );

		var $this = this;
		this.lan_selector.bind( 'formItemChange', function() {
			Global.setLanguageCookie( $this.lan_selector.getValue() );
			LocalCacheData.setI18nDic( null );

			ProgressBar.showProgressBar( message_id );
			ProgressBar.changeProgressBarMessage( $.i18n._( 'Language changed, reloading' ) + '...' );

			setTimeout( function() {
				window.location.reload( true );
			}, 2000 );

		} );

		if ( LocalCacheData.all_url_args.user_name ) {
			username_input.val( LocalCacheData.all_url_args.user_name );
		}

		if ( LocalCacheData.all_url_args.password ) {
			passwordInput.val( LocalCacheData.all_url_args.password );
		}

		Global.moveCookiesToNewPath();
		Global.setUIInitComplete();

	},

	cleanWhenUnloadView: function( callBack ) {
		$( '#loginViewContainer' ).remove();
		Global.setVirtualDeviceViewport('desktop'); // Setting mobile view on login, then back to desktop (990px virtual) after login, to allow pan & zoom, as not whole app is mobile optimized.
		this._super( 'cleanWhenUnloadView', callBack );
	},

	renderAnimalsForBackground: function() {
		var station_id = Global.getStationID();
		//week of year and numeric digits of station_id.
		if ( station_id.length > 0 ) {
			var station_id_arr = station_id.match( /\d{1,5}/g );
			if ( $.isArray( station_id_arr ) ) {
				station_id = station_id_arr.join('');
			}
		}
		var seed = parseInt( moment().week() + '' + station_id );
		var random_image_number = Math.floor( ( Math.abs( Math.sin( seed ) ) * seed ) % 2 ) + 1; // seeded random
		$( '#login-bg_animal' ).css( 'background-image', 'url(\'theme/default/images/login_animals_' + random_image_number + '.png\')' );
	}

} );
