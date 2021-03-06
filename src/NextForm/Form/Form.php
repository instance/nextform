<?php

namespace Abivia\NextForm\Form;

use Abivia\Configurable\Configurable;
use Abivia\NextForm\Contracts\FormInterface;
use Abivia\NextForm\Form\Element\Element;
use Abivia\NextForm\Form\Element\FieldElement;
use Abivia\NextForm\NextForm;
use Abivia\NextForm\Traits\JsonEncoderTrait;
use Abivia\NextForm\Traits\ShowableTrait;

/**
 *
 */
class Form implements FormInterface, \JsonSerializable
{
    use Configurable;
    use JsonEncoderTrait;
    use ShowableTrait;

    /**
     * A list of top level elements on the form.
     * @var Element[]
     */
    protected $elements;

    static protected $jsonEncodeMethod = [
        'name' => ['drop:blank', 'drop:null'],
        'useSegment' => ['drop:blank'],
        'show' => ['drop:blank'],
        'elements' => [],
    ];

    protected $name;

    protected $show = '';

    protected $useSegment = '';

    public function __construct()
    {
        $this->initialize();
    }

    protected function configureClassMap($property, $value)
    {
        $result = false;
        if ($property == 'elements') {
            $result = new \stdClass;
            $result->key = '';
            $result->className = [Element::class, 'classFromType'];
        }
        return $result;
    }

    /**
     * Sets up options and converts string-valued elements into field objects.
     * @param \stdClass $config
     */
    protected function configureInitialize(&$config, ...$context)
    {
        // Pass an instance of the form down in Configurable's options so we can
        // access the form directly from deep within the data structures.
        $this->configureOptions['_form'] = &$this;

        // Any elements that are simply strings are converted to basic field objects
        if (isset($config->elements) && is_array($config->elements)) {
            foreach ($config->elements as &$value) {
                if (is_string($value)) {
                    $value = self::expandString($value);
                }
            }
        }
    }

    /**
     * Convert a string of the form property:group:group to a configurable stdClass.
     *
     * @param string $value
     * @return \stdClass
     */
    static public function expandString(string $value)
    {
        $groupParts = explode(NextForm::GROUP_DELIM, $value);
        // Convert to a useful class
        $obj = new \stdClass;
        $obj->type = 'field';
        $obj->object = array_shift($groupParts);
        if (!empty($groupParts)) {
            $obj->memberOf = $groupParts;
        }
        return $obj;
    }

    /**
     * Generate a form object from a file
     * @param string $formFile
     * @return \Abivia\NextForm\Form
     */
    static public function fromFile($formFile) : ?FormInterface
    {
        $form = new Form();
        return $form->loadFile($formFile);
    }

    /**
     * Generate a form object from a JSON string.
     *
     * @param string $json
     * @return \Abivia\NextForm\Form
     * @throws RuntimeException
     */
    static public function fromJson($json)
    {
        $form = new Form();
        return $form->loadJson($json);
    }

    /**
     * Get a list of top level elements in the form.
     * @return Element[]
     */
    public function getElements() {
        return $this->elements;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSegment()
    {
        return $this->useSegment;
    }

    /**
     * Initialize the object.
     */
    protected function initialize() {
        $this->elements = null;
        $this->name = null;
        $this->show = '';
        $this->useSegment = '';
    }

    /**
     * Load form definition from file.
     *
     * @param string $formFile Path to the form definition.
     * @return $this
     * @throws \RuntimeException
     */
    public function loadFile($formFile)
    {
        $json = \file_get_contents($formFile);
        if ($json === false) {
            throw new \RuntimeException(
                'Unable to read ' . $formFile . "\n"
            );
        }
        try {
            $this->loadJson($json);
        } catch (\RuntimeException $e) {
            throw new \RuntimeException(
                $e->getMessage() . ' While reading ' . $formFile . "\n"
            );
        }

        return $this;
    }

    /**
     * Load form definition from JSON string.
     *
     * @param string A JSON string.
     * @return $this
     * @throws \RuntimeException
     */
    public function loadJson($json)
    {
        $this-> initialize();
        if (!$this->configure(\json_decode($json), true)) {
            throw new \RuntimeException(
                'Failed to load JSON' . "\n"
                . \implode("\n", $this->configureErrors)
            );
        }
        return $this;
    }

    protected function options($options)
    {
    }

}
