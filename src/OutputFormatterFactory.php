<?php

declare(strict_types=1);

namespace Qossmic\Deptrac;

use InvalidArgumentException;
use LogicException;
use Qossmic\Deptrac\OutputFormatter\OutputFormatterInterface;
use Symfony\Component\Console\Input\InputOption;

class OutputFormatterFactory
{
    /**
     * @var array<string, OutputFormatterInterface>
     */
    protected array $formatters = [];

    /**
     * @param iterable<OutputFormatterInterface> $formatters
     */
    public function __construct(iterable $formatters)
    {
        foreach ($formatters as $formatter) {
            $this->addFormatter($formatter);
        }
    }

    /**
     * @return InputOption[]
     */
    public function getFormatterOptions(): array
    {
        $arguments = [];

        foreach ($this->formatters as $formatter) {
            $formatterArguments = $formatter->configureOptions();

            foreach ($formatterArguments as $formatterArgument) {
                $arguments[] = new InputOption(
                    $formatterArgument->getName(),
                    null,
                    $formatterArgument->getMode(),
                    $formatterArgument->getDescription(),
                    $formatterArgument->getDefault()
                );
            }
        }

        return $arguments;
    }

    /**
     * @throws LogicException if formatter does not exists
     */
    public function getFormatterByName(string $name): OutputFormatterInterface
    {
        foreach ($this->formatters as $formatter) {
            if (strtolower($name) !== strtolower($formatter->getName())) {
                continue;
            }

            return $formatter;
        }

        throw new LogicException(sprintf('Formatter %s does not exists, did you mean %s?', $name, implode(', ', array_map(static function (OutputFormatterInterface $f): string { return $f->getName(); }, $this->formatters))));
    }

    private function addFormatter(OutputFormatterInterface $formatter): void
    {
        $this->formatters[$formatter->getName()] = $formatter;
    }

    /**
     * @param string[] $names
     *
     * @return OutputFormatterInterface[]
     */
    public function getFormattersByNames(array $names): array
    {
        $invalidNames = [];
        foreach ($names as $name) {
            if (!isset($this->formatters[$name])) {
                $invalidNames[] = $name;
            }
        }

        if ([] !== $invalidNames) {
            throw new InvalidArgumentException(sprintf('Following formatters ["%s"] are not supported.', implode('", "', $invalidNames)));
        }

        return array_values(
            array_filter(
                $this->formatters,
                static function (string $key) use ($names) {
                    return in_array($key, $names, true);
                },
                ARRAY_FILTER_USE_KEY
            )
        );
    }

    /**
     * @return string[]
     */
    public function getFormatterNames(): array
    {
        return array_keys($this->formatters);
    }

    /**
     * @return OutputFormatterInterface[]
     */
    public function getFormattersEnabledByDefault(): array
    {
        return array_values(array_filter($this->formatters, static function (OutputFormatterInterface $formatter) {
            return $formatter->enabledByDefault();
        }));
    }
}
