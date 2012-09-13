/*
 * jquery.zend.jsonrpc.js 1.6
 * 
 * CHANGELOG:
 * 08/30/2011 Added custom header support and object-based exception support
 * 05/30/2011 Adding per-method exception handler callback.
 * 04/05/2011 Added handler for JSON-RPC server exceptions.
 * 02/25/2011 Added individual call callbacks submitted by Mike Bevz
 *            Generally made async requests further more robust
 * 02/22/2011 added async call support, added smd cache
 * 12/11/2010 added namespace support
 *
 * Copyright (c) 2010 - 2011 Tanabicom, LLC
 * http://www.tanabi.com
 *
 * Contributions by Mike Bevz (http://mikebevz.com)
 * And by Sergio Surkamp (http://www.gruposinternet.com.br)
 *
 * Released under the MIT license:
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/* USAGE
 *
 * var json_client = jQuery.Zend.jsonrpc(options)
 *
 * Returns a json_client object that implements all the methods provided
 * by the Zend JSON RPC server.  Options is an object which may contain the
 * following parameters:
 *
 * url                  - The URL of the JSON-RPC server.
 * smd                  - This is a way to define the available class
 *                        structure without fetching it from the RPC server.
 *                        This should be a JSON object similar to what would
 *                        be returned from a reflection request.
 *
 *                        You can either cacheSMD response from the RPC server
 *                        and reuse it, or disable reflection on the RPC server
 *                        if you don't want your methods to be public.
 *                        Passing this will prevent the initial reflection
 *                        poll.
 * version              - Version of JSON-RPC to implement (default: detect)
 * async                - Use async requests (boolean, default false)
 * success              - Callback for successful call (async only)
 *                        Passes three parameters.  The first parameter is
 *                        the return value of the call, the second is the
 *                        sequence number which may or may not be useful :P
 *                        The third parameter is the method called.
 * error                - Callback for failed call (async only)
 *                        The (nonfunctional) JSON-RPC object and the 3
 *                        error params returned by jQuery.ajax req,stat,err
 *                        and then the sequence number and function name
 * asyncReflect         - Make reflection async (boolean, default false)
 * reflectSuccess       - Method to call for success.  1 parameter passed;
 *                        the JSON-RPC object you use to make subsequent calls
 * reflectError         - Method to call for failure.  4 parameters passed;
 *                        The (nonfunctional) JSON-RPC object and the 3
 *                        error params returned by jQuery.ajax req,stat,err
 * exceptionHandler     - Method to call on an exception -- this is a
 *                        SUCCESSFUL call that got an exception from the
 *                        server.  'error' object is passed.  Unfortunately
 *                        I tried to make this work with 'throw' but I
 *                        couldn't catch what was thrown.
 *
 *                        If this is NOT set, 'error' object will be returned
 *                        on.  In both cases, error object will be a
 *                        jQuery.Zend.Zend_Json_Exception
 * headers              - A javascript object that is passed straight to
 *                        the $.ajax call to permit additional HTTP headers.
 *
 * SPECIAL NOTES ABOUT ASYNC MODE:
 *
 * If the client is in async mode (async : true, or use setAsync method)
 * you can pass an additional argument to your methods that contains the
 * an array of success / failure /exception handler callbacks.  For ex:
 *
 * var json_client = jQuery.Zend.jsonrpc({url:...., async:true });
 *
 * json_client.add(1,2,{success: function() {...},
 *                      error:   function() {...},
 *                      exceptionHandler: function() { ... }
 * });
 *
 * These callback methods are called IN ADDITION to the success/error methods
 * if you set them.  These callbacks receive the same variables passsed to them
 * as the default callbacks do.
 *
 * ALSO: Async calls return the 'sequence ID' for the call, which can be
 * matched to the ID passed to success / error handlers.
 */
if(!jQuery.Zend){
    jQuery.Zend = { };
}
/*
 * In case of error, an instance of this class is returned to the caller
 * instead of the actual resulting message.
 */
jQuery.Zend.Zend_Json_Exception = function(data) {
	this.name = "jQuery.Zend.Zend_Json_Exception";
	this.code = data.code;
	this.message = (data.message || "");
	this.toString = function() {
		return "(" + this.code + "): " + this.message;
	}
};
jQuery.Zend.Zend_Json_Exception.prototype = new Error;

jQuery.Zend.jsonrpc = function(options) {
    /* Create an object that can be used to make JSON RPC calls. */
    return new (function(options){
        /* Self reference variable to be used all over the place */
        var self = this;
        
        /* Merge selected options in with default options. */
        this.options = jQuery.extend({
                                        url: '',
                                        version: '',
                                        async: false,
                                        beforeSend: function() { },
                                        complete: function() { },
                                        success: function() { },
                                        error: function() { },
                                        asyncReflect: false,
                                        asyncSuccess: function() { },
                                        asyncError: function() { },
                                        smd: null,
                                        exceptionHandler: null,
                                        headers: { }
        },options);
        
        /* Keep track of our ID sequence */
        this.sequence = 1;
        
        /* See if we're in an error condition. */
        this.error = false;
        
        // Add method to set async on/off
        this.setAsync = function(toggle) {
            self.options.async = toggle;
            return self;
        };

        // Add method to set async callbacks
        this.setAsyncSuccess = function(callback) {
            self.options.success = callback;
            return self;
        }

        this.setAsyncError = function(callback) {
            self.options.error = callback;
            return self;
        }

        // A private function for building callbacks.
        var buildCallbacks = function(data) {
            /* Set version if we don't have it yet. */
            if(!self.options.version){
                if(data.envelope == "JSON-RPC-1.0"){
                    self.options.version = 1;
                }else{
                    self.options.version = 2;
                }
            }

            // Common regexp object
            var detectNamespace = new RegExp("([^.]+)\\.([^.]+)");

            /* On success, let's build some callback methods. */
            jQuery.each(data.methods,function(key,val){
                // Make the method
                var new_method = function(){
                    var params = new Array();
                    for(var i = 0; i < arguments.length; i++){
                        params.push(arguments[i]);
                    }

                    var id = (self.sequence++);
                    var reply = [];

                    var successCallback = false;
					var exceptionHandler = false;
                    var errorCallback = false;

                    /* If we're doing an async call, handle callbacks
                     * if we happen to have them.
                     */
                    if(self.options.async && params.length) {
                        if(typeof params[params.length-1] == 'object') {
                            var potentialCallbacks = params[params.length-1];
                            
                            // For backwards compatibility with Mike's
                            // patch, but support more consistent
                            // callback hook names
                            if(jQuery.isFunction(potentialCallbacks.cb)) {
                                successCallback = potentialCallbacks.cb;
                            } else if(jQuery.isFunction(potentialCallbacks.success)) {
                                successCallback = potentialCallbacks.success;
                            }
                            
                            if(jQuery.isFunction(potentialCallbacks.ex)) {
                                exceptionHandler = potentialCallbacks.ex;
                            } else if(jQuery.isFunction(potentialCallbacks.exceptionHandler)) {
                                exceptionHandler = potentialCallbacks.exceptionHandler;
                            }

                            if(jQuery.isFunction(potentialCallbacks.er)) {
                                errorCallback = potentialCallbacks.er;
                            } else if(jQuery.isFunction(potentialCallbacks.error)) {
                                errorCallback = potentialCallbacks.error;
                            }
                        }
                    }
                    
                    /* We're going to build the request array based upon
                     * version.
                     */
                    if(self.options.version == 1){
                        var tosend = {method: key,params: params,id: id};
                    }else{
                        var tosend = {jsonrpc: '2.0',method: key,params: params,id: id};
                    }

                    /* AJAX away! */
                    jQuery.ajax({
                        async: self.options.async,
                        contentType: 'application/json',
                        type: data.transport,
                        processData: false,
                        dataType: 'json',
                        url: self.options.url,
                        cache: false,
                        data: JSON.stringify(tosend),
                        headers: self.options.headers,
                        beforeSend: self.options.beforeSend,
                        complete: self.options.complete,
                        error: function(req,stat,err){
                            self.error = true;
                            self.error_message = stat;
                            self.error_request = req;

                            if(self.options.async) {
								if(jQuery.isFunction(errorCallback)) {
                                    errorCallback(self,req,stat,err,id,key);
                                }
                                
                                self.options.error(self,req,stat,err,id,key);
                            }
                        },
                        success: function(inp){
                            if((typeof inp.error == 'object') && (inp.error != null)) {
                            	reply = new jQuery.Zend.Zend_Json_Exception(inp.error);
								
								if(jQuery.isFunction(exceptionHandler)) {
									exceptionHandler(reply);
									return;
								} else if(jQuery.isFunction(self.options.exceptionHandler)) {
                                    self.options.exceptionHandler(reply);
                                    return;
                                }
                            } else {
                                reply = inp.result;
                            }

                            if(self.options.async) {
                                if(jQuery.isFunction(successCallback)) {
                                    successCallback(reply,id,key);
                                }

                                self.options.success(reply,id,key);
                            }
                        }
                    });

                    if(self.options.async) {
                        return id;
                    } else {
                        return reply;
                    }
                };

                // Are we name spacing or not ?
                var matches = detectNamespace.exec(key);

                if((!matches) || (!matches.length)) {
                    self[key] = new_method;
                } else {
                    if(!self[matches[1]]) {
                        self[matches[1]] = {};
                    }

                    self[matches[1]][matches[2]] = new_method;
                }
            });

            if(self.options.asyncReflect) {
                self.options.reflectSuccess(self);
            }
        };

        /* Do an ajax request to the server and build our object.
         *
         * Or process the smd passed.
         */
        if(self.options.smd != null) {
            buildCallbacks(self.options.smd);
        } else {
            jQuery.ajax({
                async: self.options.asyncReflect,
                contentType: 'application/json',
                type: 'get',
                processData: false,
                dataType: 'json',
                url: self.options.url,
                cache: false,
                error: function(req,stat,err){
                /* This is a somewhat lame error handling -- maybe we should
                 * come up with something better?
                 */
                    self.error = true;
                    self.error_message = stat;
                    self.error_request = req;

                    if(self.options.asyncReflect) {
                        self.options.reflectError(self,req,stat,err);
                    }
                },
                success: buildCallbacks
            });
        }

        return this;
    })(options);
};
