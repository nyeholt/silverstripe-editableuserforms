<?php

class EditableMultiFileField extends EditableFormField {
	private static $singular_name = 'Multi File Upload Field';
	
	private static $plural_name = 'Multi File Upload Fields';
	
	public function Icon() {
		return 'userforms/images/editablefilefield.png';
	}

	public function getFieldConfiguration() {
		$fields = parent::getFieldConfiguration();

		$folder = ($this->getSetting('Folder')) ? $this->getSetting('Folder') : null;

		$tree = UserformsTreeDropdownField::create(
			$this->getSettingName("Folder"),
			_t('EditableUploadField.SELECTUPLOADFOLDER', 'Select upload folder'),
			"Folder"
		);

		$tree->setValue($folder);
		$fields->push($tree);

		$randomise = ($this->getSetting('Obfuscate')) ? $this->getSetting('Obfuscate') : null;
		$cb = CheckboxField::create($this->getSettingName('Obfuscate'), 'Obfuscate upload folder - provides some hiding of uploaded files', $randomise);
		$fields->push($cb);
		
		$multiple = $this->getSetting('MultipleUploads');
		$cb = CheckboxField::create($this->getSettingName("MultipleUploads"), 'Allow multiple uploads');
		$cb->setValue($multiple ? true : false);
		
		$fields->push($cb);

		return $fields;
	}

	public function getFormField() {
		$field = FileAttachmentField::create($this->Name, $this->Title);
		
//		$field = FileField::create($this->Name, $this->Title);

		if($this->getSetting('Folder')) {
			$folder = Folder::get()->byId($this->getSetting('Folder'));

			if($folder) {
				$field->setFolderName(
					preg_replace("/^assets\//","", $folder->Filename)
				);
			}
		}
		
		if ($this->getSetting('Obfuscate')) {
			$folder = rtrim($field->getFolderName(), '/');
			$folder .= '/' . md5(time() + mt_rand());
			$field->setFolderName($folder);
		}
		
		if ($this->getSetting('MultipleUploads')) {
			$field->setMultiple(true);
		}

		if ($this->Required) {
			// Required validation can conflict so add the Required validation messages
			// as input attributes
			$errorMessage = $this->getErrorMessage()->HTML();
			$field->setAttribute('data-rule-required', 'true');
			$field->setAttribute('data-msg-required', $errorMessage);
		}
		
		return $field;
	}
	
	/**
	 * Return the value for the database, link to the file is stored as a
	 * relation so value for the field can be null.
	 *
	 * @return string
	 */
	public function getValueFromData($data) {
		$val = isset($data[$this->Name]) ? $data[$this->Name] : null;
		return is_array($val) ? implode(',', $val) : $val;
	}
	
	public function getSubmittedFormField() {
		return new SubmittedMultiFileField();
	}
}