# Doc2FAQ
A Symfony-based PHP web application for document-to-FAQ conversion and management.

## Requirements

- **PHP**: 8.1 or higher
- **Composer**: 2.x or higher
- **Symfony**: 6.4 LTS

### Required PHP Extensions

- `ext-ctype`
- `ext-iconv`

## Installation

Follow these steps to set up the project locally:

### 1. Clone the Repository

```bash
git clone <repository-url>
cd doc2faq
```

### 2. Install Dependencies

Install the required Composer packages:

```bash
composer install
```

### 3. Configure Environment

Copy the `.env` file to create your local environment configuration:

```bash
cp .env .env.local
```

Edit `.env.local` and update the `APP_SECRET` with a secure random string:

```env
APP_SECRET=your-secure-random-secret-key-here
```

You can generate a secure random secret using:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

## Running the Application

### Start the Development Server

Use Symfony's built-in development server:

```bash
symfony server:start
```

Or use PHP's built-in web server:

```bash
php -S localhost:8000 -t public
```

The application will be available at: `http://localhost:8000`

## Testing the Health Endpoint

Verify the application is running correctly by accessing the health check endpoint:

```bash
curl http://localhost:8000/api/health
```

Expected response:

```json
{
  "status": "ok"
}
```

This should return HTTP 200 with the status "ok" in JSON format.

## Project Structure

```
doc2faq/
├── config/             # Application configuration files
├── public/             # Web root directory
│   └── index.php      # Front controller
├── src/                # Application source code
│   ├── Controller/    # Controllers
│   └── Kernel.php     # Application kernel
├── var/                # Cache and logs (auto-generated)
│   ├── cache/         # Application cache
│   └── log/           # Application logs
├── vendor/             # Composer dependencies (not committed)
├── .env                # Environment configuration template
├── .env.local          # Local environment overrides (not committed)
├── .gitignore          # Git ignore rules
├── composer.json       # Composer dependencies
└── README.md           # This file
```

## Development

### Adding New Features

The application follows standard Symfony conventions:

- **Controllers**: Place new controllers in `src/Controller/`
- **Services**: Place services in `src/` with appropriate subdirectories
- **Configuration**: Add configuration files in `config/packages/`
- **Routes**: Routes are configured via PHP attributes in controllers or in `config/routes.yaml`

### Environment Variables

Environment-specific configuration should be placed in `.env.local` (not committed to Git).
The `.env` file contains default values and should be committed.

## Troubleshooting

### Clear Cache

If you encounter issues, try clearing the cache:

```bash
php bin/console cache:clear
```

### Check Logs

Application logs are stored in `var/log/`:

```bash
tail -f var/log/dev.log
```

## License

Proprietary
