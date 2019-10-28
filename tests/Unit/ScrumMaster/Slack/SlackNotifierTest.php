<?php

declare(strict_types=1);

namespace App\Tests\Unit\ScrumMaster\Slack;

use App\ScrumMaster\Jira\Board;
use App\ScrumMaster\Jira\JiraHttpClient;
use App\ScrumMaster\Jira\ReadModel\Company;
use App\ScrumMaster\Jira\Tickets;
use App\ScrumMaster\Jira\UrlFactoryInterface;
use App\ScrumMaster\Slack\MessageGeneratorInterface;
use App\ScrumMaster\Slack\SlackHttpClient;
use App\ScrumMaster\Slack\SlackMapping;
use App\ScrumMaster\Slack\SlackNotifier;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class SlackNotifierTest extends TestCase
{
    /** @test */
    public function noNotificationsAreSentOutIfNoJiraIssuesWhereFound(): void
    {
        $jiraBoard = new Board(['status1' => 1]);

        $slackNotifier = new SlackNotifier(
            $jiraBoard,
            new JiraHttpClient($this->mockJiraClient($issues = [])),
            new SlackHttpClient($this->createMock(HttpClientInterface::class))
        );

        $responses = $slackNotifier->sendNotifications(
            $this->aCompany(),
            $this->createMock(UrlFactoryInterface::class),
            SlackMapping::jiraNameWithSlackId(['key' => 'value']),
            $this->createMock(MessageGeneratorInterface::class)
        );

        $this->assertEmpty($responses, 'No notifications should have been sent');
    }

    /** @test */
    public function notificationsAreSentOutIfJiraIssuesWhereFound(): void
    {
        $jiraBoard = new Board(['status1' => 1]);

        $jiraIssues = [
            $this->createAnIssueAsArray('user.1.jira'),
            $this->createAnIssueAsArray('user.2.jira'),
        ];

        $totalIssues = count($jiraIssues);

        /** @var HttpClientInterface|MockObject $mockSlackClient */
        $mockSlackClient = $this->createMock(HttpClientInterface::class);
        $mockSlackClient->expects($this->exactly($totalIssues))
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo(SlackHttpClient::SLACK_API_POST_MESSAGE),
                $this->equalTo([
                    'json' => [
                        'as_user' => true,
                        'channel' => 'channel.id',
                        'text' => 'any text',
                    ],
                ])
            );

        $slackNotifier = new SlackNotifier(
            $jiraBoard,
            new JiraHttpClient($this->mockJiraClient($jiraIssues)),
            new SlackHttpClient($mockSlackClient)
        );

        $messageGenerator = $this->createMock(MessageGeneratorInterface::class);
        $messageGenerator->expects($this->exactly($totalIssues))
            ->method('forJiraTicket')->willReturn('any text');

        $responses = $slackNotifier->sendNotifications(
            $this->aCompany(),
            $this->createMock(UrlFactoryInterface::class),
            SlackMapping::jiraNameWithSlackId([
                'user.1.jira' => 'channel.id',
                'user.2.jira' => 'channel.id',
                'user.3.jira' => 'other.channel.id',
            ]),
            $messageGenerator
        );

        $this->assertCount($totalIssues, $responses, 'Some notifications should have been sent');
    }

    private function mockJiraClient(array $issues): HttpClientInterface
    {
        $jiraResponse = $this->createMock(ResponseInterface::class);
        $jiraResponse->method('toArray')->willReturn(['issues' => $issues]);

        /** @var HttpClientInterface|MockObject $jiraClient */
        $jiraClient = $this->createMock(HttpClientInterface::class);
        $jiraClient->method('request')->willReturn($jiraResponse);

        return $jiraClient;
    }

    private function aCompany(): Company
    {
        return Company::withNameAndProject('COMPANY_NAME', 'JIRA_PROJECT_NAME');
    }

    private function createAnIssueAsArray(string $assigneeName): array
    {
        return [
            'key' => 'KEY-123',
            'fields' => [
                Tickets::FIELD_STORY_POINTS => '5.0',
                'status' => [
                    'name' => 'In Progress',
                ],
                'summary' => 'The ticket title',
                'statuscategorychangedate' => '2019-06-15T10:35:00+00',
                'assignee' => [
                    'name' => $assigneeName,
                    'key' => 'user.key.jira',
                    'emailAddress' => 'user@email.jira',
                    'displayName' => 'display.name.jira',
                ],
            ],
        ];
    }
}