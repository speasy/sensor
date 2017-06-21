<?php

declare(strict_types = 1);

require __DIR__ . '/../core/_inc/cfg.php';

use \core\ctrl\socket;

socket::udp_server();