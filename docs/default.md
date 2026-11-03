# Nextras Migrations

- **Supported databases:** PostgreSQL, MySQL
- **Supported DBALs:** [Nextras DBAL](https://github.com/nextras/dbal), [Nette Database](https://github.com/nette/database), [Doctrine DBAL](https://github.com/doctrine/dbal) and [dibi](https://github.com/dg/dibi)
- **Integrations:** [Nette Extension](nette-extension.md), [Symfony Bundle](symfony-bundle.md), [Symfony Console](symfony-console.md) and [plain PHP](plain-php.md)

---


## Organizing Migrations

Migrations are located in one or more directories (called **groups**). Unless configured otherwise, Nextras Migrations defines three groups.

1. `structures`
	- used for queries altering database structure (`CREATE TABLE ...`, `ALTER TABLE ...`) and for data migrations queries required to support the structural changes
2. `basic-data`
	- used for queries which insert, update or delete basic data required to start the application
	- used for both development and production
3. `dummy-data`
	- used for queries which insert, update or delete dummy data used when developing the application
	- used only for development

Migrations are executed in alphabetical order (by filename), e.g. `structures/2015-03-17-aaa.sql` is executed before `dummy-data/2015-03-17-zzz.sql`.
The following structure is recommended and used by Symfony Console Commands by default:

```
migrations
├── basic-data                           # for both development and production
│   ├── 2015-03-16-170342-languages.sql  # YYYY-MM-DD-HHMMSS-label.extension
│   └── ...
├── dummy-data                           # for development on localhost
│   ├── 2015-03-17-104235-users.sql
│   └── ...
└── structures                           # create, alter tables...
    ├── 2015-03-17-155419-users.sql
    └── ...
```

Optionally, you can use a **deep directory structure** which is suitable if you have a lot of migrations:

```
migrations/
├── basic-data/
│   └── 2015/
│       ├── 03/
│       │   ├── 2015-03-16-170342-languages.sql
│       │   └── ...
│       └── 04/
│           └── ...
└── ...
```

---


## Writing Migrations

Migrations can be written as a file with any extension that has a defined extension handler. The two built-in extension handlers are SQL and PHP. Migrations can be written manually or generated through a diff generator. The only built-in diff generator is a `structures` generator for Doctrine.

Doctrine structure diffs preserve tables and sequences outside the target schema, including the migrations ledger. ORM 2 uses SchemaTool's native safe mode. ORM 3 builds the target schema once through SchemaTool, including schema-generation listeners, with the existing application filter still active. The same target schema limits database introspection and is compared through the public DBAL schema manager, comparator and platform APIs. Where available, the comparator uses SchemaTool's setting to avoid reporting modified indexes.

During ORM 3 comparison, target tables and sequences are whitelisted even if the existing filter rejects them, matching SchemaTool's mapped-object behavior. Other tables and sequences are excluded. The exact original filter is restored even when introspection, comparison or SQL generation fails. This does not change application-wide schema validation or the introspection view seen by schema-generation listeners.

Safe generation can still produce destructive changes within mapped tables, such as removing columns, indexes, or foreign keys. ORM 2 retains its native safe-mode treatment of foreign keys referencing unmapped tables; ORM 3 follows SchemaTool's behavior for foreign keys on mapped tables. Always review the generated SQL before executing a migration.
