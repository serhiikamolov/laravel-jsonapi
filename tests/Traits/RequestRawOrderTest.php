<?php declare(strict_types=1);

use JsonAPI\Traits\RequestRawOrder;
use PHPUnit\Framework\TestCase;

class RequestRawOrderTest extends TestCase
{
    public function test_it_returns_default_when_no_fields_are_allowed(): void
    {
        $request = new class {
            use RequestRawOrder;

            public string $order = '-created_at';
        };

        $this->assertSame('id asc', $request->getRawOrder());
    }

    public function test_it_builds_order_from_allowed_fields(): void
    {
        $request = new class {
            use RequestRawOrder;

            public string $order = '-created_at,name';

            protected function rawOrderFields(): array
            {
                return ['created_at', 'name'];
            }
        };

        $this->assertSame('created_at DESC, name ASC', $request->getRawOrder());
    }

    public function test_it_ignores_fields_that_are_not_allowed(): void
    {
        $request = new class {
            use RequestRawOrder;

            public string $order = '-created_at,password';

            protected function rawOrderFields(): array
            {
                return ['created_at'];
            }
        };

        $this->assertSame('created_at DESC', $request->getRawOrder());
    }
}
