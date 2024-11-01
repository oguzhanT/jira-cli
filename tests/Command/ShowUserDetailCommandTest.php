<?php

namespace OguzhanTogay\JiraCLI\Tests\Command;

use Mockery;
use OguzhanTogay\JiraCLI\Command\ShowUserDetailCommand;
use OguzhanTogay\JiraCLI\JiraClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ShowUserDetailCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testExecuteSuccess()
    {
        // Mock JiraClient and set up expected user details
        $mockJiraClient = Mockery::mock(JiraClient::class);
        $mockJiraClient->shouldReceive('getUserDetails')
            ->andReturn([
                'accountId' => 'test-account-id',
                'displayName' => 'Test User',
                'emailAddress' => 'test.user@example.com',
                'timeZone' => 'UTC',
                'locale' => 'en_US',
            ]);

        // Instantiate ShowUserDetailCommand with the mock JiraClient
        $command = new ShowUserDetailCommand($mockJiraClient);

        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Execute the command
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // Verify output for successful user detail retrieval
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('User Details:', $output);
        $this->assertStringContainsString('Account ID: test-account-id', $output);
        $this->assertStringContainsString('Display Name: Test User', $output);
        $this->assertStringContainsString('Email Address: test.user@example.com', $output);
        $this->assertStringContainsString('Time Zone: UTC', $output);
        $this->assertStringContainsString('Locale: en_US', $output);
    }

    public function testExecuteFailure()
    {
        // Mock JiraClient to simulate a failure in retrieving user details
        $mockJiraClient = Mockery::mock(JiraClient::class);
        $mockJiraClient->shouldReceive('getUserDetails')
            ->andReturn(null);

        // Instantiate ShowUserDetailCommand with the mock JiraClient
        $command = new ShowUserDetailCommand($mockJiraClient);

        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Execute the command
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // Verify output for failure
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Failed to retrieve user details', $output);
    }
}
