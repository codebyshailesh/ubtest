<?php
require_once __DIR__ . '/includes/auth_middleware.php';
destroy_auth_token();
redirect('/');
