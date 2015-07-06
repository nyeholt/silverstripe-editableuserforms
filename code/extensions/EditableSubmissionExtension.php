<?php

/**
 * A decorator to be applied to form submissions so that they can be
 * re-edited at a later point in time
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class EditableSubmissionExtension extends DataExtension {

	private static $db = array(
		'SubmissionTitle'		=> 'Varchar(255)',
		'SubmissionStatus'		=> "Varchar",
		'SubmissionTime'		=> 'SS_Datetime',
	);
	
	private static $defaults = array(
		'SubmissionStatus'		=> 'Draft',
	);
	
	public function exposeDataFields($inForm = null) {
		foreach ($this->owner->Values() as $submittedField) {
			$fieldName = $submittedField->Name;
			$fieldValue = $submittedField->Value;
			
			// radio / checkbox
			if ($fieldValue == 'No') {
				$editingField = $inForm ? $inForm->dataFieldByName($fieldName) : $submittedField->getEditableField();
				if ($editingField instanceof EditableCheckbox) {
					$fieldValue = false;
				}
			}
			$this->owner->$fieldName = $fieldValue;

//			if ($submittedField instanceof SubmittedFileField) {
//				// insert an image preview
//				$field = LiteralField::create('image_preview', $submittedField->getFormattedValue());
//				$form->Fields()->push($field);
//			}
		}
	}
	
	
	protected $labelledFieldMap;
	
	public function fieldByLabel($label) {
		if (!$this->labelledFieldMap) {
			$this->labelledFieldMap = array();
			foreach ($this->owner->Values() as $submittedField) {
				$this->labelledFieldMap[$submittedField->Title] = $submittedField;
			}
		}
		return isset($this->labelledFieldMap[$label]) ? $this->labelledFieldMap[$label] : null;
	}
	
	/**
	 * Set the submission time if needbe
	 */
	public function onBeforeWrite() {
		if ($this->owner->SubmissionStatus == EditableUserDefinedForm::COMPLETE && !$this->owner->SubmissionTime) {
			$this->owner->SubmissionTime = date('Y-m-d H:i:s');
		}
	}

	/**
	 * Gets a submitted form field for a given form field
	 *
	 * @param String $name
	 *
	 * @return SubmittedFormField
	 */
	public function getFormField($name) {
		foreach ($this->owner->Values() as $field) {
			if ($field->Name == $name) {
				return $field;
			}
		}
	}

	/**
	 * Is this submission editable?
	 *
	 * @return boolean
	 */
	public function isReEditable($allowEditingOfComplete = true) {
		return $this->isViewable() && ($allowEditingOfComplete || $this->owner->SubmissionStatus != EditableUserDefinedForm::COMPLETE);
	}

	/**
	 * Is this submission viewable on the frontend?
	 *
	 * @return boolean
	 */
	public function isViewable() {
		return Permission::checkMember(null, 'ADMIN') || ($this->owner->SubmittedByID && $this->owner->SubmittedByID == Member::currentUserID());
	}

	/**
	 * Can this submission be deleted?
	 */
	public function isDeleteable() {
		return ($this->isViewable() && $this->owner->SubmissionStatus == 'Draft') || Permission::check('ADMIN');
	}

	/**
	 * Get a URL that can be used for linking to the resume interface for this submission
	 */
	public function ResumeLink() {
		$parentForm = $this->owner->Parent();
		return $parentForm->Link('resume') . '/' . (int) $this->owner->ID;
	}

	/**
	 * Get a URL that can be used for linking to the resume interface for this submission
	 */
	public function ViewLink() {
		$parentForm = $this->owner->Parent();
		return $parentForm->Link('view') . '/' . (int) $this->owner->ID;
	}

	/**
	 * Get a URL that can be used for linking to the resume interface for this submission
	 */
	public function PDFLink() {
		$parentForm = $this->owner->Parent();
		return $parentForm->Link('printpdf') . '/' . (int) $this->owner->ID . '.pdf';
	}
	
	public function WorkflowLink() {
		return "admin/pages/edit/EditForm/field/Submissions/item/{$this->owner->ID}/edit";
	}

	/**
	 * Useful for where a title might be needed
	 */
	public function Title() {
		return $this->owner->SubmissionStatus == EditableUserDefinedForm::COMPLETE ? 'Completed ' . date('l jS F \a\t g:ia', strtotime($this->owner->LastEdited)) : 'Draft started ' . date('l jS F \a\t g:ia', strtotime($this->owner->Created));
	}
	
	public function getWorkflowState() {
		if ($this->owner->Parent()->WorkflowID && $instance = $this->owner->getWorkflowInstance()) {
			if ($instance->canEditTarget()) {
				return $instance->Definition()->Title;
			}
			return 'Readonly';
		}
		return false;
	}

}
