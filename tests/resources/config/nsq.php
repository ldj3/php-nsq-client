<?php
/**
 * nsq config
 * User: moyo
 * Date: 22/12/2016
 * Time: 6:01 PM
 */

return [
    'nsq.testing.config.get' => 'hi',
    'nsq.server.lookupd.lookupd-default' => ['http://127.0.0.1:2'],
    'nsq.server.lookupd.lookupd-dsn-syntax-old' => ['http:127.0.0.1:2'],
    'nsq.server.lookupd.lookupd-dsn-normal' => ['http://127.0.0.1:2'],
    'nsq.server.lookupd.lookupd-dsn-balanced' => ['http://127.0.0.1:2', 'http://127.0.0.1:3'],
];