<?php

namespace App\Exceptions;

/**
 * Raised when saving a plan needs an explicit user decision, because the
 * activity already has a schedule and the submission would either replace it
 * or add a session.
 *
 * This is deliberately NOT an error the user caused: the controller turns it
 * into an HTTP 409 Conflict carrying the existing and proposed schedules, so
 * the browser can render the "Aktivitas Sudah Terjadwal" confirmation dialog.
 * Nothing is written to the database when this is thrown.
 */
class ScheduleConfirmationException extends AppException
{
    public const OPERATION_REPLACE = 'REPLACE';
    public const OPERATION_ADD     = 'ADD_SESSION';

    /**
     * @param array<string, mixed>       $existing   Existing plan summary
     * @param array<string, mixed>       $proposed   Proposed plan summary
     * @param list<string>               $operations Operations the user may choose
     */
    public function __construct(
        string $message,
        private readonly array $existing = [],
        private readonly array $proposed = [],
        private readonly array $operations = [self::OPERATION_REPLACE],
    ) {
        parent::__construct($message, 409);
    }

    /**
     * @return array<string, mixed>
     */
    public function getExisting(): array
    {
        return $this->existing;
    }

    /**
     * @return array<string, mixed>
     */
    public function getProposed(): array
    {
        return $this->proposed;
    }

    /**
     * @return list<string>
     */
    public function getOperations(): array
    {
        return array_values($this->operations);
    }

    public function canReplace(): bool
    {
        return in_array(self::OPERATION_REPLACE, $this->operations, true);
    }

    public function canAddSession(): bool
    {
        return in_array(self::OPERATION_ADD, $this->operations, true);
    }

    /**
     * Structured payload for the JSON API and for embedding in the HTML form.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'confirmation_required' => true,
            'code'                  => 'SCHEDULE_EXISTS',
            'message'               => $this->getMessage(),
            'existing'              => $this->existing,
            'proposed'              => $this->proposed,
            'operations'            => [
                'replace'     => $this->canReplace(),
                'add_session' => $this->canAddSession(),
            ],
        ];
    }
}
