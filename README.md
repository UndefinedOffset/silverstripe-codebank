Code Bank (Server)
=================
Code Bank is a code snippet manager with syntax highlighting for multiple languages including C++, ActionScript, Flex, HTML and SQL to name a few. Code Bank also has a simple revision history with a compare viewer so you can see the changes side-by-side between two revisions.


##Requirements:
* SilverStripe Framework 3.1.x (See http://www.silverstripe.org/system-requirements/ for SilverStripe requirements)


##Installation (Module)
1. Download from here https://github.com/UndefinedOffset/silverstripe-codebank/releases
2. Extract the module archive to the root of your SilverStripe installation, opening the extracted folder should contain _config.php in the root along with other files/folders
3. Hit http://{your domain}/{path to root of code bank server folder}/dev/build?flush=all in your browser
4. You will now see a Code Bank option in the CMS Menu

If you prefer you may also install using composer:
```
composer require undefinedoffset/silverstripe-codebank
```

###Installation (Standalone):
1. Download and extract the latest Code Bank release from here http://programs.edchipman.ca/applications/code-bank/
2. Extract the archive to a location on your SilverStripe compatible web server
3. Follow the installation instructions at http://doc.silverstripe.org/framework/en/installation/
4. If the installer completes successfully you will now be able to use the remote server in Code Bank's desktop client. Just set the server path to be http://{your domain}/{path to root of code bank server folder}
5. You should make sure that the SilverStripe installer removed the install files install.php, and install-frameworkmissing.html


##Upgrading to new versions:
####Module Only
1. Download the latest Code Bank release here https://github.com/UndefinedOffset/silverstripe-codebank/releases
2. Extract the archive to into the same folder as your SilverStripe Framework
3. Hit http://{your domain}/{path to root of code bank server folder}/dev/build?flush=all in your browser


Upgrading via composer (recommended):
```
composer update --no-dev undefinedoffset/silverstripe-codebank
```

####Stand Alone
1. Download and extract the latest Code Bank release from http://programs.edchipman.ca/applications/code-bank/
2. Replace just the framework folder, CodeBank and themes folders, you do not need the others
3. Hit http://{your domain}/{path to root of code bank server folder}/dev/build?flush=all in your browser

Upgrading via composer (recommended):
```
composer update --no-dev
```


##Custom Languages with Brushes
Code Bank uses SyntaxHighlighter (https://github.com/alexgorbatchev/SyntaxHighlighter) to highlight code snippets, to provide a language with syntax highlighting you must add to your [http://doc.silverstripe.org/framework/en/topics/configuration] yml configs the following then run dev/build (see the installation section for this url).
```yml
CodeBank:
    extra_languages:
        - Name: "Example Language" #Name of the language
          HighlightCode: "example" #Highlighter code
          FileName: "ex" #File extension
          Brush: "mysite/javascript/shBrushEx.js" #Relative Path to the snippet highlighter brush
```

#Attribution:
* Some Icons are from the Fudge Icon Set http://p.yusukekamiyamane.com/
* Code Bank Logo is derived from the Tango Desktop Project http://tango.freedesktop.org
* Other icons are from the noun project http://thenounproject.com/
* Code Bank is powered by the SilverStripe framework http://www.silverstripe.org
* Code Bank uses portions of the Zend Framework http://framework.zend.com/
* Syntax highlighting provided by SyntaxHighlighter https://github.com/alexgorbatchev/SyntaxHighlighter