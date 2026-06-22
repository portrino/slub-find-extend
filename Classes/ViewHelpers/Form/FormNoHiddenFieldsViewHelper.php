<?php

namespace Slub\SlubFindExtend\ViewHelpers\Form;

class FormNoHiddenFieldsViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper
{
    protected function renderHiddenReferrerFields(): string
    {
        return '';
    }

    protected function renderTrustedPropertiesField(): string
    {
        return '';
    }
}
