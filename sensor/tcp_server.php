<?php

declare(strict_types = 1);

require __DIR__ . '/../core/_include/cfg.php';

load_lib('core', 'ctrl_socket');

\ctrl_socket::tcp_server();