<?php
class CodeBank extends LeftAndMain implements PermissionProvider {
    public static $url_segment='codeBank';
    public static $tree_class='SnippetLanguage';
    public static $url_rule='/$Action/$ID/$OtherID';
    public static $url_priority=59;
	public static $menu_icon='CodeBank/images/menu-icon.png';
    
    public static $required_permission_codes=array(
                                                    'CODE_BANK_ACCESS'
                                                );
    
    public static $allowed_actions=array(
                                        'index',
                                        'tree',
                                        'EditForm',
                                        'show'
                                    );
    
    public static $session_namespace='CodeBank';
    
    public function init() {
        parent::init();
        
        Requirements::css('CodeBank/css/CodeBank.css');
        Requirements::javascript('CodeBank/javascript/CodeBank.Tree.js');
    }
    
    public function index($request) {
		// In case we're not showing a specific record, explicitly remove any session state,
		// to avoid it being highlighted in the tree, and causing an edit form to show.
		if(!$request->param('Action')) $this->setCurrentPageId(null);

		return parent::index($request);
	}
	
	/**
	 * Override {@link LeftAndMain} Link to allow blank URL segment for CMSMain.
	 *
	 * @return string
	 */
	public function Link($action = null) {
		$link = Controller::join_links(
			$this->stat('url_base', true),
			$this->stat('url_segment', true), // in case we want to change the segment
			'/', // trailing slash needed if $action is null!
			"$action"
		);
		$this->extend('updateLink', $link);
		return $link;
	}
	
	/**
	 * Gets the main tab link
	 * @return {string} URL to the main tab
	 */
	public function getLinkMain() {
	    if($this->currentPageID()!=0) {
	        return Controller::join_links($this->Link('show'), $this->currentPageID());
	    }
        
        return singleton('CodeBank')->Link();
	}
    
    /**
     * Gets the form used for viewing snippets
     * @param {int} $id ID of the record to fetch
     * @param {FieldList} $fields Fields to use
     * @return {Form} Form to be used
     */
    public function getEditForm($id=null, $fields=null) {
        if(!$id) {
            $id=$this->currentPageID();
        }
        
        
        $form=parent::getEditForm($id);
        
        
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
                                    new FormAction('doCopy', _t('CodeBank.COPY', '_Copy')),
                                    new FormAction('doEditRedirect', _t('CodeBank.EDIT', '_Edit')),
                                    new FormAction('doExport', _t('CodeBank.EXPORT', '_Export')),
                                    new FormAction('doPrint', _t('CodeBank.PRINT', '_Print')),
                                    new LabelField('Revision', _t('CodeBank.REVISION', '_Revision').': '),
                                    DropdownField::create('RevisionID', '', $record->Versions()->where('ID<>'.$record->CurrentVersionID)->Map('ID', 'Created'), null, null, '{'._t('CodeBank.CURRENT_REVISION', '_Current Revision').'}')->setDisabled($record->Versions()->Count()<=1)->addExtraClass('no-change-track'),
                                    FormAction::create('compareRevision', _t('CodeBank.COMPARE_WITH_CURRENT', '_Compare with Current'))->setDisabled($record->Versions()->Count()<=1)
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
            
            
            if($record->hasMethod('getCMSValidator')) {
                $validator=$record->getCMSValidator();
            }else {
                $validator=new RequiredFields();
            }
            
            
            $fields->replaceField('Text', HighlightedContentField::create('SnippetText', _t('Snippet.CODE', '_Code'), $record->Language()->HighlightCode)->setForm($form));
            $fields->addFieldToTab('Root.Main', ReadonlyField::create('CreatorName', _t('CodeBank.CREATOR', '_Creator'), ($record->Creator() ? $record->Creator()->Name:null))->setForm($form));
            $fields->addFieldToTab('Root.Main', ReadonlyField::create('LanguageName', _t('CodeBank.LANGUAGE', '_Language'), $record->Language()->Name)->setForm($form));
            $fields->addFieldToTab('Root.Main', ReadonlyField::create('LastModified', _t('CodeBank.LAST_MODIFIED', '_Last Modified'), DBField::create_field('SS_DateTime', $record->LastEdited)->Nice())->setForm($form));
            $fields->addFieldToTab('Root.Main', ReadonlyField::create('LastEditor', _t('CodeBank.LAST_EDITED_BY', '_Last Edited By'), ($record->LastEditor() ? $record->LastEditor()->Name:null))->setForm($form));
            $fields->push(new HiddenField('ID', 'ID'));
            
            $form=new Form($this, 'EditForm', $fields, $actions, $validator);
            $form->loadDataFrom($record);
            $form->disableDefaultAction();
            $form->addExtraClass('cms-edit-form');
            $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
            $form->addExtraClass('center '.$this->BaseCSSClasses());
            $form->setAttribute('data-pjax-fragment', 'CurrentForm');
            
            $readonlyFields=$form->Fields()->makeReadonly();
            
            $form->setFields($readonlyFields);
            
            
            $this->extend('updateEditForm', $form);
            
            
            Requirements::javascript('CodeBank/javascript/CodeBank.ViewForm.js');
            
            return $form;
        }else if($id) {
            $form=new Form($this, 'EditForm', new FieldList(
                                                            new LabelField('DoesntExistLabel', _t('CodeBank.SNIPPIT_NOT_EXIST', '_Snippit does not exist'))
                                                        ), new FieldList());
        }else {
            $form=$this->EmptyForm();
        }
        
        $form->disableDefaultAction();
        $form->addExtraClass('cms-edit-form');
        $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
        $form->addExtraClass('center '.$this->BaseCSSClasses());
        
        return $form;
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
	 * Get a subtree underneath the request param 'ID'.
	 * If ID = 0, then get the whole tree.
	 */
	public function getsubtree($request) {
		$html=$this->getSiteTreeFor($this->stat('tree_class'), null, 'Snippets', null, array($this, 'hasSnippets'));

		// Trim off the outer tag
		$html=preg_replace('/^[\s\t\r\n]*<ul[^>]*>/','', $html);
		$html=preg_replace('/<\/ul[^>]*>[\s\t\r\n]*$/','', $html);
		
		return $html;
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
        $recordController=singleton('CodeBank');
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
        return 'admin/codeBank/show/'.$this->currentPageID();
    }
    
    /**
     * Returns the link to settings
     * @return {string} Link to settings
     */
    public function getLinkSettings() {
        return 'admin/codeBank/settings';
    }
    
    /**
     * Detects if a node has snippets or not
     * @return {bool} Returns the value if the language has snippets or not
     */
    public function hasSnippets($node) {
        return $node->hasSnippets();
    }
    
	/**
	 * @return ArrayList
	 */
	public function Breadcrumbs($unlinked=false) {
		$defaultTitle=LeftAndMain::menu_title_for_class('CodeBank');
		$title=_t('CodeBank.MENUTITLE', $defaultTitle);
		$items=new ArrayList(array(
                        			new ArrayData(array(
                                        				'Title'=>$title,
                                        				'Link'=>($unlinked ? false:'admin/codeBank/show/'.$this->currentPageID())
                                        			))
                        		));
		
		$record=$this->currentPage();
		if($record && $record->exists()) {
			if($record->hasExtension('Hierarchy')) {
				$ancestors=$record->getAncestors();
				$ancestors=new ArrayList(array_reverse($ancestors->toArray()));
				$ancestors->push($record);
				foreach($ancestors as $ancestor) {
					$items->push(new ArrayData(array(
                            						'Title'=>$ancestor->Title,
                            						'Link'=>($unlinked ? false:Controller::join_links($this->Link('show'), $ancestor->ID))
                            					)));
				}
			}else {
				$items->push(new ArrayData(array(
                            					'Title'=>$record->Title,
                            					'Link'=>($unlinked ? false:Controller::join_links($this->Link('show'), $record->ID))
                            				)));
			}
		}

		return $items;
	}
	
	public function SearchForm() {
	    $fields=new FieldList(
                            new TextField('q[Term]', _t('CodeBank.KEYWORD', '_Keyword')),
            	            $classDropdown=new DropdownField(
                                    	                    'q[LanguageID]',
                                    	                    _t('CodeBank.LANGUAGE', '_Language'),
                                    	                    SnippetLanguage::get()->sort('Name')->map('ID', 'Name')
            	                                        )
                        );
	    
	    
	    $classDropdown->setEmptyString(_t('CodeBank.ALL_LANGUAGES', '_All Languages'));
	    
	    
	    $actions=new FieldList(
                            FormAction::create('doSearch', _t('CodeBank.APPLY_FILTER', '_Apply Filter'))->addExtraClass('ss-ui-action-constructive')->setUseButtonTag(true),
	                        Object::create('ResetFormAction', 'clear', _t('CodeBank.RESET', '_Reset'))->setUseButtonTag(true)
                        );
	    
	    
	    $form=Form::create($this, 'SearchForm', $fields, $actions)
                                                        	    ->addExtraClass('cms-search-form')
                                                        	    ->setFormMethod('GET')
                                                        	    ->setFormAction($this->Link())
                                                        	    ->disableSecurityToken()
                                                        	    ->unsetValidator();
	    $form->loadDataFrom($this->request->getVars());
	
	    $this->extend('updateSearchForm', $form);
	    return $form;
	}
	
	/**
	 * Applies filters to the tree
	 * @param {array} Array of data submitted
	 * @param {Form} $form Form submitted
	 */
	public function doSearch($data, Form $form) {
	    //return $this->getsubtree($this->request);
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
}

class CodeBank_TreeNode extends LeftAndMain_TreeNode {
    /**
     * Returns template, for further processing by {@link Hierarchy->getChildrenAsUL()}. Does not include closing tag to allow this method to inject its own children.
     * @return {string} HTML to be used
     */
    public function forTemplate() {
        $obj=$this->obj;
        return "<li ".($this->obj instanceof SnippetLanguage ? '':"id=\"record-$obj->ID\" data-id=\"$obj->ID\"")." data-pagetype=\"$obj->ClassName\" class=\"".$this->getClasses()."\">" .
                "<ins class=\"jstree-icon\">&nbsp;</ins>".
                "<a href=\"".($this->obj instanceof SnippetLanguage ? '':$this->getLink())."\" title=\"$obj->class\">".
                "<ins class=\"jstree-icon\">&nbsp;</ins><span class=\"text\">".($obj->TreeTitle)."</span></a>";
	}
}
?>