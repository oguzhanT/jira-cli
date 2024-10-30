<?php

namespace OguzhanTogay\JiraCLI\Command;

use OguzhanTogay\JiraCLI\JiraClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListProjectsCommand extends Command
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
            ->setName('list-projects')
            ->setDescription('Lists all available Jira projects.');
    }

    /**
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projects = $this->jiraClient->getProjects();
        $this->jiraClient->listProjects($projects, $output);

        return Command::SUCCESS;
    }
}
