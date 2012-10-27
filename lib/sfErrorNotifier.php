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
	static function notify(sfEvent $event)
	{
		$e = $event->getSubject();
		return ($e instanceof Exception) ? self::notifyException($e) : null;
	}

	static function notify404(sfEvent $event)
	{
		$e = $event->getSubject();
		if ((!($e instanceof Exception)) && (sfContext::hasInstance())){
			$uri = sfContext::getInstance()->getRequest()->getUri();
			$e = new sfError404Exception("Page not found [404][uri: $uri]");
		}
		return self::notifyException($e);
	}

	static function notifyException($exception)
	{
		if ($exception instanceof sfStopException) return;
		if (!$to = sfConfig::get('app_sf_error_notifier_plugin_email_to')) return;

		$sf_root_dir = sfConfig::get('sf_root_dir');
		self::alert($exception->getMessage(), 'Exception', array(
			'className' => get_class($exception),
			'sf_root_dir' => $sf_root_dir,
			'trace' => str_replace($sf_root_dir, '.', $exception->getTraceAsString())
		));
	}

	static function alert($message, $type = 'Alert', $data = array())
	{
		if (!$to = sfConfig::get('app_sf_error_notifier_plugin_email_to')) return;
    if (!sfContext::hasInstance()) return;

		$context = sfContext::getInstance();
		$configuration = $context->getConfiguration();

		$data = array_merge(array(
			'type' => $type,
			'message' => $message,
			'appName' => $configuration->getApplication(),
			'moduleName' => $context->getModuleName(),
			'actionName' => $context->getActionName(),
			'uri' => $context->getRequest()->getUri(),
			'host' => $_SERVER['HTTP_HOST'],
			'environment' => $configuration->getEnvironment()
		), $data);

		$placeholders = array_map(function($key){ return "%$key%"; }, array_keys($data));

		$subject = strtr(sfConfig::get('app_sf_error_notifier_plugin_email_subject'), array_combine($placeholders, array_values($data)));

    $configuration->loadHelpers('Partial');
		$body = get_partial('sfErrorNotifier/notify', array('data' => $data, 'user' => $context->getUser()));

		$message = Swift_Message::newInstance()
			->setFrom(sfConfig::get('app_sf_error_notifier_plugin_email_from'))
			->setTo($to)
			->setSubject($subject)
			->setBody($body, sfConfig::get('app_sf_error_notifier_plugin_email_format'));

		if (sfConfig::get('app_sf_error_notifier_plugin_enabled')) $context->getMailer()->send($message);
	}
}
