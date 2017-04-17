<?php
class ExportPackageButton extends GridFieldViewButton
{
    public function getColumnContent($field, $record, $col)
    {
        if ($record->canView()) {
            $data=new ArrayData(array(
                                    'Link'=>'code-bank-api/export-package?id='.$record->ID
                                ));
            
            return $data->renderWith('ExportPackageButton');
        }
    }
}
