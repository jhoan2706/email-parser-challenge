# Email JSON Extractor

This project provides an API endpoint that extracts JSON data from an email file. The JSON can be embedded in the body, attached as a file, or linked within the email body. The extracted JSON is then returned as the response.

## Requirements

- PHP 7.4 or higher
- Composer
- Laravel 10.x
- GuzzleHTTP

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/email-json-extractor.git
   cd email-json-extractor
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Create a `.env` file by copying the example and configure your environment variables:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

## Usage

1. Start the Laravel server:
   ```bash
   php artisan serve
   ```

2. Use Postman or any HTTP client to send a POST request to the following endpoint:

   **Endpoint:**
   ```
   POST /api/extract-json
   ```

   **Request Body:**
   ```json
   {
       "email_path": "path/to/email/file.eml"
   }
   ```

   Replace `"path/to/email/file.eml"` with the actual path to your email file.

3. Example of a successful response:
   ```json
   {
       "name": "John Doe",
       "email": "johndoe@example.com",
       "position": {
           "title": "Software Engineer",
           "department": "Engineering"
       }
   }
   ```

## Testing

To manually test the API, you can use Postman or any other HTTP client to send the request as shown in the Usage section.

---

This README file explains how to use the API endpoint, install the project, and provides an example of a successful response in Postman.