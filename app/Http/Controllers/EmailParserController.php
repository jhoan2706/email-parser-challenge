<?php

namespace App\Http\Controllers;

use eXorus\PhpMimeMailParser\Parser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;

class EmailParserController extends Controller
{
    /**
     * Parse the email to extract JSON content.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function parseEmail(Request $request)
    {
        // Get the uploaded file
        $file = $request->file('email_path');

        // Check if file is uploaded successfully
        if (!$file) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        // Get the file path
        $filePath = $file->getPathname();

        // Create a new parser instance and set the email file path
        $parser = new Parser();
        $parser->setPath($filePath);

        $jsonContent = $this->extractJsonFromAttachments($parser);

        // If no JSON found in attachments, try extracting from email body
        if (!$jsonContent) {
            $jsonContent = $this->extractJsonFromBody($parser);
        }

        // Return JSON content if found, otherwise return an error response
        if ($jsonContent) {
            return response()->json(json_decode($jsonContent));
        }

        return response()->json(['error' => 'JSON not found'], 404);
    }

    /**
     * Extract JSON content from email attachments.
     *
     * @param  Parser  $parser
     * @return string|null
     */
    private function extractJsonFromAttachments(Parser $parser)
    {
        // Loop through each attachment to find JSON files
        foreach ($parser->getAttachments() as $attachment) {
            if ($attachment->getContentType() === 'application/json') {
                return $attachment->getContent(); // Return JSON content if found
            }
        }
        return null;
    }

    /**
     * Extract JSON content from the email body.
     *
     * @param  Parser  $parser
     * @return string|null
     */
    private function extractJsonFromBody(Parser $parser)
    {
        // Get the HTML body of the email
        $html = $parser->getMessageBody('html');
        $jsonFromLink = $this->findJsonLinkInHtml($html);

        if ($jsonFromLink) {
            return $jsonFromLink; // Return JSON content if a link is found
        }

        return null;
    }

    /**
     * Find a JSON link within the HTML body.
     *
     * @param  string  $html
     * @return string|null
     */
    private function findJsonLinkInHtml($html)
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html); // Suppress warnings for malformed HTML
        $links = $dom->getElementsByTagName('a');

        // Loop through all links to find one that is a Google Drive link
        foreach ($links as $link) {
            $url = $link->getAttribute('href');

            // Check if the link is a Google Drive link
            if (preg_match('/drive\.google\.com\/file\/d\/(.*)\/view/', $url)) {
                return $this->fetchJsonFromUrl($url); // Fetch JSON from the Google Drive link
            }
        }

        return null;
    }

    /**
     * Fetch JSON content from a given URL.
     *
     * @param  string  $url
     * @return string|null
     */
    private function fetchJsonFromUrl($url)
    {
        // Convert Google Drive view link to direct download link if needed
        if (preg_match('/drive\.google\.com\/file\/d\/(.*)\/view/', $url, $matches)) {
            $fileId = $matches[1];
            $url = "https://drive.google.com/uc?id={$fileId}&export=download";
        }

        $client = new \GuzzleHttp\Client(['verify' => false]);

        try {
            $response = $client->get($url);

            if ($response->getStatusCode() === 200) {
                return $response->getBody()->getContents(); // Return the JSON content
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            error_log('Failed to fetch JSON: ' . $e->getMessage());
            return null;
        }

        return null;
    }
}
