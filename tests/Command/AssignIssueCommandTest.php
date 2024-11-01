<?php

namespace OguzhanTogay\JiraCLI\Tests\Command;

use Mockery;
use OguzhanTogay\JiraCLI\Command\AssignIssueCommand;
use OguzhanTogay\JiraCLI\JiraClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class AssignIssueCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testExecuteWithDirectAssignment()
    {
        // Mock JiraClient and set up expected method calls
        $mockJiraClient = Mockery::mock(JiraClient::class);

        $mockJiraClient->shouldReceive('assignIssue')
            ->once()
            ->with('TEST-123', 'account-id')
            ->andReturn(true);

        // Instantiate AssignIssueCommand with the mock JiraClient
        $command = new AssignIssueCommand($mockJiraClient);

        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Execute the command with issue key and assignee directly
        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute([
            '--issueKey' => 'TEST-123',
            '--assignee' => 'account-id',
        ]);

        // Verify successful exit code
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    public function testExecuteWithInteractiveUserSelection()
    {
        // Mock JiraClient to return a list of assignable users for any project key
        $mockJiraClient = Mockery::mock(JiraClient::class);
        $mockJiraClient->shouldReceive('getAssignableUsers')
            ->with('TEST')
            ->andReturn([
                ['name' => 'User One', 'accountId' => 'account-id-1'],
                ['name' => 'User Two', 'accountId' => 'account-id-2'],
            ]);

        // Expect assignIssue to be called with the selected account ID
        $mockJiraClient->shouldReceive('assignIssue')
            ->with('TEST-123', 'account-id-2')
            ->andReturn(true);

        $command = new AssignIssueCommand($mockJiraClient);

        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Set up CommandTester and provide inputs for interactive prompts
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['User Two']);

        // Execute the command without providing assignee to trigger interactive selection
        $exitCode = $commandTester->execute([
            '--issueKey' => 'TEST-123',
        ]);

        // Verify successful exit code
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    public function testExecuteWithFailedAssignment()
    {
        // Mock JiraClient to simulate a failed assignment
        $mockJiraClient = Mockery::mock(JiraClient::class);
        $mockJiraClient->shouldReceive('assignIssue')
            ->with('TEST-123', 'account-id')
            ->andReturn(false);

        $command = new AssignIssueCommand($mockJiraClient);

        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Execute the command with issue key and assignee directly
        $commandTester = new CommandTester($command);
        $exitCode = $commandTester->execute([
            '--issueKey' => 'TEST-123',
            '--assignee' => 'account-id',
        ]);

        // Verify failure exit code
        $this->assertEquals(Command::FAILURE, $exitCode);
    }
}
