<?php

namespace OguzhanTogay\JiraCLI\Command;

use GuzzleHttp\Exception\GuzzleException;
use OguzhanTogay\JiraCLI\JiraClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ShowIssueCommand extends Command
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
            ->setName('show-issue')
            ->setDescription('Shows details for a specific issue.')
            ->addOption('issueKey', null, InputOption::VALUE_REQUIRED, 'The ID or key of the Jira issue');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $issueKey = $input->getOption('issueKey');
        if (!$issueKey) {
            $output->writeln('<error>Error: The --issueKey option is required.</error>');

            return Command::FAILURE;
        }

        try {
            $issue = $this->jiraClient->getIssueDetails($issueKey);

            // Check if issue is retrieved successfully
            if ($issue === null) {
                $output->writeln("<error>Error: Issue with key '{$issueKey}' not found or could not be retrieved.</error>");

                return Command::FAILURE;
            }

            $this->jiraClient->showIssueDetails($issue, $output);

            return Command::SUCCESS;
        } catch (GuzzleException $e) {
            $output->writeln("<error>Error: Failed to retrieve issue details. {$e->getMessage()}</error>");

            return Command::FAILURE;
        }
    }
}
