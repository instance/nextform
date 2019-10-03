<?php

namespace Abivia\NextForm\Contracts;

use Abivia\NextForm\Renderer\Block;
use Abivia\NextForm\Form\Element\Element;


/**
 * Interface for a page renderer.
 * @codeCoverageIgnore
 */
interface RendererInterface
{

    /**
     * Pop the rendering context
     */
    public function popContext();

    /**
     * Pop the rendering context
     */
    public function pushContext();

    /**
     * Generate form content for an element.
     * @param Element $element The element to be rendered.
     * @param array $options
     *  $options = [
     *      'access' => (string) Access level. One of hide|none|view|write. Default is write.
     *  ]
     * @return Block The generated text and any dependencies, scripts, etc.
     */
    public function render(Element $element, $options = []) : Block;

    /**
     * Set global options.
     * @param array $options Render-specific options.
     */
    public function setOptions($options = []);

    /**
     * Set parameters related to the appearance of the form.
     * @param string $settings
     */
    public function setShow($settings);

    /**
     * Initiate a form.
     * @param array $options Render-specific options.
     */
    public function start($options = []) : Block;

}
