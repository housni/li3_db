<?php
/**
 * li3_db: Manage your database via CLI.
 *
 * @copyright     Copyright 2014, Housni Yakoob (http://kooboid.com)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_db\extensions\command;

use lithium\util\Inflector;
use lithium\data\Model;
use lithium\data\Schema;
use lithium\data\Connections;
use lithium\core\Libraries;

class Db extends \lithium\console\Command {

	/**
	 * Setting this to `true` will display all the data it possible can. Default is `false`.
	 *
	 * @var bool
	 */
	public $verbose = false;

	/**
	 * @todo
	 * @var bool
	 */
	public $data = false;

	/**
	 * @todo
	 * @var string
	 */
	public $file = LITHIUM_APP_PATH;

	/**
	 * Determines the subsequence of strings such as prefix, suffix, etc.
	 *
	 * @todo  Make this configurable
	 * @var string
	 */
	protected $_subsequence = [
		'fixtures' => [
			'prefix' => '',
			'suffix' => 'Fixture',
		]
	];

	protected function _init() {
		parent::_init();

		Libraries::paths(['fixtures' => ['{:library}\fixtures\{:name}']]);
		$this->file .= DIRECTORY_SEPARATOR;
	}

	/**
	 * Creates, drops, dumps and truncates the schema and then loads the records.
	 *
	 * To recreate the schema for 'users' and insert all the records for it:
	 *     $ li3 db reload Users
	 *
	 * To recreate the schema for 'users' and 'roles' and insert all the records for them:
	 *     $ li3 db reload Users,Roles
	 *
	 * To recreate the schema for all tables and insert all their records:
	 *     $ li3 db reload
	 *
	 * All of the actions also allow you to be verbose:
	 *     $ li3 db reload Users --verbose=true
	 *
	 * You can also specify the environment:
	 *     $ li3 db reload Users --env=test
	 *
	 * @param string $model The model(s) to reload.
	 * @return void
	 */
	public function reload($model = null) {
		$this->schema('create', $model);
		$this->fixtures('load', $model);
	}

	/**
	 * Inserts records into the database.
	 *
	 * In order to insert all the records into the table named 'users', you can do:
	 *     $ li3 db fixtures load UsersRoles
	 *
	 * You can also insert records into multiple tables ('users' and 'roles'):
	 *     $ li3 db fixtures load Users,Roles
	 *
	 * In order to insert all records into all the tables, exclude the table names:
	 *     $ li3 db fixtures load
	 *
	 * All of the actions also allow you to be verbose:
	 *     $ li3 db fixtures load Users --verbose=true
	 *
	 * You can also specify the environment:
	 *     $ li3 db fixtures load Users --env=test
	 *
	 * @todo  document $options
	 * @param string $action The action to perform on $model.
	 * @param string $model The model(s) to perform $action on.
	 * @return void
	 */
	public function fixtures($action = null, $model = null) {
		$actions = [
			'load'
		];

		if (!$action) {
			$this->_help();
			$this->stop(1);
		}

		if (!in_array($action, $actions)) {
			$this->error("Unsupported action '$action'", 'red');
			$this->stop(1);
		}

		$this->hr();
		$action = "_{$action}Fixtures";

		if (!$model) {
			foreach (Libraries::locate('fixtures') as $class) {
				$fixtureModel = $class::model();
				$this->db = $fixtureModel::connection();
				$this->db->read('SET FOREIGN_KEY_CHECKS=0');
				$this->$action($class);
				$this->db->read('SET FOREIGN_KEY_CHECKS=1');
			}
		}

		if ($model) {
			$models = (array) $model;
			if (false !== strpos($model, ',')) {
				$models = explode(',', $model);
			}

			extract($this->_subsequence['fixtures']);
			foreach ($models as $class) {
				$fixture = Libraries::locate('fixtures', "{$prefix}{$class}{$suffix}");
				$namespacedModel = Libraries::locate('models', $class);
				$this->db = $namespacedModel::connection();
				$this->db->read('SET FOREIGN_KEY_CHECKS=0');
				$this->$action($fixture);
				$this->db->read('SET FOREIGN_KEY_CHECKS=1');
			}
		}
	}

	/**
	 * Loads fixtures from $model into its table
	 *
	 * @param  string $fixture Full namespaced name of the fixture to load.
	 * @return void
	 */
	protected function _loadFixtures($fixture) {
		$model = $fixture::model();
		$count = 0;
		$query = null;
		foreach ($fixture::load() as $data) {
			$object = $model::create();
			if (!$object->save($data)) {
				$this->error("Error inserting for model $model", 'red');
				$this->stop(1);
			}
			$count++;
			unset($object);
		}
		$this->out("Inserted $count fixtures for $model.");

		if ($this->verbose) {
			$this->out("\t" . $query, "green");
		}
	}

	/**
	 * Creates, drops, dumps and truncates the schema.
	 *
	 * In order to create the schema for the 'users' table:
	 *     $ li3 db schema create Users
	 *
	 * In order to drop the schema for the 'users' table:
	 *     $ li3 db schema drop Users
	 *
	 * Like the `fixtures` command, you can apply the actions to multiple tables:
	 *     $ li3 db schema create Users,Roles
	 * or
	 *     $ li3 db schema drop Users,Roles
	 *
	 * To create or drop the schema for all tables, do:
	 *     $ li3 db schema create
	 * or
	 *     $ li3 db schema drop
	 *
	 * All of the actions also allow you to be verbose:
	 *     $ li3 db schema drop Users,Roles --verbose=true
	 *
	 * You can also specify the environment:
	 *     $ li3 db schema drop Users --env=test
	 *
	 * @param string $action The action to perform on $model.
	 * @param string $model The model(s) to perform $action on.
	 * @return void
	 */
	public function schema($action = null, $model = null) {
		$actions = ['create', 'drop', 'dump', 'truncate'];

		if (!$action) {
			$this->_help();
			$this->stop(1);
		}

		if (!in_array($action, $actions)) {
			$this->error("Unsupported action '$action'", 'red');
			$this->stop(1);
		}

		$this->hr();
		$action = "_{$action}Schema";

		if (!$model) {
			foreach (Libraries::locate('models') as $class) {
				$skip = false;
				$isSubclass = is_subclass_of($class, '\lithium\data\Model');
				if (!$isSubclass || isset($class::$persist) && false === $class::$persist) {
					$skip = true;
				}
				if (!$skip && false !== $class::meta('connection')) {
					$this->db = $class::connection();
					$this->db->read('SET FOREIGN_KEY_CHECKS=0');
					$this->$action($class);
					$this->db->read('SET FOREIGN_KEY_CHECKS=1');
				}
			}
		}

		if ($model) {
			$models = (array) $model;
			if (false !== strpos($model, ',')) {
				$models = explode(',', $model);
			}

			foreach ($models as $class) {
				$class = Libraries::locate('models', $class);
				$this->db = $class::connection();
				$this->db->read('SET FOREIGN_KEY_CHECKS=0');
				$this->$action($class);
				$this->db->read('SET FOREIGN_KEY_CHECKS=1');
			}
		}
	}

	/**
	 * Drops the existing schema and then creates it for $model and generates a message.
	 *
	 * @param  string $model Full namespaced name of the model
	 * @return void
	 */
	protected function _createSchema($model) {
		$this->_dropSchema($model);

		if (!$created = $this->_create($model)) {
			$this->error("Could not create `$model` schema", 'red');
			$this->stop(1);
		}
		$this->out("Created $model.");

		if ($this->verbose) {
			$this->out("\t" . $created->resource()->queryString, "green");
		}
	}

	/**
	 * #TODO
	 *
	 * Dumps the SQL for the schema(ta) structure for the model(s), on screen.
	 * If the `--data=true` switch is used, the data will also be dumped.
	 * In addition, if the `--file` switch is used, then the data will be dumped to the location
	 * specified by `--file` that is relative to `LITHIUM_APP_PATH` instead of displaying on screen.
	 *
	 * @param  string $model Full namespaced name of the model
	 * @return void
	 */
	protected function _dumpSchema($model) {
	}

	/**
	 * Drops the schema for $model and generates a message.
	 *
	 * @param  string $model Full namespaced name of the model
	 * @return void
	 */
	protected function _dropSchema($model) {
		if (!$dropped = $this->_drop($model)) {
			$this->error("Could not drop `$model` schema", 'red');
			$this->stop(1);
		}
		$this->out("Dropped $model.");

		if ($this->verbose) {
			$this->out("\t" . $dropped->resource()->queryString, "green");
		}
	}

	/**
	 * #TODO
	 *
	 * Truncates the schema for $model
	 *
	 * @param  string $model Full namespaced name of the model
	 * @return void
	 */
	protected function _truncateSchema($model) {
	}

	/**
	 * Creates the schema for $model.
	 *
	 * @param  string $model Full namespaced name of the model
	 * @return void
	 */
	protected function _create($model) {
		$table = $model::meta('source');

		$definition['fields'] = $model::schema()->fields();

		if (isset($model::meta()['constraints']) && !empty($model::meta()['constraints'])) {
			$definition['meta']['constraints'] = $model::meta()['constraints'];
		}

		if (isset($model::meta()['table']) && !empty($model::meta()['table'])) {
			$definition['meta']['table'] = $model::meta()['table'];
		}

		$schema = new Schema($definition);
		return $this->db->createSchema($table, $schema);
	}

	/**
	 * Drops the schema for $model.
	 *
	 * @param  string $model Full namespaced name of the model
	 * @return void
	 */
	protected function _drop($model) {
		$table = $model::meta('source');
		return $this->db->dropSchema($table);
	}

	/**
	 * Runs a raw SQL query.
	 *
	 * @todo
	 * @param  string $sql A avalid SQL string
	 * @return string      Results formatted as a table
	 */
	protected function sql($sql) {}

	/**
	 * Updates database schema so that it is in sync with the schema definition in the model.
	 *
	 * @todo
	 * @param  string $model The model(s) to reload.
	 * @return [type]        [description]
	 */
	protected function sync($model = null) {}
}

?>