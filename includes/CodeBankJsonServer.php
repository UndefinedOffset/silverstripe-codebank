<?php
/**
 * @see Zend_Json_Server_Request_Http
 */
require_once 'Zend/Json/Server/Request/Http.php';

class CodeBankJsonServer extends Zend_Json_Server {
    /**
     * Get JSON-RPC request object
     *
     * @return Zend_Json_Server_Request
     */
    public function getRequest() {
        if (null === ($request = $this->_request)) {
            require_once 'Zend/Json/Server/Request/Http.php';
            $this->setRequest(new CodeBankJsonServer_Request_Http());
        }
        return $this->_request;
    }
}

class CodeBankJsonServer_Request_Http extends Zend_Json_Server_Request_Http {
    /**
     * Set request state based on JSON
     *
     * @param  string $json
     * @return void
     */
    public function loadJson($json) {
        require_once 'Zend/Json.php';
        
        $options = Zend_Json::decode($json);
        if(array_key_exists('params', $options)) {
            for($i=0;$i<count($options['params']);$i++) {
                if(is_array($options['params'][$i]) && $this->is_assoc($options['params'][$i])) {
                    $options['params'][$i]=$this->array2object($options['params'][$i]);
                }
            }
        }
        
        $this->setOptions($options);
    }
    
    /**
     * Converts an accociative array to an object
     * @return {stdClass} Standard Class representing the assoc array
     */
    protected function array2object($data) {
        if(!is_array($data)) return $data;
    
        $object = new stdClass();
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $name=>$value) {
                $name = trim($name);
                if (!empty($name)) {
                    $object->$name = $this->array2object($value);
                }
            }
        }
        return $object;
    }
    
    /**
     * Checks to see if an array is associtive
     * @return {bool} Returns boolean true if the array is associative
     */
    private function is_assoc(array $array) {
        if(!empty($array)) {
            for ($iterator=count($array)-1;$iterator;$iterator--) {
                if (!array_key_exists($iterator, $array) ) {
                    return true;
                }
            }
            
            return !array_key_exists(0, $array);
        }
        
        return false;
    }
}
?>