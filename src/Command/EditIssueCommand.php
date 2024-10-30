<?php

namespace OguzhanTogay\JiraCLI\Command;

use OguzhanTogay\JiraCLI\JiraClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class EditIssueCommand extends Command
{
    private JiraClient $jiraClient;

    public function __construct(JiraClient $jiraClient)
    {
        $this->jiraClient = $jiraClient;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('edit-issue')
            ->setDescription('Edits an issue in Jira.')
            ->addOption('issueKey', null, InputOption::VALUE_OPTIONAL, 'The key of the issue to edit')
            ->addOption('summary', null, InputOption::VALUE_OPTIONAL, 'The new summary of the issue')
            ->addOption('description', null, InputOption::VALUE_OPTIONAL, 'The new description of the issue')
            ->addOption('assignee', null, InputOption::VALUE_OPTIONAL, 'The username to assign the issue to')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'The new issue type')
            ->addOption('priority', null, InputOption::VALUE_OPTIONAL, 'The new priority level of the issue');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $issueKey = $input->getOption('issueKey');

        if (!$issueKey) {
            $question = new Question('Enter the issue key to edit: ');
            $issueKey = $helper->ask($input, $output, $question);
        }

        $fields = [];

        // Prompt for summary if not provided
        $summary = $input->getOption('summary');
        if (!$summary) {
            $question = new Question('Enter the new summary (leave blank to keep unchanged): ');
            $summary = $helper->ask($input, $output, $question);
        }
        if ($summary) {
            $fields['summary'] = $summary;
        }

        // Prompt for description if not provided
        $description = $input->getOption('description');
        if (!$description) {
            $question = new Question('Enter the new description (leave blank to keep unchanged): ');
            $description = $helper->ask($input, $output, $question);
        }
        if ($description) {
            $fields['description'] = $description;
        }

        // Prompt for assignee if not provided
        $assignee = $input->getOption('assignee');
        if (!$assignee) {
            $question = new Question('Enter the username to assign (leave blank to keep unchanged): ');
            $assignee = $helper->ask($input, $output, $question);
        }
        if ($assignee) {
            $fields['assignee'] = ['name' => $assignee];
        }

        // Prompt for issue type if not provided
        $issueType = $input->getOption('type');
        if (!$issueType) {
            $question = new Question('Enter the new issue type (e.g., Bug, Task, Story) (leave blank to keep unchanged): ');
            $issueType = $helper->ask($input, $output, $question);
        }
        if ($issueType) {
            $fields['issuetype'] = ['name' => $issueType];
        }

        // Prompt for priority if not provided
        $priority = $input->getOption('priority');
        if (!$priority) {
            $question = new Question('Enter the new priority (e.g., Low, Medium, High) (leave blank to keep unchanged): ');
            $priority = $helper->ask($input, $output, $question);
        }
        if ($priority) {
            $fields['priority'] = ['name' => $priority];
        }

        if (empty($fields)) {
            $output->writeln('<error>No fields provided for update.</error>');

            return Command::FAILURE;
        }

        if ($this->jiraClient->editIssue($issueKey, $fields)) {
            $output->writeln("<info>Issue {$issueKey} updated successfully.</info>");
        } else {
            $output->writeln("<error>Failed to update issue {$issueKey}.</error>");
        }

        return Command::SUCCESS;
    }
}
