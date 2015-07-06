# [Editable User Forms](https://packagist.org/packages/nyeholt/silverstripe-editableuserforms)

Allows users to re-edit submissions made from a userdefined form. 

## Requirement

* SilverStripe 3.1.X
* [Userforms](https://github.com/silverstripe/silverstripe-userforms)
* [Dropzone](https://github.com/unclecheese/silverstripe-dropzone) for file uploads

## Getting Started

* Place the module under your root project directory.
* dev/build
* Create and configure an Editable User Defined Form page

## Overview

Aside from the normal User Defined Form options, the module provides several additional configuration options

### Config options

* ShowSubmittedList -  display the list of complete submissions the user has put together
* ShowDraftList -  display the list of in-complete submissions 
* AllowEditingComplete - Whether users should be able to edit 'completed' submissions
* ShowSubmitButton - Whether the form should display the 'submit' button. If this button is _not_ displayed, 
  the form will automatically be 'submitted' (ie, marked as 'complete') when a user fills out all required fields
* ShowPreviewButton - show the 'preview' button - a read-only version of the form for printing out
* ShowDeleteButton - show the 'delete' button in form submission listings
* ShowButtonsOnTop - show buttons across the top of the form as well as the base
* LoadLastSubmission - loads the most recent 'draft' submission the user made when they land on the form page
* SubmitWarning - text to display to the user when they click the 'submit' button
* Workflow definition - if specified, a form submission will be sent to a workflow for approval

### Fields

* `EditableTextFieldWithDefault` - Provides a textbox field that can be pre-populated with a field value. For example,
  `$Member.Email`
* `EditableEmailFieldWithDefault` - An email specific field extended from the TextFieldWithDefault
* `EditableMultiFileField` - Allows for the upload of one or more files from the frontend using Dropzone. Note: the  
  standard EditableFileField does NOT work for re-editable forms
* `FormSaveField` - inserts a 'save' button into the form for users to incrementally save as they go

### Workflow actions

* `SetPropertyAction` - A workflow action that sets a value on a data object field when triggered. Used for marking
  form submissions as 'Complete'



