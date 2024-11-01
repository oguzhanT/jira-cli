<?php

namespace OguzhanTogay\JiraCLI\Command;

use OguzhanTogay\JiraCLI\JiraClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class CreateProjectCommand extends Command
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
            ->setName('create-project')
            ->setDescription('Creates a new project in Jira.')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'The name of the project')
            ->addOption('key', null, InputOption::VALUE_OPTIONAL, 'The project key')
            ->addOption('projectTypeKey', null, InputOption::VALUE_OPTIONAL, 'The type of the project (e.g., software, business)')
            ->addOption('lead', null, InputOption::VALUE_OPTIONAL, 'The username of the project lead');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        $name = $input->getOption('name') ?: $helper->ask($input, $output, new Question('Enter project name: '));
        $key = $input->getOption('key') ?: $helper->ask($input, $output, new Question('Enter project key: '));
        $projectTypeKey = $input->getOption('projectTypeKey') ?: $helper->ask($input, $output, new Question('Enter project type (e.g., software, business): '));
        $lead = $input->getOption('lead') ?: $helper->ask($input, $output, new Question('Enter project lead username: '));

        $project = $this->jiraClient->createProject($name, $key, $projectTypeKey, $lead);

        if ($project) {
            $output->writeln("<info>Project '{$name}' created successfully with key '{$key}'.</info>");
        } else {
            $output->writeln("<error>Failed to create project '{$name}'.</error>");
        }

        return Command::SUCCESS;
    }
}
