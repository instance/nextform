<?php

use Abivia\NextForm\Contracts\RenderInterface;
use Abivia\NextForm\Form\Binding\Binding;
use Abivia\NextForm\Render\Block;

/**
 *
 */
class LoggingRender implements RenderInterface {

    static protected $log = [];

    static public function getLog() {
        return self::$log;
    }

    /**
     * Pop the rendering context
     */
    public function popContext()
    {

    }

    /**
     * Push the rendering context
     */
    public function pushContext()
    {

    }

    /**
     * Generate form content for an element.
     * @param Binding $binding The element and data context to be rendered.
     * @param array $options
     *  $options = [
     *      'access' => (string) Access level. One of hide|none|view|write. Default is write.
     *  ]
     * @return Block The generated text and any dependencies, scripts, etc.
     */
    public function render(Binding $binding, $options = []) : Block
    {
        self::$log[$binding->getNameOnForm() . '.'
            . $binding->getElement()->getName()] = $options['access'];
        return new Block();
    }

    public function setOption($key, $value)
        : Abivia\NextForm\Contracts\RenderInterface
    {
        return $this;
    }

    /**
     * Set global options.
     * @param array $options Render-specific options.
     */
    public function setOptions($options = [])
    {

    }

    /**
     * Set parameters related to the appearance of the form.
     * @param string $settings
     */
    public function setShow($settings)
    {

    }

    public function showGet($scope, $key)
    {
        return '';
    }

    /**
     * Initiate a form.
     * @param array $options Render-specific options.
     */
    public function start($options = []) : Block
    {
        self::$log = [];
        return new Block();
    }

    /**
     * @inheritdoc
     */
    static public function stateData($state) : Block
    {
        return new Block();
    }

    static public function writeList($list = [], $options = []) : string
    {
        return '';
    }

}
