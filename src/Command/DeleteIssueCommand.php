<?php

namespace OguzhanTogay\JiraCLI\Command;

use OguzhanTogay\JiraCLI\JiraClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class DeleteIssueCommand extends Command
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
            ->setName('delete-issue')
            ->setDescription('Deletes an issue in Jira.')
            ->addOption('issueKey', null, InputOption::VALUE_OPTIONAL, 'The key of the issue to delete');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $issueKey = $input->getOption('issueKey');

        if (!$issueKey) {
            $question = new Question('Enter the issue key to delete: ');
            $issueKey = $helper->ask($input, $output, $question);
        }

        if ($this->jiraClient->deleteIssue($issueKey)) {
            $output->writeln("<info>Issue {$issueKey} deleted successfully.</info>");
        } else {
            $output->writeln("<error>Failed to delete issue {$issueKey}.</error>");
        }

        return Command::SUCCESS;
    }
}
