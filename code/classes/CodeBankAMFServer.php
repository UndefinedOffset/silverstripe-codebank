<?php
class CodeBankAMFServer extends Zend_Amf_Server {
    /**
     * Loads a remote class or method and executes the function and returns the result
     * @param {string} $method Is the method to execute
     * @param {mixed} $param values for the method
     * @return {mixed} $response the result of executing the method
     * @throws Zend_Amf_Server_Exception
     */
    protected function _dispatch($method, $params=null, $source=null) {
        if($source) {
            if(($mapped=Zend_Amf_Parse_TypeLoader::getMappedClassName($source))!==false) {
                $source=$mapped;
            }
        }
        
        $qualifiedName=(empty($source) ? $method:$source.'.'.$method);
        
        if(!isset($this->_table[$qualifiedName])) {
            // if source is null a method that was not defined was called.
            if($source) {
                $className='CodeBank'.str_replace('.', '_', $source);
                if(class_exists($className, false) && !isset($this->_classAllowed[$className])) {
                    require_once 'Zend/Amf/Server/Exception.php';
                    throw new Zend_Amf_Server_Exception('Can not call "'.$className.'" - use setClass()');
                }
                
                try {
                    $this->getLoader()->load($className);
                } catch (Exception $e) {
                    require_once 'Zend/Amf/Server/Exception.php';
                    throw new Zend_Amf_Server_Exception('Class "'.$className.'" does not exist: '.$e->getMessage(), 0, $e);
                }
                
                // Add the new loaded class to the server.
                $this->setClass($className, $source);
            }
            
            if(!isset($this->_table[$qualifiedName])) {
                // Source is null or doesn't contain specified method
                require_once 'Zend/Amf/Server/Exception.php';
                throw new Zend_Amf_Server_Exception('Method "'.$method.'" does not exist');
            }
        }
    
        $info=$this->_table[$qualifiedName];
        $argv=$info->getInvokeArguments();
    
        if(0<count($argv)) {
            $params=array_merge($params, $argv);
        }
    
        if($info instanceof Zend_Server_Reflection_Function) {
            $func=$info->getName();
            $this->_checkAcl(null, $func);
            $return=call_user_func_array($func, $params);
        }else if($info instanceof Zend_Server_Reflection_Method) {
            // Get class
            $class=$info->getDeclaringClass()->getName();
            if('static'==$info->isStatic()) {
                // for some reason, invokeArgs() does not work the same as
                // invoke(), and expects the first argument to be an object.
                // So, using a callback if the method is static.
                $this->_checkAcl($class, $info->getName());
                $return=call_user_func_array(array($class, $info->getName()), $params);
            }else {
                // Object methods
                try {
                    $object=$info->getDeclaringClass()->newInstance();
                }catch(Exception $e) {
                    throw new Zend_Amf_Server_Exception('Error instantiating class '.$class.' to invoke method '.$info->getName().': '.$e->getMessage(), 621, $e);
                }
                
                $this->_checkAcl($object, $info->getName());
                $return=$info->invokeArgs($object, $params);
            }
        }else {
            throw new Zend_Amf_Server_Exception('Method missing implementation '.get_class($info));
        }
    
        return $return;
    }

    /**
     * (Re)Build the dispatch table. The dispatch table consists of a an array of method name => Zend_Server_Reflection_Function_Abstract pairs
     * @return void
     */
    protected function _buildDispatchTable() {
        $table=array();
        foreach($this->_methods as $key=>$dispatchable) {
            if($dispatchable instanceof Zend_Server_Reflection_Function_Abstract) {
                $ns=str_replace('CodeBank', '', $dispatchable->getNamespace());
                $name=$dispatchable->getName();
                $name=(empty($ns) ? $name:$ns.'.'.$name);
                
                if(isset($table[$name])) {
                    throw new Zend_Amf_Server_Exception('Duplicate method registered: '.$name);
                }
                
                $table[$name]=$dispatchable;
                continue;
            }
            
            if($dispatchable instanceof Zend_Server_Reflection_Class) {
                foreach($dispatchable->getMethods() as $method) {
                    $ns=str_replace('CodeBank', '', $method->getNamespace());
                    $name=$method->getName();
                    $name=(empty($ns) ? $name:$ns.'.'.$name);
                    
                    if(isset($table[$name])) {
                        throw new Zend_Amf_Server_Exception('Duplicate method registered: '.$name);
                    }
                    
                    $table[$name]=$method;
                    continue;
                }
            }
        }
        
        $this->_table=$table;
    }
    
    /**
     * Whether of not the server is using sessions
     * @return bool
     */
    public function isSession() {
        return ($this->_session || array_key_exists($this->_sessionName, $_COOKIE));
    }
}
?>