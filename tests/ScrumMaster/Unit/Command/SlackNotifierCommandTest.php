<?php

declare(strict_types=1);

namespace Chemaclass\ScrumMaster\Tests\Unit\Command;

use Chemaclass\ScrumMaster\Command\SlackNotifierCommand;
use Chemaclass\ScrumMaster\Command\SlackNotifierInput;
use Chemaclass\ScrumMaster\Command\SlackNotifierOutput;
use Chemaclass\ScrumMaster\Jira\JiraHttpClient;
use Chemaclass\ScrumMaster\Slack\SlackHttpClient;
use Chemaclass\ScrumMaster\Tests\Unit\Concerns\JiraApiResource;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SlackNotifierCommandTest extends TestCase
{
    use JiraApiResource;

    private const ENV = [
        SlackNotifierInput::COMPANY_NAME => 'company.name',
        SlackNotifierInput::JIRA_PROJECT_NAME => 'Jira project name',
        SlackNotifierInput::DAYS_FOR_STATUS => '{"status":1}',
        SlackNotifierInput::SLACK_MAPPING_IDS => '{"jira.id":"slack.id"}',
    ];

    /** @test */
    public function zeroNotificationsWereSent(): void
    {
        $command = new SlackNotifierCommand(
            new JiraHttpClient($this->createMock(HttpClientInterface::class)),
            new SlackHttpClient($this->createMock(HttpClientInterface::class))
        );

        $result = $command->execute(
            SlackNotifierInput::fromArray(self::ENV),
            $this->inMemoryOutput()
        );

        $this->assertEmpty($result->slackTickets());
    }

    /** @test */
    public function twoSuccessfulNotificationsWereSent(): void
    {
        $command = new SlackNotifierCommand(
            new JiraHttpClient($this->mockJiraClient([
                $this->createAnIssueAsArray('user.1.jira', 'KEY-111'),
                $this->createAnIssueAsArray('user.2.jira', 'KEY-222'),
            ])),
            new SlackHttpClient($this->createMock(HttpClientInterface::class))
        );

        $inMemoryOutput = new InMemoryOutput();

        $result = $command->execute(
            SlackNotifierInput::fromArray(self::ENV),
            new SlackNotifierOutput($inMemoryOutput)
        );

        $this->assertNotEmpty($inMemoryOutput->lines());
        $this->assertEquals(['KEY-111', 'KEY-222'], $result->ticketKeys());
    }

    private function inMemoryOutput(): SlackNotifierOutput
    {
        return new SlackNotifierOutput(new InMemoryOutput());
    }
}