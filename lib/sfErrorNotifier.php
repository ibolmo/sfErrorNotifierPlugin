<?php

/*
 * (c) 2008 Daniele Occhipinti
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
    $to = sfConfig::get('app_sfErrorNotifier_email');
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
    $body = "Exception notification for {$_SERVER['HTTP_HOST']}, environment $env - " . date('H:i:s j F Y'). "\n\n";
    $body .= $exception . "\n\n\n\n\n";
    $body .= "Additional data: \n\n";
    foreach($data as $key => $value)
    {
      $body .= $key . " => " . $value . "\n\n";
    }

    mail($to, $subject, $body);
  }
}
