<?php
 /**
 *  ZendAmfServiceBrowser is used by the ZamfBrowser to retrieve information
 *  about the Zend_Amf_Server object and return method information to ZamfBrowser.
 *  ZamfBrowser uses the XML return to allow developers to unit test service methods.
 * 
 *  Latest information on this project can be found at http://www.zamfbrowser.org and http://www.almerblanklabs.com
 * 
 *  Copyright (c) 2008 Omar Gonzalez 
 *  @author Omar Gonzalez
 *  Created on Nov 9, 2009
 * 
 *  Permission is hereby granted, free of charge, to any person obtaining a 
 *  copy of this software and associated documentation files (the "Software"), 
 *  to deal in the Software without restriction, including without limitation 
 *  the rights to use, copy, modify, merge, publish, distribute, sublicense, 
 *  and/or sell copies of the Software, and to permit persons to whom the Software 
 *  is furnished to do so, subject to the following conditions:
 * 
 *  The above copyright notice and this permission notice shall be included in all 
 *  copies or substantial portions of the Software.
 * 
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, 
 *  INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A 
 *  PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT 
 *  HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION 
 *  OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE 
 *  SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE. 
 * -------------------------------------------------------------------------------------------
 **/
class ZendAmfServiceBrowser
{
	static public $ZEND_AMF_SERVER;
	
	private $_zendAmfServer;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
	}
	
	/**
	 * The getServices() method returns all of the available services for the Zend_Amf_Server object.
	 * 
	 * @return array Table of parameters for each method key ("Class.method").
	 */
	public function getServices()
	{
		$this->_zendAmfServer = ZendAmfServiceBrowser::$ZEND_AMF_SERVER;
		
		$methods = $this->_zendAmfServer->getFunctions();

		$methodsTable = '<methods>';
		foreach ($methods as $method => $value)
		{
			
			$functionReflection = $methods[ $method ];
			
			$parameters = $functionReflection->getParameters();
			
			$methodsTable = $methodsTable . "<method name='$method'>";
			
			foreach ($parameters as $param)
			{
				$methodsTable = $methodsTable . "<param name='$param->name'/>";
			}
			
			$methodsTable = $methodsTable . "</method>";
		}
		
		$methodsTable = $methodsTable . '</methods>';
		
		unset( $methods );
		return $methodsTable;
	}
}