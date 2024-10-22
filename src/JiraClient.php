<?php

namespace YourVendor\JiraCLI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class JiraClient
{
    private Client $client;
    private string $baseUri;
    private string $username;
    private string $apiToken;

    public function __construct(string $baseUri, string $username, string $apiToken)
    {
        $this->baseUri = $baseUri;
        $this->username = $username;
        $this->apiToken = $apiToken;

        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'auth' => [$this->username, $this->apiToken]
        ]);
    }

    public function getIssues(string $jql = 'assignee = currentUser() AND resolution = Unresolved ORDER BY created DESC', int $maxResults = 10)
    {
        try {
            $response = $this->client->get('/rest/api/2/search', [
                'query' => [
                    'jql' => $jql,
                    'maxResults' => $maxResults
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            echo 'Error: ' . $e->getMessage();
            return [];
        }
    }

    public function listIssues(array $issues)
    {
        foreach ($issues['issues'] as $issue) {
            echo sprintf(
                "%s: %s (Status: %s)\n",
                $issue['key'],
                $issue['fields']['summary'],
                $issue['fields']['status']['name']
            );
        }
    }
}
