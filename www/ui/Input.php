<?php

include_once 'UIElement.php';
include_once __DIR__ . '/../../Models/Constants/ui/InputTypes.php';
include_once __DIR__ . '/../../Models/Traits/ui/TUIElementWithInput.php';

class Input extends UIElement {

    use TUIElementWithInput;

    private $type;

    protected function setHTML() {
        if ($this->type === null) {
            throw new Exception('Type for input element must be set');
        }

        if ($this->name === null) {
            throw new Exception('Name for input element must be set');
        }


        $class = $this->class ?: 'default_input_class';
        switch ($this->type) {
            case InputTypes::NUMBER:
                $this->html =
                    "<input
                        class=$class
                        type=$this->type
                        name=$this->name
                        value=$this->value
                    />";
                break;

            case InputTypes::CHECKBOX:
                $checked = $this->value === true ? ' checked' : '';
                $this->html =
                    "<input
                        class=$class
                        type=$this->type
                        name=$this->name
                        $checked
                    />";
                break;

            default:
                throw new Exception(sprintf(
                    '%s is not supported in Input UI element',
                    $this->type
                ));
        }
    }

    public function setType($type) {
        InputTypes::assertIsValidValue($type);
        $this->type = $type;
        return $this;
    }
}

?>
