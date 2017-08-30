<?php

namespace TBence\Validate;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
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

    private $rules = [];

    public static function bootAutoValidation()
    {
        static::saving(function ($model) {
            return $model->validate();
        });
    }

    public function validate()
    {
        $validator = Validator::make($this->getAttributes(), $this->getValidationRules());

        if ($validator->fails()) {
            if (config('validate.dump')) {
                dump($this->rules, $this->toArray(), $validator->errors());
            }

            throw new ValidationException($validator);
        }

        return true;
    }

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

        $manager = DB::connection()->getDoctrineSchemaManager();

        $details = $manager->listTableDetails($table);

        foreach ($details->getColumns() as $column) {
            $this->setColumnValidationString($column);
        }

        foreach ($details->getIndexes() as $index) {
            $this->setIndexValidation($index);
        }

        $foreignKeys = $manager->listTableForeignKeys($table);
        foreach ($foreignKeys as $foreignKey) {
            $this->setForeignKeyValidation($foreignKey);
        }

        if (config('validate.cache')) {
            Cache::forever($cacheKey, $this->rules);
        }

        return $this->rules;
    }

    private function setColumnValidationString(Column $column)
    {
        $name = $column->getName();

        if ($column->getNotnull() && !$column->getAutoincrement() && !$column->getDefault()) {
            $this->addRule($name, 'required');
        }

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
        }

    }

    private function setIndexValidation(Index $index)
    {
        $columns = $index->getColumns();

        if ($index->isUnique() && count($columns) === 1) {
            $field = $columns[0];
            $table = $this->getTable();

            $this->addRule($field, "unique:$table,$field");
        }
    }

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

    private function addRule($column, $rule)
    {
        if (!array_key_exists($column, $this->rules)) {
            $this->rules[$column] = '';
        }

        $this->rules[$column] .= '|' . $rule;
        $this->rules[$column] = trim($this->rules[$column], '|');
    }

}