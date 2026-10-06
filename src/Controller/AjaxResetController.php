<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Controller;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Registry;
use Wmdk\FactFinderQueue\Service\ArticleQueueResetter;
use Wmdk\FactFinderQueue\Service\LogFilePathResolver;

/**
 * Compatibility endpoint used by Worker Mode to re-queue an article family.
 *
 * The legacy `cl=wmdkffexport_ajax&job=reset&oxid=<id>` URL remains stable so
 * existing browser integrations do not need a coordinated deployment.
 */
final class AjaxResetController extends FrontendController
{
    public function render()
    {
        $response = [
            'success' => true,
            'validation_errors' => [],
            'system_errors' => [],
            'reseted' => [],
        ];
        $status = '200 OK';

        $request = Registry::getRequest();
        $jobParameter = $request->getRequestParameter('job');
        $articleIdParameter = $request->getRequestParameter('oxid');
        $job = is_string($jobParameter) ? $jobParameter : '';
        $articleId = is_string($articleIdParameter) ? $articleIdParameter : '';

        if ($job !== 'reset') {
            $response['success'] = false;
            $response['validation_errors'][] = 'ERROR_UNSUPPORTED_JOB';
            $status = '400 Bad Request';
        } elseif (preg_match('/^[a-f0-9]{32}$/i', $articleId) !== 1) {
            $response['success'] = false;
            $response['validation_errors'][] = $articleId === ''
                ? 'ERROR_NO_OXID_GIVEN'
                : 'ERROR_INVALID_OXID';
            $status = '400 Bad Request';
        } else {
            try {
                $response['reseted'] = (new ArticleQueueResetter())->reset(
                    $articleId,
                    (string) ($_SERVER['REMOTE_ADDR'] ?? 'wmdkffexport_ajax')
                );
            } catch (\Throwable $exception) {
                Registry::getLogger()->error('FACT Finder AJAX queue reset failed.', [
                    'article_id' => $articleId,
                    'exception' => $exception,
                ]);

                $response['success'] = false;
                $response['system_errors'][] = 'ERROR_COULD_NOT_RESET';
                $status = '500 Internal Server Error';
            }
        }

        $this->logResponse($response);
        $this->sendJsonResponse($status, $response);
    }

    /**
     * Preserve the operational response log written by the OXID 6 endpoint.
     * Logging must never turn a successful queue reset into an HTTP error.
     *
     * @param array<string, mixed> $response
     */
    private function logResponse(array $response): void
    {
        try {
            $pathResolver = new LogFilePathResolver();
            $path = $pathResolver->resolveFromSetting(
                'sWmdkFFDebugLogFileQueue',
                'log/KUSSIN_FACTFINDER_QUEUE.log'
            );
            $pathResolver->ensureDirectoryForFile($path);
            file_put_contents(
                $path,
                json_encode($response, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );
        } catch (\Throwable $exception) {
            Registry::getLogger()->warning('Could not write the FACT Finder AJAX reset response log.', [
                'exception' => $exception,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $response
     */
    private function sendJsonResponse(string $status, array $response): never
    {
        $utils = Registry::getUtils();
        $utils->setHeader('HTTP/1.1 ' . $status);
        $utils->setHeader('Content-Type: application/json; charset=utf-8');
        $utils->setHeader('Cache-Control: no-store, max-age=0');
        $utils->setHeader('Pragma: no-cache');
        $utils->setHeader('Expires: 0');
        $utils->setHeader('X-Content-Type-Options: nosniff');

        echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        exit;
    }
}
