<?php

namespace OguzhanTogay\JiraCLI\Tests\Command;

use Mockery;
use OguzhanTogay\JiraCLI\Command\ConfigureAccountIdCommand;
use OguzhanTogay\JiraCLI\JiraClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ConfigureAccountIdCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testExecuteSuccess()
    {
        // Mock JiraClient
        $mockJiraClient = Mockery::mock(JiraClient::class);
        $mockJiraClient->shouldReceive('getUserDetails')
            ->andReturn(['accountId' => 'test-account-id']);

        // Mock setting account ID in .env file
        $mockJiraClient->shouldReceive('setAccountIdInEnv')
            ->with('test-account-id')
            ->andReturn(true);

        $command = new ConfigureAccountIdCommand($mockJiraClient);

        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Execute the command
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // Verify output for success
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString("JIRA_ACCOUNT_ID set to 'test-account-id' in .env file", $output);
    }

    public function testExecuteFailure()
    {
        // Mock JiraClient
        $mockJiraClient = Mockery::mock(JiraClient::class);
        $mockJiraClient->shouldReceive('getUserDetails')
            ->andReturn(['accountId' => 'test-account-id']);

        // Mock failure when setting account ID in .env file
        $mockJiraClient->shouldReceive('setAccountIdInEnv')
            ->with('test-account-id')
            ->andReturn(false);

        $command = new ConfigureAccountIdCommand($mockJiraClient);

        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Execute the command
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // Verify output for failure
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Failed to set JIRA_ACCOUNT_ID in .env file', $output);
    }
}
