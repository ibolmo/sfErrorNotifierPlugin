<?php

/*
 * (c) 2009 Gustavo Garcia
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
class sfErrorNotifierMail{
 
  var $body;
  var $to;
  var $from;
  var $subject;
  var $headers = '';
  var $data = array();
  var $exception;
  var $context;
  var $env;
  
  public function __construct($subject = null, $data = array(), $exception=null, $context = null)
  {
  	$this->to = sfConfig::get('app_sfErrorNotifier_emailTo');
    
    if(!$this->to)
    	return false;
    
    $this->from = sfConfig::get('app_sfErrorNotifier_emailFrom');
    
    if($this->from)
    	$this->headers = "From: ".$this->from."\r\n";
    
    if($subject)
    	$this->subject = $subject;
    else
    	$this->subject = 'Symfony error';
    
    if ($conf = $context->getConfiguration())
    {
      $this->env = $conf->getEnvironment(); 
    }
    
    $this->context = $context;
    $this->data = $data;
    $this->exception = $exception;
  }
  
  private function addRow($th, $td='&nbsp;')
  {
	$this->body .= "<tr style=\"padding: 4px;spacing: 0;text-align: left;\">\n<th style=\"background:#cccccc\" width=\"100px\">$th:</th>\n<td style=\"padding: 4px;spacing: 0;text-align: left;background:#eeeeee\">".nl2br($td)."</td>\n</tr>";  
  } 
  
  private function addTitle($title)
  {
  	$this->body .= '<h1 style="background: #0055A4; color:#ffffff;padding:5px;">'.$title.'</h1>';
  }
  
  private function beginTable()
  {
  	$this->body .= '<table cellspacing="1" width="100%">';
  }
  
  public function notify($format = 'html')
  {
    if(!$this->to)
     	return false;
    if($format == 'html')
    	return $this->notifyHtml();
    return $this->notifyTxt();
  }
  
  private function notifyHtml()
  {
    //Set the content-type in header
    $this->headers .= "Content-type: text/html\r\n";  
    
    //Initialize the body message
    $this->body = '<div style="font-family: Verdana, Arial;">';
        
    //The exception resume  
    $this->addTitle('Resume');
    
    $this->beginTable();
    if ($this->exception)
    {
      $this->addRow('Message',$this->exception->getMessage());
    }
    else
    {
      $this->addRow('Subject',$this->subject);
    }
    $this->addRow('Environment', $this->env);
    $this->addRow('Generated at' , date('H:i:s j F Y'));
    $this->body .= '</table>';
    
    
    //The exception itself
    if ($this->exception)
    {
      $this->addTitle('Exception');
      
      $this->beginTable();
      	$this->addRow('Trace',$this->exception);        	
          
      $this->body .= '</table>'; 
    }
    
    //Aditional Data
    $this->addTitle('Additional Data');
    $this->beginTable();
    foreach($this->data as $key=>$value) 
        $this->addRow($key,$value);
    $this->body .= '</table>'; 
    
    
    //User attributes and credentials
    $this->addTitle('User');
    $this->beginTable();
    	$user = $this->context->getUser();
    	$subtable = array();

	if (is_object($user))
	{
          foreach ($user->getAttributeHolder()->getAll() as $key => $value)
          {
            if (is_array($value))
            {
              $value = 'Array: ' . implode(', ',  $value);
            }
            elseif(is_object($value))
            {
              if(!method_exists($value, "__toString"))
              {
                $value = "Object: ".get_class($value);
              }
            }
            $subtable[] = '<b>'.$key.'</b>: '.$value;
          }

            $subtable = implode('<br/>',$subtable);

            $this->addRow('Attributes',$subtable);
            $userCredentials = method_exists($user, 'listCredentials') ? $user->listCredentials() : $user->getCredentials();
            $this->addRow('Credentials',implode(', ', $userCredentials));
        }
    $this->body .= '</table>';


    $this->body .= '</div>';

    $this->mailer($this->to, $this->subject, $this->body, $this->headers);

    return true;
  }
  
  private function notifyTxt()
  {
    //Set the content-type in header
    $this->headers .= "Content-type: text/plain	\r\n";
    
    $this->body = "Resume:\n";

    if ($this->exception)
    {
      $this->body .= 'Message: ' . $this->exception->getMessage() . "\n";
    }
    else
    {
      $this->body .= 'Subject: ' . $this->subject . "\n";
    }
    $this->body .= 'Environment: ' . $this->env . "\n";
    $this->body .= 'Generated at: ' . date('H:i:s j F Y') . "\n\n";
    
    if ($this->exception)
    {
      $this->body .= "Exception:\n";
      $this->body .= $this->exception . "\n\n";
    }
    
    $this->body .= "Additional Data:\n";
    foreach($this->data as $key=>$value)
    {
    	$this->body .= $key . ': ' . $value . "\n"; 
    }
    $this->body .= "\n\n";
    
    $this->body .= "User Attributes:\n";
    $user = $this->context->getUser();

    if (is_object($user))
    {
        foreach ($user->getAttributeHolder()->getAll() as $key => $value){
          if (is_array($value))
          {
            $value = 'Array: ' . implode(', ',  $value);
          }
          $this->body .= $key . ': ' . $value . "\n";
        }
            $this->body .= "\n\n";

        $this->body .= "User Credentials:\n";
        $userCredentials = method_exists($user, 'listCredentials') ? $user->listCredentials() : $user->getCredentials();
        $this->body .= implode(', ' , $userCredentials);
    }
    $this->body .= "\n\n";
    
    $this->mailer($this->to, $this->subject, $this->body, $this->headers);

    return true;
  }

  private function mailer($to, $subject, $body, $headers)
  {
    $alternativeMailer = sfConfig::get('app_sfErrorNotifier_mailerMethod');

    if (strlen($alternativeMailer))
    {
        $params = array('recipient' => $to,
                        'subject' => $subject,
                        'body' => $body,
                        'headers' => $headers);
        $func = strpos($alternativeMailer, '::') === false ?  $alternativeMailer : explode('::', $alternativeMailer) ;
        call_user_func($func, $params);
    }
    else
    {
        @mail($to, $subject, $body, $headers);
    }
  }
}

