<?php

namespace OguzhanTogay\JiraCLI\Tests\Command;

use Mockery;
use OguzhanTogay\JiraCLI\Command\CreateIssueCommand;
use OguzhanTogay\JiraCLI\JiraClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CreateIssueCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testExecuteWithInteractivePrompts()
    {
        // Step 1: Create a mock JiraClient
        $mockJiraClient = Mockery::mock(JiraClient::class);

        // Step 2: Set up the mock response for createIssue
        $mockJiraClient->shouldReceive('createIssue')
            ->with('TEST', 'New Issue', 'This is a test issue description', 'Task', Mockery::any())
            ->andReturn([
                'key' => 'TEST-123',
                'fields' => [
                    'summary' => 'New Issue',
                    'status' => ['name' => 'To Do'],
                ],
            ]);

        // Step 3: Instantiate CreateIssueCommand with the mock JiraClient
        $command = new CreateIssueCommand($mockJiraClient);

        // Step 4: Set the application to enable the HelperSet
        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Step 5: Set up CommandTester and provide interactive inputs
        $commandTester = new CommandTester($command);
        $commandTester->setInputs([
            'TEST',                // Project key
            'New Issue',           // Summary
            'This is a test issue description', // Description
            'Task',                // Issue type
            'Medium',               // Priority - provide explicit input here
        ]);

        // Step 6: Execute the command
        $commandTester->execute([]);

        // Step 7: Check the output for expected success message
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Issue created successfully with key: TEST-123', $output);
    }
}
