<?php
class DefaultCodeBankSearchEngine implements ICodeBankSearchEngine
{
    /**
     * Allows for hooking in to modify the table of the snippet class for the search engine
     */
    public static function requireTable()
    {
        //Add fulltext searchable extension
        Snippet::add_extension("FulltextSearchable('Title,Description,Tags')");
        
        //Change to MyISAM for the engine for snippet tables
        Config::inst()->update('Snippet', 'create_table_options', array('MySQLDatabase'=>'ENGINE=MyISAM'));
    }
    
    /**
     * Performs the search against the snippets in the system
     *
     * @param {string} $keywords Keywords to search for
     * @param {int} $languageID Language to filter to
     * @param {int} $folderID Folder to filter to
     * @return {DataList} Data list pointing to the snippets in the results
     */
    public function doSnippetSearch($keywords, $languageID=false, $folderID=false)
    {
        $list=Snippet::get();
        
        if (isset($languageID) && !empty($languageID)) {
            $list=$list->filter('LanguageID', intval($languageID));
        }
        
        
        if (isset($folderID) && !empty($folderID)) {
            $list=$list->filter('FolderID', intval($folderID));
        }
        
        
        if (isset($keywords) && !empty($keywords)) {
            $SQL_val=Convert::raw2sql($keywords);
            if (DB::getConn() instanceof MySQLDatabase) {
                $list=$list->where("MATCH(\"Title\", \"Description\", \"Tags\") AGAINST('".$SQL_val."' IN BOOLEAN MODE)");
            } else {
                $list=$list->filterAny(array(
                                        'Title:PartialMatch'=>$SQL_val,
                                        'Description:PartialMatch'=>$SQL_val,
                                        'Tags:PartialMatch'=>$SQL_val
                                    ));
            }
        }
        
        return $list;
    }
}
