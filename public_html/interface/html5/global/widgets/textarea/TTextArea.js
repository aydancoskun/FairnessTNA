(function( $ ) {

	$.fn.TTextArea = function( options ) {
		var opts = $.extend( {}, $.fn.TTextArea.defaults, options );
		var $this = this;
		var field;
		var error_string = '';
		var error_tip_box;
		var mass_edit_mode = false;
		var check_box = null;
		var enabled = true;
		var static_width;
		var is_static_width = false;

		this.getEnabled = function() {
			return enabled;
		};

		this.setEnabled = function( val ) {
			enabled = val;
			if ( val === false || val === '' ) {
				$this.attr( 'readonly', 'true' );
				$this.children().attr( 'disabled', 'true' );
				$this.addClass( 't-text-area-readonly' );
			} else {
				$this.removeAttr( 'readonly' );
				$this.children().removeAttr( 'disabled' );
				$this.removeClass( 't-text-area-readonly' );
			}

		};

		this.setCheckBox = function( val ) {
			if ( check_box ) {
				check_box.children().eq( 0 )[0].checked = val;
			}
		};

		this.isChecked = function() {
			if ( check_box ) {
				if ( check_box.children().eq( 0 )[0].checked === true ) {
					return true;
				}
			}

			return false;
		};

		this.setMassEditMode = function( val ) {
			mass_edit_mode = val;

			if ( mass_edit_mode ) {
				check_box = $( ' <div class="mass-edit-checkbox-wrapper textarea--mass-edit-checkbox-wrapper"><input type="checkbox" class="mass-edit-checkbox" />' +
				'<label for="checkbox-input-1" class="input-helper input-helper--checkbox"></label></div>' );
				check_box.insertBefore( $( this ) );

				check_box.change( function() {
					$this.trigger( 'formItemChange', [$this] );
				} );

				if ( is_static_width && static_width.toString().indexOf( '%' ) > 0 ) {
					$this.css( 'width', 'calc(' + static_width + ' - 25px)' );
				}

			} else {
				if ( check_box ) {
					check_box.remove();
					check_box = null;
				}
			}

		};

		this.setValue = function( val ) {

			if ( !val ) {
				val = '';
			}

			$this.val( val );

		};

		this.setErrorStyle = function( errStr, show, isWarning ) {
			if ( isWarning ) {
				$( this ).addClass( 'warning-tip' );
			} else {
				$( this ).addClass( 'error-tip' );
			}
			error_string = errStr;

			if ( show ) {
				this.showErrorTip();
			}
		};

		this.showErrorTip = function( sec ) {

			if ( !Global.isSet( sec ) ) {
				sec = 2;
			}

			if ( !error_tip_box ) {
				error_tip_box = Global.loadWidgetByName( WidgetNamesDic.ERROR_TOOLTIP );
				error_tip_box = error_tip_box.ErrorTipBox();
			}
			if ( $( this ).hasClass( 'warning-tip' ) ) {
				error_tip_box.show( this, error_string, sec, true );
			} else {
				error_tip_box.show( this, error_string, sec );
			}
		};

		this.hideErrorTip = function() {

			if ( Global.isSet( error_tip_box ) ) {
				error_tip_box.remove();
			}

		};

		this.clearErrorStyle = function() {
			$( this ).removeClass( 'error-tip' );
			$( this ).removeClass( 'warning-tip' );
			this.hideErrorTip();
			error_string = '';
		};

		this.setField = function( val ) {
			field = val;
		};

		this.getField = function() {
			return field;
		};

		this.getValue = function() {
			return $this.val();
		};

		this.setValue = function( val ) {

			if ( !val ) {
				val = '';
			}

			$this.val( val );
		};

		this.each( function() {

			var o = $.meta ? $.extend( {}, opts, $( this ).data() ) : opts;

			field = o.field;

			if ( o.width && ( o.width > 0 || o.width.indexOf( '%' ) > 0) ) {
				$this.width( o.width );
				static_width = o.width;
				is_static_width = true;
			}

			if ( o.height && (o.height > 0 || o.height.indexOf( '%' ) > 0) ) {
				$this.height( o.height );
			}

			if ( o.rows > 0 ) {
				$this.attr( 'rows', o.rows );
			} else {
				$this.attr( 'rows', 3 );
			}

			if ( o.style ) {
				$this.css( o.style );
			}

			$( this ).change( function() {
				if ( check_box ) {
					$this.setCheckBox( true );
				}

				$this.trigger( 'formItemChange', [$this] );
			} );

			$( this ).mouseover( function() {

				if ( enabled ) {
					if ( error_string && error_string.length > 0 ) {
						$this.showErrorTip( 20 );
					}
				}

			} );

			$( this ).mouseout( function() {
				if ( !$( $this ).is( ':focus' ) ) {
					$this.hideErrorTip();
				}
			} );

//			$( this ).bind( 'contextmenu', ( function( e ) {
//				e.preventDefault();
//			}) );

			$( this ).focusin( function() {
				if ( !enabled ) {
					if ( !check_box ) {
						if ( LocalCacheData.current_open_sub_controller &&
							LocalCacheData.current_open_sub_controller.edit_view &&
							LocalCacheData.current_open_sub_controller.is_viewing ) {
							error_string = Global.view_mode_message;
							$this.showErrorTip( 10 );
						} else if ( LocalCacheData.current_open_primary_controller &&
							LocalCacheData.current_open_primary_controller.edit_view &&
							LocalCacheData.current_open_primary_controller.is_viewing ) {
							error_string = Global.view_mode_message;
							$this.showErrorTip( 10 );
						}
					}
				} else {
					if ( error_string && error_string.length > 0 ) {
						$this.showErrorTip( 20 );
					}
				}
			} );

			$( this ).focusout( function() {
				$this.hideErrorTip();
			} );

		} );

		return this;

	};

	$.fn.TTextArea.defaults = {};

})( jQuery );