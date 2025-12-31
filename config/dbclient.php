<?php
return [
  'middleware' => ['web', 'auth'],
  'read_only' => false,

  'allowed_commands' => [
    'migrate',
    'migrate:status',
    'migrate:rollback',
    'db:seed',
  ],
];
