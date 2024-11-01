<?php

namespace OguzhanTogay\JiraCLI\Command;

use OguzhanTogay\JiraCLI\JiraClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowUserDetailCommand extends Command
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
            ->setName('show-user-detail')
            ->setDescription('Shows details of the currently authenticated Jira user.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userDetails = $this->jiraClient->getUserDetails();

        if (!$userDetails) {
            $output->writeln('<error>Failed to retrieve user details.</error>');

            return Command::FAILURE;
        }

        $output->writeln('<info>User Details:</info>');
        $output->writeln('Account ID: ' . $userDetails['accountId']);
        $output->writeln('Display Name: ' . $userDetails['displayName']);
        $output->writeln('Email Address: ' . ($userDetails['emailAddress'] ?? 'N/A'));
        $output->writeln('Time Zone: ' . ($userDetails['timeZone'] ?? 'N/A'));
        $output->writeln('Locale: ' . ($userDetails['locale'] ?? 'N/A'));

        return Command::SUCCESS;
    }
}
