<?php
/**
 * Catches a PHP Error Message and throws it as an exception.
 * @param {int} $errno The level of the error raised, as an integer.
 * @param {string} $errstr The error message, as a string.
 * @param {string} $errfile The filename that the error was raised in, as a string.
 * @param {int} $errline The line number the error was raised at, as an integer.
 */
function error_handler($errno, $errstr, $errfile, $errline){
    if($errno!=0 && $errno!=E_STRICT && $errno!=E_NOTICE){
        if($_SERVER['REMOTE_ADDR']=='127.0.0.1'){
            throw new Exception($errstr." in ".$errfile." on line ".$errline);
        }else if(strpos($errstr,'MYSQL')===false){
            throw new Exception('An server error has occured please contact the website administrator.');
        }else {
            throw new Exception('Failed to query the database');
        }
    }
}

/**
 * Catches an Exception, flushes the output buffer and if the IP is localhost displays the error message. If not it displays a message saying an error occured.
 */
function exception_handler($aException){
    restore_error_handler();
    restore_exception_handler();
    
    //@TODO: Insert Logging code here
}
?>
