<?php

declare(strict_types=1);

if (! class_exists('WeakMap')) {
    /**
     * A polyfill for the upcoming WeakMap implementation in PHP 8, based on WeakReference in PHP 7.4.
     * The polyfill aims to be 100% compatible with the native WeakMap implementation, feature-wise.
     *
     * The difference between the native PHP 8 implementation and the polyfill is when memory is reclaimed: with the
     * native WeakMap, the memory used by the data attached to an object is reclaimed as soon as the object is
     * destroyed. With the polyfill, the memory is reclaimed only when new operations are performed on the WeakMap:
     *
     * - count() will reclaim memory immediately
     * - offsetX() methods will reclaim memory every 100 calls
     *
     * This is a reasonable trade-off between performance and memory usage, but keep in mind that the polyfill will
     * always be slower, and consume more memory, than the native implementation.
     */
    final class WeakMap implements ArrayAccess, Countable
    {
        /**
         * The number of offsetX() calls after which housekeeping will be performed.
         * Housekeeping consists in freeing memory associated with destroyed objects.
         */
        private const HOUSEKEEPING_EVERY = 100;

        /**
         * A map of spl_object_id to WeakReference objects. This must be kept in sync with $values.
         *
         * @var WeakReference[]
         */
        private array $weakRefs = [];

        /**
         * A map of spl_object_id to values. This must be kept in sync with $weakRefs.
         */
        private array $values = [];

        /**
         * The number of offsetX() calls since the last housekeeping.
         */
        private int $counter = 0;

        public function offsetExists($object) : bool
        {
            $this->housekeeping();

            $id = spl_object_id($object);

            if (isset($this->weakRefs[$id])) {
                if ($this->weakRefs[$id]->get() !== null) {
                    return true;
                }

                // This entry belongs to a destroyed object.
                unset(
                    $this->weakRefs[$id],
                    $this->values[$id]
                );
            }

            return false;
        }

        public function offsetGet($object)
        {
            $this->housekeeping();

            $id = spl_object_id($object);

            if (isset($this->weakRefs[$id])) {
                if ($this->weakRefs[$id]->get() !== null) {
                    return $this->values[$id];
                }

                // This entry belongs to a destroyed object.
                unset(
                    $this->weakRefs[$id],
                    $this->values[$id]
                );
            }

            throw new Error(sprintf('Object %s#%d not contained in WeakMap', get_class($object), $id));
        }

        public function offsetSet($object, $value) : void
        {
            $this->housekeeping();

            $id = spl_object_id($object);

            $this->weakRefs[$id] = WeakReference::create($object);
            $this->values[$id]   = $value;
        }

        public function offsetUnset($object) : void
        {
            $this->housekeeping();

            $id = spl_object_id($object);

            unset(
                $this->weakRefs[$id],
                $this->values[$id]
            );
        }

        public function count() : int
        {
            $count = 0;

            foreach ($this->weakRefs as $id => $weakRef) {
                if ($weakRef->get() !== null) {
                    $count++;
                } else {
                    unset(
                        $this->weakRefs[$id],
                        $this->values[$id]
                    );
                }
            }

            $this->counter = 0;

            return $count;
        }

        private function housekeeping() : void
        {
            if (++$this->counter === self::HOUSEKEEPING_EVERY) {
                foreach ($this->weakRefs as $id => $weakRef) {
                    if ($weakRef->get() === null) {
                        unset(
                            $this->weakRefs[$id],
                            $this->values[$id]
                        );
                    }
                }

                $this->counter = 0;
            }
        }
    }
}
