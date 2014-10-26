<?php
interface ICodeBankSearchEngine {
    /**
     * Allows for hooking in to modify the table of the snippet class for the search engine
     */
    public static function requireTable();
    
    /**
     * Performs the search against the snippets in the system
     * @param {string} $keywords Keywords to search for
     * @param {int} $languageID Language to filter to
     * @param {int} $folderID Folder to filter to
     * @return {DataList} Data list pointing to the snippets in the results
     */
    public function doSnippetSearch($keywords, $languageID=false, $folderID=false);
}
?>