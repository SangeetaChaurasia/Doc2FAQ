# Doc2FAQ

A Symfony-based PHP web application for document-to-FAQ conversion with source citation tracking. The application processes documents, splits them into chunks with metadata preservation, and generates FAQs with transparent source references.

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
## FAQ Generation API

The Doc2FAQ application provides an API endpoint for generating FAQs from documents with full source citation tracking.

### Endpoint

**POST** `/api/faq/generate`

### Features

- **Source Citation**: Every generated FAQ includes references to the source document and specific chunks
- **Metadata Preservation**: Maintains document metadata including:
  - Source filename
  - Chunk ID
  - Page number (when available, e.g., for PDFs)
  - Section/heading (when detected)
- **Flexible Input**: Accepts both JSON and multipart/form-data requests
- **Traceability**: Full transparency in how answers are derived from source material

### Request Format

#### Option 1: JSON Request

```bash
curl -X POST http://localhost:8000/api/faq/generate \
  -H "Content-Type: application/json" \
  -d '{
    "text": "The company was founded in 2020. Our mission is to democratize AI technology. We believe that artificial intelligence should be accessible to everyone. Our flagship product is an AI-powered document processing system that helps organizations convert documents into structured knowledge bases.",
    "filename": "company_info.txt",
    "questions": [
      "When was the company founded?",
      "What is the company mission?",
      "What is the main product?"
    ]
  }'
```

#### Option 2: File Upload (multipart/form-data)

```bash
curl -X POST http://localhost:8000/api/faq/generate \
  -F "document=@/path/to/document.txt" \
  -F 'questions=["What is this document about?", "What are the key points?"]'
```

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `text` | string | Yes (JSON) | The document text content to process |
| `filename` | string | No | Custom filename for the document (defaults to "input.txt") |
| `questions` | array | No | List of questions to answer. If not provided, default questions are used |
| `document` | file | Yes (multipart) | The document file to upload |

### Response Format

The API returns a JSON response with the following structure:

```json
{
  "status": "success",
  "document": "company_info.txt",
  "chunks_created": 3,
  "faqs": [
    {
      "question": "When was the company founded?",
      "answer": "The company was founded in 2020. Our mission is to democratize AI technology. We believe that artificial intelligence should be accessible to everyone.",
      "sources": [
        {
          "document": "company_info.txt",
          "chunk_id": "chunk_5d41402abc4b2a76b9719d911017c592_0",
          "page": 1
        }
      ]
    },
    {
      "question": "What is the company mission?",
      "answer": "Our mission is to democratize AI technology. We believe that artificial intelligence should be accessible to everyone. Our flagship product is an AI-powered document processing system that helps organizations convert documents into structured knowledge bases.",
      "sources": [
        {
          "document": "company_info.txt",
          "chunk_id": "chunk_5d41402abc4b2a76b9719d911017c592_0",
          "page": 1
        }
      ]
    }
  ]
}
```

### Response Fields

#### Top Level

| Field | Type | Description |
|-------|------|-------------|
| `status` | string | Response status ("success" or "error") |
| `document` | string | Name of the processed document |
| `chunks_created` | integer | Number of chunks created from the document |
| `faqs` | array | Array of FAQ items with source citations |

#### FAQ Item

| Field | Type | Description |
|-------|------|-------------|
| `question` | string | The question being answered |
| `answer` | string | The generated answer based on retrieved context |
| `sources` | array | Array of source references used to generate the answer |

#### Source Reference

| Field | Type | Description |
|-------|------|-------------|
| `document` | string | Source document filename |
| `chunk_id` | string | Unique identifier for the specific chunk |
| `page` | integer | Page number (optional, included when available) |
| `section` | string | Section or heading name (optional, included when detected) |

### Error Handling

The API handles various error scenarios gracefully:

#### Missing Required Fields

```json
{
  "error": "No text provided"
}
```

**HTTP Status**: 400 Bad Request

#### Processing Errors

```json
{
  "error": "FAQ generation failed",
  "message": "Detailed error message"
}
```

**HTTP Status**: 500 Internal Server Error

#### Unsupported Content Type

```json
{
  "error": "Unsupported content type"
}
```

**HTTP Status**: 400 Bad Request

### Example Use Cases

#### Use Case 1: Process Company Documentation

```bash
curl -X POST http://localhost:8000/api/faq/generate \
  -H "Content-Type: application/json" \
  -d '{
    "text": "Product Manual: Installation Guide\n\nTo install the software, download the installer from our website. Run the installer and follow the on-screen instructions. The installation process takes approximately 5 minutes.\n\nTroubleshooting\n\nIf you encounter errors during installation, check that you have administrator privileges.",
    "filename": "installation_guide.txt",
    "questions": [
      "How do I install the software?",
      "How long does installation take?",
      "What if I have installation errors?"
    ]
  }'
```

#### Use Case 2: Upload PDF Document

```bash
curl -X POST http://localhost:8000/api/faq/generate \
  -F "document=@user_manual.pdf" \
  -F 'questions=["What are the system requirements?", "How do I get started?"]'
```

### Source Citation Benefits

1. **Transparency**: Users can verify where answers come from
2. **Trust**: Traceable sources increase confidence in generated FAQs
3. **Debugging**: Developers can identify which chunks are used for each answer
4. **Compliance**: Audit trails for regulated industries
5. **Improvement**: Identify gaps in source documentation

### Implementation Notes

- The system uses semantic chunking to preserve context
- Chunk size is optimized at ~500 characters with 50-character overlap
- Section headings are automatically detected (ALL CAPS or ending with ':')
- Page numbers are tracked throughout the chunking process
- The retrieval system ranks chunks by relevance to each question
- Multiple source chunks may contribute to a single FAQ answer


## Project Structure

```
doc2faq/
├── config/             # Application configuration files
├── public/             # Web root directory
│   └── index.php      # Front controller
├── src/                # Application source code
│   ├── Model/         # Data models
│   ├── Service/       # Business logic services
│   ├── Controller/    # Controllers
├── tests/              # Test files
│   ├── Controller/    # Controller tests
│   └── Service/       # Service tests
│   └── Kernel.php     # Application kernel
├── var/                # Cache and logs (auto-generated)
│   ├── cache/         # Application cache
│   └── log/           # Application logs
├── vendor/             # Composer dependencies (not committed)
├── .env                # Environment configuration template
├── .env.local          # Local environment overrides (not committed)
├── .gitignore          # Git ignore rules

### Adding New Features

The application follows standard Symfony conventions:
├── phpunit.xml.dist    # PHPUnit configuration

- **Controllers**: Place new controllers in `src/Controller/`
- **Services**: Place services in `src/` with appropriate subdirectories
## Testing

The application includes comprehensive test coverage for all FAQ generation and source citation functionality.

### Running Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit tests/Service/

# Run with coverage (requires xdebug)
./vendor/bin/phpunit --coverage-html coverage/
```

### Test Coverage

The test suite covers:

- **Document Chunking**: Metadata preservation, chunk ID generation, heading detection
- **FAQ Generation**: Source citation, answer generation, error handling
- **Chunk Retrieval**: Relevance ranking, top-K selection
- **API Integration**: Request/response handling, error scenarios
- **Edge Cases**: Empty documents, missing metadata, malformed requests

- **Configuration**: Add configuration files in `config/packages/`
- **Routes**: Routes are configured via PHP attributes in controllers or in `config/routes.yaml`

### Environment Variables

Environment-specific configuration should be placed in `.env.local` (not committed to Git).
### Architecture

The application follows a service-oriented architecture:

#### Models (src/Model/)
- `DocumentChunk`: Represents a text chunk with metadata
- `FAQItem`: Represents a generated FAQ with sources
- `SourceReference`: Represents source citation information

#### Services (src/Service/)
- `DocumentLoaderService`: Loads documents from files or text input
- `DocumentChunkerService`: Splits documents into semantic chunks
- `ChunkRetrieverService`: Finds relevant chunks for questions
- `FAQGeneratorService`: Generates FAQ answers with source citations

All services are autowired and can be injected into controllers or other services.

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
