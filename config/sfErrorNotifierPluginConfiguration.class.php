<?php

class sfErrorNotifierPluginConfiguration extends sfPluginConfiguration
{
  public function initialize()
  {
    if (!sfConfig::get('app_sfErrorNotifier_enabled', true))
        return;

    $this->dispatcher->connect(
      'application.throw_exception', array('sfErrorNotifier', 'notify'));

    if (sfConfig::get('app_sfErrorNotifier_report404', false)) {
       $this->dispatcher->connect(
          'controller.page_not_found', array('sfErrorNotifier', 'notify404'));
    }

    sfErrorNotifierErrorHandler::start();
  }
}
