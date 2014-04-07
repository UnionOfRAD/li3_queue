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

### AMQP interface

#### Configuration

Configuration for your queue will go in `app/config/bootstrap/queues.php` and can contain any of the following options:

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
        'autoAck' => false
    )
));
```

Additional notes:

1. `routingKey` when `null` will be set by default to the same value as `queue`, setting the routing key will only be needed in advanced configurations

2. `autoAck` is a global way to enable `AUTO_ACK` when reading messages. If this is true messages will be automatically acknowledged on the server and whenever you use `Queue::read()` you will not need to follow it with `Queue::ack()`.

#### Usage

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
    Queue::confirm('default');
    ```

    Or requeue your message using:

    ```php
    Queue::requeue('default');
    ```

4. Consume messages

    ```php
    Queue::consume('default', function($message) {
        // Do something with message
        if($success) {
            // Return true to acknowledge success
            return true;
        }
        // Return false to requeue message
        return false;
    });
    ```

    Consuming messages is a blocking action which will retrieve the next available message and pass it off to the callback. Once you have dealt with the message you can acknowledge it with `return true` or requeue it with `return false`.

### Beanstalk interface

#### Usage

1. Add a job

    ```php
    $task = array(
        'foo' => 'bar'
    );
    $options = array(
        'tube' => 'preview',
        'priority' => 9,
        'delay' => 30
    );

    $jobId = Queue::add($task, $options);
    ```

2. Retreive & run a job

    ```php
    $options = array(
        'tube' => 'preview',
        'timeout' => 60
    );
    $job = Queue::run($options);
    ```

2. Delete a job

    ```php
    $queue = Queue::adapter('default');
    $success = $queue->delete($jobId);
    ```

3. Get stats

    ```php
    $queue = Queue::adapter('default');
    $stats = $queue->statistics();
    ```

* Check [source](https://github.com/UnionOfRAD/li3_queue/blob/master/extensions/adapter/queue/Beanstalk.php) for additional configuration.


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
