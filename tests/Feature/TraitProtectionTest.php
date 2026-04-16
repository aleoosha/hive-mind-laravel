<?php

use Aleoosha\HiveMind\Contracts\StateRepository;
use Aleoosha\HiveMind\Exceptions\HiveOvercapacityException;
use Aleoosha\HiveMind\Traits\AsHiveMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class TestOrder extends Model {
    use AsHiveMember;
    protected $fillable = ['name'];
}

test('it prevents model saving when hive is stressed', function () {
    Schema::create('test_orders', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    $mockRepo = Mockery::mock(StateRepository::class);
    $mockRepo->shouldReceive('getGlobalHealth')->andReturn(99);
    $this->app->instance(StateRepository::class, $mockRepo);

    try {
        TestOrder::create(['name' => 'iPhone 15']);
    } catch (HiveOvercapacityException $e) {
        expect($e->getHealth())->toBe(99)
            ->and($e->getMessage())->toContain('Current Hive Load: 99%');
        return;
    }

    $this->fail('Exception was not thrown');
});
