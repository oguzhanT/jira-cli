<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use OguzhanTogay\JiraCLI\JiraClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class JiraClientTest extends TestCase
{
    private $mockClient;
    private JiraClient $jiraClient;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        // Create a mock Guzzle client
        $this->mockClient = $this->createMock(Client::class);

        // Instantiate JiraClient with the mocked Guzzle client
        $this->jiraClient = new JiraClient('https://your-domain.atlassian.net', 'username', 'api_token');
        $this->jiraClient->setHttpClient($this->mockClient);
    }

    public function testGetIssueDetailsSuccess()
    {
        // Define the mock response body for a successful issue request
        $responseBody = json_encode([
            'id' => 'CHEF-1262',
            'key' => 'CHEF-1262',
            'fields' => [
                'summary' => 'Fix login bug',
                'status' => ['name' => 'In Progress'],
                'description' => [
                    'type' => 'doc',
                    'version' => 1,
                    'content' => [
                        [
                            'type' => 'paragraph',
                            'content' => [
                                ['type' => 'text', 'text' => 'This is a sample description.'],
                            ],
                        ],
                    ],
                ],
                'priority' => ['name' => 'High'],
                'creator' => ['displayName' => 'John Doe'],
                'aggregatetimeoriginalestimate' => 7200,
                'timeestimate' => 3600,
            ],
        ]);

        // Configure the mock client to return a successful response
        $this->mockClient->method('get')
            ->willReturn(new Response(200, [], $responseBody));

        // Fetch the issue details
        $issue = $this->jiraClient->getIssueDetails('CHEF-1262');

        // Assert the returned data is as expected
        $this->assertIsArray($issue);
        $this->assertEquals('CHEF-1262', $issue['id']);
        $this->assertEquals('Fix login bug', $issue['fields']['summary']);
        $this->assertEquals('In Progress', $issue['fields']['status']['name']);
        $this->assertEquals('High', $issue['fields']['priority']['name']);
        $this->assertEquals('John Doe', $issue['fields']['creator']['displayName']);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetIssueDetailsFailure()
    {
        // Configure the mock client to throw a RequestException
        $this->mockClient->method('get')
            ->willThrowException(new RequestException('Not Found', $this->createMock(RequestInterface::class)));

        // Fetch the issue details, expecting an empty array or error handling
        $issue = $this->jiraClient->getIssueDetails('INVALID_ID');

        // Assert that the result is null or an empty array (based on your error handling)
        $this->assertNull($issue);
    }
}
