<?php

namespace Slub\SlubFindExtend\Tests\Unit\ViewHelpers;

use Slub\SlubFindExtend\Tests\Unit\Fixtures\LoadableClass;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class LinksFromDataViewHelperTest extends UnitTestCase
{
    /**
     * @test
     */
    public function methodReturnsTrue(): void
    {
        $firstClassObject = new LoadableClass();
        self::assertTrue($firstClassObject->returnsTrue());
    }
}
