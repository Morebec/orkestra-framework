<?php

namespace Morebec\Orkestra\Framework\EventStore;

use Morebec\Orkestra\EventSourcing\EventStore\AppendStreamOptions;
use Morebec\Orkestra\EventSourcing\EventStore\EventDescriptor;
use Morebec\Orkestra\EventSourcing\EventStore\EventDescriptorInterface;
use Morebec\Orkestra\EventSourcing\EventStore\EventMetadata;
use Morebec\Orkestra\EventSourcing\EventStore\EventStoreInterface;
use Morebec\Orkestra\EventSourcing\EventStore\EventStoreSubscriberInterface;
use Morebec\Orkestra\EventSourcing\EventStore\EventStreamId;
use Morebec\Orkestra\EventSourcing\EventStore\EventStreamInterface;
use Morebec\Orkestra\EventSourcing\EventStore\ReadStreamOptions;
use Morebec\Orkestra\EventSourcing\EventStore\StreamedEventCollectionInterface;
use Morebec\Orkestra\EventSourcing\EventStore\TruncateStreamOptions;

/**
 * This event store decorator adds the current git hash to the events in order
 * to provide additional info on which version of the code committed an event to the store.
 */
class GitHashEventStoreDecorator implements EventStoreInterface
{
    public const GIT_HASH_KEY = 'gitHash';
    private EventStoreInterface $eventStore;

    private GitWrapper $git;

    public function __construct(EventStoreInterface $eventStore, GitWrapper $git)
    {
        $this->eventStore = $eventStore;
        $this->git = $git;
    }

    public function getGlobalStreamId(): EventStreamId
    {
        return $this->eventStore->getGlobalStreamId();
    }

    public function appendToStream(EventStreamId $streamId, iterable $eventDescriptors, AppendStreamOptions $options): void
    {
        $currentHash = $this->getCurrentGitHash();

        $updated = [];

        /** @var EventDescriptorInterface $eventDescriptor */
        foreach ($eventDescriptors as $eventDescriptor) {
            $metadata = $eventDescriptor->getEventMetadata()->toArray();
            $metadata[self::GIT_HASH_KEY] = $currentHash;
            $updated[] = new EventDescriptor(
                $eventDescriptor->getEventId(),
                $eventDescriptor->getEventType(),
                $eventDescriptor->getEventData(),
                new EventMetadata($metadata)
            );
        }

        $eventDescriptors = $updated;

        $this->eventStore->appendToStream($streamId, $eventDescriptors, $options);
    }

    public function readStream(EventStreamId $streamId, ReadStreamOptions $options): StreamedEventCollectionInterface
    {
        return $this->eventStore->readStream($streamId, $options);
    }

    public function truncateStream(EventStreamId $streamId, TruncateStreamOptions $options): void
    {
        $this->eventStore->truncateStream($streamId, $options);
    }

    public function getStream(EventStreamId $streamId): ?EventStreamInterface
    {
        return $this->eventStore->getStream($streamId);
    }

    public function streamExists(EventStreamId $streamId): bool
    {
        return $this->eventStore->streamExists($streamId);
    }

    public function subscribeToStream(EventStreamId $streamId, EventStoreSubscriberInterface $subscriber): void
    {
        $this->eventStore->subscribeToStream($streamId, $subscriber);
    }

    private function getCurrentGitHash(): string
    {
        return $this->git->getShortCommitHash();
    }
}
