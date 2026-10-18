<?php

use Illuminate\Support\Facades\Artisan;
Artisan::command('energiciency:about', function () { $this->info('Energiciency - OEE, production and energy management'); });
