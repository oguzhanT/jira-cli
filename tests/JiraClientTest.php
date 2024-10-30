<?php

namespace OguzhanTogay\JiraCLI\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Mockery;
use OguzhanTogay\JiraCLI\JiraClient;
use PHPUnit\Framework\TestCase;

class JiraClientTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testGetProjects()
    {
        // Step 1: Create a mock Guzzle Client
        $mockClient = Mockery::mock(Client::class);

        // Step 2: Ensure it responds to the specific endpoint used in getProjects()
        $mockClient->shouldReceive('get')
            ->with('/rest/api/3/project')
            ->andReturn(new Response(200, [], json_encode([
                ['key' => 'PROJ1', 'name' => 'Project 1'],
                ['key' => 'PROJ2', 'name' => 'Project 2'],
            ])));

        // Step 3: Pass the mock client into JiraClient
        $jiraClient = new JiraClient('https://example.atlassian.net', 'username', 'api_token', $mockClient);

        // Step 4: Call getProjects() and assert the expected results
        $projects = $jiraClient->getProjects();

        // Assert that the array returned contains 2 projects
        $this->assertIsArray($projects);
        $this->assertCount(2, $projects);
        $this->assertEquals('Project 1', $projects[0]['name']);
        $this->assertEquals('Project 2', $projects[1]['name']);
    }

    /**
     * @throws GuzzleException
     */
    public function testGetIssueDetails()
    {
        // Create a mock Guzzle client
        $mockClient = Mockery::mock(Client::class);

        // Define the mock response for the specific issue ID
        $mockClient->shouldReceive('get')
            ->with('/rest/api/3/issue/TEST-123')
            ->andReturn(new Response(200, [], json_encode([
                'key' => 'TEST-123',
                'fields' => [
                    'summary' => 'Test Issue',
                    'status' => ['name' => 'Open'],
                    'description' => 'This is a test issue description.',
                ],
            ])));

        // Instantiate JiraClient with the mock client
        $jiraClient = new JiraClient('https://example.atlassian.net', 'username', 'api_token', $mockClient);

        // Call getIssueDetails and verify the response
        $issueDetails = $jiraClient->getIssueDetails('TEST-123');

        // Assertions
        $this->assertIsArray($issueDetails);
        $this->assertEquals('TEST-123', $issueDetails['key']);
        $this->assertEquals('Test Issue', $issueDetails['fields']['summary']);
        $this->assertEquals('Open', $issueDetails['fields']['status']['name']);
        $this->assertEquals('This is a test issue description.', $issueDetails['fields']['description']);
    }
}
