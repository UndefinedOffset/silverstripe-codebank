<?php
class SnippetTreeFilter extends Object {
    /**
     * @var Array Search parameters, mostly properties on {@link SiteTree}.
     * Caution: Unescaped data.
     */
    protected $params = array();
    
    /**
     * @var Array
     */
    protected $_cache_language_ids = null;
    
    /**
     * @var Array
     */
    protected $_cache_snippet_ids = null;
    
    /**
     * @var String
     */
    protected $childrenMethod = null;
    
    public function __construct($params=null) {
        if($params) {
            $this->params=$params;
        }
    
        parent::__construct();
    }
    
    /**
     * @return {string} Method on {@link Hierarchy} objects which is used to traverse into children relationships.
     */
    public function getChildrenMethod() {
        return $this->childrenMethod;
    }
    
    /**
     * @return Array Map of Snippet Language IDs
     */
    public function snippetLanguagesIncluded() {
        if($this->_cache_snippet_ids===null) {
            $this->populateSnippetIDs();
        }
        
        if(empty($this->_cache_snippet_ids)) {
            return array();
        }
        
        $ids=array();
        
        $q=new SQLQuery();
		$q->setSelect(array('"SnippetLanguage"."ID"'))
		    ->setFrom('"SnippetLanguage"')
		    ->addInnerJoin('Snippet', '"Snippet"."LanguageID"="SnippetLanguage"."ID"')
		    ->setGroupBy('"SnippetLanguage"."ID"');
		
		if(isset($this->params['LanguageID']) && !empty($this->params['LanguageID'])) {
		    $q->addWhere('"SnippetLanguage"."ID"='.intval($this->params['LanguageID']));
		}
		
		$q->addWhere('"Snippet"."ID" IN('.implode(',', array_keys($this->_cache_snippet_ids)).')');
		
		foreach($q->execute() as $row) {
			$ids[]=array('ID'=>$row['ID']);
		}
		
		return $ids;
    }
    
    /**
     * @return Array Map of Snippet IDs
     */
    public function snippetsIncluded() {
        $ids=array();
        $q=new SQLQuery();
		$q->setSelect(array('"Snippet"."ID"', '"Snippet"."LanguageID"'))->setFrom('"Snippet"');
		
		if(isset($this->params['LanguageID']) && !empty($this->params['LanguageID'])) {
		    $q->addWhere('"Snippet"."LanguageID"='.intval($this->params['LanguageID']));
		}
		
		if(isset($this->params['Term']) && !empty($this->params['Term'])) {
		    $SQL_val=Convert::raw2sql($this->params['Term']);
		    $q->setWhereAny(array(
        		            "\"Title\" LIKE '%$SQL_val%'",
        		            "\"Description\" LIKE '%$SQL_val%'",
        		            "\"Tags\" LIKE '%$SQL_val%'"
        		        ));
		}
		
		
		foreach($q->execute() as $row) {
			$ids[]=array('ID'=>$row['ID']);
		}
		
		return $ids;
    }
    
    /**
     * Populate the IDs of the snippet languages returned by snippetLanguagesIncluded()
     */
    protected function populateLanguageIDs() {
        $this->_cache_language_ids=array();
        if($snippetLanguages=$this->snippetLanguagesIncluded()) {
            // And keep a record of parents we don't need to get
            // parents of themselves, as well as IDs to mark
            foreach($snippetLanguages as $langArr) {
                $this->_cache_language_ids[$langArr['ID']]=true;
            }
        }
    }
    
    /**
     * Populate the IDs of the snippets returned by snippetsIncluded()
     */
    protected function populateSnippetIDs() {
        $this->_cache_snippet_ids=array();
        if($snippets=$this->snippetsIncluded()) {
            // And keep a record of parents we don't need to get
            // parents of themselves, as well as IDs to mark
            foreach($snippets as $snippetArr) {
                $this->_cache_snippet_ids[$snippetArr['ID']]=true;
            }
        }
    }
    
    /**
     * Returns TRUE if the given snippet or language should be included in the tree.
     * @param {SnippetLanguage|Snippet} $obj Snippetor Language to be checked
     * @return {bool} Returns boolean true if the snippet or language should be included in the tree false otherwise
     */
    public function isSnippetLanguageIncluded($obj) {
        if($obj instanceof SnippetLanguage) {
            if($this->_cache_language_ids===null) {
                $this->populateLanguageIDs();
            }
            
            return (isset($this->_cache_language_ids[$obj->ID]) && $this->_cache_language_ids[$obj->ID]);
        }else if($obj instanceof Snippet) {
            if($this->_cache_snippet_ids===null) {
                $this->populateSnippetIDs();
            }
            
            return (isset($this->_cache_snippet_ids[$obj->ID]) && $this->_cache_snippet_ids[$obj->ID]);
        }
        
        return false;
    }
}
?>