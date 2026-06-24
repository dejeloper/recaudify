<?php

return [
    "paths" => ["api/*"],

    "allowed_methods" => ["GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS"],

    "allowed_origins" => [env("FRONTEND_URL", "http://localhost:4200")],

    "allowed_origins_patterns" => ['#^https://.*\.vercel\.app$#'],

    "allowed_headers" => ["Content-Type", "Accept", "X-Requested-With", "Authorization"],

    "exposed_headers" => [],

    "max_age" => 7200,

    "supports_credentials" => true,
];
