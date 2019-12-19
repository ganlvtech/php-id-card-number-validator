<?php

namespace IDCard;

use Exception;

class IDCard
{
    /** @var null|array 行政区划表 */
    static $region_names = null;
    /** @var null|array 假身份证号列表 */
    static $fake_id_numbers = null;

    /** @var string */
    protected $id_number;
    /** @var bool */
    protected $is_valid;
    /** @var bool|null */
    protected $is_fake_region = null;
    /** @var string|null */
    protected $region_name = null;

    public static function loadRegionNames()
    {
        if (static::$region_names === null) {
            static::$region_names = include __DIR__ . '/region_names.php';
        }
    }

    public static function loadFakeIdNumbers()
    {
        if (static::$fake_id_numbers === null) {
            static::$fake_id_numbers = include __DIR__ . '/fake_id_numbers.php';
        }
    }

    /**
     * 检查地区码是否合法
     *
     * @param string $region_code
     *
     * @return bool
     */
    public static function isRegionExists($region_code)
    {
        static::loadRegionNames();
        if (isset(static::$region_names[$region_code])) {
            return true;
        } elseif (!isset(static::$region_names[substr($region_code, 0, 2) . '0000'])) {
            return false;
        } else {
            // 特殊处理：检查前 5 位
            $county_code_prefix = substr($region_code, 0, 5);
            for ($i = 0; $i < 9; $i++) {
                if (isset(static::$region_names[$county_code_prefix . $i])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 获取地区码对应的地区名
     *
     * @param string $region_code
     *
     * @return string
     * @link https://github.com/cn/GB2260
     */
    public static function transformRegionName($region_code)
    {
        static::loadRegionNames();
        $prefecture_code = substr($region_code, 0, 4) . '00';
        $province_code = substr($region_code, 0, 2) . '0000';
        $region_name = isset(static::$region_names[$region_code]) ? static::$region_names[$region_code] : '未知';
        $prefecture_name = isset(static::$region_names[$prefecture_code]) ? static::$region_names[$prefecture_code] . ' ' : '';
        $province_name = isset(static::$region_names[$province_code]) ? static::$region_names[$province_code] : '未知';
        return "{$province_name} {$prefecture_name}{$region_name}";
    }

    /**
     * 检查身份证号是否合法
     */
    protected static function checkIdNumber($id_number)
    {
        if (1 !== preg_match('/^\d{17}[0-9X]$/', $id_number)) {
            return false;
        }
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        $verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        $checksum = 0;
        for ($i = 0; $i < 17; $i++) {
            $checksum += (int)substr($id_number, $i, 1) * $factor[$i];
        }
        $mod = $checksum % 11;
        $verify_number = $verify_number_list[$mod];
        return $verify_number === substr($id_number, 17, 1);
    }

    /**
     * @param string $id_number
     */
    public function __construct($id_number)
    {
        $this->id_number = $id_number;
        $this->is_valid = static::checkIdNumber($id_number);
    }

    /**
     * 检查身份证号是否合法
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->is_valid;
    }

    /**
     * 必须合法，否则抛出异常
     */
    protected function mustValid()
    {
        if (!$this->is_valid) {
            throw new InvalidIDNumberException($this->id_number);
        }
    }

    /**
     * @return string
     */
    public function getIdNumber()
    {
        return $this->id_number;
    }

    /**
     * 获取身份证号中的地区码
     *
     * @return false|string
     * @throws \IDCard\InvalidIDNumberException
     */
    public function getRegionCode()
    {
        $this->mustValid();
        return substr($this->id_number, 0, 6);
    }

    /**
     * 获取身份证号中的地区码对应的地区名
     *
     * @return string
     * @throws \IDCard\InvalidIDNumberException
     */
    public function getRegionName()
    {
        return static::transformRegionName($this->getRegionCode());
    }

    /**
     * 判断身份证号中的地区码是否是假地区
     *
     * @return bool
     * @throws \IDCard\InvalidIDNumberException
     */
    public function isFakeRegion()
    {
        return !self::isRegionExists($this->getRegionCode());
    }

    /**
     * 获取身份证号中的生日时间戳
     *
     * @return false|int
     * @throws \IDCard\InvalidIDNumberException
     */
    public function getBirthdayTimestamp()
    {
        $this->mustValid();
        return strtotime(substr($this->id_number, 6, 8));
    }

    /**
     * 根据字典判断是否是伪造的身份证
     *
     * @return false|int
     * @throws \IDCard\InvalidIDNumberException
     */
    public function isFakeIdNumber()
    {
        static::loadFakeIdNumbers();
        $this->mustValid();
        return in_array($this->id_number, static::$fake_id_numbers);
    }
}

class InvalidIDNumberException extends Exception
{
    public function __construct($id_number)
    {
        parent::__construct("身份证号码不正确: {$id_number}");
    }
}
