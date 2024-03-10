<?php

declare(strict_types=1);

namespace Brick\WeakmapPolyfill;

if (\PHP_MAJOR_VERSION === 7) {
    final class CycleWithDestructor
    {
        private \Closure $destructorFx;

        private \stdClass $cycleRef;

        public function __construct(\Closure $destructorFx)
        {
            $this->destructorFx = $destructorFx;
            $this->cycleRef = new \stdClass();
            $this->cycleRef->x = $this;
        }

        public function __destruct()
        {
            ($this->destructorFx)();
        }
    }
}
