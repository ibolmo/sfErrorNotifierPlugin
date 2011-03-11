<?php

/**
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package    symfony
 * @subpackage plugin
 * @author		 Maksim Kotlyar <mkotlar@ukr.net>
 */
class sfErrorNotifierErrorHandler
{
	private static $tmpBuffer = null;
	
	static function start()
	{
		$reportPHPErrors = sfConfig::get('app_sf_error_notifier_plugin_report_errors');
		
		if ($reportPHPErrors) set_exception_handler(array(__CLASS__, 'handleException'));
		if ($reportPHPErrors || sfConfig::get('app_sf_error_notifier_plugin_report_warnings')){
			// set_error_handler and register_shutdown_function can be triggered on
			// both warnings and errors
			set_error_handler(array(__CLASS__, 'handlePhpError'), E_ALL);
			// From PHP Documentation: the following error types cannot be handled with
			// a user defined function using set_error_handler: *E_ERROR*, *E_PARSE*, *E_CORE_ERROR*, *E_CORE_WARNING*, *E_COMPILE_ERROR*, *E_COMPILE_WARNING*
			// That is we need to use also register_shutdown_function()
			register_shutdown_function(array(__CLASS__, 'handlePhpFatalErrorAndWarnings'));
		}

		self::_reserveMemory();
	}
	
	static function handlePhpError($errno, $errstr, $errfile, $errline)
	{
		# there would be more warning codes but they are not caught by set_error_handler but by register_shutdown_function
		$warningsCodes = array(E_NOTICE, E_USER_WARNING, E_USER_NOTICE, E_STRICT);

		# E_DEPRECATED, E_USER_DEPRECATED have been introduced in PHP 5.3
		if (defined('E_DEPRECATED')) $warningsCodes[] = E_DEPRECATED;
		if (defined('E_USER_DEPRECATED')) $warningsCodes[] = E_USER_DEPRECATED;

		if(sfConfig::get('app_sf_error_notifier_plugin_report_warnings') || !in_array($errno, $warningsCodes)){
			sfErrorNotifier::notifyException(new ErrorException($errstr, 0, $errno, $errfile, $errline));
		}

		return false; # in order not to bypass the standard PHP error handler
	}

	static function handlePhpFatalErrorAndWarnings()
	{
		self::_freeMemory();

		$error = error_get_last();
		if (is_null($error)) return;

		$errors = array();
		
		if (sfConfig::get('app_sf_error_notifier_plugin_report_errors')) $errors = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR);
		if (sfConfig::get('app_sf_error_notifier_plugin_report_warnings')) $errors = array_merge($errors, array(E_CORE_WARNING, E_COMPILE_WARNING, E_STRICT));

		$type = $message = $file = $line = null;
		extract($error);
		
		if (in_array($type, $errors)) sfErrorNotifier::notifyException(new ErrorException($message, $type, $type, $file, $line));
	}

	static function handleException($e)
	{
		sfErrorNotifier::notifyException($e);
	}

	/**
	 * This allows to catch memory limit fatal errors.
	 */
	protected static function _reserveMemory()
	{
		self::$tmpBuffer = str_repeat('x', 1024 * 500);
	}

	protected static function _freeMemory()
	{
		self::$tmpBuffer = '';
	}
}
