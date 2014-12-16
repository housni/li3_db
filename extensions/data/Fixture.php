<?php
/**
 * li3_db: Manage your database via CLI.
 *
 * @copyright     Copyright 2014, Housni Yakoob (http://kooboid.com)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_db\extensions\data;

class Fixture extends \lithium\data\Model {

	public static function model() {
		return static::$_model;
	}

	public static function load($index = null) {
		if (is_numeric($index)) {
			return static::$_fixtures[$index];
		}
		return static::$_fixtures;
	}

}

?>