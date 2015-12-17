<?php

return;

/**
 * Overridden class to format the export in the way we want
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class EditableSubmittedFormReportField extends SubmittedFormReportField
{

    /**
     * Overidden to exclude certain fields (IE Save) from the export. Additionally,
     * we need to trim the result set of fields that don't exist in the field anymore, even if there
     * are form submissions that have values for those non-existent fields. 
     */
    public function export($fileName = null)
    {
        $separator = ",";

        // Get the UserDefinedForm to export data from the URL
        $SQL_ID = (isset($_REQUEST['id'])) ? Convert::raw2sql($_REQUEST['id']) : false;

        if ($SQL_ID) {
            $udf = DataObject::get_by_id("UserDefinedForm", $SQL_ID);
            if ($udf) {
                $fileName = str_replace(' ', '_', $udf->MenuTitle . '-' . date('Y-m-d_H.i.s') . '.csv');
                // we'll limit submissions to only those that are completed. 
                $submissions = $udf->Submissions(); //'"SubmissionStatus" = \'Complete\'');
                if ($submissions && $submissions->Count() > 0) {

                    // Get all the submission IDs (so we know what names/titles to get - helps for sites with many UDF's)
                    $inClause = array();
                    foreach ($submissions as $submission) {
                        $inClause[] = $submission->ID;
                    }

                    // Get the CSV header rows from the database

                    $tmp = DB::query("SELECT DISTINCT \"SubmittedFormField\".\"ID\", \"Name\", \"Title\"
						FROM \"SubmittedFormField\"
						LEFT JOIN \"SubmittedForm\" ON \"SubmittedForm\".\"ID\" = \"SubmittedFormField\".\"ParentID\"
						WHERE  \"SubmittedFormField\".\"ParentID\" IN (" . implode(',', $inClause) . ")
						ORDER BY \"SubmittedFormField\".\"ID\"");

                    // Sort the Names and Titles from the database query into separate keyed arrays
                    $stored = array();
                    foreach ($tmp as $array) {
                        // only store if we haven't got this field already
                        // TODO Specific hack here to handle save fields in editable user forms
                        if (!isset($stored[$array['Name']]) && $array['Title'] != 'Save') {
                            $csvHeaderNames[] = $array['Name'];
                            $csvHeaderTitle[] = $array['Title'];
                            $stored[$array['Name']] = true;
                        }
                    }

                    // For every submission...
                    $i = 0;
                    foreach ($submissions as $submission) {

                        // Get the rows for this submission (One row = one form field)
                        $dataRow = $submission->Values();
                        $rows[$i] = array();

                        // For every row/field, get all the columns
                        foreach ($dataRow as $column) {

                            // If the Name of this field is in the $csvHeaderNames array, get an array of all the places it exists
                            if ($index = array_keys($csvHeaderNames, $column->Name)) {
                                if (is_array($index)) {

                                    // Set the final output array for each index that we want to insert this value into
                                    foreach ($index as $idx) {
                                        $rows[$i][$idx] = $column->Value;
                                    }
                                    $rows[$i]['SubmissionStatus'] = $submission->SubmissionStatus;
                                    $rows[$i]['Submitted'] = $submission->LastEdited;
                                }
                            }
                        }

                        $i++;
                    }

                    $csvHeaderTitle[] = "Status";
                    $csvHeaderTitle[] = "Submitted";

                    // CSV header row
                    $csvData = '"' . implode('","', $csvHeaderTitle) . '"' . "\n";

                    // For every row of data (one form submission = one row)
                    foreach ($rows as $row) {
                        // Loop over all the names we can use
                        for ($i = 0; $i < count($csvHeaderNames); $i++) {
                            if (!isset($row[$i]) || !$row[$i]) {
                                $csvData .= '"",';
                            }    // If there is no data for this column, output it as blank instead
                            else {
                                $csvData .= '"' . str_replace('"', '\"', $row[$i]) . '",';
                            }
                        }
                        // Start a new row for each submission
                        $csvData .= '"' . $row['SubmissionStatus'] . '",' . '"' . $row['Submitted'] . '"' . "\n";
                    }
                } else {
                    user_error("No submissions to export.", E_USER_ERROR);
                }

                if (class_exists('SS_HTTPRequest')) {
                    SS_HTTPRequest::send_file($csvData, $fileName)->output();
                } else {
                    HTTPRequest::send_file($csvData, $fileName)->output();
                }
            } else {
                user_error("'$SQL_ID' is a valid type, but we can't find a UserDefinedForm in the database that matches the ID.", E_USER_ERROR);
            }
        } else {
            user_error("'$SQL_ID' is not a valid UserDefinedForm ID.", E_USER_ERROR);
        }
    }

    public function getSubmissions()
    {
        return $this->customise(array(
                'Submissions' => $this->Submissions(),
                'CanDelete' => Permission::checkMember(null, 'Edit'),
            ))->renderWith(array('EditableSubmittedFormReportField'));
    }

    public function Field()
    {
        Requirements::css(SAPPHIRE_DIR . "/css/SubmittedFormReportField.css");
        Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery-ui-1.8rc3.custom.js');
        Requirements::javascript("userforms/javascript/UserForm.js");
        $val = Permission::checkMember(null, 'Edit');
        return $this->customise(array(
                'CanDelete' => Permission::checkMember(null, 'Edit'),
            ))->renderWith("EditableSubmittedFormReportField");
    }

    public function performReadonlyTransformation()
    {
        $clone = clone $this;
        return $clone;
    }
}
