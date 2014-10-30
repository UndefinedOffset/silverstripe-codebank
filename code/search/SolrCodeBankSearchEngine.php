<?php
class SolrCodeBankSearchEngine implements ICodeBankSearchEngine {
    /**
     * Allows for hooking in to modify the table of the snippet class for the search engine
     */
    public static function requireTable() {}
    
    /**
     * Performs the search against the snippets in the system
     *
     * @param {string} $keywords Keywords to search for
     * @param {int} $languageID Language to filter to
     * @param {int} $folderID Folder to filter to
     * @return {DataList} Data list pointing to the snippets in the results
     */
    public function doSnippetSearch($keywords, $languageID=false, $folderID=false) {
        $searchIndex=singleton('CodeBankSolrIndex');
        $searchQuery=new SearchQuery();
        $searchQuery->classes=array(
                                    array(
                                            'class'=>'Snippet',
                                            'includeSubclasses'=>true
                                        )
                                );
        
        //Add language filtering
        if($languageID!==false && $languageID>0) {
            $searchQuery->filter('Snippet_LanguageID', $languageID);
        }
        
        //Add language filtering
        if($folderID!==false && $folderID>0) {
            $searchQuery->filter('Snippet_FolderID', $folderID);
        }
        
        //Configure search
        $searchQuery->search($keywords, null, array(
                                                    'Snippet_Title'=>2,
                                                    'Snippet_Description'=>1
                                                ));
        
        return $searchIndex->search($searchQuery, null, null)->Matches->getList();
    }
}

class CodeBankSolrIndex extends SolrIndex {
    public function init() {
        //Add the snippet class
        $this->addClass('Snippet');
        
        //Add fulltext fields
        $this->addFulltextField('Title');
        $this->addFulltextField('Description');
        $this->addFulltextField('Tags');
        
        //Add filter fields
        $this->addFilterField('LanguageID');
        $this->addFilterField('FolderID');
    }
}
?>