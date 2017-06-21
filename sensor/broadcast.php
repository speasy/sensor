<?php

declare(strict_types = 1);

require __DIR__ . '/../core/_inc/cfg.php';

use \sensor\sensor;

sensor::init();
sensor::broadcast();