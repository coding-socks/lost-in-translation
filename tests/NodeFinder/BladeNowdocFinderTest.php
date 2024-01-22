<?php

namespace CodingSocks\LostInTranslation\Tests\NodeFinder;

use CodingSocks\LostInTranslation\NodeFinder\BladeNowdocFinder;
use CodingSocks\LostInTranslation\Tests\TestCase;

class BladeNowdocFinderTest extends TestCase
{
    public function testBladeDirective()
    {
        $str = <<<'EOD'
<?php

class ExampleObject
{
    /**
     * Create a new component instance.
     */
    public function skip()
    {
        return <<<'eod'
<div>
    <!-- This has to be undetected. -->
</div>
eod;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function keep(): string
    {
        return <<<'blade'
<div>
    <!-- This has to be detected. -->
</div>
blade;
    }
}
EOD;

        $finder = new BladeNowdocFinder();

        $nodes = $finder->find($str);

        $this->assertCount(1, $nodes);
    }
}