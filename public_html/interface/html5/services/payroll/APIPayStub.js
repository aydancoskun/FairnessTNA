var APIPayStub = ServiceCaller.extend( {

	key_name: 'PayStub',
	className: 'APIPayStub',

	generatePayStubs: function() {

		return this.argumentsHandler( this.className, 'generatePayStubs', arguments );

	},

	getCurrentPayRun: function() {

		return this.argumentsHandler( this.className, 'getCurrentPayRun', arguments );

	},

	getPayStubDefaultData: function() {

		return this.argumentsHandler( this.className, 'getPayStubDefaultData', arguments );

	},

	getPayStub: function() {

		return this.argumentsHandler( this.className, 'getPayStub', arguments );

	},

	getCommonPayStubData: function() {

		return this.argumentsHandler( this.className, 'getCommonPayStubData', arguments );

	},

	validatePayStub: function() {

		return this.argumentsHandler( this.className, 'validatePayStub', arguments );

	},

	setPayStub: function() {

		return this.argumentsHandler( this.className, 'setPayStub', arguments );

	},

	deletePayStub: function() {

		return this.argumentsHandler( this.className, 'deletePayStub', arguments );

	},

	exportPayStub: function() {
		return this.argumentsHandler( this.className, 'exportPayStub', arguments );
	},

	generatePayStubs: function() {

		return this.argumentsHandler( this.className, 'generatePayStubs', arguments );

	}

} );