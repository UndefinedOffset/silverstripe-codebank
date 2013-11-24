<?php
class SnippetTreeCreatorFilter extends SnippetTreeFilter {
    /**
     * @return Array Map of Snippet Language IDs
     */
    public function snippetLanguagesIncluded() {
        $ids=array();
        $q=new SQLQuery();
        $q->setSelect(array('"Snippet"."ID"', '"Snippet"."LanguageID"'))->setFrom('"Snippet"');
        
        if(!empty($this->creator)) {
            $q->setWhereAny(array(
                                "\"CreatorID\"=".intval($this->creator)
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
		
		if(!empty($this->creator)) {
		    $q->setWhereAny(array(
        		            "\"CreatorID\"=".intval($this->creator)
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
        
        if(!empty($this->creator)) {
            $q->setWhereAny(array(
                                "\"CreatorID\"=".intval($this->creator)
                            ));
        }
		
		foreach($q->execute() as $row) {
			$ids[]=array('ID'=>$row['FolderID']);
		}
		
		return $ids;
    }
}
?>