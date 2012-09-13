<?php
$errorMsg='';

if(file_exists(dirname(__FILE__).'/config/database.php')) {
    $errorMsg='Code Bank Server appears to be installed, if you are upgrading please use the <a href="update.php">database updater</a>';
}

require_once(dirname(__FILE__).'/includes/func.inc.php');

set_error_handler('install_error_handler');

/**
 * Catches a PHP Error Message and throws it as an exception.
 * @param {int} $errno The level of the error raised, as an integer.
 * @param {string} $errstr The error message, as a string.
 * @param {string} $errfile The filename that the error was raised in, as a string.
 * @param {int} $errline The line number the error was raised at, as an integer.
 */
function install_error_handler($errno, $errstr, $errfile, $errline){
    global $errorMsg;
    
    if($errno!=0 && $errno!=E_STRICT){
        if($_SERVER['REMOTE_ADDR']=='127.0.0.1'){
            $errorMsg=$errstr." in ".$errfile." on line ".$errline;
        }else if(strpos($errstr,'MYSQL')===false){
            $errorMsg='An server error has occured please contact the website administrator.';
        }else {
            $errorMsg='Failed to query the database';
        }
    }
}

if(array_key_exists('doSave', $_POST) && $_POST['doSave']=='true' && empty($errorMsg)) {
    if(trim($_POST['mysqlServer'])!='' && trim($_POST['mysqlDatabase'])!='' && trim($_POST['mysqlUsername'])!='' && trim($_POST['mysqlPassword'])!='' && trim($_POST['password'])!='' && trim($_POST['confPass'])!='') {
        /** Generate the config file **/
        $template="<?php\n".
                  "define('MYSQL_SERVER','".str_replace("'",'',$_POST['mysqlServer'])."');\n".
                  "define('MYSQL_USER','".str_replace("'",'',$_POST['mysqlUsername'])."');\n".
                  "define('MYSQL_PASSWORD','".str_replace("'",'',$_POST['mysqlPassword'])."');\n".
                  "define('MYSQL_DATABASE','".str_replace("'",'',$_POST['mysqlDatabase'])."');\n".
                  "?>";
        
        //Write the config file
        $f=fopen(dirname(__FILE__).'/config/database.php','w');
        fwrite($f,$template);
        fclose($f);
        
        
        //Attempt a connection to the database
        $db = ADONewConnection('mysql');
        $db->Connect($_POST['mysqlServer'], $_POST['mysqlUsername'], $_POST['mysqlPassword'], $_POST['mysqlDatabase'],true);
        $db->SetFetchMode(ADODB_FETCH_ASSOC);
        
        if($db!==false){
            $sql=explode('$',file_get_contents(dirname(__FILE__).'/install.sql'));
            unset($sql[count($sql)-1]);
            
            $db->startTrans();
            
            try {
                foreach($sql as $query) {
                    if(empty($query)) {
                        continue;
                    }
                    
                    //Run the query
                    $db->execute($query);
                }
                
                //Insert the admin user
                $db->execute("INSERT INTO users (username,password) VALUES ('admin','".encSQLString(sha1($_POST['password']))."')");
            }catch (Exception $e) {
                $db->FailTrans();
                
                //Error installing the database
                $errorMsg='Could not install the database, please make sure that the code bank user has full access to the database specified';
            }
            
            //Commit the transaction and close the database
            $db->CompleteTrans();
            $db->close();
            
            if($errorMsg=='') {
                //Delete the install files
                if(@unlink(dirname(__FILE__).'/install.php')==false || @unlink(dirname(__FILE__).'/install.sql')==false || @unlink(dirname(__FILE__).'/update.php')==false) {
                    $goodMsg="Install Completed, please use Code Bank to login as the <b>admin</b> user to add or delete user accounts.";
                }else {
                    $goodMsg="Install Completed, please remove install.php, install.sql, and update.php. Use Code Bank to login as the <b>admin</b> user to add or delete user accounts.";
                }
            }
        }else {
            $errorMsg='Failed to Connect to Database';
        }
    }else {
        $errorMsg="You did not fill in all of the fields";
    }
}

echo '<?xml version="1.0" encoding="UTF-8" ?>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>Code Bank: Server Installer</title>
        
        <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
        <meta name="robots" content="noindex,nofollow"/>
        
        <link rel="shortcut icon" href="favicon.ico" />
        
        <link type="text/css" rel="stylesheet" href="css/style.css"/>
    </head>
    <body>
        <?php
        if(!empty($errorMsg)) {
            ?>
            <div id="NotifyArea" class="error">
                <div class="content"><?php echo $errorMsg; ?></div>
            </div>
            <?php
        }else if(!empty($goodMsg)) {
            ?>
            <div id="NotifyArea" class="good">
                <div class="content"><?php echo $goodMsg; ?></div>
            </div>
            <?php
        }
        ?>
        <div id="bodyWrapper">
            <div class="header">
                <div id="Logo">@@VERSION@@</div>
            </div>
            
            <div id="contentWrapper" style="margin-top: <?php if(!empty($errorMsg) || !empty($goodMsg)) { ?>60<?php }else { ?>40<?php } ?>px;">
                <h1>Server Installer</h1>
                <p><em>Please fill in all fields</em></p>
               
                <div class="content">
                    <form method="post" action="install.php" id="installForm" enctype="application/x-www-form-urlencoded">
                        <fieldset>
                            <legend>MySQL Settings</legend>
                            <div class="field">
                                <label for="mysqlServer">Server:</label>
                                <input name="mysqlServer" id="mysqlServer" type="text" value="localhost" class="required"/>
                            </div>
                            
                            <div class="field">
                                <label for="mysqlDatabase">Database Name:</label>
                                <input name="mysqlDatabase" id="mysqlDatabase" type="text" value="" class="required"/>
                            </div>
                            
                            <div class="field">
                                <label for="mysqlUsername">Username:</label>
                                <input name="mysqlUsername" id="mysqlUsername" type="text" value="root" class="required"/>
                            </div>
                            
                            <div class="field">
                                <label for="mysqlPassword">Password:</label>
                                <input name="mysqlPassword" id="mysqlPassword" type="password" value="" class="required"/>
                            </div>
                        </fieldset>
                       
                        <fieldset>
                            <legend>Administrator Settings</legend>
                            
                            <div class="field">
                                <label for="password">Password:</label>
                                <input name="password" id="password" type="password" class="required"/>
                            </div>
                            
                            <div class="field">
                                <label for="confPass">Confirm Password:</label>
                                <input name="confPass" id="confPass" type="password" class="required"/>
                            </div>
                        </fieldset>
                       
                        <p style="margin-top: 20px;">
                            <input type="hidden" id="doSave" name="doSave" value="false"/>
                            <button type="button" id="InstallForm_Submit" title="Save"><!--  --></button>
                        </p>
                   </form>
               </div>
           </div>
            
            <div class="footer">
                <p>
                    Code Bank Copyright 2011 Ed Chipman<br/>
                    Code Bank is licensed under a <a href="http://creativecommons.org/licenses/by-nc-nd/3.0/" target="_blank">Creative Commons Attribution-Noncommercial-No Derivative Works 3.0 License</a><br/>
                    Any part of the license can be waived with permission from the copyright holder.
                </p>
            </div>
        </div>
        
        <script type="text/javascript" src="javascript/external/jquery-packed.js"></script>
        <script type="text/javascript" src="javascript/external/jquery.validate.min.js"></script>
        <script type="text/javascript">
            //<![CDATA[
                (function($) {
                    $(document).ready(function() {
                        $('#installForm').validate();

                        $('#InstallForm_Submit').click(function(e) {
                            if($('#installForm').valid()) {
                                $('#doSave').val('true');
                                $('#installForm').submit();
                            }
                            
                            e.stopPropagation();
                            return false;
                        });
                    });
                })(jQuery);
            //]]>
        </script>
    </body>
</html>