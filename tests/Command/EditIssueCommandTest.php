<?php

namespace OguzhanTogay\JiraCLI\Tests\Command;

use Mockery;
use OguzhanTogay\JiraCLI\Command\EditIssueCommand;
use OguzhanTogay\JiraCLI\JiraClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class EditIssueCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testExecuteWithAllOptions()
    {
        // Step 1: Create a mock JiraClient
        $mockJiraClient = Mockery::mock(JiraClient::class);

        // Step 2: Set up the mock expectation for editIssue
        $mockJiraClient->shouldReceive('editIssue')
            ->with('TEST-123', [
                'summary' => 'Updated summary',
                'description' => 'Updated description',
                'assignee' => ['name' => 'testuser'],
                'issuetype' => ['name' => 'Bug'],
                'priority' => ['name' => 'High'],
            ])
            ->andReturn(true);

        // Step 3: Instantiate EditIssueCommand with the mock JiraClient
        $command = new EditIssueCommand($mockJiraClient);

        // Step 4: Set the application to enable the HelperSet
        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Step 5: Set up CommandTester and provide all options
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--issueKey' => 'TEST-123',
            '--summary' => 'Updated summary',
            '--description' => 'Updated description',
            '--assignee' => 'testuser',
            '--type' => 'Bug',
            '--priority' => 'High',
        ]);

        // Step 6: Check the output for expected success message
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Issue TEST-123 updated successfully', $output);
    }

    public function testExecuteWithInteractivePrompts()
    {
        // Step 1: Create a mock JiraClient
        $mockJiraClient = Mockery::mock(JiraClient::class);

        // Step 2: Set up the mock expectation for editIssue
        $mockJiraClient->shouldReceive('editIssue')
            ->with('TEST-456', [
                'summary' => 'Interactive summary',
                'description' => 'Interactive description',
                'assignee' => ['name' => 'interactiveuser'],
                'issuetype' => ['name' => 'Task'],
                'priority' => ['name' => 'Medium'],
            ])
            ->andReturn(true);

        // Step 3: Instantiate EditIssueCommand with the mock JiraClient
        $command = new EditIssueCommand($mockJiraClient);

        // Step 4: Set the application to enable the HelperSet
        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Step 5: Set up CommandTester and provide inputs for interactive prompts
        $commandTester = new CommandTester($command);
        $commandTester->setInputs([
            'TEST-456',            // Issue key
            'Interactive summary',  // Summary
            'Interactive description', // Description
            'interactiveuser',      // Assignee
            'Task',                 // Issue type
            'Medium',                // Priority
        ]);

        // Step 6: Execute the command without options to trigger interactive prompts
        $commandTester->execute([]);

        // Step 7: Check the output for expected success message
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Issue TEST-456 updated successfully', $output);
    }
}
