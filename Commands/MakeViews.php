<?php

namespace Vulcan\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Vulcan\Libraries\GeneratorTrait;

/**
 * Creates a skeleton Controller
 *
 * @package Vulcan\Commands
 */
class MakeViews extends BaseCommand {

    use GeneratorTrait;

    protected $group = 'Vulcan';
    protected $options;

    /**
     * The Command's name
     *
     * @var string
     */
    protected $name = 'make:views';

    /**
     * the Command's short description
     *
     * @var string
     */
    protected $description = 'Creates a skeleton View file.';

    /**
     * Creates a skeleton view file.
     */
    public function run(array $params = []) {
        /*
         * Controller name
         */
        $name = array_shift($params);

        if (empty($name)) {
            $name = CLI::prompt('Controller name');
        }

        // Format to CI standards
        $name = ucfirst($name);

        /*
         * Model
         */
        $model = CLI::getOption('model');

        if (empty($model)) {
            $defaultModel = $name . 'Model';
            $model = CLI::prompt('Model name', $defaultModel);
        }
        
        // Format per CI
        if ( ! empty( $model ) && substr( $model, - 5 ) !== 'Model' )
        {
                $model .= 'Model';
        }
        

        $this->options = [
            'model' => $model ?? 'UnnamedModel',
        ];

        helper('inflector');

        $data = [
            'name' => $name,
            'lower_name' => strtolower($name),
            'single_name' => singular($name),
            'plural_name' => plural($name),
            'fields' => $this->prepareFields()
        ];

        $subfolder = $data['lower_name'];
        $this->overwrite = (bool) CLI::getOption('f');

        try {

            // Index
            $destination = $this->determineOutputPath('Views/' . $subfolder) . 'index.php';
            $this->copyTemplateTPL('View/view_index', $destination, $data, $this->overwrite);

            // Create
            $destination = $this->determineOutputPath('Views/' . $subfolder) . 'form.php';
            $this->copyTemplateTPL('View/view_create', $destination, $data, $this->overwrite);

            // Show
            $destination = $this->determineOutputPath('Views/' . $subfolder) . 'show.php';
            $this->copyTemplateTPL('View/view_show', $destination, $data, $this->overwrite);

            // Index
            $destination = $this->determineOutputPath('Views/' . $subfolder) . 'update.php';
            $this->copyTemplateTPL('View/view_update', $destination, $data, $this->overwrite);
        } catch (\Exception $e) {
            $this->showError($e);
        }
    }

    //--------------------------------------------------------------------

    /**
     * Grabs the fields from the CLI options and gets them ready for
     * use within the views.
     */
    protected function prepareFields() {
        // If we have a model, we can get our fields from there
        if (!empty($this->options['model'])) {
            $fields = $this->getFieldsFromModel($this->options['model']);

            if (empty($fields)) {
                return NULL;
            }
        } else {
            return NULL;
        }

        $new_fields = [];

        foreach ($fields as $field) {

            $type = strtolower($field->type);

            // Ignore list
            if (in_array($field->name, ['created_on', 'modified_on'])) {
                continue;
            }

            // Strings
            if (in_array($type, ['char', 'character', 'character varying', 'varchar', 'string'])) {
                $new_fields[] = [
                    'name' => $field->name,
                    'type' => 'text'
                ];
            }

            // Textarea
            else if ($type == 'text') {
                $new_fields[] = [
                    'name' => $field->name,
                    'type' => 'textarea'
                ];
            }

            // Number
            else if (in_array($type, ['tinyint', 'int', 'integer', 'bigint', 'mediumint', 'float', 'double', 'number'])) {
                $new_fields[] = [
                    'name' => $field->name,
                    'type' => 'number'
                ];
            }

            // Date
            else if (in_array($type, ['date', 'datetime', 'time'])) {
                $new_fields[] = [
                    'name' => $field->name,
                    'type' => $type
                ];
            }
        }

        return $new_fields;
    }

    //--------------------------------------------------------------------

    private function getFieldsFromModel($modelName) {
        $fullModelName = 'App\Models\\' . $modelName;

        $reflectionClass = new \ReflectionClass($fullModelName);

        $reflectionProperty = $reflectionClass->getProperty('table');
        $reflectionProperty->setAccessible(true);
        $table = $reflectionProperty->getValue(new $fullModelName);

        $this->db = \Config\Database::connect();

        if (!$this->db->tableExists($table)) {
            return '';
        }

        $fields = $this->db->getFieldData($table);

        return $fields;
    }

    //--------------------------------------------------------------------
}
