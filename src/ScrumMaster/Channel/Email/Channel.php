<?php

declare(strict_types=1);

namespace Chemaclass\ScrumMaster\Channel\Email;

use Chemaclass\ScrumMaster\Channel\ChannelInterface;
use Chemaclass\ScrumMaster\Channel\ChannelResultInterface;
use Chemaclass\ScrumMaster\Channel\Email\ReadModel\Email;
use Chemaclass\ScrumMaster\Channel\MessageGeneratorInterface;
use Chemaclass\ScrumMaster\Channel\ReadModel\ChannelIssue;
use Chemaclass\ScrumMaster\Jira\Board;
use Chemaclass\ScrumMaster\Jira\JiraHttpClient;
use Chemaclass\ScrumMaster\Jira\JqlUrlFactory;
use Chemaclass\ScrumMaster\Jira\ReadModel\Company;
use Chemaclass\ScrumMaster\Jira\ReadModel\JiraTicket;

final class Channel implements ChannelInterface
{
    /** @var Client */
    private $client;

    /** @var MessageGeneratorInterface */
    private $messageGenerator;

    public function __construct(Client $client, MessageGeneratorInterface $messageGenerator)
    {
        $this->messageGenerator = $messageGenerator;
        $this->client = $client;
    }

    public function sendNotifications(
        Board $board,
        JiraHttpClient $jiraClient,
        Company $company,
        JqlUrlFactory $jqlUrlFactory,
        array $jiraUsersToIgnore = []
    ): ChannelResultInterface {
        $result = new ChannelResult();

        foreach ($board->maxDaysInStatus() as $statusName => $maxDays) {
            $tickets = $jiraClient->getTickets($jqlUrlFactory, $statusName);

            $result->append($this->sendEmail($company, $tickets, $jiraUsersToIgnore));
        }

        return $result;
    }

    private function sendEmail(Company $company, array $tickets, array $jiraUsersToIgnore): ChannelResult
    {
        $result = new ChannelResult();

        /** @var JiraTicket $ticket */
        foreach ($tickets as $ticket) {
            $assignee = $ticket->assignee();

            if (in_array($assignee->key(), $jiraUsersToIgnore)) {
                continue;
            }

            $this->client->sendMessage(new Email(
                $ticket->assignee()->email(),
                $this->messageGenerator->forJiraTicket($ticket, $company->companyName())
            ));

            $slackTicket = ChannelIssue::withCodeAndAssignee(200, $assignee->displayName());
            $result->addChannelIssue($ticket->key(), $slackTicket);
        }

        return $result;
    }
}