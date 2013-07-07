Doctrine Schema Builder
=======================

[![Build Status](https://travis-ci.org/Josiah/DoctrineSchemaBuilder.png)](https://travis-ci.org/Josiah/DoctrineSchemaBuilder)

Provides a declarative wrapper around the Doctrine DBAL `Schema` class which allows you to define the schema as you would like it to be rather than in terms of what it was.

Example
-------

Actions speak louder than words so here's an example of the builder in action:

```php
use Doctrine\DBAL\Schema\Builder;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

$schema = new Schema();
$builder = new Builder($schema);

$builder
    // Create table if it doesn't exist, do nothing if it does
    ->createTable('foo', function (Table $table) {
        $table->addColumn('id', 'integer')
            ->setNotNull(true)
            ->setUnsigned(true)
            ->setAutoIncrement(true)
            ->setComment("Unique Identifier, DUH!");

        $table->addColumn('name', 'string')
            ->setNotNull(true);

        $table->setPrimaryKey(['id']);

        // ... you get the picture.
    })

    // Overwrite table if it exists, create it if it doesnt
    ->defineTable('bar', function (Table $table) {
        // ... more definition like before
    })

    // Drop table if it exists, do nothing if it doesnt
    ->dropTable('baz');
```

License
-------
Licenced under an [MIT license](LICENSE). If that doesn't work for you get in touch with me.