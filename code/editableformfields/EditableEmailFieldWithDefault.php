<?php

/**
 * An email field that allows a user to specify a default value
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class EditableEmailFieldWithDefault extends EditableTextFieldWithDefault
{
    static $singular_name = 'Email field with default';

	static $plural_name = 'Email fields with default';

	public function Icon() {
		return 'userforms/images/editableemailfield.png';
	}

	function getFormField($fieldType='TextField') {
		return parent::getFormField('EmailField');
	}

	public function getValidation() {
		$options = array(
			'email' => true
		);

		if($this->getSetting('MinLength')) $options['minlength'] = $this->getSetting('MinLength');
		if($this->getSetting('MaxLength')) $options['maxlength'] = $this->getSetting('MaxLength');

		return $options;
	}
}