<?php

namespace OguzhanTogay\JiraCLI\Tests\Command;

use Mockery;
use OguzhanTogay\JiraCLI\Command\ShowIssueCommand;
use OguzhanTogay\JiraCLI\JiraClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class ShowIssueCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testExecute()
    {
        // Step 1: Create a mock JiraClient
        $mockJiraClient = Mockery::mock(JiraClient::class);

        // Step 2: Set up the mock response for getIssueDetails
        $mockJiraClient->shouldReceive('getIssueDetails')
            ->with('TEST-123')
            ->andReturn([
                'key' => 'TEST-123',
                'fields' => [
                    'summary' => 'Sample Issue',
                    'status' => ['name' => 'Open'],
                    'description' => 'This is a sample issue for testing.',
                ],
            ]);

        // Step 3: Set up the mock expectation for showIssueDetails
        $mockJiraClient->shouldReceive('showIssueDetails')
            ->with(Mockery::type('array'), Mockery::type(OutputInterface::class))
            ->andReturnUsing(function ($issue, $output) {
                $output->writeln("Issue Key: {$issue['key']}");
                $output->writeln("Summary: {$issue['fields']['summary']}");
                $output->writeln("Status: {$issue['fields']['status']['name']}");
                $output->writeln("Description: {$issue['fields']['description']}");
            });

        // Step 4: Instantiate ShowIssueCommand with the mock JiraClient
        $command = new ShowIssueCommand($mockJiraClient);

        // Step 5: Set the application to enable the HelperSet
        $application = new Application();
        $application->add($command);
        $command->setApplication($application);

        // Step 6: Set up CommandTester and provide the issueKey option
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--issueKey' => 'TEST-123',
        ]);

        // Step 7: Check the output for expected details
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Issue Key: TEST-123', $output);
        $this->assertStringContainsString('Summary: Sample Issue', $output);
        $this->assertStringContainsString('Status: Open', $output);
        $this->assertStringContainsString('Description: This is a sample issue for testing.', $output);
    }
}
