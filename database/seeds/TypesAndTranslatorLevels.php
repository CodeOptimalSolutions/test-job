<?php

use Illuminate\Database\Seeder;

class TypesAndTranslatorLevels extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DTApi\Models\Type::create([
            'title' => 'Paid',
            'code' => 'paid',
        ]);

        \DTApi\Models\Type::create([
            'title' => 'SSM',
            'code' => 'ssm',
        ]);

        \DTApi\Models\Type::create([
            'title' => 'RWS',
            'code' => 'rws',
        ]);

        \DTApi\Models\TranslatorLevel::create([
            'title' => 'Layman',
            'code' => 'layman'
        ]);

        \DTApi\Models\TranslatorLevel::create([
            'title' => 'Certified',
            'code' => 'certified'
        ]);

        \DTApi\Models\TranslatorLevel::create([
            'title' => 'Read Translation courses',
            'code' => 'read_courses'
        ]);

        \DTApi\Models\TranslatorLevel::create([
            'title' => 'Specialised',
            'code' => 'specialised'
        ]);
    }
}
