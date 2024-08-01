<?php

namespace App\Http\Controllers;

use PhpMimeMailParser\Parser;
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
        // Validate the request to ensure 'email_path' is a valid .eml file
        $request->validate([
            'email_path' => 'required|file|mimes:eml'
        ]);
        
        // Get the path to the email file from the request
        $filePath = $request->input('email_path');
        
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
            if ($attachment->getMimeType() === 'application/json') {
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

        // Loop through all links to find one ending with .json
        foreach ($links as $link) {
            $url = $link->getAttribute('href');
            if (preg_match('/\.json$/', $url)) {
                return $this->fetchJsonFromUrl($url); // Fetch JSON from the link
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
        $client = new Client();
        $response = $client->get($url);

        if ($response->getStatusCode() === 200) {
            return $response->getBody()->getContents();
        }

        return null;
    }
}
