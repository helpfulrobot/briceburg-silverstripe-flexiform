<?php

class FlexiFormField extends DataObject
{

    protected $field_class = 'FormField';

    protected $field_label = 'Override Me';

    protected $field_description = 'Override Me';

    // used to automatically generate fields during /dev/build
    protected $field_definitions = array();

    private static $db = array(
        'FieldName' => 'Varchar(16)',
        'FieldDefaultValue' => 'Varchar',
        'Readonly' => 'Boolean'
    );

    private static $has_many = array(
        'Options' => 'FlexiFormFieldOption'
    );

    private static $belongs_many_many = array(
        'FlexiForms' => 'FlexiForm'
    );

    private static $searchable_fields = array(
        'FieldName' => array(
            'title' => 'Name',
            'field' => 'TextField',
            'filter' => 'PartialMatchFilter'
        )
    );

    private static $summary_fields = array(
        'FieldName' => 'Name'
    );

    function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('Options');
        $fields->removeByName('FlexiForms');
        $fields->removeByName('Readonly');

        $field = $fields->dataFieldByName('FieldName');
        $field->setTitle('Name');
        $field->setMaxLength(16);
        $field->description = 'Shown in submissions. Should be short and without special characters.';

        $field = $fields->dataFieldByName('FieldDefaultValue');
        $field->setTitle('Default Value');
        $field->description = 'Optional. Will prepopulate the field with this value.';

        $field = new LiteralField('Description',
            "<strong>{$this->field_label} Field &mdash;</strong> {$this->field_description} <hr />");
        $fields->addFieldToTab('Root.Main', $field, 'FieldName');

        if ($this->Readonly) {
            $fields = $fields->transform(new ReadonlyTransformation());
        }

        return $fields;
    }

    public function validate()
    {
        $result = parent::validate();

        if ($result->valid()) {
            if (empty($this->FieldName)) {
                $result->error('Name cannot be blank');
            }
        }

        return $result;
    }

    // override this method for custom behavior
    public function getFormField($name, $value = null)
    {
        $field_class = $this->field_class;
        $field = new $field_class($name);

        if ($value !== null) {
            $field->setValue($value);
        }

        return $field;
    }

    public function Label()
    {
        return $this->field_label;
    }

    public function Description()
    {
        return $this->field_description;
    }

    public function OptionsPreview()
    {
        return '-';
    }

    public function getTitle()
    {
        $readonly = ($this->Readonly) ? '*' : '';
        return "{$this->FieldName} ({$this->field_label})$readonly";
    }

    public function getDefaultValue()
    {
        $value = $this->getField('DefaultValue');
        $name = $this->getField('Name');
        // && empty($name) ensures DefaultValue is set only once, and subsequent allows empty values.
        return (empty($value) && empty($name)) ? $this->FieldDefaultValue : $value;
    }

    public function getName()
    {
        $value = $this->getField('Name');
        return (empty($value)) ? $this->FieldName : $value;
    }

    public function getFlexiFieldDefinitions()
    {
        return $this->field_definitions;
    }

    public function setFlexiFieldDefinitions(Array $flexi_field_definitions)
    {
        return $this->field_definitions = $flexi_field_definitions;
    }

    public function requireDefaultRecords()
    {
        foreach ($this->getFlexiFieldDefinitions() as $definition) {
            FlexiFormUtil::AutoCreateFlexiField($this->ClassName, $definition);
        }

        if($yml_definitions = Config::inst()->get($this->ClassName, 'field_definitions')) {
            foreach ($yml_definitions as $definition) {
                FlexiFormUtil::AutoCreateFlexiField($this->ClassName, $definition);
            }
        }
        return parent::requireDefaultRecords();
    }

    private static $indexes = array(
        'FLEXI_READONLY' => array(
            'type' => 'index',
            'value' => 'Readonly'
        )
    );
}