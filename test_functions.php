<?php

function getFahrzeugimAbschnitt_test (array $data, int $gbt_id) {
    for ($i = 0; $i < count($data); $i++) {
        if ($data[$i]->gbt_id == $gbt_id)
            $ret = intval($data[$i]->zugnummer);
    }
    return $ret;
}