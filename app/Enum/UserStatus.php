<?php

namespace App\Enum;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case BLOCKED = 'blocked';
    case PENDING = 'pending';
    case DELETED = 'deleted';

    // Reason
    case FAILED_LOGIN_ATTEMPTS = 'failed_login_attempts';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::BLOCKED => 'Blocked',
            self::PENDING => 'Pending',
            self::DELETED => 'Deleted',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this === self::INACTIVE;
    }

    public function isBlocked(): bool
    {
        return $this === self::BLOCKED;
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isDeleted(): bool
    {
        return $this === self::DELETED;
    }
}
