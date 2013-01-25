<?php
/**************************************************/
/*** ------ MySQL Database Handler Class ------ ***/
/*** ------ Created By: Cody Moncur ----------- ***/
/*** ------ Created On: Oct 31, 2012 ---------- ***/
/*** ------ Last Updated: Dec 06, 2012 -------- ***/
/**************************************************/

/**************************************************/
/*** ------------- DEPENDENCIES --------------- ***/
/**************************************************/

include('configure.php'); //Defines constants used to connect to MySQL and SQLite




class Db {
	/**********************************************/
	/*** --------------- MEMBERS -------------- ***/
	/**********************************************/

	private $l; //MySQL Link




	/**********************************************/
	/*** - CONSTRUCTOR AND DESTRUCTOR METHODS - ***/
	/**********************************************/

	/**
	*** Constructor
	*** Automatically called upon $db = new Db()
	*** Establishes connections to MySQL
	*** Uses CONSTANTS that are defined in configure.php
	**/
	function __construct() {
		try {
			$this->l = new PDO("mysql:host=".MYSQL_HOST.";dbname=".MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);
			$this->l->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		} catch (PDOException $e) {
			$this->log(1, $e->getMessage());
			die($e->getMessage());
		} 
	}


	/**
	*** Destructor
	*** Automatically called when there are no further references to Db()
	*** Nullifies MySQL connection
	**/
	function __destruct() {
		try {
			$this->l = null;
		} catch (PDOException $e) {
			$this->log(1, $e->getMessage());
		}
	}



	/**********************************************/
	/*** ----------- PRIVATE METHODS ---------- ***/
	/**********************************************/

	/**
	*** Error log handler
	*** If a connection to MySQL is established, all errors will be logged in the MySQL database.
	*** If a connection to MySQL is NOT established, errors will be logged in a flat file.
	**/
	private function log($type, $error) {
		$e = addslashes($error);
		if ($type == 0) {
			$this->insert("log", array("date", "type", "description"), array(time(), "MySQL Error", $e));
		} elseif ($type == 1) {
			file_put_contents("../log/dberror.log", "Date: " . date('M j Y - G:i:s') . " ---- Error: " . $error.PHP_EOL, FILE_APPEND);
		}
	}




	/**********************************************/
	/*** ----------- PUBLIC METHODS ----------- ***/
	/**********************************************/

	/**
	*** Insert 
	*** Can insert one row with multiple fields OR multiple rows with one field into a MySQL database
	*** $db->insert("table", "fieldname", $array); --Multiple rows with one field
	*** OR 
	*** $db->insert("table", $fields_array, $values_array); --One row with multiple fields
	*** 
	*** The $field parameter can be either an array or a single string
	**/
	public function insert($into, $field, $values) {
		$f = count($field);
		$v = count($values);
		if ($f > 1) {
			if($f == $v) {
				$fields_i = implode(",", $field);
				$values_i = implode("','", $values);
				$s = $this->l->prepare("insert into $into ($fields_i) values ('$values_i')");
				try {
					$s->execute();
				} catch (PDOException $e) {
					$this->log(0, $e->getMessage());
					return false;
				}
			} else {
				return false;
			}
		} else {
			$i = 0;
			while($i < $v) {
				$s = $this->l->prepare("insert into $into ($field) values ('$values[$i]')");
				try {
					$s->execute();
					$i++;
				} catch (PDOException $e) {
					$this->log(0, $e->getMessage());
					return false;
				}
			}
		}
	}


	/**
	*** Number of Rows
	*** $selection = $db->numRows("some_table", "name", "Joe")
	***
	*** Intended to pull data from MySQL database when anticipating ONLY one row as a result
	*** If WHERE clause is not used, defaults to 'WHERE 1 = 1'
	**/
	public function numRows($from, $where=1, $x=1) {
		$n = $this->l->prepare("select * from $from where $where = '$x'");
		try {
			$n->execute();
			return $n->rowCount();
		} catch (PDOException $e) {
			$this->log(0, $e->getMessage());
			return false;
		}
	}


	/**
	*** Selection
	***
	*** $names = $db->selectCoarse("id", "some_table");
	*** Returns MULTIDIMENSIONAL ARRAY
	*** Example: echo $x[4]["id"];  --Where [4]is the row and ["id"] is the column
	***
	*** Intended to pull data from MySQL database when anticipating more than one row as a result
	*** If WHERE clause is not used, defaults to 'WHERE 1 = 1'
	**/
	public function select($select, $from, $where=1, $x=1) {
		try {
			$s = $this->l->query("select $select from $from where $where = '$x'");
			$s->setFetchMode(PDO::FETCH_ASSOC);
			$row = $s->fetchAll();
			return $row;
		} catch (PDOException $e) {
			$this->log(0, $e->getMessage());
			return false;
		}
	}


	/**
	*** Selection using LIKE
	***
	*** $names = $db->selectLike("*", "some_table", "this_column", "something");
	*** Returns an ARRAY 
	***
	*** Intended to pull data from MySQL database when anticipating more than one row as a result
	*** WHERE can be an ARRAY of feilds
	**/
	public function selectLike($select, $from, $where, $x) {
		$s = $this->l->query("select $select from $from where $where like '%$x%'");
		try {
			$s->setFetchMode(PDO::FETCH_OBJ);
			$r = array();
			while ($row = $s->fetch()) {  
				array_push($r, $row->$select); 
			}
			return $r;
		} catch (PDOException $e) {
			$this->log(0, $e->getMessage());
			return false;
		}
	}

	/**
	*** Sorted Selection
	***
	*** $names = $db->selectCoarse("id", "some_table");
	*** Returns MULTIDIMENSIONAL ARRAY
	*** Example: echo $x[4]["id"];  --Where [4]is the row and ["id"] is the column
	***
	*** Intended to pull data from MySQL database when anticipating more than one row as a result
	*** If WHERE clause is not used, defaults to 'WHERE 1 = 1'
	**/
	public function selectSort($select, $from, $order, $sort, $limit, $where=1, $x=1) {
		try {
			$s = $this->l->query("select $select from $from where $where = '$x' order by $order $sort limit $limit");
			$s->setFetchMode(PDO::FETCH_ASSOC);
			$row = $s->fetchAll();
			return $row;
		} catch (PDOException $e) {
			$this->log(0, $e->getMessage());
			return false;
		}
	}


	/**
	*** Update
	*** Can update an entry within a MySQL database
	*** 
	*** $db->update("table", "name", $name, "id", id); --Example of update method
	**/
	public function update($table, $field, $value, $where, $x) {
		$s = $this->l->prepare("update $table set $field = '$value' where $where = '$x'");
		try {
			$s->execute();
		} catch (PDOException $e) {
			$this->log(0, $e->getMessage());
			return false;
		}
	}
}

$db = new Db(); //Initiate new Db() instance
?>




