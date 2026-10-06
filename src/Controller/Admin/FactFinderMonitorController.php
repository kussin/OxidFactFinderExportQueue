<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Controller\Admin;

use Kussin\OxidBase\Service\AdminArticleEditUrlBuilder;
use OxidEsales\Eshop\Application\Controller\Admin\AdminController;
use OxidEsales\Eshop\Core\Registry;
use Wmdk\FactFinderQueue\Service\ModuleSettingsReader;
use Wmdk\FactFinderQueue\Service\QueueMonitorQuery;
use Wmdk\FactFinderQueue\Service\QueueMonitorRepository;
use Wmdk\FactFinderQueue\Service\QueueMonitorUrl;

final class FactFinderMonitorController extends AdminController
{
    private const FILTER_SESSION_KEY = 'kussin_factfinder_monitor_filters';
    private const QUEUE_INTERVAL_SECONDS = 120;

    protected $_sThisTemplate = '@wmdkffexportqueue/admin/factfinder_monitor.html.twig';

    private ?array $resetResult = null;

    public function render()
    {
        parent::render();

        $repository = oxNew(QueueMonitorRepository::class);
        $filters = $this->getFilters();
        $sort = QueueMonitorQuery::normalizeSort($this->getRequestString('monitorSort'));
        $direction = QueueMonitorQuery::normalizeDirection($this->getRequestString('monitorDir'));
        $total = $repository->count($filters);
        $waitingStatus = $this->getWaitingStatus($repository);
        $refreshInterval = $this->getRefreshInterval();
        $pageCount = max(1, (int) ceil($total / QueueMonitorQuery::PAGE_SIZE));
        $page = min(max(1, (int) $this->getRequestString('monitorPage')), $pageCount);

        $this->addTplParam('monitorColumns', $this->buildColumns($filters, $sort, $direction));
        $this->addTplParam(
            'monitorRows',
            $this->prepareRows($repository->findPage($filters, $sort, $direction, $page))
        );
        $this->addTplParam('monitorFilters', $filters);
        $this->addTplParam('monitorChannels', $repository->getChannels());
        $this->addTplParam('monitorSort', $sort);
        $this->addTplParam('monitorDirection', $direction);
        $this->addTplParam('monitorTotal', $total);
        $this->addTplParam('monitorWaiting', $waitingStatus['articles']);
        $this->addTplParam('monitorEtaSeconds', $waitingStatus['etaSeconds']);
        $this->addTplParam('monitorRefreshInterval', $refreshInterval);
        $this->addTplParam('monitorPage', $page);
        $this->addTplParam('monitorPageCount', $pageCount);
        $this->addTplParam('monitorPages', $this->buildPages($page, $pageCount, $filters, $sort, $direction));
        $this->addTplParam('monitorPreviousUrl', $page > 1 ? $this->buildQuery($filters, $sort, $direction, $page - 1) : '');
        $this->addTplParam('monitorNextUrl', $page < $pageCount ? $this->buildQuery($filters, $sort, $direction, $page + 1) : '');
        $this->addTplParam('monitorResetResult', $this->resetResult);

        return $this->_sThisTemplate;
    }

    public function waitingCount(): void
    {
        $status = $this->getWaitingStatus(oxNew(QueueMonitorRepository::class));
        $payload = [
            'success' => true,
            'waiting' => $status['articles'],
            'etaSeconds' => $status['etaSeconds'],
        ];

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function clearFilters(): void
    {
        Registry::getSession()->deleteVariable(self::FILTER_SESSION_KEY);
    }

    public function resetSelected(): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            throw new \RuntimeException('FACT Finder queue resets require a POST request.');
        }

        $selected = Registry::getRequest()->getRequestParameter('monitorSelected');
        $this->resetResult = oxNew(QueueMonitorRepository::class)->resetArticles(
            is_array($selected) ? array_values($selected) : []
        );
    }

    public function exportCsv(): void
    {
        $repository = oxNew(QueueMonitorRepository::class);
        $columns = $repository->getColumnNames();
        $result = $repository->selectForExport(
            $this->getFilters(),
            $this->getRequestString('monitorSort'),
            $this->getRequestString('monitorDir')
        );

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="factfinder-queue-' . date('Y-m-d-His') . '.csv"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        $output = fopen('php://output', 'wb');

        if ($output === false) {
            throw new \RuntimeException('Unable to open the CSV output stream.');
        }

        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, $columns, ';', '"', '\\', "\n");

        if ($result !== false) {
            while (!$result->EOF) {
                $row = $result->getFields();
                $values = [];

                foreach ($columns as $column) {
                    $values[] = $this->sanitizeCsvCell((string) (
                        $row[$column] ?? $row[strtoupper($column)] ?? $row[strtolower($column)] ?? ''
                    ));
                }

                fputcsv($output, $values, ';', '"', '\\', "\n");
                $result->fetchRow();
            }
        }

        fclose($output);
        exit;
    }

    /** @return array<string, string> */
    private function getFilters(): array
    {
        $requestedFilters = Registry::getRequest()->getRequestParameter('monitorFilter');
        $session = Registry::getSession();

        if (is_array($requestedFilters)) {
            $filters = QueueMonitorQuery::normalizeFilters($requestedFilters);
            $session->setVariable(self::FILTER_SESSION_KEY, $filters);

            return $filters;
        }

        return QueueMonitorQuery::normalizeFilters(
            $session->getVariable(self::FILTER_SESSION_KEY)
        );
    }

    private function getRequestString(string $name): string
    {
        $value = Registry::getRequest()->getRequestParameter($name);

        return is_scalar($value) ? (string) $value : '';
    }

    /** @return array{articles: int, etaSeconds: int} */
    private function getWaitingStatus(QueueMonitorRepository $repository): array
    {
        $settings = new ModuleSettingsReader();
        $onlyActive = $settings->getBool('sWmdkFFExportOnlyActive', true);
        $hidden = $settings->getBool('sWmdkFFExportHidden', false);
        $stockMin = (int) $settings->getString('sWmdkFFExportStockMin', '1');
        $queueLimit = max(1, (int) $settings->getString('sWmdkFFQueueLimit', '150'));
        $articles = $repository->countWaiting($onlyActive, $hidden, $stockMin);

        return [
            'articles' => $articles,
            'etaSeconds' => (int) ceil($articles / $queueLimit) * self::QUEUE_INTERVAL_SECONDS,
        ];
    }

    private function getRefreshInterval(): int
    {
        $configuredInterval = (int) (new ModuleSettingsReader())->getString(
            'sKussinFFMonitorRefreshInterval',
            (string) QueueMonitorQuery::DEFAULT_REFRESH_INTERVAL
        );

        return QueueMonitorQuery::normalizeRefreshInterval($configuredInterval);
    }

    /** @param list<array<string, mixed>> $rows */
    private function prepareRows(array $rows): array
    {
        $shopUrl = (string) Registry::getConfig()->getShopUrl();
        $articleEditUrlBuilder = new AdminArticleEditUrlBuilder();

        foreach ($rows as &$row) {
            $row['Deeplink'] = QueueMonitorUrl::absoluteDeeplink((string) ($row['Deeplink'] ?? ''), $shopUrl);
            $row['AdminArticleEditUrl'] = $articleEditUrlBuilder->build(
                (string) ($row['OXID'] ?? ''),
                (string) ($row['ProductNumber'] ?? '')
            );
        }
        unset($row);

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    private function buildColumns(array $filters, string $sort, string $direction): array
    {
        $columns = [];

        foreach (QueueMonitorQuery::DISPLAY_COLUMNS as $column) {
            $nextDirection = ($sort === $column && $direction === 'ASC') ? 'DESC' : 'ASC';
            $columns[] = [
                'name' => $column,
                'filterType' => QueueMonitorQuery::filterType($column),
                'filterValue' => $filters[$column] ?? '',
                'sortUrl' => $this->buildQuery($filters, $column, $nextDirection, 1),
                'active' => $sort === $column,
                'direction' => $direction,
            ];
        }

        return $columns;
    }

    /** @return list<array<string, mixed>> */
    private function buildPages(int $page, int $pageCount, array $filters, string $sort, string $direction): array
    {
        $start = max(1, $page - 3);
        $end = min($pageCount, $page + 3);
        $pages = [];

        for ($number = $start; $number <= $end; $number++) {
            $pages[] = [
                'number' => $number,
                'active' => $number === $page,
                'url' => $this->buildQuery($filters, $sort, $direction, $number),
            ];
        }

        return $pages;
    }

    private function buildQuery(array $filters, string $sort, string $direction, int $page): string
    {
        return http_build_query([
            'cl' => 'wmdkffexport_monitor',
            'monitorFilter' => $filters,
            'monitorSort' => QueueMonitorQuery::normalizeSort($sort),
            'monitorDir' => QueueMonitorQuery::normalizeDirection($direction),
            'monitorPage' => max(1, $page),
        ], '', '&', PHP_QUERY_RFC3986);
    }

    private function sanitizeCsvCell(string $value): string
    {
        if ($value !== '' && !is_numeric($value) && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }
}
