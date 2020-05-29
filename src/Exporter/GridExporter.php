<?php

declare(strict_types=1);

namespace FriendsOfSylius\SyliusImportExportPlugin\Exporter;

use FriendsOfSylius\SyliusImportExportPlugin\Exception\ExporterException;
use FriendsOfSylius\SyliusImportExportPlugin\Exporter\Plugin\PluginPoolInterface;
use FriendsOfSylius\SyliusImportExportPlugin\Exporter\Transformer\TransformerPoolInterface;
use FriendsOfSylius\SyliusImportExportPlugin\Writer\WriterInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Component\Grid\Data\DataProvider;
use Sylius\Component\Grid\Data\DataProviderInterface;
use Sylius\Component\Grid\Definition\Field;
use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Parameters;
use Sylius\Component\Grid\Provider\GridProviderInterface;
use Sylius\Component\Grid\Renderer\GridRendererInterface;
use Sylius\Component\Grid\View\GridView;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class GridExporter extends AbstractResourceExporter
{
    /** @var WriterInterface */
    protected $writer;

    /** @var TranslatorInterface */
    private $translator;

    /** @var Grid */
    private $gridDefinition;

    /** @var GridProviderInterface */
    private $gridProvider;

    /** @var GridRendererInterface */
    private $gridRenderer;

    /** @var DataProviderInterface */
    private $dataProvider;

    /** @var string */
    private $resource;

    /** @var  */
    private $parameters;

    /** @var array */
    private $data;

    public function __construct(
        WriterInterface $writer,
        TranslatorInterface $translator,
        GridProviderInterface $gridProvider,
        GridRendererInterface $gridRenderer,
        DataProvider $dataProvider,
        string $resource
    ) {
        parent::__construct($writer);

        $this->translator = $translator;
        $this->gridProvider = $gridProvider;
        $this->gridRenderer = $gridRenderer;
        $this->dataProvider = $dataProvider;
        $this->resource = $resource;

        $this->parameters = [];
        $this->data = [];
    }

    public function setGrid(string $grid): void
    {
        $this->gridDefinition = $this->gridProvider->get($grid);
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function export(array $idsToExport): void
    {
        $resources = $this->getResources($idsToExport);

        $this->writer->write($this->getResourceKeys());

        foreach ($resources as $resource) {
            $this->writeData($resource);
        }
    }

    /**
     * @param int[] $idsToExport
     *
     * @return array[]
     */
    public function exportData(array $idsToExport): array
    {
        $resources = $this->getResources($idsToExport);

        $exportIdDataArray = [];

        foreach ($resources as $resource) {
            $exportIdDataArray[$resource->getId()] = $this->getData($resource);
        }

        return $exportIdDataArray;
    }

    /**
     * @param ResourceInterface $resource
     *
     * @return array[]
     */
    protected function getData(ResourceInterface $resource): array
    {
        $data = [];
        /** @var Field[] $fields */
        $fields = $this->getFields();

        foreach ($fields as $field) {
            $data[$field->getLabel()] = $this->getFieldValue($field, $resource);
        }

        return $data;
    }

    protected function getFieldValue(Field $field, $data): string
    {
        $gridView = new GridView($data, $this->gridDefinition, new Parameters());

        $renderedData = $this->gridRenderer->renderField($gridView, $field, $data);
        $renderedData = str_replace(PHP_EOL, "", $renderedData);
        $renderedData = strip_tags($renderedData);

        return $renderedData;
    }

    protected function getFields(): array
    {
        $fields = $this->gridDefinition->getEnabledFields();

        return $this->sortFields($fields);
    }

    protected function sortFields(array $fields): array
    {
        $sortedFields = $fields;

        uasort($sortedFields, function (Field $fieldA, Field $fieldB) {
            if ($fieldA->getPosition() == $fieldB->getPosition()) {
                return 0;
            }

            return ($fieldA->getPosition() < $fieldB->getPosition()) ? -1 : 1;
        });

        return $sortedFields;
    }

    protected function getLabel(Field $field)
    {
        return $this->translator->trans($field->getLabel());
    }

    /**
     * @param ResourceInterface[]|array $idsToExport
     *
     * @return array
     */
    protected function getResources(array $idsToExport): array
    {
        $parameters = array_merge(['ids' => $idsToExport, $this->parameters]);

        return $this->dataProvider->getData($this->gridDefinition, new Parameters($parameters));
    }

    private function writeData(ResourceInterface $resource): void
    {
        $data = $this->getData($resource);

        $this->writer->write($data);
    }

    protected function getResourceKeys(): array
    {
        $headers = [];

        foreach($this->getFields() as $field) {
            $headers[] = $this->getLabel($field);
        }

        return $headers;
    }
}
