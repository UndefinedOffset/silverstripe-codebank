<?php
class TagsViewField extends ReadonlyField {
    public function Value() {
        if($this->value) {
            $val=Convert::raw2xml($this->value);
            $val=explode(',', $val);
            foreach($val as $key=>$value) {
                $val[$key]='<a href="'.$this->form->Controller()->Link().'?tag='.rawurlencode($value).'">'.$value.'</a>';
            }
            
            return implode(', ', $val);
        }else {
            return '<i>('._t('FormField.NONE', 'none').')</i>';
        }
    }
}
?>