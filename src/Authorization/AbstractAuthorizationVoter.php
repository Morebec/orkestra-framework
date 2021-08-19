<?php

namespace Morebec\Orkestra\Framework\Authorization;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Abstract implementation of a Voter that works with {@link AuthorizationContext}.
 */
abstract class AbstractAuthorizationVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        // Some voters do not work with AuthorizationContext.
        if (!($subject instanceof AuthorizationContext)) {
            return false;
        }

        return $this->supportsContext($subject);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        return $this->voteForContext($subject);
    }

    /**
     * Indicates if this voter supports the given Authorization Context or not.
     *
     * @return bool true if supported, otherwise false
     */
    abstract protected function supportsContext(AuthorizationContext $context): bool;

    /**
     * Performs an authorization check for a given context.
     * It is safe to assume that $context already passed the "supportsContext()" method check.
     *
     * @return bool true if authorized, otherwise false
     */
    abstract protected function voteForContext(AuthorizationContext $context): bool;
}
