# Manage your database via CLI

 * creating/dropping schema
 * loading fixtures
 * verbose options
 * environment specific


## Installation

Clone the code to your library directory (or add it as a submodule):

	cd libraries
	git clone git@github.com:housni/li3_db.git

Include the library in in your `/app/config/bootstrap/libraries.php`

	Libraries::add('li3_db');


## Usage
This is a command line tool so you will be typing all of the commands below in a terminal.
I'll replace all instances of `./libraries/lithium/console/li3` with `li3` for brevity.


### Creates, drops, dumps and truncates the schema and then loads the records.
To recreate the schema for the 'users' table and insert all the records for it:
```
li3 db reload Users
```

To recreate the schema for the 'users' and 'roles' table and insert all the records for them:
```
li3 db reload Users,Roles
```

To recreate the schema for all tables and insert all their records:
```
li3 db reload
```

All of the actions also allow you to be verbose:
```
li3 db reload Users --verbose=true
```

You can also specify the environment:
```
li3 db reload Users --env=test
```



### Inserts records into the database.
In order to insert all the records into the table named 'users', you can do:
```
li3 db fixtures load Users
```

You can also insert records into multiple tables ('users' and 'roles'):
```
li3 db fixtures load Users,Roles
```

In order to insert all records into all the tables, exclude the table names:
```
li3 db fixtures load
```

All of the actions also allow you to be verbose:
```
li3 db fixtures load Users --verbose=true
```

You can also specify the environment:
```
li3 db fixtures load Users --env=test
```


### Creates, drops, dumps and truncates the schema.
In order to create the schema for the 'users' table:
```
li3 db schema create UsersRoles
```

In order to drop the schema for the 'users' table:
```
li3 db schema drop Users
```

Like the `fixtures` command, you can apply the actions to multiple tables:
```
li3 db schema create Users,Roles
```
or
```
li3 db schema drop Users,Roles
```

To create or drop the schema for all tables, do:
```
li3 db schema create
```
or
```
li3 db schema drop
```

All of the actions also allow you to be verbose:
```
li3 db schema drop Users,Roles --verbose=true
```

You can also specify the environment:
```
li3 db schema drop Users --env=test
```