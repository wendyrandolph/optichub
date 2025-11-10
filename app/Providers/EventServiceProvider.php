<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
  /** @var array<class-string, array<int, class-string>> */
  protected $listen = [
    \Illuminate\Mail\Events\MessageSent::class => [
      \App\Listeners\LogSentEmail::class,
    ],
  ];

  public function boot(): void
  {
    //
  }
}
