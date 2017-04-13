Code Bank (Server)
=================
[![Latest Stable Version](https://poser.pugx.org/undefinedoffset/silverstripe-codebank/v/stable.png)](https://packagist.org/packages/undefinedoffset/silverstripe-codebank) [![Total Downloads](https://poser.pugx.org/undefinedoffset/silverstripe-codebank/downloads.png)](https://packagist.org/packages/undefinedoffset/silverstripe-codebank) [![Latest Unstable Version](https://poser.pugx.org/undefinedoffset/silverstripe-codebank/v/unstable.png)](https://packagist.org/packages/undefinedoffset/silverstripe-codebank) [![License](https://poser.pugx.org/undefinedoffset/silverstripe-codebank/license.png)](https://packagist.org/packages/undefinedoffset/silverstripe-codebank) [![Build Status](https://travis-ci.org/UndefinedOffset/silverstripe-codebank.png)](https://travis-ci.org/UndefinedOffset/silverstripe-codebank)

Code Bank is a code snippet manager with syntax highlighting for multiple languages including C++, ActionScript, Flex, HTML and SQL to name a few. Code Bank also has a simple revision history with a compare viewer so you can see the changes side-by-side between two revisions.


### Requirements:
* SilverStripe Framework 3.1.x (See http://www.silverstripe.org/system-requirements/ for SilverStripe requirements)
* PHP Zip extension (See http://ca1.php.net/manual/en/book.zip.php for installation instructions)


### Installation (Module)
```
composer require undefinedoffset/silverstripe-codebank 3.*@stable
```

#### Manual Install (Module only)
1. Download and extract the latest Code Bank module release from here http://programs.edchipman.ca/applications/code-bank/
2. Extract the module archive to the root of your SilverStripe installation, opening the extracted folder should contain _config.php in the root along with other files/folders
3. Hit http://{your domain}/{path to root of code bank server folder}/dev/build?flush=all in your browser
4. You will now see a Code Bank option in the CMS Menu


### Installation (Standalone)
1. Download and extract the latest Code Bank release from here http://programs.edchipman.ca/applications/code-bank/
2. Extract the Code Bank to a location on your SilverStripe compatible web server, you should now see a Code Bank folder and a framework folder among others
3. Follow the installation instructions at http://doc.silverstripe.org/framework/en/installation/
4. If the installer completes successfully you will now be able to use the remote server in Code Bank's desktop client. Just set the server path to be http://{your domain}/{path to root of code bank server folder}
5. You should make sure that the SilverStripe installer removed the install files install.php, and install-frameworkmissing.html


### Upgrading to new versions:
#### Module Only (with composer, recommended):
```
composer update --no-dev undefinedoffset/silverstripe-codebank
```

##### Module Only (without composer)
1. Download the latest Code Bank release here http://programs.edchipman.ca/applications/code-bank/
2. Extract the archive to into the same folder as your SilverStripe Framework
3. Hit http://{your domain}/{path to root of code bank server folder}/dev/build?flush=all in your browser


#### Stand Alone (with composer, recommended)
```
composer update --no-dev
```

##### Stand Alone (without composer)
1. Download and extract the latest Code Bank release from http://programs.edchipman.ca/applications/code-bank/ overwriting the Code Bank and themes folders
2. Download and extact just the CodeBank, framework and themes folders replacing only those folders and their children
3. Hit http://{your domain}/{path to root of code bank server folder}/dev/build?flush=all in your browser, then you may use Code Bank normally


### Custom Languages with Brushes
Code Bank uses [SyntaxHighlighter](https://github.com/alexgorbatchev/SyntaxHighlighter) to highlight code snippets, to provide a language with syntax highlighting you must add to your [yml configs](http://doc.silverstripe.org/framework/en/topics/configuration#setting-configuration-via-yaml-files) the following then run dev/build?flush=all.
```yml
CodeBank:
    extra_languages:
        - Name: "Example Language" #Name of the language
          HighlightCode: "example" #Highlighter code
          FileName: "ex" #File extension
          Brush: "mysite/javascript/shBrushEx.js" #Relative Path to the snippet highlighter brush
```


### Switching the search engine
By default Code Bank uses MySQL's fulltext searching and on databases like Postgres it uses a partial match filtering for searching both from the client and in the web interface. Code Bank provides support for switching the engine to Solr if you have the [silverstripe/fulltextsearch](https://github.com/silverstripe-labs/silverstripe-fulltextsearch) installed and a Solr server available. You may switch the search engine by adding the following to your mysite/_config/config.yml after add flush=1 to the url to update the config cache.
```yml
CodeBank:
    snippet_search_engine: "SolrCodeBankSearchEngine"
```

#### Writing your own engine
Code Bank provides an api for you to hook in your own engine, you simply need to implement the [ICodeBankSearchEngine](https://github.com/UndefinedOffset/silverstripe-codebank/blob/master/code/search/ICodeBankSearchEngine.php)  interface and define the methods in the interface. The key thing to remember is that the doSnippetSearch() method takes 3 parameters the first $keyword, is the term/keyword the user is searching for (this maybe empty), the second ($langugeID) is the database ID of the language the user is filtering to. The last parameter ($folderID) is the ID of the folder the system is requesting matches for. The method itself should always return a SS_List subclass typically it should be DataList containing or pointing to only snippets. To enable your custom engine follow the [steps above](#switching-the-search-engine).


### Attribution:
* Some Icons are from the Fudge Icon Set http://p.yusukekamiyamane.com/
* Code Bank Logo is derived from the Tango Desktop Project http://tango.freedesktop.org
* Other icons are from the noun project http://thenounproject.com/
* Code Bank is powered by the SilverStripe framework http://www.silverstripe.org
* Code Bank uses portions of the Zend Framework http://framework.zend.com/
* Syntax highlighting provided by SyntaxHighlighter https://github.com/alexgorbatchev/SyntaxHighlighter
