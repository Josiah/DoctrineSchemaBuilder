<?php
/**
 * Copyright (c) 2013 Josiah Truasheim
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Doctrine\DBAL\Schema;

use Closure;

/**
 * Schema Builder
 *
 * Expresses the definition of a database schema in terms of the desired state
 * rather than a reflection of change to existing state.
 *
 * @author Josiah <josiah@jjs.id.au>
 */
class Builder
{
    /**
     * DBAL Schema
     * 
     * @var Schema
     */
    protected $schema;

    /**
     * Instantiates a new schema builder instance for the specified schema
     * 
     * @param Schema $schema DBAL Schema
     */
    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * Create Table
     *
     * Creates a database table unless it already exists in the database.
     *
     * @example
     * ```php
     * use Doctrine\DBAL\Schema\Table;
     * $builder->createTable('foo', function (Table $table) {
     *     // ... definition code
     * });
     * ```
     * @param string   $name       Table name
     * @param callable $definition Table definition callback
     * @return Builder
     */
    public function createTable($name, $definition)
    {
        if (!$this->schema->hasTable($name)) {
            call_user_func($definition, $this->schema->createTable($name));
        }

        return $this;
    }

    /**
     * Define Table
     *
     * Ensures that the table matches the specified definition by replacing any
     * existing definition with the specified definition.
     * 
     * @example
     * ```php
     * use Doctrine\DBAL\Schema\Table;
     * $builder->createTable('foo', function (Table $table) {
     *     // ... definition code
     * });
     * ```
     * @param string   $name       Table name
     * @param callable $definition Table definition callback
     * @return Builder
     */
    public function defineTable($name, $definition)
    {
        $this->dropTable($name);
        call_user_func($definition, $this->schema->createTable($name));

        return $this;
    }

    /**
     * Drop Table
     *
     * Drops a database table from the schema, when the table is not in the 
     * schema nothing happens.
     *
     * @example
     * ```php
     * $builder->dropTable('foo');
     * ```
     * @param string $name Table name
     * @return Builder
     */
    public function dropTable($name)
    {
        if ($this->schema->hasTable($name)) {
            $this->schema->dropTable($name);
        }

        return $this;
    }

    /**
     * Defines a named foreign key relationship
     *
     * When a foreign key relationship exists with the same name on the database
     * it will be replaced with this definition.
     *
     * @param string|Table $localTable     Local table
     * @param array        $localColumns   Local columns
     * @param string|Table $foreignTable   Foreign table
     * @param array        $foreignColumns Foreign columns (default to primary key)
     * @return Builder
     */
    public function defineNamedForeignKey($name, $localTable, array $localColumns, $foreignTable, array $foreignColumns = null, array $options = array())
    {
        // Load the local table
        if (!$localTable instanceof Table) {
            $localTable = $this->schema->getTable($localTable);
        }

        // Load the foreign table
        if (!$foreignTable instanceof Table) {
            $foreignTable = $this->schema->getTable($foreignTable);
        }

        // Where the foreign columns are not specified they are retrieved from
        // the foreign tables primary key definition.
        if (is_null($foreignColumns)) {
            $foreignColumns = $foreignTable->getPrimaryKeyColumns();
        }

        // Where the foreign key exists, it should be removed for recreation
        if ($localTable->hasForeignKey($name)) {
            $localTable->removeForeignKey($name);
        }

        // Actual foreign key is added using the local Doctrine DBAL Table and
        // referencing the other specified options
        $localTable->addNamedForeignKeyConstraint($name, $foreignTable, $localColumns, $foreignColumns, $options);

        // Method chaining is supported
        return $this;
    }
}