<?php

/**
 * A text field that allows a user to specify a default value
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class EditableTextFieldWithDefault extends EditableTextField {

	static $singular_name = 'Text field with keyword default';
	static $plural_name = 'Text fields with keyword default';

	public function Icon() {
		return 'userforms/images/editabletextfield.png';
	}

	function getFieldConfiguration() {
		$fields = parent::getFieldConfiguration();
		// eventually replace hard-coded "Fields"?
		singleton('DefaultEditableFieldHelper')->updateFieldConfiguration($this, $fields);
		return $fields;
	}

	function getFormField() {
		$field = parent::getFormField();
		return singleton('DefaultEditableFieldHelper')->updateFormField($this, $field);
	}

}
