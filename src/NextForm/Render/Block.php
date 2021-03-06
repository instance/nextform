<?php

Namespace Abivia\NextForm\Render;

/**
 * Part of an output form that can enclose other blocks, and can contain elements of
 * a generated page, including header elements, scripts, style sheets, inline styles,
 * and inline script.
 */
class Block
{
    /**
     * Markup associated with an Element.
     * @var string
     */
    public $body = '';

    /**
     * Arbitrary application data.
     * @var array
     */
    public $context = [];

    /**
     * Page header data associated with an Element.
     * @var string
     */
    public $head = '';

    /**
     * List of linked files
     * @var array
     */
    public $linkedFiles = [];

    /**
     * Instructions on how properties are handled in a merge; missing
     * properties are ignored.
     * @var array
     */
    static protected $mergeRules = [
        'body' => 'append',
        'context' => 'data',
        'head' => 'append',
        'linkedFiles' => 'merge',
        'post' => 'prepend',
        'script' => 'append',
        'scriptFiles' => 'merge',
        'styles' => 'append',
    ];

    /**
     * On close completed event handler (pair with onCloseInit if needed).
     * @var callable
     */
    public $onCloseDone;

    /**
     * Markup that follows any nested elements.
     * @var string
     */
    public $post = '';

    /**
     * Executable script associated with an Element.
     * @var string
     */
    public $script = '';

    /**
     * List of script files to link
     * @var array
     */
    public $scriptFiles = [];

    /**
     * Inline styles
     * @var string
     */
    public $styles = '';

    /**
     * Combine the page body with any closing (post) text, execute any close handler.
     * @return $this
     */
    public function close()
    {
        $this->body .= $this->post;
        $this->post = '';
        if (is_callable($this->onCloseDone)) {
            call_user_func($this->onCloseDone, $this);
        }
        return $this;
    }

    /**
     * Create a basic block from strings.
     * @param type $body The opening/body of the block.
     * @param type $post Any closing text.
     * @return \Abivia\NextForm\Render\Block
     */
    static public function fromString($body = '', $post = '') : Block
    {
        $that = new Block();
        $that->body = $body;
        $that->post = $post;
        return $that;
    }

    /**
     * Merge the contents of another block into this block.
     * @param \Abivia\NextForm\Render\Block $block
     * @return $this
     */
    public function merge(Block $block)
    {
        foreach (self::$mergeRules as $prop => $operation)
        {
            switch ($operation) {
                case 'data':
                    // Recursive merge on application data.
                    $this->$prop = array_merge_recursive(
                        $this->$prop,
                        $block->$prop
                    );
                    break;

                case 'merge':
                    // Script and linked file lists are merged with no duplicates.
                    $this->$prop = array_unique(array_merge($this->$prop, $block->$prop));
                    break;

                case 'prepend':
                    // Merged block prefixes the existing data
                    $this->$prop = $block->$prop . $this->$prop;
                    break;

                case 'append':
                default:
                    // Merged block appended to the existing data
                    $this->$prop .= $block->$prop;
                    break;
            }
        }
        return $this;
    }

}
