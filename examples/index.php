<?php
//注释111
use IDCard\IDCard;

require __DIR__ . '/../src/IDCard.php';

$fake_id_numbers = include __DIR__ . '/../src/fake_id_numbers.php';

foreach ($fake_id_numbers as $value) {
    $id_card = new IDCard($value);
    if (!$id_card->isValid()) {
        echo "$value 身份证号码不正确\n";
    } else {
        if ($id_card->isFakeRegion()) {
            echo "$value 地区不正确: {$id_card->getRegionName()}\n";
        }
        if (!$id_card->isFakeIdNumber()) {
            echo "$value 不在假身份证字典中\n";
        }
    }
}
