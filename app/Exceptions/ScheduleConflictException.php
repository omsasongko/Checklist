<?php

namespace App\Exceptions;

/**
 * Raised when saving or enabling an activity plan would create a hard
 * schedule conflict. Carries structured conflict data so controllers and
 * views can present an actionable Indonesian explanation.
 */
class ScheduleConflictException extends AppException
{
    /**
     * @var list<array<string, mixed>>
     */
    private array $conflicts;

    /**
     * @param list<array<string, mixed>> $conflicts
     */
    public function __construct(string $message, array $conflicts = [])
    {
        parent::__construct($message, 422);
        $this->conflicts = $conflicts;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getConflicts(): array
    {
        return $this->conflicts;
    }
}
