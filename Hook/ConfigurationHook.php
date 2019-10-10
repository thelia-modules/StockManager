<?php

namespace StockManager\Hook;

use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;

class ConfigurationHook extends BaseHook
{
    public function onConfigurationOrderPathTop(HookRenderEvent $event)
    {
        $event->add($this->render(
            'stock-manager/configuration-hook.html'
        ));
    }
}