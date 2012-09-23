<?php
class CodeBank extends LeftAndMain implements PermissionProvider {
    public static $url_segment='codeBank';
    public static $tree_class='SnippetLanguage';
    
    public static $required_permission_codes=array(
                                                    'CODE_BANK_ACCESS'
                                                );
    
    public static $allowed_actions=array(
                                        'tree',
                                        'EditForm'
                                    );
    
    public static $session_namespace='CodeBank';
    
    public function init() {
        parent::init();
        
        Requirements::css('CodeBank/css/CodeBank.css');
        Requirements::javascript('CodeBank/javascript/CodeBank.Tree.js');
    }
    
    /**
     * @param Int $id
     * @param {FieldList} $fields
     * @return {Form}
     */
    public function getEditForm($id=null, $fields=null) {
        if(!$id) {
            $id=$this->currentPageID();
        }
        
        
        $form=parent::getEditForm($id);
        
        
        // TODO Duplicate record fetching (see parent implementation)
        $record=$this->getRecord($id);
        if($record && !$record->canView()) {
            return Security::permissionFailure($this);
        }
        
        
        if(!$fields) {
            $fields=$form->Fields();
        }
        
        
        $actions=$form->Actions();
        
        
        if($record) {
            $fields->push($idField=new HiddenField("ID", false, $id));
            $actions=new FieldList();
            
            
            // Use <button> to allow full jQuery UI styling
            $actionsFlattened=$actions->dataFields();
            if($actionsFlattened) {
                foreach($actionsFlattened as $action) {
                    $action->setUseButtonTag(true);
                }
            }
            
            
            if($record->hasMethod('getCMSValidator')) {
                $validator=$record->getCMSValidator();
            }else {
                $validator=new RequiredFields();
            }
            
            
            $form=new Form($this, 'EditForm', $fields, $actions, $validator);
            $form->loadDataFrom($record);
            $form->disableDefaultAction();
            $form->addExtraClass('cms-edit-form');
            $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
            // TODO Can't merge $FormAttributes in template at the moment
            $form->addExtraClass('center '.$this->BaseCSSClasses());
            $form->setAttribute('data-pjax-fragment', 'CurrentForm');
            
            $readonlyFields=$form->Fields()->makeReadonly();
            //@TODO Replace the Text field with a custom field that enables syntax highlighter
            
            $form->setFields($readonlyFields);

            $this->extend('updateEditForm', $form);
            return $form;
        }else if($id) {
            return new Form($this, 'EditForm', new FieldList(
                                                            new LabelField('DoesntExistLabel', _t('CodeBank.SNIPPIT_NOT_EXIST', '_Snippit does not exist'))
                                                        ), new FieldList());
        }
    }
    
    /**
     * Gets the link to the tree view
     * @return {string} Link to the tree load
     */
    public function getLinkTreeView() {
        return $this->Link('tree');
    }
    
    /**
     * Gets the snippet tree view
     * @return {string} Rendered snippet tree
     */
    public function tree() {
        return $this->renderWith('CodeBank_TreeView');
    }
    
    /**
     * Gets the snippet language tree as an unordered list
     * @return {string} XHTML forming the tree of languages to snippets
     */
    public function SiteTreeAsUL() {
        $html=$this->getSiteTreeFor($this->stat('tree_class'), null, 'Snippets', null, array($this, 'hasSnippets'));
        
        $this->extend('updateSiteTreeAsUL', $html);
        
        return $html;
    }
    
    /**
     * Get a site tree HTML listing which displays the nodes under the given criteria.
     * @param $className The class of the root object
     * @param $rootID The ID of the root object.  If this is null then a complete tree will be shown
     * @param $childrenMethod The method to call to get the children of the tree. For example, Children, AllChildrenIncludingDeleted, or AllHistoricalChildren
     * @return String Nested unordered list with links to each page
     */
    public function getSiteTreeFor($className, $rootID=null, $childrenMethod=null, $numChildrenMethod=null, $filterFunction=null, $minNodeCount=30) {
        // Filter criteria
        $params=$this->request->getVar('q');
        if(isset($params['FilterClass']) && $filterClass=$params['FilterClass']) {
            if(!is_subclass_of($filterClass, 'CMSSiteTreeFilter')) {
                throw new Exception(sprintf('Invalid filter class passed: %s', $filterClass));
            }
            
            $filter=new $filterClass($params);
        }else {
            $filter=null;
        }
        
        
        // Default childrenMethod and numChildrenMethod
        if(!$childrenMethod) {
            $childrenMethod=($filter && $filter->getChildrenMethod() ? $filter->getChildrenMethod():'AllChildrenIncludingDeleted');
        }
        
        
        if(!$numChildrenMethod) {
            $numChildrenMethod='numChildren';
        }
        
        
        if(!$filterFunction) {
            $filterFunction=($filter ? array($filter, 'isPageIncluded'):null);
        }
        
        
        // Get the tree root
        $record=($rootID ? $this->getRecord($rootID):null);
        $obj=($record ? $record:singleton($className));
        
        // Mark the nodes of the tree to return
        if($filterFunction) {
            $obj->setMarkingFilterFunction($filterFunction);
        }

        $obj->markPartialTree($minNodeCount, $this, $childrenMethod, $numChildrenMethod);
        
        // Ensure current page is exposed
        if($p=$this->currentPage()) {
            $obj->markToExpose($p);
        }
        
        // getChildrenAsUL is a flexible and complex way of traversing the tree
        $controller=$this;
        $recordController=($this->stat('tree_class') == 'SiteTree' ?  singleton('CMSPageEditController'):$this); //@TODO
        $titleFn=function(&$child) use(&$controller, &$recordController) {
            $link=Controller::join_links($recordController->Link("show"), $child->ID);
            return LeftAndMain_TreeNode::create($child, $link, $controller->isCurrentPage($child))->forTemplate();
        };
        
        
        $html=$obj->getChildrenAsUL("", $titleFn, null, true, $childrenMethod, $numChildrenMethod, $minNodeCount);
        
        
        // Wrap the root if needs be.
        if(!$rootID) {
            $rootLink=$this->Link('show') . '/root';
            
            // This lets us override the tree title with an extension
            if($this->hasMethod('getCMSTreeTitle') && $customTreeTitle=$this->getCMSTreeTitle()) {
                $treeTitle=$customTreeTitle;
            }else if(class_exists('SiteConfig')) {
                $siteConfig=SiteConfig::current_site_config();
                $treeTitle=$siteConfig->Title;
            }else {
                $treeTitle='...';
            }
            
            $html="<ul><li id=\"record-0\" data-id=\"0\" class=\"Root nodelete\"><strong>$treeTitle</strong>".$html."</li></ul>";
        }

        return $html;
    }
    
    /**
     * Gets the snippet for editing/viewing
     * @param {int} $id ID of the snippet to fetch
     * @return {Snippet} Snippet to use
     */
    public function getRecord($id) {
        $className='Snippet';
        if($className && $id instanceof $className) {
            return $id;
        }else if($id=='root') {
            return singleton($className);
        }else if(is_numeric($id)) {
            return DataObject::get_by_id($className, $id);
        }else {
            return false;
        }
    }
    
    /**
     * Returns the link to view/edit snippets
     * @return {string} Link to view/edit snippets
     */
    public function getEditLink() {
        return Controller::join_links($this->Link('show'), $this->currentPageID());
    }
    
    /**
     * Returns the link to view snippets
     * @return {string} Link to view snippets
     */
    public function getLinkSettings() {
        return $this->Link('settings');
    }
    
    /**
     * Gets the current version of Code Bank
     * @return {string} Version Number Plus Build Date
     */
    public static function getVersion() {
        if(CB_VERSION=='@@VERSION@@') {
            return _t('CodeBank.DEVELOPMENT_BUILD', '_Development Build');
        }
        
        return CB_VERSION.' '.CB_BUILD_DATE;
    }
    
    /**
     * Returns a map of permission codes to add to the dropdown shown in the Security section of the CMS.
     * @return {array} Map of codes to label
     */
    public function providePermissions() {
        return array(
                    'CODE_BANK_ACCESS'=>_t('CodeBank.ACCESS_CODE_BANK', '_Access Code Bank')
                );
    }
    
    /**
     * Detects if a node has snippets or not
     * @return {bool} Returns the value if the language has snippets or not
     */
    public function hasSnippets($node) {
        return $node->hasSnippets();
    }
}
?>