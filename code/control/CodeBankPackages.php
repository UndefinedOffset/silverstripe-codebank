<?php
class CodeBankPackages extends CodeBank {
    public static $url_segment='codeBank/packages';
    public static $url_rule='/$Action/$ID/$OtherID';
    public static $url_priority=65;
    public static $session_namespace='CodeBankPackages';
    public static $tree_class='SnippetPackage';
    
    public static $required_permission_codes=array(
                                                    'CODE_BANK_ACCESS'
                                                );
    
    public static $allowed_actions=array(
                                        'index',
                                        'tree',
                                        'EditForm',
                                        'show',
                                        'AddForm',
                                        'add'
                                    );
    
    public function init() {
        parent::init();
        
        Requirements::css(CB_DIR.'/css/CodeBank.css');
        Requirements::block(CB_DIR.'/javascript/CodeBank.Tree.js');
        Requirements::javascript(CB_DIR.'/javascript/CodeBankPackages.EditForm.js');
    }
	
	/**
     * Gets the form used for viewing snippets
     * @param {int} $id ID of the record to fetch
     * @param {FieldList} $fields Fields to use
     * @return {Form} Form to be used
     */
    public function getEditForm($id=null, $fields=null) {
        if($this->urlParams['Action']=='add') {
            return $this->getAddForm($id, $fields);
        }
        
        
        if(!$id) {
            $id=$this->currentPageID();
        }
        
        
        $form=LeftAndMain::getEditForm($id);
        
        
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
            $actions=new FieldList(
                                    FormAction::create('doExport', _t('CodeBankPackages.EXPORT', '_Export')),
                                    FormAction::create('doSave', _t('CodeBank.SAVE', '_Save'))->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept')
                                );
            
            
            if($record->canDelete()) {
                $actions->insertBefore(FormAction::create('doDelete', _t('CodeBank.DELETE', '_Delete'))->addExtraClass('ss-ui-action-destructive'), 'action_doExport');
            }
            
            
            // Use <button> to allow full jQuery UI styling
            $actionsFlattened=$actions->dataFields();
            if($actionsFlattened) {
                foreach($actionsFlattened as $action) {
                    if($action instanceof FormAction) {
                        $action->setUseButtonTag(true);
                    }
                }
            }
            
            
            $fields->push(new HiddenField('ID', 'ID'));
            
            
            if($record->hasMethod('getCMSValidator')) {
                $validator=$record->getCMSValidator();
            }else {
                $validator=new RequiredFields();
            }
            
            
            $form=new Form($this, 'EditForm', $fields, $actions, $validator);
            $form->loadDataFrom($record);
            
            if($record->canEdit()==false) {
                $form->makeReadonly();
            }
        }else {
            $form=$this->EmptyForm();
        }
        
        
        $form->disableDefaultAction();
        $form->addExtraClass('cms-edit-form');
        $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
        $form->addExtraClass('center '.$this->BaseCSSClasses());
        $form->setAttribute('data-pjax-fragment', 'CurrentForm');
        
        
        $this->extend('updateEditForm', $form);
        
        
        Requirements::javascript(CB_DIR.'/javascript/CodeBankPackages.EditForm.js');
        
        return $form;
    }
	
	/**
     * Gets the form used for viewing snippets
     * @param {int} $id ID of the record to fetch
     * @param {FieldList} $fields Fields to use
     * @return {Form} Form to be used
     */
    public function getAddForm($id=null, $fields=null) {
        $record=new SnippetPackage();
        $form=LeftAndMain::getEditForm($record);
        
        
        if(!$fields) {
            $fields=$form->Fields();
        }
        
        
        $actions=$form->Actions();
        
        $actions=new FieldList(
                                FormAction::create('doCreate', _t('CodeBank.SAVE', '_Save'))->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept')
                            );
        
        // Use <button> to allow full jQuery UI styling
        $actionsFlattened=$actions->dataFields();
        if($actionsFlattened) {
            foreach($actionsFlattened as $action) {
                if($action instanceof FormAction) {
                    $action->setUseButtonTag(true);
                }
            }
        }
        
        
        $fields->push(new HiddenField('ID', 'ID'));
        
        
        if($record->hasMethod('getCMSValidator')) {
            $validator=$record->getCMSValidator();
        }else {
            $validator=new RequiredFields();
        }
        
        
        $form=new Form($this, 'AddForm', $fields, $actions, $validator);
        
        if($record->canCreate()==false) {
            return $this->redirectBack();
        }
        
        
        $form->disableDefaultAction();
        $form->addExtraClass('cms-edit-form');
        $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
        $form->addExtraClass('center '.$this->BaseCSSClasses());
        $form->setAttribute('data-pjax-fragment', 'CurrentForm');
        
        
        $this->extend('updateAddForm', $form);
        
        
        Requirements::javascript(CB_DIR.'/javascript/CodeBankPackages.EditForm.js');
        
        return $form;
    }
    
    /**
     * Wrapper for getAddForm
     * @return {Form} Form to be used for adding
     */
    public function AddForm() {
        return $this->getAddForm();
    }
    
    /**
     * Redirects to the export package page, shouldn't be called this is a fallback
     * @param {array} $data Data submitted by the user
     * @param {Form} $form Submitting form
     */
    public function doExport($data, Form $form) {
        return $this->redirect('code-bank-api/export-package?id='.$this->currentPageID());
    }
    
    /**
     * Saves the snippet package to the database
     * @param {array} $data Data submitted by the user
     * @param {Form} $form Submitting form
     * @return {SS_HTTPResponse} Response
     */
    public function doSave($data, Form $form) {
        $record=$this->currentPage();
        
        if($record->canEdit()) {
            $form->saveInto($record);
            $record->write();
            
            $this->response->addHeader('X-Status', rawurlencode(_t('CodeBankPackages.PACKAGE_SAVED', '_Snippet Package has been saved')));
        }else {
            $this->response->addHeader('X-Status', rawurlencode(_t('CodeBank.PERMISSION_DENIED', '_Permission Denied')));
        }
        
        return $this->getResponseNegotiator()->respond($this->request);
    }
    
    /**
     * Deletes the snippet package from the database
     * @param {array} $data Data submitted by the user
     * @param {Form} $form Submitting form
     * @return {SS_HTTPResponse} Response
     */
    public function doDelete($data, Form $form) {
        $record=$this->currentPage();
    
        if($record->canDelete()) {
            $record->delete();
    
            $this->response->addHeader('X-Status', rawurlencode(_t('CodeBankPackages.PACKAGE_DELETED', '_Snippet Package has been deleted')));
        }else {
            $this->response->addHeader('X-Status', rawurlencode(_t('CodeBank.PERMISSION_DENIED', '_Permission Denied')));
        }
    
        return $this->getResponseNegotiator()->respond($this->request);
    }
	
	public function add($request) {
		// TODO Necessary for TableListField URLs to work properly
		$this->setCurrentPageID(0);
		return $this->getResponseNegotiator()->respond($request);
	}
    
    /**
     * Creates the snippet package
     * @param {array} $data Data submitted by the user
     * @param {Form} $form Submitting form
     * @return {SS_HTTPResponse} Response
     */
    public function doCreate($data, Form $form) {
        $record=new SnippetPackage();
        
        if($record->canEdit()) {
            $form->saveInto($record);
            $record->write();
            
            //Set the current page id
            $this->setCurrentPageID($record->ID);
            
            $this->response->addHeader('X-Status', rawurlencode(_t('CodeBankPackages.PACKAGE_CREATED', '_Snippet Package has been created')));
        }else {
            $this->response->addHeader('X-Status', rawurlencode(_t('CodeBank.PERMISSION_DENIED', '_Permission Denied')));
        }
        
        //Redirect to view
        $this->redirect($this->LinkPackages);
        return $this->getResponseNegotiator()->respond($this->request);
    }
    
    /**
     * Gets the snippet for editing/viewing
     * @param {int} $id ID of the snippet to fetch
     * @return {Snippet} Snippet to use
     */
    public function getRecord($id) {
        $className='SnippetPackage';
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
     * Gets the packages tree view
     * @return {string} Rendered packages tree
     */
    public function tree() {
        return $this->renderWith('CodeBankPackages_TreeView');
	}
    
    /**
     * Gets the snippet language tree as an unordered list
     * @return {string} XHTML forming the tree of languages to snippets
     */
	public function SiteTreeAsUL() {
	    $html=$this->getSiteTreeFor($this->stat('tree_class'), null, null, null);
	    
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
        if($params) {
            $filterClass=CodeBank::$filter_class;
            if($filterClass!='SnippetTreeFilter' && !is_subclass_of($filterClass, 'SnippetTreeFilter')) {
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
            $filterFunction=($filter ? array($filter, 'isSnippetLanguageIncluded'):array($this, 'hasSnippets'));
        }
        
        
        // Get the tree root
        $record=($rootID ? SnippetLanguage::get()->byID($rootID):null);
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
        $recordController=singleton('CodeBankPackages');
        $titleFn=function(&$child) use(&$controller, &$recordController) {
            $link=Controller::join_links($recordController->Link("show"), $child->ID);
            return CodeBank_TreeNode::create($child, $link, $controller->isCurrentPage($child))->forTemplate();
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
     * Allows requesting a view update on specific tree nodes.
     * Similar to {@link getsubtree()}, but doesn't enforce loading
     * all children with the node. Useful to refresh views after
     * state modifications, e.g. saving a form.
     *
     * @return String JSON
     */
    public function updatetreenodes($request) {
        $data=array();
        $ids=$request->getVar('ids');
        
        if(!empty($ids)) {
            $ids=explode(',', $request->getVar('ids'));
            foreach($ids as $id) {
                $record=$this->getRecord($id);
                $recordController=singleton('CodeBankPackages');
                
                //Find the next & previous nodes, for proper positioning (Sort isn't good enough - it's not a raw offset)
                $next=$prev=null;
                
                $className=$this->stat('tree_class');
                $next=SnippetPackage::get()->filter('Title:GreaterThan', $record->Title)->first();
                if(!$next) {
                    $prev=SnippetPackage::get()->filter('Title:LessThan', $record->Title)->reverse()->first();
                }
                
                $link=Controller::join_links($recordController->Link("show"), $record->ID);
                $html=CodeBank_TreeNode::create($record, $link, $this->isCurrentPage($record))->forTemplate().'</li>';
                
                $data[$id]=array(
                                'html'=>$html,
                                'ParentID'=>'0',
                                'NextID'=>($next ? $next->ID:null),
                                'PrevID'=>($prev ? $prev->ID:null)
                            );
            }
        }
        
        $this->response->addHeader('Content-Type', 'text/json');
        return Convert::raw2json($data);
    }
	
	public function Breadcrumbs($unlinked=false) {
		$defaultTitle=self::menu_title_for_class(get_class($this));
		return new ArrayList(array(
		                            new ArrayData(array(
                                        				'Title'=>_t('CodeBank.MENUTITLE', '_Code Bank'),
                                        				'Link'=>$this->LinkMain
                                        			)),
                        			new ArrayData(array(
                                        				'Title'=>_t("{$this->class}.MENUTITLE", $defaultTitle),
                                        				'Link'=>false
                                        			))
                        		));
	}
}
?>
