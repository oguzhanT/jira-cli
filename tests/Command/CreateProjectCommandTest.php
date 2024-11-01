<?php

namespace OguzhanTogay\JiraCLI\Tests\Command;

use Mockery;
use OguzhanTogay\JiraCLI\Command\CreateProjectCommand;
use OguzhanTogay\JiraCLI\JiraClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CreateProjectCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testExecuteWithAllOptions()
    {
        // Step 1: Create a mock JiraClient
        $mockJiraClient = Mockery::mock(JiraClient::class);

        // Step 2: Set up the mock expectation for createProject
        $mockJiraClient->shouldReceive('createProject')
            ->with('My Project', 'MP', 'software', 'projectlead')
            ->andReturn([
                'name' => 'My Project',
                'key' => 'MP',
            ]);

        // Step 3: Instantiate CreateProjectCommand with the mock JiraClient
        $command = new CreateProjectCommand($mockJiraClient);

        // Step 4: Set the application to enable the HelperSet
        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Step 5: Set up CommandTester and provide all options
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--name' => 'My Project',
            '--key' => 'MP',
            '--projectTypeKey' => 'software',
            '--lead' => 'projectlead',
        ]);

        // Step 6: Check the output for expected success message
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString("Project 'My Project' created successfully with key 'MP'", $output);
    }

    public function testExecuteWithFailedCreation()
    {
        // Step 1: Create a mock JiraClient
        $mockJiraClient = Mockery::mock(JiraClient::class);

        // Step 2: Set up the mock to return null to simulate failure
        $mockJiraClient->shouldReceive('createProject')
            ->with('My Project', 'MP', 'software', 'projectlead')
            ->andReturn(null);

        // Step 3: Instantiate CreateProjectCommand with the mock JiraClient
        $command = new CreateProjectCommand($mockJiraClient);

        // Step 4: Set the application to enable the HelperSet
        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Step 5: Set up CommandTester and provide all options
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--name' => 'My Project',
            '--key' => 'MP',
            '--projectTypeKey' => 'software',
            '--lead' => 'projectlead',
        ]);

        // Step 6: Check the output for expected failure message
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString("Failed to create project 'My Project'", $output);
    }

    public function testExecuteWithInteractivePrompts()
    {
        // Step 1: Create a mock JiraClient
        $mockJiraClient = Mockery::mock(JiraClient::class);

        // Step 2: Set up the mock expectation for createProject
        $mockJiraClient->shouldReceive('createProject')
            ->with('Interactive Project', 'IP', 'software', 'interactivelead')
            ->andReturn([
                'name' => 'Interactive Project',
                'key' => 'IP',
            ]);

        // Step 3: Instantiate CreateProjectCommand with the mock JiraClient
        $command = new CreateProjectCommand($mockJiraClient);

        // Step 4: Set the application to enable the HelperSet
        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Step 5: Set up CommandTester and provide inputs for interactive prompts
        $commandTester = new CommandTester($command);
        $commandTester->setInputs([
            'Interactive Project', // Project name
            'IP',                  // Project key
            'software',            // Project type
            'interactivelead',      // Project lead
        ]);

        // Step 6: Execute the command without options to trigger interactive prompts
        $commandTester->execute([]);

        // Step 7: Check the output for expected success message
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString("Project 'Interactive Project' created successfully with key 'IP'", $output);
    }
}
