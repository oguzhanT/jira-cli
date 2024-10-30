<?php

namespace OguzhanTogay\JiraCLI\Tests\Command;

use Mockery;
use OguzhanTogay\JiraCLI\Command\DeleteIssueCommand;
use OguzhanTogay\JiraCLI\JiraClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteIssueCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testExecuteWithOption()
    {
        // Step 1: Create a mock JiraClient
        $mockJiraClient = Mockery::mock(JiraClient::class);

        // Step 2: Set up the mock expectation for deleteIssue
        $mockJiraClient->shouldReceive('deleteIssue')
            ->with('TEST-123')
            ->andReturn(true);

        // Step 3: Instantiate DeleteIssueCommand with the mock JiraClient
        $command = new DeleteIssueCommand($mockJiraClient);

        // Step 4: Set the application to enable the HelperSet
        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Step 5: Set up CommandTester and provide the issueKey option
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--issueKey' => 'TEST-123',
        ]);

        // Step 6: Check the output for expected success message
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Issue TEST-123 deleted successfully', $output);
    }

    public function testExecuteWithInteractivePrompt()
    {
        // Step 1: Create a mock JiraClient
        $mockJiraClient = Mockery::mock(JiraClient::class);

        // Step 2: Set up the mock expectation for deleteIssue
        $mockJiraClient->shouldReceive('deleteIssue')
            ->with('TEST-456')
            ->andReturn(true);

        // Step 3: Instantiate DeleteIssueCommand with the mock JiraClient
        $command = new DeleteIssueCommand($mockJiraClient);

        // Step 4: Set the application to enable the HelperSet
        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Step 5: Set up CommandTester and provide inputs for interactive prompt
        $commandTester = new CommandTester($command);
        $commandTester->setInputs([
            'TEST-456',  // Issue key entered interactively
        ]);

        // Step 6: Execute the command without options to trigger interactive prompt
        $commandTester->execute([]);

        // Step 7: Check the output for expected success message
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Issue TEST-456 deleted successfully', $output);
    }

    public function testExecuteWithFailedDeletion()
    {
        // Step 1: Create a mock JiraClient
        $mockJiraClient = Mockery::mock(JiraClient::class);

        // Step 2: Set up the mock expectation for deleteIssue to return false
        $mockJiraClient->shouldReceive('deleteIssue')
            ->with('TEST-789')
            ->andReturn(false);

        // Step 3: Instantiate DeleteIssueCommand with the mock JiraClient
        $command = new DeleteIssueCommand($mockJiraClient);

        // Step 4: Set the application to enable the HelperSet
        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Step 5: Set up CommandTester and provide the issueKey option
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--issueKey' => 'TEST-789',
        ]);

        // Step 6: Check the output for expected failure message
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Failed to delete issue TEST-789', $output);
    }
}
