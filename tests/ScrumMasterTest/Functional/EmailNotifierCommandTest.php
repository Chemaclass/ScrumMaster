<?php

declare(strict_types=1);

namespace Chemaclass\ScrumMasterTests\Functional;

use Chemaclass\ScrumMaster\Channel\ChannelResult;
use Chemaclass\ScrumMaster\Channel\Email;
use Chemaclass\ScrumMaster\Channel\Email\ByPassEmail;
use Chemaclass\ScrumMaster\Channel\Email\Channel;
use Chemaclass\ScrumMaster\Channel\Email\MessageGenerator;
use Chemaclass\ScrumMaster\Channel\ReadModel\ChannelIssue;
use Chemaclass\ScrumMaster\Command\NotifierCommand;
use Chemaclass\ScrumMaster\Command\NotifierInput;
use Chemaclass\ScrumMaster\Jira\JiraHttpClient;
use Chemaclass\ScrumMasterTests\Unit\Concerns\JiraApiResource;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as SymfonyEmail;

final class EmailNotifierCommandTest extends TestCase
{
    use JiraApiResource;

    private const MANDATORY_FIELDS = [
        NotifierInput::COMPANY_NAME => 'company.name',
        NotifierInput::JIRA_PROJECT_NAME => 'Jira project name',
        NotifierInput::DAYS_FOR_STATUS => '{"status":1}',
    ];

    /** @test */
    public function zeroNotificationsWereSent(): void
    {
        $command = $this->slackNotifierCommandWithJiraTickets([]);
        $result = $command->execute($this->notifierInput());
        /** @var ChannelResult $channelResult */
        $channelResult = $result[Email\Channel::class];
        $this->assertEmpty($channelResult->channelIssues());
    }

    /** @test */
    public function twoSuccessfulNotificationsWereSent(): void
    {
        $command = $this->slackNotifierCommandWithJiraTickets([
            $this->createAJiraIssueAsArray('user.1.jira', 'KEY-111'),
            $this->createAJiraIssueAsArray('user.2.jira', 'KEY-222'),
        ]);

        $result = $command->execute($this->notifierInput());
        /** @var ChannelResult $channelResult */
        $channelResult = $result[Email\Channel::class];
        $this->assertEquals(['KEY-111', 'KEY-222'], array_keys($channelResult->channelIssues()));
    }

    /** @test */
    public function ignoredUserShouldNotReceiveAnyNotification(): void
    {
        $command = $this->slackNotifierCommandWithJiraTickets([
            $this->createAJiraIssueAsArray('user.1.jira', 'KEY-111'),
            $this->createAJiraIssueAsArray('user.2.jira', 'KEY-222'),
        ]);

        $result = $command->execute(
            $this->notifierInput([
                NotifierInput::JIRA_USERS_TO_IGNORE => '["user.1.jira"]',
            ])
        );

        /** @var ChannelResult $channelResult */
        $channelResult = $result[Email\Channel::class];
        $this->assertEquals(['KEY-222'], array_keys($channelResult->channelIssues()));
    }

    /** @test */
    public function overrideEmailFromAssignee(): void
    {
        $jiraIssues = [
            $this->createAJiraIssueAsArray('user.1.jira', 'KEY-111', 'user.1@email.com'),
            $this->createAJiraIssueAsArray('user.2.jira', 'KEY-222', 'user.2@email.com'),
            $this->createAJiraIssueAsArray('user.3.jira', 'KEY-222', 'user.3@email.com'),
        ];

        /** @var MockObject|TransportInterface $transport */
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects(self::exactly(count($jiraIssues)))
            ->method('send')
            ->willReturnCallback(function (SymfonyEmail $email): void {
                self::assertEquals([new Address('user.3@email.com', 'display.name.jira')], $email->getTo());
            });

        $command = new NotifierCommand(
            new JiraHttpClient($this->mockJiraClient($jiraIssues)),
            [
                new Channel(
                    new Mailer($transport),
                    MessageGenerator::withTimeToDiff(new DateTimeImmutable()),
                    ByPassEmail::overriddenEmails([
                        'user.1.jira' => 'user.3@email.com',
                        'user.2.jira' => 'user.3@email.com',
                    ])
                ),
            ]
        );

        $command->execute($this->notifierInput());
    }

    /** @test */
    public function ensureProperResponseStatusCodePerIssue(): void
    {
        $code = 12345;

        /** @var MockObject|TransportInterface $transport */
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects(self::once())
            ->method('send')
            ->willThrowException(new TransportException('', $code));

        $command = new NotifierCommand(
            new JiraHttpClient($this->mockJiraClient([
                $this->createAJiraIssueAsArray('user.1.jira', 'KEY-111'),
            ])),
            [
                new Channel(
                    new Mailer($transport),
                    MessageGenerator::withTimeToDiff(new DateTimeImmutable())
                ),
            ]
        );

        $results = $command->execute($this->notifierInput());
        /** @var ChannelResult $channelResult */
        $channelResult = $results[Email\Channel::class];
        /** @var ChannelIssue $issue */
        $issue = $channelResult->channelIssues()['KEY-111'];
        self::assertEquals($code, $issue->responseStatusCode());
    }

    /** @test */
    public function sameUserReceiveOneSingleNotification(): void
    {
        /** @var MockObject|TransportInterface $transport */
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects(self::once())->method('send');

        $command = new NotifierCommand(
            new JiraHttpClient($this->mockJiraClient([
                $this->createAJiraIssueAsArray('user.1.jira', 'KEY-111', 'email@a.com', 'statusName1'),
                $this->createAJiraIssueAsArray('user.1.jira', 'KEY-222', 'email@a.com', 'statusName1'),
                $this->createAJiraIssueAsArray('user.1.jira', 'KEY-333', 'email@a.com', 'statusName2'),
            ])),
            [
                new Email\Channel(
                    new Mailer($transport),
                    Email\MessageGenerator::withTimeToDiff(new DateTimeImmutable())
                ),
            ]
        );

        $command->execute($this->notifierInput());
    }

    private function notifierInput(array $optionalFields = []): NotifierInput
    {
        return NotifierInput::fromArray(array_merge(self::MANDATORY_FIELDS, $optionalFields));
    }

    private function slackNotifierCommandWithJiraTickets(array $jiraIssues): NotifierCommand
    {
        return new NotifierCommand(
            new JiraHttpClient($this->mockJiraClient($jiraIssues)),
            [
                new Email\Channel(
                    new Mailer($this->createMock(TransportInterface::class)),
                    Email\MessageGenerator::withTimeToDiff(new DateTimeImmutable())
                ),
            ]
        );
    }
}
