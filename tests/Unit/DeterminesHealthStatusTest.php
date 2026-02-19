<?php

use App\Traits\DeterminesHealthStatus;
use App\Enums\LinkStatus;

it('applies rule precedence and returns correct LinkStatus for combinations', function () {
    $obj = new class {
        use DeterminesHealthStatus;

        public function callDetermine(int $ms, bool $ok, ?int $code = null)
        {
            return $this->determineHealthStatusFromResponse($ms, $ok, $code);
        }
    };

    expect($obj->callDetermine(1500, false))->toBe(LinkStatus::DOWN);
    expect($obj->callDetermine(1500, true))->toBe(LinkStatus::UNHEALTH);
    expect($obj->callDetermine(500, true))->toBe(LinkStatus::UP);
});
