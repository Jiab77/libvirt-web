<?php
/*
MIT License

Copyright (c) 2021 Jonathan Barda

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

// Loading Fat Free Framework if not already loaded
if (!isset($f3)) {
	require 'framework.php';
}

// Defining error handler
$f3->set('ONERROR',
    function($f3) {
        // custom error handler code goes here
        // use this if you want to display errors in a
        // format consistent with your site's theme
		echo '<html><head><title>Error</title></head><body>' . PHP_EOL;
        echo $f3->get('ERROR.text');
		echo '</body></html>' . PHP_EOL;
    }
);

// Defining framework routes
$f3->route('GET /',
    function($f3, $params) {
        require __DIR__ . '/../libvirtweb.php';
    }
);
$f3->route('GET /info',
    function() {
        phpinfo();
    }
);
$f3->route('GET /test',
    function() {
        echo 'Hello, world!';
    }
);

// Run the routing engine
$f3->run();
