<?php

namespace Morebec\Orkestra\Framework\EventStore;

class GitWrapper
{
    private string $gitBinaryLocation;

    /**
     * @param string $gitBinaryLocation full path the location where the git binary is located, or relative to the CWD
     */
    public function __construct(string $gitBinaryLocation = '/usr/bin/git')
    {
        $this->gitBinaryLocation = $gitBinaryLocation;
    }

    /**
     * Returns the short hash of the current git commit.
     */
    public function getShortCommitHash(): string
    {
        return trim(exec($this->gitBinaryLocation.' log --pretty="%h" -n1 HEAD'));
    }
}
