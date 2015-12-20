<?php

/**
 * An email field that allows a user to specify a default value
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class EditableEmailFieldWithDefault extends EditableEmailField
{
    public static $singular_name = 'Email field with default';

    public static $plural_name = 'Email fields with default';

    public function Icon()
    {
        return 'userforms/images/editableemailfield.png';
    }

    public function getFieldConfiguration()
    {
        $fields = parent::getFieldConfiguration();
        singleton('DefaultEditableFieldHelper')->updateFieldConfiguration($this, $fields);
        return $fields;
    }
    
    public function getFormField()
    {
        $field = parent::getFormField();
        $field->setValue(null);
        return singleton('DefaultEditableFieldHelper')->updateFormField($this, $field);
    }
}
