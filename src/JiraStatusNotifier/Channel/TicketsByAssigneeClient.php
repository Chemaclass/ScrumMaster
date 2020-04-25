<?php

declare(strict_types=1);

namespace Chemaclass\JiraStatusNotifier\Channel;

use Chemaclass\JiraStatusNotifier\IgnoreStrategy\IgnoreStrategy;
use Chemaclass\JiraStatusNotifier\Jira\Board;
use Chemaclass\JiraStatusNotifier\Jira\JiraHttpClient;
use Chemaclass\JiraStatusNotifier\Jira\JqlUrlFactory;

final class TicketsByAssigneeClient
{
    private JiraHttpClient $jiraClient;

    private JqlUrlFactory $jqlUrlFactory;

    private IgnoreStrategy $ignoreStrategy;

    public function __construct(
        JiraHttpClient $jiraClient,
        JqlUrlFactory $jqlUrlFactory,
        IgnoreStrategy $ignoreUsersStrategy
    ) {
        $this->jiraClient = $jiraClient;
        $this->jqlUrlFactory = $jqlUrlFactory;
        $this->ignoreStrategy = $ignoreUsersStrategy;
    }

    public function fetchFromBoard(Board $board): TicketsByAssignee
    {
        $ticketsByAssignee = new TicketsByAssignee();

        foreach ($board->maxDaysInStatus() as $statusName => $maxDays) {
            $tickets = $this->jiraClient->getTickets($this->jqlUrlFactory, $statusName);

            foreach ($tickets as $ticket) {
                if ($this->ignoreStrategy->shouldIgnoreTicket($ticket)) {
                    continue;
                }

                $ticketsByAssignee->add($ticket);
            }
        }

        return $ticketsByAssignee;
    }
}
