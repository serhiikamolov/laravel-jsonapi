<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use JsonAPI\Response\Serializer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;

class SerializerTest extends TestCase
{
    private Model $model;
    private Collection $collection;

    public function setUp(): void
    {
        parent::setUp();

        $model = new class extends Model {};
        $model->id = 1;
        $model->firstname = 'John';
        $model->lastname = 'Doe';
        $model->date = Carbon::now();

        $this->model = $model;

        $this->collection = new Collection([
           $model, $model
        ]);

    }

    public function test_serializeModel()
    {
        $serializer = new Serializer();
        $this->assertSame(
            $this->model->toArray(),
            $serializer->serialize($this->model),
            'Array of fields is expected'
        );
    }

    public function test_serializeCollection()
    {
        $serializer = new Serializer();

        $this->assertSame(
            $this->collection->toArray(),
            $serializer->serialize($this->collection),
            'Array of arrays is expected'
        );
    }

    public function test_serializeWithMethods()
    {
        $serializer = new class extends Serializer{
            protected array $fields = [
                'id',
                'name',
                'date',
            ];

            // add new property
            public function name(Model $item):string
            {
                return $item->firstname . " " . $item->lastname;
            }

            // override exist property
            public function date(Model $item):int
            {
                return 123456;
            }
        };

        App::shouldReceive('call')->andReturn(
            $serializer->name($this->model),
            $serializer->date($this->model)
        );

        $this->assertSame(
            ['id' => 1, 'name' => 'John Doe', 'date' => 123456],
            $serializer->serialize($this->model)
        );
    }

    public function test_serializeWithModifier()
    {
        $serializer = new class extends Serializer{

            protected array $fields = [
                'id',
                'date:timestamp,minutes'
            ];

            /**
             * Convert seconds to minutes
             * @param $value
             * @return float|int
             */
            public function modifierMinutes($value)
            {
                return $value / 60;
            }
        };

        $this->assertSame(
            [
                'id' => 1,
                'date' => $serializer->modifierMinutes(
                    Carbon::parse($this->model->date)->timestamp
                )
            ],
            $serializer->serialize($this->model)
        );
    }


    public function serializeWithInvalidModifierProvider():array
    {
        return [
            [
                ['id', 'date:test'], "Invalid modifier: test"
            ],
            [
                ['id', 'date:timestamp:trim'], "Invalid modifiers format: date"
            ]
        ];
    }


    /**
     * @dataProvider serializeWithInvalidModifierProvider
     * @param array $fields
     * @param $expectedException
     * @throws \JsonAPI\Exceptions\SerializerException
     */
    public function test_serializeWithInvalidModifier(array $fields, $expectedException)
    {
        $serializer = new Serializer($fields);

        $this->expectException(\JsonAPI\Exceptions\SerializerException::class);
        $this->expectErrorMessage($expectedException);
        $serializer->serialize($this->model);
    }


    public function test_serializeOnlyMethod()
    {
        $serializer = new Serializer(['id', 'firstname' => 'trim', 'lastname', 'date' => 'timestamp']);

        $this->assertSame(
            ['firstname' => 'John', 'lastname' => 'Doe'],
            $serializer->only(['firstname', 'lastname'])->serialize($this->model),
            'Array of fields is expected'
        );
    }
}
