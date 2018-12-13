<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class HandleUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The Google Drive Helper
     *
     * @var \App\Helpers\GDriver
     */
    protected $driver;

    /**
     * The URL of the file to upload
     *
     * @var string
     */
    protected $url;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($driver, $url)
    {
        $this->driver = $driver;
        $this->url = $url;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $status = $this->driver->remoteUpload($this->url);
        if(data_get($status, 'id', false)){
            return true;
        }
        return false;
    }
}
