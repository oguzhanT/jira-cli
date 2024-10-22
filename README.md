# Jira CLI

A PHP CLI tool to interact with Jira issues directly from the terminal. This tool allows you to list Jira projects, list issues by project, filter by status, paginate results, and view detailed information about specific issues.

## Features

- List all projects in your Jira instance.
- List issues from a specific project.
- Filter issues by status.
- Paginate through issues.
- View details of a specific issue.

## Requirements

- PHP 8.0 or higher
- Composer
- Jira account and API token
- Jira instance (e.g., https://your-domain.atlassian.net)

## Installation

1. **Clone the repository** (if you're using Git):
   ```bash
   git clone https://github.com/oguzhanT/jira-cli.git
   cd jira-cli
   ```

2. **Install dependencies**:
   Run the following command in the root of the project to install required packages:
   ```bash
   composer install
   ```

3. **Make the CLI script executable**:
   ```bash
   chmod +x bin/jira-cli
   ```

4. **Set up environment variables**:
   Create a `.env` file in the root directory of your project and add the following:
   ```
   JIRA_SERVER=https://your-domain.atlassian.net
   JIRA_USERNAME=your-email@example.com
   JIRA_API_TOKEN=your-api-token
   ```

   Replace `https://your-domain.atlassian.net`, `your-email@example.com`, and `your-api-token` with your actual Jira server URL, username, and API token.

## Usage

### Global Installation

If you want to use this tool globally, you can install it using Composer:

```bash
composer global require oguzhantogay/jira-cli
```

Make sure your global Composer `bin` directory is in your system's `PATH`:

```bash
export PATH="$HOME/.composer/vendor/bin:$PATH"
```

### List All Jira Projects

To list all Jira projects in your instance:

```bash
php bin/jira-cli --list-projects
```

This command will display a list of all projects with their keys and names.

### List Issues by Project

To list issues from a specific project, use the `-p` or `--project` option:

```bash
php bin/jira-cli -p PROJECT_KEY
```

Replace `PROJECT_KEY` with the key of the project you want to list issues from.

### Filter Issues by Status

To filter issues by status (e.g., "In Progress", "Done"):

```bash
php bin/jira-cli -p PROJECT_KEY -s "In Progress"
```

This will list all issues in the specified project that have the status "In Progress".

### Paginate Issue Results

To paginate through issues, use the `--range` option to specify a range:

```bash
php bin/jira-cli -p PROJECT_KEY --range=5-10
```

This will list issues 5 through 10 for the specified project. The default range is `0-9`, which lists the first 10 issues.

### View Issue Details

To view detailed information about a specific issue, use the `--id` option:

```bash
php bin/jira-cli --id ISSUE-123
```

Replace `ISSUE-123` with the ID or key of the issue you want to view. This will display details such as the issue summary, status, and description.

## Environment Configuration

Make sure to include the `.env` file in the root directory of your project. Here's an example of a typical `.env` file:

```
JIRA_SERVER=https://your-domain.atlassian.net
JIRA_USERNAME=your-email@example.com
JIRA_API_TOKEN=your-api-token
```

- **JIRA_SERVER**: The base URL of your Jira instance.
- **JIRA_USERNAME**: Your Jira username or email.
- **JIRA_API_TOKEN**: Your API token generated from Jira. [Generate a new API token here](https://id.atlassian.com/manage/api-tokens).

## Example Commands

Here are a few example commands to help you get started:

- **List all projects**:
  ```bash
  php bin/jira-cli --list-projects
  ```

- **List issues for a specific project**:
  ```bash
  php bin/jira-cli -p MYPROJECT
  ```

- **List issues with "In Progress" status**:
  ```bash
  php bin/jira-cli -p MYPROJECT -s "In Progress"
  ```

- **List issues from 5 to 10**:
  ```bash
  php bin/jira-cli -p MYPROJECT --range=5-10
  ```

- **Show details of a specific issue**:
  ```bash
  php bin/jira-cli --id MYPROJECT-123
  ```

## Contributing

Contributions are welcome! If you find any issues or have suggestions for improvement, please open an issue or create a pull request.

1. Fork the repository.
2. Create a new branch for your feature or bugfix:
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. Make your changes.
4. Test thoroughly.
5. Submit a pull request with a detailed description of your changes.

## Testing

To run tests, use the following command:

```bash
composer tests
```

Make sure to write tests for any new features or bugfixes.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.

## Support

If you encounter any issues or have questions, please use the following links:

- **Issues**: [Submit an issue](https://github.com/oguzhanT/jira-cli/issues)
- **Source**: [View source on GitHub](https://github.com/oguzhanT/jira-cli)

## Author

Developed by Oğuzhan Togay. For any inquiries, please reach out via email at [ogitog@gmail.com](mailto:ogitog@gmail.com).
