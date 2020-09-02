<?php

namespace App\Console;

use App\Console\Commands\Test\Sam;
use App\Console\Commands\Apollo\Apollo;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [

        //=============测试区域=================
        Sam::class,

        Apollo::class
    ];

    /**
     * @todo  友情提示，我们坚决抵制Lumen自带的定时器，请使用中台组提供的分布式定时任务，谢谢合作
     * @author sam@2020-07-26 11:09:54
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }
}
