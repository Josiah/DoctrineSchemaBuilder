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

use PHPUnit_Framework_TestCase as TestCase;
use PHPUnit_Framework_AssertionFailedError as AssertionFailedError;
use Doctrine\DBAL\Types\Type;

/**
 * Builder Test Case
 *
 * Asserts that the builder is manipulating the DBAL schema in the way which has
 * been documented on the class.
 *
 * @author Josiah <josiah@jjs.id.au>
 */
class BuilderTest extends TestCase
{
    /**
     * Tests that the builder correctly drops a table which exists in the schema
     * without side-effects
     *
     * @covers Doctrine\DBAL\Schema\Builder::dropTable()
     */
    public function testDropExistingTable()
    {
        // Fixtures
        $tables = array(new Table('test'));
        $schema = new Schema($tables);

        // Executions
        $builder = new Builder($schema);
        $builder->dropTable('test');

        // Assertions
        $this->assertFalse($schema->hasTable('test'), "`test` table should have been removed from schema");
    }

    /**
     * Tests that the builder correctly drops a table which exists in the schema
     * without side-effects
     *
     * @covers Doctrine\DBAL\Schema\Builder::dropTable()
     */
    public function testDropMissingTable()
    {
        $schema = $this->getMock('Doctrine\DBAL\Schema\Schema');

        // The builder should determine whether the table exists before it
        // attempts to drop the table.
        $schema->expects($this->once())
            ->method('hasTable')
            ->with($this->equalTo('test'))
            ->will($this->returnValue(false));

        // The schemas `dropTable` method should not be called once the builder
        // finds that the table doesn't exist in the schema
        $schema->expects($this->never())
            ->method('dropTable');


        // Executions
        $builder = new Builder($schema);
        $builder->dropTable('test');
    }

    public function testCreateNewTable()
    {
        $schema = new Schema();
        $builder = new Builder($schema);
        $tableRef = null;

        $builder->createTable('foo', function (Table $table) use (&$tableRef) {
            $tableRef = $table;
        });

        $this->assertCount(1, $schema->getTables(), 'there should be 1 table in the schema');
        $this->assertSame($tableRef, $schema->getTable('foo'), 'the `foo` table instance in the schema should match the reference passed to the closure');
    }

    public function testCreateExistingTable()
    {
        $schema = $this->getMock('Doctrine\DBAL\Schema\Schema');

        // The builder should determine whether the table exists before it
        // attempts to create the table.
        $schema->expects($this->once())
            ->method('hasTable')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(true));

        // The table should not be created when the builder determines that it
        // already exists
        $schema->expects($this->never())
            ->method('createTable');

        $builder = new Builder($schema);
        $builder->createTable('foo', function (Table $table) {
            throw new AssertionFailedError('Table definition closure should not be called');
        });
    }

    public function testDefineNewTable()
    {
        $schema = new Schema();
        $builder = new Builder($schema);
        $tableRef = null;

        $builder->defineTable('foo', function (Table $table) use (&$tableRef) {
            $tableRef = $table;
        });

        $this->assertCount(1, $schema->getTables(), 'there should be 1 table in the schema');
        $this->assertSame($tableRef, $schema->getTable('foo'), 'the `foo` table instance in the schema should match the reference passed to the closure');
    }

    public function testDefineExistingTable()
    {
        $oldTable = new Table('bar');
        $schema = new Schema(array($oldTable));
        $builder = new Builder($schema);
        $newTable = null;

        $builder->defineTable('bar', function (Table $table) use (&$oldTable, &$newTable, &$test) {
            $newTable = $table;
        });

        $this->assertCount(1, $schema->getTables(), 'there should be 1 table in the schema');
        $this->assertNotSame($oldTable, $newTable, 'table definition should be made on a new table instance');
        $this->assertSame($newTable, $schema->getTable('bar'), 'the `bar` table instance in the schema should be the new table');
    }

    public function testDefineNamedForeignKey()
    {
        $bar = new Table('bar', array(
            new Column('id', Type::getType('integer')),
        ));
        $bar->setPrimaryKey(['id']);

        $foo = new Table('foo', array(
            new Column('bar_id', Type::getType('integer')),
        ));

        $schema = new Schema(array($foo, $bar));
        $builder = new Builder($schema);

        $builder->defineNamedForeignKey('FK_Foo_bar', 'foo', ['bar_id'], 'bar');

        $fk = $foo->getForeignKey('FK_Foo_bar');
        $this->assertEquals('foo', $fk->getLocalTableName(), 'foreign key should be on `foo`');
        $this->assertEquals(['bar_id'], $fk->getLocalColumns(), 'foreign key have local column `bar_id` as the reference');
        $this->assertEquals('bar', $fk->getForeignTableName(), 'foreign key should reference `bar`');
        $this->assertEquals(['id'], $fk->getForeignColumns(), 'foreign key should reference the `id` column');
    }

    public function testDefineNamedForeignKeyAsOverride()
    {
        $bar = new Table('bar', array(
            new Column('id', Type::getType('integer')),
        ));
        $bar->setPrimaryKey(['id']);

        $foo = new Table('foo', array(
            new Column('bar_id', Type::getType('integer')),
        ));
        $oldKey = $foo->addNamedForeignKeyConstraint('FK_Foo_bar', 'bar', ['bar_id'], ['baz']);

        $schema = new Schema(array($foo, $bar));
        $builder = new Builder($schema);

        $builder->defineNamedForeignKey('FK_Foo_bar', $foo, ['bar_id'], $bar, ['id']);

        $fk = $foo->getForeignKey('FK_Foo_bar');
        $this->assertCount(1, $foo->getForeignKeys(), 'foreign key should have replaced exising key');
        $this->assertEquals('bar', $fk->getForeignTableName(), 'foreign key should reference the `bar` table');
        $this->assertEquals(['id'], $fk->getForeignColumns(), 'foreign key should reference the `id` column');
    }
}