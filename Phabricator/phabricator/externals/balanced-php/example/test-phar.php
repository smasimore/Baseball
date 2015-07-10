<?php

// run this file to test your phar install of Balanced

include_once(__DIR__ . '/httpful.phar');
include_once(__DIR__ . '/restful.phar');
include_once(__DIR__ . '/balanced.phar');

echo "[ OK ]\n";
echo "balanced version -- " . \Balanced\Settings::VERSION . " \n";
echo "restful version -- " . \RESTful\Settings::VERSION . " \n";
echo "httpful version -- " . \Httpful\Httpful::VERSION . " \n";
