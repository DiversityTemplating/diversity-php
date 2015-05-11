Diversity engine in PHP
=======================

[![Build Status](https://travis-ci.org/DiversityTemplating/diversity-php.png?branch=master)](https://travis-ci.org/DiversityTemplating/diversity-php)
[![Coverage Status](https://coveralls.io/repos/DiversityTemplating/diversity-php/badge.png)](https://coveralls.io/r/DiversityTemplating/diversity-php)

A PHP engine for Diversity components, by [Textalk](http://www.textalk.com/).

Diversity components search engine optimized (SEO) web components, specified by mustache templates,
supporting dependencies, backend fetching of data over JSON-RPC, supporting AngularJS templating on
the frontend (by doing collected manual bootstrapping).

This component system is constructed for use with [Textalk
Webshop](http://www.textalk.com/webshop), but is in no way limited to
that use.


Using
-----

1. Add composer dependency: `"diversity_templating/diversity-php": "1.0"`

2. "Use" it:
```php
use Diversity\Factory\Api;
use Diversity\Collection;
use SAI\System\Curl;
```

3. Add a component to your page:
```php
$curl = new Curl;
$factory = new Api('https://api.diversity.io/', $curl);
$component = $factory->get('test', '1.2.3');

echo $component->render();
```

4. Use a `Collection` to handle script loading:
```php
$curl = new SAI\System\Curl;
$factory = new Api('https://api.diversity.io/', $curl);
$collection = new Collection;
$collection->add($component = $factory->get('test', '1.2.3'));

?>
<head>
  <? if ($collection->needsAngular()) {?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/angular.js/1.3.15/angular.min.js"></script>
  <? } ?>
  <?= $collection->renderScriptTags() ?>
  <?= $collection->renderStyleTags() ?>
</head>
<body>
  <?= $component->render() ?>
  <?= $collection->renderAngularBootstrap() ?>
</body>
```


Developer install
-----------------

Development depends on php, php-curl and php-xdebug.

```bash
# Will get composer, install dependencies and run tests
make test
```


Changelog
---------

1.0.0

* Using php-SAI 1.0.0.

0.3.0

* Factories are refactored; now you are supposed to instantiate a Factory-subclass, not
  Diversity\Factory directly.

0.2.0

* Handling arrays of styles.


License ([MIT](http://en.wikipedia.org/wiki/MIT_License))
---------------------------------------------------------

Copyright (C) 2014, 2015 Textalk AB <fredrik.liljegren@textalk.se>

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
associated documentation files (the "Software"), to deal in the Software without restriction,
including without limitation the rights to use, copy, modify, merge, publish, distribute,
sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or
substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT
NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT
OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

See COPYING.
