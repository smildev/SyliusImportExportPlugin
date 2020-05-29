<?php

namespace FriendsOfSylius\SyliusImportExportPlugin\Exporter;

interface GridExporterInterface extends ResourceExporterInterface
{
    /**
     * @param string $grid
     */
    public function setGrid(string $grid): void;

    /**
     * @param array $parameters
     */
    public function setParameters(array $parameters): void;
}
