<?php

namespace Morebec\Orkestra\Framework\Api;

use Morebec\Orkestra\Normalization\ObjectNormalizerInterface;

/**
 * This type of object normalizer is capable of normalizing objects specifically for
 * an HTTP transport.
 */
interface HttpObjectNormalizerInterface extends ObjectNormalizerInterface
{
}
