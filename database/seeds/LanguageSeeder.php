<?php

use DTApi\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /*customers*/
        $languages   = array();
        $languages[] = 'Albanska';
        $languages[] = 'Amhariska';
        $languages[] = 'Arabiska';
        $languages[] = 'Arameiska';
        $languages[] = 'Ardalani';
        $languages[] = 'Armeniska';
        $languages[] = 'Azari';
        $languages[] = 'Azerbajdzjanska';
        $languages[] = 'Badinani';
        $languages[] = 'Blin';
        $languages[] = 'Bosniska';
        $languages[] = 'Bulgariska';
        $languages[] = 'Danska';
        $languages[] = 'Dari';
        $languages[] = 'Engelska';
        $languages[] = 'Estniska';
        $languages[] = 'Feyli';
        $languages[] = 'Finska';
        $languages[] = 'Franska';
        $languages[] = 'Georgiska';
        $languages[] = 'Gorani';
        $languages[] = 'Grekiska';
        $languages[] = 'Hazaragi';
        $languages[] = 'Hebreiska';
        $languages[] = 'Hindi';
        $languages[] = 'Holländska';
        $languages[] = 'Italienska';
        $languages[] = 'Japanska';
        $languages[] = 'Kantonesiska';
        $languages[] = 'Kikongo';
        $languages[] = 'Kinyarwanda';
        $languages[] = 'Kirundi';
        $languages[] = 'Koreanska';
        $languages[] = 'Kroatiska';
        $languages[] = 'Kurmanji';
        $languages[] = 'Lettiska';
        $languages[] = 'Lingala';
        $languages[] = 'Litauiska';
        $languages[] = 'Makedonska';
        $languages[] = 'Mandarin';
        $languages[] = 'Marockanska';
        $languages[] = 'Montenegrinska';
        $languages[] = 'Nordafrikanska';
        $languages[] = 'Norska';
        $languages[] = 'Oromo';
        $languages[] = 'Pashto';
        $languages[] = 'Persiska';
        $languages[] = 'Polska';
        $languages[] = 'Portugisiska';
        $languages[] = 'Punjabi';
        $languages[] = 'Romani';
        $languages[] = 'Rumänska';
        $languages[] = 'Ryska';
        $languages[] = 'Serbiska';
        $languages[] = 'Serbokroatiska';
        $languages[] = 'Somaliska';
        $languages[] = 'Sorani';
        $languages[] = 'Spanska';
        $languages[] = 'Swahili';
        $languages[] = 'Syrianska';
        $languages[] = 'Thailändska';
        $languages[] = 'Tigre';
        $languages[] = 'Tigrinja';
        $languages[] = 'Turkiska';
        $languages[] = 'Tyska';
        $languages[] = 'Ukrainska';
        $languages[] = 'Ungerska';
        $languages[] = 'Urdu';
        $languages[] = 'Uzbekiska';
        $languages[] = 'Vietnamesiska';
        $languages[] = 'Wolof';
        foreach($languages as $language)
        {
            Language::create(['language'=>$language , 'type'=>'from' , 'active' => '1' ]);
        }

        //to languages
        Language::create(['language'=> "Swedish" , 'type'=>'to' , 'active' => '1' ]);

    }
}
