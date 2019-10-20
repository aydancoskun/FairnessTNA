var APIUser = ServiceCaller.extend( {

	key_name: 'User',
	className: 'APIUser',

	getCommonUserData: function() {

		return this.argumentsHandler( this.className, 'getCommonUserData', arguments );

	},

	getUser: function() {

		return this.argumentsHandler( this.className, 'getUser', arguments );

	},

	setUser: function() {

		return this.argumentsHandler( this.className, 'setUser', arguments );

	},

	getUserDefaultData: function() {

		return this.argumentsHandler( this.className, 'getUserDefaultData', arguments );

	},

	deleteUser: function() {

		return this.argumentsHandler( this.className, 'deleteUser', arguments );

	},

	validateUser: function() {

		return this.argumentsHandler( this.className, 'validateUser', arguments );

	},

	copyUser: function() {

		return this.argumentsHandler( this.className, 'copyUser', arguments );

	},

	isUniqueUserName: function() {

		return this.argumentsHandler( this.className, 'isUniqueUserName', arguments );

	},

	changePassword: function() {

		return this.argumentsHandler( this.className, 'changePassword', arguments );

	},


	UnsubscribeEmail: function() {

		return this.argumentsHandler( this.className, 'UnsubscribeEmail', arguments );

	},

	getCompanyUser: function() {

		return this.argumentsHandler( this.className, 'getCompanyUser', arguments );

	},


	getUniqueUserProvinces: function() {

		return this.argumentsHandler( this.className, 'getUniqueUserProvinces', arguments );

	},


	setUserFeedbackRating: function() {
		return this.argumentsHandler( this.className, 'setUserFeedbackRating', arguments );

	},

	setUserFeedbackReview: function() {
		return this.argumentsHandler( this.className, 'setUserFeedbackReview', arguments );

	},

	sendValidationEmail: function() {
		return this.argumentsHandler( this.className, 'sendValidationEmail', arguments );

	},

	exportUser: function() {
		return this.argumentsHandler( this.className, 'exportUser', arguments );
	},

	deleteImage: function() {
		return this.argumentsHandler( this.className, 'deleteImage', arguments );
	}


} );