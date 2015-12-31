<?php
class PackageViewButton extends GridFieldViewButton
{
    public function getColumnContent($field, $record, $col)
    {
        if ($record->canView()) {
            $data=new ArrayData(array(
                                    'Link'=>'admin/codeBank/show/'.$record->ID
                                ));
            
            return $data->renderWith('GridFieldViewButton');
        }
    }
}
