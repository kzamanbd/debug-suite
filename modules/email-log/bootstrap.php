<?php

use DebugSuite\Modules\EmailLog\Providers\EmailLogServiceProvider;

global $debug_suite_container;

$debug_suite_container->addServiceProvider( new EmailLogServiceProvider() );
