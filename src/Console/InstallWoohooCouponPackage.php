<?php

namespace Woohoo\GoapptivCoupon\Console;

use Illuminate\Console\Command;

class InstallWoohooCouponPackage extends Command {
    protected $signature = 'woohoo:install';

    protected $description = 'Install the Woohoo Coupon package';

    public function handle() {
        $this->info('Installing Woohoo Coupon package...');

        $this->info('Publishing configuration...');

        $this->call('vendor:publish', [
            '--provider' => "Woohoo\GoapptivCoupon\GoapptivCouponServiceProvider",
            '--tag' => "config"
        ]);

        $this->call('vendor:publish', [
            '--provider' => "Woohoo\GoapptivCoupon\GoapptivCouponServiceProvider",
            '--tag' => "migrations"
        ]);

        $this->info('Installed Woohoo Coupon package');
    }
}
