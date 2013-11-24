<?php
class SnippetTreeTagFilter extends SnippetTreeFilter {
    public function __construct($tag=null) {
        if($tag) {
            $this->tag=$tag;
        }
    
        parent::__construct();
    }
    
    /**
     * @return Array Map of Snippet Language IDs
     */
    public function snippetLanguagesIncluded() {
        $ids=array();
        $q=new SQLQuery();
        $q->setSelect(array('"Snippet"."ID"', '"Snippet"."LanguageID"'))->setFrom('"Snippet"');
        
        if(!empty($this->tag)) {
            $SQL_val=Convert::raw2sql($this->tag);
            $q->setWhereAny(array(
                                "\"Tags\" LIKE '$SQL_val,%' OR \"Tags\" LIKE '%,$SQL_val' OR \"Tags\" LIKE '%,$SQL_val,%' OR \"Tags\" LIKE '$SQL_val'"
                            ));
        }
		
		foreach($q->execute() as $row) {
			$ids[]=array('ID'=>$row['LanguageID']);
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
		
		if(!empty($this->tag)) {
		    $SQL_val=Convert::raw2sql($this->tag);
		    $q->setWhereAny(array(
        		            "\"Tags\" LIKE '$SQL_val,%' OR \"Tags\" LIKE '%,$SQL_val' OR \"Tags\" LIKE '%,$SQL_val,%' OR \"Tags\" LIKE '$SQL_val'"
        		        ));
		}
		
		
		foreach($q->execute() as $row) {
			$ids[]=array('ID'=>$row['ID']);
		}
		
		return $ids;
    }
    
    /**
     * @return Array Map of Snippet Folder IDs
     */
    public function snippetFoldersIncluded() {
        $ids=array();
        $q=new SQLQuery();
        $q->setSelect(array('"Snippet"."ID"', '"Snippet"."FolderID"'))->setFrom('"Snippet"');
        
        if(!empty($this->tag)) {
            $SQL_val=Convert::raw2sql($this->tag);
            $q->setWhereAny(array(
                                "\"Tags\" LIKE '$SQL_val,%' OR \"Tags\" LIKE '%,$SQL_val' OR \"Tags\" LIKE '%,$SQL_val,%' OR \"Tags\" LIKE '$SQL_val'"
                            ));
        }
		
		foreach($q->execute() as $row) {
			$ids[]=array('ID'=>$row['FolderID']);
		}
		
		return $ids;
    }
}
?>