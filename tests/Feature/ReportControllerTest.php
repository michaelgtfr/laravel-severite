<?php

use Database\Seeders\XhprofReportSeeder;

beforeEach(function () {
    $this->seed(XhprofReportSeeder::class);
});

describe('test method of report controller', function () {
    test('test index method', function () {
        $response = $this->get(route('home'));

        $response->assertInertia(
            fn ($page) => $page
            ->component('HomeView')
            ->has('reportList')
        );
    });
});
