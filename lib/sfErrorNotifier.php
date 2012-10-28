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

	static function notifyException(Exception $exception)
	{
		if ($exception instanceof sfStopException) return;
		if (!$to = sfConfig::get('app_sf_error_notifier_plugin_email_to')) return;

		self::alert($exception->getMessage(), 'Exception', array(
			'name'    => get_class($exception),
			'traces'  => self::getTraces($exception, 'html')
		));
	}

	static function alert($message, $type = 'Alert', $data = array())
	{
		if (!$to = sfConfig::get('app_sf_error_notifier_plugin_email_to')) return;
    if (!sfContext::hasInstance()) return;

		$context = sfContext::getInstance();
		$configuration = $context->getConfiguration();

    $request = $context->getRequest();
    $url = (
      ($request instanceof sfWebRequest)
        ? $request->getUri()
        : null
    );

		$data = array_merge(array(
			'settingsTable' => sfYaml::dump(sfDebug::settingsAsArray()),
      'requestTable'  => sfYaml::dump(sfDebug::requestAsArray($context->getRequest())),
      'responseTable' => sfYaml::dump(sfDebug::responseAsArray($context->getResponse())),
      'userTable'     => sfYaml::dump(sfDebug::userAsArray($context->getUser())),
      'globalsTable'  => sfYaml::dump(sfDebug::globalsAsArray()),
      'type'          => $type,
      'message'       => $message,
      'host'          => gethostname(),
      'environment'   => $context->getConfiguration()->getEnvironment(),
      'url'           => $url ?: '(not available)'
		), $data);

		$placeholders = array_map(function($key){ return "%$key%"; }, array_keys($data));

		$subject = strtr(sfConfig::get('app_sf_error_notifier_plugin_email_subject'), array_combine($placeholders, array_values($data)));

    $configuration->loadHelpers('Partial');
		$body = get_partial('sfErrorNotifier/notify', $data);

		$message = Swift_Message::newInstance()
			->setFrom(sfConfig::get('app_sf_error_notifier_plugin_email_from'))
			->setTo($to)
			->setSubject($subject)
			->setBody($body, sfConfig::get('app_sf_error_notifier_plugin_email_format'));

		if (sfConfig::get('app_sf_error_notifier_plugin_enabled')) $context->getMailer()->send($message);
	}

  /** Extracts backtrace info from an exception.
   *
   * @param Exception $exception  An Exception implementation instance
   * @param string    $format     The trace format (txt or html)
   *
   * @return array An array of traces
   */
  static protected function getTraces($exception, $format = 'txt')
  {
    $traceData = $exception->getTrace();
    array_unshift($traceData, array(
      'function' => '',
      'file'     => $exception->getFile() != null ? $exception->getFile() : null,
      'line'     => $exception->getLine() != null ? $exception->getLine() : null,
      'args'     => array(),
    ));

    $traces = array();
    if ($format == 'html')
    {
      $lineFormat = 'at <strong>%s%s%s</strong>(%s)<br />in <em>%s</em> line %s <br /><ul class="code">%s</ul>';
    }
    else
    {
      $lineFormat = 'at %s%s%s(%s) in %s line %s';
    }

    for ($i = 0, $count = count($traceData); $i < $count; $i++)
    {
      $line = isset($traceData[$i]['line']) ? $traceData[$i]['line'] : null;
      $file = isset($traceData[$i]['file']) ? $traceData[$i]['file'] : null;
      $args = isset($traceData[$i]['args']) ? $traceData[$i]['args'] : array();
      $traces[] = sprintf($lineFormat,
        (isset($traceData[$i]['class']) ? $traceData[$i]['class'] : ''),
        (isset($traceData[$i]['type']) ? $traceData[$i]['type'] : ''),
        $traceData[$i]['function'],
        self::formatArgs($args, false, $format),
        self::formatFile($file, $line, $format, null === $file ? 'n/a' : sfDebug::shortenFilePath($file)),
        null === $line ? 'n/a' : $line,
        self::fileExcerpt($file, $line)
      );
    }

    return $traces;
  }

  /**
   * Returns an excerpt of a code file around the given line number.
   *
   * @param string $file  A file path
   * @param int    $line  The selected line number
   *
   * @return string An HTML string
   */
  static protected function fileExcerpt($file, $line)
  {
    if (is_readable($file))
    {
      $content = preg_split('#<br />#', preg_replace('/^<code>(.*)<\/code>$/s', '$1', highlight_file($file, true)));

      $lines = array();
      for ($i = max($line - 3, 1), $max = min($line + 3, count($content)); $i <= $max; $i++)
      {
        $lines[] = '<li'.($i == $line ? ' class="selected"' : '').'>'.$content[$i - 1].'</li>';
      }

      return '<ol start="'.max($line - 3, 1).'">'.implode("\n", $lines).'</ol>';
    }

    return '(not available)';
  }

  /**
   * Formats an array as a string.
   *
   * @param array   $args     The argument array
   * @param boolean $single
   * @param string  $format   The format string (html or txt)
   *
   * @return string
   */
  static protected function formatArgs($args, $single = false, $format = 'html')
  {
    $result = array();

    $single and $args = array($args);

    foreach ($args as $key => $value)
    {
      if (is_object($value))
      {
        $formattedValue = ($format == 'html' ? '<em>object</em>' : 'object').sprintf("('%s')", get_class($value));
      }
      else if (is_array($value))
      {
        $formattedValue = ($format == 'html' ? '<em>array</em>' : 'array').sprintf("(%s)", self::formatArgs($value));
      }
      else if (is_string($value))
      {
        $formattedValue = ($format == 'html' ? sprintf("'%s'", self::escape($value)) : "'$value'");
      }
      else if (null === $value)
      {
        $formattedValue = ($format == 'html' ? '<em>null</em>' : 'null');
      }
      else
      {
        $formattedValue = $value;
      }

      $result[] = is_int($key) ? $formattedValue : sprintf("'%s' => %s", self::escape($key), $formattedValue);
    }

    return implode(', ', $result);
  }

  /**
   * Formats a file path.
   *
   * @param  string  $file   An absolute file path
   * @param  integer $line   The line number
   * @param  string  $format The output format (txt or html)
   * @param  string  $text   Use this text for the link rather than the file path
   *
   * @return string
   */
  static protected function formatFile($file, $line, $format = 'html', $text = null)
  {
    if (null === $text)
    {
      $text = $file;
    }

    if ('html' == $format && $file && $line && $linkFormat = sfConfig::get('sf_file_link_format', ini_get('xdebug.file_link_format')))
    {
      $link = strtr($linkFormat, array('%f' => $file, '%l' => $line));
      $text = sprintf('<a href="%s" title="Click to open this file" class="file_link">%s</a>', $link, $text);
    }

    return $text;
  }

  /**
   * Escapes a string value with html entities
   *
   * @param  string  $value
   *
   * @return string
   */
  static protected function escape($value)
  {
    if (!is_string($value))
    {
      return $value;
    }

    return htmlspecialchars($value, ENT_QUOTES, sfConfig::get('sf_charset', 'UTF-8'));
  }
}