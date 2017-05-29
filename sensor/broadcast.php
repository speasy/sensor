<?php

declare(strict_types = 1);

require __DIR__ . '/../core/_include/cfg.php';

load_lib('sensor', 'sensor');

\sensor::init();
\sensor::broadcast();