<?php

namespace Abivia;

use Abivia\NextForm\Contracts\AccessInterface;
use Abivia\NextForm\Contracts\RendererInterface as RendererInterface;
use Abivia\NextForm\Data\Schema;
use Abivia\NextForm\Form\Binding\Binding;
use Abivia\NextForm\Form\Element\ContainerElement;
use Abivia\NextForm\Form\Element\Element;
use Abivia\NextForm\Form\Element\FieldElement;
use Abivia\NextForm\Form\Form;
use Abivia\NextForm\Renderer\Attributes;
use Abivia\NextForm\Renderer\Block;
use Illuminate\Contracts\Translation\Translator as Translator;

/**
 *
 */
class NextForm
{

    public const GROUP_DELIM = ':';
    public const SEGMENT_DELIM = '/';

    /**
     * The access controller
     * @var AccessInterface
     */
    protected $access;

    /**
     * A list of all bindings.
     * @var Binding[]
     */
    protected $bindings = [];

    /**
     * A list of top level elements on the form.
     * @var Element[]
     */
    protected $elements;

    /**
     * The form definition.
     * @var Form
     */
    protected $form;

    /**
     * Counter used to assign HTML identifiers
     * @var int
     */
    static protected $htmlId = 0;

    protected $id;
    protected $name;
    /**
     * Maps form names to form elements/
     * @var array
     */
    protected $nameMap;
    /**
     * Maps schema objects to form elements/
     * @var array
     */
    protected $objectMap;
    protected $renderer;
    protected $schema;
    protected $translate;
    protected $useSegment = '';

    public function __construct()
    {
        $this->access = new \Abivia\NextForm\Access\BasicAccess;
        $this->show = '';
    }

    protected function assignNames()
    {
        $this->nameMap = [];
        $containerCount = 0;
        foreach ($this->allElements as $element) {
            if ($element instanceof FieldElement) {
                $baseName = str_replace('/', '_', $element->getObject());
                $name = $baseName;
                $confirmName = $baseName . '_confirm';
                $append = 0;
                while (isset($this->nameMap[$name]) || isset($this->nameMap[$confirmName])) {
                    $name = $baseName . '_' . ++$append;
                    $confirmName = $name . '_' . $append . '_confirm';
                }
                $this->nameMap[$name] = $element;
                $element->setFormName($name);
            } elseif ($element instanceof ContainerElement) {
                $baseName = 'container_';
                $name = $baseName;
                while (isset($this->nameMap[$name])) {
                    $name = $baseName . ++$containerCount;
                }
                $this->nameMap[$name] = $element;
                $element->setFormName($name);
            }
        }
        $this->schemaIsLinked = true;
        return $this;
    }

    /**
     * Connect all the components into something we can generate
     * @return $this
     */
    protected function bind()
    {
        $this->objectMap = [];
        $this->bindings = [];
        foreach ($this->form->getElements() as $element) {
            $binding = Binding::fromElement($element);
            $binding->setForm($this);
            $binding->bindSchema($this->schema);
            $this->bindings[] = $binding;
        }
        return $this;
    }

    /**
     * Reset the static context
     */
    static public function boot()
    {
        self::$htmlId = 0;
    }

    /**
     * Generate a form.
     * @param array $options Generation options, optional unless stated otherwise:
     *  $options = [
     *      'attrs' => (Renderer\Attributes) Attributes to be added to the form element.
     *      'token' => (string) Form submission token. If provided and empty, no token is used.
     *      'tokenName' => (string) Name/ID to use for the token. Default is "nf_token".
     *  ]
     * @return Block
     */
    public function generate($options)
    {
        $this->options($options);
        $this->bind();
        if (!isset($options['attrs'])) {
            $options['attrs'] = new Attributes();
        }

        // Set the name and ID if not passed in to us.
        $options['attrs']->default('id', $this->id);
        $options['attrs']->default('name', $this->name);

        // Assign field names
        $this->assignNames();
        $this->renderer->setShow($this->show);

        // Start the form, write all the elements, close the form, return.
        $pageData = $this->renderer->start($options);
        foreach ($this->bindings as $binding) {
            $pageData->merge($binding->generate($this->renderer, $this->access));
        }
        $pageData->close();
        return $pageData;
    }

    /**
     * Get all the data objects from the form.
     * @return array Data elements indexed by object name
     */
    public function getData()
    {
        $data = [];
        // The first element should have the value... there should only be one value.
        foreach ($this->objectMap as $objectName => $list) {
            $data[$objectName] = $list[0]->getValue();
        }
        return $data;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSegment()
    {
        return $this->form->getSegment();
    }

    /**
     * Get all the data objects in the specified segment from the form.
     * @param type $segment
     * @return array Data elements indexed by object name
     */
    public function getSegmentData($segment)
    {
        $prefix = $segment . NextForm::SEGMENT_DELIM;
        $prefixLen = strlen($segment . NextForm::SEGMENT_DELIM);
        $data = [];
        // The first element should have the value... there should only be one value.
        foreach ($this->objectMap as $objectName => $list) {
            if (substr($objectName, 0, $prefixLen) == $prefix) {
                $data[substr($objectName, $prefixLen)] = $list[0]->getValue();
            }
        }
        return $data;
    }

    /**
     * Turn a string into a valid HTML identifier, or make one up
     * @param string $name
     * @return string
     */
    static public function htmlIdentifier($name = '', $appendId = false)
    {
        if ($name == '') {
            $name = 'nf-' . ++self::$htmlId;
        } else {
            if ($appendId) {
                $name .= '-' . ++self::$htmlId;
            }
            $name = preg_replace('/[^a-z0-9\-]/i', '-', $name);
            $name = preg_replace('/^[^a-z0-9\-]/i', 'nf-\1', $name);
        }
        return $name;
    }

    protected function options($options)
    {
        if (isset($options['name'])) {
            $this->name = $options['name'];
        }
        if (isset($options['id'])) {
            $this->id = $options['id'];
        } elseif ($this->name != '') {
            $this->id = self::htmlIdentifier($this->name);
        } else {
            $this->id = 'form' . ++self::$htmlId;
        }
    }

    /**
     * Populate form elements.
     * @param array $data Values indexed by schema object ID.
     * @param string $segment Optional segment prefix.
     * @throws LogicException
     * @return $this
     */
    public function populate($data, $segment = '')
    {
        if (!$this->schemaIsLinked) {
            throw new LogicException('Form not linked to schema.');
        }
        foreach ($data as $field => $value) {
            if ($segment !== '') {
                $field = $segment . NextForm::SEGMENT_DELIM . $field;
            }
            if (!isset($this->objectMap[$field])) {
                continue;
            }
            foreach ($this->objectMap[$field] as $element) {
                $element->setValue($value);
            }
        }
        return $this;
    }

    /**
     * Add a binding to the object map.
     * @param Binding $binding
     * @return $this
     */
    public function registerBinding(Binding $binding)
    {
        $objectRef = $binding->getObject();
        if (!isset($this->objectMap[$objectRef])) {
            $this->objectMap[$objectRef] = [];
        }
        $this->objectMap[$objectRef][] = $binding;
        return $this;
    }

    /**
     * Add a form element to the list of all elements.
     * @param Element $element
     * @return $this
     */
    public function registerElement($element)
    {
        if (!in_array($element, $this->allElements)) {
            $this->allElements[] = $element;
        }
        return $this;
    }

    public function setAccess(AccessInterface $access)
    {
        $this->access = $access;
    }

    public function setForm(Form $form)
    {
        $this->form = $form;
    }

    public function setRenderer(RendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    public function setSchema(Schema $schema)
    {
        $this->schema = $schema;
    }

    public function setTranslator(Translator $translate)
    {
        $this->translate = $translate;
    }

    public function setUser($user)
    {
        $this->access->setUser($user);
    }

}
