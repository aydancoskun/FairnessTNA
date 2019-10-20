BaseWindowController = Backbone.View.extend( {

	initialize: function( options ) {
		this.content_div = $( this.el ).find( '.content' );
		this.stepsWidgetDic = {};
		this.stepsDataDic = {};

		this.default_data = BaseWizardController.default_data;
		this.call_back = BaseWizardController.call_back;

		BaseWizardController.default_data = null;
		BaseWizardController.call_back = null;

		//FIXME: pull this out when all wizards are refactored to the new way #1187
		if ( typeof this.setDefaultDataToSteps == 'function' ) {
			this.setDefaultDataToSteps();
		}

		var $this = this;
		var required_files = this.filterRequiredFiles();
		require( required_files, function() {
			if ( typeof $this.init == 'function' ) {
				$this.init( options );
			}
		} );
	},

	render: function() {

	},

	filterRequiredFiles: function() {
		var retval = [];
		var required_files;

		if ( typeof this._required_files == 'object' ) {
			required_files = this._required_files;
		} else {
			required_files = this.getRequiredFiles();
		}

		if ( required_files && required_files[0] ) {
			retval = required_files;
		} else {
			for ( var edition_id in required_files ) {
				if ( Global.getProductEdition() >= edition_id ) {
					retval = retval.concat( required_files[edition_id] );
				}
			}
		}

		Debug.Arr( retval, 'RETVAL', 'BaseWindowController.js', 'BaseWindowController', 'filterRequiredFiles', 10 );
		return retval;
	},

	preInit: function() {
		//override in child class
	}

} );