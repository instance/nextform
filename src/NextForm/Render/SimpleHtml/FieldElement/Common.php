<?php

/**
 *
 */
namespace Abivia\NextForm\Render\SimpleHtml\FieldElement;

use Abivia\NextForm\Data\Labels;
use Abivia\NextForm\Render\Attributes;
use Abivia\NextForm\Render\Block;
use Abivia\NextForm\Render\Html\FieldElement\Common as BaseCommon;

class Common extends BaseCommon {

    /**
     * Generate the input and any associated labels, inside a wrapping div.
     *
     * @param Labels $labels
     * @param Attributes $attrs
     * @return Block
     */
    protected function inputGroup(Labels $labels, Attributes $attrs) : Block
    {
        $input = $this->engine->writeElement('div', ['show' => 'inputWrapperAttributes']);
        $input->body .= $this->engine->writeLabel('before', $labels->before, 'span');
        // Generate the input element
        $input->body .= $this->engine->writeTag('input', $attrs)
            . $this->engine->writeLabel('after', $labels->after, 'span') . "\n";
        return $input;
    }

}