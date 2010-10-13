<?php

/*
 * (c) 2008-2009 Daniele Occhipinti
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package    symfony
 * @subpackage plugin
 * @author     Daniele Occhipinti <>
 */
class sfErrorNotifier
{
  static public function notify(sfEvent $event)
  {
    $e = $event->getSubject();
    if ( $e instanceof Exception )
        return self::notifyException($e);
    return ;
  }
  
  static public function notify404(sfEvent $event)
  {
    $e = $event->getSubject();
    if ( $e instanceof Exception )
        return self::notifyException($e);
    else {
        $uri = sfContext::getInstance()->getRequest()->getUri();
        return self::notifyException(
            new sfError404Exception( "Page not found [404][uri: $uri]")
        );
    }
  }
  
  static public function notifyException($exception)
  {
    // it's not an error.
    if ($exception instanceof sfStopException) {
      return;
    } 
    
    $to = sfConfig::get('app_sfErrorNotifier_emailTo');
    if(! $to) {
      // this environment is not set to notify exceptions
      return; 
    }
    
    $context = sfContext::getInstance();
    $env = 'n/a';
    if ($conf = sfContext::getInstance()->getConfiguration()) {
      $env = $conf->getEnvironment(); 
    }
    
    $data = array();      
    $data['className'] = get_class($exception);
    $data['message'] = !is_null($exception->getMessage()) ? $exception->getMessage() : 'n/a';
    $data['appName'] = $context->getConfiguration()->getApplication();
    $data['moduleName'] = $context->getModuleName();
    $data['actionName'] = $context->getActionName();
    $data['uri'] = $context->getRequest()->getUri();
    $data['serverData'] =  self::getRequestHeaders();
  
    $serverHttpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'unknown http host';
    $subject = "ERROR: $serverHttpHost Exception - $env - {$data['message']}";
    
    $mail = new sfErrorNotifierMail($subject, $data, $exception, $context);
    $mail->notify(sfConfig::get('app_sfErrorNotifier_emailFormat', 'html'));
  }

  static public function alert($alertMessage)
  {
    $to = sfConfig::get('app_sfErrorNotifier_emailTo');
    if(! $to)
    {
      // this environment is not set to notify exceptions
      return; 
    }
	
    $context = sfContext::getInstance();
	  $env = 'n/a';
    if ($conf = sfContext::getInstance()->getConfiguration())
    {
      $env = $conf->getEnvironment(); 
    }

    $data = array();
    $data['moduleName'] = $context->getModuleName();
    $data['actionName'] = $context->getActionName();
    $data['uri'] = $context->getRequest()->getUri();
	
    $serverHttpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'unknown http host';
    $subject = "ALERT: $serverHttpHost - $env - $alertMessage";
    
    $mail = new sfErrorNotifierMail($subject, $data, null, $context);

    $mail->notify(sfConfig::get('app_sfErrorNotifier_emailFormat', 'html'));
  }

  static private function getRequestHeaders()
  {
      $ret = '';

      $newLine = (sfConfig::get('app_sfErrorNotifier_emailFormat') == 'html') ?  '<br />' : '\r\n';

      foreach ($_SERVER as $key => $value)
      {
          $ret .= "$key: $value $newLine";
      }
      return $ret;
  }
}
