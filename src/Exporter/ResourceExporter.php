<?php

declare(strict_types=1);

namespace FriendsOfSylius\SyliusImportExportPlugin\Exporter;

use FriendsOfSylius\SyliusImportExportPlugin\Exporter\Plugin\PluginPoolInterface;
use FriendsOfSylius\SyliusImportExportPlugin\Exporter\Transformer\TransformerPoolInterface;
use FriendsOfSylius\SyliusImportExportPlugin\Writer\WriterInterface;

class ResourceExporter extends AbstractResourceExporter
{
    /** @var string[] */
    protected $resourceKeys;

    /** @var PluginPoolInterface */
    protected $pluginPool;

    /** @var TransformerPoolInterface|null */
    protected $transformerPool;

    /**
     * @param string[] $resourceKeys
     */
    public function __construct(
        WriterInterface $writer,
        PluginPoolInterface $pluginPool,
        array $resourceKeys,
        ?TransformerPoolInterface $transformerPool
    ) {
        parent::__construct($writer);

        $this->pluginPool = $pluginPool;
        $this->transformerPool = $transformerPool;
        $this->resourceKeys = $resourceKeys;
    }

    /**
     * {@inheritdoc}
     */
    public function export(array $idsToExport): void
    {
        $this->pluginPool->initPlugins($idsToExport);

        $this->writer->write($this->getResourceKeys());

        foreach ($idsToExport as $id) {
            $this->writeDataForId((string) $id);
        }
    }

    /**
     * @param int[] $idsToExport
     *
     * @return array[]
     */
    public function exportData(array $idsToExport): array
    {
        $this->pluginPool->initPlugins($idsToExport);

        $exportIdDataArray = [];

        foreach ($idsToExport as $id) {
            $exportIdDataArray[$id] = $this->getDataForId((string) $id);
        }

        return $exportIdDataArray;
    }

    /**
     * @return array[]
     */
    protected function getDataForId(string $id): array
    {
        $data = $this->pluginPool->getDataForId($id);

        if (null !== $this->transformerPool) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->transformerPool->handle($key, $value);
            }
        }

        return $data;
    }

    protected function getResourceKeys(): array
    {
        return $this->resourceKeys;
    }

    private function writeDataForId(string $id): void
    {
        $dataForId = $this->getDataForId($id);

        $this->writer->write($dataForId);
    }
}
