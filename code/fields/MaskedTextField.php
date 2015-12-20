<?php

/**
 * A text field that supports masking
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class MaskedTextField extends TextField
{

    /**
     * A Mask used for describing how the form should appear
     *
     * a - Represents an alpha character (A-Z,a-z)
     * 9 - Represents a numeric character (0-9)
     * * - Represents an alphanumeric character (A-Z,a-z,0-9)
     *
     * @see http://digitalbush.com/projects/masked-input-plugin/
     *
     * @var String
     */
    protected $inputMask;

    /**
     * Returns an input field, class="text" and type="text" with an optional maxlength
     */
    public function __construct($name, $title = null, $value = "", $mask = '', $maxLength = null, $form = null)
    {
        $this->inputMask = $mask;
        parent::__construct($name, $title, $value, $maxLength, $form);
    }

    /**
     * Sets the input mask
     *
     * @param String $mask
     */
    public function setInputMask($mask)
    {
        $this->inputMask = $mask;
    }

    public function Field($properties = array())
    {
        $tag = parent::Field($properties);

        // add in the logic for the masking
        Requirements::javascript('editableuserforms/javascript/jquery.maskedinput-1.4.1.min.js');
        $id = $this->id();
        $mask = $this->inputMask;
        $js = <<<JS
(function ($) {
	$().ready(function () {
		$('#$id').mask('$mask');
	});
})(jQuery);
JS;
        Requirements::customScript($js, $id . 'JS');
        return $tag;
    }
}
