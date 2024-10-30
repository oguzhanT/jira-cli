<?php

namespace OguzhanTogay\JiraCLI\Tests\Command;

use Mockery;
use OguzhanTogay\JiraCLI\Command\ListProjectsCommand;
use OguzhanTogay\JiraCLI\JiraClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class ListProjectsCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testExecute()
    {
        // Step 1: Create a mock JiraClient
        $mockJiraClient = Mockery::mock(JiraClient::class);

        // Step 2: Set up the mock response for getProjects
        $mockJiraClient->shouldReceive('getProjects')
            ->andReturn([
                ['key' => 'PROJ1', 'name' => 'Project 1'],
                ['key' => 'PROJ2', 'name' => 'Project 2'],
            ]);

        // Step 3: Mock listProjects to handle output to OutputInterface
        $mockJiraClient->shouldReceive('listProjects')
            ->andReturnUsing(function ($projects, OutputInterface $output) {
                foreach ($projects as $project) {
                    $output->writeln(sprintf(
                        '%s: %s',
                        $project['key'],
                        $project['name']
                    ));
                }
            });

        // Step 4: Instantiate ListProjectsCommand with the mock JiraClient
        $command = new ListProjectsCommand($mockJiraClient);
        $commandTester = new CommandTester($command);

        // Step 5: Execute the command
        $commandTester->execute([]);

        // Step 6: Check the output for expected project details
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('PROJ1: Project 1', $output);
        $this->assertStringContainsString('PROJ2: Project 2', $output);
    }
}
