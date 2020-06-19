<?php

namespace spec\FriendsOfSylius\SyliusImportExportPlugin\Exporter;

use FriendsOfSylius\SyliusImportExportPlugin\Exporter\AbstractResourceExporter;
use FriendsOfSylius\SyliusImportExportPlugin\Exporter\GridExporter;
use FriendsOfSylius\SyliusImportExportPlugin\Exporter\GridExporterInterface;
use FriendsOfSylius\SyliusImportExportPlugin\Writer\WriterInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\Grid\Data\DataProviderInterface;
use Sylius\Component\Grid\Definition\Field;
use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Parameters;
use Sylius\Component\Grid\Provider\GridProviderInterface;
use Sylius\Component\Grid\Renderer\GridRendererInterface;
use Sylius\Component\Grid\View\GridView;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class GridExporterSpec extends ObjectBehavior
{
    function let(
        WriterInterface $writer,
        TranslatorInterface $translator,
        GridProviderInterface $gridProvider,
        GridRendererInterface $gridRenderer,
        DataProviderInterface $dataProvider
    ) {
        $this->beConstructedWith($writer, $translator, $gridProvider, $gridRenderer, $dataProvider);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(GridExporter::class);
    }

    function it_extends_abstract_exporter()
    {
        $this->shouldHaveType(AbstractResourceExporter::class);
    }

    function it_can_set_grid(GridProviderInterface $gridProvider)
    {
        $gridProvider->get('grid')->shouldBeCalled();

        $this->setGrid('grid');
    }

    function it_can_export(
        DataProviderInterface $dataProvider,
        ResourceInterface $resource,
        GridProviderInterface $gridProvider,
        GridRendererInterface $gridRenderer,
        TranslatorInterface $translator,
        WriterInterface $writer,
        Grid $grid,
        Field $field
    ) {
        $idsToExport = [1, 2];
        $parameters = ["order" => 1];

        $gridProvider->get('grid')->willReturn($grid);

        $this->setParameters($parameters);
        $this->setGrid('grid');

        $field->getLabel()->willReturn('label');
        $dataProvider->getData($grid, Argument::type(Parameters::class))->willReturn([$resource]);

        $grid->getEnabledFields()->willReturn([$field]);
        $gridRenderer->renderField(Argument::type(GridView::class), $field, $resource)->willReturn("value");
        $translator->trans('label')->willReturn('label');

        $dataProvider->getData($grid, Argument::type(Parameters::class))->shouldBeCalled();
        $field->getLabel()->shouldBeCalled();
        $translator->trans('label')->shouldBeCalled();
        $writer->write(['label'])->shouldBeCalled();
        $gridRenderer->renderField(Argument::type(GridView::class), $field, $resource)->shouldBeCalled();
        $writer->write(['label' => 'value'])->shouldBeCalled();

        $this->export($idsToExport);
    }

    function it_can_export_data(
        DataProviderInterface $dataProvider,
        TranslatorInterface $translator,
        ResourceInterface $resource,
        GridProviderInterface $gridProvider,
        GridRendererInterface $gridRenderer,
        Grid $grid,
        Field $field
    ) {
        $idsToExport = [1, 2];
        $parameters = ["order" => 1];

        $resource->getId()->willReturn(1);
        $gridProvider->get('grid')->willReturn($grid);

        $this->setParameters($parameters);
        $this->setGrid('grid');

        $field->getLabel()->willReturn('label');
        $dataProvider->getData($grid, Argument::type(Parameters::class))->willReturn([$resource]);

        $grid->getEnabledFields()->willReturn([$field]);
        $gridRenderer->renderField(Argument::type(GridView::class), $field, $resource)->willReturn("value");

        $dataProvider->getData($grid, Argument::type(Parameters::class))->shouldBeCalled();
        $field->getLabel()->shouldBeCalled();

        $gridRenderer->renderField(Argument::type(GridView::class), $field, $resource)->shouldBeCalled();

        $this->exportData($idsToExport)->shouldReturn([1 => ['label' => 'value']]);
    }

    function it_can_set_export_file(WriterInterface $writer)
    {
        $writer->setFile('filename')->shouldBeCalled();

        $this->setExportFile('filename');
    }

    function it_can_get_exported_data(WriterInterface $writer)
    {
        $writer->getFileContent()->shouldBeCalled();

        $this->getExportedData();
    }

    function it_can_finish_export(WriterInterface $writer)
    {
        $writer->finish()->shouldBeCalled();

        $this->finish();
    }

    function it_throw_an_exception_if_no_grid_is_defined()
    {
        $exception = new \Exception("No grid found, 'setGrid' must be called before exporting data");
        $this->shouldThrow($exception)->during('export', [[]]);
    }
}
