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
                                        'compare',
                                        'addSnippet',
                                        'moveSnippet',
                                        'addFolder',
                                        'AddFolderForm',
                                        'doAddFolder',
                                        'renameFolder',
                                        'RenameFolderForm',
                                        'doRenameFolder'
                                    );
    
    public static $session_namespace='CodeBank';
    
    
    private $_folderAdded=false;
    
    
    
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
        
        Requirements::add_i18n_javascript(CB_DIR.'/javascript/lang');
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
        if($this->currentPageID()!=0 && $this->class=='CodeBankEditSnippet') {
            return $this->LinkWithSearch(Controller::join_links($this->Link('show'), $this->currentPageID()));
        }else if($this->currentPageID()!=0 && $this->class=='CodeBank') {
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
            
            
            if($record->Package() && $record->Package()!==false && $record->Package()->ID!=0) {
                $package=new ArrayList(array($record->Package()));
            }else {
                $package=null;
            }
            
            $fields->replaceField('PackageID', new PackageViewField('PackageID', _t('Snippet.PACKAGE', '_Package'), $package, $record->ID));
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
                                                            new TabSet('Root',
                                                                            new Tab('Main', ' ',
                                                                                        new LabelField('DoesntExistLabel', _t('CodeBank.SNIPPIT_NOT_EXIST', '_Snippit does not exist'))
                                                                                    )
                                                                    )
                                                        ), new FieldList());
            $form->addExtraClass('cms-edit-form');
            $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
            $form->addExtraClass('center '.$this->BaseCSSClasses());
            $form->setAttribute('data-pjax-fragment', 'CurrentForm');
        }else {
            $form=$this->EmptyForm();
            if(Session::get('CodeBank.deletedSnippetID')) {
                $form->Fields()->push(new HiddenField('ID', 'ID', Session::get('CodeBank.deletedSnippetID')));
            }
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
        if(strpos($request->getVar('ID'), 'folder-')!==false) {
            $folderID=(strpos($request->getVar('ID'), 'folder-')!==false ? intval(str_replace('folder-', '', $request->getVar('ID'))):null);
            $html=$this->getSiteTreeFor('SnippetFolder', $folderID, 'Children', null, array($this, 'hasSnippets'));
        }else {
            $languageID=(strpos($request->getVar('ID'), 'language-')!==false ? intval(str_replace('language-', '', $request->getVar('ID'))):null);
            $html=$this->getSiteTreeFor($this->stat('tree_class'), $languageID, 'Children', null, array($this, 'hasSnippets'));
        }

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
            if($id==Session::get('CodeBank.deletedSnippetID')) {
                Session::clear('CodeBank.deletedSnippetID');
                $this->response->addHeader('Content-Type', 'text/json');
                return '{"'.$id.'": false}';
            }
            $record=$this->getRecord($id);
            $recordController=singleton('CodeBank');
            
            //Find the next & previous nodes, for proper positioning (Sort isn't good enough - it's not a raw offset)
            $next=$prev=null;
            $className=$this->stat('tree_class');
            $next=Snippet::get()->filter('LanguageID', $record->LanguageID)->where('"FolderID"='.$record->FolderID)->filter('Title:GreaterThan', $record->Title)->first();
            if(!$next) {
                $prev=Snippet::get()->filter('LanguageID', $record->LanguageID)->where('"FolderID"='.$record->FolderID)->filter('Title:LessThan', $record->Title)->reverse()->first();
            }
            
            $link=Controller::join_links($recordController->Link("show"), $record->ID);
            $html=CodeBank_TreeNode::create($record, $link, $this->isCurrentPage($record))->forTemplate().'</li>';
            
            $folder=$record->Folder();
            $data[$id]=array(
                            'html'=>$html,
                            'ParentID'=>(!empty($folder) && $folder!==false && $folder->ID!=0 ? 'folder-'.$record->FolderID:'language-'.$record->LanguageID),
                            'NextID'=>($next ? $next->ID:null),
                            'PrevID'=>($prev ? $prev->ID:null)
                        );
        }
        
        $this->response->addHeader('Content-Type', 'text/json');
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
        $html=$this->getSiteTreeFor($this->stat('tree_class'), null, 'Children', null);
        
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
        $record=($rootID ? $className::get()->byID($rootID):null);
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
     * @return {DataObject} DataObject to use
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
     * Returns the link to packages
     * @return {string} Link to packages
     */
    public function getLinkPackages() {
        if($this->urlParams['Action']=='add' && $this->class=='CodeBankPackages') {
            return $this->LinkWithSearch($this->Link('add'));
        }else if($this->currentPageID()!=0 && $this->class=='CodeBankPackages') {
            $otherID=null;
            if(!empty($this->urlParams['OtherID']) && is_numeric($this->urlParams['OtherID'])) {
                $otherID=intval($this->urlParams['OtherID']);
            }
            
            return $this->LinkWithSearch(Controller::join_links($this->Link('show'), $this->currentPageID(), $otherID));
        }
        
        return $this->LinkWithSearch('admin/codeBank/packages');
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
        $frameworkVersions=parent::CMSVersion();
    
        return sprintf('Code Bank: %s, %s', self::getVersion(), $frameworkVersions);
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
	 * Create serialized JSON string with tree hints data to be injected into 'data-hints' attribute of root node of jsTree.
	 * @return {string} Serialized JSON
	 */
	public function getTreeHints() {
		$json = '';

		$classes = array('Snippet', 'SnippetLanguage', 'SnippetFolder');

	 	$cacheCanCreate = array();
	 	foreach($classes as $class) $cacheCanCreate[$class] = singleton($class)->canCreate();

	 	// Generate basic cache key. Too complex to encompass all variations
	 	$cache=SS_Cache::factory('CodeBank_TreeHints');
	 	$cacheKey = md5(implode('_', array(Member::currentUserID(), implode(',', $cacheCanCreate), implode(',', $classes))));
	 	if($this->request->getVar('flush')) $cache->clean(Zend_Cache::CLEANING_MODE_ALL);
	 	$json = $cache->load($cacheKey);
	 	if(!$json) {
			$def['Root'] = array();
			$def['Root']['disallowedParents'] = array();

			foreach($classes as $class) {
				$allowedChildren = $class::$allowed_children;
				
				// SiteTree::allowedChildren() returns null rather than an empty array if SiteTree::allowed_chldren == 'none'
				if($allowedChildren == null) $allowedChildren = array();
				
				// Find i18n - names and build allowed children array
				foreach($allowedChildren as $child) {
					$instance = singleton($child);
					
					if($instance instanceof HiddenClass) continue;

					if(!array_key_exists($child, $cacheCanCreate) || !$cacheCanCreate[$child]) continue;

					// skip this type if it is restricted
					if($instance->stat('need_permission') && !$this->can(singleton($class)->stat('need_permission'))) continue;

					$title = $instance->i18n_singular_name();

					$def[$class]['allowedChildren'][] = array("ssclass" => $child, "ssname" => $title);
				}

				$allowedChildren = array_keys(array_diff($classes, $allowedChildren));
				if($allowedChildren) $def[$class]['disallowedChildren'] = $allowedChildren;
				$defaultChild = $class::$default_child;
				if($defaultChild != null) $def[$class]['defaultChild'] = $defaultChild;
				if(isset($def[$class]['disallowedChildren'])) {
					foreach($def[$class]['disallowedChildren'] as $disallowedChild) {
						$def[$disallowedChild]['disallowedParents'][] = $class;
					}
				}
				
				// Are any classes allowed to be parents of root?
				$def['Root']['disallowedParents'][] = $class;
			}

			$json = Convert::raw2xml(Convert::raw2json($def));
			$cache->save($json, $cacheKey);
		}
		return $json;
    }
    
    /**
     * Handles requests to add a snippet or folder to a language
     * @param {SS_HTTPRequest} $request HTTP Request
     */
    public function addSnippet(SS_HTTPRequest $request)  {
        if($request->getVar('Type')=='SnippetFolder') {
            return $this->redirect(Controller::join_links($this->Link('addFolder'), '?FolderID='.str_replace('folder-', '', $request->getVar('ID'))));
        }else {
            return $this->redirect(Controller::join_links($this->Link('add'), '?LanguageID='.str_replace('language-', '', $request->getVar('ID'))));
        }
    }
    
    /**
     * Handles moving of a snippet when the tree is reordered
     * @param {SS_HTTPRequest} $request HTTP Request
     */
    public function moveSnippet(SS_HTTPRequest $request) {
        $snippet=Snippet::get()->byID(intval($request->getVar('ID')));
        
        if(empty($snippet) || $snippet===false || $snippet->ID==0) {
            $this->response->setStatusCode(403, _t('CodeBank.SNIPPIT_NOT_EXIST', '_Snippit does not exist'));
            return;
        }
        
        $parentID=$request->getVar('ParentID');
        if(strpos($parentID, 'language-')!==false) {
            $lang=SnippetLanguage::get()->byID(intval(str_replace('language-', '', $parentID)));
            if(empty($lang) || $lang===false || $lang->ID==0) {
                $this->response->setStatusCode(403, _t('CodeBank.LANGUAGE_NOT_EXIST', '_Language does not exist'));
                return;
            }
            
            
            if($lang->ID!=$snippet->LanguageID) {
                $this->response->setStatusCode(403, _t('CodeBank.CANNOT_MOVE_TO_LANGUAGE', '_You cannot move a snippet to another language'));
                return;
            }
            
            //Move out of folder
            DB::query('UPDATE "Snippet" SET "FolderID"=0 WHERE "ID"='.$snippet->ID);
            
            
            $this->response->addHeader('X-Status', rawurlencode(_t('CodeBank.SNIPPET_MOVED', '_Snippet moved successfully')));
            return;
        }else if(strpos($parentID, 'folder-')!==false) {
            $folder=SnippetFolder::get()->byID(intval(str_replace('folder-', '', $parentID)));
            if(empty($folder) || $folder===false || $folder->ID==0) {
                $this->response->setStatusCode(403, _t('CodeBank.FOLDER_NOT_EXIST', '_Folder does not exist'));
                return;
            }
            
            
            if($folder->LanguageID!=$snippet->LanguageID) {
                $this->response->setStatusCode(403, _t('CodeBank.CANNOT_MOVE_TO_FOLDER', '_You cannot move a snippet to a folder in another language'));
                return;
            }
            
            //Move to folder
            DB::query('UPDATE "Snippet" SET "FolderID"='.$folder->ID.' WHERE "ID"='.$snippet->ID);
            
            
            $this->response->addHeader('X-Status', rawurlencode(_t('CodeBank.SNIPPET_MOVED', '_Snippet moved successfully')));
            return;
        }
        
        $this->response->setStatusCode(403, _t('CodeBank.UNKNOWN_PARENT', '_Unknown Parent'));
    }
    
    /**
     * Handles requests to add a folder
     * @return {string} HTML to be sent to the browser
     */
    public function addFolder() {
        $form=$this->AddFolderForm();
        return $this->customise(array(
                                    'Content'=>' ',
                                    'Form'=>$form
                                ))->renderWith('CMSDialog');
    }
    
    /**
     * Form used for adding a folder
     * @return {Form} Form to be used for adding a folder
     */
    public function AddFolderForm() {
        $fields=new FieldList(
                            new TabSet('Root',
                                            new Tab('Main', 'Main',
                                                                    new TextField('Name', _t('SnippetFolder.NAME', '_Name'), null, 150)
                                                                )
                                        )
                        );
        
        
        $noParent=true;
        if(strpos($this->request->getVar('ParentID'), 'language-')!==false) {
            $fields->push(new HiddenField('LanguageID', 'LanguageID', intval(str_replace('language-', '', $this->request->getVar('ParentID')))));
            
            $noParent=false;
        }else if(strpos($this->request->getVar('LanguageID'), 'folder-')!==false) {
            $folder=Folder::get()->byID(intval(str_replace('language-', '', $this->request->getVar('ParentID'))));
            
            if(!empty($folder) && $folder!==false && $folder->ID!=0) {
                $fields->push(new HiddenField('ParentID', 'ParentID', $folder->ID));
                $fields->push(new HiddenField('LanguageID', 'LanguageID', $folder->LanguageID));
                
                $noParent=false;
            }
        }else {
            if($this->request->postVar('LanguageID')) {
                $fields->push(new HiddenField('LanguageID', 'LanguageID', intval($this->request->postVar('LanguageID'))));
                
                if($this->request->postVar('ParentID')) {
                    $fields->push(new HiddenField('ParentID', 'ParentID', intval($this->request->postVar('ParentID'))));
                }
                
                $noParent=false;
            }
        }
        
        
        $actions=new FieldList(
                                new FormAction('doAddFolder', _t('CodeBank.SAVE', '_Save'))
                            );
        
        $validator=new RequiredFields('Name');
        
        $form=new Form($this, 'AddFolderForm', $fields, $actions, $validator);
        $form->addExtraClass('member-profile-form');
        
        
        //If no parent disable folder
        if($noParent) {
            $form->setMessage(_t('CodeBank.FOLDER_NO_PARENT', '_Folder does not have a parent language or folder'), 'bad');
            $form->setFields(new FieldList());
            $form->setActions(new FieldList());
        }
        
        return $form;
    }
    
    /**
     * Handles actually adding a folder to the databsae
     * @param {array} $data Submitted data
     * @param {Form} $form Submitting form
     * @return {string} HTML to be rendered
     */
    public function doAddFolder($data, Form $form) {
        $folder=new SnippetFolder();
        $folder->Name=$data['Name'];
        $folder->LanguageID=$data['LanguageID'];
        
        if(array_key_exists('FolderID', $data)) {
            $folder->ParentID=$data['FolderID'];
        }
        
        //Write the folder to the database
        $folder->write();
        
        
        //Find the next & previous nodes, for proper positioning (Sort isn't good enough - it's not a raw offset)
        $next=$prev=null;
        $next=SnippetFolder::get()->filter('LanguageID', $folder->LanguageID)->filter('ParentID', $folder->ParentID)->filter('Name:GreaterThan', $folder->Title)->first();
        if(!$next) {
            $prev=SnippetFolder::get()->filter('LanguageID', $folder->LanguageID)->filter('ParentID', $folder->ParentID)->filter('Name:LessThan', $folder->Title)->reverse()->first();
        }
        
        
        //Setup js that will add the node to the tree
        $html=CodeBank_TreeNode::create($folder, '', false)->forTemplate().'</li>';
        $parentFolder=$folder->Parent();
        $outputData=array('folder-'.$folder->ID=>array(
                                            'html'=>$html,
                                            'ParentID'=>(!empty($parentFolder) && $parentFolder!==false && $parentFolder->ID!=0 ? 'folder-'.$folder->ParentID:'language-'.$folder->LanguageID),
                                            'NextID'=>($next ? 'folder-'.$next->ID:null),
                                            'PrevID'=>($prev ? 'folder-'.$prev->ID:null)
                                        ));
        
        Requirements::customScript('window.parent.updateCodeBankTreeNodes('.json_encode($outputData).');');
        
        
        //Re-render the form
        $form->setFields(new FieldList());
        $form->setActions(new FieldList());
        $form->setMessage(_t('CodeBank.FOLDER_ADDED', '_Folder added you may now close this dialog'), 'good');
        
        return $this->customise(array(
                                    'Content'=>' ',
                                    'Form'=>$form
                                ))->renderWith('CMSDialog');
    }
    
    /**
     * Handles requests to rename a folder
     * @return {string} HTML to be sent to the browser
     */
    public function renameFolder() {
        $form=$this->RenameFolderForm();
        return $this->customise(array(
                                    'Content'=>' ',
                                    'Form'=>$form
                                ))->renderWith('CMSDialog');
    }
    
    /**
     * Form used for renaming a folder
     * @return {Form} Form to be used for renaming a folder
     */
    public function RenameFolderForm() {
        $folder=SnippetFolder::get()->byID(intval(str_replace('folder-', '', $this->request->getVar('ID'))));
        
        if(!empty($folder) && $folder!==false && $folder->ID!=0) {
            $fields=new FieldList(
                                new TabSet('Root',
                                                new Tab('Main', 'Main',
                                                                        new TextField('Name', _t('SnippetFolder.NAME', '_Name'), null, 150)
                                                                    )
                                            ),
                                new HiddenField('ID', 'ID')
                            );
            
            $actions=new FieldList(
                                    new FormAction('doRenameFolder', _t('CodeBank.SAVE', '_Save'))
                                );
        }else {
            $fields=new FieldList();
            $actions=new FieldList();
        }
        
        $validator=new RequiredFields('Name');
        
        $form=new Form($this, 'RenameFolderForm', $fields, $actions, $validator);
        $form->addExtraClass('member-profile-form');
        
        
        //If no parent disable folder
        if(empty($folder) || $folder===false || $folder->ID==0) {
            $form->setMessage(_t('CodeBank.FOLDER_NOT_FOUND', '_Folder could not be found'), 'bad');
        }else {
            $form->loadDataFrom($folder);
            $form->setFormAction(Controller::join_links($form->FormAction(), '?ID='.$folder->ID));
        }
        
        return $form;
    }
    
    /**
     * Performs the rename of the folder
     * @param {array} $data Submitted data
     * @param {Form} $form Submitting form
     * @return {string} HTML to be rendered
     */
    public function doRenameFolder($data, Form $form) {
        $folder=SnippetFolder::get()->byID(intval($data['ID']));
        if(empty($folder) || $folder===false || $folder->ID==0) {
            $form->sessionMessage(_t('CodeBank.FOLDER_NOT_FOUND', '_Folder could not be found'), 'bad');
            return $this->redirectBack();
        }
        
        
        //Update Folder
        $form->saveInto($folder);
        $folder->write();
        
        
        //Add script to rename the folder in the tree
        Requirements::customScript('window.parent.renameCodeBankTreeNode("folder-'.$folder->ID.'", "'.addslashes($folder->TreeTitle).'");');
        
        
        //Re-render the form
        $form->setMessage(_t('CodeBank.FOLDER_RENAMED', '_Folder Renamed'), 'good');
        return $this->customise(array(
                                    'Content'=>' ',
                                    'Form'=>$form
                                ))->renderWith('CMSDialog');
    }
    
    /**
     * Deletes a folder node
     */
    public function deleteFolder() {
        $folder=SnippetFolder::get()->byID(intval($data['ID']));
        if(empty($folder) || $folder===false || $folder->ID==0) {
            $this->response->setStatusCode(404, _t('CodeBank.FOLDER_NOT_FOUND', '_Folder could not be found'));
            return;
        }
        
        
        $folder->delete();
        
        //@TODO Need to figure out how to re-insert the child nodes back into the tree, maybe do this js side?
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
        if($this->obj instanceof SnippetLanguage) {
            $liAttrib='id="language-'.$obj->ID.'" data-id="language-'.$obj->ID.'"';
        }else if($this->obj instanceof SnippetFolder) {
            $liAttrib='id="folder-'.$obj->ID.'" data-id="folder-'.$obj->ID.'" data-languageID="'.$obj->LanguageID.'"';
        }else {
            $liAttrib='id="record-'.$obj->ID.'" data-id="'.$obj->ID.'" data-languageID="'.$obj->LanguageID.'"';
        }
        
        return "<li ".$liAttrib." data-pagetype=\"$obj->ClassName\" class=\"".$this->getClasses()."\">" .
                "<ins class=\"jstree-icon\">&nbsp;</ins>".
                "<a href=\"".($this->obj instanceof SnippetLanguage || $this->obj instanceof SnippetFolder ? '':$this->getLink())."\" title=\"$obj->class: ".strip_tags($obj->TreeTitle)."\">".
                "<ins class=\"jstree-icon\">&nbsp;</ins><span class=\"text\">".($obj->TreeTitle)."</span></a>";
    }
}
?>