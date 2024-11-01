<?php

namespace OguzhanTogay\JiraCLI\Command;

use OguzhanTogay\JiraCLI\JiraClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigureAccountIdCommand extends Command
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
            ->setName('configure-account-id')
            ->setDescription('Fetches your Jira accountId and sets it in the .env file as JIRA_ACCOUNT_ID.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Step 1: Retrieve the user’s accountId
        $userDetails = $this->jiraClient->getUserDetails();
        if (!$userDetails || !isset($userDetails['accountId'])) {
            $output->writeln('<error>Failed to retrieve accountId. Please check your Jira credentials.</error>');

            return Command::FAILURE;
        }

        $accountId = $userDetails['accountId'];

        // Step 2: Set accountId in .env file
        if ($this->jiraClient->setAccountIdInEnv($accountId)) {
            $output->writeln("<info>JIRA_ACCOUNT_ID set to '{$accountId}' in .env file.</info>");

            return Command::SUCCESS;
        } else {
            $output->writeln('<error>Failed to set JIRA_ACCOUNT_ID in .env file. Ensure .env file exists and is writable.</error>');

            return Command::FAILURE;
        }
    }
}
