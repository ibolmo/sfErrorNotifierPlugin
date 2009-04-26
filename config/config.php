<?php

$this->dispatcher->connect('application.throw_exception', array('sfErrorNotifier', 'notify'));
