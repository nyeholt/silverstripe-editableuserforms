<?php

/**
 * A field used to allow 'save' buttons to be intersperesed in an
 * editable form submission
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class FormSaveField extends EditableFormField {

	static $singular_name = 'Save button';
	static $plural_name = 'Save buttons';

	public function Icon() {
		return 'editableuserforms/images/formsavefield.png';
	}

	/**
	 * Return a button for saving this form if the user is logged in
	 *
	 * @return FormField
	 */
	function getFormField() {
		if (Member::currentUserID()) {
			$id = $this->Name;
			$field = new EditableUserFormSaveButton($id, $this->Title);
		} else {
			$field = new LiteralField('NULL', '');
		}

		return $field;
	}

}

/**
 * Allows users to add multiple save buttons to the form
 */
class EditableUserFormSaveButton extends DatalessField {

	protected $text;

	function __construct($name, $text) {
		parent::__construct($name, '');
		$this->text = $text;
	}

	public function Field($properties = array()) {
		if ($this->readonly) {
			return '';
		}

		$label = $this->Title();

		$id = $this->name;
		$title = Convert::raw2htmlatt($this->text);
		$fieldHtml = <<<HTML
		<div id="$id" class="field formaction cancel">
		<label class="left" for="Form_Form_action_storesubmission">$label</label>
		<div class="middleColumn">
		<input id="$id" class="action cancel" type="submit" title="$title" value="$title" name="action_storesubmission"/>
		</div>
		</div>
HTML;
		return $fieldHtml;
	}

}
