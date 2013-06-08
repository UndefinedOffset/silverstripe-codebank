<?php
class SnippetVersion extends DataObject {
    private static $db=array(
                            'Text'=>'Text'
                         );
    
    private static $has_one=array(
                                'Parent'=>'Snippet'
                             );
    
    private static $default_sort='Created DESC';
    
    /**
     * Checks to see if the given member can create this object or not
     * @param {Member} $member Member instance or member id to check
     * @return {bool} Returns boolean true or false depending if the user can create this object
     */
    public function canCreate($member=null) {
        return false;
    }
    
    /**
     * Checks to see if the given member can edit this object or not
     * @param {Member} $member Member instance or member id to check
     * @return {bool} Returns boolean true or false depending if the user can edit this object
     */
    public function canEdit($member=null) {
        return false;
    }
    
    /**
     * Checks to see if the given member can delete this object or not
     * @param {Member} $member Member instance or member id to check
     * @return {bool} Returns boolean true or false depending if the user can delete this object
     */
    public function canDelete($member=null) {
        return false;
    }
}
?>