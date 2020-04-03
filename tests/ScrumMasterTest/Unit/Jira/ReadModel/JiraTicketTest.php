<?php

declare(strict_types=1);

namespace Chemaclass\ScrumMasterTests\Unit\Jira\ReadModel;

use Chemaclass\ScrumMaster\Jira\ReadModel\Assignee;
use Chemaclass\ScrumMaster\Jira\ReadModel\JiraTicket;
use Chemaclass\ScrumMaster\Jira\ReadModel\TicketStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class JiraTicketTest extends TestCase
{
    private JiraTicket $ticket;

    protected function setUp(): void
    {
        $this->ticket = new JiraTicket(
            'title',
            'key',
            new TicketStatus(
                'statusName',
                (new DateTimeImmutable())->setTime(0, 0, 0)
            ),
            new Assignee(
                'assigneeName',
                'assignee.key',
                'Assignee Display Name',
                'assignee@email.com'
            ),
            ['real_key' => 'customKey']
        );
    }

    /** @test */
    public function title(): void
    {
        self::assertSame('title', $this->ticket->title());
    }

    /** @test */
    public function key(): void
    {
        self::assertSame('key', $this->ticket->key());
    }

    /** @test */
    public function status(): void
    {
        self::assertSame('statusName', $this->ticket->status()->name());
        self::assertSame(
            (new DateTimeImmutable())->format('Y-m-d'),
            $this->ticket->status()->changeDate()->format('Y-m-d')
        );
    }

    /** @test */
    public function assignee(): void
    {
        self::assertEquals(new Assignee(
            'assigneeName',
            'assignee.key',
            'Assignee Display Name',
            'assignee@email.com'
        ), $this->ticket->assignee());
    }

    /** @test */
    public function customFields(): void
    {
        self::assertSame(['real_key' => 'customKey'], $this->ticket->customFields());
    }
}
