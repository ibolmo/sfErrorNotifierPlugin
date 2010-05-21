<?php

class sfErrorNotifierPluginConfiguration extends sfPluginConfiguration
{
  public function initialize()
  {
    $this->dispatcher->connect(
      'application.throw_exception', array('sfErrorNotifier', 'notify'));
    
    sfErrorNotifierErrorHandler::start();
  }
}