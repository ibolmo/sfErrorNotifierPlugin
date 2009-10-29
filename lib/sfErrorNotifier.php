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
    
    $to = sfConfig::get('app_sfErrorNotifier_emailTo');
    if(! $to)
    {
      // this environment is not set to notify exceptions
      return; 
    }
	
    $exception = $event->getSubject();
    $context = sfContext::getInstance();
	$env = 'n/a';
    if ($conf = sfContext::getInstance()->getConfiguration())
    {
      $env = $conf->getEnvironment(); 
    }

    $data = array();      
    $data['className'] = get_class($exception);
    $data['message'] = !is_null($exception->getMessage()) ? $exception->getMessage() : 'n/a';
    $data['moduleName'] = $context->getModuleName();
    $data['actionName'] = $context->getActionName();
    $data['uri'] = $context->getRequest()->getUri();
	
    $subject = "ERROR: {$_SERVER['HTTP_HOST']} Exception - $env - {$data['message']}";
    
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
	
    $subject = "ALERT: {$_SERVER['HTTP_HOST']} - $env - $alertMessage";
    
    $mail = new sfErrorNotifierMail($subject, $data, null, $context);

    $mail->notify(sfConfig::get('app_sfErrorNotifier_emailFormat', 'html'));
  }
}
