<?php

namespace OguzhanTogay\JiraCLI\Command;

use OguzhanTogay\JiraCLI\JiraClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class CreateIssueCommand extends Command
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
            ->setName('create-issue')
            ->setDescription('Creates a new issue in Jira.')
            ->addOption('project', null, InputOption::VALUE_OPTIONAL, 'The project key')
            ->addOption('summary', null, InputOption::VALUE_OPTIONAL, 'The issue summary')
            ->addOption('description', null, InputOption::VALUE_OPTIONAL, 'The issue description')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'The issue type', 'Task')
            ->addOption('priority', null, InputOption::VALUE_OPTIONAL, 'The issue priority', 'Medium');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        // Interactive prompt for project key if not provided
        $projectKey = $input->getOption('project');
        if (!$projectKey) {
            $question = new Question('Enter the project key: ');
            $projectKey = $helper->ask($input, $output, $question);
        }

        // Interactive prompt for summary if not provided
        $summary = $input->getOption('summary');
        if (!$summary) {
            $question = new Question('Enter the issue summary: ');
            $summary = $helper->ask($input, $output, $question);
        }

        // Interactive prompt for description if not provided
        $description = $input->getOption('description');
        if (!$description) {
            $question = new Question('Enter the issue description (optional): ', '');
            $description = $helper->ask($input, $output, $question);
        }

        // Interactive prompt for issue type if not provided
        $issueType = $input->getOption('type');
        if (!$issueType) {
            $question = new Question('Enter the issue type (e.g., Bug, Task, Story): ', 'Task');
            $issueType = $helper->ask($input, $output, $question);
        }

        // Interactive prompt for priority if not provided
        $priority = $input->getOption('priority');
        if (!$priority) {
            $question = new Question('Enter the issue priority (e.g., Low, Medium, High): ', 'Medium');
            $priority = $helper->ask($input, $output, $question);
        }

        // Call the createIssue method on JiraClient
        $issue = $this->jiraClient->createIssue($projectKey, $summary, $description, $issueType, $priority);

        if ($issue) {
            $output->writeln(sprintf(
                '<info>Issue created successfully with key: %s</info>',
                $issue['key']
            ));
        } else {
            $output->writeln('<error>Failed to create the issue.</error>');
        }

        return Command::SUCCESS;
    }
}
