<?php

namespace OguzhanTogay\JiraCLI\Command;

use GuzzleHttp\Exception\GuzzleException;
use OguzhanTogay\JiraCLI\JiraClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListIssuesCommand extends Command
{
    private JiraClient $jiraClient;

    public function __construct(JiraClient $jiraClient)
    {
        $this->jiraClient = $jiraClient;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('list-issues')
            ->setDescription('Lists issues for a specified project.')
            ->addOption('project', 'p', InputOption::VALUE_REQUIRED, 'The project key')
            ->addOption('status', 's', InputOption::VALUE_OPTIONAL, 'Filter issues by status')
            ->addOption('range', 'r', InputOption::VALUE_OPTIONAL, 'Specify a range for pagination, e.g., "5-10"', '0-9');
    }

    /**
     * @throws GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectKey = $input->getOption('project');
        if (!$projectKey) {
            $output->writeln('<error>Error: The --project option is required.</error>');

            return Command::FAILURE;
        }

        $status = $input->getOption('status');
        $range = $input->getOption('range');
        [$start, $end] = explode('-', $range);
        $startAt = (int) $start;
        $maxResults = (int) $end - (int) $start + 1;

        $issues = $this->jiraClient->getIssuesByProject($projectKey, $status, $startAt, $maxResults);
        $this->jiraClient->listIssues($issues, $output);

        return Command::SUCCESS;
    }
}
