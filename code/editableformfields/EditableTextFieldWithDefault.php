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
		$baseName = "Fields[$this->ID]";
		$default = $this->getSetting('DefaultValue');

		$extraFields = new FieldList(
			new TextField($baseName . "[CustomSettings][DefaultValue]", _t('EditableTextFieldWithDefault.DEFAULT_VALUE', 'Default Value'), $default)
		);

		$fields->merge($extraFields);
		return $fields;
	}

	function getFormField($fieldType = 'TextField') {
		$field = null;
		if ($this->getSetting('Rows') && $this->getSetting('Rows') > 1) {
			$field = TextareaField($this->Name, $this->Title, $this->getSetting('Rows'));
		} else {
			$field = new $fieldType($this->Name, $this->Title, null, $this->getSetting('MaxLength'));
		}

		$default = $this->getSetting('DefaultValue');
		if ($field->Value() === null && strpos($default, '$') !== false) {
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
