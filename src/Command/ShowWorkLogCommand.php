<?php

namespace OguzhanTogay\JiraCLI\Command;

use DateTime;
use OguzhanTogay\JiraCLI\JiraClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
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
            ->setDescription('Shows the users worklog totals for a specified period.')
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

        // Determine date range based on period
        list($startDate, $endDate) = $this->calculateDateRange($period);

        // Output the date range we're looking at
        $output->writeln("<info>Time period: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}</info>");
        $output->writeln('');

        // Retrieve the worklog totals by date
        $totalsByDate = $this->jiraClient->getWorkLogTotalsByDateRange($accountId, $startDate, $endDate, $detailed);

        // Display worklog totals based on period
        $overallTotal = $this->displayWorkLogs($output, $totalsByDate, $period, $detailed);

        // Display overall total
        $overallHours = round($overallTotal / 3600, 2);
        $output->writeln("<info>Total: {$overallHours} hours</info>");

        return Command::SUCCESS;
    }

    /**
     * Calculate the start and end dates based on the period
     *
     * @param string $period
     * @return array [startDate, endDate]
     */
    private function calculateDateRange(string $period): array
    {
        $today = new DateTime();

        switch ($period) {
            case 'daily':
                // Just today
                $startDate = clone $today;
                $endDate = clone $today;
                break;

            case 'weekly':
                // Current week (Monday to Sunday)
                $startDate = clone $today;
                $currentDayOfWeek = (int)$today->format('N'); // 1 (Mon) to 7 (Sun)
                $daysToSubtract = $currentDayOfWeek - 1; // How many days to go back to Monday
                $startDate->modify("-{$daysToSubtract} days");

                $endDate = clone $startDate;
                $endDate->modify('+6 days'); // Go to Sunday

                // Make sure end date doesn't exceed today
                if ($endDate > $today) {
                    $endDate = clone $today;
                }
                break;

            case 'biweekly':
                // Current week and previous week
                $startDate = clone $today;
                $currentDayOfWeek = (int)$today->format('N');
                $daysToSubtract = $currentDayOfWeek - 1 + 7; // Go back to previous Monday
                $startDate->modify("-{$daysToSubtract} days");

                $endDate = clone $today;
                break;

            case 'monthly':
                // Current month
                $startDate = clone $today;
                $startDate->modify('first day of this month');

                $endDate = clone $today;
                $endDate->modify('last day of this month');

                // Make sure end date doesn't exceed today
                if ($endDate > $today) {
                    $endDate = clone $today;
                }
                break;

            default:
                // Default to today
                $startDate = clone $today;
                $endDate = clone $today;
        }

        // Reset time components for consistent comparisons
        $startDate->setTime(0, 0, 0);
        $endDate->setTime(23, 59, 59);

        return [$startDate, $endDate];
    }

    /**
     * Displays worklogs in appropriate table format based on period
     *
     * @param OutputInterface $output
     * @param array $totalsByDate
     * @param string $period
     * @param bool $detailed
     * @return int Total seconds worked
     */
    private function displayWorkLogs(OutputInterface $output, array $totalsByDate, string $period, bool $detailed): int
    {
        $overallTotal = 0;

        switch ($period) {
            case 'daily':
                $overallTotal = $this->displayDailyWorkLogs($output, $totalsByDate, $detailed);
                break;
            case 'weekly':
                $overallTotal = $this->displayWeeklyWorkLogs($output, $totalsByDate, $detailed);
                break;
            case 'biweekly':
                $overallTotal = $this->displayBiweeklyWorkLogs($output, $totalsByDate, $detailed);
                break;
            case 'monthly':
                $overallTotal = $this->displayMonthlyWorkLogs($output, $totalsByDate, $detailed);
                break;
        }

        return $overallTotal;
    }

    /**
     * Displays worklogs for a single day in a simple table
     *
     * @param OutputInterface $output
     * @param array $totalsByDate
     * @param bool $detailed
     * @return int Total seconds worked
     */
    private function displayDailyWorkLogs(OutputInterface $output, array $totalsByDate, bool $detailed): int
    {
        $overallTotal = 0;

        if (empty($totalsByDate)) {
            $output->writeln('<comment>No worklogs found for this day.</comment>');
            return 0;
        }

        $date = array_key_first($totalsByDate);
        $details = $totalsByDate[$date];

        if ($detailed && is_array($details)) {
            $table = new Table($output);
            $table->setHeaderTitle("Work Log - $date");
            $table->setHeaders(['Issue', 'Hours', 'Summary']);

            $rows = [];
            $dailyTotal = 0;

            foreach ($details as $issueKey => $data) {
                if (is_array($data)) {
                    $timeSpentSeconds = $data['timeSpent'];
                    $summary = $data['summary'] ?? '';
                } else {
                    $timeSpentSeconds = $data;
                    $summary = '';
                }

                $hours = round($timeSpentSeconds / 3600, 2);
                $rows[] = [$issueKey, $hours, $summary];
                $dailyTotal += $timeSpentSeconds;
            }

            if (!empty($rows)) {
                $rows[] = new TableSeparator();
                $rows[] = ['<info>Total</info>', '<info>' . round($dailyTotal / 3600, 2) . '</info>', ''];

                $table->setRows($rows);
                $table->render();

                $overallTotal = $dailyTotal;
            } else {
                $output->writeln('<comment>No worklogs found for this day.</comment>');
            }
        } else {
            if (is_array($details)) {
                $details = array_sum(array_map(function($item) {
                    return is_array($item) ? $item['timeSpent'] : $item;
                }, $details));
            }

            $hours = round($details / 3600, 2);
            $table = new Table($output);
            $table->setHeaderTitle("Work Log - $date");
            $table->setHeaders(['Date', 'Hours']);
            $table->setRows([[$date, $hours]]);
            $table->render();

            $overallTotal = $details;
        }

        return $overallTotal;
    }

    /**
     * Displays worklogs for a week in a 7-column table
     *
     * @param OutputInterface $output
     * @param array $totalsByDate
     * @param bool $detailed
     * @return int Total seconds worked
     */
    private function displayWeeklyWorkLogs(OutputInterface $output, array $totalsByDate, bool $detailed): int
    {
        if (empty($totalsByDate)) {
            $output->writeln('<comment>No worklogs found for this week.</comment>');
            return 0;
        }

        $table = new Table($output);
        $table->setHeaderTitle('Weekly Work Log');

        // Setup headers (Mon-Sun)
        $headers = ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $table->setHeaders($headers);

        $overallTotal = 0;
        $rows = [];

        if ($detailed) {
            // Collect all issue keys across all days
            $allIssueKeys = [];
            foreach ($totalsByDate as $dateDetails) {
                if (is_array($dateDetails)) {
                    foreach ($dateDetails as $issueKey => $data) {
                        $allIssueKeys[$issueKey] = true;
                    }
                }
            }

            // Create a row for each issue
            foreach (array_keys($allIssueKeys) as $issueKey) {
                $row = [$issueKey];

                // Get day of week for each date
                foreach ($totalsByDate as $date => $dateDetails) {
                    $dayOfWeek = (int)(new DateTime($date))->format('N'); // 1 (Mon) to 7 (Sun)

                    if (is_array($dateDetails) && isset($dateDetails[$issueKey])) {
                        $timeSpent = is_array($dateDetails[$issueKey])
                            ? $dateDetails[$issueKey]['timeSpent']
                            : $dateDetails[$issueKey];

                        $hours = round($timeSpent / 3600, 2);
                        $row[$dayOfWeek] = $hours;
                        $overallTotal += $timeSpent;
                    } else {
                        $row[$dayOfWeek] = '-';
                    }
                }

                // Fill in any missing days with -
                for ($i = 1; $i <= 7; $i++) {
                    if (!isset($row[$i])) {
                        $row[$i] = '-';
                    }
                }

                // Re-order row to make sure days are in correct sequence
                ksort($row);
                $rows[] = $row;
            }
        } else {
            // Create a single row with daily totals
            $row = ['Total'];
            $weekdayHours = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0]; // Initialize for Mon-Sun

            foreach ($totalsByDate as $date => $seconds) {
                $dayOfWeek = (int)(new DateTime($date))->format('N');
                $hours = is_array($seconds)
                    ? round(array_sum(array_map(function($item) {
                            return is_array($item) ? $item['timeSpent'] : $item;
                        }, $seconds)) / 3600, 2)
                    : round($seconds / 3600, 2);

                $weekdayHours[$dayOfWeek] = $hours;

                // Calculate overall total
                $overallTotal += is_array($seconds)
                    ? array_sum(array_map(function($item) {
                        return is_array($item) ? $item['timeSpent'] : $item;
                    }, $seconds))
                    : $seconds;
            }

            // Add weekday hours to row
            for ($i = 1; $i <= 7; $i++) {
                $row[$i] = $weekdayHours[$i] ?: '-';
            }

            // Re-order row to make sure days are in correct sequence
            ksort($row);
            $rows[] = $row;
        }

        if (!empty($rows)) {
            // Add a total row
            $totalRow = ['<info>Total</info>'];
            for ($i = 1; $i <= 7; $i++) {
                $dailyTotal = 0;
                foreach ($rows as $row) {
                    if ($row[$i] !== '-') {
                        $dailyTotal += $row[$i];
                    }
                }
                $totalRow[$i] = $dailyTotal ? '<info>' . $dailyTotal . '</info>' : '-';
            }

            $rows[] = new TableSeparator();
            $rows[] = $totalRow;

            $table->setRows($rows);
            $table->render();
        } else {
            $output->writeln('<comment>No worklogs found for this week.</comment>');
        }

        return $overallTotal;
    }

    /**
     * Displays worklogs for two weeks in two separate tables
     *
     * @param OutputInterface $output
     * @param array $totalsByDate
     * @param bool $detailed
     * @return int Total seconds worked
     */
    private function displayBiweeklyWorkLogs(OutputInterface $output, array $totalsByDate, bool $detailed): int
    {
        if (empty($totalsByDate)) {
            $output->writeln('<comment>No worklogs found for this period.</comment>');
            return 0;
        }

        // Split the data into two weeks
        $firstWeek = [];
        $secondWeek = [];
        $overallTotal = 0;

        // Find the earliest date
        $dates = array_keys($totalsByDate);
        sort($dates);

        if (count($dates) > 0) {
            $firstDate = new DateTime($dates[0]);
            $splitDate = (clone $firstDate)->modify('+7 days');

            foreach ($totalsByDate as $date => $data) {
                $currentDate = new DateTime($date);
                if ($currentDate < $splitDate) {
                    $firstWeek[$date] = $data;
                } else {
                    $secondWeek[$date] = $data;
                }
            }

            // Display each week separately
            $output->writeln('<info>Week 1:</info>');
            $firstWeekTotal = $this->displayWeeklyWorkLogs($output, $firstWeek, $detailed);

            $output->writeln('');
            $output->writeln('<info>Week 2:</info>');
            $secondWeekTotal = $this->displayWeeklyWorkLogs($output, $secondWeek, $detailed);

            $overallTotal = $firstWeekTotal + $secondWeekTotal;
        } else {
            $output->writeln('<comment>No worklogs found for this period.</comment>');
        }

        return $overallTotal;
    }

    /**
     * Displays worklogs for a month in a calendar format
     *
     * @param OutputInterface $output
     * @param array $totalsByDate
     * @param bool $detailed
     * @return int Total seconds worked
     */
    private function displayMonthlyWorkLogs(OutputInterface $output, array $totalsByDate, bool $detailed): int
    {
        $overallTotal = 0;

        // Get the month and year from the first date
        $dates = array_keys($totalsByDate);
        if (empty($dates)) {
            $output->writeln('<comment>No worklogs found for this month.</comment>');
            return 0;
        }

        sort($dates);
        $firstDate = new DateTime($dates[0]);
        $monthYear = $firstDate->format('F Y');

        $table = new Table($output);
        $table->setHeaderTitle("Monthly Work Log - $monthYear");
        $table->setHeaders(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']);

        // Start with the first day of the month
        $startDay = (clone $firstDate)->modify('first day of this month');
        $dayOfWeek = (int)$startDay->format('N'); // 1 (Mon) to 7 (Sun)

        // Create calendar grid
        $rows = [];
        $currentRow = array_fill(1, 7, '');

        // Fill in empty cells before the first day
        for ($i = 1; $i < $dayOfWeek; $i++) {
            $currentRow[$i] = '';
        }

        // Get last day of month
        $lastDay = (clone $startDay)->modify('last day of this month');
        $totalDays = (int)$lastDay->format('d');

        for ($day = 1; $day <= $totalDays; $day++) {
            $currentDate = (clone $startDay)->modify('+' . ($day - 1) . ' days');
            $dayOfWeek = (int)$currentDate->format('N');
            $dateString = $currentDate->format('Y-m-d');

            // Format the hours for this day
            $hourText = '';
            if (isset($totalsByDate[$dateString])) {
                $data = $totalsByDate[$dateString];
                if (is_array($data)) {
                    $seconds = array_sum(array_map(function($item) {
                        return is_array($item) ? $item['timeSpent'] : $item;
                    }, $data));
                } else {
                    $seconds = $data;
                }

                $hours = round($seconds / 3600, 2);
                $hourText = $hours > 0 ? $hours : '';
                $overallTotal += $seconds;
            }

            // Format day cell
            $currentRow[$dayOfWeek] = $day . ($hourText ? "\n<info>$hourText h</info>" : '');

            // Start a new row after Sunday or at the end of month
            if ($dayOfWeek === 7 || $day === $totalDays) {
                // Fill in any remaining empty cells
                for ($i = $dayOfWeek + 1; $i <= 7; $i++) {
                    $currentRow[$i] = '';
                }

                // Re-order row to make sure days are in correct sequence
                ksort($currentRow);
                $rows[] = $currentRow;
                $currentRow = array_fill(1, 7, '');
            }
        }

        $table->setRows($rows);
        $table->render();

        // Display issue breakdown if detailed mode
        if ($detailed && $overallTotal > 0) {
            $output->writeln('');
            $output->writeln('<info>Issue Breakdown:</info>');

            $issueTable = new Table($output);
            $issueTable->setHeaders(['Issue', 'Hours', 'Summary']);

            // Collect all issues across all days
            $issueData = [];
            foreach ($totalsByDate as $date => $dateDetails) {
                if (is_array($dateDetails)) {
                    foreach ($dateDetails as $issueKey => $data) {
                        if (!isset($issueData[$issueKey])) {
                            $issueData[$issueKey] = [
                                'timeSpent' => 0,
                                'summary' => is_array($data) ? ($data['summary'] ?? '') : ''
                            ];
                        }

                        $issueData[$issueKey]['timeSpent'] += is_array($data) ? $data['timeSpent'] : $data;
                    }
                }
            }

            // Sort issues by time spent (descending)
            uasort($issueData, function ($a, $b) {
                return $b['timeSpent'] <=> $a['timeSpent'];
            });

            $rows = [];
            foreach ($issueData as $issueKey => $data) {
                $hours = round($data['timeSpent'] / 3600, 2);
                $rows[] = [$issueKey, $hours, $data['summary']];
            }

            if (!empty($rows)) {
                $rows[] = new TableSeparator();
                $rows[] = ['<info>Total</info>', '<info>' . round($overallTotal / 3600, 2) . '</info>', ''];

                $issueTable->setRows($rows);
                $issueTable->render();
            }
        }

        return $overallTotal;
    }
}