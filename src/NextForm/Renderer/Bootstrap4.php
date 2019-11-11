<?php
namespace Abivia\NextForm\Renderer;

use Abivia\NextForm\Contracts\RendererInterface;
use Abivia\NextForm\Data\Labels;
use Abivia\NextForm\Form\Binding\Binding;
use Abivia\NextForm\Form\Binding\ContainerBinding;
use Abivia\NextForm\Form\Binding\FieldBinding;
use Abivia\NextForm\Form\Binding\SimpleBinding;

/**
 * Renderer for Bootstrap4
 */
class Bootstrap4 extends CommonHtml implements RendererInterface
{

    static protected $buttonSizeClasses = ['large' => ' btn-lg', 'regular' => '', 'small' => ' btn-sm'];

    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->initialize();
        $this->setOptions($options);
    }

    /**
     * Use current show settings to build the button class
     * @return string
     */
    public function getButtonClass($scope = 'button') : string
    {
        $buttonClass = 'btn btn'
            . ($this->showGet($scope, 'fill') === 'outline' ? '-outline' : '')
            . '-' . $this->showGet($scope, 'purpose')
            . self::$buttonSizeClasses[$this->showGet($scope, 'size')];
        return $buttonClass;
    }

    /**
     * Set up to start generating output.
     */
    protected function initialize()
    {
        parent::initialize();
        // Reset the context
        $this->context = [
            'inCell' => false
        ];
        // Initialize custom settings
        $this->setShow('layout:vertical');
        $this->setShow('purpose:primary');
    }

    /**
     * Write a "standard" input element; if there are before/after labels, generate a group.
     * @param Labels $labels
     * @param Attributes $attrs
     * @return \Abivia\NextForm\Renderer\Block
     */
    public function inputGroup(Labels $labels, Attributes $attrs)
    {
        // Generate the actual input element, with labels if provided.
        $input = $this->inputGroupPre($labels);
        // Generate the input element
        $input->body .= $this->writeTag('input', $attrs) . "\n";

        $input->merge($this->inputGroupPost($labels));

        // If there's help text we need to generate a break.
        if ($labels->has('help')) {
            $input->body .= '<span class="w-100"></span>' . "\n";
        }
        return $input;
    }

    public function inputGroupPost(Labels $labels)
    {
        if ($labels->has('after')) {
            // Write an append group for the after label
            $group = $this->writeElement(
                'div', ['attributes' => new Attributes('class', ['input-group-append'])]
            );
            // Write the after label in the append group
            $group->body .= $this->writeLabel(
                'inputAfter', $labels->after, 'span',
                new Attributes('class', ['input-group-text'])
            ) . "\n";
            $group->close();
        } else {
            $group = new Block();
        }

        return $group;
    }

    public function inputGroupPre(Labels $labels)
    {
        if ($labels->has('before') || $labels->has('after')) {
            // We have before/after elements to attach, we need to create
            // an input group.
            $input = $this->writeElement(
                'div', ['attributes' => new Attributes('class', 'input-group'), 'show' => 'inputWrapperAttributes']
            );

            if ($labels->has('before')) {
                // Write a prepend group for the before label
                $group = $this->writeElement(
                    'div', ['attributes' => new Attributes('class', ['input-group-prepend'])]
                );
                // Write the before label in the prepend group
                $group->body .= $this->writeLabel(
                    'inputBefore', $labels->before, 'span',
                    new Attributes('class', ['input-group-text'])
                ) . "\n";
                $group->close();
                $input->merge($group);
            }
        } else {
            $input = $this->writeElement(
                'div', ['show' => 'inputWrapperAttributes']
            );
        }

        return $input;
    }

    protected function renderFieldSelect(FieldBinding $binding, $options = [])
    {
        $value = $binding->getValue();
        if ($options['access'] === 'hide') {

            // Hidden: generate one or more hidden input elements
            $block = $this->elementHidden($binding, $value);
            return $block;
        }

        // Push and update the show context
        $element = $binding->getElement();
        $show = $element->getShow();
        if ($show) {
            $this->pushContext();
            $this->setShow($show, 'select');
        }

        // This element is displayed
        $block = new Block();

        // Create a form group.
        $block = $this->writeElement(
            'div', [
                'attributes' => $this->groupAttributes($binding),
                'show' => 'formGroupAttributes'
            ]
        );

        $labels = $binding->getLabels(true);
        $data = $binding->getDataProperty();

        // Link the label if we're not in view mode
        $fieldName = $binding->getFormName();
        $headAttr = new Attributes();
        if ($options['access'] != 'view') {
            $headAttr->set('for', $fieldName);
        }

        $block->body .= $this->writeLabel(
            'headingAttributes', $labels->heading, 'label', $headAttr, ['break' => true]
        );

        // Horizontal layouts generate a div for the input column
        $block->merge($this->writeElement('div', ['show' => 'inputWrapperAttributes']));

        // Text preceeding the select
        $block->body .= $this->writeLabel(
            'before', $labels->before, 'div', null, ['break' => true]
        );
        if ($options['access'] == 'view') {
            // In view mode we just generate a list of currently selected values as text
            $block->merge($this->renderFieldSelectView($binding));
        } else {
            // Generate an actual select!
            $attrs = new Attributes();
            $attrs->set('name', $fieldName);

            $attrs->set('id', $binding->getId());
            $attrs->setIfNotNull('size', $data->getPresentation()->getRows());
            $attrs->addValidation('select', $data->getValidation());
            if ($this->showGet('select', 'appearance') === 'custom') {
                $attrs->set('class', 'custom-select');
            } else {
                $attrs->set('class', 'form-control');
            }

            $select = $this->writeElement('select', ['attributes' => $attrs]);

            // Add the options
            // If there's no value set, see if there's a default
            if ($value === null) {
                $value = $element->getDefault();
            }
            if (!is_array($value)) {
                $value = [$value];
            }
            $select->merge(
                $this->renderFieldSelectOptions($binding->getList(true), $value)
            );
            $select->close();
            $block->merge($select);
        }
        $block->body .= $this->writeLabel(
            'after', $labels->after, 'div', null, ['break' => true]
        );
        $block->close();

        // Restore show context and return.
        if ($show) {
            $this->popContext();
        }

        return $block;
    }

    protected function renderFieldSelectView($binding)
    {
        $baseId = $binding->getId();
        $data = $binding->getDataProperty();
        $multiple = $data->getValidation()->get('multiple');

        $attrs = new Attributes();
        $attrs->set('name', $binding->getFormName());

        $list = $binding->getFlatList(true);
        // render as hidden with text
        $attrs->set('type', 'hidden');

        $value = $binding->getValue();
        $block = new Block();
        if ($multiple) {
            // step through each possible value, output matches
            if (!is_array($value)) {
                $value = [$value];
            }
            $optId = 0;
            foreach ($list as $option) {
                $slot = array_search($option->getValue(), $value);
                if ($slot !== false) {
                    $id = $baseId . '_opt' . $optId;
                    $attrs->set('id', $id);
                    $attrs->set('value', $value[$slot]);
                    $block->body .= $this->writeTag('input', $attrs) . "\n";
                    $block->body .= $this->writeTag('span', [], $option->getLabel())
                        . "<br/>\n";
                    ++$optId;
                }
            }
        } else {
            $attrs->set('id', $baseId);
            $attrs->set('value', $value);
            $block->body .= $this->writeTag('input', $attrs) . "\n";
            foreach ($list as $option) {
                if ($value == $option->getValue()) {
                    $block->body .= $this->writeTag('span')
                        . $option->getLabel() . '</span>'
                        . "\n";
                }
            }
        }
        return $block;
    }

    protected function renderStaticElement_deprecated(SimpleBinding $binding, $options = [])
    {
        // There's no way to hide this element so if all we have is hidden access, skip it.
        if ($options['access'] === 'hide') {
            return new Block();
        }

        // Push and update the show context
        $element = $binding->getElement();
        $show = $element->getShow();
        if ($show !== '') {
            $this->pushContext();
            $this->setShow($show, 'html');
        }

        // We can see or change the data. Create a form group.
        $block = $this->writeElement(
            'div', [
                'attributes' => $this->groupAttributes($binding),
                'show' => 'formGroupAttributes'
            ]
        );

        // Write a heading if there is one
        $labels = $binding->getLabels(true);
        $block->body .= $this->writeLabel(
            'headingAttributes',
            $labels ? $labels->heading : null,
            'div', null, ['break' => true]
        );
        $block->merge($this->writeElement('div', ['show' => 'inputWrapperAttributes']));

        $attrs = new Attributes('id', $binding->getId());
        $block->merge($this->writeElement('div', ['attributes' => $attrs]));
        // Escape the value if it's not listed as HTML
        $value = $binding->getValue() . "\n";
        $block->body .= $element->getHtml() ? $value : htmlspecialchars($value);
        $block->close();

        // Restore show context and return.
        if ($show !== '') {
            $this->popContext();
        }

        return $block;
    }

    /**
     * This method should be reworked to support different JS frameworks...
     * @param FieldBinding $binding
     * @return \Abivia\NextForm\Renderer\Block
     */
    public function renderTriggers(FieldBinding $binding) : Block
    {
        $result = new Block;
        $triggers = $binding->getElement()->getTriggers();
        if (empty($triggers)) {
            return $result;
        }
        $formId = $binding->getManager()->getId();
        $script = "$('#" . $formId . " [name^=\"" . $binding->getFormName(true)
            . "\"]').change(function () {\n";
        foreach ($triggers as $trigger) {
            if ($trigger->getEvent() !== 'change') {
                continue;
            }
            $value = $trigger->getValue();
            $closing = "  }\n";
            if (is_array($value)) {
                $script .= "  if (" . json_encode($value) . ".includes(this.value)) {\n";
            } elseif ($value === null) {
                // Null implies no conditions.
                $closing = '';
            } else {
                $script .= "  if (this.value === " . json_encode($value) . ") {\n";
            }
            foreach ($trigger->getActions() as $action) {
                $script .= $this->renderAction($formId, $binding, $action);
            }
            $script .= $closing;
        }
        $script .= "});\n";
        $result->script = $script;

        return $result;
    }

    protected function renderAction($formId, $binding, $action)
    {
        $script = '';
        switch ($action->getSubject()) {
            case 'checked':
                $value = $action->getValue();
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } elseif ($value === 'checked') {
                    $value = 'true';
                } elseif ($value === 'unchecked') {
                    $value = 'false';
                }
                foreach ($action->getTarget() as $target) {
                    if (preg_match('/{(.*)}/', $target, $match)) {
                        $target = $match[1];
                        $script .= "    " . $formId . ".checkGroup("
                            . "'" . $target . "', " . $value . ");\n";
                    } elseif ($target[0] === '#') {
                        $script .= "    " . $formId . ".check("
                            . "$('" . $target . "'), " . $value . ");\n";
                    } elseif ($target[0] === '&') {
                        $script .= "    " . $formId . ".check("
                            . "$('#" . $formId . " [data-nf-name^=\"" . $target . "\"]'),"
                            . " " . $value . ");\n";
                    } else {
                        $script .= "    " . $formId . ".check("
                            . "$('" . $target . "'),"
                            . " " . $value . ");\n";
                    }
                }
                break;

            case 'display':
                $value = $action->getValue();
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } elseif ($value === 'checked') {
                    $value = 'this.checked';
                } elseif ($value === 'unchecked') {
                    $value = '!this.checked';
                }
                foreach ($action->getTarget() as $target) {
                    if (preg_match('/{(.*)}/', $target, $match)) {
                        $target = $match[1];
                        $script .= "    " . $formId .
                            ".displayGroup('" . $target . "', " . $value . ");\n";
                    } elseif ($target[0] === '#') {
                        $script .= "    $('" . $target . "').toggle(" . $value . ");\n";
                    } elseif ($target[0] === '&') {
                        $script .= "    $('#" . $formId . " [data-nf-name^=\"" . $target . "\"]')"
                            . ".toggle(" . $value . ");\n";
                    } else {
                        $script .= "    " . $formId
                            . ".displayContainer('" . $target . "',"
                            . " " . $value . ");\n";
                    }
                }
                break;

            case 'enable':
                $value = $action->getValue() ? 'false' : 'true';
                foreach ($action->getTarget() as $target) {
                    if (preg_match('/{(.*)}/', $target, $match)) {
                        $target = $match[1];
                        $script .= "    " . $formId .
                            ".disableGroup('" . $target . "', "
                            . $value . ");\n";
                    } elseif ($target[0] === '#') {
                        $script .= "    $('" . $target . "').prop('disabled', "
                            . $value . ");\n";
                    } elseif ($target[0] === '&') {
                        $script .= "    $('#" . $formId . " [data-nf-name^=\"" . $target . "\"]')"
                            . ".prop('disabled', " . $value . ");\n";
                    } else {
                        $script .= "    " . $formId
                            . ".disableContainer('" . $target . "',"
                            . " " . $value . ");\n";
                    }
                }
                break;

            case 'script':
                $script .= "  " . $action->getValue() . "\n";
                break;
        }
        return $script;
    }

    /**
     * Process cell spacing options, called from show().
     *
     * @param string $scope Names the settings scope/element this applies to.
     * @param string $choice Primary option selection
     * @param array $values Array of colon-delimited settings including the initial keyword.
     */
    public function showDoCellspacing($scope, $choice, $values = [])
    {
        // Expecting choice to be "a" or "b".
        // For "a", one or more space delimited single digits from 0 to 5,
        // optionally prefixed with rr-
        //
        // For "b" one or more space-delimited sets of [rr-]xx-n where ss is a
        // renderer selector (bs for Bootstrap), xx is a size specifier,
        // and n is 0 to 5.
        //
        // Specifiers other than bs are ignored the result is a list of
        // classes to be used when spacing between the second and subsequent
        // elements in a cell.

        $classList = [];
        if ($choice == 'a') {
            foreach ($values as $value) {
                \preg_match(
                    '/(?<prefix>[a-z][a-z0-9]-)?(?<size>sm|md|lg|xl)\-(?<weight>[0-5])/',
                    $value, $match
                );
                if ($match['prefix'] !== '' && $match['prefix'] !== 'bs-') {
                    continue;
                }
                $classList[] = 'ml-' . $match['size'] . '-' . $match['weight'];
            }
        } else {
            foreach ($values as $value) {
                \preg_match(
                    '/(?<prefix>[a-z][a-z0-9]-)?(?<weight>[0-5])/',
                    $value, $match
                );
                if ($match['prefix'] !== '' && $match['prefix'] !== 'bs-') {
                    continue;
                }
                $classList[] = 'ml-' . $match['weight'];
            }
        }
        if (!empty($classList)) {
            $this->showState[$scope]['cellspacing']
                = new Attributes('class', $classList);
        }
    }

    /**
     * Process layout options, called from show()
     * @param string $scope Names the settings scope/element this applies to.
     * @param string $choice Primary option selection
     * @param array $values Array of colon-delimited settings including the initial keyword.
     */
    public function showDoLayout($scope, $choice, $values = [])
    {
        //
        // Structure of the layout elements
        // formGroupAttributes - An Attributes object associated with the element acting as a form group
        // headingAttributes - Set in horizontal layouts to set heading widths
        // inputWrapperAttributes - Set in horizontal layouts for giving an input element width
        //
        if (!isset($this->showState[$scope])) {
            $this->showState[$scope] = [];
        }
        $this->showState[$scope]['layout'] = $choice;
        if ($scope === 'form') {

            // Reset key settings
            unset($this->showState['form']['inputWrapperAttributes']);
            unset($this->showState['form']['headingAttributes']);

            // When before text is in a span, it has a right margin
            $this->showState['form']['beforespan'] = new Attributes('class', ['mr-1']);

            // A cell element will appear as a row
            $this->showState['form']['cellElementAttributes'] = new Attributes('class', ['form-row']);

            // Group wrapper encloses the complete output for a field, including labels
            $this->showState['form']['formGroupAttributes'] = new Attributes('class', ['form-group']);
            if ($choice === 'horizontal') {
                $this->showDoLayoutAnyHorizontal($scope, $values);
            } elseif ($choice === 'vertical') {
                $this->showDoLayoutAnyVertical($scope, $values);
            }
        }
    }

    /**
     * Process horizontal layout settings for any scope
     * @param string $scope Names the settings scope/element this applies to.
     * @param array $values Array of colon-delimited settings including the initial keyword.
     * @throws \RuntimeException
     */
    public function showDoLayoutAnyHorizontal($scope, $values)
    {
        // possible values for arguments:
        // h            - We get to decide
        // h:nxx        - Ignored, we decide
        // h:nxx/mxx    - Ignored, we decide
        // h:n:m:t      - ratio of headers to inputs over space t. If no t, t=n+m
        // h:.c1        - Ignored, we decide
        // h:.c1:.c2    - Class for headers / input elements
        //
        // Adjusts:
        // cellElementAttributes - Add the input column class
        // formGroupAttributes - add the row class
        //
        // Creates an attribute set for:
        // headingAttributes -- to be used for input element headings
        //
        $apply = &$this->showState[$scope];
        $default = true;
        $apply['formGroupAttributes']->itemAppend('class', 'row');
        if (count($values) >= 3) {
            if ($values[1][0] == '.') {
                // Dual class specification
                $apply['headingAttributes'] = new Attributes(
                    'class', [substr($values[1], 1), 'col-form-label']
                );
                $col2 = substr($values[2], 1);
                $apply['inputWrapperAttributes'] = new Attributes(
                    'class', $col2
                );
                $apply['cellElementAttributes']->itemAppend('class', $col2);
                $default = false;
            } elseif (preg_match('/^[+\-]?[0-9](\.[0-9]*)?$/', $values[1])) {
                // ratio
                $part1 = (float) $values[1];
                $part2 = (float) $values[2];
                if (!$part1 || !$part2) {
                    throw new \RuntimeException(
                        'Invalid ratio: ' . $values[1] . ':' . $values[2]
                    );
                }
                $sum = isset($values[3]) ? $values[3] : ($part1 + $part2);
                $factor = 12.0 / $sum;
                $total = round($factor * ($part1 + $part2));
                // Ensure columns are nonzero
                $width1 = ((int) round($factor * $part1)) ?: 1;
                $col1 = 'col-sm-' . $width1;
                $col2 = 'col-sm-' . (int) ($total - $width1 > 0 ? $total - $width1 : 1);
                $apply['headingAttributes'] = new Attributes(
                    'class',[$col1, 'col-form-label']
                );
                $apply['inputWrapperAttributes'] = new Attributes(
                    'class', [$col2]
                );
                $apply['cellElementAttributes']->itemAppend('class', $col2);
                $default = false;
            }
        }
        if ($default) {
            $apply['headingAttributes'] = new Attributes('class', ['col-sm-2', 'col-form-label']);
            $apply['inputWrapperAttributes'] = new Attributes('class', ['col-sm-10']);
            $apply['cellElementAttributes']->itemAppend('class', 'col-sm-10');
        }
    }

    /**
     * Process vertical layout settings for any scope
     * @param string $scope Names the settings scope/element this applies to.
     * @param array $values Array of colon-delimited settings including the initial keyword.
     * @throws \RuntimeException
     */
    public function showDoLayoutAnyVertical($scope, $values)
    {
        // possible values for arguments:
        // v            - Default
        // v:.class
        // v:n          - Inputs use n columns in the 12 column grid
        // v:m:t        - ratio of inputs over space t, adjusted to the BS grid
        //
        // Adjusts:
        // cellElementAttributes - Add the input column class
        // formGroupAttributes - add the form width
        //
        $default = true;
        $apply = &$this->showState[$scope];
        if (count($values) >= 2) {
            if ($values[1][0] == '.') {
                // class specification
                $col1 = substr($values[1], 1);
                $apply['formGroupAttributes']->itemAppend('class', $col1);
                $apply['cellElementAttributes']->itemAppend('class', $col1);
                $default = false;
            } elseif (preg_match('/^[+\-]?[0-9]+(\.[0-9]*)?$/', $values[1])) {
                // ratio
                $part1 = (float) $values[1];
                if (!$part1) {
                    throw new \RuntimeException(
                        'Zero is invalid for a ratio.'
                    );
                }
                $sum = isset($values[2]) ? $values[2] : 12;
                $factor = 12.0 / $sum;
                // Ensure columns are nonzero
                $col1 = 'col-sm-' . ((int) round($factor * $part1)) ?: 1;
                $apply['formGroupAttributes']->itemAppend('class', $col1);
                $apply['cellElementAttributes']->itemAppend('class', $col1);
                $default = false;
            }
        }
        if ($default) {
            $apply['formGroupAttributes']->itemAppend('class', 'col-sm-12');
            $apply['cellElementAttributes']->itemAppend('class', 'col-sm-12');
        }

    }

    /**
     * Process purpose options, called from showValidate()
     * @param string $scope Names the settings scope/element this applies to.
     * @param string $choice Primary option selection
     * @param array $value Array of colon-delimited settings including the initial keyword.
     */
    public function showDoPurpose($scope, $choice, $value = [])
    {
        if (
            strpos(
                '|primary|secondary|success|danger|warning|info|light|dark|link',
                '|' . $choice
            ) === false
        ) {
            throw new RuntimeException($choice . ' is an invalid value for purpose.');
        }
        if (!isset($this->showState[$scope])) {
            $this->showState[$scope] = [];
        }
        $this->showState[$scope]['purpose'] = $choice;
    }

    /**
     * Start rendering a form.
     *
     * @param type $options The 'attributes' option isn't really optional.
     * @return \Abivia\NextForm\Renderer\Block
     */
    public function start($options = []) : Block {
        $pageData = parent::start($options);
        $pageData->styleFiles[] = '<link rel="stylesheet"'
            . ' href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"'
            . ' integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T"'
            . ' crossorigin="anonymous">';
        $pageData->scriptFiles[] = '<script'
            . ' src="https://code.jquery.com/jquery-3.3.1.slim.min.js"'
            . ' integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo"'
            . ' crossorigin="anonymous"></script>';
        $pageData->scriptFiles[] = '<script'
            . ' src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"'
            . ' integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1"'
            . ' crossorigin="anonymous"></script>';
        $pageData->scriptFiles[] = '<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"'
            . ' integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM"'
            . ' crossorigin="anonymous"></script>';
        if (isset($options['scriptpath'])) {
            $pageData->scriptFiles[] = '<script src="' . $options['scriptpath'] . '></script>';
        } else {
            $pageData->script .= file_get_contents(__DIR__ . '/js/nf-bootstrap4.js')
                . "\n";
        }
        $id = $options['attributes']->get('id');
        $pageData->script .= 'var ' . $id . " = new NextForm($('#" . $id . "'));\n";
        return $pageData;
    }

}

