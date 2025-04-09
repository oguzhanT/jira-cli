<?php

namespace OguzhanTogay\JiraCLI;

use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Console\Output\OutputInterface;

class JiraClient
{
    private string $baseUri;
    private string $username;
    private string $apiToken;
    private Client $client;

    public function __construct(string $baseUri, string $username, string $apiToken, Client $client = null)
    {
        $this->baseUri = rtrim($baseUri, '/');
        $this->username = $username;
        $this->apiToken = $apiToken;

        // Accept an injected client or create a default one if none provided
        $this->client = $client ?: new Client([
            'base_uri' => $this->baseUri,
            'auth' => [$this->username, $this->apiToken],
        ]);
    }

    /**
     * Fetches a list of Jira projects.
     *
     * @return array The list of projects.
     */
    public function getProjects(): array
    {
        try {
            $response = $this->client->get('/rest/api/3/project');

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException|GuzzleException $e) {
            return [];
        }
    }

    /**
     * Outputs a list of Jira projects.
     *
     * @param array           $projects The list of projects.
     * @param OutputInterface $output   The Symfony Console output interface.
     */
    public function listProjects(array $projects, OutputInterface $output): void
    {
        if (empty($projects)) {
            $output->writeln('<error>No projects found or an error occurred while fetching projects.</error>');

            return;
        }

        foreach ($projects as $project) {
            $output->writeln(sprintf('<info>%s</info>: %s', $project['key'], $project['name']));
        }
    }

    /**
     * Deletes an issue in Jira.
     *
     * @param  string $issueKey The issue key.
     * @return bool   True if deletion was successful, false otherwise.
     */
    public function deleteIssue(string $issueKey): bool
    {
        try {
            $response = $this->client->delete("/rest/api/3/issue/$issueKey");

            return $response->getStatusCode() === 204;
        } catch (RequestException | GuzzleException $e) {
            // Handle exceptions as needed
            return false;
        }
    }

    /**
     * Edits an issue in Jira.
     *
     * @param  string $issueKey The issue key.
     * @param  array  $fields   An array of fields to update (e.g., summary, description, assignee).
     * @return bool   True if the edit was successful, false otherwise.
     */
    public function editIssue(string $issueKey, array $fields): bool
    {
        $data = ['fields' => $fields];

        try {
            $response = $this->client->put("/rest/api/3/issue/$issueKey", [
                'json' => $data,
            ]);

            return $response->getStatusCode() === 204;
        } catch (RequestException | GuzzleException $e) {
            // Handle exceptions as needed
            return false;
        }
    }

    /**
     * Creates a new project in Jira.
     *
     * @param  string     $name           The name of the project.
     * @param  string     $key            The project key.
     * @param  string     $projectTypeKey The type of the project (e.g., software, business).
     * @param  string     $lead           The username of the project lead.
     * @return array|null The created project details, or null if an error occurred.
     */
    public function createProject(string $name, string $key, string $projectTypeKey, string $lead): ?array
    {
        $data = [
            'name' => $name,
            'key' => $key,
            'projectTypeKey' => $projectTypeKey,
            'lead' => $lead,
        ];

        try {
            $response = $this->client->post('/rest/api/3/project', [
                'json' => $data,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            // Optionally log the error or handle it as needed
            return null;
        }
    }

    /**
     * Retrieves a list of assignable users for a project.
     *
     * @param  string $projectKey The key of the project to retrieve assignable users.
     * @return array  List of assignable users with display names and account IDs.
     */
    public function getAssignableUsers(string $projectKey): array
    {
        try {
            $response = $this->client->get('/rest/api/3/user/assignable/search', [
                'query' => [
                    'project' => $projectKey,
                    'maxResults' => 50,
                ],
            ]);

            $users = json_decode($response->getBody()->getContents(), true);

            return array_map(fn ($user) => ['name' => $user['displayName'], 'accountId' => $user['accountId']], $users);
        } catch (GuzzleException $e) {
            return [];
        }
    }

    /**
     * Assigns a user to an issue in Jira.
     *
     * @param  string $issueKey  The key of the issue to assign.
     * @param  string $accountId The accountId of the user to assign to the issue.
     * @return bool   True if assignment was successful, false otherwise.
     */
    public function assignIssue(string $issueKey, string $accountId): bool
    {
        $data = [
            'accountId' => $accountId,
        ];

        try {
            $response = $this->client->put("/rest/api/3/issue/{$issueKey}/assignee", [
                'json' => $data,
            ]);

            return $response->getStatusCode() === 204;
        } catch (GuzzleException $e) {
            return false;
        }
    }

    /**
     * Retrieves the details of the currently authenticated user.
     *
     * @return array|null The user details, including accountId, or null if an error occurred.
     */
    public function getUserDetails(): ?array
    {
        try {
            $response = $this->client->get('/rest/api/3/myself');

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            // Handle exception if needed
            return null;
        }
    }

    /**
     * Sets or updates the JIRA_ACCOUNT_ID in the .env file.
     *
     * @param  string $accountId The accountId to set in the .env file.
     * @return bool   True if successful, false otherwise.
     */
    public function setAccountIdInEnv(string $accountId): bool
    {
        $dir = __DIR__;

        $envPath = null;
        // Traverse upwards through the directory structure
        while ($dir !== dirname($dir)) {
            $envPath = $dir . '/.env';

            if (file_exists($envPath)) {
                return $envPath;
            }

            // Move up one level
            $dir = dirname($dir);
        }

        if (!file_exists($envPath)) {
            return false;
        }

        $envContent = file_get_contents($envPath);
        $pattern = '/^JIRA_ACCOUNT_ID=.*$/m';

        // If JIRA_ACCOUNT_ID already exists, update it; otherwise, add it.
        if (preg_match($pattern, $envContent)) {
            $envContent = preg_replace($pattern, "JIRA_ACCOUNT_ID={$accountId}", $envContent);
        } else {
            $envContent .= PHP_EOL . "JIRA_ACCOUNT_ID={$accountId}" . PHP_EOL;
        }

        return file_put_contents($envPath, $envContent) !== false;
    }

    /**
     * Retrieves the total time logged by the user for each day in a specified date range, optionally with details per issue.
     *
     * @param  string   $userAccountId The accountId of the user for whom to retrieve worklogs.
     * @param  DateTime $startDate     The start date of the range.
     * @param  DateTime $endDate       The end date of the range.
     * @param  bool     $detailed      Whether to include detailed output by issue.
     * @return array    An associative array with dates as keys, each containing total time or issue-by-issue details.
     */
    public function getWorkLogTotalsByDateRange(string $userAccountId, DateTime $startDate, DateTime $endDate, bool $detailed = false): array
    {
        $totalsByDate = [];
        $currentDate = clone $startDate;

        // Initialize totals array with 0 for each date in range
        while ($currentDate <= $endDate) {
            $totalsByDate[$currentDate->format('Y-m-d')] = $detailed ? [] : 0;
            $currentDate->modify('+1 day');
        }

        try {
            // Use the worklogDate and worklogAuthor parameters in JQL to narrow the search
            $jql = "worklogAuthor = {$userAccountId} AND " .
                "worklogDate >= '{$startDate->format('Y-m-d')}' AND " .
                "worklogDate <= '{$endDate->format('Y-m-d')}'";

            // Process issues in batches to avoid memory issues with large datasets
            $startAt = 0;
            $batchSize = 50; // Process 50 issues at a time
            $moreIssues = true;

            // Store issue keys and summaries for detailed reports
            $issueDetails = [];

            while ($moreIssues) {
                $response = $this->client->get('/rest/api/3/search', [
                    'query' => [
                        'jql' => $jql,
                        'fields' => 'key,summary', // Only get essential fields
                        'startAt' => $startAt,
                        'maxResults' => $batchSize,
                    ],
                ]);

                $issuesData = json_decode($response->getBody()->getContents(), true);
                $issues = $issuesData['issues'] ?? [];

                if (empty($issues)) {
                    break; // No more issues to process
                }

                // Extract issue IDs for bulk worklog retrieval
                $issueIds = [];
                foreach ($issues as $issue) {
                    $issueIds[] = $issue['id'];
                    $issueDetails[$issue['key']] = $issue['fields']['summary'] ?? '';
                }

                // Fetch worklogs in bulk for all issues in this batch
                // Use the worklog API endpoint that allows for bulk retrieval
                foreach ($issueIds as $issueId) {
                    $issueKey = null;
                    foreach ($issues as $issue) {
                        if ($issue['id'] === $issueId) {
                            $issueKey = $issue['key'];
                            break;
                        }
                    }

                    if (!$issueKey) continue;

                    // Request worklogs for this issue
                    $worklogStartAt = 0;
                    $worklogBatchSize = 100;
                    $moreWorklogs = true;

                    while ($moreWorklogs) {
                        $worklogResponse = $this->client->get("/rest/api/3/issue/{$issueKey}/worklog", [
                            'query' => [
                                'startAt' => $worklogStartAt,
                                'maxResults' => $worklogBatchSize,
                            ],
                        ]);

                        $worklogData = json_decode($worklogResponse->getBody()->getContents(), true);
                        $worklogs = $worklogData['worklogs'] ?? [];

                        if (empty($worklogs)) {
                            break; // No more worklogs for this issue
                        }

                        // Process worklogs
                        foreach ($worklogs as $worklog) {
                            if ($worklog['author']['accountId'] !== $userAccountId) {
                                continue; // Skip worklogs by other authors
                            }

                            $worklogDate = substr($worklog['started'], 0, 10);

                            // Skip if outside our date range
                            if (!isset($totalsByDate[$worklogDate])) {
                                continue;
                            }

                            $timeSpent = $worklog['timeSpentSeconds'];

                            if ($detailed) {
                                if (!isset($totalsByDate[$worklogDate][$issueKey])) {
                                    $totalsByDate[$worklogDate][$issueKey] = [
                                        'timeSpent' => 0,
                                        'summary' => $issueDetails[$issueKey] ?? ''
                                    ];
                                }
                                $totalsByDate[$worklogDate][$issueKey]['timeSpent'] += $timeSpent;
                            } else {
                                $totalsByDate[$worklogDate] += $timeSpent;
                            }
                        }

                        // Check if we need to fetch more worklogs
                        $worklogStartAt += count($worklogs);
                        $moreWorklogs = $worklogStartAt < $worklogData['total'];
                    }
                }

                // Update for the next batch of issues
                $startAt += count($issues);
                $moreIssues = $startAt < $issuesData['total'];
            }

            // Clean up empty dates if requested
            if (!empty($_GET['removeEmpty'] ?? false)) {
                foreach ($totalsByDate as $date => $total) {
                    if (($detailed && empty($total)) || (!$detailed && $total === 0)) {
                        unset($totalsByDate[$date]);
                    }
                }
            }
        } catch (GuzzleException $e) {
            // Optionally log the error
            // error_log('Error fetching worklogs: ' . $e->getMessage());
        }

        return $totalsByDate;
    }

    /**
     * Creates a new issue in Jira.
     *
     * @param  string     $projectKey  The project key.
     * @param  string     $summary     The issue summary.
     * @param  string     $description The issue description.
     * @param  string     $issueType   The issue type.
     * @param  string     $priority    The issue priority.
     * @return array|null The created issue details, or null if an error occurred.
     */
    public function createIssue(string $projectKey, string $summary, string $description, string $issueType, string $priority): ?array
    {
        $data = [
            'fields' => [
                'project' => ['key' => $projectKey],
                'summary' => $summary,
                'description' => $description,
                'issuetype' => ['name' => $issueType], // Correct field name
                'priority' => ['name' => $priority],
            ],
        ];

        try {
            $response = $this->client->post('/rest/api/3/issue', [
                'json' => $data,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException | GuzzleException $e) {
            // Handle exceptions (logging can be added here if necessary)
            return null;
        }
    }

    /**
     * Fetches issue details by issue ID or key.
     *
     * @param  string          $issueId The issue ID or key.
     * @return array|null      The issue details, or null if not found.
     * @throws GuzzleException
     */
    public function getIssueDetails(string $issueId): ?array
    {
        try {
            $response = $this->client->get("/rest/api/3/issue/$issueId");

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return null;
        }
    }

    /**
     * Outputs the details of a specific issue.
     *
     * @param array|null      $issue  The issue details.
     * @param OutputInterface $output The Symfony Console output interface.
     */
    public function showIssueDetails(?array $issue, OutputInterface $output): void
    {
        if (!$issue) {
            $output->writeln('<error>Issue not found or an error occurred while fetching the issue.</error>');

            return;
        }

        $output->writeln([
            "<info>Issue Key:</info> {$issue['key']}",
            "<info>Summary:</info> {$issue['fields']['summary']}",
            "<info>Status:</info> {$issue['fields']['status']['name']}",
            '<info>Description:</info> ' . ($issue['fields']['description'] ?? 'No description available'),
        ]);
    }

    /**
     * Fetches a list of issues for a specific project, filtered by status and paginated.
     *
     * @param  string          $projectKey The project key.
     * @param  string|null     $status     The status to filter by (optional).
     * @param  int             $startAt    The starting index for pagination.
     * @param  int             $maxResults The maximum number of results to return.
     * @return array           The list of issues.
     * @throws GuzzleException
     */
    public function getIssuesByProject(string $projectKey, ?string $status = null, int $startAt = 0, int $maxResults = 10): array
    {
        $jql = "project = $projectKey";
        if ($status) {
            $jql .= " AND status = '$status'";
        }
        $jql .= ' ORDER BY created DESC';

        try {
            $response = $this->client->get('/rest/api/3/search', [
                'query' => [
                    'jql' => $jql,
                    'startAt' => $startAt,
                    'maxResults' => $maxResults,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true)['issues'];
        } catch (RequestException $e) {
            return [];
        }
    }

    /**
     * Outputs a list of issues for a specified project.
     *
     * @param array           $issues The list of issues.
     * @param OutputInterface $output The Symfony Console output interface.
     */
    public function listIssues(array $issues, OutputInterface $output): void
    {
        if (empty($issues)) {
            $output->writeln('<error>No issues found or an error occurred while fetching issues.</error>');

            return;
        }

        foreach ($issues as $issue) {
            $output->writeln(sprintf(
                '<info>%s</info> (%s): %s',
                $issue['key'],
                $issue['fields']['status']['name'],
                $issue['fields']['summary']
            ));
        }
    }
}
