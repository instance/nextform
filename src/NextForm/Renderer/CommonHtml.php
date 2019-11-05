<?php
namespace Abivia\NextForm\Renderer;

use Abivia\NextForm\Contracts\RendererInterface;
use Abivia\NextForm\Form\Binding\Binding;
use Abivia\NextForm\Form\Binding\FieldBinding;

/**
 * Render methods common to HTML render classes
 */
abstract class CommonHtml extends Html implements RendererInterface
{

    /**
     * Maps element types to render methods.
     * @var array
     */
    static $renderMethodCache = [];

    public function __construct($options = [])
    {
        parent::__construct($options);
        self::$showDefaultScope = 'form';
        $this->initialize();
    }

    /**
     * Render a data list, if there is one.
     * @param \Abivia\NextForm\Renderer\Attributes $attrs Parent attributes.
     * @param Binding $binding The binding for the element we're rendering.
     * @param string $type The element type
     * @param array $options Options, specifically access rights.
     * @return \Abivia\NextForm\Renderer\Block
     */
    public function dataList(Attributes $attrs, Binding $binding, $type, $options)
    {
        $block = new Block();
        // Check for a data list, if there is write access.
        $list = $options['access'] === 'write' && Attributes::inputHas($type, 'list')
            ? $binding->getList(true) : [];
        if (!empty($list)) {
            $attrs->set('list', $attrs->get('id') . '_list');
            $block->body = '<datalist id="' . $attrs->get('list') . "\">\n";
            foreach ($list as $option) {
                $optAttrs = new Attributes();
                $optAttrs->set('value', $option->getValue());
                $optAttrs->setIfNotNull('data-nf-name', $option->getName());
                $optAttrs->setIfNotEmpty('*data-nf-group', $option->getGroups());
                $optAttrs->setIfNotNull('*data-nf-sidecar', $option->sidecar);
                $block->body .= $this->writeTag('option', $optAttrs) . "\n";
            }
            $block->body .= "</datalist>\n";
        }
        return $block;
    }

    protected function initialize()
    {
        // Reset the context
        $this->context = [
            'inCell' => false
        ];
        // Initialize custom settings
        $this->setShow('cellspacing:3');
        $this->setShow('hidden:nf-hidden');
        $this->setShow('layout:vertical');
    }

    protected function renderFieldSelectOption($option, $value)
    {
        $block = new Block();
        $attrs = new Attributes();
        $attrs->set('value', $option->getValue());
        $attrs->setIfNotNull('data-nf-name', $option->getName());
        $attrs->setIfNotEmpty('*data-nf-group', $option->getGroups());
        $attrs->setIfNotNull('*data-nf-sidecar', $option->getSidecar());
        if (in_array($attrs->get('value'), $value)) {
            $attrs->setFlag('selected');
        }
        $block->body .= $this->writeTag('option', $attrs, $option->getLabel()) . "\n";
        return $block;
    }

    protected function renderFieldSelectOptions($list, $value) {
        $block = new Block();
        foreach ($list as $option) {
            if ($option->isNested()) {
                $attrs = new Attributes();
                $attrs->set('label', $option->getLabel());
                $attrs->setIfNotNull('data-nf-name', $option->getName());
                $attrs->setIfNotEmpty('*data-nf-group', $option->getGroups());
                $attrs->setIfNotNull('*data-nf-sidecar', $option->getSidecar());
                $block->body .= $this->writeTag('optgroup', $attrs) . "\n";
                $block->merge($this->renderFieldSelectOptions($option->getList(), $value));
                $block->body .= '</optgroup>' . "\n";
            } else {
                $block->merge($this->renderFieldSelectOption($option, $value));
            }
        }
        return $block;
    }

    /**
     * Insert a raw HTML element into the form.
     * @param Binding $binding Binding for the Element containing the HTML.
     * @param type $options Options, if access is "hide" no output is generated
     * @return \Abivia\NextForm\Renderer\Block
     */
    protected function renderHtmlElement(Binding $binding, $options = [])
    {
//        $block = new Block();
//
//        // There's no way to hide this element so if all we have is hidden access, skip it.
//        if ($options['access'] !== 'hide') {
//            $block->body = $binding->getElement()->getValue();
//        }
//        return $block;
    }

    abstract protected function renderTriggers(FieldBinding $binding) : Block;

    public function setOptions($options = [])
    {

    }

}

