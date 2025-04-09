<?php

namespace OguzhanTogay\JiraCLI\Tests\Command;

use DateTime;
use Mockery;
use OguzhanTogay\JiraCLI\Command\ShowWorkLogCommand;
use OguzhanTogay\JiraCLI\JiraClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ShowWorkLogCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testExecuteDailyView()
    {
        // Calculate expected dates for daily view (today)
        $today = new DateTime();
        $formattedDate = $today->format('Y-m-d');

        // Mock JiraClient and set up expected worklog totals
        $mockJiraClient = Mockery::mock(JiraClient::class);
        $mockJiraClient->shouldReceive('getWorkLogTotalsByDateRange')
            ->withArgs(function ($accountId, $startDate, $endDate, $detailed) use ($today) {
                // Verify start date is today at 00:00:00
                $expectedStart = clone $today;
                $expectedStart->setTime(0, 0, 0);

                // Verify end date is today at 23:59:59
                $expectedEnd = clone $today;
                $expectedEnd->setTime(23, 59, 59);

                return $accountId === 'test-account-id' &&
                    $startDate == $expectedStart &&
                    $endDate == $expectedEnd &&
                    $detailed === false;
            })
            ->andReturn([
                $formattedDate => 3600, // 1 hour
            ]);

        // Instantiate ShowWorkLogCommand with the mock JiraClient
        $command = new ShowWorkLogCommand($mockJiraClient);

        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Execute the command with daily view
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--accountId' => 'test-account-id',
            '--period' => 'daily',
        ]);

        // Verify the output for daily view
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString("Time period: $formattedDate to $formattedDate", $output);
        // Just check for the date part since the full header might be truncated in the test output
        $this->assertStringContainsString($formattedDate, $output);
        $this->assertStringContainsString('1', $output); // 1 hour
        $this->assertStringContainsString('Total: 1 hours', $output);
    }

    public function testExecuteWeeklyView()
    {
        // Calculate expected dates for weekly view (Monday to today)
        $today = new DateTime();
        $currentDayOfWeek = (int)$today->format('N'); // 1 (Mon) to 7 (Sun)

        $startDate = clone $today;
        $daysToSubtract = $currentDayOfWeek - 1;
        $startDate->modify("-$daysToSubtract days")->setTime(0, 0, 0);

        $endDate = clone $today;
        $endDate->setTime(23, 59, 59);

        $formattedStartDate = $startDate->format('Y-m-d');
        $formattedEndDate = $endDate->format('Y-m-d');

        // Create mock data - one entry for each day of the week up to today
        $mockData = [];
        $totalSeconds = 0;

        $currentDate = clone $startDate;
        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m-d');
            $seconds = 3600; // 1 hour per day
            $mockData[$dateKey] = $seconds;
            $totalSeconds += $seconds;
            $currentDate->modify('+1 day');
        }

        // Mock JiraClient
        $mockJiraClient = Mockery::mock(JiraClient::class);
        $mockJiraClient->shouldReceive('getWorkLogTotalsByDateRange')
            ->withArgs(function ($accountId, $start, $end, $detailed) use ($startDate, $endDate) {
                return $accountId === 'test-account-id' &&
                    $start == $startDate &&
                    $end == $endDate &&
                    $detailed === false;
            })
            ->andReturn($mockData);

        // Instantiate command
        $command = new ShowWorkLogCommand($mockJiraClient);
        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Execute the command with weekly view
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--accountId' => 'test-account-id',
            '--period' => 'weekly',
        ]);

        // Verify the output
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString("Time period: $formattedStartDate to $formattedEndDate", $output);
        $this->assertStringContainsString('Weekly Work Log', $output);
        $this->assertStringContainsString('Total', $output);
        $totalHours = round($totalSeconds / 3600, 2);
        $this->assertStringContainsString("Total: $totalHours hours", $output);
    }

    public function testExecuteMonthlyView()
    {
        // Calculate expected dates for monthly view
        $today = new DateTime();

        $startDate = clone $today;
        $startDate->modify('first day of this month')->setTime(0, 0, 0);

        $endDate = clone $today;
        $endDate->setTime(23, 59, 59);

        $formattedStartDate = $startDate->format('Y-m-d');
        $formattedEndDate = $endDate->format('Y-m-d');
        $monthYear = $today->format('F Y');

        // Create mock data - one entry for each day of the month up to today
        $mockData = [];
        $totalSeconds = 0;

        $currentDate = clone $startDate;
        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m-d');
            $seconds = 3600; // 1 hour per day
            $mockData[$dateKey] = $seconds;
            $totalSeconds += $seconds;
            $currentDate->modify('+1 day');
        }

        // Mock JiraClient
        $mockJiraClient = Mockery::mock(JiraClient::class);
        $mockJiraClient->shouldReceive('getWorkLogTotalsByDateRange')
            ->withArgs(function ($accountId, $start, $end, $detailed) use ($startDate, $endDate) {
                return $accountId === 'test-account-id' &&
                    $start == $startDate &&
                    $end == $endDate &&
                    $detailed === false;
            })
            ->andReturn($mockData);

        // Instantiate command
        $command = new ShowWorkLogCommand($mockJiraClient);
        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Execute the command with monthly view
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--accountId' => 'test-account-id',
            '--period' => 'monthly',
        ]);

        // Verify the output
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString("Time period: $formattedStartDate to $formattedEndDate", $output);
        $this->assertStringContainsString($monthYear, $output);
        $totalHours = round($totalSeconds / 3600, 2);
        $this->assertStringContainsString("Total: $totalHours hours", $output);
    }

    public function testExecuteDetailedView()
    {
        // Calculate expected dates for weekly view
        $today = new DateTime();
        $currentDayOfWeek = (int)$today->format('N');

        $startDate = clone $today;
        $daysToSubtract = $currentDayOfWeek - 1;
        $startDate->modify("-$daysToSubtract days")->setTime(0, 0, 0);

        $endDate = clone $today;
        $endDate->setTime(23, 59, 59);

        // Format dates for mock data
        $date1 = $startDate->format('Y-m-d');
        $date2 = (clone $startDate)->modify('+1 day')->format('Y-m-d');

        // Mock detailed worklog data
        $mockDetailedData = [
            $date1 => [
                'ISSUE-1' => [
                    'timeSpent' => 1800,
                    'summary' => 'Test Issue 1'
                ],
                'ISSUE-2' => [
                    'timeSpent' => 1800,
                    'summary' => 'Test Issue 2'
                ],
            ],
            $date2 => [
                'ISSUE-1' => [
                    'timeSpent' => 3600,
                    'summary' => 'Test Issue 1'
                ],
                'ISSUE-3' => [
                    'timeSpent' => 1800,
                    'summary' => 'Test Issue 3'
                ],
            ],
        ];

        // Mock JiraClient
        $mockJiraClient = Mockery::mock(JiraClient::class);
        $mockJiraClient->shouldReceive('getWorkLogTotalsByDateRange')
            ->withArgs(function ($accountId, $start, $end, $detailed) use ($startDate, $endDate) {
                return $accountId === 'test-account-id' &&
                    $start == $startDate &&
                    $end == $endDate &&
                    $detailed === true;
            })
            ->andReturn($mockDetailedData);

        // Instantiate command
        $command = new ShowWorkLogCommand($mockJiraClient);
        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Execute the command with detailed weekly view
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--accountId' => 'test-account-id',
            '--period' => 'weekly',
            '--detailed' => true,
        ]);

        // Verify the output
        $output = $commandTester->getDisplay();

        // Check that the table has issue rows
        $this->assertStringContainsString('ISSUE-1', $output);
        $this->assertStringContainsString('ISSUE-2', $output);
        $this->assertStringContainsString('ISSUE-3', $output);

        // Check totals
        $totalHours = round((1800 + 1800 + 3600 + 1800) / 3600, 2);
        $this->assertStringContainsString("Total: $totalHours hours", $output);
    }

    public function testNoWorklogsFound()
    {
        // Mock JiraClient to return empty data
        $mockJiraClient = Mockery::mock(JiraClient::class);
        $mockJiraClient->shouldReceive('getWorkLogTotalsByDateRange')
            ->withArgs(function ($accountId, $startDate, $endDate, $detailed) {
                return $accountId === 'test-account-id' &&
                    $startDate instanceof DateTime &&
                    $endDate instanceof DateTime &&
                    $detailed === false;
            })
            ->andReturn([]);

        // Instantiate command
        $command = new ShowWorkLogCommand($mockJiraClient);
        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Execute the command
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--accountId' => 'test-account-id',
            '--period' => 'daily',
        ]);

        // Verify the output shows a message about no worklogs
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('No worklogs found', $output);
        $this->assertStringContainsString('Total: 0 hours', $output);
    }
}