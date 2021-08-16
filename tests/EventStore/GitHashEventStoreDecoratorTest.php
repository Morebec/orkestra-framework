<?php

namespace Tests\Morebec\Orkestra\Framework\EventStore;

use Morebec\Orkestra\EventSourcing\EventStore\AppendStreamOptions;
use Morebec\Orkestra\EventSourcing\EventStore\EventDescriptorInterface;
use Morebec\Orkestra\EventSourcing\EventStore\EventStoreInterface;
use Morebec\Orkestra\EventSourcing\EventStore\EventStreamId;
use Morebec\Orkestra\Framework\EventStore\GitHashEventStoreDecorator;
use Morebec\Orkestra\Framework\EventStore\GitWrapper;
use PHPUnit\Framework\TestCase;

class GitHashEventStoreDecoratorTest extends TestCase
{
    public function testAppendToStream(): void
    {
        $eventStore = $this->getMockBuilder(EventStoreInterface::class)->getMock();
        $git = $this->getMockBuilder(GitWrapper::class)->getMock();
        $descriptor = $this->getMockBuilder(EventDescriptorInterface::class)->getMock();

        $decorator = new GitHashEventStoreDecorator($eventStore, $git);

        $git->method('getShortCommitHash')->willReturn('abc123');

        $options = AppendStreamOptions::append();
        $streamId = EventStreamId::fromString('test');

        $eventStore->expects($this->once())
            ->method('appendToStream')
            ->withConsecutive([
                $streamId,
                $this->callback(
                    function (array $ds) {
                        /** @var EventDescriptorInterface $d */
                        $d = $ds[0];
                        self::assertTrue($d->getEventMetadata()->hasKey(GitHashEventStoreDecorator::GIT_HASH_KEY));
                        self::assertEquals('abc123', $d->getEventMetadata()->getValue(GitHashEventStoreDecorator::GIT_HASH_KEY));

                        return true;
                    }
                ),
                $options,
            ]);
        $decorator->appendToStream($streamId, [$descriptor], $options);
    }
}
