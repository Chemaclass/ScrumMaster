<?php

declare(strict_types=1);

namespace Chemaclass\ScrumMaster\Channel;

use Chemaclass\ScrumMaster\Jira\ReadModel\Company;

interface ChannelInterface
{
    public function send(array $ticketsByAssignee, Company $company): ChannelResult;
}
