<?php

use Severite\Database\Seeders\XhprofReportSeeder;

beforeEach(function () {
    $this->seed(XhprofReportSeeder::class);
});

describe('test method of report controller', function () {
    test('test index method', function () {
         $response = $this->withHeaders([
            'X-Inertia' => 'true',
        ])->get(route('home'));

        $response->assertStatus(200)
             ->assertJson([
                 'component' => 'HomeView',
             ])
             ->assertJsonPath('props.reportList', fn($list) => count($list) > 0);
    });
});
