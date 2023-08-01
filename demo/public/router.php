<?php declare(strict_types=1);

namespace Reactor\Demo;

use Reactor\Reactor;

include(dirname(__FILE__) . '/../../vendor/autoload.php');

$reactor = new Reactor();
$reactor->run();
