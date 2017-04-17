<?php
class PackageViewField extends FormField
{
    protected $sourceList;
    protected $showNested=true;
    protected $currentSnippetID=-1;
    
    /**
     * Initializes the Package View List
     * @param {string} $name Name of the field
     * @param {string} $title Title of the field
     * @param {SS_List} $sourceList Source List to be used
     * @param {mixed} $value Value of the field
     */
    public function __construct($name, $title, $sourceList, $currentSnippetID=-1, $value=null)
    {
        $this->sourceList=$sourceList;
        $this->currentSnippetID=$currentSnippetID;
        
        parent::__construct($name, $title, $value);
    }
    
    /**
     * Sets the source list
     * @param {SS_List} $list Source List
     * @return {PackageViewField} Returns this
     */
    public function setSourceList($list)
    {
        $this->sourceList=$list;
        
        return $this;
    }
    
    /**
     * Gets the source list
     * @return {SS_List} Source List
     */
    public function getSourceList()
    {
        return $this->sourceList;
    }
    
    /**
     * Show the nested package contents
     * @param {bool} $val Value to set to
     * @return {PackageViewField} Returns this
     */
    public function setShowNested($val)
    {
        $this->showNested=false;
        
        return $this;
    }
    
    /**
     * Gets the whether to show the nested package contents or not
     * @return {bool} Returns boolean true to show false otherwise
     */
    public function getShowNested()
    {
        return $this->showNested;
    }
    
    /**
     * Returns a "field holder" for this field - used by templates.
     * @param {array} $properties key value pairs of template variables
     * @return {string} HTML to be used
     */
    public function FieldHolder($properties=array())
    {
        $obj=($properties ? $this->customise($properties):$this);
        
        
        Requirements::css(CB_DIR.'/css/PackageViewField.css');
        Requirements::javascript(CB_DIR.'/javascript/PackageViewField.js');
        
        return $obj->renderWith($this->getFieldHolderTemplates());
    }
    
    /**
     * Sets the current snippet id
     * @param {int} ID of the current snippet
     * @return {PackageViewField} Returns this
     */
    public function setCurrentSnippetID()
    {
        $this->currentSnippetID=$id;
        
        return $this;
    }
    
    /**
     * Gets the current snippet id
     * @return {int} ID of the current snippet
     */
    public function getCurrentSnippetID()
    {
        return $this->currentSnippetID;
    }
    
    /**
     * Returns a readonly version of this field
     */
    public function performReadonlyTransformation()
    {
        return $this;
    }
}
