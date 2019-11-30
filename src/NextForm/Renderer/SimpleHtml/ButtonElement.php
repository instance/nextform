<?php

/**
 *
 */
namespace Abivia\NextForm\Renderer\SimpleHtml;

use Abivia\NextForm\Data\Labels;
use Abivia\NextForm\Renderer\Attributes;
use Abivia\NextForm\Renderer\Block;
use Abivia\NextForm\Renderer\Html\ButtonElementBase;

class ButtonElement extends ButtonElementBase {

    protected function inputGroup(Labels $labels, Attributes $attrs) : Block
    {
        if ($labels->has('help')) {
            $attrs->set('aria-describedby', $attrs->get('id') . '_formhelp');
        }
        $input = $this->engine->writeElement('div', ['show' => 'inputWrapperAttributes']);
        $input->body .= $this->engine->writeLabel('before', $labels->before, 'span')
            . $this->engine->writeTag('input', $attrs)
            . $this->engine->writeLabel('after', $labels->after, 'span') . "\n";

        // Generate help text, if any
        if ($labels->has('help')) {
            $input->body .= $this->engine->queryContext('inCell') ? '&nbsp;' : "<br/>\n";
            $input->body .= $this->engine->writeLabel(
                'help', $labels->help, 'small',
                new Attributes('id', $attrs->get('aria-describedby')),
                ['break' => true]
            );
        }
        return $input;
    }

}
