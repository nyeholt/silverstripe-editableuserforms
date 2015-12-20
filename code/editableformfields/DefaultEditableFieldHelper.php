<?php

/**
 * @author marcus
 */
class DefaultEditableFieldHelper
{
    public function updateFieldConfiguration($editableFormField, $fieldList)
    {
        $baseName = "Fields[$editableFormField->ID]";
        $default = $editableFormField->getSetting('DefaultValue');

        $field = TextField::create(
            $editableFormField->getSettingName('DefaultValue'),
            _t('EditableUserForms.DEFAULT_FIELD_VALUE', 'Default Value'),
            $default
        );

        $fieldList->push($field);
    }
    
    public function updateFormField($editableFormField, $field = null, $fieldType = 'TextField')
    {
        if (!$field) {
            if ($editableFormField->getSetting('Rows') && $editableFormField->getSetting('Rows') > 1) {
                $field = TextareaField($editableFormField->Name, $editableFormField->Title, $editableFormField->getSetting('Rows'));
            } else {
                $field = new $fieldType($editableFormField->Name, $editableFormField->Title, null, $editableFormField->getSetting('MaxLength'));
            }
        }

        $default = $editableFormField->getSetting('DefaultValue');
        if (($field->Value() === null) && strpos($default, '$') !== false) {
            preg_match_all('/\$([\w.]+)/', $default, $matches);
            foreach ($matches[1] as $match) {
                if (strpos($match, '.')) {
                    // if it's a compound, lets get an object to use

                    list($object, $value) = explode('.', $match);
                    $item = null;
                    switch ($object) {
                        case 'Member': {
                                $item = Member::currentUser();
                                break;
                            }
                        default: {
                                $controller = Controller::curr();
                                if ($controller instanceof ContentController) {
                                    $item = $controller->data();
                                }
                                break;
                            }
                    }
                    if ($item) {
                        /* @var $item DataObject */
                        $default = str_replace('$' . $match, $item->getField($value), $default);
                    } else {
                        $default = '';
                    }
                } else {
                }
            }
        }

        $field->setValue($default);
        return $field;
    }
}
