<?php

namespace OguzhanTogay\JiraCLI;

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
