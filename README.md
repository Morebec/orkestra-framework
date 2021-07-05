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
- Modern: PHP 7.4+
- Docker + docker-compose setup

## Installation
Clone repository
```shell
git clone https://github.com/Morebec/orkestra-framework
```

Build & Start Docker containers
```shell
bin/ok up
```

Install composer dependencies
```shell
bin/ok composer install
```
> If asked, you can install the symfony recipes.

Launch Quickstart utility
```shell
bin/ok console orkestra:quickstart
```

Restart Docker Containers to have the latest changes
```shell
bin/ok restart
```

Go to `http://localhost:9090` to see the application live.

## Ok binary
The Ok binary contains commands to simply perform actions against the docker containers:

To start containers:
```shell
bin/ok start 
```

To restart containers:
```shell
bin/ok restart
```

To stop containers:
```shell
bin/ok stop
```

To access the PHP container
```shell
bin/ok bash
```

See all the commands available by doing:

````shell
bin/ok help
````

## Console Commands
The framework is shipped with console commands to operate the Projections, the Event Processor and the Timeout Processor:

> These commands are supervised using supervisord to ensure they are always running and restarted in the event that they would fail.

### Debugging Messaging
Use the following to see the class map of messages
```shell
bin/ok console orkestra:messaging:debug-classmap
```

To see which messages are routed to which handlers:
```shell
bin/ok console orkestra:messaging:debug-router
```

### Event Processor
To show the progress of the event processor:
```shell
bin/ok console orkestra:event-processor progress
```

To continuously run the processor:
```shell
bin/ok console orkestra:event-processor start
````
> If using the docker containers, a supervisor is configured to ensure this is always running.
> 
To replay the events
```shell
bin/ok console orkestra:event-processor replay
````
> Be advised that this command should not be used in production, and can have unexpected side effects, since
> events processed by the event processor are intended for write-side reactions.

To reset the processor to the start of the stream:
```shell
bin/ok console orkestra:event-processor reset
````
> Be advised that this command should not be used in production, and can have unexpected side effects, since
> events processed by the event processor are intended for write-side reactions.


### Timeout Processor
To start the timeout processor
```shell
bin/ok console orkestra:timeout-processor
```
> If using the docker containers, a supervisor is configured to ensure this is always running.
> 
### Projection Processor
To show the progress of the projection processor:
```shell
bin/ok console orkestra:projection-processor progress
```

To continuously run the processor:
```shell
bin/ok console orkestra:projection-processor start
````
> If using the docker containers, a supervisor is configured to ensure this is always running.

To replay the projections
```shell
bin/ok console orkestra:projection-processor replay
````

To reset the processor to the start of the stream:
```shell
bin/ok console orkestra:projection-processor reset
````

## License
Apache 2.0, Please read [LICENSE.md](./LICENSE.md) for more information.
Developed and Maintained by [Morébec](https://morebec.com)