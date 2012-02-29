# Lithium Queue Plugin #
by `Christopher Garvis` & `Olivier Louvignes`


### Description

This plugin provides a simple way to handle work queues, it currently supports:

* [Beanstalk](http://kr.github.com/beanstalkd/)


### Installation

1. To enable the library add the following line at the end of `app/config/bootstrap/libraries.php`:

        Libraries::add('li3_queue');

2. Then configure your queues in `app/config/bootstrap/queues.php`:

        use li3_queue\Queue;

        Queue::config(array('default' => array(
            'adapter' => 'Beanstalk',
            'host' => '127.0.0.1',
            'port' => 11300
        )));

3. Update `app/config/bootstrap.php` to include this new configuration file:

        /**
         * Include this file if your application uses one or more queues.
         */
        require __DIR__ . '/bootstrap/queues.php';

4. You can now use your configured queues in your application:

        use li3_queue\Queue;


#### Beanstalk interface

1. Add a job

        $task = array(
            'foo' => 'bar'
        );
        $options = array(
            'tube' => 'preview',
            'priority' => 500,
            'delay' => 5
        );

        $jobId = Queue::add($task, $options);

2. Retreive & run a job

        $options = array(
            'tube' => 'preview',
            'timeout' => 60
        );
        $job = Queue::run($options);

2. Delete a job

        $queue = Queue::adapter('default');
        $success = $queue->delete($jobId);

3. Get stats

        $queue = Queue::adapter('default');
        $stats = $queue->statistics();

* Check [source](https://github.com/UnionOfRAD/li3_queue/blob/master-beanstalk/extensions/adapter/queue/Beanstalk.php) for additional configuration.


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
