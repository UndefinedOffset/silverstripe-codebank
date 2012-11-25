<?php
class CodeBank extends LeftAndMain implements PermissionProvider {
    public static $url_segment='codeBank';
    public static $tree_class='SnippetLanguage';
    public static $url_rule='/$Action/$ID/$OtherID';
    public static $url_priority=59;
    public static $filter_class='SnippetTreeFilter';
    
    public static $required_permission_codes=array(
                                                    'CODE_BANK_ACCESS'
                                                );
    
    public static $allowed_actions=array(
                                        'index',
                                        'tree',
                                        'EditForm',
                                        'show',
                                        'compare'
                                    );
    
    public static $session_namespace='CodeBank';
    
    /**
     * Constructor
     * @see LeftAndMain::__construct()
     */
    public function __construct() {
        parent::__construct();
        
        //Work around to allow dynamic path
        Config::inst()->update('CodeBank', 'menu_icon', CB_DIR.'/images/menu-icon.png');
    }
    
    /**
     * Initializes the code bank admin
     */
    public function init() {
        parent::init();
        
        Requirements::css(CB_DIR.'/css/CodeBank.css');
        
        Requirements::customScript("var CB_DIR='".CB_DIR."';", 'cb_dir');
        Requirements::javascript(CB_DIR.'/javascript/CodeBank.Tree.js');
        
        if(!empty(CodeBankConfig::CurrentConfig()->IPMessage) && Session::get('CodeBankIPAgreed')!==true) {
            $this->redirect('admin/codeBank/agreement');
        }
    }
    
    public function index($request) {
        // In case we're not showing a specific record, explicitly remove any session state,
        // to avoid it being highlighted in the tree, and causing an edit form to show.
        if(!$request->param('Action')) $this->setCurrentPageId(null);

        return parent::index($request);
    }
    
    /**
     * Override {@link LeftAndMain} Link to allow blank URL segment for CMSMain.
     * @param {string} $action Action to be used
     * @return {string} Resulting link
     */
    public function Link($action=null) {
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
     * Generates the link with search params
     * @param {string} Link to
     * @return {string} Link with search params
     */
    protected function LinkWithSearch($link) {
        // Whitelist to avoid side effects
        $params=array(
                    'q'=>(array)$this->request->getVar('q'),
                    'ParentID'=>$this->request->getVar('ParentID')
                );
        
        $link=Controller::join_links(
                                    $link,
                                    (array_filter(array_values($params)) ? '?'.http_build_query($params):null)
                                );
        
        $this->extend('updateLinkWithSearch', $link);
        return $link;
    }
    
    /**
     * Gets the main tab link
     * @return {string} URL to the main tab
     */
    public function getLinkMain() {
        if($this->currentPageID()!=0) {
            $otherID=null;
            if(!empty($this->urlParams['OtherID']) && is_numeric($this->urlParams['OtherID'])) {
                $otherID=intval($this->urlParams['OtherID']);
            }
            
            return $this->LinkWithSearch(Controller::join_links($this->Link('show'), $this->currentPageID(), $otherID));
        }
        
        return $this->LinkWithSearch(singleton('CodeBank')->Link());
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
                                    DropdownField::create('RevisionID', '', $record->Versions()->where('ID<>'.$record->CurrentVersionID)->Map('ID', 'Created'), $this->urlParams['OtherID'], null, '{'._t('CodeBank.CURRENT_REVISION', '_Current Revision').'}')->setDisabled($record->Versions()->Count()<=1)->addExtraClass('no-change-track'),
                                    FormAction::create('compareRevision', _t('CodeBank.COMPARE_WITH_CURRENT', '_Compare with Current'))->setDisabled($record->Versions()->Count()<=1 || empty($this->urlParams['OtherID']) || !is_numeric($this->urlParams['OtherID']))
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
            $fields->addFieldToTab('Root.Main', DatetimeField_Readonly::create('LastModified', _t('CodeBank.LAST_MODIFIED', '_Last Modified'), $record->CurrentVersion->LastEdited)->setForm($form));
            $fields->addFieldToTab('Root.Main', ReadonlyField::create('LastEditorName', _t('CodeBank.LAST_EDITED_BY', '_Last Edited By'), ($record->LastEditor() ? $record->LastEditor()->Name:null))->setForm($form));
            $fields->addFieldToTab('Root.Main', ReadonlyField::create('SnippetID', _t('CodeBank.ID', '_ID'), $record->ID));
            $fields->addFieldToTab('Root.Main', ReadonlyField::create('CurrentVersionID', _t('CodeBank.VERSION', '_Version')));
            $fields->push(new HiddenField('ID', 'ID'));
            
            $form=new Form($this, 'EditForm', $fields, $actions, $validator);
            $form->loadDataFrom($record);
            $form->disableDefaultAction();
            $form->addExtraClass('cms-edit-form');
            $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
            $form->addExtraClass('center '.$this->BaseCSSClasses());
            $form->setAttribute('data-pjax-fragment', 'CurrentForm');
            
            
            //Swap content for version text
            if(!empty($this->urlParams['OtherID']) && is_numeric($this->urlParams['OtherID'])) {
                $version=$record->Version(intval($this->urlParams['OtherID']));
                if(!empty($version) && $version!==false && $version->ID!=0) {
                    $fields->dataFieldByName('SnippetText')->setValue($version->Text);
                    $fields->dataFieldByName('LastModified')->setValue($version->LastEdited);
                    $fields->dataFieldByName('CurrentVersionID')->setValue($version->ID);
                }
                
                $form->setMessage(_t('CodeBank.NOT_CURRENT_VERSION', '_You are viewing a past version of this snippet\'s content, {linkopen}click here{linkclose} to view the current version', array('linkopen'=>'<a href="admin/codeBank/show/'.$record->ID.'">', 'linkclose'=>'</a>')), 'warning');
            }
            
            
            $fields->replaceField('PackageSnippets', PackageViewField::create('PackageSnippets', _t('Snippet.PACKAGE_SNIPPETS', '_Package Snippets'), $record->PackageSnippets(), $record->ID)->setShowNested(false));
            
            $readonlyFields=$form->Fields()->makeReadonly();
            
            $form->setFields($readonlyFields);
            
            
            $this->extend('updateEditForm', $form);
            
            
            Requirements::add_i18n_javascript(CB_DIR.'/javascript/lang');
            Requirements::add_i18n_javascript('mysite/javascript/lang');
            Requirements::javascript(CB_DIR.'/javascript/external/jquery-zclip/jquery.zclip.min.js');
            Requirements::javascript(CB_DIR.'/javascript/CodeBank.ViewForm.js');
            
            
            //Display message telling user to run dev/build because the version numbers are out of sync
            if(CB_VERSION!='@@VERSION@@' && CodeBankConfig::CurrentConfig()->Version!=CB_VERSION.' '.CB_BUILD_DATE) {
                $form->setMessage(_t('CodeBank.UPDATE_NEEDED', '_A database upgrade is required please run {startlink}dev/build{endlink}.', array('startlink'=>'<a href="dev/build?flush=all">', 'endlink'=>'</a>')), 'error');
            }
            
            
            return $form;
        }else if($id) {
            $form=new Form($this, 'EditForm', new FieldList(
                                                            new LabelField('DoesntExistLabel', _t('CodeBank.SNIPPIT_NOT_EXIST', '_Snippit does not exist'))
                                                        ), new FieldList());
        }else {
            $form=$this->EmptyForm();
        }
        
        
        //Display message telling user to run dev/build because the version numbers are out of sync
        if(CB_VERSION!='@@VERSION@@' && CodeBankConfig::CurrentConfig()->Version!=CB_VERSION.' '.CB_BUILD_DATE) {
            $form->setMessage(_t('CodeBank.UPDATE_NEEDED', '_A database upgrade is required please run {startlink}dev/build{endlink}.', array('startlink'=>'<a href="dev/build?flush=all">', 'endlink'=>'</a>')), 'error');
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
        return $this->LinkWithSearch($this->Link('tree'));
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
        $languageID=(strpos($request->getVar('ID'), 'language-')!==false ? intval(str_replace('language-', '', $request->getVar('ID'))):null);
        $html=$this->getSiteTreeFor($this->stat('tree_class'), $languageID, 'Snippets', null, array($this, 'hasSnippets'));

        // Trim off the outer tag
        $html=preg_replace('/^[\s\t\r\n]*<ul[^>]*>/','', $html);
        $html=preg_replace('/<\/ul[^>]*>[\s\t\r\n]*$/','', $html);
        
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
        $ids=explode(',', $request->getVar('ids'));
        foreach($ids as $id) {
            $record=$this->getRecord($id);
            $recordController=singleton('CodeBank');
            
            //Find the next & previous nodes, for proper positioning (Sort isn't good enough - it's not a raw offset)
            $next=$prev=null;
            
            $className=$this->stat('tree_class');
            $next=DataObject::get('Snippet')->filter('LanguageID', $record->LanguageID)->filter('Title:GreaterThan', $record->Title)->first();
            if(!$next) {
                $prev=DataObject::get('Snippet')->filter('LanguageID', $record->LanguageID)->filter('Title:LessThan', $record->Title)->reverse()->first();
            }
            
            $link=Controller::join_links($recordController->Link("show"), $record->ID);
            $html=CodeBank_TreeNode::create($record, $link, $this->isCurrentPage($record))->forTemplate().'</li>';
            
            $data[$id]=array(
                            'html'=>$html,
                            'ParentID'=>'language-'.$record->LanguageID,
                            'NextID'=>($next ? $next->ID:null),
                            'PrevID'=>($prev ? $prev->ID:null)
                        );
        }
        
        //$this->response->addHeader('Content-Type', 'text/json');
        return Convert::raw2json($data);
    }
    
    /**
     * Checks to see if the tree should be filtered or not
     * @return {bool}
     */
    public function TreeIsFiltered() {
        return $this->request->getVar('q');
    }
    
    /**
     * Gets the snippet language tree as an unordered list
     * @return {string} XHTML forming the tree of languages to snippets
     */
    public function SiteTreeAsUL() {
        $html=$this->getSiteTreeFor($this->stat('tree_class'), null, 'Snippets', null);
        
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
        if(!empty($this->urlParams['OtherID']) && is_numeric($this->urlParams['OtherID'])) {
            return $this->LinkWithSearch('admin/codeBank/show/'.$this->currentPageID().'/'.intval($this->urlParams['OtherID']));
        }
        
        return $this->LinkWithSearch('admin/codeBank/show/'.$this->currentPageID());
    }
    
    /**
     * Returns the link to settings
     * @return {string} Link to settings
     */
    public function getLinkSettings() {
        return $this->LinkWithSearch('admin/codeBank/settings');
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
                                                        'Link'=>($unlinked ? false:'admin/codeBank')
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
    
    /**
     * Generates the search form
     * @return {Form} Form used for searching
     */
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
     * Return the version number of this application.
     * Uses the subversion path information in <mymodule>/silverstripe_version
     * (automacially replaced by build scripts).
     *
     * @return string
     */
    public function CMSVersion() {
        $frameworkVersion=file_get_contents(FRAMEWORK_PATH.'/silverstripe_version');
        if(!$frameworkVersion) {
            $frameworkVersion=_t('LeftAndMain.VersionUnknown', 'Unknown');
        }
    
        return sprintf('Code Bank: %s Framework: %s', self::getVersion(), $frameworkVersion);
    }
    
    /**
     * Handles rendering of the compare view
     * @return {string} HTML to be sent to the browser
     */
    public function compare() {
        $compareContent=false;
        
        
        //Get the Main Revision
        $snippet1=Snippet::get()->byID(intval($this->urlParams['ID']));
        if(empty($snippet1) || $snippet1===false || $snippet1->ID==0) {
            $snippet1=false;
        }
        
        if($snippet1!==false) {
            //Get the Comparision Revision
            $snippet2=$snippet1->Version(intval($this->urlParams['OtherID']));
            if(empty($snippet2) || $snippet1===false || $snippet2->ID==0) {
                $snippet2=false;
            }
            
            if($snippet2!==false) {
                $snippet1Text=preg_replace('/\r\n|\n|\r/', "\n", $snippet1->SnippetText);
                $snippet2Text=preg_replace('/\r\n|\n|\r/', "\n", $snippet2->Text);
                
                //Generate the diff file
                $diff=new Text_Diff('auto', array(preg_split('/\n/', $snippet1Text), preg_split('/\n/', $snippet2Text)));
                $renderer=new WP_Text_Diff_Renderer_Table();
                
                $renderedDiff=$renderer->render($diff);
                if(!empty($renderedDiff)) {
                    $lTable='<table cellspacing="0" cellpadding="0" border="0" class="diff">'.
                                '<colgroup>'.
                                    '<col class="ltype"/>'.
                                    '<col class="content"/>'.
                                '</colgroup>'.
                                '<tbody>';
                    $rTable=$lTable;
                    
                    header('content-type: text/plain');
                    $xml=simplexml_load_string('<tbody>'.str_replace('&nbsp;', ' ', $renderedDiff).'</tbody>');
                    foreach($xml->children() as $row) {
                        $i=0;
                        $lTable.='<tr>';
                        $rTable.='<tr>';
                        
                        foreach($row->children() as $td) {
                            $attr=$td->attributes();
                            
                            if($i==0) {
                                $lTable.=$td->asXML();
                            }else {
                                $rTable.=$td->asXML();
                            }
                            
                            $i++;
                        }
                        
                        $lTable.='</tr>';
                        $rTable.='</tr>';
                    }
                    
                    $lTable.='</tbody></table>';
                    $rTable.='</tbody></table>';
                    
                    $compareContent='<div class="compare leftSide">'.$rTable.'</div>'.
                                    '<div class="compare rightSide">'.$lTable.'</div>';
                }
            }
        }
        
        
        Requirements::css(CB_DIR.'/css/CompareView.css');
        Requirements::javascript(CB_DIR.'/javascript/CodeBank.CompareView.js');
        
        return $this->renderWith('CodeBank_CompareView', array(
                                                                'CompareContent'=>$compareContent
                                                            ));
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
        return "<li ".($this->obj instanceof SnippetLanguage ? "id=\"language-$obj->ID\" data-id=\"language-$obj->ID\"":"id=\"record-$obj->ID\" data-id=\"$obj->ID\"")." data-pagetype=\"$obj->ClassName\" class=\"".$this->getClasses()."\">" .
                "<ins class=\"jstree-icon\">&nbsp;</ins>".
                "<a href=\"".($this->obj instanceof SnippetLanguage ? '':$this->getLink())."\" title=\"$obj->class: ".strip_tags($obj->TreeTitle)."\">".
                "<ins class=\"jstree-icon\">&nbsp;</ins><span class=\"text\">".($obj->TreeTitle)."</span></a>";
    }
}
?>