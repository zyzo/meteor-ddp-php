# meteor-ddp-php 
   A [minimalist](http://www.becomingminimalist.com/) PHP library that implements DDP client, the realtime protocol for [Meteor](https://www.meteor.com/ddp) framework.
  
### How to use

Suppose you have declared a remote function `foo` in your meteor server code :
```javascript
Meteor.methods({
  foo : function (arg) {
    check(arg, Number);
    if (arg == 1) { return 42; }
    return "You suck";
  }
});
```

Then in your php client's code, you could just invoke `foo` by executing :
```php
use zyzo\MeteorDDP\DDPClient;

$client = new DDPClient('localhost', 3000);

$client->connect();

$client->call("foo", array(1));
while(($a = $client->getResult("foo")) === null) {};

echo 'Result = ' . $a . PHP_EOL;

$client->stop();
```

===> 
```
Result = 42
```

More use cases can be found in the [examples](https://github.com/zyzo/meteor-ddp-php/tree/devel/examples) folder.
### How to install
   This library is available via [composer](https://packagist.org/packages/zyzo/meteor-ddp-php), the dependency manager for PHP. Please add this in your composer.json :
```php
"require" : {
    "zyzo/meteor-ddp-php": "1.0.0"
}
```
  and update composer to automatically retrieve the package :
```shell
php composer.phar update
```

### Requirements : pthreads
   pthreads is not part of php core library, and installing it in an non-supported php environment is hard work. Here's a pretty good tutorial on how to set up the whole thing from scratch : 
   http://eddmann.com/posts/compiling-php-5-5-with-zts-and-pthreads-support/


### Roadmap
  None (maybe subscribe() with callback method - if there's enough requests)

> ####_Javascript for the server, PHP for the client_.

### Version
1.0.0 