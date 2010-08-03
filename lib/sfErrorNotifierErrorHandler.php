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
 * @author     Maksim Kotlyar <mkotlar@ukr.net>
 */
class sfErrorNotifierErrorHandler
{
  private static $tmpBuffer = null;

  /**
   * @see handlePhpError
   */
	public static function start()
	{
             $reportFatalErrors = sfConfig::get('app_sfErrorNotifier_reportFatalErrors');

             if (!$reportFatalErrors)
             {
                 return;
             }

		set_error_handler(array(__CLASS__,'handlePhpError'), E_ALL);
		set_exception_handler(array(__CLASS__,'handleException'));
		register_shutdown_function(array(__CLASS__, 'handlePhpFatalError'));
		
            self::_reserveMemory();
	}

	/**
	 * 
	 * @param unknown_type $errno
	 * @param unknown_type $errstr
	 * @param unknown_type $errfile
	 * @param unknown_type $errline
	 * 
	 * @throws ErrorException
	 */
	public static function handlePhpError($errno, $errstr, $errfile, $errline)
	{
	  sfErrorNotifier::notifyException(
	   new ErrorException($errstr, 0, $errno, $errfile, $errline));
	} 
	
	public static function handlePhpFatalError()
	{ 
    $lastError = error_get_last();
    if (is_null($lastError)) return;
    
    self::_freeMemory();
    
    $errors = array(
      E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, 
      E_COMPILE_ERROR, E_COMPILE_WARNING, E_STRICT);

    if (in_array($lastError['type'], $errors)) {
       sfErrorNotifier::notifyException(new ErrorException(
         @$lastError['message'], @$lastError['type'], @$lastError['type'], 
         @$lastError['file'], @$lastError['line']));
    }
	}
	
  public static function handleException($e)
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