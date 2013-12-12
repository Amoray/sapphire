<?php

namespace spin;

/**
* Base CRM
*/
class core
{
	const		TABLENAME			= null;
	const		TABLECOLS			= null;

	const		VIEWNAME			= null;
	const		VIEWCOLS			= null;

	const		GETACCESS			= "children, expandpath, depth, enabled, id, tabledefinition, sysalert, image";
	const		SETACCESS			= "children, expandpath, depth, enabled";
	const		TRANSLATETO			= null;
	const		TRANSLATEFROM		= null;
	const		NOUN				= "";

	const		PERMALINK			= null;

	const		MEMBER 				= false;

	static		$user				= null;

	protected	$id					= null;
	protected	$deleted			= false;
	protected	$enabled			= true;
	private		$runonce			= false;
	public		$safename			= "";

	protected	$timezone			= null;

	protected	$sysalert			= null;

	protected	$expandpath			= false;
	protected	$error				= array();
	protected	$depth				= 0;
	protected	$tabledefinition	= null;

	protected	$attach				= array();
	protected	$children			= array();
	protected	$image				= array();


	public function __construct($id = null)
	{
		$this->safename = static::safename();

		$this->inspectTable();

		if (is_numeric($id))
		{
			$this->id = $id;
		}
	}

	static function safename($class = null)
	{
		return str_replace('\\', '_', get_called_class());
	}

	/**
	 * Fetches approved variables via CLASSES GETACCESS
	 * @param  string $value name of the variable requested
	 * @return mixed        returns variable.
	 */
	public function __get($name)
	{
		$access = array_unique(array_merge(explode(',', static::GETACCESS), explode(',', self::GETACCESS)));
		$access = array_map('trim', $access);
		if (in_array($name, $access))
		{
			return $this->{$name};
		}
		elseif (array_key_exists($name, $this->attach))
		{
			return $this->attach[$name];
		}
		else
		{
			throw new \Exception("Cannot grant accessor ". get_class() ."(". get_called_class() ."->{$name})");
		}
	}

	/**
	 * Sets approved variables via CLASS' SETACCESS
	 * @param string $name  name of the value you wish to set
	 * @param string $value value you wish to apply to the name
	 */
	public function __set($name, $value)
	{
		if (defined('static::TRANSLATEFROM'))
		{
			$access = array_map('trim', array_unique(array_merge(explode(',', static::TRANSLATEFROM), explode(',', self::TRANSLATEFROM))));
			if (in_array($name, $access))
			{
				if (defined('static::TRANSLATETO'))
				{
					$translateto = static::TRANSLATETO;
				}
				elseif (defined('self::TRANSLATETO'))
				{
					$translateto = static::TRANSLATETO;
				}
				else
				{
					throw new \Exception("Translator missing, cannot set ". get_class() ."(". get_called_class() ."->{$name})");
				}
				return self::__set($translateto, $value);
			}
		}
		$access = array_map('trim', array_unique(array_merge(explode(',', static::SETACCESS), explode(',', self::SETACCESS))));
		if (in_array($name, $access))
		{
			$this->{$name} = $value;
			return $value;
		}
		else
		{
			throw new \Exception("Cannot grant mutator ". get_class() ."(". get_called_class() ."->{$name})");
		}
	}

	public function __isset($name)
	{
		return isset($this->{$name});
	}

	public function __unset($name)
	{
		throw new \Exception("Cannot unset properties". get_class() ."(". get_called_class() ."->{$name})");
		return false;
	}

	public function __call($name, $attributes)
	{
		if (preg_match('/^add_?(.*)$/', $name, $match))
		{
			call_user_func(function ($object, $call, $attributes)
			{
				$object->add($call, $attributes);
			}, $object = $this, $match[1], $attributes);

		}
		elseif (preg_match('/^set_?(.*)$/', $name, $match))
		{
			call_user_func(function ($object, $property, $attribute)
			{
				$object->{$property} = $attribute;
			}, $object = $this, $match[1], $attributes[0]);
		}
		elseif (preg_match('/^attach_?(.*)$/', $name, $match))
		{
			call_user_func(function ($object, $property, $attribute)
			{
				$object->attach($property, $attribute);
			}, $object = $this, $match[1], $attributes[0]);
		}
		else
		{
			throw new \Exception("No magic found: {$name}");
			
		}
		return $this;
	}

	/**
	 * load assistant (id)
	 * @param  int $id record primary key
	 * @return loaded instance of class
	 */
	static function withid( $id )
	{
		$instance = new static( $id );
		$instance->load();
		return $instance;
	}

	/**
	 * load assistant (all)
	 * @param  array $array all required data (can be used to fudge classes)
	 * @return loaded instance of class
	 */
	static function withall( $array )
	{
		$instance = new static();
		$instance->loadall($array);
		return $instance;
	}

	/**
	 * search assistant assists creating the search functions
	 * @param boolean find from view?
	 * @return object instance of search containing class
	 */
	// static function find($view = false)
	// {
	// 	$instance = new search(new static(), $view = false);
	// 	return $instance;
	// }
	static function findbean($view = false)
	{
		$instance = new searchbean(new static(), $view);
		return $instance;
	}

	/**
	 * find - alias for findbean
	 * @param  boolean $view find from database view
	 * @return object        instance of search containing class
	 */
	public function find($view = false)
	{
		return static::findbean($view);
	}

	/**
	 * search - find functionality using a class constant table reference to search 
	 * @param  [type] $reference (TABLE, VIEW, SEARCH)
	 * @return [type]            searchbean object;
	 */
	public function search($reference)
	{
		$instance = new searchbean(new static(), $reference);
		return $instance;
	}

	/**
	 * findAssociated - attempts to locate classes that this belongs to
	 * @param  string $select class name ( auto prepended with \crm\)
	 * @return array         array of objects
	 * @return boolean       false if non-class name provided
	 */
	public function findAssociated($select = "account")
	{
		$class = '\\crm\\'. $select;
		$select = $class .'::MEMBERNAME';
		if (defined($select)) 
		{
			$beans = \R::find(constant($select), " child = ? ", array($this->id));
			$beans = array_map(function ($value) use ($class)
			{
				$temp = $class::withid($value->parent);
				$temp->load();
				return $temp;
			}, $beans);
			return $beans;
		}
		return false;

	}


	static function email()
	{
		$instance = new email(new static());
		return $instance;
	}

	/**
	 * default loader
	 * @return boolean true
	 */
	protected function load($view = false)
	{
		if (!empty($this->id)) 
		{
			$columns = array();
			if ($view)
			{
				$bean = \R::load(static::VIEWNAME, $this->id);
				if (defined('static::VIEWCOLS') && !is_null(static::VIEWCOLS))
				{
					$columns = array_map('trim', explode(',', static::VIEWCOLS));
				}
			}
			else
			{
				$bean = \R::load(static::TABLENAME, $this->id);
				if (defined('static::TABLECOLS') && !is_null(static::TABLECOLS))
				{
					$columns = array_map('trim', explode(',', static::TABLECOLS));
				}
			}

			if (!$bean->id)
			{
				throw new \LoadingCRMException("Content Missing: Could not load instance of ". get_called_class() . " extending " . get_class());
			}
			
			if (!empty($columns))
			{
				foreach ($columns as $column) 
				{
					$this->{$column} = $bean->{$column};
				}
			}
			else
			{
				foreach ($bean->getProperties() as $key => $value) 
				{
					$this->{$key} = $value;
				}
			}

			if (is_string($this->timezone) || empty($this->timezone))
			{
				$this->timezone = new DateTimeZone((empty($this->timezone) ? \System::$timezone : $this->timezone));
			}
			static::postload();
			return true;
		}

		throw new \Exception("ID Missing: Could not load instance of ". get_called_class() . " extending " . get_class());
	}

	/**
	 * loadall - attempt to load the object with supplied array rather than the database
	 * This is generally for creating an instance of an class without touching the database
	 * A child class may override this by setting $this->id and calling self::load();
	 * @param  array $array an array of the values to pass into the object
	 * @return boolean true
	 */
	public function loadall($values)
	{
		foreach ($values as $key => $value) 
		{
			try {
				$this->{$key} = $value;
			} catch (\Exception $e) {
				$this->attach($key, $value);
			}
		}
		static::postload();
		return true;
	}

	/**
	 * postload - Run after load for any additional configurations
	 * @return boolean true
	 */
	public function postload()
	{
		return true;
	}

	public function attach($name, $value)
	{
		$this->attach[$name] = $value;
		return $this;
	}

	/**
	 * save - crm helper function that returns a bean for use in save operations
	 * @return RedBean bean for saving
	 */
	protected function save()
	{
		if (is_null($this->id))
		{
			$bean = \R::dispense(static::TABLENAME);
			$this->sysalert = static::NOUN ." created";
		}
		else
		{
			$bean = \R::load(static::TABLENAME, $this->id);
			if (!$bean->id)
			{
				throw new \Exception(static::NOUN ." not found, could not save");
				return;
			}
			$this->sysalert = static::NOUN ." saved";
		}

		return $bean;
	}

	protected function store($bean)
	{
		try 
		{
			$this->id = \R::store($bean);
			if (property_exists($this, 'sysalert')) 
			{
				$_SESSION['sysalert'] = $this->sysalert;
			}
			return true;
		} 
		catch (\Exception $e) 
		{
			$e = sqlException($e);
			$this->errorAdd($e->test());
			return false;
		}
	}

	/**
	 * delete - delete function marks a row deleted.
	 * @return boolean successful
	 */
	public function delete()
	{
		if (is_numeric($this->id))
		{
			$bean = \R::load(static::TABLENAME, $this->id);
			$bean->deleted = 1;
			\R::store($bean);
			return true;
		}
		return false;
	}

	/**
	 * nuke - remove the bean from the database
	 * @return boolean successful
	 */
	public function nuke()
	{
		if (is_numeric($this->id))
		{
			$bean = \R::load(static::TABLENAME, $this->id);
			\R::trash($bean);
			return true;
		}
		return false;
	}

	public function toggle($value)
	{
		switch ($value) 
		{
			case 'enable':
				return self::toggleEnable();
				break;
			
			case 'feature':
				return self::toggleFeature();
				break;
		}
	}

	public function toggleEnable()
	{
		if (is_numeric($this->id))
		{
			$bean = \R::load(static::TABLENAME, $this->id);
			$bean->enabled = ((bool)$bean->enabled ? false : true);
			\R::store($bean);
			return true;
		}
		return false;
	}

	public function toggleFeature()
	{
		if (is_numeric($this->id))
		{
			$bean = \R::load(static::TABLENAME, $this->id);
			$bean->featured = ((bool)$bean->featured ? false : true);
			\R::store($bean);
			return true;
		}
		return false;
	}

	/**
	 * process - can be called as a parent process method, used as a shortcut to invoke helper processes
	 * crm\account->process() with member-remove will trigger crm\account_member->process()
	 * @return $this
	 */
	protected function process($limit = false)
	{
		switch (reset(explode('-', \System::$request))) 
		{
			case 'member':
				$classname = "\\crm\\". end(explode('\\', get_class($this))) ."_member";
				if (\System::Request('contentid'))
				{
					$object = $classname::withid(\System::Request('contentid'));
				}
				elseif (\System::Request('id'))
				{
					$object = $classname::withid(\System::Request('id'));
				}
				else
				{
					$object = new $classname();
				}

				if ("spincrm" == \System::$process)
				{
					return $object->process($limit);
				}

				break;

			case 'address':
				if (\System::Request('contentid'))
				{
					$object = new \crm\address(\System::Request('contentid'));
					$object->process($limit);
				}
				break;

			case 'location':
				if (\System::Request('contentid')) 
				{
					location::remove(get_class($this), \System::Request('contentid'));
				}	
				break;

			case 'position':
				if (\System::Request('contentid'))
				{
					$object = \crm\career_position::withid(\System::Request('contentid'));
					$object->process($limit);
				}
				break;

			case 'campus':
				if (\System::Request('contentid')) 
				{
					$object = \crm\program_campus::withid(\System::Request('contentid'));
					$object->process($limit);
				}
				break;

			case 'file':

				if (\System::Request('id')) 
				{
					$object = new \crm\file(\System::Request('id'));
					$object->process($this, $limit);
				}
				break;

			default:
				throw new \Exception("Process not found: ". \System::$process ."->". \System::$request);
				break;
		}

		return $this;
	}

	static function init()
	{
		// Check for user login
		// 
		self::$user = \crm\user::withsession();
		if (self::$user) 
		{}
	}

	static function preprocess()
	{
		switch (reset(explode('-', \System::$request))) 
		{
			case 'user':
				
				if (\System::Request('id')) 
				{
					$object = \crm\user::withid(\System::Request('id'));
				}
				elseif (self::$user) 
				{
					$object = self::$user;
				}
				else
				{
					$object = new \crm\user();
				}

				$object->process('public');

				break;

			case 'event':
				if (\System::Request('id')) 
				{
					$object = \crm\event::withid(\System::Request('id'));
				}
				else
				{
					$object = new \crm\event();
				}

				$object->process('public');

				break;
		}
	}

	public function getreference()
	{
		return array_pop(explode('\\', get_class($this)));
	}

	public function getMemberRelationship($id)
	{
		if (static::MEMBER) 
		{
			$memberclass = "\\crm\\". static::MEMBER;
			return $memberclass::withuniquepair($this->id, $id);
		}
		return false;
	}

	public function permalink()
	{
		if (!defined("static::PERMALINK")) 
		{
			throw new \Exception("Permalink unavailable in ". get_called_class() . " extending " . get_class());
		}
		$name = "";
		if (property_exists($this, 'name')) 
		{
			$name = "/". \Location::URLString($this->name);
		}
		return "/". static::PERMALINK . $name . "/id/{$this->id}";
	}

	/**
	 * check if object is empty
	 * @return [type] [description]
	 */
	public function isEmpty()
	{
		return empty($this->id);
	}

	/**
	 * fetch html form elements, automatically fills $this with elements requested
	 * if a string is used as a values key, postGet will fill $this->string->field
	 * @param  array  $fields list of fields required
	 * @param  string $prefix can be used to prefix the posted value's key
	 * @return array list of fields found
	 */
	protected function postGet($fields, $prefix="")
	{
		$retrieved = array();
		foreach ($fields as $class => $field) 
		{
			if (is_array($field))
			{
				array_merge($retrieved, $this->postGet($field, $prefix));
			}
			elseif (array_key_exists($prefix.$field, $_POST))
			{

				$retrieved[] = $field;
				// if $class has 'class::' we need to try to instantiate the class that follows
				// if $class is set then we need to assume that the property mutation needs to take place in that class
				// if both of the above are true such as 'foo::class::bar' we need to mutate foo by instantiate bar and assigning
				// 

				if (is_string($class) && false !== strpos($class, "class::"))
				{
					$split = explode('::', $class, 3);
					$classname = array_pop($split);
					$psuedo = array_shift($split);

					if ($psuedo !== "class")
					{
						$this->{$psuedo} = new $classname($this->postGet_FETCH($prefix.$field));
					}
					elseif (class_exists($classname))
					{
						$this->{$field} = new $classname($this->postGet_FETCH($prefix.$field));
					}
					else
					{
						throw new \Exception("Cannot instantiate class ". get_class() ."(". get_called_class() ."->{$classname})");
					}
				}
				elseif (is_string($class))
				{
					$this->{$class}->{$field} = $this->postGet_FETCH($prefix.$field);
				}
				else
				{
					$this->{$field} = $this->postGet_FETCH($prefix.$field);
				}
			}
		}
		return $retrieved;
	}


	/**
	 * A little helper function to make postGet less complex looking
	 * @param  string $name name of the post field you want to grab
	 * @return mixed 	Whatever happens to be in _POST
	 */
	private function postGet_FETCH($name)
	{
		if (!empty($_POST[$name]))
		{	
			return $_POST[$name];
		}
		elseif (0 === $_POST[$name] || "0" === $_POST[$name])
		{
			return 0;
		}
		else
		{
			return NULL;
		}
	}

	/**
	 * add errors to internal and system variables
	 * @param  array  $array array containing index value pairs
	 */
	public function errorAdd(array $array)
	{
		foreach ($array as $key => $value) 
		{
			if (array_key_exists($key, $this->error))
			{
				if (is_string($this->error[$key]))
				{
					$temp = $this->error[$key];
					$this->error[$key] = array($temp);
				}
				if (is_array($this->error[$key]))
				{
					if (is_array($value)) 
					{
						foreach ($value as $val) {
							$this->error[$key][] = $val;
						}
					}
					else
					{
						$this->error[$key][] = $value;
					}
				}
			}
			else
			{
				$this->error[$key] = $value;
			}
		}
		$class = str_replace('\\', '_', get_class($this));
		if (!array_key_exists($class, \System::$errors))
		{
			\System::$errors[$class] = array();
		}

		\System::$errors[$class] = array_merge(\System::$errors[$class], $this->error);
	}

	static function errorTranscend($value='')
	{
		$_SESSION['errortranscend'] = $value;
	}

	static function errorMaterialize($value='')
	{
		if (array_key_exists('errortranscend', $_SESSION))
		{
			$temp = $_SESSION['errortranscend'];
			unset($_SESSION['errortranscend']);
			return $temp;
		}
		return false;		
	}
			

	/**
	 * Clear error holding variable
	 * @return boolean true
	 */
	public function errorClear()
	{
		$this->error = array();
		return true;
	}

	/**
	 * Get any errors previously created
	 * @param  string $mutate effects the keys for returned values
	 * @param  string $method how to mutate, prefix, suffix, ""(replace)
	 * @return array all stored errors in the current object
	 */
	public function errorGet($mutate = "", $method = "")
	{
		if (empty($mutate))
		{
			return $this->error;
		}
		else
		{
			$errorstemp = array();
			array_walk($this->error, function ($value, $index) use (&$errorstemp, $mutate, $method)
			{
				switch ($method) 
				{
					case 'prefix':
						$mutate = $mutate . $index;
						break;

					case 'suffix':
						$mutate = $index . $mutate;
						break;
				}
				$errorstemp[$mutate] = $value; 
			});
			return $errorstemp;
		}
	}

	/**
	 * Get a definition of the currently used table
	 * @return $this->tabledefinition array of DESC table
	 */
	public function inspectTable()
	{
		if (defined('static::TABLENAME'))
		{
			$this->tabledefinition = array_map(function ($value)
			{
				$return = array();

				$return['type'] = $value;

				if (preg_match('/(set|enum)\((.*)\)/', $value, $matches))
				{
					$return['option'] = array_map(function ($value)
					{
						// return ucwords( trim($value, "'") );
						return trim($value, "'");
					}, explode(',', $matches[2]));
				}

				$return['length'] = $matches[2];

				return $return;
			}, \R::inspect(static::TABLENAME));
		}
	}

	/**
	 * Request the definition for a specific field
	 * @param string $column select column to return
	 * @param string $options return only options
	 * @return [type]         [description]
	 */
	public function getTableDefinition($column = "", $options = false)
	{
		$def = array_pop(array_filter($this->tabledefinition, function (&$value) use ($column)
		{
			return $column == $value['Field'];
		}));

		if (true === $options)
		{
			return $def['Options'];
		}
		elseif (is_string($options) && array_key_exists($options, $def))
		{
			return $def[$options];
		}
		return $def;
	}

	/**
	 * autostamp - automatically stamps a template using all getAccesses
	 * @param  \StampTE $stamp template to stamp (reference)
	 */
	public function autostamp(\StampTE &$stamp)
	{
		$default = array_map('trim', explode(',', static::GETACCESS));
		foreach ($default as $value) 
		{
			$stamp->inject($value, $this->{$value});
		}
	}

	/**
	 * sqlColumns - get a list of columns and prefix them
	 * @param  string $prefix prefix to add
	 * @return string         
	 */
	static function sqlColumns($prefix = null, $view = false)
	{
		if (is_string($view)) 
		{
			$table = constant("static::{$view}NAME");
			$columns = constant("static::{$view}COLS");
		}
		elseif (is_bool($view) && $view)
		{
			$table = static::VIEWNAME;
			$columns = static::VIEWCOLS;
		}
		else
		{
			$table = static::TABLENAME;
			$columns = static::TABLECOLS;
		}

		if (empty($columns) && empty($table))
		{
			return "*";
		}
		elseif (empty($columns))
		{
			return "`{$table}`.*";
		}
		
		return implode(', ', array_map(function ($value) use ($prefix, $table)
		{
			$value = trim($value);

			if ($prefix)
			{
				return "\r\n`{$table}`.`{$value}` AS {$prefix}{$value}";
			}

			return "\r\n`{$table}`.`{$value}`";
		}, explode(',', $columns)));
	}

	/**
	 * Return list of 
	 * @return array An array containing all the non deleted objects
	 */
	static function getList()
	{	
		$beans = \R::find(static::TABLENAME, " deleted = :deleted", 
			array(":deleted" => 0));
		
		array_walk($beans, function (&$bean)
		{
			$bean = $bean->getProperties();
		});

		return spincrm::buildTree($beans);
	}

	public function getListByType($type)
	{
		$beans = \R::find(static::TABLENAME, " deleted = :deleted AND type = :type", 
			array(":deleted" => 0, ":type" => $type));

		array_walk($beans, function (&$bean)
		{
			$bean = $bean->getProperties();
		});

		return spincrm::buildTree($beans);
	}

	/**
	 * getEnabled - Return a list of enabled objects
	 * @return [type] [description]
	 */
	static function getEnabled()
	{	
		$beans = \R::find(static::TABLENAME, " deleted = :deleted AND enabled = :enabled", 
			array(":deleted" => 0, ":enabled" => 1));
		array_walk($beans, function (&$bean)
		{
			$bean = $bean->getProperties();
		});

		return spincrm::buildTree($beans);
	}

	/**
	 * runonce - part of an object loader, prevents code in postload from running more than once
	 * @return boolean
	 */
	final protected function runonce()
	{
		if (false === $this->runonce)
		{
			$this->runonce = true;
			return true;
		}
		return false;
	}

	static function invoke_class($type, $id = false)
	{
		$class = "\\crm\\". $type;
		if (!class_exists($class)) 
		{
			throw new \Exception("Class does not exist");
			exit;
		}

		if ( $id && is_numeric($id) )
		{
			return $class::withid($id);
		}
		return new $class();
	}

	/**
	 * Build a parent/child tree
	 * @param  array   $elements  list of objects with internal parentid reference
	 * @param  integer $searchid an ID to search for, can be used to create a path to a specific id
	 * @return array/object            created tree with, if specified, an expansion path
	 */
	static function buildTree(array $elements, $searchid=0, $depth = 0)
	{
		/**
		 * $createTree - Creates a tree from leaves
		 * @param  array  $elements   a list of elements with parentid
		 * @param  int  $parentid   Current parentid search for, set to non-zero to return a sub-tree
		 * @param  int  $searchid   allows you to highlight a leaf and all of it's ancestors
		 * @param  int $depth       marks how many ancestors a leaf has
		 * @return array/object     Completed tree
		 */
		$createTree = function (&$elements, $parentid, $searchid, $depth) use (&$createTree)
		{
			// Scan all elements, return an array of elements with a matched parentid
			// 
			$children = array_filter($elements, function (&$value) use ($parentid)
			{
				if (is_object($value) && property_exists($value, 'parentid'))
				{
					return $parentid == $value->parentid;
				}
				elseif (is_array($value) && array_key_exists('parentid', $value))
				{
					return $parentid == $value['parentid'];
				}
			});

			// Map the selected children
			// Give all siblings a depth, expandpath and if fertile
			// 
			$children = array_map(function ($value) use ($createTree, $parentid, &$elements, $searchid, $depth)
			{
				unset($elements[array_search($value, $elements)]);

				if (is_object($value) && ((is_null($value->parentid) || 0 == $value->parentid) || $depth > 0))
				{
					$value->depth = $depth;
					if ($value->id == $searchid)
					{
						$value->expandpath = true;
					}
					
					// Find the children from the maze
					// 
					$value->children = $createTree($elements, $value->id, $searchid, $depth + 1);

					// If gene is false, search the children
					// If children have gene, make sure the parent has it too
					// 
					$value->expandpath || $value->expandpath = array_reduce($value->children, function ($output, $value)
					{
						return (bool)$output || $value->expandpath;
					});

				}
				elseif (is_array($value) && ((is_null($value['parentid']) || 0 == $value['parentid']) || $depth > 0))
				{
					$value['depth'] = $depth;
					if ($value['id'] == $searchid)
					{
						$value['expandpath'] = true;
					}

					$value['children'] || $value['children'] = $createTree($elements, $value['id'], $searchid, $depth + 1);
					$value['expandpath'] = array_reduce($value['children'], function ($output, $value)
					{
						return (bool)$output || $value['expandpath'];
					});

				}

				return $value;
			}, $children);

			return $children;
		};

		// Prepare the elements with the default values
		array_walk($elements, function (&$value)
		{
			if (is_object($value))
			{
				$value->expandpath = false;
				$value->depth = 0;
				$value->children = array();
			}
			elseif (is_array($value))
			{
				$value['expandpath'] = false;
				$value['depth'] = 0;
				$value['children'] = array();
			}
		});

		$tree = $createTree($elements, 0, $searchid, $depth);
		if (empty($tree))
		{
			return $elements;
		}
		return $tree;
	}
}

?>