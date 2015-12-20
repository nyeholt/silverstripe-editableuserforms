<?php

/**
 * An extension of a user defined form that allows a user to re-edit a submission
 * at a later point in time if they so desired
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class EditableUserDefinedForm extends UserDefinedForm
{
    
    const PENDING = 'Pending Approval';
    const COMPLETE = 'Complete';

    private static $db = array(
        'ShowSubmittedList'        => 'Boolean',    // Whether the list of previous submissions should be shown
        'ShowDraftList'            => 'Boolean',    // Show the list of drafts this user has submitted
        'AllowEditingComplete'    => 'Boolean',    // are 'complete' submissions allowed to be re-edited?
        'ShowSubmitButton'        => 'Boolean',        // Users can choose to display the submit button or not - this is useful for when users
                                                    // have put a continual 'save' button in the form. If this is false, completeness is
                                                    // determined by whether all required fields have been completed.

        'ShowPreviewButton'        => 'Boolean',        // whether to display the 'preview' and PDF buttons at the bottom of the page
        'ShowDeleteButton'        => 'Boolean',        // whether to display the 'delete' button on the form
        'ShowButtonsOnTop'        => 'Boolean',        // do we show buttons along the top of the form too?
        'LoadLastSubmission'    => 'Boolean',        // If set to true, the system will automatically load the user's last
                                                    // "Draft" submission when they come to the form
        'SubmitWarning'            => 'Varchar(255)',    // warning text shown before Submit button goes through

        'SubmissionTitleField'    => 'Varchar(255)',        // field to use as submission title

        'WorkflowID'            => 'Int',            // a workflow definition to be used for submissions
    );

    private static $defaults = array(
        'AllowEditingComplete'    => 1,
        'ShowSubmitButton'        => 1,
        'ShowSubmittedList'        => 1,
        'ShowDraftList'            => 1,
    );

    /**
     * @return FieldSet
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        

        $fields->addFieldToTab('Root.FormOptions', CheckboxField::create('ShowSubmittedList', 'Show the list of this user\'s submissions'));
        $fields->addFieldToTab('Root.FormOptions', CheckboxField::create('ShowDraftList', 'Show the list of this user\'s draft submissions'));

        
        $fields->addFieldToTab('Root.FormOptions', new CheckboxField('AllowEditingComplete', 'Allow "Complete" submissions to be re-edited'));
        $fields->addFieldToTab('Root.FormOptions', new CheckboxField('ShowSubmitButton', 'Show the submit button - if not checked, forms will be "submitted" as soon as all required fields are complete'));
        $fields->addFieldToTab('Root.FormOptions', new TextField('SubmitWarning', 'A warning to display to users when the submit button is clicked'));

        $fields->addFieldToTab('Root.FormOptions', new CheckboxField('ShowPreviewButton', 'Show the buttons to preview the form'));
        $fields->addFieldToTab('Root.FormOptions', new CheckboxField('ShowDeleteButton', 'Show the button to delete this form submission'));
        $fields->addFieldToTab('Root.FormOptions', new CheckboxField('ShowButtonsOnTop', 'Show the form action buttons above the form as well as below'));
        $fields->addFieldToTab('Root.FormOptions', new CheckboxField('LoadLastSubmission', 'Automatically load the latest incomplete submission when a user visits the form'));

        $formFields = $this->Fields();
        $options = array();
        foreach ($formFields as $editableField) {
            $options[$editableField->Name] = $editableField->Title;
        }
        
        $fields->addFieldToTab('Root.FormOptions', $df = DropdownField::create('SubmissionTitleField', 'Title field to use for new submissions', $options));
        $df->setEmptyString('-- field --');
        $df->setRightTitle('Useful if submissions are to be listed somewhere and a sort field is required');

        if (class_exists('WorkflowDefinition')) {
            $definitions = WorkflowDefinition::get()->map();
            $field = DropdownField::create('WorkflowID', 'Submission workflow', $definitions)->setEmptyString('-- select a workflow --');
            $field->setRightTitle('Workflow to use for making a submission complete');
            $fields->addFieldToTab('Root.FormOptions', $field);
        }
        
        return $fields;
    }


    /**
     * Returns a list of all the current user's submissions for this form
     *
     * @return DataList
     */
    public function UserSubmissions($user = null)
    {
        return $this->submissionList($user);
    }

    /**
     * Returns a list of all the current user's completed submissions for this form
     *
     * @return DataList
     */
    public function CompletedSubmissions($user = null)
    {
        return $this->submissionList($user, array('SubmissionStatus' => 'Complete'));
    }

    /**
     * Gets all the submissions of this form for the current user that they
     * are able to continue editing
     *
     * @return DataList
     */
    public function DraftSubmissions($user = null)
    {
        return $this->submissionList($user, array('SubmissionStatus:not' => 'Complete'));
    }

    /**
     * Get a list of the submissions for a particular user
     *
     * @param Member $user
     *			The user to get the list of submissions for
     * @param array $additional
     *			Any additional filter variables
     * @return DataList
     */
    protected function submissionList($user = null, $additional = array())
    {
        if (!$user) {
            $user = Member::currentUser();
        }

        if (!$user) {
            return array();
        }

        $filter = array(
            'ParentID' => $this->ID,
            'SubmittedByID' => Member::currentUserID(),
        );

        $filter = array_merge($filter, $additional);

        return SubmittedForm::get()->filter($filter);
    }
    
    public function getSubmissionWorkflow()
    {
        if ($this->WorkflowID) {
            return WorkflowDefinition::get()->byID($this->WorkflowID);
        }
    }
}

class EditableUserDefinedForm_Controller extends UserDefinedForm_Controller
{

    private static $allowed_actions = array(
        'index',
        'view',
        'resume',
        'printpdf',
        'cancelsubmission',
        'Form',
    );
    
    protected $submission = null;
    protected $readonly = false;
    protected $formValues = array();

    public function init()
    {
        parent::init();
        
        Requirements::javascript('editableuserforms/javascript/editable-userforms.js');
    }
    
    /**
     * If this form is configured to load an existing draft, do so here
     */
    public function index()
    {
        if ($this->data()->LoadLastSubmission) {
            // we need to load up the last draft submission if it exists
            $drafts = $this->data()->DraftSubmissions();
            if ($drafts && $drafts->Count() > 0) {
                $draft = $drafts->First();
                return $this->redirect($this->Link('resume').'/'.$draft->ID);
            }
        }
        return parent::index();
    }
    
    /**
     * Action called when you want to edit an existing form submission
     *
     * @return String
     */
    public function resume()
    {
        if ($this->request->param('ID')) {
            $id = (int) $this->request->param('ID');
            $resumeSubmission  = SubmittedForm::get()->byID($id);

            // if we don't have a submit button, then we can assume it's safe to re-edit the form
            // as many times as we want, so the 'editable' check can ignore the Complete status
            if ($resumeSubmission && $resumeSubmission->isReEditable($this->data()->AllowEditingComplete)) {
                $this->submission = $resumeSubmission;
            }
        }

        return parent::index();
    }

    /**
     * This provides a mechanism to view the form on the frontend in a read-only manner
     */
    public function view()
    {
        if ($this->request->param('ID')) {
            $id = (int) $this->request->param('ID');
            $resumeSubmission  = DataObject::get_by_id('SubmittedForm', $id);

            if ($resumeSubmission && $resumeSubmission->isViewable()) {
                $this->submission = $resumeSubmission;
                $this->readonly = true;
            }
        }

        return parent::index();
    }

    /**
     * This creates a PDF of the created form for the user to download
     */
    public function printpdf()
    {
        if ($this->request->param('ID')) {
            $id = $this->request->param('ID');
            if (strpos($id, '.')) {
                list($id, $pdf) = explode('.', $id);
            } else {
                $id = (int) $id;
            }
            $resumeSubmission  = DataObject::get_by_id('SubmittedForm', $id);

            if ($resumeSubmission && $resumeSubmission->isViewable()) {
                // okay, we don't want to view it, we want to create a URL for the pdf renderer to view instead
                $url = $this->Link('view').'/'.$id;

                $name = str_replace(' ', '_', $this->MenuTitle . '-' . date('Y-m-d_H.i.s').'.pdf');
                singleton('PDFRenditionService')->renderUrl($url, 'browser', $name);
            }
        }

        return parent::index();
    }

    public function Form()
    {
        $form = parent::Form();
        if (!$form) {
            return;
        }
        if ($this->ShowButtonsOnTop) {
            $form->ShowButtonsOnTop = true;
        }

        $form->setTemplate('EditableUserDefinedFormControl');

        // now add an action for "Save"
        $fields = $form->Fields();

        // first, lets add any custom Note text that is needed
        foreach ($this->Fields() as $editableField) {
            if ($editableField instanceof EditableFileField) {
                $fields->removeByName($editableField->Name);
                continue;
            }
            $formField = $fields->fieldByName($editableField->Name);
            if ($formField && $editableField->getSetting('Note')) {
                $title = $formField->Title() .'<span class="userformFieldNote">' . Convert::raw2xml($editableField->getSetting('Note')).'</span>';
                $formField->setTitle($title);
            }
        }

        // lets see if there's a submission that we should be loading into the form
        if ($this->submission && $this->submission->ID) {
            $this->submission->exposeDataFields();
            $form->loadDataFrom($this->submission);
            $fields->push(new HiddenField('ResumeID', '', $this->submission->ID));
            
            $workflowState = $this->submission->getWorkflowState();
            if ($workflowState !== false) {
                $form->addExtraClass("workflow-review");
                if ($workflowState == 'Readonly') {
                    $fields->unshift(LiteralField::create('workflow_warning', '<p class="message warning">Currently under review</p>'));
                    $this->readonly = true;
                } else {
                    $fields->unshift(LiteralField::create('workflow_warning', '<p class="message warning">Review via ' . Convert::raw2xml($workflowState) . ' </p>'));
                }
            }
        }

        if ($this->readonly) {
            $form->makeReadonly();
        }

        $actions = $form->Actions();

        if (!$this->ShowSubmitButton) {
            $actions->removeByName('action_process');
        } else {
            if (strlen($this->SubmitWarning)) {
                $submitName = $actions->fieldByName('action_process');
                $submitName->setAttribute('data-submitwarning', Convert::raw2att($this->SubmitWarning));
                $submitName->addExtraClass('submitwarning');
            }
        }

        if (!$this->readonly) {
            if (Member::currentUserID()) {
                $actions->push($action = new FormAction('storesubmission', 'Save'));
                $action->setAttribute('formnovalidate', 'formnovalidate');
                
                // $actions->push(new FormAction('cancelsubmission', 'Cancel', null, null, 'cancel'));

                if ($this->ShowPreviewButton) {
                    $actions->push($action = new FormAction('previewsubmission', 'Preview'));
                    $action->setAttribute('formnovalidate', 'formnovalidate');
                    $actions->push($action = new FormAction('previewpdfsubmission', 'PDF / Print'));
                    $action->setAttribute('formnovalidate', 'formnovalidate');
                }
                
                if ($this->submission && $this->ShowDeleteButton) {
                    $actions->push($action = new FormAction('cancelsubmission', 'Delete'));
                    $action->setAttribute('formnovalidate', 'formnovalidate');
                }
            }
        } else {
            $form->setActions(ArrayList::create());
        }

        if ($this->submission && $this->readonly) {
            $actions->push(new LiteralField("PrintLink", '<a class="editableFormPDFLink" href="'.$this->submission->PDFLink().'">Download PDF</a>'));
        }

        // finally - we want to check if this request is trying to do an action that doesn't care about validation.
        // IF we are, then we want to clear the validation for this form
        if (!isset($_REQUEST['action_process'])) {
            $form->unsetValidator();
        }
        
        return $form;
    }
    
    /**
     * Save a form submission as "Saved" but not yet completely submitted.
     * 
     *
     * @param array $data
     * @param Form $form
     * @return String
     */
    public function storesubmission($data, $form)
    {
        if (Member::currentUserID()) {
            $submission = $this->saveSubmission($data, $form);
            return $this->redirect($this->Link() . 'resume/' . ((int) $submission->ID));
        }
        return $this->redirect($this->Link());
    }

    public function previewsubmission($data, $form)
    {
        if (Member::currentUserID()) {
            $submission = $this->saveSubmission($data, $form);
            return $this->redirect($this->Link() . 'view/' . ((int) $submission->ID) . '?print=1');
        }
        return $this->redirect($this->Link());
    }

    public function previewpdfsubmission($data, $form)
    {
        if (Member::currentUserID()) {
            $submission = $this->saveSubmission($data, $form);
            return $this->redirect($submission->PDFLink());
        }
        return $this->redirect($this->Link());
    }

    /**
     * Encapsulation of logic needed to save data into the form, usable by any action
     * that needs to save the data before forwarding to somewhere else, whether it be 'save', 'viewpdf', etc
     *
     * @param array $data
     * @param Form $form
     */
    protected function saveSubmission($data, $form)
    {
        if (Member::currentUserID()) {
            $submission = $this->processSubmission($data, $form);
            $submission->SubmissionStatus = 'Saved';

            $workflow = false;
            // check to see whether or not all required fields were filled out
            if (!$this->ShowSubmitButton) {
                // assume it's complete unless we find a required field that's not filled out
                $submission->SubmissionStatus = EditableUserDefinedForm::COMPLETE;
                foreach ($this->Fields() as $field) {
                    if ($field->Required) {
                        $value = null;
                        if ($field->hasMethod('getValueFromData')) {
                            $value = $field->getValueFromData($data);
                        } else {
                            if (isset($data[$field->Name])) {
                                $value = $data[$field->Name];
                            }
                        }

                        if (empty($value)) {
                            // means it's not complete
                            $submission->SubmissionStatus = 'Saved';
                            break;
                        }
                    }
                }
                
                if ($this->data()->WorkflowID && $submission->SubmissionStatus == EditableUserDefinedForm::COMPLETE) {
                    $submission->SubmissionStatus = EditableUserDefinedForm::PENDING;
                }
            }

            $submission->write();
            
            if ($submission->SubmissionStatus == EditableUserDefinedForm::PENDING) {
                singleton('WorkflowService')->startWorkflow($submission, $this->data()->WorkflowID);
            }

            return $submission;
        }
    }


    /**
     * Called to cancel the submission of a form
     *
     * @param array $data
     * @param Form $form
     */
    public function cancelsubmission($data, $form=null)
    {
        $submissionId = isset($data['ResumeID']) ? (int) $data['ResumeID'] : 0;
        if ($submissionId) {
            $submission  = DataObject::get_by_id('SubmittedForm', $submissionId);
            if ($submission && $submission->isDeleteable()) {
                // delete it!
                $submission->delete();
            }
        }

        return $this->redirect($this->Link());
    }


    /**
     * Need to override the whole process method to be able to catch the fact
     * that we might be editing a resumed entry 
     *
     * @param Array Data
     * @param Form Form
     * @return Redirection
     */
    public function process($data, $form)
    {
        Session::set("FormInfo.{$form->FormName()}.data", $data);
        Session::clear("FormInfo.{$form->FormName()}.errors");
        
        foreach ($this->Fields() as $field) {
            $messages[$field->Name] = $field->getErrorMessage()->HTML();
            $formField = $field->getFormField();

            if ($field->Required && $field->CustomRules()->Count() == 0) {
                if (isset($data[$field->Name])) {
                    $formField->setValue($data[$field->Name]);
                }

                if (
                    !isset($data[$field->Name]) ||
                    !$data[$field->Name] ||
                    !$formField->validate($form->getValidator())
                ) {
                    $form->addErrorMessage($field->Name, $field->getErrorMessage(), 'bad');
                }
            }
        }
        
        if (Session::get("FormInfo.{$form->FormName()}.errors")) {
            Controller::curr()->redirectBack();
            return;
        }
        
        
        $submission = $this->processSubmission($data, $form);
        if ($submission) {
            if ($this->data()->WorkflowID) {
                $submission->SubmissionStatus = EditableUserDefinedForm::PENDING;
            } else {
                $submission->SubmissionStatus = EditableUserDefinedForm::COMPLETE;
            }

            $submission->write();
            if ($submission->SubmissionStatus == EditableUserDefinedForm::PENDING) {
                $instance = new WorkflowInstance();
                $instance->beginWorkflow($this->data()->getSubmissionWorkflow(), $submission);
                $instance->execute();
            }
        }

        // set a session variable from the security ID to stop people accessing 
        // the finished method directly.
        if (!$this->DisableAuthenicatedFinishAction) {
            if (isset($data['SecurityID'])) {
                Session::set('FormProcessed', $data['SecurityID']);
            } else {
                // if the form has had tokens disabled we still need to set FormProcessed
                // to allow us to get through the finshed method
                if (!$this->Form()->getSecurityToken()->isEnabled()) {
                    $randNum = rand(1, 1000);
                    $randHash = md5($randNum);
                    Session::set('FormProcessed', $randHash);
                    Session::set('FormProcessedNum', $randNum);
                }
            }
        }
        
        if (!$this->DisableSaveSubmissions) {
            Session::set('userformssubmission'. $this->ID, $submission->ID);
        }
        
        $referrer = (isset($data['Referrer'])) ? '?referrer=' . urlencode($data['Referrer']) : "";
        return $this->redirect($this->Link('finished') . $referrer . $this->config()->finished_anchor);
    }


    /**
     * Do the dirty work of processing the form submission and saving it if necessary
     *
     * This has been overridden to be able to re-edit existing form submissions
     */
    protected function processSubmission($data, $form)
    {
        $submittedForm = SubmittedForm::create();

        $reEdit = false;
        if (isset($data['ResumeID'])) {
            $resumeSubmission = DataObject::get_by_id('SubmittedForm', (int) $data['ResumeID']);
            // make sure it was this user that submitted it
            if ($resumeSubmission->isReEditable()) {
                $submittedForm = $resumeSubmission;
                $reEdit = true;
            }
        }

        $submittedForm->SubmittedByID = ($id = Member::currentUserID()) ? $id : 0;
        $submittedForm->ParentID = $this->ID;
        $submittedForm->Recipient = $this->EmailTo;

        if (!$this->DisableSaveSubmissions) {
            $submittedForm->write();
        }

        // email values
        $values = array();
        $recipientAddresses = array();
        $sendCopy = false;
        $attachments = array();

        $submittedFields = ArrayList::create();
        
        $titleField = $this->data()->SubmissionTitleField;

        foreach ($this->Fields() as $field) {
            // don't show fields that shouldn't be shown
            if (!$field->showInReports()) {
                continue;
            }

            $submittedField = null;
            if ($reEdit) {
                // get the field from the existing submission, otherwise return it
                // from the form field directly
                $submittedField = $submittedForm->getFormField($field->Name);
            }

            // we want to do things this way to ensure that we have a submittedField - sometimes a field won't 
            // existing on a form re-edit (eg if the form changes)
            if (!$submittedField) {
                $submittedField = $field->getSubmittedFormField();
            }
            
            $submittedField->ParentID = $submittedForm->ID;
            $submittedField->Name = $field->Name;
            $submittedField->Title = $field->getField('Title');

            if ($field->hasMethod('getValueFromData')) {
                $submittedField->Value = $field->getValueFromData($data);
            } else {
                if (isset($data[$field->Name])) {
                    $submittedField->Value = $data[$field->Name];
                }
            }

            if ($titleField == $field->Name) {
                $submittedForm->SubmissionTitle = $submittedField->Value;
            }

            if (!empty($data[$field->Name])) {
                if (in_array("EditableFileField", $field->getClassAncestry())) {
                    if (isset($_FILES[$field->Name])) {
                        $foldername = $field->getFormField()->getFolderName();
                        
                        // create the file from post data
                        $upload = new Upload();
                        $file = new File();
                        $file->ShowInSearch = 0;
                        
                        try {
                            $upload->loadIntoFile($_FILES[$field->Name], $file);
                        } catch (ValidationException $e) {
                            $validationResult = $e->getResult();
                            $form->addErrorMessage($field->Name, $validationResult->message(), 'bad');
                            Controller::curr()->redirectBack();
                            return;
                        }
                        
                        // write file to form field
                        $submittedField->UploadedFileID = $file->ID;

                        // Attach the file if its less than 1MB, provide a link if its over.
                        if ($file->getAbsoluteSize() < 1024*1024*1) {
                            $attachments[] = $file;
                        }
                    }
                }
            }
            
            $submittedField->extend('onPopulationFromField', $field);
            
            if (!$this->DisableSaveSubmissions) {
                $submittedField->write();
            }

            $submittedFields->push($submittedField);
        }

        $emailData = array(
            "Sender" => Member::currentUser(),
            "Fields" => $submittedFields
        );

        $this->extend('updateEmailData', $emailData, $attachments);
        
        // email users on submit.
        if ($recipients = $this->FilteredEmailRecipients($data, $form)) {
            $email = new UserDefinedForm_SubmittedFormEmail($submittedFields);
            
            if ($attachments) {
                foreach ($attachments as $file) {
                    if ($file->ID != 0) {
                        $email->attachFile(
                            $file->Filename,
                            $file->Filename,
                            HTTP::get_mime_type($file->Filename)
                        );
                    }
                }
            }

            foreach ($recipients as $recipient) {
                $email->populateTemplate($recipient);
                $email->populateTemplate($emailData);
                $email->setFrom($recipient->EmailFrom);
                $email->setBody($recipient->EmailBody);
                $email->setTo($recipient->EmailAddress);
                $email->setSubject($recipient->EmailSubject);
                
                if ($recipient->EmailReplyTo) {
                    $email->setReplyTo($recipient->EmailReplyTo);
                }

                // check to see if they are a dynamic reply to. eg based on a email field a user selected
                if ($recipient->SendEmailFromField()) {
                    $submittedFormField = $submittedFields->find('Name', $recipient->SendEmailFromField()->Name);

                    if ($submittedFormField && is_string($submittedFormField->Value)) {
                        $email->setReplyTo($submittedFormField->Value);
                    }
                }
                // check to see if they are a dynamic reciever eg based on a dropdown field a user selected
                if ($recipient->SendEmailToField()) {
                    $submittedFormField = $submittedFields->find('Name', $recipient->SendEmailToField()->Name);
                    
                    if ($submittedFormField && is_string($submittedFormField->Value)) {
                        $email->setTo($submittedFormField->Value);
                    }
                }
                
                // check to see if there is a dynamic subject
                if ($recipient->SendEmailSubjectField()) {
                    $submittedFormField = $submittedFields->find('Name', $recipient->SendEmailSubjectField()->Name);

                    if ($submittedFormField && trim($submittedFormField->Value)) {
                        $email->setSubject($submittedFormField->Value);
                    }
                }

                $this->extend('updateEmail', $email, $recipient, $emailData);

                if ($recipient->SendPlain) {
                    $body = strip_tags($recipient->EmailBody) . "\n";
                    if (isset($emailData['Fields']) && !$recipient->HideFormData) {
                        foreach ($emailData['Fields'] as $Field) {
                            $body .= $Field->Title .': '. $Field->Value ." \n";
                        }
                    }

                    $email->setBody($body);
                    $email->sendPlain();
                } else {
                    $email->send();
                }
            }
        }
        
        $submittedForm->extend('updateAfterProcess');

        Session::clear("FormInfo.{$form->FormName()}.errors");
        Session::clear("FormInfo.{$form->FormName()}.data");
        

        return $submittedForm;
    }
}
