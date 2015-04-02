<?php
namespace zyzo\MeteorDDP\enum;

class FrameStatus {
    const __default = 0;
    const NOT_VALID = 0;
    const MISSING_END = 1;
    const VALID    = 2;
    const EXCEED_END = 3;
    private $value;

    public function __construct($value) {
        $this->value = $value;
    }

    public function value() {
        return $this->value;
    }
    /**
     * @return string
     */
    public function __toString() {
        switch ($this->value) {
            case (self::NOT_VALID) :
                return 'not valid';
            case (self::MISSING_END) :
                return 'missing ending';
            case (self::VALID) :
                return 'valid';
            case (self::EXCEED_END) :
                return 'exceed ending';
        }
        return null;
    }
}