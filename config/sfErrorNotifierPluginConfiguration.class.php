<?php

class sfErrorNotifierPluginConfiguration extends sfPluginConfiguration
{
  public function initialize()
  {
    if (!sfConfig::get('app_sf_error_notifier_plugin_enabled')) return;

    $this->dispatcher->connect('application.throw_exception', array('sfErrorNotifier', 'notify'));

    if (sfConfig::get('app_sf_error_notifier_plugin_report_404')) $this->dispatcher->connect('controller.page_not_found', array('sfErrorNotifier', 'notify404'));

    sfErrorNotifierErrorHandler::start();
  }
}
