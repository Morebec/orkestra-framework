# Orkestra Framework
PHP Framework to develop enterprise level applications using DDD, CQRS and Event Sourcing based on 
the Orkestra Components, Symfony 5.2 and RoadRunner PHP application server.

It is essentially an opinionated distribution of the Orkestra components on top of Symfony using PostgreSQL as a database.

## Features
- Based on Symfony 5.2 providing all of its features
- Orkestra Components are bundled and tightly integrated with Symfony's dependency Injection
- CQRS Support
- Event Sourcing Support
- DDD Support
- PostgreSQL as Database
- Long Running Processes
- [RoadRunner](https://roadrunner.dev/) application server
- Pub/Sub capabilities through messaging and [RoadRunner](https://roadrunner.dev/)
- Ideal for:
  - Web Apps
  - Microservices
  - Backend Systems
  - CLI Apps
- Modern: PHP 7.3+
- Docker + docker-compose setup

## Installation
Clone repository
```shell
git clone https://github.com/Morebec/orkestra-framework
```

Install composer dependencies
```shell
composer install
```

```shell
bin/console orkestra:quickstart
```

## Docker and docker-compose
A Makefile is available with predefined commands to start, restart and stop docker containers:

To start containers:
```shell
make docker_start 
```

To restart containers:
```shell
make docker_restart
```

To stop containers:
```shell
make docker_stop
```

To access the PHP container
```shell
make docker_php
```

## Console Commands
The framework is shipped with console commands to operate the Projections, the Event Processor and the Timer Processor:

### Debugging Messaging
Use the following to see the class map of messages
```shell
bin/console orkestra:messaging:debug-classmap
```

To see which messages are routed to which handlers:
```shell
bin/console orkestra:messaging:debug-router
```

### Event Processor
To show the progress of the event processor:
```shell
bin/console orkestra:event-processor progress
```

To continuously run the processor:
```shell
bin/console orkestra:event-processor start
````

To replay the events
```shell
bin/console orkestra:event-processor replay
````
> Be advised that this command should not be used in production, and can have unexpected side effects.

To reset the processor to the start of the stream:
```shell
bin/console orkestra:event-processor reset
````
> Be advised that this command should not be used in production, and can have unexpected side effects.


### Timer Processor
To start the timer processor
```shell
bin/console orkestra:timer-processor
```

### Projection Processor
To show the progress of the projection processor:
```shell
bin/console orkestra:projection-processor progress
```

To continuously run the processor:
```shell
bin/console orkestra:projection-processor start
````

To replay the projections
```shell
bin/console orkestra:projection-processor replay
````

To reset the processor to the start of the stream:
```shell
bin/console orkestra:projection-processor reset
````

## License
Apache 2.0, Please read [LICENSE.md](./LICENSE.md) for more information.
Developed and Maintained by [Morébec](https://morebec.com)