CodeBank (Server)
=================
Code Bank is a code snippet manager with syntax highlighting for multiple languages including C++, ActionScript, Flex, HTML and SQL to name a few. Code Bank also has a simple revision history with a compare viewer so you can see the changes side-by-side between two revisions.


##Requirements:
See http://www.silverstripe.org/system-requirements/ as Code Bank is built on the SilverStripe Framework


##Installation (Standalone):
1. Extract the archive to a location on your SilverStripe compatible web server
2. Follow the installation instructions at http://doc.silverstripe.org/framework/en/installation/
3. If the installer completes successfully you will now be able to use the remote server in Code Bank's desktop client. Just set the server path to be http://{your domain}/{path to root of code bank server folder}
4. You should make sure that the SilverStripe installer removed the install files install.php, and install-frameworkmissing.html


##Installation (Module)
1. Extract the module archive to the root of your SilverStripe installation, opening the extracted folder should contain _config.php in the root along with other files/folders
2. Hit http://{your domain}/{path to root of code bank server folder}/dev/build?flush=all in your browser
3. You will now see a Code Bank option in the CMS Menu


##Upgrading to new versions:
1. Visit http://programs.edchipman.ca/applications/code-bank/ and download the latest version of the server (standalone or module)
2. Extract all files in the archive over your existing install (standalone or module)
3. Backup your code bank server database
4. Hit http://{your domain}/{path to root of code bank server folder}/dev/build?flush=all in your browser
5. If you are installing the standalone file make sure before you upload the new files you remove mysite, .htaccess, webconfig.conf, install.php, and install-frameworkmissing.html. As well you should just overwrite the existing files.


#Attribution:
* Some Icons are from the Fudge Icon Set http://p.yusukekamiyamane.com/
* Code Bank Logo is derived from the Tango Desktop Project http://tango.freedesktop.org
* Other icons are from the noun project http://thenounproject.com/
* Code Bank is powered by the SilverStripe framework http://www.silverstripe.org
* Code Bank uses portions of the Zend Framework http://framework.zend.com/