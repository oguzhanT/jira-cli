<?php

namespace OguzhanTogay\JiraCLI\Tests\Command;

use Mockery;
use OguzhanTogay\JiraCLI\Command\ListIssuesCommand;
use OguzhanTogay\JiraCLI\JiraClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class ListIssuesCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testExecute()
    {
        // Step 1: Create a mock JiraClient
        $mockJiraClient = Mockery::mock(JiraClient::class);

        // Step 2: Set up the mock response for getIssuesByProject
        $mockJiraClient->shouldReceive('getIssuesByProject')
            ->with('TEST', 'Open', 0, 10)
            ->andReturn([
                [
                    'key' => 'TEST-1',
                    'fields' => [
                        'summary' => 'Issue 1',
                        'status' => ['name' => 'Open'],
                    ],
                ],
                [
                    'key' => 'TEST-2',
                    'fields' => [
                        'summary' => 'Issue 2',
                        'status' => ['name' => 'Open'],
                    ],
                ],
            ]);

        // Step 3: Mock listIssues to handle output to OutputInterface
        $mockJiraClient->shouldReceive('listIssues')
            ->andReturnUsing(function ($issues, OutputInterface $output) {
                foreach ($issues as $issue) {
                    $output->writeln(sprintf(
                        '%s (%s): %s',
                        $issue['key'],
                        $issue['fields']['status']['name'],
                        $issue['fields']['summary']
                    ));
                }
            });

        // Step 4: Instantiate ListIssuesCommand with the mock JiraClient
        $command = new ListIssuesCommand($mockJiraClient);
        $commandTester = new CommandTester($command);

        // Step 5: Execute the command with the required options
        $commandTester->execute([
            '--project' => 'TEST',
            '--status' => 'Open',
        ]);

        // Step 6: Check the output for expected issue details
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('TEST-1', $output);
        $this->assertStringContainsString('Issue 1', $output);
        $this->assertStringContainsString('Open', $output);

        $this->assertStringContainsString('TEST-2', $output);
        $this->assertStringContainsString('Issue 2', $output);
        $this->assertStringContainsString('Open', $output);
    }
}
