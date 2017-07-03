# Lithium Queue Plugin #
by `Christopher Garvis` & `Olivier Louvignes`


### Description

This plugin provides a simple way to handle work queues, it currently supports:

* [AMQP](http://pecl.php.net/package/amqp/)
* [Beanstalk](http://kr.github.com/beanstalkd/)
* [Gearman](http://gearman.org/) in the gearman branch

### Installation

1. To enable the library add the following line at the end of `app/config/bootstrap/libraries.php`:

    ```php
    Libraries::add('li3_queue');
    ```

2. Then configure your queues in `app/config/bootstrap/queues.php`:

    ```php
    use li3_queue\storage\Queue;

    Queue::config(array('default' => array(
        'adapter' => 'Beanstalk',
        'host' => '127.0.0.1',
        'port' => 11300
    )));
    ```

3. Update `app/config/bootstrap.php` to include this new configuration file:

    ```php
    /**
     * Include this file if your application uses one or more queues.
     */
    require __DIR__ . '/bootstrap/queues.php';
    ```

4. You can now use your configured queues in your application:

    ```php
    use li3_queue\storage\Queue;
    ```

5. There is some [known bugs](https://bugs.php.net/60817) with several PHP versions regarding the `stream_get_line` function that can incorrectly fail to return on `\r\n EOL` packets. Unfortunately this bug affects the 12.04 shipped PHP version (php5.3.10-1).

#### Settings

1. If `autoConfirm` is true messages will be automatically confirmed on the server and whenever you use `Queue::read()` or `Queue::consume()`. This means you will not need to use `$message->confirm()` and will be unable to requeue using `$message->requeue()`.

### AMQP interface

#### Configuration

Configuration for your queue will go in `app/config/bootstrap/queues.php` and can contain any of the following options:

##### 1. Basic

```php
Queue::config(array(
    'default' => array(
        'adapter' => 'AMQP',
        'host' => '127.0.0.1',
        'login' => 'guest',
        'password' => 'guest',
        'port' => 5672,
        'vhost' => '/',
        'exchange' => 'li3.default',
        'queue' => 'li3.default',
        'routingKey' => null,
        'autoConfirm' => false,
        'cacert' => null,
        'cert' => null,
        'key' => null,
        'verify' => true
    )
));
```

##### 2. Publish/Subscribe

To configure the AMQP adapter to function as publish/subscribe, you can create multiple queue configs in the following way:

```php
Queue::config(array(
    'publish' => array(
        'adapter' => 'AMQP',
        'exchangeType' => AMQP_EX_TYPE_FANOUT,
        'exchange' => 'li3.publish',
        'queue' => false,
    ),
    'subscribe.1' => array(
        'adapter' => 'AMQP',
        'exchangeType' => AMQP_EX_TYPE_FANOUT,
        'exchange' => 'li3.publish',
        'queue' => 'li3.subscribe.1'
    ),
    'subscribe.2' => array(
        'adapter' => 'AMQP',
        'exchangeType' => AMQP_EX_TYPE_FANOUT,
        'exchange' => 'li3.publish',
        'queue' => 'li3.subscribe.2'
    )
));
```

Additional notes:

1. `routingKey` when `null` will be set by default to the same value as `queue`, setting the routing key will only be needed in advanced configurations

### Beanstalk interface

#### Configuration

Configuration for your queue will go in `app/config/bootstrap/queues.php` and can contain any of the following options:

```php
Queue::config(array(
    'default' => array(
        'adapter' => 'Beanstalk',
        'host' => '127.0.0.1',
        'port' => 11300,
        'tube' => 'default',
        'autoConfirm' => false
    )
));
```

* Check [source](https://github.com/UnionOfRAD/li3_queue/blob/master/extensions/adapter/queue/Beanstalk.php) for additional configuration.

### Usage

1. Write a message

    ```php
    Queue::write('default', 'message');
    ```

2. Read a message

    ```php
    $message = Queue::read('default');
    ```

3. Confirm or requeue a message

    Once you've read a message from the queue you will either need to confirm it's success using:

    ```php
    $message->confirm();
    ```

    Or requeue your message using:

    ```php
    $message->requeue();
    ```

4. Consume messages

    ```php
    Queue::consume('default', function($message) {
        // Do something with message
        if($success) {
            // Confirm message
            $message->confirm();
        }
        // Requeue message
        $message->requeue();
    });
    ```

    Consuming messages is a blocking action which will retrieve the next available message and pass it off to the callback. Returning false in the callback will break out of the consume.

### Bugs & Contribution

Patches welcome! Send a pull request.

Post issues on [Github](https://github.com/UnionOfRAD/li3_queue/issues)


### License

    Copyright (c) 2012, Union of RAD http://union-of-rad.org
    All rights reserved.

    Redistribution and use in source and binary forms, with or without modification,
    are permitted provided that the following conditions are met:

        * Redistributions of source code must retain the above copyright notice,
            this list of conditions and the following disclaimer.
        * Redistributions in binary form must reproduce the above copyright notice,
            this list of conditions and the following disclaimer in the documentation
            and/or other materials provided with the distribution.
        * Neither the name of Lithium, Union of Rad, nor the names of its contributors
            may be used to endorse or promote products derived from this software
            without specific prior written permission.

    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
    ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
    WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
    IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
    INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
    BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
    DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
    OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
    NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
    EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
