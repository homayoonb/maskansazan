<?php
/*
    jDateTime Version 2.20
    https://jdf.scr.ir/jdf
*/

function tr_num($str, $mod = 'en', $mf = '٫'){
    $num_a = array('0','1','2','3','4','5','6','7','8','9','.');
    $key_a = array('۰','۱','۲','۳','۴','۵','۶','۷','۸','۹',$mf);
    return ($mod == 'fa') ? str_replace($num_a, $key_a, $str) : str_replace($key_a, $num_a, $str);
}

function jdate($format, $timestamp = '', $none = '', $time_zone = 'Asia/Tehran', $tr_num = 'fa'){
    if($time_zone != 'local') date_default_timezone_set(($time_zone == 'Asia/Tehran' or !$time_zone) ? 'Asia/Tehran' : $time_zone);
    $ts = ($timestamp == '') ? time() : tr_num($timestamp);
    $date = explode('_', date('H_i_j_n_O_P_s_w_Y', $ts));
    list($j_y, $j_m, $j_d) = gregorian_to_jalali($date[8], $date[3], $date[2]);
    $out = '';
    $len = strlen($format);
    for($i = 0; $i < $len; $i++){
        $sub = substr($format, $i, 1);
        if($sub == '\\'){
            $out .= substr($format, ++$i, 1);
            continue;
        }
        switch($sub){
            case 'Y': $out .= $j_y; break;
            case 'y': $out .= substr($j_y, 2, 2); break;
            case 'm': $out .= str_pad($j_m, 2, '0', STR_PAD_LEFT); break;
            case 'n': $out .= $j_m; break;
            case 'd': $out .= str_pad($j_d, 2, '0', STR_PAD_LEFT); break;
            case 'j': $out .= $j_d; break;
            case 'H': $out .= $date[0]; break;
            case 'i': $out .= $date[1]; break;
            case 's': $out .= $date[6]; break;
            default: $out .= $sub;
        }
    }
    return ($tr_num == 'fa') ? tr_num($out, 'fa') : $out;
}

function gregorian_to_jalali($g_y, $g_m, $g_d, $mod = ''){
    $g_d_m = array(0,31,($g_y % 4 == 0 && $g_y % 100 != 0) || ($g_y % 400 == 0) ? 29 : 28,31,30,31,30,31,31,30,31,30,31);
    $j_days_in_month = array(31,31,31,31,31,31,30,30,30,30,30,29);

    $gy = $g_y - 1600;
    $gm = $g_m - 1;
    $gd = $g_d - 1;

    $g_day_no = 365 * $gy + floor(($gy + 3) / 4) - floor(($gy + 99) / 100) + floor(($gy + 399) / 400);
    for($i = 0; $i < $gm; ++$i)
        $g_day_no += $g_d_m[$i + 1];
    $g_day_no += $gd;

    $j_day_no = $g_day_no - 79;
    $j_np = floor($j_day_no / 12053);
    $j_day_no %= 12053;

    $jy = 979 + 33 * $j_np + 4 * floor($j_day_no / 1461);
    $j_day_no %= 1461;

    if($j_day_no >= 366){
        $jy += floor(($j_day_no - 1) / 365);
        $j_day_no = ($j_day_no - 1) % 365;
    }

    for($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i)
        $j_day_no -= $j_days_in_month[$i];

    $jm = $i + 1;
    $jd = $j_day_no + 1;

    return ($mod == '') ? array($jy, $jm, $jd) : "$jy/$jm/$jd";
}
