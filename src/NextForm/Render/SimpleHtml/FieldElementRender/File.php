<?php

/**
 *
 */
namespace Abivia\NextForm\Render\SimpleHtml\FieldElementRender;

use Abivia\NextForm\Data\Labels;
use Abivia\NextForm\Render\Attributes;
use Abivia\NextForm\Render\Block;
use Abivia\NextForm\Render\Html\FieldElementRender\File as BaseFile;

class File extends BaseFile {

    /**
     * Generate the input element and any wrapping/supporting code.
     */
    protected function inputGroup(
        Labels $labels,
        Attributes $attrs
    ) : Block
    {
        // Start the input group
        $input = $this->engine->writeElement(
            'div',
            ['show' => 'inputWrapperAttributes']
        );

        $input->body .= $this->engine->writeLabel('span', $labels, 'before');
        $input->body .= $this->engine->writeTag('input', $attrs) . "\n";
        $input->body .= $this->engine->writeLabel('span', $labels, 'after');

        // Generate help text, if any
        if ($labels->has('help')) {
            $helpAttrs = new Attributes();
            $helpAttrs->set('id', $attrs->get('aria-describedby'));
            $helpAttrs->itemAppend('class', 'form-text text-muted');
            $input->body .= $this->engine->writeTag(
                'small',
                $helpAttrs,
                $labels->get('help')
            ) . "\n";
        }

        return $input;
    }

}
