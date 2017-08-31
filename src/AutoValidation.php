<?php

namespace TBence\Validate;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\DateTimeTzType;
use Doctrine\DBAL\Types\DateType;
use Doctrine\DBAL\Types\DecimalType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\SmallIntType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

trait AutoValidation
{
    /**
     * Is automatic validation active?
     *
     * @var bool
     */
    private static $active = true;

    /**
     * Stores laravel validation rules for model
     *
     * @var array
     */
    private $rules = [];

    /**
     * Register the saving event listener for the model validation
     */
    public static function bootAutoValidation()
    {
        static::saving(function (Validates $model) {
            return (self::$active) ? $model->validate() : true;
        });
    }

    /**
     * Enable/disable automatic validation
     *
     * @param bool $bool
     */
    public static function useAutoValidation($bool = true)
    {
        self::$active = $bool;
    }

    /**
     * Enable automatic validation
     *
     * @param bool $bool
     */
    public static function enableAutoValidation()
    {
        self::useAutoValidation();
    }

    /**
     * Disable automatic validation
     *
     * @param bool $bool
     */
    public static function disableAutoValidation()
    {
        self::useAutoValidation(false);
    }

    /**
     * Get model validation rules
     *
     * @return mixed
     */
    public static function getRules()
    {
        return (new static)->getValidationRules();
    }

    /**
     * Validate the model.
     * ValidationExceptions are handled automatically by laravel.
     *
     * @return bool
     * @throws ValidationException
     */
    public function validate()
    {
        $validator = Validator::make($this->getAttributes(), $this->getValidationRules());

        if ($validator->fails()) {
            if (config('validate.dump')) {
                dump($this->rules, $this->getAttributes(), $validator->errors());
            }

            throw new ValidationException($validator);
        }

        return true;
    }

    /**
     * Get validation rules for model.
     * If there is a rules method on the model call that.
     * If there is a cached object for this model return with that.
     * Else generate and cache model validation rules.
     *
     * @return array|mixed
     */
    public function getValidationRules()
    {
        if (method_exists($this, 'rules')) {
            return $this->rules();
        }

        $table    = $this->getTable();
        $cacheKey = 'validate.' . $table;

        if (config('validate.cache') && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Get table info for model and generate rules
        $manager     = DB::connection()->getDoctrineSchemaManager();
        $details     = $manager->listTableDetails($table);
        $foreignKeys = $manager->listTableForeignKeys($table);

        $this->generateRules($details, $foreignKeys);

        if (config('validate.cache')) {
            Cache::forever($cacheKey, $this->rules);
        }

        return $this->rules;
    }

    /**
     * Generate validation rules by DB schema
     *
     * @param Table $details
     * @param array $foreignKeys
     */
    private function generateRules(Table $details, $foreignKeys)
    {
        foreach ($details->getColumns() as $column) {
            $this->setColumnValidationString($column);
        }

        foreach ($details->getIndexes() as $index) {
            $this->setIndexValidation($index);
        }

        foreach ($foreignKeys as $foreignKey) {
            $this->setForeignKeyValidation($foreignKey);
        }
    }

    /**
     * Add rules by column definitions
     *
     * @param Column $column
     */
    private function setColumnValidationString(Column $column)
    {
        $this->addRequiredRule($column);

        $this->addTypeRule($column);

        $this->addLengthRule($column);
    }

    /**
     * Add rules by indexes
     *
     * @param Index $index
     */
    private function setIndexValidation(Index $index)
    {
        $columns = $index->getColumns();

        if ($index->isUnique() && count($columns) === 1) {
            $field = $columns[0];
            $table = $this->getTable();

            $this->addRule($field, "unique:$table,$field");
        }
    }

    /**
     * Add rules by foreign keys
     *
     * @param ForeignKeyConstraint $foreignKey
     */
    public function setForeignKeyValidation(ForeignKeyConstraint $foreignKey)
    {
        $localColumns   = $foreignKey->getLocalColumns();
        $foreignColumns = $foreignKey->getForeignColumns();
        if (count($localColumns) > 1 || count($foreignColumns) > 1) {
            return;
        }

        $localColumn   = $localColumns[0];
        $foreignColumn = $foreignColumns[0];
        $foreignTable  = $foreignKey->getForeignTableName();

        $this->addRule($localColumn, "exists:$foreignTable,$foreignColumn");
    }

    /**
     * Add validation rule to column
     *
     * @param $column
     * @param $rule
     */
    private function addRule($column, $rule)
    {
        if (!array_key_exists($column, $this->rules)) {
            $this->rules[$column] = '';
        }

        $this->rules[$column] .= '|' . $rule;
        $this->rules[$column] = trim($this->rules[$column], '|');
    }

    /**
     * @param Column $column
     */
    private function addRequiredRule(Column $column)
    {
        if ($column->getNotnull() && !$column->getAutoincrement() && !$column->getDefault()) {
            $this->addRule($column->getName(), 'required');
        }
    }

    /**
     * @param Column $column
     */
    private function addTypeRule(Column $column)
    {
        $name = $column->getName();
        $type = $column->getType();

        if (
            $type instanceof SmallIntType ||
            $type instanceof IntegerType ||
            $type instanceof BigIntType ||
            $type instanceof DecimalType
        ) {
            $this->addRule($name, 'integer');
        } else if (
            $type instanceof StringType ||
            $type instanceof TextType
        ) {
            $this->addRule($name, 'string');
        } else if (
            $type instanceof DateType ||
            $type instanceof DateTimeType ||
            $type instanceof DateTimeTzType
        ) {
            $this->addRule($name, 'date');
        } else if (config('validate.dump')) {
            dump("Unknown type", $type);
        }
    }

    /**
     * @param Column $column
     */
    public function addLengthRule(Column $column)
    {
        if ($length = $column->getLength()) {
            $this->addRule($column->getName(), "max:$length");
        }
    }

}