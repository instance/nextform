<?php

namespace Abivia\NextForm\Element;

use Abivia\NextForm;
use Abivia\NextForm\Render\Block;
use Illuminate\Contracts\Translation\Translator as Translator;

/**
 *
 */
abstract class Element implements \JsonSerializable {
    use \Abivia\Configurable\Configurable;
    use \Abivia\NextForm\Traits\JsonEncoder;

    /**
     * System-assigned element ID
     * @var string
     */
    protected $autoId;
    protected $enabled = true;
    /**
     * The form this element belongs to
     * @var \Abivia\NextForm
     */
    protected $form;
    protected $group;
    /**
     * User-specified element id, overrides auto ID
     * @var string
     */
    protected $id = '';
    static protected $jsonEncodeMethod = [
        'type' => [],
        'name' => ['drop:blank'],
        'id' => ['drop:blank'],
        'group' => ['drop:null', 'map:memberOf'],
        'enabled' => ['drop:true'],
        'readonly' => ['drop:false', 'drop:null'],
        'visible' => ['drop:true']
    ];
    protected $name = '';
    protected $readonly;
    protected $type;
    protected $visible = true;

    public function __construct() {

    }

    /**
     * Connect data elements in a schema
     * @param \Abivia\NextForm\Data\Schema $schema
     * @codeCoverageIgnore
     */
    public function bindSchema(\Abivia\NextForm\Data\Schema $schema) {
        // Non-data elements do nothing. This just simplifies walking the tree
    }

    static public function classFromType($obj) {
        $result = 'Abivia\NextForm\Element\\' . ucfirst(strtolower($obj -> type)) . 'Element';
        if (!class_exists($result)) {
            throw new \InvalidArgumentException($obj -> type . ' is not a valid element type.');
        }
        return $result;
    }

    protected function configureComplete() {
        return true;
    }

    protected function configureInitialize() {
        if (isset($this -> configureOptions['_form'])) {
            $this -> form = $this -> configureOptions['_form'];
            $this -> form -> registerElement($this);
        }
    }

    protected function configurePropertyIgnore($property) {
        return $property == 'type';
    }

    protected function configurePropertyMap($property) {
        if ($property == 'memberOf') {
            $property = 'group';
        }
        return $property;
    }

    public function generate($renderer, $access, Translator $translate) {
        $this -> translate($translate);
        //$readOnly = false; // $access -> hasAccess(...)
        $options = ['access' => 'write'];
        $pageData = $renderer -> render($this, $options);
        return $pageData;
    }

    public function getEnabled() {
        return $this -> enabled;
    }

    public function getGroup() {
        return $this -> group;
    }

    public function getId() {
        if ($this -> id != '') {
            return $this -> id;
        }
        if ($this -> autoId == '') {
            $this -> autoId = NextForm::htmlIdentifier($this -> type, true);
        }
        return $this -> autoId;
    }

    public function getName() {
        return $this -> name;
    }

    public function getReadonly() {
        return $this -> readonly;
    }

    public function getType() {
        return $this -> type;
    }

    public function getVisible() {
        return $this -> visible;
    }

    public function setEnabled($enabled) {
        $this -> enabled = $enabled;
        return $this;
    }

    public function setGroup($group) {
        $this -> group = $group;
        return $this;
    }

    public function setId($id) {
        $this -> id = $id;
        return $this;
    }

    public function setName($name) {
        $this -> name = $name;
        return $this;
    }

    public function setReadonly($readonly) {
        $this -> readonly = $readonly;
        return $this;
    }

    public function setVisible($visible) {
        $this -> visible = $visible;
        return $this;
    }

    /**
     * Translate -- this method probably should be abstract...
     * @param Translator $translate
     * @codeCoverageIgnore
     */
    public function translate(Translator $translate) {

    }

}