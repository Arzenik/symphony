<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class OAuthController extends AbstractController
{
    #[Route('/oauth/authorize', name: 'oauth_authorize', methods: ['GET'])]
    public function authorize(Request $request, LoggerInterface $logger): Response
    {
        $githubOAuthUrl = 'https://github.com/login/oauth/authorize';
        $clientId = $_ENV['OAUTH_GITHUB_ID'] ?? null;
        $redirectUri = 'http://localhost:80/oauth/callback';

        if (!$clientId) {
            $logger->error('GitHub Client ID not found in environment variables');
            return new JsonResponse(['error' => 'OAuth configuration error'], 500);
        }

        $logger->info('Starting OAuth process', [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'env_vars' => array_keys($_ENV)
        ]);
        
        $url = sprintf(
            '%s?client_id=%s&redirect_uri=%s&scope=read:user,user:email',
            $githubOAuthUrl,
            $clientId,
            urlencode($redirectUri)
        );

        $logger->info('Generated GitHub URL', ['url' => $url]);

        return new Response(
            sprintf('<html><head><title>Redirecting...</title></head><body>Redirecting to <a href="%s">GitHub</a>...</body></html>', htmlspecialchars($url)),
            302,
            ['Location' => $url]
        );
    }

    #[Route('/oauth/callback', name: 'oauth_callback', methods: ['GET'])]
    public function callback(Request $request, LoggerInterface $logger): JsonResponse
    {
        $code = $request->query->get('code');
        $error = $request->query->get('error');
        
        $logger->info('Received callback', [
            'code' => $code,
            'error' => $error,
            'query_params' => $request->query->all()
        ]);

        if ($error) {
            return new JsonResponse(['error' => $error], 400);
        }

        if (!$code) {
            return new JsonResponse(['error' => 'No code provided'], 400);
        }

        // Échange du code contre un token
        $clientId = $_ENV['OAUTH_GITHUB_ID'];
        $clientSecret = $_ENV['OAUTH_GITHUB_SECRET'];

        $logger->info('Attempting token exchange', [
            'client_id_exists' => !empty($clientId),
            'client_secret_exists' => !empty($clientSecret)
        ]);
        
        $ch = curl_init('https://github.com/login/oauth/access_token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        $postData = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code
        ];
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $logger->info('GitHub API response', [
            'http_code' => $httpCode,
            'response' => $response,
            'curl_error' => $error,
            'post_data' => $postData
        ]);

        if ($httpCode !== 200) {
            return new JsonResponse([
                'error' => 'Failed to exchange code for token',
                'details' => [
                    'http_code' => $httpCode,
                    'response' => $response,
                    'curl_error' => $error
                ]
            ], 500);
        }

        $data = json_decode($response, true);
        
        if (!isset($data['access_token'])) {
            return new JsonResponse([
                'error' => 'No token in response',
                'response_data' => $data
            ], 500);
        }

        return new JsonResponse([
            'token' => $data['access_token']
        ]);
    }
} 