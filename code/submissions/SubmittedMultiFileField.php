<?php

/**
 * A file uploaded on a {@link UserDefinedForm} and attached to a single
 * {@link SubmittedForm}.
 *
 * @package userforms
 */

class SubmittedMultiFileField extends SubmittedFormField
{

    protected $attachments;

    public function Attachments()
    {
        if ($this->attachments) {
            return $this->attachments;
        }

        $idStr = trim($this->Value);
        if (strlen($idStr)) {
            $ids = explode(',', $idStr);
            $this->attachments = File::get()->filter(array('ID:ExactMatch' => $ids));
            return $this->attachments;
        }
        return ArrayList::create();
    }

    /**
     * Return the value of this field for inclusion into things such as
     * reports.
     *
     * @return string
     */
    public function getFormattedValue()
    {
        $attachments = $this->Attachments();
        $parts = array();
        foreach ($attachments as $a) {
            $name = $a->Title;
            $link = $a->getURL();

            if ($link) {
                if ($a instanceof Image) {
                    $linkText = $a->SetRatioSize(100, 100)->forTemplate();
                } else {
                    $linkText = 'Download ' . Convert::raw2xml($name);
                }
                $parts[] = sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    $link, $linkText
                );
            }
        }

        if (count($parts)) {
            $string = '<p>' . implode('</p><p>', $parts) . '</p>';
            return DBField::create_field('HTMLText', $string);
        }

        return false;
    }

    /**
     * Return the value for this field in the CSV export.
     *
     * @return string
     */
    public function getExportValue()
    {
        return ($link = $this->getLink()) ? $link : "";
    }

    /**
     * Return the link for the file attached to this submitted form field.
     *
     * @return string
     */
    public function getLink()
    {
        if ($attachments = $this->Attachments()) {
            $file = $attachments->first();
            if ($file && trim($file->getFilename(), '/') != trim(ASSETS_DIR, '/')) {
                return $file->getURL();
            }
        }
    }
}
