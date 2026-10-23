<?php

/*
 * This file is part of Laravel Swap.
 *
 * (c) Florian Voutzinos <florian@voutzinos.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swap\Laravel\Tests;

use Exchanger\Service\Registry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Assert;
use Swap\Swap;

/**
 * Keeps the services reference in config/swap.php and the README provider tables in sync with Exchanger.
 */
final class DocumentationTest extends AbstractTestCase
{
    private const CONFIG = __DIR__ . '/../config/swap.php';

    private const README = __DIR__ . '/../README.md';

    /**
     * Registered identifiers that are deliberately absent from the config reference, with the reason.
     */
    private const NOT_IN_CONFIG = [
        'array' => 'in-memory service fed with fixed rates, not an exchange rate provider',
    ];

    /**
     * Registered identifiers that are deliberately absent from the README provider tables, with the reason.
     */
    private const NOT_IN_README = [
        'array' => 'in-memory service fed with fixed rates, not an exchange rate provider',
        'fixer_apilayer' => 'APILayer Fixer variant sending the key as a header; the README lists apilayer_fixer',
    ];

    #[Test]
    #[DataProvider('provideRegisteredIdentifiers')]
    public function it_lists_each_service_in_the_config_reference(string $identifier): void
    {
        if (isset(self::NOT_IN_CONFIG[$identifier])) {
            $this->markTestSkipped(self::NOT_IN_CONFIG[$identifier]);
        }

        Assert::assertArrayHasKey(
            $identifier,
            self::documentedServices(),
            \sprintf('Add `%s` to the services reference in the comment above "services" in config/swap.php.', $identifier),
        );
    }

    #[Test]
    #[DataProvider('provideRegisteredIdentifiers')]
    public function it_lists_each_service_in_the_readme(string $identifier): void
    {
        if (isset(self::NOT_IN_README[$identifier])) {
            $this->markTestSkipped(self::NOT_IN_README[$identifier]);
        }

        Assert::assertStringContainsString(
            '`' . $identifier . '`',
            self::read(self::README),
            \sprintf('Add a row for `%s` to the "Commercial providers" or "Public providers" table in README.md.', $identifier),
        );
    }

    /**
     * @param array<string, string|bool>|true $options
     */
    #[Test]
    #[DataProvider('provideDocumentedServices')]
    public function it_builds_each_service_with_its_documented_options(string $identifier, array|bool $options): void
    {
        $this->setConfig('swap.services', [$identifier => $options]);

        Assert::assertInstanceOf(Swap::class, $this->application()->make('swap'));
    }

    /**
     * Guards against documenting an option name the service ignores: without the credential, the service must refuse to build.
     *
     * @param array<string, string|bool> $options
     */
    #[Test]
    #[DataProvider('provideServicesWithOptions')]
    public function it_requires_the_documented_credential(string $identifier, array $options): void
    {
        $credential = (string) array_key_first($options);
        unset($options[$credential]);
        $this->setConfig('swap.services', [$identifier => $options]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(\sprintf('The "%s" option must be provided', $credential));

        $this->application()->make('swap');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideRegisteredIdentifiers(): iterable
    {
        foreach (array_keys(Registry::getServices()) as $identifier) {
            yield (string) $identifier => [(string) $identifier];
        }
    }

    /**
     * @return iterable<string, array{string, array<string, string|bool>|true}>
     */
    public static function provideDocumentedServices(): iterable
    {
        foreach (self::documentedServices() as $identifier => $options) {
            yield $identifier => [$identifier, $options];
        }
    }

    /**
     * @return iterable<string, array{string, array<string, string|bool>}>
     */
    public static function provideServicesWithOptions(): iterable
    {
        foreach (self::documentedServices() as $identifier => $options) {
            if (\is_array($options)) {
                yield $identifier => [$identifier, $options];
            }
        }
    }

    /**
     * Parses the services reference in config/swap.php: identifiers listed as enabled with true,
     * and one "'identifier' => ['option' => value, ...]," line per service that takes options.
     *
     * @return array<string, array<string, string|bool>|true>
     */
    private static function documentedServices(): array
    {
        $config = self::read(self::CONFIG);
        $services = [];

        Assert::assertSame(1, preg_match('/are enabled with true:\n(.*?)\n\s*\|\s*\n\s*\| Services that need credentials/s', $config, $match));

        preg_match_all('/[a-z_]+/', str_replace('|', '', $match[1]), $identifiers);

        foreach ($identifiers[0] as $identifier) {
            $services[$identifier] = true;
        }

        preg_match_all("/^\\s*\\|\\s+'([a-z_]+)'\\s+=> \\[(.*)\\],/m", $config, $lines, PREG_SET_ORDER);
        Assert::assertNotEmpty($lines, 'No "\'identifier\' => [...]," lines found in the config/swap.php services reference.');

        foreach ($lines as [, $identifier, $optionList]) {
            preg_match_all("/'([\\w-]+)' => ('[^']*'|true|false)/", $optionList, $pairs, PREG_SET_ORDER);

            $options = [];

            foreach ($pairs as [, $name, $value]) {
                $options[$name] = match ($value) {
                    'true' => true,
                    'false' => false,
                    default => trim($value, "'"),
                };
            }

            $services[$identifier] = $options;
        }

        return $services;
    }

    private static function read(string $path): string
    {
        $contents = file_get_contents($path);
        Assert::assertIsString($contents, \sprintf('Could not read %s.', $path));

        return $contents;
    }
}
