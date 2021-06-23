<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Console\Command;

use Qossmic\Deptrac\Configuration\ConfigurationLayer;
use Qossmic\Deptrac\Configuration\Loader;
use Qossmic\Deptrac\Console\Command\Exception\SingleDepfileIsRequiredException;
use Qossmic\Deptrac\Console\Symfony\Style;
use Qossmic\Deptrac\LayerAnalyzer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DebugLayerCommand extends Command
{
    protected static $defaultName = 'debug:layer';

    /** @var LayerAnalyzer */
    private $analyzer;
    /** @var Loader */
    private $loader;

    public function __construct(
        LayerAnalyzer $analyzer,
        Loader $loader
    ) {
        parent::__construct();

        $this->analyzer = $analyzer;
        $this->loader = $loader;
    }

    protected function configure(): void
    {
        $this->addArgument('depfile', InputArgument::REQUIRED, 'Path to the depfile');
        $this->addArgument('layer', InputArgument::REQUIRED, 'Layer to debug');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new Style(new SymfonyStyle($input, $output));

        $depfile = $input->getArgument('depfile');

        if (!is_string($depfile)) {
            throw SingleDepfileIsRequiredException::fromArgument($depfile);
        }

        /** @var string $layer */
        $layer = $input->getArgument('layer');

        $configuration = $this->loader->load($depfile);

        $configurationLayers = array_map(static function (ConfigurationLayer $configurationLayer) {
            return $configurationLayer->getName();
        }, $configuration->getLayers());

        if (!in_array($layer, $configurationLayers, true)) {
            $style->error('Layer not found.');

            return 1;
        }

        $matchedLayers = $this->analyzer->analyze($configuration, $layer);
        natcasesort($matchedLayers);

        $output->writeln($matchedLayers);

        return 0;
    }
}
