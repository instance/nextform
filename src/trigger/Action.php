<?php

namespace Abivia\NextForm\Trigger;

/**
 *
 */
class Action implements \JsonSerializable {
    use \Abivia\Configurable\Configurable;
    use \Abivia\NextForm\Traits\JsonEncoder;

    protected $change = [];
    static protected $changeValidation = [
        'enable', 'enabled', 'script', 'value', 'visible'
    ];
    static protected $jsonEncodeMethod = [
        'change' => ['scalarize'],
        'target' => ['drop:empty', 'drop:null', 'scalarize'],
        'value' => [],
    ];
    protected $target = [];
    protected $value;

    protected function configureValidate($property, &$value) {
        switch ($property) {
            case 'change':
                if (!is_array($value)) {
                    $value = [$value];
                }
                $result = true;
                foreach ($value as &$element) {
                    if (!($valid = in_array($element, self::$changeValidation))) {
                        $result = false;
                        $this -> configureLogError(
                            '"' . $element . '" is not a valid value for "' . $property . '".'
                        );
                    }
                    if ($element == 'enabled') {
                        $element = 'enable';
                    }
                }
                break;
            case 'target':
                if (!is_array($value)) {
                    $value = [$value];
                }
                $result = true;
                break;
            default:
                $result = true;
        }
        return $result;
    }

    public function getChange() {
        return $this -> change;
    }

    public function getTarget() {
        return $this -> target;
    }

    public function getValue() {
        return $this -> value;
    }

    public function setChange($value) {
        if (!$this -> configureValidate('change', $value)) {
            throw new \UnexpectedValueException(
                'Valid values for change are: ' . implode('|', self::$changeValidation)
            );
        }
        if ($value == 'enabled') {
            $value = 'enable';
        }
        $this -> change = $value;
        return $this;
    }

    public function setTarget($value) {
        if (!$this -> configureValidate('target', $value)) {
            // @codeCoverageIgnoreStart
            throw new \UnexpectedValueException('Invalid value for target.');
            // @codeCoverageIgnoreEnd
        }
        $this -> target = $value;
        return $this;
    }

    public function setValue($value) {
        if (!$this -> configureValidate('value', $value)) {
            // @codeCoverageIgnoreStart
            throw new \UnexpectedValueException('Invalid value.');
            // @codeCoverageIgnoreEnd
        }
        $this -> value = $value;
        return $this;
    }

}
