# meteor-ddp-php 
   A [minimalist](http://www.becomingminimalist.com/) PHP library that implements DDP client, the realtime protocol for [Meteor](https://www.meteor.com/ddp) framework.

### Requirements : pthreads
   pthreads is not part of php core library, and installing it in an non-supported php environment is hard work. Here's a pretty good tutorial on how to set up the whole thing from scratch : 
   http://eddmann.com/posts/compiling-php-5-5-with-zts-and-pthreads-support/
     
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

Then in your php client's code, you could invoke `foo` by executing :
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


### Roadmap
* nicer documentation & examples (include meteor example app)
* subscribe()
* asyncCall()
* handle errors properly
    
### Version
1.0.0-beta 