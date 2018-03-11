<?php
/**
 * This file is part of prolic/fpp.
 * (c) 2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FppTest;

use Fpp\Constructor;
use Fpp\Definition;
use Fpp\DefinitionCollection;
use function Fpp\locatePsrPath;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use const Fpp\loadTemplate;
use const Fpp\replace;
use function Fpp\dump;

class DumpTest extends TestCase
{
    /**
     * @var vfsStream
     */
    private $root;

    /**
     * @var callable
     */
    private $dump;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup();
        $root = $this->root->url();

        $prefixesPsr4 = [
            'Foo\\' => [
                $root . '/Foo',
            ],
        ];

        $locatePsrPath = function (Definition $definition, ?Constructor $constructor) use ($prefixesPsr4): string {
            return locatePsrPath($prefixesPsr4, [], $definition, $constructor);
        };

        $this->dump = function (DefinitionCollection $collection) use ($locatePsrPath): void {
            dump($collection, $locatePsrPath, loadTemplate, replace);
        };
    }

    /**
     * @test
     */
    public function it_dumps_simple_class(): void
    {
        $dump = $this->dump;

        $definition = new Definition('Foo', 'Bar', [new Constructor('String')]);
        $collection = $this->buildCollection($definition);

        $expected = <<<CODE
<?php

// this file is auto-generated by prolic/fpp
// don't edit this file manually

declare(strict_types=1);

namespace Foo;

final class Bar
{
    private \$value;

    public function __construct(string \$value)
    {
        \$this->value = \$value;
    }

    public function value(): string
    {
        return \$this->value;
    }
}

CODE;
        $dump($collection);
        $this->assertSame($expected, file_get_contents($this->root->url() . '/Foo/Bar.php'));
    }

    /**
     * @test
     */
    public function it_dumps_class_incl_its_child(): void
    {
        $dump = $this->dump;

        $definition = new Definition(
            'Foo',
            'Bar',
            [
                new Constructor('Foo\Bar'),
                new Constructor('Foo\Baz'),
            ]
        );

        $collection = $this->buildCollection($definition);

        $expected1 = <<<CODE
<?php

// this file is auto-generated by prolic/fpp
// don't edit this file manually

declare(strict_types=1);

namespace Foo;

class Bar
{
}

CODE;

        $expected2 = <<<CODE
<?php

// this file is auto-generated by prolic/fpp
// don't edit this file manually

declare(strict_types=1);

namespace Foo;

final class Baz extends Bar
{
}

CODE;

        $dump($collection);
        $this->assertSame($expected1, file_get_contents($this->root->url() . '/Foo/Bar.php'));
        $this->assertSame($expected2, file_get_contents($this->root->url() . '/Foo/Baz.php'));
    }

    private function buildCollection(Definition ...$definition): DefinitionCollection
    {
        $collection = new DefinitionCollection();

        foreach (func_get_args() as $arg) {
            $collection->addDefinition($arg);
        }

        return $collection;
    }
}
