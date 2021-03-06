<?php
require(ROOT . '/core/ClassLoader.php');

// Define constants for application specific directories
define('ACTIONS_PATH', APP_PATH . '/actions');
define('MODELS_PATH', APP_PATH . '/models');
define('VIEW_PATH', APP_PATH . '/views');

/**
 * This class represents the configuration of the Kolibri framework.
 *
 * All configuration variables are easily available through the static methods of this class.
 */
class Config {
	/*
	 * Constants for the different environment modes an application can be in.
	 * Development is default, but can be changed through KOLIBRI_MODE environment variable.
	 */
	const PRODUCTION = 'production';
	const DEVELOPMENT = 'development';
	const TEST = 'test';

	/*
	 * Default charset. Can be overridden application configuration.
	 * @var string
	 */
	const CHARSET = 'utf-8';

	/**
	 * Current environment mode.
	 * @var string
	 */
	private $mode;

	/**
	 * General configuration settings.
	 * @var array
	 */
	private $config;

	/**
	 * Name of core classes mapped to their class files, providing a fast lookup index
	 * for the autoloader.
	 * @var array
	 */
	private $autoloadClasses;

	/**
	 * Name of interceptors and interceptor stacks mapped to interceptor classes.
	 * @var array
	 */
	private $interceptorClasses;

	/**
	 * Settings for interceptors.
	 * @var array
	 */
	private $interceptorSettings;

	/**
	 * Prepared list of URIs to unique set of interceptors.
	 * @var array
	 */
	private $interceptorMappings;

	/**
	 * Defines the validation classes.
	 * @var array
	 */
	private $validationClasses;

	/**
	 * Defines validation error messages.
	 * @var array
	 */
	private $validationMessages;

	/**
	 * Singleton instance of this class.
	 * @var Config
	 */
	private static $instance;

	/**
	 * Private constructor which initializes the configuration. It is defined private as all
	 * interaction with this class goes through static methods.
	 */
	private function __construct ($mode) {
		$this->mode = $mode;

		require(ROOT . '/conf/autoload.php');
		require(ROOT . '/conf/interceptors.php');
		require(ROOT . '/conf/validation.php');
		require(ROOT . '/core/ConfigHelper.php');

		// Cache the autoload class index
		$this->autoloadClasses = $autoloadClasses;

		// Load relevant app configuration depending on current environment mode
		$helper = new ConfigHelper($this->mode, $interceptors);
		$this->config = $helper->loadApp();

		/*
		 * Extract all interceptor configurations from the loaded application
		 * configuration. These configurations are irrelevant as normal configuration
		 * values. They are instead merged with the default configuration and compiled
		 * internally for use with the Dispatcher.
		 */
		if (isset($this->config['interceptors.classes'])) {
			$helper->addInterceptorClasses($this->config['interceptors.classes']);
			unset($this->config['interceptors.classes']);
		}
		if (isset($this->config['interceptors.stacks'])) {
			$appInterceptorStacks = $this->config['interceptors.stacks'];
			unset($this->config['interceptors.stacks']);
		}
		else $appInterceptorStacks = array();

		if (isset($this->config['interceptors.settings'])) {
			$appInterceptorSettings = $this->config['interceptors.settings'];
			unset($this->config['interceptors.settings']);
		}
		else $appInterceptorSettings = array();

		if (isset($this->config['interceptors'])) {
			$appInterceptorMappings = $this->config['interceptors'];
			unset($this->config['interceptors']);
		}
		else $appInterceptorMappings = array();

		// Compile single index of interceptor classes, default stacks and application stacks
		$this->interceptorClasses = $helper->prepareInterceptors($interceptorStacks,
				$appInterceptorStacks);
		// Merge default interceptor settings with any application specific settings
		$this->interceptorSettings = $helper->prepareInterceptorSettings($interceptorSettings,
				$appInterceptorSettings);
		// Flatten stacks and filter down to a unique list of interceptors for each URI
		$this->interceptorMappings =
				$helper->prepareInterceptorMappings($appInterceptorMappings);

		// Store validation configuration from conf/validation.php
		$this->validationClasses = $validators;
		$this->validationMessages = $validationMessages;
	}

	/**
	 * Initializes PHP settings based on the current configuration. This is done separately
	 * from the constructor to support initalization after loading a stored instance
	 * of the configuration (ie. serialized).
	 */
	private function init () {
		/*
		 * Initialize the Kolibri class autoloader with classname-to-file mappings from
		 * conf/autoload.php
		 */
		ClassLoader::initialize($this->autoloadClasses);

		$incPath = ROOT . '/lib';
		if (file_exists(APP_PATH . '/lib')) {
			$incPath .= PATH_SEPARATOR . APP_PATH . '/lib';

			// Adds all directories inside /lib recursively
			$dirIterator = new RecursiveDirectoryIterator(APP_PATH . '/lib');
			$recIterator = new RecursiveIteratorIterator($dirIterator,
					RecursiveIteratorIterator::SELF_FIRST);
			foreach ($recIterator as $path) {
				if ($path->isDir()) {
					$incPath .= PATH_SEPARATOR . $path->getPathname();
				}
			}
		}
		if (isset($this->config['includePath'])) {
			$paths = (array) $this->config['includePath'];
			$incPath .= PATH_SEPARATOR . implode(PATH_SEPARATOR, $paths);
		}
		ini_set('include_path', $incPath . PATH_SEPARATOR . ini_get('include_path'));

		/*
		 * Sets the current locale for date formatting et cetera
		 * XXX: We leave LC_NUMERIC at default, as locales with comma as decimal seperator
		 * will cause SQL queries with floating point values to fail. We should find a better
		 * solution...
		 */
		if (isset($this->config['locale'])) {
			$envLocale = setlocale(LC_NUMERIC, 0);
			setlocale(LC_ALL, $this->config['locale']);
			setlocale(LC_NUMERIC, $envLocale);
		}

		// Set default character set for all multibyte functions.
		if (function_exists('mb_internal_encoding')) {
			mb_internal_encoding(self::getCharset());
		}
	}

	/**
	 * Returns an instance of this class. An existing instance is returned if one exists, else
	 * a new instance is created.
	 *
	 * @return Config
	 */
	public static function getInstance () {
		if (!isset(self::$instance)) {
			$mode = self::getMode();
			// TODO: Unserialize cached config when appropriate depending on mode here
			self::$instance = new Config($mode);
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Returns the environment mode Kolibri is currently running in. If the config has not
	 * been initialized yet, the mode is determined by looking for a KOLIBRI_MODE environment
	 * variable. If KOLIBRI_MODE doesn't exist, the default mode (Config::DEVELOPMENT) is
	 * returned. When the config has been initialized, the cached mode value is returned.
	 *
	 * @return string Either Config::DEVELOPMENT, Config::TEST or Config::PRODUCTION.
	 * @throws Exception If reevaluating mode value and KOLIBRI_MODE is found to contain
	 *                   an unsupported environment mode.
	 */
	public static function getMode () {
		if (!isset(self::$instance)) {
			if (($envMode = getenv('KOLIBRI_MODE')) !== false) {
				if ($envMode == self::PRODUCTION
						|| $envMode == self::DEVELOPMENT
						|| $envMode == self::TEST) {
					return $envMode;
				}
				else {
					throw new Exception("Invalid environment mode in \$KOLIBRI_MODE: '$envMode'");
				}
			}

			return self::DEVELOPMENT;
		}

		$instance = Config::getInstance();
		return $instance->mode;
	}

	/**
	 * Returns the charset, either the one from the config file if exists or default charset
	 * defined as const CHARSET.
	 *
	 * @return string
	 */
	public static function getCharset () {
		$instance = self::getInstance();
		return isset($instance->config['charset']) ?
				$instance->config['charset'] : self::CHARSET;
	}

	/**
	 * Returns the value of the configuration setting with the specified key, or
	 * <code>NULL</code> if not found. If no key is supplied, all settings are returned.
	 *
	 * @param string $key Key of the configuration value to return, or <code>NULL</code> to
	 *                    retrieve all.
	 * @return mixed Configuration variable, <code>NULL</code> og array of all variables.
	 */
	public static function get ($key = null) {
		$instance = Config::getInstance();
		if ($key !== null) {
			return (isset($instance->config[$key]) ? $instance->config[$key] : null);
		}
		return $instance->config;
	}

	/**
	 * Returns the names of the action mappers defined for this application.
	 *
	 * @return mixed Associative array with URIs mapped to action mappers, or
	 *               <code>NULL</code> when none is defined.
	 */
	public static function getActionMappers () {
		$instance = Config::getInstance();
		if (isset($instance->config['actionmappers'])) {
			return $instance->config['actionmappers'];
		}
		return null;
	}

	/**
	 * Returns the interceptor mappings defined for this application.
	 *
	 * @return array Associative array with action paths mapped to interceptor names.
	 */
	public static function getInterceptorMappings () {
		$instance = Config::getInstance();
		return (array) $instance->interceptorMappings;
	}

	/**
	 * Returns any settings defined for a specific interceptor, or all interceptor
	 * settings.
	 *
	 * @param string $name Optional name of interceptor to return settings for.
	 * @return array Associative array with settings for all interceptors or the one
	 *               interceptor asked for.
	 */
	public static function getInterceptorSettings ($name = null) {
		$instance = Config::getInstance();
		if ($name !== null) {
			return (isset($instance->interceptorSettings[$name]) ?
				$instance->interceptorSettings[$name] : null);
		}
		return (array) $instance->interceptorSettings;
	}

	/**
	 * Returns an array of validation configuration.
	 * TODO: Separate fetching of validation classes and messages.
	 *
	 * @return array		Array of validation configuration.
	 */
	public static function getValidationConfig () {
		$instance = Config::getInstance();
		return array(
			'classes' => $instance->validationClasses,
			'messages' => $instance->validationMessages
		);
	}
}
?>
