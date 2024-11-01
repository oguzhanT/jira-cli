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

    public function testExecuteSummaryView()
    {
        // Mock JiraClient and set up expected worklog totals
        $mockJiraClient = Mockery::mock(JiraClient::class);
        $mockJiraClient->shouldReceive('getWorklogTotalsByDateRange')
            ->with('test-account-id', Mockery::type(DateTime::class), Mockery::type(DateTime::class), false)
            ->andReturn([
                '2024-10-01' => 3600,
                '2024-10-02' => 7200,
            ]);

        // Instantiate ShowWorkLogCommand with the mock JiraClient
        $command = new ShowWorkLogCommand($mockJiraClient);

        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Execute the command with summary view
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--accountId' => 'test-account-id',
            '--period' => 'daily',
        ]);

        // Verify the output for summary view
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('2024-10-01: 1 hours', $output);
        $this->assertStringContainsString('2024-10-02: 2 hours', $output);
        $this->assertStringContainsString('Total: 3 hours', $output);
    }

    public function testExecuteDetailedView()
    {
        // Mock JiraClient and set up expected worklog totals with details per issue
        $mockJiraClient = Mockery::mock(JiraClient::class);
        $mockJiraClient->shouldReceive('getWorklogTotalsByDateRange')
            ->with('test-account-id', Mockery::type(DateTime::class), Mockery::type(DateTime::class), true)
            ->andReturn([
                '2024-10-01' => [
                    'ISSUE-1' => 1800,
                    'ISSUE-2' => 1800,
                ],
                '2024-10-02' => [
                    'ISSUE-1' => 3600,
                    'ISSUE-3' => 1800,
                ],
            ]);

        $command = new ShowWorkLogCommand($mockJiraClient);

        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Execute the command with detailed view
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--accountId' => 'test-account-id',
            '--period' => 'weekly',
            '--detailed' => true,
        ]);

        // Verify the output for detailed view
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Date: 2024-10-01', $output);
        $this->assertStringContainsString('  Issue ISSUE-1: 0.5 hours', $output);
        $this->assertStringContainsString('  Issue ISSUE-2: 0.5 hours', $output);
        $this->assertStringContainsString('  Daily Total: 1 hours', $output);

        $this->assertStringContainsString('Date: 2024-10-02', $output);
        $this->assertStringContainsString('  Issue ISSUE-1: 1 hours', $output);
        $this->assertStringContainsString('  Issue ISSUE-3: 0.5 hours', $output);
        $this->assertStringContainsString('  Daily Total: 1.5 hours', $output);

        $this->assertStringContainsString('Total: 2.5 hours', $output);
    }
}
