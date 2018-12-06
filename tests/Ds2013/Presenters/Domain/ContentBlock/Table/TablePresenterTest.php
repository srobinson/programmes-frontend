<?php
declare(strict_types = 1);

namespace Tests\App\Ds2013\Presenters\Domain\ContentBlock\Table;

use App\Ds2013\Presenters\Domain\ContentBlock\Table\TablePresenter;
use App\ExternalApi\Isite\Domain\ContentBlock\Table;
use PHPUnit\Framework\TestCase;

class TablePresenterTest extends TestCase
{
    /**
     * @dataProvider cellClassProvider
     */
    public function testCorrectCellClasses(bool $isPrimary, string $classes)
    {
        $presenter = new TablePresenter($this->createMock(Table::class), $isPrimary, true);
        $this->assertSame($classes, $presenter->getCellClasses());
    }

    public function cellClassProvider(): array
    {
        return [
            'primary' => [true, 'br-box-subtle br-page-bg-onborder'],
            'secondary' => [false, 'br-box-page br-subtle-bg-onborder'],
        ];
    }

    /**
     * @dataProvider cellHeaderProvider
     */
    public function testCorrectHeaderClasses(bool $isPrimary, string $classes)
    {
        $presenter = new TablePresenter($this->createMock(Table::class), $isPrimary, true);
        $this->assertSame($classes, $presenter->getHeaderClasses());
    }

    public function cellHeaderProvider(): array
    {
        return [
            'primary' => [true, 'br-box-highlight br-page-bg-onborder'],
            'secondary' => [false, 'br-box-highlight br-subtle-bg-onborder'],
        ];
    }
}
