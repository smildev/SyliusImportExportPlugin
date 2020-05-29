<?php

declare(strict_types=1);

namespace FriendsOfSylius\SyliusImportExportPlugin\Exporter;

use FriendsOfSylius\SyliusImportExportPlugin\Exporter\Plugin\PluginPoolInterface;
use FriendsOfSylius\SyliusImportExportPlugin\Exporter\Transformer\TransformerPoolInterface;
use FriendsOfSylius\SyliusImportExportPlugin\Writer\WriterInterface;

abstract class AbstractResourceExporter implements ResourceExporterInterface
{
    /** @var WriterInterface */
    protected $writer;

    public function __construct(WriterInterface $writer) {
        $this->writer = $writer;
    }

    /**
     * {@inheritdoc}
     */
    public function setExportFile(string $filename): void
    {
        $this->writer->setFile($filename);
    }

    /**
     * {@inheritdoc}
     */
    public function getExportedData(): string
    {
        return $this->writer->getFileContent();
    }

    /**
     * {@inheritdoc}
     */
    abstract public function export(array $idsToExport): void;

    /**
     * @param int[] $idsToExport
     *
     * @return array[]
     */
    abstract public function exportData(array $idsToExport): array;

    public function finish(): void
    {
        $this->writer->finish();
    }

    abstract protected function getResourceKeys(): array;
}
