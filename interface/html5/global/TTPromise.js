/*
 * $License$
 */
TTPromise = {
	promises: {},

	add: function( category, key ) {
		if ( !this.promises[category] || !this.promises[category][key] || (this.promises[category][key].state() != 'pending') ) {
			Debug.Text( 'Promise: add: ' + category + '|' + key, 'TTPromise.js', 'TTPromise.js', 'add', 11 );
			if ( typeof this.promises[category] == 'undefined' ) {
				this.promises[category] = {};
			}

			this.promises[category][key] = $.Deferred();
			this.promises[category][key].extra_data = { category: category, key: key };
		} else {
			Debug.Text( 'Promise: already exists: ' + category + '|' + key, 'TTPromise.js', 'TTPromise.js', 'add', 11 );
		}
	},

	resolve: function( category, key ) {
		if ( this.promises && this.promises[category] && this.promises[category][key] ) {
			Debug.Text( 'Promise: resolved: ' + category + '|' + key, 'TTPromise.js', 'TTPromise.js', 'resolve', 11 );
			this.promises[category][key].resolve( this.promises[category][key].extra_data );
		}
	},

	reject: function( category, key ) {
		if ( this.promises && this.promises[category] && this.promises[category][key] ) {
			Debug.Text( 'Promise: rejected: ' + category + '|' + key, 'TTPromise.js', 'TTPromise.js', 'reject', 11 );
			this.promises[category][key].reject( this.promises[category][key].extra_data );
		}
	},

	/**
	 * Wait for all or specific category/key
	 *
	 * In the case of waiting on all promises, error_callback is triggered for each individual error (rejection), and success callback is called when all promises are resolved (either success or failure)
	 * so when waiting for all promises, most times you don't want a failure callback because both "success" and "failure" can happen on the same wait.
	 *
	 * The function call should look like:
	 *    TTPromise.wait(null,null,function(){
	 *    	//do stuff on success
	 *    });
	 *
	 * @param category
	 * @param key
	 * @param success_callback
	 * @param error_callback
	 */
	wait: function( category, key, success_callback, error_callback ) {
		Debug.Arr( arguments, 'Promise: wait(' + category + '|' + key + ').', 'TTPromise.js', 'TTPromise.js', 'wait', 11 );
		if ( typeof success_callback != 'function' ) {
			success_callback = function() {
				Global.setUIInitComplete();
				Debug.Text( 'Promise: resolved with default callback.', 'TTPromise.js', 'TTPromise.js', 'wait', 11 );
			};
		}

		if ( typeof error_callback != 'function' ) {
			error_callback = function() {
				Global.setUIInitComplete();
				Debug.Text( 'Promise failed with default callback.', 'TTPromise.js', 'TTPromise.js', 'wait', 1 );
			};
		}

		if ( Object.keys( TTPromise.promises ).length > 0 ) {
			var onComplete = function() {
				for ( var i = 0; i < arguments.length; i++ ) {
					//numerically indexed arguments come back from Promise resolution arguments.
					var obj = arguments[i];

					var category = obj.category;
					var key = obj.key;
					var wait_category = obj.wait_arguments.category;
					var wait_key = obj.wait_arguments.key;

					TTPromise.clearCompletedPromise( category, key );
				}

				if ( !wait_category || TTPromise.filterPromiseArray( wait_category, wait_key ).length == 0 ) {
					Debug.Text( 'Promise: success callback', 'TTPromise.js', 'TTPromise.js', 'wait', 11 );
					success_callback( true );
				} else {
					Debug.Text( 'Promise: waiting again ' + category + '|' + key, 'TTPromise.js', 'TTPromise.js', 'wait', 11 );
					TTPromise.wait( category, key, success_callback, error_callback );
				}

				return true;
			};

			var onError = function() {
				//When one promise fails in a category, we need to call the error_callback if waiting on that category, and *never* call the success callback.
				Debug.Text( 'Promise: ERROR callback', 'TTPromise.js', 'TTPromise.js', 'wait', 11 );
				for ( var i = 0; i < arguments.length; i++ ) {
					//numerically indexed arguments come back from Promise resolution arguments.
					var obj = arguments[i];

					var category = obj.category;
					var key = obj.key;
					var wait_category = obj.wait_arguments.category;
					var wait_key = obj.wait_arguments.key;

					TTPromise.clearCompletedPromise( category, key );
				}

				//Different than success because we always want to call the error_callback immediately, as the category can never be success after a single error.
				if ( typeof error_callback != 'undefined' ) {
					error_callback( false );
				}

				//Don't wait again, as that will cause success/error callbacks to be triggered multiple times and could cause an infinite loop too.
				//TTPromise.wait( wait_category, wait_key, success_callback, error_callback );
				return true;
			};

			var pending_promises = this.filterPromiseArray( category, key );
			$.when.apply( $, pending_promises ).pipe( onComplete, onError );

		} else {
			success_callback( true );
		}
	},

	filterPromiseArray: function( category, key ) {
		retval = [];
		for ( var c in this.promises ) {
			if ( !category || c == category ) {

				//Debug.Text('Promise: processing category: '+ c, 'TTPromise.js', 'TTPromise.js', 'pending_to_waiting', 11);
				for ( var k in this.promises[c] ) {
					if ( !key || k == key ) {
						//Debug.Text('Promise: processing key: '+ c+'|'+k, 'TTPromise.js', 'TTPromise.js', 'pending_to_waiting', 11);
						this.promises[c][k].extra_data.wait_arguments = { category: category, key: key };
						retval.push( this.promises[c][k] );
					} else {
						//Debug.Text('Promise: ignoring key: '+ k, 'TTPromise.js', 'TTPromise.js', 'pending_to_waiting', 11);
					}
				}
			} else {
				//Debug.Text('Promise: ignoring category: '+ c, 'TTPromise.js', 'TTPromise.js', 'pending_to_waiting', 11);
			}
		}
		return retval;
	},

	clearCompletedPromise: function( category, key ) {
		if ( this.promises && category && key ) {
			if ( this.promises[category] && this.promises[category][key] ) {
				this.promises[category][key] = false;
				delete this.promises[category][key];
				Debug.Text( 'Promise: clear category: ' + category + '|' + key, 'TTPromise.js', 'TTPromise.js', 'init', 11 );
			}

			if ( this.promises[category] && Object.keys( this.promises[category] ).length == 0 ) {
				this.promises[category] = false;
				Debug.Text( 'Promise: clear category: ' + category, 'TTPromise.js', 'TTPromise.js', 'init', 11 );
				delete this.promises[category];
			}

			return true;
		}

		return false;
	},

	//clear existing promises (mostly for testing and first add)
	clearAllPromises: function() {
		Debug.Text( 'Promise: clear all promises.', 'TTPromise.js', 'TTPromise.js', 'init', 11 );
		this.promises = {};
	},

	isPendingPromises: function( category, key ) {
		var p = TTPromise.filterPromiseArray( category, key );

		var pending_count = 0;
		for ( var n in p ) {
			if ( p[n].state() == 'pending' ) {
				console.debug( 'Category: ' + p[n].extra_data.category + ' Key: ' + p[n].extra_data.key + ' State: ' + p[n].state() );
				pending_count++;
			}
		}

		if ( pending_count > 0 ) {
			return true;
		}

		return false;
	},

	debugPromises: function( category, key ) {
		var p = TTPromise.filterPromiseArray( category, key );

		for ( var n in p ) {
			console.debug( 'Category: ' + p[n].extra_data.category + ' Key: ' + p[n].extra_data.key + ' State: ' + p[n].state() );
		}

		return true;
	}
};