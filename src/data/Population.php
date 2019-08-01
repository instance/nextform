<?php

namespace Abivia\NextForm\Data;

/**
 * Describes how a data object is displayed on a form.
 */
class Population implements \JsonSerializable {
    use \Abivia\Configurable\Configurable;
    use \Abivia\NextForm\Traits\JsonEncoder;

    static protected $jsonEncodeMethod = [
        'source' => [],
        'parameters' => ['drop:empty','drop:null'],
        'query' => ['drop:blank','drop:null'],
        'translate' => ['drop:true'],
        'list' => [],
    ];
    static protected $knownSources = [
        'fixed', 'local', 'remote', 'static',
    ];
    protected $list;
    protected $parameters;
    protected $query;
    protected $source;
    protected $translate = true;

    /**
     * Map a property to a class.
     * @param string $property The current class property name.
     * @param mixed $value The value to be stored in the property, made available for inspection.
     * @return mixed An object containing a class name and key, or false
     * @codeCoverageIgnore
     */
    protected function configureClassMap($property, $value) {
        static $classMap = [
            'list' => ['className' => '\Abivia\NextForm\Data\Population\Option'], //'key' => '', 'keyIsMethod' => true],
        ];
        if (isset($classMap[$property])) {
            return (object) $classMap[$property];
        }
        return false;
    }

    protected function configureInitialize(&$config, &$options) {
        // if the list is an array of strings, convert it
        if (isset($config -> list) && is_array($config -> list)) {
            foreach ($config -> list as &$value) {
                if (is_string($value)) {
                    // Convert to a useful class
                    $obj = new \Stdclass;
                    $obj -> label = $value;
                    $value = $obj;
                }
            }
        }
    }

    protected function configureValidate($property, &$value) {
        switch ($property) {
            case 'source':
                $result = in_array($value, self::$knownSources);
                break;
            default:
                $result = true;
        }
        return $result;
    }

    public function getList() {
        if ($this -> list === null) {
            return [];
        }
        return $this -> list;
    }

    public function getQuery() {
        return $this -> query;
    }

    public function getSource() {
        return $this -> source;
    }

    public function getTranslate() {
        return $this -> translate;
    }

    public function setSource($source) {
        if (!$this -> configureValidate('source', $source)) {
            throw new \LogicException('Invalid value for source: ' . $source);
        }
        return $this;
    }

}
