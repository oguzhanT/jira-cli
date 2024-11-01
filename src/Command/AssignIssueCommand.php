<?php

namespace OguzhanTogay\JiraCLI\Command;

use OguzhanTogay\JiraCLI\JiraClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class AssignIssueCommand extends Command
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
            ->setName('assign-issue')
            ->setDescription('Assigns a user to a Jira issue.')
            ->addOption('issueKey', null, InputOption::VALUE_OPTIONAL, 'The key of the issue to assign')
            ->addOption('assignee', null, InputOption::VALUE_OPTIONAL, 'The account ID of the user to assign to the issue')
            ->addOption('projectKey', null, InputOption::VALUE_OPTIONAL, 'The key of the project to list assignable users');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        // Retrieve or prompt for the issue key
        $issueKey = $input->getOption('issueKey') ?: $helper->ask($input, $output, new Question('Enter issue key: '));
        $selectedName = '';
        // Retrieve or prompt for the assignee
        $assignee = $input->getOption('assignee');
        if (!$assignee) {
            $projectKey = explode('-', $issueKey)[0];
            $users = $this->jiraClient->getAssignableUsers($projectKey);

            if (empty($users)) {
                $output->writeln('<error>No assignable users found for the project.</error>');

                return Command::FAILURE;
            }

            // Prepare choices for ChoiceQuestion
            $choices = array_column($users, 'name');
            $choiceQuestion = new ChoiceQuestion('Select assignee:', $choices);
            $choiceQuestion->setErrorMessage('Assignee %s is invalid.');

            // Ask for the assignee from choices and get the corresponding accountId
            $selectedName = $helper->ask($input, $output, $choiceQuestion);
            $selectedUser = array_filter($users, fn ($user) => $user['name'] === $selectedName);
            $assignee = array_values($selectedUser)[0]['accountId'];
        }

        // Assign the issue
        if ($this->jiraClient->assignIssue($issueKey, $assignee)) {
            $output->writeln("<info>Issue '{$issueKey}' assigned to '{$selectedName}' successfully.</info>");

            return Command::SUCCESS;
        } else {
            $output->writeln("<error>Failed to assign issue '{$issueKey}' to '{$selectedName}'.</error>");

            return Command::FAILURE;
        }
    }
}
