<?php

namespace OguzhanTogay\JiraCLI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class JiraClient
{
    private $client;
    private $baseUri;
    private $username;
    private $apiToken;

    /**
     * JiraClient constructor.
     *
     * @param string $baseUri  The Jira server URI.
     * @param string $username The username for authentication.
     * @param string $apiToken The API token for authentication.
     */
    public function __construct(string $baseUri, string $username, string $apiToken)
    {
        $this->baseUri = $baseUri;
        $this->username = $username;
        $this->apiToken = $apiToken;

        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'auth' => [$this->username, $this->apiToken],
        ]);
    }

    /**
     * Get a list of issues based on project, status, and pagination.
     *
     * @param  string      $projectKey The key of the project.
     * @param  string|null $status     The status to filter issues.
     * @param  int         $startAt    The starting point for pagination.
     * @param  int         $maxResults The maximum number of results per page.
     * @return array       The list of issues.
     */
    public function getIssuesByProject(string $projectKey, ?string $status = null, int $startAt = 0, int $maxResults = 10)
    {
        $jql = "project = {$projectKey}";
        if ($status) {
            $jql .= " AND status = '{$status}'";
        }
        $jql .= ' ORDER BY Priority DESC';

        try {
            $response = $this->client->get('/rest/api/3/search', [
                'query' => [
                    'jql' => $jql,
                    'startAt' => $startAt,
                    'maxResults' => $maxResults,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            echo 'Error: ' . $e->getMessage();

            return [];
        }
    }

    /**
     * Get the details of a specific issue by its ID or key.
     *
     * @param  string          $issueId The ID or key of the issue.
     * @return array|null      The issue details.
     * @throws GuzzleException
     */
    public function getIssueDetails(string $issueId): ?array
    {
        try {
            $response = $this->client->get("/rest/api/3/issue/{$issueId}");

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            echo 'Error: ' . $e->getMessage();

            return null;
        }
    }

    /**
     * List all Jira projects.
     *
     * @return array The list of projects.
     */
    public function getProjects()
    {
        try {
            $response = $this->client->get('/rest/api/3/project');

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            echo 'Error: ' . $e->getMessage();

            return [];
        }
    }

    /**
     * Display a list of issues in the terminal.
     *
     * @param array $issues The issues data to be displayed.
     */
    public function listIssues(array $issues)
    {
        foreach ($issues['issues'] as $issue) {
            // Get the issue key and priority name
            $issueKey = $issue['key'];
            $priorityName = $issue['fields']['priority']['name'] ?? 'Unknown';

            // Determine color for priority
            $priorityColorCode = match (strtolower($priorityName)) {
                'high', 'highest' => "\033[31m",   // Red for High priorities
                'medium' => "\033[33m",            // Yellow for Medium priorities
                'low', 'lowest' => "\033[32m",     // Green for Low priorities
                default => "\033[0m",              // Default terminal color for others
            };

            // Set color for the issue key (green)
            $issueKeyColorCode = "\033[32m";  // Green for issue key
            $resetCode = "\033[0m";           // Reset color to default

            // Print the issue with the colored key and priority
            echo sprintf(
                "%s%s%s: %s (Status: %s) - %s%s%s\n",
                $issueKeyColorCode,             // Start color for issue key
                $issueKey,                      // The issue key itself
                $resetCode,                     // Reset color after the issue key
                $issue['fields']['summary'],    // The issue summary
                $issue['fields']['status']['name'], // The issue status
                $priorityColorCode,             // Start color for priority
                $priorityName,                  // Priority name
                $resetCode                      // Reset color after the priority
            );
        }
    }

    /**
     * Display detailed information about a specific issue.
     *
     * @param array $issue The issue data to be displayed.
     */
    public function showIssueDetails(array $issue): void
    {
        echo sprintf(
            "\033[31mID:\033[0m %s\n\033[31mKey:\033[0m %s\n\033[31mSummary:\033[0m %s\n\033[31mStatus:\033[0m %s\n\033[31mDescription:\033[0m %s\n\033[31mPriority:\033[0m %s\n\033[31mCreator:\033[0m %s\n\033[31mTime Prediction:\033[0m %s\n\033[31mTime Spent:\033[0m %s\n",
            $issue['id'],
            $issue['key'],
            $issue['fields']['summary'],
            $issue['fields']['status']['name'],
            $this->parseDescription($issue['fields']['description']),
            $issue['fields']['priority']['name'],
            $issue['fields']['creator']['displayName'],
            isset($issue['fields']['aggregatetimeoriginalestimate']) ? $issue['fields']['aggregatetimeoriginalestimate'] / 3600 . 'h' : 'N/A',
            isset($issue['fields']['timeestimate']) ? $issue['fields']['timeestimate'] / 3600 . 'h' : 'N/A'
        );
    }

    public function parseDescription(array $description): string
    {
        $result = '';

        // Check if the content exists in the provided description
        if (isset($description['content']) && is_array($description['content'])) {
            foreach ($description['content'] as $block) {
                // Process paragraph blocks
                if ($block['type'] === 'paragraph' && isset($block['content'])) {
                    foreach ($block['content'] as $content) {
                        if ($content['type'] === 'text' && isset($content['text'])) {
                            $result .= $content['text'];
                        }
                        if ($content['type'] === 'hardBreak') {
                            $result .= "\n"; // Add a line break for better readability
                        }
                    }
                    $result .= "\n"; // Add a line break after paragraphs for better formatting
                }

                // Process bullet list blocks
                if ($block['type'] === 'bulletList' && isset($block['content'])) {
                    foreach ($block['content'] as $listItem) {
                        if (isset($listItem['content'])) {
                            foreach ($listItem['content'] as $content) {
                                if ($content['type'] === 'paragraph' && isset($content['content'])) {
                                    foreach ($content['content'] as $listContent) {
                                        if ($listContent['type'] === 'text' && isset($listContent['text'])) {
                                            $result .= '- ' . $listContent['text'] . "\n";
                                        }
                                        if ($listContent['type'] === 'hardBreak') {
                                            $result .= "\n";
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $result .= "\n";
                }
            }
        }

        return trim($result);
    }

    /**
     * Display a list of projects in the terminal.
     *
     * @param array $projects The projects data to be displayed.
     */
    public function listProjects(array $projects)
    {
        foreach ($projects as $project) {
            echo sprintf(
                "%s: %s\n",
                $project['key'],
                $project['name']
            );
        }
    }

    public function setHttpClient(Client $client): void
    {
        $this->client = $client;
    }
}
