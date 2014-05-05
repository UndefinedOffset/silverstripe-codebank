Code Bank (Server)
=================
[![Latest Stable Version](https://poser.pugx.org/undefinedoffset/silverstripe-codebank/v/stable.png)](https://packagist.org/packages/undefinedoffset/silverstripe-codebank) [![Total Downloads](https://poser.pugx.org/undefinedoffset/silverstripe-codebank/downloads.png)](https://packagist.org/packages/undefinedoffset/silverstripe-codebank) [![Latest Unstable Version](https://poser.pugx.org/undefinedoffset/silverstripe-codebank/v/unstable.png)](https://packagist.org/packages/undefinedoffset/silverstripe-codebank) [![License](https://poser.pugx.org/undefinedoffset/silverstripe-codebank/license.png)](https://packagist.org/packages/undefinedoffset/silverstripe-codebank) [![Build Status](https://travis-ci.org/UndefinedOffset/silverstripe-codebank.png)](https://travis-ci.org/UndefinedOffset/silverstripe-codebank)

Code Bank is a code snippet manager with syntax highlighting for multiple languages including C++, ActionScript, Flex, HTML and SQL to name a few. Code Bank also has a simple revision history with a compare viewer so you can see the changes side-by-side between two revisions.


###Requirements:
* SilverStripe Framework 3.1.x (See http://www.silverstripe.org/system-requirements/ for SilverStripe requirements)


###Installation (Module)
```
composer require undefinedoffset/silverstripe-codebank
```

####Manual Install (Module only)
1. Download and extract the latest Code Bank module release from here http://programs.edchipman.ca/applications/code-bank/
2. Extract the module archive to the root of your SilverStripe installation, opening the extracted folder should contain _config.php in the root along with other files/folders
3. Hit http://{your domain}/{path to root of code bank server folder}/dev/build?flush=all in your browser
4. You will now see a Code Bank option in the CMS Menu


###Installation (Standalone)
1. Download and extract the latest Code Bank release from here http://programs.edchipman.ca/applications/code-bank/
2. Extract the Code Bank to a location on your SilverStripe compatible web server, you should now see a Code Bank folder and a framework folder among others
3. Follow the installation instructions at http://doc.silverstripe.org/framework/en/installation/
4. If the installer completes successfully you will now be able to use the remote server in Code Bank's desktop client. Just set the server path to be http://{your domain}/{path to root of code bank server folder}
5. You should make sure that the SilverStripe installer removed the install files install.php, and install-frameworkmissing.html


###Upgrading to new versions:
####Module Only (with composer, recommended):
```
composer update --no-dev undefinedoffset/silverstripe-codebank
```

#####Module Only (without composer)
1. Download the latest Code Bank release here http://programs.edchipman.ca/applications/code-bank/
2. Extract the archive to into the same folder as your SilverStripe Framework
3. Hit http://{your domain}/{path to root of code bank server folder}/dev/build?flush=all in your browser


####Stand Alone (with composer, recommended)
```
composer update --no-dev
```

#####Stand Alone (without composer)
1. Download and extract the latest Code Bank release from http://programs.edchipman.ca/applications/code-bank/ overwriting the Code Bank and themes folders
2. Download and extact just the CodeBank, framework and themes folders replacing only those folders and their children
3. Hit http://{your domain}/{path to root of code bank server folder}/dev/build?flush=all in your browser, then you may use Code Bank normally


###Custom Languages with Brushes
Code Bank uses [SyntaxHighlighter](https://github.com/alexgorbatchev/SyntaxHighlighter) to highlight code snippets, to provide a language with syntax highlighting you must add to your [yml configs](http://doc.silverstripe.org/framework/en/topics/configuration#setting-configuration-via-yaml-files) the following then run dev/build?flush=all.
```yml
CodeBank:
    extra_languages:
        - Name: "Example Language" #Name of the language
          HighlightCode: "example" #Highlighter code
          FileName: "ex" #File extension
          Brush: "mysite/javascript/shBrushEx.js" #Relative Path to the snippet highlighter brush
```

###Attribution:
* Some Icons are from the Fudge Icon Set http://p.yusukekamiyamane.com/
* Code Bank Logo is derived from the Tango Desktop Project http://tango.freedesktop.org
* Other icons are from the noun project http://thenounproject.com/
* Code Bank is powered by the SilverStripe framework http://www.silverstripe.org
* Code Bank uses portions of the Zend Framework http://framework.zend.com/
* Syntax highlighting provided by SyntaxHighlighter https://github.com/alexgorbatchev/SyntaxHighlighter