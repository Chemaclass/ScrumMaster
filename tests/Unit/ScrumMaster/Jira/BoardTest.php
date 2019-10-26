<?php

declare(strict_types=1);

namespace App\Tests\Unit\ScrumMaster;

use App\ScrumMaster\Jira\Board;
use PHPUnit\Framework\TestCase;

final class BoardTest extends TestCase
{
    /** @test */
    public function daysForStatus(): void
    {
        $board = new Board(['status1' => 1, 'status2' => 2]);
        $this->assertEquals(2, $board->getDaysForStatus('status2'));
    }

    /** @test */
    public function daysForStatusWhenStatusNotFound(): void
    {
        $board = new Board(['status1' => 1], $default = 9);
        $this->assertEquals($default, $board->getDaysForStatus('statusNotFound'));
    }
}