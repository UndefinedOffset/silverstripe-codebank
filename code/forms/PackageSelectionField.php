<?php
class PackageSelectionField extends DropdownField
{
    protected $extraClasses=array(
                                'dropdown'
                            );
    
    private static $allowed_actions=array(
                                        'Field',
                                        'addPackage',
                                        'AddPackageForm',
                                        'ReloadField'
                                    );
    
    /**
     * Returns a "field holder" for this field - used by templates.
     * @param {array} $properties Key value pairs of template variables
     * @return {string}
     */
    public function FieldHolder($properties=array())
    {
        Requirements::css(CB_DIR.'/css/PackageSelectionField.css');
        Requirements::javascript(CB_DIR.'/javascript/PackageSelectionField.js');
        
        return parent::FieldHolder($properties);
    }
    
    /**
     * Handles requests for the add package dialog
     * @return {string} HTML to be rendered
     */
    public function addPackage()
    {
        return $this->renderWith('CMSDialog', array(
                                                    'Form'=>$this->AddPackageForm()
                                                ));
    }
    
    /**
     * Generates the form for adding packages
     * @return {Form} Form to be used
     */
    public function AddPackageForm()
    {
        $sng=singleton('SnippetPackage');
        
        $fields=new FieldList(
                            new TabSet('Root',
                                    new Tab('Main', _t('PackageSelectionField.MAIN', '_Main'),
                                            new TextField('Title', _t('PackageSelectionField.TITLE', '_Title'), null, 300)
                                        )
                                )
                        );
        
        $actions=new FieldList(
                                FormAction::create('doAddPackage', _t('PackageSelectionField.CREATE', '_Create'))
                                                    ->addExtraClass('ss-ui-action-constructive')
                                                    ->setAttribute('data-icon', 'accept')
                                                    ->setUseButtonTag(true)
                            );
        
        $validator=new RequiredFields(
                                    'Title'
                                );
        
        return Form::create($this, 'AddPackageForm', $fields, $actions, $validator)
                            ->addExtraClass('member-profile-form')
                            ->setFormAction($this->Link('AddPackageForm'));
    }
    
    /**
     * Handles adding of the package
     * @param {array} $data Submitted data
     * @param {Form} $form Submitting form
     * @return {mixed}
     */
    public function doAddPackage($data, Form $form)
    {
        $record=new SnippetPackage();
        if ($record->canEdit()) {
            $form->saveInto($record);
            $record->write();
            
            
            Requirements::customScript("window.parent.jQuery('#".$this->getName()."').entwine('ss').handleSuccessResult(".$record->ID.");");
            return $this->renderWith('CMSDialog', array('Form'=>''));
        }
        
        return Controller::curr()->redirectBack();
    }
    
    /**
     * Wrapper for Field Holder expects the id of the element to select on the get variables
     * @param {SS_HTTPRequest} $request HTTP Request Data
     * @return {string} HTML to be returned
     */
    public function ReloadField(SS_HTTPRequest $request)
    {
        $this->value=$request->getVar('id');
        return $this->FieldHolder();
    }
}
