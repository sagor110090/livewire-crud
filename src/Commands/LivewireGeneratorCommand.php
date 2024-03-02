<?php

namespace Sagor110090\LivewireCrud\Commands;

use Sagor110090\LivewireCrud\ModelGenerator;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class GeneratorCommand.
 */
abstract class LivewireGeneratorCommand extends Command
{
    /**
     * The filesystem instance.
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Do not make these columns fillable in Model or views.
     * @var array
     */
    protected $unwantedColumns = [
        'id',
        'password',
        'email_verified_at',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Table name from argument.
     * @var string
     */
    protected $table = null;

    /**
     * Formatted Class name from Table.
     * @var string
     */
    protected $name = null;

    /**
     * Store the DB table columns.
     * @var array
     */
    private $tableColumns = null;

    private $datatypeAndFields = null;

    /**
     * Model Namespace.
     * @var string
     */
    protected $modelNamespace = 'App\Models';

    /**
     * Controller Namespace.
     * @var string
     */
    protected $controllerNamespace = 'App\Http\Controllers';
    /**
     * Controller Namespace.
     * @var string
     */
    protected $livewireNamespace = 'App\Livewire';

    /**
     * Application Layout
     * @var string
     */
    protected $layout = 'layouts.app';

    /**
     * Custom Options name
     * @var array
     */
    protected $options = [];

    /**
     * Create a new controller creator command instance.
     * @param \Illuminate\Filesystem\Filesystem $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
        $this->unwantedColumns = config('livewire-crud.model.unwantedColumns', $this->unwantedColumns);
        $this->modelNamespace = config('crud.model.namespace', $this->modelNamespace);
        $this->controllerNamespace = config('livewire-crud.controller.namespace', $this->controllerNamespace);
        $this->livewireNamespace = config('livewire-crud.livewire.namespace', $this->livewireNamespace);
        $this->layout = config('livewire-crud.layout', $this->layout);
    }

    /**
     * Generate the Model.
     * @return $this
     */
    abstract protected function buildModel();

    /**
     * Generate the views.
     * @return $this
     */
    abstract protected function buildViews();

    /**
     * Build the directory if necessary.
     * @param string $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true, true);
        }

        return $path;
    }

    /**
     * Write the file/Class.
     * @param $path
     * @param $content
     */
    protected function write($path, $content)
    {
        $this->files->put($path, $content);
    }

    /**
     * Get the stub file.
     * @param string $type
     * @param boolean $content
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     */
    protected function getStub($type, $content = true)
    {
        $stub_path = config('livewire-crud.stub_path', 'default');
        if ($stub_path == 'default') {
            $stub_path = __DIR__ . '/../stubs/';
        }

        $path = Str::finish($stub_path, '/') . "{$type}.stub";

        if (!$content) {
            return $path;
        }

        return $this->files->get($path);
    }

    /**
     * @param $no
     * @return string
     */
    private function _getSpace($no = 1)
    {
        $tabs = '';
        for ($i = 0; $i < $no; $i++) {
            $tabs .= "\t";
        }

        return $tabs;
    }

    /**
     * @param $name
     * @return string
     */
    protected function _getMigrationPath($name)
    {
        return base_path("database/migrations/" . date('Y-m-d_His') . "_create_" . Str::lower(Str::plural($name)) . "_table.php");
    }
    protected function _getFactoryPath($name)
    {
        return base_path("database/factories/{$name}Factory.php");
    }

    /**
     * @param $name
     * @return string
     */
    protected function _getLivewirePath($name)
    {
        //make plural
        $name = Str::plural($name);
        return app_path($this->_getNamespacePath($this->livewireNamespace) . "{$name}");
    }

    /**
     * @param $name
     * @return string
     */
    protected function _getModelPath($name)
    {
        return $this->makeDirectory(app_path($this->_getNamespacePath($this->modelNamespace) . "{$name}.php"));
    }

    /**
     * Get the path from namespace.
     * @param $namespace
     * @return string
     */
    private function _getNamespacePath($namespace)
    {
        $str = Str::start(Str::finish(Str::after($namespace, 'App'), '\\'), '\\');

        return str_replace('\\', '/', $str);
    }

    /**
     * Get the default layout path.
     * @return string
     */
    private function _getLayoutPath()
    {
        return $this->makeDirectory(resource_path("/views/layouts/app.blade.php"));
    }

    /**
     * @param $view
     * @return string
     */
    protected function _getViewPath($view)
    {
        // $name = Str::kebab($this->name);
        $name = $this->options['route'] ?? Str::kebab(Str::plural($this->name));

        return $this->makeDirectory(resource_path("/views/livewire/{$name}/{$view}.blade.php"));
    }

    protected function _getLivewirePathForClass($class)
    {
        // var_dump($class);
        $name = $this->options['route'] ?? Str::kebab(Str::plural($this->name));
        return $this->makeDirectory(app_path("Livewire/{$name}/{$class}.php"));
    }

    /**
     * Build the replacement.
     * @return array
     */
    protected function buildReplacements()
    {
        return [
            '{{layout}}' => $this->layout,
            '{{modelName}}' => $this->name,
            '{{modelTitle}}' => Str::title(Str::snake($this->name, ' ')),
            '{{modelTitlePlural}}' => Str::title(Str::snake(Str::plural($this->name), ' ')),
            '{{modelNamespace}}' => $this->modelNamespace,
            '{{controllerNamespace}}' => $this->controllerNamespace,
            '{{modelNamePluralLowerCase}}' => Str::camel(Str::plural($this->name)),
            '{{modelNamePluralUpperCase}}' => ucfirst(Str::plural($this->name)),
            '{{modelNameLowerCase}}' => Str::camel($this->name),
            '{{modelRoute}}' => $this->options['route'] ?? Str::kebab(Str::plural($this->name)),
            '{{modelView}}' => $this->options['route'] ?? Str::kebab(Str::plural($this->name)),
            '{{tableName}}' => $this->table,
        ];
    }

    /**
     * Build the form fields for form.
     * @param $title
     * @param $column
     * @param string $type
     * @return mixed
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     */
    protected function getField($title, $column, $type = null)
    {
        $replace = array_merge($this->buildReplacements(), [
            '{{title}}' => $title,
            '{{column}}' => $column,
        ]);


        $unwanted = $this->unwantedColumns;

        for ($i = 0; $i < sizeof($this->datatypeAndFields); $i++) {
            $datatypeAndFields[] = array_filter($this->datatypeAndFields[$i], function ($value) use ($unwanted) {
                return !in_array($value, $unwanted);
            });
            if (isset($datatypeAndFields[$i]['name'])) {
                if ($datatypeAndFields[$i]['name'] == $column) {
                    if ($datatypeAndFields[$i]['type'] == 'varchar(255)') {
                        $type = 'form-field';
                    }
                    if ($datatypeAndFields[$i]['type'] == 'int(11)') {
                        $type = 'number-field';
                    }
                    if ($datatypeAndFields[$i]['type'] == 'longtext') {
                        $type = 'textarea-field';
                    }
                    if ($datatypeAndFields[$i]['type'] == 'text') {
                        $type = 'textarea-field';
                    }
                    if ($datatypeAndFields[$i]['type'] == 'date') {
                        $type = 'date-field';
                    }
                    if ($datatypeAndFields[$i]['type'] == 'time') {
                        $type = 'time-field';
                    }
                    if ($datatypeAndFields[$i]['type'] == 'tinyint(1)') {
                        $type = 'checkbox-field';
                    }
                    // dump($datatypeAndFields[$i]);
                }
            }
        }
        return str_replace(
            array_keys($replace),
            array_values($replace),
            $this->getStub("views/fields/{$type}")
        );
    }
    protected function getFieldForShow($title, $column, $type = null)
    {
        $replace = array_merge($this->buildReplacements(), [
            '{{title}}' => $title,
            '{{column}}' => $column,
        ]);
        return str_replace(
            array_keys($replace),
            array_values($replace),
            $this->getStub("views/fields/{$type}")
        );
    }

    /**
     * @param $title
     * @return mixed
     */
    protected function getHead($title)
    {
        $replace = array_merge($this->buildReplacements(), [
            '{{title}}' => $title,
        ]);

        return str_replace(
            array_keys($replace),
            array_values($replace),
            $this->_getSpace(4) . "<th>{{__('{{title}}')}}</th>" . "\n"
        );
    }

    /**
     * @param $column
     * @return mixed
     */
    protected function getBody($column)
    {
        $replace = array_merge($this->buildReplacements(), [
            '{{column}}' => $column,
        ]);

        return str_replace(
            array_keys($replace),
            array_values($replace),
            $this->_getSpace(4) . '<td>{{ $row->{{column}} }}</td>' . "\n"
        );
    }

    /**
     * Make layout if not exists.
     * @throws \Exception
     */
    protected function buildLayout(): void
    {
        if (!(view()->exists($this->layout))) {

            $this->info('Creating Layout ...');

            if ($this->layout == 'layouts.app') {
                $this->files->copy($this->getStub('layouts/app', false), $this->_getLayoutPath());
            } else {
                throw new \Exception("{$this->layout} layout not found!");
            }
        }
    }

    /**
     * Get the DB Table columns.
     * @return array
     */
    protected function getColumns()
    {
        if (empty($this->tableColumns)) {
            $this->tableColumns = DB::select('SHOW COLUMNS FROM ' . $this->table);
        }
        return $this->tableColumns;
    }

    /**
     * @return array
     */
    protected function getFilteredColumns()
    {
        $unwanted = $this->unwantedColumns;
        $columns = [];
        foreach ($this->getColumns() as $key => $column) {
            $columns[] = $column->Field;
            $type[$key]['type'] = $column->Type;
            $type[$key]['name'] = $column->Field;
        }
        $this->datatypeAndFields = $type;

        return array_filter($columns, function ($value) use ($unwanted) {
            return !in_array($value, $unwanted);
        });
    }

    /**
     * Make model attributes/replacements.
     * @return array
     */
    protected function modelReplacements()
    {
        $properties = '';
        $rulesArray = [];
        $softDeletesNamespace = $softDeletes = '';

        foreach ($this->getColumns() as $value) {
            $properties .= "\n * @property $$value->Field";

            if ($value->Null == 'NO') {
                $rulesArray[$value->Field] = 'required';
            }

            if ($value->Field == 'deleted_at') {
                $softDeletesNamespace = "use Illuminate\Database\Eloquent\SoftDeletes;\n";
                $softDeletes = "use SoftDeletes;\n";
            }
        }

        $rules = function () use ($rulesArray) {
            $rules = '';
            // Exclude the unwanted rulesArray
            $rulesArray = Arr::except($rulesArray, $this->unwantedColumns);
            // Make rulesArray
            foreach ($rulesArray as $col => $rule) {
                $rules .= "\n\t\t'{$col}' => '{$rule}',";
            }

            return $rules;
        };

        $fillable = function () {

            /** @var array $filterColumns Exclude the unwanted columns */
            $filterColumns = $this->getFilteredColumns();

            // Add quotes to the unwanted columns for fillable
            array_walk($filterColumns, function (&$value) {
                $value = "'" . $value . "'";
            });

            // CSV format
            return implode(',', $filterColumns);
        };

        $updatefield = function () {

            /** @var array $filterColumns Exclude the unwanted columns */
            $filterColumns = $this->getFilteredColumns();

            // Add quotes to the unwanted columns for fillable
            array_walk($filterColumns, function (&$value) {
                $value = "$" . $value . "";
            });

            // CSV format
            return implode(', ', $filterColumns);
        };

        $resetfields = function () {

            /** @var array $filterColumns Exclude the unwanted columns */
            $filterColumns = $this->getFilteredColumns();

            // Add quotes to the unwanted columns for fillable
            array_walk($filterColumns, function (&$value) {
                $value = "\n\t\t\$this->" . $value . " = null";
                $value .= ";";
            });

            // CSV format
            return implode('', $filterColumns);
        };

        $addfields = function () {

            /** @var array $filterColumns Exclude the unwanted columns */
            $filterColumns = $this->getFilteredColumns();

            // Add quotes to the unwanted columns for fillable
            array_walk($filterColumns, function (&$value) {
                $value = "\n\t\t\t'" . $value . "' => \$this-> " . $value;
            });

            // CSV format
            return implode(',', $filterColumns);
        };

        $keyWord = function () {

            /** @var array $filterColumns Exclude the unwanted columns */
            $filterColumns = $this->getFilteredColumns();

            // Add quotes to the unwanted columns for fillable
            array_walk($filterColumns, function (&$value) {
                $value = "\n\t\t\t\t\t\t->orWhere('" . $value . "', 'LIKE', \$keyWord)";
            });

            // CSV format
            return implode('', $filterColumns);
        };

        $factoryfields = function () {

            /** @var array $filterColumns Exclude the unwanted columns */
            $filterColumns = $this->getFilteredColumns();

            // Add quotes to the unwanted columns for fillable */
            array_walk($filterColumns, function (&$value) {
                $value = "\n\t\t\t'" . $value . "' => \$this->faker->name,";
            });

            // CSV format
            return implode('', $filterColumns);
        };

        $editfields = function () {

            /** @var array $filterColumns Exclude the unwanted columns */
            $filterColumns = $this->getFilteredColumns();

            // Add quotes to the unwanted columns for fillable
            array_walk($filterColumns, function (&$value) {
                $value = "\n\t\t\$this->" . $value . " = \$record->" . $value . ";";
            });

            // CSV format
            return implode('', $filterColumns);
        };

        $tableColumnsClass = function () {
            $markup = '    public function columns(): array
            {
                return [
                    Column::make("Id", "id")
                        ->sortable(),

';

            foreach ($this->getFilteredColumns() as $column) {
                if ($column != 'is_active') {
                    $markup .= '            Column::make("' . Str::title(str_replace('_', ' ', $column)) . '", "' . $column . '")->searchable()->sortable(),';
                } else {
                    $markup .= '            BooleanColumn::make("active", "is_active")->sortable(),';
                }
            }

            $markup .= '
                    Column::make("Updated at", "updated_at")
                        ->format(function ($value, $row, Column $column) {
                            return Carbon::parse($value)->diffForHumans();
                        })
                        ->sortable(),

                    Column::make("Actions")
                        ->label(
                            function ($row, Column $column) {
                                $delete = "<button class=\"rounded-lg bg-red-500 px-4 py-2 text-white mr-2\" wire:click=\"triggerConfirm(" . $row->id . ")\">Delete</button>";
                                $edit = "<button class=\"rounded-lg bg-blue-500 px-4 py-2 text-white mr-2\" wire:click=\"edit(" . $row->id . ")\">Edit</button>";
                                if (!$row->is_active) {
                                    $is_active = "<button class=\"rounded-lg bg-green-500 px-4 py-2 text-white mr-2\" wire:click=\"approve(" . $row->id . ")\">Active</button>";
                                } else {
                                    $is_active = "<button class=\"rounded-lg bg-red-500 px-4 py-2 text-white mr-2\" wire:click=\"approve(" . $row->id . ")\">Deactive</button>";
                                }
                                // return  $delete;
                                return $edit . $delete . $is_active;
                            }
                        )->html(),

                ];
            }';

            return $markup;
        };

        list($relations, $properties) = (new ModelGenerator($this->table, $properties, $this->modelNamespace))->getEloquentRelations();

        return [
            '{{fillable}}' => $fillable(),
            '{{updatefield}}' => $updatefield(),
            '{{resetfields}}' => $resetfields(),
            '{{editfields}}' => $editfields(),
            '{{addfields}}' => $addfields(),
            '{{factory}}' => $factoryfields(),
            '{{rules}}' => $rules(),
            '{{search}}' => $keyWord(),
            '{{relations}}' => $relations,
            '{{properties}}' => $properties,
            '{{softDeletesNamespace}}' => $softDeletesNamespace,
            '{{softDeletes}}' => $softDeletes,
            '{{tableColumnsClass}}' => $tableColumnsClass(),
        ];
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return trim($this->argument('name'));
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the table'],
        ];
    }

    /**
     * Is Table exist in DB.
     * @return mixed
     */
    protected function tableExists()
    {
        return Schema::hasTable($this->table);
    }
}
