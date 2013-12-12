<?php 

namespace crm;

/**
* password management
*/
final class password extends spincrm
{
	const	GETACCESS	= "";
	const	SETACCESS	= "";
	const	TABLENAME	= "";
	const	VIEWNAME	= "";

	
	// TOKEN and SALT must be at least 16 characters long
	// 
	const	TOKEN		= "356a192b7913b04c";
	const	SALT		= "2hsdf89hadf3ghqr";

	// const	CONSTRAINTS	= "upper|lower|number|symbol|break|length";
	const	CONSTRAINTS	= "upper|lower|length";


	// Function Properties
	// 
	private $decoded	= null;
	private $salt		= null;
	private	$encoded	= null;
	private $matched	= null;
	private $disabled	= false;

	private static $constraints	= array(
		"length" => array(
			"regex" => '/^.{8,}$/',
			"error" => 'Password requires at least 8 characters'
		),
		"upper" => array(
			"regex" => '/[A-Z]+/',
			"error" => "Password requires at least 1 capital character.",
		),
		"lower" => array(
			"regex" => '/[a-z]+/',
			"error" => "Password requires at least 1 lower character.",
		),
		"number" => array(
			"regex" => '/[0-9]+/',
			"error" => "Password requires at least 1 number.",
		),
		"symbol" => array(
			"regex" => '/\W+/',
			"error" => "Password requires at least 1 symbol.",
		),
		"break" => array(
			"regex" => '/\S+\s+\S+.*/',
			"error" => "Password requires at least 2 words.",
		),
	);


	// Magic Methods
	// Sure we extend spincrm, but we want to overwrite a bunch of these functions
	// 
	public function __unset($name)
	{
		$this->__set($name, null);
		return false;
	}

	public function __set($name, $value)
	{
		throw new \Exception("Class is immutable. ". get_class() ."(". get_called_class() ."->{$name})");
		return null;
	}

	public function __get($name)
	{
		throw new \Exception("Class is inaccessible. ". get_class() ."(". get_called_class() ."->{$name})");
		return null;
	}

	public function __clone()
	{
		throw new \Exception("Class is not cloneable. ". get_class() ."(". get_called_class() .")");
		return false;	
	}

	public function __set_state(array $properties)
	{
		throw new \Exception("Class is not exportable. ". get_class() ."(". get_called_class() .")");
		return false;
	}

	public function __sleep()
	{
		throw new \Exception("Class is not sleepable. ". get_class() ."(". get_called_class() .")");
		return false;
	}

	public function __wakeup()
	{
		throw new \Exception("Class is not wakeable. ". get_class() ."(". get_called_class() .")");
		return false;
	}

	public function __invoke()
	{
		throw new \Exception("Class is not invokable. ". get_class() ."(". get_called_class() .")");
		return false;
	}

	/**
	 * __construct - Construct a password object, password must be provided at this stage
	 * @param string $pass password to encrypt
	 */
	public function __construct($pass=null, $encode=true)
	{
		if ($encode) 
		{
			if (empty($pass))
			{
				return;
			}

			$this->decoded = $pass;
			$this->salt();
			$this->encrypt($pass);
		}
		else
		{
			$this->encoded = $pass;
			$this->disabled = true;
		}
	}

	/**
	 * __toString - Return the encrypted password
	 * @return string the encrypted password
	 */
	public function __toString()
	{
		if (!$this->isEmpty() || $this->disabled)
		{
			return substr($this->encoded, 0, 80);
		}
		return "";
	}

	// Methods
	//
	/**
	 * salt - Create a salt of at least 16 characters
	 * @param  string $salt user defined salt
	 */
	private function salt($salt="")
	{
		$salt = substr($salt.self::SALT, 0, 16);
		$this->salt = $salt;
	}

	/**
	 * encrypt - encrypt the password using the default salt or provided salt or some combination of.
	 */
	private function encrypt()
	{
		if (strlen($this->salt) < 16)
		{
			throw new \Exception("Password token length is insufficient, cannot encrypt.");
		}
		$secure = crypt($this->decoded, '$6$rounds=5000$'. $this->salt .'$');
		$secure = explode('$', $secure);
		$this->encoded = array_pop($secure);
	}

	/**
	 * setSalt - Update the default salt and re-encrypt
	 * @param string $salt salt to use
	 */	
	public function setSalt($salt)
	{
		$this->salt($salt);
		$this->encrypt();
	}

	/**
	 * isEmpty - has the object been used
	 * @return boolean  false if object has been used
	 */
	public function isEmpty()
	{
		return (empty($this->encoded) || empty($this->decoded) || empty($this->salt));
	}

	/**
	 * match - does this password object match another password object
	 * @param  password $password another password object
	 * @return boolean            true if passwords match
	 */
	public function match(password $password)
	{
		return (string)$password === (string)$this;
	}

	/**
	 * valid - does the password meet the defined constraints
	 * @return booelan true if password meets the defined and selected constraints
	 */
	public function valid()
	{
		$valid = true;
		$constraints = explode('|', self::CONSTRAINTS);

		if ($this->isEmpty()) { $valid = false; $this->errorAdd(array('pass' => "Password is empty. ")); }
		
		foreach ($constraints as $value) 
		{
			if (array_key_exists($value, self::$constraints) && !preg_match(self::$constraints[$value]['regex'], $this->decoded))
			{
				$valid = false;
				$this->errorAdd(array('pass' => self::$constraints[$value]['error'] ));
			}
		}

		return $valid;
	}

	/**
	 * plain - return the plain password
	 * @return string password used in constructor
	 */
	public function plain()
	{
		if (!empty($this->decoded))
		{
			return $this->decoded;
		}
		else
		{
			throw new \Exception("Cannot output password");
			exit;
		}
	}

	/**
	 * explain - return a list of applied constraints
	 * @return array an array of constraints
	 */
	static function explain()
	{
		$constraints = explode('|', self::CONSTRAINTS);
		$return = array();
		foreach ($constraints as $value) 
		{
			$return[] = self::$constraints[$value]['error'];
		}
		return $return;
	}

	/**
	 * generate - create a plain password using constraints
	 * @param int $length length of password to generate
	 * @return string a plain password
	 */
	static function generate($length = 8)
	{
		$string = "";
		$constraints = array_unique(
			array_merge(explode('|', self::CONSTRAINTS), array('upper', 'lower', 'number'))
		);

		$selection	= array(
			"upper" => "ABCDEFGHJKMNPQRSTUVWXYZ",
			"lower" => "abcdefghjkmnpqrstuvwxyz", 
			"number" => "23456789",
			"symbol" => "!@#$%^&*()_+{}<>?|[];",
			"break" => " "
		);

		$use = array_intersect_key($selection, array_flip($constraints));
		while (list($key, $var) = each($use)) {
			$string .= str_pad("", ceil($length / count($use)), str_shuffle($var));
		}
		return substr(str_shuffle($string), 0, $length);
	}
}


?>