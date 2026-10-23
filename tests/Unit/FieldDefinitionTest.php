<?php

use App\Support\Crud\FieldDefinition;

test('parser field CRUD membaca tipe dan modifier', function () {
    $fields = FieldDefinition::parseList('name:string:unique, description:text:nullable, active:boolean');

    expect($fields)->toHaveCount(3)
        ->and($fields[0]->name)->toBe('name')
        ->and($fields[0]->unique)->toBeTrue()
        ->and($fields[1]->nullable)->toBeTrue()
        ->and($fields[2]->type)->toBe('boolean');
});

test('parser field CRUD menolak field sistem dan tipe tidak dikenal', function (string $definition) {
    FieldDefinition::parseList($definition);
})->with(['id:string', 'name:money'])->throws(InvalidArgumentException::class);
