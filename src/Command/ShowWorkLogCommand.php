<?php

namespace OguzhanTogay\JiraCLI\Command;

use DateTime;
use OguzhanTogay\JiraCLI\JiraClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ShowWorkLogCommand extends Command
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
            ->setName('show-work-log')
            ->setDescription('Shows the user’s worklog totals for a specified period.')
            ->addOption('period', null, InputOption::VALUE_OPTIONAL, 'The period for worklogs (daily, weekly, biweekly, monthly)', 'daily')
            ->addOption('detailed', null, InputOption::VALUE_NONE, 'Show detailed worklog breakdown by issue')
            ->addOption('accountId', null, InputOption::VALUE_OPTIONAL, 'The accountId of the user to show worklogs for');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $accountId = $input->getOption('accountId') ?? $_ENV['JIRA_ACCOUNT_ID'] ?? null;

        if (!$accountId) {
            $output->writeln('<error>Please provide an accountId using the --accountId option.</error>');

            return Command::FAILURE;
        }

        $period = $input->getOption('period') ?? 'daily';
        $detailed = $input->getOption('detailed');

        // Determine date range
        $endDate = new DateTime();
        switch ($period) {
            case 'weekly':
                $startDate = (clone $endDate)->modify('-6 days');
                break;
            case 'biweekly':
                $startDate = (clone $endDate)->modify('-13 days');
                break;
            case 'monthly':
                $startDate = (clone $endDate)->modify('-29 days');
                break;
            case 'daily':
            default:
                $startDate = clone $endDate;
                break;
        }

        // Retrieve the worklog totals by date
        $totalsByDate = $this->jiraClient->getWorkLogTotalsByDateRange($accountId, $startDate, $endDate, $detailed);

        // Display worklog totals
        $overallTotal = 0;
        foreach ($totalsByDate as $date => $details) {
            if ($detailed && is_array($details)) {
                $output->writeln("Date: {$date}");
                $dailyTotal = 0;
                foreach ($details as $issueKey => $timeSpentSeconds) {
                    $hours = round($timeSpentSeconds / 3600, 2);
                    $output->writeln("  Issue {$issueKey}: {$hours} hours");
                    $dailyTotal += $timeSpentSeconds;
                }
                $output->writeln('  Daily Total: ' . round($dailyTotal / 3600, 2) . ' hours');
                $overallTotal += $dailyTotal;
            } else {
                $hours = round($details / 3600, 2);
                $output->writeln("{$date}: {$hours} hours");
                $overallTotal += $details;
            }
        }

        // Display overall total
        $overallHours = round($overallTotal / 3600, 2);
        $output->writeln("<info>Total: {$overallHours} hours</info>");

        return Command::SUCCESS;
    }
}
