
# Jira CLI Tool

![Jira CLI](https://img.shields.io/badge/Jira-CLI-blue) ![Platform](https://img.shields.io/badge/Platform-Mac%20%7C%20Linux%20%7C%20Windows-lightgrey)

A powerful, easy-to-use CLI tool for interacting with Jira, making it simple to manage issues, track worklogs, and automate common tasks right from your terminal. Perfect for developers and project managers who want to streamline their Jira workflows.

## Features

- View, create, edit, assign, and delete Jira issues
- Track daily, weekly or monthly worklogs with detailed or summarized views
- Retrieve user details and set environment configurations automatically

## Supported Platforms

| Platform | Supported | Notes                  |
|----------|-----------|------------------------|
| macOS    | ✅         | Requires PHP installed |
| Linux    | ✅         | Requires PHP installed |
| Windows  | ✅         | Requires PHP installed |

## Installation

1. **Clone the repository**:
    ```bash
    git clone https://github.com/oguzhanT/jira-cli.git
    cd jira-cli
    ```

2. **Install dependencies**:
    ```bash
    composer install
    ```

3. **Configure your environment**:
    - Copy the `.env.example` file to `.env` and fill in your Jira details:
    ```plaintext
    JIRA_SERVER=https://your-jira-instance.atlassian.net
    JIRA_USERNAME=your-email@example.com
    JIRA_API_TOKEN=your-jira-api-token
    ```

4. **Set up account ID**:
    - Run the following command to automatically set your `JIRA_ACCOUNT_ID` in `.env`:
    ```bash
    php bin/console configure-account-id
    ```

## Commands

### Issue Management

#### View Issue Details
Display details for a specific issue by key.
```bash
php bin/console show-issue --issueKey=ISSUE-123
```

#### Create a New Issue
Interactively create a new issue in Jira.
```bash
php bin/console create-issue
```
You will be prompted for details such as project, summary, description, issue type, and priority.

#### Edit an Issue
Edit details of an existing issue.
```bash
php bin/console edit-issue --issueKey=ISSUE-123
```
Provides prompts for modifying fields like summary, description, assignee, issue type, and priority.

#### Assign an Issue
Assign an issue to a user by account ID.
```bash
php bin/console assign-issue --issueKey=ISSUE-123 --assignee=account_id
```
Alternatively, use `--projectKey` to choose from a list of assignable users.

#### Delete an Issue
Delete a specified issue.
```bash
php bin/console delete-issue --issueKey=ISSUE-123
```

### Worklog Tracking

#### Show Worklog Summary
View the total time logged for a specified period (daily, weekly, biweekly, monthly).
```bash
php bin/console show-work-log --accountId=your_account_id --period=weekly
```

#### Show Detailed Worklog by Issue
Get a breakdown of worklogs by issue for each day.
```bash
php bin/console show-work-log --accountId=your_account_id --period=monthly --detailed
```

### User Management

#### Configure Account ID
Automatically fetch and set your Jira `accountId` in the `.env` file.
```bash
php bin/console configure-account-id
```

#### Show User Details
Retrieve details for the authenticated user.
```bash
php bin/console show-user-detail
```

### Example Workflows

1. **Set up and View Your User Details**:
    ```bash
    php bin/console configure-account-id
    php bin/console show-user-detail
    ```

2. **Log and Track Work**:
    ```bash
    php bin/console create-issue
    php bin/console assign-issue --issueKey=ISSUE-123 --assignee=account_id
    php bin/console show-work-log --accountId=your_account_id --period=daily --detailed
    ```

## Contribution

If you’d like to contribute to this project:
1. Fork the repository.
2. Create a feature branch (`git checkout -b feature-branch`).
3. Commit your changes (`git commit -m "Add a new feature"`).
4. Push to the branch (`git push origin feature-branch`).
5. Create a pull request.

## License

This project is licensed under the MIT License.
